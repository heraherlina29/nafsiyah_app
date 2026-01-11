<?php
session_start();
require_once 'koneksi.php';

/**
 * CATATAN PENTING:
 * 1. Pastikan file koneksi.php mendefinisikan variabel $pdo.
 * 2. Pastikan password di database dibuat dengan password_hash('passwordanda', PASSWORD_DEFAULT).
 */

// Konfigurasi Fonnte
$token_fonte = "gszYkUgf5BoCK9barMnW"; 

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
    <title>Login - Nafsiyah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-50: #f0f9ff;
            --primary-100: #e0f2fe;
            --primary-200: #bae6fd;
            --primary-300: #7dd3fc;
            --primary-400: #38bdf8;
            --primary-500: #0ea5e9;
            --primary-600: #0284c7;
            --primary-700: #0369a1;
            --green-50: #f0fdf4;
            --green-100: #dcfce7;
            --green-400: #4ade80;
            --green-500: #22c55e;
            --green-600: #16a34a;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-50) 0%, var(--green-50) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 380px;
        }
        
        .login-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(14, 165, 233, 0.12);
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-400), var(--green-500));
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .logo-circle {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-400), var(--green-400));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 6px 15px rgba(14, 165, 233, 0.2);
            transform: rotate(3deg);
        }
        
        .logo-circle i {
            color: white;
            font-size: 1.5rem;
        }
        
        .app-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }
        
        .app-subtitle {
            font-size: 0.8125rem;
            color: var(--gray-500);
            font-weight: 400;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.375rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 10px;
            font-size: 0.875rem;
            background: var(--gray-50);
            transition: all 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-400);
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.1);
            background: white;
        }
        
        .form-input::placeholder {
            color: var(--gray-400);
            font-size: 0.875rem;
        }
        
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.75rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-500), var(--green-500));
            color: white;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.15);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.2);
            background: linear-gradient(135deg, var(--primary-600), var(--green-600));
        }
        
        .btn-whatsapp {
            background: linear-gradient(135deg, #25D366, #128C7E);
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.15);
        }
        
        .btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.2);
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            font-size: 0.8125rem;
            animation: fadeIn 0.3s ease-out;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        
        .step-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .step-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.375rem;
        }
        
        .step-subtitle {
            font-size: 0.8125rem;
            color: var(--gray-500);
        }
        
        .otp-input {
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            letter-spacing: 0.5rem;
            padding-right: 0.5rem;
            height: 3rem;
        }
        
        .link-text {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.8125rem;
        }
        
        .link-text a {
            color: var(--primary-600);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .link-text a:hover {
            color: var(--primary-700);
            text-decoration: underline;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .login-card {
                padding: 1.5rem;
                border-radius: 14px;
            }
            
            .login-container {
                max-width: 340px;
            }
            
            .logo-circle {
                width: 50px;
                height: 50px;
            }
            
            .logo-circle i {
                font-size: 1.25rem;
            }
            
            .app-title {
                font-size: 1.25rem;
            }
            
            .otp-input {
                font-size: 1.25rem;
                letter-spacing: 0.375rem;
                height: 2.5rem;
            }
        }
        
        @media (max-height: 700px) {
            body {
                padding: 0.5rem;
                align-items: flex-start;
                min-height: auto;
            }
            
            .login-container {
                margin: 1rem 0;
            }
        }
    </style>
</head>
<body>
    <div class="login-container animate-fade-in">
        <div class="login-card">
            <!-- Logo Section -->
            <div class="logo-section">
                <div class="logo-circle">
                    <i class="fas fa-praying-hands"></i>
                </div>
                <h1 class="app-title">Nafsiyah App</h1>
                <p class="app-subtitle">Sistem Management Amalan Harian</p>
            </div>

            <!-- Error/Success Messages -->
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle mt-0.5"></i>
                    <div><?= htmlspecialchars($error_message) ?></div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <?php if ($step === 'login'): ?>
                <form action="login.php?step=login" method="POST">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-input" placeholder="Masukkan username Anda" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                    </div>
                    
                    <button type="submit" name="login_username" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        Masuk
                    </button>
                    
                    <div class="link-text">
                        <a href="login.php?step=forgot">Lupa password? Login via WhatsApp</a>
                    </div>
                </form>

            <!-- Forgot Password Form -->
            <?php elseif ($step === 'forgot'): ?>
                <div class="step-header">
                    <h2 class="step-title">Login via WhatsApp</h2>
                    <p class="step-subtitle">OTP akan dikirim ke nomor WhatsApp terdaftar</p>
                </div>
                
                <form action="login.php?step=forgot" method="POST">
                    <div class="form-group">
                        <label class="form-label">Nomor WhatsApp</label>
                        <input type="text" name="no_wa" class="form-input" placeholder="Contoh: 628123456789" required>
                        <p class="text-xs text-gray-500 mt-1">Format: tanpa tanda + atau spasi</p>
                    </div>
                    
                    <button type="submit" name="send_otp" class="btn btn-whatsapp">
                        <i class="fab fa-whatsapp"></i>
                        Kirim Kode OTP
                    </button>
                    
                    <div class="link-text">
                        <a href="login.php?step=login">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Login
                        </a>
                    </div>
                </form>

            <!-- OTP Verification Form -->
            <?php elseif ($step === 'verify_otp'): ?>
                <div class="step-header">
                    <h2 class="step-title">Verifikasi OTP</h2>
                    <p class="step-subtitle">Masukkan 6 digit kode yang dikirim ke WhatsApp</p>
                </div>
                
                <form action="login.php?step=verify_otp" method="POST">
                    <div class="form-group">
                        <input type="text" name="otp" maxlength="6" class="form-input otp-input" 
                               placeholder="000000" required 
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                               pattern="\d{6}">
                        <p class="text-xs text-gray-500 mt-1 text-center">Kode berlaku selama 10 menit</p>
                    </div>
                    
                    <button type="submit" name="verify_otp_action" class="btn btn-primary">
                        <i class="fas fa-check-circle"></i>
                        Verifikasi & Login
                    </button>
                    
                    <div class="link-text">
                        <a href="login.php?step=forgot">
                            <i class="fas fa-redo mr-1"></i> Kirim ulang kode
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto focus pada input pertama
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('.form-input');
            if (firstInput) firstInput.focus();
            
            // Auto advance untuk OTP input
            const otpInput = document.querySelector('.otp-input');
            if (otpInput) {
                otpInput.addEventListener('input', function(e) {
                    if (this.value.length === 6) {
                        this.form.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>