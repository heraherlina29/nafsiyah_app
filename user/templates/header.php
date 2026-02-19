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

// Ambil Data Sidebar & Topbar (Poin, Streak, & Foto Profil)
$stmt_sidebar = $pdo->prepare("SELECT username, total_poin, streak_count, profile_pic FROM users WHERE id = ?");
$stmt_sidebar->execute([$_SESSION['user_id']]);
$user_sidebar = $stmt_sidebar->fetch();

$nama_tampil = $user_sidebar['username'] ?? 'Pejuang Nafsiyah';
$total_poin_sidebar = $user_sidebar['total_poin'] ?? 0;
$streak_sidebar = $user_sidebar['streak_count'] ?? 0;
$foto_profil = $user_sidebar['profile_pic'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script>
        (function () {
            const theme = document.cookie.split('; ').find(row => row.startsWith('theme='));
            if (theme && theme.split('=')[1] === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <title>Nafsiyah App - Spiritual Journey</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#F5F3FF', 100: '#EDE9FE', 200: '#DDD6FE', 300: '#C4B5FD', 400: '#A78BFA',
                            500: '#8B5CF6', 600: '#7C3AED', 700: '#6D28D9', 800: '#5B21B6', 900: '#4C1D95',
                        },
                        secondary: {
                            50: '#FFFBEB', 100: '#FEF3C7', 400: '#FBBF24', 500: '#F59E0B',
                        },
                        rose: {
                            50: '#FFF1F2', 100: '#FFE4E6', 500: '#F43F5E', 600: '#E11D48',
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
            transition: background-color 0.3s ease;
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

        /* Sidebar State Classes */
        .sidebar-collapsed .sidebar-text,
        .sidebar-collapsed .logo-text,
        .sidebar-collapsed .stat-label,
        .sidebar-collapsed .menu-arrow,
        .sidebar-collapsed .logout-text {
            display: none;
            opacity: 0;
        }

        .sidebar-collapsed .logo-container {
            justify-content: center;
        }

        .sidebar-collapsed .logo-box {
            width: 3.5rem;
            height: 3.5rem;
        }

        .sidebar-collapsed .sidebar-header {
            justify-content: center;
            padding: 0;
        }

        .sidebar-collapsed .stats-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sidebar-collapsed .stat-item {
            flex-direction: column;
            padding: 8px 4px;
            width: 100%;
        }

        .sidebar-collapsed .stat-value {
            font-size: 0.65rem;
        }

        .sidebar-collapsed .stat-icon {
            font-size: 1rem;
            margin-bottom: 2px;
        }

        .sidebar-collapsed .menu-link,
        .sidebar-collapsed .logout-link {
            justify-content: center;
            padding: 0.75rem 0;
        }

        .sidebar-collapsed .menu-icon,
        .sidebar-collapsed .logout-icon {
            margin-right: 0;
            font-size: 1.25rem;
        }

        .sidebar-close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 50;
        }
    </style>
</head>

<body
    class="flex min-h-screen relative overflow-x-hidden bg-[#F8FAFC] text-slate-900 transition-colors duration-300 dark:bg-dark-bg dark:text-dark-text">

    <div id="sidebarOverlay"
        class="fixed inset-0 bg-slate-900/60 z-40 hidden lg:hidden transition-opacity duration-300 backdrop-blur-sm">
    </div>

    <aside id="sidebar"
        class="fixed inset-y-0 left-0 bg-white border-r border-slate-100 z-50 transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen w-72 transition-all duration-300 ease-in-out dark:bg-dark-surface dark:border-dark-surface2 overflow-hidden flex flex-col shadow-soft">

        <button id="closeSidebarBtn"
            class="lg:hidden sidebar-close-btn p-2 text-slate-400 hover:text-rose-500 transition-colors rounded-lg hover:bg-slate-50 dark:hover:bg-dark-surface2">
            <i class="fas fa-times text-xl"></i>
        </button>

        <div class="h-20 flex items-center px-6 sidebar-header transition-all duration-300 flex-shrink-0">
            <div class="flex items-center gap-3 w-full logo-container">
                <div class="logo-box w-12 h-12 rounded-xl flex items-center justify-center shadow-glow overflow-hidden">
                    <img src="../assets/img/logo.png" class="w-full h-full object-contain" />
                </div>
                <div class="logo-text overflow-hidden whitespace-nowrap transition-all duration-300">
                    <h1 class="text-xl font-extrabold text-slate-800 dark:text-white">Nafsiyah App</h1>
                    <p class="text-[10px] font-semibold text-primary-500 tracking-widest uppercase">Spiritual Path</p>
                </div>
            </div>
        </div>

        <div class="flex-1 flex flex-col px-4 pb-4 overflow-y-auto">
            <nav class="space-y-1 flex-1 mt-4">
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
                        class="menu-link flex items-center px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 group whitespace-nowrap <?= $is_active ? $activeClass : $inactiveClass ?>"
                        title="<?= $item[2] ?>">
                        <i
                            class="<?= $item[1] ?> menu-icon w-6 text-center text-lg mr-3 transition-transform group-hover:scale-110 <?= $is_active ? 'text-white' : 'text-slate-400 group-hover:text-primary-600' ?>"></i>
                        <span class="sidebar-text"><?= $item[2] ?></span>
                        <?php if ($is_active): ?>
                            <i class="fas fa-chevron-right menu-arrow ml-auto text-xs opacity-70"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="mt-6 mb-4">
                <div class="stats-container grid grid-cols-2 gap-2 transition-all duration-300">
                    <div
                        class="stat-item bg-slate-50 border border-slate-100 rounded-xl p-2.5 text-center dark:bg-dark-surface2 dark:border-slate-700 transition-all">
                        <i class="fas fa-star text-primary-500 text-sm mb-1 stat-icon block"></i>
                        <p class="stat-label text-[8px] font-bold text-slate-400 uppercase leading-none mb-1">Poin</p>
                        <span
                            class="stat-value text-xs font-black text-slate-700 dark:text-white leading-none"><?= number_format($total_poin_sidebar); ?></span>
                    </div>
                    <div
                        class="stat-item bg-slate-50 border border-slate-100 rounded-xl p-2.5 text-center dark:bg-dark-surface2 dark:border-slate-700 transition-all">
                        <i class="fas fa-fire text-secondary-500 text-sm mb-1 stat-icon block"></i>
                        <p class="stat-label text-[8px] font-bold text-slate-400 uppercase leading-none mb-1">Streak</p>
                        <span
                            class="stat-value text-xs font-black text-slate-700 dark:text-white leading-none"><?= $streak_sidebar; ?></span>
                    </div>
                </div>
            </div>

            <div>
                <a href="../logout.php"
                    class="logout-link flex items-center px-4 py-3 rounded-xl font-semibold text-sm text-rose-500 bg-rose-50 hover:bg-rose-100 transition-all dark:bg-rose-900/10 dark:hover:bg-rose-900/20 whitespace-nowrap"
                    title="Keluar">
                    <i class="fas fa-sign-out-alt logout-icon w-6 text-center text-lg mr-3"></i>
                    <span class="sidebar-text">Keluar</span>
                </a>
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 transition-all duration-300">

        <header
            class="h-16 sticky top-0 z-30 px-4 md:px-8 flex justify-between items-center bg-white backdrop-blur-md shadow-sm dark:bg-dark-bg/90 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn"
                    class="lg:hidden text-slate-500 hover:text-primary-600 p-2 rounded-lg bg-white shadow-sm border border-slate-100 dark:bg-dark-surface dark:border-slate-700 dark:text-white">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <button id="desktopSidebarToggle"
                    class="hidden lg:flex text-slate-400 hover:text-primary-600 p-2 rounded-lg transition-colors hover:bg-white/50"
                    title="Toggle Sidebar">
                    <i class="fas fa-indent text-xl transition-transform duration-300" id="toggleIcon"></i>
                </button>
                <span class="lg:hidden font-bold text-slate-800 dark:text-white text-lg">Nafsiyah</span>
            </div>

            <div class="flex items-center gap-4">
                <button id="headerThemeToggle"
                    class="w-9 h-9 rounded-full bg-white border border-slate-200 text-slate-500 flex items-center justify-center shadow-sm hover:text-primary-600 transition-all dark:bg-dark-surface dark:border-slate-700 dark:text-white">
                    <i class="fas fa-moon"></i>
                </button>

                <div class="h-8 w-[1px] bg-slate-200 dark:bg-slate-700 hidden sm:block"></div>

                <div class="flex items-center gap-3 hidden sm:flex">
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-slate-400 leading-none mb-0.5">Assalamu'alaikum,</p>
                        <p class="text-sm font-bold text-slate-800 dark:text-white">
                            <?= htmlspecialchars($nama_tampil); ?>
                        </p>
                    </div>
                    <div
                        class="w-9 h-9 rounded-full bg-primary-100 p-0.5 border border-primary-200 dark:bg-primary-900/30 dark:border-primary-800 overflow-hidden">
                        <?php
                        $path_foto_fisik = __DIR__ . '/../../uploads/profile/' . $foto_profil;
                        $url_foto = '../uploads/profile/' . $foto_profil;
                        if (!empty($foto_profil) && file_exists($path_foto_fisik)): ?>
                            <img src="<?= $url_foto ?>" class="w-full h-full rounded-full object-cover">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_tampil); ?>&background=8B5CF6&color=fff&bold=true"
                                class="w-full h-full rounded-full">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                const toggleIcon = document.getElementById('toggleIcon');
                const htmlEl = document.documentElement;

                // 1. Sidebar Toggle Logic
                const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';

                function updateSidebarState(collapsed) {
                    if (window.innerWidth >= 1024) {
                        if (collapsed) {
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

                document.getElementById('desktopSidebarToggle')?.addEventListener('click', () => {
                    const newState = !sidebar.classList.contains('sidebar-collapsed');
                    localStorage.setItem('sidebar-collapsed', newState);
                    updateSidebarState(newState);
                });

                // Mobile Actions
                const toggleMobile = () => {
                    sidebar.classList.toggle('-translate-x-full');
                    overlay.classList.toggle('hidden');
                };
                document.getElementById('mobileMenuBtn')?.addEventListener('click', toggleMobile);
                document.getElementById('closeSidebarBtn')?.addEventListener('click', toggleMobile);
                overlay?.addEventListener('click', toggleMobile);

                // 2. Theme Toggle Logic
                const themeBtn = document.getElementById('headerThemeToggle');

                const updateIcon = () => {
                    const isDark = htmlEl.classList.contains('dark');
                    themeBtn.querySelector('i').className = isDark ? 'fas fa-sun' : 'fas fa-moon';
                };

                themeBtn?.addEventListener('click', () => {
                    htmlEl.classList.toggle('dark');
                    const isDark = htmlEl.classList.contains('dark');
                    document.cookie = `theme=${isDark ? 'dark' : 'light'}; path=/; max-age=31536000`;
                    updateIcon();
                });

                // Init states
                updateSidebarState(isCollapsed);
                updateIcon();
            });
        </script>

        <main class="p-4 md:p-8 lg:p-12 max-w-7xl mx-auto w-full">