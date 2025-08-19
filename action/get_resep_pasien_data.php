<?php
// File: magang/action/get_resep_pasien_data.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

function hitungUmur(string $tgl_lahir): string {
    if (!$tgl_lahir || $tgl_lahir === '0000-00-00') return '-';
    try {
        $lahir = new DateTime($tgl_lahir);
        $today = new DateTime('today');
        $umur = $lahir->diff($today);
        return "{$umur->y} Th {$umur->m} Bl {$umur->d} Hr";
    } catch (Exception $e) { return '-'; }
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

// Query ini mengambil SEMUA data yang kita butuhkan dalam satu kali jalan
$sql = "SELECT 
            p.nm_pasien, p.jk, p.tgl_lahir, 
            d.kd_dokter,
            rp.no_rawat, rp.kd_pj
        FROM kamar_inap ki
        JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
        JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        JOIN dokter d ON rp.kd_dokter = d.kd_dokter
        WHERE 
            ki.stts_pulang = '-' AND 
            (p.no_rkm_medis LIKE :kw OR p.nm_pasien LIKE :kw)
        ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC 
        LIMIT 10";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':kw' => "%$q%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Proses data untuk menambahkan informasi yang sudah diformat
    foreach($results as $i => $row) {
        $results[$i]['umur'] = hitungUmur($row['tgl_lahir']);
        $results[$i]['tgl_lahir_formatted'] = date("d-m-Y", strtotime($row['tgl_lahir']));
        $results[$i]['jk_formatted'] = ($row['jk'] == 'L' ? 'Laki-laki' : 'Perempuan');
    }

    echo json_encode($results);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>