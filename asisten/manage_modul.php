<?php
$pageTitle = 'Kelola Modul';
require_once 'templates/header.php';
require_once __DIR__ . '/../config.php';

$praktikum_id = filter_var($_GET['praktikum_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
$message = '';

// Jika praktikum_id tidak ada, tampilkan daftar mata praktikum untuk dipilih
if (!$praktikum_id) {
    // Ambil semua mata praktikum
    $praktikums = [];
    $sql_fetch_praktikum = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC";
    $result_fetch_praktikum = $conn->query($sql_fetch_praktikum);
    if ($result_fetch_praktikum->num_rows > 0) {
        while ($row = $result_fetch_praktikum->fetch_assoc()) {
            $praktikums[] = $row;
        }
    }
    ?>
    <div class="bg-gray-800 p-8 lg:p-10 rounded-lg shadow-xl mb-8">
        <h1 class="text-3xl lg:text-4xl font-bold text-white mb-6">Pilih Praktikum untuk Dikelola Modulnya</h1>
        <?php echo $message; ?>

        <?php if (empty($praktikums)): ?>
            <p class="text-gray-400 text-lg">Belum ada mata praktikum yang ditambahkan. Silakan tambahkan di <a href="manage_praktikum.php" class="text-blue-400 hover:underline">Kelola Praktikum</a>.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($praktikums as $praktikum): ?>
                    <div class="bg-gray-700 border border-gray-600 rounded-lg p-6 lg:p-8 shadow-md hover:shadow-xl transition-shadow duration-300 flex flex-col">
                        <h2 class="text-2xl font-semibold text-blue-400 mb-3"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h2>
                        <p class="text-base text-gray-400 mb-4">Kode: <span class="font-medium text-gray-300"><?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></span></p>
                        <p class="text-gray-300 text-lg mb-6 flex-grow"><?php echo nl2br(htmlspecialchars($praktikum['deskripsi'])); ?></p>
                        <a href="manage_modul.php?praktikum_id=<?php echo $praktikum['id']; ?>" class="mt-auto w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 shadow-md text-lg text-center">
                            <i class="fas fa-cubes mr-3"></i>Kelola Modul
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $conn->close();
    require_once 'templates/footer.php';
    exit(); // Hentikan eksekusi script setelah menampilkan daftar praktikum
}

// Jika praktikum_id ada, lanjutkan dengan logika pengelolaan modul untuk praktikum tersebut
// Ambil nama praktikum untuk judul
$praktikum_name = '';
$sql_praktikum = "SELECT nama_praktikum FROM mata_praktikum WHERE id = ?";
$stmt_praktikum = $conn->prepare($sql_praktikum);
$stmt_praktikum->bind_param("i", $praktikum_id);
$stmt_praktikum->execute();
$result_praktikum = $stmt_praktikum->get_result();
if ($result_praktikum->num_rows > 0) {
    $praktikum_name = $result_praktikum->fetch_assoc()['nama_praktikum'];
} else {
    echo '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">Praktikum tidak ditemukan.</span>
          </div>';
    $conn->close();
    require_once 'templates/footer.php';
    exit();
}
$stmt_praktikum->close();

// Handle CRUD operations for modules
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_modul'])) {
        $judul_modul = trim($_POST['judul_modul']);
        $deskripsi_modul = trim($_POST['deskripsi_modul']);
        $file_materi = null;

        if (empty($judul_modul)) {
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Judul Modul tidak boleh kosong.</span>
                        </div>';
        } else {
            // Handle file upload
            if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === UPLOAD_ERR_OK) {
                $file_tmp_name = $_FILES['file_materi']['tmp_name'];
                $file_name = basename($_FILES['file_materi']['name']);
                $file_size = $_FILES['file_materi']['size'];
                $file_type = $_FILES['file_materi']['type'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                $allowed_ext = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
                if (!in_array($file_ext, $allowed_ext)) {
                    $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Gagal!</strong>
                                    <span class="block sm:inline">Hanya file PDF, DOC, DOCX, PPT, dan PPTX yang diizinkan.</span>
                                </div>';
                } elseif ($file_size > 20 * 1024 * 1024) { // Max 20MB
                    $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Gagal!</strong>
                                    <span class="block sm:inline">Ukuran file terlalu besar (maks 20MB).</span>
                                </div>';
                } else {
                    $upload_dir = __DIR__ . '/../../uploads/materi/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $new_file_name = uniqid('materi_') . '.' . $file_ext;
                    $file_path = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp_name, $file_path)) {
                        $file_materi = $new_file_name;
                    } else {
                        $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                        <strong class="font-bold">Gagal!</strong>
                                        <span class="block sm:inline">Gagal mengunggah file materi.</span>
                                    </div>';
                    }
                }
            }

            if (empty($message)) { // Only proceed if no file upload error
                $sql = "INSERT INTO modul_praktikum (praktikum_id, judul_modul, deskripsi_modul, file_materi) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $praktikum_id, $judul_modul, $deskripsi_modul, $file_materi);
                if ($stmt->execute()) {
                    $message = '<div class="bg-green-700 border border-green-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Berhasil!</strong>
                                    <span class="block sm:inline">Modul berhasil ditambahkan.</span>
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
    } elseif (isset($_POST['edit_modul'])) {
        $modul_id = filter_var($_POST['modul_id'], FILTER_SANITIZE_NUMBER_INT);
        $judul_modul = trim($_POST['judul_modul']);
        $deskripsi_modul = trim($_POST['deskripsi_modul']);
        $existing_file_materi = $_POST['existing_file_materi'] ?? null;
        $file_materi_to_save = $existing_file_materi;

        if (empty($judul_modul)) {
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Judul Modul tidak boleh kosong.</span>
                        </div>';
        } else {
            // Handle new file upload for materi
            if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === UPLOAD_ERR_OK) {
                $file_tmp_name = $_FILES['file_materi']['tmp_name'];
                $file_name = basename($_FILES['file_materi']['name']);
                $file_size = $_FILES['file_materi']['size'];
                $file_type = $_FILES['file_materi']['type'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                $allowed_ext = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
                if (!in_array($file_ext, $allowed_ext)) {
                    $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Gagal!</strong>
                                    <span class="block sm:inline">Hanya file PDF, DOC, DOCX, PPT, dan PPTX yang diizinkan.</span>
                                </div>';
                } elseif ($file_size > 20 * 1024 * 1024) { // Max 20MB
                    $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Gagal!</strong>
                                    <span class="block sm:inline">Ukuran file terlalu besar (maks 20MB).</span>
                                </div>';
                } else {
                    $upload_dir = __DIR__ . '/../../uploads/materi/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $new_file_name = uniqid('materi_') . '.' . $file_ext;
                    $file_path = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp_name, $file_path)) {
                        // Delete old file if exists
                        if ($existing_file_materi && file_exists($upload_dir . $existing_file_materi)) {
                            unlink($upload_dir . $existing_file_materi);
                        }
                        $file_materi_to_save = $new_file_name;
                    } else {
                        $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                        <strong class="font-bold">Gagal!</strong>
                                        <span class="block sm:inline">Gagal mengunggah file materi baru.</span>
                                    </div>';
                    }
                }
            }

            if (empty($message)) { // Only proceed if no file upload error
                $sql = "UPDATE modul_praktikum SET judul_modul = ?, deskripsi_modul = ?, file_materi = ? WHERE id = ? AND praktikum_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssii", $judul_modul, $deskripsi_modul, $file_materi_to_save, $modul_id, $praktikum_id);
                if ($stmt->execute()) {
                    $message = '<div class="bg-green-700 border border-green-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Berhasil!</strong>
                                    <span class="block sm:inline">Modul berhasil diperbarui.</span>
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
    } elseif (isset($_POST['delete_modul'])) {
        $modul_id = filter_var($_POST['modul_id'], FILTER_SANITIZE_NUMBER_INT);

        // Get file_materi path before deleting from DB
        $file_to_delete = null;
        $get_file_sql = "SELECT file_materi FROM modul_praktikum WHERE id = ?";
        $get_file_stmt = $conn->prepare($get_file_sql);
        $get_file_stmt->bind_param("i", $modul_id);
        $get_file_stmt->execute();
        $get_file_result = $get_file_stmt->get_result();
        if ($get_file_result->num_rows > 0) {
            $file_to_delete = $get_file_result->fetch_assoc()['file_materi'];
        }
        $get_file_stmt->close();

        $sql = "DELETE FROM modul_praktikum WHERE id = ? AND praktikum_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $modul_id, $praktikum_id);
        if ($stmt->execute()) {
            // Delete the physical file if it exists
            if ($file_to_delete && file_exists(__DIR__ . '/../../uploads/materi/' . $file_to_delete)) {
                unlink(__DIR__ . '/../../uploads/materi/' . $file_to_delete);
            }
            $message = '<div class="bg-green-700 border border-green-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Berhasil!</strong>
                            <span class="block sm:inline">Modul berhasil dihapus.</span>
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

// Fetch all modules for this praktikum
$modules = [];
$sql_fetch = "SELECT id, judul_modul, deskripsi_modul, file_materi FROM modul_praktikum WHERE praktikum_id = ? ORDER BY created_at ASC";
$stmt_fetch = $conn->prepare($sql_fetch);
$stmt_fetch->bind_param("i", $praktikum_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
if ($result_fetch->num_rows > 0) {
    while ($row = $result_fetch->fetch_assoc()) {
        $modules[] = $row;
    }
}
$stmt_fetch->close();
?>

<div class="bg-gray-800 p-8 lg:p-10 rounded-lg shadow-xl mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold text-white mb-6">Kelola Modul untuk Praktikum: <span class="text-blue-400"><?php echo htmlspecialchars($praktikum_name); ?></span></h1>
    <a href="manage_modul.php" class="inline-flex items-center text-blue-400 hover:underline mb-8 text-lg">
        <i class="fas fa-arrow-left mr-3"></i>Kembali ke Daftar Praktikum
    </a>
    <?php echo $message; ?>

    <!-- Form Tambah Modul -->
    <div class="mb-8 p-6 lg:p-8 border border-green-600 rounded-lg bg-gray-700">
        <h2 class="text-2xl lg:text-3xl font-semibold text-green-400 mb-5">Tambah Modul Baru</h2>
        <form action="manage_modul.php?praktikum_id=<?php echo $praktikum_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label for="judul_modul" class="block text-lg font-medium text-gray-300 mb-2">Judul Modul</label>
                <input type="text" id="judul_modul" name="judul_modul" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label for="deskripsi_modul" class="block text-lg font-medium text-gray-300 mb-2">Deskripsi Modul</label>
                <textarea id="deskripsi_modul" name="deskripsi_modul" rows="4" class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-green-500 focus:border-green-500"></textarea>
            </div>
            <div>
                <label for="file_materi" class="block text-lg font-medium text-gray-300 mb-2">File Materi (PDF/DOC/DOCX/PPT/PPTX, maks 20MB)</label>
                <input type="file" id="file_materi" name="file_materi" accept=".pdf,.doc,.docx,.ppt,.pptx" class="mt-1 block w-full text-lg text-gray-100 border border-gray-600 rounded-lg cursor-pointer bg-gray-800 focus:outline-none file:mr-4 file:py-3 file:px-6 file:rounded-md file:border-0 file:text-lg file:font-semibold file:bg-green-500 file:text-white hover:file:bg-green-600"/>
            </div>
            <button type="submit" name="add_modul" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 shadow-md text-lg">
                <i class="fas fa-plus-circle mr-3"></i>Tambah Modul
            </button>
        </form>
    </div>

    <!-- Daftar Modul -->
    <h2 class="text-2xl lg:text-3xl font-semibold text-white mb-6 border-b border-gray-700 pb-3">Daftar Modul</h2>
    <?php if (empty($modules)): ?>
        <p class="text-gray-400 text-lg">Belum ada modul untuk praktikum ini.</p>
    <?php else: ?>
        <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
            <table class="w-full text-base text-left text-gray-400">
                <thead class="text-sm text-gray-300 uppercase bg-gray-700">
                    <tr>
                        <th scope="col" class="py-4 px-6">Judul Modul</th>
                        <th scope="col" class="py-4 px-6">Deskripsi</th>
                        <th scope="col" class="py-4 px-6">File Materi</th>
                        <th scope="col" class="py-4 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modules as $modul): ?>
                        <tr class="bg-gray-800 border-b border-gray-700 hover:bg-gray-700">
                            <td class="py-4 px-6 font-medium text-white whitespace-nowrap text-lg"><?php echo htmlspecialchars($modul['judul_modul']); ?></td>
                            <td class="py-4 px-6 text-lg"><?php echo nl2br(htmlspecialchars($modul['deskripsi_modul'])); ?></td>
                            <td class="py-4 px-6 text-lg">
                                <?php if (!empty($modul['file_materi'])): ?>
                                    <a href="../../uploads/materi/<?php echo htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="text-blue-400 hover:underline">
                                        <i class="fas fa-file-download mr-2"></i>Unduh File
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-500">Tidak ada</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex flex-col sm:flex-row justify-center items-center space-y-3 sm:space-y-0 sm:space-x-3">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($modul)); ?>)" class="inline-flex items-center bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2.5 px-5 rounded-md text-sm transition-colors duration-300 shadow-sm">
                                        <i class="fas fa-edit mr-2"></i>Edit
                                    </button>
                                    <form action="manage_modul.php?praktikum_id=<?php echo $praktikum_id; ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus modul ini? Ini juga akan menghapus semua laporan terkait modul ini!');">
                                        <input type="hidden" name="modul_id" value="<?php echo $modul['id']; ?>">
                                        <button type="submit" name="delete_modul" class="inline-flex items-center bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-5 rounded-md text-sm transition-colors duration-300 shadow-sm">
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

<!-- Edit Modul Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full hidden flex items-center justify-center">
    <div class="relative p-8 lg:p-10 border w-full max-w-lg shadow-lg rounded-lg bg-gray-800 text-white">
        <h3 class="text-2xl lg:text-3xl font-bold text-white mb-5">Edit Modul</h3>
        <form action="manage_modul.php?praktikum_id=<?php echo $praktikum_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" id="edit_modul_id" name="modul_id">
            <input type="hidden" id="edit_existing_file_materi" name="existing_file_materi">
            <div>
                <label for="edit_judul_modul" class="block text-lg font-medium text-gray-300 mb-2">Judul Modul</label>
                <input type="text" id="edit_judul_modul" name="judul_modul" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="edit_deskripsi_modul" class="block text-lg font-medium text-gray-300 mb-2">Deskripsi Modul</label>
                <textarea id="edit_deskripsi_modul" name="deskripsi_modul" rows="4" class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <div>
                <label for="edit_file_materi" class="block text-lg font-medium text-gray-300 mb-2">File Materi Baru (Opsional, PDF/DOC/DOCX/PPT/PPTX, maks 20MB)</label>
                <input type="file" id="edit_file_materi" name="file_materi" accept=".pdf,.doc,.docx,.ppt,.pptx" class="mt-1 block w-full text-lg text-gray-100 border border-gray-600 rounded-lg cursor-pointer bg-gray-800 focus:outline-none file:mr-4 file:py-3 file:px-6 file:rounded-md file:border-0 file:text-lg file:font-semibold file:bg-blue-500 file:text-white hover:file:bg-blue-600"/>
                <p id="current_file_materi" class="text-base text-gray-400 mt-2"></p>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="closeEditModal()" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 text-lg">Batal</button>
                <button type="submit" name="edit_modul" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 text-lg">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(modul) {
    document.getElementById('edit_modul_id').value = modul.id;
    document.getElementById('edit_judul_modul').value = modul.judul_modul;
    document.getElementById('edit_deskripsi_modul').value = modul.deskripsi_modul;
    document.getElementById('edit_existing_file_materi').value = modul.file_materi;

    const currentFileMateriElement = document.getElementById('current_file_materi');
    if (modul.file_materi) {
        currentFileMateriElement.innerHTML = `File saat ini: <a href="../../uploads/materi/${modul.file_materi}" target="_blank" class="text-blue-400 hover:underline">${modul.file_materi}</a>`;
    } else {
        currentFileMateriElement.textContent = 'Tidak ada file materi saat ini.';
    }
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    // Clear the file input when closing the modal
    document.getElementById('edit_file_materi').value = '';
}
</script>

<?php
$conn->close();
require_once 'templates/footer.php';
?>
