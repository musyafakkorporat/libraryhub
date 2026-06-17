<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
requireLogin();
$user  = currentUser();
$depth = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <script>(function(){try{if(localStorage.getItem('theme')==='dark'){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}})();</script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle??'Dashboard') ?> — LibraryHub</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?= $depth ?>assets/css/style.css"/>
</head>
<body>

<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo"><i class="fas fa-book-open"></i></div>
    <div class="brand-text">
      <span class="brand-name">LibraryHub</span>
      <span class="brand-sub"><?= ucfirst($user['role']) ?> Panel</span>
    </div>
  </div>
  <nav class="sidebar-nav">

    <div class="nav-label">UTAMA</div>
    <a href="<?= $depth ?>index.php" class="nav-item <?= ($activeMenu??'')==='dashboard'?'active':'' ?>">
      <i class="fas fa-chart-pie"></i><span>Dashboard</span>
    </a>

    <div class="nav-label">KOLEKSI</div>
    <a href="<?= $depth ?>pages/buku.php" class="nav-item <?= ($activeMenu??'')==='buku'?'active':'' ?>">
      <i class="fas fa-book"></i><span>Katalog Buku</span>
    </a>
    <a href="<?= $depth ?>pages/kategori.php" class="nav-item <?= ($activeMenu??'')==='kategori'?'active':'' ?>">
      <i class="fas fa-tags"></i><span>Kategori</span>
    </a>
    <a href="<?= $depth ?>pages/rak.php" class="nav-item <?= ($activeMenu??'')==='rak'?'active':'' ?>">
      <i class="fas fa-archive"></i><span>Manajemen Rak</span>
    </a>

    <div class="nav-label">SIRKULASI</div>
    <a href="<?= $depth ?>pages/peminjaman.php" class="nav-item <?= ($activeMenu??'')==='peminjaman'?'active':'' ?>">
      <i class="fas fa-hand-holding-heart"></i><span>Peminjaman</span>
    </a>
    <a href="<?= $depth ?>pages/pengembalian.php" class="nav-item <?= ($activeMenu??'')==='pengembalian'?'active':'' ?>">
      <i class="fas fa-undo-alt"></i><span>Pengembalian</span>
    </a>
    <a href="<?= $depth ?>pages/denda.php" class="nav-item <?= ($activeMenu??'')==='denda'?'active':'' ?>">
      <i class="fas fa-coins"></i><span>Denda</span>
    </a>
    <a href="<?= $depth ?>pages/reservasi.php" class="nav-item <?= ($activeMenu??'')==='reservasi'?'active':'' ?>">
      <i class="fas fa-bookmark"></i><span>Reservasi</span>
    </a>

    <div class="nav-label">ANGGOTA</div>
    <a href="<?= $depth ?>pages/anggota.php" class="nav-item <?= ($activeMenu??'')==='anggota'?'active':'' ?>">
      <i class="fas fa-users"></i><span>Data Anggota</span>
    </a>

    <div class="nav-label">LAPORAN</div>
    <a href="<?= $depth ?>pages/laporan.php" class="nav-item <?= ($activeMenu??'')==='laporan'?'active':'' ?>">
      <i class="fas fa-chart-bar"></i><span>Laporan &amp; Statistik</span>
    </a>

    <?php if ($user['role']==='admin'): ?>
    <div class="nav-label">PENGATURAN</div>
    <a href="<?= $depth ?>pages/users.php" class="nav-item <?= ($activeMenu??'')==='users'?'active':'' ?>">
      <i class="fas fa-user-cog"></i><span>Manajemen User</span>
    </a>
    <?php endif; ?>

  </nav>
</div>

<div class="main-wrapper">
  <header class="topbar">
    <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    <div class="topbar-title"><?= e($pageTitle??'Dashboard') ?></div>
    <div class="topbar-right">
      <button class="sidebar-toggle" onclick="toggleTheme()" title="Mode Gelap/Terang"><i class="fas fa-moon" id="themeToggleIcon"></i></button>
      <div class="topbar-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Cari buku / anggota..." onkeydown="if(event.key==='Enter'){window.location='<?= $depth ?>pages/buku.php?search='+this.value}"/>
      </div>
      <div class="user-dropdown">
        <div class="user-avatar" onclick="toggleDropdown()"><?= strtoupper(substr($user['nama'],0,1)) ?></div>
        <div class="dropdown-menu" id="userDropdown">
          <div class="dropdown-header"><strong><?= e($user['nama']) ?></strong><span><?= ucfirst($user['role']) ?></span></div>
          <a href="<?= $depth ?>logout.php" onclick="return confirm('Keluar?')"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
      </div>
    </div>
  </header>

  <?php $fs = flash('success'); $fe = flash('error'); ?>
  <?php if($fs): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= e($fs) ?><button onclick="this.parentElement.remove()" class="alert-close">&times;</button></div><?php endif; ?>
  <?php if($fe): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($fe) ?><button onclick="this.parentElement.remove()" class="alert-close">&times;</button></div><?php endif; ?>

  <main class="page-content">