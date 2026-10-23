<?php
session_start();
require '../helper/koneksi.php';
require '../helper/fungsi.php';

if (!isset($_SESSION['id_admin']) || ($_SESSION['role'] != 'Super Administrator' && $_SESSION['role'] != 'Operator')) {
    header("Location: ../dashboard/index.php");
    exit();
}

$id_katalog = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

// ==========================================
// PROSES UPDATE DATA (KETIKA TOMBOL DIKLIK)
// ==========================================
if (isset($_POST['update_katalog'])) {
    $kategori = $_POST['kategori'];
    $nama_model = mysqli_real_escape_string($koneksi, $_POST['nama_model']);
    $harga_satuan = $_POST['harga_satuan'];
    $harga_set = ($kategori == 'Piala') ? $_POST['harga_set'] : 0;
    
    $foto_model = $_POST['foto_lama']; // Ambil nama file lama bawaan dulu

    // Jika user mengunggah foto baru
    if (isset($_FILES['foto_model']) && $_FILES['foto_model']['error'] == 0) {
        $nama_file = $_FILES['foto_model']['name'];
        $tmp_file = $_FILES['foto_model']['tmp_name'];
        $ext = pathinfo($nama_file, PATHINFO_EXTENSION);
        
        // Buat nama file baru unik berdasarkan waktu
        $foto_model_baru = "katalog_" . time() . "." . $ext;
        
        if (move_uploaded_file($tmp_file, "../assets/img/uploads/" . $foto_model_baru)) {
            // Hapus file foto lama dari folder server agar tidak menumpuk sampah (kecuali default)
            if ($_POST['foto_lama'] != 'default-trophy.png' && !empty($_POST['foto_lama'])) {
                $path_lama = "../assets/img/uploads/" . $_POST['foto_lama'];
                if (file_exists($path_lama)) {
                    unlink($path_lama);
                }
            }
            $foto_model = $foto_model_baru; // Ganti acuan ke nama file yang baru
        }
    }

    // Update data utama produk
    mysqli_query($koneksi, "UPDATE katalog_produk SET 
                                Kategori = '$kategori', 
                                Nama_Model = '$nama_model', 
                                Harga_Satuan = '$harga_satuan', 
                                Harga_Set = '$harga_set', 
                                Foto_Model = '$foto_model' 
                            WHERE ID_KATALOG = '$id_katalog'");

    // Update Resep Komponen Khusus Kategori Piala
    if ($kategori == 'Piala') {
        // Hapus resep lama dulu biar tidak duplikat
        mysqli_query($koneksi, "DELETE FROM resep_piala WHERE ID_KATALOG = '$id_katalog'");
        
        $komponen = [
            $_POST['id_figure'] ?? '', $_POST['id_body'] ?? '', $_POST['id_marmer'] ?? '',
            $_POST['id_kaki'] ?? '', $_POST['id_tangga'] ?? '', $_POST['id_stall'] ?? ''
        ];
        foreach ($komponen as $id_bahan) {
            if (!empty($id_bahan)) {
                mysqli_query($koneksi, "INSERT INTO resep_piala (ID_KATALOG, ID_BAHAN, Qty_Bahan_Per_Pcs) VALUES ('$id_katalog', '$id_bahan', '1')");
            }
        }
    } else {
        // Jika diubah dari piala ke medali, hapus sisa resepnya
        mysqli_query($koneksi, "DELETE FROM resep_piala WHERE ID_KATALOG = '$id_katalog'");
    }

    header("Location: index.php?pesan=diubah");
    exit();
}

// ==========================================
// AMBIL DATA PRODUK SEBELUMNYA UNTUK DI-LOAD
// ==========================================
$q_produk = mysqli_query($koneksi, "SELECT * FROM katalog_produk WHERE ID_KATALOG = '$id_katalog'");
if (mysqli_num_rows($q_produk) == 0) {
    header("Location: index.php");
    exit();
}
$p = mysqli_fetch_assoc($q_produk);

// Ambil resep komponen yang sedang terpasang (diubah ke format array biar gampang dicek)
$resep_aktif = [];
$q_res = mysqli_query($koneksi, "SELECT ID_BAHAN FROM resep_piala WHERE ID_KATALOG = '$id_katalog'");
while ($r = mysqli_fetch_assoc($q_res)) {
    $resep_aktif[] = $r['ID_BAHAN'];
}

// Helper dropdown bahan
function getBahan($koneksi, $kat) { return mysqli_query($koneksi, "SELECT * FROM bahanbaku WHERE Kategori='$kat'"); }

$page_title = "Edit Produk - Cahaya Trophy";
include '../layouts/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="btn btn-sm btn-light border mb-3 text-muted">
        <i class="bi bi-arrow-left"></i> Batal & Kembali
    </a>
    <h4 class="fw-bold mb-1 text-primary">Edit Ragam Varian Produk</h4>
    <p class="text-muted small">Ubah informasi harga, resep rakitan, atau unggah foto asli produk.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="foto_lama" value="<?= $p['Foto_Model'] ?>">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Kategori</label>
                            <select class="form-select bg-light" name="kategori" id="kategoriSelect" required>
                                <option value="Piala" <?= $p['Kategori'] == 'Piala' ? 'selected' : '' ?>>Piala</option>
                                <option value="Medali" <?= $p['Kategori'] == 'Medali' ? 'selected' : '' ?>>Medali</option>
                                <option value="Pin" <?= $p['Kategori'] == 'Pin' ? 'selected' : '' ?>>Pin</option>
                                <option value="Gantungan Kunci" <?= $p['Kategori'] == 'Gantungan Kunci' ? 'selected' : '' ?>>Gantungan Kunci</option>
                                <option value="Lainnya" <?= $p['Kategori'] == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label small fw-medium">Nama Model Produk</label>
                            <input type="text" name="nama_model" class="form-control" value="<?= htmlspecialchars($p['Nama_Model']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Harga Jual Satuan</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                <input type="number" name="harga_satuan" class="form-control border-start-0" value="<?= $p['Harga_Satuan'] ?>" required min="0">
                            </div>
                        </div>

                        <div class="col-md-6" id="areaHargaSet">
                            <label class="form-label small fw-medium">Harga Jual 1 Set (Isi 3)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                <input type="number" name="harga_set" id="inputHargaSet" class="form-control border-start-0" value="<?= $p['Harga_Set'] ?>" min="0">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-medium d-block">File Foto Sekarang</label>
                            <div class="d-flex align-items-center gap-3 border rounded p-2 bg-light bg-opacity-50 mb-2" style="max-width: 350px;">
                                <img src="../assets/img/uploads/<?= $p['Foto_Model'] ?>" class="rounded shadow-sm" width="60" height="60" style="object-fit: cover;">
                                <span class="text-muted small text-truncate"><?= $p['Foto_Model'] ?></span>
                            </div>
                            
                            <label class="form-label small fw-medium text-primary">Ganti / Upload Foto Baru</label>
                            <input type="file" name="foto_model" class="form-control" accept="image/*">
                            <div class="form-text small">Biarkan kosong jika tidak ingin mengubah foto produk.</div>
                        </div>

                        <div id="areaResep" class="col-12 mt-4 row g-3">
                            <div class="col-12">
                                <h6 class="fw-bold mb-0 small text-primary border-bottom pb-2"><i class="bi bi-tools me-1"></i> Sesuaikan Ulang Resep Bahan Baku (BOM)</h6>
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
                                    <?php 
                                    while($b = mysqli_fetch_assoc($q_bahan)): 
                                        // Check apakah part ini masuk dalam resep aktif piala ini sebelumnya
                                        $selected = in_array($b['ID_BAHAN'], $resep_aktif) ? 'selected' : '';
                                    ?>
                                        <option value="<?= $b['ID_BAHAN'] ?>" <?= $selected ?>><?= $b['Nama_Bahan'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <?php } ?>
                        </div>

                        <div class="col-12 mt-4 pt-2 border-top">
                            <button type="submit" name="update_katalog" class="btn btn-primary fw-bold px-4 py-2"><i class="bi bi-save me-1"></i> Simpan Semua Perubahan</button>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
        }
    }
    kategoriSelect.addEventListener('change', toggleForm);
    toggleForm(); 
});
</script>

<?php include '../layouts/footer.php'; ?>