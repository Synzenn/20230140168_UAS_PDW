<?php
$pageTitle = 'Kelola Pengguna';
require_once 'templates/header.php';
require_once __DIR__ . '/../config.php';

$message = '';

// Handle CRUD operations for users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $nama = trim($_POST['nama']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $role = trim($_POST['role']);

        if (empty($nama) || empty($email) || empty($password) || empty($role)) {
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Semua field harus diisi.</span>
                        </div>';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Format email tidak valid.</span>
                        </div>';
        } elseif (!in_array($role, ['mahasiswa', 'asisten'])) {
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Peran tidak valid.</span>
                        </div>';
        } else {
            // Check if email already exists
            $check_sql = "SELECT id FROM users WHERE email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                <strong class="font-bold">Gagal!</strong>
                                <span class="block sm:inline">Email sudah terdaftar.</span>
                            </div>';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
                if ($stmt->execute()) {
                    $message = '<div class="bg-green-700 border border-green-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Berhasil!</strong>
                                    <span class="block sm:inline">Pengguna berhasil ditambahkan.</span>
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
    } elseif (isset($_POST['edit_user'])) {
        $id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        $nama = trim($_POST['nama']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        $password = trim($_POST['password_new']); // New password, optional

        if (empty($nama) || empty($email) || empty($role)) {
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Nama, Email, dan Peran tidak boleh kosong.</span>
                        </div>';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Format email tidak valid.</span>
                        </div>';
        } elseif (!in_array($role, ['mahasiswa', 'asisten'])) {
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Peran tidak valid.</span>
                        </div>';
        } else {
            // Check if email already exists for another user
            $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $email, $id);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                <strong class="font-bold">Gagal!</strong>
                                <span class="block sm:inline">Email sudah terdaftar untuk pengguna lain.</span>
                            </div>';
            } else {
                $sql = "UPDATE users SET nama = ?, email = ?, role = ?";
                $params = [$nama, $email, $role];
                $types = "sss";

                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $sql .= ", password = ?";
                    $params[] = $hashed_password;
                    $types .= "s";
                }
                $sql .= " WHERE id = ?";
                $params[] = $id;
                $types .= "i";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    $message = '<div class="bg-green-700 border border-green-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                    <strong class="font-bold">Berhasil!</strong>
                                    <span class="block sm:inline">Pengguna berhasil diperbarui.</span>
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
    } elseif (isset($_POST['delete_user'])) {
        $id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        // Prevent deleting own account
        if ($id == $_SESSION['user_id']) {
            $message = '<div class="bg-red-700 border border-red-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Anda tidak bisa menghapus akun Anda sendiri.</span>
                        </div>';
        } else {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = '<div class="bg-green-700 border border-green-500 text-white px-6 py-4 rounded-lg relative mb-6 text-lg" role="alert">
                                <strong class="font-bold">Berhasil!</strong>
                                <span class="block sm:inline">Pengguna berhasil dihapus.</span>
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
}

// Fetch all users
$users = [];
$sql_fetch = "SELECT id, nama, email, role, created_at FROM users ORDER BY created_at DESC";
$result_fetch = $conn->query($sql_fetch);
if ($result_fetch->num_rows > 0) {
    while ($row = $result_fetch->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<div class="bg-gray-800 p-8 lg:p-10 rounded-lg shadow-xl mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold text-white mb-6">Kelola Akun Pengguna</h1>
    <?php echo $message; ?>

    <!-- Form Tambah Pengguna -->
    <div class="mb-8 p-6 lg:p-8 border border-blue-600 rounded-lg bg-gray-700">
        <h2 class="text-2xl lg:text-3xl font-semibold text-blue-400 mb-5">Tambah Pengguna Baru</h2>
        <form action="manage_users.php" method="POST" class="space-y-6">
            <div>
                <label for="nama" class="block text-lg font-medium text-gray-300 mb-2">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="email" class="block text-lg font-medium text-gray-300 mb-2">Email</label>
                <input type="email" id="email" name="email" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="password" class="block text-lg font-medium text-gray-300 mb-2">Password</label>
                <input type="password" id="password" name="password" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="role" class="block text-lg font-medium text-gray-300 mb-2">Peran</label>
                <select id="role" name="role" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="asisten">Asisten</option>
                </select>
            </div>
            <button type="submit" name="add_user" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 shadow-md text-lg">
                <i class="fas fa-user-plus mr-3"></i>Tambah Pengguna
            </button>
        </form>
    </div>

    <!-- Daftar Pengguna -->
    <h2 class="text-2xl lg:text-3xl font-semibold text-white mb-6 border-b border-gray-700 pb-3">Daftar Pengguna Terdaftar</h2>
    <?php if (empty($users)): ?>
        <p class="text-gray-400 text-lg">Belum ada pengguna yang terdaftar.</p>
    <?php else: ?>
        <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
            <table class="w-full text-base text-left text-gray-400">
                <thead class="text-sm text-gray-300 uppercase bg-gray-700">
                    <tr>
                        <th scope="col" class="py-4 px-6">Nama</th>
                        <th scope="col" class="py-4 px-6">Email</th>
                        <th scope="col" class="py-4 px-6">Peran</th>
                        <th scope="col" class="py-4 px-6">Tanggal Daftar</th>
                        <th scope="col" class="py-4 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr class="bg-gray-800 border-b border-gray-700 hover:bg-gray-700">
                            <td class="py-4 px-6 font-medium text-white whitespace-nowrap text-lg"><?php echo htmlspecialchars($user['nama']); ?></td>
                            <td class="py-4 px-6 text-lg"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="py-4 px-6">
                                <span class="px-3 py-1 rounded-full text-base font-semibold 
                                    <?php echo ($user['role'] == 'asisten') ? 'bg-purple-600 text-white' : 'bg-blue-600 text-white'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-lg"><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex flex-col sm:flex-row justify-center items-center space-y-3 sm:space-y-0 sm:space-x-3">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="inline-flex items-center bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2.5 px-5 rounded-md text-sm transition-colors duration-300 shadow-sm">
                                        <i class="fas fa-user-edit mr-2"></i>Edit
                                    </button>
                                    <form action="manage_users.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini? Semua data terkait akan dihapus!');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" <?php echo ($user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?> class="inline-flex items-center bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-5 rounded-md text-sm transition-colors duration-300 shadow-sm <?php echo ($user['id'] == $_SESSION['user_id']) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                            <i class="fas fa-user-times mr-2"></i>Hapus
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

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full hidden flex items-center justify-center">
    <div class="relative p-8 lg:p-10 border w-full max-w-lg shadow-lg rounded-lg bg-gray-800 text-white">
        <h3 class="text-2xl lg:text-3xl font-bold text-white mb-5">Edit Pengguna</h3>
        <form action="manage_users.php" method="POST" class="space-y-6">
            <input type="hidden" id="edit_user_id" name="user_id">
            <div>
                <label for="edit_nama" class="block text-lg font-medium text-gray-300 mb-2">Nama Lengkap</label>
                <input type="text" id="edit_nama" name="nama" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="edit_email" class="block text-lg font-medium text-gray-300 mb-2">Email</label>
                <input type="email" id="edit_email" name="email" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="edit_password_new" class="block text-lg font-medium text-gray-300 mb-2">Password Baru (Kosongkan jika tidak ingin mengubah)</label>
                <input type="password" id="edit_password_new" name="password_new" class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="edit_role" class="block text-lg font-medium text-gray-300 mb-2">Peran</label>
                <select id="edit_role" name="role" required class="mt-1 block w-full border border-gray-600 rounded-md shadow-sm p-3 bg-gray-800 text-white text-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="asisten">Asisten</option>
                </select>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="closeEditModal()" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 text-lg">Batal</button>
                <button type="submit" name="edit_user" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md transition-colors duration-300 text-lg">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_nama').value = user.nama;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_password_new').value = ''; // Clear password field
    document.getElementById('editUserModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editUserModal').classList.add('hidden');
}
</script>

<?php
$conn->close();
require_once 'templates/footer.php';
?>
