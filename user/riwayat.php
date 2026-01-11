<?php
require_once __DIR__ . '/../koneksi.php';
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

if (!isset($_SESSION['user_id'])) { 
    header('Location: ../login.php');
    exit();
}

$id_user = $_SESSION['user_id'];

// Ambil data user untuk header
$stmtUser = $pdo->prepare("SELECT username, total_poin, streak_count FROM users WHERE id = ?");
$stmtUser->execute([$id_user]);
$userData = $stmtUser->fetch();

// Fungsi leveling
function getLevel($poin) {
    if ($poin < 1000) return ['lv' => 1, 'nama' => 'Newbie', 'next' => 1000, 'min' => 0];
    if ($poin < 3000) return ['lv' => 2, 'nama' => 'Istiqomah', 'next' => 3000, 'min' => 1000];
    if ($poin < 7000) return ['lv' => 3, 'nama' => 'Muttaqin', 'next' => 7000, 'min' => 3000];
    return ['lv' => 4, 'nama' => 'Murobitun', 'next' => 15000, 'min' => 7000];
}

$level = getLevel($userData['total_poin']);
$progress = (($userData['total_poin'] - $level['min']) / ($level['next'] - $level['min'])) * 100;

// Filter tanggal
$filter_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Ambil data riwayat
$query = "SELECT nl.*, ni.activity_name 
          FROM nafsiyah_logs nl 
          JOIN nafsiyah_items ni ON nl.item_id = ni.id 
          WHERE nl.user_id = ?";

$params = [$id_user];

if (!empty($filter_tanggal)) {
    $query .= " AND DATE(nl.log_date) = ?";
    $params[] = $filter_tanggal;
} elseif (!empty($filter_bulan)) {
    $query .= " AND DATE_FORMAT(nl.log_date, '%Y-%m') = ?";
    $params[] = $filter_bulan;
}

$query .= " ORDER BY nl.log_date DESC, ni.urutan ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$riwayat = $stmt->fetchAll();

// Group by date untuk tampilan
$riwayat_by_date = [];
$total_poin_per_date = [];
foreach ($riwayat as $item) {
    $date = date('d M Y', strtotime($item['log_date']));
    $riwayat_by_date[$date][] = $item;
    
    if (!isset($total_poin_per_date[$date])) {
        $total_poin_per_date[$date] = 0;
    }
    $total_poin_per_date[$date] += $item['poin_didapat'];
}

// Ambil daftar bulan yang tersedia
$stmtMonths = $pdo->prepare("SELECT DISTINCT DATE_FORMAT(log_date, '%Y-%m') as bulan 
                             FROM nafsiyah_logs 
                             WHERE user_id = ? 
                             ORDER BY bulan DESC");
$stmtMonths->execute([$id_user]);
$available_months = $stmtMonths->fetchAll(PDO::FETCH_COLUMN);

// Hitung statistik
$stmtStats = $pdo->prepare("SELECT 
                            COUNT(DISTINCT log_date) as total_hari,
                            SUM(poin_didapat) as total_poin,
                            COUNT(*) as total_amalan,
                            AVG(poin_didapat) as rata_poin
                            FROM nafsiyah_logs 
                            WHERE user_id = ?");
$stmtStats->execute([$id_user]);
$stats = $stmtStats->fetch();

include 'templates/header.php';
?>

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
    padding: 16px;
    color: var(--gray-900);
}

@media (min-width: 768px) {
    body {
        padding: 20px;
    }
}

.container {
    max-width: 100%;
    margin: 0 auto;
}

@media (min-width: 640px) {
    .container {
        max-width: 600px;
    }
}

@media (min-width: 768px) {
    .container {
        max-width: 750px;
    }
}

@media (min-width: 1024px) {
    .container {
        max-width: 800px;
    }
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
    flex-direction: column;
    gap: 16px;
    background: linear-gradient(135deg, white, #f8fafc);
    position: relative;
    overflow: hidden;
}

@media (min-width: 768px) {
    .user-header {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
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

.user-info {
    display: flex;
    align-items: center;
    gap: 16px;
}

.user-avatar {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--primary-400), var(--green-400));
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
}

.user-text h1 {
    color: var(--gray-900);
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.user-text .level {
    color: var(--primary-600);
    font-size: 0.875rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.user-stats {
    display: flex;
    gap: 12px;
    width: 100%;
    justify-content: space-between;
}

@media (min-width: 768px) {
    .user-stats {
        width: auto;
        gap: 20px;
        justify-content: flex-end;
    }
}

.stat-box {
    text-align: center;
    padding: 8px 12px;
    background: var(--primary-50);
    border-radius: 12px;
    min-width: 80px;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--primary-600);
}

.stat-label {
    font-size: 0.7rem;
    color: var(--gray-800);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Back Button */
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: var(--primary-100);
    color: var(--primary-700);
    border: 2px solid var(--primary-300);
    border-radius: 16px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    margin-bottom: 20px;
}

.back-btn:hover {
    background: var(--primary-200);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
}

/* Filter Section */
.filter-card {
    background: white;
    border-radius: 20px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
}

.filter-card h2 {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-card h2 i {
    color: var(--primary-500);
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

@media (min-width: 640px) {
    .filter-form {
        flex-direction: row;
        align-items: flex-end;
    }
}

.form-group {
    flex: 1;
}

.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 6px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--gray-200);
    border-radius: 12px;
    font-size: 0.875rem;
    color: var(--gray-900);
    background: white;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-500);
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
}

.filter-btn {
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--primary-500), var(--green-500));
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 44px;
}

.filter-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
    background: linear-gradient(135deg, var(--primary-600), var(--green-600));
}

/* Stats Overview */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

@media (min-width: 640px) {
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
    transition: all 0.3s ease;
    text-align: center;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 40px -10px rgba(14, 165, 233, 0.15);
    border-color: var(--primary-300);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin: 0 auto 12px;
}

.stat-value-large {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 4px;
}

.stat-label-small {
    font-size: 0.75rem;
    color: var(--gray-600);
    font-weight: 600;
}

/* Icon colors */
.icon-calendar { 
    background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
    color: var(--primary-600);
}
.icon-star { 
    background: linear-gradient(135deg, var(--secondary-50), var(--secondary-100));
    color: var(--secondary-600);
}
.icon-check { 
    background: linear-gradient(135deg, var(--green-50), var(--green-100));
    color: var(--green-600);
}
.icon-chart { 
    background: linear-gradient(135deg, var(--rose-50), var(--rose-100));
    color: var(--rose-600);
}

/* History List */
.history-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.history-date-card {
    background: white;
    border-radius: 20px;
    padding: 0;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
    overflow: hidden;
}

.date-header {
    background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
    padding: 20px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.date-title {
    font-size: 1rem;
    font-weight: 800;
    color: var(--gray-900);
    display: flex;
    align-items: center;
    gap: 8px;
}

.date-title i {
    color: var(--primary-500);
}

.date-points {
    background: linear-gradient(135deg, var(--green-500), var(--green-600));
    color: white;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 700;
}

.amalan-items {
    padding: 16px;
}

.amalan-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 12px;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    transition: all 0.3s ease;
}

.amalan-item:last-child {
    margin-bottom: 0;
}

.amalan-item:hover {
    border-color: var(--primary-300);
    background: var(--primary-50);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.amalan-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.amalan-info {
    flex: 1;
}

.amalan-name {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 2px;
}

.amalan-catatan {
    font-size: 0.75rem;
    color: var(--gray-600);
}

.amalan-points {
    font-size: 0.875rem;
    font-weight: 800;
    color: var(--green-600);
}

/* Status badges */
.status-selesai {
    color: var(--green-600);
    font-weight: 600;
}

.status-tidak-selesai {
    color: var(--rose-600);
    font-weight: 600;
}

.status-udzur {
    color: var(--rose-600);
    font-weight: 600;
}

/* Empty State */
.empty-state {
    background: white;
    border-radius: 20px;
    padding: 48px 24px;
    text-align: center;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
}

.empty-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-100), var(--primary-200));
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-600);
    font-size: 2rem;
    margin: 0 auto 24px;
}

.empty-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.empty-text {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 24px;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.start-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--primary-500), var(--green-500));
    color: white;
    border-radius: 16px;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.start-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
    background: linear-gradient(135deg, var(--primary-600), var(--green-600));
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.6s ease-out forwards;
}

.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }
.delay-4 { animation-delay: 0.4s; }
</style>

<div class="container">
    <!-- Header User -->
    <div class="user-header fade-in">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-text">
                <h1><?= htmlspecialchars($userData['username']) ?></h1>
                <div class="level">
                    <i class="fas fa-medal" style="color: var(--secondary-400);"></i>
                    <span>Level <?= $level['lv'] ?> - <?= $level['nama'] ?></span>
                </div>
            </div>
        </div>
        <div class="user-stats">
            <div class="stat-box">
                <div class="stat-value"><?= date('d/m/Y') ?></div>
                <div class="stat-label">Hari Ini</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">🔥 <?= $userData['streak_count'] ?></div>
                <div class="stat-label">Streak</div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <a href="dashboard.php" class="back-btn fade-in">
        <i class="fas fa-arrow-left"></i>
        Kembali ke Dashboard
    </a>

    <!-- Filter Section -->
    <div class="filter-card fade-in delay-1">
        <h2>
            <i class="fas fa-filter"></i>
            Filter Riwayat
        </h2>
        <form method="GET" action="" class="filter-form">
            <div class="form-group">
                <label for="tanggal">Tanggal</label>
                <input type="date" id="tanggal" name="tanggal" 
                       value="<?= htmlspecialchars($filter_tanggal) ?>" 
                       class="form-control" 
                       max="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label for="bulan">Bulan</label>
                <select id="bulan" name="bulan" class="form-control">
                    <option value="">Semua Bulan</option>
                    <?php foreach ($available_months as $month): ?>
                        <option value="<?= $month ?>" <?= $month == $filter_bulan ? 'selected' : '' ?>>
                            <?= date('F Y', strtotime($month . '-01')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="filter-btn">
                <i class="fas fa-search"></i>
                Filter
            </button>
        </form>
    </div>

    <!-- Stats Overview -->
    <div class="stats-grid fade-in delay-2">
        <div class="stat-card">
            <div class="stat-icon icon-calendar">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-value-large"><?= $stats['total_hari'] ?></div>
            <div class="stat-label-small">Total Hari</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-star">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-value-large"><?= number_format($stats['total_poin']) ?></div>
            <div class="stat-label-small">Total Poin</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-check">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value-large"><?= $stats['total_amalan'] ?></div>
            <div class="stat-label-small">Total Amalan</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-chart">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="stat-value-large"><?= number_format($stats['rata_poin'], 1) ?></div>
            <div class="stat-label-small">Rata-rata Poin</div>
        </div>
    </div>

    <!-- History List -->
    <?php if (!empty($riwayat_by_date)): ?>
        <div class="history-list">
            <?php foreach ($riwayat_by_date as $date => $amalan_list): ?>
                <div class="history-date-card fade-in">
                    <div class="date-header">
                        <div class="date-title">
                            <i class="fas fa-calendar-day"></i>
                            <?= $date ?>
                        </div>
                        <div class="date-points">
                            <?= $total_poin_per_date[$date] ?> Poin
                        </div>
                    </div>
                    <div class="amalan-items">
                        <?php foreach ($amalan_list as $amalan): ?>
                            <div class="amalan-item">
                                <?php
                                // Tentukan icon berdasarkan jenis amalan
                                $icon = 'fas fa-star';
                                $icon_bg = 'var(--primary-50)';
                                $icon_color = 'var(--primary-600)';
                                
                                $activity = $amalan['activity_name'];
                                if (stripos($activity, 'Tahajjud') !== false) {
                                    $icon = 'fas fa-moon';
                                } elseif (stripos($activity, 'Sholat') !== false || stripos($activity, 'sholat') !== false) {
                                    $icon = 'fas fa-person-praying';
                                } elseif (stripos($activity, 'Sholawat') !== false) {
                                    $icon = 'fas fa-pray';
                                } elseif (stripos($activity, 'Doa') !== false) {
                                    $icon = 'fas fa-hands-praying';
                                } elseif (stripos($activity, 'Dzikir') !== false) {
                                    $icon = 'fas fa-hands-praying';
                                } elseif (stripos($activity, 'Istigfar') !== false) {
                                    $icon = 'fas fa-pray';
                                } elseif (stripos($activity, 'Sedekah') !== false) {
                                    $icon = 'fas fa-hand-holding-usd';
                                } elseif (stripos($activity, 'Tilawah') !== false) {
                                    $icon = 'fas fa-book-quran';
                                } elseif (stripos($activity, 'Murojaah') !== false) {
                                    $icon = 'fas fa-redo-alt';
                                } elseif (stripos($activity, 'Hafalan') !== false) {
                                    $icon = 'fas fa-brain';
                                } elseif (stripos($activity, 'Syuruq') !== false) {
                                    $icon = 'fas fa-sunrise';
                                } elseif (stripos($activity, 'Dhuha') !== false) {
                                    $icon = 'fas fa-sun';
                                } elseif (stripos($activity, 'Buku') !== false) {
                                    $icon = 'fas fa-book-open';
                                } elseif (stripos($activity, 'Halqoh') !== false) {
                                    $icon = 'fas fa-users';
                                } elseif (stripos($activity, 'Minta Doa') !== false) {
                                    $icon = stripos($activity, 'orang tua') !== false || stripos($activity, 'ortu') !== false ? 'fas fa-user-friends' : 'fas fa-hands-praying';
                                } elseif (stripos($activity, 'Memaafkan') !== false) {
                                    $icon = 'fas fa-handshake';
                                } elseif (stripos($activity, 'Olahraga') !== false) {
                                    $icon = 'fas fa-dumbbell';
                                } elseif (stripos($activity, 'Tidur') !== false) {
                                    $icon = 'fas fa-bed';
                                } elseif (stripos($activity, 'Makan') !== false) {
                                    $icon = stripos($activity, 'junk') !== false ? 'fas fa-hamburger' : 'fas fa-utensils';
                                }
                                
                                // Status color
                                $status_class = '';
                                if ($amalan['catatan'] === "Udzur Syar'i") {
                                    $status_class = 'status-udzur';
                                    $icon_bg = 'var(--rose-50)';
                                    $icon_color = 'var(--rose-600)';
                                } elseif ($amalan['status'] === 'selesai') {
                                    $status_class = 'status-selesai';
                                    $icon_bg = 'var(--green-50)';
                                    $icon_color = 'var(--green-600)';
                                } else {
                                    $status_class = 'status-tidak-selesai';
                                    $icon_bg = 'var(--rose-50)';
                                    $icon_color = 'var(--rose-600)';
                                }
                                ?>
                                <div class="amalan-icon" style="background: <?= $icon_bg ?>; color: <?= $icon_color ?>;">
                                    <i class="<?= $icon ?>"></i>
                                </div>
                                <div class="amalan-info">
                                    <div class="amalan-name"><?= htmlspecialchars($amalan['activity_name']) ?></div>
                                    <div class="amalan-catatan <?= $status_class ?>">
                                        <?php 
                                        if ($amalan['catatan'] === "Udzur Syar'i") {
                                            echo 'Udzur Syar\'i';
                                        } elseif ($amalan['catatan']) {
                                            echo htmlspecialchars($amalan['catatan']);
                                        } else {
                                            echo $amalan['status'] === 'selesai' ? 'Selesai' : 'Tidak Selesai';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="amalan-points">
                                    +<?= $amalan['poin_didapat'] ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="empty-state fade-in delay-2">
            <div class="empty-icon">
                <i class="fas fa-history"></i>
            </div>
            <h3 class="empty-title">Belum Ada Riwayat</h3>
            <p class="empty-text">
                <?php if (!empty($filter_tanggal) || !empty($filter_bulan)): ?>
                    Tidak ada riwayat amalan pada periode yang dipilih. Coba filter tanggal atau bulan lain.
                <?php else: ?>
                    Anda belum memiliki riwayat amalan. Mulai dengan mengisi checklist amalan harian Anda.
                <?php endif; ?>
            </p>
            <a href="index.php" class="start-btn">
                <i class="fas fa-plus"></i>
                Mulai Amalan Hari Ini
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
// Filter interaction
document.addEventListener('DOMContentLoaded', function() {
    const tanggalInput = document.getElementById('tanggal');
    const bulanSelect = document.getElementById('bulan');
    
    // Reset bulan when tanggal is selected
    tanggalInput.addEventListener('change', function() {
        if (this.value) {
            bulanSelect.value = '';
        }
    });
    
    // Reset tanggal when bulan is selected
    bulanSelect.addEventListener('change', function() {
        if (this.value) {
            tanggalInput.value = '';
        }
    });
    
    // Add hover effects to cards
    const amalanItems = document.querySelectorAll('.amalan-item');
    amalanItems.forEach(item => {
        item.addEventListener('mouseenter', () => {
            item.style.transform = 'translateY(-2px)';
        });
        item.addEventListener('mouseleave', () => {
            item.style.transform = 'translateY(0)';
        });
    });
});

// Today button functionality
function filterToday() {
    document.getElementById('tanggal').value = '<?= date('Y-m-d') ?>';
    document.getElementById('bulan').value = '';
    document.querySelector('form').submit();
}
</script>

<?php include 'templates/footer.php'; ?>