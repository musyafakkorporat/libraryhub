<?php
// includes/config.php
define('DB_HOST',    'localhost');
define('DB_NAME',    'db_library');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');
define('SITE_NAME',  'LibraryHub');
define('DENDA_PER_HARI', 1000); // Rp1.000/hari
define('MAKS_PINJAM', 3);       // maks buku per sekali pinjam
define('LAMA_PINJAM', 7);       // hari pinjam default

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES=>false]
            );
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:30px;color:#dc2626">
                <h3>❌ Koneksi Database Gagal</h3>
                <p>'.$e->getMessage().'</p>
                <p>Pastikan XAMPP aktif dan database <strong>'.DB_NAME.'</strong> sudah dibuat dari file <code>database.sql</code>.</p>
            </div>');
        }
    }
    return $pdo;
}

function rupiah(int $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function hitungDenda(string $tglRencana): array {
    $today  = new DateTime();
    $rencana= new DateTime($tglRencana);
    $selisih= $today > $rencana ? (int)$today->diff($rencana)->days : 0;
    return ['hari'=>$selisih, 'total'=>$selisih * DENDA_PER_HARI];
}
