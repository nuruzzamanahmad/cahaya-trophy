<?php
session_start();
require '../helper/koneksi.php';
require '../helper/fungsi.php';

// Proteksi Keamanan: Wajib Login
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Profil Pengguna - Cahaya Trophy";
$id_admin = $_SESSION['id_admin'];

// Ambil data admin dari database
$query = mysqli_query($koneksi, "SELECT * FROM admin WHERE ID_ADMIN = '$id_admin'");
$data_admin = mysqli_fetch_assoc($query);

// Proses jika tombol Simpan Perubahan ditekan
if (isset($_POST['simpan_profil'])) {
    $nama_baru = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    // Update Nama
    mysqli_query($koneksi, "UPDATE admin SET Nama_Lengkap = '$nama_baru' WHERE ID_ADMIN = '$id_admin'");
    $_SESSION['nama'] = $nama_baru; // Update session nama

    // Jika user mengisi form ganti password
    if (!empty($password_baru)) {
        if ($password_baru == $konfirmasi_password) {
            mysqli_query($koneksi, "UPDATE admin SET Password = '$password_baru' WHERE ID_ADMIN = '$id_admin'");
            $pesan_sukses = "Profil dan Password berhasil diperbarui!";
        } else {
            $pesan_error = "Konfirmasi password baru tidak cocok!";
        }
    } else {
        $pesan_sukses = "Profil berhasil diperbarui!";
    }
    
    // Refresh data setelah update
    $query = mysqli_query($koneksi, "SELECT * FROM admin WHERE ID_ADMIN = '$id_admin'");
    $data_admin = mysqli_fetch_assoc($query);
}

include '../layouts/header.php';
?>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <h4 class="fw-bold mb-1 text-primary">Profil Pengguna</h4>
    <a href="../dashboard/index.php" class="btn btn-sm btn-light border text-muted">
        <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
    </a>
</div>

<?php if(isset($pesan_sukses)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= $pesan_sukses ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if(isset($pesan_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $pesan_error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form action="" method="POST">
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4 d-flex align-items-center gap-4">
            <div class="position-relative">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($data_admin['Nama_Lengkap']) ?>&background=0D8ABC&color=fff&size=128" class="rounded-4 shadow-sm" alt="Profile" style="width: 100px; height: 100px; object-fit: cover;">
                <button type="button" class="btn btn-primary btn-sm rounded-circle position-absolute" style="bottom: -10px; right: -10px; width: 32px; height: 32px; padding: 0;">
                    <i class="bi bi-camera-fill"></i>
                </button>
            </div>
            <div class="flex-grow-1">
                <h4 class="fw-bold text-dark mb-1"><?= $data_admin['Nama_Lengkap'] ?></h4>
                <p class="text-muted small mb-2"><i class="bi bi-patch-check-fill text-primary me-1"></i> Cahaya Trophy Management</p>
                <div class="d-flex gap-2 mt-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">Aktif</span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">ID: <?= $data_admin['ID_ADMIN'] ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-person text-primary me-2"></i>Informasi Pribadi</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" value="<?= $data_admin['Nama_Lengkap'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Email <span class="text-muted fw-normal">(Opsional)</span></label>
                            <input type="email" class="form-control" value="admin@cahayatrophy.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Nomor Telepon <span class="text-muted fw-normal">(Opsional)</span></label>
                            <input type="text" class="form-control" value="+62 812 3456 7890">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Role</label>
                            <input type="text" class="form-control bg-light" value="<?= $data_admin['Role'] ?>" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">Bio</label>
                            <textarea class="form-control" rows="3">Mengelola operasional harian dan koordinasi pesanan piala kustom untuk Cahaya Trophy.</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bell text-primary me-2"></i>Preferensi Notifikasi</h6>
                </div>
                <div class="card-body p-4 pt-2">
                    <div class="d-flex justify-content-between align-items-center mb-4 p-3 border rounded-3 bg-light bg-opacity-50">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark small">Notifikasi Email</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Terima laporan harian dan konfirmasi pesanan via email.</div>
                            </div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" checked style="transform: scale(1.3);">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-3 border rounded-3 bg-light bg-opacity-50">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-whatsapp"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark small">Notifikasi WhatsApp</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Terima peringatan stok rendah dan chat pelanggan via WhatsApp.</div>
                            </div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" style="transform: scale(1.3);">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-5">
            
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-shield-check text-primary me-2"></i>Keamanan</h6>
                </div>
                <div class="card-body p-4 pt-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Password Saat Ini</label>
                        <div class="input-group">
                            <input type="password" class="form-control" placeholder="••••••••" disabled>
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-eye"></i></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Password Baru</label>
                        <input type="password" name="password_baru" class="form-control" placeholder="Min. 8 karakter">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">Konfirmasi Password Baru</label>
                        <input type="password" name="konfirmasi_password" class="form-control" placeholder="Ulangi password baru">
                    </div>

                    <div class="d-flex gap-2 p-3 bg-light border rounded-3 text-muted small">
                        <i class="bi bi-info-circle text-primary mt-1"></i>
                        <span>Kosongkan kolom password baru jika Anda tidak ingin mengganti password saat ini.</span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 text-uppercase" style="font-size: 0.85rem; letter-spacing: 1px;">Aktivitas Terakhir</h6>
                    <ul class="list-unstyled mb-0 small opacity-75">
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-circle-fill mt-1" style="font-size: 6px;"></i> Login dari Chrome di Windows</li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-circle-fill mt-1" style="font-size: 6px;"></i> Update status pesanan #ORD-982</li>
                        <li class="d-flex gap-2"><i class="bi bi-circle-fill mt-1" style="font-size: 6px;"></i> Tambah inventaris produk baru</li>
                    </ul>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" name="simpan_profil" class="btn btn-primary fw-bold px-4 py-2 w-100 shadow-sm">
                    <i class="bi bi-save me-2"></i> Simpan Perubahan
                </button>
            </div>

        </div>
    </div>
</form>

<?php include '../layouts/footer.php'; ?>