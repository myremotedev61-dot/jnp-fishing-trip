<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">Manajemen Armada Kapal Pemancingan</h5>
        <p class="text-muted small mb-0">Kelola spesifikasi, kapasitas, fasilitas, dan status operasional armada kapal.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalKapal" onclick="resetFormKapal()">
        <i class="fa-solid fa-plus me-1"></i> Tambah Kapal Baru
    </button>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode & Nama Kapal</th>
                    <th>Kapasitas</th>
                    <th>Fasilitas Utama</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kapal)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada armada kapal yang terdaftar.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($kapal as $k): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= esc($k['nama_kapal']) ?></div>
                                <span class="badge bg-secondary-subtle text-secondary"><?= esc($k['kode_kapal']) ?></span>
                            </td>
                            <td>
                                <span class="fw-bold fs-6 text-primary"><?= esc($k['kapasitas']) ?></span> Orang
                            </td>
                            <td>
                                <small class="text-muted d-block text-truncate" style="max-width: 320px;">
                                    <?= esc($k['fasilitas']) ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($k['status'] === 'aktif'): ?>
                                    <span class="badge bg-success-subtle text-success">Aktif Beroperasi</span>
                                <?php elseif ($k['status'] === 'pemeliharaan'): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis">Pemeliharaan (Dock)</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" onclick='editKapal(<?= json_encode($k) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <a href="<?= base_url('admin/kapal/toggle/' . $k['id']) ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Ubah status aktif kapal ini?')">
                                    <i class="fa-solid fa-power-off"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah / Edit Kapal -->
<div class="modal fade" id="modalKapal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= base_url('admin/kapal/save') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="kapal_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">Tambah Kapal Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kode Kapal <span class="text-danger">*</span></label>
                            <input type="text" name="kode_kapal" id="kapal_kode" class="form-control" placeholder="KPL-001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Kapal <span class="text-danger">*</span></label>
                            <input type="text" name="nama_kapal" id="kapal_nama" class="form-control" placeholder="KM Samudra Turatea" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kapasitas Maksimal (Orang) <span class="text-danger">*</span></label>
                            <input type="number" name="kapasitas" id="kapal_kapasitas" class="form-control" min="1" max="100" placeholder="12" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Status Operasional <span class="text-danger">*</span></label>
                            <select name="status" id="kapal_status" class="form-select" required>
                                <option value="aktif">Aktif Beroperasi</option>
                                <option value="pemeliharaan">Dalam Perawatan (Docking)</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Fasilitas Kapal</label>
                            <textarea name="fasilitas" id="kapal_fasilitas" class="form-control" rows="3" placeholder="Fishfinder Sonar, Kabin Ber-AC, Toilet Bersih, Dapur Mini, Life Jacket, Coolbox Es..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Deskripsi Kapal</label>
                            <textarea name="deskripsi" id="kapal_deskripsi" class="form-control" rows="2" placeholder="Keterangan mesin, spesifikasi lambung, dan daya jelajah..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan Pemeliharaan (Opsional)</label>
                            <input type="text" name="catatan_pemeliharaan" id="kapal_pemeliharaan" class="form-control" placeholder="Jadwal servis oli mesin atau cek lambung berikutnya">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data Kapal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function resetFormKapal() {
    document.getElementById('modalTitle').innerText = 'Tambah Kapal Baru';
    document.getElementById('kapal_id').value = '';
    document.getElementById('kapal_kode').value = '';
    document.getElementById('kapal_nama').value = '';
    document.getElementById('kapal_kapasitas').value = '';
    document.getElementById('kapal_status').value = 'aktif';
    document.getElementById('kapal_fasilitas').value = '';
    document.getElementById('kapal_deskripsi').value = '';
    document.getElementById('kapal_pemeliharaan').value = '';
}

function editKapal(data) {
    document.getElementById('modalTitle').innerText = 'Edit Data Kapal';
    document.getElementById('kapal_id').value = data.id;
    document.getElementById('kapal_kode').value = data.kode_kapal;
    document.getElementById('kapal_nama').value = data.nama_kapal;
    document.getElementById('kapal_kapasitas').value = data.kapasitas;
    document.getElementById('kapal_status').value = data.status;
    document.getElementById('kapal_fasilitas').value = data.fasilitas || '';
    document.getElementById('kapal_deskripsi').value = data.deskripsi || '';
    document.getElementById('kapal_pemeliharaan').value = data.catatan_pemeliharaan || '';
    new bootstrap.Modal(document.getElementById('modalKapal')).show();
}
</script>
<?= $this->endSection() ?>
