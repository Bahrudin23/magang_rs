<?php
// File: magang/action/save_resep.php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/resep_obat.php');
    exit;
}

$no_resep   = $_POST['no_resep'] ?? '';
$no_rawat   = $_POST['no_rawat'] ?? '';
$kd_dokter  = $_POST['kd_dokter'] ?? '';
$obat_list  = $_POST['obat'] ?? [];
$jumlah_list = $_POST['jumlah'] ?? [];
$aturan_list = $_POST['aturan_pakai'] ?? [];

// Validasi dasar
if (empty($no_resep) || empty($no_rawat) || empty($kd_dokter) || empty($obat_list)) {
    header('Location: ../public/resep_obat.php?no_rawat='.$no_rawat.'&status=gagal&pesan=Data_tidak_lengkap');
    exit;
}

try {
    // Mulai transaksi database untuk memastikan semua data tersimpan atau tidak sama sekali
    $pdo->beginTransaction();

    // 1. Simpan data utama ke tabel resep_obat
    $sql_resep = "INSERT INTO resep_obat (no_resep, tgl_resep, jam_resep, no_rawat, kd_dokter) VALUES (?, ?, ?, ?, ?)";
    $stmt_resep = $pdo->prepare($sql_resep);
    $stmt_resep->execute([
        $no_resep,
        date('Y-m-d'),
        date('H:i:s'),
        $no_rawat,
        $kd_dokter
    ]);

    // 2. Simpan setiap item obat ke tabel resep_detail
    $sql_detail = "INSERT INTO resep_detail (no_resep, kode_brng, jumlah, aturan_pakai, harga) VALUES (?, ?, ?, ?, ?)";
    $stmt_detail = $pdo->prepare($sql_detail);

    // 3. Kurangi stok obat di tabel databarang
    $sql_update_stok = "UPDATE databarang SET stok = stok - ? WHERE kode_brng = ?";
    $stmt_update_stok = $pdo->prepare($sql_update_stok);

    foreach ($obat_list as $index => $kode_obat) {
        // Hanya proses baris yang obatnya diisi
        if (!empty($kode_obat)) {
            $jumlah = (float)($jumlah_list[$index] ?? 0);
            $aturan = trim($aturan_list[$index] ?? '');

            // Ambil harga terakhir dari database untuk keamanan
            $stmt_harga = $pdo->prepare("SELECT dasar FROM databarang WHERE kode_brng = ?");
            $stmt_harga->execute([$kode_obat]);
            $harga = $stmt_harga->fetchColumn();

            // Masukkan ke resep_detail
            $stmt_detail->execute([
                $no_resep,
                $kode_obat,
                $jumlah,
                $aturan,
                $harga
            ]);

            // Kurangi stok
            $stmt_update_stok->execute([$jumlah, $kode_obat]);
        }
    }

    // Jika semua berhasil, commit transaksi
    $pdo->commit();

    header('Location: ../public/resep_obat.php?no_rawat='.$no_rawat.'&status=sukses');
    exit;

} catch (Exception $e) {
    // Jika ada error, batalkan semua perubahan
    $pdo->rollBack();
    error_log("Gagal simpan resep: " . $e->getMessage());
    header('Location: ../public/resep_obat.php?no_rawat='.$no_rawat.'&status=gagal&pesan=' . urlencode($e->getMessage()));
    exit;
}