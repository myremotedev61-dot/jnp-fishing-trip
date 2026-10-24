<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<!-- Baris Kartu Ringkasan Statistik -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card p-3 bg-white h-100 border-start border-primary border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">TRIP BULAN INI</div>
                    <div class="fs-3 fw-bold text-dark my-1"><?= $total_trip_bulan_ini ?></div>
                    <small class="text-success fw-semibold"><i class="fa-solid fa-calendar-check me-1"></i> Bulan <?= date('F Y') ?></small>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                    <i class="fa-solid fa-ship fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card stat-card p-3 bg-white h-100 border-start border-success border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">PESERTA TERKONFIRMASI</div>
                    <div class="fs-3 fw-bold text-dark my-1"><?= $total_peserta_konfirm ?></div>
                    <small class="text-muted"><i class="fa-solid fa-users me-1"></i> Total aktif terdaftar</small>
                </div>
                <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                    <i class="fa-solid fa-circle-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card stat-card p-3 bg-white h-100 border-start border-warning border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">MENUNGGU PERSETUJUAN</div>
                    <div class="fs-3 fw-bold text-warning my-1"><?= $antrean_peserta ?></div>
                    <a href="<?= base_url('admin/persetujuan') ?>" class="small text-decoration-none fw-semibold">
                        Tinjau sekarang <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                    <i class="fa-solid fa-user-clock fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card stat-card p-3 bg-white h-100 border-start border-danger border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">MENUNGGU VERIFIKASI BAYAR</div>
                    <div class="fs-3 fw-bold text-danger my-1"><?= $bayar_menunggu ?></div>
                    <a href="<?= base_url('admin/pembayaran') ?>" class="small text-danger text-decoration-none fw-semibold">
                        Verifikasi bukti <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3">
                    <i class="fa-solid fa-receipt fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Baris Finansial Kas Riil -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card stat-card p-3 bg-white border-0">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">TOTAL DANA DITERIMA & TERVERIFIKASI</div>
                    <div class="fs-3 fw-bold text-primary my-1">Rp <?= number_format($total_diterima, 0, ',', '.') ?></div>
                    <span class="badge bg-success-subtle text-success">Kas Masuk Riil</span>
                </div>
                <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
                    <i class="fa-solid fa-wallet fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card stat-card p-3 bg-white border-0">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">TOTAL SISA TAGIHAN AKTIF</div>
                    <div class="fs-3 fw-bold text-secondary my-1">Rp <?= number_format($total_sisa_tagihan, 0, ',', '.') ?></div>
                    <span class="badge bg-warning-subtle text-warning-emphasis">Piutang Pemancing</span>
                </div>
                <div class="rounded-circle bg-secondary bg-opacity-10 p-3 text-secondary">
                    <i class="fa-solid fa-clock-rotate-left fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Baris Tabel Trip Mendatang & Log Aktivitas -->
<div class="row g-3">
    <!-- Jadwal Trip Mendatang -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-compass me-2 text-primary"></i> Jadwal Trip Mendatang</h6>
                <a href="<?= base_url('admin/trips') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua Trip</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode Trip & Tujuan</th>
                            <th>Kapal</th>
                            <th>Tanggal & Jam</th>
                            <th>Kuota</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trip_mendatang)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada trip aktif mendatang.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($trip_mendatang as $tm): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= esc($tm['tujuan']) ?></div>
                                        <small class="text-muted"><code><?= esc($tm['kode_trip']) ?></code></small>
                                    </td>
                                    <td><?= esc($tm['nama_kapal']) ?></td>
                                    <td>
                                        <div><i class="fa-regular fa-calendar me-1 text-primary"></i> <?= date('d M Y', strtotime($tm['tanggal_trip'])) ?></div>
                                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i> <?= substr($tm['jam_berangkat'], 0, 5) ?> WITA</small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-primary"><?= $tm['terkonfirmasi'] ?></span> / <?= $tm['kuota'] ?>
                                        <div class="small text-muted">Sisa: <?= $tm['sisa_kuota'] ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success"><?= strtoupper($tm['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Aktivitas Terbaru (Approval Log) -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> Riwayat Aktivitas Admin</h6>
            </div>
            <div class="card-body p-3">
                <?php if (empty($log_aktivitas)): ?>
                    <p class="text-muted small text-center my-3">Belum ada riwayat aktivitas terbaru.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($log_aktivitas as $log): ?>
                            <div class="d-flex gap-3 mb-3 pb-2 border-bottom">
                                <div class="text-primary mt-1">
                                    <i class="fa-solid fa-circle-dot fs-6"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small fw-bold text-dark"><?= esc(ucwords(str_replace('_', ' ', $log['aksi']))) ?></div>
                                    <div class="text-muted small" style="font-size: 0.78rem;"><?= esc($log['alasan'] ?? 'Diproses oleh admin') ?></div>
                                    <div class="text-muted mt-1" style="font-size: 0.72rem;">
                                        <i class="fa-regular fa-clock me-1"></i> <?= date('d M, H:i', strtotime($log['created_at'])) ?> oleh <?= esc($log['nama_admin'] ?? 'Sistem') ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
