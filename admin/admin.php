<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/../koneksi.php';

// --- LOGIKA PAGINATION ---
$halaman_aktif = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit_per_halaman = 10;
$offset = ($halaman_aktif - 1) * $limit_per_halaman;

$total_data = $pdo->query("SELECT COUNT(id) FROM admins")->fetchColumn();
$total_halaman = ceil($total_data / $limit_per_halaman);

$sql = "SELECT id, username, created_at FROM admins ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(1, $limit_per_halaman, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$admins = $stmt->fetchAll();

$start_number = ($total_data > 0) ? $offset + 1 : 0;
$end_number = min($offset + $limit_per_halaman, $total_data);
?>

<div class="px-8 py-8 max-w-[1400px] mx-auto">
    <!-- Header Section -->
    <div class="mb-8 flex justify-between items-end">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                    <i class="fas fa-shield-alt text-white text-sm"></i>
                </div>
                <h2 class="text-[11px] font-bold tracking-[0.15em] text-gray-400 uppercase">ADMINISTRATOR <span class="text-primary-600">PANEL</span></h2>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 tracking-tight">Manajemen Admin</h1>
            <p class="text-sm text-gray-400 mt-2">Kelola administrator yang memiliki akses ke sistem</p>
        </div>
        <button type="button" id="openModalBtn"
            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary-500 to-green-500 text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:shadow-primary-200 transition-all uppercase tracking-wide">
            <i class="fas fa-plus mr-2"></i> Tambah Admin Baru
        </button>
    </div>

    <!-- Stats Card -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-primary-50 to-white p-5 rounded-2xl border border-primary-100 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Total Admin</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $total_data ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary-100 to-primary-50 flex items-center justify-center">
                    <i class="fas fa-users text-primary-500 text-lg"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl border border-blue-100 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Admin Aktif</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $total_data ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                    <i class="fas fa-user-check text-blue-500 text-lg"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl border border-green-100 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Halaman Ini</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $halaman_aktif ?>/<?= $total_halaman ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center">
                    <i class="fas fa-layer-group text-green-500 text-lg"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-amber-50 to-white p-5 rounded-2xl border border-amber-100 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Data Ditampilkan</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $start_number ?>-<?= $end_number ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-100 to-amber-50 flex items-center justify-center">
                    <i class="fas fa-table text-amber-500 text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-green-50 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Daftar Administrator</h3>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-1 text-xs font-medium bg-primary-100 text-primary-700 rounded-lg border border-primary-200">
                        <i class="fas fa-database mr-1"></i> <?= $total_data ?> Data
                    </span>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Administrator</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal Bergabung</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($admins)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary-50 to-green-50 flex items-center justify-center mb-4 border border-primary-100">
                                        <i class="fas fa-user-shield text-3xl text-primary-300"></i>
                                    </div>
                                    <p class="text-gray-400 font-medium">Belum ada administrator yang terdaftar</p>
                                    <p class="text-sm text-gray-300 mt-2">Mulai dengan menambahkan administrator baru</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($admins as $admin): ?>
                    <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors last:border-b-0">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center mr-4 shadow-sm">
                                    <i class="fas fa-shield-alt text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                                        <?= htmlspecialchars($admin['username']); ?>
                                        <span class="px-2 py-0.5 text-[10px] font-bold bg-gradient-to-r from-primary-100 to-green-50 text-primary-600 rounded-full border border-primary-200 uppercase">
                                            Admin
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-id-card mr-1"></i> ID: <span class="font-mono"><?= $admin['id']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center mr-3 border border-gray-200">
                                    <i class="fas fa-calendar-alt text-gray-400 text-xs"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-700">
                                        <?= date('d M Y', strtotime($admin['created_at'])); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        <i class="far fa-clock mr-1"></i> <?= date('H:i', strtotime($admin['created_at'])); ?> WIB
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <button type="button" 
                                        class="edit-btn px-3 py-2 bg-gradient-to-r from-blue-50 to-blue-100 text-blue-600 hover:text-blue-700 hover:from-blue-100 hover:to-blue-50 transition-all rounded-lg border border-blue-200 text-xs font-medium flex items-center gap-1"
                                        data-id="<?= $admin['id']; ?>"
                                        data-username="<?= htmlspecialchars($admin['username']); ?>"
                                        title="Edit Admin">
                                    <i class="fas fa-edit text-xs"></i> Edit
                                </button>
                                <button type="button" 
                                        class="hapus-btn px-3 py-2 bg-gradient-to-r from-red-50 to-red-100 text-red-600 hover:text-red-700 hover:from-red-100 hover:to-red-50 transition-all rounded-lg border border-red-200 text-xs font-medium flex items-center gap-1"
                                        data-id="<?= $admin['id']; ?>"
                                        data-username="<?= htmlspecialchars($admin['username']); ?>"
                                        title="Hapus Admin">
                                    <i class="fas fa-trash text-xs"></i> Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-white flex flex-col md:flex-row justify-between items-center gap-4 border-t border-gray-200">
            <p class="text-sm text-gray-600">
                <i class="fas fa-list mr-1"></i> Menampilkan <span class="font-semibold"><?= $start_number ?></span> - <span class="font-semibold"><?= $end_number ?></span> dari <span class="font-semibold"><?= $total_data ?></span> administrator
            </p>
            <?php if ($total_halaman > 1): ?>
                <div class="flex gap-2">
                    <!-- Previous Button -->
                    <?php if ($halaman_aktif > 1): ?>
                        <a href="?page=<?= $halaman_aktif - 1 ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium bg-white text-gray-600 border border-gray-200 hover:border-primary-300 hover:text-primary-600 transition-colors">
                           <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                        <a href="?page=<?= $i ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-all <?= $i == $halaman_aktif ? 'bg-gradient-to-r from-primary-500 to-green-500 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:border-primary-300 hover:text-primary-600' ?>">
                           <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <!-- Next Button -->
                    <?php if ($halaman_aktif < $total_halaman): ?>
                        <a href="?page=<?= $halaman_aktif + 1 ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium bg-white text-gray-600 border border-gray-200 hover:border-primary-300 hover:text-primary-600 transition-colors">
                           <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div id="dataModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-4 w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200 transform transition-all">
            <!-- Modal Header -->
            <div class="px-6 py-5 bg-gradient-to-r from-primary-500 to-green-500">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                            <i class="fas fa-user-shield text-white"></i>
                        </div>
                        <h3 id="modalTitle" class="text-lg font-bold text-white"></h3>
                    </div>
                    <button id="closeModalBtn" class="w-8 h-8 flex items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Form -->
            <form id="dataForm" class="p-6 space-y-5">
                <input type="hidden" name="action" id="formAction" value="tambah">
                <input type="hidden" name="id" id="dataId">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-primary-500"></i>Username *
                    </label>
                    <input type="text" name="username" id="username" required 
                           class="w-full px-4 py-3 bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-primary-400 text-sm placeholder:text-gray-400 transition-all"
                           placeholder="Masukkan username admin">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-primary-500"></i>Password <span id="passwordHint" class="text-xs text-gray-400">* wajib untuk admin baru</span>
                    </label>
                    <input type="password" name="password" id="password" 
                           class="w-full px-4 py-3 bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-primary-400 text-sm placeholder:text-gray-400 transition-all"
                           placeholder="Masukkan password admin">
                </div>
                
                <div class="pt-4">
                    <div class="flex items-center gap-3">
                        <button type="button" id="cancelModalBtn" 
                                class="btn-secondary flex-1 px-4 py-3 rounded-xl text-sm font-semibold border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit" 
                                class="flex-[1.5] px-4 py-3 bg-gradient-to-r from-primary-500 to-green-500 text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:shadow-primary-200 transition-all">
                            <i class="fas fa-save mr-2"></i> Simpan Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Custom color classes */
    .text-primary-50 { color: #f0f9ff; }
    .bg-primary-50 { background-color: #f0f9ff; }
    .border-primary-50 { border-color: #f0f9ff; }
    .from-primary-50 { --tw-gradient-from: #f0f9ff; }
    .to-primary-50 { --tw-gradient-to: #f0f9ff; }
    
    .text-primary-100 { color: #e0f2fe; }
    .bg-primary-100 { background-color: #e0f2fe; }
    .border-primary-100 { border-color: #e0f2fe; }
    .from-primary-100 { --tw-gradient-from: #e0f2fe; }
    .to-primary-100 { --tw-gradient-to: #e0f2fe; }
    
    .text-primary-200 { color: #bae6fd; }
    .bg-primary-200 { background-color: #bae6fd; }
    .border-primary-200 { border-color: #bae6fd; }
    .from-primary-200 { --tw-gradient-from: #bae6fd; }
    .to-primary-200 { --tw-gradient-to: #bae6fd; }
    
    .text-primary-300 { color: #7dd3fc; }
    .bg-primary-300 { background-color: #7dd3fc; }
    .border-primary-300 { border-color: #7dd3fc; }
    .from-primary-300 { --tw-gradient-from: #7dd3fc; }
    .to-primary-300 { --tw-gradient-to: #7dd3fc; }
    
    .text-primary-400 { color: #38bdf8; }
    .bg-primary-400 { background-color: #38bdf8; }
    .border-primary-400 { border-color: #38bdf8; }
    .from-primary-400 { --tw-gradient-from: #38bdf8; }
    .to-primary-400 { --tw-gradient-to: #38bdf8; }
    
    .text-primary-500 { color: #0ea5e9; }
    .bg-primary-500 { background-color: #0ea5e9; }
    .border-primary-500 { border-color: #0ea5e9; }
    .from-primary-500 { --tw-gradient-from: #0ea5e9; }
    .to-primary-500 { --tw-gradient-to: #0ea5e9; }
    
    .text-primary-600 { color: #0284c7; }
    .bg-primary-600 { background-color: #0284c7; }
    .border-primary-600 { border-color: #0284c7; }
    .from-primary-600 { --tw-gradient-from: #0284c7; }
    .to-primary-600 { --tw-gradient-to: #0284c7; }
    
    /* Green colors */
    .text-green-50 { color: #f0fdf4; }
    .bg-green-50 { background-color: #f0fdf4; }
    .border-green-50 { border-color: #f0fdf4; }
    .from-green-50 { --tw-gradient-from: #f0fdf4; }
    .to-green-50 { --tw-gradient-to: #f0fdf4; }
    
    .text-green-100 { color: #dcfce7; }
    .bg-green-100 { background-color: #dcfce7; }
    .border-green-100 { border-color: #dcfce7; }
    .from-green-100 { --tw-gradient-from: #dcfce7; }
    .to-green-100 { --tw-gradient-to: #dcfce7; }
    
    .text-green-500 { color: #22c55e; }
    .bg-green-500 { background-color: #22c55e; }
    .border-green-500 { border-color: #22c55e; }
    .from-green-500 { --tw-gradient-from: #22c55e; }
    .to-green-500 { --tw-gradient-to: #22c55e; }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('dataModal');
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelModalBtn = document.getElementById('cancelModalBtn');
    const form = document.getElementById('dataForm');
    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const dataId = document.getElementById('dataId');
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
        modalTitle.textContent = 'Tambah Admin Baru';
        formAction.value = 'tambah';
        dataId.value = '';
        passwordHint.textContent = '* wajib untuk admin baru';
        document.getElementById('password').required = true;
        showModal();
    });

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = this.dataset;
            modalTitle.textContent = 'Edit Data Admin';
            formAction.value = 'edit';
            dataId.value = data.id;
            document.getElementById('username').value = data.username;
            passwordHint.textContent = '* kosongkan jika tidak ingin mengubah password';
            document.getElementById('password').required = false;
            showModal();
        });
    });

    closeModalBtn.addEventListener('click', hideModal);
    cancelModalBtn.addEventListener('click', hideModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) hideModal(); });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        
        Swal.fire({
            title: 'Menyimpan Data...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        fetch('admin_api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    title: 'Berhasil!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#0ea5e9',
                    confirmButtonText: 'OK'
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: data.message,
                    icon: 'error',
                    confirmButtonColor: '#0ea5e9'
                });
            }
        });
    });

    document.querySelectorAll('.hapus-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const username = this.dataset.username;

            Swal.fire({
                title: 'Hapus Administrator?',
                html: `<div class="text-center">
                         <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-red-50 to-red-100 flex items-center justify-center border border-red-200">
                           <i class="fas fa-user-shield text-red-500 text-xl"></i>
                         </div>
                         <p class="text-gray-700">Anda akan menghapus administrator:</p>
                         <p class="font-bold text-lg text-gray-800 mt-1">@${username}</p>
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

                    fetch('admin_api.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Data administrator berhasil dihapus.',
                                icon: 'success',
                                confirmButtonColor: '#0ea5e9',
                                confirmButtonText: 'OK'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                title: 'Gagal!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonColor: '#0ea5e9'
                            });
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Terjadi kesalahan koneksi server.', 'error'));
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>