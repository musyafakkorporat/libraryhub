<?php
$pageTitle='Reservasi Buku'; $activeMenu='reservasi'; $depth='../';
require_once __DIR__.'/../includes/header.php';
$db=getDB();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    if($act==='tambah'){
        $db->prepare("INSERT INTO reservasi (anggota_id,buku_id,tgl_expired,status) VALUES (?,?,?,'menunggu')")
           ->execute([$_POST['anggota_id'],$_POST['buku_id'],$_POST['tgl_expired']]);
        flash('success','Reservasi berhasil dicatat.'); header('Location: reservasi.php'); exit;
    }
    if($act==='update'){
        $db->prepare("UPDATE reservasi SET status=? WHERE id=?")->execute([$_POST['status'],$_POST['rid']]);
        flash('success','Status reservasi diperbarui.'); header('Location: reservasi.php'); exit;
    }
    if($act==='hapus'){
        $db->prepare("DELETE FROM reservasi WHERE id=?")->execute([$_POST['hid']]);
        flash('success','Reservasi dihapus.'); header('Location: reservasi.php'); exit;
    }
}

$rows=$db->query("
    SELECT r.*,a.nama AS anggota_nama,a.no_anggota,b.judul,b.kode_buku,b.jumlah_tersedia
    FROM reservasi r
    JOIN anggota a ON a.id=r.anggota_id
    JOIN buku b ON b.id=r.buku_id
    ORDER BY r.tgl_reservasi DESC
")->fetchAll();
$anggotaList=$db->query("SELECT id,no_anggota,nama FROM anggota WHERE status='aktif' ORDER BY nama")->fetchAll();
$bukuList=$db->query("SELECT id,kode_buku,judul,jumlah_tersedia FROM buku ORDER BY judul")->fetchAll();
$sbMap=['menunggu'=>'sb-warn','tersedia'=>'sb-ok','diambil'=>'sb-info','batal'=>'sb-gray','expired'=>'sb-err'];
?>
<div class="page-header">
  <div><div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span> Reservasi</div>
  <h1 class="page-title">Reservasi Buku</h1></div>
  <button class="btn btn-primary" onclick="document.getElementById('modalRes').classList.add('open')"><i class="fas fa-plus"></i> Tambah Reservasi</button>
</div>
<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Anggota</th><th>Buku</th><th>Tgl Reservasi</th><th>Tgl Expired</th><th>Stok Saat Ini</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach($rows as $i=>$r): ?>
        <tr>
          <td style="color:var(--muted)"><?=$i+1?></td>
          <td><strong><?=e($r['anggota_nama'])?></strong><br><span style="font-size:11px;color:var(--muted)"><?=e($r['no_anggota'])?></span></td>
          <td><div style="font-weight:500;font-size:12px"><?=e($r['judul'])?></div><code style="font-size:10px"><?=e($r['kode_buku'])?></code></td>
          <td style="font-size:12px"><?=date('d M Y H:i',strtotime($r['tgl_reservasi']))?></td>
          <td style="font-size:12px;color:<?=strtotime($r['tgl_expired']??'9999-01-01')<time()?'#dc2626':'var(--text2)'?>"><?=$r['tgl_expired']?date('d M Y',strtotime($r['tgl_expired'])):'-'?></td>
          <td><span style="color:<?=$r['jumlah_tersedia']>0?'#059669':'#dc2626'?>;font-weight:700"><?=$r['jumlah_tersedia']?></span></td>
          <td><span class="status-badge <?=$sbMap[$r['status']]?>"><?=ucfirst($r['status'])?></span></td>
          <td style="white-space:nowrap">
            <select onchange="updateRes(<?=$r['id']?>,this.value)" class="form-control" style="width:120px;padding:4px 8px;font-size:11px">
              <?php foreach(['menunggu','tersedia','diambil','batal','expired'] as $s): ?>
              <option value="<?=$s?>" <?=$r['status']===$s?'selected':''?>><?=ucfirst($s)?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <?php endforeach; if(empty($rows)): ?><tr><td colspan="8" style="text-align:center;padding:30px;color:var(--muted)">Tidak ada reservasi</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="modalRes">
  <div class="modal" style="max-width:420px">
    <div class="modal-header"><h3>Tambah Reservasi</h3><button class="modal-close" onclick="closeModal('modalRes')">&times;</button></div>
    <form method="POST">
      <input type="hidden" name="act" value="tambah"/>
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Anggota *</label>
          <select name="anggota_id" class="form-control" required><option value="">-- Pilih Anggota --</option>
            <?php foreach($anggotaList as $a): ?><option value="<?=$a['id']?>"><?=e($a['no_anggota'].' — '.$a['nama'])?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Buku *</label>
          <select name="buku_id" class="form-control" required><option value="">-- Pilih Buku --</option>
            <?php foreach($bukuList as $b): ?><option value="<?=$b['id']?>"><?=e($b['kode_buku'].' — '.$b['judul'])?> (<?=$b['jumlah_tersedia']?> tersedia)</option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Expired Tanggal</label><input type="date" name="tgl_expired" class="form-control" value="<?=date('Y-m-d',strtotime('+7 days'))?>"/></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalRes')">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>
<form method="POST" id="updateResForm"><input type="hidden" name="act" value="update"/><input type="hidden" name="rid" id="updateResId"/><input type="hidden" name="status" id="updateResStatus"/></form>
<script>
function updateRes(id,status){
    if(!confirm('Update status reservasi menjadi: '+status+'?')){ location.reload(); return; }
    document.getElementById('updateResId').value=id;
    document.getElementById('updateResStatus').value=status;
    document.getElementById('updateResForm').submit();
}
</script>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
