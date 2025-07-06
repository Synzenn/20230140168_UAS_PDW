<?php
$pageTitle = 'Kelola Praktikum';
require_once 'templates/header.php';
require_once __DIR__ . '/../config.php';

$message = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_praktikum'])) {
        $nama_praktikum = trim($_POST['nama_praktikum']);
        $deskripsi = trim($_POST['deskripsi']);
        $kode_praktikum = trim($_POST['kode_praktikum']);

        if (empty($nama_praktikum) || empty($kode_praktikum)) {
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Nama Praktikum dan Kode Praktikum tidak boleh kosong.</span>
                        </div>';
        } else {
            // Cek kode praktikum unik
            $check_sql = "SELECT id FROM mata_praktikum WHERE kode_praktikum = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $kode_praktikum);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                <strong class="font-bold">Gagal!</strong>
                                <span class="block sm:inline">Kode Praktikum sudah ada.</span>
                            </div>';
            } else {
                $sql = "INSERT INTO mata_praktikum (nama_praktikum, deskripsi, kode_praktikum) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $nama_praktikum, $deskripsi, $kode_praktikum);
                if ($stmt->execute()) {
                    $message = '<div class="bg-green-700 border border-green-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Berhasil!</strong>
                                    <span class="block sm:inline">Mata praktikum berhasil ditambahkan.</span>
                                </div>';
                } else {
                    $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Gagal!</strong>
                                    <span class="block sm:inline">Terjadi kesalahan: ' . htmlspecialchars($conn->error) . '</span>
                                </div>';
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
    } elseif (isset($_POST['edit_praktikum'])) {
        $id = filter_var($_POST['praktikum_id'], FILTER_SANITIZE_NUMBER_INT);
        $nama_praktikum = trim($_POST['nama_praktikum']);
        $deskripsi = trim($_POST['deskripsi']);
        $kode_praktikum = trim($_POST['kode_praktikum']);

        if (empty($nama_praktikum) || empty($kode_praktikum)) {
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Nama Praktikum dan Kode Praktikum tidak boleh kosong.</span>
                        </div>';
        } else {
            // Cek kode praktikum unik, kecuali untuk praktikum yang sedang diedit
            $check_sql = "SELECT id FROM mata_praktikum WHERE kode_praktikum = ? AND id != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $kode_praktikum, $id);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                <strong class="font-bold">Gagal!</strong>
                                <span class="block sm:inline">Kode Praktikum sudah ada untuk praktikum lain.</span>
                            </div>';
            } else {
                $sql = "UPDATE mata_praktikum SET nama_praktikum = ?, deskripsi = ?, kode_praktikum = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $nama_praktikum, $deskripsi, $kode_praktikum, $id);
                if ($stmt->execute()) {
                    $message = '<div class="bg-green-700 border border-green-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Berhasil!</strong>
                                    <span class="block sm:inline">Mata praktikum berhasil diperbarui.</span>
                                </div>';
                } else {
                    $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Gagal!</strong>
                                    <span class="block sm:inline">Terjadi kesalahan: ' . htmlspecialchars($conn->error) . '</span>
                                </div>';
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
    } elseif (isset($_POST['delete_praktikum'])) {
        $id = filter_var($_POST['praktikum_id'], FILTER_SANITIZE_NUMBER_INT);
        $sql = "DELETE FROM mata_praktikum WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = '<div class="bg-green-700 border border-green-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Berhasil!</strong>
                            <span class="block sm:inline">Mata praktikum berhasil dihapus.</span>
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

// Fetch all praktikum data
$praktikums = [];
$sql_fetch = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC";
$result_fetch = $conn->query($sql_fetch);
if ($result_fetch->num_rows > 0) {
    while ($row = $result_fetch->fetch_assoc()) {
        $praktikums[] = $row;
    }
}
?>

<div class="bg-gray-800 p-8 lg:p-10 rounded-lg shadow-xl mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold text-white mb-6">Kelola Mata Praktikum</h1>
    <?php echo $message; ?>

    <!-- Form Tambah Praktikum -->
    <div class="mb-8 p-6 lg:p-8 border border-blue-600 rounded-lg bg-gray-700">
        <h2 class="text-2xl lg:text-3xl font-semibold text-blue-400 mb-5">Tambah Mata Praktikum Baru</h2>
        <form action="manage_praktikum.php" method="POST" class="space-y-6">
            <div>
                <label for="nama_praktikum" class="block text-lg font-medium text-gray-300 mb-2">Nama Praktikum</label>
                <input type="text" id="nama_praktikum" name="nama_praktikum" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="kode_praktikum" class="block text-lg font-medium text-gray-300 mb-2">Kode Praktikum (Unik)</label>
                <input type="text" id="kode_praktikum" name="kode_praktikum" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="deskripsi" class="block text-lg font-medium text-gray-300 mb-2">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" rows="4" class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <button type="submit" name="add_praktikum" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 shadow-md text-lg">
                <i class="fas fa-plus-circle mr-3"></i>Tambah Praktikum
            </button>
        </form>
    </div>

    <!-- Daftar Praktikum -->
    <h2 class="text-2xl lg:text-3xl font-semibold text-white mb-6 border-b border-gray-700 pb-3">Daftar Mata Praktikum Tersedia</h2>
    <?php if (empty($praktikums)): ?>
        <p class="text-gray-400 text-lg">Belum ada mata praktikum yang ditambahkan.</p>
    <?php else: ?>
        <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
            <table class="w-full text-base text-left text-gray-400">
                <thead class="text-sm text-gray-300 uppercase bg-gray-700">
                    <tr>
                        <th scope="col" class="py-4 px-6">Nama Praktikum</th>
                        <th scope="col" class="py-4 px-6">Kode</th>
                        <th scope="col" class="py-4 px-6">Deskripsi</th>
                        <th scope="col" class="py-4 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($praktikums as $praktikum): ?>
                        <tr class="bg-gray-800 border-b border-gray-700 hover:bg-gray-700">
                            <td class="py-4 px-6 font-medium text-white whitespace-nowrap text-lg"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></td>
                            <td class="py-4 px-6 text-lg"><?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></td>
                            <td class="py-4 px-6 text-lg"><?php echo nl2br(htmlspecialchars($praktikum['deskripsi'])); ?></td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex flex-col sm:flex-row justify-center items-center space-y-3 sm:space-y-0 sm:space-x-3">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($praktikum)); ?>)" class="inline-flex items-center bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2.5 px-5 rounded-md text-sm transition-colors duration-300 shadow-sm">
                                        <i class="fas fa-edit mr-2"></i>Edit
                                    </button>
                                    <form action="manage_praktikum.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus praktikum ini? Ini juga akan menghapus semua modul dan laporan terkait!');">
                                        <input type="hidden" name="praktikum_id" value="<?php echo $praktikum['id']; ?>">
                                        <button type="submit" name="delete_praktikum" class="inline-flex items-center bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-5 rounded-md text-sm transition-colors duration-300 shadow-sm">
                                            <i class="fas fa-trash-alt mr-2"></i>Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Edit Praktikum Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full hidden flex items-center justify-center">
    <div class="relative p-8 lg:p-10 border w-full max-w-lg shadow-lg rounded-lg bg-gray-800 text-white">
        <h3 class="text-2xl lg:text-3xl font-bold text-white mb-5">Edit Mata Praktikum</h3>
        <form action="manage_praktikum.php" method="POST" class="space-y-6">
            <input type="hidden" id="edit_praktikum_id" name="praktikum_id">
            <div>
                <label for="edit_nama_praktikum" class="block text-lg font-medium text-gray-300 mb-2">Nama Praktikum</label>
                <input type="text" id="edit_nama_praktikum" name="nama_praktikum" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="edit_kode_praktikum" class="block text-lg font-medium text-gray-300 mb-2">Kode Praktikum (Unik)</label>
                <input type="text" id="edit_kode_praktikum" name="kode_praktikum" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="edit_deskripsi" class="block text-lg font-medium text-gray-300 mb-2">Deskripsi</label>
                <textarea id="edit_deskripsi" name="deskripsi" rows="4" class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="closeEditModal()" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 text-lg">Batal</button>
                <button type="submit" name="edit_praktikum" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 text-lg">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(praktikum) {
    document.getElementById('edit_praktikum_id').value = praktikum.id;
    document.getElementById('edit_nama_praktikum').value = praktikum.nama_praktikum;
    document.getElementById('edit_kode_praktikum').value = praktikum.kode_praktikum;
    document.getElementById('edit_deskripsi').value = praktikum.deskripsi;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php
$conn->close();
require_once 'templates/footer.php';
?>
