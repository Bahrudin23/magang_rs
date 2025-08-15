<?php
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$kd = $_GET['kd'] ?? '';
if ($kd === '') { echo json_encode(null); exit; }

$sql = "SELECT k.kd_kamar, k.kelas, k.kd_bangsal, k.trf_kamar, k.status,
                b.nm_bangsal
        FROM kamar k
        JOIN bangsal b ON b.kd_bangsal = k.kd_bangsal
        WHERE k.kd_kamar = ?";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$kd]);

echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));