<?php
// 1. KONEKSI WAJIB DI ATAS AGAR $pdo TERBACA
require_once __DIR__ . '/../koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. PROTEKSI HALAMAN
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$id_user = $_SESSION['user_id'];

// 3. FUNGSI LEVELING (NEWBIE - ISTIQOMAH - MUTTAQIN)
if (!function_exists('getLevel')) {
    function getLevel($poin)
    {
        if ($poin <= 1000)
            return ['lv' => 1, 'nama' => 'NEWBIE', 'next' => 1000, 'min' => 0];
        if ($poin <= 3000)
            return ['lv' => 2, 'nama' => 'ISTIQOMAH', 'next' => 3000, 'min' => 1000];
        if ($poin <= 7000)
            return ['lv' => 3, 'nama' => 'MUTTAQIN', 'next' => 7000, 'min' => 3000];
        return ['lv' => 4, 'nama' => 'MUROBITUN', 'next' => 15000, 'min' => 7000];
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

    $chart_labels = [];
    $chart_values = [];
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

<!-- Wrapper Utama -->
<div class="max-w-6xl mx-auto space-y-6 font-sans">

    <!-- Section 1: User Header Card -->
    <div
        class="relative bg-gradient-to-br from-white to-slate-50 rounded-3xl p-6 mb-6 shadow-sm border border-gray-200 overflow-hidden dark:from-dark-surface dark:to-dark-bg dark:border-dark-surface2 transition-all duration-300">
        <!-- Garis Atas Warna -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary-400 to-green-500"></div>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative z-10">

            <!-- Info Kiri -->
            <div class="flex-1 w-full">
                <h2 class="text-2xl font-bold mb-1 text-gray-900 dark:text-white">
                    Halo, <?php echo htmlspecialchars($userData['username'] ?? $nama_tampil); ?>! ✨
                </h2>

                <div class="flex items-center gap-2 text-sm font-semibold text-primary-600 dark:text-primary-400 mb-3">
                    <?php
                    // Fallback logic level in view just in case
                    $current_poin = $userData['total_poin'] ?? $total_poin_sidebar;
                    if (!function_exists('getLevel')) {
                        function getLevel($poin)
                        {
                            if ($poin < 1000)
                                return ['lv' => 1, 'nama' => 'Newbie', 'next' => 1000, 'min' => 0];
                            if ($poin < 3000)
                                return ['lv' => 2, 'nama' => 'Istiqomah', 'next' => 3000, 'min' => 1000];
                            if ($poin < 7000)
                                return ['lv' => 3, 'nama' => 'Muttaqin', 'next' => 7000, 'min' => 3000];
                            return ['lv' => 4, 'nama' => 'Murobitun', 'next' => 15000, 'min' => 7000];
                        }
                    }
                    $level = getLevel($current_poin);
                    $progress = (($current_poin - $level['min']) / ($level['next'] - $level['min'])) * 100;
                    ?>
                    <i class="fas fa-medal text-yellow-500"></i>
                    <span>Level <?php echo $level['lv']; ?> - <?php echo $level['nama']; ?></span>
                </div>

                <!-- Progress Bar -->
                <div class="w-full">
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden dark:bg-dark-surface2">
                        <div class="h-full bg-gradient-to-r from-primary-400 to-green-400 transition-all duration-500 rounded-full"
                            style="width: <?php echo min($progress, 100); ?>%"></div>
                    </div>
                    <div class="flex justify-between mt-1 text-xs font-semibold text-gray-500 dark:text-gray-400">
                        <span><?php echo number_format($current_poin); ?> Poin</span>
                        <span>Next: <?php echo number_format($level['next']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats Kanan -->
            <div class="flex gap-4 self-end md:self-center">
                <div class="text-center bg-primary-50 px-4 py-2 rounded-2xl min-w-[80px] dark:bg-dark-surface2">
                    <div class="text-2xl font-extrabold text-primary-600 dark:text-primary-400">
                        <?php echo $userData['streak_count'] ?? $streak_sidebar; ?>
                    </div>
                    <div class="text-[10px] uppercase font-bold tracking-wider text-gray-500 dark:text-gray-400">Streak
                        🔥</div>
                </div>
                <div class="text-center bg-primary-50 px-4 py-2 rounded-2xl min-w-[80px] dark:bg-dark-surface2">
                    <div class="text-2xl font-extrabold text-primary-600 dark:text-primary-400"><?php echo date('d'); ?>
                    </div>
                    <div class="text-[10px] uppercase font-bold tracking-wider text-gray-500 dark:text-gray-400">
                        <?php echo date('M Y'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Progress & Level Circle -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main Progress Bar -->
        <div
            class="lg:col-span-2 bg-white rounded-3xl p-6 md:p-8 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2 flex flex-col justify-center relative overflow-hidden group hover:shadow-lg transition-all">
            <!-- Background Decoration -->
            <div
                class="absolute top-0 right-0 w-32 h-32 bg-primary-50 rounded-bl-full -mr-8 -mt-8 dark:bg-primary-900/10 opacity-50 group-hover:opacity-100 transition-opacity">
            </div>

            <div class="relative z-10">
                <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2 dark:text-white">
                    <div
                        class="p-2 bg-primary-100 rounded-lg text-primary-600 dark:bg-primary-900/20 dark:text-primary-400">
                        <i class="fas fa-chart-simple"></i>
                    </div>
                    Progress Nafsiyah
                </h2>

                <div class="flex items-end justify-between mb-2">
                    <div>
                        <span
                            class="text-4xl font-black text-slate-800 dark:text-white"><?= number_format($total_poin) ?></span>
                        <span class="text-sm font-semibold text-slate-400 ml-1">Poin</span>
                    </div>
                    <div
                        class="text-xs font-bold text-primary-600 bg-primary-50 border border-primary-100 px-3 py-1 rounded-lg dark:bg-primary-500/20 dark:border-primary-500/30 dark:text-primary-400">
                        Level <?= $lvlData['lv'] ?>
                    </div>
                </div>

                <!-- Bar -->
                <div class="h-4 bg-slate-100 rounded-full overflow-hidden mb-3 dark:bg-dark-surface2 shadow-inner">
                    <!-- Ubah Gradient ke Ungu (Primary) -->
                    <div class="h-full bg-gradient-to-r from-primary-500 to-purple-400 rounded-full transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(139,92,246,0.5)]"
                        style="width: <?= min($progress_lv, 100) ?>%"></div>
                </div>

                <div class="flex justify-between text-xs font-semibold text-slate-400 mb-8">
                    <span><?= number_format($lvlData['min']) ?></span>
                    <span><?= number_format($lvlData['next']) ?> (Next Level)</span>
                </div>

                <!-- Tombol Utama Ungu -->
                <a href="index.php"
                    class="w-full flex items-center justify-center gap-2 py-4 rounded-2xl bg-slate-900 text-white font-bold hover:bg-primary-600 hover:-translate-y-1 hover:shadow-lg hover:shadow-primary-500/30 transition-all dark:bg-white dark:text-slate-900 dark:hover:bg-primary-100">
                    <i class="fas fa-plus-circle"></i> Lanjutkan Ibadah
                </a>
            </div>
        </div>

        <!-- Circular Level Visualization -->
        <div
            class="bg-white rounded-3xl p-6 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2 flex flex-col items-center justify-center relative overflow-hidden group hover:shadow-lg transition-all">
            <div class="relative z-10 text-center">
                <div class="relative w-40 h-40 mx-auto mb-4">
                    <!-- SVG Circle -->
                    <svg class="w-full h-full transform -rotate-90">
                        <!-- Background Circle -->
                        <circle cx="80" cy="80" r="70" stroke="currentColor" stroke-width="12" fill="transparent"
                            class="text-slate-100 dark:text-slate-700" />
                        <!-- Progress Circle (Warna Ungu) -->
                        <circle cx="80" cy="80" r="70" stroke="currentColor" stroke-width="12" fill="transparent"
                            stroke-dasharray="440" stroke-dashoffset="<?= 440 - (440 * min($progress_lv, 100) / 100) ?>"
                            class="text-primary-500 transition-all duration-1000 ease-out drop-shadow-md"
                            stroke-linecap="round" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black text-slate-800 dark:text-white"><?= $lvlData['lv'] ?></span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Level</span>
                    </div>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white"><?= $lvlData['nama'] ?></h3>
                <p class="text-xs text-slate-400 mt-1">Tingkatan saat ini</p>
            </div>
        </div>
    </div>

    <!-- Section 3: Stats Grid Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Today -->
        <div
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:-translate-y-1 hover:shadow-soft hover:border-primary-200 transition-all dark:bg-dark-surface dark:border-dark-surface2 group">
            <div class="flex justify-between items-start mb-4">
                <div
                    class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-600 group-hover:bg-green-100 transition-colors dark:bg-green-900/20 dark:text-green-400">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Today</span>
            </div>
            <div class="text-2xl font-black text-slate-800 mb-1 dark:text-white"><?= $done_today ?></div>
            <div class="text-xs text-slate-500 font-medium dark:text-slate-400">Amalan Selesai</div>
        </div>

        <!-- Total -->
        <div
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:-translate-y-1 hover:shadow-soft hover:border-primary-200 transition-all dark:bg-dark-surface dark:border-dark-surface2 group">
            <div class="flex justify-between items-start mb-4">
                <div
                    class="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center text-primary-600 group-hover:bg-primary-100 transition-colors dark:bg-primary-900/20 dark:text-primary-400">
                    <i class="fas fa-mosque"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total</span>
            </div>
            <div class="text-2xl font-black text-slate-800 mb-1 dark:text-white"><?= number_format($total_amalan) ?>
            </div>
            <div class="text-xs text-slate-500 font-medium dark:text-slate-400">Ibadah Tercatat</div>
        </div>

        <!-- Streak -->
        <div
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:-translate-y-1 hover:shadow-soft hover:border-primary-200 transition-all dark:bg-dark-surface dark:border-dark-surface2 group">
            <div class="flex justify-between items-start mb-4">
                <div
                    class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-500 group-hover:bg-rose-100 transition-colors dark:bg-rose-900/20 dark:text-rose-400">
                    <i class="fas fa-fire"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Streak</span>
            </div>
            <div class="text-2xl font-black text-slate-800 mb-1 dark:text-white"><?= $user_data['streak_count'] ?></div>
            <div class="text-xs text-slate-500 font-medium dark:text-slate-400">Hari Beruntun</div>
        </div>

        <!-- Challenge -->
        <div
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:-translate-y-1 hover:shadow-soft hover:border-primary-200 transition-all dark:bg-dark-surface dark:border-dark-surface2 group">
            <div class="flex justify-between items-start mb-4">
                <div
                    class="w-10 h-10 rounded-xl bg-secondary-50 flex items-center justify-center text-secondary-500 group-hover:bg-secondary-100 transition-colors dark:bg-secondary-900/20 dark:text-secondary-400">
                    <i class="fas fa-trophy"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Target</span>
            </div>
            <div class="text-2xl font-black text-slate-800 mb-1 dark:text-white">3</div>
            <div class="text-xs text-slate-500 font-medium dark:text-slate-400">Tantangan Bulan Ini</div>
        </div>
    </div>

    <!-- Section 4: Chart & Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chart -->
        <div
            class="lg:col-span-2 bg-white rounded-3xl p-6 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2 group hover:shadow-lg transition-all">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-slate-800 dark:text-white">Aktivitas 7 Hari</h3>
                <span
                    class="text-xs font-bold text-primary-600 bg-primary-50 border border-primary-100 px-3 py-1 rounded-lg dark:bg-primary-900/20 dark:text-primary-400 dark:border-primary-800/30"><?= date('M Y') ?></span>
            </div>
            <div class="h-64 w-full">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        <!-- Quick Actions -->
        <div
            class="bg-white rounded-3xl p-6 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2 flex flex-col group hover:shadow-lg transition-all">
            <h3 class="font-bold text-slate-800 mb-6 dark:text-white">Aksi Cepat</h3>
            <div class="flex-1 space-y-3">
                <a href="index.php"
                    class="flex items-center gap-4 p-4 rounded-2xl border border-slate-100 hover:border-primary-300 hover:bg-primary-50 transition-all group/item dark:border-slate-700 dark:hover:bg-dark-surface2 dark:hover:border-primary-500/50 bg-slate-50/50">
                    <div
                        class="w-10 h-10 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center group-hover/item:scale-110 transition-transform dark:bg-primary-900/20 dark:text-primary-400 border border-primary-200 dark:border-primary-800/30">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="flex-1">
                        <div
                            class="text-sm font-bold text-slate-800 dark:text-white group-hover/item:text-primary-700 transition-colors">
                            Isi Checklist</div>
                        <div class="text-xs text-slate-400">Amalan hari ini</div>
                    </div>
                    <i
                        class="fas fa-chevron-right text-slate-300 group-hover/item:text-primary-500 transition-colors"></i>
                </a>

                <a href="laporan.php"
                    class="flex items-center gap-4 p-4 rounded-2xl border border-slate-100 hover:border-green-300 hover:bg-green-50 transition-all group/item dark:border-slate-700 dark:hover:bg-dark-surface2 dark:hover:border-green-500/50 bg-slate-50/50">
                    <div
                        class="w-10 h-10 rounded-xl bg-green-100 text-green-600 flex items-center justify-center group-hover/item:scale-110 transition-transform dark:bg-green-900/20 dark:text-green-400 border border-green-200 dark:border-green-800/30">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="flex-1">
                        <div
                            class="text-sm font-bold text-slate-800 dark:text-white group-hover/item:text-green-700 transition-colors">
                            Riwayat</div>
                        <div class="text-xs text-slate-400">Lihat progress</div>
                    </div>
                    <i
                        class="fas fa-chevron-right text-slate-300 group-hover/item:text-green-500 transition-colors"></i>
                </a>

                <a href="leaderboard.php"
                    class="flex items-center gap-4 p-4 rounded-2xl border border-slate-100 hover:border-secondary-300 hover:bg-secondary-50 transition-all group/item dark:border-slate-700 dark:hover:bg-dark-surface2 dark:hover:border-secondary-500/50 bg-slate-50/50">
                    <div
                        class="w-10 h-10 rounded-xl bg-secondary-100 text-secondary-600 flex items-center justify-center group-hover/item:scale-110 transition-transform dark:bg-secondary-900/20 dark:text-secondary-400 border border-secondary-200 dark:border-secondary-800/30">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="flex-1">
                        <div
                            class="text-sm font-bold text-slate-800 dark:text-white group-hover/item:text-secondary-700 transition-colors">
                            Leaderboard</div>
                        <div class="text-xs text-slate-400">Peringkat user</div>
                    </div>
                    <i
                        class="fas fa-chevron-right text-slate-300 group-hover/item:text-secondary-500 transition-colors"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Motivation Quote -->
    <div
        class="text-center p-6 rounded-3xl bg-slate-50 border border-slate-200/60 text-slate-500 italic font-medium dark:bg-dark-surface dark:border-dark-surface2 dark:text-slate-400 font-serif">
        "Konsistensi kecil yang berulang akan membawa dampak besar dalam perjalanan spiritualmu."
    </div>

</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Config Chart JS dengan Warna Tailwind UNGU (Purple)
    const ctx = document.getElementById('activityChart');

    // Mendeteksi Dark Mode untuk menyesuaikan warna grid/text chart
    const isDarkMode = document.documentElement.classList.contains('dark');
    const textColor = isDarkMode ? '#94a3b8' : '#64748b';
    const gridColor = isDarkMode ? '#334155' : '#f1f5f9';

    if (ctx) {
        // Gradient Context untuk Ungu
        const ctx2d = ctx.getContext('2d');
        const gradient = ctx2d.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(139, 92, 246, 0.5)'); // Purple-500 opacity
        gradient.addColorStop(1, 'rgba(139, 92, 246, 0.05)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    data: <?= json_encode($chart_values) ?>,
                    borderColor: '#8B5CF6', // Purple-500 (Primary)
                    backgroundColor: gradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#8B5CF6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { size: 12, family: "'Plus Jakarta Sans', sans-serif" },
                        bodyFont: { size: 13, weight: '600', family: "'Plus Jakarta Sans', sans-serif" },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor, drawBorder: false },
                        ticks: {
                            font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" },
                            color: textColor,
                            padding: 10
                        },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: {
                            font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" },
                            color: textColor,
                            padding: 10
                        },
                        border: { display: false }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }
</script>

<?php include 'templates/footer.php'; ?>