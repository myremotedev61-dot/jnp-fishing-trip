<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">Pengaturan Usaha & Rekening Pembayaran</h5>
        <p class="text-muted small mb-0">Konfigurasi nama usaha, kontak WhatsApp, rekening bank tujuan transfer, dan kebijakan panjar.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <form action="<?= base_url('admin/pengaturan/save') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-store me-2 text-primary"></i> Profil Usaha & Kontak</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Usaha Pemancingan</label>
                            <input type="text" name="nama_usaha" class="form-control" value="<?= esc($setting['nama_usaha']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nomor WhatsApp Usaha (Hotline)</label>
                            <input type="text" name="no_whatsapp" class="form-control" value="<?= esc($setting['no_whatsapp']) ?>" placeholder="6281234567890" required>
                            <div class="form-text text-muted" style="font-size: 0.72rem;">Awali dengan kode negara 62 untuk integrasi chat langsung.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Zona Waktu Sistem</label>
                            <select name="zona_waktu" class="form-select">
                                <option value="Asia/Makassar" <?= ($setting['zona_waktu'] === 'Asia/Makassar') ? 'selected' : '' ?>>WITA (Asia/Makassar)</option>
                                <option value="Asia/Jakarta" <?= ($setting['zona_waktu'] === 'Asia/Jakarta') ? 'selected' : '' ?>>WIB (Asia/Jakarta)</option>
                                <option value="Asia/Jayapura" <?= ($setting['zona_waktu'] === 'Asia/Jayapura') ? 'selected' : '' ?>>WIT (Asia/Jayapura)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Alamat Kantor / Dermaga</label>
                            <input type="text" name="alamat" class="form-control" value="<?= esc($setting['alamat'] ?? '') ?>" placeholder="Pelabuhan Jeneponto, Sulawesi Selatan">
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-credit-card me-2 text-primary"></i> Rekening Bank Tujuan Transfer</h6>
                    <div id="rekeningContainer" class="mb-3">
                        <?php if (empty($setting['rekening_pembayaran'])): ?>
                            <div class="row g-2 mb-2 rek-row">
                                <div class="col-md-3"><input type="text" name="bank_nama[]" class="form-control" placeholder="Nama Bank (BCA)"></div>
                                <div class="col-md-4"><input type="text" name="bank_nomor[]" class="form-control" placeholder="Nomor Rekening"></div>
                                <div class="col-md-4"><input type="text" name="bank_pemilik[]" class="form-control" placeholder="Atas Nama Pemilik"></div>
                                <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="removeRek(this)"><i class="fa-solid fa-trash"></i></button></div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($setting['rekening_pembayaran'] as $rek): ?>
                                <div class="row g-2 mb-2 rek-row">
                                    <div class="col-md-3"><input type="text" name="bank_nama[]" class="form-control" value="<?= esc($rek['bank']) ?>" placeholder="Nama Bank"></div>
                                    <div class="col-md-4"><input type="text" name="bank_nomor[]" class="form-control" value="<?= esc($rek['nomor']) ?>" placeholder="Nomor Rekening"></div>
                                    <div class="col-md-4"><input type="text" name="bank_pemilik[]" class="form-control" value="<?= esc($rek['pemilik']) ?>" placeholder="Atas Nama Pemilik"></div>
                                    <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="removeRek(this)"><i class="fa-solid fa-trash"></i></button></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-4" onclick="addRek()">
                        <i class="fa-solid fa-plus me-1"></i> Tambah Rekening Lain
                    </button>

                    <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-shield-halved me-2 text-primary"></i> Ketentuan Panjar & Kebijakan Refund</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Minimal Panjar (%)</label>
                            <input type="number" name="minimal_panjar_persen" class="form-control" min="10" max="100" value="<?= esc($setting['pengaturan_panjar']['minimal_panjar_persen'] ?? 30) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Pernyataan Kebijakan Pembatalan & Refund</label>
                            <textarea name="kebijakan_refund" class="form-control" rows="3"><?= esc($setting['pengaturan_panjar']['kebijakan_refund'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function addRek() {
    let container = document.getElementById('rekeningContainer');
    let div = document.createElement('div');
    div.className = 'row g-2 mb-2 rek-row';
    div.innerHTML = `
        <div class="col-md-3"><input type="text" name="bank_nama[]" class="form-control" placeholder="Nama Bank (BCA)"></div>
        <div class="col-md-4"><input type="text" name="bank_nomor[]" class="form-control" placeholder="Nomor Rekening"></div>
        <div class="col-md-4"><input type="text" name="bank_pemilik[]" class="form-control" placeholder="Atas Nama Pemilik"></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="removeRek(this)"><i class="fa-solid fa-trash"></i></button></div>
    `;
    container.appendChild(div);
}

function removeRek(btn) {
    btn.closest('.rek-row').remove();
}
</script>
<?= $this->endSection() ?>
