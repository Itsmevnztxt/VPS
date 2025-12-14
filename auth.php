<?php
include 'koneksi.php';

// Cek apakah user sedang ingin menambah akun (Mode Switch Account)
$is_adding = isset($_GET['add_account']);

// Jika sudah login DAN tidak sedang tambah akun, lempar ke dashboard
if (isset($_SESSION['user_id']) && !$is_adding) {
    header("Location: dashboard.php");
    exit();
}

$msg = "";

// --- PROSES LOGIN ---
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = $_POST['password'];

    $q = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
    if (mysqli_num_rows($q) == 1) {
        $row = mysqli_fetch_assoc($q);
        if (password_verify($pass, $row['password'])) {
            
            // LOGIKA MULTI-ACCOUNT (SESSION ARRAY)
            if (!isset($_SESSION['accounts'])) $_SESSION['accounts'] = [];
            
            // Cek apakah akun sudah ada di list session (Update jika ada)
            $found_idx = -1;
            foreach ($_SESSION['accounts'] as $k => $v) {
                if ($v['id'] == $row['id']) { $found_idx = $k; $_SESSION['accounts'][$k] = $row; break; }
            }
            
            // Jika baru, tambahkan ke array
            if ($found_idx == -1) {
                $_SESSION['accounts'][] = $row;
                $idx = count($_SESSION['accounts']) - 1;
            } else {
                $idx = $found_idx;
            }

            // Set Akun Aktif
            $_SESSION['active_index'] = $idx;
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['name'] = $row['name'];
            
            header("Location: dashboard.php");
            exit();
        }
    }
    $msg = "Email atau Password salah!";
}

// --- PROSES REGISTER ---
if (isset($_POST['register'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Hardcode Admin: Hanya email ini yang jadi admin
    $role = ($email === 'gregoriusolvans16@gmail.com') ? 'admin' : 'patient';

    $check = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $msg = "Email sudah terdaftar!";
    } else {
        $q = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$pass', '$role')";
        if (mysqli_query($conn, $q)) {
            $msg = "Akun berhasil dibuat! Silakan Login.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Akses MindCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-teal-50 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 md:p-12 rounded-[2rem] shadow-xl w-full max-w-md border border-teal-100">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-slate-800 mb-2">
                <?= $is_adding ? 'Tambahkan Akun' : 'Selamat Datang' ?>
            </h1>
            <p class="text-slate-500">Kesehatan mental Anda prioritas kami.</p>
        </div>
        
        <?php if($msg): ?><div class="bg-red-50 text-red-600 p-3 rounded-xl mb-4 text-center text-sm font-bold border border-red-100"><?= $msg ?></div><?php endif; ?>

        <!-- LOGIN FORM -->
        <form id="form-login" method="POST" class="space-y-5">
            <div><label class="block text-sm font-bold text-slate-700 mb-1">Email</label><input type="email" name="email" class="w-full px-5 py-3 rounded-xl border border-slate-200 focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none transition" required></div>
            <div><label class="block text-sm font-bold text-slate-700 mb-1">Password</label><input type="password" name="password" class="w-full px-5 py-3 rounded-xl border border-slate-200 focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none transition" required></div>
            <button type="submit" name="login" class="w-full py-3 bg-teal-600 text-white rounded-xl font-bold hover:bg-teal-700 transition shadow-lg shadow-teal-500/20">Masuk</button>
        </form>

        <!-- REGISTER FORM -->
        <form id="form-register" method="POST" class="space-y-5 hidden">
            <div><label class="block text-sm font-bold text-slate-700 mb-1">Nama Lengkap</label><input type="text" name="name" class="w-full px-5 py-3 rounded-xl border border-slate-200 focus:border-teal-500 outline-none" required></div>
            <div><label class="block text-sm font-bold text-slate-700 mb-1">Email</label><input type="email" name="email" class="w-full px-5 py-3 rounded-xl border border-slate-200 focus:border-teal-500 outline-none" required></div>
            <div><label class="block text-sm font-bold text-slate-700 mb-1">Password</label><input type="password" name="password" class="w-full px-5 py-3 rounded-xl border border-slate-200 focus:border-teal-500 outline-none" required></div>
            <button type="submit" name="register" class="w-full py-3 bg-teal-600 text-white rounded-xl font-bold hover:bg-teal-700 transition shadow-lg">Buat Akun</button>
        </form>
        
        <div class="mt-6 text-center text-sm text-slate-500">
            <span id="text-switch">Belum punya akun?</span>
            <button onclick="toggleAuth()" id="btn-switch" class="text-teal-600 font-bold hover:underline">Daftar sekarang</button>
        </div>
        <div class="mt-8 pt-6 border-t border-slate-100 text-center">
            <a href="index.php" class="text-slate-400 text-sm hover:text-teal-600 transition">Kembali ke Beranda</a>
        </div>
    </div>

    <script>
        function toggleAuth() {
            const login = document.getElementById('form-login');
            const reg = document.getElementById('form-register');
            const txt = document.getElementById('text-switch');
            const btn = document.getElementById('btn-switch');
            
            if(login.classList.contains('hidden')) {
                login.classList.remove('hidden'); reg.classList.add('hidden');
                txt.innerText = 'Sudah punya akun?'; btn.innerText = 'Masuk';
            } else {
                login.classList.add('hidden'); reg.classList.remove('hidden');
                txt.innerText = 'Belum punya akun?'; btn.innerText = 'Daftar sekarang';
            }
        }
    </script>
</body>
</html>