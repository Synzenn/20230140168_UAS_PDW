<?php
$pageTitle = 'Cari Praktikum';
$activePage = 'courses'; // Untuk menandai navigasi aktif
require_once 'templates/header_mahasiswa.php';
require_once __DIR__ . '/../config.php'; // Pastikan path benar

$message = '';

// Handle pendaftaran praktikum
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_praktikum_id'])) {
    $praktikum_id = filter_var($_POST['register_praktikum_id'], FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];

    // Cek apakah mahasiswa sudah terdaftar di praktikum ini
    $check_sql = "SELECT id FROM registrasi_praktikum WHERE user_id = ? AND praktikum_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt) { // Pastikan statement berhasil dipersiapkan
        $check_stmt->bind_param("ii", $user_id, $praktikum_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = '<div class="bg-yellow-700 border border-yellow-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Peringatan!</strong>
                            <span class="block sm:inline">Anda sudah terdaftar di praktikum ini.</span>
                        </div>';
        } else {
            // Daftarkan mahasiswa ke praktikum
            $insert_sql = "INSERT INTO registrasi_praktikum (user_id, praktikum_id, status_registrasi) VALUES (?, ?, 'terdaftar')";
            $insert_stmt = $conn->prepare($insert_sql);
            if ($insert_stmt) { // Pastikan statement berhasil dipersiapkan
                $insert_stmt->bind_param("ii", $user_id, $praktikum_id);

                if ($insert_stmt->execute()) {
                    $message = '<div class="bg-green-700 border border-green-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Berhasil!</strong>
                                    <span class="block sm:inline">Anda berhasil mendaftar ke praktikum.</span>
                                </div>';
                } else {
                    $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Gagal!</strong>
                                    <span class="block sm:inline">Terjadi kesalahan saat mendaftar: ' . htmlspecialchars($conn->error) . '</span>
                                </div>';
                }
                $insert_stmt->close();
            } else {
                $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                <strong class="font-bold">Gagal!</strong>
                                <span class="block sm:inline">Gagal mempersiapkan statement pendaftaran: ' . htmlspecialchars($conn->error) . '</span>
                            </div>';
            }
        }
        $check_stmt->close();
    } else {
        $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                        <strong class="font-bold">Gagal!</strong>
                        <span class="block sm:inline">Gagal mempersiapkan statement pengecekan: ' . htmlspecialchars($conn->error) . '</span>
                    </div>';
    }
}

// Ambil semua mata praktikum
$sql = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC";
$result = $conn->query($sql);

$praktikums = [];
if ($result && $result->num_rows > 0) { // Pastikan query berhasil
    while ($row = $result->fetch_assoc()) {
        $praktikums[] = $row;
    }
} else if (!$result) {
    $message .= '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                    <strong class="font-bold">Error Database!</strong>
                    <span class="block sm:inline">Gagal mengambil data praktikum: ' . htmlspecialchars($conn->error) . '</span>
                </div>';
}
?>

<div class="bg-gray-800 p-8 lg:p-10 rounded-lg shadow-xl mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold text-white mb-6">Katalog Mata Praktikum</h1>
    <?php echo $message; // Tampilkan pesan ?>

    <?php if (empty($praktikums)): ?>
        <p class="text-gray-400 text-lg">Belum ada mata praktikum yang tersedia saat ini.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($praktikums as $praktikum): ?>
                <div class="bg-gray-700 border border-gray-600 rounded-lg p-6 lg:p-8 shadow-md hover:shadow-xl transition-shadow duration-300 flex flex-col">
                    <h2 class="text-2xl font-semibold text-blue-400 mb-3"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h2>
                    <p class="text-base text-gray-400 mb-4">Kode: <span class="font-medium text-gray-300"><?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></span></p>
                    <p class="text-gray-300 text-lg mb-6 flex-grow"><?php echo nl2br(htmlspecialchars($praktikum['deskripsi'])); ?></p>
                    
                    <form action="courses.php" method="POST" class="mt-auto">
                        <input type="hidden" name="register_praktikum_id" value="<?php echo $praktikum['id']; ?>">
                        <?php
                        // Cek lagi status pendaftaran untuk setiap praktikum
                        $is_registered = false;
                        $check_reg_sql_loop = "SELECT id FROM registrasi_praktikum WHERE user_id = ? AND praktikum_id = ?";
                        $check_reg_stmt_loop = $conn->prepare($check_reg_sql_loop);
                        if ($check_reg_stmt_loop) { // Pastikan statement berhasil dipersiapkan
                            $check_reg_stmt_loop->bind_param("ii", $_SESSION['user_id'], $praktikum['id']);
                            $check_reg_stmt_loop->execute();
                            $check_reg_stmt_loop->store_result();
                            if ($check_reg_stmt_loop->num_rows > 0) {
                                $is_registered = true;
                            }
                            $check_reg_stmt_loop->close(); // Tutup statement setelah digunakan
                        } else {
                            // Handle error if statement preparation fails
                            error_log("Failed to prepare statement in loop: " . $conn->error);
                        }

                        if ($is_registered) {
                            echo '<button type="button" class="w-full bg-gray-600 text-white font-bold py-3 px-6 rounded-md cursor-not-allowed text-lg" disabled>Sudah Terdaftar</button>';
                        } else {
                            echo '<button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 shadow-md text-lg">Daftar Praktikum</button>';
                        }
                        ?>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close(); // Tutup koneksi database hanya sekali di akhir file
require_once 'templates/footer_mahasiswa.php';
?>
