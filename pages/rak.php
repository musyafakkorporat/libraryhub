<?php
$pageTitle='Manajemen Rak'; $activeMenu='rak'; $depth='../';
require_once __DIR__.'/../includes/header.php';
$db=getDB();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    if($act==='simpan'){
        $d=['kode'=>strtoupper(trim($_POST['kode'])),'nama'=>trim($_POST['nama']),'lokasi'=>trim($_POST['lokasi']??'')];
        $eid=(int)($_POST['edit_id']??0);
        if($eid){$sets=implode(',',array_map(fn($k)=>"$k=:$k",array_keys($d)));$s=$db->prepare("UPDATE rak SET $sets WHERE id=:id");$d['id']=$eid;$s->execute($d);}
        else $db->prepare("INSERT INTO rak (kode,nama,lokasi) VALUES (:kode,:nama,:lokasi)")->execute($d);
        flash('success','Rak berhasil disimpan.'); header('Location: rak.php'); exit;
    }
    if($act==='hapus'){$db->prepare("DELETE FROM rak WHERE id=?")->execute([$_POST['hid']]);flash('success','Rak dihapus.');header('Location: rak.php');exit;}
}

$rows=$db->query("SELECT r.*,(SELECT COUNT(*) FROM buku WHERE rak_id=r.id) AS jml FROM rak r ORDER BY r.kode")->fetchAll();
?>
<div class="page-header">
  <div><div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span> Manajemen Rak</div>
  <h1 class="page-title">Manajemen Rak</h1></div>
  <button class="btn btn-primary" onclick="document.getElementById('modalRak').classList.add('open')"><i class="fas fa-plus"></i> Tambah Rak</button>
</div>
<div class="grid-3">
  <?php foreach($rows as $r): ?>
  <div class="card" style="margin-bottom:0">
    <div class="card-body" style="padding:20px">
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px">
        <div style="width:48px;height:48px;background:#f0fdfa;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:22px"><i class="fas fa-archive"></i></div>
        <div><div style="font-weight:700;font-size:15px"><?=e($r['kode'])?></div><div style="font-size:12px;color:var(--text2)"><?=e($r['nama'])?></div></div>
      </div>
      <div style="font-size:12px;color:var(--muted);margin-bottom:10px"><i class="fas fa-map-marker-alt"></i> <?=e($r['lokasi']??'-')?></div>
      <div style="display:flex;justify-content:space-between;align-items:center">
        <span class="chip chip-teal"><?=$r['jml']?> buku</span>
        <div>
          <button class="icon-btn" onclick="editRak(<?=$r['id']?>,'<?=e(addslashes($r['kode']))?>','<?=e(addslashes($r['nama']))?>','<?=e(addslashes($r['lokasi']??''))?>')"><i class="fas fa-edit"></i></button>
          <form method="POST" style="display:inline" onsubmit="return confirm('Hapus rak ini?')"><input type="hidden" name="act" value="hapus"/><input type="hidden" name="hid" value="<?=$r['id']?>"/><button type="submit" class="icon-btn danger"><i class="fas fa-trash"></i></button></form>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="modal-overlay" id="modalRak">
  <div class="modal" style="max-width:400px">
    <div class="modal-header"><h3 id="modalRakTitle">Tambah Rak</h3><button class="modal-close" onclick="closeModal('modalRak')">&times;</button></div>
    <form method="POST">
      <input type="hidden" name="act" value="simpan"/><input type="hidden" name="edit_id" id="rakEid" value=""/>
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Kode Rak *</label><input type="text" name="kode" id="rakKode" class="form-control" required placeholder="Contoh: R-A"/></div>
        <div class="form-group"><label class="form-label">Nama Rak *</label><input type="text" name="nama" id="rakNama" class="form-control" required/></div>
        <div class="form-group"><label class="form-label">Lokasi</label><input type="text" name="lokasi" id="rakLokasi" class="form-control" placeholder="Contoh: Lantai 1 - Kiri"/></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalRak')">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
function editRak(id,kode,nama,lokasi){
    document.getElementById('rakEid').value=id;
    document.getElementById('rakKode').value=kode;
    document.getElementById('rakNama').value=nama;
    document.getElementById('rakLokasi').value=lokasi;
    document.getElementById('modalRakTitle').textContent='Edit Rak';
    document.getElementById('modalRak').classList.add('open');
}
</script>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
