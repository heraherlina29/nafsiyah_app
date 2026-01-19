<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Halaman Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$currentPage = basename($_SERVER['PHP_SELF']);
$userName = htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Administrator');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Nafsiyah</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Konfigurasi Tailwind Kustom (Sama dengan User) -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
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
                            400: '#FBBF24',
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
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #A78BFA; }
        .dark ::-webkit-scrollbar-thumb { background: #334155; }

        /* Styling Sidebar Collapsed */
        .sidebar-collapsed .sidebar-text { display: none; opacity: 0; }
        .sidebar-collapsed .logo-text { display: none; opacity: 0; }
        .sidebar-collapsed .sidebar-header { justify-content: center; padding-left: 0; padding-right: 0; }
        .sidebar-collapsed .menu-link { justify-content: center; padding-left: 0; padding-right: 0; }
        .sidebar-collapsed .menu-icon { margin-right: 0; font-size: 1.25rem; }
        .sidebar-collapsed .menu-arrow { display: none; }
        .sidebar-collapsed .logout-link { justify-content: center; }
        .sidebar-collapsed .logout-text { display: none; }
        .sidebar-collapsed .logout-icon { margin: 0; }
        .sidebar-collapsed .logo-container { justify-content: center; }
    </style>
</head>

<body class="flex min-h-screen relative overflow-x-hidden bg-[#F8FAFC] text-slate-900 transition-colors duration-300 dark:bg-dark-bg dark:text-dark-text font-sans">

    <!-- Overlay Mobile -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/60 z-40 hidden lg:hidden transition-opacity duration-300 backdrop-blur-sm"></div>

    <!-- Sidebar Modern -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 bg-white border-r border-slate-100 z-50 transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen w-72 transition-all duration-300 ease-in-out dark:bg-dark-surface dark:border-dark-surface2 overflow-hidden flex flex-col shadow-soft">
        
        <!-- Tombol Close (Mobile) -->
        <button id="closeSidebarBtn" class="lg:hidden absolute top-4 right-4 p-2 text-slate-400 hover:text-rose-500 transition-colors rounded-lg hover:bg-slate-50 dark:hover:bg-dark-surface2">
            <i class="fas fa-times text-xl"></i>
        </button>

        <!-- 1. Header Sidebar (Logo) -->
        <div class="h-20 flex items-center px-6 sidebar-header transition-all duration-300 flex-shrink-0 border-b border-dashed border-slate-100 dark:border-slate-700">
            <div class="flex items-center gap-3 w-full justify-start transition-all duration-300 logo-container">
                <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center text-white shadow-glow flex-shrink-0">
                    <i class="fas fa-user-shield text-xl"></i>
                </div>
                <div class="logo-text overflow-hidden whitespace-nowrap transition-all duration-300">
                    <h1 class="text-xl font-extrabold text-slate-800 tracking-tight leading-none dark:text-white font-sans">Admin Panel</h1>
                    <p class="text-[10px] font-semibold text-primary-500 tracking-widest uppercase mt-0.5 font-sans">Nafsiyah App</p>
                </div>
            </div>
        </div>

        <!-- Scrollable Content -->
        <div class="flex-1 flex flex-col px-4 pb-4 overflow-y-auto scrollbar-hide pt-6">
            
            <!-- 2. Navigation Menu -->
            <nav class="space-y-1 flex-1 font-sans">
                <?php
                $menus = [
                    ['index.php', 'Dashboard', 'fas fa-chart-pie'],
                    ['user.php', 'Kelola User', 'fas fa-users'],
                    ['admin.php', 'Kelola Admin', 'fas fa-user-tie'],
                    ['nafsiyah.php', 'Master Nafsiyah', 'fas fa-list-check']
                ];

                foreach ($menus as $m):
                    $is_active = ($currentPage == $m[0]);
                    $activeClass = 'bg-primary-600 text-white shadow-soft';
                    $inactiveClass = 'text-slate-500 hover:bg-primary-50 hover:text-primary-700 dark:text-slate-400 dark:hover:bg-dark-surface2 dark:hover:text-white';
                    ?>
                    <a href="<?= $m[0] ?>" class="menu-link flex items-center px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 group relative overflow-hidden whitespace-nowrap <?= $is_active ? $activeClass : $inactiveClass ?>" title="<?= $m[1] ?>">
                        <i class="<?= $m[2] ?> menu-icon w-6 text-center text-lg mr-3 transition-transform group-hover:scale-110 <?= $is_active ? 'text-white' : 'text-slate-400 group-hover:text-primary-600 dark:text-slate-500 dark:group-hover:text-white' ?>"></i>
                        <span class="sidebar-text transition-opacity duration-300 font-sans"><?= $m[1] ?></span>
                        <?php if ($is_active): ?>
                                <i class="fas fa-chevron-right menu-arrow ml-auto text-xs opacity-70"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- 3. Logout Button -->
            <div class="mt-auto">
                <a href="../logout.php" class="logout-link flex items-center px-4 py-3 rounded-xl font-semibold text-sm text-rose-500 bg-rose-50 hover:bg-rose-100 hover:shadow-sm transition-all dark:bg-rose-900/10 dark:hover:bg-rose-900/20 whitespace-nowrap" title="Keluar">
                    <i class="fas fa-sign-out-alt logout-icon w-6 text-center text-lg mr-3"></i>
                    <span class="sidebar-text logout-text">Keluar</span>
                </a>
            </div>

        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 transition-all duration-300">
        
        <!-- Header Topbar -->
        <header class="h-16 sticky top-0 z-30 px-4 md:px-8 flex justify-between items-center bg-white shadow-sm dark:bg-dark-surface dark:border-b dark:border-slate-700">
            <div class="flex items-center gap-4">
                <!-- Mobile Menu Button -->
                <button id="mobileMenuBtn" class="lg:hidden text-slate-500 hover:text-primary-600 p-2 rounded-lg transition-colors bg-slate-50 shadow-sm border border-slate-100 dark:bg-dark-surface2 dark:border-slate-700 dark:text-white">
                    <i class="fas fa-bars text-lg"></i>
                </button>

                <!-- Desktop Sidebar Toggle -->
                <button id="desktopSidebarToggle" class="hidden lg:flex text-slate-400 hover:text-primary-600 p-2 rounded-lg transition-colors hover:bg-slate-50" title="Toggle Sidebar">
                    <i class="fas fa-indent text-xl transition-transform duration-300" id="toggleIcon"></i>
                </button>

                <!-- Page Title -->
                <div class="flex flex-col">
                    <h2 class="font-bold text-slate-800 dark:text-white text-lg font-sans leading-none">
                        <?php
                        $titles = [
                            'index.php' => 'Dashboard',
                            'user.php' => 'Manajemen User',
                            'admin.php' => 'Manajemen Admin',
                            'nafsiyah.php' => 'Master Data'
                        ];
                        echo $titles[$currentPage] ?? 'Admin Area';
                        ?>
                    </h2>
                    <span class="text-[10px] text-slate-400 hidden sm:block">Administrator Panel</span>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Theme Toggle -->
                <button id="headerThemeToggle" class="w-9 h-9 rounded-full bg-slate-50 border border-slate-200 text-slate-500 flex items-center justify-center shadow-sm hover:text-primary-600 hover:shadow-md transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white">
                    <i class="fas fa-moon"></i>
                </button>

                <div class="h-8 w-[1px] bg-slate-200 dark:bg-slate-700 hidden sm:block"></div>

                <!-- Profile Info -->
                <div class="flex items-center gap-3 hidden sm:flex">
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-slate-400 uppercase leading-none mb-0.5 font-sans">Halo Admin,</p>
                        <p class="text-sm font-bold text-slate-800 dark:text-white font-sans"><?= htmlspecialchars($userName) ?></p>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-primary-100 p-0.5 border border-primary-200 dark:bg-primary-900/30 dark:border-primary-800">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=8B5CF6&color=fff&bold=true" class="w-full h-full rounded-full object-cover" alt="Admin">
                    </div>
                </div>
            </div>
        </header>

        <!-- JavaScript Logic -->
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
                let isCollapsed = localStorage.getItem('admin-sidebar-collapsed') === 'true';

                function updateSidebarState() {
                    if (window.innerWidth >= 1024) { 
                        if (isCollapsed) {
                            sidebar.classList.add('w-20', 'sidebar-collapsed');
                            sidebar.classList.remove('w-72');
                            if(toggleIcon) toggleIcon.classList.replace('fa-indent', 'fa-outdent');
                        } else {
                            sidebar.classList.remove('w-20', 'sidebar-collapsed');
                            sidebar.classList.add('w-72');
                            if(toggleIcon) toggleIcon.classList.replace('fa-outdent', 'fa-indent');
                        }
                    }
                }

                if (desktopBtn) {
                    desktopBtn.addEventListener('click', () => {
                        isCollapsed = !isCollapsed;
                        localStorage.setItem('admin-sidebar-collapsed', isCollapsed);
                        updateSidebarState();
                    });
                }

                updateSidebarState();
                window.addEventListener('resize', updateSidebarState);

                // 3. Theme Toggle
                const headerThemeBtn = document.getElementById('headerThemeToggle');
                const htmlEl = document.documentElement;

                function updateThemeIcon() {
                    const isDark = htmlEl.classList.contains('dark');
                    const iconClass = isDark ? 'fas fa-sun' : 'fas fa-moon';
                    if(headerThemeBtn) headerThemeBtn.querySelector('i').className = iconClass;
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

        <main class="p-4 md:p-8 lg:p-10 max-w-7xl mx-auto w-full font-sans">