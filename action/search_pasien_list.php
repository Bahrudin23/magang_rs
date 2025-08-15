<?php
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode([]); exit; }

$sql = "SELECT DISTINCT p.no_rkm_medis, p.nm_pasien
        FROM pasien p
        JOIN reg_periksa rp ON p.no_rkm_medis = rp.no_rkm_medis
        WHERE
            rp.kd_poli = 'IGDK' AND 
            (p.no_rkm_medis LIKE ? OR p.nm_pasien LIKE ?)
        ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC
        LIMIT 20";

$like = "%$q%";
$stmt = $pdo->prepare($sql);
$stmt->execute([$like, $like]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));