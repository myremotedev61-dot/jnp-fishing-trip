<?php

namespace App\Controllers\Api;

use App\Models\PembayaranModel;
use App\Models\PesertaTripModel;
use App\Models\PendaftaranModel;
use App\Models\NotifikasiModel;
use CodeIgniter\HTTP\ResponseInterface;

class PembayaranController extends BaseApiController
{
    /**
     * Ajukan Pembayaran Panjar / Pelunasan
     * POST /api/pembayaran (multipart/form-data)
     */
    public function create(): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $rules = [
            'peserta_trip_id'   => 'required|is_natural_no_zero',
            'nominal'           => 'required|numeric|greater_than[0]',
            'metode_pembayaran' => 'required|min_length[3]|max_length[50]',
            'tanggal_transaksi' => 'required|valid_date[Y-m-d]',
            'bukti_pembayaran'  => 'uploaded[bukti_pembayaran]|max_size[bukti_pembayaran,5120]|ext_in[bukti_pembayaran,jpg,jpeg,png,webp,pdf]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Data pembayaran atau file bukti transfer tidak valid.', 422, $this->validator->getErrors());
        }

        $pesertaTripId = (int)$this->request->getVar('peserta_trip_id');
        $nominal       = (float)$this->request->getVar('nominal');
        $metode        = trim($this->request->getVar('metode_pembayaran'));
        $tanggal       = trim($this->request->getVar('tanggal_transaksi'));
        $nomorRef      = trim($this->request->getVar('nomor_referensi') ?? '');

        $pesertaModel = new PesertaTripModel();
        $peserta = $pesertaModel->find($pesertaTripId);

        if (!$peserta) {
            return $this->respondError('Data peserta trip tidak ditemukan.', 404);
        }

        // Cek kepemilikan pendaftaran (Proteksi IDOR)
        $pendaftaranModel = new PendaftaranModel();
        $pendaftaran = $pendaftaranModel->find($peserta['pendaftaran_id']);

        if (!$pendaftaran || ($pendaftaran['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'owner']))) {
            return $this->respondError('Anda tidak memiliki izin untuk melakukan pembayaran pada peserta ini.', 403);
        }

        // Cek status persetujuan peserta
        if (!in_array($peserta['status_persetujuan'], ['disetujui', 'menunggu_pembayaran_panjar', 'dikonfirmasi'])) {
            return $this->respondError('Peserta belum disetujui oleh pemilik usaha. Pembayaran hanya dapat dilakukan setelah data peserta disetujui.', 400);
        }

        // Cek sisa tagihan
        $finSummary = $pesertaModel->getFinancialSummary($pesertaTripId);
        if ($finSummary['sisa_tagihan'] <= 0) {
            return $this->respondError('Tagihan untuk peserta ini sudah lunas.', 400);
        }

        // Simpan file bukti transfer ke direktori privat yang aman
        $file = $this->request->getFile('bukti_pembayaran');
        if (!$file->isValid()) {
            return $this->respondError('File bukti transfer gagal diunggah: ' . $file->getErrorString(), 400);
        }

        $uploadPath = WRITEPATH . 'uploads/bukti_pembayaran/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($uploadPath, $newName);

        $pembayaranModel = new PembayaranModel();
        $kodePembayaran = $pembayaranModel->generateKode();

        $pembayaranId = $pembayaranModel->insert([
            'kode_pembayaran'   => $kodePembayaran,
            'peserta_trip_id'   => $pesertaTripId,
            'nominal'           => $nominal,
            'metode_pembayaran' => $metode,
            'tanggal_transaksi' => $tanggal,
            'nomor_referensi'   => !empty($nomorRef) ? $nomorRef : null,
            'bukti_pembayaran'  => $newName,
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);

        // Notifikasi ke pemancing
        $notifModel = new NotifikasiModel();
        $notifModel->kirim(
            (int)$user['id'],
            'Pembayaran Berhasil Diajukan',
            "Pengajuan pembayaran Rp " . number_format($nominal, 0, ',', '.') . " untuk peserta {$peserta['nama_pemancing']} ({$kodePembayaran}) berhasil dikirim. Harap menunggu verifikasi dari pemilik usaha.",
            'pembayaran',
            ['pembayaran_id' => $pembayaranId, 'kode' => $kodePembayaran]
        );

        return $this->respondSuccess([
            'pembayaran_id'     => $pembayaranId,
            'kode_pembayaran'   => $kodePembayaran,
            'nominal'           => $nominal,
            'status_verifikasi' => 'menunggu_verifikasi',
            'pesan_verifikasi'  => 'Bukti pembayaran berhasil diunggah dan sedang dalam antrean verifikasi pemilik usaha. Status belum berubah menjadi Panjar atau Lunas sebelum diverifikasi.',
        ], 'Bukti transfer berhasil dikirim. Menunggu verifikasi pemilik usaha.', 201);
    }

    /**
     * Riwayat Pembayaran Pengguna
     * GET /api/pembayaran/saya
     */
    public function myPayments(): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $pembayaranModel = new PembayaranModel();
        $list = $pembayaranModel->select('pembayaran.*, peserta_trip.nama_pemancing, peserta_trip.no_telepon, trips.kode_trip, trips.tujuan, trips.tanggal_trip')
            ->join('peserta_trip', 'peserta_trip.id = pembayaran.peserta_trip_id')
            ->join('pendaftaran', 'pendaftaran.id = peserta_trip.pendaftaran_id')
            ->join('trips', 'trips.id = peserta_trip.trip_id')
            ->where('pendaftaran.user_id', $user['id'])
            ->orderBy('pembayaran.id', 'DESC')
            ->findAll();

        foreach ($list as &$item) {
            $item['nominal_formatted'] = 'Rp ' . number_format((float)$item['nominal'], 0, ',', '.');
        }

        return $this->respondSuccess($list, 'Riwayat pembayaran Anda.');
    }

    /**
     * Detail Transaksi Pembayaran
     * GET /api/pembayaran/{id}
     */
    public function detail($id = null): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $pembayaranModel = new PembayaranModel();
        $bayar = $pembayaranModel->select('pembayaran.*, peserta_trip.nama_pemancing, peserta_trip.no_telepon, pendaftaran.user_id, trips.kode_trip, trips.tujuan, trips.tanggal_trip')
            ->join('peserta_trip', 'peserta_trip.id = pembayaran.peserta_trip_id')
            ->join('pendaftaran', 'pendaftaran.id = peserta_trip.pendaftaran_id')
            ->join('trips', 'trips.id = peserta_trip.trip_id')
            ->where('pembayaran.id', $id)
            ->first();

        if (!$bayar) {
            return $this->respondError('Pembayaran tidak ditemukan.', 404);
        }

        if ($bayar['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'owner'])) {
            return $this->respondError('Akses ditolak.', 403);
        }

        $bayar['nominal_formatted'] = 'Rp ' . number_format((float)$bayar['nominal'], 0, ',', '.');
        return $this->respondSuccess($bayar, 'Detail transaksi pembayaran.');
    }

    /**
     * Akses Privat Bukti Transfer yang Aman
     * GET /api/pembayaran/{id}/bukti
     */
    public function viewBukti($id = null)
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $pembayaranModel = new PembayaranModel();
        $bayar = $pembayaranModel->select('pembayaran.*, pendaftaran.user_id')
            ->join('peserta_trip', 'peserta_trip.id = pembayaran.peserta_trip_id')
            ->join('pendaftaran', 'pendaftaran.id = peserta_trip.pendaftaran_id')
            ->where('pembayaran.id', $id)
            ->first();

        if (!$bayar) {
            return $this->respondError('Data pembayaran tidak ditemukan.', 404);
        }

        if ($bayar['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'owner'])) {
            return $this->respondError('Akses ditolak.', 403);
        }

        $filePath = WRITEPATH . 'uploads/bukti_pembayaran/' . $bayar['bukti_pembayaran'];
        if (!file_exists($filePath)) {
            return $this->respondError('Berkas bukti transfer tidak ditemukan di server.', 404);
        }

        $mime = mime_content_type($filePath);
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($filePath) . '"')
            ->setBody(file_get_contents($filePath));
    }
}
