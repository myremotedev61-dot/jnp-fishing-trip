<?php

namespace App\Models;

use CodeIgniter\Model;

class PengembalianDanaModel extends Model
{
    protected $table            = 'pengembalian_dana';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'pembayaran_id',
        'peserta_trip_id',
        'nominal_refund',
        'alasan_refund',
        'diproses_oleh',
        'tanggal_refund',
        'bukti_refund',
        'created_at'
    ];

    protected $useTimestamps = false;
}
