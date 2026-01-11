<?php
require_once __DIR__ . '/../koneksi.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$id_user_login = $_SESSION['user_id'];

// Ambil data user untuk header
$stmtUser = $pdo->prepare("SELECT username, total_poin, streak_count FROM users WHERE id = ?");
$stmtUser->execute([$id_user_login]);
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

$total_items_aktif = $pdo->query("SELECT COUNT(id) FROM nafsiyah_items WHERE is_active = 1")->fetchColumn();

// --- BAGIAN 1: LOGIKA UNTUK FILTER GRAFIK ---
$range = $_GET['range'] ?? 'daily';
$chart_labels = [];
$chart_data = [];
$chart_title = '';

switch ($range) {
    case 'weekly':
        $chart_title = 'Penyelesaian Mingguan';
        for ($i = 3; $i >= 0; $i--) {
            $start_date = (new DateTime())->modify('-' . ($i * 7) . ' days')->format('Y-m-d');
            $end_date = (new DateTime())->modify('-' . (($i * 7) - 6) . ' days')->format('Y-m-d');
            $chart_labels[] = ($i === 0) ? "Minggu Ini" : "$i Mgg Lalu";
            if ($total_items_aktif > 0) {
                $stmt = $pdo->prepare("SELECT COUNT(id) FROM nafsiyah_logs WHERE user_id = ? AND log_date BETWEEN ? AND ? AND status = 'selesai'");
                $stmt->execute([$id_user_login, $start_date, $end_date]);
                $chart_data[] = round(($stmt->fetchColumn() / ($total_items_aktif * 7)) * 100);
            } else { $chart_data[] = 0; }
        }
        break;
    case 'monthly':
        $chart_title = 'Penyelesaian Bulanan';
        for ($i = 5; $i >= 0; $i--) {
            $bulan = (new DateTime())->modify("-$i months");
            $chart_labels[] = $bulan->format('M Y');
            if ($total_items_aktif > 0) {
                $stmt = $pdo->prepare("SELECT COUNT(id) FROM nafsiyah_logs WHERE user_id = ? AND YEAR(log_date) = ? AND MONTH(log_date) = ? AND status = 'selesai'");
                $stmt->execute([$id_user_login, $bulan->format('Y'), $bulan->format('m')]);
                $chart_data[] = round(($stmt->fetchColumn() / ($total_items_aktif * $bulan->format('t'))) * 100);
            } else { $chart_data[] = 0; }
        }
        break;
    default:
        $chart_title = 'Penyelesaian Harian';
        for ($i = 6; $i >= 0; $i--) {
            $tanggal = (new DateTime())->modify("-$i days");
            $chart_labels[] = $tanggal->format('d M');
            if ($total_items_aktif > 0) {
                $stmt = $pdo->prepare("SELECT COUNT(id) FROM nafsiyah_logs WHERE user_id = ? AND log_date = ? AND status = 'selesai'");
                $stmt->execute([$id_user_login, $tanggal->format('Y-m-d')]);
                $chart_data[] = round(($stmt->fetchColumn() / $total_items_aktif) * 100);
            } else { $chart_data[] = 0; }
        }
        break;
}

// --- BAGIAN 2: LOGIKA DETAIL LAPORAN ---
$tanggal_sekarang_str = $_GET['tanggal'] ?? date('Y-m-d');
$tanggal_obj = new DateTime($tanggal_sekarang_str);
$tanggal_kemarin = (clone $tanggal_obj)->modify('-1 day')->format('Y-m-d');
$tanggal_besok = (clone $tanggal_obj)->modify('+1 day')->format('Y-m-d');

$stmt = $pdo->prepare("SELECT i.urutan, i.activity_name, l.status, l.catatan FROM nafsiyah_items i LEFT JOIN nafsiyah_logs l ON i.id = l.item_id AND l.user_id = ? AND l.log_date = ? WHERE i.is_active = 1 ORDER BY i.urutan ASC");
$stmt->execute([$id_user_login, $tanggal_sekarang_str]);
$laporan = $stmt->fetchAll();

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

/* Chart Card */
.chart-card {
    background: white;
    border-radius: 24px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
    background: linear-gradient(135deg, white, #f8fafc);
}

@media (min-width: 768px) {
    .chart-card {
        padding: 32px;
    }
}

.chart-header {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 24px;
}

@media (min-width: 768px) {
    .chart-header {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
}

.chart-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--gray-900);
}

.chart-filters {
    display: inline-flex;
    background: var(--gray-50);
    padding: 6px;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
    gap: 4px;
}

.filter-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--gray-600);
    background: transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.filter-btn.active {
    background: linear-gradient(135deg, var(--primary-500), var(--green-500));
    color: white;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
}

.filter-btn:not(.active):hover {
    background: var(--gray-100);
    color: var(--gray-900);
}

.chart-container {
    height: 300px;
    position: relative;
}

/* Detail Report Card */
.detail-card {
    background: white;
    border-radius: 24px;
    padding: 0;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
    overflow: hidden;
}

.detail-header {
    background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
    padding: 24px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    flex-direction: column;
    gap: 16px;
}

@media (min-width: 768px) {
    .detail-header {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
}

.detail-title h3 {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 4px;
}

.detail-title p {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--primary-600);
}

.date-navigation {
    display: flex;
    gap: 8px;
}

.date-btn {
    width: 44px;
    height: 44px;
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-600);
    text-decoration: none;
    transition: all 0.3s ease;
}

.date-btn:hover {
    border-color: var(--primary-300);
    color: var(--primary-600);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.detail-content {
    padding: 24px;
}

.report-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.report-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 16px;
    transition: all 0.3s ease;
}

.report-item:hover {
    border-color: var(--primary-300);
    background: var(--primary-50);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.report-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.report-number {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--gray-900), var(--gray-800));
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
    font-weight: 800;
    flex-shrink: 0;
}

.report-name {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--gray-900);
}

.report-status {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-selesai {
    background: linear-gradient(135deg, var(--green-50), var(--green-100));
    color: var(--green-700);
    border: 1px solid var(--green-200);
}

.status-sebagian {
    background: linear-gradient(135deg, var(--secondary-50), var(--secondary-100));
    color: var(--secondary-700);
    border: 1px solid var(--secondary-200);
}

.status-absen {
    background: linear-gradient(135deg, var(--rose-50), var(--rose-100));
    color: var(--rose-700);
    border: 1px solid var(--rose-200);
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
</style>

<div class="container">
    <!-- Header User -->
    <div class="user-header fade-in">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-chart-bar"></i>
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

    <!-- Chart Card -->
    <div class="chart-card fade-in delay-1">
        <div class="chart-header">
            <h2 class="chart-title"><?= $chart_title ?> (%)</h2>
            <div class="chart-filters">
                <a href="laporan.php?range=daily" class="filter-btn <?= $range === 'daily' ? 'active' : '' ?>">
                    Harian
                </a>
                <a href="laporan.php?range=weekly" class="filter-btn <?= $range === 'weekly' ? 'active' : '' ?>">
                    Mingguan
                </a>
                <a href="laporan.php?range=monthly" class="filter-btn <?= $range === 'monthly' ? 'active' : '' ?>">
                    Bulanan
                </a>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="chartCanvas"></canvas>
        </div>
    </div>

    <!-- Detail Report Card -->
    <div class="detail-card fade-in delay-2">
        <div class="detail-header">
            <div class="detail-title">
                <h3>Detail Laporan</h3>
                <p><?= $tanggal_obj->format('l, d F Y') ?></p>
            </div>
            <div class="date-navigation">
                <a href="laporan.php?range=<?= $range ?>&tanggal=<?= $tanggal_kemarin ?>" class="date-btn">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a href="laporan.php?range=<?= $range ?>&tanggal=<?= $tanggal_besok ?>" class="date-btn">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        <div class="detail-content">
            <div class="report-list">
                <?php foreach ($laporan as $item): ?>
                    <?php
                    $status = $item['status'] ?? 'tidak_selesai';
                    $status_class = 'status-absen';
                    if ($status === 'selesai') {
                        $status_class = 'status-selesai';
                    } elseif ($status === 'sebagian') {
                        $status_class = 'status-sebagian';
                    }
                    ?>
                    <div class="report-item">
                        <div class="report-info">
                            <div class="report-number">
                                <?= $item['urutan'] ?>
                            </div>
                            <div class="report-name">
                                <?= htmlspecialchars($item['activity_name']) ?>
                            </div>
                        </div>
                        <div class="report-status <?= $status_class ?>">
                            <?php
                            if ($status === 'selesai') {
                                echo 'Selesai';
                            } elseif ($status === 'sebagian') {
                                echo 'Sebagian';
                            } else {
                                echo 'Absen';
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chartCanvas').getContext('2d');
    
    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(14, 165, 233, 0.8)');
    gradient.addColorStop(1, 'rgba(14, 165, 233, 0.1)');
    
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Penyelesaian %',
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: gradient,
                borderColor: 'var(--primary-500)',
                borderWidth: 2,
                borderRadius: 12,
                borderSkipped: false,
                barPercentage: 0.6
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
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        font: { size: 11, weight: '600' },
                        color: 'var(--gray-600)',
                        callback: function(value) {
                            return value + '%';
                        }
                    },
                    grid: {
                        color: 'var(--gray-100)'
                    }
                },
                x: {
                    ticks: {
                        font: { size: 11, weight: '600' },
                        color: 'var(--gray-600)'
                    },
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });
    
    // Add hover effects to report items
    const reportItems = document.querySelectorAll('.report-item');
    reportItems.forEach(item => {
        item.addEventListener('mouseenter', () => {
            item.style.transform = 'translateY(-2px)';
        });
        item.addEventListener('mouseleave', () => {
            item.style.transform = 'translateY(0)';
        });
    });
    
    // Responsive chart adjustments
    window.addEventListener('resize', function() {
        chart.resize();
    });
});
</script>

<?php include 'templates/footer.php'; ?>