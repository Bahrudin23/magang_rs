<?php
require_once __DIR__ . '/../config/db.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /magang/public/asesmen.php');
    exit;
}

function getNumericOrNull(string $key) {
    $v = trim($_POST[$key] ?? '');
    if ($v === '') {
        return null;
    }
    return is_numeric($v) ? $v : null;
}

$no_rawat    = trim($_POST['no_rawat']    ?? '');
$nipRaw = trim($_POST['nip'] ?? '');
$nip    = ($nipRaw === '') ? null : $nipRaw; 
$tgl_jam_raw = trim($_POST['tgl_jam']     ?? '');

// === FIELD VITAL SIGN ===
$suhu      = getNumericOrNull('suhu');
$tensi     = trim($_POST['tensi']    ?? '') ?: null;
$nadi      = getNumericOrNull('nadi');
$respirasi = getNumericOrNull('respirasi');
$tinggi    = getNumericOrNull('tinggi');
$berat     = getNumericOrNull('berat');
$spo2      = getNumericOrNull('spo2');
$gcs       = trim($_POST['gcs']      ?? '') ?: null;

// === FIELD ASESMEN LAIN ===
$kesadaran   = trim($_POST['kesadaran']  ?? '');
$keluhan     = trim($_POST['keluhan']    ?? '');
$pemeriksaan = trim($_POST['pemeriksaan']?? '');
$alergi      = trim($_POST['alergi']     ?? '');
$penilaian   = trim($_POST['penilaian']  ?? '');
$rtl         = trim($_POST['rtl']        ?? '');
$instruksi   = trim($_POST['instruksi']  ?? '');
$evaluasi    = trim($_POST['evaluasi']   ?? '');

if (empty($_POST['tensi']) || empty($_POST['spo2'])) {
    die("Tensi dan SpO2 wajib diisi!");
}

if ($no_rawat === '' || $tgl_jam_raw === '') {
    header('Location: /magang/public/asesmen.php?no_rawat=' . urlencode($no_rawat) . '&msg=invalid');
    exit;
}

$t = DateTime::createFromFormat('Y-m-d\TH:i:s', $tgl_jam_raw);
if (!$t) {
    header('Location: /magang/public/asesmen.php?no_rawat=' . urlencode($no_rawat) . '&msg=badtime');
    exit;
}
$tgl = $t->format('Y-m-d');
$jam = $t->format('H:i:s');

if ($nip !== null) {
    $cek = $pdo->prepare('SELECT 1 FROM pegawai WHERE nik = ? LIMIT 1');
    $cek->execute([$nip]);
    if (!$cek->fetch()) {
        header('Location: /magang/public/asesmen.php?no_rawat=' . urlencode($no_rawat) . '&msg=badnip');
        exit;
    }
}

try {
    $ins = $pdo->prepare('INSERT INTO pemeriksaan_ranap
        (no_rawat, tgl_perawatan, jam_rawat,
        suhu_tubuh, tensi, nadi, respirasi, tinggi, berat, spo2, gcs,
        kesadaran, keluhan, pemeriksaan, alergi,
        penilaian, rtl, instruksi, evaluasi, nip)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $ins->execute([
        $no_rawat, $tgl, $jam,
        $suhu, $tensi, $nadi, $respirasi, $tinggi, $berat, $spo2, $gcs,
        $kesadaran, $keluhan, $pemeriksaan, $alergi,
        $penilaian, $rtl, $instruksi, $evaluasi, $nip
    ]);

    header('Location: /magang/public/asesmen.php?no_rkm_medis=' . urlencode($no_rkm_medis) . '&msg=saved');
    exit;

} catch (PDOException $e) {
    header('Location: /magang/public/asesmen.php?no_rawat=' . urlencode($no_rawat) . '&msg=error');
    exit;
}
