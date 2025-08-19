<?php
// File: magang/action/generate_no_resep.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

try {
    $tanggal_sekarang = date('Y-m-d');
    $prefix = date('Ymd'); // Format YYYYMMDD, contoh: 20250815

    // Cari nomor resep dengan awalan tanggal hari ini yang paling besar
    $sql = "SELECT MAX(no_resep) as no_resep_terakhir 
            FROM resep_obat 
            WHERE no_resep LIKE :prefix";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':prefix' => $prefix . '%']);
    $hasil = $stmt->fetch(PDO::FETCH_ASSOC);

    $nomor_urut = 1;
    if ($hasil && !empty($hasil['no_resep_terakhir'])) {
        // Ambil 4 digit terakhir, ubah ke angka, lalu tambah 1
        $nomor_urut = (int)substr($hasil['no_resep_terakhir'], 8) + 1;
    }

    // Gabungkan lagi: 20250815 + 0001 -> 202508150001
    $no_resep_baru = $prefix . str_pad($nomor_urut, 4, '0', STR_PAD_LEFT);

    echo json_encode(['no_resep' => $no_resep_baru]);

} catch (Exception $e) {
    http_response_code(500);
    // Fallback jika terjadi error
    echo json_encode(['no_resep' => date('YmdHis')]);
}
?>