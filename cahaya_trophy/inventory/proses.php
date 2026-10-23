<?php
session_start();
require '../helper/koneksi.php';

// Pastikan yang akses adalah admin
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

$id_admin = $_SESSION['id_admin'];

// ==============================================
// 1. PROSES TAMBAH ITEM BARU
// ==============================================
if (isset($_POST['tambah_item'])) {
    $id_bahan = mysqli_real_escape_string($koneksi, $_POST['id_bahan']);
    $nama_bahan = mysqli_real_escape_string($koneksi, $_POST['nama_bahan']);
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan']);
    $stok_awal = (int)$_POST['stok_awal'];
    $batas_kritis = (int)$_POST['batas_kritis'];

    // Insert ke tabel bahanbaku
    mysqli_query($koneksi, "INSERT INTO bahanbaku (ID_BAHAN, Nama_Bahan, Kategori, Jumlah_Stok, Batas_Kritis, Satuan) 
                            VALUES ('$id_bahan', '$nama_bahan', '$kategori', '$stok_awal', '$batas_kritis', '$satuan')");
    
    // Jika stok awal lebih dari 0, catat sebagai mutasi masuk awal
    if ($stok_awal > 0) {
        mysqli_query($koneksi, "INSERT INTO riwayat_mutasi (ID_BAHAN, Jenis, Jumlah, Keterangan, ID_ADMIN) 
                                VALUES ('$id_bahan', 'Masuk', '$stok_awal', 'Stok Awal Sistem', '$id_admin')");
    }

    header("Location: index.php?pesan=sukses");
    exit();
}

// ==============================================
// 2. PROSES MUTASI (BARANG MASUK ATAU KELUAR)
// ==============================================
if (isset($_POST['proses_mutasi'])) {
    $id_bahan = mysqli_real_escape_string($koneksi, $_POST['id_bahan']);
    $jenis_mutasi = $_POST['jenis_mutasi']; // 'Masuk' atau 'Keluar'
    $jumlah = (int)$_POST['jumlah'];
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    // Catat ke buku riwayat mutasi
    mysqli_query($koneksi, "INSERT INTO riwayat_mutasi (ID_BAHAN, Jenis, Jumlah, Keterangan, ID_ADMIN) 
                            VALUES ('$id_bahan', '$jenis_mutasi', '$jumlah', '$keterangan', '$id_admin')");

    // Update stok riil di gudang (tabel bahanbaku)
    if ($jenis_mutasi == 'Masuk') {
        mysqli_query($koneksi, "UPDATE bahanbaku SET Jumlah_Stok = Jumlah_Stok + $jumlah WHERE ID_BAHAN = '$id_bahan'");
    } else {
        mysqli_query($koneksi, "UPDATE bahanbaku SET Jumlah_Stok = Jumlah_Stok - $jumlah WHERE ID_BAHAN = '$id_bahan'");
    }

    header("Location: index.php?pesan=sukses");
    exit();
}

// ==============================================
// 3. PROSES EDIT INFO ITEM (BATAS KRITIS DLL)
// ==============================================
if (isset($_POST['edit_bahan'])) {
    $id_bahan = mysqli_real_escape_string($koneksi, $_POST['id_bahan']);
    $nama_bahan = mysqli_real_escape_string($koneksi, $_POST['nama_bahan']);
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $batas_kritis = (int)$_POST['batas_kritis'];

    mysqli_query($koneksi, "UPDATE bahanbaku SET Nama_Bahan = '$nama_bahan', Kategori = '$kategori', Batas_Kritis = '$batas_kritis' WHERE ID_BAHAN = '$id_bahan'");
    
    header("Location: index.php?pesan=sukses");
    exit();
}

// ==============================================
// 4. PROSES HAPUS ITEM (DELETE)
// ==============================================
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    
    // Hapus data (Otomatis ditolak MySQL jika bahan ini sedang dipakai di tabel pesanan_komponen)
    mysqli_query($koneksi, "DELETE FROM bahanbaku WHERE ID_BAHAN = '$id_hapus'");
    
    header("Location: index.php?pesan=sukses");
    exit();
}

// Jika diakses tanpa parameter yang jelas, tendang balik
header("Location: index.php");
exit();
?>