<?php
// 1. KONEKSI WAJIB DI ATAS AGAR $pdo TERBACA
require_once __DIR__ . '/../koneksi.php';

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 2. PROTEKSI HALAMAN
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../login.php');
    exit();
}

$id_user = $_SESSION['user_id'];

// 3. FUNGSI LEVELING (NEWBIE - ISTIQOMAH - MUTTAQIN)
if (!function_exists('getLevel')) {
    function getLevel($poin) {
        if ($poin <= 1000) return ['lv' => 1, 'nama' => 'NEWBIE', 'next' => 1000, 'min' => 0];
        if ($poin <= 3000) return ['lv' => 2, 'nama' => 'ISTIQOMAH', 'next' => 3000, 'min' => 1000];
        return ['lv' => 3, 'nama' => 'MUTTAQIN', 'next' => 6000, 'min' => 3000];
    }
}

// 4. AMBIL DATA USER & STATISTIK
try {
    $stmt = $pdo->prepare("SELECT username, total_poin, streak_count FROM users WHERE id = ?");
    $stmt->execute([$id_user]);
    $user_data = $stmt->fetch();

    $total_poin = $user_data['total_poin'] ?? 0;
    $username = $user_data['username'] ?? 'User';
    $lvlData = getLevel($total_poin);
    $progress_lv = (($total_poin - $lvlData['min']) / ($lvlData['next'] - $lvlData['min'])) * 100;

    // Ambil data amalan hari ini
    $stmt_today = $pdo->prepare("SELECT COUNT(*) FROM nafsiyah_logs WHERE user_id = ? AND log_date = CURDATE() AND status = 'selesai'");
    $stmt_today->execute([$id_user]);
    $done_today = $stmt_today->fetchColumn();

    // Ambil total amalan
    $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM nafsiyah_logs WHERE user_id = ? AND status = 'selesai'");
    $stmt_total->execute([$id_user]);
    $total_amalan = $stmt_total->fetchColumn();

    // Ambil data chart 7 hari
    $stmt_chart = $pdo->prepare("SELECT log_date, COUNT(*) as count FROM nafsiyah_logs WHERE user_id = ? AND status = 'selesai' AND log_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY log_date ORDER BY log_date ASC");
    $stmt_chart->execute([$id_user]);
    $chart_raw = $stmt_chart->fetchAll(PDO::FETCH_KEY_PAIR);

    $chart_labels = []; $chart_values = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chart_labels[] = date('d M', strtotime($date));
        $chart_values[] = $chart_raw[$date] ?? 0;
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// 5. LOAD HEADER
require_once __DIR__ . '/templates/header.php';
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
        max-width: 1200px;
    }
}

/* Header User */
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

/* Progress Card */
.progress-card {
    background: white;
    border-radius: 24px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
    background: linear-gradient(135deg, white, #f8fafc);
}

@media (min-width: 768px) {
    .progress-card {
        padding: 32px;
    }
}

.progress-content {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

@media (min-width: 768px) {
    .progress-content {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

.progress-info {
    flex: 1;
}

.progress-info h2 {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 16px;
}

.points-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.points-value {
    font-size: 2rem;
    font-weight: 800;
    color: var(--gray-900);
}

.points-label {
    font-size: 1rem;
    color: var(--gray-600);
}

.level-badge {
    background: var(--primary-100);
    color: var(--primary-700);
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 700;
}

.progress-bar {
    height: 8px;
    background: var(--gray-100);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
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
    display: flex;
    justify-content: space-between;
}

.continue-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 24px;
    background: linear-gradient(135deg, var(--primary-500), var(--green-500));
    color: white;
    border-radius: 16px;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    margin-top: 16px;
    box-shadow: 0 8px 25px rgba(14, 165, 233, 0.2);
}

.continue-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(14, 165, 233, 0.3);
    background: linear-gradient(135deg, var(--primary-600), var(--green-600));
}

.level-visual {
    position: relative;
    width: 160px;
    height: 160px;
    flex-shrink: 0;
}

.level-number {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.level-value {
    font-size: 3rem;
    font-weight: 800;
    color: var(--gray-900);
    line-height: 1;
}

.level-text {
    font-size: 0.875rem;
    color: var(--gray-600);
    font-weight: 600;
}

.level-ring {
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
}

.level-ring-bg {
    fill: none;
    stroke: var(--gray-200);
    stroke-width: 8;
}

.level-ring-fill {
    fill: none;
    stroke: var(--primary-400);
    stroke-width: 8;
    stroke-linecap: round;
    stroke-dasharray: 283;
    stroke-dashoffset: 283;
    transition: stroke-dashoffset 1.5s ease-in-out;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    margin-bottom: 24px;
}

@media (min-width: 640px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
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
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px -10px rgba(14, 165, 233, 0.15);
    border-color: var(--primary-300);
}

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.stat-card-title {
    font-size: 0.875rem;
    color: var(--gray-600);
    font-weight: 600;
}

.stat-card-value {
    font-size: 2rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.stat-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.stat-card-footer {
    font-size: 0.75rem;
    color: var(--gray-500);
    line-height: 1.4;
}

/* Icon colors */
.icon-today {
    background: linear-gradient(135deg, var(--green-50), var(--green-100));
    color: var(--green-600);
}
.icon-total {
    background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
    color: var(--primary-600);
}
.icon-streak {
    background: linear-gradient(135deg, var(--rose-50), var(--rose-100));
    color: var(--rose-600);
}
.icon-challenge {
    background: linear-gradient(135deg, var(--secondary-50), var(--secondary-100));
    color: var(--secondary-600);
}

/* Chart Section */
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

@media (min-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 2fr 1fr;
    }
}

.chart-card {
    background: white;
    border-radius: 24px;
    padding: 24px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.chart-header h3 {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--gray-900);
}

.chart-date {
    font-size: 0.875rem;
    color: var(--gray-600);
    font-weight: 600;
}

.chart-container {
    height: 200px;
    position: relative;
}

/* Quick Actions */
.actions-card {
    background: white;
    border-radius: 24px;
    padding: 24px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
}

.actions-card h3 {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 20px;
}

.actions-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.action-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid var(--gray-200);
}

.action-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    border-color: var(--primary-300);
}

.action-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.action-text {
    flex: 1;
}

.action-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 2px;
}

.action-subtitle {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.action-arrow {
    color: var(--gray-400);
    font-size: 0.875rem;
}

/* Action colors */
.action-checklist .action-icon {
    background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
    color: var(--primary-600);
}
.action-checklist:hover {
    background: var(--primary-50);
    border-color: var(--primary-300);
}

.action-history .action-icon {
    background: linear-gradient(135deg, var(--green-50), var(--green-100));
    color: var(--green-600);
}
.action-history:hover {
    background: var(--green-50);
    border-color: var(--green-300);
}

.action-leaderboard .action-icon {
    background: linear-gradient(135deg, var(--rose-50), var(--rose-100));
    color: var(--rose-600);
}
.action-leaderboard:hover {
    background: var(--rose-50);
    border-color: var(--rose-300);
}

.action-settings .action-icon {
    background: linear-gradient(135deg, var(--gray-50), var(--gray-100));
    color: var(--gray-600);
}
.action-settings:hover {
    background: var(--gray-50);
    border-color: var(--gray-300);
}

/* Motivational Quote */
.motivation {
    text-align: center;
    padding: 20px;
    color: var(--gray-600);
    font-style: italic;
    font-size: 0.875rem;
    background: white;
    border-radius: 16px;
    border: 1px solid var(--gray-200);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
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
                <h1><?= htmlspecialchars($username) ?></h1>
                <div class="level">
                    <i class="fas fa-medal" style="color: var(--secondary-400);"></i>
                    <span>Level <?= $lvlData['lv'] ?> - <?= $lvlData['nama'] ?></span>
                </div>
            </div>
        </div>
        <div class="user-stats">
            <div class="stat-box">
                <div class="stat-value"><?= date('d/m/Y') ?></div>
                <div class="stat-label">Hari Ini</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">🔥 <?= $user_data['streak_count'] ?></div>
                <div class="stat-label">Streak</div>
            </div>
        </div>
    </div>

    <!-- Progress Card -->
    <div class="progress-card fade-in delay-1">
        <div class="progress-content">
            <div class="progress-info">
                <h2>Progress Spiritual Anda</h2>
                
                <div class="points-display">
                    <div>
                        <div class="points-value"><?= number_format($total_poin) ?></div>
                        <div class="points-label">Total Poin</div>
                    </div>
                    <div class="level-badge">Level <?= $lvlData['lv'] ?></div>
                </div>
                
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= min($progress_lv, 100) ?>%"></div>
                </div>
                
                <div class="progress-text">
                    <span><?= number_format($lvlData['min']) ?> poin</span>
                    <span><?= number_format($lvlData['next'] - $total_poin) ?> poin menuju level berikutnya</span>
                </div>
                
                <a href="index.php" class="continue-btn">
                    <i class="fas fa-plus"></i>
                    Lanjutkan Perjalanan
                </a>
            </div>
            
            <!-- Level Visualization -->
            <div class="level-visual">
                <div class="level-number">
                    <div class="level-value"><?= $lvlData['lv'] ?></div>
                    <div class="level-text">Level</div>
                </div>
                <svg class="level-ring" width="160" height="160" viewBox="0 0 100 100">
                    <circle class="level-ring-bg" cx="50" cy="50" r="45" />
                    <circle class="level-ring-fill" cx="50" cy="50" r="45" 
                            stroke-dashoffset="<?= 283 * (1 - min($progress_lv, 100) / 100) ?>" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card fade-in delay-1">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-title">Amalan Hari Ini</div>
                    <div class="stat-card-value"><?= $done_today ?></div>
                </div>
                <div class="stat-card-icon icon-today">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <?= $done_today > 0 ? 'Lanjutkan semangat!' : 'Yuk mulai amalan hari ini!' ?>
            </div>
        </div>
        
        <div class="stat-card fade-in delay-2">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-title">Total Amalan</div>
                    <div class="stat-card-value"><?= $total_amalan ?></div>
                </div>
                <div class="stat-card-icon icon-total">
                    <i class="fas fa-mosque"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                Total ibadah yang sudah diselesaikan
            </div>
        </div>
        
        <div class="stat-card fade-in delay-3">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-title">Streak Hari</div>
                    <div class="stat-card-value"><?= $user_data['streak_count'] ?></div>
                </div>
                <div class="stat-card-icon icon-streak">
                    <i class="fas fa-fire"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                Jaga terus streak-mu!
            </div>
        </div>
        
        <!-- BAGIAN TANTANGAN YANG SUDAH DISESUAIKAN -->
        <div class="stat-card fade-in delay-4">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-title">Tantangan</div>
                    <div class="stat-card-value">3</div>
                </div>
                <div class="stat-card-icon icon-challenge">
                    <i class="fas fa-trophy"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                Tantangan bulan ini
            </div>
        </div>
    </div>

    <!-- Chart & Quick Actions -->
    <div class="dashboard-grid">
        <!-- Activity Chart -->
        <div class="chart-card fade-in">
            <div class="chart-header">
                <h3>Aktivitas 7 Hari Terakhir</h3>
                <div class="chart-date"><?= date('M Y') ?></div>
            </div>
            <div class="chart-container">
                <canvas id="activityChart"></canvas>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="actions-card fade-in delay-1">
            <h3>Aksi Cepat</h3>
            <div class="actions-list">
                <a href="index.php" class="action-item action-checklist">
                    <div class="action-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="action-text">
                        <div class="action-title">Isi Checklist</div>
                        <div class="action-subtitle">Amalan hari ini</div>
                    </div>
                    <div class="action-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
                
                <a href="history.php" class="action-item action-history">
                    <div class="action-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="action-text">
                        <div class="action-title">Riwayat Laporan</div>
                        <div class="action-subtitle">Lihat progres lengkap</div>
                    </div>
                    <div class="action-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
                
                <a href="leaderboard.php" class="action-item action-leaderboard">
                    <div class="action-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="action-text">
                        <div class="action-title">Leaderboard</div>
                        <div class="action-subtitle">Peringkat komunitas</div>
                    </div>
                    <div class="action-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
                
                <a href="settings.php" class="action-item action-settings">
                    <div class="action-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="action-text">
                        <div class="action-title">Pengaturan</div>
                        <div class="action-subtitle">Akun & notifikasi</div>
                    </div>
                    <div class="action-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Motivational Quote -->
    <div class="motivation fade-in delay-2">
        "Konsistensi kecil yang berulang akan membawa dampak besar"
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart Configuration
const ctx = document.getElementById('activityChart');
const activityChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            data: <?= json_encode($chart_values) ?>,
            borderColor: 'var(--primary-500)',
            backgroundColor: 'rgba(14, 165, 233, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: 'var(--primary-500)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(31, 41, 55, 0.9)',
                titleFont: { size: 12 },
                bodyFont: { size: 13, weight: '600' },
                padding: 12,
                cornerRadius: 8
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'var(--gray-100)' },
                ticks: { 
                    font: { size: 11 },
                    color: 'var(--gray-600)'
                }
            },
            x: {
                grid: { display: false },
                ticks: { 
                    font: { size: 11 },
                    color: 'var(--gray-600)'
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// Animate progress ring
document.addEventListener('DOMContentLoaded', function() {
    const progressRing = document.querySelector('.level-ring-fill');
    if (progressRing) {
        setTimeout(() => {
            progressRing.style.transition = 'stroke-dashoffset 1.5s ease-in-out';
        }, 500);
    }
    
    // Add hover effects to cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });
});

// Responsive chart adjustments
window.addEventListener('resize', function() {
    if (activityChart) {
        activityChart.resize();
    }
});
</script>

<?php include 'templates/footer.php'; ?>