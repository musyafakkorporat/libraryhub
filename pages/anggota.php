<?php
$pageTitle='Data Anggota'; $activeMenu='anggota'; $depth='../';
require_once __DIR__.'/../includes/header.php';
$db=getDB();
$aksi=$_GET['aksi']??'list'; $id=(int)($_GET['id']??0);

if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    if($act==='simpan'){
        $d=['nama'=>trim($_POST['nama']),'nik'=>trim($_POST['nik']??''),'tempat_lahir'=>trim($_POST['tempat_lahir']??''),
            'tgl_lahir'=>$_POST['tgl_lahir']?:null,'jenis_kelamin'=>$_POST['jenis_kelamin'],'alamat'=>trim($_POST['alamat']??''),
            'no_hp'=>trim($_POST['no_hp']??''),'email'=>trim($_POST['email']??''),'jenis_anggota'=>$_POST['jenis_anggota'],
            'instansi'=>trim($_POST['instansi']??''),'tgl_aktif_sampai'=>$_POST['tgl_aktif_sampai']?:null,'status'=>$_POST['status']??'aktif'];
        $eid=(int)($_POST['edit_id']??0);
        if($eid){
            $sets=implode(',',array_map(fn($k)=>"$k=:$k",array_keys($d)));
            $s=$db->prepare("UPDATE anggota SET $sets WHERE id=:id"); $d['id']=$eid; $s->execute($d);
            flash('success','Data anggota berhasil diperbarui.');
        } else {
            $last=$db->query("SELECT MAX(CAST(SUBSTRING(no_anggota,5) AS UNSIGNED)) FROM anggota WHERE no_anggota LIKE 'AGT-%'")->fetchColumn();
            $d['no_anggota']='AGT-'.str_pad(($last+1),4,'0',STR_PAD_LEFT);
            $d['tgl_daftar']=date('Y-m-d');
            $cols=implode(',',array_keys($d)); $vals=':'.implode(',:',array_keys($d));
            $db->prepare("INSERT INTO anggota ($cols) VALUES ($vals)")->execute($d);
            flash('success','Anggota '.$d['no_anggota'].' berhasil ditambahkan.');
        }
        header('Location: anggota.php'); exit;
    }
    if($act==='hapus'){$db->prepare("DELETE FROM anggota WHERE id=?")->execute([$_POST['hid']]);flash('success','Anggota dihapus.');header('Location: anggota.php');exit;}
}

if($aksi==='tambah'||$aksi==='edit'){
    $fd=[]; if($aksi==='edit'&&$id){$s=$db->prepare("SELECT * FROM anggota WHERE id=?");$s->execute([$id]);$fd=$s->fetch()?:[];}
?>
<div class="page-header">
  <div><div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span><a href="anggota.php">Anggota</a><span class="sep">/</span> <?=$aksi==='edit'?'Edit':'Tambah'?></div>
  <h1 class="page-title"><?=$aksi==='edit'?'Edit':'Tambah'?> Anggota</h1></div>
  <a href="anggota.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>
<form method="POST">
  <input type="hidden" name="act" value="simpan"/>
  <?php if($aksi==='edit'): ?><input type="hidden" name="edit_id" value="<?=$id?>"/><?php endif; ?>
  <div class="sc"><div class="sc-head"><h4>Data Anggota</h4></div><div class="sc-body">
    <div class="form-group"><label class="form-label">Nama Lengkap *</label><input type="text" name="nama" class="form-control" value="<?=e($fd['nama']??'')?>" required/></div>
    <div class="form-grid">
      <div class="form-group"><label class="form-label">NIK</label><input type="text" name="nik" class="form-control" value="<?=e($fd['nik']??'')?>"/></div>
      <div class="form-group"><label class="form-label">Tempat Lahir</label><input type="text" name="tempat_lahir" class="form-control" value="<?=e($fd['tempat_lahir']??'')?>"/></div>
      <div class="form-group"><label class="form-label">Tanggal Lahir</label><input type="date" name="tgl_lahir" class="form-control" value="<?=e($fd['tgl_lahir']??'')?>"/></div>
      <div class="form-group"><label class="form-label">Jenis Kelamin</label>
        <select name="jenis_kelamin" class="form-control"><option value="L" <?=($fd['jenis_kelamin']??'')==='L'?'selected':''?>>Laki-laki</option><option value="P" <?=($fd['jenis_kelamin']??'')==='P'?'selected':''?>>Perempuan</option></select>
      </div>
      <div class="form-group"><label class="form-label">No. HP</label><input type="text" name="no_hp" class="form-control" value="<?=e($fd['no_hp']??'')?>"/></div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?=e($fd['email']??'')?>"/></div>
      <div class="form-group"><label class="form-label">Jenis Anggota</label>
        <select name="jenis_anggota" class="form-control">
          <?php foreach(['siswa','mahasiswa','umum','guru','dosen'] as $j): ?><option value="<?=$j?>" <?=($fd['jenis_anggota']??'')===$j?'selected':''?>><?=ucfirst($j)?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Instansi / Sekolah</label><input type="text" name="instansi" class="form-control" value="<?=e($fd['instansi']??'')?>"/></div>
      <div class="form-group"><label class="form-label">Aktif Sampai</label><input type="date" name="tgl_aktif_sampai" class="form-control" value="<?=e($fd['tgl_aktif_sampai']??date('Y-m-d',strtotime('+1 year')))?>"/></div>
      <div class="form-group"><label class="form-label">Status</label>
        <select name="status" class="form-control"><option value="aktif" <?=($fd['status']??'')==='aktif'?'selected':''?>>Aktif</option><option value="nonaktif" <?=($fd['status']??'')==='nonaktif'?'selected':''?>>Nonaktif</option><option value="ditangguhkan" <?=($fd['status']??'')==='ditangguhkan'?'selected':''?>>Ditangguhkan</option></select>
      </div>
    </div>
    <div class="form-group"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2"><?=e($fd['alamat']??'')?></textarea></div>
    <div style="display:flex;justify-content:flex-end;gap:8px"><a href="anggota.php" class="btn btn-outline">Batal</a><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button></div>
  </div></div>
</form>
<?php require_once __DIR__.'/../includes/footer.php'; exit; }

// LIST
$search=trim($_GET['search']??''); $fJenis=$_GET['jenis']??''; $page=max(1,(int)($_GET['page']??1)); $perPage=15;
$where=['1=1']; $params=[];
if($search){$where[]="(a.nama LIKE ? OR a.no_anggota LIKE ? OR a.no_hp LIKE ?)";$params=array_merge($params,["%$search%","%$search%","%$search%"]);}
if($fJenis){$where[]="a.jenis_anggota=?";$params[]=$fJenis;}
$ws=implode(' AND ',$where);
$tot=$db->prepare("SELECT COUNT(*) FROM anggota a WHERE $ws");$tot->execute($params);$tot=$tot->fetchColumn();
$offset=($page-1)*$perPage;
$rows=$db->prepare("SELECT a.*,(SELECT COUNT(*) FROM peminjaman WHERE anggota_id=a.id AND status IN ('dipinjam','terlambat')) AS aktif_pinjam FROM anggota a WHERE $ws ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset");
$rows->execute($params); $rows=$rows->fetchAll();
$colors=['#0f766e','#4f46e5','#d97706','#dc2626','#9333ea','#0891b2'];
?>
<div class="page-header">
  <div><div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span> Data Anggota</div>
  <h1 class="page-title">Data Anggota</h1><p class="page-sub">Total <?=$tot?> anggota terdaftar</p></div>
  <a href="anggota.php?aksi=tambah" class="btn btn-primary"><i class="fas fa-plus"></i> Daftar Anggota</a>
</div>
<div class="card">
  <form method="GET" class="filter-bar">
    <input type="text" name="search" class="form-control" placeholder="Cari nama / no anggota..." value="<?=e($search)?>" style="max-width:280px"/>
    <select name="jenis" class="form-control" style="width:150px">
      <option value="">Semua Jenis</option>
      <?php foreach(['siswa','mahasiswa','umum','guru','dosen'] as $j): ?><option value="<?=$j?>" <?=$fJenis===$j?'selected':''?>><?=ucfirst($j)?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
    <a href="anggota.php" class="btn btn-outline">Reset</a>
  </form>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>No. Anggota</th><th>Nama</th><th>Jenis</th><th>Instansi</th><th>Aktif Pinjam</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach($rows as $i=>$a): ?>
        <tr>
          <td style="color:var(--muted)"><?=$offset+$i+1?></td>
          <td><code><?=e($a['no_anggota'])?></code></td>
          <td>
            <div class="avatar-cell">
              <div class="mini-avatar" style="background:<?=$colors[crc32($a['nama'])%6]?>"><?=strtoupper(substr($a['nama'],0,1))?></div>
              <div><div style="font-weight:500"><?=e($a['nama'])?></div><div style="font-size:11px;color:var(--muted)"><?=e($a['email']??'')?></div></div>
            </div>
          </td>
          <td><span class="chip chip-teal"><?=ucfirst($a['jenis_anggota'])?></span></td>
          <td style="font-size:12px;color:var(--text2)"><?=e($a['instansi']??'-')?></td>
          <td><?=$a['aktif_pinjam']>0?"<span class='chip chip-orange'>{$a['aktif_pinjam']} buku</span>":'<span style="color:var(--muted)">-</span>'?></td>
          <td><span class="status-badge <?=$a['status']==='aktif'?'sb-ok':($a['status']==='ditangguhkan'?'sb-warn':'sb-gray')?>"><?=ucfirst($a['status'])?></span></td>
          <td style="white-space:nowrap">
            <a href="anggota.php?aksi=edit&id=<?=$a['id']?>" class="icon-btn"><i class="fas fa-edit"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus anggota ini?')">
              <input type="hidden" name="act" value="hapus"/><input type="hidden" name="hid" value="<?=$a['id']?>"/>
              <button type="submit" class="icon-btn danger"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; if(empty($rows)): ?><tr><td colspan="8" style="text-align:center;padding:30px;color:var(--muted)">Tidak ada data</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if(ceil($tot/$perPage)>1): ?>
  <div class="pagination">
    <span class="page-info">Menampilkan <?=$offset+1?>–<?=min($offset+$perPage,$tot)?> dari <?=$tot?></span>
    <div class="page-btns"><?php for($i=1;$i<=ceil($tot/$perPage);$i++): ?><a href="?search=<?=urlencode($search)?>&jenis=<?=$fJenis?>&page=<?=$i?>" class="page-btn <?=$i==$page?'active':''?>"><?=$i?></a><?php endfor; ?></div>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
