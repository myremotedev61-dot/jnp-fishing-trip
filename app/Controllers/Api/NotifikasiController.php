<?php

namespace App\Controllers\Api;

use App\Models\NotifikasiModel;
use CodeIgniter\HTTP\ResponseInterface;

class NotifikasiController extends BaseApiController
{
    /**
     * Daftar Notifikasi Milik Pengguna
     * GET /api/notifikasi
     */
    public function index(): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $notifModel = new NotifikasiModel();
        $notifs = $notifModel->where('user_id', $user['id'])
                             ->orderBy('id', 'DESC')
                             ->findAll(50);

        foreach ($notifs as &$n) {
            $n['data_referensi'] = $n['data_referensi'] ? json_decode($n['data_referensi'], true) : null;
        }

        $unreadCount = $notifModel->where('user_id', $user['id'])->where('dibaca_pada', null)->countAllResults();

        return $this->respondSuccess([
            'belum_dibaca' => $unreadCount,
            'notifikasi'   => $notifs,
        ], 'Daftar notifikasi akun.');
    }

    /**
     * Tandai Notifikasi Sebagai Telah Dibaca
     * PUT /api/notifikasi/{id}/read
     */
    public function markAsRead($id = null): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $notifModel = new NotifikasiModel();
        $notif = $notifModel->where('id', $id)->where('user_id', $user['id'])->first();

        if ($notif) {
            $notifModel->update($id, ['dibaca_pada' => date('Y-m-d H:i:s')]);
        }

        return $this->respondSuccess(null, 'Notifikasi ditandai telah dibaca.');
    }

    /**
     * Tandai Semua Notifikasi Telah Dibaca
     * PUT /api/notifikasi/read-all
     */
    public function markAllAsRead(): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $notifModel = new NotifikasiModel();
        $notifModel->where('user_id', $user['id'])
                   ->where('dibaca_pada', null)
                   ->set(['dibaca_pada' => date('Y-m-d H:i:s')])
                   ->update();

        return $this->respondSuccess(null, 'Semua notifikasi ditandai telah dibaca.');
    }
}
