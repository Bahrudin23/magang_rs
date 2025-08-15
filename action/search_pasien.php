<?php
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$no = $_GET['no'] ?? '';
if ($no === '') { echo json_encode(null); exit; }

$sql = "SELECT rp.no_rawat, rp.no_rkm_medis, p.nm_pasien
        FROM reg_periksa rp
        JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
        WHERE rp.no_rkm_medis = ?
        ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC, rp.no_rawat DESC
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$no]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));