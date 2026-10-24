<?php

namespace App\Models;

use CodeIgniter\Model;

class ApprovalLogModel extends Model
{
    protected $table            = 'approval_log';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_admin_id',
        'entitas',
        'entitas_id',
        'aksi',
        'status_sebelumnya',
        'status_sesudahnya',
        'alasan',
        'created_at'
    ];

    protected $useTimestamps = false;

    public function recordLog(int $adminId, string $entitas, int $entitasId, string $aksi, ?string $sebelum, string $sesudah, ?string $alasan = null): int
    {
        return $this->insert([
            'user_admin_id'    => $adminId,
            'entitas'          => $entitas,
            'entitas_id'       => $entitasId,
            'aksi'             => $aksi,
            'status_sebelumnya'=> $sebelum,
            'status_sesudahnya'=> $sesudah,
            'alasan'           => $alasan,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
    }
}
