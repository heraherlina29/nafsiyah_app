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

    // Ambil input data
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $no_wa = $_POST['no_wa'] ?? ''; // <--- FIELD BARU
    $status = $_POST['status'] ?? '';

    // --- AKSI TAMBAH ---
    if ($_POST['action'] === 'tambah') {
        $password = $_POST['password'] ?? '';

        if (empty($nama_lengkap) || empty($email) || empty($username) || empty($password) || empty($no_wa)) {
            $response['message'] = 'Semua field (termasuk No. WA) wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Format email tidak valid.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, email, username, no_wa, password, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nama_lengkap, $email, $username, $no_wa, $hashed_password, $status]);
                $response = ['status' => 'success', 'message' => "User '{$nama_lengkap}' berhasil ditambahkan."];
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $response['message'] = "Username atau Email sudah terdaftar.";
                } else {
                    $response['message'] = "Database error: " . $e->getMessage();
                }
            }
        }
    }

    // --- AKSI EDIT ---
    elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($id) || empty($nama_lengkap) || empty($email) || empty($username) || empty($no_wa)) {
            $response['message'] = 'Semua field wajib diisi.';
        } else {
            try {
                if (!empty($password)) {
                    // Update dengan ganti password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, email = ?, username = ?, no_wa = ?, password = ?, status = ? WHERE id = ?");
                    $stmt->execute([$nama_lengkap, $email, $username, $no_wa, $hashed_password, $status, $id]);
                } else {
                    // Update tanpa ganti password
                    $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, email = ?, username = ?, no_wa = ?, status = ? WHERE id = ?");
                    $stmt->execute([$nama_lengkap, $email, $username, $no_wa, $status, $id]);
                }
                $response = ['status' => 'success', 'message' => "Data user '{$nama_lengkap}' berhasil diperbarui."];
            } catch (PDOException $e) {
                $response['message'] = "Database error: " . $e->getMessage();
            }
        }
    }

    // --- AKSI HAPUS ---
    elseif ($_POST['action'] === 'hapus') {
        $id = $_POST['id'] ?? '';
        if (!empty($id)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $response = ['status' => 'success', 'message' => 'User berhasil dihapus.'];
            } catch (PDOException $e) {
                $response['message'] = 'Gagal menghapus user.';
            }
        }
    }
}

echo json_encode($response);