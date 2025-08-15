<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_rkm      = $_POST['no_rkm_medis'];
    $no_rawat    = $_POST['no_rawat'];
    $kd_kamar    = $_POST['kd_kamar'];
    $diagnosa    = $_POST['diagnosa_awal'];
    $tgl_masuk   = $_POST['tgl_masuk'];
    $jam_masuk   = $_POST['jam_masuk'];
    $lama        = $_POST['lama'];
    $ttl_biaya   = $_POST['ttl_biaya'];
    $stts_pulang = $_POST['stts_pulang'];

    $sql = "INSERT INTO kamar_inap
            (no_rkm_medis, no_rawat, kd_kamar, diagnosa_awal, tgl_masuk, jam_masuk, lama, ttl_biaya, stts_pulang)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        $no_rkm, $no_rawat, $kd_kamar, $diagnosa,
        $tgl_masuk, $jam_masuk, $lama, $ttl_biaya, $stts_pulang
    ]);

    if ($ok) {
        header('Location: dashboard_ranap.php');
        exit;
    } else {
        $error = 'Gagal menyimpan data kamar inap.';
    }
}
require __DIR__.'/partials/header.php'; 
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>INPUT KAMAR INAP PASIEN</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .required-label::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>

<div class="container">
    <h3>INPUT KAMAR INAP PASIEN</h3>

    <form id="form-inap" autocomplete="off">
        <div class="form-grid">
            <label>No. Rekam Medis</label>
            <div style="position:relative">
                <input type="text" name="no_rkm_medis" id="no_rkm_medis" placeholder="Ketik no rekam medis..." required>
                <ul id="auto-rkm" class="dropdown" style="display:none"></ul>
            </div>
            <label>No. Rawat</label>
            <input type="text" name="no_rawat" id="no_rawat" class="readonly" readonly>
            <label>Nama Pasien</label>
            <input type="text" name="nm_pasien" id="nm_pasien" class="readonly" readonly>
            <hr class="full">
            <label>Kode Kamar</label>
            <div style="position:relative">
                <input type="text" id="kd_kamar_txt" placeholder="Ketik kode kamar..." autocomplete="off" required>
                <ul id="auto-kamar" class="dropdown" style="display:none"></ul>
            </div>
            <input type="hidden" name="kd_kamar" id="kd_kamar">

            <label>Jenis Kelas</label>
            <input type="text" id="kelas" class="readonly" readonly>

            <label>Bangsal</label>
            <input type="text" id="nm_bangsal" class="readonly" readonly> <label>Status Kamar</label>
            <input type="text" id="stts_kamar" class="readonly" readonly>
            
            <hr class="full">
            
            <label class="required-label">Diagnosa Awal Masuk</label>
            <input type="text" name="diagnosa_awal" id="diagnosa_awal" class="textarea.samebox" required>
            <label>Tgl Masuk</label>
            <input type="date" name="tgl_masuk" id="tgl_masuk"
                value="<?= date('Y-m-d') ?>" required>

            <label>Jam Masuk</label>
            <input type="time" name="jam_masuk" id="jam_masuk"
                value="<?= date('H:i:s') ?>" required>

            <hr class="full">
            <label>Tarif Kamar</label>
            <div class="biaya-row full">
            <input type="number" name="trf_kamar" id="trf_kamar" class="readonly" readonly>
            <span>X</span>
            <input type="number" name="lama" id="lama" value="1" min="1">
            <span>=</span>
            <input type="number" name="ttl_biaya" id="ttl_biaya" class="readonly" readonly>
            </div>

            <input type="hidden" name="stts_pulang" value="-">
            <button class="mt-24" type="submit">Simpan</button></div>
            </form>
        </div>
    </form>
</div>

<script src="js/inpt.js"></script>

</body>
</html>