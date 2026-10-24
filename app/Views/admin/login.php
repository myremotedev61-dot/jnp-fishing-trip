<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator - Fishing Trip</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0A2540 0%, #0077B6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }
        .btn-ocean {
            background-color: #0077B6;
            color: #fff;
            font-weight: 600;
        }
        .btn-ocean:hover {
            background-color: #023E8A;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="container p-3">
    <div class="login-card mx-auto p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 64px; height: 64px;">
                <i class="fa-solid fa-ship fs-2"></i>
            </div>
            <h4 class="fw-bold mb-1">Fishing Trip</h4>
            <p class="text-muted small">Dashboard Administrator & Pemilik Usaha</p>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success py-2 px-3 small mb-3">
                <i class="fa-solid fa-circle-check me-1"></i> <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('admin/do-login') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label small fw-semibold">No. Telepon / Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-muted"></i></span>
                    <input type="text" name="login" class="form-control border-start-0" placeholder="081234567899" value="<?= old('login', '081234567899') ?>" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Kata Sandi</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" value="admin123" required>
                </div>
                <div class="form-text text-muted" style="font-size: 0.75rem;">
                    Default Admin: <code>081234567899</code> / <code>admin123</code>
                </div>
            </div>

            <button type="submit" class="btn btn-ocean w-100 py-2 mb-3">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk ke Dashboard
            </button>
        </form>

        <div class="text-center text-muted small mt-2">
            <a href="<?= base_url('/') ?>" class="text-decoration-none text-muted">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</div>
</body>
</html>
