<?php
require_once __DIR__ . '/../config/db.php';

$q = trim($_GET['q'] ?? '');

$sql = "SELECT rp1.no_rawat, rp1.no_rkm_medis, p.nm_pasien
FROM reg_periksa rp1
JOIN pasien p ON p.no_rkm_medis = rp1.no_rkm_medis
JOIN (
    SELECT no_rkm_medis, MAX(CONCAT(tgl_registrasi,' ',jam_reg)) AS maxdt
    FROM reg_periksa
    GROUP BY no_rkm_medis
) last ON last.no_rkm_medis = rp1.no_rkm_medis
      AND CONCAT(rp1.tgl_registrasi,' ',rp1.jam_reg) = last.maxdt
WHERE (p.nm_pasien LIKE :kw OR rp1.no_rkm_medis LIKE :kw OR rp1.no_rawat LIKE :kw)
ORDER BY last.maxdt DESC
LIMIT 20
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':kw' => "%{$q}%"]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($rows);
