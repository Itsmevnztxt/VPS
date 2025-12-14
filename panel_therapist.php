<?php
$uid = $_SESSION['user_id'];
$q_me = mysqli_query($conn, "SELECT * FROM users WHERE id='$uid'");
$me = mysqli_fetch_assoc($q_me);

// Cari ID Therapist di tabel therapists
$q_t = mysqli_query($conn, "SELECT * FROM therapists WHERE user_id='$uid'");
$therapist_data = mysqli_fetch_assoc($q_t);
$tid = $therapist_data['id'];

// Hitung Pendapatan (Saldo di tabel users sudah otomatis bertambah saat booking)
$balance = $me['balance'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Panel Psikolog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8FAFC; } </style>
</head>
<body class="bg-slate-50 text-slate-800">
    <nav class="bg-white border-b border-slate-200 px-8 h-20 flex items-center justify-between sticky top-0 z-50">
        <div class="flex items-center gap-2 font-bold text-xl text-teal-700">
            <i class="fa-solid fa-user-doctor"></i> MindCare Pro
        </div>
        <div class="flex items-center gap-6">
            <div class="text-right">
                <p class="font-bold text-sm text-slate-800"><?= $me['name'] ?></p>
                <p class="text-xs text-slate-500">Psikolog</p>
            </div>
            <a href="dashboard.php?logout=true" class="text-red-500 font-bold text-sm hover:underline">Keluar</a>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-8">
        <!-- Stats -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Total Pendapatan</p>
                <h3 class="text-3xl font-extrabold text-slate-900">Rp <?= number_format($balance) ?></h3>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Total Pasien</p>
                <?php $count_p = mysqli_num_rows(mysqli_query($conn, "SELECT DISTINCT patient_id FROM appointments WHERE therapist_id='$tid'")); ?>
                <h3 class="text-3xl font-extrabold text-slate-900"><?= $count_p ?> Org</h3>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Sesi Selesai</p>
                <?php $count_s = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM appointments WHERE therapist_id='$tid' AND status='completed'")); ?>
                <h3 class="text-3xl font-extrabold text-slate-900"><?= $count_s ?></h3>
            </div>
        </div>

        <!-- Jadwal -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-slate-800">Jadwal Konsultasi</h3>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                    <tr><th class="p-4">Waktu</th><th class="p-4">Pasien</th><th class="p-4">Link Meet</th><th class="p-4">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php 
                    $qa = mysqli_query($conn, "SELECT a.*, u.name as patient_name FROM appointments a JOIN users u ON a.patient_id=u.id WHERE a.therapist_id='$tid' ORDER BY a.date DESC");
                    if(mysqli_num_rows($qa)==0) echo "<tr><td colspan='4' class='p-8 text-center text-slate-400'>Belum ada jadwal.</td></tr>";
                    while($r=mysqli_fetch_assoc($qa)): 
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-4">
                            <p class="font-bold text-slate-700"><?= date('d M Y', strtotime($r['date'])) ?></p>
                            <p class="text-xs text-slate-400"><?= $r['time'] ?></p>
                        </td>
                        <td class="p-4 font-bold text-slate-800"><?= $r['patient_name'] ?></td>
                        <td class="p-4"><a href="<?= $r['meet_link'] ?>" target="_blank" class="text-blue-600 font-bold hover:underline">Buka Meet</a></td>
                        <td class="p-4"><span class="px-2 py-1 rounded text-xs font-bold uppercase bg-blue-100 text-blue-700"><?= $r['status'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>