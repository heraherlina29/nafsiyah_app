<?php
require_once __DIR__ . '/../koneksi.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Ambil ID User dari URL
$user_id = $_GET['id'] ?? null;
if (!$user_id) { header('Location: user.php'); exit(); }

// 1. Ambil Data Profil User
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();

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

<div class="px-6 py-6 max-w-7xl mx-auto">
    <div class="mb-6">
        <h2 class="text-xs font-bold tracking-widest text-gray-400 uppercase italic">ADMIN <span class="text-blue-600">WORKSPACE</span></h2>
        <div class="flex items-center gap-4 mt-2">
            <a href="user.php" class="text-gray-400 hover:text-purple-600 transition-colors">
                <i class="fas fa-chevron-left text-xl"></i>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Detail Pengguna</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-3xl font-bold mx-auto mb-4 border-4 border-white shadow-sm">
                        <?= strtoupper(substr($u['nama_lengkap'] ?? $u['username'], 0, 1)) ?>
                    </div>
                    <h2 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($u['nama_lengkap']) ?></h2>
                    <p class="text-sm text-gray-400">@<?= htmlspecialchars($u['username']) ?></p>
                    
                    <?php if (($u['status'] ?? 'aktif') == 'aktif'): ?>
                        <span class="mt-3 inline-block px-3 py-1 text-[10px] font-bold rounded-full bg-green-100 text-green-600 uppercase">Aktif</span>
                    <?php else: ?>
                        <span class="mt-3 inline-block px-3 py-1 text-[10px] font-bold rounded-full bg-orange-100 text-orange-600 uppercase">Udzur</span>
                    <?php endif; ?>
                </div>

                <div class="space-y-3 pt-6 border-t border-gray-50">
                    <div class="flex justify-between items-center px-4 py-3 bg-gray-50 rounded-xl">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-tight">Total Poin</span>
                        <span class="text-sm font-bold text-purple-600"><?= number_format($u['total_poin'] ?? 0) ?> <span class="text-[10px]">pts</span></span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-3 bg-gray-50 rounded-xl">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-tight">Streak</span>
                        <span class="text-sm font-bold text-orange-500 italic">🔥 <?= $u['streak_count'] ?? 0 ?> Hari</span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-3 bg-gray-50 rounded-xl">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-tight">Amalan Selesai</span>
                        <span class="text-sm font-bold text-blue-600"><?= $total_selesai ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Informasi Akun</h3>
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400">
                            <i class="fas fa-envelope text-xs"></i>
                        </div>
                        <div class="text-xs">
                            <p class="text-gray-400">Email Address</p>
                            <p class="font-semibold text-gray-700"><?= htmlspecialchars($u['email']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400">
                            <i class="fas fa-calendar-alt text-xs"></i>
                        </div>
                        <div class="text-xs">
                            <p class="text-gray-400">Bergabung Sejak</p>
                            <p class="font-semibold text-gray-700"><?= date('d F Y', strtotime($u['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-8">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden h-full flex flex-col">
                <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-tight">Riwayat Amalan Terakhir</h3>
                    <button class="text-xs font-bold text-purple-600 hover:text-purple-800">Export PDF</button>
                </div>
                
                <div class="overflow-x-auto flex-grow">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-wider bg-gray-50/50">
                                <th class="px-6 py-4">Amalan / Catatan</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Poin</th>
                                <th class="px-6 py-4 text-right">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php if (empty($user_logs)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-400 italic text-sm">Belum ada aktivitas terekam.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($user_logs as $log): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-800 tracking-tight"><?= htmlspecialchars($log['activity_name']) ?></div>
                                    <div class="text-[11px] text-gray-400 mt-0.5"><?= htmlspecialchars($log['catatan'] ?: 'Tanpa catatan') ?></div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($log['status'] == 'selesai'): ?>
                                        <div class="w-7 h-7 bg-green-50 text-green-500 rounded-full flex items-center justify-center mx-auto shadow-sm border border-green-100">
                                            <i class="fas fa-check text-[10px]"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-7 h-7 bg-red-50 text-red-400 rounded-full flex items-center justify-center mx-auto shadow-sm border border-red-100">
                                            <i class="fas fa-times text-[10px]"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-bold text-blue-600">+<?= $log['poin_didapat'] ?></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="text-xs font-bold text-gray-700"><?= date('d M Y', strtotime($log['log_date'])) ?></div>
                                    <div class="text-[10px] text-gray-400 mt-1"><?= date('H:i', strtotime($log['created_at'])) ?> WIB</div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-gray-50/50 border-t border-gray-50">
                     <p class="text-[10px] text-center text-gray-400 font-medium uppercase tracking-widest italic">Hanya menampilkan 50 aktivitas terakhir pengguna</p>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>