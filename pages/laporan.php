<?php
$pageTitle='Laporan & Statistik'; $activeMenu='laporan'; $depth='../';
require_once __DIR__.'/../includes/header.php';
$db=getDB();

$totalBuku     =$db->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$totalAnggota  =$db->query("SELECT COUNT(*) FROM anggota")->fetchColumn();
$totalPinjam   =$db->query("SELECT COUNT(*) FROM peminjaman")->fetchColumn();
$totalKembali  =$db->query("SELECT COUNT(*) FROM peminjaman WHERE status='dikembalikan'")->fetchColumn();
$totalTerlambat=$db->query("SELECT COUNT(*) FROM peminjaman WHERE status='terlambat'")->fetchColumn();
$totalDenda    =$db->query("SELECT COALESCE(SUM(jumlah),0) FROM denda")->fetchColumn();
$dendaTertagih =$db->query("SELECT COALESCE(SUM(jumlah),0) FROM denda WHERE status='sudah_bayar'")->fetchColumn();

$perKategori=$db->query("SELECT k.nama,COUNT(b.id) AS jml FROM kategori k LEFT JOIN buku b ON b.kategori_id=k.id GROUP BY k.id ORDER BY jml DESC")->fetchAll();
$top10=$db->query("SELECT b.judul,b.pengarang,COUNT(pd.id) AS total FROM buku b LEFT JOIN peminjaman_detail pd ON pd.buku_id=b.id GROUP BY b.id ORDER BY total DESC LIMIT 10")->fetchAll();
$perBulan=$db->query("SELECT DATE_FORMAT(tgl_pinjam,'%b %Y') AS bulan,COUNT(*) AS total FROM peminjaman WHERE tgl_pinjam >= DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(tgl_pinjam,'%Y-%m') ORDER BY tgl_pinjam")->fetchAll();
$perJenis=$db->query("SELECT jenis_anggota,COUNT(*) AS c FROM anggota GROUP BY jenis_anggota ORDER BY c DESC")->fetchAll();

$colors=['#0f766e','#4f46e5','#d97706','#dc2626','#9333ea','#0891b2','#059669'];
$maxKat=max(array_column($perKategori,'jml')?:[1]);
?>
<div class="page-header">
  <div><div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span> Laporan</div>
  <h1 class="page-title">Laporan &amp; Statistik</h1></div>
  <button class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Cetak</button>
</div>

<div class="stat-grid">
  <div class="stat-card" style="--accent:#0f766e"><div class="stat-icon"><i class="fas fa-book"></i></div><div class="stat-body"><div class="stat-value"><?=$totalBuku?></div><div class="stat-label">Total Buku</div></div></div>
  <div class="stat-card" style="--accent:#4f46e5"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-body"><div class="stat-value"><?=$totalAnggota?></div><div class="stat-label">Total Anggota</div></div></div>
  <div class="stat-card" style="--accent:#d97706"><div class="stat-icon"><i class="fas fa-exchange-alt"></i></div><div class="stat-body"><div class="stat-value"><?=$totalPinjam?></div><div class="stat-label">Total Transaksi Pinjam</div></div></div>
  <div class="stat-card" style="--accent:#059669"><div class="stat-icon"><i class="fas fa-undo"></i></div><div class="stat-body"><div class="stat-value"><?=$totalKembali?></div><div class="stat-label">Sudah Dikembalikan</div></div></div>
  <div class="stat-card" style="--accent:#dc2626"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-body"><div class="stat-value"><?=$totalTerlambat?></div><div class="stat-label">Terlambat</div></div></div>
  <div class="stat-card" style="--accent:#9333ea"><div class="stat-icon"><i class="fas fa-coins"></i></div><div class="stat-body"><div class="stat-value" style="font-size:16px"><?=rupiah((int)$dendaTertagih)?></div><div class="stat-label">Denda Tertagih / <?=rupiah((int)$totalDenda)?></div></div></div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-header"><h3>Koleksi per Kategori</h3></div>
    <div style="padding:16px 18px">
      <?php foreach($perKategori as $i=>$k): $pct=$maxKat>0?round($k['jml']/$maxKat*100):0; ?>
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
          <span style="font-weight:500"><?=e($k['nama'])?></span><span style="color:var(--text2)"><?=$k['jml']?> buku</span>
        </div>
        <div class="prog-bar"><div class="prog-fill" style="width:<?=$pct?>%;background:<?=$colors[$i%7]?>"></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3>Anggota per Jenis</h3></div>
    <div style="padding:16px 18px">
      <?php $maxJ=max(array_column($perJenis,'c')?:[1]); foreach($perJenis as $i=>$j): $pct=round($j['c']/$maxJ*100); ?>
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
          <span style="font-weight:500"><?=ucfirst($j['jenis_anggota'])?></span><span style="color:var(--text2)"><?=$j['c']?> orang</span>
        </div>
        <div class="prog-bar"><div class="prog-fill" style="width:<?=$pct?>%;background:<?=$colors[$i%7]?>"></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3>Top 10 Buku Terpopuler</h3></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Peringkat</th><th>Judul Buku</th><th>Pengarang</th><th>Total Dipinjam</th></tr></thead>
      <tbody>
        <?php foreach($top10 as $i=>$b): ?>
        <tr>
          <td><?php if($i===0): ?><span style="background:#ffd700;color:#92400e;padding:2px 8px;border-radius:4px;font-weight:700">#1</span>
          <?php elseif($i===1): ?><span style="background:#c0c0c0;padding:2px 8px;border-radius:4px;font-weight:700">#2</span>
          <?php elseif($i===2): ?><span style="background:#cd7f32;color:#fff;padding:2px 8px;border-radius:4px;font-weight:700">#3</span>
          <?php else: ?><span style="color:var(--muted)">#<?=$i+1?></span><?php endif; ?></td>
          <td><strong><?=e($b['judul'])?></strong></td>
          <td style="color:var(--text2)"><?=e($b['pengarang'])?></td>
          <td><span class="chip chip-teal"><?=$b['total']?>x dipinjam</span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3>Aktivitas Peminjaman 6 Bulan Terakhir</h3></div>
  <div style="padding:20px">
    <?php if($perBulan): $maxBulan=max(array_column($perBulan,'total')?:[1]); ?>
    <div style="display:flex;align-items:flex-end;gap:12px;height:140px">
      <?php foreach($perBulan as $b): $h=round($b['total']/$maxBulan*100); ?>
      <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px">
        <span style="font-size:11px;font-weight:700;color:var(--primary)"><?=$b['total']?></span>
        <div style="width:100%;background:var(--primary);border-radius:4px 4px 0 0;height:<?=$h?>%;min-height:4px"></div>
        <span style="font-size:10px;color:var(--muted);white-space:nowrap"><?=e($b['bulan'])?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?><p style="text-align:center;color:var(--muted)">Belum ada data peminjaman</p><?php endif; ?>
  </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
