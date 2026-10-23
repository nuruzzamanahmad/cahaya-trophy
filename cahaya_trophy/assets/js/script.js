document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Logika Toggle Sidebar untuk versi Mobile (HP)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    if(sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('show');
        });
    }

    // 2. Inisialisasi Tooltip Bootstrap (opsional, jika nanti ada ikon yang butuh teks penjelas saat di-hover)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

});