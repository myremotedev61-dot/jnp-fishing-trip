<?php

namespace App\Models;

use CodeIgniter\Model;

class PendaftaranModel extends Model
{
    protected $table            = 'pendaftaran';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'kode_pendaftaran',
        'trip_id',
        'user_id',
        'tanggal_daftar',
        'jumlah_diajukan',
        'status',
        'catatan'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function generateKode(): string
    {
        return 'REG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }
}
