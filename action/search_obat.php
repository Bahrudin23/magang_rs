<?php
// File: magang/action/search_obat.php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/db.php';

    $keyword = trim($_GET['keyword'] ?? '');
    $kd_pj   = trim($_GET['pj'] ?? 'UMU');

    if (strlen($keyword) < 3) {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT 
                d.kode_brng, 
                d.nama_brng, 
                IFNULL((SELECT SUM(stok) FROM gudangbarang WHERE kode_brng = d.kode_brng), 0) AS stok,
                d.dasar AS harga_beli
            FROM databarang d
            WHERE d.status = '1' AND d.nama_brng LIKE :keyword
            LIMIT 15";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyword' => "%{$keyword}%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($results as $key => $item) {
        $harga_beli = (float)($item['harga_beli'] ?? 0);

        $markup = 0.40;
        if (stripos($kd_pj, 'BPJ') !== false) {
            $markup = 0.20;
        }

        $harga_jual = $harga_beli * (1 + $markup);

        $results[$key]['harga_satuan'] = (int)round($harga_jual);
        unset($results[$key]['harga_beli']);
    }

    echo json_encode($results);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>