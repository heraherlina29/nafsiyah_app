<?php
session_start();
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
        $no_wa = $_POST['no_wa']; // <-- Ambil data WA
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $response['message'] = 'Username dan password wajib diisi.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                // Tambahkan no_wa ke query INSERT
                $stmt = $pdo->prepare("INSERT INTO admins (username, no_wa, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $no_wa, $hashed_password]);
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
        $no_wa = $_POST['no_wa']; // <-- Ambil data WA
        $password = $_POST['password'];

        if (empty($username) || empty($id)) {
            $response['message'] = 'Username tidak boleh kosong.';
        } else {
            try {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    // Update dengan no_wa dan password
                    $stmt = $pdo->prepare("UPDATE admins SET username = ?, no_wa = ?, password = ? WHERE id = ?");
                    $stmt->execute([$username, $no_wa, $hashed_password, $id]);
                } else {
                    // Update dengan no_wa saja
                    $stmt = $pdo->prepare("UPDATE admins SET username = ?, no_wa = ? WHERE id = ?");
                    $stmt->execute([$username, $no_wa, $id]);
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