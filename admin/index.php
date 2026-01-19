<?php
require_once __DIR__ . '/../koneksi.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// --- 1. LOGIKA RESET POIN BULANAN ---
if (isset($_POST['reset_monthly_points'])) {
    try {
        $pdo->query("UPDATE users SET total_poin = 0");
        $admin_name = $_SESSION['username'] ?? 'Admin';
        $stmt_log = $pdo->prepare("INSERT INTO system_logs (aktivitas, tipe) VALUES (?, 'warning')");
        $stmt_log->execute(["$admin_name melakukan Reset Poin Bulanan"]);

        $_SESSION['toast'] = [
            'type' => 'success',
            'message' => 'Poin berhasil direset untuk periode baru'
        ];

        header("Location: index.php?status=points_reset");
        exit();
    } catch (PDOException $e) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'Gagal reset poin: ' . $e->getMessage()
        ];
    }
}

// --- 2. LOGIKA HAPUS SEMUA LOG ---
if (isset($_POST['delete_all_logs'])) {
    try {
        $pdo->query("DELETE FROM system_logs");
        $_SESSION['toast'] = [
            'type' => 'success',
            'message' => 'Semua log sistem telah dibersihkan'
        ];
        header("Location: index.php?status=logs_cleared");
        exit();
    } catch (PDOException $e) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'Gagal membersihkan log'
        ];
    }
}

// Fungsi format waktu
function formatWaktuRelatif($timestamp)
{
    if ($timestamp === null)
        return 'Belum ada aktivitas';
    $waktu = new DateTime($timestamp);
    $sekarang = new DateTime();
    $diff = $sekarang->diff($waktu);

    if ($diff->days == 0) {
        if ($diff->h == 0)
            return 'Baru saja';
        return $waktu->format('H:i');
    } elseif ($diff->days == 1)
        return 'Kemarin';
    elseif ($diff->days < 7)
        return $diff->days . ' hari lalu';
    return $waktu->format('d M');
}

try {
    // 3. AMBIL STATISTIK
    $total_users = $pdo->query("SELECT COUNT(id) FROM users")->fetchColumn();

    // User aktif hari ini (mengerjakan minimal 1 amalan)
    $active_today = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM nafsiyah_logs WHERE log_date = CURDATE()");
    $active_today->execute();
    $active_users_today = $active_today->fetchColumn();

    // AMALAN PALING SEDANG DIKERJAIN (TERLEWAT) - HARI INI
    $least_done_today = $pdo->prepare("
        SELECT i.activity_name, COUNT(l.id) as total 
        FROM nafsiyah_logs l 
        JOIN nafsiyah_items i ON l.item_id = i.id 
        WHERE l.log_date = CURDATE() 
        AND l.status = 'selesai'
        GROUP BY l.item_id, i.activity_name 
        ORDER BY total ASC 
        LIMIT 1
    ");
    $least_done_today->execute();
    $least_amalan_today = $least_done_today->fetch();
    $nama_least_amalan = $least_amalan_today ? $least_amalan_today['activity_name'] : 'Belum ada data';
    $total_least_amalan = $least_amalan_today ? $least_amalan_today['total'] : 0;

    // Hitung persentase partisipasi untuk amalan yang paling sedikit dikerjakan
    $partisipasi_percentage = $active_users_today > 0 ? ($total_least_amalan / $active_users_today) * 100 : 0;

    // Amalan terpopuler (paling banyak dikerjakan)
    $top_item = $pdo->query("
        SELECT i.activity_name, COUNT(l.id) as total 
        FROM nafsiyah_logs l 
        JOIN nafsiyah_items i ON l.item_id = i.id 
        WHERE l.status = 'selesai' 
        AND l.log_date = CURDATE()
        GROUP BY l.item_id, i.activity_name 
        ORDER BY total DESC 
        LIMIT 1
    ")->fetch();
    $nama_top_item = $top_item ? $top_item['activity_name'] : 'Belum ada data';
    $total_top_item = $top_item ? $top_item['total'] : 0;

    // 4. DATA GRAFIK 7 HARI (untuk chart)
    $admin_chart_labels = [];
    $admin_chart_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $tanggal = (new DateTime())->modify("-$i days");
        $admin_chart_labels[] = $tanggal->format('d M');
        $stmt_daily = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM nafsiyah_logs WHERE log_date = ?");
        $stmt_daily->execute([$tanggal->format('Y-m-d')]);
        $admin_chart_data[] = $stmt_daily->fetchColumn();
    }

    // 5. DATA AMALAN PALING SEDANG DIKERJAIN 7 HARI TERAKHIR (untuk insight tambahan)
    $least_done_week = $pdo->prepare("
        SELECT i.activity_name, COUNT(l.id) as total 
        FROM nafsiyah_logs l 
        JOIN nafsiyah_items i ON l.item_id = i.id 
        WHERE l.log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND l.status = 'selesai'
        GROUP BY l.item_id, i.activity_name 
        ORDER BY total ASC 
        LIMIT 3
    ");
    $least_done_week->execute();
    $least_amalan_week = $least_done_week->fetchAll();

    // 6. AMBIL DATA LOGS & RINGKASAN
    $system_logs = $pdo->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 5")->fetchAll();

    // Data ringkasan pengguna (top 10 by points)
    $sql_ringkasan = "SELECT u.id, u.username, u.email, u.total_poin, u.streak_count, 
                      (SELECT MAX(created_at) FROM nafsiyah_logs WHERE user_id = u.id) AS aktivitas_terakhir,
                      (SELECT COUNT(*) FROM nafsiyah_logs WHERE user_id = u.id AND catatan = 'Udzur Syar\'i' AND log_date = CURDATE()) as is_haid
                      FROM users u ORDER BY u.total_poin DESC LIMIT 10";
    $ringkasan_users = $pdo->query($sql_ringkasan)->fetchAll();

    // Data tambahan untuk insight
    $total_amalan_all_time = $pdo->query("SELECT COUNT(*) FROM nafsiyah_logs WHERE status = 'selesai'")->fetchColumn();
    $avg_points = $pdo->query("SELECT AVG(total_poin) FROM users")->fetchColumn();

} catch (PDOException $e) {
    $error_db = $e->getMessage();
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ];
}

require_once __DIR__ . '/templates/header.php';
?>

<!-- Wrapper Utama -->
<div class="max-w-7xl mx-auto space-y-8 font-sans">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Dashboard Admin</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Monitor aktivitas dan performa pengguna</p>
        </div>
        <div
            class="flex items-center gap-2 px-4 py-2 bg-white rounded-xl border border-slate-100 shadow-sm dark:bg-dark-surface dark:border-slate-700">
            <i class="far fa-calendar-alt text-primary-500"></i>
            <span class="text-sm font-bold text-slate-700 dark:text-slate-300"><?= date('l, d F Y') ?></span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

        <!-- Card 1: Total Pengguna -->
        <div
            class="bg-white rounded-3xl p-6 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2 group hover:shadow-lg transition-all">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Pengguna</p>
                    <div class="text-3xl font-black text-slate-800 dark:text-white"><?= number_format($total_users) ?>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">Akun terdaftar</p>
                </div>
                <div
                    class="w-12 h-12 rounded-2xl bg-primary-50 flex items-center justify-center text-primary-600 dark:bg-primary-900/20 dark:text-primary-400 group-hover:scale-110 transition-transform">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Card 2: Aktif Hari Ini -->
        <div
            class="bg-white rounded-3xl p-6 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2 group hover:shadow-lg transition-all">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Aktif Hari Ini</p>
                    <div class="text-3xl font-black text-slate-800 dark:text-white"><?= $active_users_today ?></div>
                    <p class="text-xs text-green-500 font-bold mt-2 flex items-center gap-1">
                        <i class="fas fa-user-check"></i> Online
                    </p>
                </div>
                <div
                    class="w-12 h-12 rounded-2xl bg-green-50 flex items-center justify-center text-green-600 dark:bg-green-900/20 dark:text-green-400 group-hover:scale-110 transition-transform">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Card 3: Perlu Perhatian -->
        <div
            class="bg-white rounded-3xl p-6 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2 group hover:shadow-lg transition-all relative overflow-hidden">
            <div class="absolute top-0 right-0 w-16 h-16 bg-amber-50 rounded-bl-full -mr-4 -mt-4 dark:bg-amber-900/10">
            </div>
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-2">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Jarang Dikerjakan</p>
                    <i class="fas fa-exclamation-triangle text-amber-500"></i>
                </div>
                <div class="text-lg font-bold text-slate-800 truncate dark:text-white mb-1"
                    title="<?= htmlspecialchars($nama_least_amalan) ?>">
                    <?= htmlspecialchars($nama_least_amalan) ?>
                </div>
                <p class="text-xs text-slate-500 mb-3">Hanya <?= $total_least_amalan ?>x hari ini</p>

                <?php if ($active_users_today > 0): ?>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 dark:bg-slate-700">
                        <div class="bg-amber-500 h-1.5 rounded-full"
                            style="width: <?= min($partisipasi_percentage, 100) ?>%"></div>
                    </div>
                    <p class="text-[10px] text-right text-slate-400 mt-1"><?= number_format($partisipasi_percentage, 1) ?>%
                        Partisipasi</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card 4: Amalan Terpopuler -->
        <div
            class="bg-white rounded-3xl p-6 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2 group hover:shadow-lg transition-all relative overflow-hidden">
            <div
                class="absolute top-0 right-0 w-16 h-16 bg-primary-50 rounded-bl-full -mr-4 -mt-4 dark:bg-primary-900/10">
            </div>
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-2">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Terpopuler</p>
                    <i class="fas fa-fire text-primary-500"></i>
                </div>
                <div class="text-lg font-bold text-slate-800 truncate dark:text-white mb-1"
                    title="<?= htmlspecialchars($nama_top_item) ?>">
                    <?= htmlspecialchars($nama_top_item) ?>
                </div>
                <p class="text-xs text-slate-500 mb-3"><?= $total_top_item ?>x dikerjakan</p>

                <?php if ($active_users_today > 0):
                    $popularity_percentage = ($total_top_item / $active_users_today) * 100;
                    ?>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 dark:bg-slate-700">
                        <div class="bg-primary-500 h-1.5 rounded-full"
                            style="width: <?= min($popularity_percentage, 100) ?>%"></div>
                    </div>
                    <p class="text-[10px] text-right text-slate-400 mt-1"><?= number_format($popularity_percentage, 1) ?>%
                        Popularitas</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Insight Section -->
    <?php if (count($least_amalan_week) > 0): ?>
        <div
            class="bg-gradient-to-r from-amber-50/80 to-orange-50/80 rounded-3xl p-6 border border-amber-100 dark:from-amber-900/10 dark:to-orange-900/10 dark:border-amber-800/30">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <i class="fas fa-lightbulb text-amber-500"></i>
                    Insight Mingguan
                </h3>
                <span
                    class="px-3 py-1 rounded-lg bg-white/60 text-xs font-bold text-amber-700 dark:bg-black/20 dark:text-amber-400 border border-amber-200/50">
                    Perlu Perhatian
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($least_amalan_week as $index => $amalan):
                    $rankColor = $index == 0 ? 'text-amber-600 bg-amber-100' : 'text-primary-600 bg-primary-100';
                    $cardBg = $index == 0 ? 'bg-white border-amber-200 shadow-sm' : 'bg-white/60 border-amber-100';
                    ?>
                    <div class="p-4 rounded-2xl border <?= $cardBg ?> dark:bg-dark-surface dark:border-slate-700">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 dark:text-white mb-1">
                                    <?= htmlspecialchars($amalan['activity_name']) ?>
                                </h4>
                                <p class="text-xs text-slate-500">
                                    <?= $amalan['total'] ?>x dalam 7 hari
                                </p>
                            </div>
                            <span class="text-[10px] font-black <?= $rankColor ?> px-2 py-1 rounded-lg dark:bg-slate-700">
                                #<?= $index + 1 ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="text-xs text-slate-500 mt-4 italic">
                <i class="fas fa-info-circle mr-1"></i> Amalan ini memiliki tingkat pengerjaan terendah dalam seminggu
                terakhir.
            </p>
        </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Activity Chart -->
        <div
            class="lg:col-span-2 bg-white rounded-3xl p-6 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white">Tren Aktivitas</h3>
                    <p class="text-xs text-slate-400">Pengguna aktif 7 hari terakhir</p>
                </div>
                <button class="text-slate-400 hover:text-primary-500 transition-colors">
                    <i class="fas fa-ellipsis-h"></i>
                </button>
            </div>
            <div class="h-64 w-full">
                <canvas id="adminActivityChart"></canvas>
            </div>
        </div>

        <!-- System Logs -->
        <div
            class="bg-white rounded-3xl p-6 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2 flex flex-col">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-slate-800 dark:text-white">Log Sistem</h3>
                <?php if ($system_logs): ?>
                    <form method="POST" onsubmit="return confirm('Hapus semua log?')">
                        <button type="submit" name="delete_all_logs"
                            class="text-xs font-bold text-rose-500 hover:text-rose-600 bg-rose-50 px-3 py-1.5 rounded-lg transition-colors dark:bg-rose-900/20">
                            Bersihkan
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="flex-1 space-y-4 overflow-y-auto max-h-[300px] pr-2 scrollbar-hide">
                <?php if ($system_logs): ?>
                    <?php foreach ($system_logs as $log):
                        $iconClass = 'bg-primary-50 text-primary-500';
                        $icon = 'fa-info';
                        if ($log['tipe'] == 'warning') {
                            $iconClass = 'bg-amber-50 text-amber-500';
                            $icon = 'fa-exclamation';
                        }
                        if ($log['tipe'] == 'error') {
                            $iconClass = 'bg-rose-50 text-rose-500';
                            $icon = 'fa-times';
                        }
                        if ($log['tipe'] == 'success') {
                            $iconClass = 'bg-green-50 text-green-500';
                            $icon = 'fa-check';
                        }
                        ?>
                        <div class="flex gap-3 items-start">
                            <div
                                class="w-8 h-8 rounded-xl flex-shrink-0 flex items-center justify-center text-xs <?= $iconClass ?> dark:bg-slate-700">
                                <i class="fas <?= $icon ?>"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-slate-800 dark:text-white leading-tight">
                                    <?= htmlspecialchars($log['aktivitas']) ?>
                                </p>
                                <span class="text-[10px] text-slate-400 mt-1 block">
                                    <?= date('H:i', strtotime($log['created_at'])) ?> •
                                    <?= date('d M', strtotime($log['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8 text-slate-400">
                        <i class="fas fa-clipboard-check text-4xl mb-2 opacity-30"></i>
                        <p class="text-xs">Tidak ada aktivitas baru</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- User Table Section -->
    <div
        class="bg-white rounded-3xl shadow-soft border border-slate-100 overflow-hidden dark:bg-dark-surface dark:border-dark-surface2">
        <div
            class="p-6 border-b border-slate-100 dark:border-slate-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h3 class="font-bold text-slate-800 dark:text-white text-lg">Top 10 Pengguna</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Berdasarkan total poin tertinggi</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <!-- Export Button -->
                <button onclick="document.getElementById('exportModal').classList.remove('hidden')"
                    class="px-4 py-2 bg-green-50 text-green-600 text-xs font-bold rounded-xl hover:bg-green-100 transition-colors border border-green-200 flex items-center gap-2 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
                    <i class="fas fa-file-excel"></i> Export Data
                </button>

                <!-- Reset Button -->
                <form method="POST"
                    onsubmit="return confirm('Yakin ingin mereset poin bulanan? Tindakan ini tidak bisa dibatalkan.')">
                    <button type="submit" name="reset_monthly_points"
                        class="px-4 py-2 bg-rose-50 text-rose-600 text-xs font-bold rounded-xl hover:bg-rose-100 transition-colors border border-rose-200 flex items-center gap-2 dark:bg-rose-900/20 dark:border-rose-800 dark:text-rose-400">
                        <i class="fas fa-sync-alt"></i> Reset Poin
                    </button>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                <thead
                    class="bg-slate-50 text-xs uppercase font-bold text-slate-400 dark:bg-dark-surface2 dark:text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Pengguna</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-center">Poin</th>
                        <th class="px-6 py-4 text-center">Streak</th>
                        <th class="px-6 py-4">Terakhir Aktif</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php foreach ($ringkasan_users as $user): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors dark:hover:bg-dark-surface2/50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-bold text-xs dark:bg-primary-900/30">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800 dark:text-white">
                                            <?= htmlspecialchars($user['username']) ?></p>
                                        <p class="text-[10px] text-slate-400"><?= htmlspecialchars($user['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($user['is_haid'] > 0): ?>
                                    <span
                                        class="px-2 py-1 rounded-md bg-amber-50 text-amber-600 text-[10px] font-bold border border-amber-100 dark:bg-amber-900/20 dark:border-amber-800">Udzur</span>
                                <?php else: ?>
                                    <span
                                        class="px-2 py-1 rounded-md bg-green-50 text-green-600 text-[10px] font-bold border border-green-100 dark:bg-green-900/20 dark:border-green-800">Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-slate-800 dark:text-white">
                                <?= number_format($user['total_poin']) ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="flex items-center justify-center gap-1 font-bold text-rose-500">
                                    <i class="fas fa-fire text-xs"></i> <?= $user['streak_count'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium">
                                <?= formatWaktuRelatif($user['aktivitas_terakhir']) ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="user_detail.php?id=<?= $user['id'] ?>"
                                    class="text-primary-500 hover:text-primary-700 font-bold text-xs transition-colors">Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="p-4 bg-slate-50 border-t border-slate-100 text-center dark:bg-dark-surface2 dark:border-slate-700">
            <a href="users.php"
                class="text-xs font-bold text-slate-500 hover:text-primary-600 transition-colors flex items-center justify-center gap-2">
                Lihat Semua Pengguna <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>

</div>

<!-- Modal Export Excel (Hidden by Default) -->
<div id="exportModal"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div
        class="bg-white rounded-2xl w-full max-w-sm p-6 shadow-2xl dark:bg-dark-surface border border-slate-100 dark:border-slate-700">
        <h3 class="font-bold text-lg text-slate-800 mb-4 dark:text-white">Export Laporan</h3>
        <form action="export_excel.php" method="GET" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase">Bulan</label>
                <select name="bulan"
                    class="w-full px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 text-sm focus:outline-none focus:border-primary-500 dark:bg-dark-surface2 dark:border-slate-600 dark:text-white">
                    <?php
                    $months = [
                        "01" => "Januari",
                        "02" => "Februari",
                        "03" => "Maret",
                        "04" => "April",
                        "05" => "Mei",
                        "06" => "Juni",
                        "07" => "Juli",
                        "08" => "Agustus",
                        "09" => "September",
                        "10" => "Oktober",
                        "11" => "November",
                        "12" => "Desember"
                    ];
                    foreach ($months as $num => $name) {
                        $selected = ($num == date('m')) ? 'selected' : '';
                        echo "<option value='$num' $selected>$name</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase">Tahun</label>
                <select name="tahun"
                    class="w-full px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 text-sm focus:outline-none focus:border-primary-500 dark:bg-dark-surface2 dark:border-slate-600 dark:text-white">
                    <?php for ($i = date('Y'); $i >= 2020; $i--): ?>
                        <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('exportModal').classList.add('hidden')"
                    class="flex-1 px-4 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-xl hover:bg-slate-200 transition-colors">Batal</button>
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-green-500 text-white text-sm font-bold rounded-xl hover:bg-green-600 transition-colors shadow-lg shadow-green-500/30">Download</button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Toast Notification Handler
    <?php if (isset($_SESSION['toast'])): ?>
        const toast = <?= json_encode($_SESSION['toast']) ?>;
        // Simple vanilla JS toast implementation can be added here or use library
        // alert(toast.message); 
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    // Chart Configuration
    const ctx = document.getElementById('adminActivityChart');
    if (ctx) {
        // Deteksi Dark Mode
        const isDarkMode = document.documentElement.classList.contains('dark');
        const textColor = isDarkMode ? '#94a3b8' : '#64748b';
        const gridColor = isDarkMode ? '#334155' : '#f1f5f9';

        // Gradient Context
        const ctx2d = ctx.getContext('2d');
        const gradient = ctx2d.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(139, 92, 246, 0.5)'); // Primary 500
        gradient.addColorStop(1, 'rgba(139, 92, 246, 0.05)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($admin_chart_labels) ?>,
                datasets: [{
                    label: 'User Aktif',
                    data: <?= json_encode($admin_chart_data) ?>,
                    borderColor: '#8B5CF6',
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
                            stepSize: 1,
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

<?php require_once __DIR__ . '/templates/footer.php'; ?>