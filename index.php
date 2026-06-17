<?php
$pageTitle = 'Dashboard'; $activeMenu = 'dashboard'; $depth = '';
require_once '/includes/header.php';
$db = getDB();

$totalBuku      = $db->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$totalAnggota   = $db->query("SELECT COUNT(*) FROM anggota WHERE status='aktif'")->fetchColumn();
$sedangDipinjam = $db->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam'")->fetchColumn();
$terlambat      = $db->query("SELECT COUNT(*) FROM peminjaman WHERE status='terlambat' OR (status='dipinjam' AND tgl_kembali_rencana < CURDATE())")->fetchColumn();
$dendaBelumBayar= $db->query("SELECT COALESCE(SUM(jumlah),0) FROM denda WHERE status='belum_bayar'")->fetchColumn();
$totalJudulTersedia = $db->query("SELECT COUNT(*) FROM buku WHERE jumlah_tersedia > 0")->fetchColumn();

// Buku terpopuler (paling sering dipinjam)
$populer = $db->query("
  SELECT b.judul, b.pengarang, b.kode_buku, COUNT(pd.id) AS total_pinjam, k.nama AS kategori
  FROM buku b
  LEFT JOIN peminjaman_detail pd ON pd.buku_id = b.id
  LEFT JOIN kategori k ON k.id = b.kategori_id
  GROUP BY b.id ORDER BY total_pinjam DESC LIMIT 6
")->fetchAll();

// Peminjaman aktif terbaru
$pinjamAktif = $db->query("
  SELECT p.*, a.nama AS anggota_nama, a.no_anggota,
         GROUP_CONCAT(b.judul SEPARATOR ', ') AS judul_buku
  FROM peminjaman p
  JOIN anggota a ON a.id = p.anggota_id
  JOIN peminjaman_detail pd ON pd.peminjaman_id = p.id
  JOIN buku b ON b.id = pd.buku_id
  WHERE p.status IN ('dipinjam','terlambat')
  GROUP BY p.id ORDER BY p.tgl_kembali_rencana ASC LIMIT 8
")->fetchAll();

// Statistik per kategori
$katStats = $db->query("
  SELECT k.nama, COUNT(b.id) AS jumlah
  FROM kategori k LEFT JOIN buku b ON b.kategori_id = k.id
  GROUP BY k.id ORDER BY jumlah DESC LIMIT 7
")->fetchAll();

$maxKat = max(array_column($katStats,'jumlah') ?: [1]);
$colors = ['#0f766e','#0891b2','#4f46e5','#d97706','#dc2626','#9333ea','#059669'];
$covers = ['c1','c2','c3','c4','c5','c1','c2'];
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><span>Home</span><span class="sep">/</span> Dashboard</div>
    <h1 class="page-title">Dashboard LibraryHub</h1>
    <p class="page-sub">Selamat datang, <?= e(currentUser()['nama']) ?> — <?= date('l, d F Y') ?></p>
  </div>
  <div class="header-actions">
    <a href="pages/peminjaman.php?aksi=tambah" class="btn btn-primary"><i class="fas fa-plus"></i> Pinjam Buku</a>
  </div>
</div>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card" style="--accent:#0f766e">
    <div class="stat-icon"><i class="fas fa-book"></i></div>
    <div class="stat-body">
      <div class="stat-value"><?= $totalBuku ?></div>
      <div class="stat-label">Total Judul Buku</div>
      <div class="stat-change up"><i class="fas fa-check"></i> <?= $totalJudulTersedia ?> judul tersedia</div>
    </div>
  </div>
  <div class="stat-card" style="--accent:#4f46e5">
    <div class="stat-icon"><i class="fas fa-users"></i></div>
    <div class="stat-body">
      <div class="stat-value"><?= $totalAnggota ?></div>
      <div class="stat-label">Anggota Aktif</div>
      <div class="stat-change up"><i class="fas fa-user-check"></i> Terdaftar &amp; aktif</div>
    </div>
  </div>
  <div class="stat-card" style="--accent:#d97706">
    <div class="stat-icon"><i class="fas fa-hand-holding-heart"></i></div>
    <div class="stat-body">
      <div class="stat-value"><?= $sedangDipinjam ?></div>
      <div class="stat-label">Sedang Dipinjam</div>
      <div class="stat-change <?= $terlambat>0?'down':'up' ?>"><i class="fas fa-clock"></i> <?= $terlambat ?> terlambat dikembalikan</div>
    </div>
  </div>
  <div class="stat-card" style="--accent:#dc2626">
    <div class="stat-icon"><i class="fas fa-coins"></i></div>
    <div class="stat-body">
      <div class="stat-value" style="font-size:18px;font-weight:800"><?= rupiah((int)$dendaBelumBayar) ?></div>
      <div class="stat-label">Denda Belum Dibayar</div>
      <div class="stat-change down"><i class="fas fa-exclamation-triangle"></i> Perlu ditagih</div>
    </div>
  </div>
</div>

<div class="grid-2">
  <!-- Buku Populer -->
  <div class="card">
    <div class="card-header"><h3>Buku Terpopuler</h3><a href="pages/buku.php" class="btn-link">Lihat Semua <i class="fas fa-arrow-right"></i></a></div>
    <div style="padding:14px;display:flex;flex-direction:column;gap:10px">
      <?php foreach($populer as $i=>$b): ?>
      <div style="display:flex;align-items:center;gap:12px">
        <div class="book-cover <?= $covers[$i%5] ?>" style="width:42px;height:54px;border-radius:6px;font-size:18px;flex-shrink:0"><i class="fas fa-book"></i></div>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($b['judul']) ?></div>
          <div style="font-size:11px;color:var(--text2)"><?= e($b['pengarang']) ?> &bull; <?= e($b['kategori']??'-') ?></div>
        </div>
        <span class="chip chip-teal" style="flex-shrink:0"><?= $b['total_pinjam'] ?>x</span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Per Kategori -->
  <div class="card">
    <div class="card-header"><h3>Koleksi per Kategori</h3><span class="badge-tag"><?= $totalBuku ?> total</span></div>
    <div style="padding:16px 18px">
      <?php foreach($katStats as $i=>$k): $pct=$maxKat>0?round($k['jumlah']/$maxKat*100):0; ?>
      <div style="margin-bottom:13px">
        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
          <span style="font-weight:500"><?= e($k['nama']) ?></span>
          <span style="color:var(--text2)"><?= $k['jumlah'] ?> buku</span>
        </div>
        <div class="prog-bar"><div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $colors[$i%7] ?>"></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Peminjaman Aktif -->
<div class="card">
  <div class="card-header">
    <h3>Peminjaman Aktif</h3>
    <a href="pages/peminjaman.php" class="btn-link">Lihat Semua <i class="fas fa-arrow-right"></i></a>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Kode Pinjam</th><th>Anggota</th><th>Buku</th><th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Status</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php foreach($pinjamAktif as $p):
          $terlambatHari = (new DateTime()) > (new DateTime($p['tgl_kembali_rencana']))
            ? (int)(new DateTime())->diff(new DateTime($p['tgl_kembali_rencana']))->days : 0;
          $isLate = strtotime($p['tgl_kembali_rencana']) < time();
        ?>
        <tr>
          <td><code><?= e($p['kode_pinjam']) ?></code></td>
          <td>
            <div style="font-weight:500"><?= e($p['anggota_nama']) ?></div>
            <div style="font-size:11px;color:var(--muted)"><?= e($p['no_anggota']) ?></div>
          </td>
          <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($p['judul_buku']) ?></td>
          <td style="font-size:12px"><?= date('d M Y', strtotime($p['tgl_pinjam'])) ?></td>
          <td style="font-size:12px;color:<?= $isLate?'#dc2626':'var(--text2)' ?>;font-weight:<?= $isLate?'700':'400' ?>">
            <?= date('d M Y', strtotime($p['tgl_kembali_rencana'])) ?>
            <?php if($isLate): ?><div style="font-size:10px"><?= $terlambatHari ?> hari terlambat</div><?php endif; ?>
          </td>
          <td>
            <?php if($isLate): ?>
              <span class="status-badge sb-err">Terlambat</span>
            <?php else: ?>
              <span class="status-badge sb-info">Dipinjam</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="pages/pengembalian.php?pid=<?= $p['id'] ?>" class="icon-btn" title="Proses Kembali"><i class="fas fa-undo-alt"></i></a>
          </td>
        </tr>
        <?php endforeach; if(empty($pinjamAktif)): ?>
        <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--muted)">Tidak ada peminjaman aktif</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Quick Actions -->
<div class="card">
  <div class="card-header"><h3>Aksi Cepat</h3></div>
  <div style="padding:16px;display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px">
    <a href="pages/peminjaman.php?aksi=tambah" class="quick-action"><i class="fas fa-plus-circle"></i><span>Pinjam Buku</span></a>
    <a href="pages/pengembalian.php" class="quick-action"><i class="fas fa-undo"></i><span>Kembalikan</span></a>
    <a href="pages/anggota.php?aksi=tambah" class="quick-action"><i class="fas fa-user-plus"></i><span>Daftar Anggota</span></a>
    <a href="pages/buku.php?aksi=tambah" class="quick-action"><i class="fas fa-book-medical"></i><span>Tambah Buku</span></a>
    <a href="pages/denda.php" class="quick-action"><i class="fas fa-coins"></i><span>Bayar Denda</span></a>
    <a href="pages/laporan.php" class="quick-action"><i class="fas fa-file-alt"></i><span>Lihat Laporan</span></a>
    <a href="pages/reservasi.php" class="quick-action"><i class="fas fa-bookmark"></i><span>Reservasi</span></a>
    <a href="pages/kategori.php" class="quick-action"><i class="fas fa-tags"></i><span>Kategori</span></a>
  </div>
</div>

<?php require_once '/includes/footer.php'; ?>
