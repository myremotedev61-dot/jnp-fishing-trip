<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // 1. Cek sesi Web Admin
        $session = session();
        if ($session->get('is_admin_logged_in')) {
            return;
        }

        // 2. Cek token API jika diakses via header API
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
            $tokenModel = new \App\Models\UserTokenModel();
            $tokenData = $tokenModel->where('token', $token)->where('expires_at >', date('Y-m-d H:i:s'))->first();
            if ($tokenData) {
                $userModel = new \App\Models\UserModel();
                $u = $userModel->find($tokenData['user_id']);
                if ($u && in_array($u['role'], ['admin', 'owner'])) {
                    $session->set([
                        'admin_id'           => (int)$u['id'],
                        'admin_nama'         => $u['nama'],
                        'admin_role'         => $u['role'],
                        'is_admin_logged_in' => true,
                    ]);
                    return;
                }
            }
        }

        // Jika request menginginkan JSON
        if (strpos($request->getHeaderLine('Accept'), 'application/json') !== false || strpos($request->getUri()->getPath(), 'api/') !== false) {
            $response = service('response');
            return $response->setStatusCode(403)->setJSON([
                'status'  => false,
                'message' => 'Akses ditolak: Anda tidak memiliki hak akses administrator.'
            ]);
        }

        return redirect()->to(base_url('admin/login'))->with('error', 'Silakan login sebagai administrator terlebih dahulu.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
