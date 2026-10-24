<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-1">Laporan Operasional & Rekapitulasi Kas</h5>
        <p class="text-muted small mb-0">Laporan aktual berdasarkan data transaksi peserta terverifikasi.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="fa-solid fa-print me-1"></i> Cetak Dokumen / PDF
        </button>
        <button onclick="exportLaporanExcel()" class="btn btn-success">
            <i class="fa-solid fa-file-excel me-1"></i> Ekspor CSV / Excel
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4 p-3 bg-white">
    <form method="GET" action="<?= base_url('admin/laporan') ?>" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Pilih Trip Tertentu</label>
            <select name="trip_id" class="form-select form-select-sm">
                <option value="">-- Semua Trip --</option>
                <?php foreach ($all_trips as $at): ?>
                    <option value="<?= $at['id'] ?>" <?= ($filter_trip == $at['id']) ? 'selected' : '' ?>>
                        <?= esc($at['tujuan']) ?> (<?= date('d/m/Y', strtotime($at['tanggal_trip'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Bulan</label>
            <select name="bulan" class="form-select form-select-sm">
                <option value="semua" <?= ($filter_bulan === 'semua') ? 'selected' : '' ?>>-- Semua Bulan --</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($filter_bulan == $m) ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Tahun</label>
            <select name="tahun" class="form-select form-select-sm">
                <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                    <option value="<?= $y ?>" <?= ($filter_tahun == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-sm btn-primary w-100">
                <i class="fa-solid fa-arrows-rotate me-1"></i> Terapkan Filter Laporan
            </button>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card p-3 bg-white border-0">
            <div class="text-muted small fw-semibold">TOTAL PESERTA TERKONFIRMASI</div>
            <div class="fs-3 fw-bold text-dark my-1"><?= $total_konfirm ?> Orang</div>
            <small class="text-success"><i class="fa-solid fa-users me-1"></i> Peserta disetujui periode ini</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card p-3 bg-white border-0">
            <div class="text-muted small fw-semibold">TOTAL PENERIMAAN KAS BERSIH</div>
            <div class="fs-3 fw-bold text-success my-1">Rp <?= number_format($total_penerimaan, 0, ',', '.') ?></div>
            <small class="text-muted"><i class="fa-solid fa-circle-check me-1"></i> Dana masuk riil setelah refund</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card p-3 bg-white border-0">
            <div class="text-muted small fw-semibold">SISA TAGIHAN / PIUTANG</div>
            <div class="fs-3 fw-bold text-warning my-1">Rp <?= number_format($total_sisa_tagihan, 0, ',', '.') ?></div>
            <small class="text-muted"><i class="fa-solid fa-clock me-1"></i> Menunggu pelunasan pemancing</small>
        </div>
    </div>
</div>

<!-- Tabel Rincian Per Trip -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tabelLaporan">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">No.</th>
                    <th>Jadwal & Kode Trip</th>
                    <th>Tujuan & Kapal</th>
                    <th>Kuota / Terkonfirmasi</th>
                    <th>Tarif / Peserta</th>
                    <th>Total Kas Masuk</th>
                    <th>Sisa Tagihan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trips)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Tidak ada data trip pada periode ini.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($trips as $t): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= date('d M Y', strtotime($t['tanggal_trip'])) ?></div>
                                <small class="text-muted"><code><?= esc($t['kode_trip']) ?></code></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= esc($t['tujuan']) ?></div>
                                <small class="text-muted"><?= esc($t['nama_kapal']) ?></small>
                            </td>
                            <td>
                                <span class="fw-bold text-success"><?= $t['terkonfirmasi'] ?></span> / <?= $t['kuota'] ?> Kursi
                            </td>
                            <td>Rp <?= number_format($t['harga_per_peserta'], 0, ',', '.') ?></td>
                            <td>
                                <strong class="text-success">Rp <?= number_format($t['uang_masuk'], 0, ',', '.') ?></strong>
                            </td>
                            <td>
                                <span class="<?= $t['sisa_tagihan'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    Rp <?= number_format($t['sisa_tagihan'], 0, ',', '.') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary"><?= strtoupper($t['status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="text-end">TOTAL KESELURUHAN:</td>
                    <td><?= $total_konfirm ?> Kursi</td>
                    <td>-</td>
                    <td class="text-success fs-6">Rp <?= number_format($total_penerimaan, 0, ',', '.') ?></td>
                    <td class="text-danger fs-6">Rp <?= number_format($total_sisa_tagihan, 0, ',', '.') ?></td>
                    <td>-</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function exportLaporanExcel() {
    let table = document.getElementById("tabelLaporan");
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
    downloadLink.download = "Laporan_Pendapatan_Fishing_Trip_" + new Date().toISOString().slice(0,10) + ".csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>
<?= $this->endSection() ?>
