<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserTokenModel;
use App\Models\UserModel;

class ApiAuthFilter implements FilterInterface
{
    public static ?array $currentUser = null;

    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $response = service('response');
            return $response->setStatusCode(401)->setJSON([
                'status'  => false,
                'message' => 'Token otentikasi tidak ditemukan. Harap sertakan header Authorization: Bearer <token>.'
            ]);
        }

        $token = trim($matches[1]);
        $tokenModel = new UserTokenModel();
        $tokenData = $tokenModel->validateToken($token);

        if (!$tokenData) {
            $response = service('response');
            return $response->setStatusCode(401)->setJSON([
                'status'  => false,
                'message' => 'Sesi tidak valid atau telah kedaluwarsa. Silakan login kembali.'
            ]);
        }

        $userModel = new UserModel();
        $user = $userModel->find($tokenData['user_id']);

        if (!$user || $user['status_akun'] !== 'aktif') {
            $response = service('response');
            return $response->setStatusCode(403)->setJSON([
                'status'  => false,
                'message' => 'Akun dinonaktifkan atau tidak ditemukan.'
            ]);
        }

        // Simpan user di static container untuk controller
        self::$currentUser = $user;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}
