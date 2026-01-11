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

    // --- AKSI TAMBAH (DENGAN TAMBAHAN EMAIL) ---
    if ($_POST['action'] === 'tambah') {
        $nama_lengkap = $_POST['nama_lengkap'];
        $email = $_POST['email']; // <-- BARU
        $username = $_POST['username'];
        $password = $_POST['password'];
        $status = $_POST['status'];

        if (empty($nama_lengkap) || empty($email) || empty($username) || empty($password) || empty($status)) {
            $response['message'] = 'Semua field wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // <-- VALIDASI EMAIL
            $response['message'] = 'Format email tidak valid.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                // UPDATE QUERY INSERT
                $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, email, username, password, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nama_lengkap, $email, $username, $hashed_password, $status]);
                $response = ['status' => 'success', 'message' => "User '{$nama_lengkap}' berhasil ditambahkan."];
            } catch (PDOException $e) { /* ... (penanganan error tetap sama) ... */
            }
        }
    }

    // --- AKSI EDIT (DENGAN TAMBAHAN EMAIL) ---
    elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $email = $_POST['email']; // <-- BARU
        $username = $_POST['username'];
        $password = $_POST['password'];
        $status = $_POST['status'];

        if (empty($nama_lengkap) || empty($email) || empty($username) || empty($status) || empty($id)) {
            $response['message'] = 'Data tidak lengkap untuk proses edit.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // <-- VALIDASI EMAIL
            $response['message'] = 'Format email tidak valid.';
        } else {
            try {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    // UPDATE QUERY DENGAN PASSWORD
                    $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, email = ?, username = ?, password = ?, status = ? WHERE id = ?");
                    $stmt->execute([$nama_lengkap, $email, $username, $hashed_password, $status, $id]);
                } else {
                    // UPDATE QUERY TANPA PASSWORD
                    $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, email = ?, username = ?, status = ? WHERE id = ?");
                    $stmt->execute([$nama_lengkap, $email, $username, $status, $id]);
                }
                $response = ['status' => 'success', 'message' => "Data user '{$nama_lengkap}' berhasil diperbarui."];
            } catch (PDOException $e) { /* ... (penanganan error tetap sama) ... */
            }
        }
    }

    // --- AKSI HAPUS (TIDAK BERUBAH) ---
    elseif ($_POST['action'] === 'hapus') {
        $id = $_POST['id'];
        if (empty($id)) {
            $response['message'] = 'ID User tidak valid.';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $response = ['status' => 'success', 'message' => 'User berhasil dihapus.'];
            } catch (PDOException $e) { /* ... (penanganan error tetap sama) ... */
            }
        }
    }
}

echo json_encode($response);
?>