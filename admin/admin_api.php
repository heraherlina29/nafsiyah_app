<?php
session_start();
// Menggunakan nama file koneksi yang sudah Anda ubah
require_once __DIR__ . '/../koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$response = ['status' => 'error', 'message' => 'Aksi tidak valid.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- AKSI TAMBAH ---
    if ($_POST['action'] === 'tambah') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $response['message'] = 'Username dan password wajib diisi.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $hashed_password]);
                $response = ['status' => 'success', 'message' => "Admin '{$username}' berhasil ditambahkan."];
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $response['message'] = "Username '{$username}' sudah ada.";
                } else {
                    $response['message'] = "Database error.";
                }
            }
        }
    }

    // --- AKSI EDIT ---
    elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (empty($username) || empty($id)) {
            $response['message'] = 'Username tidak boleh kosong.';
        } else {
            try {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE admins SET username = ?, password = ? WHERE id = ?");
                    $stmt->execute([$username, $hashed_password, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?");
                    $stmt->execute([$username, $id]);
                }
                $response = ['status' => 'success', 'message' => "Data admin '{$username}' berhasil diperbarui."];
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $response['message'] = "Username '{$username}' sudah ada.";
                } else {
                    $response['message'] = "Database error.";
                }
            }
        }
    }

    // --- AKSI HAPUS ---
    elseif ($_POST['action'] === 'hapus') {
        $id = $_POST['id'];
        if (empty($id)) {
            $response['message'] = 'ID Admin tidak valid.';
        } elseif ($id == $_SESSION['user_id']) {
            // Proteksi agar admin tidak bisa menghapus dirinya sendiri
            $response['message'] = 'Anda tidak bisa menghapus akun Anda sendiri.';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
                $stmt->execute([$id]);
                $response = ['status' => 'success', 'message' => 'Admin berhasil dihapus.'];
            } catch (PDOException $e) {
                $response['message'] = 'Gagal menghapus admin.';
            }
        }
    }
}

echo json_encode($response);
?>