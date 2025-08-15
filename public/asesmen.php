<?php
require_once '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

if (!function_exists('esc')) {
  function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$no_rawat = $_POST['no_rawat'] ?? $_GET['no_rawat'] ?? '';
$no_rkm_medis = $_GET['no_rkm_medis'] ?? '';

$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 5;
$offset  = ($page - 1) * $perPage;

$pasien = ['no_rkm_medis'=>'','nm_pasien'=>''];

if ($no_rawat === '' && $no_rkm_medis !== '') {
    $sqlLast = "SELECT rp.no_rawat
        FROM reg_periksa rp
        LEFT JOIN kamar_inap ki ON ki.no_rawat = rp.no_rawat
        WHERE rp.no_rkm_medis = ?
        ORDER BY
          COALESCE(CONCAT(ki.tgl_masuk,' ', COALESCE(ki.jam_masuk,'00:00:00')),
                  CONCAT(rp.tgl_registrasi,' ', rp.jam_reg)) DESC
        LIMIT 1
    ";
    $stLast = $pdo->prepare($sqlLast);
    $stLast->execute([$no_rkm_medis]);
    $no_rawat = $stLast->fetchColumn() ?: '';
}

if ($no_rawat) {
    $sql = "SELECT rp.no_rawat, rp.no_rkm_medis, p.nm_pasien
            FROM reg_periksa rp
            JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
            JOIN kamar_inap ki ON ki.no_rawat = rp.no_rawat
            WHERE rp.no_rkm_medis = ? OR rp.no_rawat = ?
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$no_rkm_medis, $no_rawat]);
    $pasien = $st->fetch(PDO::FETCH_ASSOC) ?: $pasien;

} elseif ($no_rkm_medis) {
    $st = $pdo->prepare("SELECT no_rkm_medis, nm_pasien FROM pasien WHERE no_rkm_medis = ? LIMIT 1");
    $st->execute([$no_rkm_medis]);
    $pasien = $st->fetch(PDO::FETCH_ASSOC) ?: $pasien;
}


$filterByRm = $pasien['no_rkm_medis'] !== '';

$kesadaran_list = ['Compos Mentis','Somnolence','Sopor','Coma'];

if (!$filterByRm) {
    $cntSql = "SELECT COUNT(*) FROM pemeriksaan_ranap pr
              JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat";
    $total = $pdo->query($cntSql)->fetchColumn();
} else {
    $cntSql = "SELECT COUNT(*) FROM pemeriksaan_ranap pr
              JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
              WHERE rp.no_rkm_medis = :rm";
    $stCnt = $pdo->prepare($cntSql);
    $stCnt->execute([':rm' => $pasien['no_rkm_medis']]);
    $total = $stCnt->fetchColumn();
}
$totalPages = ceil($total / $perPage);

if (!$filterByRm) {
    $sql2 = "SELECT 
        pr.no_rawat, rp.no_rkm_medis, p.nm_pasien, pr.tgl_perawatan, pr.jam_rawat,
        pr.suhu_tubuh, pr.tensi, pr.nadi, pr.respirasi,
        pr.tinggi, pr.berat, pr.spo2, pr.gcs, pr.kesadaran, pr.keluhan,
        pr.pemeriksaan, pr.alergi, pr.penilaian, pr.rtl, pr.instruksi, pr.evaluasi,
        COALESCE(pt.nama, d.nm_dokter) AS nama_pemeriksa
      FROM pemeriksaan_ranap pr
      JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
      JOIN pasien p       ON rp.no_rkm_medis = p.no_rkm_medis
      LEFT JOIN petugas pt ON pr.nip = pt.nip
      LEFT JOIN dokter d   ON pr.nip = d.kd_dokter
      ORDER BY pr.tgl_perawatan DESC, pr.jam_rawat DESC
      LIMIT :lim OFFSET :off";
    $st2 = $pdo->prepare($sql2);
    $st2->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $st2->bindValue(':off', $offset,  PDO::PARAM_INT);
    $st2->execute();
    $riwayat = $st2->fetchAll(PDO::FETCH_ASSOC);

} else {
    $sql2 = "SELECT 
        pr.no_rawat, rp.no_rkm_medis, p.nm_pasien, pr.tgl_perawatan, pr.jam_rawat,
        pr.suhu_tubuh, pr.tensi, pr.nadi, pr.respirasi,
        pr.tinggi, pr.berat, pr.spo2, pr.gcs, pr.kesadaran, pr.keluhan,
        pr.pemeriksaan, pr.alergi, pr.penilaian, pr.rtl, pr.instruksi, pr.evaluasi,
        COALESCE(pt.nama, d.nm_dokter) AS nama_pemeriksa
      FROM pemeriksaan_ranap pr
      JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
      JOIN pasien p       ON rp.no_rkm_medis = p.no_rkm_medis
      LEFT JOIN petugas pt ON pr.nip = pt.nip
      LEFT JOIN dokter d   ON pr.nip = d.kd_dokter
      WHERE rp.no_rkm_medis = :rm
      ORDER BY pr.tgl_perawatan DESC, pr.jam_rawat DESC
      LIMIT :lim OFFSET :off";
    $st2 = $pdo->prepare($sql2);
    $st2->bindValue(':rm',  $pasien['no_rkm_medis']);
    $st2->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $st2->bindValue(':off', $offset,  PDO::PARAM_INT);
    $st2->execute();
    $riwayat = $st2->fetchAll(PDO::FETCH_ASSOC);
}


if (!empty($_GET['ajax'])) {
  require __DIR__ . '/../action/partial_riwayat.php';
  exit;
}
require __DIR__.'/partials/header.php'; 
?>

<?php if(isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
  <div id="flash-msg" class="alert alert-success" style="position:fixed;right:24px;top:24px;z-index:9999">Data tersimpan.</div>
  <script>
    setTimeout(function(){
      var el = document.getElementById('flash-msg');
      if(el) el.style.display = 'none';
    }, 3000);
  </script>
<?php endif; ?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>ASESMEN AWAL PASIEN RAWAT INAP</title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <style>#tabel-riwayat { visibility: hidden; }</style>
</head>
<body>
<div class="container">
  <h3>ASESMEN AWAL PASIEN RAWAT INAP</h3>

  <form id="form-asesmen" method="post" action="../action/save_asesmen.php" autocomplete="off">
    <div class="form-grid-asm">

      <label>No. Rawat</label>
      <div class="auto-box">
        <input type="text" name="no_rawat" id="no_rawat" value="<?=htmlspecialchars($no_rawat)?>" required>
        <ul id="auto-rawat" class="dropdown"></ul>
      </div>

      <label>No. Rekam Medis</label>
      <input type="text" id="no_rkm_medis" name="no_rkm_medis"
            value="<?=htmlspecialchars($pasien['no_rkm_medis'])?>" readonly class="readonly">

      <label>Nama Pasien</label>
      <input type="text" id="nm_pasien" name="nm_pasien"
            value="<?=htmlspecialchars($pasien['nm_pasien'])?>" readonly class="readonly">

      <label>NIP Petugas</label>
      <div class="auto-box">
        <input type="text" name="nip" id="nip_petugas" placeholder="Ketik NIP atau nama">
        <ul id="auto-staff" class="dropdown"></ul>
      </div>

      <label>Jabatan</label>
      <input type="text" name="nm_jbtn" id="nm_jbtn" readonly class="readonly">

      <label>Alergi</label>
      <input type="text" name="alergi">

      <label>Subjek (Keluhan)</label>
      <textarea name="keluhan" class="textarea"></textarea>

      <label>Objek (Pemeriksaan)</label>
      <textarea name="pemeriksaan" class="textarea"></textarea>

      <label>Penilaian</label>
      <textarea name="penilaian" class="textarea"></textarea>

      <label>Plan (Rencana RTL)</label>
      <textarea name="rtl" class="textarea"></textarea>

      <label>Instruksi</label>
      <textarea name="instruksi" class="textarea"></textarea>

      <label>Evaluasi</label>
      <textarea name="evaluasi" class="textarea"></textarea>
    </div>

<h4 class="mt-24">Vital Sign</h4>
<div class="vital-row">
  <?php
  $fieldType = function($f){
    if (in_array($f, ['tensi','gcs'])) return 'text';
    return 'number';
  };
  $fieldStep = function($f){
    if (in_array($f, ['suhu','spo2'])) return '0.1';
    return '1';
  };
  $vitals = [
    'suhu'      => ['Suhu', '°C'],
    'tensi'     => ['Tensi', 'mmHg'],
    'nadi'      => ['Nadi', '/menit'],
    'respirasi' => ['Respirasi', '/menit'],
    'tinggi'    => ['Tinggi', 'Cm'],
    'berat'     => ['Berat', 'Kg'],
    'spo2'      => ['SpO₂', '%'],
    'gcs'       => ['GCS', 'E,V,M'],
  ];
  foreach($vitals as $field => list($label, $unit)):
  ?>
    <div class="vital-col input-with-unit">
      <label for="<?= $field ?>"><?= $label ?></label>
      <input
        type="<?= $fieldType($field) ?>"
        step="<?= $fieldStep($field) ?>"
        name="<?= $field ?>"
        id="<?= $field ?>"
        value="<?= htmlspecialchars($_POST[$field] ?? '') ?>"
        <?= $field !== 'tensi' && $field !== 'gcs' ? 'inputmode="numeric"' : '' ?>
        oninput="<?= ($field !== 'tensi' && $field !== 'gcs') ? "this.value=this.value.replace(/[^0-9.]/g,'');" : ($field == 'tensi' ? "this.value=this.value.replace(/[^0-9\\/]/g,'');" : "this.value=this.value.replace(/[^0-9]/g,'');") ?>"
        placeholder="">
      <span class="unit"><?= $unit ?></span>
    </div>
  <?php endforeach; ?>
</div>

    <div class="form-grid-asm mt-24">
      <label>Kesadaran</label>
      <select name="kesadaran">
        <?php foreach($kesadaran_list as $k): ?>
          <option value="<?=htmlspecialchars($k)?>"><?=htmlspecialchars($k)?></option>
        <?php endforeach; ?>
      </select>

    <label for="tgl_jam">Tanggal & Jam</label>
    <input type="datetime-local"class="form-control"id="tgl_jam"name="tgl_jam"step="1"value="">
      </div>

    <button type="submit" class="mt-24">Simpan</button>
  </form>

<div id="riwayat-container">
    <?php
      include __DIR__ . '/../action/partial_riwayat.php';
    ?>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>

<script src="js/ases.js" defer></script>
</body>
</html>