<?php
// Mulai session paling awal
session_start();
include 'koneksi.php'; // Wajib ada agar $conn bisa dipakai di panel

// --- 1. INISIALISASI DATA DEMO (JIKA DB KOSONG/MODE DEMO) ---
if (!isset($_SESSION['accounts'])) {
    $_SESSION['accounts'] = [
        0 => [
            'id' => 1, 
            'name' => 'Agung Dwi Saputra', 
            'email' => 'olvansgmg@gmail.com', 
            'role' => 'patient', 
            'avatar' => 'default.png',
            'bio' => 'Semangat menjalani hari!'
        ],
        1 => [
            'id' => 2, 
            'name' => 'Aditya Pratama Putra', 
            'email' => 'olvans2008@gmail.com', 
            'role' => 'patient', 
            'avatar' => 'default.png',
            'bio' => 'Kesehatan mental adalah prioritas.'
        ]
    ];
}

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

// --- 2. FITUR GANTI AKUN (SWITCH ACCOUNT) ---
if (isset($_GET['switch_account'])) {
    $target_index = intval($_GET['switch_account']);
    
    if (isset($_SESSION['accounts'][$target_index])) {
        $new_user = $_SESSION['accounts'][$target_index];
        
        $_SESSION['active_index'] = $target_index;
        $_SESSION['user_id'] = $new_user['id'];
        $_SESSION['role'] = $new_user['role'];
        $_SESSION['name'] = $new_user['name'];
        
        session_write_close(); 
        header("Location: dashboard.php");
        exit();
    }
}

// --- 3. LOGOUT SYSTEM ---
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// --- 4. ARAHKAN KE PANEL SESUAI ROLE ---
$role = $_SESSION['role'] ?? 'patient';

// Update Last Activity jika user ada di DB
if($conn && isset($_SESSION['user_id'])){
    $uid = $_SESSION['user_id'];
    mysqli_query($conn, "UPDATE users SET last_activity=NOW() WHERE id='$uid'");
}

if ($role == 'admin') {
    include 'panel_admin.php';
} elseif ($role == 'therapist') {
    include 'panel_therapist.php';
} else {
    include 'panel_patient.php';
}
?>