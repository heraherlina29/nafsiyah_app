<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/../koneksi.php';

// --- LOGIKA PAGINATION ---
$halaman_aktif = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit_per_halaman = 10;
$offset = ($halaman_aktif - 1) * $limit_per_halaman;

// Hitung total data
$total_data = $pdo->query("SELECT COUNT(id) FROM nafsiyah_items")->fetchColumn();
$total_halaman = ceil($total_data / $limit_per_halaman);

// Ambil data dengan LIMIT dan OFFSET
$stmt = $pdo->prepare("SELECT id, activity_name, sub_komponen, urutan FROM nafsiyah_items ORDER BY urutan ASC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit_per_halaman, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

$start_number = ($total_data > 0) ? $offset + 1 : 0;
$end_number = min($offset + $limit_per_halaman, $total_data);
?>

<div class="max-w-7xl mx-auto space-y-8 font-sans">
    
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Kelola Amalan</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manajemen daftar ibadah harian dan poin penilaian</p>
        </div>
        <button type="button" id="openModalBtn"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-700 transition-all shadow-lg shadow-primary-500/30">
            <i class="fas fa-plus"></i> Tambah Amalan
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Amalan -->
        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-soft dark:bg-dark-surface dark:border-dark-surface2 flex items-center justify-between group hover:shadow-lg transition-all">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Amalan</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white"><?= $total_data ?></p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-primary-50 flex items-center justify-center text-primary-600 dark:bg-primary-900/20 dark:text-primary-400 group-hover:scale-110 transition-transform">
                <i class="fas fa-list-check text-xl"></i>
            </div>
        </div>

        <!-- Urutan Tertinggi -->
        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-soft dark:bg-dark-surface dark:border-dark-surface2 flex items-center justify-between group hover:shadow-lg transition-all">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Max Urutan</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white">
                    <?= $items ? max(array_column($items, 'urutan')) : 0 ?>
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 group-hover:scale-110 transition-transform">
                <i class="fas fa-sort-numeric-up text-xl"></i>
            </div>
        </div>

        <!-- Rata-rata Opsi -->
        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-soft dark:bg-dark-surface dark:border-dark-surface2 flex items-center justify-between group hover:shadow-lg transition-all">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Avg. Opsi</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white">
                    <?= $items ? round(array_reduce($items, function ($carry, $item) {
                        return $carry + ($item['sub_komponen'] ? count(explode(',', $item['sub_komponen'])) : 0);
                    }, 0) / count($items), 1) : 0 ?>
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 group-hover:scale-110 transition-transform">
                <i class="fas fa-sliders-h text-xl"></i>
            </div>
        </div>

        <!-- Halaman -->
        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-soft dark:bg-dark-surface dark:border-dark-surface2 flex items-center justify-between group hover:shadow-lg transition-all">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Halaman</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white"><?= $halaman_aktif ?> <span class="text-sm text-slate-400 font-medium">/ <?= $total_halaman ?></span></p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600 dark:bg-amber-900/20 dark:text-amber-400 group-hover:scale-110 transition-transform">
                <i class="fas fa-layer-group text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="bg-white rounded-3xl shadow-soft border border-slate-100 overflow-hidden dark:bg-dark-surface dark:border-dark-surface2">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-400 dark:bg-dark-surface2 dark:text-slate-500">
                    <tr>
                        <th class="px-6 py-4 text-center w-20">Urutan</th>
                        <th class="px-6 py-4">Nama Amalan</th>
                        <th class="px-6 py-4">Opsi & Poin</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 mb-4 dark:bg-dark-surface2 dark:text-slate-600">
                                            <i class="fas fa-heart-broken text-2xl"></i>
                                        </div>
                                        <p class="text-slate-500 font-medium dark:text-slate-400">Belum ada amalan terdaftar</p>
                                        <p class="text-xs text-slate-400 mt-1 dark:text-slate-500">Tambahkan amalan baru untuk memulai</p>
                                    </div>
                                </td>
                            </tr>
                    <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors dark:hover:bg-dark-surface2/50 group">
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-600 font-bold text-xs border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
                                            <?= $item['urutan'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center text-primary-600 font-bold shadow-sm border border-primary-100 dark:bg-primary-900/20 dark:border-primary-800 dark:text-primary-400">
                                                <i class="fas fa-heart text-sm"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800 dark:text-white group-hover:text-primary-600 transition-colors"><?= htmlspecialchars($item['activity_name']) ?></p>
                                                <p class="text-[10px] text-slate-400 font-mono">ID: <?= $item['id'] ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <?php if ($item['sub_komponen']): ?>
                                                    <?php
                                                    $opts = explode(',', $item['sub_komponen']);
                                                    foreach ($opts as $o):
                                                        $parts = explode(':', $o);
                                                        $label = $parts[0] ?? '';
                                                        $val = $parts[1] ?? '0';
                                                        ?>
                                                        <span class="inline-flex items-center gap-1.5 bg-slate-50 px-2.5 py-1 rounded-lg text-xs border border-slate-200 dark:bg-dark-surface2 dark:border-slate-700">
                                                            <span class="font-medium text-slate-600 dark:text-slate-300"><?= htmlspecialchars($label) ?></span>
                                                            <span class="text-[10px] font-bold text-primary-600 bg-primary-50 px-1.5 py-0.5 rounded border border-primary-100 dark:bg-primary-900/30 dark:text-primary-400 dark:border-primary-800"><?= $val ?></span>
                                                        </span>
                                                    <?php endforeach; ?>
                                            <?php else: ?>
                                                    <span class="text-xs text-slate-400 italic">Tidak ada opsi</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" 
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-primary-50 hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm edit-btn dark:bg-dark-surface2 dark:border-slate-700 dark:text-slate-400 dark:hover:text-primary-400"
                                                    data-id="<?= $item['id'] ?>" 
                                                    data-activity_name="<?= htmlspecialchars($item['activity_name']) ?>" 
                                                    data-sub_komponen="<?= htmlspecialchars($item['sub_komponen']) ?>" 
                                                    data-urutan="<?= $item['urutan'] ?>"
                                                    title="Edit">
                                                <i class="fas fa-pen text-xs"></i>
                                            </button>
                                            <button type="button" 
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm hapus-btn dark:bg-dark-surface2 dark:border-slate-700 dark:text-slate-400 dark:hover:text-rose-400"
                                                    data-id="<?= $item['id'] ?>" 
                                                    data-activity_name="<?= htmlspecialchars($item['activity_name']) ?>"
                                                    title="Hapus">
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
        <div class="p-4 border-t border-slate-100 bg-slate-50 dark:bg-dark-surface2 dark:border-slate-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-xs text-slate-500 font-medium dark:text-slate-400">
                Menampilkan <span class="font-bold text-slate-700 dark:text-white"><?= $start_number ?></span> - <span class="font-bold text-slate-700 dark:text-white"><?= $end_number ?></span> dari <span class="font-bold text-slate-700 dark:text-white"><?= $total_data ?></span> data
            </p>
            
            <?php if ($total_halaman > 1): ?>
                <div class="flex gap-2">
                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                            <a href="?page=<?= $i ?>" 
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
    <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl transform scale-95 transition-transform duration-300 overflow-hidden dark:bg-dark-surface border border-slate-100 dark:border-slate-700" id="modalContent">
        
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-dark-surface2/50">
            <div>
                <h3 id="modalTitle" class="text-lg font-black text-slate-800 dark:text-white">Tambah Amalan</h3>
                <p id="modalSubtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Konfigurasi detail ibadah harian</p>
            </div>
            <button id="closeModalBtn" class="w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-400 flex items-center justify-center hover:bg-rose-50 hover:text-rose-500 hover:border-rose-200 transition-all dark:bg-dark-surface2 dark:border-slate-600 dark:text-slate-400 dark:hover:text-rose-400">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Modal Body -->
        <form id="dataForm" class="p-6 space-y-5 max-h-[70vh] overflow-y-auto scrollbar-hide">
            <input type="hidden" name="action" id="formAction" value="tambah">
            <input type="hidden" name="id" id="dataId">
            
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Nama Amalan <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <i class="fas fa-heart absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="activity_name" id="activity_name" required 
                           class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:ring-primary-900"
                           placeholder="Contoh: Sholat Subuh">
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Opsi & Poin</label>
                    <button type="button" id="tambahKomponenBtn" class="text-xs font-bold text-primary-600 hover:text-primary-700 flex items-center gap-1 transition-colors">
                        <i class="fas fa-plus-circle"></i> Tambah Opsi
                    </button>
                </div>
                
                <div class="space-y-2" id="subKomponenContainer">
                    <!-- Dynamic inputs will be added here -->
                </div>
                <p class="text-[10px] text-slate-400 italic">Setiap opsi merepresentasikan status pengerjaan (misal: Berjamaah, Munfarid)</p>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Nomor Urutan <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <i class="fas fa-sort-numeric-up absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="number" name="urutan" id="urutan" required 
                           class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all dark:bg-dark-surface2 dark:border-slate-700 dark:text-white dark:focus:ring-primary-900"
                           placeholder="Urutan tampilan di checklist">
                </div>
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
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('dataModal');
    const modalContent = document.getElementById('modalContent');
    const container = document.getElementById('subKomponenContainer');
    const form = document.getElementById('dataForm');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');

    // Helper: Show/Hide Modal with Animation
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

    // Fungsi Tambah Baris Opsi
    const addRow = (nama = '', poin = '0') => {
        const div = document.createElement('div');
        div.className = 'flex gap-2 items-center animate-fade-in-up';
        div.innerHTML = `
            <div class="flex-1 relative">
                <input type="text" name="opt_nama[]" value="${nama}" placeholder="Label Opsi" 
                       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 dark:bg-dark-surface2 dark:border-slate-700 dark:text-white">
            </div>
            <div class="w-20 relative">
                <input type="number" name="opt_poin[]" value="${poin}" placeholder="0"
                       class="w-full px-2 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-center font-bold text-primary-600 focus:outline-none focus:border-primary-500 dark:bg-dark-surface2 dark:border-slate-700 dark:text-primary-400">
            </div>
            <button type="button" class="w-9 h-9 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-all remove-row dark:hover:bg-rose-900/20">
                <i class="fas fa-trash text-xs"></i>
            </button>
        `;
        container.appendChild(div);
        
        div.querySelector('.remove-row').onclick = () => {
            div.style.opacity = '0';
            div.style.transform = 'translateX(20px)';
            setTimeout(() => div.remove(), 200);
        };
    };

    // Add styles for animation
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in-up { animation: fadeInUp 0.2s ease-out forwards; }
    `;
    document.head.appendChild(style);

    // Event Listeners
    document.getElementById('tambahKomponenBtn').onclick = () => addRow();
    document.getElementById('openModalBtn').onclick = () => {
        form.reset();
        container.innerHTML = '';
        modalTitle.textContent = 'Tambah Amalan Baru';
        modalSubtitle.textContent = 'Konfigurasi detail amalan baru';
        document.getElementById('formAction').value = 'tambah';
        document.getElementById('dataId').value = '';
        addRow('Selesai', '10'); // Default row
        showModal();
    };

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.onclick = () => {
            const d = btn.dataset;
            modalTitle.textContent = 'Edit Amalan';
            modalSubtitle.textContent = 'Perbarui detail amalan';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('dataId').value = d.id;
            document.getElementById('activity_name').value = d.activity_name;
            document.getElementById('urutan').value = d.urutan;
            
            container.innerHTML = '';
            if(d.sub_komponen && d.sub_komponen !== '') {
                d.sub_komponen.split(',').forEach(s => {
                    const p = s.split(':');
                    addRow(p[0], p[1]);
                });
            } else {
                addRow();
            }
            showModal();
        };
    });

    document.getElementById('closeModalBtn').onclick = hideModal;
    document.getElementById('cancelModalBtn')?.addEventListener('click', hideModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) hideModal(); });

    // Submit Form
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const names = form.querySelectorAll('input[name="opt_nama[]"]');
        const points = form.querySelectorAll('input[name="opt_poin[]"]');
        let combined = [];
        
        names.forEach((n, i) => {
            if(n.value.trim()) {
                combined.push(`${n.value.trim()}:${points[i].value || '0'}`);
            }
        });

        const formData = new FormData();
        formData.append('action', document.getElementById('formAction').value);
        formData.append('id', document.getElementById('dataId').value);
        formData.append('activity_name', document.getElementById('activity_name').value);
        formData.append('urutan', document.getElementById('urutan').value);
        formData.append('sub_komponen', combined.join(','));

        Swal.fire({
            title: 'Menyimpan...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); },
            background: document.documentElement.classList.contains('dark') ? '#1E293B' : '#fff',
            color: document.documentElement.classList.contains('dark') ? '#fff' : '#1e293b',
            customClass: { popup: 'rounded-3xl' }
        });

        try {
            const res = await fetch('nafsiyah_api.php', { method: 'POST', body: formData });
            const json = await res.json();
            
            if(json.status === 'success') {
                hideModal();
                Swal.fire({
                    title: 'Berhasil!',
                    text: json.message,
                    icon: 'success',
                    confirmButtonColor: '#8B5CF6',
                    confirmButtonText: 'OK',
                    background: document.documentElement.classList.contains('dark') ? '#1E293B' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#1e293b',
                    customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl font-bold' }
                }).then(() => window.location.reload());
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: json.message,
                    icon: 'error',
                    confirmButtonColor: '#8B5CF6',
                    background: document.documentElement.classList.contains('dark') ? '#1E293B' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#1e293b',
                    customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl font-bold' }
                });
            }
        } catch (error) {
            Swal.fire('Error', 'Terjadi kesalahan koneksi server.', 'error');
        }
    });

    // Hapus Data
    document.querySelectorAll('.hapus-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const activityName = this.dataset.activity_name;

            Swal.fire({
                title: 'Hapus Amalan?',
                html: `<p class="text-slate-600 dark:text-slate-400">Anda akan menghapus amalan:</p>
                       <p class="font-bold text-lg text-slate-800 dark:text-white mt-1 mb-2">${activityName}</p>
                       <p class="text-xs text-rose-500">Tindakan ini tidak dapat dibatalkan!</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#F43F5E',
                cancelButtonColor: '#94A3B8',
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
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); },
                        background: document.documentElement.classList.contains('dark') ? '#1E293B' : '#fff',
                        customClass: { popup: 'rounded-3xl' }
                    });
                    
                    const fd = new FormData();
                    fd.append('action', 'hapus');
                    fd.append('id', id);

                    fetch('nafsiyah_api.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Amalan berhasil dihapus.',
                                icon: 'success',
                                confirmButtonColor: '#8B5CF6',
                                confirmButtonText: 'OK',
                                background: document.documentElement.classList.contains('dark') ? '#1E293B' : '#fff',
                                customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl font-bold' }
                            }).then(() => location.reload());
                        } else {
                            throw new Error(data.message);
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