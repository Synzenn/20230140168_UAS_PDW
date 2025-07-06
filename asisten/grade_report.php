<?php
$pageTitle = 'Nilai Laporan';
require_once 'templates/header.php';
require_once __DIR__ . '/../config.php';

$laporan_id = filter_var($_GET['id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
$message = '';

if (!$laporan_id) {
    echo '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">ID Laporan tidak ditemukan.</span>
          </div>';
    require_once 'templates/footer.php';
    exit();
}

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grade'])) {
    $nilai = filter_var($_POST['nilai'], FILTER_SANITIZE_NUMBER_INT);
    $feedback = trim($_POST['feedback']);

    if ($nilai === false || $nilai < 0 || $nilai > 100) {
        $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                        <strong class="font-bold">Gagal!</strong>
                        <span class="block sm:inline">Nilai harus angka antara 0-100.</span>
                    </div>';
    } else {
        $sql = "UPDATE laporan_praktikum SET nilai = ?, feedback = ?, status_laporan = 'graded' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $nilai, $feedback, $laporan_id);
        if ($stmt->execute()) {
            $message = '<div class="bg-green-700 border border-green-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Berhasil!</strong>
                            <span class="block sm:inline">Nilai dan feedback berhasil disimpan.</span>
                        </div>';
        } else {
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Terjadi kesalahan: ' . htmlspecialchars($conn->error) . '</span>
                        </div>';
        }
        $stmt->close();
    }
}

// Fetch report details
$report = null;
$sql_report = "SELECT 
                lp.id AS laporan_id,
                lp.file_laporan,
                lp.tanggal_unggah,
                lp.nilai,
                lp.feedback,
                lp.status_laporan,
                u.nama AS nama_mahasiswa,
                u.email AS email_mahasiswa,
                mp.judul_modul,
                mp.deskripsi_modul,
                mpr.nama_praktikum,
                mpr.kode_praktikum
            FROM laporan_praktikum lp
            JOIN users u ON lp.user_id = u.id
            JOIN modul_praktikum mp ON lp.modul_id = mp.id
            JOIN mata_praktikum mpr ON mp.praktikum_id = mpr.id
            WHERE lp.id = ?";
$stmt_report = $conn->prepare($sql_report);
$stmt_report->bind_param("i", $laporan_id);
$stmt_report->execute();
$result_report = $stmt_report->get_result();
if ($result_report->num_rows > 0) {
    $report = $result_report->fetch_assoc();
}
$stmt_report->close();

if (!$report) {
    echo '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">Laporan tidak ditemukan.</span>
          </div>';
    require_once 'templates/footer.php';
    exit();
}
?>

<div class="bg-gray-800 p-8 lg:p-10 rounded-lg shadow-xl mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold text-white mb-6">Nilai Laporan</h1>
    <a href="view_reports.php" class="inline-flex items-center text-blue-400 hover:underline mb-8 text-lg">
        <i class="fas fa-arrow-left mr-3"></i>Kembali ke Daftar Laporan
    </a>
    <?php echo $message; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-10">
        <!-- Detail Laporan -->
        <div class="bg-gray-700 border border-gray-600 rounded-lg p-6 lg:p-8 shadow-md">
            <h2 class="text-2xl font-semibold text-white mb-5 border-b border-gray-600 pb-3">Detail Laporan</h2>
            <p class="mb-3 text-lg"><strong class="text-gray-300">Mahasiswa:</strong> <?php echo htmlspecialchars($report['nama_mahasiswa']); ?> (<?php echo htmlspecialchars($report['email_mahasiswa']); ?>)</p>
            <p class="mb-3 text-lg"><strong class="text-gray-300">Praktikum:</strong> <?php echo htmlspecialchars($report['nama_praktikum']); ?> (<?php echo htmlspecialchars($report['kode_praktikum']); ?>)</p>
            <p class="mb-3 text-lg"><strong class="text-gray-300">Modul:</strong> <?php echo htmlspecialchars($report['judul_modul']); ?></p>
            <p class="mb-3 text-lg"><strong class="text-gray-300">Tanggal Unggah:</strong> <?php echo date('d M Y H:i', strtotime($report['tanggal_unggah'])); ?></p>
            <p class="mb-6 text-lg"><strong class="text-gray-300">Status:</strong> 
                <span class="px-3 py-1 rounded-full text-base font-semibold 
                    <?php echo ($report['status_laporan'] == 'not_graded') ? 'bg-yellow-600 text-white' : 'bg-green-600 text-white'; ?>">
                    <?php echo ($report['status_laporan'] == 'not_graded') ? 'Belum Dinilai' : 'Sudah Dinilai'; ?>
                </span>
            </p>
            
            <div class="mt-6">
                <h3 class="text-xl font-medium text-gray-300 mb-3">File Laporan:</h3>
                <?php if (!empty($report['file_laporan'])): ?>
                    <a href="../../uploads/laporan/<?php echo htmlspecialchars($report['file_laporan']); ?>" target="_blank" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md text-lg transition-colors duration-300 shadow-md">
                        <i class="fas fa-download mr-3"></i>Unduh Laporan
                    </a>
                <?php else: ?>
                    <p class="text-red-500 text-lg">File laporan tidak ditemukan.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Form Penilaian -->
        <div class="bg-gray-700 border border-blue-600 rounded-lg p-6 lg:p-8 shadow-md">
            <h2 class="text-2xl font-semibold text-blue-400 mb-5 border-b border-gray-600 pb-3">Form Penilaian</h2>
            <form action="grade_report.php?id=<?php echo $laporan_id; ?>" method="POST" class="space-y-6">
                <div>
                    <label for="nilai" class="block text-lg font-medium text-gray-300 mb-2">Nilai (0-100)</label>
                    <input type="number" id="nilai" name="nilai" min="0" max="100" value="<?php echo htmlspecialchars($report['nilai'] ?? ''); ?>" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="feedback" class="block text-lg font-medium text-gray-300 mb-2">Feedback</label>
                    <textarea id="feedback" name="feedback" rows="6" class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($report['feedback'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="submit_grade" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 shadow-md text-lg">
                    <i class="fas fa-save mr-3"></i>Simpan Nilai
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once 'templates/footer.php';
?>
