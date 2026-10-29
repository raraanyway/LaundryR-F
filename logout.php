<?php
session_start();
session_destroy(); // Menghapus semua sesi login
header("location: index.php"); // Mengarahkan kembali ke halaman login
?>