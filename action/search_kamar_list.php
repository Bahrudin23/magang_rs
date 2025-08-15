<?php
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode([]); exit; }

$sql = "SELECT k.kd_kamar, k.kd_bangsal, b.nm_bangsal, k.status, k.trf_kamar
        FROM kamar k
        JOIN bangsal b ON b.kd_bangsal = k.kd_bangsal
        WHERE (k.kd_kamar LIKE ? OR b.nm_bangsal LIKE ?) AND k.status = 'KOSONG'
        ORDER BY k.kd_kamar
        LIMIT 20";
        
$like = "%$q%";
$stmt = $pdo->prepare($sql);
$stmt->execute([$like, $like]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));