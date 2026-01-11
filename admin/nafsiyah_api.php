<?php
session_start();
require_once __DIR__ . '/../koneksi.php'; // Menggunakan nama file koneksi Anda

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$response = ['status' => 'error', 'message' => 'Aksi tidak valid.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- AKSI TAMBAH ---
    if ($_POST['action'] === 'tambah') {
        $activity_name = $_POST['activity_name'];
        $sub_komponen = !empty($_POST['sub_komponen']) ? $_POST['sub_komponen'] : null;
        $urutan = $_POST['urutan'];

        if (empty($activity_name) || !is_numeric($urutan)) {
            $response['message'] = 'Nama Aktivitas dan Urutan wajib diisi dengan benar.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO nafsiyah_items (activity_name, sub_komponen, urutan) VALUES (?, ?, ?)");
                $stmt->execute([$activity_name, $sub_komponen, $urutan]);
                $response = ['status' => 'success', 'message' => 'Item baru berhasil ditambahkan.'];
            } catch (PDOException $e) {
                $response['message'] = "Database error: " . $e->getMessage();
            }
        }
    }

    // --- AKSI EDIT ---
    elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $activity_name = $_POST['activity_name'];
        $sub_komponen = !empty($_POST['sub_komponen']) ? $_POST['sub_komponen'] : null;
        $urutan = $_POST['urutan'];

        if (empty($activity_name) || !is_numeric($urutan) || empty($id)) {
            $response['message'] = 'Semua field wajib diisi dengan benar.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE nafsiyah_items SET activity_name = ?, sub_komponen = ?, urutan = ? WHERE id = ?");
                $stmt->execute([$activity_name, $sub_komponen, $urutan, $id]);
                $response = ['status' => 'success', 'message' => 'Item berhasil diperbarui.'];
            } catch (PDOException $e) {
                $response['message'] = "Database error: " . $e->getMessage();
            }
        }
    }

    // --- AKSI HAPUS ---
    elseif ($_POST['action'] === 'hapus') {
        $id = $_POST['id'];
        if (empty($id)) {
            $response['message'] = 'ID Item tidak valid.';
        } else {
            try {
                // Hati-hati: Menghapus item ini juga akan menghapus semua log terkait (karena ON DELETE CASCADE)
                $stmt = $pdo->prepare("DELETE FROM nafsiyah_items WHERE id = ?");
                $stmt->execute([$id]);
                $response = ['status' => 'success', 'message' => 'Item berhasil dihapus.'];
            } catch (PDOException $e) {
                $response['message'] = 'Gagal menghapus item: ' . $e->getMessage();
            }
        }
    }
}

echo json_encode($response);
?>