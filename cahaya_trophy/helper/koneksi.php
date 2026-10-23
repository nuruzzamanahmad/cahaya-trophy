<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db-cahaya-trophy"; // Pastikan ini sesuai dengan nama database Kakak di phpMyAdmin

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>