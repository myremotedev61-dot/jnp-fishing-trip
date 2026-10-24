<?php

namespace App\Models;

use CodeIgniter\Model;

class NotifikasiModel extends Model
{
    protected $table            = 'notifikasi';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'judul',
        'pesan',
        'jenis',
        'data_referensi',
        'dibaca_pada',
        'created_at'
    ];

    protected $useTimestamps = false;

    public function kirim(int $userId, string $judul, string $pesan, string $jenis = 'sistem', ?array $dataReferensi = null): int
    {
        return $this->insert([
            'user_id'        => $userId,
            'judul'          => $judul,
            'pesan'          => $pesan,
            'jenis'          => $jenis,
            'data_referensi' => $dataReferensi ? json_encode($dataReferensi) : null,
            'dibaca_pada'    => null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }
}
