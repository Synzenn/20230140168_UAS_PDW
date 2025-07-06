<?php
$pageTitle = 'Dashboard';
require_once 'templates/header.php'; // Menggunakan header asisten
require_once __DIR__ . '/../config.php'; // Pastikan path benar

// --- Ambil Data untuk Kartu Statistik ---
$total_modul = 0;
$total_laporan_masuk = 0;
$laporan_belum_dinilai = 0;
$recent_activities = [];

// Query Total Modul Diajarkan
$sql_total_modul = "SELECT COUNT(id) AS total FROM modul_praktikum";
$result_total_modul = $conn->query($sql_total_modul);
if ($result_total_modul) {
    $total_modul = $result_total_modul->fetch_assoc()['total'];
}

// Query Total Laporan Masuk
$sql_total_laporan = "SELECT COUNT(id) AS total FROM laporan_praktikum";
$result_total_laporan = $conn->query($sql_total_laporan);
if ($result_total_laporan) {
    $total_laporan_masuk = $result_total_laporan->fetch_assoc()['total'];
}

// Query Laporan Belum Dinilai
$sql_belum_dinilai = "SELECT COUNT(id) AS total FROM laporan_praktikum WHERE status_laporan = 'not_graded'";
$result_belum_dinilai = $conn->query($sql_belum_dinilai);
if ($result_belum_dinilai) {
    $laporan_belum_dinilai = $result_belum_dinilai->fetch_assoc()['total'];
}

// Query Aktivitas Laporan Terbaru (misal 3 aktivitas terbaru)
$sql_recent_activities = "SELECT 
                            lp.tanggal_unggah, 
                            u.nama AS nama_mahasiswa, 
                            mp.judul_modul 
                          FROM laporan_praktikum lp
                          JOIN users u ON lp.user_id = u.id
                          JOIN modul_praktikum mp ON lp.modul_id = mp.id
                          ORDER BY lp.tanggal_unggah DESC 
                          LIMIT 3";
$result_recent_activities = $conn->query($sql_recent_activities);
if ($result_recent_activities && $result_recent_activities->num_rows > 0) {
    while ($row = $result_recent_activities->fetch_assoc()) {
        $recent_activities[] = $row;
    }
}

$conn->close(); // Tutup koneksi database setelah semua data diambil
?>

<h1 class="text-4xl lg:text-5xl font-extrabold text-white mb-8">Dashboard</h1>

<!-- Statistik Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-10">
    <!-- Total Modul Diajarkan Card -->
    <div class="bg-gray-800 p-8 rounded-lg shadow-xl flex items-center space-x-6 transform transition-transform duration-300 hover:scale-105">
        <div class="p-4 bg-blue-600 rounded-full text-white text-3xl">
            <i class="fas fa-book"></i>
        </div>
        <div>
            <p class="text-gray-400 text-base">Total Modul Diajarkan</p>
            <p class="text-white text-4xl font-bold"><?php echo $total_modul; ?></p>
        </div>
    </div>

    <!-- Total Laporan Masuk Card -->
    <div class="bg-gray-800 p-8 rounded-lg shadow-xl flex items-center space-x-6 transform transition-transform duration-300 hover:scale-105">
        <div class="p-4 bg-green-600 rounded-full text-white text-3xl">
            <i class="fas fa-file-invoice"></i>
        </div>
        <div>
            <p class="text-gray-400 text-base">Total Laporan Masuk</p>
            <p class="text-white text-4xl font-bold"><?php echo $total_laporan_masuk; ?></p>
        </div>
    </div>

    <!-- Laporan Belum Dinilai Card -->
    <div class="bg-gray-800 p-8 rounded-lg shadow-xl flex items-center space-x-6 transform transition-transform duration-300 hover:scale-105">
        <div class="p-4 bg-orange-600 rounded-full text-white text-3xl">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div>
            <p class="text-gray-400 text-base">Laporan Belum Dinilai</p>
            <p class="text-white text-4xl font-bold"><?php echo $laporan_belum_dinilai; ?></p>
        </div>
    </div>
</div>

<!-- Aktivitas Laporan Terbaru Section -->
<div class="bg-gray-800 p-8 rounded-lg shadow-xl">
    <h2 class="text-3xl font-semibold text-white mb-6 border-b border-gray-700 pb-4">Aktivitas Laporan Terbaru</h2>
    <?php if (empty($recent_activities)): ?>
        <p class="text-gray-400 text-lg">Belum ada aktivitas laporan terbaru.</p>
    <?php else: ?>
        <ul class="space-y-6">
            <?php foreach ($recent_activities as $activity): ?>
                <li class="flex items-start space-x-4">
                    <div class="p-3 bg-gray-700 rounded-full text-gray-300 text-2xl">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div>
                        <p class="text-white text-lg font-medium">
                            <span class="text-blue-400"><?php echo htmlspecialchars($activity['nama_mahasiswa']); ?></span>
                            mengumpulkan laporan untuk modul 
                            <span class="text-green-400"><?php echo htmlspecialchars($activity['judul_modul']); ?></span>
                        </p>
                        <p class="text-gray-400 text-base mt-1"><?php echo date('d M Y H:i', strtotime($activity['tanggal_unggah'])); ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer.php'; // Menggunakan footer asisten
?>
