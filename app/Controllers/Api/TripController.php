<?php

namespace App\Controllers\Api;

use App\Models\TripModel;
use App\Models\KapalModel;
use App\Models\PengaturanUsahaModel;
use CodeIgniter\HTTP\ResponseInterface;

class TripController extends BaseApiController
{
    /**
     * Daftar Jadwal Trip (Bulan berjalan / Filter Kalender)
     * GET /api/trips?bulan=11&tahun=2026&status=dibuka&q=
     */
    public function index(): ResponseInterface
    {
        $tripModel = new TripModel();

        $bulan  = $this->request->getVar('bulan') ? (int)$this->request->getVar('bulan') : null;
        $tahun  = $this->request->getVar('tahun') ? (int)$this->request->getVar('tahun') : null;
        $status = $this->request->getVar('status') ? trim($this->request->getVar('status')) : null;
        $q      = $this->request->getVar('q') ? trim($this->request->getVar('q')) : null;

        $trips = $tripModel->getTripsFiltered($bulan, $tahun, $status, $q);

        // Format tanggal Indonesia untuk kemudahan konsumsi aplikasi mobile
        $hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulanIndo = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        foreach ($trips as &$t) {
            $timestamp = strtotime($t['tanggal_trip']);
            $namaHari = $hariIndo[date('w', $timestamp)];
            $tgl = date('j', $timestamp);
            $namaBulan = $bulanIndo[(int)date('n', $timestamp)];
            $thn = date('Y', $timestamp);

            $t['tanggal_formatted'] = "{$namaHari}, {$tgl} {$namaBulan} {$thn}";
            $t['harga_formatted'] = 'Rp ' . number_format((float)$t['harga_per_peserta'], 0, ',', '.');
            $t['panjar_formatted'] = 'Rp ' . number_format((float)$t['nominal_panjar'], 0, ',', '.');
        }

        return $this->respondSuccess($trips, 'Daftar jadwal trip.');
    }

    /**
     * Detail Trip Lengkap
     * GET /api/trips/{id}
     */
    public function detail($id = null): ResponseInterface
    {
        if (!$id) {
            return $this->respondError('ID Trip tidak valid.', 400);
        }

        $tripModel = new TripModel();
        $trip = $tripModel->getTripDetail((int)$id);

        if (!$trip) {
            return $this->respondError('Trip tidak ditemukan.', 404);
        }

        $hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulanIndo = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        $timestamp = strtotime($trip['tanggal_trip']);
        $trip['tanggal_formatted'] = $hariIndo[date('w', $timestamp)] . ', ' . date('j', $timestamp) . ' ' . $bulanIndo[(int)date('n', $timestamp)] . ' ' . date('Y', $timestamp);
        $trip['harga_formatted'] = 'Rp ' . number_format((float)$trip['harga_per_peserta'], 0, ',', '.');
        $trip['panjar_formatted'] = 'Rp ' . number_format((float)$trip['nominal_panjar'], 0, ',', '.');

        return $this->respondSuccess($trip, 'Detail informasi trip.');
    }

    /**
     * Cek Kuota Realtime Trip
     * GET /api/trips/{id}/kuota
     */
    public function getQuota($id = null): ResponseInterface
    {
        if (!$id) {
            return $this->respondError('ID Trip tidak valid.', 400);
        }

        $tripModel = new TripModel();
        $trip = $tripModel->find($id);

        if (!$trip) {
            return $this->respondError('Trip tidak ditemukan.', 404);
        }

        $confirmed = $tripModel->getConfirmedParticipantsCount((int)$id);
        $sisaKuota = max(0, (int)$trip['kuota'] - $confirmed);

        return $this->respondSuccess([
            'trip_id'              => (int)$id,
            'kuota_maksimal'       => (int)$trip['kuota'],
            'peserta_terkonfirmasi'=> $confirmed,
            'sisa_kuota'           => $sisaKuota,
            'status_trip'          => $trip['status'],
            'apakah_penuh'         => $sisaKuota <= 0,
        ], 'Informasi kuota trip terkini.');
    }

    /**
     * Daftar Kapal Pemancingan
     * GET /api/kapal
     */
    public function kapalList(): ResponseInterface
    {
        $kapalModel = new KapalModel();
        $kapal = $kapalModel->where('status', 'aktif')->findAll();

        return $this->respondSuccess($kapal, 'Daftar armada kapal pemancingan.');
    }

    /**
     * Pengaturan Usaha, Rekening Bank, dan Kontak Hotline WhatsApp
     * GET /api/pengaturan
     */
    public function pengaturan(): ResponseInterface
    {
        $pengaturanModel = new PengaturanUsahaModel();
        $data = $pengaturanModel->getPengaturan();

        return $this->respondSuccess($data, 'Pengaturan usaha dan kontak pemesanan.');
    }
}
