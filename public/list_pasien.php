<?php
session_start();
if (empty($_SESSION['role'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../config/db.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Koneksi database ($pdo) tidak ditemukan. Cek config/db.php');
}

// Ambil data pasien rawat inap yang belum keluar
$sql = "SELECT rp.no_rkm_medis, ki.no_rawat, ki.kd_kamar, p.nm_pasien, 
            rp.umurdaftar, rp.sttsumur, b.nm_bangsal, k.kelas,
            ki.diagnosa_awal, d.nm_dokter, pj.png_jawab, ki.tgl_masuk, 
            ki.jam_masuk
        FROM kamar_inap ki
        JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
        JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        JOIN dokter d ON rp.kd_dokter = d.kd_dokter
        JOIN penjab pj ON rp.kd_pj = pj.kd_pj
        JOIN kamar k ON ki.kd_kamar = k.kd_kamar
        JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
        WHERE (ki.tgl_keluar IS NULL OR ki.tgl_keluar = '0000-00-00' OR ki.stts_pulang IS NULL OR ki.stts_pulang = '-')";
$params = [];

if (($_SESSION['role'] ?? '') === 'dokter') {
    $sql .= " AND rp.kd_dokter = :dok";
    $params[':dok'] = $_SESSION['kd_dokter'];
}

$sql .= " ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC";

$stmt  = $pdo->prepare($sql);
$stmt->execute($params);
$rows  = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Ambil data kamar kosong
$qKamar = $pdo->query("SELECT k.kd_kamar, b.nm_bangsal, k.kelas
                        FROM kamar k
                        JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
                        WHERE k.status = 'KOSONG'");
$kamarKosong = $qKamar->fetchAll(PDO::FETCH_ASSOC);

require __DIR__.'/partials/header.php'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>E-Ranap RS UNIPDU MEDIKA</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
    .fab-add{
        position: fixed;
        right: 22px;
        bottom: calc(22px + env(safe-area-inset-bottom));
        width: 56px; height: 56px;
        border-radius: 50%;
        background: #0d6efd; color: #fff;
        box-shadow: 0 8px 16px rgba(0,0,0,.2);
        z-index: 1049;
        display: grid;
        place-items: center;
        padding: 0;
        border: 0;
    }
    .fab-add svg{
        width: 28px; height: 28px;
        display: block;
    }
    .fab-add:hover{ transform: translateY(-1px); }
    </style>
</head>
<body>

<div class="container">
    <h4 class="mb-3">Daftar Pasien Rawat Inap</h4>
    <table class="table table-bordered table-striped">
        <thead class="table-dark text-center">
            <tr>
                <th>No</th>
                <th>Nama (Usia)</th>
                <th>Bangsal (Kelas)</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Diagnosa</th>
                <th>Dokter</th>
                <th>Askes</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rows)): 
                $no = 1;
                foreach ($rows as $row):
                    $id_modal = str_replace(['/', ':', ' '], '_', $row['no_rawat']);
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= $row['nm_pasien'] . ' (' . $row['umurdaftar'] . ' ' . $row['sttsumur'] . ')' ?></td>
                <td><?= $row['kd_kamar'].' : '. $row['nm_bangsal'] . ' (' . $row['kelas'] . ')' ?></td>
                <td class="text-center"><?= $row['tgl_masuk'] ?></td>
                <td class="text-center"><?= $row['jam_masuk'] ?></td>
                <td><?= $row['diagnosa_awal'] ?></td>
                <td><?= $row['nm_dokter'] ?></td>
                <td><?= $row['png_jawab'] ?></td>
                <td class="text-center">
                    <a href="detail.php?no_rkm_medis=<?= $row['no_rkm_medis'] ?>" class="btn btn-info btn-sm">Detail</a>
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $id_modal ?>">Edit</button>
                </td>
            </tr>


            <!-- Modal Edit -->
                <div class="modal fade" id="editModal<?= $id_modal ?>" tabindex="-1" aria-labelledby="editLabel<?= $id_modal ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                    <form action="proses_edit.php" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editLabel<?= $id_modal ?>">Edit Data Rawat Inap</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="no_rawat" value="<?= $row['no_rawat'] ?>">

                            <div class="mb-3">
                                <label>Kamar</label>
                                <select name="kd_kamar" class="form-select" required>
                                    <option value="">-- Pilih Kamar --</option>
                                    <?php foreach ($kamarKosong as $k): ?>
                                        <option value="<?= $k['kd_kamar'] ?>"><?= '('.$k['kd_kamar'].')'.$k['nm_bangsal'] . ' (' . $k['kelas'] . ')' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>Tanggal Masuk</label>
                                <input type="text" name="tgl_masuk" class="form-control" value="<?= $row['tgl_masuk'] ?>" required>
                            </div>

                            <div class="mb-3">
                                <label>Jam Masuk</label>
                                <input type="text" name="jam_masuk" class="form-control" value="<?= $row['jam_masuk'] ?>" required>
                            </div>

                            <div class="mb-3">
                                <label>Diagnosa</label>
                                <textarea name="diagnosa_awal" class="form-control" rows="3" required><?= $row['diagnosa_awal'] ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </form>
                    </div>
                </div>
                </div>


            <?php endforeach; else: ?>
                <tr><td colspan="9" class="text-center">Tidak ada data pasien rawat inap.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <a href="inpt_kmr_pasien.php" class="fab-add" aria-label="Tambah pasien">
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 5v14M5 12h14"
            fill="none" stroke="currentColor" stroke-width="2.5"
            stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    </a>
<script src="js/bootstrap.bundle.min.js"></script>

</body>
</html>
