<?php
// File: magang/action/get_detail_pasien_lab.php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/db.php';

    $no_rm = trim($_GET['no_rkm_medis'] ?? '');
    if (empty($no_rm)) {
        echo json_encode(null);
        exit;
    }

    $sql = "SELECT ki.no_rawat, d.kd_dokter, d.nm_dokter, b.nm_bangsal, k.kelas,
            (SELECT pr.penilaian FROM pemeriksaan_ranap pr WHERE pr.no_rawat = ki.no_rawat 
             ORDER BY pr.tgl_perawatan DESC, pr.jam_rawat DESC LIMIT 1) AS diagnosa_terakhir,
            ki.diagnosa_awal
            FROM kamar_inap ki
            JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
            JOIN dokter d ON rp.kd_dokter = d.kd_dokter
            JOIN kamar k ON ki.kd_kamar = k.kd_kamar
            JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
            WHERE rp.no_rkm_medis = :no_rm AND ki.tgl_keluar = '0000-00-00'
            ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':no_rm' => $no_rm]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $data['lokasi'] = $data['nm_bangsal'] . ' (' . $data['kelas'] . ')';
        $data['diagnosa'] = !empty(trim($data['diagnosa_terakhir'])) ? trim($data['diagnosa_terakhir']) : trim($data['diagnosa_awal']);
    }
    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Server Error: ' . $e->getMessage()]);
}