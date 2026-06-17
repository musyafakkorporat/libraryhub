<?php
$pageTitle='Peminjaman'; $activeMenu='peminjaman'; $depth='../';
require_once __DIR__.'/../includes/header.php';
$db=getDB();
$aksi=$_GET['aksi']??'list'; $id=(int)($_GET['id']??0);

if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    if($act==='simpan'){
        // Validasi buku tersedia
        $bukuIds = array_filter(array_map('intval', $_POST['buku_id']??[]));
        if(empty($bukuIds)){flash('error','Pilih minimal satu buku.');header('Location: peminjaman.php?aksi=tambah');exit;}
        if(count($bukuIds)>MAKS_PINJAM){flash('error','Maksimal '.MAKS_PINJAM.' buku per peminjaman.');header('Location: peminjaman.php?aksi=tambah');exit;}

        $anggotaId=(int)$_POST['anggota_id'];
        $petugasId=currentUser()['id'];
        $tglPinjam=$_POST['tgl_pinjam']?:date('Y-m-d');
        $tglRencana=$_POST['tgl_kembali_rencana']?:date('Y-m-d',strtotime("+".LAMA_PINJAM." days"));

        // Cek anggota aktif
        $ang=$db->prepare("SELECT * FROM anggota WHERE id=? AND status='aktif'");
        $ang->execute([$anggotaId]); $ang=$ang->fetch();
        if(!$ang){flash('error','Anggota tidak ditemukan atau tidak aktif.');header('Location: peminjaman.php?aksi=tambah');exit;}

        // Generate kode pinjam
        $last=$db->query("SELECT MAX(CAST(SUBSTRING(kode_pinjam,9) AS UNSIGNED)) FROM peminjaman WHERE kode_pinjam LIKE 'PNJ-".date('Y')."%'")->fetchColumn();
        $kode='PNJ-'.date('Y').str_pad(($last+1),4,'0',STR_PAD_LEFT);

        $db->beginTransaction();
        try {
            $db->prepare("INSERT INTO peminjaman (kode_pinjam,anggota_id,petugas_id,tgl_pinjam,tgl_kembali_rencana,status) VALUES (?,?,?,?,?,'dipinjam')")
               ->execute([$kode,$anggotaId,$petugasId,$tglPinjam,$tglRencana]);
            $pinjamId=$db->lastInsertId();

            foreach($bukuIds as $bid){
                // Kurangi stok
                $buku=$db->prepare("SELECT jumlah_tersedia FROM buku WHERE id=? FOR UPDATE");
                $buku->execute([$bid]); $buku=$buku->fetch();
                if(!$buku||$buku['jumlah_tersedia']<1){throw new Exception("Buku ID $bid tidak tersedia.");}
                $db->prepare("UPDATE buku SET jumlah_tersedia=jumlah_tersedia-1 WHERE id=?")->execute([$bid]);
                $db->prepare("INSERT INTO peminjaman_detail (peminjaman_id,buku_id) VALUES (?,?)")->execute([$pinjamId,$bid]);
            }
            $db->commit();
            flash('success',"Peminjaman $kode berhasil dicatat.");
            header('Location: peminjaman.php'); exit;
        } catch(Exception $e){
            $db->rollBack();
            flash('error','Gagal: '.$e->getMessage());
            header('Location: peminjaman.php?aksi=tambah'); exit;
        }
    }
}

// Form Tambah
if($aksi==='tambah'){
    $anggotaList=$db->query("SELECT * FROM anggota WHERE status='aktif' ORDER BY nama")->fetchAll();
    $bukuList=$db->query("SELECT b.*,k.nama AS kat_nama FROM buku b LEFT JOIN kategori k ON k.id=b.kategori_id WHERE b.jumlah_tersedia>0 ORDER BY b.judul")->fetchAll();
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span><a href="peminjaman.php">Peminjaman</a><span class="sep">/</span> Tambah</div>
    <h1 class="page-title">Catat Peminjaman Baru</h1>
  </div>
  <a href="peminjaman.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>
<form method="POST">
  <input type="hidden" name="act" value="simpan"/>
  <div class="sc">
    <div class="sc-head"><h4><i class="fas fa-user"></i> Data Anggota</h4></div>
    <div class="sc-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Pilih Anggota *</label>
          <select name="anggota_id" class="form-control" required>
            <option value="">-- Pilih Anggota --</option>
            <?php foreach($anggotaList as $a): ?><option value="<?=$a['id']?>"><?=e($a['no_anggota'].' — '.$a['nama'])?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Tanggal Pinjam</label>
          <input type="date" name="tgl_pinjam" class="form-control" value="<?=date('Y-m-d')?>"/>
        </div>
        <div class="form-group">
          <label class="form-label">Tanggal Kembali (Rencana)</label>
          <input type="date" name="tgl_kembali_rencana" class="form-control" value="<?=date('Y-m-d',strtotime('+'.LAMA_PINJAM.' days'))?>"/>
        </div>
      </div>
    </div>
  </div>
  <div class="sc">
    <div class="sc-head"><h4><i class="fas fa-book"></i> Pilih Buku (maks. <?=MAKS_PINJAM?>)</h4></div>
    <div class="sc-body">
      <div style="margin-bottom:12px">
        <input type="text" class="form-control" placeholder="Cari buku..." id="cariB" style="max-width:320px" oninput="filterBuku(this.value)"/>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;max-height:400px;overflow-y:auto" id="bukuGrid">
        <?php foreach($bukuList as $b): ?>
        <label class="book-pick-card" data-judul="<?=strtolower(e($b['judul']))?>" data-pengarang="<?=strtolower(e($b['pengarang']))?>">
          <input type="checkbox" name="buku_id[]" value="<?=$b['id']?>" style="display:none"/>
          <div class="bpc-inner">
            <div style="font-weight:600;font-size:12px;margin-bottom:3px;line-height:1.3"><?=e($b['judul'])?></div>
            <div style="font-size:11px;color:var(--text2)"><?=e($b['pengarang'])?></div>
            <div style="margin-top:6px;display:flex;justify-content:space-between;align-items:center">
              <span class="chip chip-teal" style="font-size:10px"><?=e($b['kat_nama']??'-')?></span>
              <span style="font-size:11px;color:#059669;font-weight:600"><?=$b['jumlah_tersedia']?> tersedia</span>
            </div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
      <p style="font-size:12px;color:var(--muted);margin-top:10px" id="pilihanInfo">0 buku dipilih</p>
    </div>
  </div>
  <div style="display:flex;justify-content:flex-end;gap:8px">
    <a href="peminjaman.php" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Catat Peminjaman</button>
  </div>
</form>
<style>
.book-pick-card{cursor:pointer;display:block;height:100%}
.bpc-inner{border:2px solid var(--border);border-radius:var(--r);padding:12px;transition:all .15s;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:space-between}
.book-pick-card input:checked+.bpc-inner{border-color:var(--primary);background:#f0fdfa}
.bpc-inner:hover{border-color:var(--primary-light)}
</style>
<script>
document.querySelectorAll('.book-pick-card input').forEach(cb=>{
    cb.addEventListener('change',()=>{
        const checked=document.querySelectorAll('.book-pick-card input:checked');
        document.getElementById('pilihanInfo').textContent=checked.length+' buku dipilih';
        if(checked.length>3){cb.checked=false;alert('Maksimal 3 buku.');}
    });
});
function filterBuku(val){
    val=val.toLowerCase();
    document.querySelectorAll('.book-pick-card').forEach(c=>{
        c.style.display=(c.dataset.judul.includes(val)||c.dataset.pengarang.includes(val))?'block':'none';
    });
}
</script>
<?php require_once __DIR__.'/../includes/footer.php'; exit; }

// LIST
$fStatus=$_GET['status']??''; $search=trim($_GET['search']??'');
$page=max(1,(int)($_GET['page']??1)); $perPage=15;
$where=['1=1']; $params=[];
// Auto-update terlambat
$db->exec("UPDATE peminjaman SET status='terlambat' WHERE status='dipinjam' AND tgl_kembali_rencana < CURDATE()");
if($fStatus){$where[]="p.status=?";$params[]=$fStatus;}
if($search){$where[]="(a.nama LIKE ? OR p.kode_pinjam LIKE ? OR a.no_anggota LIKE ?)";$params=array_merge($params,["%$search%","%$search%","%$search%"]);}
$ws=implode(' AND ',$where);
$tot=$db->prepare("SELECT COUNT(*) FROM peminjaman p JOIN anggota a ON a.id=p.anggota_id WHERE $ws");
$tot->execute($params); $tot=$tot->fetchColumn();
$offset=($page-1)*$perPage;
$stmt=$db->prepare("
    SELECT p.*,a.nama AS anggota_nama,a.no_anggota,
           GROUP_CONCAT(b.judul SEPARATOR ' | ') AS judul_buku,
           COUNT(pd.id) AS jml_buku
    FROM peminjaman p
    JOIN anggota a ON a.id=p.anggota_id
    JOIN peminjaman_detail pd ON pd.peminjaman_id=p.id
    JOIN buku b ON b.id=pd.buku_id
    WHERE $ws
    GROUP BY p.id ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset
");
$stmt->execute($params); $rows=$stmt->fetchAll();

$statusCounts=$db->query("SELECT status, COUNT(*) AS c FROM peminjaman GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$sbMap=['dipinjam'=>'sb-info','dikembalikan'=>'sb-ok','terlambat'=>'sb-err','hilang'=>'sb-warn'];
$slabel=['dipinjam'=>'Dipinjam','dikembalikan'=>'Dikembalikan','terlambat'=>'Terlambat','hilang'=>'Hilang'];
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="../index.php">Dashboard</a><span class="sep">/</span> Peminjaman</div>
    <h1 class="page-title">Data Peminjaman</h1>
    <p class="page-sub">Total <?=$tot?> transaksi peminjaman</p>
  </div>
  <a href="peminjaman.php?aksi=tambah" class="btn btn-primary"><i class="fas fa-plus"></i> Catat Peminjaman</a>
</div>

<div class="card">
  <div class="tab-bar">
    <a href="peminjaman.php" class="tab <?=!$fStatus?'active':''?>">Semua <span class="tab-count"><?=array_sum($statusCounts)?></span></a>
    <a href="peminjaman.php?status=dipinjam" class="tab <?=$fStatus==='dipinjam'?'active':''?>">Dipinjam <span class="tab-count warn"><?=$statusCounts['dipinjam']??0?></span></a>
    <a href="peminjaman.php?status=terlambat" class="tab <?=$fStatus==='terlambat'?'active':''?>">Terlambat <span class="tab-count err"><?=$statusCounts['terlambat']??0?></span></a>
    <a href="peminjaman.php?status=dikembalikan" class="tab <?=$fStatus==='dikembalikan'?'active':''?>">Dikembalikan <span class="tab-count ok"><?=$statusCounts['dikembalikan']??0?></span></a>
  </div>
  <form method="GET" class="filter-bar">
    <input type="hidden" name="status" value="<?=e($fStatus)?>"/>
    <input type="text" name="search" class="form-control" placeholder="Cari nama / kode pinjam..." value="<?=e($search)?>" style="max-width:300px"/>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
    <a href="peminjaman.php?status=<?=e($fStatus)?>" class="btn btn-outline">Reset</a>
  </form>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Kode Pinjam</th><th>Anggota</th><th>Buku</th><th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r):
          $isLate=in_array($r['status'],['dipinjam','terlambat'])&&strtotime($r['tgl_kembali_rencana'])<time();
        ?>
        <tr>
          <td><code><?=e($r['kode_pinjam'])?></code></td>
          <td>
            <div style="font-weight:500"><?=e($r['anggota_nama'])?></div>
            <div style="font-size:11px;color:var(--muted)"><?=e($r['no_anggota'])?></div>
          </td>
          <td style="max-width:240px">
            <div style="font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=e($r['judul_buku'])?></div>
            <div style="font-size:11px;color:var(--muted)"><?=$r['jml_buku']?> buku</div>
          </td>
          <td style="font-size:12px"><?=date('d M Y',strtotime($r['tgl_pinjam']))?></td>
          <td style="font-size:12px;color:<?=$isLate?'#dc2626':'var(--text2)'?>;font-weight:<?=$isLate?600:400?>">
            <?=date('d M Y',strtotime($r['tgl_kembali_rencana']))?>
          </td>
          <td><span class="status-badge <?=$sbMap[$r['status']]?>"><?=$slabel[$r['status']]?></span></td>
          <td style="white-space:nowrap">
            <?php if(in_array($r['status'],['dipinjam','terlambat'])): ?>
            <a href="pengembalian.php?pid=<?=$r['id']?>" class="icon-btn" title="Kembalikan"><i class="fas fa-undo-alt"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; if(empty($rows)): ?><tr><td colspan="7" style="text-align:center;padding:36px;color:var(--muted)">Tidak ada data</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if(ceil($tot/$perPage)>1): ?>
  <div class="pagination">
    <span class="page-info">Menampilkan <?=$offset+1?>–<?=min($offset+$perPage,$tot)?> dari <?=$tot?></span>
    <div class="page-btns">
      <?php for($i=1;$i<=ceil($tot/$perPage);$i++): ?><a href="?status=<?=$fStatus?>&search=<?=urlencode($search)?>&page=<?=$i?>" class="page-btn <?=$i==$page?'active':''?>"><?=$i?></a><?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>