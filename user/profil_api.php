<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$response = ['status' => 'error', 'message' => 'Aksi tidak valid.'];
$id_user = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'];
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];

    if (empty($nama_lengkap)) {
        $response['message'] = 'Nama Lengkap tidak boleh kosong.';
    } else {
        try {
            // Selalu update nama lengkap
            $sql = "UPDATE users SET nama_lengkap = ? WHERE id = ?";
            $params = [$nama_lengkap, $id_user];

            // Jika user ingin mengubah password
            if (!empty($password_lama) && !empty($password_baru)) {
                // 1. Ambil password saat ini dari DB
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$id_user]);
                $user = $stmt->fetch();

                // 2. Verifikasi password lama
                if ($user && password_verify($password_lama, $user['password'])) {
                    // 3. Jika cocok, hash password baru dan update
                    $hashed_password_baru = password_hash($password_baru, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET nama_lengkap = ?, password = ? WHERE id = ?";
                    $params = [$nama_lengkap, $hashed_password_baru, $id_user];
                } else {
                    $response['message'] = 'Password lama yang Anda masukkan salah.';
                    echo json_encode($response);
                    exit();
                }
            } elseif (!empty($password_baru) && empty($password_lama)) {
                $response['message'] = 'Untuk mengubah password, Anda harus memasukkan password lama.';
                echo json_encode($response);
                exit();
            }

            // Eksekusi query update
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Perbarui session dengan nama baru
            $_SESSION['nama_lengkap'] = $nama_lengkap;

            $response = ['status' => 'success', 'message' => 'Profil berhasil diperbarui.'];

        } catch (PDOException $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    }
}

echo json_encode($response);
?>