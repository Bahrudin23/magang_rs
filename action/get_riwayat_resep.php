<?php
// File: magang/action/get_riwayat_resep.php
require_once __DIR__ . '/../config/db.php';

$no_rawat = $_GET['no_rawat'] ?? '';
if ($no_rawat === '') { echo ''; exit; }

try {
    $sql = "SELECT ro.no_resep, ro.tgl_resep, ro.jam_resep,
                   rd.kode_brng, d.nama_brng, rd.jumlah, rd.aturan_pakai, rd.harga
            FROM resep_obat ro
            JOIN resep_detail rd ON rd.no_resep = ro.no_resep
            JOIN databarang d ON d.kode_brng = rd.kode_brng
            WHERE ro.no_rawat = ?
            ORDER BY ro.tgl_resep DESC, ro.jam_resep DESC, ro.no_resep DESC
            LIMIT 200";
    $st = $pdo->prepare($sql);
    $st->execute([$no_rawat]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { echo ''; exit; }
    ?>
    <h4>Riwayat Resep Obat</h4>
    <table class="prescription-table">
      <thead>
        <tr>
          <th>No. Resep</th>
          <th>Tanggal</th>
          <th>Jam</th>
          <th>Kode</th>
          <th>Nama Obat</th>
          <th>Jml</th>
          <th>Aturan Pakai</th>
          <th>Harga</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?=htmlspecialchars($r['no_resep'])?></td>
          <td><?=htmlspecialchars(date('d-m-Y', strtotime($r['tgl_resep'])))?></td>
          <td><?=htmlspecialchars($r['jam_resep'])?></td>
          <td><?=htmlspecialchars($r['kode_brng'])?></td>
          <td><?=htmlspecialchars($r['nama_brng'])?></td>
          <td><?=htmlspecialchars($r['jumlah'])?></td>
          <td><?=htmlspecialchars($r['aturan_pakai'])?></td>
          <td><?=number_format((float)$r['harga'],0,',','.')?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php
} catch (Exception $e) {
    echo '<div class="alert alert-warning">Gagal memuat riwayat resep.</div>';
}
