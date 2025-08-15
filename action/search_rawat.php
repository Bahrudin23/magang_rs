<?php
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$no_rawat = $_GET['no'] ?? '';
if ($no_rawat === '') { echo json_encode(null); exit; }

$sql = "SELECT rp.no_rawat, rp.no_rkm_medis, p.nm_pasien
        FROM reg_periksa rp
        JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
        WHERE rp.no_rawat = ?
        LIMIT 1";
$st = $pdo->prepare($sql);
$st->execute([$no_rawat]);

echo json_encode($st->fetch(PDO::FETCH_ASSOC));
