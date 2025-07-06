<?php
$pageTitle = 'Lihat Laporan';
require_once 'templates/header.php';
require_once __DIR__ . '/../config.php';

$message = '';

// Filter parameters
$filter_modul = filter_var($_GET['filter_modul'] ?? '', FILTER_SANITIZE_NUMBER_INT);
$filter_mahasiswa = filter_var($_GET['filter_mahasiswa'] ?? '', FILTER_SANITIZE_STRING);
$filter_status = filter_var($_GET['filter_status'] ?? '', FILTER_SANITIZE_STRING);

// Build SQL query
$sql = "SELECT 
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
            mpr.nama_praktikum
        FROM laporan_praktikum lp
        JOIN users u ON lp.user_id = u.id
        JOIN modul_praktikum mp ON lp.modul_id = mp.id
        JOIN mata_praktikum mpr ON mp.praktikum_id = mpr.id
        WHERE 1=1"; // Placeholder for dynamic WHERE clauses

$params = [];
$types = '';

if (!empty($filter_modul)) {
    $sql .= " AND mp.id = ?";
    $params[] = $filter_modul;
    $types .= 'i';
}
if (!empty($filter_mahasiswa)) {
    $sql .= " AND u.nama LIKE ?";
    $params[] = '%' . $filter_mahasiswa . '%';
    $types .= 's';
}
if (!empty($filter_status) && in_array($filter_status, ['not_graded', 'graded'])) {
    $sql .= " AND lp.status_laporan = ?";
    $params[] = $filter_status;
    $types .= 's';
}

$sql .= " ORDER BY lp.tanggal_unggah DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$reports = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}
$stmt->close();

// Get all modules for filter dropdown
$modules_for_filter = [];
$sql_modules_filter = "SELECT id, judul_modul, praktikum_id FROM modul_praktikum ORDER BY judul_modul ASC";
$result_modules_filter = $conn->query($sql_modules_filter);
if ($result_modules_filter->num_rows > 0) {
    while ($row = $result_modules_filter->fetch_assoc()) {
        $modules_for_filter[] = $row;
    }
}

// Get all students for filter dropdown (optional, could be text input for search)
// For simplicity, we'll keep it as text input for now as per the spec "berdasarkan mahasiswa"
?>

<div class="bg-gray-800 p-8 lg:p-10 rounded-lg shadow-xl mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold text-white mb-6">Daftar Laporan Masuk</h1>
    <?php echo $message; ?>

    <!-- Filter Form -->
    <div class="mb-8 p-6 lg:p-8 border border-gray-600 rounded-lg bg-gray-700">
        <h2 class="text-2xl lg:text-3xl font-semibold text-white mb-5">Filter Laporan</h2>
        <form action="view_reports.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="filter_modul" class="block text-lg font-medium text-gray-300 mb-2">Modul</label>
                <select id="filter_modul" name="filter_modul" class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Semua Modul --</option>
                    <?php foreach ($modules_for_filter as $mod): ?>
                        <option value="<?php echo $mod['id']; ?>" <?php echo ($filter_modul == $mod['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($mod['judul_modul']); ?> (Praktikum ID: <?php echo htmlspecialchars($mod['praktikum_id']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter_mahasiswa" class="block text-lg font-medium text-gray-300 mb-2">Nama Mahasiswa</label>
                <input type="text" id="filter_mahasiswa" name="filter_mahasiswa" value="<?php echo htmlspecialchars($filter_mahasiswa); ?>" class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Cari nama mahasiswa...">
            </div>
            <div>
                <label for="filter_status" class="block text-lg font-medium text-gray-300 mb-2">Status</label>
                <select id="filter_status" name="filter_status" class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Semua Status --</option>
                    <option value="not_graded" <?php echo ($filter_status == 'not_graded') ? 'selected' : ''; ?>>Belum Dinilai</option>
                    <option value="graded" <?php echo ($filter_status == 'graded') ? 'selected' : ''; ?>>Sudah Dinilai</option>
                </select>
            </div>
            <div class="md:col-span-3 flex justify-end space-x-4 mt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 shadow-md text-lg">
                    <i class="fas fa-filter mr-3"></i>Terapkan Filter
                </button>
                <a href="view_reports.php" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 text-lg">
                    <i class="fas fa-sync-alt mr-3"></i>Reset Filter
                </a>
            </div>
        </form>
    </div>

    <?php if (empty($reports)): ?>
        <p class="text-gray-400 text-lg">Tidak ada laporan yang ditemukan dengan filter yang diterapkan.</p>
    <?php else: ?>
        <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
            <table class="w-full text-base text-left text-gray-400">
                <thead class="text-sm text-gray-300 uppercase bg-gray-700">
                    <tr>
                        <th scope="col" class="py-4 px-6">Mahasiswa</th>
                        <th scope="col" class="py-4 px-6">Praktikum</th>
                        <th scope="col" class="py-4 px-6">Modul</th>
                        <th scope="col" class="py-4 px-6">Tanggal Unggah</th>
                        <th scope="col" class="py-4 px-6">Status</th>
                        <th scope="col" class="py-4 px-6">Nilai</th>
                        <th scope="col" class="py-4 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr class="bg-gray-800 border-b border-gray-700 hover:bg-gray-700">
                            <td class="py-4 px-6 font-medium text-white whitespace-nowrap text-lg">
                                <?php echo htmlspecialchars($report['nama_mahasiswa']); ?><br>
                                <span class="text-base text-gray-400"><?php echo htmlspecialchars($report['email_mahasiswa']); ?></span>
                            </td>
                            <td class="py-4 px-6 text-lg"><?php echo htmlspecialchars($report['nama_praktikum']); ?></td>
                            <td class="py-4 px-6 text-lg"><?php echo htmlspecialchars($report['judul_modul']); ?></td>
                            <td class="py-4 px-6 text-lg"><?php echo date('d M Y H:i', strtotime($report['tanggal_unggah'])); ?></td>
                            <td class="py-4 px-6">
                                <span class="px-3 py-1 rounded-full text-base font-semibold 
                                    <?php echo ($report['status_laporan'] == 'not_graded') ? 'bg-yellow-600 text-white' : 'bg-green-600 text-white'; ?>">
                                    <?php echo ($report['status_laporan'] == 'not_graded') ? 'Belum Dinilai' : 'Sudah Dinilai'; ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-lg">
                                <?php echo ($report['nilai'] !== null) ? htmlspecialchars($report['nilai']) : '-'; ?>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <a href="grade_report.php?id=<?php echo $report['laporan_id']; ?>" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-md text-sm transition-colors duration-300 shadow-sm">
                                    <i class="fas fa-clipboard-check mr-2"></i>Nilai Laporan
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
require_once 'templates/footer.php';
?>
