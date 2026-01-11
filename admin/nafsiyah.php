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
?>

<div class="px-8 py-8 max-w-[1400px] mx-auto">
    <!-- Header Section -->
    <div class="mb-8 flex justify-between items-end">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i class="fas fa-heart text-white text-sm"></i>
                </div>
                <h2 class="text-[11px] font-bold tracking-[0.15em] text-gray-400 uppercase">MASTER DATA <span class="text-purple-500">NAFSIYAH</span></h2>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 tracking-tight">Kelola Amalan Harian</h1>
            <p class="text-sm text-gray-400 mt-2">Atur daftar amalan beserta poin penilaiannya</p>
        </div>
        <button id="openModalBtn" 
                class="btn-primary inline-flex items-center px-6 py-3 text-sm font-semibold rounded-xl transition-all uppercase tracking-wide">
            <i class="fas fa-plus mr-2"></i> Tambah Amalan Baru
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl border border-purple-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Total Amalan</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $total_data ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-100 to-purple-50 flex items-center justify-center">
                    <i class="fas fa-list-check text-purple-500 text-lg"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl border border-blue-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Urutan Tertinggi</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <?= $items ? max(array_column($items, 'urutan')) : 0 ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                    <i class="fas fa-sort-numeric-up text-blue-500 text-lg"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-emerald-50 to-white p-5 rounded-2xl border border-emerald-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Opsi Rata-rata</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <?= $items ? round(array_reduce($items, function($carry, $item) {
                            return $carry + ($item['sub_komponen'] ? count(explode(',', $item['sub_komponen'])) : 0);
                        }, 0) / count($items), 1) : 0 ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-100 to-emerald-50 flex items-center justify-center">
                    <i class="fas fa-sliders text-emerald-500 text-lg"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-amber-50 to-white p-5 rounded-2xl border border-amber-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Halaman Ini</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $halaman_aktif ?>/<?= $total_halaman ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-100 to-amber-50 flex items-center justify-center">
                    <i class="fas fa-layer-group text-amber-500 text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-white border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Daftar Amalan Harian</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Urutan</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama Amalan</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Opsi & Poin</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                        <i class="fas fa-heart text-3xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-400 font-medium">Belum ada amalan yang terdaftar</p>
                                    <p class="text-sm text-gray-300 mt-2">Mulai dengan menambahkan amalan baru</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($items as $item): ?>
                    <tr class="table-row border-b border-gray-50 last:border-b-0">
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-sm">
                                    <span class="text-white font-bold text-sm"><?= $item['urutan'] ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-heart text-purple-500 text-xs"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($item['activity_name']) ?></div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        ID: <span class="font-mono"><?= $item['id'] ?></span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2">
                                <?php if($item['sub_komponen']): ?>
                                    <?php 
                                    $opts = explode(',', $item['sub_komponen']);
                                    foreach($opts as $o):
                                        $parts = explode(':', $o);
                                        $label = $parts[0] ?? '';
                                        $val = $parts[1] ?? '0';
                                    ?>
                                    <span class="inline-flex items-center gap-1 bg-gradient-to-r from-purple-50 to-white px-3 py-1.5 rounded-lg text-xs border border-purple-100">
                                        <span class="font-medium text-gray-700"><?= htmlspecialchars($label) ?></span>
                                        <span class="text-[10px] font-bold text-purple-500 bg-purple-100 px-1.5 py-0.5 rounded"><?= $val ?>pt</span>
                                    </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm italic">Tidak ada opsi</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end items-center gap-2">
                                <button type="button" 
                                        class="edit-btn text-gray-600 hover:text-teal-600 transition-colors p-2 hover:bg-teal-50 rounded-lg"
                                        data-id="<?= $item['id'] ?>" 
                                        data-activity_name="<?= htmlspecialchars($item['activity_name']) ?>" 
                                        data-sub_komponen="<?= htmlspecialchars($item['sub_komponen']) ?>" 
                                        data-urutan="<?= $item['urutan'] ?>"
                                        title="Edit Amalan">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" 
                                        class="hapus-btn text-gray-600 hover:text-pink-600 transition-colors p-2 hover:bg-pink-50 rounded-lg"
                                        data-id="<?= $item['id'] ?>" 
                                        data-activity_name="<?= htmlspecialchars($item['activity_name']) ?>"
                                        title="Hapus Amalan">
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
                Menampilkan <span class="font-semibold"><?= count($items) ?></span> dari <span class="font-semibold"><?= $total_data ?></span> amalan
            </p>
            
            <?php if ($total_halaman > 1): ?>
                <div class="flex gap-2">
                    <?php if ($halaman_aktif > 1): ?>
                        <a href="?page=<?= $halaman_aktif - 1 ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg bg-white text-gray-600 border border-gray-200 hover:border-purple-300 hover:text-purple-600 transition-all">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                        <a href="?page=<?= $i ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-all <?= ($i == $halaman_aktif) ? 'pagination-active shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:border-purple-300 hover:text-purple-600' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($halaman_aktif < $total_halaman): ?>
                        <a href="?page=<?= $halaman_aktif + 1 ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg bg-white text-gray-600 border border-gray-200 hover:border-purple-300 hover:text-purple-600 transition-all">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div id="dataModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-4 w-full max-w-lg">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200 transform transition-all">
            <!-- Modal Header -->
            <div class="px-6 py-5 bg-gradient-to-r from-purple-600 to-purple-500">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                            <i class="fas fa-heart text-white"></i>
                        </div>
                        <div>
                            <h3 id="modalTitle" class="text-lg font-bold text-white"></h3>
                            <p id="modalSubtitle" class="text-xs text-white/80 mt-1">Konfigurasi detail amalan</p>
                        </div>
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
                        <i class="fas fa-heart mr-2 text-gray-400"></i>Nama Amalan *
                    </label>
                    <input type="text" name="activity_name" id="activity_name" required 
                           class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none text-sm placeholder:text-gray-400"
                           placeholder="Contoh: Sholat Subuh">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-sliders mr-2 text-gray-400"></i>Opsi & Poin
                    </label>
                    <div class="space-y-3 mb-4" id="subKomponenContainer"></div>
                    <button type="button" id="tambahKomponenBtn" 
                            class="btn-secondary w-full py-3 rounded-xl text-sm font-semibold border-2 border-dashed border-gray-200 hover:border-purple-300 hover:text-purple-600 hover:bg-purple-50 transition-all">
                        <i class="fas fa-plus-circle mr-2"></i>Tambah Opsi Baru
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-sort-numeric-up mr-2 text-gray-400"></i>Nomor Urutan *
                    </label>
                    <input type="number" name="urutan" id="urutan" required 
                           class="input-focus w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none text-sm placeholder:text-gray-400"
                           placeholder="Masukkan nomor urutan">
                </div>

                <div class="pt-4">
                    <div class="flex items-center gap-3">
                        <button type="button" id="cancelModalBtn" 
                                class="btn-secondary flex-1 px-4 py-3 rounded-xl text-sm font-semibold">
                            Batal
                        </button>
                        <button type="submit" 
                                class="btn-primary flex-[1.5] px-4 py-3 rounded-xl text-sm font-semibold">
                            <i class="fas fa-save mr-2"></i> Simpan Amalan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('dataModal');
    const container = document.getElementById('subKomponenContainer');
    const form = document.getElementById('dataForm');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');

    // Fungsi Tambah Baris Opsi
    const addRow = (nama = '', poin = '0') => {
        const div = document.createElement('div');
        div.className = 'flex gap-3 items-center bg-white p-3 rounded-xl border border-gray-200 shadow-sm';
        div.innerHTML = `
            <div class="flex-1">
                <input type="text" name="opt_nama[]" value="${nama}" placeholder="Contoh: Berjamaah" 
                       class="input-focus w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none">
            </div>
            <div class="w-24">
                <div class="relative">
                    <input type="number" name="opt_poin[]" value="${poin}" 
                           class="input-focus w-full px-4 py-2.5 bg-purple-50 border border-purple-200 rounded-lg text-sm font-semibold text-purple-600 text-center focus:outline-none">
                    <span class="absolute -top-2 left-1/2 -translate-x-1/2 text-[10px] font-medium text-purple-400 bg-white px-1">Poin</span>
                </div>
            </div>
            <button type="button" class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-red-500 transition-colors remove-row">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(div);
        div.querySelector('.remove-row').onclick = () => {
            div.classList.add('opacity-0', 'scale-95');
            setTimeout(() => div.remove(), 200);
        };
    };

    // Event untuk tambah opsi baru
    document.getElementById('tambahKomponenBtn').onclick = () => addRow();

    // Open Modal Tambah
    document.getElementById('openModalBtn').onclick = () => {
        form.reset();
        container.innerHTML = '';
        modalTitle.textContent = 'Tambah Amalan Baru';
        modalSubtitle.textContent = 'Konfigurasi detail amalan baru';
        document.getElementById('formAction').value = 'tambah';
        document.getElementById('dataId').value = '';
        modal.classList.remove('hidden');
    };

    // Open Modal Edit
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
            }
            
            modal.classList.remove('hidden');
        };
    });

    // Close Modal
    document.getElementById('closeModalBtn').onclick = () => modal.classList.add('hidden');
    document.getElementById('cancelModalBtn')?.addEventListener('click', () => modal.classList.add('hidden'));
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.add('hidden'); });

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

        // Tampilkan loading
        Swal.fire({
            title: 'Menyimpan...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const res = await fetch('nafsiyah_api.php', { method: 'POST', body: formData });
            const json = await res.json();
            
            if(json.status === 'success') {
                Swal.fire({
                    title: 'Berhasil!',
                    text: json.message,
                    icon: 'success',
                    confirmButtonColor: '#7c3aed',
                    confirmButtonText: 'OK'
                }).then(() => window.location.reload());
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: json.message,
                    icon: 'error',
                    confirmButtonColor: '#7c3aed'
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
                html: `<div class="text-center">
                         <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
                           <i class="fas fa-heart text-red-500 text-xl"></i>
                         </div>
                         <p class="text-gray-700">Anda akan menghapus amalan:</p>
                         <p class="font-bold text-lg text-gray-800 mt-1">${activityName}</p>
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

                    fetch('nafsiyah_api.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Amalan berhasil dihapus.',
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
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>