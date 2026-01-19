<?php
require_once __DIR__ . '/../koneksi.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Halaman
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$id_user = $_SESSION['user_id'];

// Ambil data profil terbaru
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch();

include 'templates/header.php';
?>

<!-- Container Utama -->
<div class="max-w-5xl mx-auto space-y-8 font-sans">

    <!-- Page Header -->
    <div class="text-center sm:text-left">
        <h1 class="text-2xl font-black text-slate-800 dark:text-white">Pengaturan Profil</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola informasi akun dan keamanan Anda</p>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['status'])): ?>
        <div
            class="p-4 rounded-2xl border flex items-center gap-3 animate-bounce-in <?= $_GET['status'] == 'success' ? 'bg-green-50 border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-800' : 'bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-900/20 dark:border-rose-800' ?>">
            <i class="fas <?= $_GET['status'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-xl"></i>
            <span class="font-bold text-sm"><?= htmlspecialchars($_GET['msg']) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Left Column: Identity Card -->
        <div class="lg:col-span-1">
            <div
                class="bg-white rounded-3xl shadow-soft border border-slate-100 overflow-hidden dark:bg-dark-surface dark:border-dark-surface2 relative group">
                <!-- Cover Decoration -->
                <div class="h-32 bg-gradient-to-r from-primary-500 to-primary-600 relative overflow-hidden">
                    <div class="absolute inset-0 opacity-20"
                        style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 20px 20px;">
                    </div>
                </div>

                <!-- Avatar -->
                <div class="absolute top-16 left-1/2 transform -translate-x-1/2">
                    <div class="w-24 h-24 rounded-full p-1 bg-white dark:bg-dark-surface shadow-lg">
                        <div
                            class="w-full h-full rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-3xl font-black text-white uppercase select-none">
                            <?= substr($user['nama_lengkap'], 0, 1) ?>
                        </div>
                    </div>
                </div>

                <!-- Info -->
                <div class="pt-14 pb-8 px-6 text-center mt-2">
                    <h2 class="text-xl font-bold text-slate-800 dark:text-white">
                        <?= htmlspecialchars($user['nama_lengkap']) ?></h2>
                    <p class="text-sm font-medium text-primary-500">@<?= htmlspecialchars($user['username']) ?></p>

                    <div class="grid grid-cols-2 gap-4 mt-8">
                        <div
                            class="p-3 rounded-2xl bg-slate-50 border border-slate-100 dark:bg-dark-surface2 dark:border-slate-700">
                            <div class="text-xl font-black text-slate-800 dark:text-white">
                                <?= number_format($user['total_poin']) ?></div>
                            <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Poin</div>
                        </div>
                        <div
                            class="p-3 rounded-2xl bg-amber-50 border border-amber-100 dark:bg-amber-900/10 dark:border-amber-900/20">
                            <!-- Menggunakan streak_count agar konsisten dengan header -->
                            <div class="text-xl font-black text-amber-500"><?= $user['streak_count'] ?? 0 ?></div>
                            <div
                                class="text-[10px] uppercase font-bold text-amber-600/60 dark:text-amber-500/60 tracking-wider">
                                Streak</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Edit Form -->
        <div class="lg:col-span-2">
            <div
                class="bg-white rounded-3xl shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2 overflow-hidden">
                <div
                    class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-dark-surface2/50">
                    <h3 class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                        <i class="fas fa-user-cog text-primary-500"></i> Detail Akun
                    </h3>
                </div>

                <div class="p-6 md:p-8 space-y-6">
                    <!-- Read Only Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nama
                                Lengkap</label>
                            <div
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-bold dark:bg-dark-surface2 dark:border-slate-700 dark:text-slate-300">
                                <?= htmlspecialchars($user['nama_lengkap']) ?>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Username</label>
                            <div
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-bold dark:bg-dark-surface2 dark:border-slate-700 dark:text-slate-300">
                                @<?= htmlspecialchars($user['username']) ?>
                            </div>
                        </div>
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Email</label>
                            <div
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-bold dark:bg-dark-surface2 dark:border-slate-700 dark:text-slate-300">
                                <?= !empty($user['email']) ? htmlspecialchars($user['email']) : '<span class="italic text-slate-400">Belum diatur</span>' ?>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100 dark:border-slate-700">
                        <button onclick="document.getElementById('modalPW').classList.remove('hidden')"
                            class="w-full sm:w-auto px-6 py-3 bg-white border-2 border-primary-100 text-primary-600 font-bold rounded-xl hover:bg-primary-50 hover:border-primary-200 transition-all flex items-center justify-center gap-2 dark:bg-dark-surface2 dark:border-primary-900/30 dark:text-primary-400 dark:hover:bg-primary-900/20">
                            <i class="fas fa-lock"></i> Ubah Password
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Change Password -->
<div id="modalPW"
    class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
    <div
        class="bg-white dark:bg-dark-surface w-full max-w-md rounded-3xl shadow-2xl overflow-hidden transform transition-all scale-100 border border-slate-100 dark:border-slate-700">
        <div
            class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-dark-surface2/50">
            <div>
                <h3 class="font-bold text-lg text-slate-800 dark:text-white">Ganti Password</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Amankan akun Anda secara berkala</p>
            </div>
            <button onclick="document.getElementById('modalPW').classList.add('hidden')"
                class="text-slate-400 hover:text-rose-500 transition-colors bg-white dark:bg-dark-surface2 p-2 rounded-full shadow-sm hover:shadow-md">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <form action="update_password.php" method="POST" class="p-6 space-y-4">
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Password
                    Lama</label>
                <input type="password" name="password_lama" required
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:border-primary-500 dark:focus:ring-primary-900"
                    placeholder="••••••••">
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Password
                    Baru</label>
                <input type="password" name="password_baru" required
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:border-primary-500 dark:focus:ring-primary-900"
                    placeholder="••••••••">
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Konfirmasi
                    Password</label>
                <input type="password" name="konfirmasi_password" required
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:border-primary-500 dark:focus:ring-primary-900"
                    placeholder="••••••••">
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="document.getElementById('modalPW').classList.add('hidden')"
                    class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-all dark:bg-dark-surface2 dark:text-slate-400 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 px-4 py-3 bg-primary-600 text-white font-bold rounded-xl hover:bg-primary-700 shadow-lg shadow-primary-500/30 hover:shadow-primary-500/50 transition-all transform hover:-translate-y-0.5">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>