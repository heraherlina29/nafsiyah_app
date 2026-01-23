<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Halaman: Cek session login user
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Koneksi ke database
require_once __DIR__ . '/../../koneksi.php';

// Ambil Data Sidebar (Poin & Streak)
$stmt_sidebar = $pdo->prepare("SELECT username, total_poin, streak_count FROM users WHERE id = ?");
$stmt_sidebar->execute([$_SESSION['user_id']]);
$user_sidebar = $stmt_sidebar->fetch();

$nama_tampil = $user_sidebar['username'] ?? 'Pejuang Nafsiyah';
$total_poin_sidebar = $user_sidebar['total_poin'] ?? 0;
$streak_sidebar = $user_sidebar['streak_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nafsiyah App - Spiritual Journey</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Konfigurasi Tailwind Kustom -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        // Palet Warna Ungu Modern
                        primary: {
                            50: '#F5F3FF',
                            100: '#EDE9FE',
                            200: '#DDD6FE',
                            300: '#C4B5FD',
                            400: '#A78BFA',
                            500: '#8B5CF6', // Ungu Utama
                            600: '#7C3AED',
                            700: '#6D28D9',
                            800: '#5B21B6',
                            900: '#4C1D95',
                        },
                        secondary: {
                            50: '#FFFBEB',
                            100: '#FEF3C7',
                            400: '#FBBF24', // Kuning/Gold
                            500: '#F59E0B',
                        },
                        rose: {
                            50: '#FFF1F2',
                            100: '#FFE4E6',
                            500: '#F43F5E',
                            600: '#E11D48',
                        },
                        dark: {
                            bg: '#0F172A',
                            surface: '#1E293B',
                            surface2: '#334155',
                            text: '#F8FAFC',
                            textSec: '#94A3B8',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    boxShadow: {
                        'soft': '0 4px 20px -2px rgba(139, 92, 246, 0.1)',
                        'glow': '0 0 15px rgba(139, 92, 246, 0.3)',
                    },
                    transitionProperty: {
                        'width': 'width',
                        'spacing': 'margin, padding',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* Scrollbar Halus */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #A78BFA;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #334155;
        }

        /* Styling Khusus untuk Sidebar Collapsed */
        .sidebar-collapsed .sidebar-text {
            display: none;
            opacity: 0;
        }

        .sidebar-collapsed .logo-container {
            justify-content: center;
        }

        .sidebar-collapsed .logo-text {
            width: 0;
            opacity: 0;
        }

        .sidebar-collapsed .logo-box {
            width: 3.5rem;
            /* lebih besar saat kecil */
            height: 3.5rem;
        }

        .sidebar-collapsed .sidebar-header {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }

        /* Stats saat collapsed */
        .sidebar-collapsed .stats-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sidebar-collapsed .stat-item {
            flex-direction: column;
            padding: 8px 4px;
            justify-content: center;
            width: 100%;
        }

        .sidebar-collapsed .stat-label {
            display: none;
        }

        .sidebar-collapsed .stat-value {
            font-size: 0.65rem;
        }

        .sidebar-collapsed .stat-icon {
            font-size: 1rem;
            margin-bottom: 2px;
        }

        /* Menu saat collapsed */
        .sidebar-collapsed .menu-link {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }

        .sidebar-collapsed .menu-icon {
            margin-right: 0;
            font-size: 1.25rem;
        }

        .sidebar-collapsed .menu-arrow {
            display: none;
        }

        /* Logout saat collapsed */
        .sidebar-collapsed .logout-link {
            justify-content: center;
        }

        .sidebar-collapsed .logout-text {
            display: none;
        }

        .sidebar-collapsed .logout-icon {
            margin: 0;
        }

        /* Close Button Sidebar Mobile */
        .sidebar-close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 50;
        }
    </style>
</head>

<body
    class="flex min-h-screen relative overflow-x-hidden bg-[#F8FAFC] text-slate-900 transition-colors duration-300 dark:bg-dark-bg dark:text-dark-text font-sans">

    <!-- Overlay Mobile -->
    <div id="sidebarOverlay"
        class="fixed inset-0 bg-slate-900/60 z-40 hidden lg:hidden transition-opacity duration-300 backdrop-blur-sm">
    </div>

    <!-- Sidebar Modern -->
    <aside id="sidebar"
        class="fixed inset-y-0 left-0 bg-white border-r border-slate-100 z-50 transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen w-72 transition-all duration-300 ease-in-out dark:bg-dark-surface dark:border-dark-surface2 overflow-hidden flex flex-col shadow-soft">

        <!-- Tombol Close Sidebar (Mobile Only) - Di pojok kanan atas sidebar -->
        <button id="closeSidebarBtn"
            class="lg:hidden sidebar-close-btn p-2 text-slate-400 hover:text-rose-500 transition-colors rounded-lg hover:bg-slate-50 dark:hover:bg-dark-surface2">
            <i class="fas fa-times text-xl"></i>
        </button>

        <!-- 1. Header Sidebar (Logo) -->
        <div class="h-20 flex items-center px-6 sidebar-header transition-all duration-300 flex-shrink-0">
            <div class="flex items-center gap-3 w-full transition-all duration-300 logo-container">

                <!-- Logo Utama -->
                <div
                    class="logo-box w-12 h-12 rounded-xl flex items-center justify-center shadow-glow transition-all duration-300">
                    <img src="../assets/img/logo.png" class="w-full h-full object-contain" />
                </div>

                <!-- Teks Logo -->
                <div class="logo-text overflow-hidden whitespace-nowrap transition-all duration-300">
                    <h1 class="text-xl font-extrabold text-slate-800 dark:text-white">
                        Nafsiyah App
                    </h1>
                    <p class="text-[10px] font-semibold text-primary-500 tracking-widest">
                        Track Your Progress
                    </p>
                </div>
            </div>
        </div>


        <!-- Scrollable Content -->
        <div class="flex-1 flex flex-col px-4 pb-4 overflow-y-auto scrollbar-hide">

            <!-- 2. Navigation Menu -->
            <nav class="space-y-1 flex-1 mt-4 font-sans">
                <?php
                $menu = [
                    ['dashboard.php', 'fas fa-home', 'Dashboard'],
                    ['index.php', 'fas fa-check-square', 'Amalan Harian'],
                    ['laporan.php', 'fas fa-chart-bar', 'Statistik'],
                    ['leaderboard.php', 'fas fa-trophy', 'Leaderboard'],
                    ['profil.php', 'fas fa-cog', 'Pengaturan'],
                ];

                $current_file = basename($_SERVER['PHP_SELF']);

                foreach ($menu as $item):
                    $is_active = ($current_file == $item[0]);
                    $activeClass = 'bg-primary-600 text-white shadow-soft';
                    $inactiveClass = 'text-slate-500 hover:bg-primary-50 hover:text-primary-700 dark:text-slate-400 dark:hover:bg-dark-surface2 dark:hover:text-white';
                    ?>
                    <a href="<?= $item[0] ?>"
                        class="menu-link flex items-center px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 group relative overflow-hidden whitespace-nowrap <?= $is_active ? $activeClass : $inactiveClass ?>"
                        title="<?= $item[2] ?>">
                        <i
                            class="<?= $item[1] ?> menu-icon w-6 text-center text-lg mr-3 transition-transform group-hover:scale-110 <?= $is_active ? 'text-white' : 'text-slate-400 group-hover:text-primary-600 dark:text-slate-500 dark:group-hover:text-white' ?>"></i>
                        <span class="sidebar-text transition-opacity duration-300 font-sans"><?= $item[2] ?></span>
                        <?php if ($is_active): ?>
                            <i class="fas fa-chevron-right menu-arrow ml-auto text-xs opacity-70"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- 4. Stats (Poin & Streak) - Di ATAS Button Keluar -->
            <div class="mt-6 mb-4 font-sans">
                <div class="stats-container grid grid-cols-2 gap-2 transition-all duration-300">
                    <!-- Poin -->
                    <div
                        class="stat-item bg-slate-50 border border-slate-100 rounded-xl p-2.5 text-center dark:bg-dark-surface2 dark:border-slate-700 transition-all">
                        <i class="fas fa-star text-primary-500 text-sm mb-1 stat-icon block"></i>
                        <div>
                            <p
                                class="stat-label text-[8px] font-bold text-slate-400 uppercase leading-none mb-0.5 font-sans">
                                Poin</p>
                            <span
                                class="stat-value text-xs font-black text-slate-700 dark:text-white leading-none font-sans"><?php echo number_format($total_poin_sidebar); ?></span>
                        </div>
                    </div>
                    <!-- Streak -->
                    <div
                        class="stat-item bg-slate-50 border border-slate-100 rounded-xl p-2.5 text-center dark:bg-dark-surface2 dark:border-slate-700 transition-all">
                        <i class="fas fa-fire text-secondary-500 text-sm mb-1 stat-icon block"></i>
                        <div>
                            <p
                                class="stat-label text-[8px] font-bold text-slate-400 uppercase leading-none mb-0.5 font-sans">
                                Streak</p>
                            <span
                                class="stat-value text-xs font-black text-slate-700 dark:text-white leading-none font-sans"><?php echo $streak_sidebar; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Logout Button (Paling Bawah) -->
            <div class="font-sans">
                <a href="../logout.php"
                    class="logout-link flex items-center px-4 py-3 rounded-xl font-semibold text-sm text-rose-500 bg-rose-50 hover:bg-rose-100 hover:shadow-sm transition-all dark:bg-rose-900/10 dark:hover:bg-rose-900/20 whitespace-nowrap"
                    title="Keluar">
                    <i class="fas fa-sign-out-alt logout-icon w-6 text-center text-lg mr-3"></i>
                    <span class="sidebar-text logout-text">Keluar</span>
                </a>
            </div>

        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 transition-all duration-300">

        <!-- Header Topbar (Minimalis - Hanya Toggle Sidebar & Theme) -->
        <header
            class="bg-white h-16 sticky top-0 z-30 px-4 md:px-8 flex justify-between items-center bg-[#F8FAFC]/80 backdrop-blur-md dark:bg-dark-bg/90 border-b border-transparent">
            <div class="flex items-center gap-4">
                <!-- Mobile Menu Button (Burger) -->
                <button id="mobileMenuBtn"
                    class="lg:hidden text-slate-500 hover:text-primary-600 p-2 rounded-lg transition-colors bg-white shadow-sm border border-slate-100 dark:bg-dark-surface dark:border-slate-700 dark:text-white">
                    <i class="fas fa-bars text-lg"></i>
                </button>

                <!-- Desktop Sidebar Toggle (Shrink/Expand Button) -->
                <button id="desktopSidebarToggle"
                    class="hidden lg:flex text-slate-400 hover:text-primary-600 p-2 rounded-lg transition-colors hover:bg-white/50"
                    title="Toggle Sidebar">
                    <i class="fas fa-indent text-xl transition-transform duration-300" id="toggleIcon"></i>
                </button>

                <!-- Page Title (Mobile Only) -->
                <span class="lg:hidden font-bold text-slate-800 dark:text-white text-lg font-sans">Nafsiyah</span>
            </div>

            <div class="flex items-center gap-4">
                <button id="headerThemeToggle"
                    class="w-9 h-9 rounded-full bg-white border border-slate-200 text-slate-500 flex items-center justify-center shadow-sm hover:text-primary-600 hover:shadow-md transition-all dark:bg-dark-surface dark:border-slate-700 dark:text-white">
                    <i class="fas fa-moon"></i>
                </button>

                <div class="h-8 w-[1px] bg-slate-200 dark:bg-slate-700 hidden sm:block"></div>

                <div class="flex items-center gap-3 hidden sm:flex">
                    <div class="text-right">
                        <p
                            class="text-[10px] font-bold text-slate-400 dark:text-slate-500 leading-none mb-0.5">
                            Assalamu'alaikum,
                        </p>
                        <p class="text-sm font-bold text-slate-800 dark:text-white">
                            <?= htmlspecialchars($nama_tampil); ?>
                        </p>
                    </div>

                    <div
                        class="w-9 h-9 rounded-full bg-primary-100 p-0.5 border border-primary-200 dark:bg-primary-900/30 dark:border-primary-800">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_tampil); ?>&background=8B5CF6&color=fff&bold=true"
                            class="w-full h-full rounded-full object-cover" alt="User">
                    </div>
                </div>
            </div>

        </header>

        <!-- Script Logic -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                const toggleIcon = document.getElementById('toggleIcon');

                const mobileBtn = document.getElementById('mobileMenuBtn');
                const closeBtn = document.getElementById('closeSidebarBtn');
                const desktopBtn = document.getElementById('desktopSidebarToggle');

                // 1. Mobile Toggle
                function toggleMobileMenu() {
                    sidebar.classList.toggle('-translate-x-full');
                    overlay.classList.toggle('hidden');
                    document.body.classList.toggle('overflow-hidden');
                }

                if (mobileBtn) mobileBtn.addEventListener('click', toggleMobileMenu);
                if (closeBtn) closeBtn.addEventListener('click', toggleMobileMenu);
                if (overlay) overlay.addEventListener('click', toggleMobileMenu);

                // 2. Desktop Toggle (Collapse)
                // Cek localStorage, default false (expanded)
                let isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';

                function updateSidebarState() {
                    if (window.innerWidth >= 1024) {
                        if (isCollapsed) {
                            sidebar.classList.add('w-20', 'sidebar-collapsed');
                            sidebar.classList.remove('w-72');
                            if (toggleIcon) toggleIcon.classList.replace('fa-indent', 'fa-outdent');
                        } else {
                            sidebar.classList.remove('w-20', 'sidebar-collapsed');
                            sidebar.classList.add('w-72');
                            if (toggleIcon) toggleIcon.classList.replace('fa-outdent', 'fa-indent');
                        }
                    }
                }

                if (desktopBtn) {
                    desktopBtn.addEventListener('click', () => {
                        isCollapsed = !isCollapsed;
                        localStorage.setItem('sidebar-collapsed', isCollapsed);
                        updateSidebarState();
                    });
                }

                // Init Sidebar
                updateSidebarState();
                window.addEventListener('resize', updateSidebarState);

                // 3. Theme Toggle
                const headerThemeBtn = document.getElementById('headerThemeToggle');
                const htmlEl = document.documentElement;

                function updateThemeIcon() {
                    const isDark = htmlEl.classList.contains('dark');
                    const iconClass = isDark ? 'fas fa-sun' : 'fas fa-moon';
                    if (headerThemeBtn) headerThemeBtn.querySelector('i').className = iconClass;
                }

                const themeCookie = document.cookie.split('; ').find(row => row.startsWith('theme='));
                if (themeCookie && themeCookie.split('=')[1] === 'dark') {
                    htmlEl.classList.add('dark');
                }
                updateThemeIcon();

                if (headerThemeBtn) {
                    headerThemeBtn.addEventListener('click', () => {
                        const isDark = htmlEl.classList.contains('dark');
                        if (isDark) {
                            htmlEl.classList.remove('dark');
                            document.cookie = "theme=light; path=/; max-age=31536000";
                        } else {
                            htmlEl.classList.add('dark');
                            document.cookie = "theme=dark; path=/; max-age=31536000";
                        }
                        updateThemeIcon();
                    });
                }
            });
        </script>

        <main class="p-4 md:p-8 lg:p-12 max-w-7xl mx-auto w-full font-sans">