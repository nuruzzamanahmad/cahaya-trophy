<?php
session_start();
require '../helper/koneksi.php';

// Proteksi: Wajib login
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $no_pesanan = mysqli_real_escape_string($koneksi, $_GET['id']);

    // 1. KEMBALIKAN STOK BAHAN BAKU (BOM) KE GUDANG
    // Cari komponen apa saja yang dipakai di pesanan ini
    $q_komponen = mysqli_query($koneksi, "SELECT ID_BAHAN, Qty_Digunakan FROM pesanan_komponen WHERE NO_PESANAN = '$no_pesanan'");
    
    if ($q_komponen && mysqli_num_rows($q_komponen) > 0) {
        while ($komp = mysqli_fetch_assoc($q_komponen)) {
            $id_bahan = $komp['ID_BAHAN'];
            $qty_balik = (int)$komp['Qty_Digunakan'];
            
            // Tambahkan kembali stok ke tabel bahanbaku
            mysqli_query($koneksi, "UPDATE bahanbaku SET Jumlah_Stok = Jumlah_Stok + $qty_balik WHERE ID_BAHAN = '$id_bahan'");
        }
    }

    // 2. HAPUS CHILD DATA AGAR TIDAK ERROR FOREIGN KEY
    // Hapus data yang nyangkut di keranjang dan riwayat komponen terlebih dahulu
    mysqli_query($koneksi, "DELETE FROM pesanan_komponen WHERE NO_PESANAN = '$no_pesanan'");
    mysqli_query($koneksi, "DELETE FROM detail_pesanan WHERE NO_PESANAN = '$no_pesanan'");

    // 3. BARU HAPUS DATA PESANAN UTAMA
    mysqli_query($koneksi, "DELETE FROM pesanan WHERE NO_PESANAN = '$no_pesanan'");

    // Kembali ke halaman order dengan notifikasi sukses
    header("Location: index.php?pesan=dihapus");
    exit();

} else {
    // Jika tidak ada ID di URL, kembalikan ke halaman index
    header("Location: index.php");
    exit();
}
?>