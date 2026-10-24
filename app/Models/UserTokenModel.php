<?php

namespace App\Models;

use CodeIgniter\Model;

class UserTokenModel extends Model
{
    protected $table            = 'user_tokens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'token',
        'ip_address',
        'user_agent',
        'expires_at',
        'created_at'
    ];

    protected $useTimestamps = false;

    public function generateToken(int $userId, string $ip = '', string $userAgent = '', int $expiryDays = 30): string
    {
        $token = bin2hex(random_bytes(32)) . hash('sha256', microtime() . $userId);
        $this->insert([
            'user_id'    => $userId,
            'token'      => $token,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiryDays} days")),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $token;
    }

    public function validateToken(string $token): ?array
    {
        $record = $this->where('token', $token)
                       ->where('expires_at >=', date('Y-m-d H:i:s'))
                       ->first();
        return $record;
    }
}
