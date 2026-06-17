<?php
// includes/config.php
define('DB_HOST',    'thomas.proxy.rlwy.net'); 
define('DB_NAME',    'railway');                
define('DB_USER',    'root');                   
define('DB_PASS',    'XYyisJYQUQErHNQciQgsSweXFobVwGIx'); 
define('DB_PORT',    56132);                    
define('DB_CHARSET', 'utf8mb4');
define('SITE_NAME',  'LibraryHub');
define('DENDA_PER_HARI', 1000);
define('MAKS_PINJAM', 3);       
define('LAMA_PINJAM', 7);       

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // Sudah ditambahkan ;port= di bawah ini agar tersambung ke port proxy Railway
            $pdo = new PDO(
                "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET,
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES=>false]
            );

            // TAMBAHKAN BARIS INI UNTUK MEMATIKAN MODE KETAT MYSQL RAILWAY
            $pdo->exec("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");

        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:30px;color:#dc2626">
                <h3>Koneksi Database Gagal</h3>
                <p>'.$e->getMessage().'</p>
                <p>Pastikan koneksi internet aktif dan variabel proxy <strong>'.DB_HOST.'</strong> sudah sesuai.</p>
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