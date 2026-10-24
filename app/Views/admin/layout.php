<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Dashboard Administrator') ?> - Fishing Trip</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-navy: #0A2540;
            --ocean-blue: #0077B6;
            --tosca-teal: #009688;
            --sand-gold: #F4A261;
            --bg-light: #F4F7FB;
            --card-shadow: 0 4px 15px rgba(10, 37, 64, 0.05);
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-light);
            color: #2D3748;
        }
        .sidebar {
            background-color: var(--primary-navy);
            min-height: 100vh;
            color: #fff;
            transition: all 0.3s;
        }
        .sidebar .brand-title {
            font-weight: 800;
            font-size: 1.25rem;
            color: #fff;
            letter-spacing: -0.5px;
        }
        .sidebar .nav-link {
            color: #CBD5E0;
            padding: 11px 18px;
            font-size: 0.92rem;
            font-weight: 500;
            border-radius: 8px;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease-in-out;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.12);
        }
        .sidebar .nav-link.active {
            background: linear-gradient(90deg, var(--ocean-blue), #009688);
            box-shadow: 0 4px 12px rgba(0, 119, 182, 0.3);
        }
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .table-custom {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        .table-custom thead th {
            background-color: #EDF2F7;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4A5568;
            border: none;
            padding: 12px 16px;
        }
        .table-custom tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            font-size: 0.92rem;
            border-bottom: 1px solid #EDF2F7;
        }
        .badge-status {
            font-weight: 600;
            font-size: 0.78rem;
            padding: 6px 10px;
            border-radius: 20px;
        }
        .top-navbar {
            background: #fff;
            border-bottom: 1px solid #E2E8F0;
            padding: 14px 24px;
        }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar Navigation -->
        <div class="col-lg-2 col-md-3 sidebar p-3 d-none d-md-block">
            <div class="d-flex align-items-center gap-2 mb-4 px-2 pt-2">
                <i class="fa-solid fa-ship fs-4 text-warning"></i>
                <div>
                    <div class="brand-title">Fishing Trip</div>
                    <small class="text-white-50" style="font-size: 0.72rem;">Turatea Operator Admin</small>
                </div>
            </div>
            
            <ul class="nav flex-column mb-auto">
                <li class="nav-item">
                    <a href="<?= base_url('admin/dashboard') ?>" class="nav-link <?= ($active_menu ?? '') === 'dashboard' ? 'active' : '' ?>">
                        <i class="fa-solid fa-chart-pie"></i> Dashboard Utama
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/persetujuan') ?>" class="nav-link <?= ($active_menu ?? '') === 'persetujuan' ? 'active' : '' ?>">
                        <i class="fa-solid fa-user-check"></i> Antrean Persetujuan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/pembayaran') ?>" class="nav-link <?= ($active_menu ?? '') === 'pembayaran' ? 'active' : '' ?>">
                        <i class="fa-solid fa-money-check-dollar"></i> Verifikasi Bayar
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/peserta') ?>" class="nav-link <?= ($active_menu ?? '') === 'peserta' ? 'active' : '' ?>">
                        <i class="fa-solid fa-users"></i> Tabel Data Peserta
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/trips') ?>" class="nav-link <?= ($active_menu ?? '') === 'trips' ? 'active' : '' ?>">
                        <i class="fa-solid fa-calendar-days"></i> Jadwal Trip
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/kapal') ?>" class="nav-link <?= ($active_menu ?? '') === 'kapal' ? 'active' : '' ?>">
                        <i class="fa-solid fa-sailboat"></i> Armada Kapal
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/pembatalan') ?>" class="nav-link <?= ($active_menu ?? '') === 'pembatalan' ? 'active' : '' ?>">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Pembatalan & Refund
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/laporan') ?>" class="nav-link <?= ($active_menu ?? '') === 'laporan' ? 'active' : '' ?>">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Laporan Pendapatan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/pengaturan') ?>" class="nav-link <?= ($active_menu ?? '') === 'pengaturan' ? 'active' : '' ?>">
                        <i class="fa-solid fa-gear"></i> Pengaturan Usaha
                    </a>
                </li>
            </ul>

            <hr class="border-secondary my-4">
            
            <div class="px-2">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div>
                        <div class="text-white small fw-bold"><?= esc(session()->get('admin_nama') ?? 'Administrator') ?></div>
                        <div class="text-white-50" style="font-size: 0.72rem;"><?= esc(session()->get('admin_role') ?? 'Admin') ?></div>
                    </div>
                </div>
                <a href="<?= base_url('admin/logout') ?>" class="btn btn-sm btn-outline-danger w-100 mt-2">
                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-lg-10 col-md-9 min-vh-100 d-flex flex-column">
            <!-- Top Navbar for Mobile & Header -->
            <nav class="top-navbar d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h5 class="mb-0 fw-bold text-dark"><?= esc($title ?? 'Dashboard') ?></h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-secondary border px-3 py-2">
                        <i class="fa-regular fa-clock me-1 text-primary"></i> <?= date('d M Y, H:i') ?> WITA
                    </span>
                    <a href="<?= base_url('admin/persetujuan') ?>" class="btn btn-sm btn-primary position-relative">
                        <i class="fa-solid fa-bell"></i>
                    </a>
                </div>
            </nav>

            <!-- Alerts Container -->
            <div class="p-4 flex-grow-1">
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i> <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- View Injection Slot -->
                <?= $this->renderSection('content') ?>
            </div>

            <!-- Footer -->
            <footer class="bg-white border-top py-3 px-4 text-center text-muted small">
                &copy; <?= date('Y') ?> <strong>Fishing Trip Turatea</strong>. Seluruh hak cipta dilindungi. Sistem Manajemen Kapal Pemancingan.
            </footer>
        </div>
    </div>
</div>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-header border-bottom border-secondary">
        <h5 class="offcanvas-title text-warning fw-bold"><i class="fa-solid fa-ship me-2"></i> Fishing Trip</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3">
        <ul class="nav flex-column">
            <li class="nav-item mb-2"><a href="<?= base_url('admin/dashboard') ?>" class="nav-link text-white"><i class="fa-solid fa-chart-pie me-2"></i> Dashboard</a></li>
            <li class="nav-item mb-2"><a href="<?= base_url('admin/persetujuan') ?>" class="nav-link text-white"><i class="fa-solid fa-user-check me-2"></i> Antrean Persetujuan</a></li>
            <li class="nav-item mb-2"><a href="<?= base_url('admin/pembayaran') ?>" class="nav-link text-white"><i class="fa-solid fa-money-check-dollar me-2"></i> Verifikasi Pembayaran</a></li>
            <li class="nav-item mb-2"><a href="<?= base_url('admin/peserta') ?>" class="nav-link text-white"><i class="fa-solid fa-users me-2"></i> Tabel Data Peserta</a></li>
            <li class="nav-item mb-2"><a href="<?= base_url('admin/trips') ?>" class="nav-link text-white"><i class="fa-solid fa-calendar-days me-2"></i> Jadwal Trip</a></li>
            <li class="nav-item mb-2"><a href="<?= base_url('admin/kapal') ?>" class="nav-link text-white"><i class="fa-solid fa-sailboat me-2"></i> Armada Kapal</a></li>
            <li class="nav-item mb-2"><a href="<?= base_url('admin/pembatalan') ?>" class="nav-link text-white"><i class="fa-solid fa-arrow-rotate-left me-2"></i> Pembatalan & Refund</a></li>
            <li class="nav-item mb-2"><a href="<?= base_url('admin/laporan') ?>" class="nav-link text-white"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Laporan Pendapatan</a></li>
            <li class="nav-item mb-2"><a href="<?= base_url('admin/pengaturan') ?>" class="nav-link text-white"><i class="fa-solid fa-gear me-2"></i> Pengaturan Usaha</a></li>
            <li class="nav-item mt-3 pt-3 border-top"><a href="<?= base_url('admin/logout') ?>" class="nav-link text-danger"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Logout</a></li>
        </ul>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
