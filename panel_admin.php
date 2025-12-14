<?php
// --- LOGIKA BACKEND ---
$admin_name = $_SESSION['name'];
$view = $_GET['view'] ?? 'dashboard';

// 1. HITUNG NOTIFIKASI PENDING (FIXED)
// Ini wajib ada agar banner notifikasi muncul
$query_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM topups WHERE status='pending'");
$data_pending = mysqli_fetch_assoc($query_pending);
$count_pending = $data_pending['total'];

// 2. DATA UNTUK GRAFIK (Pendapatan 7 Hari Terakhir)
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($date));
    $q_sum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM topups WHERE status='success' AND DATE(created_at) = '$date'"));
    $chart_data[] = $q_sum['total'] ?? 0;
}

// 3. LOGIKA HAPUS USER
if (isset($_GET['delete_user'])) {
    $uid = $_GET['delete_user'];
    if ($uid != $_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id='$uid'");
        echo "<script>alert('User berhasil dihapus.'); window.location='dashboard.php?view=users';</script>";
    }
}

// 4. LOGIKA TAMBAH PSIKOLOG
if (isset($_POST['add_therapist'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $spec = mysqli_real_escape_string($conn, $_POST['spec']);
    $price = $_POST['price'];
    
    $cek = mysqli_query($conn, "SELECT email FROM users WHERE email='$email'");
    if(mysqli_num_rows($cek) == 0) {
        mysqli_query($conn, "INSERT INTO users (name, email, password, role, avatar) VALUES ('$name', '$email', '$pass', 'therapist', 'default.png')");
        $uid = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO therapists (user_id, specialization, price) VALUES ('$uid', '$spec', '$price')");
        echo "<script>alert('Psikolog Ditambahkan!'); window.location='dashboard.php?view=therapists';</script>";
    } else {
        echo "<script>alert('Email sudah terdaftar!');</script>";
    }
}

// 5. VALIDASI TOPUP (TERIMA)
if (isset($_GET['acc_topup'])) {
    $tid = $_GET['acc_topup'];
    $tdata = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM topups WHERE id='$tid'"));
    
    // Pastikan status masih pending sebelum diproses agar saldo tidak nambah 2x
    if($tdata && $tdata['status'] == 'pending'){
        // Update status jadi success
        mysqli_query($conn, "UPDATE topups SET status='success' WHERE id='$tid'");
        // Tambah saldo user
        mysqli_query($conn, "UPDATE users SET balance = balance + {$tdata['amount']} WHERE id='{$tdata['user_id']}'");
        echo "<script>alert('Top Up Diterima! Saldo user bertambah.'); window.location='dashboard.php?view=transactions';</script>";
    }
}

// 6. VALIDASI TOPUP (TOLAK)
if (isset($_GET['reject_topup'])) {
    $tid = $_GET['reject_topup'];
    mysqli_query($conn, "UPDATE topups SET status='rejected' WHERE id='$tid'");
    echo "<script>alert('Top Up Ditolak.'); window.location='dashboard.php?view=transactions';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - MindCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8FAFC; } .nav-active { background: #0F766E; color: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); } </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col shrink-0 transition-all duration-300">
        <div class="h-20 flex items-center px-6 border-b border-slate-800">
            <i class="fa-solid fa-brain text-teal-400 text-2xl mr-3"></i>
            <span class="text-xl font-bold text-white">Mind<span class="text-teal-400">Admin</span></span>
        </div>
        <div class="p-6 border-b border-slate-800 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-teal-600 text-white flex items-center justify-center font-bold">A</div>
            <div class="overflow-hidden">
                <h4 class="text-sm font-bold text-white truncate"><?= explode(' ',$admin_name)[0] ?></h4>
                <p class="text-[10px] text-slate-400 uppercase tracking-wider">Super Admin</p>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <p class="px-4 text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Main Menu</p>
            <a href="dashboard.php?view=dashboard" class="<?= $view=='dashboard'?'nav-active':'' ?> flex items-center px-4 py-3 rounded-xl font-medium hover:bg-slate-800 transition"><i class="fa-solid fa-chart-pie w-5 mr-3"></i> Dashboard</a>
            <a href="dashboard.php?view=users" class="<?= $view=='users'?'nav-active':'' ?> flex items-center px-4 py-3 rounded-xl font-medium hover:bg-slate-800 transition"><i class="fa-solid fa-users w-5 mr-3"></i> Data User</a>
            <a href="dashboard.php?view=therapists" class="<?= $view=='therapists'?'nav-active':'' ?> flex items-center px-4 py-3 rounded-xl font-medium hover:bg-slate-800 transition"><i class="fa-solid fa-user-doctor w-5 mr-3"></i> Data Psikolog</a>
            
            <!-- MENU TRANSAKSI DENGAN BADGE -->
            <a href="dashboard.php?view=transactions" class="<?= $view=='transactions'?'nav-active':'' ?> flex items-center justify-between px-4 py-3 rounded-xl font-medium hover:bg-slate-800 transition">
                <div class="flex items-center"><i class="fa-solid fa-money-bill-wave w-5 mr-3"></i> Transaksi</div>
                <?php if($count_pending > 0): ?>
                    <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse"><?= $count_pending ?></span>
                <?php endif; ?>
            </a>
        </nav>
        <div class="p-4 border-t border-slate-800">
            <a href="dashboard.php?logout=true" class="flex items-center justify-center gap-2 w-full py-2.5 bg-red-600 text-white rounded-xl text-sm font-bold hover:bg-red-700 transition">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </aside>

    <!-- CONTENT -->
    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 shrink-0 z-10 shadow-sm">
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wide"><?= ucfirst($view) ?></h2>
            <a href="index.php" target="_blank" class="text-sm font-bold text-teal-600 hover:bg-teal-50 px-4 py-2 rounded-lg transition">Lihat Website <i class="fa-solid fa-external-link-alt ml-1"></i></a>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            
            <?php if ($view == 'dashboard'): ?>
                <!-- STATS & GRAPH -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Stat Cards -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex justify-between items-center">
                        <div><p class="text-slate-500 text-xs font-bold uppercase mb-1">Total Pasien</p><h3 class="text-3xl font-extrabold text-slate-800"><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role='patient'")) ?></h3></div>
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl"><i class="fa-solid fa-users"></i></div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex justify-between items-center">
                        <div><p class="text-slate-500 text-xs font-bold uppercase mb-1">Pending Topup</p><h3 class="text-3xl font-extrabold text-slate-800"><?= $count_pending ?></h3></div>
                        <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-xl flex items-center justify-center text-xl"><i class="fa-solid fa-clock"></i></div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex justify-between items-center">
                        <div><p class="text-slate-500 text-xs font-bold uppercase mb-1">Psikolog</p><h3 class="text-3xl font-extrabold text-slate-800"><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role='therapist'")) ?></h3></div>
                        <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-xl"><i class="fa-solid fa-user-doctor"></i></div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                        <h3 class="font-bold text-lg text-slate-800 mb-4">Grafik Transaksi (7 Hari)</h3>
                        <div class="h-64"><canvas id="revChart"></canvas></div>
                    </div>
                    <!-- User Aktif -->
                    <div class="lg:col-span-1 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col">
                        <h3 class="font-bold text-slate-800 mb-4">Status User</h3>
                        <div class="flex-1 overflow-y-auto pr-2 space-y-3 max-h-64">
                            <?php 
                            $q_on = mysqli_query($conn, "SELECT * FROM users WHERE role != 'admin' ORDER BY last_activity DESC LIMIT 10");
                            while($u = mysqli_fetch_assoc($q_on)):
                                $time_diff = time() - strtotime($u['last_activity']);
                                $is_online = $time_diff < 300; 
                            ?>
                            <div class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-lg transition">
                                <div class="relative">
                                    <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center font-bold text-slate-600 text-xs"><?= substr($u['name'],0,1) ?></div>
                                    <div class="absolute bottom-0 right-0 w-3 h-3 <?= $is_online?'bg-emerald-500':'bg-slate-300' ?> border-2 border-white rounded-full"></div>
                                </div>
                                <div class="min-w-0"><p class="text-sm font-bold text-slate-800 truncate"><?= $u['name'] ?></p><p class="text-[10px] text-slate-500"><?= $is_online ? 'Sedang Aktif' : 'Offline' ?></p></div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

            <?php elseif ($view == 'users'): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-left text-sm"><thead class="bg-slate-50 border-b font-bold text-slate-500"><tr><th class="p-4">Nama</th><th class="p-4">Email</th><th class="p-4">Role</th><th class="p-4">Aksi</th></tr></thead>
                    <tbody class="divide-y"><?php $qu = mysqli_query($conn, "SELECT * FROM users WHERE role!='admin' ORDER BY id DESC"); while($u=mysqli_fetch_assoc($qu)): ?>
                    <tr class="hover:bg-slate-50"><td class="p-4 font-bold"><?= $u['name'] ?></td><td class="p-4"><?= $u['email'] ?></td><td class="p-4"><span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $u['role']=='therapist'?'bg-purple-100 text-purple-700':'bg-blue-100 text-blue-700' ?>"><?= $u['role'] ?></span></td><td class="p-4"><a href="?view=users&delete_user=<?= $u['id'] ?>" onclick="return confirm('Hapus permanen?')" class="text-red-500 hover:underline font-bold">Hapus</a></td></tr><?php endwhile; ?></tbody></table>
                </div>

            <?php elseif ($view == 'transactions'): ?>
                
                <!-- NOTIFIKASI PENDING -->
                <?php if($count_pending > 0): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-2xl shadow-sm overflow-hidden mb-8">
                    <div class="p-6 border-b border-yellow-100 flex items-center gap-2">
                        <i class="fa-solid fa-bell text-yellow-600"></i>
                        <h3 class="font-bold text-yellow-800">Menunggu Konfirmasi (<?= $count_pending ?>)</h3>
                    </div>
                    <table class="w-full text-left text-sm">
                        <thead class="bg-yellow-100/50 text-yellow-800 font-bold"><tr><th class="p-4">User</th><th class="p-4">Tipe</th><th class="p-4">Jumlah</th><th class="p-4">Waktu</th><th class="p-4 text-center">Aksi</th></tr></thead>
                        <tbody class="divide-y divide-yellow-100">
                            <?php 
                            $q_pend = mysqli_query($conn, "SELECT t.*, u.name FROM topups t JOIN users u ON t.user_id=u.id WHERE t.status='pending'");
                            while($p = mysqli_fetch_assoc($q_pend)):
                            ?>
                            <tr class="bg-white hover:bg-yellow-50/50 transition">
                                <td class="p-4 font-bold"><?= $p['name'] ?></td>
                                <td class="p-4"><span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-bold">Top Up</span></td>
                                <td class="p-4 font-bold text-slate-700">Rp <?= number_format($p['amount']) ?></td>
                                <td class="p-4 text-slate-500"><?= $p['created_at'] ?></td>
                                <td class="p-4 text-center flex justify-center gap-2">
                                    <a href="?view=transactions&acc_topup=<?= $p['id'] ?>" class="bg-teal-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-teal-700 shadow-sm" onclick="return confirm('Terima Top Up ini?')">Terima</a>
                                    <a href="?view=transactions&reject_topup=<?= $p['id'] ?>" class="bg-red-100 text-red-600 px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-red-200" onclick="return confirm('Tolak Top Up ini?')">Tolak</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- RIWAYAT -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100"><h3 class="font-bold text-slate-800">Riwayat Semua Transaksi</h3></div>
                    <table class="w-full text-left text-sm"><thead class="bg-slate-50 font-bold text-slate-500"><tr><th class="p-4">User</th><th class="p-4">Tipe</th><th class="p-4">Jumlah</th><th class="p-4">Status</th><th class="p-4">Tanggal</th></tr></thead>
                    <tbody class="divide-y">
                        <?php 
                        $q_trans = mysqli_query($conn, "SELECT id, user_id, amount, status, 'Top Up' as type, created_at FROM topups WHERE status != 'pending' UNION ALL SELECT id, user_id, amount, status, 'Konsultasi' as type, created_at FROM appointments WHERE amount > 0 ORDER BY created_at DESC LIMIT 20");
                        while($tr = mysqli_fetch_assoc($q_trans)): 
                            $uid = $tr['user_id'];
                            $uname = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM users WHERE id='$uid'"))['name'] ?? 'Unknown';
                            $bg_status = $tr['status']=='success'||$tr['status']=='completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                        ?>
                        <tr><td class="p-4 font-bold"><?= $uname ?></td><td class="p-4"><?= $tr['type'] ?></td><td class="p-4 font-bold">Rp <?= number_format($tr['amount']) ?></td><td class="p-4"><span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $bg_status ?>"><?= $tr['status'] ?></span></td><td class="p-4 text-xs text-slate-400"><?= $tr['created_at'] ?></td></tr>
                        <?php endwhile; ?>
                    </tbody></table>
                </div>

            <?php elseif ($view == 'therapists'): ?>
                <div class="grid lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-1">
                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm sticky top-6">
                            <h3 class="font-bold text-lg mb-4 text-slate-800">Tambah Psikolog</h3>
                            <form method="POST" class="space-y-4">
                                <input type="text" name="name" placeholder="Nama Lengkap" class="w-full px-4 py-2 border rounded-lg" required>
                                <input type="email" name="email" placeholder="Email Login" class="w-full px-4 py-2 border rounded-lg" required>
                                <input type="password" name="password" placeholder="Password" class="w-full px-4 py-2 border rounded-lg" required>
                                <input type="text" name="spec" placeholder="Spesialisasi" class="w-full px-4 py-2 border rounded-lg" required>
                                <input type="number" name="price" placeholder="Tarif (Rp)" class="w-full px-4 py-2 border rounded-lg" required>
                                <button type="submit" name="add_therapist" class="w-full py-2 bg-teal-600 text-white font-bold rounded-lg hover:bg-teal-700">Simpan</button>
                            </form>
                        </div>
                    </div>
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                            <table class="w-full text-left text-sm"><thead class="bg-slate-50 font-bold"><tr><th class="p-4">Nama</th><th class="p-4">Spesialisasi</th><th class="p-4">Tarif</th></tr></thead>
                            <tbody class="divide-y"><?php $qp=mysqli_query($conn, "SELECT t.*, u.name FROM therapists t JOIN users u ON t.user_id=u.id"); while($p=mysqli_fetch_assoc($qp)): ?>
                            <tr><td class="p-4 font-bold"><?= $p['name'] ?></td><td class="p-4"><?= $p['specialization'] ?></td><td class="p-4">Rp <?= number_format($p['price']) ?></td></tr>
                            <?php endwhile; ?></tbody></table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('revChart');
        if(ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chart_labels) ?>,
                    datasets: [{
                        label: 'Pendapatan (Rp)',
                        data: <?= json_encode($chart_data) ?>,
                        borderColor: '#0d9488',
                        backgroundColor: 'rgba(13, 148, 136, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    </script>
</body>
</html>