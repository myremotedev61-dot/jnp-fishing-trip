<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-1">Pengelolaan Pembatalan & Restitusi Dana (Refund)</h5>
        <p class="text-muted small mb-0">Catat pembatalan peserta secara transparan dan proses pengembalian dana sesuai kebijakan usaha.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Tabel Peserta Dibatalkan -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-danger"><i class="fa-solid fa-user-xmark me-2"></i> Daftar Peserta Berstatus Dibatalkan</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">No.</th>
                            <th>Nama Pemancing</th>
                            <th>Trip & Tanggal</th>
                            <th>Total Dana Terverifikasi</th>
                            <th>Sisa Tagihan</th>
                            <th>Alasan Pembatalan</th>
                            <th class="text-end">Aksi Refund</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($peserta_batal)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Belum ada peserta yang dibatalkan.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($peserta_batal as $pb): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= esc($pb['nama_pemancing']) ?></div>
                                        <small class="text-muted"><code><?= esc($pb['kode_pendaftaran']) ?></code></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= esc($pb['tujuan']) ?></div>
                                        <small class="text-muted"><?= date('d M Y', strtotime($pb['tanggal_trip'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">Rp <?= number_format($pb['keuangan']['total_terverifikasi'], 0, ',', '.') ?></span>
                                    </td>
                                    <td>Rp <?= number_format($pb['keuangan']['sisa_tagihan'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="text-muted small"><?= esc($pb['alasan_penolakan'] ?? 'Atas permintaan peserta') ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($pb['keuangan']['total_terverifikasi'] > 0 && $pb['keuangan']['total_refund'] < $pb['keuangan']['total_terverifikasi']): ?>
                                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalProsesRefund" onclick="setupRefund(<?= $pb['id'] ?>, '<?= esc($pb['nama_pemancing']) ?>', <?= $pb['keuangan']['pembayaran_bersih'] ?>)">
                                                <i class="fa-solid fa-hand-holding-dollar me-1"></i> Proses Refund
                                            </button>
                                        <?php elseif ($pb['keuangan']['total_refund'] >= $pb['keuangan']['total_terverifikasi'] && $pb['keuangan']['total_terverifikasi'] > 0): ?>
                                            <span class="badge bg-success-subtle text-success">Telah Direfund</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary">Tidak Ada Dana Masuk</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Riwayat Pengembalian Dana (Refund Log) -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> Riwayat Transaksi Pengembalian Dana (Refund)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">No.</th>
                            <th>Tanggal Refund</th>
                            <th>Peserta Penerima</th>
                            <th>Nominal Refund</th>
                            <th>Alasan / Kebijakan</th>
                            <th>Diproses Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($refund_list)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat transaksi refund.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($refund_list as $rf): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= date('d M Y, H:i', strtotime($rf['tanggal_refund'])) ?> WITA</td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= esc($rf['nama_pemancing']) ?></div>
                                        <small class="text-muted">Ref Bayar: <code><?= esc($rf['kode_pembayaran']) ?></code></small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-danger">Rp <?= number_format($rf['nominal_refund'], 0, ',', '.') ?></span>
                                    </td>
                                    <td><?= esc($rf['alasan_refund']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= esc($rf['nama_admin'] ?? 'Admin') ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Input Refund -->
<div class="modal fade" id="modalProsesRefund" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('admin/pembatalan/refund') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="peserta_trip_id" id="refund_peserta_id">
                <input type="hidden" name="pembayaran_id" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-warning-emphasis"><i class="fa-solid fa-hand-holding-dollar me-1"></i> Proses Pengembalian Dana</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Peserta</label>
                        <input type="text" id="refund_nama_peserta" class="form-control" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Maksimal Dana Dapat Dikembalikan (Rp)</label>
                        <input type="text" id="refund_max_nominal" class="form-control fw-bold text-success" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nominal Yang Dikembalikan (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="nominal_refund" id="refund_nominal_input" class="form-control" min="1000" step="1000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alasan / Dasar Kebijakan Refund <span class="text-danger">*</span></label>
                        <textarea name="alasan_refund" class="form-control" rows="3" placeholder="Sesuai kebijakan pembatalan H-3 panjar dikembalikan 100% via transfer bank..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="fa-solid fa-check me-1"></i> Simpan Transaksi Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function setupRefund(pesertaId, nama, maxDana) {
    document.getElementById('refund_peserta_id').value = pesertaId;
    document.getElementById('refund_nama_peserta').value = nama;
    document.getElementById('refund_max_nominal').value = "Rp " + new Intl.NumberFormat('id-ID').format(maxDana);
    document.getElementById('refund_nominal_input').value = maxDana;
    document.getElementById('refund_nominal_input').max = maxDana;
}
</script>
<?= $this->endSection() ?>
