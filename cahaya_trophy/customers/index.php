<?php
session_start();
require '../helper/koneksi.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Data Pelanggan - Cahaya Trophy";

if (isset($_POST['tambah_pelanggan'])) {
    $id_pelanggan = "PLG" . rand(10000, 99999);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $wa = mysqli_real_escape_string($koneksi, $_POST['no_wa']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    
    mysqli_query($koneksi, "INSERT INTO pelanggan (ID_PELANGGAN, Nama_Pelanggan, No_Whatsapp, Alamat) VALUES ('$id_pelanggan', '$nama', '$wa', '$alamat')");
    header("Location: index.php?pesan=sukses_tambah");
    exit();
}

if (isset($_POST['edit_pelanggan'])) {
    $id = mysqli_real_escape_string($koneksi, $_POST['id_pelanggan']);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $wa = mysqli_real_escape_string($koneksi, $_POST['no_wa']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    
    mysqli_query($koneksi, "UPDATE pelanggan SET Nama_Pelanggan='$nama', No_Whatsapp='$wa', Alamat='$alamat' WHERE ID_PELANGGAN='$id'");
    header("Location: index.php?pesan=sukses_edit");
    exit();
}

// Proteksi proses hapus di backend agar hanya Admin yang bisa eksekusi
if (isset($_GET['hapus'])) {
    if ($_SESSION['role'] != 'Super Administrator') {
        header("Location: index.php");
        exit();
    }

    $id = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    
    $cek = mysqli_query($koneksi, "SELECT NO_PESANAN FROM pesanan WHERE ID_PELANGGAN = '$id'");
    if(mysqli_num_rows($cek) > 0) {
        header("Location: index.php?pesan=gagal_hapus");
        exit();
    } else {
        mysqli_query($koneksi, "DELETE FROM pelanggan WHERE ID_PELANGGAN='$id'");
        header("Location: index.php?pesan=sukses_hapus");
        exit();
    }
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$where_sql = "";
if($search != "") {
    $where_sql = "WHERE p.Nama_Pelanggan LIKE '%$search%' OR p.No_Whatsapp LIKE '%$search%'";
}

$query = "SELECT p.*, (SELECT COUNT(NO_PESANAN) FROM pesanan WHERE ID_PELANGGAN = p.ID_PELANGGAN) as Total_Order 
          FROM pelanggan p $where_sql ORDER BY p.Nama_Pelanggan ASC";
$q_pelanggan = mysqli_query($koneksi, $query);

$q_total = mysqli_query($koneksi, "SELECT COUNT(*) as total_pelanggan FROM pelanggan");
$stats_total = mysqli_fetch_assoc($q_total)['total_pelanggan'];

$q_loyal = mysqli_query($koneksi, "SELECT COUNT(*) as total_loyal FROM (SELECT ID_PELANGGAN FROM pesanan GROUP BY ID_PELANGGAN HAVING COUNT(NO_PESANAN) > 1) as loyal_customers");
$stats_loyal = mysqli_fetch_assoc($q_loyal)['total_loyal'] ?? 0;

include '../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-primary">Basis Data Pelanggan</h4>
        <p class="text-muted small mb-0">Kelola informasi pelanggan dan pantau performa loyalitas mereka.</p>
    </div>
    <button class="btn btn-primary fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-person-plus-fill me-1"></i> Tambah Pelanggan
    </button>
</div>

<?php if(isset($_GET['pesan'])): ?>
    <?php if($_GET['pesan'] == 'gagal_hapus'): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 py-2">
            <i class="bi bi-x-circle-fill me-2"></i> Gagal dihapus! Pelanggan ini masih memiliki riwayat pesanan.
            <button type="button" class="btn-close pb-2" data-bs-dismiss="alert"></button>
        </div>
    <?php else: ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 py-2">
            <i class="bi bi-check-circle-fill me-2"></i> Data pelanggan berhasil diperbarui!
            <button type="button" class="btn-close pb-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success"><i class="bi bi-people fs-3"></i></div>
                <div>
                    <div class="text-muted small fw-bold mb-1 text-uppercase">Total Pelanggan Terdaftar</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= $stats_total ?> <span class="fs-6 text-muted fw-normal">Orang</span></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning"><i class="bi bi-star-fill fs-3"></i></div>
                <div>
                    <div class="text-muted small fw-bold mb-1 text-uppercase">Pelanggan Loyal (Repeat Order)</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= $stats_loyal ?> <span class="fs-6 text-muted fw-normal">Orang</span></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small">
                    <tr>
                        <th class="ps-4 py-3">NAMA PELANGGAN</th>
                        <th>WHATSAPP</th>
                        <th>ALAMAT</th>
                        <th class="text-center">TOTAL PESANAN</th>
                        <th class="pe-4 text-end">AKSI</th>
                    </tr>
                </thead>
                <tbody style="font-size: 0.9rem;">
                    <?php 
                    if(mysqli_num_rows($q_pelanggan) > 0) {
                        while($row = mysqli_fetch_assoc($q_pelanggan)): 
                            $inisial = strtoupper(substr($row['Nama_Pelanggan'], 0, 1));
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                    <?= $inisial ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= $row['Nama_Pelanggan'] ?></div>
                                    <div class="text-muted small">ID: <?= $row['ID_PELANGGAN'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="https://wa.me/<?= str_replace('08', '628', $row['No_Whatsapp']) ?>" target="_blank" class="text-decoration-none text-success fw-medium">
                                <i class="bi bi-whatsapp me-1"></i> <?= $row['No_Whatsapp'] ?>
                            </a>
                        </td>
                        <td class="text-muted text-truncate" style="max-width: 200px;"><?= $row['Alamat'] ?></td>
                        <td class="text-center">
                            <span class="badge bg-primary rounded-pill px-3 py-2"><?= $row['Total_Order'] ?> Pesanan</span>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="btn-group btn-group-sm shadow-sm">
                                <a href="detail.php?id=<?= $row['ID_PELANGGAN'] ?>" class="btn btn-primary"><i class="bi bi-eye me-1"></i> Detail</a>
                                <button type="button" class="btn btn-light border text-secondary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['ID_PELANGGAN'] ?>"><i class="bi bi-pencil"></i></button>
                                
                                <?php if($_SESSION['role'] == 'Super Administrator'): ?>
                                <a href="index.php?hapus=<?= $row['ID_PELANGGAN'] ?>" class="btn btn-light border text-danger btn-hapus" data-pesan="Semua data milik pelanggan <?= $row['Nama_Pelanggan'] ?> akan dihapus dari sistem!">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEdit<?= $row['ID_PELANGGAN'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-header border-bottom-0 pt-4 px-4"><h5 class="fw-bold mb-0">Edit Pelanggan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                                <form action="" method="POST">
                                    <input type="hidden" name="id_pelanggan" value="<?= $row['ID_PELANGGAN'] ?>">
                                    <div class="modal-body px-4 py-3 row g-3 text-start">
                                        <div class="col-12">
                                            <label class="form-label small fw-medium">Nama Pelanggan</label>
                                            <input type="text" name="nama_pelanggan" class="form-control" value="<?= $row['Nama_Pelanggan'] ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-medium">Nomor WhatsApp</label>
                                            <input type="text" name="no_wa" class="form-control" value="<?= $row['No_Whatsapp'] ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-medium">Alamat</label>
                                            <textarea name="alamat" class="form-control" rows="2" required><?= $row['Alamat'] ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top-0 px-4 pb-4"><button type="submit" name="edit_pelanggan" class="btn btn-primary w-100 fw-bold">Simpan Perubahan</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile; 
                    } else {
                        echo '<tr><td colspan="5" class="text-center py-5 text-muted">Data pelanggan tidak ditemukan.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pt-4 px-4"><h5 class="fw-bold mb-0 text-primary">Tambah Pelanggan Baru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="" method="POST">
                <div class="modal-body px-4 py-3 row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-medium">Nama Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_pelanggan" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Nomor WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="no_wa" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Alamat Lengkap <span class="text-danger">*</span></label>
                        <textarea name="alamat" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4"><button type="submit" name="tambah_pelanggan" class="btn btn-primary w-100 fw-bold">Simpan Pelanggan</button></div>
            </form>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>