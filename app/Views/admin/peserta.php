<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-1">Tabel Data Peserta Trip</h5>
        <p class="text-muted small mb-0">Rekapitulasi lengkap data pemancing, status persetujuan, dan verifikasi mutasi kas.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="fa-solid fa-print me-1"></i> Cetak PDF
        </button>
        <button onclick="exportToExcel()" class="btn btn-success">
            <i class="fa-solid fa-file-excel me-1"></i> Ekspor Excel
        </button>
    </div>
</div>

<!-- Filter Box -->
<div class="card border-0 shadow-sm rounded-3 mb-4 p-3 bg-white">
    <form method="GET" action="<?= base_url('admin/peserta') ?>" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Pilih Trip</label>
            <select name="trip_id" class="form-select form-select-sm">
                <option value="">-- Semua Trip --</option>
                <?php foreach ($trips as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= ($filter_trip == $t['id']) ? 'selected' : '' ?>>
                        <?= esc($t['tujuan']) ?> (<?= date('d/m/Y', strtotime($t['tanggal_trip'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Bulan</label>
            <select name="bulan" class="form-select form-select-sm">
                <option value="">-- Semua Bulan --</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($filter_bulan == $m) ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Status Persetujuan</label>
            <select name="status_persetujuan" class="form-select form-select-sm">
                <option value="">-- Semua Status --</option>
                <option value="menunggu_persetujuan" <?= ($filter_persetujuan === 'menunggu_persetujuan') ? 'selected' : '' ?>>Menunggu</option>
                <option value="disetujui" <?= ($filter_persetujuan === 'disetujui') ? 'selected' : '' ?>>Disetujui</option>
                <option value="dikonfirmasi" <?= ($filter_persetujuan === 'dikonfirmasi') ? 'selected' : '' ?>>Dikonfirmasi</option>
                <option value="ditolak" <?= ($filter_persetujuan === 'ditolak') ? 'selected' : '' ?>>Ditolak</option>
                <option value="dibatalkan" <?= ($filter_persetujuan === 'dibatalkan') ? 'selected' : '' ?>>Dibatalkan</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Status Bayar</label>
            <select name="status_bayar" class="form-select form-select-sm">
                <option value="">-- Semua Status Bayar --</option>
                <option value="Belum Bayar" <?= ($filter_bayar === 'Belum Bayar') ? 'selected' : '' ?>>Belum Bayar</option>
                <option value="Panjar" <?= ($filter_bayar === 'Panjar') ? 'selected' : '' ?>>Panjar</option>
                <option value="Lunas" <?= ($filter_bayar === 'Lunas') ? 'selected' : '' ?>>Lunas</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Cari Nama/WA</label>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Nama / No. WA" value="<?= esc($filter_q ?? '') ?>">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-sm btn-primary w-100">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Tabel 7 Kolom Wajib Spesifikasi -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tabelPeserta">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">No.</th>
                    <th>Hari/Tanggal/Tahun</th>
                    <th>Nama Pemancing</th>
                    <th>No. Telepon/WA</th>
                    <th>Keterangan Pembayaran</th>
                    <th>Pembayaran</th>
                    <th>Persetujuan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($peserta)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Tidak ada data peserta yang cocok dengan filter.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($peserta as $p): ?>
                        <tr>
                            <!-- 1. No -->
                            <td class="fw-semibold text-muted"><?= $no++ ?></td>
                            
                            <!-- 2. Hari/Tanggal/Tahun -->
                            <td>
                                <div class="fw-bold text-dark"><?= esc($p['tanggal_formatted']) ?></div>
                                <small class="text-muted"><i class="fa-solid fa-anchor me-1"></i> <?= esc($p['tujuan']) ?></small>
                            </td>

                            <!-- 3. Nama Pemancing -->
                            <td>
                                <div class="fw-bold text-dark"><?= esc($p['nama_pemancing']) ?></div>
                                <small class="text-muted">Ref: <code><?= esc($p['kode_pendaftaran']) ?></code></small>
                            </td>

                            <!-- 4. No. Telepon/WA -->
                            <td>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $p['no_telepon']) ?>" target="_blank" class="text-decoration-none text-success fw-semibold">
                                    <i class="fa-brands fa-whatsapp me-1 fs-6"></i> <?= esc($p['no_telepon']) ?>
                                </a>
                            </td>

                            <!-- 5. Keterangan Pembayaran -->
                            <td>
                                <?php
                                $statusBayar = $p['keuangan']['status_pembayaran'];
                                $badgeBayar = 'bg-danger-subtle text-danger';
                                if ($statusBayar === 'Lunas') $badgeBayar = 'bg-success-subtle text-success';
                                elseif ($statusBayar === 'Panjar') $badgeBayar = 'bg-warning-subtle text-warning-emphasis';
                                ?>
                                <span class="badge <?= $badgeBayar ?> mb-1"><?= $statusBayar ?></span>
                                <div class="small text-muted">
                                    Masuk: <strong>Rp <?= number_format($p['keuangan']['pembayaran_bersih'], 0, ',', '.') ?></strong><br>
                                    Sisa: <span class="<?= $p['keuangan']['sisa_tagihan'] > 0 ? 'text-danger' : 'text-success' ?>">Rp <?= number_format($p['keuangan']['sisa_tagihan'], 0, ',', '.') ?></span>
                                </div>
                            </td>

                            <!-- 6. Tindakan Pembayaran -->
                            <td>
                                <a href="<?= base_url('admin/pembayaran?peserta_id=' . $p['id']) ?>" class="btn btn-sm btn-outline-primary mb-1 d-inline-block">
                                    <i class="fa-solid fa-receipt me-1"></i> Rincian Kas
                                </a>
                                <?php if ($p['keuangan']['pembayaran_menunggu'] > 0): ?>
                                    <span class="badge bg-danger d-block">Ada Bukti Baru</span>
                                <?php endif; ?>
                            </td>

                            <!-- 7. Status Persetujuan Data Peserta -->
                            <td>
                                <?php
                                $statusSetuju = $p['status_persetujuan'];
                                $badgeSetuju = 'bg-secondary-subtle text-secondary';
                                if (in_array($statusSetuju, ['disetujui', 'dikonfirmasi'])) $badgeSetuju = 'bg-success-subtle text-success';
                                elseif ($statusSetuju === 'menunggu_persetujuan') $badgeSetuju = 'bg-warning-subtle text-warning-emphasis';
                                elseif ($statusSetuju === 'menunggu_pembayaran_panjar') $badgeSetuju = 'bg-info-subtle text-info-emphasis';
                                elseif ($statusSetuju === 'ditolak') $badgeSetuju = 'bg-danger-subtle text-danger';
                                elseif ($statusSetuju === 'dibatalkan') $badgeSetuju = 'bg-dark-subtle text-dark';
                                ?>
                                <span class="badge <?= $badgeSetuju ?> d-inline-block mb-1">
                                    <?= ucwords(str_replace('_', ' ', $statusSetuju)) ?>
                                </span>

                                <?php if ($statusSetuju === 'menunggu_persetujuan'): ?>
                                    <div class="mt-1">
                                        <a href="<?= base_url('admin/peserta/' . $p['id'] . '/setujui') ?>" class="btn btn-sm btn-success py-0 px-2" onclick="return confirm('Setujui peserta ini?')">
                                            <i class="fa-solid fa-check"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalTolakPeserta" onclick="setupTolak(<?= $p['id'] ?>, '<?= esc($p['nama_pemancing']) ?>')">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                <?php elseif ($statusSetuju === 'disetujui' || $statusSetuju === 'dikonfirmasi'): ?>
                                    <form action="<?= base_url('admin/peserta/' . $p['id'] . '/batal') ?>" method="POST" onsubmit="return confirm('Batalkan peserta ini? Sisa kuota trip akan diperbarui.')" class="mt-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="alasan" value="Pembatalan atas permintaan peserta/admin">
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Batalkan Peserta">
                                            <i class="fa-solid fa-ban"></i> Batalkan
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tolak Peserta -->
<div class="modal fade" id="modalTolakPeserta" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formTolakPeserta" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Tolak Data Peserta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Peserta yang ditolak tidak akan dimasukkan ke dalam kuota terkonfirmasi. Harap cantumkan alasan agar pemancing dapat memperbaiki data.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Peserta</label>
                        <input type="text" id="tolak_nama_peserta" class="form-control" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="alasan" class="form-control" rows="3" placeholder="Contoh: Nomor WhatsApp tidak aktif saat dihubungi / nama belum lengkap..." required></textarea>
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
function setupTolak(id, nama) {
    document.getElementById('tolak_nama_peserta').value = nama;
    document.getElementById('formTolakPeserta').action = "<?= base_url('admin/peserta/') ?>/" + id + "/tolak";
}

function exportToExcel() {
    let table = document.getElementById("tabelPeserta");
    let rows = table.querySelectorAll("tr");
    let csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) {
            let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/"/g, '""');
            row.push('"' + text.trim() + '"');
        }
        csv.push(row.join(","));
    }
    
    let csvFile = new Blob([csv.join("\n")], { type: "text/csv;charset=utf-8;" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "Data_Peserta_Fishing_Trip_" + new Date().toISOString().slice(0,10) + ".csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>
<?= $this->endSection() ?>
