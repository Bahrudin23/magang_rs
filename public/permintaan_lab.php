<?php
session_start();
if (empty($_SESSION['role'])) { header('Location: login.php'); exit; }
require_once '../config/db.php'; 

$daftar_dokter = [];
try {
    $stmt_dokter = $pdo->query("SELECT kd_dokter, nm_dokter FROM dokter WHERE status = '1' ORDER BY nm_dokter ASC");
    $daftar_dokter = $stmt_dokter->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
require __DIR__.'/partials/header.php'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Pemeriksaan Lab</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; align-items: start; }
        .form-grid-2col .form-group { display: flex; flex-direction: column; }
        .form-grid-2col label { margin-bottom: 6px; font-weight: 500; font-size: 0.9rem; }
        .full-width { grid-column: 1 / -1; }
        .readonly { background-color: #e9ecef !important; cursor: not-allowed; }
        .search-wrapper { position: relative; }
        .search-wrapper .dropdown { position: absolute; top: 100%; left: 0; right: 0; z-index: 1000; border: 1px solid #ced4da; background: #fff; max-height: 250px; overflow-y: auto; list-style-type: none; padding: 0; margin: 4px 0 0 0; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: var(--radius); }
        .search-wrapper .dropdown li { padding: 8px 12px; cursor: pointer; }
        .search-wrapper .dropdown li:hover { background-color: #f0f6ff; }
    </style>
</head>
<body>
<div class="container">
    <h2>PERMINTAAN PEMERIKSAAN LAB</h2>
    <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
        <div class="alert success" style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">Permintaan lab berhasil disimpan!</div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] == 'gagal'): ?>
        <div class="alert error" style="background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">Gagal menyimpan permintaan lab. Pesan: <?= htmlspecialchars($_GET['pesan'] ?? 'Error tidak diketahui'); ?></div>
    <?php endif; ?>

    <form action="../action/save_permintaan_lab.php" method="POST" id="form-permintaan-lab">
        <div class="form-grid-2col">
            <div class="form-group full-width">
                <label for="pasien-search">Pilih Pasien (Ketik Nama / No. RM)</label>
                <div class="search-wrapper">
                    <input type="text" id="pasien-search" name="nama_pasien_display" placeholder="Ketik min. 2 huruf..." autocomplete="off" required>
                    <input type="hidden" id="no-rawat" name="no_rawat">
                    <input type="hidden" id="no-rkm-medis" name="no_rkm_medis">
                    <ul id="pasien-suggestions" class="dropdown" style="display:none;"></ul>
                </div>
            </div>
            <div class="form-group">
                <label for="dokter">Pengirim (Dokter)</label>
                <select id="dokter" name="kd_dokter" required class="readonly" tabindex="-1" style="pointer-events: none;">
                    <option value="">-- Akan terisi otomatis --</option>
                    <?php foreach ($daftar_dokter as $dokter): ?>
                        <option value="<?= htmlspecialchars($dokter['kd_dokter']); ?>"><?= htmlspecialchars($dokter['nm_dokter']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="poli">Poli / Kamar</label>
                <input type="text" id="poli" name="poli" class="readonly" readonly>
            </div>
            <div class="form-group">
                <label for="tanggal">Tanggal Permintaan</label>
                <input type="text" id="tanggal" name="tanggal_display" value="<?= date('d-m-Y'); ?>" class="readonly" readonly>
            </div>
            <div class="form-group">
                <label for="jam">Jam Permintaan</label>
                <input type="text" id="jam" name="jam_display" value="<?= date('H:i:s'); ?>" class="readonly" readonly>
            </div>
            <div class="form-group full-width">
                <label for="diagnosa">Diagnosa Klinis</label>
                <textarea id="diagnosa" name="diagnosa" placeholder="Akan terisi otomatis dari asesmen terakhir..." class="readonly" readonly style="min-height: 80px;"></textarea>
            </div>
            <div class="form-group full-width">
                <label for="info">Informasi Tambahan</label>
                <textarea id="info" name="info" placeholder="Keterangan tambahan..." style="min-height: 80px;"></textarea>
            </div>
        </div>
        <div class="text-right mt-24">
            <button type="submit">Simpan Permintaan</button>
        </div>
    </form>
</div>

<script src="js/inpt.js"></script>
<script src="js/lab.js?v=1"></script>

</body>
</html>