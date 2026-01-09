<?php
// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_museum');

// Membuat koneksi ke database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek apakah koneksi berhasil
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set charset ke utf8 untuk mendukung karakter Indonesia
$conn->set_charset("utf8");
?>
