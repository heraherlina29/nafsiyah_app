<?php
require_once __DIR__ . '/../koneksi.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_SESSION['user_id'];
    $pw_lama = $_POST['password_lama'];
    $pw_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_password'];

    // 1. Ambil password lama dari database
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$id_user]);
    $user = $stmt->fetch();

    // 2. Validasi
    if (!password_verify($pw_lama, $user['password'])) {
        header("Location: profil.php?status=error&msg=Password lama salah!");
        exit();
    }
    if ($pw_baru !== $konfirmasi) {
        header("Location: profil.php?status=error&msg=Konfirmasi password tidak cocok!");
        exit();
    }

    // 3. Update Password
    $pw_hash = password_hash($pw_baru, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->execute([$pw_hash, $id_user]);

    header("Location: profil.php?status=success&msg=Password berhasil diperbarui!");
    exit();
}