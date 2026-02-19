<?php
require_once __DIR__ . '/../koneksi.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$id_user = $_SESSION['user_id'];
$tgl = date('Y-m-d');

// --- PROSES SIMPAN LAPORAN ---
// Cek apakah user sudah lapor hari ini
$cek = $pdo->prepare("SELECT COUNT(id) FROM nafsiyah_logs WHERE user_id = ? AND log_date = ?");
$cek->execute([$id_user, $tgl]);
$done = $cek->fetchColumn() > 0;

if (!$done && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $total_poin_hari_ini = 0;
        $is_haid = isset($_POST['mode_haid']) ? 1 : 0;

        // Ambil data user terbaru untuk update streak
        $stmtUser = $pdo->prepare("SELECT total_poin, streak_count, terakhir_lapor FROM users WHERE id = ?");
        $stmtUser->execute([$id_user]);
        $userData = $stmtUser->fetch();

        // Ambil daftar amalan dari database
        $items = $pdo->query("SELECT * FROM nafsiyah_items ORDER BY urutan ASC")->fetchAll();

        foreach ($items as $item) {
            $id_item = $item['id'];

            // LOGIKA DINAMIS: Cek status udzur dari kolom database
            $is_kena_udzur = ($item['is_udzur'] == 1);

            if ($is_haid && $is_kena_udzur) {
                // Jika sedang haid dan amalan mendukung udzur, otomatis dianggap selesai dengan poin apresiasi
                $catatan = "Udzur Syar'i";
                $skor = 5;
                $st = 'selesai';
            } else {
                if (isset($_POST['item'][$id_item])) {
                    $p = explode('|', $_POST['item'][$id_item]);
                    $catatan = $p[0];
                    $skor = (int) $p[1];
                    $st = (in_array($catatan, ['Tidak', 'Absen', 'Tidur', 'Makan', 'Tidak Mengerjakan'])) ? 'tidak_selesai' : 'selesai';
                } else {
                    // Jika amalan wajib tapi tidak diisi, skip (atau bisa beri default)
                    continue;
                }
            }

            $total_poin_hari_ini += $skor;
            $pdo->prepare("INSERT INTO nafsiyah_logs (user_id, item_id, log_date, status, catatan, poin_didapat) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$id_user, $id_item, $tgl, $st, $catatan, $skor]);
        }

        // Logika Streak Count
        $tgl_kemarin = date('Y-m-d', strtotime("-1 day"));
        $new_streak = 1;
        if ($userData['terakhir_lapor'] == $tgl_kemarin) {
            $new_streak = $userData['streak_count'] + 1;
        } elseif ($userData['terakhir_lapor'] == $tgl) {
            $new_streak = $userData['streak_count'];
        }

        // Update total poin dan streak user
        $pdo->prepare("UPDATE users SET total_poin = total_poin + ?, terakhir_lapor = ?, streak_count = ? WHERE id = ?")
            ->execute([$total_poin_hari_ini, $tgl, $new_streak, $id_user]);

        $pdo->commit();
        header('Location: index.php?status=done');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die($e->getMessage());
    }
}

// Include Header
include 'templates/header.php';
?>

<div class="max-w-6xl mx-auto space-y-6 font-sans">

    <?php if ($done || isset($_GET['status'])): ?>
        <div
            class="bg-white rounded-3xl p-10 text-center shadow-lg border border-gray-100 mt-6 bg-gradient-to-br from-white to-slate-50 dark:from-dark-surface dark:to-dark-bg dark:border-dark-surface2">
            <div
                class="w-20 h-20 bg-gradient-to-br from-green-100 to-green-200 rounded-2xl flex items-center justify-center mx-auto mb-6 text-3xl text-green-600 shadow-sm dark:from-green-900/30 dark:to-green-800/30 dark:text-green-400">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 mb-2 dark:text-white">Laporan Terkirim! 🎉</h1>
            <p class="text-sm font-bold text-green-500 uppercase tracking-widest mb-6">Amalan hari ini telah tercatat</p>
            <p class="text-slate-600 mb-8 dark:text-slate-400 max-w-md mx-auto">
                Poin dan streak kamu sudah diperbarui. Tetap semangat untuk konsisten menjaga amalan ya!
            </p>
            <a href="dashboard.php"
                class="inline-flex items-center justify-center gap-2 px-8 py-3 bg-primary-600 text-white rounded-2xl font-bold uppercase tracking-wide hover:bg-primary-700 hover:-translate-y-1 hover:shadow-lg transition-all duration-300">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    <?php else: ?>

        <form action="" method="POST" id="formNafsiyah">

            <div
                class="relative flex flex-col sm:flex-row justify-between items-center bg-gradient-to-br from-rose-50 to-rose-100 rounded-2xl p-6 mb-6 border border-rose-200 overflow-hidden dark:from-rose-900/20 dark:to-rose-800/20 dark:border-rose-500/30 transition-all duration-300">
                <div class="flex items-center gap-4 mb-4 sm:mb-0 relative z-10">
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white text-lg shadow-lg shadow-rose-500/30">
                        <i class="fas fa-moon moon-icon transition-transform duration-500"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-rose-600 dark:text-rose-300 text-base">Mode Udzur Syar'i (Haid)</h3>
                        <p class="text-xs font-semibold text-rose-500 dark:text-rose-400">Aktifkan untuk amalan ibadah
                            mahdhah</p>
                    </div>
                </div>

                <label for="modeHaid" class="flex items-center cursor-pointer relative z-10">
                    <div class="relative">
                        <input type="checkbox" id="modeHaid" name="mode_haid" class="sr-only peer">
                        <div
                            class="w-14 h-8 bg-slate-300 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:bg-rose-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-7 after:w-7 after:transition-all">
                        </div>
                    </div>
                </label>
            </div>

            <div
                class="bg-white rounded-3xl p-6 md:p-8 mb-6 shadow-sm border border-slate-100 dark:bg-dark-surface dark:border-dark-surface2">
                <h2 class="text-2xl font-extrabold text-slate-800 mb-8 flex items-center gap-3 dark:text-white">
                    <span
                        class="w-8 h-8 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center text-sm dark:bg-primary-500/20 dark:text-primary-400">
                        <i class="fas fa-clipboard-check"></i>
                    </span>
                    Laporan Amalan
                </h2>

                <div class="space-y-4">
                    <?php
                    $q = $pdo->query("SELECT * FROM nafsiyah_items ORDER BY urutan ASC")->fetchAll();
                    foreach ($q as $i):
                        $activity = $i['activity_name'];

                        // LOGIKA DINAMIS: Ambil dari kolom is_udzur
                        $is_kena_udzur = ($i['is_udzur'] == 1);

                        // Icon Mapping Sederhana
                        $iconClass = 'fa-star';
                        $colorClass = 'from-primary-50 to-primary-100 text-primary-600';
                        if (stripos($activity, 'Tahajjud') !== false) {
                            $iconClass = 'fa-moon';
                            $colorClass = 'from-indigo-50 to-indigo-100 text-indigo-600';
                        } elseif (stripos($activity, 'Sedekah') !== false) {
                            $iconClass = 'fa-hand-holding-usd';
                            $colorClass = 'from-amber-50 to-amber-100 text-amber-600';
                        } elseif (stripos($activity, 'Quran') !== false || stripos($activity, 'Tilawah') !== false) {
                            $iconClass = 'fa-book-quran';
                            $colorClass = 'from-green-50 to-green-100 text-green-600';
                        } elseif (stripos($activity, 'Olahraga') !== false) {
                            $iconClass = 'fa-dumbbell';
                            $colorClass = 'from-red-50 to-red-100 text-red-600';
                        } elseif (stripos($activity, 'Makan') !== false) {
                            $iconClass = 'fa-utensils';
                            $colorClass = 'from-orange-50 to-orange-100 text-orange-600';
                        } elseif (stripos($activity, 'Belajar') !== false || stripos($activity, 'Halqoh') !== false) {
                            $iconClass = 'fa-book-open';
                            $colorClass = 'from-pink-50 to-pink-100 text-pink-600';
                        }

                        $opts = !empty($i['sub_komponen']) ? explode(',', $i['sub_komponen']) : ["Selesai:10", "Tidak Mengerjakan:0"];
                        ?>

                        <div
                            class="group relative bg-slate-50 rounded-2xl p-5 border border-slate-200 hover:border-primary-300 hover:bg-white hover:shadow-lg transition-all duration-300 dark:bg-dark-surface2/50 dark:border-slate-700 amalan-item <?php echo $is_kena_udzur ? 'udzur-target' : ''; ?> ">

                            <div class="flex items-center gap-4 mb-4">
                                <div
                                    class="w-12 h-12 rounded-xl bg-gradient-to-br <?php echo $colorClass; ?> flex items-center justify-center text-xl shadow-sm flex-shrink-0 dark:bg-none dark:bg-slate-700 dark:text-primary-400">
                                    <i class="fas <?php echo $iconClass; ?>"></i>
                                </div>
                                <h3 class="font-bold text-slate-800 text-lg flex-1 dark:text-slate-200">
                                    <?php echo htmlspecialchars($activity); ?>
                                </h3>
                            </div>

                            <div class="normal-options grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <?php foreach ($opts as $o):
                                    $x = explode(':', $o);
                                    $l = trim($x[0]);
                                    $s = $x[1] ?? 0;
                                    ?>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="item[<?php echo $i['id']; ?>]"
                                            value="<?php echo $l . '|' . $s; ?>" class="peer sr-only option-radio" <?php echo !$is_kena_udzur ? 'required' : ''; ?>>
                                        <div
                                            class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-white transition-all peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:bg-dark-surface dark:border-slate-600 dark:peer-checked:bg-slate-700">
                                            <div
                                                class="w-5 h-5 rounded-full border-2 border-slate-300 bg-white flex items-center justify-center peer-checked:border-primary-500 peer-checked:bg-primary-500 relative">
                                                <!-- Centang di dalam radio button -->
                                                <div
                                                    class="checkmark absolute inset-0 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity duration-200">
                                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4"
                                                            d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <span
                                                class="font-semibold text-sm text-slate-700 dark:text-slate-300"><?php echo $l; ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div class="udzur-view hidden mt-2">
                                <div
                                    class="flex items-center gap-3 p-4 bg-rose-50 border border-rose-200 rounded-xl dark:bg-rose-900/20 dark:border-rose-500/30">
                                    <i class="fas fa-moon text-rose-500 text-xl"></i>
                                    <div>
                                        <div class="font-bold text-rose-600 text-sm dark:text-rose-400">Sedang Udzur Syar'i
                                        </div>
                                        <div class="text-xs text-rose-500 font-medium dark:text-rose-300">Otomatis tercatat (5
                                            poin apresiasi)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit"
                class="w-full py-4 px-6 bg-gradient-to-r from-primary-500 to-green-500 text-white rounded-2xl font-extrabold text-lg uppercase tracking-wider shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-3 group">
                <i class="fas fa-paper-plane group-hover:rotate-12 transition-transform"></i> Kirim Laporan
            </button>
        </form>
    <?php endif; ?>
</div>

<script>
    const modeHaidCheckbox = document.getElementById('modeHaid');

    if (modeHaidCheckbox) {
        const udzurCards = document.querySelectorAll('.udzur-target');
        const moonIcon = document.querySelector('.moon-icon');

        function toggleUdzur(isHaid) {
            moonIcon.style.transform = isHaid ? "rotate(360deg) scale(1.2)" : "rotate(0deg) scale(1)";
            udzurCards.forEach(card => {
                const normalOpts = card.querySelector('.normal-options');
                const udzurView = card.querySelector('.udzur-view');
                const radios = card.querySelectorAll('input[type="radio"]');

                if (isHaid) {
                    normalOpts.classList.add('hidden');
                    udzurView.classList.remove('hidden');
                    card.classList.add('border-rose-200', 'bg-rose-50/50', 'dark:border-rose-900', 'dark:bg-rose-900/10');
                    radios.forEach(r => r.removeAttribute('required'));
                } else {
                    normalOpts.classList.remove('hidden');
                    udzurView.classList.add('hidden');
                    card.classList.remove('border-rose-200', 'bg-rose-50/50', 'dark:border-rose-900', 'dark:bg-rose-900/10');
                    radios.forEach(r => r.setAttribute('required', 'required'));
                }
            });
        }
        modeHaidCheckbox.addEventListener('change', (e) => toggleUdzur(e.target.checked));
    }
</script>

<?php include 'templates/footer.php'; ?>