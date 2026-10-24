<?php

namespace App\Models;

use CodeIgniter\Model;

class KapalModel extends Model
{
    protected $table            = 'kapal';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'kode_kapal',
        'nama_kapal',
        'foto',
        'kapasitas',
        'deskripsi',
        'fasilitas',
        'status',
        'catatan_pemeliharaan'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
