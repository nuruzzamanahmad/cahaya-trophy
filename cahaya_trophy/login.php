<?php
session_start();
require 'helper/koneksi.php';

// Jika sudah login, jangan biarkan masuk ke halaman login lagi, langsung tendang ke dashboard
if (isset($_SESSION['id_admin'])) {
    header("Location: dashboard/index.php");
    exit();
}

$error = "";

// Jika tombol Masuk ditekan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    // Cari username di database
    $query = mysqli_query($koneksi, "SELECT * FROM admin WHERE Username = '$username'");
    
    if (mysqli_num_rows($query) === 1) {
        $row = mysqli_fetch_assoc($query);
        
        // Verifikasi password (cocokkan ketikan dengan password di database)
        if ($password == $row['Password']) {
            // SET SESSION UNTUK AKSES HALAMAN LAIN
            $_SESSION['id_admin'] = $row['ID_ADMIN'];
            $_SESSION['nama_admin'] = $row['Nama_Lengkap'];
            $_SESSION['role'] = $row['Role'];
            
            // Berhasil login, masuk ke dashboard
            header("Location: dashboard/index.php");
            exit();
        } else {
            $error = "Password yang Anda masukkan salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cahaya Trophy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        .bg-login-image {
            /* Sesuaikan nama file gambar latar belakangmu di sini */
            background-image: linear-gradient(rgba(10, 37, 88, 0.1), rgba(10, 37, 88, 0.8)), url('assets/img/bg-trophy.png');
            background-color: #0a2558; /* Warna cadangan jika gambar tidak ada */
            background-size: cover;
            background-position: center;
        }
        .icon-box {
            width: 48px; height: 48px; background-color: #e6f0ff; color: #0d6efd;
            border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 24px;
        }
        
        /* --- PERBAIKAN CSS FOCUS RING --- */
        .form-control:focus { 
            box-shadow: none; /* Hilangkan bayangan bawaan input */
            border-color: #86b7fe; 
        }
        /* Saat input di dalam input-group di-klik, beri efek pada span ikonnya juga */
        .input-group:focus-within .input-group-text {
            border-color: #86b7fe;
        }
        /* Berikan bayangan biru (glow) yang menyelimuti seluruh grup */
        .input-group:focus-within {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-radius: 0.375rem; 
        }
    </style>
</head>
<body>

<div class="container-fluid min-vh-100 d-flex flex-column p-0">
    <div class="row flex-grow-1 g-0">
        
        <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-center px-5 bg-login-image text-white">
            <div class="px-xl-5">
                <span class="badge bg-primary bg-opacity-50 border border-light rounded-pill px-3 py-2 mb-4">PREMIUM QUALITY SINCE 2019</span>
                <h1 class="display-4 fw-bold mb-4">Cahaya Trophy Management System</h1>
                <p class="lead text-light mb-5" style="max-width: 500px;">Platform terpadu untuk mengelola pesanan, stok inventaris, dan pelanggan dengan efisiensi tinggi dan akurasi maksimal.</p>
            </div>
        </div>

        <div class="col-lg-6 col-12 d-flex flex-column justify-content-center align-items-center bg-white">
            <div class="w-100" style="max-width: 450px; padding: 20px;">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5">
                    <div class="text-center mb-4">
                        <div class="icon-box mb-3"><i class="bi bi-trophy-fill"></i></div>
                        <h2 class="fw-bold fs-3">Selamat Datang</h2>
                        <p class="text-muted">Silakan masuk ke akun Anda</p>
                    </div>

                    <?php if($error != ""): ?>
                        <div class="alert alert-danger py-2 small fw-medium text-center"><?= $error ?></div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-medium small">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-control border-start-0 py-2" placeholder="Masukkan username" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-medium small mb-0">Password</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control border-start-0 py-2" placeholder="••••••••" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-3">
                            Masuk <i class="bi bi-box-arrow-in-right ms-1"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>