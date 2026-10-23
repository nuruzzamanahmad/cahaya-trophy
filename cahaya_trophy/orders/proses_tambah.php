<?php
session_start();
require '../helper/koneksi.php';

if (isset($_POST['cart']) && !empty($_POST['cart'])) {
    
    // ==========================================
    // 1. PROSES DATA PELANGGAN
    // ==========================================
    $tipe_pelanggan = $_POST['tipe_pelanggan'];
    
    if ($tipe_pelanggan == 'baru') {
        $id_pelanggan = "PLG" . rand(10000, 99999); 
        $nama_pelanggan = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
        $no_wa = mysqli_real_escape_string($koneksi, $_POST['no_wa']);
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
        
        $query_pelanggan = "INSERT INTO pelanggan (ID_PELANGGAN, Nama_Pelanggan, No_Whatsapp, Alamat) 
                            VALUES ('$id_pelanggan', '$nama_pelanggan', '$no_wa', '$alamat')";
        mysqli_query($koneksi, $query_pelanggan);
    } else {
        $id_pelanggan = $_POST['id_pelanggan_lama'];
    }

    // ==========================================
    // 2. GENERATE ID PESANAN BERURUTAN (ORD00001)
    // ==========================================
    // Cari ID pesanan yang memiliki format persis "ORD" + 5 angka, ambil yang terbesar
    $query_id = mysqli_query($koneksi, "SELECT NO_PESANAN FROM pesanan WHERE NO_PESANAN LIKE 'ORD_____' ORDER BY NO_PESANAN DESC LIMIT 1");
    
    if (mysqli_num_rows($query_id) > 0) {
        $data_id = mysqli_fetch_assoc($query_id);
        $id_max = $data_id['NO_PESANAN'];
        // Ambil 5 angka terakhir dari ORD00001 dan tambahkan 1
        $urutan = (int) substr($id_max, 3, 5);
        $urutan++;
    } else {
        // Jika belum ada pesanan dengan format baru sama sekali, mulai dari 1
        $urutan = 1;
    }
    // Gabungkan "ORD" dengan angka yang dipadding nol di depannya
    $no_pesanan = "ORD" . sprintf("%05s", $urutan); 

    // ==========================================
    // 3. PROSES DATA PESANAN UTAMA
    // ==========================================
    $deadline = $_POST['deadline'];
    $total_harga = $_POST['total_harga'];
    $status_pembayaran = $_POST['status_pembayaran'];
    $nominal_dp = isset($_POST['nominal_dp']) ? (int)$_POST['nominal_dp'] : 0;
    $metode_pengambilan = $_POST['metode_pengambilan'];
    $id_admin = $_SESSION['id_admin'];

    // Penentuan Status Pesanan & Sisa Tagihan
    if ($status_pembayaran == 'Lunas') {
        $nominal_dp = $total_harga; 
        $sisa_tagihan = 0;
        $status_pesanan = 'Diproses'; 
    } elseif ($status_pembayaran == 'DP') {
        $sisa_tagihan = $total_harga - $nominal_dp; 
        if ($sisa_tagihan < 0) $sisa_tagihan = 0;
        $status_pesanan = 'Diproses';
    } else { 
        $nominal_dp = 0;
        $sisa_tagihan = $total_harga;
        $status_pesanan = 'Menunggu DP';
    }

    $query_pesanan = "INSERT INTO pesanan (NO_PESANAN, Deadline, Total_Harga, Nominal_DP, Sisa_Tagihan, Metode_Pengambilan, Status_Pesanan, ID_ADMIN, ID_PELANGGAN) 
                      VALUES ('$no_pesanan', '$deadline', '$total_harga', '$nominal_dp', '$sisa_tagihan', '$metode_pengambilan', '$status_pesanan', '$id_admin', '$id_pelanggan')";
    mysqli_query($koneksi, $query_pesanan);

    // ==========================================
    // 4. PROSES ISI KERANJANG & POTONG STOK
    // ==========================================
    $cart = $_POST['cart']; 

    foreach ($cart as $item) {
        $id_katalog = mysqli_real_escape_string($koneksi, $item['id_katalog']);
        $tipe = mysqli_real_escape_string($koneksi, $item['tipe']); 
        $qty = (int)$item['qty'];
        $harga = (int)$item['harga'];
        $catatan = mysqli_real_escape_string($koneksi, $item['catatan']);

        $q_kat = mysqli_query($koneksi, "SELECT Nama_Model, Kategori FROM katalog_produk WHERE ID_KATALOG = '$id_katalog'");
        $d_kat = mysqli_fetch_assoc($q_kat);
        $kategori_master = $d_kat['Kategori'];
        
        $nama_produk_final = $d_kat['Nama_Model'] . " (" . $tipe . ")"; 

        $id_detail = "DTL" . rand(10000, 99999);
        mysqli_query($koneksi, "INSERT INTO detail_pesanan (ID_DETAIL, Kategori_Produk, Qty, Teks_Kustom, NO_PESANAN) 
                                VALUES ('$id_detail', '$nama_produk_final', '$qty', '$catatan', '$no_pesanan')");

        if ($kategori_master == 'Piala') {
            $q_resep = mysqli_query($koneksi, "SELECT ID_BAHAN, Qty_Bahan_Per_Pcs FROM resep_piala WHERE ID_KATALOG = '$id_katalog'");
            
            while ($resep = mysqli_fetch_assoc($q_resep)) {
                $id_bahan = $resep['ID_BAHAN'];
                $qty_per_pcs = $resep['Qty_Bahan_Per_Pcs'];

                $multiplier = 1;
                if (strpos($tipe, 'Set') !== false) {
                    $multiplier = 3; 

                    $q_cek_bahan = mysqli_query($koneksi, "SELECT Kategori FROM bahanbaku WHERE ID_BAHAN = '$id_bahan'");
                    if ($d_cek_bahan = mysqli_fetch_assoc($q_cek_bahan)) {
                        if ($d_cek_bahan['Kategori'] == 'Tangga Juara') {
                            $multiplier = 1;
                        }
                    }
                }

                $total_potong = $qty * $multiplier * $qty_per_pcs;

                mysqli_query($koneksi, "INSERT INTO pesanan_komponen (NO_PESANAN, ID_KATALOG, ID_BAHAN, Qty_Digunakan) 
                                        VALUES ('$no_pesanan', '$id_katalog', '$id_bahan', '$total_potong')");

                mysqli_query($koneksi, "UPDATE bahanbaku SET Jumlah_Stok = Jumlah_Stok - $total_potong WHERE ID_BAHAN = '$id_bahan'");
            }
        }
    }

    header("Location: detail.php?id=$no_pesanan&pesan=berhasil");
    exit();

} else {
    header("Location: index.php");
    exit();
}
?>