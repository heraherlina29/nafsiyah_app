<?php
session_start();
require_once 'koneksi.php';

/**
 * CATATAN PENTING:
 * 1. Pastikan file koneksi.php mendefinisikan variabel $pdo.
 * 2. Pastikan password di database dibuat dengan password_hash('passwordanda', PASSWORD_DEFAULT).
 */

// Konfigurasi Fonnte
$token_fonte = "eSJDYxaMoxjNvy8vTuDy";

$error_message = '';
$success_message = '';
// Step handling: login, forgot, verify_otp
$step = isset($_GET['step']) ? $_GET['step'] : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- 1. PROSES LOGIN UTAMA (USERNAME & PASSWORD) ---
    if (isset($_POST['login_username'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $error_message = "Username dan Password wajib diisi!";
        } else {
            // Query gabungan admin dan user
            $stmt = $pdo->prepare("
                SELECT id, username, password, 'admin' as role FROM admins WHERE username = ?
                UNION 
                SELECT id, username, password, 'user' as role FROM users WHERE username = ?
            ");
            $stmt->execute([$username, $username]);
            $acc = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifikasi password dengan hash
            if ($acc && password_verify($password, $acc['password'])) {
                $_SESSION['user_id'] = $acc['id'];
                $_SESSION['user_role'] = $acc['role'];

                // Redirect sesuai role
                if ($acc['role'] === 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: user/index.php');
                }
                exit();
            } else {
                $error_message = "Username atau Password salah!";
                $step = 'login';
            }
        }
    }

    // --- 2. TAHAP LUPA PASSWORD: MINTA OTP WA ---
    if (isset($_POST['send_otp'])) {
        $no_wa = preg_replace('/[^0-9]/', '', $_POST['no_wa']); // Bersihkan karakter non-angka

        $stmt = $pdo->prepare("
            SELECT id, no_wa, 'admin' as role FROM admins WHERE no_wa = ?
            UNION 
            SELECT id, no_wa, 'user' as role FROM users WHERE no_wa = ?
        ");
        $stmt->execute([$no_wa, $no_wa]);
        $acc = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($acc) {
            $otp = rand(100000, 999999);
            $_SESSION['temp_otp'] = $otp;
            $_SESSION['temp_no_wa'] = $no_wa;
            $_SESSION['temp_role'] = $acc['role'];

            // Kirim via Fonnte
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.fonnte.com/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'target' => $no_wa,
                    'message' => "Kode OTP Login Nafsiyah Anda: *$otp*. JANGAN BERIKAN KODE INI KEPADA SIAPAPUN."
                ),
                CURLOPT_HTTPHEADER => array("Authorization: $token_fonte"),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            header("Location: login.php?step=verify_otp");
            exit();
        } else {
            $error_message = "Nomor WhatsApp tidak terdaftar!";
            $step = 'forgot';
        }
    }

    // --- 3. VERIFIKASI OTP ---
    if (isset($_POST['verify_otp_action'])) {
        $user_otp = $_POST['otp'];

        if (isset($_SESSION['temp_otp']) && $user_otp == $_SESSION['temp_otp']) {
            $target = $_SESSION['temp_no_wa'];
            $role = $_SESSION['temp_role'];
            $table = ($role === 'admin') ? 'admins' : 'users';

            $stmt = $pdo->prepare("SELECT id FROM $table WHERE no_wa = ?");
            $stmt->execute([$target]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $role;

                unset($_SESSION['temp_otp']);
                unset($_SESSION['temp_no_wa']);
                unset($_SESSION['temp_role']);

                header('Location: ' . ($role === 'admin' ? 'admin/index.php' : 'user/index.php'));
                exit();
            }
        } else {
            $error_message = "Kode OTP salah atau sudah kadaluwarsa!";
            $step = 'verify_otp';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Nafsiyah App</title>

    <!-- Tailwind CSS & Font Awesome -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Config Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#F5F3FF',
                            100: '#EDE9FE',
                            500: '#8B5CF6', // Ungu Utama
                            600: '#7C3AED',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<!-- Body dibuat h-screen dan overflow-hidden agar statis tidak bisa discroll -->

<body
    class="bg-gradient-to-br from-primary-50 to-white h-screen w-screen overflow-hidden flex items-center justify-center p-4">

    <!-- Card dibuat lebih compact (max-w-sm) dan padding lebih kecil (p-6) -->
    <div
        class="w-full max-w-sm bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 overflow-hidden relative">

        <!-- Decoration Top -->
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-primary-500 to-purple-400"></div>

        <div class="p-6">
            <!-- Logo Section -->
            <div class="text-center mb-6">
                <!-- Logo Image -->
                <img src="assets/img/logo.png" alt="Logo Nafsiyah" width="100" height="100"
                    class="w-16 h-16 mx-auto mb-2 object-contain hover:scale-105 transition-transform duration-300">

                <h1 class="text-xl font-extrabold text-slate-800 tracking-tight">Nafsiyah App</h1>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Sistem Management Amalan Harian</p>
            </div>

            <!-- Alert Messages -->
            <?php if ($error_message): ?>
                <div
                    class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 text-xs font-semibold flex items-start gap-2 animate-pulse">
                    <i class="fas fa-exclamation-circle mt-0.5"></i>
                    <div><?= htmlspecialchars($error_message) ?></div>
                </div>
            <?php endif; ?>

            <!-- LOGIN FORM -->
            <?php if ($step === 'login'): ?>
                <form action="login.php?step=login" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Username</label>
                        <div class="relative group">
                            <i
                                class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs group-focus-within:text-primary-500 transition-colors"></i>
                            <input type="text" name="username" required
                                class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all placeholder:text-slate-400"
                                placeholder="Masukkan username">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Password</label>
                        <div class="relative group">
                            <i
                                class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs group-focus-within:text-primary-500 transition-colors"></i>
                            <input type="password" name="password" id="password" required
                                class="w-full pl-9 pr-9 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all placeholder:text-slate-400"
                                placeholder="••••••••">
                            <button type="button" onclick="togglePassword()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary-500 transition-colors text-xs">
                                <i class="far fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="login_username"
                        class="w-full py-2.5 bg-primary-600 text-white text-sm font-bold rounded-lg shadow-md shadow-primary-500/30 hover:bg-primary-500 hover:shadow-primary-500/40 hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2">
                        <span>Masuk Sekarang</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="mt-6 text-center border-t border-slate-100 pt-4">
                    <p class="text-[10px] text-slate-500 mb-2">Lupa password akun anda?</p>
                    <a href="login.php?step=forgot"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-600 text-xs font-bold rounded-lg hover:bg-slate-50 hover:border-slate-300 transition-all">
                        <i class="fab fa-whatsapp text-green-500"></i>
                        <span>Login via WhatsApp</span>
                    </a>
                </div>

                <!-- FORGOT PASSWORD FORM -->
            <?php elseif ($step === 'forgot'): ?>
                <div class="text-center mb-4">
                    <h2 class="text-base font-bold text-slate-800">Login Alternatif</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Kami akan mengirimkan OTP ke WhatsApp Anda</p>
                </div>

                <form action="login.php?step=forgot" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nomor
                            WhatsApp</label>
                        <div class="relative group">
                            <i
                                class="fab fa-whatsapp absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-green-500 transition-colors"></i>
                            <input type="text" name="no_wa" required
                                class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all placeholder:text-slate-400"
                                placeholder="Contoh: 08123456789">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 ml-1">* Pastikan nomor terdaftar</p>
                    </div>

                    <button type="submit" name="send_otp"
                        class="w-full py-2.5 bg-green-500 text-white text-sm font-bold rounded-lg shadow-md shadow-green-500/30 hover:bg-green-600 hover:shadow-green-500/40 hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2">
                        <span>Kirim Kode OTP</span>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <a href="login.php?step=login"
                        class="text-xs font-bold text-slate-500 hover:text-primary-600 transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Login Utama
                    </a>
                </div>

                <!-- VERIFY OTP FORM -->
            <?php elseif ($step === 'verify_otp'): ?>
                <div class="text-center mb-4">
                    <h2 class="text-base font-bold text-slate-800">Verifikasi OTP</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Masukkan 6 digit kode yang telah dikirim</p>
                </div>

                <form action="login.php?step=verify_otp" method="POST" class="space-y-4">
                    <div>
                        <input type="text" name="otp" maxlength="6" required
                            class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-xl text-xl font-bold text-center tracking-[0.5em] text-slate-800 focus:outline-none focus:border-primary-500 focus:bg-white transition-all placeholder:tracking-normal placeholder:text-sm placeholder:font-normal placeholder:text-slate-400"
                            placeholder="000000" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>

                    <button type="submit" name="verify_otp_action"
                        class="w-full py-2.5 bg-primary-600 text-white text-sm font-bold rounded-lg shadow-md shadow-primary-500/30 hover:bg-primary-500 hover:shadow-primary-500/40 hover:-translate-y-0.5 transition-all duration-200">
                        Verifikasi & Masuk
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <p class="text-[10px] text-slate-500 mb-2">Tidak menerima kode?</p>
                    <a href="login.php?step=forgot"
                        class="text-xs font-bold text-primary-600 hover:text-primary-700 transition-colors">
                        Kirim Ulang OTP
                    </a>
                </div>
            <?php endif; ?>

        </div>

        <!-- Compact Footer -->
        <div class="bg-slate-50 p-3 text-center border-t border-slate-100">
            <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">
                &copy; <?= date('Y') ?> Nafsiyah App
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'far fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'far fa-eye';
            }
        }
    </script>
</body>

</html>