<?php
// 1. Fungsi mengubah angka menjadi format Rupiah (Contoh: Rp 1.500.000)
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// 2. Fungsi mengubah angka menjadi ringkasan untuk Dashboard (Contoh: 45.200.000 menjadi Rp 45,2M / Jt)
function formatRupiahSingkat($angka) {
    if ($angka >= 1000000000) {
        return 'Rp ' . round($angka / 1000000000, 1) . ' Miliar';
    } elseif ($angka >= 1000000) {
        return 'Rp ' . round($angka / 1000000, 1) . ' Jt';
    } else {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}

// 3. Fungsi mengubah format tanggal YYYY-MM-DD menjadi format Indonesia (Contoh: 12 Oktober 2023)
function formatTanggal($tanggal) {
    if(empty($tanggal)) return "-";
    
    $bulan = array (
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $pecahkan = explode('-', $tanggal); // Memecah YYYY, MM, DD
    
    // index 0 = tahun, 1 = bulan, 2 = tanggal
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

// 4. Fungsi membuat badge warna status pesanan (Biar cantik seperti di desain Figma)
function badgeStatusPesanan($status) {
    if ($status == 'Menunggu DP') {
        return '<span class="badge bg-warning text-dark bg-opacity-25 px-2 py-1">Menunggu DP</span>';
    } elseif ($status == 'Diproses' || $status == 'Produksi') {
        return '<span class="badge bg-info text-dark bg-opacity-25 px-2 py-1">'.$status.'</span>';
    } elseif ($status == 'Siap Diambil') {
        return '<span class="badge bg-success text-dark bg-opacity-25 px-2 py-1">Siap Diambil</span>';
    } elseif ($status == 'Selesai') {
        return '<span class="badge bg-secondary bg-opacity-25 text-dark px-2 py-1">Selesai</span>';
    } else {
        return '<span class="badge bg-light text-dark px-2 py-1">'.$status.'</span>';
    }
}
?>