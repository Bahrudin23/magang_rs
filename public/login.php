<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* ====== jika sudah login, langsung ke list ====== */
if (!empty($_SESSION['role'])) {
    header('Location: /magang/public/list_pasien.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = trim($_POST['kd_dokter'] ?? '');

    $pick = function(array $row, array $keys, $default = null){
        foreach ($keys as $k) {
            if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                return $row[$k];
            }
        }
        return $default;
    };

    try {
        // --- LOGIN DOKTER
        $q1 = $pdo->prepare("SELECT * FROM dokter
            WHERE UPPER(TRIM(kd_dokter)) = UPPER(TRIM(?))
            LIMIT 1
        ");
        $q1->execute([$nip]);
        if ($dok = $q1->fetch(PDO::FETCH_ASSOC)) {
            session_regenerate_id(true);

            $nama  = $pick($dok, ['nm_dokter','nama','name','nm_nama'], $dok['kd_dokter']);
            $foto  = $pick($dok, ['foto','photo','gambar'], null);

            $_SESSION['role']       = 'dokter';
            $_SESSION['kd_dokter']  = $dok['kd_dokter'];
            $_SESSION['nama_login'] = $nama;
            $_SESSION['foto_login'] = $foto;

            header('Location: /magang/public/list_pasien.php'); exit;
        }

        // --- LOGIN PETUGAS
        $q2 = $pdo->prepare("SELECT * FROM petugas
            WHERE UPPER(TRIM(nip)) = UPPER(TRIM(?))
            LIMIT 1
        ");
        $q2->execute([$nip]);
        if ($ptg = $q2->fetch(PDO::FETCH_ASSOC)) {
            session_regenerate_id(true);

            $nama  = $pick($ptg, ['nama','nm_petugas','name'], $ptg['nip']);
            $foto  = $pick($ptg, ['foto','photo','gambar'], null);

            $_SESSION['role']       = 'petugas';
            $_SESSION['nip']        = $ptg['nip'];
            $_SESSION['nama_login'] = $nama;
            $_SESSION['foto_login'] = $foto;

            header('Location: /magang/public/list_pasien.php'); exit;
        }

        $error = 'Data tidak ditemukan. Periksa kembali NIP/Kode.';
    } catch (Exception $e) {
        $error = 'Terjadi kesalahan: '.$e->getMessage();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="css/log.css">
</head>
<body class="login-page">
  <div class="login-container">
    <h2>LOGIN</h2>
    <form method="post">
      <div class="input-group">
        <input type="text" name="kd_dokter" placeholder="NIP" required autofocus>
      </div>
      <button type="submit" class="btn-login">Login</button>
    </form>
    <?php if (!empty($error)): ?>
      <div style="color:#d00;margin-top:10px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
  </div>
  <script>
    function togglePass(){
      const p = document.getElementById('pass');
      p.type = p.type === 'password' ? 'text' : 'password';
    }
  </script>
</body>
</html>