<?php
$pageTitle='Kategori Buku'; $activeMenu='kategori'; $depth='../';
require_once __DIR__.'/../includes/header.php';
$db=getDB();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    if($act==='simpan'){
        $nama=trim($_POST['nama']); $desk=trim($_POST['deskripsi']??'');
        $eid=(int)($_POST['edit_id']??0);
        if($eid) $db->prepare("UPDATE kategori SET nama=?,deskripsi=? WHERE id=?")->execute([$nama,$desk,$eid]);
        else $db->prepare("INSERT INTO kategori (nama,deskripsi) VALUES (?,?)")->execute([$nama,$desk]);
        flash('success','Kategori berhasil disimpan.'); header('Location: kategori.php'); exit;
    }
    if($act==='hapus'){
        $db->prepare("DELETE FROM kategori WHERE id=?")->execute([$_POST['hid']]);
        flash('success','Kategori dihapus.'); header('Location: kategori.php'); exit;
    }
}

$rows=$db->query("SELECT k.*,(SELECT COUNT(*) FROM buku WHERE kategori_id=k.id) AS jml_buku FROM kategori k ORDER BY k.nama")->fetchAll();
?>
<div class="page-header">
  <div><div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span> Kategori</div>
  <h1 class="page-title">Kategori Buku</h1></div>
  <button class="btn btn-primary" onclick="document.getElementById('modalKat').classList.add('open')"><i class="fas fa-plus"></i> Tambah Kategori</button>
</div>
<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Nama Kategori</th><th>Deskripsi</th><th>Jumlah Buku</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach($rows as $i=>$r): ?>
        <tr>
          <td style="color:var(--muted)"><?=$i+1?></td>
          <td><strong><?=e($r['nama'])?></strong></td>
          <td style="color:var(--text2);font-size:12px"><?=e($r['deskripsi']??'-')?></td>
          <td><span class="chip chip-teal"><?=$r['jml_buku']?> buku</span></td>
          <td style="white-space:nowrap">
            <button class="icon-btn" onclick="editKat(<?=$r['id']?>,'<?=e(addslashes($r['nama']))?>','<?=e(addslashes($r['deskripsi']??''))?>')"><i class="fas fa-edit"></i></button>
            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus kategori ini?')">
              <input type="hidden" name="act" value="hapus"/><input type="hidden" name="hid" value="<?=$r['id']?>"/>
              <button type="submit" class="icon-btn danger"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="modalKat">
  <div class="modal" style="max-width:420px">
    <div class="modal-header"><h3 id="modalKatTitle">Tambah Kategori</h3><button class="modal-close" onclick="closeModal('modalKat')">&times;</button></div>
    <form method="POST">
      <input type="hidden" name="act" value="simpan"/>
      <input type="hidden" name="edit_id" id="katEditId" value=""/>
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Nama Kategori *</label><input type="text" name="nama" id="katNama" class="form-control" required/></div>
        <div class="form-group"><label class="form-label">Deskripsi</label><textarea name="deskripsi" id="katDesk" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalKat')">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
function editKat(id,nama,desk){
    document.getElementById('katEditId').value=id;
    document.getElementById('katNama').value=nama;
    document.getElementById('katDesk').value=desk;
    document.getElementById('modalKatTitle').textContent='Edit Kategori';
    document.getElementById('modalKat').classList.add('open');
}
</script>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
