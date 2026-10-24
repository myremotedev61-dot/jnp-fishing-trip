<?php

namespace App\Models;

use CodeIgniter\Model;

class PengaturanUsahaModel extends Model
{
    protected $table            = 'pengaturan_usaha';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama_usaha',
        'logo',
        'no_whatsapp',
        'alamat',
        'rekening_pembayaran',
        'pengaturan_panjar',
        'zona_waktu'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getPengaturan(): array
    {
        $data = $this->first();
        if (!$data) {
            return [
                'id'                  => 1,
                'nama_usaha'          => 'Fishing Trip Turatea',
                'logo'                => 'logo.png',
                'no_whatsapp'         => '6281234567890',
                'alamat'              => 'Pelabuhan Jeneponto, Sulawesi Selatan',
                'rekening_pembayaran' => [],
                'pengaturan_panjar'   => ['minimal_panjar_persen' => 30],
                'zona_waktu'          => 'Asia/Makassar',
            ];
        }

        if (is_string($data['rekening_pembayaran'])) {
            $data['rekening_pembayaran'] = json_decode($data['rekening_pembayaran'], true) ?: [];
        }
        if (is_string($data['pengaturan_panjar'])) {
            $data['pengaturan_panjar'] = json_decode($data['pengaturan_panjar'], true) ?: [];
        }

        return $data;
    }
}
