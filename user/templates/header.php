<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Halaman: Cek session login user
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../login.php');
    exit();
}

// Koneksi ke database
require_once __DIR__ . '/../../koneksi.php';

// Ambil Data Sidebar (Poin & Streak)
$stmt_sidebar = $pdo->prepare("SELECT nama_lengkap, total_poin, streak_count FROM users WHERE id = ?");
$stmt_sidebar->execute([$_SESSION['user_id']]);
$user_sidebar = $stmt_sidebar->fetch();

$nama_tampil = $user_sidebar['nama_lengkap'] ?? $_SESSION['nama_lengkap'] ?? 'Pejuang Nafsiyah';
$total_poin_sidebar = $user_sidebar['total_poin'] ?? 0;
$streak_sidebar = $user_sidebar['streak_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nafsiyah App - Spiritual Journey</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f8fafc; 
        }
    </style>
</head>
<body class="flex min-h-screen relative overflow-x-hidden">

    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/50 z-30 hidden lg:hidden transition-opacity duration-300"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 w-72 bg-white border-r border-slate-200 z-40 transform -translate-x-full lg:translate-x-0 lg:static lg:block transition-all duration-300 ease-in-out shadow-2xl lg:shadow-none">
        <div class="h-full flex flex-col p-8">
            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-100 rotate-3">
                        <i class="fas fa-leaf text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-slate-800 tracking-tighter uppercase italic leading-none">Nafsiyah</h1>
                        <span class="text-[9px] font-bold text-indigo-400 uppercase tracking-[0.3em]">User Panel</span>
                    </div>
                </div>
                <button id="closeSidebarBtn" class="lg:hidden text-slate-400 hover:text-red-500">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <nav class="flex-1 space-y-1">
                <?php
                // Menambahkan Leaderboard ke dalam array menu
                $menu = [
                    ['dashboard.php', 'fas fa-chart-pie', 'Overview'],
                    ['index.php', 'fas fa-list-check', 'Amalan Harian'],
                    ['laporan.php', 'fas fa-history', 'Riwayat Laporan'],
                    ['leaderboard.php', 'fas fa-trophy', 'Leaderboard'], // MENU BARU!
                    ['profil.php', 'fas fa-user-gear', 'Pengaturan'],
                ];

                $current_file = basename($_SERVER['PHP_SELF']);
                foreach ($menu as $item):
                    $is_active = ($current_file == $item[0]);
                ?>
                <a href="<?= $item[0] ?>" class="flex items-center px-4 py-3.5 rounded-xl transition-all duration-200 <?= $is_active ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-400 hover:bg-slate-50 hover:text-indigo-600' ?>">
                    <i class="<?= $item[1] ?> mr-4 text-base w-5 text-center"></i> 
                    <span class="font-bold text-[10px] uppercase tracking-[0.2em]"><?= $item[2] ?></span>
                </a>
                <?php endforeach; ?>
            </nav>

            <div class="mt-auto pt-6 border-t border-slate-100">
                <div class="grid grid-cols-2 gap-2 mb-6 text-center">
                    <div class="p-2 bg-slate-50 rounded-xl border border-slate-100">
                        <p class="text-[8px] font-bold text-slate-400 uppercase mb-1">Poin</p>
                        <p class="text-xs font-black text-indigo-600"><?php echo number_format($total_poin_sidebar); ?></p>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-xl border border-slate-100">
                        <p class="text-[8px] font-bold text-slate-400 uppercase mb-1">Streak</p>
                        <p class="text-xs font-black text-orange-500">🔥 <?php echo $streak_sidebar; ?></p>
                    </div>
                </div>
                <a href="../logout.php" class="flex items-center px-4 py-3 text-red-400 hover:bg-red-50 rounded-xl transition-all font-bold text-[10px] uppercase tracking-[0.2em]">
                    <i class="fas fa-power-off mr-4 text-center w-5"></i> Keluar
                </a>
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-30 px-6 lg:px-10 flex justify-between items-center">
            <div class="flex items-center">
                <button id="mobileMenuBtn" class="lg:hidden mr-4 text-slate-400 hover:text-indigo-600 transition-all p-2 bg-slate-50 rounded-lg">
                    <i class="fas fa-bars-staggered text-lg"></i>
                </button>
                <h2 class="text-xs font-black text-slate-400 tracking-[0.4em] uppercase italic">
                    <?php 
                        if($current_file == 'dashboard.php') echo 'User Workspace';
                        elseif($current_file == 'index.php') echo 'Daily Checklist';
                        elseif($current_file == 'laporan.php') echo 'History Log';
                        elseif($current_file == 'leaderboard.php') echo 'Leaderboard';
                        elseif($current_file == 'profil.php') echo 'Profile Settings';
                        else echo 'Nafsiyah App';
                    ?>
                </h2>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="text-right hidden sm:block">
                    <p class="text-[10px] font-bold text-slate-400 uppercase leading-none mb-1">Selamat Datang,</p>
                    <p class="font-black text-slate-700 text-xs italic uppercase tracking-tighter"><?php echo htmlspecialchars($nama_tampil); ?></p>
                </div>
                <div class="h-10 w-10 rounded-xl bg-slate-50 border border-slate-200 p-0.5 shadow-sm">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nama_tampil); ?>&background=f1f5f9&color=6366f1&bold=true" class="h-full w-full rounded-lg" alt="User">
                </div>
            </div>
        </header>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const sidebar = document.getElementById('sidebar');
                const menuBtn = document.getElementById('mobileMenuBtn');
                const closeBtn = document.getElementById('closeSidebarBtn');
                const overlay = document.getElementById('sidebarOverlay');

                function toggleMenu() {
                    sidebar.classList.toggle('-translate-x-full');
                    overlay.classList.toggle('hidden');
                    document.body.classList.toggle('overflow-hidden');
                }

                menuBtn.addEventListener('click', toggleMenu);
                closeBtn.addEventListener('click', toggleMenu);
                overlay.addEventListener('click', toggleMenu);
            });
        </script>

        <main class="p-6 lg:p-10">