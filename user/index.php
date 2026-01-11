<?php
require_once __DIR__ . '/../koneksi.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }

$id_user = $_SESSION['user_id'];
$tgl = date('Y-m-d');

// Fungsi pembantu untuk menentukan level
function getLevel($poin) {
    if ($poin < 1000) return ['lv' => 1, 'nama' => 'Newbie', 'next' => 1000, 'min' => 0];
    if ($poin < 3000) return ['lv' => 2, 'nama' => 'Istiqomah', 'next' => 3000, 'min' => 1000];
    if ($poin < 7000) return ['lv' => 3, 'nama' => 'Muttaqin', 'next' => 7000, 'min' => 3000];
    return ['lv' => 4, 'nama' => 'Murobitun', 'next' => 15000, 'min' => 7000];
}

// Ambil data user untuk header
$stmtUser = $pdo->prepare("SELECT username, total_poin, streak_count, terakhir_lapor FROM users WHERE id = ?");
$stmtUser->execute([$id_user]);
$userData = $stmtUser->fetch();
$level = getLevel($userData['total_poin']);
$progress = (($userData['total_poin'] - $level['min']) / ($level['next'] - $level['min'])) * 100;

// Cek apakah hari ini sudah lapor
$cek = $pdo->prepare("SELECT COUNT(id) FROM nafsiyah_logs WHERE user_id = ? AND log_date = ?");
$cek->execute([$id_user, $tgl]);
$done = $cek->fetchColumn() > 0;

// --- PROSES SIMPAN LAPORAN ---
if (!$done && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $total_poin_hari_ini = 0;
        $is_haid = isset($_POST['mode_haid']) ? 1 : 0;

        // Ambil data user untuk hitung Streak
        $stmtUser = $pdo->prepare("SELECT total_poin, streak_count, terakhir_lapor FROM users WHERE id = ?");
        $stmtUser->execute([$id_user]);
        $userData = $stmtUser->fetch();

        // Ambil item amalan dari database
        $items = $pdo->query("SELECT * FROM nafsiyah_items ORDER BY urutan ASC")->fetchAll();

        foreach ($items as $item) {
            $id_item = $item['id'];
            $nama_amalan = $item['activity_name'];
            
            // Daftar kata kunci amalan yang kena Udzur Syar'i
            $keywords = ['Tahajjud', 'Dhuha', 'Sholat', 'Rawatib', 'Dzikir', 'Doa Setelah', 'Tilawah', 'Murojaah', 'Hafalan'];
            $is_kena_udzur = false;
            foreach ($keywords as $key) {
                if (stripos($nama_amalan, $key) !== false) { $is_kena_udzur = true; break; }
            }

            // Logika Penentuan Skor
            if ($is_haid && $is_kena_udzur) {
                $catatan = "Udzur Syar'i";
                $skor = 5; // Poin apresiasi udzur
                $st = 'selesai';
            } else {
                if (isset($_POST['item'][$id_item])) {
                    $p = explode('|', $_POST['item'][$id_item]);
                    $catatan = $p[0];
                    $skor = (int)$p[1];
                    $st = (in_array($catatan, ['Tidak','Absen','Tidur','Makan','Tidak Mengerjakan'])) ? 'tidak_selesai' : 'selesai';
                } else {
                    continue; 
                }
            }

            $total_poin_hari_ini += $skor;
            $pdo->prepare("INSERT INTO nafsiyah_logs (user_id, item_id, log_date, status, catatan, poin_didapat) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$id_user, $id_item, $tgl, $st, $catatan, $skor]);
        }

        // --- LOGIKA STREAK ---
        $tgl_kemarin = date('Y-m-d', strtotime("-1 day"));
        $new_streak = 1; // Default jika sebelumnya bolong

        if ($userData['terakhir_lapor'] == $tgl_kemarin) {
            $new_streak = $userData['streak_count'] + 1; // Sambung streak
        } elseif ($userData['terakhir_lapor'] == $tgl) {
            $new_streak = $userData['streak_count']; // Jaga-jaga jika refresh halaman
        }

        // Update total poin, streak, dan tanggal terakhir lapor di tabel users
        $pdo->prepare("UPDATE users SET total_poin = total_poin + ?, terakhir_lapor = ?, streak_count = ? WHERE id = ?")
            ->execute([$total_poin_hari_ini, $tgl, $new_streak, $id_user]);

        $pdo->commit(); 
        header('Location: index.php?status=done'); 
        exit();
    } catch (Exception $e) { $pdo->rollBack(); die($e->getMessage()); }
}

include 'templates/header.php';

// Cek preferensi tema dari cookie
$theme = $_COOKIE['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="id" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Nafsiyah</title>
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
            --secondary-50: #fefce8;
            --secondary-100: #fef9c3;
            --secondary-200: #fef08a;
            --secondary-300: #fde047;
            --secondary-400: #facc15;
            --green-50: #f0fdf4;
            --green-100: #dcfce7;
            --green-200: #bbf7d0;
            --green-400: #4ade80;
            --green-500: #22c55e;
            --green-600: #16a34a;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --rose-50: #fff1f2;
            --rose-100: #ffe4e6;
            --rose-200: #fecdd3;
            --rose-300: #fda4af;
            --rose-400: #fb7185;
            --rose-500: #f43f5e;
            --rose-600: #e11d48;
            --rose-700: #be123c;
            --moon-color: #6366f1;
            
            /* Dark theme variables */
            --dark-bg: #0f172a;
            --dark-surface: #1e293b;
            --dark-surface-2: #334155;
            --dark-text: #f1f5f9;
            --dark-text-secondary: #cbd5e1;
        }
        
        [data-theme="dark"] {
            --primary-50: #0c4a6e;
            --primary-100: #075985;
            --primary-200: #0369a1;
            --primary-300: #0284c7;
            --primary-400: #0ea5e9;
            --primary-500: #38bdf8;
            --primary-600: #7dd3fc;
            --gray-50: #1e293b;
            --gray-100: #334155;
            --gray-200: #475569;
            --gray-300: #64748b;
            --gray-800: #cbd5e1;
            --gray-900: #f1f5f9;
            --green-50: #064e3b;
            --green-100: #047857;
            --green-200: #059669;
            --green-400: #34d399;
            --green-500: #10b981;
            --secondary-50: #78350f;
            --secondary-100: #92400e;
            --secondary-400: #fbbf24;
            --rose-50: #4c0519;
            --rose-100: #881337;
            --rose-200: #9f1239;
            --rose-300: #be123c;
            --rose-400: #e11d48;
            --rose-500: #f43f5e;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-50) 0%, #f8fafc 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--gray-900);
            transition: background-color 0.3s ease;
        }
        
        [data-theme="dark"] body {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #0f172a 100%);
            color: var(--dark-text);
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Theme Toggle Button - Dipindahkan ke bawah */
        .theme-toggle-bottom {
            margin-top: 24px;
            text-align: center;
        }
        
        .theme-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--primary-100);
            color: var(--primary-700);
            border: 2px solid var(--primary-300);
            border-radius: 16px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        
        .theme-btn:hover {
            background: var(--primary-200);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
        }
        
        [data-theme="dark"] .theme-btn {
            background: var(--dark-surface-2);
            color: var(--dark-text);
            border-color: var(--primary-500);
        }
        
        .theme-btn i {
            font-size: 1.1rem;
        }
        
        /* Header */
        .user-header {
            background: white;
            border-radius: 24px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            background: linear-gradient(135deg, white, #f8fafc);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .user-header {
            background: linear-gradient(135deg, var(--dark-surface), var(--dark-bg));
            border-color: var(--dark-surface-2);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
        }
        
        .user-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-400), var(--green-500));
        }
        
        .user-info h2 {
            color: var(--gray-900);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        [data-theme="dark"] .user-info h2 {
            color: var(--dark-text);
        }
        
        .user-info .level {
            color: var(--primary-600);
            font-size: 0.875rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stats {
            display: flex;
            gap: 20px;
        }
        
        .stat-box {
            text-align: center;
            padding: 8px 12px;
            background: var(--primary-50);
            border-radius: 12px;
            min-width: 80px;
        }
        
        [data-theme="dark"] .stat-box {
            background: var(--dark-surface-2);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-600);
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: var(--gray-800);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        [data-theme="dark"] .stat-label {
            color: var(--dark-text-secondary);
        }
        
        .progress-container {
            width: 100%;
            margin-top: 12px;
        }
        
        .progress-bar {
            height: 8px;
            background: var(--gray-100);
            border-radius: 4px;
            overflow: hidden;
        }
        
        [data-theme="dark"] .progress-bar {
            background: var(--dark-surface-2);
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-400), var(--green-400));
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        .progress-text {
            font-size: 0.75rem;
            color: var(--gray-800);
            margin-top: 4px;
            display: flex;
            justify-content: space-between;
        }
        
        [data-theme="dark"] .progress-text {
            color: var(--dark-text-secondary);
        }
        
        /* Main Content */
        .success-card {
            background: white;
            border-radius: 24px;
            padding: 48px 32px;
            text-align: center;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--gray-200);
            margin-top: 24px;
            background: linear-gradient(135deg, white, #f8fafc);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .success-card {
            background: linear-gradient(135deg, var(--dark-surface), var(--dark-bg));
            border-color: var(--dark-surface-2);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.2);
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--green-100), var(--green-200));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: var(--green-600);
            font-size: 2rem;
        }
        
        [data-theme="dark"] .success-icon {
            background: linear-gradient(135deg, var(--green-100), var(--green-200));
        }
        
        .success-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        
        [data-theme="dark"] .success-title {
            color: var(--dark-text);
        }
        
        .success-subtitle {
            color: var(--green-500);
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 32px;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: var(--primary-600);
            color: white;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: var(--primary-700);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(14, 165, 233, 0.2);
        }
        
        /* Haid Mode Toggle - WARNA MERAH */
        .haid-toggle-card {
            background: linear-gradient(135deg, var(--rose-50), var(--rose-100));
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 24px;
            border: 2px solid var(--rose-300);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .haid-toggle-card {
            background: linear-gradient(135deg, var(--rose-100), var(--rose-200));
            border-color: var(--rose-400);
        }
        
        .haid-toggle-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, rgba(244, 63, 94, 0.1), rgba(225, 29, 72, 0.05));
            border-radius: 50%;
        }
        
        .haid-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .moon-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--rose-500), var(--rose-600));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(244, 63, 94, 0.3);
        }
        
        .haid-text h3 {
            color: var(--rose-600);
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        [data-theme="dark"] .haid-text h3 {
            color: #fda4af;
        }
        
        .haid-text p {
            color: var(--rose-500);
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        [data-theme="dark"] .haid-text p {
            color: #fb7185;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--gray-300);
            transition: .4s;
            border-radius: 34px;
        }
        
        input:checked + .toggle-slider {
            background-color: var(--rose-500);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(30px);
            background-color: white;
        }
        
        /* Amalan Cards */
        .amalan-container {
            background: white;
            border-radius: 24px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-200);
            background: linear-gradient(135deg, white, #f8fafc);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .amalan-container {
            background: linear-gradient(135deg, var(--dark-surface), var(--dark-bg));
            border-color: var(--dark-surface-2);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 24px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        [data-theme="dark"] .section-title {
            color: var(--dark-text);
        }
        
        .section-title i {
            color: var(--primary-500);
        }
        
        .amalan-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            position: relative;
        }
        
        [data-theme="dark"] .amalan-card {
            background: var(--dark-surface);
            border-color: var(--dark-surface-2);
        }
        
        .amalan-card:hover {
            border-color: var(--primary-300);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.1);
            transform: translateY(-2px);
        }
        
        [data-theme="dark"] .amalan-card:hover {
            border-color: var(--primary-500);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.2);
        }
        
        .amalan-header {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            gap: 12px;
        }
        
        .amalan-title-container {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        
        .amalan-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        
        [data-theme="dark"] .amalan-icon {
            background: linear-gradient(135deg, var(--primary-100), var(--primary-200));
        }
        
        .amalan-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--gray-900);
            flex: 1;
        }
        
        [data-theme="dark"] .amalan-title {
            color: var(--dark-text);
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        
        @media (max-width: 640px) {
            .options-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .option-label {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            background: var(--gray-50);
            border-radius: 12px;
            border: 2px solid var(--gray-200);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        [data-theme="dark"] .option-label {
            background: var(--dark-surface-2);
            border-color: var(--dark-surface-2);
        }
        
        .option-label:hover {
            border-color: var(--primary-300);
            background: var(--primary-50);
            transform: translateY(-2px);
        }
        
        [data-theme="dark"] .option-label:hover {
            background: var(--primary-100);
        }
        
        .option-label.selected {
            border-color: var(--primary-500);
            background: var(--primary-50);
        }
        
        [data-theme="dark"] .option-label.selected {
            background: var(--primary-100);
        }
        
        .option-radio {
            display: none;
        }
        
        .radio-custom {
            width: 20px;
            height: 20px;
            border: 2px solid var(--gray-300);
            border-radius: 50%;
            margin-right: 12px;
            position: relative;
            flex-shrink: 0;
        }
        
        .option-radio:checked + .radio-custom {
            border-color: var(--primary-500);
            background-color: var(--primary-500);
        }
        
        .option-radio:checked + .radio-custom::after {
            content: "";
            position: absolute;
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .option-text {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-800);
        }
        
        [data-theme="dark"] .option-text {
            color: var(--dark-text-secondary);
        }
        
        /* Udzur Badge dengan warna merah */
        .udzur-badge {
            background: linear-gradient(135deg, var(--rose-50), var(--rose-100));
            border: 2px solid var(--rose-300);
            border-radius: 12px;
            padding: 16px;
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        [data-theme="dark"] .udzur-badge {
            background: linear-gradient(135deg, var(--rose-100), var(--rose-200));
            border-color: var(--rose-400);
        }
        
        .udzur-badge i {
            color: var(--rose-600);
            font-size: 1.25rem;
        }
        
        .udzur-text {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--rose-600);
        }
        
        [data-theme="dark"] .udzur-text {
            color: #fda4af;
        }
        
        .udzur-note {
            font-size: 0.75rem;
            color: var(--rose-500);
            margin-top: 4px;
            font-weight: 500;
        }
        
        [data-theme="dark"] .udzur-note {
            color: #fb7185;
        }
        
        .submit-btn {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-500), var(--green-500));
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.2);
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(14, 165, 233, 0.3);
            background: linear-gradient(135deg, var(--primary-600), var(--green-600));
        }
        
        .hidden {
            display: none !important;
        }
        
        /* Icon colors for different amalan */
        .icon-tahajjud { 
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            color: #4f46e5;
        }
        .icon-sholat { 
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1d4ed8;
        }
        .icon-sholawat { 
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            color: #0369a1;
        }
        .icon-doa { 
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            color: #0284c7;
        }
        .icon-dzikir { 
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            color: #0ea5e9;
        }
        .icon-istigfar { 
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #16a34a;
        }
        .icon-sedekah { 
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }
        .icon-quran { 
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #059669;
        }
        .icon-hafalan { 
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            color: #7c3aed;
        }
        .icon-matahari { 
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }
        .icon-buku { 
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            color: #4f46e5;
        }
        .icon-belajar { 
            background: linear-gradient(135deg, #fce7f3, #fbcfe8);
            color: #be185d;
        }
        .icon-memaafkan { 
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #16a34a;
        }
        .icon-orangtua { 
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            color: #0369a1;
        }
        .icon-gym { 
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
        }
        .icon-tidur { 
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            color: #4f46e5;
        }
        .icon-makan { 
            background: linear-gradient(135deg, #ffedd5, #fed7aa);
            color: #ea580c;
        }
        
        /* Indicator untuk amalan yang selesai */
        .completed-indicator {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            background: var(--green-500);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.7rem;
            opacity: 0;
            transform: scale(0);
            transition: all 0.3s ease;
        }
        
        .amalan-card.has-selection .completed-indicator {
            opacity: 1;
            transform: scale(1);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header dengan info user -->
        <div class="user-header">
            <div class="user-info">
                <h2>Halo, <?php echo htmlspecialchars($userData['username']); ?>! ✨</h2>
                <div class="level">
                    <i class="fas fa-medal" style="color: var(--secondary-400);"></i>
                    <span>Level <?php echo $level['lv']; ?> - <?php echo $level['nama']; ?></span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min($progress, 100); ?>%"></div>
                    </div>
                    <div class="progress-text">
                        <span><?php echo number_format($userData['total_poin']); ?> Poin</span>
                        <span><?php echo number_format($level['next']); ?> Poin</span>
                    </div>
                </div>
            </div>
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $userData['streak_count']; ?></div>
                    <div class="stat-label">Streak 🔥</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo date('d/m/Y'); ?></div>
                    <div class="stat-label">Hari Ini</div>
                </div>
            </div>
        </div>

        <?php if ($done || isset($_GET['status'])): ?>
            <!-- Success Screen -->
            <div class="success-card">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h1 class="success-title">Laporan Terkirim! 🎉</h1>
                <p class="success-subtitle">Amalan hari ini telah tercatat</p>
                <p style="color: var(--gray-800); margin-bottom: 32px; font-size: 0.95rem;">
                    Poin dan streak kamu sudah diperbarui. Tetap semangat untuk konsisten!
                </p>
                <a href="dashboard.php" class="btn">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
                </a>
            </div>
        <?php else: ?>
            <!-- Form Input Nafsiyah -->
            <form action="" method="POST" id="formNafsiyah">
                <!-- Toggle Haid dengan warna merah -->
                <div class="haid-toggle-card">
                    <div class="haid-info">
                        <div class="moon-icon">
                            <i class="fas fa-moon"></i>
                        </div>
                        <div class="haid-text">
                            <h3>Mode Udzur Syar'i (Haid/Nifas)</h3>
                            <p>Aktifkan untuk amalan ibadah mahdhah</p>
                        </div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="mode_haid" id="modeHaid">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <!-- Daftar Amalan -->
                <div class="amalan-container">
                    <h2 class="section-title">
                        <i class="fas fa-clipboard-check"></i> Laporan Amalan Harian
                    </h2>
                    
                    <?php
                    $q = $pdo->query("SELECT * FROM nafsiyah_items ORDER BY urutan ASC")->fetchAll();
                    foreach ($q as $i):
                        $activity = $i['activity_name'];
                        
                        // Deteksi amalan yang kena filter udzur
                        $keywords = ['Tahajjud', 'Dhuha', 'Sholat', 'Rawatib', 'Dzikir', 'Doa Setelah', 'Tilawah', 'Murojaah', 'Hafalan'];
                        $is_kena_udzur = false;
                        foreach ($keywords as $k) {
                            if (stripos($activity, $k) !== false) { $is_kena_udzur = true; break; }
                        }
                        
                        // Tentukan icon berdasarkan jenis amalan
                        $icon = 'fas fa-star';
                        $icon_class = 'icon-sholat';
                        
                        if (stripos($activity, 'Tahajjud') !== false) {
                            $icon = 'fas fa-moon';
                            $icon_class = 'icon-tahajjud';
                        } elseif (stripos($activity, 'Sholat') !== false || stripos($activity, 'sholat') !== false) {
                            $icon = 'fas fa-person-praying';
                            $icon_class = 'icon-sholat';
                        } elseif (stripos($activity, 'Sholawat') !== false) {
                            $icon = 'fas fa-hands-praying';
                            $icon_class = 'icon-sholawat';
                        } elseif (stripos($activity, 'Doa') !== false && stripos($activity, 'Dzikir') === false) {
                            $icon = 'fas fa-hands-praying';
                            $icon_class = 'icon-doa';
                        } elseif (stripos($activity, 'Dzikir') !== false) {
                            $icon = 'fas fa-hands-praying';
                            $icon_class = 'icon-dzikir';
                        } elseif (stripos($activity, 'Istigfar') !== false) {
                            $icon = 'fas fa-pray';
                            $icon_class = 'icon-istigfar';
                        } elseif (stripos($activity, 'Sedekah') !== false) {
                            $icon = 'fas fa-hand-holding-usd';
                            $icon_class = 'icon-sedekah';
                        } elseif (stripos($activity, 'Tilawah') !== false) {
                            $icon = 'fas fa-book-quran';
                            $icon_class = 'icon-quran';
                        } elseif (stripos($activity, 'Murojaah') !== false) {
                            $icon = 'fas fa-book-quran';
                            $icon_class = 'icon-quran';
                        } elseif (stripos($activity, 'Hafalan') !== false) {
                            $icon = 'fas fa-brain';
                            $icon_class = 'icon-hafalan';
                        } elseif (stripos($activity, 'Syuruq') !== false) {
                            $icon = 'fas fa-sunrise';
                            $icon_class = 'icon-matahari';
                        } elseif (stripos($activity, 'Dhuha') !== false) {
                            $icon = 'fas fa-sun';
                            $icon_class = 'icon-matahari';
                        } elseif (stripos($activity, 'Rawatib') !== false) {
                            $icon = 'fas fa-hands-praying';
                            $icon_class = 'icon-doa';
                        } elseif (stripos($activity, 'Sayyidul') !== false && stripos($activity, 'Istigfar') !== false) {
                            $icon = 'fas fa-pray';
                            $icon_class = 'icon-istigfar';
                        } elseif (stripos($activity, 'Buku') !== false) {
                            $icon = 'fas fa-book-open';
                            $icon_class = 'icon-buku';
                        } elseif (stripos($activity, 'Halqoh') !== false) {
                            $icon = 'fas fa-users';
                            $icon_class = 'icon-belajar';
                        } elseif (stripos($activity, 'Minta Doa') !== false && (stripos($activity, 'orang tua') !== false || stripos($activity, 'ortu') !== false)) {
                            $icon = 'fas fa-users';
                            $icon_class = 'icon-orangtua';
                        } elseif (stripos($activity, 'Minta Doa') !== false) {
                            $icon = 'fas fa-hands-praying';
                            $icon_class = 'icon-doa';
                        } elseif (stripos($activity, 'Memaafkan') !== false) {
                            $icon = 'fas fa-handshake';
                            $icon_class = 'icon-memaafkan';
                        } elseif (stripos($activity, 'Olahraga') !== false) {
                            $icon = 'fas fa-dumbbell';
                            $icon_class = 'icon-gym';
                        } elseif (stripos($activity, 'Tidur') !== false && stripos($activity, 'pagi') !== false) {
                            $icon = 'fas fa-bed';
                            $icon_class = 'icon-tidur';
                        } elseif (stripos($activity, 'Makan') !== false && stripos($activity, 'junk') !== false) {
                            $icon = 'fas fa-hamburger';
                            $icon_class = 'icon-makan';
                        } elseif (stripos($activity, 'Makan') !== false) {
                            $icon = 'fas fa-utensils';
                            $icon_class = 'icon-makan';
                        }
                        
                        $opts = !empty($i['sub_komponen']) ? explode(',', $i['sub_komponen']) : ["Selesai:10", "Tidak Mengerjakan:0"];
                    ?>
                    <div class="amalan-card <?= $is_kena_udzur ? 'udzur-item' : '' ?>" data-item-id="<?= $i['id'] ?>">
                        <div class="completed-indicator">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="amalan-header">
                            <div class="amalan-title-container">
                                <div class="amalan-icon <?= $icon_class ?>">
                                    <i class="<?= $icon ?>"></i>
                                </div>
                                <h3 class="amalan-title"><?= htmlspecialchars($activity) ?></h3>
                            </div>
                        </div>
                        
                        <!-- Opsi Normal -->
                        <div class="options-grid normal-options">
                            <?php foreach ($opts as $o): 
                                $x = explode(':', $o); 
                                $l = trim($x[0]); 
                                $s = $x[1] ?? 0; 
                                
                                // Tentukan icon untuk opsi
                                $option_icon = 'fas fa-circle';
                                $option_color = 'var(--gray-400)';
                                
                                if (stripos($l, 'Selesai') !== false) {
                                    $option_icon = 'fas fa-check-circle';
                                    $option_color = 'var(--green-500)';
                                } elseif (stripos($l, 'Tidak') !== false) {
                                    $option_icon = 'fas fa-times-circle';
                                    $option_color = 'var(--rose-500)';
                                } elseif (stripos($l, 'Tidur') !== false) {
                                    $option_icon = 'fas fa-bed';
                                    $option_color = 'var(--primary-500)';
                                } elseif (stripos($l, 'Makan') !== false || stripos($l, 'Junk') !== false) {
                                    $option_icon = 'fas fa-utensils';
                                    $option_color = 'var(--secondary-400)';
                                }
                            ?>
                            <label class="option-label">
                                <input type="radio" name="item[<?= $i['id'] ?>]" 
                                       value="<?= $l.'|'.$s ?>" 
                                       class="option-radio" 
                                       <?= !$is_kena_udzur ? 'required' : '' ?>>
                                <span class="radio-custom"></span>
                                <span class="option-text">
                                    <i class="<?= $option_icon ?> mr-2" style="color: <?= $option_color ?>;"></i>
                                    <?= $l ?>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Badge Udzur dengan warna merah -->
                        <?php if ($is_kena_udzur): ?>
                        <div class="udzur-badge hidden">
                            <i class="fas fa-moon"></i>
                            <div>
                                <div class="udzur-text">Udzur Syar'i</div>
                                <div class="udzur-note">Tetap mendapatkan 5 poin apresiasi</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Kirim Laporan Harian
                </button>
            </form>
        <?php endif; ?>
        
        <!-- Theme Toggle Button - DIPINDAHKAN KE BAWAH -->
        <div class="theme-toggle-bottom">
            <button class="theme-btn" id="themeToggle">
                <i class="fas fa-moon"></i>
                <span>Mode Gelap</span>
            </button>
        </div>
    </div>

    <script>
        // Theme Toggle Functionality
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;
        const themeIcon = themeToggle.querySelector('i');
        const themeText = themeToggle.querySelector('span');
        
        // Set initial icon dan text berdasarkan current theme
        if (htmlElement.getAttribute('data-theme') === 'dark') {
            themeIcon.className = 'fas fa-sun';
            themeText.textContent = 'Mode Terang';
        } else {
            themeIcon.className = 'fas fa-moon';
            themeText.textContent = 'Mode Gelap';
        }
        
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            // Update theme attribute
            htmlElement.setAttribute('data-theme', newTheme);
            
            // Update icon dan text
            if (newTheme === 'dark') {
                themeIcon.className = 'fas fa-sun';
                themeText.textContent = 'Mode Terang';
            } else {
                themeIcon.className = 'fas fa-moon';
                themeText.textContent = 'Mode Gelap';
            }
            
            // Save preference to cookie (expires in 30 days)
            document.cookie = `theme=${newTheme}; path=/; max-age=${30 * 24 * 60 * 60}`;
            
            // Add animation effect
            themeToggle.style.transform = 'scale(0.95)';
            setTimeout(() => {
                themeToggle.style.transform = 'scale(1)';
            }, 150);
        });
        
        // Toggle Mode Haid
        document.getElementById('modeHaid').addEventListener('change', function() {
            const udzurItems = document.querySelectorAll('.udzur-item');
            const isHaid = this.checked;
            const moonIcon = document.querySelector('.moon-icon');
            
            // Animasi untuk moon icon
            if (isHaid) {
                moonIcon.style.transform = 'scale(1.1)';
                moonIcon.style.boxShadow = '0 6px 20px rgba(244, 63, 94, 0.4)';
                document.querySelector('.haid-toggle-card').style.background = 'linear-gradient(135deg, var(--rose-100), var(--rose-200))';
                if (htmlElement.getAttribute('data-theme') === 'dark') {
                    document.querySelector('.haid-toggle-card').style.background = 'linear-gradient(135deg, var(--rose-200), var(--rose-300))';
                }
            } else {
                moonIcon.style.transform = 'scale(1)';
                moonIcon.style.boxShadow = '0 4px 12px rgba(244, 63, 94, 0.3)';
                document.querySelector('.haid-toggle-card').style.background = 'linear-gradient(135deg, var(--rose-50), var(--rose-100))';
                if (htmlElement.getAttribute('data-theme') === 'dark') {
                    document.querySelector('.haid-toggle-card').style.background = 'linear-gradient(135deg, var(--rose-100), var(--rose-200))';
                }
            }
            
            udzurItems.forEach(item => {
                const normalOptions = item.querySelector('.normal-options');
                const udzurBadge = item.querySelector('.udzur-badge');
                const radios = normalOptions.querySelectorAll('.option-radio');
                
                if (isHaid) {
                    normalOptions.classList.add('hidden');
                    if (udzurBadge) udzurBadge.classList.remove('hidden');
                    radios.forEach(r => {
                        r.removeAttribute('required');
                        r.checked = false;
                        r.closest('.option-label').classList.remove('selected');
                    });
                    item.classList.remove('has-selection');
                } else {
                    normalOptions.classList.remove('hidden');
                    if (udzurBadge) udzurBadge.classList.add('hidden');
                    radios.forEach(r => r.setAttribute('required', ''));
                }
            });
        });
        
        // Style untuk radio button yang dipilih
        document.querySelectorAll('.option-radio').forEach(radio => {
            radio.addEventListener('change', function() {
                // Hapus class selected dari semua label di parent yang sama
                const allLabels = this.closest('.options-grid').querySelectorAll('.option-label');
                allLabels.forEach(label => label.classList.remove('selected'));
                
                // Tambah class selected ke label yang dipilih
                if (this.checked) {
                    this.closest('.option-label').classList.add('selected');
                    const amalanCard = this.closest('.amalan-card');
                    amalanCard.classList.add('has-selection');
                    
                    // Animasi untuk indicator
                    const indicator = amalanCard.querySelector('.completed-indicator');
                    indicator.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        indicator.style.transform = 'scale(1)';
                    }, 200);
                }
            });
        });
        
        // Animasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.amalan-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Tambah hover effect untuk option label
            document.querySelectorAll('.option-label').forEach(label => {
                label.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                label.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('selected')) {
                        this.style.transform = 'translateY(0)';
                    }
                });
            });
            
            // Progress bar animation
            const progressFill = document.querySelector('.progress-fill');
            if (progressFill) {
                const width = progressFill.style.width;
                progressFill.style.width = '0';
                setTimeout(() => {
                    progressFill.style.transition = 'width 1.5s ease';
                    progressFill.style.width = width;
                }, 500);
            }
        });
        
        // Form submission feedback
        document.getElementById('formNafsiyah').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
            submitBtn.disabled = true;
            
            // Simulasi loading sebelum submit sebenarnya
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });
    </script>
</body>
</html>