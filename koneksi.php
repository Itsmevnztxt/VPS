<?php
// Matikan error teknis agar web terlihat bersih
error_reporting(0);

// Mulai Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "mental_health_db";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("<h3>Gagal Konek Database. Pastikan database 'mental_health_db' sudah dibuat.</h3>");
}

date_default_timezone_set('Asia/Jakarta');

// --- PENGATURAN BAHASA & TEKS ---
// Default bahasa Indonesia jika belum diset
if (!isset($_SESSION['lang'])) $_SESSION['lang'] = 'id';
if (isset($_GET['lang'])) $_SESSION['lang'] = $_GET['lang'];

// Kamus Kata Lengkap
$lang_map = [
    'id' => [
        'nav_home' => 'Beranda',
        'nav_services' => 'Layanan',
        'nav_doctors' => 'Psikolog',
        'btn_login' => 'Masuk',
        'btn_register' => 'Daftar',
        'btn_dashboard' => 'Dashboard',
        'hero_title' => 'Pulihkan Pikiran, Temukan Harapan',
        'hero_desc' => 'Layanan kesehatan mental profesional yang aman, nyaman, dan terjangkau untuk semua kalangan.',
        'btn_start' => 'Konsultasi Sekarang',
        'btn_learn' => 'Cari Psikolog',
        'stat_1' => 'Pasien Sembuh',
        'stat_2' => 'Psikolog Aktif',
        'stat_3' => 'Rating Aplikasi',
        'feat_title' => 'Layanan Unggulan',
        'feat_1' => 'Konseling Online',
        'feat_1_desc' => 'Tatap muka dengan psikolog via video call.',
        'feat_2' => 'AI Chatbot',
        'feat_2_desc' => 'Teman cerita cerdas 24/7.',
        'feat_3' => 'Jurnal Mood',
        'feat_3_desc' => 'Pantau emosimu setiap hari.'
    ],
    'en' => [
        'nav_home' => 'Home',
        'nav_services' => 'Services',
        'nav_doctors' => 'Doctors',
        'btn_login' => 'Login',
        'btn_register' => 'Register',
        'btn_dashboard' => 'Dashboard',
        'hero_title' => 'Heal Your Mind, Find Hope',
        'hero_desc' => 'Professional mental health services that are safe, comfortable, and affordable for everyone.',
        'btn_start' => 'Consult Now',
        'btn_learn' => 'Find Doctors',
        'stat_1' => 'Patients Healed',
        'stat_2' => 'Active Doctors',
        'stat_3' => 'App Rating',
        'feat_title' => 'Our Services',
        'feat_1' => 'Online Counseling',
        'feat_1_desc' => 'Face to face with psychologists via video call.',
        'feat_2' => 'AI Chatbot',
        'feat_2_desc' => 'Intelligent chat companion 24/7.',
        'feat_3' => 'Mood Journal',
        'feat_3_desc' => 'Track your emotions daily.'
    ]
];

// Fungsi Penerjemah Aman
function t($key) {
    global $lang_map;
    $l = $_SESSION['lang'];
    // Jika teks di bahasa terpilih tidak ada, pakai bahasa Indonesia, jika tidak ada juga, tampilkan key-nya
    return $lang_map[$l][$key] ?? ($lang_map['id'][$key] ?? ucfirst(str_replace('_', ' ', $key)));
}
?>