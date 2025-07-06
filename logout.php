<?php
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Hancurkan session
session_destroy();

// Redirect ke halaman login (login.php ada di direktori yang sama dengan logout.php)
header("Location: login.php");
exit;
?>
