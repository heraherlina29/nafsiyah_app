<?php
require_once __DIR__ . '/../koneksi.php';

// Ambil filter bulan & tahun
$bulan_num = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

// Daftar nama bulan untuk judul
$months_name = [
    "01"=>"Januari", "02"=>"Februari", "03"=>"Maret", "04"=>"April", "05"=>"Mei", "06"=>"Juni", 
    "07"=>"Juli", "08"=>"Agustus", "09"=>"September", "10"=>"Oktober", "11"=>"November", "12"=>"Desember"
];
$nama_bulan = $months_name[$bulan_num];

$filename = "Rekapitulasi_Nafsiyah_" . $nama_bulan . "_" . $tahun . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");

try {
    // 1. Ambil semua jenis amalan yang ada di sistem agar kolomnya dinamis
    $stmt_items = $pdo->query("SELECT activity_name FROM nafsiyah_items ORDER BY id ASC");
    $all_items = $stmt_items->fetchAll(PDO::FETCH_COLUMN);

    // 2. Bangun Query SUM(CASE WHEN...) secara otomatis berdasarkan data di database
    $pivot_query = "";
    foreach ($all_items as $item) {
        $safe_name = str_replace("'", "''", $item); // Handle jika ada tanda petik
        $pivot_query .= ", SUM(CASE WHEN i.activity_name = '$safe_name' AND l.status = 'selesai' THEN 1 ELSE 0 END) as `" . $item . "`";
    }

    // 3. Eksekusi query rekapitulasi
    $sql = "
        SELECT 
            u.nama_lengkap,
            u.username
            $pivot_query,
            SUM(l.poin_didapat) as total_poin_bulanan
        FROM users u
        LEFT JOIN nafsiyah_logs l ON u.id = l.user_id AND MONTH(l.log_date) = ? AND YEAR(l.log_date) = ?
        LEFT JOIN nafsiyah_items i ON l.item_id = i.id
        GROUP BY u.id
        ORDER BY total_poin_bulanan DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$bulan_num, $tahun]);
    $rekap = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Gagal menarik data: " . $e->getMessage());
}
?>

<table border="1">
    <thead>
        <tr>
            <th colspan="<?= count($all_items) + 3 ?>" style="background-color: #f3f4f6; font-weight: bold; height: 35px; text-align: center; font-size: 14px;">
                REKAPITULASI POIN DAN AMALAN NAFSIYAH BULAN <?= strtoupper($nama_bulan) ?> <?= $tahun ?>
            </th>
        </tr>
        <tr style="background-color: #805AD5; color: white; font-weight: bold; text-align: center;">
            <th width="50">No</th>
            <th width="200">Nama Karyawan</th>
            <?php foreach ($all_items as $item): ?>
                <th width="100"><?= htmlspecialchars($item) ?></th>
            <?php endforeach; ?>
            <th width="120">Total Poin Sebulan</th>
        </tr>
    </thead>
    <tbody>
        <?php if($rekap): $no = 1; foreach ($rekap as $row): ?>
        <tr>
            <td style="text-align: center;"><?= $no++ ?></td>
            <td><?= htmlspecialchars($row['nama_lengkap'] ?? $row['username']) ?></td>
            <?php foreach ($all_items as $item): ?>
                <td style="text-align: center;"><?= $row[$item] ?? 0 ?></td>
            <?php endforeach; ?>
            <td style="text-align: center; font-weight: bold; color: #4C51BF; background-color: #F7FAFC;">
                <?= number_format($row['total_poin_bulanan'] ?? 0) ?>
            </td>
        </tr>
        <?php endforeach; else: ?>
        <tr>
            <td colspan="<?= count($all_items) + 3 ?>" style="text-align: center; padding: 20px;">Tidak ada data pada periode ini.</td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>