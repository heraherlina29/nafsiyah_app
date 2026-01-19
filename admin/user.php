<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/../koneksi.php';

// --- 1. KONFIGURASI PAGINATION ---
$halaman_aktif = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit_per_halaman = 10;
$offset = ($halaman_aktif - 1) * $limit_per_halaman;

// --- 2. LOGIKA FILTER DAN PENCARIAN ---
$search_query = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$base_sql = "FROM users";
$where_clauses = [];
$params = [];

if (!empty($search_query)) {
    $where_clauses[] = "(nama_lengkap LIKE ? OR username LIKE ? OR email LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}
if (!empty($status_filter)) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
}
if (!empty($where_clauses)) {
    $base_sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Hitung total data
$total_sql = "SELECT COUNT(*) " . $base_sql;
$stmt_total = $pdo->prepare($total_sql);
$stmt_total->execute($params);
$total_data = $stmt_total->fetchColumn();
$total_halaman = ceil($total_data / $limit_per_halaman);

// --- 3. QUERY UTAMA AMBIL DATA ---
$data_sql = "SELECT id, nama_lengkap, email, username, status, created_at " . $base_sql . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit_per_halaman;
$params[] = $offset;

$stmt = $pdo->prepare($data_sql);
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($i + 1, $params[$i], is_int($params[$i]) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$users = $stmt->fetchAll();

$start_number = ($total_data > 0) ? $offset + 1 : 0;
$end_number = min($offset + $limit_per_halaman, $total_data);
?>

<div class="max-w-7xl mx-auto space-y-8 font-sans">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Kelola Pengguna</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manajemen akun dan data pengguna aplikasi</p>
        </div>
        <button type="button" id="openModalBtn"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-700 transition-all shadow-lg shadow-primary-500/30">
            <i class="fas fa-plus"></i> Tambah Pengguna
        </button>
    </div>

    <!-- Filter Section -->
    <div
        class="bg-white rounded-3xl p-6 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2">
        <form action="user.php" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-6">
                <label
                    class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 dark:text-slate-400">Pencarian</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" placeholder="Nama, email, atau username..."
                        value="<?= htmlspecialchars($search_query) ?>"
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:ring-primary-900">
                </div>
            </div>
            <div class="md:col-span-4">
                <label
                    class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 dark:text-slate-400">Status
                    Akun</label>
                <select name="status"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:ring-primary-900">
                    <option value="">Semua Status</option>
                    <option value="aktif" <?= $status_filter == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="tidak_aktif" <?= $status_filter == 'tidak_aktif' ? 'selected' : '' ?>>Tidak Aktif
                    </option>
                </select>
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button type="submit"
                    class="flex-1 px-4 py-3 bg-slate-800 text-white text-sm font-bold rounded-xl hover:bg-slate-700 transition-all dark:bg-slate-700 dark:hover:bg-slate-600">
                    Filter
                </button>
                <a href="user.php"
                    class="px-4 py-3 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition-all dark:bg-dark-surface2 dark:text-slate-400 dark:hover:bg-slate-700"
                    title="Reset">
                    <i class="fas fa-sync-alt"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div
        class="bg-white rounded-3xl shadow-soft border border-slate-100 overflow-hidden dark:bg-dark-surface dark:border-dark-surface2">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                <thead
                    class="bg-slate-50 text-xs uppercase font-bold text-slate-400 dark:bg-dark-surface2 dark:text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Pengguna</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4">Username</th>
                        <th class="px-6 py-4">Bergabung</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div
                                        class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 mb-4 dark:bg-dark-surface2 dark:text-slate-600">
                                        <i class="fas fa-users-slash text-2xl"></i>
                                    </div>
                                    <p class="text-slate-500 font-medium dark:text-slate-400">Data pengguna tidak ditemukan
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1 dark:text-slate-500">Coba ubah kata kunci
                                        pencarian atau filter</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors dark:hover:bg-dark-surface2/50 group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-bold shadow-sm">
                                            <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p
                                                class="font-bold text-slate-800 dark:text-white group-hover:text-primary-600 transition-colors">
                                                <?= htmlspecialchars($user['nama_lengkap']) ?></p>
                                            <p class="text-[10px] text-slate-400"><?= htmlspecialchars($user['email']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($user['status'] == 'aktif'): ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-green-50 text-green-600 text-[10px] font-bold border border-green-100 dark:bg-green-900/20 dark:border-green-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Aktif
                                        </span>
                                    <?php else: ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 text-[10px] font-bold border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Nonaktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-700 dark:text-slate-300">
                                    @<?= htmlspecialchars($user['username']) ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    <?= date('d M Y', strtotime($user['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Tombol Lihat Detail (Ikon Mata) -->
                                        <a href="user_detail.php?id=<?= $user['id'] ?>"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm dark:bg-dark-surface2 dark:border-slate-700 dark:text-slate-400 dark:hover:text-blue-400"
                                            title="Lihat Detail">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>

                                        <!-- Tombol Edit -->
                                        <button type="button"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-primary-50 hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm edit-btn dark:bg-dark-surface2 dark:border-slate-700 dark:text-slate-400 dark:hover:text-primary-400"
                                            data-id="<?= $user['id'] ?>" data-nama="<?= $user['nama_lengkap'] ?>"
                                            data-email="<?= $user['email'] ?>" data-username="<?= $user['username'] ?>"
                                            data-status="<?= $user['status'] ?>" title="Edit">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>

                                        <!-- Tombol Hapus -->
                                        <button type="button"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm hapus-btn dark:bg-dark-surface2 dark:border-slate-700 dark:text-slate-400 dark:hover:text-rose-400"
                                            data-id="<?= $user['id'] ?>" data-nama="<?= $user['nama_lengkap'] ?>" title="Hapus">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div
            class="p-4 border-t border-slate-100 bg-slate-50 dark:bg-dark-surface2 dark:border-slate-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-xs text-slate-500 font-medium dark:text-slate-400">
                Menampilkan <span class="font-bold text-slate-700 dark:text-white"><?= $start_number ?></span> - <span
                    class="font-bold text-slate-700 dark:text-white"><?= $end_number ?></span> dari <span
                    class="font-bold text-slate-700 dark:text-white"><?= $total_data ?></span> data
            </p>

            <?php if ($total_halaman > 1): ?>
                <div class="flex gap-2">
                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search_query) ?>&status=<?= $status_filter ?>"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-all <?= $i == $halaman_aktif ? 'bg-primary-600 text-white shadow-md shadow-primary-500/30' : 'bg-white text-slate-500 border border-slate-200 hover:border-primary-300 hover:text-primary-600 dark:bg-dark-surface dark:border-slate-600 dark:text-slate-400' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div id="dataModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl transform scale-95 transition-transform duration-300 overflow-hidden dark:bg-dark-surface border border-slate-100 dark:border-slate-700"
        id="modalContent">

        <!-- Modal Header -->
        <div
            class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-dark-surface2/50">
            <div>
                <h3 id="modalTitle" class="text-lg font-black text-slate-800 dark:text-white">Tambah Pengguna</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Isi detail informasi pengguna</p>
            </div>
            <button id="closeModalBtn"
                class="w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-400 flex items-center justify-center hover:bg-rose-50 hover:text-rose-500 hover:border-rose-200 transition-all dark:bg-dark-surface2 dark:border-slate-600 dark:text-slate-400 dark:hover:text-rose-400">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="dataForm" class="p-6 space-y-5">
            <input type="hidden" name="action" id="formAction" value="tambah">
            <input type="hidden" name="id" id="dataId">

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Nama
                    Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_lengkap" id="nama_lengkap" required
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:ring-primary-900"
                    placeholder="Contoh: Ahmad Fulani">
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Email <span
                        class="text-rose-500">*</span></label>
                <input type="email" name="email" id="email" required
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:ring-primary-900"
                    placeholder="email@example.com">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label
                        class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Username
                        <span class="text-rose-500">*</span></label>
                    <input type="text" name="username" id="username" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:ring-primary-900"
                        placeholder="username">
                </div>
                <div class="space-y-2">
                    <label
                        class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Status</label>
                    <select name="status" id="status_user"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:ring-primary-900">
                        <option value="aktif">Aktif</option>
                        <option value="tidak_aktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="space-y-2">
                <label
                    class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:ring-primary-900"
                        placeholder="••••••••">
                    <button type="button" onclick="togglePassword()"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary-500 transition-colors">
                        <i class="far fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
                <p id="passwordHint" class="text-[10px] text-slate-400 italic mt-1 dark:text-slate-500">* Wajib diisi
                    untuk pengguna baru</p>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" id="cancelModalBtn"
                    class="flex-1 px-4 py-3 bg-white border border-slate-200 text-slate-600 text-sm font-bold rounded-xl hover:bg-slate-50 transition-all dark:bg-dark-surface2 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit"
                    class="flex-[2] px-4 py-3 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-700 shadow-lg shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
                    <i class="fas fa-save mr-2"></i> Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Toggle Password Visibility
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'far fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'far fa-eye';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('dataModal');
        const modalContent = document.getElementById('modalContent');
        const openModalBtn = document.getElementById('openModalBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const cancelModalBtn = document.getElementById('cancelModalBtn');
        const form = document.getElementById('dataForm');
        const passwordHint = document.getElementById('passwordHint');

        const showModal = () => {
            modal.classList.remove('hidden');
            // Small delay to allow display:block to apply before opacity transition
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 10);
            document.body.style.overflow = 'hidden';
        };

        const hideModal = () => {
            modal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        };

        openModalBtn.addEventListener('click', () => {
            form.reset();
            document.getElementById('modalTitle').textContent = 'Tambah Pengguna';
            document.getElementById('formAction').value = 'tambah';
            document.getElementById('dataId').value = '';
            passwordHint.textContent = '* Wajib diisi untuk pengguna baru';
            passwordHint.className = 'text-[10px] text-rose-500 italic mt-1 font-bold';
            document.getElementById('password').required = true;
            showModal();
        });

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const d = this.dataset;
                document.getElementById('modalTitle').textContent = 'Edit Pengguna';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('dataId').value = d.id;
                document.getElementById('nama_lengkap').value = d.nama;
                document.getElementById('email').value = d.email;
                document.getElementById('username').value = d.username;
                document.getElementById('status_user').value = d.status;

                passwordHint.textContent = '* Kosongkan jika tidak ingin mengubah password';
                passwordHint.className = 'text-[10px] text-slate-400 italic mt-1 dark:text-slate-500';
                document.getElementById('password').required = false;
                showModal();
            });
        });

        closeModalBtn.addEventListener('click', hideModal);
        cancelModalBtn.addEventListener('click', hideModal);
        modal.addEventListener('click', (e) => { if (e.target === modal) hideModal(); });

        // Handle Delete
        document.querySelectorAll('.hapus-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const nama = this.dataset.nama;

                Swal.fire({
                    title: 'Hapus Pengguna?',
                    html: `<p class="text-slate-600 dark:text-slate-400">Anda akan menghapus data:</p>
                       <p class="font-bold text-lg text-slate-800 dark:text-white mt-1 mb-2">${nama}</p>
                       <p class="text-xs text-rose-500">Tindakan ini tidak dapat dibatalkan!</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#F43F5E', // Rose-500
                    cancelButtonColor: '#94A3B8', // Slate-400
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    background: document.documentElement.classList.contains('dark') ? '#1E293B' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#1e293b',
                    customClass: {
                        popup: 'rounded-3xl',
                        confirmButton: 'rounded-xl px-6 py-2.5 font-bold',
                        cancelButton: 'rounded-xl px-6 py-2.5 font-bold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Memproses...',
                            text: 'Mohon tunggu sebentar',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); },
                            background: document.documentElement.classList.contains('dark') ? '#1E293B' : '#fff',
                            color: document.documentElement.classList.contains('dark') ? '#fff' : '#1e293b',
                            customClass: { popup: 'rounded-3xl' }
                        });

                        const fd = new FormData();
                        fd.append('action', 'hapus');
                        fd.append('id', id);

                        fetch('user_api.php', { method: 'POST', body: fd })
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire({
                                        title: 'Berhasil!',
                                        text: 'Data pengguna berhasil dihapus.',
                                        icon: 'success',
                                        confirmButtonColor: '#8B5CF6',
                                        confirmButtonText: 'OK',
                                        background: document.documentElement.classList.contains('dark') ? '#1E293B' : '#fff',
                                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#1e293b',
                                        customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl font-bold' }
                                    }).then(() => location.reload());
                                } else {
                                    throw new Error(data.message);
                                }
                            })
                            .catch(err => {
                                Swal.fire({
                                    title: 'Gagal!',
                                    text: err.message || 'Terjadi kesalahan server.',
                                    icon: 'error',
                                    confirmButtonColor: '#8B5CF6',
                                    background: document.documentElement.classList.contains('dark') ? '#1E293B' : '#fff',
                                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#1e293b',
                                    customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl font-bold' }
                                });
                            });
                    }
                });
            });
        });

        // Handle Submit Form
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const fd = new FormData(this);

            Swal.fire({
                title: 'Menyimpan...',
                text: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); },
                background: document.documentElement.classList.contains('dark') ? '#1E293B' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#1e293b',
                customClass: { popup: 'rounded-3xl' }
            });

            fetch('user_api.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        hideModal();
                        Swal.fire({
                            title: 'Berhasil!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#8B5CF6',
                            confirmButtonText: 'OK',
                            background: document.documentElement.classList.contains('dark') ? '#1E293B' : '#fff',
                            color: document.documentElement.classList.contains('dark') ? '#fff' : '#1e293b',
                            customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl font-bold' }
                        }).then(() => location.reload());
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(err => {
                    Swal.fire({
                        title: 'Gagal!',
                        text: err.message || 'Terjadi kesalahan saat menyimpan data.',
                        icon: 'error',
                        confirmButtonColor: '#8B5CF6',
                        background: document.documentElement.classList.contains('dark') ? '#1E293B' : '#fff',
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#1e293b',
                        customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl font-bold' }
                    });
                });
        });
    });
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>