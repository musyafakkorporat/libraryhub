<?php
$pageTitle='Manajemen Denda'; $activeMenu='denda'; $depth='../';
require_once __DIR__.'/../includes/header.php';
$db=getDB();

if($_SERVER['REQUEST_METHOD']==='POST'&&$_POST['act']==='bayar'){
    $did=(int)$_POST['did'];
    $uid=currentUser()['id'];
    $db->prepare("UPDATE denda SET status='sudah_bayar',tgl_bayar=CURDATE(),dibayar_ke=? WHERE id=?")->execute([$uid,$did]);
    flash('success','Denda berhasil ditandai lunas.');
    header('Location: denda.php'); exit;
}

$fStatus=$_GET['status']??'';
$where=['1=1']; $params=[];
if($fStatus){$where[]="d.status=?";$params[]=$fStatus;}
$ws=implode(' AND ',$where);

$rows=$db->prepare("
    SELECT d.*,p.kode_pinjam,p.tgl_kembali_rencana,p.tgl_kembali_aktual,
           a.nama AS anggota_nama,a.no_anggota,
           GROUP_CONCAT(b.judul SEPARATOR ', ') AS judul_buku
    FROM denda d
    JOIN peminjaman p ON p.id=d.peminjaman_id
    JOIN anggota a ON a.id=p.anggota_id
    JOIN peminjaman_detail pd ON pd.peminjaman_id=p.id
    JOIN buku b ON b.id=pd.buku_id
    WHERE $ws GROUP BY d.id ORDER BY d.created_at DESC
");
$rows->execute($params); $rows=$rows->fetchAll();

$totalBelum=$db->query("SELECT COALESCE(SUM(jumlah),0) FROM denda WHERE status='belum_bayar'")->fetchColumn();
$totalLunas=$db->query("SELECT COALESCE(SUM(jumlah),0) FROM denda WHERE status='sudah_bayar'")->fetchColumn();
$countBelum=$db->query("SELECT COUNT(*) FROM denda WHERE status='belum_bayar'")->fetchColumn();
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span> Denda</div>
    <h1 class="page-title">Manajemen Denda</h1>
  </div>
</div>
<div class="stat-grid" style="margin-bottom:20px">
  <div class="stat-card" style="--accent:#dc2626">
    <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
    <div class="stat-body"><div class="stat-value" style="font-size:17px"><?=rupiah((int)$totalBelum)?></div><div class="stat-label">Total Belum Dibayar (<?=$countBelum?> kasus)</div></div>
  </div>
  <div class="stat-card" style="--accent:#059669">
    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
    <div class="stat-body"><div class="stat-value" style="font-size:17px"><?=rupiah((int)$totalLunas)?></div><div class="stat-label">Total Sudah Dibayar</div></div>
  </div>
</div>
<div class="card">
  <div class="tab-bar">
    <a href="denda.php" class="tab <?=!$fStatus?'active':''?>">Semua</a>
    <a href="denda.php?status=belum_bayar" class="tab <?=$fStatus==='belum_bayar'?'active':''?>">Belum Bayar <span class="tab-count err"><?=$countBelum?></span></a>
    <a href="denda.php?status=sudah_bayar" class="tab <?=$fStatus==='sudah_bayar'?'active':''?>">Lunas</a>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Kode Pinjam</th><th>Anggota</th><th>Buku</th><th>Hari Terlambat</th><th>Jumlah Denda</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td><code><?=e($r['kode_pinjam'])?></code></td>
          <td><strong><?=e($r['anggota_nama'])?></strong><br><span style="font-size:11px;color:var(--muted)"><?=e($r['no_anggota'])?></span></td>
          <td style="font-size:12px;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=e($r['judul_buku'])?></td>
          <td><?=$r['hari_terlambat']?> hari</td>
          <td><strong style="color:#dc2626"><?=rupiah($r['jumlah'])?></strong></td>
          <td><span class="status-badge <?=$r['status']==='sudah_bayar'?'sb-ok':'sb-err'?>"><?=$r['status']==='sudah_bayar'?'Lunas':'Belum Bayar'?></span></td>
          <td>
            <?php if($r['status']==='belum_bayar'): ?>
            <form method="POST" onsubmit="return confirm('Tandai denda ini sudah dibayar?')">
              <input type="hidden" name="act" value="bayar"/>
              <input type="hidden" name="did" value="<?=$r['id']?>"/>
              <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Tandai Lunas</button>
            </form>
            <?php else: ?>
            <span style="font-size:12px;color:var(--muted)"><?=$r['tgl_bayar']?date('d M Y',strtotime($r['tgl_bayar'])):'-'?></span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; if(empty($rows)): ?><tr><td colspan="7" style="text-align:center;padding:30px;color:var(--muted)">Tidak ada data denda</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
