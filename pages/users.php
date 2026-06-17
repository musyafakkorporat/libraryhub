<?php
$pageTitle='Manajemen User'; $activeMenu='users'; $depth='../';
require_once __DIR__.'/../includes/header.php';
requireRole('admin');
$db=getDB();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    if($act==='simpan'){
        $d=['nama'=>trim($_POST['nama']),'username'=>trim($_POST['username']),'email'=>trim($_POST['email']??''),'role'=>$_POST['role'],'status'=>$_POST['status']??'aktif'];
        $eid=(int)($_POST['edit_id']??0);
        if($eid){
            if($_POST['password']) $d['password']=password_hash($_POST['password'],PASSWORD_DEFAULT);
            $sets=implode(',',array_map(fn($k)=>"$k=:$k",array_keys($d)));
            $s=$db->prepare("UPDATE users SET $sets WHERE id=:id"); $d['id']=$eid; $s->execute($d);
        } else {
            if(!$_POST['password']){flash('error','Password wajib diisi.');header('Location: users.php');exit;}
            $d['password']=password_hash($_POST['password'],PASSWORD_DEFAULT);
            $cols=implode(',',array_keys($d));$vals=':'.implode(',:',array_keys($d));
            $db->prepare("INSERT INTO users ($cols) VALUES ($vals)")->execute($d);
        }
        flash('success','User berhasil disimpan.'); header('Location: users.php'); exit;
    }
    if($act==='hapus'){
        if($_POST['hid']==currentUser()['id']){flash('error','Tidak bisa hapus akun sendiri.');header('Location: users.php');exit;}
        $db->prepare("DELETE FROM users WHERE id=?")->execute([$_POST['hid']]);
        flash('success','User dihapus.'); header('Location: users.php'); exit;
    }
}

$rows=$db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>
<div class="page-header">
  <div><div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span> Manajemen User</div>
  <h1 class="page-title">Manajemen User</h1></div>
  <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Tambah User</button>
</div>
<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach($rows as $i=>$u): ?>
        <tr>
          <td style="color:var(--muted)"><?=$i+1?></td>
          <td><div class="avatar-cell"><div class="mini-avatar" style="background:<?=$u['role']==='admin'?'#dc2626':($u['role']==='petugas'?'#0f766e':'#4f46e5')?>"><?=strtoupper(substr($u['nama'],0,1))?></div><?=e($u['nama'])?></div></td>
          <td><code><?=e($u['username'])?></code></td>
          <td style="font-size:12px;color:var(--text2)"><?=e($u['email']??'-')?></td>
          <td><span class="chip <?=$u['role']==='admin'?'chip-red':($u['role']==='petugas'?'chip-teal':'chip-blue')?>"><?=ucfirst($u['role'])?></span></td>
          <td><span class="status-badge <?=$u['status']==='aktif'?'sb-ok':'sb-gray'?>"><?=ucfirst($u['status'])?></span></td>
          <td style="white-space:nowrap">
            <button class="icon-btn" onclick="editUser(<?=$u['id']?>,'<?=e(addslashes($u['nama']))?>','<?=e(addslashes($u['username']))?>','<?=e(addslashes($u['email']??''))?>','<?=$u['role']?>','<?=$u['status']?>')"><i class="fas fa-edit"></i></button>
            <?php if($u['id']!=currentUser()['id']): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus user ini?')"><input type="hidden" name="act" value="hapus"/><input type="hidden" name="hid" value="<?=$u['id']?>"/><button type="submit" class="icon-btn danger"><i class="fas fa-trash"></i></button></form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="modalUser">
  <div class="modal" style="max-width:440px">
    <div class="modal-header"><h3 id="modalTitle">Tambah User</h3><button class="modal-close" onclick="closeModal('modalUser')">&times;</button></div>
    <form method="POST">
      <input type="hidden" name="act" value="simpan"/><input type="hidden" name="edit_id" id="uEid" value=""/>
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Nama Lengkap *</label><input type="text" name="nama" id="uNama" class="form-control" required/></div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Username *</label><input type="text" name="username" id="uUsername" class="form-control" required/></div>
          <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="uEmail" class="form-control"/></div>
        </div>
        <div class="form-group"><label class="form-label">Password <span id="passHint" style="color:var(--muted)">(kosongkan jika tidak diubah)</span></label><input type="password" name="password" class="form-control"/></div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Role</label>
            <select name="role" id="uRole" class="form-control"><option value="petugas">Petugas</option><option value="admin">Admin</option><option value="anggota">Anggota</option></select>
          </div>
          <div class="form-group"><label class="form-label">Status</label>
            <select name="status" id="uStatus" class="form-control"><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalUser')">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
function openModal(){
    document.getElementById('uEid').value='';
    document.getElementById('uNama').value='';
    document.getElementById('uUsername').value='';
    document.getElementById('uEmail').value='';
    document.getElementById('uRole').value='petugas';
    document.getElementById('uStatus').value='aktif';
    document.getElementById('modalTitle').textContent='Tambah User';
    document.getElementById('passHint').style.display='none';
    document.getElementById('modalUser').classList.add('open');
}
function editUser(id,nama,username,email,role,status){
    document.getElementById('uEid').value=id;
    document.getElementById('uNama').value=nama;
    document.getElementById('uUsername').value=username;
    document.getElementById('uEmail').value=email;
    document.getElementById('uRole').value=role;
    document.getElementById('uStatus').value=status;
    document.getElementById('modalTitle').textContent='Edit User';
    document.getElementById('passHint').style.display='';
    document.getElementById('modalUser').classList.add('open');
}
</script>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
