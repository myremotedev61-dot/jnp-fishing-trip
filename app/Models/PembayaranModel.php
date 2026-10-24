<?php

namespace App\Models;

use CodeIgniter\Model;

class PembayaranModel extends Model
{
    protected $table            = 'pembayaran';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'kode_pembayaran',
        'peserta_trip_id',
        'nominal',
        'metode_pembayaran',
        'tanggal_transaksi',
        'nomor_referensi',
        'bukti_pembayaran',
        'status_verifikasi',
        'diverifikasi_oleh',
        'tanggal_verifikasi',
        'alasan_penolakan',
        'catatan_internal'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function generateKode(): string
    {
        return 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }
}
