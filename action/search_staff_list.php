<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
if(strlen($q)<2) exit(json_encode([]));

$sql = "SELECT p.nip, p.nama, p.kd_jbtn AS kode, j.nm_jbtn AS jabatan, 'petugas' AS jenis
FROM petugas p
JOIN jabatan j ON j.kd_jbtn = p.kd_jbtn
WHERE p.nip LIKE ? OR p.nama LIKE ?
UNION
SELECT d.kd_dokter, d.nm_dokter, d.kd_sps AS kode, s.nm_sps AS jabatan, 'dokter' AS jenis
FROM dokter d
LEFT JOIN spesialis s ON s.kd_sps = d.kd_sps
WHERE d.kd_dokter LIKE ? OR d.nm_dokter LIKE ?
LIMIT 20
";
$st = $pdo->prepare($sql);
$like = "%$q%";
$st->execute([$like,$like,$like,$like]);
$data = $st->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
