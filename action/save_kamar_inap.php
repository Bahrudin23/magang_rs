<?php
require '../config/db.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

try{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        throw new Exception('Token tidak valid.');
    }

    $pdo->beginTransaction();

    $no_rawat   = trim($_POST['no_rawat'] ?? '');
    $kd_kamar   = trim($_POST['kd_kamar'] ?? '');
    $diag_awal  = trim($_POST['diagnosa_awal'] ?? '');
    $tgl_masuk  = $_POST['tgl_masuk'] ?? date('Y-m-d');
    $jam_masuk  = $_POST['jam_masuk'] ?? date('H:i:s');
    $lama       = max(1, (int)($_POST['lama'] ?? 1));

    $cek = $pdo->prepare("SELECT no_rawat FROM reg_periksa WHERE no_rawat=?");
    $cek->execute([$no_rawat]);
    if(!$cek->fetch()){ throw new Exception('No. Rawat tidak ditemukan.'); }

    $q = $pdo->prepare("SELECT kd_kamar, trf_kamar, status FROM kamar WHERE kd_kamar=? FOR UPDATE");
    $q->execute([$kd_kamar]);
    $kamar = $q->fetch(PDO::FETCH_ASSOC);
    if(!$kamar){ throw new Exception('Kode kamar tidak ada.'); }
    if($kamar['status'] !== 'KOSONG'){ throw new Exception('Kamar sudah terisi.'); }

    $trf_kamar = (int)$kamar['trf_kamar'];
    $ttl_biaya = $trf_kamar * $lama;

    $ins = $pdo->prepare("INSERT INTO kamar_inap
        (no_rawat,kd_kamar,trf_kamar,diagnosa_awal,diagnosa_akhir,
        tgl_masuk,jam_masuk,tgl_keluar,jam_keluar,lama,ttl_biaya,stts_pulang)
        VALUES (?,?,?,?,NULL,?,?,NULL,NULL,?,?,?)");
    $ins->execute([$no_rawat,$kd_kamar,$trf_kamar,$diag_awal,$tgl_masuk,$jam_masuk,$lama,$ttl_biaya,'-']);

    $pdo->prepare("UPDATE kamar SET status='ISI' WHERE kd_kamar=?")->execute([$kd_kamar]);

    $pdo->commit();
    echo json_encode(['ok'=>true]);
}catch(Exception $e){
    $pdo->rollBack();
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
