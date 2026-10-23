<?php
session_start();
require '../helper/koneksi.php';
require '../helper/fungsi.php';

// Proteksi: Kasir dan Admin boleh masuk, Operator ditendang
if (!isset($_SESSION['id_admin']) || $_SESSION['role'] == 'Operator') {
    header("Location: ../dashboard/index.php");
    exit();
}

$page_title = "Master Produk - Cahaya Trophy";

// ==========================================
// 1. PROSES TAMBAH KATALOG & RESEP (CREATE)
// ==========================================
if (isset($_POST['simpan_katalog'])) {
    $kategori = $_POST['kategori'];
    $nama_model = mysqli_real_escape_string($koneksi, $_POST['nama_model']);
    $harga_satuan = $_POST['harga_satuan'];
    
    // Harga Set hanya berlaku untuk Piala, sisanya 0
    $harga_set = ($kategori == 'Piala') ? $_POST['harga_set'] : 0;
    
    // Default gambar jika tidak upload
    $foto_model = "default-trophy.png"; 

    // Proses Upload Foto
    if (isset($_FILES['foto_model']) && $_FILES['foto_model']['error'] == 0) {
        $nama_file = $_FILES['foto_model']['name'];
        $tmp_file = $_FILES['foto_model']['tmp_name'];
        $ext = pathinfo($nama_file, PATHINFO_EXTENSION);
        $foto_model = "katalog_" . time() . "." . $ext;
        move_uploaded_file($tmp_file, "../assets/img/uploads/" . $foto_model);
    }

    // Simpan ke tabel master
    mysqli_query($koneksi, "INSERT INTO katalog_produk (Kategori, Nama_Model, Harga_Satuan, Harga_Set, Foto_Model) VALUES ('$kategori', '$nama_model', '$harga_satuan', '$harga_set', '$foto_model')");
    $id_katalog = mysqli_insert_id($koneksi); 

    // Jika Kategori Piala, simpan resepnya
    if ($kategori == 'Piala') {
        $komponen = [
            $_POST['id_figure'] ?? '', $_POST['id_body'] ?? '', $_POST['id_marmer'] ?? '',
            $_POST['id_kaki'] ?? '', $_POST['id_tangga'] ?? '', $_POST['id_stall'] ?? ''
        ];
        foreach ($komponen as $id_bahan) {
            if (!empty($id_bahan)) {
                mysqli_query($koneksi, "INSERT INTO resep_piala (ID_KATALOG, ID_BAHAN, Qty_Bahan_Per_Pcs) VALUES ('$id_katalog', '$id_bahan', '1')");
            }
        }
    }

    header("Location: index.php?pesan=berhasil");
    exit();
}

// ==========================================
// 2. PROSES HAPUS KATALOG (DELETE)
// ==========================================
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    
    $q_foto = mysqli_query($koneksi, "SELECT Foto_Model FROM katalog_produk WHERE ID_KATALOG = '$id_hapus'");
    if ($row_foto = mysqli_fetch_assoc($q_foto)) {
        if ($row_foto['Foto_Model'] != 'default-trophy.png' && !empty($row_foto['Foto_Model'])) {
            $path = "../assets/img/uploads/" . $row_foto['Foto_Model'];
            if (file_exists($path)) unlink($path);
        }
    }

    mysqli_query($koneksi, "DELETE FROM katalog_produk WHERE ID_KATALOG = '$id_hapus'");
    header("Location: index.php?pesan=dihapus");
    exit();
}

// Fungsi helper ambil bahan baku
function getBahan($koneksi, $kat) { return mysqli_query($koneksi, "SELECT * FROM bahanbaku WHERE Kategori='$kat'"); }

// LOGIKA PENCARIAN
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$where_katalog = "";
if (!empty($search)) {
    $where_katalog = " WHERE Nama_Model LIKE '%$search%' OR Kategori LIKE '%$search%' ";
}

// Ambil daftar produk (dengan filter pencarian jika ada)
$q_katalog = mysqli_query($koneksi, "SELECT * FROM katalog_produk $where_katalog ORDER BY Kategori ASC, Nama_Model ASC");

include '../layouts/header.php';
?>

<style>
    /* Styling khusus untuk filter button agar tampil elegan */
    .category-filter {
        border-radius: 8px;
        transition: all 0.2s ease-in-out;
    }
    .category-filter.active {
        background-color: #0d6efd !important;
        color: #fff !important;
        border-color: #0d6efd !important;
        box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
    }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'berhasil'): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 py-2" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> Produk baru berhasil disimpan!
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php elseif(isset($_GET['pesan']) && $_GET['pesan'] == 'diubah'): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 py-2" role="alert">
        <i class="bi bi-pencil-square me-2"></i> Perubahan produk dan foto berhasil disimpan!
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php elseif(isset($_GET['pesan']) && $_GET['pesan'] == 'dihapus'): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 py-2" role="alert">
        <i class="bi bi-trash-fill me-2"></i> Produk berhasil dihapus dari katalog!
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-primary">Master Produk (Katalog)</h4>
        <p class="text-muted small mb-0">Kelola daftar harga dan gambar piala, medali, dan merchandise lainnya.</p>
    </div>
    
    <?php if($_SESSION['role'] == 'Super Administrator'): ?>
    <button class="btn btn-primary fw-medium" data-bs-toggle="modal" data-bs-target="#modalTambahKatalog">
        <i class="bi bi-plus-circle me-1"></i> Tambah Produk Baru
    </button>
    <?php endif; ?>
</div>

<?php if(!empty($search)): ?>
    <div class="alert alert-info py-2 rounded-3 small mb-3 d-flex align-items-center justify-content-between">
        <div><i class="bi bi-search me-2"></i> Menampilkan hasil pencarian untuk: <strong>"<?= htmlspecialchars($search) ?>"</strong></div>
        <a href="index.php" class="btn btn-sm btn-danger fw-bold text-decoration-none py-0 px-2"><i class="bi bi-x-lg"></i> Reset Filter</a>
    </div>
<?php endif; ?>

<div class="d-flex gap-2 overflow-auto no-scrollbar mb-4" id="kategoriFilters">
    <button type="button" class="btn btn-outline-primary px-4 py-2 fw-medium category-filter active" data-filter="Semua">Semua</button>
    <button type="button" class="btn btn-outline-primary px-4 py-2 fw-medium category-filter" data-filter="Piala">Piala</button>
    <button type="button" class="btn btn-outline-primary px-4 py-2 fw-medium category-filter" data-filter="Medali">Medali</button>
    <button type="button" class="btn btn-outline-primary px-4 py-2 fw-medium category-filter" data-filter="Pin">Pin</button>
    <button type="button" class="btn btn-outline-primary px-4 py-2 fw-medium category-filter" data-filter="Gantungan Kunci">Ganci</button>
    <button type="button" class="btn btn-outline-primary px-4 py-2 fw-medium category-filter" data-filter="Lainnya">Lainnya</button>
</div>

<div class="row g-4" id="productGrid">
    <?php 
    if(mysqli_num_rows($q_katalog) > 0) {
        while($row = mysqli_fetch_assoc($q_katalog)) { 
            $badge_color = 'bg-secondary';
            if($row['Kategori'] == 'Piala') $badge_color = 'bg-primary';
            if($row['Kategori'] == 'Medali') $badge_color = 'bg-warning text-dark';
            if($row['Kategori'] == 'Pin' || $row['Kategori'] == 'Gantungan Kunci') $badge_color = 'bg-info text-dark';
    ?>
    <div class="col-12 col-md-4 col-lg-3 product-item" data-category="<?= $row['Kategori'] ?>">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden d-flex flex-column position-relative">
            <span class="badge <?= $badge_color ?> position-absolute top-0 start-0 m-2 z-1"><?= $row['Kategori'] ?></span>
            
            <div class="bg-light text-center d-flex align-items-center justify-content-center" style="height: 200px; border-bottom: 1px solid #eee;">
                <?php if($row['Foto_Model'] == 'default-trophy.png' || empty($row['Foto_Model'])): ?>
                    <div class="d-flex flex-column align-items-center justify-content-center text-muted opacity-50">
                        <i class="bi bi-image" style="font-size: 4rem;"></i>
                        <span class="small mt-2 fw-medium">Tanpa Foto</span>
                    </div>
                <?php else: ?>
                    <img src="../assets/img/uploads/<?= $row['Foto_Model'] ?>" alt="<?= $row['Nama_Model'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php endif; ?>
            </div>
            
            <div class="card-body p-3 flex-grow-1">
                <h6 class="fw-bold text-dark mb-2 lh-sm" style="min-height: 38px;"><?= $row['Nama_Model'] ?></h6>
                <div class="d-flex flex-column gap-1 mb-2">
                    <span class="badge bg-light text-dark text-start border py-2 text-wrap">
                        Satuan: <span class="text-primary fw-bold"><?= formatRupiah($row['Harga_Satuan']) ?></span>
                    </span>
                    <?php if($row['Kategori'] == 'Piala'): ?>
                    <span class="badge bg-light text-dark text-start border py-2 text-wrap">
                        1 Set (3 Pcs): <span class="text-success fw-bold"><?= formatRupiah($row['Harga_Set']) ?></span>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if($row['Kategori'] == 'Piala'): ?>
                    <?php 
                        $id_kat = $row['ID_KATALOG'];
                        $q_resep = mysqli_query($koneksi, "SELECT b.Nama_Bahan FROM resep_piala r JOIN bahanbaku b ON r.ID_BAHAN = b.ID_BAHAN WHERE r.ID_KATALOG = '$id_kat'");
                    ?>
                    <hr class="my-2 border-secondary opacity-25">
                    <p class="text-muted mb-1" style="font-size: 0.7rem; font-weight: 600;">KOMPONEN (BOM):</p>
                    <ul class="text-muted ps-3 mb-0" style="font-size: 0.75rem; line-height: 1.4;">
                        <?php while($resep = mysqli_fetch_assoc($q_resep)): ?>
                            <li><?= $resep['Nama_Bahan'] ?></li>
                        <?php endwhile; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <?php if($_SESSION['role'] == 'Super Administrator'): ?>
            <div class="card-footer bg-white border-top-0 p-3 pt-0 d-flex gap-2">
                <a href="edit.php?id=<?= $row['ID_KATALOG'] ?>" class="btn btn-sm btn-outline-secondary w-50"><i class="bi bi-pencil me-1"></i> Edit</a>
                
                <a href="index.php?hapus=<?= $row['ID_KATALOG'] ?>" class="btn btn-sm btn-outline-danger w-50 btn-hapus" data-pesan="Produk <?= $row['Nama_Model'] ?> akan dihapus permanen dari katalog!">
                    <i class="bi bi-trash me-1"></i> Hapus
                </a>
                
            </div>
            <?php else: ?>
            <div class="card-footer bg-white border-top-0 p-3 pt-0 text-center">
                <span class="badge bg-light text-muted border w-100 py-2"><i class="bi bi-eye"></i> Mode Hanya Lihat</span>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <?php 
        }
    } else {
        // Tampilkan ini jika pencarian tidak membuahkan hasil
        echo '<div class="col-12"><div class="text-center text-muted p-5 bg-white rounded-4 border border-dashed"><i class="bi bi-journal-x fs-1 mb-3 d-block opacity-50"></i>Produk tidak ditemukan.</div></div>';
    }
    ?>
</div>

<div class="modal fade" id="modalTambahKatalog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h1 class="modal-title fs-5 fw-bold text-dark">Tambah Produk Baru</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body px-4 pb-0 row g-3">
                    
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-primary">Kategori <span class="text-danger">*</span></label>
                        <select class="form-select border-primary" name="kategori" id="kategoriSelect" required>
                            <option value="Piala" selected>Piala</option>
                            <option value="Medali">Medali</option>
                            <option value="Pin">Pin</option>
                            <option value="Gantungan Kunci">Gantungan Kunci</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label small fw-medium">Nama Produk / Model <span class="text-danger">*</span></label>
                        <input type="text" name="nama_model" class="form-control" placeholder="Contoh: Piala Elang, Medali Emas, dll" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Harga Jual Satuan <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" name="harga_satuan" class="form-control border-start-0" required min="0">
                        </div>
                    </div>
                    
                    <div class="col-md-6" id="areaHargaSet">
                        <label class="form-label small fw-medium">Harga 1 Set (Juara 1-3) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" name="harga_set" id="inputHargaSet" class="form-control border-start-0" required min="0">
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-medium">Foto Produk (Opsional)</label>
                        <input type="file" name="foto_model" class="form-control" accept="image/*">
                    </div>

                    <div id="areaResep" class="col-12 mt-4 row g-3">
                        <div class="col-12">
                            <h6 class="fw-bold mb-0 small text-primary border-bottom pb-2"><i class="bi bi-tools me-1"></i> Resep Perakitan Piala (BOM)</h6>
                        </div>

                        <?php 
                        $kategori_resep = ['Figure', 'Body', 'Marmer', 'Kaki Piala', 'Tangga Juara', 'Stall'];
                        $name_inputs = ['id_figure', 'id_body', 'id_marmer', 'id_kaki', 'id_tangga', 'id_stall'];
                        for($i=0; $i<count($kategori_resep); $i++) {
                            $kat = $kategori_resep[$i];
                            $q_bahan = getBahan($koneksi, $kat);
                        ?>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium text-muted"><?= $kat ?></label>
                            <select class="form-select form-select-sm" name="<?= $name_inputs[$i] ?>">
                                <option value="">-- Tidak Pakai --</option>
                                <?php while($b = mysqli_fetch_assoc($q_bahan)): ?>
                                    <option value="<?= $b['ID_BAHAN'] ?>"><?= $b['Nama_Bahan'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <?php } ?>
                    </div>

                </div>
                <div class="modal-footer border-top-0 pt-4 pb-4 px-4 mt-3">
                    <button type="button" class="btn-light fw-medium" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_katalog" class="btn btn-primary fw-bold px-4">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Logika Filter Kategori
    const filterButtons = document.querySelectorAll('.category-filter');
    const productItems = document.querySelectorAll('.product-item');

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            filterButtons.forEach(b => b.classList.remove('active', 'text-white'));
            this.classList.add('active');

            let filterValue = this.getAttribute('data-filter');

            productItems.forEach(item => {
                if(filterValue === 'Semua' || item.getAttribute('data-category') === filterValue) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    // Logika Form Input Modal
    const kategoriSelect = document.getElementById('kategoriSelect');
    const areaResep = document.getElementById('areaResep');
    const areaHargaSet = document.getElementById('areaHargaSet');
    const inputHargaSet = document.getElementById('inputHargaSet');

    function toggleForm() {
        if(kategoriSelect.value === 'Piala') {
            areaResep.style.display = 'flex';
            areaHargaSet.style.display = 'block';
            inputHargaSet.required = true;
        } else {
            areaResep.style.display = 'none';
            areaHargaSet.style.display = 'none';
            inputHargaSet.required = false;
            inputHargaSet.value = 0;
        }
    }
    kategoriSelect.addEventListener('change', toggleForm);
    toggleForm(); 
});
</script>

<?php include '../layouts/footer.php'; ?>