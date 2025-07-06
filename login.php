<?php
session_start();
require_once 'config.php';

// Jika sudah login, redirect ke halaman yang sesuai
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'asisten') {
        header("Location: asisten/dashboard.php");
    } elseif ($_SESSION['role'] == 'mahasiswa') {
        header("Location: mahasiswa/dashboard.php");
    }
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $message = "Email dan password harus diisi!";
    } else {
        $sql = "SELECT id, nama, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Password benar, simpan semua data penting ke session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];

                // Logika untuk mengarahkan pengguna berdasarkan peran (role)
                if ($user['role'] == 'asisten') {
                    header("Location: asisten/dashboard.php");
                    exit();
                } elseif ($user['role'] == 'mahasiswa') {
                    header("Location: mahasiswa/dashboard.php");
                    exit();
                } else {
                    // Fallback jika peran tidak dikenali
                    $message = "Peran pengguna tidak valid.";
                }

            } else {
                $message = "Password yang Anda masukkan salah.";
            }
        } else {
            $message = "Akun dengan email tersebut tidak ditemukan.";
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
    <title>Login </title>
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
        <h2 class="text-4xl font-extrabold text-center text-gray-800 mb-8">Login</h2>
        
        <?php 
            if (isset($_GET['status']) && $_GET['status'] == 'registered') {
                echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">Berhasil!</strong>
                        <span class="block sm:inline">Registrasi berhasil! Silakan login.</span>
                      </div>';
            }
            if (!empty($message)) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">Gagal!</strong>
                        <span class="block sm:inline">' . htmlspecialchars($message) . '</span>
                      </div>';
            }
        ?>

        <form action="login.php" method="post" class="space-y-6">
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
            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md 
                           transition-colors duration-300 shadow-lg text-lg transform hover:-translate-y-1">
                Login
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-gray-600">Belum punya akun? 
                <a href="register.php" class="font-medium text-blue-600 hover:text-blue-800 hover:underline">Daftar di sini</a>
            </p>
        </div>
    </div>
</body>
</html>
