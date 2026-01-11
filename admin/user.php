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

<!-- Tambahkan CSS inline untuk tema ungu -->
<style>
    :root {
        --primary-purple: #7c3aed;
        --primary-purple-light: #8b5cf6;
        --primary-purple-dark: #5b21b6;
        --light-purple: #f5f3ff;
        --accent-teal: #0d9488;
        --accent-pink: #db2777;
        --gray-light: #f8fafc;
        --gray-border: #e2e8f0;
        --text-dark: #1e293b;
        --text-light: #64748b;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-purple-light) 100%);
        color: white;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, var(--primary-purple-dark) 0%, var(--primary-purple) 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(124, 58, 237, 0.3);
    }
    
    .btn-secondary {
        background: var(--gray-light);
        color: var(--text-light);
        border: 1px solid var(--gray-border);
    }
    
    .btn-secondary:hover {
        background: white;
        color: var(--primary-purple);
        border-color: var(--primary-purple);
    }
    
    .status-active {
        background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        color: white;
    }
    
    .status-inactive {
        background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        color: white;
    }
    
    .table-header {
        background: var(--light-purple);
        color: var(--primary-purple);
    }
    
    .table-row:hover {
        background: var(--light-purple);
    }
    
    .avatar-purple {
        background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-purple-light) 100%);
        color: white;
    }
    
    .input-focus:focus {
        border-color: var(--primary-purple);
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }
    
    .pagination-active {
        background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-purple-light) 100%);
        color: white;
    }
</style>

<div class="px-8 py-8 max-w-[1400px] mx-auto">
    <div class="mb-8 flex justify-between items-end">
        <div>
            <h2 class="text-[10px] font-bold tracking-[0.2em] text-gray-400 uppercase mb-1">ADMIN <span class="text-purple-600">DASHBOARD</span></h2>
            <h1 class="text-3xl font-bold text-gray-800">Kelola Pengguna</h1>
            <p class="text-sm text-gray-400 mt-2">Kelola data pengguna dengan mudah dan efisien</p>
        </div>
        <button type="button" id="openModalBtn"
            class="btn-primary inline-flex items-center px-6 py-3 text-sm font-semibold rounded-xl transition-all uppercase tracking-wide">
            <i class="fas fa-plus mr-2"></i> Tambah Pengguna Baru
        </button>
    </div>

    <!-- Card Filter -->
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm mb-8">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Filter & Pencarian</h3>
        <form action="user.php" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-6">
                <label class="block text-xs font-medium text-gray-600 mb-2">Cari Pengguna</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" placeholder="Cari berdasarkan nama, email, atau username..." 
                           value="<?= htmlspecialchars($search_query) ?>"
                           class="input-focus w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none text-sm placeholder:text-gray-400">
                </div>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                <select name="status" class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none text-sm text-gray-700 cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="aktif" <?= $status_filter == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="tidak_aktif" <?= $status_filter == 'tidak_aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>
            <div class="md:col-span-3 flex gap-2">
                <button type="submit" class="btn-primary flex-1 px-6 py-3 rounded-xl text-sm font-semibold">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
                <a href="user.php" class="btn-secondary w-12 flex items-center justify-center rounded-xl hover:shadow transition-all">
                    <i class="fas fa-sync-alt"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabel Pengguna -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header">
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider border-b border-purple-100">Pengguna</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider border-b border-purple-100 text-center">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider border-b border-purple-100">Username</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider border-b border-purple-100 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-400 font-medium">Data pengguna tidak ditemukan.</p>
                                    <p class="text-sm text-gray-300 mt-1">Coba gunakan kata kunci pencarian yang berbeda</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($users as $user): ?>
                    <tr class="table-row transition-colors border-b border-gray-50 last:border-b-0">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="avatar-purple w-10 h-10 rounded-full flex items-center justify-center font-bold mr-4 shadow-sm">
                                    <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($user['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($user['status'] == 'aktif'): ?>
                                <span class="status-active inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold shadow-sm">
                                    <i class="fas fa-circle mr-1.5 text-[8px]"></i> Aktif
                                </span>
                            <?php else: ?>
                                <span class="status-inactive inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold shadow-sm">
                                    <i class="fas fa-circle mr-1.5 text-[8px]"></i> Tidak Aktif
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <span class="text-sm text-gray-700 bg-gray-100 px-3 py-1 rounded-lg">
                                    @<?= htmlspecialchars($user['username']) ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end items-center gap-2">
                                <a href="user_detail.php?id=<?= $user['id'] ?>" 
                                   class="text-gray-600 hover:text-purple-600 transition-colors p-2 hover:bg-purple-50 rounded-lg"
                                   title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" 
                                        class="text-gray-600 hover:text-teal-600 transition-colors p-2 hover:bg-teal-50 rounded-lg edit-btn"
                                        data-id="<?= $user['id'] ?>" 
                                        data-nama="<?= $user['nama_lengkap'] ?>" 
                                        data-email="<?= $user['email'] ?>" 
                                        data-username="<?= $user['username'] ?>" 
                                        data-status="<?= $user['status'] ?>"
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" 
                                        class="text-gray-600 hover:text-pink-600 transition-colors p-2 hover:bg-pink-50 rounded-lg hapus-btn"
                                        data-id="<?= $user['id'] ?>" 
                                        data-nama="<?= $user['nama_lengkap'] ?>"
                                        title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4 border-t border-gray-200">
            <p class="text-sm text-gray-600">
                Menampilkan <span class="font-semibold"><?= $start_number ?></span> - <span class="font-semibold"><?= $end_number ?></span> dari <span class="font-semibold"><?= $total_data ?></span> pengguna
            </p>
            <?php if ($total_halaman > 1): ?>
                <div class="flex gap-2">
                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search_query) ?>&status=<?= $status_filter ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-all <?= $i == $halaman_aktif ? 'pagination-active shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:border-purple-300 hover:text-purple-600' ?>">
                           <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div id="dataModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-4 w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200 transform transition-all">
            <div class="px-6 py-5 bg-gradient-to-r from-purple-600 to-purple-500">
                <div class="flex justify-between items-center">
                    <h3 id="modalTitle" class="text-lg font-bold text-white">Tambah Pengguna Baru</h3>
                    <button id="closeModalBtn" class="w-8 h-8 flex items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30 transition-colors">&times;</button>
                </div>
            </div>
            
            <form id="dataForm" class="p-6 space-y-5">
                <input type="hidden" name="action" id="formAction" value="tambah">
                <input type="hidden" name="id" id="dataId">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap" required 
                           class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none text-sm">
                </div>
                
                <div class="grid grid-cols-1 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input type="email" name="email" id="email" required 
                               class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none text-sm">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                    <input type="text" name="username" id="username" required 
                           class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none text-sm">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password <span id="passwordHint" class="text-xs text-gray-400">* wajib untuk pengguna baru</span></label>
                    <input type="password" name="password" id="password" 
                           class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none text-sm" 
                           placeholder="Masukkan password">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Pengguna</label>
                    <select name="status" id="status_user" 
                            class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none text-sm">
                        <option value="aktif">Aktif</option>
                        <option value="tidak_aktif">Tidak Aktif</option>
                    </select>
                </div>
                
                <div class="flex items-center gap-3 pt-4">
                    <button type="button" id="cancelModalBtn" 
                            class="btn-secondary flex-1 px-4 py-3 rounded-xl text-sm font-semibold">
                        Batal
                    </button>
                    <button type="submit" 
                            class="btn-primary flex-[1.5] px-4 py-3 rounded-xl text-sm font-semibold">
                        <i class="fas fa-save mr-2"></i> Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('dataModal');
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelModalBtn = document.getElementById('cancelModalBtn');
    const form = document.getElementById('dataForm');
    const passwordHint = document.getElementById('passwordHint');

    const showModal = () => {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    const hideModal = () => {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    };

    openModalBtn.addEventListener('click', () => {
        form.reset();
        document.getElementById('modalTitle').innerHTML = 'Tambah Pengguna Baru';
        document.getElementById('formAction').value = 'tambah';
        document.getElementById('dataId').value = '';
        passwordHint.textContent = '* wajib untuk pengguna baru';
        document.getElementById('password').required = true;
        showModal();
    });

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const d = this.dataset;
            document.getElementById('modalTitle').innerHTML = 'Edit Data Pengguna';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('dataId').value = d.id;
            document.getElementById('nama_lengkap').value = d.nama;
            document.getElementById('email').value = d.email;
            document.getElementById('username').value = d.username;
            document.getElementById('status_user').value = d.status;
            passwordHint.textContent = '* kosongkan jika tidak ingin mengubah password';
            document.getElementById('password').required = false;
            showModal();
        });
    });

    closeModalBtn.addEventListener('click', hideModal);
    cancelModalBtn.addEventListener('click', hideModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) hideModal(); });

    document.querySelectorAll('.hapus-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const nama = this.dataset.nama;

            Swal.fire({
                title: 'Hapus Pengguna?',
                html: `<div class="text-center">
                         <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
                           <i class="fas fa-trash text-red-500 text-xl"></i>
                         </div>
                         <p class="text-gray-700">Anda akan menghapus pengguna:</p>
                         <p class="font-bold text-lg text-gray-800 mt-1">${nama}</p>
                         <p class="text-sm text-gray-500 mt-2">Tindakan ini tidak dapat dibatalkan.</p>
                       </div>`,
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'px-6 py-2.5 rounded-lg font-semibold',
                    cancelButton: 'px-6 py-2.5 rounded-lg font-semibold'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Sedang memproses penghapusan data.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
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
                                confirmButtonColor: '#7c3aed',
                                confirmButtonText: 'OK'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                title: 'Gagal!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonColor: '#7c3aed'
                            });
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Terjadi kesalahan koneksi server.', 'error'));
                }
            });
        });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        
        Swal.fire({
            title: 'Menyimpan Data...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        fetch('user_api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    title: 'Berhasil!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#7c3aed',
                    confirmButtonText: 'OK'
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: data.message,
                    icon: 'error',
                    confirmButtonColor: '#7c3aed'
                });
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>