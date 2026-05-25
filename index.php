<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Administrasi Digital Tim Admin UPTD Puskesmas Kuta Selatan</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #eff6ff 0%, #f1f5f9 50%, #f5f3ff 100%);
            --primary-color: #3b82f6;
            --success-color: #10b981;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }
        body {
            background: var(--bg-gradient);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: var(--text-dark);
            position: relative;
            overflow-x: hidden;
        }
        /* Ornamen Estetik di Background */
        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(59, 130, 246, 0.05);
            border-radius: 50%;
            top: -50px;
            left: -50px;
            z-index: -1;
        }
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(16, 185, 129, 0.04);
            border-radius: 50%;
            bottom: -100px;
            right: -100px;
            z-index: -1;
        }
        .main-wrapper {
            padding: 60px 0;
        }
        .header-brand {
            text-align: center;
            margin-bottom: 50px;
            padding: 0 15px;
        }
        .header-brand .badge-top {
            background-color: #ffffff;
            color: var(--primary-color);
            padding: 8px 16px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            border: 1px solid rgba(226, 232, 240, 0.8);
            display: inline-block;
            margin-bottom: 16px;
        }
        .header-brand h1 {
            font-weight: 800;
            font-size: 2.5rem;
            letter-spacing: -1px;
            color: #0f172a;
        }
        .header-brand p {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 1.1rem;
        }
        /* Card Layout Lebar (Split Horizontal Style) */
        .card-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .menu-link {
            text-decoration: none !important;
            display: block;
            margin-bottom: 24px;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 24px;
            padding: 32px;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.03);
        }
        .menu-link:hover .glass-card {
            transform: translateY(-4px) scale(1.01);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 20px 30px -10px rgba(15, 23, 42, 0.06);
            border-color: rgba(203, 213, 225, 0.5);
        }
        .icon-box {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            transition: all 0.3s;
        }
        .bg-icon-blue {
            background-color: #eff6ff;
            color: var(--primary-color);
        }
        .bg-icon-green {
            background-color: #ecfdf5;
            color: var(--success-color);
        }
        .menu-link:hover .bg-icon-blue {
            background-color: var(--primary-color);
            color: #ffffff;
        }
        .menu-link:hover .bg-icon-green {
            background-color: var(--success-color);
            color: #ffffff;
        }
        .menu-title {
            font-weight: 700;
            font-size: 1.35rem;
            color: #0f172a;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .menu-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0;
        }
        .badge-status-pill {
            padding: 6px 14px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .arrow-action {
            font-size: 1.5rem;
            color: #cbd5e1;
            transition: transform 0.3s, color 0.3s;
        }
        .menu-link:hover .arrow-action {
            color: var(--text-dark);
            transform: translateX(6px);
        }
        .footer {
            padding: 24px 0;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 500;
            border-top: 1px solid rgba(226, 232, 240, 0.5);
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(4px);
        }

        /* ==================== MEDIA QUERIES OPTIMASI MOBILE ==================== */
        @media (max-width: 767.98px) {
            .main-wrapper {
                padding: 40px 0;
            }
            .header-brand {
                margin-bottom: 35px;
            }
            .header-brand h1 {
                font-size: 1.85rem; /* Mengecilkan judul di HP agar pas */
            }
            .header-brand p {
                font-size: 0.95rem;
            }
            .glass-card {
                padding: 24px; /* Mempersempit padding dalam agar ruang teks luas */
                text-align: center; /* Membuat teks rata tengah di mobile agar estetik */
            }
            .icon-box {
                margin: 0 auto 15px auto; /* Memindahkan kotak ikon ke tengah atas */
            }
            .menu-title {
                font-size: 1.2rem;
                justify-content: center;
                flex-direction: column; /* Badge status ditaruh di bawah teks judul */
                gap: 6px;
            }
            .menu-desc {
                font-size: 0.88rem;
            }
            .card-container {
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>

    <div class="container main-wrapper">
        <div class="header-brand">
            <div class="badge-top">Internal Digital Workspace - By Pebriawan</div>
            <h1>Sistem Administrasi Internal Tim Admin</h1>
            <p>UPTD Puskesmas Kuta Selatan</p>
        </div>

        <div class="card-container">
            
            <a href="notulen.php" class="menu-link">
                <div class="glass-card">
                    <div class="row align-items-center g-4">
                        <div class="col-12 col-md-auto">
                            <div class="icon-box bg-icon-blue">
                                <i class="bi bi-file-earmark-text-fill"></i>
                            </div>
                        </div>
                        <div class="col-12 col-md">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1 justify-content-md-start justify-content-center">
                                <h3 class="menu-title m-0">Notulen Rapat & Pelatihan</h3>
                                <span class="badge bg-primary-subtle text-primary badge-status-pill">Akses Pegawai & Admin</span>
                            </div>
                            <p class="menu-desc">Dokumentasikan agenda rapat dinas, pembuatan laporan berkala, serta rekapitulasi usulan pendidikan & pelatihan staf.</p>
                        </div>
                        <div class="col-auto d-none d-md-block text-end ps-3">
                            <i class="bi bi-arrow-right arrow-action"></i>
                        </div>
                    </div>
                </div>
            </a>

            <a href="cuti.php" class="menu-link">
                <div class="glass-card">
                    <div class="row align-items-center g-4">
                        <div class="col-12 col-md-auto">
                            <div class="icon-box bg-icon-green">
                                <i class="bi bi-calendar2-check-fill"></i>
                            </div>
                        </div>
                        <div class="col-12 col-md">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1 justify-content-md-start justify-content-center">
                                <h3 class="menu-title m-0">Pengajuan Layanan Cuti</h3>
                                <span class="badge bg-success-subtle text-success badge-status-pill">Akses Pegawai & Admin</span>
                            </div>
                            <p class="menu-desc">Formulir pengajuan cuti aparatur terintegrasi, kalkulasi sisa kuota tahunan, pencetakan berkas fisik, dan upload dokumen persetujuan.</p>
                        </div>
                        <div class="col-auto d-none d-md-block text-end ps-3">
                            <i class="bi bi-arrow-right arrow-action"></i>
                        </div>
                    </div>
                </div>
            </a>

        </div>
    </div>

    <div class="footer">
        <div class="container">
            <span>UPTD Puskesmas Kuta Selatan &copy; 2025 - 2026 • Pebriawan all right reserved </span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>