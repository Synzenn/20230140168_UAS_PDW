<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika pengguna belum login atau bukan asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php"); // Path yang benar dari asisten/ ke login.php di root
    exit();
}

// Sertakan file konfigurasi database
// Menggunakan __DIR__ untuk memastikan path absolut yang benar
require_once __DIR__ . '/../../config.php';

// Variabel untuk menandai halaman aktif di navigasi
$activePage = $pageTitle ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Asisten - <?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Custom scrollbar for better aesthetics */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #2d3748; /* Darker track for dark theme */
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #4a5568; /* Darker thumb for dark theme */
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }

        /* Custom styles for sidebar transition */
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }
        .sidebar-open {
            transform: translateX(0);
        }
        .sidebar-closed {
            transform: translateX(-100%);
        }
        @media (min-width: 768px) { /* md breakpoint */
            .sidebar-closed {
                transform: translateX(0); /* Always open on desktop */
            }
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 w-72 bg-gray-800 shadow-lg z-50 md:relative md:translate-x-0 sidebar-closed">
            <div class="flex items-center justify-between p-6 border-b border-gray-700">
                <span class="text-white text-3xl font-bold">Panel Asisten</span>
                <!-- Close button for mobile sidebar -->
                <button type="button" class="md:hidden text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white" id="sidebar-close-button">
                    <span class="sr-only">Close sidebar</span>
                    <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="mt-8">
                <div class="px-4 space-y-2">
                    <?php 
                        $activeClass = 'bg-blue-700 text-white shadow-md';
                        $inactiveClass = 'text-gray-300 hover:bg-gray-700 hover:text-white';
                    ?>
                    <a href="dashboard.php" class="flex items-center px-6 py-3 rounded-lg text-lg font-medium transition-colors duration-200 <?php echo ($activePage == 'Dashboard') ? $activeClass : $inactiveClass; ?>">
                        <i class="fas fa-tachometer-alt mr-4 text-xl"></i>Dashboard
                    </a>
                    <a href="manage_praktikum.php" class="flex items-center px-6 py-3 rounded-lg text-lg font-medium transition-colors duration-200 <?php echo ($activePage == 'Kelola Praktikum') ? $activeClass : $inactiveClass; ?>">
                        <i class="fas fa-flask mr-4 text-xl"></i>Manajemen Mata Praktikum
                    </a>
                    <a href="manage_modul.php" class="flex items-center px-6 py-3 rounded-lg text-lg font-medium transition-colors duration-200 <?php echo ($activePage == 'Kelola Modul') ? $activeClass : $inactiveClass; ?>">
                        <i class="fas fa-cubes mr-4 text-xl"></i>Manajemen Modul
                    </a>
                    <a href="view_reports.php" class="flex items-center px-6 py-3 rounded-lg text-lg font-medium transition-colors duration-200 <?php echo ($activePage == 'Lihat Laporan') ? $activeClass : $inactiveClass; ?>">
                        <i class="fas fa-file-alt mr-4 text-xl"></i>Laporan Masuk
                    </a>
                    <a href="manage_users.php" class="flex items-center px-6 py-3 rounded-lg text-lg font-medium transition-colors duration-200 <?php echo ($activePage == 'Kelola Pengguna') ? $activeClass : $inactiveClass; ?>">
                        <i class="fas fa-users-cog mr-4 text-xl"></i>Manajemen Akun Pengguna
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main content area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar for mobile and desktop for user info/logout -->
            <header class="w-full bg-gray-800 shadow-md p-4 flex items-center justify-between md:hidden">
                <button type="button" class="text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white" id="sidebar-open-button">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <span class="text-white text-xl font-bold">Dashboard Asisten</span>
                <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md text-base transition-colors duration-300 shadow-md">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </header>

            <!-- Desktop top bar for user info/logout -->
            <header class="hidden md:flex w-full bg-gray-800 shadow-md p-4 items-center justify-end">
                <div class="flex items-center space-x-6">
                    <span class="text-gray-300 text-lg">Selamat Datang, <span class="font-semibold text-white"><?php echo htmlspecialchars($_SESSION['nama']); ?></span></span>
                    <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300 shadow-md text-lg">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </header>

            <!-- Page Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-900 p-8 lg:p-10">
