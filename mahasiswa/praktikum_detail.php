<?php
$pageTitle = 'Detail Praktikum';
// Tidak ada activePage karena ini halaman detail dinamis
require_once 'templates/header_mahasiswa.php';
require_once __DIR__ . '/../config.php'; // Pastikan path benar

$praktikum_id = filter_var($_GET['id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
$user_id = $_SESSION['user_id'];
$message = '';

if (!$praktikum_id) {
    echo '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">ID Praktikum tidak ditemukan.</span>
          </div>';
    require_once 'templates/footer_mahasiswa.php';
    exit();
}

// Cek apakah mahasiswa terdaftar di praktikum ini
$check_reg_sql = "SELECT id FROM registrasi_praktikum WHERE user_id = ? AND praktikum_id = ?";
$check_reg_stmt = $conn->prepare($check_reg_sql);
$check_reg_stmt->bind_param("ii", $user_id, $praktikum_id);
$check_reg_stmt->execute();
$check_reg_stmt->store_result();
if ($check_reg_stmt->num_rows === 0) {
    echo '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
            <strong class="font-bold">Akses Ditolak!</strong>
            <span class="block sm:inline">Anda tidak terdaftar di praktikum ini.</span>
          </div>';
    require_once 'templates/footer_mahasiswa.php';
    exit();
}
$check_reg_stmt->close();

// Ambil detail praktikum
$praktikum = null;
$sql_praktikum = "SELECT nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum WHERE id = ?";
$stmt_praktikum = $conn->prepare($sql_praktikum);
$stmt_praktikum->bind_param("i", $praktikum_id);
$stmt_praktikum->execute();
$result_praktikum = $stmt_praktikum->get_result();
if ($result_praktikum->num_rows > 0) {
    $praktikum = $result_praktikum->fetch_assoc();
}
$stmt_praktikum->close();

if (!$praktikum) {
    echo '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">Praktikum tidak ditemukan.</span>
          </div>';
    require_once 'templates/footer_mahasiswa.php';
    exit();
}

// Handle pengumpulan laporan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report']) && isset($_POST['modul_id'])) {
    $modul_id = filter_var($_POST['modul_id'], FILTER_SANITIZE_NUMBER_INT);

    // Cek apakah file diunggah
    if (isset($_FILES['file_laporan']) && $_FILES['file_laporan']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['file_laporan']['tmp_name'];
        $file_name = basename($_FILES['file_laporan']['name']);
        $file_size = $_FILES['file_laporan']['size'];
        $file_type = $_FILES['file_laporan']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_ext = ['pdf', 'doc', 'docx'];
        if (!in_array($file_ext, $allowed_ext)) {
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Hanya file PDF, DOC, dan DOCX yang diizinkan.</span>
                        </div>';
        } elseif ($file_size > 10 * 1024 * 1024) { // Max 10MB
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Ukuran file terlalu besar (maks 10MB).</span>
                        </div>';
        } else {
            // Buat direktori upload jika belum ada
            $upload_dir = __DIR__ . '/../../uploads/laporan/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $new_file_name = uniqid('laporan_') . '.' . $file_ext;
            $file_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $file_path)) {
                // Cek apakah laporan untuk modul ini sudah ada dari user ini
                $check_report_sql = "SELECT id FROM laporan_praktikum WHERE modul_id = ? AND user_id = ?";
                $check_report_stmt = $conn->prepare($check_report_sql);
                $check_report_stmt->bind_param("ii", $modul_id, $user_id);
                $check_report_stmt->execute();
                $check_report_stmt->store_result();

                if ($check_report_stmt->num_rows > 0) {
                    // Update laporan yang sudah ada
                    $update_sql = "UPDATE laporan_praktikum SET file_laporan = ?, tanggal_unggah = CURRENT_TIMESTAMP, nilai = NULL, feedback = NULL, status_laporan = 'not_graded' WHERE modul_id = ? AND user_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("sii", $new_file_name, $modul_id, $user_id);
                    if ($update_stmt->execute()) {
                        $message = '<div class="bg-green-700 border border-green-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                        <strong class="font-bold">Berhasil!</strong>
                                        <span class="block sm:inline">Laporan berhasil diperbarui.</span>
                                    </div>';
                    } else {
                        $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                        <strong class="font-bold">Gagal!</strong>
                                        <span class="block sm:inline">Terjadi kesalahan saat memperbarui laporan: ' . htmlspecialchars($conn->error) . '</span>
                                    </div>';
                    }
                    $update_stmt->close();
                } else {
                    // Insert laporan baru
                    $insert_sql = "INSERT INTO laporan_praktikum (modul_id, user_id, file_laporan, status_laporan) VALUES (?, ?, ?, 'not_graded')";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("iis", $modul_id, $user_id, $new_file_name);
                    if ($insert_stmt->execute()) {
                        $message = '<div class="bg-green-700 border border-green-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                        <strong class="font-bold">Berhasil!</strong>
                                        <span class="block sm:inline">Laporan berhasil diunggah.</span>
                                    </div>';
                    } else {
                        $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                        <strong class="font-bold">Gagal!</strong>
                                        <span class="block sm:inline">Terjadi kesalahan saat mengunggah laporan: ' . htmlspecialchars($conn->error) . '</span>
                                    </div>';
                    }
                    $insert_stmt->close();
                }
                $check_report_stmt->close();
            } else {
                $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                <strong class="font-bold">Gagal!</strong>
                                <span class="block sm:inline">Gagal memindahkan file yang diunggah.</span>
                            </div>';
            }
        }
    } else {
        $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                        <strong class="font-bold">Gagal!</strong>
                        <span class="block sm:inline">Mohon pilih file laporan.</span>
                    </div>';
    }
}

// Ambil modul-modul untuk praktikum ini beserta status laporan mahasiswa
$modules = [];
$sql_modules = "SELECT 
                    mp.id AS modul_id, 
                    mp.judul_modul, 
                    mp.deskripsi_modul, 
                    mp.file_materi,
                    lp.id AS laporan_id,
                    lp.file_laporan,
                    lp.tanggal_unggah,
                    lp.nilai,
                    lp.feedback,
                    lp.status_laporan
                FROM modul_praktikum mp
                LEFT JOIN laporan_praktikum lp ON mp.id = lp.modul_id AND lp.user_id = ?
                WHERE mp.praktikum_id = ?
                ORDER BY mp.created_at ASC";
$stmt_modules = $conn->prepare($sql_modules);
$stmt_modules->bind_param("ii", $user_id, $praktikum_id);
$stmt_modules->execute();
$result_modules = $stmt_modules->get_result();
if ($result_modules->num_rows > 0) {
    while ($row = $result_modules->fetch_assoc()) {
        $modules[] = $row;
    }
}
$stmt_modules->close();
?>

<div class="bg-gray-800 p-8 lg:p-10 rounded-lg shadow-xl mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold text-white mb-4"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h1>
    <p class="text-lg text-gray-300 mb-3">Kode Praktikum: <span class="font-medium"><?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></span></p>
    <p class="text-gray-300 text-xl mb-8"><?php echo nl2br(htmlspecialchars($praktikum['deskripsi'])); ?></p>

    <?php echo $message; // Tampilkan pesan ?>

    <h2 class="text-2xl lg:text-3xl font-semibold text-white mb-6 border-b border-gray-700 pb-3">Daftar Modul & Tugas</h2>

    <?php if (empty($modules)): ?>
        <p class="text-gray-400 text-lg">Belum ada modul yang tersedia untuk praktikum ini.</p>
    <?php else: ?>
        <div class="space-y-8">
            <?php foreach ($modules as $modul): ?>
                <div class="bg-gray-700 border border-gray-600 rounded-lg p-6 lg:p-8 shadow-md">
                    <h3 class="text-2xl font-semibold text-blue-400 mb-3"><?php echo htmlspecialchars($modul['judul_modul']); ?></h3>
                    <p class="text-gray-300 text-lg mb-6"><?php echo nl2br(htmlspecialchars($modul['deskripsi_modul'])); ?></p>

                    <!-- Mengunduh Materi -->
                    <?php 
                    $file_materi_path = '../../uploads/materi/' . htmlspecialchars($modul['file_materi']);
                    $file_exists = file_exists($file_materi_path);
                    ?>
                    <div class="mb-6">
                        <h4 class="text-xl font-medium text-gray-300 mb-3">Materi Modul:</h4>
                        <?php if (!empty($modul['file_materi']) && $file_exists): ?>
                            <a href="<?php echo $file_materi_path; ?>" target="_blank" class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 shadow-md text-lg">
                                <i class="fas fa-download mr-3"></i>Unduh Materi
                            </a>
                        <?php else: ?>
                            <p class="text-gray-400 text-lg">Tidak ada materi yang diunggah atau file tidak ditemukan.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Mengumpulkan Laporan -->
                    <div class="mb-6">
                        <h4 class="text-xl font-medium text-gray-300 mb-3">Unggah Laporan:</h4>
                        <form action="praktikum_detail.php?id=<?php echo $praktikum_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="modul_id" value="<?php echo $modul['modul_id']; ?>">
                            <input type="hidden" name="submit_report" value="1">
                            <div>
                                <label for="file_laporan_<?php echo $modul['modul_id']; ?>" class="block text-lg font-medium text-gray-300 mb-2">Pilih File Laporan (PDF/DOC/DOCX, maks 10MB):</label>
                                <input type="file" id="file_laporan_<?php echo $modul['modul_id']; ?>" name="file_laporan" accept=".pdf,.doc,.docx" class="mt-1 block w-full text-lg text-gray-100 border border-gray-600 rounded-lg cursor-pointer bg-gray-800 focus:outline-none file:mr-4 file:py-3 file:px-6 file:rounded-md file:border-0 file:text-lg file:font-semibold file:bg-blue-500 file:text-white hover:file:bg-blue-600"/>
                            </div>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 shadow-md text-lg">
                                <i class="fas fa-upload mr-3"></i>Unggah/Perbarui Laporan
                            </button>
                        </form>
                        <?php if (!empty($modul['file_laporan'])): ?>
                            <p class="text-base text-gray-400 mt-3">Laporan saat ini: <a href="../uploads/laporan/<?php echo htmlspecialchars($modul['file_laporan']); ?>" target="_blank" class="text-blue-400 hover:underline"><?php echo htmlspecialchars($modul['file_laporan']); ?></a> (Terakhir diunggah: <?php echo date('d M Y H:i', strtotime($modul['tanggal_unggah'])); ?>)</p>
                        <?php endif; ?>
                    </div>

                    <!-- Melihat Nilai -->
                    <div>
                        <h4 class="text-xl font-medium text-gray-300 mb-3">Nilai Laporan:</h4>
                        <?php if ($modul['status_laporan'] === 'graded'): ?>
                            <p class="text-3xl font-bold text-green-400">Nilai: <?php echo htmlspecialchars($modul['nilai']); ?></p>
                            <p class="text-gray-300 text-lg mt-2">Feedback: <?php echo nl2br(htmlspecialchars($modul['feedback'])); ?></p>
                        <?php elseif ($modul['status_laporan'] === 'not_graded' && !empty($modul['file_laporan'])): ?>
                            <p class="text-yellow-400 text-lg">Laporan sudah diunggah, menunggu penilaian.</p>
                        <?php else: ?>
                            <p class="text-gray-400 text-lg">Nilai belum tersedia. Silakan unggah laporan Anda.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
require_once 'templates/footer_mahasiswa.php';
?>
