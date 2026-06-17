<?php
$pageTitle='Pengembalian Buku'; $activeMenu='pengembalian'; $depth='../';
require_once __DIR__.'/../includes/header.php';
$db=getDB();

$pid=(int)($_GET['pid']??0);

if($_SERVER['REQUEST_METHOD']==='POST'&&$_POST['act']==='kembalikan'){
    $pid2=(int)$_POST['pid'];
    $p=$db->prepare("SELECT * FROM peminjaman WHERE id=?"); $p->execute([$pid2]); $p=$p->fetch();
    if(!$p||!in_array($p['status'],['dipinjam','terlambat'])){flash('error','Data tidak valid.');header('Location: pengembalian.php');exit;}

    $tglKembali=date('Y-m-d');
    $denda=hitungDenda($p['tgl_kembali_rencana']);

    $db->beginTransaction();
    try {
        $db->prepare("UPDATE peminjaman SET status='dikembalikan',tgl_kembali_aktual=?,denda=? WHERE id=?")
           ->execute([$tglKembali,$denda['total'],$pid2]);
        // Kembalikan stok buku
        $bids=$db->prepare("SELECT buku_id FROM peminjaman_detail WHERE peminjaman_id=?"); $bids->execute([$pid2]); $bids=$bids->fetchAll(PDO::FETCH_COLUMN);
        foreach($bids as $bid) $db->prepare("UPDATE buku SET jumlah_tersedia=jumlah_tersedia+1 WHERE id=?")->execute([$bid]);
        // Catat denda jika ada
        if($denda['total']>0){
            $existing=$db->prepare("SELECT id FROM denda WHERE peminjaman_id=?"); $existing->execute([$pid2]); $existing=$existing->fetchColumn();
            if(!$existing) $db->prepare("INSERT INTO denda (peminjaman_id,jumlah,denda_per_hari,hari_terlambat) VALUES (?,?,?,?)")
                ->execute([$pid2,$denda['total'],DENDA_PER_HARI,$denda['hari']]);
        }
        $db->commit();
        flash('success','Buku berhasil dikembalikan.'.($denda['total']>0?' Denda: '.rupiah($denda['total']):''));
        header('Location: pengembalian.php'); exit;
    } catch(Exception $e){ $db->rollBack(); flash('error','Gagal: '.$e->getMessage()); header('Location: pengembalian.php'); exit; }
}

// Jika ada pid dari GET, tampilkan form konfirmasi
$detail=null;
if($pid){
    $s=$db->prepare("SELECT p.*,a.nama AS anggota_nama,a.no_anggota,GROUP_CONCAT(b.judul SEPARATOR ', ') AS judul_buku FROM peminjaman p JOIN anggota a ON a.id=p.anggota_id JOIN peminjaman_detail pd ON pd.peminjaman_id=p.id JOIN buku b ON b.id=pd.buku_id WHERE p.id=? AND p.status IN ('dipinjam','terlambat') GROUP BY p.id");
    $s->execute([$pid]); $detail=$s->fetch();
}

// List aktif
$aktif=$db->query("
    SELECT p.*,a.nama AS anggota_nama,a.no_anggota,GROUP_CONCAT(b.judul SEPARATOR ', ') AS judul_buku
    FROM peminjaman p JOIN anggota a ON a.id=p.anggota_id
    JOIN peminjaman_detail pd ON pd.peminjaman_id=p.id
    JOIN buku b ON b.id=pd.buku_id
    WHERE p.status IN ('dipinjam','terlambat') GROUP BY p.id ORDER BY p.tgl_kembali_rencana ASC
")->fetchAll();
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span> Pengembalian</div>
    <h1 class="page-title">Pengembalian Buku</h1>
    <p class="page-sub"><?=count($aktif)?> buku sedang dipinjam</p>
  </div>
</div>

<?php if($detail): $d=hitungDenda($detail['tgl_kembali_rencana']); ?>
<!-- Konfirmasi Pengembalian -->
<div class="sc" style="border:2px solid var(--primary)">
  <div class="sc-head" style="background:#f0fdfa"><h4><i class="fas fa-undo-alt"></i> Konfirmasi Pengembalian — <?=e($detail['kode_pinjam'])?></h4></div>
  <div class="sc-body">
    <div class="form-grid">
      <table class="detail-table">
        <tr><td>Anggota</td><td><strong><?=e($detail['anggota_nama'])?></strong> (<?=e($detail['no_anggota'])?>)</td></tr>
        <tr><td>Buku</td><td><?=e($detail['judul_buku'])?></td></tr>
        <tr><td>Tgl Pinjam</td><td><?=date('d M Y',strtotime($detail['tgl_pinjam']))?></td></tr>
        <tr><td>Jatuh Tempo</td><td><?=date('d M Y',strtotime($detail['tgl_kembali_rencana']))?></td></tr>
      </table>
      <div>
        <?php if($d['hari']>0): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:16px;margin-bottom:12px">
          <div style="font-weight:700;color:#dc2626;font-size:14px;margin-bottom:4px"><i class="fas fa-exclamation-triangle"></i> Terlambat <?=$d['hari']?> Hari</div>
          <div style="font-size:24px;font-weight:800;color:#dc2626"><?=rupiah($d['total'])?></div>
          <div style="font-size:11px;color:#9ca3af">Rp <?=number_format(DENDA_PER_HARI,0,',','.')?>/hari</div>
        </div>
        <?php else: ?>
        <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:16px">
          <div style="color:#059669;font-weight:600"><i class="fas fa-check-circle"></i> Tepat Waktu — Tidak ada denda</div>
        </div>
        <?php endif; ?>
        <form method="POST" style="margin-top:12px">
          <input type="hidden" name="act" value="kembalikan"/>
          <input type="hidden" name="pid" value="<?=$detail['id']?>"/>
          <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-check"></i> Konfirmasi Pengembalian</button>
        </form>
        <a href="pengembalian.php" class="btn btn-outline" style="width:100%;margin-top:8px;justify-content:center"><i class="fas fa-times"></i> Batal</a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><h3>Daftar Buku Sedang Dipinjam</h3></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Kode</th><th>Anggota</th><th>Buku</th><th>Jatuh Tempo</th><th>Denda Est.</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach($aktif as $r):
          $d2=hitungDenda($r['tgl_kembali_rencana']);
          $isLate=$d2['hari']>0;
        ?>
        <tr>
          <td><code><?=e($r['kode_pinjam'])?></code></td>
          <td><strong><?=e($r['anggota_nama'])?></strong><br><span style="font-size:11px;color:var(--muted)"><?=e($r['no_anggota'])?></span></td>
          <td style="font-size:12px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=e($r['judul_buku'])?></td>
          <td style="color:<?=$isLate?'#dc2626':'var(--text2)'?>;font-weight:<?=$isLate?700:400?>"><?=date('d M Y',strtotime($r['tgl_kembali_rencana']))?><?php if($isLate): ?><br><span style="font-size:11px"><?=$d2['hari']?> hari terlambat</span><?php endif; ?></td>
          <td style="font-weight:700;color:<?=$isLate?'#dc2626':'#059669'?>"><?=$isLate?rupiah($d2['total']):'Rp 0'?></td>
          <td><a href="pengembalian.php?pid=<?=$r['id']?>" class="btn btn-primary btn-sm"><i class="fas fa-undo"></i> Kembalikan</a></td>
        </tr>
        <?php endforeach; if(empty($aktif)): ?><tr><td colspan="6" style="text-align:center;padding:36px;color:var(--muted)">Tidak ada peminjaman aktif</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
