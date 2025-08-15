<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

$rm = trim($_GET['no_rkm_medis'] ?? '');
$nr = trim($_GET['no_rawat'] ?? '');

if ($rm === '' && $nr !== '') {
  $st = $pdo->prepare("SELECT no_rkm_medis FROM reg_periksa WHERE no_rawat = ? LIMIT 1");
  $st->execute([$nr]);
  $rm = (string)($st->fetchColumn() ?: '');
}

if ($rm === '') {
  header('Location: list_pasien.php');
  exit;
}

$title = 'Detail Pasien';
require __DIR__.'/partials/header.php';
?>

<div class="d-flex align-items-center mb-3">
  <h3 class="page-title me-3 mb-0">Detail Pasien</h3>
  <span class="badge rounded-pill text-bg-secondary">RM: <?= htmlspecialchars($rm) ?></span>
</div>

<?php
$NO_RKM_MEDIS = $rm;
include __DIR__ . '/detail_pasien.php';

require __DIR__.'/partials/footer.php';
