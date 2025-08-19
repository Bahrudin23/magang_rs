<?php
// File: magang/action/save_resep.php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/resep_obat.php');
    exit;
}

// ... (kode lainnya tetap sama) ...
$no_resep   = $_POST['no_resep'] ?? '';
$no_rawat   = $_POST['no_rawat'] ?? '';
// ... (dst) ...
$aturan_list = $_POST['aturan_pakai'] ?? [];

if (empty($no_resep) || empty($no_rawat) || empty($kd_dokter) || empty($obat_list)) {
    header('Location: ../public/resep_obat.php?status=gagal&pesan=Data tidak lengkap');
    exit;
}

try {
    $pdo->beginTransaction();

    // Ambil dulu kd_pj pasien untuk perhitungan harga
    $stmt_pj = $pdo->prepare("SELECT kd_pj FROM reg_periksa WHERE no_rawat = ?");
    $stmt_pj->execute([$no_rawat]);
    $kd_pj = $stmt_pj->fetchColumn() ?: 'UMU';

    // ... (kode insert ke resep_obat tetap sama) ...
    $sql_resep = "INSERT INTO resep_obat (no_resep, tgl_resep, jam_resep, no_rawat, kd_dokter) VALUES (?, ?, ?, ?, ?)";
    $stmt_resep = $pdo->prepare($sql_resep);
    $stmt_resep->execute([$no_resep, date('Y-m-d'), date('H:i:s'), $no_rawat, $kd_dokter]);


    $sql_detail = "INSERT INTO resep_detail (no_resep, kode_brng, jumlah, aturan_pakai, harga) VALUES (?, ?, ?, ?, ?)";
    $stmt_detail = $pdo->prepare($sql_detail);
    
    $sql_update_stok = "UPDATE gudangbarang SET stok = stok - ? WHERE kode_brng = ? AND kd_bangsal = 'AP'";
    $stmt_update_stok = $pdo->prepare($sql_update_stok);

    foreach ($obat_list as $index => $kode_obat) {
        if (!empty($kode_obat)) {
            $jumlah = (float)($jumlah_list[$index] ?? 0);
            $aturan = trim($aturan_list[$index] ?? '');

            // Ambil harga dasar (harga beli)
            $stmt_harga_beli = $pdo->prepare("SELECT dasar FROM databarang WHERE kode_brng = ?");
            $stmt_harga_beli->execute([$kode_obat]);
            $harga_beli = (float)$stmt_harga_beli->fetchColumn();

            // HITUNG ULANG HARGA JUAL DI SERVER
            $markup = 0.40; // Default markup 40%
            if (strpos(strtoupper($kd_pj), 'BPJ') !== false) {
                $markup = 0.20; // Markup 20% untuk BPJS
            }
            $harga_jual = round($harga_beli + ($harga_beli * $markup));

            // Masukkan ke resep_detail dengan harga jual yang sudah dihitung
            $stmt_detail->execute([$no_resep, $kode_obat, $jumlah, $aturan, $harga_jual]);
            $stmt_update_stok->execute([$jumlah, $kode_obat]);
        }
    }

    $pdo->commit();
    header('Location: ../public/resep_obat.php?status=sukses');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Gagal simpan resep: " . $e->getMessage());
    header('Location: ../public/resep_obat.php?no_rawat='.$no_rawat.'&status=gagal&pesan=' . urlencode($e->getMessage()));
    exit;
}
?>