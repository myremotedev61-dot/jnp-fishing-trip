<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-1">Verifikasi Pembayaran & Bukti Transfer</h5>
        <p class="text-muted small mb-0">Periksa bukti setoran panjar atau pelunasan peserta sebelum mengonfirmasi kas masuk riil.</p>
    </div>
</div>

<!-- Nav Tabs Filter Status -->
<ul class="nav nav-pills mb-4 gap-2">
    <li class="nav-item">
        <a class="nav-link <?= $filter_status === 'menunggu_verifikasi' ? 'active bg-primary' : 'bg-white text-secondary' ?>" href="<?= base_url('admin/pembayaran?status=menunggu_verifikasi') ?>">
            <i class="fa-solid fa-clock me-1"></i> Menunggu Verifikasi
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filter_status === 'terverifikasi' ? 'active bg-success' : 'bg-white text-secondary' ?>" href="<?= base_url('admin/pembayaran?status=terverifikasi') ?>">
            <i class="fa-solid fa-circle-check me-1"></i> Terverifikasi
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filter_status === 'ditolak' ? 'active bg-danger' : 'bg-white text-secondary' ?>" href="<?= base_url('admin/pembayaran?status=ditolak') ?>">
            <i class="fa-solid fa-circle-xmark me-1"></i> Ditolak
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filter_status === 'semua' ? 'active bg-secondary' : 'bg-white text-secondary' ?>" href="<?= base_url('admin/pembayaran?status=semua') ?>">
            <i class="fa-solid fa-list me-1"></i> Semua Transaksi
        </a>
    </li>
</ul>

<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">No.</th>
                    <th>Kode & Waktu Transaksi</th>
                    <th>Peserta & Trip</th>
                    <th>Nominal Disetor</th>
                    <th>Metode & Bukti Transfer</th>
                    <th>Tagihan & Sisa Kas</th>
                    <th>Status</th>
                    <th class="text-end">Tindakan Admin</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pembayaran_list)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">Tidak ada transaksi pembayaran pada kategori ini.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($pembayaran_list as $bayar): ?>
                        <tr>
                            <td class="fw-semibold text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><code><?= esc($bayar['kode_pembayaran']) ?></code></div>
                                <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i> <?= date('d M Y', strtotime($bayar['tanggal_transaksi'])) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= esc($bayar['nama_pemancing']) ?></div>
                                <small class="text-muted"><?= esc($bayar['tujuan']) ?></small>
                            </td>
                            <td>
                                <div class="fs-6 fw-bold text-primary">Rp <?= number_format($bayar['nominal'], 0, ',', '.') ?></div>
                                <?php if (!empty($bayar['nomor_referensi'])): ?>
                                    <small class="text-muted">Ref: <?= esc($bayar['nomor_referensi']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-secondary mb-1"><?= esc($bayar['metode_pembayaran']) ?></div>
                                <button type="button" class="btn btn-sm btn-outline-info py-0 px-2" onclick="previewBukti('<?= base_url('admin/pembayaran/' . $bayar['id'] . '/bukti') ?>', '<?= esc($bayar['kode_pembayaran']) ?>')">
                                    <i class="fa-regular fa-image me-1"></i> Lihat Bukti
                                </button>
                            </td>
                            <td>
                                <div class="small">
                                    Total: <strong>Rp <?= number_format($bayar['keuangan']['harga_total'], 0, ',', '.') ?></strong><br>
                                    Terverifikasi: <span class="text-success">Rp <?= number_format($bayar['keuangan']['pembayaran_bersih'], 0, ',', '.') ?></span><br>
                                    Sisa: <span class="text-danger fw-bold">Rp <?= number_format($bayar['keuangan']['sisa_tagihan'], 0, ',', '.') ?></span>
                                </div>
                            </td>
                            <td>
                                <?php
                                $statusVerif = $bayar['status_verifikasi'];
                                $badgeClass = 'bg-warning-subtle text-warning-emphasis';
                                if ($statusVerif === 'terverifikasi') $badgeClass = 'bg-success-subtle text-success';
                                elseif ($statusVerif === 'ditolak') $badgeClass = 'bg-danger-subtle text-danger';
                                elseif ($statusVerif === 'dikembalikan') $badgeClass = 'bg-info-subtle text-info-emphasis';
                                ?>
                                <span class="badge <?= $badgeClass ?> d-block mb-1">
                                    <?= ucwords(str_replace('_', ' ', $statusVerif)) ?>
                                </span>
                                <?php if (!empty($bayar['nama_admin_verifikator'])): ?>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">Oleh: <?= esc($bayar['nama_admin_verifikator']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($statusVerif === 'menunggu_verifikasi'): ?>
                                    <form action="<?= base_url('admin/pembayaran/' . $bayar['id'] . '/verifikasi') ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah mutasi rekening bank sebesar Rp <?= number_format($bayar['nominal'], 0, ',', '.') ?> benar-benar telah diterima?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-success me-1 px-3">
                                            <i class="fa-solid fa-check me-1"></i> Verifikasi
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-outline-danger px-2" data-bs-toggle="modal" data-bs-target="#modalTolakBayar" onclick="setupTolakBayar(<?= $bayar['id'] ?>, '<?= esc($bayar['kode_pembayaran']) ?>')">
                                        <i class="fa-solid fa-xmark"></i> Tolak
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="fa-solid fa-lock me-1"></i> Selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Preview Bukti Transfer -->
<div class="modal fade" id="modalPreviewBukti" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="previewTitle">Bukti Transfer Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3 bg-light">
                <img id="imgBuktiPreview" src="" class="img-fluid rounded shadow-sm" style="max-height: 520px; object-fit: contain;" alt="Bukti Transfer">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tolak Pembayaran -->
<div class="modal fade" id="modalTolakBayar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formTolakBayar" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-circle-xmark me-1"></i> Tolak Bukti Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Tolak jika bukti transfer palsu, nominal tidak sesuai rekening koran, atau gambar tidak terbaca.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kode Pembayaran</label>
                        <input type="text" id="tolak_kode_bayar" class="form-control" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="alasan_penolakan" class="form-control" rows="3" placeholder="Contoh: Dana belum masuk pada mutasi rekening bank, atau foto bukti transfer buram/tidak terbaca..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fa-solid fa-xmark me-1"></i> Tolak Bukti Bayar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function previewBukti(url, kode) {
    document.getElementById('previewTitle').innerText = 'Bukti Transfer: ' + kode;
    document.getElementById('imgBuktiPreview').src = url;
    new bootstrap.Modal(document.getElementById('modalPreviewBukti')).show();
}

function setupTolakBayar(id, kode) {
    document.getElementById('tolak_kode_bayar').value = kode;
    document.getElementById('formTolakBayar').action = "<?= base_url('admin/pembayaran/') ?>/" + id + "/tolak";
}
</script>
<?= $this->endSection() ?>
