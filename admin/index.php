<?php
require_once __DIR__ . '/../koneksi.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

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
function formatWaktuRelatif($timestamp) {
    if ($timestamp === null) return 'Belum ada aktivitas';
    $waktu = new DateTime($timestamp);
    $sekarang = new DateTime();
    $diff = $sekarang->diff($waktu);
    
    if ($diff->days == 0) {
        if ($diff->h == 0) return 'Baru saja';
        return $waktu->format('H:i');
    } elseif ($diff->days == 1) return 'Kemarin';
    elseif ($diff->days < 7) return $diff->days . ' hari lalu';
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

<!-- CSS Tambahan untuk Admin -->
<style>
:root {
    --admin-primary: #4f46e5;
    --admin-danger: #ef4444;
    --admin-warning: #f59e0b;
    --admin-success: #10b981;
    --admin-dark: #1f2937;
    --admin-light: #f9fafb;
}

.admin-card {
    transition: all 0.3s ease;
    border: 1px solid #e5e7eb;
}

.admin-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-success { background-color: #d1fae5; color: #065f46; }
.badge-warning { background-color: #fef3c7; color: #92400e; }
.badge-danger { background-color: #fee2e2; color: #991b1b; }
.badge-info { background-color: #dbeafe; color: #1e40af; }
.badge-low { background-color: #fef3c7; color: #92400e; }

.table-row-hover:hover {
    background-color: #f9fafb;
}

.chart-container {
    height: 240px;
}

.progress-mini {
    height: 6px;
    border-radius: 3px;
    background-color: #e5e7eb;
    overflow: hidden;
}

.progress-mini-bar {
    height: 100%;
    border-radius: 3px;
    transition: width 0.5s ease;
}

.insight-item {
    padding: 0.75rem;
    border-radius: 0.75rem;
    background-color: #f9fafb;
    border-left: 4px solid transparent;
}

.insight-item.warning {
    border-left-color: #f59e0b;
    background-color: #fffbeb;
}

.insight-item.info {
    border-left-color: #3b82f6;
    background-color: #eff6ff;
}
</style>

<div class="min-h-screen bg-gray-50 px-4 py-6">
    <div class="max-w-7xl mx-auto">
        
        <!-- Header & Breadcrumb -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard Admin</h1>
                    <p class="text-gray-600 mt-1">Monitor aktivitas pengguna Nafsiyah App</p>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span><?= date('l, d F Y') ?></span>
                    <span class="px-2 py-1 bg-gray-100 rounded-full text-xs">Admin</span>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Card 1: Total Pengguna -->
            <div class="admin-card bg-white rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Pengguna</p>
                        <div class="text-3xl font-bold text-gray-900"><?= number_format($total_users) ?></div>
                        <p class="text-xs text-gray-500 mt-2">Registrasi aktif</p>
                    </div>
                    <div class="stat-icon bg-blue-50 text-blue-600">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            
            <!-- Card 2: Aktif Hari Ini -->
            <div class="admin-card bg-white rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Aktif Hari Ini</p>
                        <div class="text-3xl font-bold text-gray-900"><?= $active_users_today ?></div>
                        <p class="text-xs text-gray-500 mt-2">Dari <?= $total_users ?> pengguna</p>
                    </div>
                    <div class="stat-icon bg-green-50 text-green-600">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
            
            <!-- Card 3: AMALAN PALING SEDANG DIKERJAIN (BARU) -->
            <div class="admin-card bg-white rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-600 mb-1">Perlu Perhatian</p>
                        <div class="text-lg font-semibold text-gray-900 truncate" 
                             title="<?= htmlspecialchars($nama_least_amalan) ?>">
                            <?= htmlspecialchars($nama_least_amalan) ?>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            Hanya <?= $total_least_amalan ?> kali dikerjakan hari ini
                        </p>
                        
                        <!-- Progress Bar Partisipasi -->
                        <?php if($active_users_today > 0): ?>
                        <div class="mt-3">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span>Partisipasi:</span>
                                <span class="font-semibold"><?= number_format($partisipasi_percentage, 1) ?>%</span>
                            </div>
                            <div class="progress-mini">
                                <div class="progress-mini-bar bg-yellow-500" 
                                     style="width: <?= min($partisipasi_percentage, 100) ?>%"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-icon bg-yellow-50 text-yellow-600 ml-4 flex-shrink-0">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            
            <!-- Card 4: Amalan Terpopuler -->
            <div class="admin-card bg-white rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-600 mb-1">Amalan Terpopuler</p>
                        <div class="text-lg font-semibold text-gray-900 truncate" 
                             title="<?= htmlspecialchars($nama_top_item) ?>">
                            <?= htmlspecialchars($nama_top_item) ?>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <?= $total_top_item ?> kali dikerjakan hari ini
                        </p>
                        
                        <!-- Progress Bar Popularitas -->
                        <?php if($active_users_today > 0): 
                            $popularity_percentage = ($total_top_item / $active_users_today) * 100;
                        ?>
                        <div class="mt-3">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span>Popularitas:</span>
                                <span class="font-semibold"><?= number_format($popularity_percentage, 1) ?>%</span>
                            </div>
                            <div class="progress-mini">
                                <div class="progress-mini-bar bg-emerald-500" 
                                     style="width: <?= min($popularity_percentage, 100) ?>%"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-icon bg-purple-50 text-purple-600 ml-4 flex-shrink-0">
                        <i class="fas fa-fire"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Insight Section: Amalan yang Perlu Perhatian -->
        <?php if(count($least_amalan_week) > 0): ?>
        <div class="mb-8">
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-2xl p-6 border border-yellow-200">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-lightbulb text-yellow-600 mr-2"></i>
                            Insight: Amalan yang Perlu Perhatian
                        </h3>
                        <p class="text-sm text-gray-600">7 hari terakhir</p>
                    </div>
                    <span class="text-xs font-medium px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                        Prioritas
                    </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach($least_amalan_week as $index => $amalan): 
                        $ranking_class = $index == 0 ? 'warning' : 'info';
                        $ranking_icon = $index == 0 ? 'fa-exclamation-triangle' : 'fa-info-circle';
                    ?>
                    <div class="insight-item <?= $ranking_class ?>">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center mb-1">
                                    <i class="fas <?= $ranking_icon ?> text-sm mr-2 
                                        <?= $index == 0 ? 'text-yellow-600' : 'text-blue-600' ?>"></i>
                                    <span class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($amalan['activity_name']) ?>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600">
                                    <?= $amalan['total'] ?> kali dalam 7 hari
                                </p>
                            </div>
                            <span class="text-xs font-bold 
                                <?= $index == 0 ? 'text-yellow-700' : 'text-blue-700' ?>">
                                #<?= $index + 1 ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-4 text-sm text-gray-600">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                    <span class="font-medium">Saran:</span> Pertimbangkan untuk memberikan motivasi atau reminder khusus untuk amalan-amalan di atas.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Charts & Activity Logs -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Activity Chart -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-6 admin-card">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Aktivitas 7 Hari Terakhir</h3>
                        <p class="text-sm text-gray-600">Pengguna aktif per hari</p>
                    </div>
                    <span class="text-sm text-gray-500"><?= date('M Y') ?></span>
                </div>
                <div class="chart-container">
                    <canvas id="adminActivityChart"></canvas>
                </div>
            </div>
            
            <!-- System Logs -->
            <div class="bg-white rounded-2xl p-6 admin-card">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Log Sistem</h3>
                        <p class="text-sm text-gray-600">Aktivitas terbaru</p>
                    </div>
                    <?php if($system_logs): ?>
                    <form method="POST" onsubmit="return confirm('Hapus semua riwayat aktivitas?')">
                        <button type="submit" name="delete_all_logs" 
                                class="text-xs text-red-600 hover:text-red-800 font-medium">
                            <i class="fas fa-trash-alt mr-1"></i> Bersihkan
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="space-y-3">
                    <?php if($system_logs): ?>
                        <?php foreach ($system_logs as $log): 
                            $badge_class = '';
                            $icon = '';
                            switch($log['tipe']) {
                                case 'warning': 
                                    $badge_class = 'badge-warning'; 
                                    $icon = 'fa-exclamation-triangle';
                                    break;
                                case 'success': 
                                    $badge_class = 'badge-success'; 
                                    $icon = 'fa-check-circle';
                                    break;
                                case 'error': 
                                    $badge_class = 'badge-danger'; 
                                    $icon = 'fa-times-circle';
                                    break;
                                default: 
                                    $badge_class = 'badge-info'; 
                                    $icon = 'fa-info-circle';
                            }
                        ?>
                        <div class="p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-gray-600 mt-1">
                                    <i class="fas <?= $icon ?> text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <p class="text-sm text-gray-800"><?= htmlspecialchars($log['aktivitas']) ?></p>
                                        <span class="text-xs text-gray-500"><?= date('H:i', strtotime($log['created_at'])) ?></span>
                                    </div>
                                    <span class="text-xs <?= $badge_class ?> inline-block mt-2"><?= ucfirst($log['tipe']) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-clipboard-list text-3xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Belum ada log aktivitas</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Summary & Actions -->
        <div class="bg-white rounded-2xl p-6 admin-card mb-8">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Ringkasan Pengguna</h2>
                    <p class="text-gray-600 mt-1">10 pengguna dengan poin tertinggi</p>
                </div>
                
                <div class="flex flex-wrap gap-3">
                    <!-- Export Form -->
                    <form action="export_excel.php" method="GET" class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-xl">
                        <select name="bulan" class="bg-transparent border-none text-sm focus:ring-0 focus:outline-none">
                            <?php
                            $months = ["01"=>"Januari", "02"=>"Februari", "03"=>"Maret", "04"=>"April", 
                                      "05"=>"Mei", "06"=>"Juni", "07"=>"Juli", "08"=>"Agustus", 
                                      "09"=>"September", "10"=>"Oktober", "11"=>"November", "12"=>"Desember"];
                            foreach($months as $num => $name) {
                                $selected = ($num == date('m')) ? 'selected' : '';
                                echo "<option value='$num' $selected>$name</option>";
                            }
                            ?>
                        </select>
                        <select name="tahun" class="bg-transparent border-none text-sm focus:ring-0 focus:outline-none">
                            <?php for($i = date('Y'); $i >= 2020; $i--): ?>
                                <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-file-export mr-2"></i>Export
                        </button>
                    </form>

                    <!-- Reset Points -->
                    <form method="POST" 
                          onsubmit="return confirm('Reset semua poin pengguna ke 0? Pastikan sudah melakukan backup data.')">
                        <button type="submit" 
                                name="reset_monthly_points" 
                                class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>Reset Periode
                        </button>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pengguna
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Poin
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Streak
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aktivitas Terakhir
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($ringkasan_users as $user): ?>
                        <tr class="table-row-hover">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 mr-3">
                                        <i class="fas fa-user text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <a href="user_detail.php?id=<?= $user['id'] ?>" class="hover:text-indigo-600">
                                                <?= htmlspecialchars($user['username']) ?>
                                            </a>
                                        </div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($user['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="<?= $user['is_haid'] > 0 ? 'badge-warning' : 'badge-success' ?> badge">
                                    <?= $user['is_haid'] > 0 ? 'Udzur' : 'Aktif' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900"><?= number_format($user['total_poin']) ?></span>
                                <span class="text-xs text-gray-500">poin</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <i class="fas fa-fire text-orange-500 mr-1"></i>
                                    <span class="text-sm font-medium text-gray-900"><?= $user['streak_count'] ?></span>
                                    <span class="text-xs text-gray-500 ml-1">hari</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= formatWaktuRelatif($user['aktivitas_terakhir']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="user_detail.php?id=<?= $user['id'] ?>" 
                                   class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    <i class="fas fa-eye mr-1"></i>Lihat
                                </a>
                                <a href="#" class="text-gray-600 hover:text-gray-900">
                                    <i class="fas fa-chart-line mr-1"></i>Report
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- View All Link -->
            <div class="mt-6 text-center">
                <a href="users.php" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium">
                    Lihat semua pengguna
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>

        <!-- Quick Stats Footer -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-gray-700">Total Amalan</h4>
                        <div class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($total_amalan_all_time) ?></div>
                    </div>
                    <i class="fas fa-star text-blue-500 text-xl"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-gray-700">Rata-rata Poin</h4>
                        <div class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($avg_points, 0) ?></div>
                    </div>
                    <i class="fas fa-chart-bar text-green-500 text-xl"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-gray-700">Keterlibatan</h4>
                        <?php
                        $engagement_rate = $total_users > 0 ? ($active_users_today / $total_users) * 100 : 0;
                        ?>
                        <div class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($engagement_rate, 1) ?>%</div>
                    </div>
                    <i class="fas fa-heart text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Toast Notification
    <?php if(isset($_SESSION['toast'])): ?>
        const toast = <?= json_encode($_SESSION['toast']) ?>;
        showToast(toast.message, toast.type);
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    // Activity Chart
    const ctx = document.getElementById('adminActivityChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($admin_chart_labels) ?>,
            datasets: [{
                label: 'User Aktif',
                data: <?= json_encode($admin_chart_data) ?>,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.05)',
                borderWidth: 3,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#4f46e5',
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
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: { size: 12 },
                    bodyFont: { size: 14, weight: '600' },
                    padding: 12
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { 
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: { 
                        font: { size: 11 },
                        stepSize: 1
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    // Toast function
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white font-medium transform transition-all duration-300 translate-x-full ${type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'}`;
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-3"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
        }, 10);
        
        setTimeout(() => {
            toast.classList.remove('translate-x-0');
            toast.classList.add('translate-x-full');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 5000);
    }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>