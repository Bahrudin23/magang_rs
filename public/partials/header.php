<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$title = $title ?? 'E-Ranap';
$rm    = $rm    ?? ($_GET['no_rkm_medis'] ?? '');
$cur   = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

function active($file, $cur){ return $file === $cur ? 'active' : ''; }
function patient_href($file, $rm){
  return $rm ? "{$file}?no_rkm_medis=".urlencode($rm) : $file;
}

$namaLogin = $_SESSION['nama_login'] ?? '';
$fotoLogin = $_SESSION['foto_login'] ?? '';
if (!$fotoLogin) $fotoLogin = 'img/default.png';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php if (!empty($extra_css)) foreach((array)$extra_css as $href): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($href) ?>">
  <?php endforeach; ?>

  <style>
    :root{
      --brand-1:#0d6efd;
      --brand-2:#3b82f6;
      --nav-bg: rgba(255,255,255,.75);
      --nav-border: rgba(0,0,0,.06);
      --shadow: 0 10px 28px rgba(0,0,0,.08);
    }
    @media (prefers-color-scheme: dark){
      :root{
        --nav-bg: rgba(20,22,26,.75);
        --nav-border: rgba(255,255,255,.08);
        --shadow: 0 10px 28px rgba(0,0,0,.6);
      }
    }

    /* Glassy navbar + anim compact on scroll */
    .navbar.glass {
      position: sticky; top: 0; z-index: 1030;
      background: var(--nav-bg);
      backdrop-filter: saturate(180%) blur(10px);
      -webkit-backdrop-filter: saturate(180%) blur(10px);
      border-bottom: 1px solid var(--nav-border);
      transition: padding .25s ease, box-shadow .25s ease, background-color .25s ease;
    }
    .navbar.glass.nav-compact {
      box-shadow: var(--shadow);
    }

    /* Brand gradient + micro hover */
    .navbar .navbar-brand {
      background: linear-gradient(90deg, var(--brand-1), var(--brand-2));
      -webkit-background-clip: text; background-clip: text; color: transparent;
      font-weight: 800;
      letter-spacing: .2px;
      transition: transform .2s ease;
    }
    .navbar .navbar-brand:hover { transform: translateY(-1px); }

    /* Link underline animation */
    .navbar .nav-link {
      position: relative;
      transition: color .2s ease;
    }
    .navbar .nav-link::after{
      content: '';
      position: absolute; left: 0; bottom: 0.2rem;
      width: 0; height: 2px;
      background: var(--brand-1);
      transition: width .22s ease;
    }
    .navbar .nav-link:hover::after,
    .navbar .nav-link.active::after { width: 100%; }

    /* Avatar kecil */
    .navbar .avatar{
      width:22px;height:22px;border-radius:50%;
      object-fit:cover;vertical-align:middle;margin-right:6px;
      box-shadow: 0 2px 6px rgba(0,0,0,.15);
      transition: transform .2s ease;
    }
    .navbar .avatar:hover{ transform: scale(1.04); }

    /* Tombol logout ikon tetap btn-sm, tapi center & ada hover halus */
    .btn-logout {
      display:inline-flex;align-items:center;justify-content:center;
      width:34px;height:32px;padding:0;
    }
    .btn-logout svg{ transition: transform .2s ease, opacity .2s ease; }
    .btn-logout:hover svg{ transform: translateX(1px); opacity:.95; }

    /* Kurangi gerakan bila user prefer reduced motion */
    @media (prefers-reduced-motion: reduce){
      * { transition: none !important; animation: none !important; }
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light glass">
  <div class="container-fluid">
    <a class="navbar-brand" href="list_pasien.php">RS UNIPDU MEDIKA</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav" aria-controls="topnav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topnav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?= active('detail.php',$cur) ? 'active' : '' ?>"
            href="detail.php?no_rkm_medis=<?= urlencode($rm) ?>">Detail Pasien</a>
        </li>
        <li class="nav-item"><a class="nav-link" href="asesmen.php?no_rkm_medis=<?= urlencode($rm) ?>">Asesmen</a></li>
        <li class="nav-item"><a class="nav-link" href="permintaan_lab.php">Permintaaan Lab</a></li>
        <li class="nav-item"><a class="nav-link" href="resep_obat.php">Resep Obat</a></li>
      </ul>

      <div class="d-flex ms-auto align-items-center">
        <span class="navbar-text me-3">
          <img src="<?= htmlspecialchars($fotoLogin) ?>" alt="foto" class="avatar"
              onerror="this.onerror=null;this.src='img/default.png'"><?= htmlspecialchars($namaLogin) ?>
        </span>
        <a class="btn btn-danger btn-sm btn-logout" href="logout.php" title="Logout" aria-label="Logout">
          <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
            <path d="M15 17l5-5-5-5M20 12H9" fill="none" stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 4h7a2 2 0 0 1 2 2v3" fill="none" stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M13 15v3a2 2 0 0 1-2 2H4" fill="none" stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
    </div>
    </div>
  </div>
</nav>
<div class="container my-4">

<script>
  (function(){
    const nav = document.querySelector('.navbar.glass');
    if(!nav) return;
    const onScroll = () => {
      if (window.scrollY > 6) nav.classList.add('nav-compact');
      else nav.classList.remove('nav-compact');
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  })();
</script>