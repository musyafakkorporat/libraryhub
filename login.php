<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!login(trim($_POST['username']??''), $_POST['password']??''))
        $err = 'Username atau password salah.';
    else { header('Location: index.php'); exit; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Login — LibraryHub</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/style.css"/>
  <style>
    body{background:#f1f5f9;align-items:center;justify-content:center}
    .login-wrap{display:flex;box-shadow:0 20px 60px rgba(0,0,0,.12);border-radius:16px;overflow:hidden;width:820px;max-width:95vw}
    .ll{flex:1;background:linear-gradient(150deg,#0a1628 0%,#0f766e 100%);padding:48px 36px;color:#fff;display:flex;flex-direction:column;justify-content:center}
    .ll .logo{display:flex;align-items:center;gap:12px;margin-bottom:36px}
    .ll .logo-icon{width:46px;height:46px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px}
    .ll h1{font-size:26px;font-weight:800;margin-bottom:8px}
    .ll p{font-size:13px;opacity:.7;line-height:1.7}
    .ll .feats{margin-top:32px;display:flex;flex-direction:column;gap:11px}
    .feat{display:flex;align-items:center;gap:10px;font-size:13px;opacity:.85}
    .feat-icon{width:26px;height:26px;background:rgba(255,255,255,.13);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0}
    .lr{width:340px;background:#fff;padding:48px 36px;display:flex;flex-direction:column;justify-content:center}
    .lr h2{font-size:21px;font-weight:700;margin-bottom:4px;color:#0f172a}
    .lr .sub{font-size:13px;color:#64748b;margin-bottom:28px}
    .iw{position:relative}
    .iw i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px}
    .iw input{width:100%;padding:10px 12px 10px 36px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;font-family:inherit;transition:border-color .15s}
    .iw input:focus{border-color:#0f766e;box-shadow:0 0 0 3px rgba(15,118,110,.1)}
    .login-btn{width:100%;padding:11px;background:#0f766e;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;transition:background .15s}
    .login-btn:hover{background:#0d5c57}
    .err-box{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:8px;padding:9px 12px;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
    .demo{margin-top:18px;background:#f8fafc;border:1px dashed #e2e8f0;border-radius:8px;padding:11px 12px;font-size:11px;color:#64748b}
    .demo strong{color:#374151}
    @media(max-width:600px){.ll{display:none}.lr{width:100%}}
  </style>
</head>
<body>
<div class="login-wrap">
  <div class="ll">
    <div class="logo">
      <div class="logo-icon"><i class="fas fa-book-open"></i></div>
      <div><div style="font-size:17px;font-weight:800">LibraryHub</div><div style="font-size:11px;opacity:.55">Perpustakaan Digital</div></div>
    </div>
    <h1>Sistem Manajemen Perpustakaan Digital</h1>
    <p>Platform lengkap untuk mengelola koleksi buku, sirkulasi, anggota, dan laporan perpustakaan secara digital.</p>
    <div class="feats">
      <div class="feat"><div class="feat-icon"><i class="fas fa-book"></i></div> Katalog buku digital terintegrasi</div>
      <div class="feat"><div class="feat-icon"><i class="fas fa-exchange-alt"></i></div> Sistem peminjaman &amp; pengembalian</div>
      <div class="feat"><div class="feat-icon"><i class="fas fa-coins"></i></div> Manajemen denda otomatis</div>
      <div class="feat"><div class="feat-icon"><i class="fas fa-chart-bar"></i></div> Laporan &amp; statistik lengkap</div>
    </div>
  </div>
  <div class="lr">
    <h2>Selamat Datang</h2>
    <p class="sub">Masuk ke panel LibraryHub</p>
    <?php if($err): ?><div class="err-box"><i class="fas fa-exclamation-circle"></i> <?= e($err) ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Username</label>
        <div class="iw"><i class="fas fa-user"></i><input type="text" name="username" value="<?= e($_POST['username']??'') ?>" required autofocus placeholder="Masukkan username"/></div>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="iw"><i class="fas fa-lock"></i><input type="password" name="password" required placeholder="Masukkan password"/></div>
      </div>
      <button type="submit" class="login-btn"><i class="fas fa-sign-in-alt"></i> Masuk</button>
    </form>
    <div class="demo">
      <strong>Demo Login:</strong><br>
      Admin &nbsp;: <code>admin</code> / <code>admin123</code><br>
      Petugas: <code>petugas1</code> / <code>admin123</code>
    </div>
  </div>
</div>
</body>
</html>
