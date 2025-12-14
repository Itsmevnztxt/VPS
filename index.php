<?php 
// Cek koneksi database (opsional)
$db_file = 'koneksi.php';
if (file_exists($db_file)) { include $db_file; }
$is_logged_in = isset($_SESSION['user_id']); 
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCare - Kesehatan Mental Masa Depan</title>
    
    <!-- Frameworks & Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Chart JS for Realtime Graph -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style> 
        body { font-family: 'Plus Jakarta Sans', sans-serif; overflow-x: hidden; } 
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #0d9488; border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: #0f766e; }

        /* Glassmorphism */
        .glass-nav { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.3); }
        .glass-card { background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.5); }
        
        /* Animations */
        .hero-blob { position: absolute; filter: blur(80px); z-index: -1; opacity: 0.6; animation: floatBlob 8s infinite alternate cubic-bezier(0.4, 0, 0.2, 1); }
        @keyframes floatBlob { 0% { transform: translate(0,0) scale(1); } 100% { transform: translate(20px, -20px) scale(1.1); } }
        
        .float-img { animation: floatImg 6s ease-in-out infinite; }
        @keyframes floatImg { 0% { transform: translateY(0px); } 50% { transform: translateY(-20px); } 100% { transform: translateY(0px); } }

        .quote-card:hover { transform: translateY(-5px) rotate(1deg); }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-teal-200 selection:text-teal-900">

    <!-- NAVBAR -->
    <nav class="fixed w-full z-50 glass-nav transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
            <a href="#" class="flex items-center gap-2 group">
                <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-teal-700 rounded-xl flex items-center justify-center text-white shadow-lg shadow-teal-500/30 group-hover:rotate-12 transition-transform duration-300">
                    <i class="fa-solid fa-brain text-lg"></i>
                </div>
                <span class="text-2xl font-extrabold text-slate-900 tracking-tight">MindCare<span class="text-teal-500">.</span></span>
            </a>
            
            <div class="hidden md:flex gap-8 font-medium text-slate-500 text-sm">
                <a href="#home" class="hover:text-teal-600 transition">Beranda</a>
                <a href="#live-data" class="hover:text-teal-600 transition">Data Live</a>
                <a href="#features" class="hover:text-teal-600 transition">Layanan</a>
                <a href="#quotes" class="hover:text-teal-600 transition">Motivasi</a>
                <a href="#contact" class="hover:text-teal-600 transition">Kontak</a>
            </div>
            
            <div>
                <?php if($is_logged_in): ?>
                    <a href="dashboard.php" class="bg-teal-600 text-white px-6 py-2.5 rounded-full font-bold text-sm shadow-lg shadow-teal-500/30 hover:bg-teal-700 hover:scale-105 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-gauge"></i> Dashboard
                    </a>
                <?php else: ?>
                    <div class="flex gap-3">
                        <a href="auth.php" class="px-5 py-2.5 font-bold text-slate-600 hover:text-teal-600 text-sm transition border border-transparent hover:border-teal-100 hover:bg-teal-50 rounded-full">Masuk</a>
                        <a href="auth.php" class="bg-slate-900 text-white px-6 py-2.5 rounded-full font-bold text-sm hover:bg-slate-800 hover:shadow-xl transition-all">Daftar</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section id="home" class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <div class="hero-blob bg-teal-300 w-[500px] h-[500px] rounded-full top-[-100px] left-[-100px]"></div>
        <div class="hero-blob bg-blue-300 w-[400px] h-[400px] rounded-full bottom-0 right-[-50px] animation-delay-2000"></div>
        
        <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-16 items-center relative z-10">
            <div data-aos="fade-right" data-aos-duration="1000">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/80 border border-teal-100 text-teal-700 text-xs font-bold uppercase tracking-wider mb-8 shadow-sm backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-teal-500 animate-ping"></span> 
                    <span class="relative">AI & Psikolog Terintegrasi</span>
                </div>
                
                <h1 class="text-5xl lg:text-7xl font-extrabold text-slate-900 leading-[1.1] mb-6 tracking-tight">
                    Pulihkan Pikiran,<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-600 to-blue-600">Temukan Harapan.</span>
                </h1>
                
                <p class="text-lg text-slate-600 mb-10 leading-relaxed max-w-lg">
                    Platform kesehatan mental #1 di Indonesia yang menggabungkan kecerdasan buatan dan sentuhan manusia.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="auth.php" class="group px-8 py-4 bg-teal-600 text-white rounded-full font-bold text-lg shadow-xl shadow-teal-500/30 hover:bg-teal-700 transition-all hover:-translate-y-1 flex items-center justify-center gap-2">
                        Mulai Konseling <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <a href="#features" class="px-8 py-4 bg-white text-slate-700 border border-slate-200 rounded-full font-bold text-lg hover:border-teal-500 hover:text-teal-600 hover:shadow-lg transition-all text-center flex items-center justify-center gap-2">
                        <i class="fa-regular fa-circle-play"></i> Pelajari Fitur
                    </a>
                </div>

                <div class="mt-12 flex items-center gap-8 border-t border-slate-200 pt-8">
                    <div class="flex -space-x-4">
                        <img class="w-10 h-10 rounded-full border-2 border-white" src="https://i.pravatar.cc/100?img=1" alt="">
                        <img class="w-10 h-10 rounded-full border-2 border-white" src="https://i.pravatar.cc/100?img=2" alt="">
                        <img class="w-10 h-10 rounded-full border-2 border-white" src="https://i.pravatar.cc/100?img=3" alt="">
                        <div class="w-10 h-10 rounded-full border-2 border-white bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600">+2k</div>
                    </div>
                    <div>
                        <div class="flex text-yellow-400 text-sm mb-1">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-700">Dipercaya 12.000+ Pasien</p>
                    </div>
                </div>
            </div>
            
            <div class="relative hidden lg:block" data-aos="fade-left" data-aos-duration="1200">
                <div class="absolute inset-0 bg-gradient-to-tr from-teal-100 to-blue-50 rounded-full blur-3xl opacity-50 animate-pulse"></div>
                <div class="relative z-10 float-img">
                    <img src="https://img.freepik.com/free-vector/mental-health-awareness-concept-illustration_114360-2022.jpg?w=800" class="w-full drop-shadow-2xl rounded-[3rem] border-8 border-white/50" alt="Mental Health Illustration">
                    
                    <!-- Floating Stat Card -->
                    <div class="absolute -left-8 top-20 bg-white/90 backdrop-blur p-4 rounded-2xl shadow-xl border border-white flex items-center gap-3 animate-bounce" style="animation-duration: 3s;">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600"><i class="fa-solid fa-check"></i></div>
                        <div>
                            <p class="text-xs text-slate-500 font-bold">Status Mood</p>
                            <p class="text-sm font-extrabold text-slate-800">Meningkat 85%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LIVE DATA SECTION -->
    <section id="live-data" class="py-20 bg-slate-900 text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="grid lg:grid-cols-3 gap-12 items-center">
                <div data-aos="fade-right">
                    <div class="inline-block px-3 py-1 bg-teal-500/20 text-teal-300 rounded-full text-xs font-bold uppercase tracking-wider mb-4 border border-teal-500/30">
                        <i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Real-time Monitoring
                    </div>
                    <h2 class="text-3xl lg:text-4xl font-extrabold mb-4">Indeks Kebahagiaan Komunitas</h2>
                    <p class="text-slate-400 mb-8 leading-relaxed">
                        Kami memantau tren mood pengguna secara anonim untuk memahami pola kesehatan mental global. Data ini membantu kami meningkatkan layanan.
                    </p>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="bg-slate-800 p-4 rounded-xl border border-slate-700">
                            <h4 class="text-2xl font-bold text-teal-400">4,821</h4>
                            <p class="text-xs text-slate-500">Sesi Chat Hari Ini</p>
                        </div>
                        <div class="bg-slate-800 p-4 rounded-xl border border-slate-700">
                            <h4 class="text-2xl font-bold text-blue-400">98%</h4>
                            <p class="text-xs text-slate-500">Kepuasan Pengguna</p>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-slate-800/50 p-6 rounded-3xl border border-slate-700 backdrop-blur-sm shadow-2xl" data-aos="zoom-in">
                    <canvas id="mentalHealthChart" class="w-full h-[300px]"></canvas>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section id="features" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-20" data-aos="fade-up">
                <h2 class="text-3xl lg:text-4xl font-extrabold text-slate-900 mb-4">Layanan Komprehensif</h2>
                <p class="text-slate-500 max-w-2xl mx-auto">Kami menyediakan ekosistem lengkap untuk mendukung perjalanan kesehatan mental Anda, dari AI hingga profesional.</p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="group p-8 rounded-[2rem] bg-slate-50 border border-slate-100 hover:bg-white hover:shadow-2xl hover:shadow-teal-500/10 transition-all duration-300" data-aos="fade-up" data-aos-delay="0">
                    <div class="w-14 h-14 bg-teal-100 text-teal-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">AI Mira Chatbot</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Teman cerita 24/7 yang siap mendengarkan tanpa menghakimi, didukung teknologi AI canggih.</p>
                </div>

                <!-- Feature 2 -->
                <div class="group p-8 rounded-[2rem] bg-slate-50 border border-slate-100 hover:bg-white hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-300" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-user-doctor"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Konseling Ahli</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Sesi tatap muka online dengan psikolog klinis berlisensi untuk penanganan mendalam.</p>
                </div>

                <!-- Feature 3 -->
                <div class="group p-8 rounded-[2rem] bg-slate-50 border border-slate-100 hover:bg-white hover:shadow-2xl hover:shadow-purple-500/10 transition-all duration-300" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Komunitas Anonim</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Ruang aman untuk berbagi cerita dengan sesama pejuang kesehatan mental tanpa identitas.</p>
                </div>

                <!-- Feature 4 -->
                <div class="group p-8 rounded-[2rem] bg-slate-50 border border-slate-100 hover:bg-white hover:shadow-2xl hover:shadow-orange-500/10 transition-all duration-300" data-aos="fade-up" data-aos-delay="300">
                    <div class="w-14 h-14 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Mood Tracker</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Pantau perkembangan emosi harianmu dengan grafik intuitif dan jurnal digital.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- MOTIVATIONAL QUOTES (DAILY ZEN) -->
    <section id="quotes" class="py-24 bg-gradient-to-br from-teal-50 to-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-6" data-aos="fade-up">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900">Daily Zen</h2>
                    <p class="text-slate-500 mt-2">Kutipan penyemangat untuk harimu yang lebih baik.</p>
                </div>
                <button onclick="changeQuote()" class="px-6 py-2 bg-white border border-slate-200 text-slate-600 rounded-full text-sm font-bold hover:bg-teal-50 hover:text-teal-600 transition shadow-sm">
                    <i class="fa-solid fa-rotate mr-2"></i> Ganti Kutipan
                </button>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Quote 1 -->
                <div class="quote-card p-8 bg-white rounded-3xl border border-slate-100 shadow-xl shadow-teal-900/5 transition-all duration-500" data-aos="fade-up" data-aos-delay="0">
                    <i class="fa-solid fa-quote-left text-4xl text-teal-100 mb-4"></i>
                    <p class="text-lg font-medium text-slate-700 italic mb-6 leading-relaxed">"Tidak apa-apa untuk beristirahat. Bintang-bintang pun butuh kegelapan untuk bersinar."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center text-teal-600 font-bold text-xs">A</div>
                        <span class="text-sm font-bold text-slate-400">Anonim</span>
                    </div>
                </div>
                
                <!-- Quote 2 -->
                <div class="quote-card p-8 bg-slate-900 rounded-3xl shadow-xl shadow-slate-900/20 text-white transform md:-translate-y-4 transition-all duration-500" data-aos="fade-up" data-aos-delay="100">
                    <i class="fa-solid fa-quote-left text-4xl text-slate-700 mb-4"></i>
                    <p class="text-lg font-medium italic mb-6 leading-relaxed" id="mainQuote">"Kesehatan mentalmu adalah prioritas. Kebahagiaanmu adalah penting. Keberadaanmu berharga."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-teal-500 flex items-center justify-center text-white font-bold text-xs">M</div>
                        <span class="text-sm font-bold text-slate-400">MindCare</span>
                    </div>
                </div>

                <!-- Quote 3 -->
                <div class="quote-card p-8 bg-white rounded-3xl border border-slate-100 shadow-xl shadow-teal-900/5 transition-all duration-500" data-aos="fade-up" data-aos-delay="200">
                    <i class="fa-solid fa-quote-left text-4xl text-teal-100 mb-4"></i>
                    <p class="text-lg font-medium text-slate-700 italic mb-6 leading-relaxed">"Satu langkah kecil setiap hari lebih baik daripada tidak melangkah sama sekali."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs">R</div>
                        <span class="text-sm font-bold text-slate-400">Rumi</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTACT ADMIN -->
    <section id="contact" class="py-24 bg-white relative">
        <div class="max-w-5xl mx-auto px-6">
            <div class="glass-card rounded-[3rem] p-8 md:p-12 shadow-2xl border border-slate-100 relative overflow-hidden" data-aos="zoom-in">
                <!-- Decorative BG -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-teal-50 rounded-full blur-3xl -z-10 opacity-50"></div>
                
                <div class="grid md:grid-cols-2 gap-12">
                    <div>
                        <h2 class="text-3xl font-extrabold text-slate-900 mb-4">Hubungi Kami</h2>
                        <p class="text-slate-500 mb-8">Punya pertanyaan atau butuh bantuan segera? Tim MindCare siap membantu Anda 24/7.</p>
                        
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-teal-50 flex items-center justify-center text-teal-600"><i class="fa-solid fa-envelope"></i></div>
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase">Email</p>
                                    <p class="font-bold text-slate-800">contact@mindcare.com</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-teal-50 flex items-center justify-center text-teal-600"><i class="fa-brands fa-whatsapp"></i></div>
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase">WhatsApp</p>
                                    <p class="font-bold text-slate-800">+62 858-1037-9509</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <form onsubmit="event.preventDefault(); alert('Pesan terkirim! Admin akan segera membalas.');" class="space-y-4">
                        <input type="text" placeholder="Nama Lengkap" class="w-full px-6 py-4 bg-slate-50 rounded-xl border border-slate-200 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition" required>
                        <input type="email" placeholder="Alamat Email" class="w-full px-6 py-4 bg-slate-50 rounded-xl border border-slate-200 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition" required>
                        <textarea rows="3" placeholder="Tulis pesanmu..." class="w-full px-6 py-4 bg-slate-50 rounded-xl border border-slate-200 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition resize-none" required></textarea>
                        <button class="w-full py-4 bg-slate-900 text-white font-bold rounded-xl hover:bg-teal-600 transition shadow-lg">Kirim Pesan</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-slate-900 text-slate-400 py-12 text-center border-t border-slate-800 text-sm">
        <div class="max-w-4xl mx-auto px-6">
            <div class="flex items-center justify-center gap-2 mb-6 text-white text-2xl font-bold">
                <i class="fa-solid fa-brain text-teal-500"></i> MindCare
            </div>
            <p class="mb-8 leading-relaxed max-w-sm mx-auto">Platform kesehatan mental inklusif untuk masa depan Indonesia yang lebih bahagia.</p>
            <div class="flex justify-center gap-6 mb-8 text-lg">
                <a href="#" class="hover:text-teal-400 transition"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" class="hover:text-teal-400 transition"><i class="fa-brands fa-twitter"></i></a>
                <a href="#" class="hover:text-teal-400 transition"><i class="fa-brands fa-linkedin"></i></a>
            </div>
            &copy; 2025 MindCare. All rights reserved.
        </div>
    </footer>
    
    <!-- SCRIPTS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script> 
        // 1. Initialize AOS (Animations)
        AOS.init({
            once: true,
            duration: 800,
            offset: 100
        }); 

        // 2. Initialize Chart.js (Live Data Graph)
        const ctx = document.getElementById('mentalHealthChart').getContext('2d');
        const mentalChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00'],
                datasets: [{
                    label: 'Tingkat Kebahagiaan Rata-rata',
                    data: [65, 70, 68, 75, 72, 80, 85],
                    borderColor: '#14b8a6', // Teal 500
                    backgroundColor: 'rgba(20, 184, 166, 0.1)',
                    borderWidth: 3,
                    tension: 0.4, // Smooth curves
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#14b8a6',
                    pointRadius: 5,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 50,
                        max: 100,
                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                        ticks: { color: '#94a3b8' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8' }
                    }
                }
            }
        });

        // Simulasi Update Data Real-time
        setInterval(() => {
            const newData = Math.floor(Math.random() * (90 - 70 + 1)) + 70;
            const newTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

            // Geser data
            mentalChart.data.labels.shift();
            mentalChart.data.labels.push(newTime);
            mentalChart.data.datasets[0].data.shift();
            mentalChart.data.datasets[0].data.push(newData);
            
            mentalChart.update();
        }, 3000); // Update setiap 3 detik

        // 3. Script untuk Ganti Quote
        const quotes = [
            "Kesehatan mentalmu adalah prioritas. Kebahagiaanmu adalah penting.",
            "Tarik napas dalam-dalam. Ini hanya hari yang buruk, bukan hidup yang buruk.",
            "Kamu lebih kuat dari yang kamu tahu. Lebih mampu dari yang kamu kira.",
            "Setiap langkah kecil menuju penyembuhan adalah kemenangan.",
            "Jangan menyerah pada dirimu sendiri, kamu sedang berproses."
        ];
        
        function changeQuote() {
            const quoteEl = document.getElementById('mainQuote');
            // Fade out
            quoteEl.style.opacity = 0;
            setTimeout(() => {
                const randomQuote = quotes[Math.floor(Math.random() * quotes.length)];
                quoteEl.innerText = `"${randomQuote}"`;
                // Fade in
                quoteEl.style.opacity = 1;
            }, 300);
        }
    </script>
</body>
</html>