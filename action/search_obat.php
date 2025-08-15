<?php
// File: magang/action/search_obat.php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/db.php';

    $keyword = trim($_GET['keyword'] ?? '');
    if (strlen($keyword) < 3) {
        echo json_encode([]);
        exit;
    }

    // Query untuk mencari obat di tabel databarang
    // Asumsi: tabel obat bernama 'databarang' dan punya kolom 'kode_brng', 'nama_brng', 'stok', 'dasar' (harga)
    $sql = "SELECT 
                kode_brng, 
                nama_brng, 
                stok, 
                dasar as harga
            FROM databarang
            WHERE 
                status = '1' AND 
                stok > 0 AND
                nama_brng LIKE :keyword
            LIMIT 15";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyword' => "%$keyword%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}