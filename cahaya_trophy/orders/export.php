<?php
session_start();
require '../helper/koneksi.php';

// Hanya yang sudah login yang bisa download
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

$filter_status = isset($_GET['status']) ? $_GET['status'] : 'Semua';
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

$where_conditions = [];
if ($filter_status != 'Semua') {
    $where_conditions[] = "p.Status_Pesanan = '$filter_status'";
}
if (!empty($search)) {
    $where_conditions[] = "(p.NO_PESANAN LIKE '%$search%' OR pel.Nama_Pelanggan LIKE '%$search%')";
}
$where_clause = "";
if (count($where_conditions) > 0) {
    $where_clause = " WHERE " . implode(" AND ", $where_conditions);
}

// Ambil semua data (tanpa limit halaman)
$query = mysqli_query($koneksi, "
    SELECT p.NO_PESANAN, pel.Nama_Pelanggan, pel.No_Whatsapp, p.Deadline, 
           p.Total_Harga, p.Nominal_DP, p.Sisa_Tagihan, p.Status_Pesanan
    FROM pesanan p
    JOIN pelanggan pel ON p.ID_PELANGGAN = pel.ID_PELANGGAN
    $where_clause
    ORDER BY p.Deadline ASC
");

// Setting header agar terunduh sebagai file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Laporan_Pesanan_Cahaya_Trophy_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Header kolom CSV
fputcsv($output, array('No Pesanan', 'Nama Pelanggan', 'No WA', 'Deadline', 'Total Harga', 'DP Masuk', 'Sisa Tagihan', 'Status'));

// Masukkan data ke CSV
while ($row = mysqli_fetch_assoc($query)) {
    fputcsv($output, array(
        $row['NO_PESANAN'],
        $row['Nama_Pelanggan'],
        $row['No_Whatsapp'],
        $row['Deadline'],
        $row['Total_Harga'],
        $row['Nominal_DP'],
        $row['Sisa_Tagihan'],
        $row['Status_Pesanan']
    ));
}

fclose($output);
exit();
?>