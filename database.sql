-- ============================================================
-- LibraryHub — Sistem Manajemen Perpustakaan Digital
-- Database: MySQL (XAMPP)
-- ============================================================

CREATE DATABASE IF NOT EXISTS db_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_library;

-- =====================
-- TABEL USERS (Admin & Petugas & Anggota)
-- =====================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    no_hp VARCHAR(20),
    role ENUM('admin','petugas','anggota') DEFAULT 'anggota',
    foto VARCHAR(255),
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================
-- TABEL KATEGORI BUKU
-- =====================
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(80) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================
-- TABEL RAK
-- =====================
CREATE TABLE rak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(10) NOT NULL UNIQUE,
    nama VARCHAR(80) NOT NULL,
    lokasi VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================
-- TABEL BUKU
-- =====================
CREATE TABLE buku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_buku VARCHAR(20) NOT NULL UNIQUE,
    judul VARCHAR(200) NOT NULL,
    pengarang VARCHAR(150) NOT NULL,
    penerbit VARCHAR(100),
    tahun_terbit YEAR,
    isbn VARCHAR(20),
    kategori_id INT,
    rak_id INT,
    jumlah_total INT DEFAULT 1,
    jumlah_tersedia INT DEFAULT 1,
    sinopsis TEXT,
    cover VARCHAR(255),
    bahasa VARCHAR(30) DEFAULT 'Indonesia',
    halaman INT,
    status ENUM('tersedia','habis','tidak_aktif') DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL,
    FOREIGN KEY (rak_id) REFERENCES rak(id) ON DELETE SET NULL
);

-- =====================
-- TABEL ANGGOTA
-- =====================
CREATE TABLE anggota (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_anggota VARCHAR(20) NOT NULL UNIQUE,
    user_id INT,
    nama VARCHAR(100) NOT NULL,
    nik VARCHAR(20),
    tempat_lahir VARCHAR(50),
    tgl_lahir DATE,
    jenis_kelamin ENUM('L','P'),
    alamat TEXT,
    no_hp VARCHAR(20),
    email VARCHAR(100),
    jenis_anggota ENUM('siswa','mahasiswa','umum','guru','dosen') DEFAULT 'umum',
    instansi VARCHAR(100),
    tgl_daftar DATE DEFAULT (CURDATE()),
    tgl_aktif_sampai DATE,
    foto VARCHAR(255),
    status ENUM('aktif','nonaktif','ditangguhkan') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================
-- TABEL PEMINJAMAN
-- =====================
CREATE TABLE peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_pinjam VARCHAR(20) NOT NULL UNIQUE,
    anggota_id INT NOT NULL,
    petugas_id INT,
    tgl_pinjam DATE NOT NULL,
    tgl_kembali_rencana DATE NOT NULL,
    tgl_kembali_aktual DATE,
    status ENUM('dipinjam','dikembalikan','terlambat','hilang') DEFAULT 'dipinjam',
    denda INT DEFAULT 0,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (anggota_id) REFERENCES anggota(id),
    FOREIGN KEY (petugas_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================
-- TABEL DETAIL PEMINJAMAN (buku yang dipinjam)
-- =====================
CREATE TABLE peminjaman_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peminjaman_id INT NOT NULL,
    buku_id INT NOT NULL,
    kondisi_pinjam ENUM('baik','rusak_ringan','rusak_berat') DEFAULT 'baik',
    kondisi_kembali ENUM('baik','rusak_ringan','rusak_berat','hilang'),
    FOREIGN KEY (peminjaman_id) REFERENCES peminjaman(id) ON DELETE CASCADE,
    FOREIGN KEY (buku_id) REFERENCES buku(id)
);

-- =====================
-- TABEL DENDA
-- =====================
CREATE TABLE denda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peminjaman_id INT NOT NULL UNIQUE,
    jumlah INT NOT NULL DEFAULT 0,
    denda_per_hari INT DEFAULT 1000,
    hari_terlambat INT DEFAULT 0,
    status ENUM('belum_bayar','sudah_bayar') DEFAULT 'belum_bayar',
    tgl_bayar DATE,
    dibayar_ke INT,
    catatan VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (peminjaman_id) REFERENCES peminjaman(id),
    FOREIGN KEY (dibayar_ke) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================
-- TABEL RESERVASI BUKU
-- =====================
CREATE TABLE reservasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anggota_id INT NOT NULL,
    buku_id INT NOT NULL,
    tgl_reservasi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tgl_expired DATE,
    status ENUM('menunggu','tersedia','diambil','batal','expired') DEFAULT 'menunggu',
    FOREIGN KEY (anggota_id) REFERENCES anggota(id),
    FOREIGN KEY (buku_id) REFERENCES buku(id)
);

-- =====================
-- TABEL ULASAN BUKU
-- =====================
CREATE TABLE ulasan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buku_id INT NOT NULL,
    anggota_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    komentar TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE CASCADE,
    FOREIGN KEY (anggota_id) REFERENCES anggota(id) ON DELETE CASCADE
);

-- =====================
-- SEED DATA
-- =====================

-- Users (password = "admin123")
INSERT INTO users (nama, username, password, email, role) VALUES
('Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@library.com', 'admin'),
('Budi Santoso', 'petugas1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'budi@library.com', 'petugas'),
('Siti Rahayu', 'anggota1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siti@email.com', 'anggota');

-- Kategori
INSERT INTO kategori (nama, deskripsi) VALUES
('Fiksi', 'Novel, cerpen, dan karya sastra fiksi'),
('Non-Fiksi', 'Biografi, sejarah, dan fakta nyata'),
('Teknologi', 'Buku seputar IT, komputer, dan sains terapan'),
('Sains', 'Fisika, kimia, biologi, dan ilmu pengetahuan alam'),
('Sejarah', 'Buku sejarah nasional dan dunia'),
('Ekonomi', 'Bisnis, keuangan, manajemen, dan akuntansi'),
('Hukum', 'Peraturan, undang-undang, dan ilmu hukum'),
('Psikologi', 'Psikologi umum, klinis, dan pengembangan diri'),
('Pendidikan', 'Metodologi, kurikulum, dan ilmu pendidikan'),
('Agama', 'Al-Quran, hadis, dan buku keagamaan');

-- Rak
INSERT INTO rak (kode, nama, lokasi) VALUES
('R-A', 'Rak A', 'Lantai 1 - Kiri'),
('R-B', 'Rak B', 'Lantai 1 - Kanan'),
('R-C', 'Rak C', 'Lantai 2 - Kiri'),
('R-D', 'Rak D', 'Lantai 2 - Kanan'),
('R-E', 'Rak E', 'Lantai 1 - Tengah');

-- Buku
INSERT INTO buku (kode_buku, judul, pengarang, penerbit, tahun_terbit, isbn, kategori_id, rak_id, jumlah_total, jumlah_tersedia, sinopsis, bahasa, halaman) VALUES
('BK-0001', 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, '978-979-1226-00-0', 1, 1, 5, 3, 'Kisah inspiratif sepuluh anak Belitung yang berjuang mendapatkan pendidikan layak.', 'Indonesia', 529),
('BK-0002', 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Lentera Dipantara', 1980, '978-979-97312-3-2', 1, 1, 3, 2, 'Novel pertama dari tetralogi Buru yang menceritakan perjuangan Minke di era kolonial.', 'Indonesia', 535),
('BK-0003', 'Clean Code', 'Robert C. Martin', 'Prentice Hall', 2008, '978-0-13-235088-4', 3, 3, 4, 4, 'Panduan praktis menulis kode bersih dan mudah dipelihara.', 'Inggris', 431),
('BK-0004', 'Atomic Habits', 'James Clear', 'Penguin Books', 2018, '978-0-7352-1129-2', 8, 2, 6, 5, 'Panduan membangun kebiasaan baik dan menghapus kebiasaan buruk.', 'Indonesia', 319),
('BK-0005', 'Sapiens: Sejarah Singkat Umat Manusia', 'Yuval Noah Harari', 'KPG', 2011, '978-602-424-712-8', 5, 4, 3, 1, 'Sejarah komprehensif tentang perjalanan manusia dari zaman purba hingga modern.', 'Indonesia', 512),
('BK-0006', 'Python Crash Course', 'Eric Matthes', 'No Starch Press', 2019, '978-1-59327-928-8', 3, 3, 5, 3, 'Pengenalan pemrograman Python yang komprehensif untuk pemula.', 'Inggris', 544),
('BK-0007', 'Thinking, Fast and Slow', 'Daniel Kahneman', 'Farrar, Straus and Giroux', 2011, '978-0-374-27563-1', 8, 2, 2, 2, 'Eksplorasi mendalam tentang dua sistem berpikir manusia.', 'Indonesia', 499),
('BK-0008', 'Rich Dad Poor Dad', 'Robert T. Kiyosaki', 'Plata Publishing', 1997, '978-1-61268-116-2', 6, 5, 8, 6, 'Pelajaran tentang keuangan dan investasi dari perspektif dua ayah berbeda.', 'Indonesia', 336),
('BK-0009', 'Filosofi Teras', 'Henry Manampiring', 'Kompas', 2018, '978-979-709-980-1', 8, 2, 4, 3, 'Penerapan filsafat Stoa dalam kehidupan sehari-hari orang Indonesia.', 'Indonesia', 304),
('BK-0010', 'Hukum Perdata Indonesia', 'R. Subekti', 'PT Intermasa', 2003, '978-979-8120-78-2', 7, 4, 2, 2, 'Kompendium hukum perdata yang berlaku di Indonesia.', 'Indonesia', 248),
('BK-0011', 'Biologi Molekuler', 'Bruce Alberts', 'Garland Science', 2014, '978-0-8153-4432-2', 4, 3, 3, 3, 'Dasar-dasar biologi molekuler sel untuk mahasiswa sains.', 'Inggris', 1342),
('BK-0012', 'Manajemen Strategis', 'Fred R. David', 'Salemba Empat', 2011, '978-979-691-408-7', 6, 5, 4, 2, 'Konsep dan teknik manajemen strategis dalam persaingan bisnis modern.', 'Indonesia', 628);

-- Anggota
INSERT INTO anggota (no_anggota, nama, nik, tempat_lahir, tgl_lahir, jenis_kelamin, alamat, no_hp, email, jenis_anggota, instansi, tgl_daftar, tgl_aktif_sampai, status) VALUES
('AGT-0001', 'Siti Rahayu', '3578010101020001', 'Surabaya', '2000-01-01', 'P', 'Jl. Mawar No.5, Surabaya', '08111111001', 'siti@email.com', 'mahasiswa', 'Universitas Airlangga', '2024-01-10', '2025-01-10', 'aktif'),
('AGT-0002', 'Ahmad Fauzi', '3578020202030002', 'Malang', '1999-02-02', 'L', 'Jl. Melati No.8, Malang', '08111111002', 'ahmad@email.com', 'mahasiswa', 'Universitas Brawijaya', '2024-01-15', '2025-01-15', 'aktif'),
('AGT-0003', 'Dewi Pertiwi', '3578030303040003', 'Kediri', '2001-03-03', 'P', 'Jl. Kenanga No.12, Kediri', '08111111003', 'dewi@email.com', 'siswa', 'SMAN 1 Kediri', '2024-02-01', '2025-02-01', 'aktif'),
('AGT-0004', 'Rizal Hidayat', '3578040404050004', 'Sidoarjo', '1998-04-04', 'L', 'Jl. Nusa Indah No.3, Sidoarjo', '08111111004', 'rizal@email.com', 'umum', NULL, '2024-02-10', '2025-02-10', 'aktif'),
('AGT-0005', 'Maya Sari', '3578050505060005', 'Gresik', '2002-05-05', 'P', 'Jl. Flamboyan No.17, Gresik', '08111111005', 'maya@email.com', 'mahasiswa', 'ITS Surabaya', '2024-03-01', '2025-03-01', 'aktif'),
('AGT-0006', 'Eko Prasetyo', '3578060606070006', 'Pasuruan', '1995-06-06', 'L', 'Jl. Cempaka No.9, Pasuruan', '08111111006', 'eko@email.com', 'guru', 'SDN Pasuruan', '2024-03-15', '2025-03-15', 'aktif');

-- Peminjaman
INSERT INTO peminjaman (kode_pinjam, anggota_id, petugas_id, tgl_pinjam, tgl_kembali_rencana, tgl_kembali_aktual, status, denda) VALUES
('PNJ-20240001', 1, 2, '2024-06-01', '2024-06-08', NULL, 'dipinjam', 0),
('PNJ-20240002', 2, 2, '2024-06-02', '2024-06-09', '2024-06-09', 'dikembalikan', 0),
('PNJ-20240003', 3, 2, '2024-05-20', '2024-05-27', NULL, 'terlambat', 14000),
('PNJ-20240004', 4, 2, '2024-06-05', '2024-06-12', NULL, 'dipinjam', 0),
('PNJ-20240005', 5, 2, '2024-06-01', '2024-06-08', '2024-06-07', 'dikembalikan', 0);

-- Detail Peminjaman
INSERT INTO peminjaman_detail (peminjaman_id, buku_id, kondisi_pinjam) VALUES
(1, 1, 'baik'), (1, 4, 'baik'),
(2, 3, 'baik'),
(3, 5, 'baik'), (3, 8, 'baik'),
(4, 2, 'baik'),
(5, 6, 'baik');

-- Update stok buku
UPDATE buku SET jumlah_tersedia = jumlah_tersedia - 1 WHERE id IN (1,4,5,8,2);

-- Denda
INSERT INTO denda (peminjaman_id, jumlah, denda_per_hari, hari_terlambat, status) VALUES
(3, 14000, 1000, 14, 'belum_bayar');

-- Pengumuman/reservasi
INSERT INTO reservasi (anggota_id, buku_id, tgl_expired, status) VALUES
(6, 3, '2024-07-01', 'menunggu'),
(1, 5, '2024-07-05', 'menunggu');
