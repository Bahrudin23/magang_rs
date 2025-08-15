<?php
// File: magang/action/simpan_permintaan_lab.php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/permintaan_lab.php');
    exit;
}

$no_rawat           = trim($_POST['no_rawat'] ?? '');
$kd_dokter          = trim($_POST['kd_dokter'] ?? '');
$diagnosa           = trim($_POST['diagnosa'] ?? '');
$informasi_tambahan = trim($_POST['info'] ?? '');
$tgl_permintaan     = date('Y-m-d');
$jam_permintaan     = date('H:i:s');

if (empty($no_rawat) || empty($kd_dokter)) {
    header('Location: ../public/permintaan_lab.php?status=gagal&pesan=Data_pasien_atau_dokter_tidak_lengkap');
    exit;
}

try {
    $sql = "INSERT INTO permintaan_lab (no_rawat, tgl_permintaan, jam_permintaan, kd_dokter_peminta, diagnosa_klinis, informasi_tambahan) 
            VALUES (:no_rawat, :tgl, :jam, :kd_dokter, :diagnosa, :info)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':no_rawat' => $no_rawat,
        ':tgl' => $tgl_permintaan,
        ':jam' => $jam_permintaan,
        ':kd_dokter' => $kd_dokter,
        ':diagnosa' => $diagnosa,
        ':info' => $informasi_tambahan
    ]);
    header('Location: ../public/permintaan_lab.php?status=sukses');
    exit;
} catch (PDOException $e) {
    error_log("Gagal simpan permintaan lab: " . $e->getMessage());
    header('Location: ../public/permintaan_lab.php?status=gagal&pesan=DB_Error');
    exit;
}