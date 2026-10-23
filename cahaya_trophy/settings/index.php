<?php
session_start();
require '../helper/koneksi.php';
require '../helper/fungsi.php';

// Proteksi Keamanan
if (!isset($_SESSION['id_admin']) || $_SESSION['role'] != 'Super Administrator') {
    header("Location: ../dashboard/index.php");
    exit();
}

$page_title = "Pengaturan Sistem - Cahaya Trophy";
$id_admin = $_SESSION['id_admin'];
$pesan_sukses = "";
$pesan_error = "";

// =========================================================================
// 1. AUTO-SETUP TABEL PENGATURAN
// =========================================================================
$cek_tabel = mysqli_query($koneksi, "SHOW TABLES LIKE 'pengaturan'");
if(mysqli_num_rows($cek_tabel) == 0) {
    mysqli_query($koneksi, "CREATE TABLE pengaturan (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        nama_toko VARCHAR(100), wa_toko VARCHAR(20), email_toko VARCHAR(100),
        alamat_toko TEXT, prefix_nota VARCHAR(20), catatan_struk TEXT
    )");
    mysqli_query($koneksi, "INSERT INTO pengaturan (nama_toko, wa_toko, email_toko, alamat_toko, prefix_nota, catatan_struk) 
                            VALUES ('Cahaya Trophy & Souvenir', '081234567890', 'info@cahayatrophy.com', 'Jl. Raya Sukses No. 99', 'ORD-', 'Terima kasih telah berbelanja.')");
}

// =========================================================================
// 2. PROSES FORM: Menyimpan Data ke Database
// =========================================================================

// A. SIMPAN PROFIL PRIBADI
if (isset($_POST['simpan_profil_pribadi'])) {
    $nama_baru = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username_baru = mysqli_real_escape_string($koneksi, $_POST['username']);
    $pass_lama = mysqli_real_escape_string($koneksi, $_POST['password_saat_ini']);
    $pass_baru = mysqli_real_escape_string($koneksi, $_POST['password_baru']);
    $konfirmasi = mysqli_real_escape_string($koneksi, $_POST['konfirmasi_password']);

    $q_cek = mysqli_query($koneksi, "SELECT Password FROM admin WHERE ID_ADMIN = '$id_admin'");
    $data_cek = mysqli_fetch_assoc($q_cek);
    $pass_db = $data_cek['Password'] ?? '';

    // Update Nama dan Username
    mysqli_query($koneksi, "UPDATE admin SET Nama_Lengkap = '$nama_baru', Username = '$username_baru' WHERE ID_ADMIN = '$id_admin'");
    $_SESSION['nama_admin'] = $nama_baru; 

    // Jika ingin ganti password
    if (!empty($pass_baru)) {
        if ($pass_lama === $pass_db) {
            if ($pass_baru === $konfirmasi) {
                mysqli_query($koneksi, "UPDATE admin SET Password = '$pass_baru' WHERE ID_ADMIN = '$id_admin'");
                $pesan_sukses = "Profil dan Password Anda berhasil diperbarui!";
            } else {
                $pesan_error = "Konfirmasi password baru tidak cocok!";
            }
        } else {
            $pesan_error = "Password saat ini salah! Perubahan sandi dibatalkan.";
        }
    } else {
        $pesan_sukses = "Profil Anda berhasil diperbarui!";
    }
}

// B. SIMPAN PROFIL TOKO
if (isset($_POST['simpan_toko'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_toko']);
    $wa = mysqli_real_escape_string($koneksi, $_POST['wa_toko']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email_toko']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat_toko']);
    if(mysqli_query($koneksi, "UPDATE pengaturan SET nama_toko='$nama', wa_toko='$wa', email_toko='$email', alamat_toko='$alamat' WHERE id=1")) {
        $pesan_sukses = "Profil identitas toko berhasil diperbarui!";
    }
}

// C. SIMPAN KONFIGURASI STRUK
if (isset($_POST['simpan_struk'])) {
    $prefix = mysqli_real_escape_string($koneksi, $_POST['prefix_nota']);
    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan_struk']);
    if(mysqli_query($koneksi, "UPDATE pengaturan SET prefix_nota='$prefix', catatan_struk='$catatan' WHERE id=1")) {
        $pesan_sukses = "Format struk & invoice berhasil diperbarui!";
    }
}

// D. TAMBAH KARYAWAN BARU
if (isset($_POST['tambah_user'])) {
    $id_baru = "ADM" . rand(100, 999);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_admin']);
    $user = mysqli_real_escape_string($koneksi, $_POST['username']);
    $pass = mysqli_real_escape_string($koneksi, $_POST['password']);
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);
    
    $q_tambah = "INSERT INTO admin (ID_ADMIN, Nama_Lengkap, Username, Password, Role) VALUES ('$id_baru', '$nama', '$user', '$pass', '$role')";
    if(mysqli_query($koneksi, $q_tambah)) {
        $pesan_sukses = "Akun karyawan baru berhasil ditambahkan!";
    } else {
        $pesan_error = "Gagal menambah data karyawan. " . mysqli_error($koneksi);
    }
}

// E. EDIT KARYAWAN
if (isset($_POST['edit_user'])) {
    $id_edit = mysqli_real_escape_string($koneksi, $_POST['id_admin_edit']);
    $nama_edit = mysqli_real_escape_string($koneksi, $_POST['nama_admin']);
    $user_edit = mysqli_real_escape_string($koneksi, $_POST['username']);
    $pass_edit = mysqli_real_escape_string($koneksi, $_POST['password']);
    $role_edit = mysqli_real_escape_string($koneksi, $_POST['role']);
    
    $q_edit = "UPDATE admin SET Nama_Lengkap = '$nama_edit', Username = '$user_edit', Password = '$pass_edit', Role = '$role_edit' WHERE ID_ADMIN = '$id_edit'";
    
    if(mysqli_query($koneksi, $q_edit)) {
        $pesan_sukses = "Data akun karyawan berhasil diperbarui!";
        // Jika mengedit akunnya sendiri
        if($id_edit == $id_admin) {
            $_SESSION['nama_admin'] = $nama_edit;
            $_SESSION['role'] = $role_edit;
        }
    } else {
        $pesan_error = "Gagal memperbarui data karyawan.";
    }
}

// F. HAPUS KARYAWAN
if (isset($_GET['hapus_user'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus_user']);
    if($id_hapus != $id_admin) { 
        if(mysqli_query($koneksi, "DELETE FROM admin WHERE ID_ADMIN = '$id_hapus'")) {
            $pesan_sukses = "Akun karyawan berhasil dihapus!";
        }
    } else {
        $pesan_error = "Anda tidak bisa menghapus akun Anda sendiri!";
    }
}

// =========================================================================
// 3. MENGAMBIL DATA UNTUK DITAMPILKAN
// =========================================================================
$q_pengaturan = mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1");
$pengaturan = mysqli_fetch_assoc($q_pengaturan);

$q_users = mysqli_query($koneksi, "SELECT * FROM admin ORDER BY ID_ADMIN ASC");

$q_akun_saya = mysqli_query($koneksi, "SELECT * FROM admin WHERE ID_ADMIN = '$id_admin'");
$akun_saya = mysqli_fetch_assoc($q_akun_saya);
$nama_saya = $akun_saya['Nama_Lengkap'] ?? 'Admin';
$user_saya = $akun_saya['Username'] ?? '';

include '../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-primary">Pengaturan Sistem</h4>
        <p class="text-muted small mb-0">Kelola akun Anda, identitas toko, hak akses karyawan, dan format cetak.</p>
    </div>
</div>

<?php if($pesan_sukses): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 py-2 small fw-medium shadow-sm mb-4">
        <i class="bi bi-check-circle-fill me-2"></i> <?= $pesan_sukses ?>
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if($pesan_error): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 py-2 small fw-medium shadow-sm mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $pesan_error ?>
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-3">
        <div class="nav flex-column nav-pills card p-2 border-0 shadow-sm rounded-4" id="v-pills-tab" role="tablist">
            <button class="nav-link active text-start py-3 fw-medium mb-1 rounded-3" data-bs-toggle="pill" data-bs-target="#content-profil" type="button" role="tab">
                <i class="bi bi-person-badge me-2"></i> Profil Anda
            </button>
            <button class="nav-link text-start py-3 fw-medium mb-1 rounded-3" data-bs-toggle="pill" data-bs-target="#content-toko" type="button" role="tab">
                <i class="bi bi-shop me-2"></i> Profil Toko
            </button>
            <button class="nav-link text-start py-3 fw-medium mb-1 rounded-3" data-bs-toggle="pill" data-bs-target="#content-users" type="button" role="tab">
                <i class="bi bi-people me-2"></i> Manajemen Pengguna
            </button>
            <button class="nav-link text-start py-3 fw-medium rounded-3" data-bs-toggle="pill" data-bs-target="#content-struk" type="button" role="tab">
                <i class="bi bi-receipt me-2"></i> Konfigurasi Struk
            </button>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="tab-content" id="v-pills-tabContent">
            
            <div class="tab-pane fade show active" id="content-profil" role="tabpanel">
                <form action="" method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-dark mb-4"><i class="bi bi-person me-2 text-primary"></i>Informasi Pribadi</h6>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Nama Lengkap</label>
                                        <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($nama_saya) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Username <span class="text-danger">*</span></label>
                                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user_saya) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Role Akses</label>
                                        <input type="text" class="form-control bg-light text-muted" value="<?= $_SESSION['role'] ?? 'Administrator' ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-dark mb-4"><i class="bi bi-shield-check me-2 text-primary"></i>Keamanan (Ganti Sandi)</h6>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Password Saat Ini</label>
                                        <input type="text" name="password_saat_ini" class="form-control" placeholder="Masukkan sandi saat ini untuk verifikasi">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Password Baru</label>
                                        <input type="text" name="password_baru" class="form-control" placeholder="Ketik sandi baru">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Konfirmasi Password Baru</label>
                                        <input type="text" name="konfirmasi_password" class="form-control" placeholder="Ulangi sandi baru">
                                    </div>
                                    <div class="form-text small text-primary mt-0"><i class="bi bi-info-circle me-1"></i>Kosongkan jika tidak ingin ganti sandi.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12 mt-3 text-end">
                            <button type="submit" name="simpan_profil_pribadi" class="btn btn-primary fw-bold px-5 py-2 shadow-sm rounded-pill">
                                <i class="bi bi-save me-2"></i> Simpan Profil Saya
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="content-toko" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                        <h6 class="fw-bold text-dark mb-0">Informasi Dasar Toko</h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Nama Toko</label>
                                <input type="text" name="nama_toko" class="form-control" value="<?= htmlspecialchars($pengaturan['nama_toko'] ?? '') ?>" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Nomor WhatsApp</label>
                                    <input type="text" name="wa_toko" class="form-control" value="<?= htmlspecialchars($pengaturan['wa_toko'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Email Toko</label>
                                    <input type="email" name="email_toko" class="form-control" value="<?= htmlspecialchars($pengaturan['email_toko'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Alamat Lengkap</label>
                                <textarea name="alamat_toko" class="form-control" rows="3" required><?= htmlspecialchars($pengaturan['alamat_toko'] ?? '') ?></textarea>
                            </div>
                            <div class="text-end border-top pt-4 mt-4">
                                <button type="submit" name="simpan_toko" class="btn btn-primary fw-medium px-4 rounded-3"><i class="bi bi-save me-2"></i> Simpan Profil Toko</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="content-users" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0">Daftar Karyawan</h6>
                        <button class="btn btn-primary btn-sm rounded-pill px-3 fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                            <i class="bi bi-person-plus-fill me-1"></i> Tambah Akun
                        </button>
                    </div>
                    <div class="card-body p-0 mt-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small">
                                    <tr>
                                        <th class="ps-4">NAMA</th>
                                        <th>USERNAME</th>
                                        <th>HAK AKSES</th>
                                        <th class="pe-4 text-end">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if(mysqli_num_rows($q_users) > 0) {
                                        while($user = mysqli_fetch_assoc($q_users)):
                                            $id_user_row = $user['ID_ADMIN'];
                                            $nama_user = $user['Nama_Lengkap'];
                                            $username_user = $user['Username'];
                                            $password_user = $user['Password'];
                                            $role_user = $user['Role'];
                                            $inisial_user = strtoupper(substr($nama_user, 0, 1));
                                            
                                            if($role_user == 'Super Administrator') $badge_class = 'bg-primary text-primary';
                                            elseif($role_user == 'Kasir') $badge_class = 'bg-info text-info';
                                            else $badge_class = 'bg-secondary text-secondary';
                                    ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 35px; height: 35px;">
                                                    <?= $inisial_user ?>
                                                </div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($nama_user) ?></div>
                                            </div>
                                        </td>
                                        <td class="text-muted fw-medium"><?= htmlspecialchars($username_user) ?></td>
                                        <td><span class="badge <?= $badge_class ?> bg-opacity-10 border border-opacity-25 rounded-pill px-3 py-1"><?= $role_user ?></span></td>
                                        <td class="pe-4 text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <button class="btn btn-sm btn-light border text-primary rounded-pill px-3 fw-medium" data-bs-toggle="modal" data-bs-target="#modalEditUser<?= $id_user_row ?>">Edit</button>
                                                
                                                <?php if($id_user_row != $id_admin): ?>
                                                    <a href="?hapus_user=<?= $id_user_row ?>" class="btn btn-sm btn-light border text-danger rounded-pill px-3 fw-medium btn-hapus" data-pesan="Akun <?= htmlspecialchars($nama_user) ?>">Hapus</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="modalEditUser<?= $id_user_row ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-bottom-0 pt-4 px-4">
                                                    <h5 class="fw-bold text-dark mb-0">Edit Kredensial Akun</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="" method="POST">
                                                    <div class="modal-body p-4">
                                                        <input type="hidden" name="id_admin_edit" value="<?= $id_user_row ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold text-dark">Nama Lengkap</label>
                                                            <input type="text" name="nama_admin" class="form-control" value="<?= htmlspecialchars($nama_user) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold text-dark">Username Baru</label>
                                                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username_user) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold text-dark">Password Baru</label>
                                                            <input type="text" name="password" class="form-control" value="<?= htmlspecialchars($password_user) ?>" required>
                                                            <div class="form-text small text-muted">Password disimpan dalam format teks biasa (bisa dibaca).</div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold text-dark">Hak Akses / Role</label>
                                                            <select name="role" class="form-select" required>
                                                                <option value="Kasir" <?= $role_user == 'Kasir' ? 'selected' : '' ?>>Kasir (Front Office)</option>
                                                                <option value="Operator" <?= $role_user == 'Operator' ? 'selected' : '' ?>>Operator Produksi</option>
                                                                <option value="Super Administrator" <?= $role_user == 'Super Administrator' ? 'selected' : '' ?>>Super Administrator (Owner)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top-0 pb-4 px-4">
                                                        <button type="button" class="btn btn-light fw-medium" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="edit_user" class="btn btn-primary fw-bold px-4">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <?php endwhile; } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="content-struk" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                        <h6 class="fw-bold text-dark mb-0">Format Cetak Struk</h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="" method="POST">
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-dark">Awalan Nomor Pesanan (Prefix)</label>
                                <div class="input-group">
                                    <input type="text" name="prefix_nota" class="form-control" value="<?= htmlspecialchars($pengaturan['prefix_nota'] ?? 'ORD-') ?>" required>
                                    <span class="input-group-text bg-light text-muted">001</span>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-dark">Catatan Kaki Struk (Footer)</label>
                                <textarea name="catatan_struk" class="form-control" rows="4" required><?= htmlspecialchars($pengaturan['catatan_struk'] ?? '') ?></textarea>
                            </div>
                            <div class="border-top pt-4 text-end">
                                <button type="submit" name="simpan_struk" class="btn btn-primary fw-medium px-4 rounded-3"><i class="bi bi-save me-2"></i> Simpan Konfigurasi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="fw-bold text-dark mb-0">Tambah Akun Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Nama Lengkap</label>
                        <input type="text" name="nama_admin" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Password</label>
                        <input type="text" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Hak Akses / Role</label>
                        <select name="role" class="form-select" required>
                            <option value="Kasir">Kasir (Front Office)</option>
                            <option value="Operator">Operator Produksi</option>
                            <option value="Super Administrator">Super Administrator (Owner)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pb-4 px-4">
                    <button type="button" class="btn btn-light fw-medium" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_user" class="btn btn-primary fw-bold px-4">Buat Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .nav-pills .nav-link { transition: all 0.2s; color: #6c757d; }
    .nav-pills .nav-link:hover { background-color: #f8f9fa; }
    .nav-pills .nav-link.active { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; font-weight: 700; border-left: 4px solid #0d6efd; }
</style>

<?php include '../layouts/footer.php'; ?>