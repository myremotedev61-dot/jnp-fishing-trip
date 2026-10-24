<?php

namespace App\Controllers\Api;

use App\Models\TripModel;
use App\Models\PendaftaranModel;
use App\Models\PesertaTripModel;
use App\Models\NotifikasiModel;
use CodeIgniter\HTTP\ResponseInterface;

class PendaftaranController extends BaseApiController
{
    /**
     * Pendaftaran Trip Baru (Perorangan atau Kelompok/Rombongan)
     * POST /api/pendaftaran
     */
    public function create(): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $json = $this->request->getJSON(true);
        $tripId  = $json['trip_id'] ?? $this->request->getVar('trip_id');
        $peserta = $json['peserta'] ?? $this->request->getVar('peserta');
        $catatan = $json['catatan'] ?? $this->request->getVar('catatan') ?? '';

        if (empty($tripId) || empty($peserta) || !is_array($peserta)) {
            return $this->respondError('Data trip dan daftar peserta wajib diisi.', 422);
        }

        $tripModel = new TripModel();
        $trip = $tripModel->find($tripId);

        if (!$trip) {
            return $this->respondError('Jadwal trip tidak ditemukan.', 404);
        }

        if (!in_array($trip['status'], ['dibuka', 'hampir_penuh'])) {
            return $this->respondError('Pendaftaran untuk trip ini sudah ditutup atau tidak menerima pendaftaran baru.', 400);
        }

        if (strtotime($trip['batas_pendaftaran']) < time()) {
            return $this->respondError('Batas waktu pendaftaran trip ini telah berakhir.', 400);
        }

        $jumlahDiajukan = count($peserta);
        if ($jumlahDiajukan <= 0) {
            return $this->respondError('Harap sertakan minimal 1 peserta.', 422);
        }

        // Cek sisa kuota
        $confirmed = $tripModel->getConfirmedParticipantsCount((int)$tripId);
        $sisaKuota = (int)$trip['kuota'] - $confirmed;

        if ($sisaKuota < $jumlahDiajukan) {
            return $this->respondError("Sisa kuota trip hanya tersisa {$sisaKuota} orang, tidak mencukupi untuk {$jumlahDiajukan} orang yang diajukan.", 400);
        }

        // Validasi data setiap peserta
        foreach ($peserta as $idx => $p) {
            if (empty($p['nama_pemancing']) || strlen(trim($p['nama_pemancing'])) < 3) {
                return $this->respondError("Nama peserta nomor " . ($idx + 1) . " tidak valid (minimal 3 karakter).", 422);
            }
            if (empty($p['no_telepon']) || strlen(trim($p['no_telepon'])) < 8) {
                return $this->respondError("Nomor telepon/WA peserta nomor " . ($idx + 1) . " tidak valid.", 422);
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $pendaftaranModel = new PendaftaranModel();
        $pesertaModel     = new PesertaTripModel();
        $notifModel       = new NotifikasiModel();

        $kodePendaftaran = $pendaftaranModel->generateKode();

        $pendaftaranId = $pendaftaranModel->insert([
            'kode_pendaftaran' => $kodePendaftaran,
            'trip_id'          => (int)$tripId,
            'user_id'          => (int)$user['id'],
            'tanggal_daftar'   => date('Y-m-d H:i:s'),
            'jumlah_diajukan'  => $jumlahDiajukan,
            'status'           => 'menunggu_persetujuan',
            'catatan'          => $catatan,
        ]);

        $pesertaInserted = [];
        foreach ($peserta as $p) {
            $pesertaId = $pesertaModel->insert([
                'pendaftaran_id'    => $pendaftaranId,
                'trip_id'           => (int)$tripId,
                'nama_pemancing'    => trim($p['nama_pemancing']),
                'no_telepon'        => trim($p['no_telepon']),
                'status_persetujuan'=> 'menunggu_persetujuan',
                'harga_final'       => (float)$trip['harga_per_peserta'],
            ]);
            $pesertaInserted[] = [
                'id'                 => $pesertaId,
                'nama_pemancing'     => trim($p['nama_pemancing']),
                'no_telepon'         => trim($p['no_telepon']),
                'status_persetujuan' => 'menunggu_persetujuan',
                'harga_final'        => (float)$trip['harga_per_peserta'],
            ];
        }

        // Kirim Notifikasi dalam aplikasi ke Pemancing
        $notifModel->kirim(
            (int)$user['id'],
            'Pendaftaran Berhasil Diajukan',
            "Pendaftaran trip ke {$trip['tujuan']} ({$kodePendaftaran}) untuk {$jumlahDiajukan} peserta berhasil dikirim dan sedang menunggu persetujuan pemilik usaha.",
            'pendaftaran',
            ['pendaftaran_id' => $pendaftaranId, 'kode' => $kodePendaftaran]
        );

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->respondError('Gagal menyimpan pendaftaran trip. Silakan coba kembali.', 500);
        }

        return $this->respondSuccess([
            'pendaftaran_id'   => $pendaftaranId,
            'kode_pendaftaran' => $kodePendaftaran,
            'status'           => 'menunggu_persetujuan',
            'peserta'          => $pesertaInserted,
            'catatan_penting'  => 'Pendaftaran berhasil dikirim. Harap menunggu verifikasi dan persetujuan dari administrator sebelum melakukan pembayaran.',
        ], 'Pendaftaran berhasil diajukan! Menunggu persetujuan pemilik usaha.', 201);
    }

    /**
     * Riwayat Pendaftaran Milik Pengguna
     * GET /api/pendaftaran/saya?tab=all
     */
    public function myRegistrations(): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $tab = $this->request->getVar('tab') ?? 'all';
        $pendaftaranModel = new PendaftaranModel();
        $pesertaModel     = new PesertaTripModel();

        $builder = $pendaftaranModel->select('pendaftaran.*, trips.kode_trip, trips.tujuan, trips.tanggal_trip, trips.jam_berangkat, trips.lokasi_kumpul, trips.nominal_panjar, trips.harga_per_peserta, trips.status as status_trip, kapal.nama_kapal')
            ->join('trips', 'trips.id = pendaftaran.trip_id')
            ->join('kapal', 'kapal.id = trips.kapal_id', 'left')
            ->where('pendaftaran.user_id', $user['id']);

        if ($tab === 'menunggu') {
            $builder->where('pendaftaran.status', 'menunggu_persetujuan');
        } elseif ($tab === 'disetujui') {
            $builder->whereIn('pendaftaran.status', ['disetujui', 'disetujui_sebagian']);
        } elseif ($tab === 'ditolak') {
            $builder->where('pendaftaran.status', 'ditolak');
        } elseif ($tab === 'mendatang') {
            $builder->where('trips.tanggal_trip >=', date('Y-m-d'));
        } elseif ($tab === 'selesai') {
            $builder->where('trips.tanggal_trip <', date('Y-m-d'));
        }

        $list = $builder->orderBy('pendaftaran.id', 'DESC')->findAll();

        foreach ($list as &$item) {
            // Ambil daftar peserta
            $pesertaList = $pesertaModel->where('pendaftaran_id', $item['id'])->findAll();
            foreach ($pesertaList as &$p) {
                $p['keuangan'] = $pesertaModel->getFinancialSummary($p['id']);
            }
            $item['peserta'] = $pesertaList;
        }

        return $this->respondSuccess($list, 'Riwayat pendaftaran trip Anda.');
    }

    /**
     * Detail Pendaftaran Spesifik
     * GET /api/pendaftaran/{id}
     */
    public function detail($id = null): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $pendaftaranModel = new PendaftaranModel();
        $pendaftaran = $pendaftaranModel->select('pendaftaran.*, trips.kode_trip, trips.tujuan, trips.tanggal_trip, trips.jam_berangkat, trips.jam_kumpul, trips.lokasi_kumpul, trips.nominal_panjar, trips.harga_per_peserta, trips.status as status_trip, trips.kebijakan_pembatalan, kapal.nama_kapal')
            ->join('trips', 'trips.id = pendaftaran.trip_id')
            ->join('kapal', 'kapal.id = trips.kapal_id', 'left')
            ->where('pendaftaran.id', $id)
            ->first();

        if (!$pendaftaran) {
            return $this->respondError('Pendaftaran tidak ditemukan.', 404);
        }

        // Pastikan hanya pemilik pendaftaran atau admin yang bisa mengakses (Proteksi IDOR)
        if ($pendaftaran['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'owner'])) {
            return $this->respondError('Anda tidak memiliki izin untuk melihat pendaftaran ini.', 403);
        }

        $pesertaModel = new PesertaTripModel();
        $pesertaList = $pesertaModel->where('pendaftaran_id', $id)->findAll();

        foreach ($pesertaList as &$p) {
            $p['keuangan'] = $pesertaModel->getFinancialSummary($p['id']);
        }

        $pendaftaran['peserta'] = $pesertaList;

        return $this->respondSuccess($pendaftaran, 'Detail pendaftaran trip.');
    }

    /**
     * Tambah Peserta Tambahan ke Pendaftaran Yang Ada
     * POST /api/pendaftaran/{id}/peserta
     */
    public function addPeserta($pendaftaranId = null): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $pendaftaranModel = new PendaftaranModel();
        $reg = $pendaftaranModel->find($pendaftaranId);

        if (!$reg || ($reg['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'owner']))) {
            return $this->respondError('Pendaftaran tidak ditemukan atau akses ditolak.', 404);
        }

        $rules = [
            'nama_pemancing' => 'required|min_length[3]|max_length[100]',
            'no_telepon'     => 'required|min_length[8]|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Data nama atau nomor telepon tidak valid.', 422, $this->validator->getErrors());
        }

        $tripModel = new TripModel();
        $trip = $tripModel->find($reg['trip_id']);

        $confirmed = $tripModel->getConfirmedParticipantsCount((int)$reg['trip_id']);
        $sisaKuota = (int)$trip['kuota'] - $confirmed;

        if ($sisaKuota < 1) {
            return $this->respondError('Kuota trip sudah penuh, tidak dapat menambah peserta lagi.', 400);
        }

        $pesertaModel = new PesertaTripModel();
        $pesertaId = $pesertaModel->insert([
            'pendaftaran_id'    => $pendaftaranId,
            'trip_id'           => $reg['trip_id'],
            'nama_pemancing'    => trim($this->request->getVar('nama_pemancing')),
            'no_telepon'        => trim($this->request->getVar('no_telepon')),
            'status_persetujuan'=> 'menunggu_persetujuan',
            'harga_final'       => (float)$trip['harga_per_peserta'],
        ]);

        $pendaftaranModel->update($pendaftaranId, [
            'jumlah_diajukan' => $reg['jumlah_diajukan'] + 1,
        ]);

        return $this->respondSuccess([
            'peserta_id' => $pesertaId,
            'status'     => 'menunggu_persetujuan',
        ], 'Peserta tambahan berhasil diajukan dan sedang menunggu persetujuan.', 201);
    }

    /**
     * Perbarui Data Peserta yang Ditolak untuk Pengajuan Ulang
     * PUT /api/peserta/{id}
     */
    public function updatePeserta($pesertaId = null): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $pesertaModel = new PesertaTripModel();
        $peserta = $pesertaModel->find($pesertaId);

        if (!$peserta) {
            return $this->respondError('Data peserta tidak ditemukan.', 404);
        }

        $pendaftaranModel = new PendaftaranModel();
        $reg = $pendaftaranModel->find($peserta['pendaftaran_id']);

        if (!$reg || ($reg['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'owner']))) {
            return $this->respondError('Akses ditolak.', 403);
        }

        $rules = [
            'nama_pemancing' => 'required|min_length[3]|max_length[100]',
            'no_telepon'     => 'required|min_length[8]|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Data tidak valid', 422, $this->validator->getErrors());
        }

        $pesertaModel->update($pesertaId, [
            'nama_pemancing'     => trim($this->request->getVar('nama_pemancing')),
            'no_telepon'         => trim($this->request->getVar('no_telepon')),
            'status_persetujuan' => 'menunggu_persetujuan',
            'alasan_penolakan'   => null,
        ]);

        return $this->respondSuccess(null, 'Data peserta berhasil diperbaiki dan diajukan ulang untuk persetujuan.');
    }
}
