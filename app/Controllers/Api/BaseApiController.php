<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class BaseApiController extends BaseController
{
    /**
     * Dapatkan user yang sedang terotentikasi dari filter
     */
    protected function getAuthUser(): ?array
    {
        return \App\Filters\ApiAuthFilter::$currentUser;
    }

    /**
     * Standar JSON Response Sukses
     */
    protected function respondSuccess($data = null, string $message = 'Berhasil', int $code = 200): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON([
            'status'  => true,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    /**
     * Standar JSON Response Error
     */
    protected function respondError(string $message = 'Terjadi kesalahan', int $code = 400, $errors = null): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON([
            'status'  => false,
            'message' => $message,
            'errors'  => $errors,
        ]);
    }
}
