<?php
session_start();

// Cek apakah pengguna sudah login atau belum
if (isset($_SESSION['user_id'])) {
    // Jika sudah, arahkan berdasarkan peran (role)
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: user/index.php');
    }
} else {
    // Jika belum login, arahkan ke halaman login
    header('Location: login.php');
}
exit();
?>