<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">Manajemen Jadwal Perjalanan Trip</h5>
        <p class="text-muted small mb-0">Atur jadwal keberangkatan, kuota pemancing, harga tiket, nominal panjar, dan status trip.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTrip" onclick="resetFormTrip()">
        <i class="fa-solid fa-plus me-1"></i> Buat Jadwal Trip Baru
    </button>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode & Tujuan Trip</th>
                    <th>Kapal</th>
                    <th>Jadwal Keberangkatan</th>
                    <th>Tarif Tiket / Panjar</th>
                    <th>Kuota & Kursi</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trips)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Belum ada jadwal trip yang dibuat.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($trips as $t): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= esc($t['tujuan']) ?></div>
                                <small class="text-muted"><code><?= esc($t['kode_trip']) ?></code></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-secondary"><?= esc($t['nama_kapal']) ?></div>
                                <small class="text-muted">Kapasitas: <?= $t['kapasitas_kapal'] ?> org</small>
                            </td>
                            <td>
                                <div><i class="fa-regular fa-calendar me-1 text-primary"></i> <?= date('d M Y', strtotime($t['tanggal_trip'])) ?></div>
                                <small class="text-muted"><i class="fa-regular fa-clock me-1"></i> Kumpul: <?= substr($t['jam_kumpul'], 0, 5) ?> | Brgkt: <?= substr($t['jam_berangkat'], 0, 5) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-primary">Rp <?= number_format($t['harga_per_peserta'], 0, ',', '.') ?></div>
                                <small class="text-muted">Panjar: Rp <?= number_format($t['nominal_panjar'], 0, ',', '.') ?></small>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-bold text-success"><?= $t['peserta_terkonfirmasi'] ?></span> / 
                                    <span class="fw-bold"><?= $t['kuota'] ?></span>
                                </div>
                                <small class="text-muted">Sisa: <strong class="<?= $t['sisa_kuota'] <= 2 ? 'text-danger' : 'text-primary' ?>"><?= $t['sisa_kuota'] ?> slot</strong></small>
                            </td>
                            <td>
                                <?php
                                $badgeClass = 'bg-secondary-subtle text-secondary';
                                if ($t['status'] === 'dibuka') $badgeClass = 'bg-success-subtle text-success';
                                elseif ($t['status'] === 'hampir_penuh') $badgeClass = 'bg-warning-subtle text-warning-emphasis';
                                elseif ($t['status'] === 'penuh') $badgeClass = 'bg-danger-subtle text-danger';
                                elseif ($t['status'] === 'selesai') $badgeClass = 'bg-info-subtle text-info-emphasis';
                                elseif ($t['status'] === 'dibatalkan') $badgeClass = 'bg-dark-subtle text-dark';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= strtoupper($t['status']) ?></span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" onclick='editTrip(<?= json_encode($t) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah / Edit Trip -->
<div class="modal fade" id="modalTrip" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="<?= base_url('admin/trips/save') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="trip_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTripTitle">Buat Jadwal Trip Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kode Trip (Otomatis jika kosong)</label>
                            <input type="text" name="kode_trip" id="trip_kode" class="form-control" placeholder="TRIP-202611-XXXX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Pilih Armada Kapal <span class="text-danger">*</span></label>
                            <select name="kapal_id" id="trip_kapal" class="form-select" required>
                                <option value="">-- Pilih Kapal --</option>
                                <?php foreach ($kapal_list as $k): ?>
                                    <option value="<?= $k['id'] ?>" data-kapasitas="<?= $k['kapasitas'] ?>">
                                        <?= esc($k['nama_kapal']) ?> (Kapasitas: <?= $k['kapasitas'] ?> org)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Keberangkatan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_trip" id="trip_tanggal" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jam Kumpul <span class="text-danger">*</span></label>
                            <input type="time" name="jam_kumpul" id="trip_jam_kumpul" class="form-control" value="05:00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jam Keberangkatan <span class="text-danger">*</span></label>
                            <input type="time" name="jam_berangkat" id="trip_jam_berangkat" class="form-control" value="06:00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Perkiraan Jam Kembali <span class="text-danger">*</span></label>
                            <input type="time" name="jam_kembali" id="trip_jam_kembali" class="form-control" value="18:00" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Lokasi Berkumpul / Dermaga <span class="text-danger">*</span></label>
                            <input type="text" name="lokasi_kumpul" id="trip_lokasi" class="form-control" placeholder="Dermaga TPI Binamu Jeneponto" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tujuan / Spot Pemancingan <span class="text-danger">*</span></label>
                            <input type="text" name="tujuan" id="trip_tujuan" class="form-control" placeholder="Spot Rumpon Turatea & Karang Dalam" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Harga Per Peserta (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="harga_per_peserta" id="trip_harga" class="form-control" placeholder="650000" min="10000" step="5000" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nominal Panjar / DP (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="nominal_panjar" id="trip_panjar" class="form-control" placeholder="200000" min="0" step="5000" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kuota Peserta <span class="text-danger">*</span></label>
                            <input type="number" name="kuota" id="trip_kuota" class="form-control" placeholder="10" min="1" max="100" required>
                            <div class="form-text text-muted" style="font-size: 0.72rem;">Tidak boleh melebihi kapasitas kapal</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Batas Akhir Pendaftaran</label>
                            <input type="datetime-local" name="batas_pendaftaran" id="trip_batas_daftar" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Batas Waktu Pelunasan</label>
                            <input type="datetime-local" name="batas_pembayaran" id="trip_batas_bayar" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Status Trip <span class="text-danger">*</span></label>
                            <select name="status" id="trip_status" class="form-select" required>
                                <option value="dibuka">Dibuka</option>
                                <option value="hampir_penuh">Hampir Penuh</option>
                                <option value="penuh">Penuh</option>
                                <option value="ditutup">Ditutup</option>
                                <option value="draft">Draft</option>
                                <option value="selesai">Selesai</option>
                                <option value="dibatalkan">Dibatalkan</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Fasilitas Trip Termasuk</label>
                            <textarea name="fasilitas" id="trip_fasilitas" class="form-control" rows="2" placeholder="Umpan hidup, Makan siang, Es batu & Coolbox, Alat pancing sewa gratis..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Ketentuan & Syarat Perjalanan</label>
                            <textarea name="ketentuan" id="trip_ketentuan" class="form-control" rows="2" placeholder="Wajib sehat, memakai pelampung keselamatan, patuh arahan kapten kapal..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Deskripsi Trip</label>
                            <textarea name="deskripsi" id="trip_deskripsi" class="form-control" rows="2" placeholder="Penjelasan target ikan (GT, Tenggiri, Kakap, Tuna) dan teknik memancing..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kebijakan Pembatalan & Refund</label>
                            <textarea name="kebijakan_pembatalan" id="trip_kebijakan" class="form-control" rows="2" placeholder="Pembatalan H-3 refund 100%, pembatalan H-2 hangus..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Jadwal Trip</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function resetFormTrip() {
    document.getElementById('modalTripTitle').innerText = 'Buat Jadwal Trip Baru';
    document.getElementById('trip_id').value = '';
    document.getElementById('trip_kode').value = '';
    document.getElementById('trip_kapal').value = '';
    document.getElementById('trip_tanggal').value = '';
    document.getElementById('trip_jam_kumpul').value = '05:00';
    document.getElementById('trip_jam_berangkat').value = '06:00';
    document.getElementById('trip_jam_kembali').value = '18:00';
    document.getElementById('trip_lokasi').value = 'Dermaga TPI Binamu Jeneponto';
    document.getElementById('trip_tujuan').value = '';
    document.getElementById('trip_harga').value = '';
    document.getElementById('trip_panjar').value = '';
    document.getElementById('trip_kuota').value = '';
    document.getElementById('trip_status').value = 'dibuka';
    document.getElementById('trip_fasilitas').value = 'Umpan cumi/tongkol, Makan siang + snack, Air mineral & es batu, Alat pancing cadangan';
    document.getElementById('trip_ketentuan').value = 'Peserta wajib sehat jasmani, wajib memakai pelampung selama perjalanan';
    document.getElementById('trip_deskripsi').value = '';
    document.getElementById('trip_kebijakan').value = 'Pembatalan sebelum H-3 panjar kembali 100%, pembatalan setelah H-3 panjar hangus.';
}

function editTrip(data) {
    document.getElementById('modalTripTitle').innerText = 'Edit Jadwal Trip';
    document.getElementById('trip_id').value = data.id;
    document.getElementById('trip_kode').value = data.kode_trip;
    document.getElementById('trip_kapal').value = data.kapal_id;
    document.getElementById('trip_tanggal').value = data.tanggal_trip;
    document.getElementById('trip_jam_kumpul').value = data.jam_kumpul.substring(0,5);
    document.getElementById('trip_jam_berangkat').value = data.jam_berangkat.substring(0,5);
    document.getElementById('trip_jam_kembali').value = data.jam_kembali.substring(0,5);
    document.getElementById('trip_lokasi').value = data.lokasi_kumpul;
    document.getElementById('trip_tujuan').value = data.tujuan;
    document.getElementById('trip_harga').value = parseInt(data.harga_per_peserta);
    document.getElementById('trip_panjar').value = parseInt(data.nominal_panjar);
    document.getElementById('trip_kuota').value = data.kuota;
    document.getElementById('trip_status').value = data.status;
    document.getElementById('trip_fasilitas').value = data.fasilitas || '';
    document.getElementById('trip_ketentuan').value = data.ketentuan || '';
    document.getElementById('trip_deskripsi').value = data.deskripsi || '';
    document.getElementById('trip_kebijakan').value = data.kebijakan_pembatalan || '';
    new bootstrap.Modal(document.getElementById('modalTrip')).show();
}
</script>
<?= $this->endSection() ?>
