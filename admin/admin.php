<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/../koneksi.php';

// --- 1. KONFIGURASI PAGINATION ---
$halaman_aktif = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit_per_halaman = 10;
$offset = ($halaman_aktif - 1) * $limit_per_halaman;

// --- 2. LOGIKA FILTER DAN PENCARIAN ---
$search_query = $_GET['search'] ?? '';

$base_sql = "FROM admins";
$where_clauses = [];
$params = [];

if (!empty($search_query)) {
    $where_clauses[] = "(username LIKE ?)";
    $params[] = "%$search_query%";
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
$data_sql = "SELECT id, username, created_at " . $base_sql . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit_per_halaman;
$params[] = $offset;

$stmt = $pdo->prepare($data_sql);
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($i + 1, $params[$i], is_int($params[$i]) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$admins = $stmt->fetchAll();

$start_number = ($total_data > 0) ? $offset + 1 : 0;
$end_number = min($offset + $limit_per_halaman, $total_data);
?>

<div class="max-w-7xl mx-auto space-y-8 font-sans">
    
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Kelola Admin</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manajemen akun administrator sistem</p>
        </div>
        <button type="button" id="openModalBtn"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-700 transition-all shadow-lg shadow-primary-500/30">
            <i class="fas fa-plus"></i> Tambah Admin
        </button>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2">
        <form action="admin.php" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-10">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 dark:text-slate-400">Pencarian</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" placeholder="Cari berdasarkan username..." 
                           value="<?= htmlspecialchars($search_query) ?>"
                           class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:ring-primary-900">
                </div>
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="flex-1 px-4 py-3 bg-slate-800 text-white text-sm font-bold rounded-xl hover:bg-slate-700 transition-all dark:bg-slate-700 dark:hover:bg-slate-600">
                    Cari
                </button>
                <a href="admin.php" class="px-4 py-3 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition-all dark:bg-dark-surface2 dark:text-slate-400 dark:hover:bg-slate-700" title="Reset">
                    <i class="fas fa-sync-alt"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-3xl shadow-soft border border-slate-100 overflow-hidden dark:bg-dark-surface dark:border-dark-surface2">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-400 dark:bg-dark-surface2 dark:text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Administrator</th>
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Terdaftar</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php if (empty($admins)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 mb-4 dark:bg-dark-surface2 dark:text-slate-600">
                                            <i class="fas fa-user-shield text-2xl"></i>
                                        </div>
                                        <p class="text-slate-500 font-medium dark:text-slate-400">Data admin tidak ditemukan</p>
                                        <p class="text-xs text-slate-400 mt-1 dark:text-slate-500">Coba ubah kata kunci pencarian</p>
                                    </div>
                                </td>
                            </tr>
                    <?php else: ?>
                            <?php foreach ($admins as $admin): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors dark:hover:bg-dark-surface2/50 group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-bold shadow-sm">
                                                <i class="fas fa-shield-alt"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800 dark:text-white group-hover:text-primary-600 transition-colors">
                                                    <?= htmlspecialchars($admin['username']) ?>
                                                </p>
                                                <p class="text-[10px] text-slate-400">Super Admin</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs text-slate-500 dark:text-slate-400">
                                        #<?= $admin['id'] ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs">
                                            <p class="font-medium text-slate-700 dark:text-slate-300">
                                                <?= date('d M Y', strtotime($admin['created_at'])) ?>
                                            </p>
                                            <p class="text-slate-400">
                                                <?= date('H:i', strtotime($admin['created_at'])) ?> WIB
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" 
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-primary-50 hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm edit-btn dark:bg-dark-surface2 dark:border-slate-700 dark:text-slate-400 dark:hover:text-primary-400"
                                                    data-id="<?= $admin['id'] ?>" 
                                                    data-username="<?= htmlspecialchars($admin['username']) ?>"
                                                    title="Edit">
                                                <i class="fas fa-pen text-xs"></i>
                                            </button>
                                    
                                            <!-- Prevent self-delete usually handled in backend, but UI feedback is good -->
                                            <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                                <button type="button" 
                                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm hapus-btn dark:bg-dark-surface2 dark:border-slate-700 dark:text-slate-400 dark:hover:text-rose-400"
                                                        data-id="<?= $admin['id'] ?>" 
                                                        data-username="<?= htmlspecialchars($admin['username']) ?>"
                                                        title="Hapus">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="w-8 h-8 flex items-center justify-center text-slate-300 cursor-not-allowed" title="Anda sedang login">
                                                    <i class="fas fa-ban text-xs"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="p-4 border-t border-slate-100 bg-slate-50 dark:bg-dark-surface2 dark:border-slate-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-xs text-slate-500 font-medium dark:text-slate-400">
                Menampilkan <span class="font-bold text-slate-700 dark:text-white"><?= $start_number ?></span> - <span class="font-bold text-slate-700 dark:text-white"><?= $end_number ?></span> dari <span class="font-bold text-slate-700 dark:text-white"><?= $total_data ?></span> data
            </p>
            
            <?php if ($total_halaman > 1): ?>
                <div class="flex gap-2">
                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                            <a href="?page=<?= $i ?>&search=<?= urlencode($search_query) ?>" 
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
<div id="dataModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl transform scale-95 transition-transform duration-300 overflow-hidden dark:bg-dark-surface border border-slate-100 dark:border-slate-700" id="modalContent">
        
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-dark-surface2/50">
            <div>
                <h3 id="modalTitle" class="text-lg font-black text-slate-800 dark:text-white">Tambah Admin</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Isi detail akun administrator</p>
            </div>
            <button id="closeModalBtn" class="w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-400 flex items-center justify-center hover:bg-rose-50 hover:text-rose-500 hover:border-rose-200 transition-all dark:bg-dark-surface2 dark:border-slate-600 dark:text-slate-400 dark:hover:text-rose-400">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="dataForm" class="p-6 space-y-5">
            <input type="hidden" name="action" id="formAction" value="tambah">
            <input type="hidden" name="id" id="dataId">
            
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Username <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="username" id="username" required 
                           class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:ring-primary-900"
                           placeholder="username">
                </div>
            </div>
            
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Password</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="password" name="password" id="password" 
                           class="w-full pl-11 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:ring-primary-900"
                           placeholder="••••••••">
                    <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary-500 transition-colors">
                        <i class="far fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
                <p id="passwordHint" class="text-[10px] text-slate-400 italic mt-1 dark:text-slate-500">* Wajib diisi untuk admin baru</p>
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
        document.getElementById('modalTitle').textContent = 'Tambah Admin';
        document.getElementById('formAction').value = 'tambah';
        document.getElementById('dataId').value = '';
        passwordHint.textContent = '* Wajib diisi untuk admin baru';
        passwordHint.className = 'text-[10px] text-rose-500 italic mt-1 font-bold';
        document.getElementById('password').required = true;
        showModal();
    });

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const d = this.dataset;
            document.getElementById('modalTitle').textContent = 'Edit Admin';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('dataId').value = d.id;
            document.getElementById('username').value = d.username;
            
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
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const username = this.dataset.username;

            Swal.fire({
                title: 'Hapus Admin?',
                html: `<p class="text-slate-600 dark:text-slate-400">Anda akan menghapus data:</p>
                       <p class="font-bold text-lg text-slate-800 dark:text-white mt-1 mb-2">${username}</p>
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
                    const fd = new FormData();
                    fd.append('action', 'hapus');
                    fd.append('id', id);

                    fetch('admin_api.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Data admin berhasil dihapus.',
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
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        
        // Show loading state
        Swal.fire({
            title: 'Menyimpan...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); },
            background: document.documentElement.classList.contains('dark') ? '#1E293B' : '#fff',
            color: document.documentElement.classList.contains('dark') ? '#fff' : '#1e293b',
            customClass: { popup: 'rounded-3xl' }
        });
        
        fetch('admin_api.php', { method: 'POST', body: fd })
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