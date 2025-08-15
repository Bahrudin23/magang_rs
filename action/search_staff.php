<?php
require_once '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$nip = trim($_GET['nip'] ?? '');
if(!$nip){ echo json_encode(null); exit; }

$sql = "SELECT p.nip, p.nama, j.nm_jbtn AS jabatan, 'petugas' AS jenis
FROM petugas p
LEFT JOIN jabatan j ON j.kd_jbtn = p.kd_jbtn
WHERE p.nip = ?
UNION
SELECT d.kd_dokter, d.nm_dokter, s.nm_sps AS jabatan, 'dokter' AS jenis
FROM dokter d
LEFT JOIN spesialis s ON s.kd_sps = d.kd_sps
WHERE d.kd_dokter = ?
LIMIT 1
";
$st = $pdo->prepare($sql);
$st->execute([$nip, $nip]);
echo json_encode($st->fetch(PDO::FETCH_ASSOC) ?: null);
