<?php

namespace App\Models;

use CodeIgniter\Model;

class TripModel extends Model
{
    protected $table            = 'trips';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'kode_trip',
        'kapal_id',
        'tanggal_trip',
        'jam_kumpul',
        'jam_berangkat',
        'jam_kembali',
        'lokasi_kumpul',
        'tujuan',
        'harga_per_peserta',
        'nominal_panjar',
        'kuota',
        'batas_pendaftaran',
        'batas_pembayaran',
        'fasilitas',
        'ketentuan',
        'deskripsi',
        'kebijakan_pembatalan',
        'status',
        'alasan_pembatalan'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Hitung sisa kuota trip dengan aman (mendukung pessimistic locking saat proses persetujuan).
     */
    public function getConfirmedParticipantsCount(int $tripId, bool $lock = false): int
    {
        $builder = $this->db->table('peserta_trip')
            ->where('trip_id', $tripId)
            ->whereIn('status_persetujuan', ['disetujui', 'dikonfirmasi', 'menunggu_pembayaran_panjar']);

        if ($lock) {
            // Pessimistic locking for anti-overbooking concurrency
            $this->db->query('SELECT id FROM trips WHERE id = ? FOR UPDATE', [$tripId]);
        }

        return $builder->countAllResults();
    }

    /**
     * Dapatkan detail trip beserta relasi kapal dan kalkulasi kuota riil
     */
    public function getTripDetail(int $tripId): ?array
    {
        $trip = $this->select('trips.*, kapal.nama_kapal, kapal.kode_kapal, kapal.foto as foto_kapal, kapal.kapasitas as kapasitas_kapal')
                     ->join('kapal', 'kapal.id = trips.kapal_id', 'left')
                     ->where('trips.id', $tripId)
                     ->first();

        if (!$trip) {
            return null;
        }

        $confirmed = $this->getConfirmedParticipantsCount($tripId);
        $trip['peserta_terkonfirmasi'] = $confirmed;
        $trip['sisa_kuota'] = max(0, (int)$trip['kuota'] - $confirmed);
        $trip['persentase_kuota'] = $trip['kuota'] > 0 ? round(($confirmed / $trip['kuota']) * 100, 1) : 0;

        return $trip;
    }

    /**
     * Ambil daftar trip dengan filter bulan, tahun, status, dan pencarian
     */
    public function getTripsFiltered(?int $bulan = null, ?int $tahun = null, ?string $status = null, ?string $q = null): array
    {
        $builder = $this->select('trips.*, kapal.nama_kapal, kapal.kode_kapal, kapal.foto as foto_kapal, kapal.kapasitas as kapasitas_kapal')
                        ->join('kapal', 'kapal.id = trips.kapal_id', 'left');

        if ($bulan) {
            $builder->where('MONTH(trips.tanggal_trip)', $bulan);
        }
        if ($tahun) {
            $builder->where('YEAR(trips.tanggal_trip)', $tahun);
        }
        if ($status) {
            $builder->where('trips.status', $status);
        }
        if ($q) {
            $builder->groupStart()
                    ->like('trips.tujuan', $q)
                    ->orLike('trips.kode_trip', $q)
                    ->orLike('kapal.nama_kapal', $q)
                    ->groupEnd();
        }

        $trips = $builder->orderBy('trips.tanggal_trip', 'ASC')->findAll();

        foreach ($trips as &$t) {
            $confirmed = $this->getConfirmedParticipantsCount($t['id']);
            $t['peserta_terkonfirmasi'] = $confirmed;
            $t['sisa_kuota'] = max(0, (int)$t['kuota'] - $confirmed);
            $t['persentase_kuota'] = $t['kuota'] > 0 ? round(($confirmed / $t['kuota']) * 100, 1) : 0;
        }

        return $trips;
    }
}
