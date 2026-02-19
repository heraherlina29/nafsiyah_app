<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

header('Content-Type: application/json');

// Proteksi: Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Silakan login kembali.']);
    exit();
}

$response = ['status' => 'error', 'message' => 'Aksi tidak valid.'];
$id_user = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Ambil data user saat ini
        $stmt = $pdo->prepare("SELECT password, profile_pic FROM users WHERE id = ?");
        $stmt->execute([$id_user]);
        $currentUser = $stmt->fetch();

        if (!$currentUser) {
            echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan.']);
            exit();
        }

        $update_fields = [];
        $params = [];

        // --- 1. LOGIKA UPLOAD FOTO PROFIL ---
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
            $target_dir = "../uploads/profile/";

            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png'];

            if (in_array($file_ext, $allowed_ext)) {
                $new_filename = "profile_" . $id_user . "_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                    // Hapus foto lama
                    if (!empty($currentUser['profile_pic']) && file_exists($target_dir . $currentUser['profile_pic'])) {
                        unlink($target_dir . $currentUser['profile_pic']);
                    }
                    $update_fields[] = "profile_pic = ?";
                    $params[] = $new_filename;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Format file harus JPG atau PNG.']);
                exit();
            }
        }

        // --- 2. LOGIKA UPDATE NAMA (Jika dikirim) ---
        if (isset($_POST['nama_lengkap']) && !empty($_POST['nama_lengkap'])) {
            $update_fields[] = "nama_lengkap = ?";
            $params[] = $_POST['nama_lengkap'];
            $_SESSION['nama_lengkap'] = $_POST['nama_lengkap'];
        }

        // --- 3. LOGIKA UPDATE PASSWORD (Jika dikirim) ---
        if (!empty($_POST['password_baru'])) {
            if (empty($_POST['password_lama'])) {
                echo json_encode(['status' => 'error', 'message' => 'Password lama wajib diisi.']);
                exit();
            }
            if (password_verify($_POST['password_lama'], $currentUser['password'])) {
                $update_fields[] = "password = ?";
                $params[] = password_hash($_POST['password_baru'], PASSWORD_DEFAULT);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Password lama salah.']);
                exit();
            }
        }

        // Jalankan Update jika ada field yang diubah
        if (!empty($update_fields)) {
            $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
            $params[] = $id_user;
            $stmtUpdate = $pdo->prepare($sql);
            $stmtUpdate->execute($params);

            $response = ['status' => 'success', 'message' => 'Profil berhasil diperbarui!'];
        } else {
            $response = ['status' => 'error', 'message' => 'Tidak ada data yang diubah.'];
        }

    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    }
}

echo json_encode($response);