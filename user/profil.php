<?php
require_once __DIR__ . '/../koneksi.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$id_user = $_SESSION['user_id'];

// Ambil data profil terbaru
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch();

include 'templates/header.php';
?>

<!-- Clean Minimalist Design -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-white p-4 md:p-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="mb-10 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
                PROFILE <span class="text-purple-600">SETTINGS</span>
            </h1>
            <p class="text-gray-600 max-w-md mx-auto">
                Kelola informasi profil dan keamanan akun Anda
            </p>
        </div>

        <!-- Success/Error Message -->
        <?php if (isset($_GET['status'])): ?>
        <div class="mb-6 max-w-2xl mx-auto animate-fadeIn">
            <div class="p-4 rounded-lg border <?= $_GET['status'] == 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?>">
                <div class="flex items-center">
                    <?php if ($_GET['status'] == 'success'): ?>
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    <?php endif; ?>
                    <span class="font-medium"><?= htmlspecialchars($_GET['msg']) ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Profile Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-4xl mx-auto">
            
            <!-- Left Column - User Info -->
            <div class="lg:col-span-1">
                <!-- Profile Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <!-- Profile Header -->
                    <div class="h-32 bg-gradient-to-r from-purple-600 to-indigo-600 relative">
                        <div class="absolute -bottom-10 left-1/2 transform -translate-x-1/2">
                            <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center text-2xl font-bold text-white shadow-xl border-4 border-white">
                                <?= substr($user['nama_lengkap'], 0, 1) ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile Content -->
                    <div class="pt-12 pb-6 px-6 text-center">
                        <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($user['nama_lengkap']) ?></h2>
                        <p class="text-gray-600 text-sm mt-1">@<?= htmlspecialchars($user['username']) ?></p>
                        
                        <!-- Stats -->
                        <div class="grid grid-cols-2 gap-4 mt-6">
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="text-2xl font-bold text-gray-900"><?= $user['total_poin'] ?></div>
                                <p class="text-xs text-gray-500 mt-1">Total Poin</p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="text-2xl font-bold text-gray-900"><?= $user['streak_harian'] ?></div>
                                <p class="text-xs text-gray-500 mt-1">Day Streak</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Profile Details -->
            <div class="lg:col-span-2">
                <!-- Profile Information Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <!-- Card Header -->
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-900">
                            <span class="text-purple-600">Hera Herlina</span>
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">Informasi Profil</p>
                    </div>
                    
                    <!-- Profile Fields -->
                    <div class="p-6 space-y-6">
                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Lengkap
                            </label>
                            <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <p class="text-gray-900 font-medium"><?= htmlspecialchars($user['nama_lengkap']) ?></p>
                            </div>
                        </div>

                        <!-- Username -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Username
                            </label>
                            <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <p class="text-gray-900 font-medium"><?= htmlspecialchars($user['username']) ?></p>
                            </div>
                        </div>

                        <!-- Email -->
                        <?php if (!empty($user['email'])): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email
                            </label>
                            <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <p class="text-gray-900 font-medium"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Divider -->
                        <div class="pt-6">
                            <!-- Single Button - Change Password -->
                            <button onclick="document.getElementById('modalPW').classList.remove('hidden')"
                                    class="w-full px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold rounded-xl hover:shadow-lg hover:shadow-purple-200 transition-all duration-300 flex items-center justify-center gap-3 group">
                                <svg class="w-5 h-5 group-hover:rotate-12 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                Ubah Password
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Change Password -->
<div id="modalPW" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Ubah Password</h3>
                    <p class="text-gray-500 text-sm">Masukkan password baru Anda</p>
                </div>
                <button onclick="document.getElementById('modalPW').classList.add('hidden')" 
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Modal Form -->
        <form action="update_password.php" method="POST" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Lama</label>
                    <input type="password" name="password_lama" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all"
                           placeholder="••••••••" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                    <input type="password" name="password_baru" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all"
                           placeholder="••••••••" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                    <input type="password" name="konfirmasi_password" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all"
                           placeholder="••••••••" required>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-8">
                <button type="button" 
                        onclick="document.getElementById('modalPW').classList.add('hidden')"
                        class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-3 bg-purple-600 text-white font-medium rounded-xl hover:bg-purple-700 transition-colors">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<?php include 'templates/footer.php'; ?>