<?php
// Catatan: Session sudah distart di dashboard.php (Router)

// Gunakan koneksi dari dashboard.php, atau coba koneksi manual jika gagal
if (!isset($conn) || !$conn) {
    $conn = false;
    try {
        $conn = mysqli_connect("localhost", "root", "", "mental_health_db");
    } catch(Exception $e) { $conn = false; }
}

// --- DATA USER AKTIF ---
$idx = $_SESSION['active_index'] ?? 0;

// Fallback jika akun demo belum di-set
if (!isset($_SESSION['accounts']) || empty($_SESSION['accounts'])) {
    $_SESSION['accounts'] = [
        0 => ['id'=>1, 'name'=>'Agung Dwi Saputra', 'email'=>'olvansgmg@gmail.com', 'role'=>'patient', 'bio'=>'Semangat!', 'avatar'=>'default.png'],
        1 => ['id'=>2, 'name'=>'Aditya Pratama Putra', 'email'=>'olvans2008@gmail.com', 'role'=>'patient', 'bio'=>'Keep strong!', 'avatar'=>'default.png']
    ];
}

$user_data = $_SESSION['accounts'][$idx] ?? $_SESSION['accounts'][0];
$uid = $_SESSION['user_id'] ?? $user_data['id']; 

// Variabel Tampilan
$nama_user = $user_data['name'];
$email_user = $user_data['email'];
$bio_user = $user_data['bio'] ?? 'User MindCare';

// --- LOGIC AVATAR DISPLAY (DIPERBAIKI) ---
// Cek apakah ada foto custom di session atau database
$custom_avatar = $user_data['avatar'] ?? 'default.png';
$avatar_path = 'uploads/' . $custom_avatar;

if ($custom_avatar != 'default.png' && file_exists($avatar_path)) {
    // Gunakan foto upload
    $avatar_src = $avatar_path . "?t=" . time(); // Anti-cache
} else {
    // Gunakan inisial nama
    $avatar_src = "https://ui-avatars.com/api/?name=".urlencode($nama_user)."&background=0d9488&color=fff";
}

// Saldo & Data User Realtime
$saldo_user = 0;
if ($conn) {
    $q_u = mysqli_query($conn, "SELECT balance FROM users WHERE id='$uid'");
    if($q_u && mysqli_num_rows($q_u) > 0){
        $d_u = mysqli_fetch_assoc($q_u);
        $saldo_user = $d_u['balance'];
    }
} 
if ($saldo_user == 0 && isset($_SESSION['demo_balance'])) {
    $saldo_user = $_SESSION['demo_balance']; // Gunakan demo balance jika DB 0/offline
}

// Inisialisasi Session Demo
if (!isset($_SESSION['demo_journals'])) $_SESSION['demo_journals'] = [];
if (!isset($_SESSION['demo_transactions'])) $_SESSION['demo_transactions'] = [];

// --- LOGIC: BOOKING SESSION ---
if (isset($_POST['book_session'])) {
    $tid = $_POST['therapist_id']; 
    $price = (int)$_POST['price']; 
    $date = $_POST['date']; 
    $time = $_POST['time'];
    
    $current_balance = $saldo_user;
    
    if ($current_balance >= $price) {
        if ($conn) {
            mysqli_query($conn, "UPDATE users SET balance = balance - $price WHERE id='$uid'");
            mysqli_query($conn, "INSERT INTO appointments (patient_id, therapist_id, date, time, amount, status) VALUES ('$uid', '$tid', '$date', '$time', '$price', 'scheduled')");
        } else {
            $_SESSION['demo_balance'] -= $price;
            if (!isset($_SESSION['demo_transactions'][$uid])) $_SESSION['demo_transactions'][$uid] = [];
            array_unshift($_SESSION['demo_transactions'][$uid], [
                'created_at' => date('Y-m-d H:i:s'),
                'type' => 'Booking Psikolog',
                'amount' => -$price,
                'status' => 'success'
            ]);
        }
        echo "<script>alert('Booking Berhasil! Silakan cek menu Riwayat.'); window.location='dashboard.php?view=history';</script>";
    } else {
        echo "<script>alert('Saldo tidak cukup! Silakan isi saldo terlebih dahulu.');</script>";
    }
}

// --- LOGIC BACKEND: CHATBOT (OLLAMA) ---
if (isset($_POST['chat_message'])) {
    if (ob_get_length()) ob_clean(); 
    header('Content-Type: application/json');
    
    $input = $_POST['chat_message'];
    $history = json_decode($_POST['chat_history'] ?? '[]', true);
    
    $ollama_url = "http://localhost:11434/api/chat"; 
    $ollama_model = "mistral"; 
    
    $messages = [["role" => "system", "content" => "Kamu adalah Mira, psikolog virtual."]];
    if (is_array($history)) {
        foreach ($history as $msg) {
            if (isset($msg['role'], $msg['content'])) {
                $messages[] = ["role" => ($msg['role'] == 'user' ? 'user' : 'assistant'), "content" => substr($msg['content'], 0, 500)];
            }
        }
    }
    $messages[] = ["role" => "user", "content" => $input];

    $ch = curl_init($ollama_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["model" => $ollama_model, "messages" => $messages, "stream" => false]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    $reply_html = ""; $reply_raw = "";

    if ($curl_error) {
        $reply_html = "<div class='bg-red-50 text-red-600 p-3 rounded-lg text-xs'><b>⚠️ Gagal Terhubung ke Ollama</b><br>Pastikan aplikasi Ollama berjalan.</div>";
    } else {
        $json = json_decode($response, true);
        if (isset($json['error'])) {
            $reply_html = "<div class='bg-yellow-50 text-yellow-700 p-3 rounded-lg text-xs'><b>⚠️ Model Belum Ada</b><br>Ketik: <code>ollama pull $ollama_model</code></div>";
            $reply_raw = "Model missing";
        } else {
            $reply_raw = $json['message']['content'] ?? "Maaf, saya bingung.";
            $reply_html = nl2br(htmlspecialchars($reply_raw));
            $reply_html = str_replace(['**', '__'], '', $reply_html); 
        }
    }
    
    echo json_encode(['status' => 'success', 'reply' => $reply_html, 'raw_reply' => $reply_raw]);
    exit(); 
}

// --- LOGIC: UPDATE PROFIL DENGAN FOTO (DIPERBAIKI) ---
if (isset($_POST['update_profile'])) {
    $newName = htmlspecialchars($_POST['name']);
    $newBio = htmlspecialchars($_POST['bio']);
    
    // Inisialisasi nama file gambar (pakai yang lama dulu)
    $avatar_name = $user_data['avatar'] ?? 'default.png';
    
    // Proses Upload Gambar
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Buat folder uploads jika belum ada
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            // Nama file unik: ID_Timestamp.ext
            $newFilename = $uid . '_' . time() . '.' . $ext;
            $destination = 'uploads/' . $newFilename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                $avatar_name = $newFilename; // Update nama file jika sukses
            }
        }
    }

    // Update Database
    if ($conn) {
        $q = "UPDATE users SET name='$newName', bio='$newBio'";
        // Hanya update kolom avatar jika ada upload baru
        if ($avatar_name != ($user_data['avatar'] ?? 'default.png')) {
            $q .= ", avatar='$avatar_name'";
        }
        $q .= " WHERE id='$uid'";
        mysqli_query($conn, $q);
    }
    
    // Update Session (Wajib agar langsung terlihat perubahannya)
    $_SESSION['accounts'][$idx]['name'] = $newName;
    $_SESSION['accounts'][$idx]['bio'] = $newBio;
    if ($avatar_name != ($user_data['avatar'] ?? 'default.png')) {
        $_SESSION['accounts'][$idx]['avatar'] = $avatar_name;
    }
    $_SESSION['name'] = $newName;

    echo "<script>alert('Profil berhasil diperbarui!'); window.location='dashboard.php?view=settings';</script>";
}

// --- LOGIC: JURNAL ---
if (isset($_POST['save_journal'])) {
    $mood = $_POST['mood'];
    $note = htmlspecialchars($_POST['note']);
    $date = date('Y-m-d H:i:s');
    
    if (!isset($_SESSION['demo_journals'][$uid])) $_SESSION['demo_journals'][$uid] = [];
    array_unshift($_SESSION['demo_journals'][$uid], ['mood' => $mood, 'content' => $note, 'created_at' => $date]);
    
    echo "<script>window.location='dashboard.php';</script>";
}

// --- LOGIC: TOP UP ---
if (isset($_POST['request_topup'])) {
    $amount = (int)$_POST['amount'];
    $date = date('Y-m-d H:i:s');

    if ($conn) {
        mysqli_query($conn, "INSERT INTO topups (user_id, amount, status) VALUES ('$uid', '$amount', 'pending')");
    } else {
        if (!isset($_SESSION['demo_transactions'][$uid])) $_SESSION['demo_transactions'][$uid] = [];
        array_unshift($_SESSION['demo_transactions'][$uid], [
            'created_at' => $date, 'type' => 'Top Up Saldo', 'amount' => $amount, 'status' => 'pending' 
        ]);
    }
    echo "<script>alert('Permintaan Top Up berhasil dikirim!'); window.location='dashboard.php?view=history';</script>";
}

$view = $_GET['view'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pasien - MindCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style> 
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8FAFC; }
        .nav-active { background: #CCFBF1; color: #0F766E; border-right: 4px solid #0F766E; }
        .chat-bubble { max-width: 85%; padding: 12px 16px; border-radius: 16px; margin-bottom: 12px; font-size: 0.95rem; line-height: 1.5; animation: fadeIn 0.3s; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .chat-user { background: #0D9488; color: white; align-self: flex-end; border-bottom-right-radius: 2px; margin-left: auto; }
        .chat-bot { background: white; color: #334155; align-self: flex-start; border-bottom-left-radius: 2px; border: 1px solid #E2E8F0; margin-right: auto; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        /* Dropdown Animation */
        .profile-dropdown { display: none; } 
        .profile-dropdown.show { display: block; animation: slideDown 0.2s ease-out; } 
        @keyframes slideDown { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-700">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-white border-r border-slate-200 hidden md:flex flex-col z-30 shrink-0">
        <div class="h-20 flex items-center px-8 border-b border-slate-100">
            <span class="font-extrabold text-xl text-teal-700 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-brain text-2xl"></i> MindCare
            </span>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Menu Utama</p>
            <a href="dashboard.php?view=home" class="<?= $view=='home'?'nav-active':'' ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium hover:bg-slate-50 text-slate-600 transition">
                <i class="fa-solid fa-house w-5 text-center"></i> Beranda
            </a>
            <a href="dashboard.php?view=community" class="<?= $view=='community'?'nav-active':'' ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium hover:bg-slate-50 text-slate-600 transition">
                <i class="fa-solid fa-users w-5 text-center"></i> Komunitas
            </a>
            <a href="dashboard.php?view=therapists" class="<?= $view=='therapists'?'nav-active':'' ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium hover:bg-slate-50 text-slate-600 transition">
                <i class="fa-solid fa-user-doctor w-5 text-center"></i> Cari Psikolog
            </a>
            <a href="dashboard.php?view=chatbot" class="<?= $view=='chatbot'?'nav-active':'' ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium hover:bg-slate-50 text-slate-600 transition">
                <i class="fa-solid fa-robot w-5 text-center"></i> AI Teman Cerita
            </a>
            <a href="dashboard.php?view=history" class="<?= $view=='history'?'nav-active':'' ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium hover:bg-slate-50 text-slate-600 transition">
                <i class="fa-solid fa-clock-rotate-left w-5 text-center"></i> Riwayat
            </a>
            <a href="dashboard.php?view=settings" class="<?= $view=='settings'?'nav-active':'' ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium hover:bg-slate-50 text-slate-600 transition">
                <i class="fa-solid fa-gear w-5 text-center"></i> Pengaturan
            </a>
            
            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 mt-4">Aplikasi</p>
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-500 hover:bg-teal-50 hover:text-teal-600 transition">
                <i class="fa-solid fa-globe w-5 text-center"></i> Ke Website Utama
            </a>
        </nav>
        <div class="p-4 border-t border-slate-100">
            <a href="dashboard.php?logout=true" class="flex items-center justify-center gap-2 w-full py-2.5 bg-red-50 text-red-600 rounded-xl font-bold hover:bg-red-100 transition text-sm">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-full overflow-hidden relative w-full">
        
        <!-- HEADER -->
        <header class="h-20 bg-white/90 backdrop-blur-sm border-b border-slate-200 flex items-center justify-between px-6 shrink-0 z-20 sticky top-0">
            <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <?php 
                    if($view=='community') echo '<i class="fa-solid fa-users text-teal-500"></i> Ruang Cerita'; 
                    elseif($view=='chatbot') echo '<i class="fa-solid fa-robot text-teal-500"></i> Chat dengan Mira'; 
                    elseif($view=='settings') echo '<i class="fa-solid fa-gear text-teal-500"></i> Pengaturan Profil'; 
                    elseif($view=='history') echo '<i class="fa-solid fa-receipt text-teal-500"></i> Riwayat Transaksi';
                    elseif($view=='therapists') echo '<i class="fa-solid fa-user-doctor text-teal-500"></i> Cari Psikolog';
                    else echo '<i class="fa-solid fa-chart-pie text-teal-500"></i> Dashboard'; 
                ?>
            </h2>
            <div class="flex items-center gap-6">
                <!-- Saldo Button -->
                <button onclick="document.getElementById('modalTopup').classList.remove('hidden')" class="hidden sm:flex items-center gap-2 px-4 py-1.5 bg-teal-50 hover:bg-teal-100 rounded-full border border-teal-100 transition cursor-pointer">
                    <i class="fa-solid fa-wallet text-teal-600"></i>
                    <span class="text-sm font-bold text-teal-700">Rp <?= number_format($saldo_user) ?></span>
                    <i class="fa-solid fa-plus-circle text-teal-400 text-xs ml-1"></i>
                </button>

                <!-- Profile Dropdown Trigger -->
                <div class="relative">
                    <button onclick="document.getElementById('profileDropdown').classList.toggle('show')" class="flex items-center gap-3 focus:outline-none group">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-bold text-slate-700 truncate max-w-[120px] group-hover:text-teal-600 transition"><?= explode(' ', $nama_user)[0] ?></p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase">Pasien</p>
                        </div>
                        <img src="<?= $avatar_src ?>" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm group-hover:border-teal-300 transition">
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div id="profileDropdown" class="profile-dropdown absolute right-0 mt-4 w-72 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden z-50 transform origin-top-right transition-all">
                        
                        <!-- Header Dropdown: Akun Aktif -->
                        <div class="p-5 border-b border-slate-50 bg-teal-50/30 text-center">
                            <div class="relative inline-block">
                                <img src="<?= $avatar_src ?>" class="w-16 h-16 rounded-full mx-auto mb-2 object-cover border-2 border-white shadow-sm">
                                <div class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                            </div>
                            <h4 class="font-bold text-slate-800 truncate"><?= $nama_user ?></h4>
                            <p class="text-xs text-slate-500 truncate mb-3"><?= $email_user ?></p>
                            <a href="dashboard.php?view=settings" class="inline-block px-4 py-1.5 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-600 hover:text-teal-600 hover:border-teal-200 transition">Edit Profil</a>
                        </div>

                        <!-- List Ganti Akun -->
                        <div class="max-h-48 overflow-y-auto p-2 space-y-1">
                            <p class="px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Ganti Akun</p>
                            
                            <?php if(isset($_SESSION['accounts']) && count($_SESSION['accounts']) > 1): ?>
                                <?php foreach($_SESSION['accounts'] as $key => $acc): ?>
                                    <?php if($key == $idx) continue; // Skip akun yang sedang aktif ?>
                                    
                                    <a href="dashboard.php?switch_account=<?= $key ?>&t=<?= time() ?>" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition group">
                                        <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden border border-slate-200 flex-shrink-0">
                                             <img src="https://ui-avatars.com/api/?name=<?= urlencode($acc['name']) ?>&background=random" class="w-full h-full object-cover">
                                        </div>
                                        <div class="text-left overflow-hidden min-w-0">
                                            <p class="text-sm font-bold text-slate-700 truncate group-hover:text-teal-600"><?= $acc['name'] ?></p>
                                            <p class="text-[10px] text-slate-400 truncate"><?= $acc['email'] ?></p>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="px-3 text-xs text-slate-400 italic">Tidak ada akun lain.</p>
                            <?php endif; ?>

                            <!-- Tombol Tambah Akun -->
                            <a href="auth.php?add_account=true" class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition group text-teal-600 mt-2 text-left">
                                <div class="w-10 h-10 rounded-full border border-dashed border-teal-300 flex items-center justify-center text-teal-500 group-hover:bg-teal-50 transition flex-shrink-0">
                                    <i class="fa-solid fa-plus"></i>
                                </div>
                                <span class="text-sm font-bold">Tambahkan Akun</span>
                            </a>
                        </div>

                        <!-- Logout -->
                        <div class="p-3 border-t border-slate-100 bg-slate-50 text-center">
                            <a href="dashboard.php?logout=true" class="text-red-500 text-xs font-bold hover:underline">Keluar dari semua akun</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- CONTENT AREA -->
        <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 bg-slate-50/50">
            
            <?php if ($view == 'home'): ?>
                <!-- [VIEW: HOME] -->
                <div class="grid lg:grid-cols-3 gap-6 max-w-7xl mx-auto">
                    <!-- KIRI: Banner & Jurnal -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-gradient-to-r from-teal-600 to-emerald-600 rounded-3xl p-8 text-white shadow-xl shadow-teal-600/10 relative overflow-hidden" data-aos="fade-up">
                            <div class="relative z-10">
                                <h1 class="text-3xl font-bold mb-2">Halo, <?= explode(' ', $nama_user)[0] ?>! 👋</h1>
                                <p class="text-teal-100 mb-6 text-sm font-medium opacity-90">Setiap perasaan itu valid. Apa kabarmu hari ini?</p>
                                <div class="flex gap-3">
                                    <button onclick="document.getElementById('modalTopup').classList.remove('hidden')" class="bg-white text-teal-700 px-5 py-2.5 rounded-full font-bold text-sm shadow-sm hover:bg-teal-50 transition"><i class="fa-solid fa-plus mr-1"></i> Isi Saldo</button>
                                    <a href="dashboard.php?view=chatbot" class="bg-teal-700/50 text-white px-5 py-2.5 rounded-full font-bold text-sm hover:bg-teal-700/70 transition border border-white/20"><i class="fa-solid fa-comment mr-1"></i> Curhat ke Mira</a>
                                </div>
                            </div>
                            <i class="fa-solid fa-leaf absolute -bottom-8 -right-8 text-9xl opacity-10 rotate-12"></i>
                        </div>

                        <!-- Jurnal Harian -->
                        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm" data-aos="fade-up" data-aos-delay="100">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-book-open text-teal-500"></i> Jurnal Harian</h3>
                                <span class="text-[10px] font-bold bg-slate-100 text-slate-500 px-2 py-1 rounded">Private</span>
                            </div>
                            <form method="POST" class="mb-6">
                                <div class="flex gap-4 mb-4 overflow-x-auto pb-2">
                                    <?php 
                                        $moods = ['senang'=>['😊','bg-yellow-100'], 'biasa'=>['😐','bg-gray-100'], 'sedih'=>['😢','bg-blue-100'], 'marah'=>['😠','bg-red-100']];
                                        foreach($moods as $k => $v): 
                                    ?>
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="mood" value="<?= $k ?>" class="peer hidden" <?= $k=='senang'?'checked':'' ?>>
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl bg-slate-50 border border-slate-200 grayscale peer-checked:grayscale-0 peer-checked:<?= $v[1] ?> peer-checked:border-transparent peer-checked:scale-110 transition group-hover:bg-slate-100"><?= $v[0] ?></div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="relative">
                                    <textarea name="note" rows="3" class="w-full bg-slate-50 rounded-xl border-none p-4 text-sm focus:ring-2 focus:ring-teal-200 resize-none transition placeholder-slate-400" placeholder="Tulis cerita singkat hari ini..." required></textarea>
                                    <button type="submit" name="save_journal" class="absolute bottom-3 right-3 bg-teal-600 text-white w-8 h-8 rounded-full flex items-center justify-center shadow hover:bg-teal-700 hover:scale-110 transition"><i class="fa-solid fa-paper-plane text-xs"></i></button>
                                </div>
                            </form>
                            <div class="space-y-3">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Catatan Terakhir</p>
                                <?php 
                                    $journals = $_SESSION['demo_journals'][$uid] ?? [];
                                    $journals = array_slice($journals, 0, 2);
                                    if (empty($journals)) { echo '<div class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-center text-xs text-slate-400">Belum ada catatan.</div>'; }
                                    else { foreach ($journals as $j): ?>
                                    <div class="flex gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100"><div class="text-xl">📝</div><div class="flex-1 min-w-0"><p class="text-sm text-slate-700 truncate"><?= htmlspecialchars($j['content']) ?></p><p class="text-[10px] text-slate-400 mt-1"><?= date('d M H:i', strtotime($j['created_at'])) ?></p></div></div>
                                <?php endforeach; } ?>
                            </div>
                        </div>
                    </div>

                    <!-- KANAN: Widget -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm text-center relative overflow-hidden" data-aos="fade-left">
                            <div class="w-20 h-20 bg-orange-50 text-orange-400 rounded-full flex items-center justify-center text-4xl mx-auto mb-4 floating shadow-sm">🌤️</div>
                            <h3 class="font-bold text-slate-800">Mood Mingguan</h3>
                            <p class="text-xs text-slate-500 mb-6">Grafik emosimu stabil minggu ini.</p>
                            <div class="flex items-end justify-center gap-2 h-16 px-4"><div class="w-2 bg-slate-100 rounded-t h-1/3"></div><div class="w-2 bg-slate-100 rounded-t h-2/3"></div><div class="w-2 bg-teal-400 rounded-t h-full"></div><div class="w-2 bg-slate-100 rounded-t h-1/2"></div></div>
                        </div>
                    </div>
                </div>

            <?php elseif ($view == 'therapists'): ?>
                <!-- [VIEW: THERAPISTS (DENGAN DUMMY DATA)] -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6" data-aos="fade-up">
                    <?php 
                    // 1. Coba ambil data dari DB
                    $therapists = [];
                    if ($conn) {
                        $q_t = mysqli_query($conn, "SELECT t.id, t.specialization, t.experience_years, t.price, u.name 
                                                  FROM therapists t 
                                                  JOIN users u ON t.user_id = u.id");
                        if ($q_t && mysqli_num_rows($q_t) > 0) {
                            while($row = mysqli_fetch_assoc($q_t)) {
                                $therapists[] = $row;
                            }
                        }
                    }

                    // 2. Jika Kosong (DB Error atau Belum ada Data), gunakan DUMMY
                    if (empty($therapists)) {
                        $therapists = [
                            ['id'=>1, 'name'=>'Dr. Sarah Wijaya, M.Psi', 'specialization'=>'Psikolog Klinis', 'experience_years'=>5, 'price'=>150000],
                            ['id'=>2, 'name'=>'Budi Santoso, S.Psi', 'specialization'=>'Konselor Keluarga', 'experience_years'=>8, 'price'=>200000],
                            ['id'=>3, 'name'=>'Citra Kirana, M.Psi', 'specialization'=>'Psikolog Anak', 'experience_years'=>3, 'price'=>120000],
                            ['id'=>4, 'name'=>'Dr. Andi Pratama', 'specialization'=>'Trauma Healing', 'experience_years'=>10, 'price'=>250000],
                        ];
                    }

                    foreach($therapists as $t): 
                    ?>
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition flex flex-col group">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-14 h-14 bg-teal-50 rounded-2xl flex items-center justify-center text-teal-600 text-xl font-bold border border-teal-100 group-hover:bg-teal-600 group-hover:text-white transition">
                                <?= substr($t['name'], 0, 1) ?>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-lg leading-tight"><?= $t['name'] ?></h3>
                                <span class="bg-teal-50 text-teal-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide"><?= $t['specialization'] ?></span>
                            </div>
                        </div>
                        
                        <div class="mt-auto space-y-3 border-t border-slate-50 pt-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Pengalaman</span>
                                <span class="font-bold text-slate-700"><?= $t['experience_years'] ?> Tahun</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Sesi 1 Jam</span>
                                <span class="font-bold text-teal-600">Rp <?= number_format($t['price']) ?></span>
                            </div>
                            <button onclick="openBooking(<?= $t['id'] ?>, '<?= $t['name'] ?>', <?= $t['price'] ?>)" class="w-full py-2.5 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-teal-600 transition shadow-lg mt-2">
                                Booking Jadwal
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- MODAL BOOKING -->
                <div id="modalBook" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
                    <div class="bg-white rounded-3xl p-6 w-full max-w-sm shadow-2xl" data-aos="zoom-in">
                        <h3 class="font-bold text-lg mb-1 text-slate-800">Konfirmasi Booking</h3>
                        <p class="text-sm text-slate-500 mb-6">Psikolog: <span id="tName" class="font-bold text-teal-700"></span></p>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="therapist_id" id="tId">
                            <input type="hidden" name="price" id="tPrice">
                            <div>
                                <label class="text-xs font-bold text-slate-400 uppercase ml-1">Tanggal</label>
                                <input type="date" name="date" class="w-full px-4 py-3 rounded-xl border border-slate-200 outline-none focus:border-teal-500 text-sm font-medium" required>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-400 uppercase ml-1">Jam</label>
                                <select name="time" class="w-full px-4 py-3 rounded-xl border border-slate-200 outline-none focus:border-teal-500 text-sm font-medium">
                                    <option>09:00</option><option>13:00</option><option>19:00</option>
                                </select>
                            </div>
                            <div class="p-4 bg-teal-50 rounded-xl flex justify-between items-center border border-teal-100">
                                <span class="text-sm text-teal-800 font-bold">Total Bayar</span>
                                <span id="dPrice" class="text-lg font-extrabold text-teal-600"></span>
                            </div>
                            <div class="flex gap-2 pt-2">
                                <button type="button" onclick="document.getElementById('modalBook').classList.add('hidden')" class="flex-1 py-3 bg-slate-100 font-bold text-slate-600 rounded-xl hover:bg-slate-200 transition text-sm">Batal</button>
                                <button type="submit" name="book_session" class="flex-1 py-3 bg-teal-600 text-white font-bold rounded-xl shadow-lg hover:bg-teal-700 transition text-sm">Bayar Sekarang</button>
                            </div>
                        </form>
                    </div>
                </div>
                <script>
                    function openBooking(id, name, price){
                        document.getElementById('modalBook').classList.remove('hidden');
                        document.getElementById('tId').value=id;
                        document.getElementById('tName').innerText=name;
                        document.getElementById('tPrice').value=price;
                        document.getElementById('dPrice').innerText='Rp '+new Intl.NumberFormat().format(price);
                    }
                </script>

            <?php elseif ($view == 'chatbot'): ?>
                <!-- [VIEW: CHATBOT] -->
                <div class="flex flex-col h-[calc(100vh-8rem)] bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" data-aos="fade-up">
                    <div id="chat-box" class="flex-1 overflow-y-auto p-4 md:p-6 bg-slate-50 flex flex-col">
                        <div class="chat-bubble chat-bot">Halo! Saya Mira (AI).<br>Ceritakan apa saja, saya siap mendengarkan. 😊</div>
                    </div>
                    <form onsubmit="sendChat(event)" class="p-4 bg-white border-t border-slate-100 flex gap-2 shrink-0">
                        <input type="text" id="chat-input" class="flex-1 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition text-sm" placeholder="Ketik pesan..." required>
                        <button type="submit" id="send-btn" class="w-12 h-12 bg-teal-600 text-white rounded-xl flex items-center justify-center hover:bg-teal-700 transition shadow-lg"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                </div>
                <script>
                    let chatHistory = []; 
                    async function sendChat(e){
                        e.preventDefault(); const inp=document.getElementById('chat-input'), btn=document.getElementById('send-btn'), txt=inp.value.trim(); if(!txt) return;
                        const box=document.getElementById('chat-box');
                        box.innerHTML+=`<div class="chat-bubble chat-user">${txt}</div>`; inp.value=''; inp.disabled=true; btn.disabled=true; box.scrollTop=box.scrollHeight;
                        const lId='l-'+Date.now(); box.innerHTML+=`<div id="${lId}" class="chat-bubble chat-bot text-slate-400 italic text-xs">...</div>`; box.scrollTop=box.scrollHeight;
                        const fd=new FormData(); fd.append('chat_message',txt); fd.append('chat_history',JSON.stringify(chatHistory));
                        try{
                            // PENTING: URL Fetch ke dashboard.php (router)
                            const r=await fetch('dashboard.php',{method:'POST',body:fd}); 
                            const j=await r.json(); 
                            document.getElementById(lId).remove(); 
                            box.innerHTML+=`<div class="chat-bubble chat-bot">${j.reply}</div>`; 
                            chatHistory.push({role:"user",content:txt}); 
                            if(j.raw_reply)chatHistory.push({role:"assistant",content:j.raw_reply});
                        }
                        catch(e){document.getElementById(lId).remove(); box.innerHTML+=`<div class="chat-bubble chat-bot text-red-500">Error koneksi ke server.</div>`;}
                        inp.disabled=false; btn.disabled=false; inp.focus(); box.scrollTop=box.scrollHeight;
                    }
                </script>

            <?php elseif ($view == 'settings'): ?>
                <!-- [VIEW: SETTINGS] -->
                <div class="max-w-2xl mx-auto" data-aos="fade-up">
                    <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                        <h2 class="text-2xl font-bold text-slate-800 mb-6">Edit Profil</h2>
                        <form method="POST" enctype="multipart/form-data" class="space-y-6">
                            <div class="flex flex-col items-center mb-6">
                                <label class="relative group cursor-pointer">
                                    <img src="<?= $avatar_src ?>" id="preview" class="w-24 h-24 rounded-full object-cover border-4 border-slate-50 shadow-md">
                                    <div class="absolute bottom-0 right-0 bg-teal-600 text-white p-2 rounded-full shadow hover:bg-teal-700 transition">
                                        <i class="fa-solid fa-camera text-xs"></i>
                                    </div>
                                    <input type="file" name="avatar" class="hidden" onchange="document.getElementById('preview').src=window.URL.createObjectURL(this.files[0])">
                                </label>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Nama Lengkap</label>
                                <input type="text" name="name" value="<?= $nama_user ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-teal-500 focus:outline-none transition font-medium">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Bio Singkat</label>
                                <textarea name="bio" rows="3" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-teal-500 focus:outline-none transition font-medium"><?= htmlspecialchars($bio_user) ?></textarea>
                            </div>
                            
                            <div class="pt-4 flex justify-end gap-3">
                                <button type="submit" name="update_profile" class="px-6 py-3 bg-teal-600 text-white rounded-xl font-bold hover:bg-teal-700 shadow-lg transition">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($view == 'history'): ?>
                <!-- [VIEW: HISTORY] -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden" data-aos="fade-up">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-bold text-slate-800 text-lg">Riwayat Aktivitas</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                                <tr>
                                    <th class="p-5">Tanggal</th>
                                    <th class="p-5">Aktivitas</th>
                                    <th class="p-5">Nominal</th>
                                    <th class="p-5">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php 
                                    // Ambil riwayat topup dari session per user atau DB
                                    $transactions = [];
                                    if ($conn) {
                                        // Gabung Topup dan Appointment
                                        $q_tr = mysqli_query($conn, "SELECT created_at, 'Top Up Saldo' as type, amount, status FROM topups WHERE user_id='$uid' 
                                            UNION ALL 
                                            SELECT created_at, 'Booking Psikolog' as type, -amount as amount, status FROM appointments WHERE patient_id='$uid' 
                                            ORDER BY created_at DESC LIMIT 20");
                                        while($t = mysqli_fetch_assoc($q_tr)) $transactions[] = $t;
                                    } else {
                                        $transactions = $_SESSION['demo_transactions'][$uid] ?? [];
                                    }
                                    
                                    if (empty($transactions)) {
                                        echo '<tr><td colspan="4" class="p-5 text-center text-slate-400">Belum ada riwayat transaksi.</td></tr>';
                                    } else {
                                        foreach($transactions as $t): 
                                            $st = $t['status'];
                                            $badgeColor = $st == 'success' || $st == 'scheduled' ? 'bg-green-100 text-green-700' : ($st == 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
                                            $statusLabel = $st == 'success' ? 'Berhasil' : ($st == 'pending' ? 'Menunggu' : ($st=='scheduled'?'Terjadwal':'Gagal'));
                                            $colorAmt = $t['amount'] > 0 ? 'text-teal-600' : 'text-red-500';
                                            $prefix = $t['amount'] > 0 ? '+ ' : '';
                                ?>
                                <tr>
                                    <td class="p-5 text-slate-600"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></td>
                                    <td class="p-5 font-bold text-slate-700"><?= $t['type'] ?></td>
                                    <td class="p-5 font-bold <?= $colorAmt ?>"><?= $prefix . "Rp " . number_format(abs($t['amount'])) ?></td>
                                    <td class="p-5"><span class="px-3 py-1 rounded-full text-xs font-bold <?= $badgeColor ?>"><?= $statusLabel ?></span></td>
                                </tr>
                                <?php endforeach; } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($view == 'community'): ?>
                <!-- [VIEW: COMMUNITY] -->
                <div class="max-w-2xl mx-auto" data-aos="fade-up">
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm mb-8">
                        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-pen-nib text-teal-500"></i> Bagikan Cerita (Anonim)</h3>
                        <form method="POST">
                            <textarea name="content" rows="3" class="w-full p-4 bg-slate-50 rounded-xl border border-slate-200 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 text-sm mb-4 transition resize-none" placeholder="Apa yang kamu rasakan?" required></textarea>
                            <div class="flex justify-end"><button class="bg-teal-600 text-white px-6 py-2 rounded-xl font-bold text-sm hover:bg-teal-700 transition">Kirim Cerita</button></div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- MODAL TOPUP -->
    <div id="modalTopup" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl p-6 w-full max-w-sm shadow-2xl" data-aos="zoom-in">
            <h3 class="font-bold text-lg text-center mb-1 text-slate-800">Isi Saldo (Demo)</h3>
            <form method="POST">
                <input type="number" name="amount" class="w-full px-4 py-3 rounded-xl border-2 border-slate-100 text-xl text-center font-bold text-slate-700 mb-6 focus:border-teal-500 outline-none" placeholder="50000" min="10000" required>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('modalTopup').classList.add('hidden')" class="flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition text-sm">Batal</button>
                    <button type="submit" name="request_topup" class="flex-1 py-3 bg-teal-600 text-white rounded-xl font-bold hover:bg-teal-700 transition text-sm">Isi Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({once: true, duration: 600});
        window.onclick = function(e) {
            if (!e.target.closest('.relative')) {
                document.getElementById('profileDropdown').classList.remove('show');
            }
        }
    </script>
</body>
</html>