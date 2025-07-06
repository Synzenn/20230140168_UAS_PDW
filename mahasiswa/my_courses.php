<?php
$pageTitle = 'Praktikum Saya';
$activePage = 'my_courses'; // Untuk menandai navigasi aktif
require_once 'templates/header_mahasiswa.php';
require_once __DIR__ . '/../config.php'; // Pastikan path benar

$user_id = $_SESSION['user_id'];

// Ambil praktikum yang diikuti oleh mahasiswa ini
$sql = "SELECT mp.id, mp.nama_praktikum, mp.deskripsi, mp.kode_praktikum, rp.status_registrasi
        FROM registrasi_praktikum rp
        JOIN mata_praktikum mp ON rp.praktikum_id = mp.id
        WHERE rp.user_id = ?
        ORDER BY mp.nama_praktikum ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$my_praktikums = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $my_praktikums[] = $row;
    }
}
$stmt->close();
?>

<div class="bg-gray-800 p-8 lg:p-10 rounded-lg shadow-xl mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold text-white mb-6">Praktikum yang Saya Ikuti</h1>

    <?php if (empty($my_praktikums)): ?>
        <p class="text-gray-400 text-lg">Anda belum terdaftar di praktikum manapun. <a href="courses.php" class="text-blue-400 hover:underline">Cari praktikum sekarang!</a></p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($my_praktikums as $praktikum): ?>
                <div class="bg-gray-700 border border-gray-600 rounded-lg p-6 lg:p-8 shadow-md hover:shadow-xl transition-shadow duration-300 flex flex-col">
                    <h2 class="text-2xl font-semibold text-blue-400 mb-3"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h2>
                    <p class="text-base text-gray-400 mb-4">Kode: <span class="font-medium text-gray-300"><?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></span></p>
                    <p class="text-gray-300 text-lg mb-6 flex-grow"><?php echo nl2br(htmlspecialchars($praktikum['deskripsi'])); ?></p>
                    <div class="mt-auto flex justify-between items-center">
                        <span class="text-base font-medium <?php echo ($praktikum['status_registrasi'] == 'terdaftar') ? 'text-blue-400' : 'text-green-400'; ?>">
                            Status: <?php echo ucfirst(htmlspecialchars($praktikum['status_registrasi'])); ?>
                        </span>
                        <a href="praktikum_detail.php?id=<?php echo $praktikum['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 shadow-md text-lg">
                            Lihat Detail & Tugas
                        </a>
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
