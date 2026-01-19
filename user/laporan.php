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

<!-- Container Utama -->
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Header User Card (Simplified for History Page) -->
    <div class="relative bg-white rounded-3xl p-6 shadow-sm border border-slate-200 overflow-hidden dark:bg-dark-surface dark:border-dark-surface2">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary-400 to-green-500"></div>
        <div class="flex items-center justify-between relative z-10">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-100 to-primary-200 flex items-center justify-center text-2xl text-primary-600 shadow-inner dark:bg-primary-900/20 dark:text-primary-400">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-white">Riwayat Progres</h1>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Analisis perjalanan ibadahmu</p>
                </div>
            </div>
            
            <!-- Tombol Kembali -->
            <a href="dashboard.php" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-50 hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm dark:bg-dark-surface2 dark:border-slate-700 dark:text-slate-300 dark:hover:text-white">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-200 dark:bg-dark-surface dark:border-dark-surface2">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-800 dark:text-white"><?= $chart_title ?></h2>
                <p class="text-xs text-slate-400 font-medium mt-1">Persentase penyelesaian amalan</p>
            </div>
            
            <!-- Filters -->
            <div class="flex bg-slate-100 p-1 rounded-xl dark:bg-dark-surface2 border border-slate-200 dark:border-slate-700">
                <?php 
                $filters = [
                    'daily' => 'Harian',
                    'weekly' => 'Mingguan',
                    'monthly' => 'Bulanan'
                ];
                foreach($filters as $key => $label): 
                    $isActive = $range === $key;
                    $activeClass = 'bg-white text-primary-600 shadow-sm dark:bg-slate-700 dark:text-white';
                    $inactiveClass = 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200';
                ?>
                <a href="laporan.php?range=<?= $key ?>" class="px-4 py-2 rounded-lg text-xs font-bold transition-all <?= $isActive ? $activeClass : $inactiveClass ?>">
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="relative h-72 w-full">
            <canvas id="chartCanvas"></canvas>
        </div>
    </div>

    <!-- Detail Laporan Harian -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden dark:bg-dark-surface dark:border-dark-surface2">
        <!-- Header Detail -->
        <div class="flex flex-col sm:flex-row justify-between items-center p-6 border-b border-slate-100 bg-slate-50/50 dark:bg-dark-surface2/50 dark:border-slate-700 gap-4">
            <div class="text-center sm:text-left">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Detail Laporan</h3>
                <p class="text-sm font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 px-3 py-1 rounded-lg mt-2 inline-block">
                    <i class="far fa-calendar-alt mr-2"></i> <?= $tanggal_obj->format('l, d F Y') ?>
                </p>
            </div>
            
            <!-- Navigasi Tanggal -->
            <div class="flex items-center gap-2">
                <a href="laporan.php?range=<?= $range ?>&tanggal=<?= $tanggal_kemarin ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:bg-white hover:text-primary-600 hover:shadow-md hover:border-primary-200 transition-all bg-white dark:bg-dark-surface dark:border-slate-700 dark:text-slate-400 dark:hover:text-white">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a href="laporan.php?range=<?= $range ?>&tanggal=<?= $tanggal_besok ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:bg-white hover:text-primary-600 hover:shadow-md hover:border-primary-200 transition-all bg-white dark:bg-dark-surface dark:border-slate-700 dark:text-slate-400 dark:hover:text-white">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <!-- List Amalan -->
        <div class="p-6">
            <div class="grid gap-3">
                <?php foreach ($laporan as $item): ?>
                    <?php
                    $status = $item['status'] ?? 'tidak_selesai';
                    
                    // Style berdasarkan status
                    $itemBg = 'bg-white border-slate-100 hover:border-slate-200 dark:bg-dark-surface dark:border-slate-700';
                    $iconBg = 'bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500';
                    $statusBadge = 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400';
                    $statusIcon = 'fa-minus';
                    $statusText = 'Belum';

                    if ($status === 'selesai') {
                        $itemBg = 'bg-green-50/30 border-green-100 hover:border-green-200 dark:bg-green-900/10 dark:border-green-900/30';
                        $iconBg = 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400';
                        $statusBadge = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                        $statusIcon = 'fa-check';
                        $statusText = 'Selesai';
                    } elseif ($status === 'sebagian') {
                        $itemBg = 'bg-amber-50/30 border-amber-100 hover:border-amber-200 dark:bg-amber-900/10 dark:border-amber-900/30';
                        $iconBg = 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400';
                        $statusBadge = 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                        $statusIcon = 'fa-hourglass-half';
                        $statusText = 'Sebagian';
                    } elseif ($status === 'tidak_selesai' && !empty($item['status'])) { // Jika ada record tapi statusnya gagal/absen
                         $itemBg = 'bg-rose-50/30 border-rose-100 hover:border-rose-200 dark:bg-rose-900/10 dark:border-rose-900/30';
                         $iconBg = 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400';
                         $statusBadge = 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
                         $statusIcon = 'fa-times';
                         $statusText = 'Absen';
                    }
                    ?>
                    <div class="flex items-center justify-between p-4 rounded-2xl border transition-all group <?= $itemBg ?>">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold <?= $iconBg ?>">
                                <?= $item['urutan'] ?>
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 text-sm md:text-base dark:text-white">
                                    <?= htmlspecialchars($item['activity_name']) ?>
                                </div>
                                <?php if (!empty($item['catatan']) && $item['catatan'] !== $statusText): ?>
                                    <div class="text-xs text-slate-500 mt-0.5 italic dark:text-slate-400">
                                        "<?= htmlspecialchars($item['catatan']) ?>"
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider flex items-center gap-2 <?= $statusBadge ?>">
                            <i class="fas <?= $statusIcon ?>"></i>
                            <span class="hidden sm:inline"><?= $statusText ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<!-- Script Chart JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chartCanvas');
    if(!ctx) return;

    // Deteksi Dark Mode
    const isDarkMode = document.documentElement.classList.contains('dark');
    const textColor = isDarkMode ? '#94a3b8' : '#64748b';
    const gridColor = isDarkMode ? '#334155' : '#f1f5f9';
    
    // Gradient Context (UPDATED TO PURPLE)
    const ctx2d = ctx.getContext('2d');
    const gradient = ctx2d.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(168, 85, 247, 0.5)'); // Purple 500 opacity
    gradient.addColorStop(1, 'rgba(168, 85, 247, 0.05)');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Penyelesaian %',
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: gradient,
                borderColor: '#a855f7', // Purple 500
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
                barPercentage: 0.6,
                hoverBackgroundColor: '#9333ea' // Purple 600
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
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
                    grid: { color: gridColor },
                    ticks: { 
                        font: { size: 11 },
                        color: textColor,
                        callback: function(value) { return value + '%' }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { 
                        font: { size: 11 },
                        color: textColor
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });
});
</script>

<?php include 'templates/footer.php'; ?>