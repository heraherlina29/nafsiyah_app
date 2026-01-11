<?php
require_once __DIR__ . '/../koneksi.php';
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Proteksi halaman user
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../login.php');
    exit();
}

// Fungsi leveling untuk user
function getLevelUser($poin) {
    if ($poin < 1000) return [
        'label' => 'NEWBIE',
        'color' => 'text-gray-600',
        'bg' => 'bg-gray-100',
        'icon' => '🌱'
    ];
    if ($poin < 3000) return [
        'label' => 'ISTIQOMAH',
        'color' => 'text-blue-600',
        'bg' => 'bg-blue-100',
        'icon' => '⭐'
    ];
    if ($poin < 7000) return [
        'label' => 'MUTTAQIN',
        'color' => 'text-purple-600',
        'bg' => 'bg-purple-100',
        'icon' => '🌟'
    ];
    return [
        'label' => 'MUROBITUN',
        'color' => 'text-yellow-600',
        'bg' => 'bg-yellow-100',
        'icon' => '👑'
    ];
}

// Ambil data user untuk header
$id_user = $_SESSION['user_id'];
$stmtUser = $pdo->prepare("SELECT username, total_poin, streak_count FROM users WHERE id = ?");
$stmtUser->execute([$id_user]);
$userData = $stmtUser->fetch();

// Fungsi leveling untuk header
function getLevel($poin) {
    if ($poin < 1000) return ['lv' => 1, 'nama' => 'Newbie', 'next' => 1000, 'min' => 0];
    if ($poin < 3000) return ['lv' => 2, 'nama' => 'Istiqomah', 'next' => 3000, 'min' => 1000];
    if ($poin < 7000) return ['lv' => 3, 'nama' => 'Muttaqin', 'next' => 7000, 'min' => 3000];
    return ['lv' => 4, 'nama' => 'Murobitun', 'next' => 15000, 'min' => 7000];
}

$level = getLevel($userData['total_poin']);
$progress = (($userData['total_poin'] - $level['min']) / ($level['next'] - $level['min'])) * 100;

// Ambil 10 besar user dengan poin tertinggi
try {
    $stmt = $pdo->query("SELECT nama_lengkap, username, total_poin, streak_count FROM users ORDER BY total_poin DESC LIMIT 10");
    $top_users = $stmt->fetchAll();
} catch (PDOException $e) { 
    die("Gagal memuat klasemen: " . $e->getMessage()); 
}

// Ambil ranking user saat ini
$stmtRank = $pdo->prepare("SELECT COUNT(*) + 1 as ranking FROM users WHERE total_poin > ?");
$stmtRank->execute([$userData['total_poin']]);
$current_rank = $stmtRank->fetch()['ranking'];

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

/* Podium Section */
.podium-section {
    background: white;
    border-radius: 24px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
}

.podium-title {
    text-align: center;
    margin-bottom: 24px;
}

.podium-title h2 {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.podium-title p {
    font-size: 0.875rem;
    color: var(--gray-600);
    font-weight: 600;
}

.podium-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

@media (min-width: 640px) {
    .podium-grid {
        grid-template-columns: repeat(3, 1fr);
        align-items: flex-end;
    }
}

.podium-item {
    text-align: center;
    padding: 20px;
    border-radius: 16px;
    transition: all 0.3s ease;
}

.podium-item:hover {
    transform: translateY(-5px);
}

.podium-avatar {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 16px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.podium-name {
    font-size: 0.875rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 4px;
    line-height: 1.2;
}

.podium-points {
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.podium-streak {
    font-size: 0.7rem;
    color: var(--gray-600);
    font-weight: 600;
}

/* Podium positions */
.podium-2nd {
    background: linear-gradient(135deg, var(--gray-50), var(--gray-100));
}
.podium-2nd .podium-avatar {
    background: linear-gradient(135deg, var(--gray-200), var(--gray-300));
    border: 4px solid white;
}

.podium-1st {
    background: linear-gradient(135deg, var(--secondary-50), var(--secondary-100));
}
.podium-1st .podium-avatar {
    background: linear-gradient(135deg, var(--secondary-400), var(--secondary-500));
    border: 6px solid white;
    width: 100px;
    height: 100px;
    font-size: 2.5rem;
}

.podium-3rd {
    background: linear-gradient(135deg, var(--rose-50), var(--rose-100));
}
.podium-3rd .podium-avatar {
    background: linear-gradient(135deg, var(--rose-200), var(--rose-300));
    border: 4px solid white;
}

/* Leaderboard Card */
.leaderboard-card {
    background: white;
    border-radius: 24px;
    padding: 0;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
    overflow: hidden;
}

.leaderboard-header {
    background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
    padding: 24px;
    border-bottom: 1px solid var(--gray-200);
}

.leaderboard-header h3 {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.leaderboard-header p {
    font-size: 0.875rem;
    color: var(--gray-600);
    font-weight: 600;
}

.leaderboard-list {
    padding: 0;
}

.leaderboard-item {
    display: flex;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-100);
    transition: all 0.3s ease;
}

.leaderboard-item:last-child {
    border-bottom: none;
}

.leaderboard-item:hover {
    background: var(--primary-50);
}

.rank-number {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 800;
    margin-right: 16px;
    flex-shrink: 0;
}

.rank-1 { 
    background: linear-gradient(135deg, var(--secondary-100), var(--secondary-200));
    color: var(--secondary-700);
}
.rank-2 { 
    background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
    color: var(--gray-700);
}
.rank-3 { 
    background: linear-gradient(135deg, var(--rose-100), var(--rose-200));
    color: var(--rose-700);
}
.rank-other { 
    background: var(--gray-50);
    color: var(--gray-600);
}

.user-info-expanded {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.user-name {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--gray-900);
    display: flex;
    align-items: center;
    gap: 8px;
}

.level-badge {
    font-size: 0.65rem;
    padding: 4px 8px;
    border-radius: 8px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: inline-flex;
    align-items: center;
    gap: 2px;
}

.user-streak {
    font-size: 0.75rem;
    color: var(--gray-600);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.user-points {
    text-align: right;
    flex-shrink: 0;
}

.points-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--primary-600);
    margin-bottom: 2px;
}

.points-label {
    font-size: 0.7rem;
    color: var(--gray-600);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Current User Rank */
.current-rank {
    background: linear-gradient(135deg, var(--green-50), var(--green-100));
    border-radius: 16px;
    padding: 20px;
    margin-top: 24px;
    text-align: center;
    border: 2px solid var(--green-200);
}

.current-rank h4 {
    font-size: 1rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.rank-display {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--green-600);
    line-height: 1;
    margin-bottom: 4px;
}

.rank-text {
    font-size: 0.875rem;
    color: var(--gray-600);
    font-weight: 600;
}

/* Footer */
.leaderboard-footer {
    text-align: center;
    padding: 20px;
    color: var(--gray-400);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-top: 1px solid var(--gray-100);
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
</style>

<div class="container">
    <!-- Header User -->
    <div class="user-header fade-in">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-trophy"></i>
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
                <div class="stat-value">#<?= $current_rank ?></div>
                <div class="stat-label">Ranking</div>
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

    <!-- Podium Section -->
    <div class="podium-section fade-in delay-1">
        <div class="podium-title">
            <h2>Klasemen Nafsiyah</h2>
            <p>Peringkat 10 Besar Karyawan Terajin</p>
        </div>
        
        <div class="podium-grid">
            <?php if(count($top_users) >= 2): ?>
            <div class="podium-item podium-2nd">
                <div class="podium-avatar">
                    2
                </div>
                <div class="podium-name">
                    <?= htmlspecialchars($top_users[1]['username']) ?>
                </div>
                <div class="podium-points" style="color: var(--gray-700);">
                    <?= number_format($top_users[1]['total_poin']) ?> PTS
                </div>
                <div class="podium-streak">
                    🔥 <?= $top_users[1]['streak_count'] ?> hari
                </div>
            </div>
            <?php endif; ?>

            <?php if(count($top_users) >= 1): ?>
            <div class="podium-item podium-1st">
                <div class="podium-avatar">
                    1
                </div>
                <div class="podium-name">
                    <?= htmlspecialchars($top_users[0]['username']) ?>
                </div>
                <div class="podium-points" style="color: var(--secondary-700);">
                    <?= number_format($top_users[0]['total_poin']) ?> PTS
                </div>
                <div class="podium-streak">
                    🔥 <?= $top_users[0]['streak_count'] ?> hari
                </div>
            </div>
            <?php endif; ?>

            <?php if(count($top_users) >= 3): ?>
            <div class="podium-item podium-3rd">
                <div class="podium-avatar">
                    3
                </div>
                <div class="podium-name">
                    <?= htmlspecialchars($top_users[2]['username']) ?>
                </div>
                <div class="podium-points" style="color: var(--rose-700);">
                    <?= number_format($top_users[2]['total_poin']) ?> PTS
                </div>
                <div class="podium-streak">
                    🔥 <?= $top_users[2]['streak_count'] ?> hari
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Leaderboard Card -->
    <div class="leaderboard-card fade-in delay-2">
        <div class="leaderboard-header">
            <h3>Leaderboard</h3>
            <p>Peringkat berdasarkan total poin</p>
        </div>
        
        <div class="leaderboard-list">
            <?php foreach ($top_users as $index => $user): ?>
                <?php 
                $userLevel = getLevelUser($user['total_poin']);
                $rankClass = 'rank-other';
                if ($index === 0) $rankClass = 'rank-1';
                elseif ($index === 1) $rankClass = 'rank-2';
                elseif ($index === 2) $rankClass = 'rank-3';
                ?>
                
                <div class="leaderboard-item">
                    <div class="rank-number <?= $rankClass ?>">
                        <?= $index + 1 ?>
                    </div>
                    
                    <div class="user-info-expanded">
                        <div class="user-name">
                            <?= htmlspecialchars($user['nama_lengkap'] ?? $user['username']) ?>
                            <span class="level-badge" style="background: <?= str_replace('bg-', '', $userLevel['bg']) ?>; color: <?= str_replace('text-', '', $userLevel['color']) ?>;">
                                <?= $userLevel['icon'] ?> <?= $userLevel['label'] ?>
                            </span>
                        </div>
                        
                        <div class="user-streak">
                            <i class="fas fa-fire" style="color: var(--rose-500);"></i>
                            Streak <?= $user['streak_count'] ?> hari
                        </div>
                    </div>
                    
                    <div class="user-points">
                        <div class="points-value">
                            <?= number_format($user['total_poin']) ?>
                        </div>
                        <div class="points-label">
                            Poin
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Current User Rank -->
        <div class="current-rank fade-in delay-3">
            <h4>Peringkat Anda Saat Ini</h4>
            <div class="rank-display">
                #<?= $current_rank ?>
            </div>
            <div class="rank-text">
                Dari seluruh peserta
            </div>
        </div>
        
        <div class="leaderboard-footer">
            Update Otomatis • Nafsiyah App <?= date('Y') ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to leaderboard items
    const leaderboardItems = document.querySelectorAll('.leaderboard-item');
    leaderboardItems.forEach(item => {
        item.addEventListener('mouseenter', () => {
            item.style.transform = 'translateY(-2px)';
            item.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.05)';
        });
        item.addEventListener('mouseleave', () => {
            item.style.transform = 'translateY(0)';
            item.style.boxShadow = 'none';
        });
    });
    
    // Add animation to podium items
    const podiumItems = document.querySelectorAll('.podium-item');
    podiumItems.forEach((item, index) => {
        setTimeout(() => {
            item.style.animation = 'fadeIn 0.8s ease-out forwards';
        }, index * 200);
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>