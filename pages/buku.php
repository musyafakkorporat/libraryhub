<?php
$pageTitle='Katalog Buku'; $activeMenu='buku'; $depth='../';
require_once __DIR__.'/../includes/header.php';
$db=getDB();

$aksi=$_GET['aksi']??'list'; $id=(int)($_GET['id']??0);

// POST handlers
if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    if($act==='simpan'){
        $d=[
            'judul'=>trim($_POST['judul']),
            'pengarang'=>trim($_POST['pengarang']),
            'penerbit'=>trim($_POST['penerbit']??''),
            'tahun_terbit'=>$_POST['tahun_terbit']?:null,
            'isbn'=>trim($_POST['isbn']??''),
            'kategori_id'=>$_POST['kategori_id']?:null,
            'rak_id'=>$_POST['rak_id']?:null,
            'jumlah_total'=>(int)$_POST['jumlah_total'],
            'jumlah_tersedia'=>(int)$_POST['jumlah_tersedia'],
            'bahasa'=>$_POST['bahasa']??'Indonesia',
            'halaman'=>$_POST['halaman']?:null,
            'sinopsis'=>trim($_POST['sinopsis']??''),
            'status'=>$_POST['status']??'tersedia',
        ];
        $eid=(int)($_POST['edit_id']??0);
        if($eid){
            $sets=implode(',',array_map(fn($k)=>"$k=:$k",array_keys($d)));
            $s=$db->prepare("UPDATE buku SET $sets WHERE id=:id");
            $d['id']=$eid; $s->execute($d);
            flash('success','Data buku berhasil diperbarui.');
        } else {
            // generate kode
            $last=$db->query("SELECT MAX(CAST(SUBSTRING(kode_buku,4) AS UNSIGNED)) FROM buku WHERE kode_buku LIKE 'BK-%'")->fetchColumn();
            $d['kode_buku']='BK-'.str_pad(($last+1),4,'0',STR_PAD_LEFT);
            $cols=implode(',',array_keys($d));
            $vals=':'.implode(',:',array_keys($d));
            $db->prepare("INSERT INTO buku ($cols) VALUES ($vals)")->execute($d);
            flash('success','Buku '.$d['kode_buku'].' berhasil ditambahkan.');
        }
        header('Location: buku.php'); exit;
    }
    if($act==='hapus'){
        $db->prepare("DELETE FROM buku WHERE id=?")->execute([$_POST['hapus_id']]);
        flash('success','Buku berhasil dihapus.'); header('Location: buku.php'); exit;
    }
}

// Detail
if($aksi==='detail'&&$id){
    $s=$db->prepare("SELECT b.*,k.nama AS kat_nama,r.nama AS rak_nama,r.lokasi AS rak_lokasi FROM buku b LEFT JOIN kategori k ON k.id=b.kategori_id LEFT JOIN rak r ON r.id=b.rak_id WHERE b.id=?");
    $s->execute([$id]); $row=$s->fetch();
    if(!$row){flash('error','Data tidak ditemukan.');header('Location: buku.php');exit;}
    $ulasan=$db->prepare("SELECT u.*,a.nama AS anggota FROM ulasan u JOIN anggota a ON a.id=u.anggota_id WHERE u.buku_id=? ORDER BY u.created_at DESC");
    $ulasan->execute([$id]); $reviews=$ulasan->fetchAll();
    $avgRating=$db->prepare("SELECT ROUND(AVG(rating),1) FROM ulasan WHERE buku_id=?");
    $avgRating->execute([$id]); $avgRating=$avgRating->fetchColumn()?:0;
    $covers=['c1','c2','c3','c4','c5'];
    $cc=$covers[$id%5];
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span><a href="buku.php">Buku</a><span class="sep">/</span> Detail</div>
    <h1 class="page-title">Detail Buku</h1>
  </div>
  <div class="header-actions">
    <a href="buku.php?aksi=edit&id=<?=$id?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
    <a href="buku.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Kembali</a>
  </div>
</div>
<div class="grid-2">
  <div>
    <div class="sc">
      <div class="sc-body" style="display:flex;gap:20px;align-items:flex-start">
        <div class="book-cover <?=$cc?>" style="width:100px;height:130px;border-radius:10px;font-size:36px;flex-shrink:0"><i class="fas fa-book"></i></div>
        <div>
          <h2 style="font-size:16px;font-weight:700;margin-bottom:4px"><?=e($row['judul'])?></h2>
          <p style="color:var(--text2);font-size:13px;margin-bottom:8px"><?=e($row['pengarang'])?></p>
          <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
            <span class="chip chip-teal"><?=e($row['kat_nama']??'Uncategorized')?></span>
            <span class="chip chip-blue"><?=e($row['bahasa'])?></span>
            <span class="chip <?=$row['status']==='tersedia'?'chip-green':'chip-red'?>"><?=ucfirst($row['status'])?></span>
          </div>
          <div style="font-size:13px;color:var(--text2)">
            ⭐ <?=$avgRating?> / 5 &bull; <?=count($reviews)?> ulasan
          </div>
        </div>
      </div>
    </div>
    <div class="sc">
      <div class="sc-head"><h4><i class="fas fa-info-circle"></i> Informasi Buku</h4></div>
      <div class="sc-body">
        <table class="detail-table">
          <tr><td>Kode Buku</td><td><code><?=e($row['kode_buku'])?></code></td></tr>
          <tr><td>ISBN</td><td><?=e($row['isbn']??'-')?></td></tr>
          <tr><td>Penerbit</td><td><?=e($row['penerbit']??'-')?></td></tr>
          <tr><td>Tahun Terbit</td><td><?=e($row['tahun_terbit']??'-')?></td></tr>
          <tr><td>Halaman</td><td><?=$row['halaman']?e($row['halaman']).' hal.':'-'?></td></tr>
          <tr><td>Rak</td><td><?=e($row['rak_nama']??'-')?> <?=$row['rak_lokasi']?"(${row['rak_lokasi']})":''?></td></tr>
          <tr><td>Stok Total</td><td><?=$row['jumlah_total']?></td></tr>
          <tr><td>Stok Tersedia</td><td><strong style="color:<?=$row['jumlah_tersedia']>0?'#059669':'#dc2626'?>"><?=$row['jumlah_tersedia']?></strong></td></tr>
        </table>
      </div>
    </div>
  </div>
  <div>
    <div class="sc">
      <div class="sc-head"><h4><i class="fas fa-align-left"></i> Sinopsis</h4></div>
      <div class="sc-body" style="font-size:13px;color:var(--text2);line-height:1.7">
        <?=$row['sinopsis']?nl2br(e($row['sinopsis'])):'<em>Tidak ada sinopsis.</em>'?>
      </div>
    </div>
    <div class="sc">
      <div class="sc-head"><h4><i class="fas fa-star"></i> Ulasan Pembaca (<?=count($reviews)?>)</h4></div>
      <div class="sc-body" style="max-height:280px;overflow-y:auto">
        <?php if($reviews): foreach($reviews as $rev): ?>
        <div style="border-bottom:1px solid var(--border);padding-bottom:10px;margin-bottom:10px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
            <strong style="font-size:13px"><?=e($rev['anggota'])?></strong>
            <span style="color:#fbbf24"><?=str_repeat('★',$rev['rating'])?>
              <span style="color:#e2e8f0"><?=str_repeat('★',5-$rev['rating'])?></span>
            </span>
          </div>
          <p style="font-size:12px;color:var(--text2)"><?=e($rev['komentar']??'')?></p>
          <div style="font-size:11px;color:var(--muted)"><?=date('d M Y',strtotime($rev['created_at']))?></div>
        </div>
        <?php endforeach; else: ?><p style="font-size:13px;color:var(--muted)">Belum ada ulasan.</p><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; exit; }

// Form Tambah/Edit
if($aksi==='tambah'||$aksi==='edit'){
    $fd=[]; if($aksi==='edit'&&$id){$s=$db->prepare("SELECT * FROM buku WHERE id=?");$s->execute([$id]);$fd=$s->fetch()?:[];}
    $katList=$db->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();
    $rakList=$db->query("SELECT * FROM rak ORDER BY kode")->fetchAll();
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span><a href="buku.php">Buku</a><span class="sep">/</span> <?=$aksi==='edit'?'Edit':'Tambah'?></div>
    <h1 class="page-title"><?=$aksi==='edit'?'Edit':'Tambah'?> Buku</h1>
  </div>
  <a href="buku.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>
<form method="POST">
  <input type="hidden" name="act" value="simpan"/>
  <?php if($aksi==='edit'): ?><input type="hidden" name="edit_id" value="<?=$id?>"/><?php endif; ?>
  <div class="sc">
    <div class="sc-head"><h4><i class="fas fa-book"></i> Informasi Buku</h4></div>
    <div class="sc-body">
      <div class="form-group"><label class="form-label">Judul Buku *</label><input type="text" name="judul" class="form-control" value="<?=e($fd['judul']??'')?>" required/></div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Pengarang *</label><input type="text" name="pengarang" class="form-control" value="<?=e($fd['pengarang']??'')?>" required/></div>
        <div class="form-group"><label class="form-label">Penerbit</label><input type="text" name="penerbit" class="form-control" value="<?=e($fd['penerbit']??'')?>"/></div>
        <div class="form-group"><label class="form-label">ISBN</label><input type="text" name="isbn" class="form-control" value="<?=e($fd['isbn']??'')?>"/></div>
        <div class="form-group"><label class="form-label">Tahun Terbit</label><input type="number" name="tahun_terbit" class="form-control" value="<?=e($fd['tahun_terbit']??date('Y'))?>" min="1900" max="<?=date('Y')?>"/></div>
        <div class="form-group"><label class="form-label">Kategori</label>
          <select name="kategori_id" class="form-control">
            <option value="">-- Pilih Kategori --</option>
            <?php foreach($katList as $k): ?><option value="<?=$k['id']?>" <?=($fd['kategori_id']??'')==$k['id']?'selected':''?>><?=e($k['nama'])?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Lokasi Rak</label>
          <select name="rak_id" class="form-control">
            <option value="">-- Pilih Rak --</option>
            <?php foreach($rakList as $r): ?><option value="<?=$r['id']?>" <?=($fd['rak_id']??'')==$r['id']?'selected':''?>><?=e($r['kode'].' — '.$r['nama'])?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Jumlah Total *</label><input type="number" name="jumlah_total" class="form-control" value="<?=e($fd['jumlah_total']??1)?>" min="1" required/></div>
        <div class="form-group"><label class="form-label">Jumlah Tersedia *</label><input type="number" name="jumlah_tersedia" class="form-control" value="<?=e($fd['jumlah_tersedia']??1)?>" min="0" required/></div>
        <div class="form-group"><label class="form-label">Bahasa</label>
          <select name="bahasa" class="form-control">
            <?php foreach(['Indonesia','Inggris','Arab','Lainnya'] as $b): ?><option value="<?=$b?>" <?=($fd['bahasa']??'')===$b?'selected':''?>><?=$b?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Jumlah Halaman</label><input type="number" name="halaman" class="form-control" value="<?=e($fd['halaman']??'')?>"/></div>
        <div class="form-group"><label class="form-label">Status</label>
          <select name="status" class="form-control">
            <option value="tersedia" <?=($fd['status']??'')==='tersedia'?'selected':''?>>Tersedia</option>
            <option value="habis" <?=($fd['status']??'')==='habis'?'selected':''?>>Habis</option>
            <option value="tidak_aktif" <?=($fd['status']??'')==='tidak_aktif'?'selected':''?>>Tidak Aktif</option>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Sinopsis</label><textarea name="sinopsis" class="form-control" rows="4"><?=e($fd['sinopsis']??'')?></textarea></div>
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <a href="buku.php" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      </div>
    </div>
  </div>
</form>
<?php require_once __DIR__.'/../includes/footer.php'; exit; }

// LIST
$search=$_GET['search']??''; $fKat=(int)($_GET['kat']??0); $fStatus=$_GET['status']??''; $view=$_GET['view']??'table';
$page=max(1,(int)($_GET['page']??1)); $perPage=16;
$where=['1=1']; $params=[];
if($search){$where[]="(b.judul LIKE ? OR b.pengarang LIKE ? OR b.kode_buku LIKE ?)";$params=array_merge($params,["%$search%","%$search%","%$search%"]);}
if($fKat){$where[]="b.kategori_id=?";$params[]=$fKat;}
if($fStatus){$where[]="b.status=?";$params[]=$fStatus;}
$ws=implode(' AND ',$where);
$tot=$db->prepare("SELECT COUNT(*) FROM buku b WHERE $ws");$tot->execute($params);$tot=$tot->fetchColumn();
$offset=($page-1)*$perPage;
$stmt=$db->prepare("SELECT b.*,k.nama AS kat_nama,r.kode AS rak_kode FROM buku b LEFT JOIN kategori k ON k.id=b.kategori_id LEFT JOIN rak r ON r.id=b.rak_id WHERE $ws ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params); $rows=$stmt->fetchAll();
$katList=$db->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();
$covers=['c1','c2','c3','c4','c5'];
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span> Katalog Buku</div>
    <h1 class="page-title">Katalog Buku</h1>
    <p class="page-sub">Total <?=$tot?> judul buku terdaftar</p>
  </div>
  <div class="header-actions">
    <a href="?view=<?=$view==='table'?'grid':'table'?>&search=<?=e($search)?>&kat=<?=$fKat?>" class="btn btn-outline">
      <i class="fas fa-<?=$view==='table'?'th-large':'list'?>"></i> <?=$view==='table'?'Grid':'Tabel'?>
    </a>
    <a href="buku.php?aksi=tambah" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Buku</a>
  </div>
</div>

<div class="card">
  <form method="GET" class="filter-bar">
    <input type="hidden" name="view" value="<?=e($view)?>"/>
    <input type="text" name="search" class="form-control" placeholder="Cari judul / pengarang..." value="<?=e($search)?>" style="max-width:280px"/>
    <select name="kat" class="form-control" style="width:170px">
      <option value="">Semua Kategori</option>
      <?php foreach($katList as $k): ?><option value="<?=$k['id']?>" <?=$fKat==$k['id']?'selected':''?>><?=e($k['nama'])?></option><?php endforeach; ?>
    </select>
    <select name="status" class="form-control" style="width:140px">
      <option value="">Semua Status</option>
      <option value="tersedia" <?=$fStatus==='tersedia'?'selected':''?>>Tersedia</option>
      <option value="habis" <?=$fStatus==='habis'?'selected':''?>>Habis</option>
      <option value="tidak_aktif" <?=$fStatus==='tidak_aktif'?'selected':''?>>Tidak Aktif</option>
    </select>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
    <a href="buku.php" class="btn btn-outline">Reset</a>
  </form>

  <?php if($view==='grid'): ?>
  <div class="book-grid">
    <?php foreach($rows as $i=>$b): ?>
    <div class="book-card">
      <div class="book-cover <?=$covers[$i%5]?>"><i class="fas fa-book"></i></div>
      <div class="book-info">
        <div class="book-title" title="<?=e($b['judul'])?>"><?=e($b['judul'])?></div>
        <div class="book-author"><?=e($b['pengarang'])?></div>
        <div class="book-meta">
          <span class="chip <?=$b['jumlah_tersedia']>0?'chip-green':'chip-red'?>" style="font-size:10px"><?=$b['jumlah_tersedia']?> tersedia</span>
          <div style="display:flex;gap:4px">
            <a href="buku.php?aksi=detail&id=<?=$b['id']?>" class="icon-btn" style="padding:4px 6px"><i class="fas fa-eye"></i></a>
            <a href="buku.php?aksi=edit&id=<?=$b['id']?>" class="icon-btn" style="padding:4px 6px"><i class="fas fa-edit"></i></a>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; if(empty($rows)): ?><div style="padding:40px;text-align:center;color:var(--muted);grid-column:1/-1">Tidak ada buku ditemukan</div><?php endif; ?>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Kode</th><th>Judul</th><th>Pengarang</th><th>Kategori</th><th>Rak</th><th>Tersedia</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach($rows as $i=>$b): ?>
        <tr>
          <td style="color:var(--muted)"><?=$offset+$i+1?></td>
          <td><code><?=e($b['kode_buku'])?></code></td>
          <td>
            <div style="font-weight:500;max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=e($b['judul'])?></div>
            <div style="font-size:11px;color:var(--muted)"><?=e($b['penerbit']??'').($b['tahun_terbit']?' · '.$b['tahun_terbit']:'')?></div>
          </td>
          <td><?=e($b['pengarang'])?></td>
          <td><?=$b['kat_nama']?'<span class="chip chip-teal">'.e($b['kat_nama']).'</span>':'-'?></td>
          <td><?=$b['rak_kode']?'<code>'.e($b['rak_kode']).'</code>':'-'?></td>
          <td>
            <span style="font-weight:700;color:<?=$b['jumlah_tersedia']>0?'#059669':'#dc2626'?>"><?=$b['jumlah_tersedia']?></span>
            <span style="color:var(--muted);font-size:11px">/ <?=$b['jumlah_total']?></span>
          </td>
          <td><span class="status-badge <?=$b['status']==='tersedia'?'sb-ok':($b['status']==='habis'?'sb-warn':'sb-gray')?>"><?=ucfirst($b['status'])?></span></td>
          <td style="white-space:nowrap">
            <a href="buku.php?aksi=detail&id=<?=$b['id']?>" class="icon-btn"><i class="fas fa-eye"></i></a>
            <a href="buku.php?aksi=edit&id=<?=$b['id']?>" class="icon-btn"><i class="fas fa-edit"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus buku ini?')">
              <input type="hidden" name="act" value="hapus"/><input type="hidden" name="hapus_id" value="<?=$b['id']?>"/>
              <button type="submit" class="icon-btn danger"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; if(empty($rows)): ?><tr><td colspan="9" style="text-align:center;padding:36px;color:var(--muted)">Tidak ada data buku</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <?php if(ceil($tot/$perPage)>1): ?>
  <div class="pagination">
    <span class="page-info">Menampilkan <?=$offset+1?>–<?=min($offset+$perPage,$tot)?> dari <?=$tot?></span>
    <div class="page-btns">
      <?php for($i=1;$i<=ceil($tot/$perPage);$i++): ?>
      <a href="?search=<?=urlencode($search)?>&kat=<?=$fKat?>&status=<?=$fStatus?>&view=<?=$view?>&page=<?=$i?>" class="page-btn <?=$i==$page?'active':''?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
