<?php
session_start();
require '../helper/koneksi.php';
require '../helper/fungsi.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Buat Pesanan Kasir - Cahaya Trophy";

// 1. Ambil Data Pelanggan
$q_pelanggan = mysqli_query($koneksi, "SELECT ID_PELANGGAN, Nama_Pelanggan FROM pelanggan ORDER BY Nama_Pelanggan ASC");

// 2. Ambil Data Master Produk dari Katalog
$q_katalog = mysqli_query($koneksi, "SELECT * FROM katalog_produk ORDER BY Kategori ASC, Nama_Model ASC");

include '../layouts/header.php';
?>

<style>
    .product-card { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
    .product-card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; border-color: #0d6efd !important; }
    .cart-item { border-left: 3px solid #0d6efd; background-color: #f8f9fa; }
    .category-filter.active { background-color: #0d6efd; color: white !important; border-color: #0d6efd; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<div class="mb-4">
    <a href="index.php" class="btn btn-sm btn-light border mb-3 text-muted">
        <i class="bi bi-arrow-left"></i> Kembali ke Daftar
    </a>
    <h4 class="fw-bold mb-0 text-primary">Sistem Kasir (POS)</h4>
    <p class="text-muted small">Pilih pelanggan, klik produk untuk memasukkan ke keranjang, dan proses transaksi.</p>
</div>

<div id="errorNotif" class="alert alert-danger d-none shadow-sm rounded-4 mb-4 border-danger border-opacity-25">
    <div class="d-flex align-items-start gap-3">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
        <div id="errorText" class="small text-dark mt-1"></div>
    </div>
</div>

<form action="proses_tambah.php" method="POST" id="formKasir" enctype="multipart/form-data">
    <div class="row g-4">
        
        <div class="col-lg-8">
            
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-person-badge text-primary me-2"></i>Informasi Pelanggan</h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="tipe_pelanggan" id="pelLama" value="lama" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="pelLama">Data Lama</label>

                        <input type="radio" class="btn-check" name="tipe_pelanggan" id="pelBaru" value="baru" autocomplete="off">
                        <label class="btn btn-outline-primary" for="pelBaru">Buat Baru</label>
                    </div>
                </div>
                <div class="card-body p-4 pt-3">
                    <div id="areaPelangganLama">
                        <select class="form-select border-primary" name="id_pelanggan_lama" id="id_pelanggan_lama">
                            <option value="" disabled selected>-- Cari dan Pilih Pelanggan --</option>
                            <?php while($pel = mysqli_fetch_assoc($q_pelanggan)): ?>
                                <option value="<?= $pel['ID_PELANGGAN'] ?>"><?= $pel['Nama_Pelanggan'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div id="areaPelangganBaru" class="row g-3" style="display: none;">
                        <div class="col-md-6">
                            <input type="text" name="nama_pelanggan" id="nama_pelanggan" class="form-control" placeholder="Nama Lengkap">
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="no_wa" id="no_wa" class="form-control" placeholder="Nomor WhatsApp">
                        </div>
                        <div class="col-12">
                            <input type="text" name="alamat" id="alamat" class="form-control" placeholder="Alamat Lengkap">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 border-top border-primary border-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-grid text-primary me-2"></i>Katalog Produk</h6>
                    </div>
                    <div class="d-flex gap-2 overflow-auto no-scrollbar pb-2" id="kategoriFilters">
                        <button type="button" class="btn btn-sm btn-outline-secondary category-filter active" data-filter="Semua">Semua</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary category-filter" data-filter="Piala">Piala</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary category-filter" data-filter="Medali">Medali</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary category-filter" data-filter="Pin">Pin</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary category-filter" data-filter="Gantungan Kunci">Ganci</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary category-filter" data-filter="Lainnya">Lainnya</button>
                    </div>
                </div>
                <div class="card-body p-4 bg-light bg-opacity-50">
                    <div class="row g-3" id="productGrid">
                        <?php 
                        mysqli_data_seek($q_katalog, 0); 
                        while($row = mysqli_fetch_assoc($q_katalog)): 
                            $img = (empty($row['Foto_Model']) || $row['Foto_Model'] == 'default-trophy.png') ? '' : '../assets/img/uploads/'.$row['Foto_Model'];
                        ?>
                        <div class="col-6 col-md-4 product-item" data-category="<?= $row['Kategori'] ?>">
                            <div class="card h-100 border rounded-3 product-card overflow-hidden">
                                <div class="bg-white text-center d-flex align-items-center justify-content-center" style="height: 120px; border-bottom: 1px solid #f1f3f5;">
                                    <?php if($img == ''): ?>
                                        <i class="bi bi-box-seam text-muted opacity-25" style="font-size: 3rem;"></i>
                                    <?php else: ?>
                                        <img src="<?= $img ?>" alt="img" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                                <div class="card-body p-2 d-flex flex-column">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary mb-1 align-self-start" style="font-size: 0.6rem;"><?= $row['Kategori'] ?></span>
                                    <h6 class="fw-bold mb-2 small text-dark lh-sm flex-grow-1"><?= $row['Nama_Model'] ?></h6>
                                    
                                    <div class="d-grid gap-1 mt-auto">
                                        <button type="button" class="btn btn-outline-primary btn-sm fw-bold d-flex justify-content-between align-items-center px-2 py-1" style="font-size: 0.75rem;" 
                                            onclick="tambahKeKeranjang(<?= $row['ID_KATALOG'] ?>, '<?= addslashes($row['Nama_Model']) ?>', 'Satuan', <?= $row['Harga_Satuan'] ?>)">
                                            <span>Satuan</span> <span><?= formatRupiahSingkat($row['Harga_Satuan']) ?> +</span>
                                        </button>
                                        
                                        <?php if($row['Harga_Set'] > 0): ?>
                                        <button type="button" class="btn btn-outline-success btn-sm fw-bold d-flex justify-content-between align-items-center px-2 py-1" style="font-size: 0.75rem;"
                                            onclick="tambahKeKeranjang(<?= $row['ID_KATALOG'] ?>, '<?= addslashes($row['Nama_Model']) ?>', 'Set (1-3)', <?= $row['Harga_Set'] ?>)">
                                            <span>Paket Set</span> <span><?= formatRupiahSingkat($row['Harga_Set']) ?> +</span>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 90px; z-index: 1;">
                <div class="card-header bg-primary text-white rounded-top-4 py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-cart3 me-2"></i>Keranjang Pesanan</h6>
                    <span class="badge bg-white text-primary rounded-pill" id="cartCount">0 Item</span>
                </div>
                
                <div class="card-body p-0" style="max-height: 350px; overflow-y: auto; background-color: #f8f9fa;">
                    <div id="cartEmpty" class="text-center p-5 text-muted">
                        <i class="bi bi-cart-x fs-1 opacity-50 mb-2 d-block"></i>
                        <small>Keranjang masih kosong.<br>Klik produk di sebelah kiri.</small>
                    </div>
                    <div id="cartItems" class="d-flex flex-column gap-2 p-2">
                        </div>
                </div>

                <div class="card-footer bg-white border-top p-4 rounded-bottom-4">
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark mb-1">Deadline <span class="text-danger">*</span></label>
                            <input type="date" name="deadline" id="inputDeadline" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark mb-1">Ambil via <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="metode_pengambilan">
                                <option value="Diambil di Toko">Toko</option>
                                <option value="Kirim Kurir">Kurir</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>Subtotal</span>
                        <span id="txtSubtotal">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 small text-muted">
                        <span>Diskon</span>
                        <div class="input-group input-group-sm w-50">
                            <span class="input-group-text border-0 bg-light">Rp</span>
                            <input type="number" name="diskon" id="diskon" class="form-control border-0 bg-light text-end kalkulasi" value="0" min="0">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3 pt-2 border-top">
                        <span class="fw-bold text-dark">Total Tagihan</span>
                        <span class="fs-4 fw-bold text-primary" id="txtTotal">Rp 0</span>
                        <input type="hidden" name="total_harga" id="inputTotal" value="0">
                    </div>

                    <div class="border rounded-3 p-3 bg-light bg-opacity-50 mb-3">
                        <select class="form-select form-select-sm mb-2 fw-bold" name="status_pembayaran" id="statusPembayaran">
                            <option value="Belum Bayar">Belum Bayar</option>
                            <option value="DP">Bayar DP</option>
                            <option value="Lunas">Langsung Lunas</option>
                        </select>
                        <div id="areaNominalBayar" style="display: none;">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white">Rp</span>
                                <input type="number" name="nominal_dp" id="jumlahDibayar" class="form-control kalkulasi" placeholder="Nominal DP" value="0" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div id="cartHiddenInputs"></div>

                    <button type="button" class="btn btn-primary fw-bold w-100 py-2 shadow-sm" id="btnSubmitForm">
                        <i class="bi bi-check2-circle me-1"></i> Proses Transaksi
                    </button>

                </div>
            </div>
        </div>

    </div>
</form>

<?php include '../layouts/footer.php'; ?>

<script>
let cart = []; 

function formatRupiahJS(angka) {
    return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
}

function tambahKeKeranjang(id_katalog, nama, tipe, harga) {
    let existingItem = cart.find(item => item.id_katalog === id_katalog && item.tipe === tipe);
    if(existingItem) {
        existingItem.qty += 1; 
    } else {
        cart.push({ id_katalog: id_katalog, nama: nama, tipe: tipe, harga: parseInt(harga), qty: 1, catatan: '' });
    }
    renderKeranjang();
}

function hapusItem(index) {
    cart.splice(index, 1);
    renderKeranjang();
}

function ubahQty(index, val) {
    let newQty = parseInt(val);
    if(newQty > 0) { cart[index].qty = newQty; renderKeranjang(); }
}

function ubahCatatan(index, val) {
    cart[index].catatan = val;
}

function renderKeranjang() {
    const cartContainer = document.getElementById('cartItems');
    const emptyState = document.getElementById('cartEmpty');
    const hiddenContainer = document.getElementById('cartHiddenInputs');
    
    cartContainer.innerHTML = '';
    hiddenContainer.innerHTML = '';
    
    let subtotal = 0;
    let totalItems = 0;

    if(cart.length === 0) {
        emptyState.style.display = 'block';
    } else {
        emptyState.style.display = 'none';
        cart.forEach((item, index) => {
            let itemTotal = item.harga * item.qty;
            subtotal += itemTotal;
            totalItems += item.qty;

            let itemHtml = `
                <div class="cart-item p-3 border border-start-0 border-end-0 border-top-0 bg-white shadow-sm rounded-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.85rem;">${item.nama}</div>
                            <span class="badge bg-light text-dark border p-1" style="font-size: 0.65rem;">${item.tipe}</span>
                        </div>
                        <button type="button" class="btn btn-sm text-danger p-0" onclick="hapusItem(${index})"><i class="bi bi-x-circle-fill"></i></button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-primary fw-bold small">${formatRupiahJS(item.harga)}</div>
                        <div class="input-group input-group-sm" style="width: 90px;">
                            <span class="input-group-text p-1 text-muted" style="font-size: 0.7rem;">Qty</span>
                            <input type="number" class="form-control text-center p-1" value="${item.qty}" min="1" onchange="ubahQty(${index}, this.value)">
                        </div>
                    </div>
                    <div>
                        <input type="text" class="form-control form-control-sm bg-light" placeholder="Tulisan / Catatan (opsional)" value="${item.catatan}" onchange="ubahCatatan(${index}, this.value)" style="font-size: 0.75rem;">
                    </div>
                </div>
            `;
            cartContainer.insertAdjacentHTML('beforeend', itemHtml);

            hiddenContainer.innerHTML += `<input type="hidden" name="cart[${index}][id_katalog]" value="${item.id_katalog}">`;
            hiddenContainer.innerHTML += `<input type="hidden" name="cart[${index}][tipe]" value="${item.tipe}">`;
            hiddenContainer.innerHTML += `<input type="hidden" name="cart[${index}][qty]" value="${item.qty}">`;
            hiddenContainer.innerHTML += `<input type="hidden" name="cart[${index}][harga]" value="${item.harga}">`;
            hiddenContainer.innerHTML += `<input type="hidden" name="cart[${index}][catatan]" value="${item.catatan}">`;
        });
    }

    document.getElementById('cartCount').innerText = totalItems + " Item";
    document.getElementById('txtSubtotal').innerText = formatRupiahJS(subtotal);
    kalkulasiPembayaran(subtotal);
}

const diskonInput = document.getElementById('diskon');
const statusBayar = document.getElementById('statusPembayaran');
const bayarInput = document.getElementById('jumlahDibayar');
const areaNominal = document.getElementById('areaNominalBayar');
const inputTotal = document.getElementById('inputTotal');

function kalkulasiPembayaran(subtotal = null) {
    if(subtotal === null) {
        let subText = document.getElementById('txtSubtotal').innerText.replace(/[^0-9]/g, '');
        subtotal = parseInt(subText) || 0;
    }

    let diskon = parseInt(diskonInput.value) || 0;
    let grandTotal = subtotal - diskon;
    if(grandTotal < 0) grandTotal = 0;

    let stat = statusBayar.value;
    if(stat === 'Belum Bayar') {
        areaNominal.style.display = 'none';
        bayarInput.value = 0;
    } else if(stat === 'Lunas') {
        areaNominal.style.display = 'none';
        bayarInput.value = grandTotal;
    } else if(stat === 'DP') {
        areaNominal.style.display = 'block';
        if(parseInt(bayarInput.value) > grandTotal) { bayarInput.value = grandTotal; }
    }

    document.getElementById('txtTotal').innerText = formatRupiahJS(grandTotal);
    inputTotal.value = grandTotal;
}

document.querySelectorAll('.kalkulasi').forEach(el => el.addEventListener('input', () => kalkulasiPembayaran()));
statusBayar.addEventListener('change', () => kalkulasiPembayaran());

document.querySelectorAll('.category-filter').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.category-filter').forEach(b => b.classList.remove('active', 'text-white'));
        this.classList.add('active');

        let filterValue = this.getAttribute('data-filter');
        document.querySelectorAll('.product-item').forEach(item => {
            if(filterValue === 'Semua' || item.getAttribute('data-category') === filterValue) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

document.getElementById('pelLama').addEventListener('change', function() {
    document.getElementById('areaPelangganLama').style.display = 'block';
    document.getElementById('areaPelangganBaru').style.display = 'none';
});
document.getElementById('pelBaru').addEventListener('change', function() {
    document.getElementById('areaPelangganLama').style.display = 'none';
    document.getElementById('areaPelangganBaru').style.display = 'flex';
});

// LOGIKA VALIDASI SEBELUM SUBMIT
document.getElementById('btnSubmitForm').addEventListener('click', function() {
    let errors = [];

    // Cek Pelanggan
    if(document.getElementById('pelLama').checked) {
        if(!document.getElementById('id_pelanggan_lama').value) errors.push("Pilihan <b>Pelanggan Lama</b> belum dipilih.");
    } else {
        if(!document.getElementById('nama_pelanggan').value) errors.push("<b>Nama Pelanggan</b> baru masih kosong.");
        if(!document.getElementById('no_wa').value) errors.push("<b>Nomor WhatsApp</b> pelanggan masih kosong.");
        if(!document.getElementById('alamat').value) errors.push("<b>Alamat Pelanggan</b> masih kosong.");
    }

    // Cek Deadline
    if(!document.getElementById('inputDeadline').value) errors.push("<b>Tanggal Deadline</b> belum ditentukan.");

    // Cek Keranjang
    if(cart.length === 0) errors.push("<b>Keranjang pesanan</b> masih kosong. Klik produk di sebelah kiri.");

    // Tampilkan Error atau Lanjut Submit
    let errorBox = document.getElementById('errorNotif');
    let errorText = document.getElementById('errorText');

    if(errors.length > 0) {
        errorText.innerHTML = "<b>Oops, pesanan belum bisa diproses Kak:</b><br>" + errors.map(e => "- " + e).join("<br>");
        errorBox.classList.remove('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        errorBox.classList.add('d-none');
        document.getElementById('formKasir').submit();
    }
});

</script>