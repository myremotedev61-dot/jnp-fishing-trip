<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-1">Antrean Persetujuan Data Peserta</h5>
        <p class="text-muted small mb-0">Tinjau keabsahan identitas nama pemancing dan nomor WhatsApp sebelum dikonfirmasi ke kuota resmi.</p>
    </div>
    <span class="badge bg-warning text-dark px-3 py-2 fs-6">
        <i class="fa-solid fa-hourglass-half me-1"></i> <?= count($antrean) ?> Peserta Menunggu Tinjauan
    </span>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">No.</th>
                    <th>Kode Reg & Pengajuan</th>
                    <th>Nama Pemancing & WhatsApp</th>
                    <th>Trip & Tanggal</th>
                    <th>Sisa Kuota</th>
                    <th class="text-end">Tindakan Administrator</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($antrean)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-success mb-2"><i class="fa-solid fa-circle-check fs-1"></i></div>
                            <h6 class="fw-bold text-dark">Tidak Ada Antrean Tertunda</h6>
                            <p class="text-muted small">Seluruh pengajuan peserta trip telah selesai ditinjau.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($antrean as $item): ?>
                        <tr>
                            <td class="fw-semibold text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= esc($item['kode_pendaftaran']) ?></div>
                                <small class="text-muted"><i class="fa-regular fa-clock me-1"></i> <?= date('d M Y, H:i', strtotime($item['tanggal_daftar'])) ?> WITA</small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark fs-6"><?= esc($item['nama_pemancing']) ?></div>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $item['no_telepon']) ?>" target="_blank" class="text-decoration-none text-success small fw-semibold">
                                    <i class="fa-brands fa-whatsapp me-1"></i> <?= esc($item['no_telepon']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?= esc($item['tujuan']) ?></div>
                                <small class="text-muted">
                                    <i class="fa-regular fa-calendar me-1"></i> <?= date('d M Y', strtotime($item['tanggal_trip'])) ?> (<?= esc($item['nama_kapal']) ?>)
                                </small>
                            </td>
                            <td>
                                <span class="badge <?= $item['sisa_kuota'] > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fs-6">
                                    <?= $item['sisa_kuota'] ?> Kursi Tersedia
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= base_url('admin/peserta/' . $item['id'] . '/setujui') ?>" class="btn btn-sm btn-success me-1 px-3" onclick="return confirm('Apakah Anda yakin ingin MENYETUJUI peserta <?= esc($item['nama_pemancing']) ?>?')">
                                    <i class="fa-solid fa-check me-1"></i> Setujui
                                </a>
                                <button class="btn btn-sm btn-danger px-3" data-bs-toggle="modal" data-bs-target="#modalTolakQueue" onclick="setupTolakQueue(<?= $item['id'] ?>, '<?= esc($item['nama_pemancing']) ?>')">
                                    <i class="fa-solid fa-xmark me-1"></i> Tolak
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tolak dari Antrean -->
<div class="modal fade" id="modalTolakQueue" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formTolakQueue" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Tolak Pengajuan Peserta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Peserta yang ditolak tidak akan dimasukkan ke dalam kuota terkonfirmasi. Pemancing akan menerima notifikasi alasan penolakan ini.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Peserta</label>
                        <input type="text" id="tolak_queue_nama" class="form-control" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="alasan" class="form-control" rows="3" placeholder="Sebutkan alasan penolakan, misalnya nomor WhatsApp tidak bisa dihubungi atau data tidak sesuai ketentuan..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fa-solid fa-xmark me-1"></i> Tolak Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function setupTolakQueue(id, nama) {
    document.getElementById('tolak_queue_nama').value = nama;
    document.getElementById('formTolakQueue').action = "<?= base_url('admin/peserta/') ?>/" + id + "/tolak";
}
</script>
<?= $this->endSection() ?>
