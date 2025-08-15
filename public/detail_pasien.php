<?php
require_once __DIR__ . '/../config/db.php';
date_default_timezone_set('Asia/Jakarta');

function umurDariTanggal(string $tgl_lahir, $patokan = 'today'): string {
    if (!$tgl_lahir || $tgl_lahir === '0000-00-00') return '-';
    try {
        $lahir = new DateTime($tgl_lahir);
        $ref   = is_string($patokan) ? new DateTime($patokan) : $patokan;
        $ref->setTime(0,0,0);

        $d = $lahir->diff($ref);

        return "{$d->y} Th {$d->m} Bl {$d->d} Hr";
    } catch (Exception $e) {
        return '-';
    }
}

$rm = $NO_RKM_MEDIS ?? ($_GET['no_rkm_medis'] ?? '');
if ($rm === '') { echo '<div class="alert alert-warning">No. RM tidak valid.</div>'; return; }

// Ambil data pasien
$sp = $pdo->prepare("SELECT * FROM pasien WHERE no_rkm_medis = ? LIMIT 1");
$sp->execute([$rm]);
$ps = $sp->fetch(PDO::FETCH_ASSOC);
if (!$ps) { echo '<div class="alert alert-warning">Pasien tidak ditemukan.</div>'; return; }

// Kunjungan terakhir
$rp = $pdo->prepare("SELECT tgl_registrasi, jam_reg, kd_pj
    FROM reg_periksa
    WHERE no_rkm_medis = ?
    ORDER BY tgl_registrasi DESC, jam_reg DESC
    LIMIT 1
");
$rp->execute([$rm]);
$reg = $rp->fetch(PDO::FETCH_ASSOC);

$akses = '-';
$last_visit = '-';
if ($reg) {
    $last_visit = $reg['tgl_registrasi'].' '.$reg['jam_reg'];
    if (!empty($reg['kd_pj'])) {
        $pj = $pdo->prepare("SELECT png_jawab FROM penjab WHERE kd_pj = ? LIMIT 1");
        if ($pj->execute([$reg['kd_pj']]) && ($row = $pj->fetch(PDO::FETCH_ASSOC))) {
        $akses = $row['png_jawab'];
        } else {
        $akses = $reg['kd_pj'];
        }
    }
}

// Format TTL
$ttl = trim(($ps['tmp_lahir'] ?? '').', '.($ps['tgl_lahir'] ?? ''));
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
        <div class="card-header bg-white">
            <strong>Identitas Pasien</strong>
        </div>
        <div class="card-body">
            <div class="row gy-2">
            <div class="col-5 col-md-4 label">Nama</div>
            <div class="col-7 col-md-8 value text-uppercase">: <?= htmlspecialchars($ps['nm_pasien']) ?></div>

            <div class="col-5 col-md-4 label">Jenis Kelamin</div>
            <div class="col-7 col-md-8 value">: <?= htmlspecialchars($ps['jk']) ?></div>

            <div class="col-5 col-md-4 label">TTL</div>
            <div class="col-7 col-md-8 value">: <?= htmlspecialchars($ttl) ?></div>

            <div class="col-5 col-md-4 label">Umur</div>
            <div class="col-7 col-md-8 value">: <?= umurDariTanggal($ps['tgl_lahir']) ?>
            </div>

            <div class="col-5 col-md-4 label">Gol. Darah</div>
            <div class="col-7 col-md-8 value">: <?= htmlspecialchars($ps['gol_darah'] ?? '-') ?></div>

            <div class="col-5 col-md-4 label">Akses</div>
            <div class="col-7 col-md-8 value">: <?= htmlspecialchars($akses) ?></span></div>

            <div class="col-5 col-md-4 label">Agama</div>
            <div class="col-7 col-md-8 value">: <?= htmlspecialchars($ps['agama'] ?? '-') ?></div>

            <div class="col-5 col-md-4 label">Alamat</div>
            <div class="col-7 col-md-8 value">: <?= htmlspecialchars($ps['alamat'] ?? '-') ?></div>

            <div class="col-5 col-md-4 label">Tgl Daftar</div>
            <div class="col-7 col-md-8 value">: <?= htmlspecialchars($ps['tgl_daftar'] ?? '-') ?></div>

            <div class="col-5 col-md-4 label">No. Tlpn</div>
            <div class="col-7 col-md-8 value">: <?= htmlspecialchars($ps['no_tlp'] ?? '-') ?></div>
            </div>
        </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
        <div class="card-header bg-white">
            <strong>Ringkasan Kunjungan</strong>
        </div>
        <div class="card-body d-flex flex-column">
            <div class="small text-muted mb-1">Kunjungan terakhir</div>
            <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar2-week fs-4 text-primary"></i>
            <div><div class="fw-semibold"><?= htmlspecialchars($last_visit) ?></div></div>
            </div>
            <div class="small text-muted mt-3">Akses/penjamin</div>
            <div class="fw-semibold"><?= htmlspecialchars($akses) ?></div>

            <div class="mt-auto">
            <a class="btn btn-primary w-100 mt-4" href="asesmen.php?no_rkm_medis=<?= urlencode($rm) ?>">
                <i class="bi bi-clipboard-pulse"></i> Lihat Riwayat Asesmen
            </a>
            </div>
        </div>
        </div>
    </div>
</div>
