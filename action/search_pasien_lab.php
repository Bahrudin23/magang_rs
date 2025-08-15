<?php
// File: magang/action/search_pasien_lab.php
header('Content-Type: application/json; charset=utf-8');

try {
    // Path dari 'action' ke 'config' sudah benar
    require_once __DIR__ . '/../config/db.php';

    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode([]);
        exit;
    }

    // Mengambil pasien rawat inap yang masih aktif, sama seperti di list_pasien.php
    $sql = "SELECT p.no_rkm_medis, p.nm_pasien FROM kamar_inap ki
            JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
            JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            WHERE ki.tgl_keluar = '0000-00-00' 
            AND (p.no_rkm_medis LIKE :kw OR p.nm_pasien LIKE :kw)
            GROUP BY p.no_rkm_medis ORDER BY p.nm_pasien ASC LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':kw' => "%$q%"]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Server Error: ' . $e->getMessage()]);
}