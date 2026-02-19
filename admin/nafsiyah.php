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

// Ambil data dengan kolom is_udzur
$stmt = $pdo->prepare("SELECT id, activity_name, sub_komponen, urutan, is_udzur FROM nafsiyah_items ORDER BY urutan ASC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit_per_halaman, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

$start_number = ($total_data > 0) ? $offset + 1 : 0;
$end_number = min($offset + $limit_per_halaman, $total_data);
?>

<div class="max-w-7xl mx-auto space-y-8 font-sans">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Kelola Amalan</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manajemen daftar ibadah harian dan poin penilaian
            </p>
        </div>
        <button type="button" id="openModalBtn"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-700 transition-all shadow-lg shadow-primary-500/30">
            <i class="fas fa-plus"></i> Tambah Amalan
        </button>
    </div>

    <div
        class="bg-white rounded-3xl shadow-soft border border-slate-100 overflow-hidden dark:bg-dark-surface dark:border-dark-surface2">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                <thead
                    class="bg-slate-50 text-xs uppercase font-bold text-slate-400 dark:bg-dark-surface2 dark:text-slate-500">
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
                                <p class="text-slate-500 font-medium">Belum ada amalan terdaftar</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors dark:hover:bg-dark-surface2/50 group">
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-600 font-bold text-xs border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
                                        <?= $item['urutan'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center text-primary-600 font-bold border border-primary-100 dark:bg-primary-900/20 dark:border-primary-800 dark:text-primary-400">
                                            <i class="fas fa-heart text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="font-bold text-slate-800 dark:text-white">
                                                    <?= htmlspecialchars($item['activity_name']) ?></p>
                                                <?php if ($item['is_udzur']): ?>
                                                    <span
                                                        class="px-2 py-0.5 bg-rose-100 text-rose-600 text-[10px] font-bold rounded-full uppercase dark:bg-rose-900/30 dark:text-rose-400"
                                                        title="Terpengaruh Mode Udzur">
                                                        <i class="fas fa-moon"></i> Udzur
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-[10px] text-slate-400 font-mono uppercase">ID: <?= $item['id'] ?></p>
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
                                                <span
                                                    class="inline-flex items-center gap-1.5 bg-slate-50 px-2.5 py-1 rounded-lg text-xs border border-slate-200 dark:bg-dark-surface2 dark:border-slate-700">
                                                    <span
                                                        class="font-medium text-slate-600 dark:text-slate-300"><?= htmlspecialchars($label) ?></span>
                                                    <span
                                                        class="text-[10px] font-bold text-primary-600 bg-primary-50 px-1.5 py-0.5 rounded border border-primary-100 dark:bg-primary-900/30 dark:text-primary-400"><?= $val ?></span>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-primary-50 hover:text-primary-600 edit-btn dark:bg-dark-surface2 dark:border-slate-700"
                                            data-id="<?= $item['id'] ?>"
                                            data-activity_name="<?= htmlspecialchars($item['activity_name']) ?>"
                                            data-sub_komponen="<?= htmlspecialchars($item['sub_komponen']) ?>"
                                            data-urutan="<?= $item['urutan'] ?>" data-is_udzur="<?= $item['is_udzur'] ?>">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>
                                        <button type="button"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-rose-50 hover:text-rose-600 hapus-btn dark:bg-dark-surface2 dark:border-slate-700"
                                            data-id="<?= $item['id'] ?>"
                                            data-activity_name="<?= htmlspecialchars($item['activity_name']) ?>">
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

        <div
            class="p-4 border-t border-slate-100 bg-slate-50 dark:bg-dark-surface2 dark:border-slate-700 flex justify-between items-center">
            <p class="text-xs text-slate-500">Menampilkan <?= $start_number ?>-<?= $end_number ?> dari
                <?= $total_data ?></p>
            <?php if ($total_halaman > 1): ?>
                <div class="flex gap-1">
                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                        <a href="?page=<?= $i ?>"
                            class="px-3 py-1 rounded-lg text-xs font-bold <?= $i == $halaman_aktif ? 'bg-primary-600 text-white' : 'bg-white border border-slate-200 text-slate-500' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="dataModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden dark:bg-dark-surface border dark:border-slate-700"
        id="modalContent">

        <div
            class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-dark-surface2/50">
            <div>
                <h3 id="modalTitle" class="text-lg font-black text-slate-800 dark:text-white">Tambah Amalan</h3>
                <p class="text-xs text-slate-500 mt-0.5">Konfigurasi detail ibadah harian</p>
            </div>
            <button id="closeModalBtn"
                class="w-8 h-8 rounded-full border flex items-center justify-center text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="dataForm" class="flex flex-col">
            <div class="p-6 space-y-5 overflow-y-auto max-h-[60vh] custom-scrollbar">
                <input type="hidden" name="action" id="formAction" value="tambah">
                <input type="hidden" name="id" id="dataId">

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase">Nama Amalan</label>
                    <input type="text" name="activity_name" id="activity_name" required
                        class="w-full px-4 py-3 bg-slate-50 border rounded-xl text-sm focus:outline-none focus:border-primary-500 dark:bg-dark-surface2 dark:border-slate-700 dark:text-white"
                        placeholder="Contoh: Sholat Subuh">
                </div>

                <div
                    class="flex items-center gap-3 p-4 bg-rose-50 rounded-xl border border-rose-100 dark:bg-rose-900/10 dark:border-rose-800">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_udzur" id="is_udzur" class="sr-only peer">
                        <div
                            class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-rose-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all">
                        </div>
                    </label>
                    <div>
                        <span class="text-sm font-bold text-rose-600 dark:text-rose-400">Status Udzur Syar'i</span>
                        <p class="text-[10px] text-rose-400 leading-tight">Otomatis dianggap selesai jika user
                            mengaktifkan Mode Haid.</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label class="text-xs font-bold text-slate-500 uppercase">Opsi & Poin</label>
                        <button type="button" id="tambahKomponenBtn"
                            class="text-xs font-bold text-primary-600 hover:text-primary-700 flex items-center gap-1">
                            <i class="fas fa-plus-circle"></i> Tambah Opsi
                        </button>
                    </div>
                    <div class="space-y-2" id="subKomponenContainer">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase">Urutan Tampilan</label>
                    <input type="number" name="urutan" id="urutan" required
                        class="w-full px-4 py-3 bg-slate-50 border rounded-xl text-sm focus:outline-none dark:bg-dark-surface2 dark:border-slate-700 dark:text-white"
                        placeholder="Angka urutan (1, 2, 3...)">
                </div>
            </div>

            <div class="p-6 border-t border-slate-100 dark:border-slate-700 bg-slate-50/30 dark:bg-dark-surface">
                <button type="submit"
                    class="w-full px-4 py-3 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-700 shadow-lg transition-all transform active:scale-95">
                    <i class="fas fa-save mr-2"></i> Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 5px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }

    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #334155;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('dataModal');
        const container = document.getElementById('subKomponenContainer');
        const form = document.getElementById('dataForm');

        const showModal = () => {
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.remove('opacity-0'), 10);
        };

        const hideModal = () => {
            modal.classList.add('opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        };

        const addRow = (nama = '', poin = '0') => {
            const div = document.createElement('div');
            div.className = 'flex gap-2 items-center';
            div.innerHTML = `
            <input type="text" name="opt_nama[]" value="${nama}" placeholder="Label" class="flex-1 px-3 py-2 bg-slate-50 border rounded-lg text-sm dark:bg-dark-surface2 dark:border-slate-700 dark:text-white">
            <input type="number" name="opt_poin[]" value="${poin}" class="w-20 px-3 py-2 bg-slate-50 border rounded-lg text-sm text-center font-bold text-primary-600 dark:bg-dark-surface2 dark:border-slate-700">
            <button type="button" class="text-slate-400 hover:text-rose-500 remove-row"><i class="fas fa-times"></i></button>
        `;
            container.appendChild(div);
            div.querySelector('.remove-row').onclick = () => div.remove();
        };

        document.getElementById('openModalBtn').onclick = () => {
            form.reset();
            container.innerHTML = '';
            document.getElementById('formAction').value = 'tambah';
            document.getElementById('is_udzur').checked = false;
            addRow('Selesai', '10');
            addRow('Tidak Mengerjakan', '0');
            showModal();
        };

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.onclick = () => {
                const d = btn.dataset;
                document.getElementById('formAction').value = 'edit';
                document.getElementById('dataId').value = d.id;
                document.getElementById('activity_name').value = d.activity_name;
                document.getElementById('urutan').value = d.urutan;
                document.getElementById('is_udzur').checked = d.is_udzur == "1";

                container.innerHTML = '';
                if (d.sub_komponen) {
                    d.sub_komponen.split(',').forEach(s => {
                        const p = s.split(':');
                        addRow(p[0], p[1]);
                    });
                }
                showModal();
            };
        });

        document.getElementById('tambahKomponenBtn').onclick = () => addRow();
        document.getElementById('closeModalBtn').onclick = hideModal;

        form.onsubmit = async (e) => {
            e.preventDefault();

            const names = form.querySelectorAll('input[name="opt_nama[]"]');
            const points = form.querySelectorAll('input[name="opt_poin[]"]');
            let combined = [];
            names.forEach((n, i) => { if (n.value.trim()) combined.push(`${n.value.trim()}:${points[i].value || '0'}`); });

            const formData = new FormData();
            formData.append('action', document.getElementById('formAction').value);
            formData.append('id', document.getElementById('dataId').value);
            formData.append('activity_name', document.getElementById('activity_name').value);
            formData.append('urutan', document.getElementById('urutan').value);
            formData.append('sub_komponen', combined.join(','));
            formData.append('is_udzur', document.getElementById('is_udzur').checked ? 1 : 0);

            try {
                const res = await fetch('nafsiyah_api.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.status === 'success') location.reload();
                else alert(json.message);
            } catch (error) {
                alert('Terjadi kesalahan server.');
            }
        };

        // Logika Hapus (Sama seperti sebelumnya)
        document.querySelectorAll('.hapus-btn').forEach(btn => {
            btn.onclick = () => {
                if (confirm('Hapus amalan ini?')) {
                    const fd = new FormData();
                    fd.append('action', 'hapus');
                    fd.append('id', btn.dataset.id);
                    fetch('nafsiyah_api.php', { method: 'POST', body: fd }).then(() => location.reload());
                }
            };
        });
    });
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>