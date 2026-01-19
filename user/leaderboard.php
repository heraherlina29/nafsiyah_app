<?php
// 1. KONEKSI & SESSION
require_once __DIR__ . '/../koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. PROTEKSI HALAMAN
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// 3. FUNGSI LEVELING (Untuk Tampilan Badge)
function getLevelUser($poin)
{
    if ($poin < 1000)
        return [
            'label' => 'NEWBIE',
            'bg' => 'bg-emerald-100 dark:bg-emerald-900/30',
            'text' => 'text-emerald-600 dark:text-emerald-400',
            'icon' => '🌱'
        ];
    if ($poin < 3000)
        return [
            'label' => 'ISTIQOMAH',
            'bg' => 'bg-blue-100 dark:bg-blue-900/30',
            'text' => 'text-blue-600 dark:text-blue-400',
            'icon' => '⭐'
        ];
    if ($poin < 7000)
        return [
            'label' => 'MUTTAQIN',
            'bg' => 'bg-primary-100 dark:bg-primary-900/30',
            'text' => 'text-primary-600 dark:text-primary-400',
            'icon' => '🌟'
        ];
    return [
        'label' => 'MUROBITUN',
        'bg' => 'bg-amber-100 dark:bg-amber-900/30',
        'text' => 'text-amber-600 dark:text-amber-400',
        'icon' => '👑'
    ];
}

// 4. DATA USER LOGIN
$id_user = $_SESSION['user_id'];
$stmtUser = $pdo->prepare("SELECT username, total_poin, streak_count FROM users WHERE id = ?");
$stmtUser->execute([$id_user]);
$userData = $stmtUser->fetch();

// 5. DATA LEADERBOARD (TOP 10)
try {
    $stmt = $pdo->query("SELECT id, nama_lengkap, username, total_poin, streak_count FROM users ORDER BY total_poin DESC LIMIT 10");
    $top_users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat klasemen: " . $e->getMessage());
}

// 6. RANKING USER SAAT INI
$stmtRank = $pdo->prepare("SELECT COUNT(*) + 1 as ranking FROM users WHERE total_poin > ?");
$stmtRank->execute([$userData['total_poin']]);
$current_rank = $stmtRank->fetch()['ranking'];

// 7. INCLUDE HEADER
require_once __DIR__ . '/templates/header.php';
?>

<!-- Wrapper Utama -->
<div class="max-w-5xl mx-auto space-y-8 font-sans">

    <!-- Header Section (User Stats) -->
    <div class="relative bg-white rounded-3xl p-6 shadow-soft border border-slate-100 overflow-hidden dark:bg-dark-surface dark:border-dark-surface2 transition-all">
        <!-- Decoration -->
        <div class="absolute top-0 right-0 p-3 opacity-10">
            <i class="fas fa-trophy text-9xl text-primary-500 transform rotate-12"></i>
        </div>
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary-400 to-amber-400"></div>

        <div class="flex flex-col md:flex-row justify-between items-center relative z-10 gap-6">
            <div class="flex items-center gap-5 w-full md:w-auto">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-2xl text-white shadow-glow flex-shrink-0">
                    <i class="fas fa-user-astronaut"></i>
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-800 dark:text-white mb-1">
                        <?= htmlspecialchars($userData['username']) ?>
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                        Terus tingkatkan ibadahmu!
                    </p>
                </div>
            </div>

            <!-- Stats Rank & Streak -->
            <div class="flex gap-3 w-full md:w-auto">
                <div class="flex-1 md:flex-none text-center px-6 py-3 bg-slate-50 rounded-2xl border border-slate-100 dark:bg-dark-surface2 dark:border-slate-700">
                    <div class="text-lg font-black text-primary-600 dark:text-primary-400">#<?= $current_rank ?></div>
                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Ranking</div>
                </div>
                <div class="flex-1 md:flex-none text-center px-6 py-3 bg-amber-50 rounded-2xl border border-amber-100 dark:bg-amber-900/10 dark:border-amber-900/20">
                    <div class="text-lg font-black text-amber-500">🔥 <?= $userData['streak_count'] ?></div>
                    <div class="text-[10px] uppercase font-bold text-amber-600/60 dark:text-amber-500/60 tracking-wider">Streak</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tombol Kembali -->
    <div>
        <a href="dashboard.php" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-50 hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm dark:bg-dark-surface dark:border-dark-surface2 dark:text-slate-300 dark:hover:text-white">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <!-- Podium Section (Top 3) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end mb-8">
        
        <?php
        // Helper untuk Podium
        function renderPodiumCard($user, $rank)
        {
            $height = $rank == 1 ? 'h-full md:h-72' : 'h-full md:h-60';
            $order = $rank == 1 ? 'order-1 md:order-2' : ($rank == 2 ? 'order-2 md:order-1' : 'order-3');
            $color = $rank == 1 ? 'amber' : ($rank == 2 ? 'slate' : 'orange'); // orange for bronze approximation
            $bg = $rank == 1 ? 'bg-gradient-to-b from-amber-50 to-white dark:from-amber-900/20 dark:to-dark-surface' :
                ($rank == 2 ? 'bg-gradient-to-b from-slate-50 to-white dark:from-slate-800/50 dark:to-dark-surface' :
                    'bg-gradient-to-b from-orange-50 to-white dark:from-orange-900/20 dark:to-dark-surface');
            $border = "border-{$color}-200 dark:border-{$color}-900/50";
            $text = "text-{$color}-600 dark:text-{$color}-400";
            $crown = $rank == 1 ? '<i class="fas fa-crown text-amber-400 absolute -top-4 left-1/2 transform -translate-x-1/2 text-3xl drop-shadow-md animate-bounce"></i>' : '';

            $avatarColor = $rank == 1 ? 'f59e0b' : ($rank == 2 ? '94a3b8' : 'f97316');
            $username = htmlspecialchars($user['username'] ?? 'User');

            echo "
            <div class='{$order} relative'>
                {$crown}
                <div class='{$bg} border {$border} rounded-3xl p-6 text-center shadow-soft flex flex-col items-center justify-end {$height} transition-transform hover:-translate-y-2 duration-300 relative overflow-hidden group'>
                    <div class='absolute top-0 inset-x-0 h-1 bg-{$color}-400'></div>
                    
                    <div class='relative mb-4'>
                        <div class='w-20 h-20 rounded-full p-1 border-4 border-{$color}-200 dark:border-{$color}-800 shadow-md bg-white dark:bg-dark-surface'>
                            <img src='https://ui-avatars.com/api/?name=" . urlencode($username) . "&background={$avatarColor}&color=fff&bold=true' class='w-full h-full rounded-full object-cover'>
                        </div>
                        <div class='absolute -bottom-3 left-1/2 transform -translate-x-1/2 bg-{$color}-500 text-white text-xs font-black w-7 h-7 flex items-center justify-center rounded-full border-2 border-white dark:border-dark-surface'>
                            {$rank}
                        </div>
                    </div>
                    
                    <h3 class='font-bold text-slate-800 dark:text-white truncate w-full mb-1'>{$username}</h3>
                    <p class='text-xs font-bold {$text} mb-3'>" . number_format($user['total_poin']) . " Poin</p>
                    
                    <div class='flex items-center gap-1 text-[10px] font-semibold text-slate-400 bg-white dark:bg-dark-surface2 px-2 py-1 rounded-lg border border-slate-100 dark:border-slate-700'>
                        <i class='fas fa-fire text-rose-500'></i> {$user['streak_count']} Hari
                    </div>
                </div>
            </div>";
        }

        // Render Podium if data exists
        if (isset($top_users[1]))
            renderPodiumCard($top_users[1], 2); // 2nd Place
        if (isset($top_users[0]))
            renderPodiumCard($top_users[0], 1); // 1st Place
        if (isset($top_users[2]))
            renderPodiumCard($top_users[2], 3); // 3rd Place
        ?>
    </div>

    <!-- Full Leaderboard List -->
    <div class="bg-white rounded-3xl shadow-soft border border-slate-200 dark:bg-dark-surface dark:border-dark-surface2 overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-dark-surface2/50 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 dark:text-white text-lg flex items-center gap-2">
                <i class="fas fa-list-ol text-primary-500"></i> Peringkat Lengkap
            </h3>
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Top 10 Pejuang</span>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-700">
            <?php foreach ($top_users as $index => $user):
                $rank = $index + 1;
                $isMe = ($user['id'] == $id_user);
                $rowClass = $isMe ? 'bg-primary-50/60 dark:bg-primary-900/10' : 'hover:bg-slate-50 dark:hover:bg-dark-surface2';
                $levelInfo = getLevelUser($user['total_poin']);
                ?>
                <div class="flex items-center justify-between p-4 transition-colors <?= $rowClass ?>">
                    <div class="flex items-center gap-4 overflow-hidden">
                        <!-- Rank Number -->
                        <div class="w-8 h-8 flex items-center justify-center font-black text-slate-400 dark:text-slate-500 text-sm flex-shrink-0">
                            <?php if ($rank <= 3): ?>
                                    <i class="fas fa-medal text-lg <?= $rank == 1 ? 'text-amber-400' : ($rank == 2 ? 'text-slate-400' : 'text-orange-400') ?>"></i>
                            <?php else: ?>
                                    #<?= $rank ?>
                            <?php endif; ?>
                        </div>

                        <!-- User Info -->
                        <div class="flex items-center gap-3 min-w-0">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&background=random&color=fff&bold=true" class="w-10 h-10 rounded-full border border-slate-200 dark:border-slate-600 flex-shrink-0">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-800 dark:text-white text-sm truncate">
                                        <?= htmlspecialchars($user['username']) ?>
                                    </span>
                                    <?php if ($isMe): ?>
                                            <span class="text-[10px] font-bold bg-primary-500 text-white px-1.5 py-0.5 rounded">YOU</span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md <?= $levelInfo['bg'] ?> <?= $levelInfo['text'] ?>">
                                        <?= $levelInfo['icon'] ?>     <?= $levelInfo['label'] ?>
                                    </span>
                                    <span class="text-[10px] font-medium text-slate-400 flex items-center gap-1">
                                        <i class="fas fa-fire text-rose-400"></i> <?= $user['streak_count'] ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Points -->
                    <div class="text-right flex-shrink-0">
                        <div class="font-black text-slate-800 dark:text-white text-sm md:text-base">
                            <?= number_format($user['total_poin']) ?>
                        </div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Poin</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Footer Info -->
        <div class="p-4 bg-slate-50 dark:bg-dark-surface2/30 text-center text-xs font-medium text-slate-400 dark:text-slate-500 border-t border-slate-100 dark:border-slate-700">
            Peringkat diperbarui secara real-time berdasarkan aktivitas ibadah.
        </div>
    </div>

</div>

<?php include 'templates/footer.php'; ?>