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

// Ambil ID User dari URL
$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header('Location: user.php');
    exit();
}

// 1. Ambil Data Profil User
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();

if (!$u) {
    // Redirect jika user tidak ditemukan
    header('Location: user.php');
    exit();
}

// 2. Ambil Statistik User
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM nafsiyah_logs WHERE user_id = ? AND status = 'selesai'");
$stmt_count->execute([$user_id]);
$total_selesai = $stmt_count->fetchColumn();

// 3. Ambil Riwayat Amalan Terbaru
$stmt_logs = $pdo->prepare("
    SELECT l.*, i.activity_name 
    FROM nafsiyah_logs l 
    JOIN nafsiyah_items i ON l.item_id = i.id 
    WHERE l.user_id = ? 
    ORDER BY l.created_at DESC LIMIT 50
");
$stmt_logs->execute([$user_id]);
$user_logs = $stmt_logs->fetchAll();

require_once __DIR__ . '/templates/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8 font-sans">
    
    <!-- Page Header & Breadcrumb -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="user.php" class="hover:text-primary-600 transition-colors">Kelola User</a>
                <i class="fas fa-chevron-right text-[10px]"></i>
                <span class="text-primary-600">Detail</span>
            </div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Detail Pengguna</h1>
        </div>
        <a href="user.php" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-600 text-sm font-bold rounded-xl hover:bg-slate-50 hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm dark:bg-dark-surface2 dark:border-slate-700 dark:text-slate-300 dark:hover:text-white">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left Column: Profile & Summary Stats -->
        <div class="lg:col-span-4 space-y-6">
            
            <!-- Profile Card -->
            <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2 relative overflow-hidden group">
                <!-- Decorative BG Gradient -->
                <div class="absolute top-0 left-0 right-0 h-24 bg-gradient-to-br from-primary-500 to-primary-600"></div>
                
                <div class="relative z-10 flex flex-col items-center mt-12">
                    <!-- Avatar -->
                    <div class="w-24 h-24 rounded-full p-1 bg-white dark:bg-dark-surface shadow-xl">
                        <div class="w-full h-full rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-3xl font-black text-white uppercase select-none">
                            <?= strtoupper(substr($u['nama_lengkap'] ?? $u['username'], 0, 1)) ?>
                        </div>
                    </div>
                    
                    <h2 class="mt-4 text-xl font-bold text-slate-800 dark:text-white text-center">
                        <?= htmlspecialchars($u['nama_lengkap']) ?>
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium text-center">
                        @<?= htmlspecialchars($u['username']) ?>
                    </p>

                    <div class="mt-4">
                        <?php if (($u['status'] ?? 'aktif') == 'aktif'): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-50 text-green-600 text-xs font-bold border border-green-100 dark:bg-green-900/20 dark:border-green-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Aktif
                                </span>
                        <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-500 text-xs font-bold border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Nonaktif
                                </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="mt-8 space-y-4 border-t border-slate-50 pt-6 dark:border-slate-700/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-primary-500 shadow-sm dark:bg-dark-surface2 dark:border-slate-700">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Email</p>
                            <p class="text-sm font-semibold text-slate-700 truncate dark:text-slate-200"><?= htmlspecialchars($u['email']) ?></p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-primary-500 shadow-sm dark:bg-dark-surface2 dark:border-slate-700">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Bergabung</p>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200"><?= date('d F Y', strtotime($u['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Summary Card -->
            <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2">
                <h3 class="text-sm font-bold text-slate-800 mb-4 dark:text-white uppercase tracking-tight">Statistik Aktivitas</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 rounded-2xl bg-primary-50 border border-primary-100 dark:bg-primary-900/10 dark:border-primary-800/30 text-center">
                        <div class="text-2xl font-black text-primary-600 dark:text-primary-400"><?= number_format($u['total_poin'] ?? 0) ?></div>
                        <div class="text-[10px] uppercase font-bold text-primary-400 mt-1">Total Poin</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-100 dark:bg-rose-900/10 dark:border-rose-900/20 text-center">
                        <div class="text-2xl font-black text-rose-500"><?= $u['streak_count'] ?? 0 ?></div>
                        <div class="text-[10px] uppercase font-bold text-rose-400 mt-1">Streak Hari</div>
                    </div>
                    <div class="col-span-2 p-4 rounded-2xl bg-slate-50 border border-slate-100 dark:bg-dark-surface2 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <div class="text-xl font-black text-slate-700 dark:text-white"><?= number_format($total_selesai) ?></div>
                            <div class="text-[10px] uppercase font-bold text-slate-400">Amalan Selesai</div>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 dark:bg-green-900/20 dark:text-green-400">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Activity History -->
        <div class="lg:col-span-8">
            <div class="bg-white rounded-3xl shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2 overflow-hidden h-full flex flex-col">
                <!-- Card Header -->
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-dark-surface2/50 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-slate-800 dark:text-white text-lg">Riwayat Amalan</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">50 aktivitas ibadah terakhir</p>
                    </div>
                    <button class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-400 flex items-center justify-center hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm dark:bg-dark-surface dark:border-slate-600 dark:text-slate-300 dark:hover:text-primary-400" title="Export Log">
                        <i class="fas fa-file-export text-xs"></i>
                    </button>
                </div>

                <!-- Table -->
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                        <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-400 dark:bg-dark-surface2 dark:text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Aktivitas</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Poin</th>
                                <th class="px-6 py-4 text-right">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <?php if (empty($user_logs)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 mb-4 dark:bg-dark-surface2 dark:text-slate-600">
                                                    <i class="fas fa-history text-2xl"></i>
                                                </div>
                                                <p class="text-slate-500 font-medium dark:text-slate-400">Belum ada aktivitas terekam</p>
                                            </div>
                                        </td>
                                    </tr>
                            <?php else: ?>
                                    <?php foreach ($user_logs as $log): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors dark:hover:bg-dark-surface2/50 group">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 rounded-lg bg-primary-50 flex items-center justify-center text-primary-500 flex-shrink-0 dark:bg-primary-900/20 border border-primary-100 dark:border-primary-800/30">
                                                        <i class="fas fa-check-square text-xs"></i>
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-slate-800 dark:text-white text-sm"><?= htmlspecialchars($log['activity_name']) ?></p>
                                                        <?php if ($log['catatan']): ?>
                                                            <p class="text-xs text-slate-400 italic mt-0.5">"<?= htmlspecialchars($log['catatan']) ?>"</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <?php if ($log['status'] == 'selesai'): ?>
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-green-50 text-green-600 text-[10px] font-bold border border-green-100 dark:bg-green-900/20 dark:border-green-800">
                                                            <i class="fas fa-check"></i> Selesai
                                                        </span>
                                                <?php elseif ($log['status'] == 'sebagian'): ?>
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-600 text-[10px] font-bold border border-amber-100 dark:bg-amber-900/20 dark:border-amber-800">
                                                            <i class="fas fa-hourglass-half"></i> Sebagian
                                                        </span>
                                                <?php else: ?>
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 text-rose-600 text-[10px] font-bold border border-rose-100 dark:bg-rose-900/20 dark:border-rose-800">
                                                            <i class="fas fa-times"></i> Gagal/Absen
                                                        </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="font-bold text-primary-600 dark:text-primary-400">+<?= $log['poin_didapat'] ?></span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="text-xs">
                                                    <p class="font-medium text-slate-700 dark:text-slate-300">
                                                        <?= date('d M Y', strtotime($log['log_date'])) ?>
                                                    </p>
                                                    <p class="text-slate-400">
                                                        <?= date('H:i', strtotime($log['created_at'])) ?>
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>