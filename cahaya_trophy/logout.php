<?php
session_start();
session_unset();    // Bersihkan semua data sesi
session_destroy();  // Hancurkan sesi

// Tendang kembali ke halaman login
header("Location: login.php");
exit();
?>