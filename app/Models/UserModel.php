<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama',
        'email',
        'no_telepon',
        'password_hash',
        'role',
        'status_akun',
        'foto_profil'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nama'       => 'required|min_length[3]|max_length[100]',
        'no_telepon' => 'required|min_length[8]|max_length[25]|is_unique[users.no_telepon,id,{id}]',
    ];
}
