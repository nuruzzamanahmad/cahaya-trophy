<div style="margin-top: 40px;"></div>

    <footer class="bg-white border-top p-3 d-flex flex-column flex-sm-row justify-content-between align-items-center text-muted mt-auto d-print-none" style="font-size: 0.8rem; z-index: 10;">
        <span class="text-center text-sm-start mb-2 mb-sm-0">&copy; 2024 Cahaya Trophy Management System. Semua hak dilindungi.</span>
        <div class="d-flex gap-3">
            <a href="#" class="text-muted text-decoration-none">Privasi</a>
            <a href="#" class="text-muted text-decoration-none">Ketentuan</a>
        </div>
    </footer>
    
</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Logika Hamburger Menu
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (hamburgerBtn && sidebar && overlay) {
        hamburgerBtn.addEventListener('click', function() {
            sidebar.classList.add('active');
            overlay.classList.add('active');
        });
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }

    // 2. Mesin Global Popup Hapus (Smart Text Detection)
    const tombolHapus = document.querySelectorAll('.btn-hapus');
    
    tombolHapus.forEach(tombol => {
        tombol.addEventListener('click', function(e) {
            e.preventDefault(); 
            const urlHapus = this.getAttribute('href'); 
            
            // Logika cerdas untuk mendeteksi tipe yang akan dihapus berdasarkan URL URL
            let tipeData = "data";
            if (urlHapus.includes('hapus_user')) tipeData = "akun karyawan";
            else if (window.location.href.includes('customers')) tipeData = "pelanggan";
            else if (window.location.href.includes('orders')) tipeData = "pesanan";
            else if (window.location.href.includes('catalog')) tipeData = "produk";
            else if (window.location.href.includes('inventory')) tipeData = "item stok";

            // Membersihkan kalimat bawaan agar hanya tersisa nama spesifiknya saja
            let itemName = this.getAttribute('data-pesan') || "terpilih";
            itemName = itemName.replace(/Pesanan /gi, '')
                               .replace(/ beserta detailnya.*/gi, '')
                               .replace(/Item /gi, '')
                               .replace(/ akan dihapus.*/gi, '')
                               .replace(/Produk /gi, '')
                               .replace(/Semua data milik pelanggan /gi, '')
                               .replace(/Akun /gi, '');
            
            Swal.fire({
                html: `
                    <div class="mt-2 text-center">
                        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 65px; height: 65px; background-color: #ffe6e6; border-radius: 50%;">
                            <i class="bi bi-trash" style="font-size: 2rem; color: #ff3333;"></i>
                        </div>
                        
                        <h4 class="fw-bold text-dark mb-2" style="font-size: 1.25rem;">Konfirmasi Hapus</h4>
                        
                        <p class="text-muted mb-0" style="font-size: 0.95rem;">
                            Anda yakin ingin menghapus ${tipeData} <strong class="text-dark">${itemName}</strong>?
                        </p>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal',
                buttonsStyling: false, 
                customClass: {
                    popup: 'rounded-4 shadow-sm border-0 p-2',
                    confirmButton: 'btn btn-danger rounded-pill px-4 py-2 fw-medium ms-2',
                    cancelButton: 'btn btn-light border rounded-pill px-4 py-2 fw-medium text-dark'
                },
                reverseButtons: true, 
                width: '360px'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = urlHapus;
                }
            });
        });
    });
});
</script>

</body>
</html>