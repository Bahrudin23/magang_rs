<?php
if (!function_exists('esc')) {
  function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$totalPages = isset($totalPages) ? max(1,(int)$totalPages) : 1;
$page       = isset($page) ? max(1,(int)$page) : 1;
$no_rawat   = isset($no_rawat) ? (string)$no_rawat : '';
$riwayat    = isset($riwayat) && is_array($riwayat) ? $riwayat : [];

?>
<div class="riwayat-header mt-24">
  <h4 class="mt-24">Riwayat Asesmen</h4>
  <input type="text" id="cari_riwayat" placeholder="Cari di riwayat asesmen..." style="width:220px;">
</div>

<div class="table-responsive mt-24">
  <table class="data-list" id="tabel-riwayat">
    <thead>
      <tr>
        <th>Tanggal</th><th>Jam</th><th>Nama Pasien</th><th>Suhu</th><th>Tensi</th><th>Nadi</th>
        <th>Resp</th><th>Tinggi</th><th>Berat</th><th>SpO₂</th><th>GCS</th>
        <th>Kesadaran</th><th>Keluhan</th><th>Pemeriksaan</th><th>Alergi</th>
        <th>Penilaian</th><th>RTL</th><th>Instruksi</th><th>Evaluasi</th><th>Nama Pemeriksa</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($riwayat): foreach ($riwayat as $r): ?>

      <tr>
        <td style="min-width:70px;"><?= esc($r['tgl_perawatan'] ?? '') ?></td>
        <td><?= esc($r['jam_rawat'] ?? '') ?></td>
        <td style="min-width:150px;"><?= esc($r['nm_pasien'] ?? '') ?></td>
        <td><?= esc($r['suhu_tubuh'] ?? '') ?></td>
        <td><?= esc($r['tensi'] ?? '') ?></td>
        <td><?= esc($r['nadi'] ?? '') ?></td>
        <td><?= esc($r['respirasi'] ?? '') ?></td>
        <td><?= esc($r['tinggi'] ?? '') ?></td>
        <td><?= esc($r['berat'] ?? '') ?></td>
        <td><?= esc($r['spo2'] ?? '') ?></td>
        <td><?= esc($r['gcs'] ?? '') ?></td>
        <td style="min-width:100px;"><?= esc($r['kesadaran'] ?? '') ?></td>
        <td style="min-width:150px;"><?= esc($r['keluhan'] ?? '') ?></td>
        <td><?= esc($r['pemeriksaan'] ?? '') ?></td>
        <td style="min-width:60px;"><?= esc($r['alergi'] ?? '') ?></td>
        <td style="min-width:150px;"><?= esc($r['penilaian'] ?? '') ?></td>
        <td style="min-width:150px;"><?= esc($r['rtl'] ?? '') ?></td>
        <td><?= esc($r['instruksi'] ?? '') ?></td>
        <td style="min-width:100px;"><?= esc($r['evaluasi'] ?? '') ?></td>
        <td style="min-width:200px;"><?= esc($r['nama_pemeriksa'] ?? '') ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<nav aria-label="Halaman">
  <ul class="pagination">
    <?php
      $adj  = 2;
      $last = max(1, (int)$totalPages);
      $cur  = max(1, (int)$page);
    ?>

    <li class="page-item <?= $cur <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="?no_rawat=<?= urlencode($no_rawat) ?>&page=<?= max(1, $cur-1) ?>">&laquo; Prev</a>
    </li>

    <li class="page-item <?= $cur==1 ? 'active' : '' ?>">
      <a class="page-link" href="?no_rawat=<?= urlencode($no_rawat) ?>&page=1">1</a>
    </li>

    <?php
      $start = max(2, $cur - $adj);
      if ($start > 2) {
        echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
      }

      $end = min($last-1, $cur + $adj);
      for ($i=$start; $i <= $end; $i++) {
        $active = ($cur==$i) ? 'active' : '';
        echo '<li class="page-item '.$active.'">'
            .'<a class="page-link" href="?no_rawat='.urlencode($no_rawat).'&page='.$i.'">'
            .$i.'</a></li>';
      }

      if ($end < $last-1) {
        echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
      }

      if ($last > 1) {
        $active = ($cur==$last) ? 'active' : '';
        echo '<li class="page-item '.$active.'">'
            .'<a class="page-link" href="?no_rawat='.urlencode($no_rawat).'&page='.$last.'">'
            .$last.'</a></li>';
      }
    ?>

    <li class="page-item <?= $cur >= $last ? 'disabled' : '' ?>">
      <a class="page-link" href="?no_rawat=<?= urlencode($no_rawat) ?>&page=<?= min($last, $cur+1) ?>">Next &raquo;</a>
    </li>
  </ul>
</nav>
