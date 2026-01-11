<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$currentPage = basename($_SERVER['PHP_SELF']);
$userName = htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Administrator');
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Nafsiyah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-purple: #7c3aed;
            --primary-light: #8b5cf6;
            --primary-dark: #5b21b6;
            --accent-teal: #0d9488;
            --accent-pink: #db2777;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-700: #334155;
            --gray-900: #0f172a;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
            color: var(--gray-900);
        }
        
        /* Sidebar */
        .sidebar {
            background: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }
        
        .nav-item {
            transition: all 0.2s ease;
            border-radius: 10px;
        }
        
        .nav-item:hover {
            background-color: #f5f3ff;
            color: var(--primary-purple);
        }
        
        .nav-item.active {
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
        }
        
        .nav-item.active i {
            transform: scale(1.1);
        }
        
        .logo-circle {
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-light) 100%);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
        }
        
        /* Header */
        .main-header {
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .user-avatar {
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-light) 100%);
            color: white;
            font-weight: 600;
        }
        
        .page-title {
            position: relative;
            padding-left: 20px;
        }
        
        .page-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary-purple);
        }
        
        /* Breadcrumb */
        .breadcrumb-item:not(:last-child)::after {
            content: '/';
            margin: 0 8px;
            color: var(--gray-300);
        }
        
        /* Smooth transitions */
        * {
            transition: all 0.2s ease;
        }
        
        /* Card styling */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-200);
        }
        
        /* Button styling */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-light) 100%);
            color: white;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
        }
        
        /* Status indicators */
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        
        .status-online {
            background-color: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }
        
        /* Input styling */
        .input-field {
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .input-field:focus {
            outline: none;
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        
        /* Table styling */
        .table-header {
            background-color: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
        }
        
        .table-row:hover {
            background-color: var(--gray-50);
        }
    </style>
</head>

<body class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-100 fixed h-full lg:block hidden z-40">
        <div class="h-full flex flex-col p-6">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-10 px-2">
                <div class="logo-circle w-10 h-10 rounded-xl flex items-center justify-center">
                    <i class="fas fa-heart text-white"></i>
                </div>
                <div>
                    <span class="text-lg font-bold text-gray-900">Nafsiyah</span>
                    <p class="text-xs text-gray-500 font-medium mt-0.5">Admin Panel</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-2">
                <?php
                $menus = [
                    ['index.php', 'Dashboard', 'fas fa-chart-line'],
                    ['user.php', 'Kelola User', 'fas fa-users'],
                    ['admin.php', 'Kelola Admin', 'fas fa-user-shield'],
                    ['nafsiyah.php', 'Master Nafsiyah', 'fas fa-heart']
                ];

                foreach ($menus as $m):
                    $active = ($currentPage == $m[0]) ? 'active' : '';
                ?>
                <a href="<?= $m[0] ?>" 
                   class="flex items-center px-4 py-3 rounded-lg font-medium text-sm nav-item <?= $active ?>">
                    <div class="w-5 mr-3 text-center">
                        <i class="<?= $m[2] ?>"></i>
                    </div>
                    <span><?= $m[1] ?></span>
                    <?php if ($active): ?>
                    <div class="ml-auto w-2 h-2 rounded-full bg-white"></div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </nav>

            <!-- User Profile & Logout -->
            <div class="mt-auto pt-6 border-t border-gray-100">
                <!-- User Info -->
                <div class="flex items-center gap-3 px-2 py-3 mb-4 rounded-lg bg-gray-50">
                    <div class="user-avatar w-8 h-8 rounded-lg flex items-center justify-center">
                        <?= $userInitial ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate"><?= $userName ?></p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="status-dot status-online"></span>
                            <span class="text-xs text-gray-500">Online</span>
                        </div>
                    </div>
                </div>
                
                <!-- Logout -->
                <a href="../logout.php" 
                   class="flex items-center px-4 py-3 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                    <div class="w-5 mr-3 text-center">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <span class="font-medium">Keluar</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 lg:ml-64 flex flex-col">
        <!-- Header -->
        <header class="h-16 bg-white border-b border-gray-100 sticky top-0 z-30 px-6 flex justify-between items-center">
            <!-- Left: Page Title -->
            <div class="flex items-center gap-4">
                <!-- Mobile Menu Button -->
                <button class="lg:hidden w-9 h-9 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-gray-200">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- Page Title -->
                <div class="page-title">
                    <?php
                    $pageTitles = [
                        'index.php' => 'Dashboard',
                        'user.php' => 'Kelola User',
                        'admin.php' => 'Kelola Admin',
                        'nafsiyah.php' => 'Master Nafsiyah'
                    ];
                    ?>
                    <h1 class="text-lg font-semibold text-gray-900">
                        <?= $pageTitles[$currentPage] ?? 'Admin Panel' ?>
                    </h1>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                        <span>Admin</span>
                        <span>•</span>
                        <span><?= date('d M Y') ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Right: User Info -->
            <div class="flex items-center gap-3">
                <!-- Notifications (Optional) -->
                <button class="w-9 h-9 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-gray-200 relative">
                    <i class="fas fa-bell"></i>
                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
                </button>
                
                <!-- User Profile -->
                <div class="flex items-center gap-3">
                    <div class="hidden md:block text-right">
                        <p class="text-sm font-medium text-gray-900"><?= $userName ?></p>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                    <div class="relative">
                        <div class="user-avatar w-10 h-10 rounded-lg flex items-center justify-center cursor-pointer">
                            <?= $userInitial ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Breadcrumb (Optional) -->
        <div class="px-6 py-3 bg-gray-50 border-b border-gray-100">
            <nav class="flex items-center text-sm">
                <a href="index.php" class="text-gray-600 hover:text-purple-600">Dashboard</a>
                <?php if ($currentPage != 'index.php'): ?>
                <span class="breadcrumb-item">
                    <a href="<?= $currentPage ?>" class="text-gray-900 font-medium"><?= $pageTitles[$currentPage] ?? 'Page' ?></a>
                </span>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 bg-gray-50">
            <!-- Content will be inserted here -->