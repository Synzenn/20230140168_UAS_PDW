<?php
require_once 'config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    // Validasi sederhana
    if (empty($nama) || empty($email) || empty($password) || empty($role)) {
        $message = "Semua field harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
    } elseif (!in_array($role, ['mahasiswa', 'asisten'])) {
        $message = "Peran tidak valid!";
    } else {
        // Cek apakah email sudah terdaftar
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Email sudah terdaftar. Silakan gunakan email lain.";
        } else {
            // Hash password untuk keamanan
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Simpan ke database
            $sql_insert = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssss", $nama, $email, $hashed_password, $role);

            if ($stmt_insert->execute()) {
                header("Location: login.php?status=registered");
                exit();
            } else {
                $message = "Terjadi kesalahan. Silakan coba lagi.";
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Optional: Custom scrollbar for better aesthetics */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gradient-to-r from-blue-500 to-indigo-600 min-h-screen flex items-center justify-center font-sans">
    <div class="bg-white p-8 rounded-lg shadow-2xl w-full max-w-md transform transition-all duration-300 hover:scale-105">
        <h2 class="text-4xl font-extrabold text-center text-gray-800 mb-8">Registrasi</h2>
        
        <?php if (!empty($message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Gagal!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <form action="register.php" method="post" class="space-y-6">
            <div>
                <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" required 
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm 
                              focus:ring-blue-500 focus:border-blue-500 sm:text-sm 
                              transition-colors duration-200">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required 
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm 
                              focus:ring-blue-500 focus:border-blue-500 sm:text-sm 
                              transition-colors duration-200">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required 
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm 
                              focus:ring-blue-500 focus:border-blue-500 sm:text-sm 
                              transition-colors duration-200">
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Daftar Sebagai</label>
                <select id="role" name="role" required 
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm 
                               focus:ring-blue-500 focus:border-blue-500 sm:text-sm 
                               transition-colors duration-200">
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="asisten">Asisten</option>
                </select>
            </div>
            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md 
                           transition-colors duration-300 shadow-lg text-lg transform hover:-translate-y-1">
                Daftar
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-gray-600">Sudah punya akun? 
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-800 hover:underline">Login di sini</a>
            </p>
        </div>
    </div>
</body>
</html>
