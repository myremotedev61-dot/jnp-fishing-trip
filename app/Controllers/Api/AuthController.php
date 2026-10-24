<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use App\Models\UserTokenModel;
use App\Models\FcmTokenModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseApiController
{
    /**
     * Registrasi Akun Pemancing Baru
     * POST /api/auth/register
     */
    public function register(): ResponseInterface
    {
        $rules = [
            'nama'       => 'required|min_length[3]|max_length[100]',
            'no_telepon' => 'required|min_length[8]|max_length[20]|is_unique[users.no_telepon]',
            'password'   => 'required|min_length[6]',
            'email'      => 'permit_empty|valid_email|is_unique[users.email]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Validasi gagal', 422, $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $tokenModel = new UserTokenModel();

        $nama       = trim($this->request->getVar('nama'));
        $noTelepon  = trim($this->request->getVar('no_telepon'));
        $email      = trim($this->request->getVar('email') ?? '');
        $password   = (string)$this->request->getVar('password');

        $userId = $userModel->insert([
            'nama'          => $nama,
            'email'         => !empty($email) ? $email : null,
            'no_telepon'    => $noTelepon,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role'          => 'pemancing',
            'status_akun'   => 'aktif',
        ]);

        if (!$userId) {
            return $this->respondError('Gagal membuat akun, silakan coba lagi.', 500);
        }

        $user = $userModel->find($userId);
        unset($user['password_hash']);

        $token = $tokenModel->generateToken(
            $userId,
            $this->request->getIPAddress(),
            $this->request->getUserAgent()->getAgentString()
        );

        return $this->respondSuccess([
            'user'  => $user,
            'token' => $token,
        ], 'Registrasi akun berhasil! Selamat datang di Fishing Trip.', 201);
    }

    /**
     * Login Akun
     * POST /api/auth/login
     */
    public function login(): ResponseInterface
    {
        $rules = [
            'login'    => 'required', // no_telepon atau email
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Harap isi nomor telepon/email dan password.', 422, $this->validator->getErrors());
        }

        $loginIdentifier = trim($this->request->getVar('login'));
        $password        = (string)$this->request->getVar('password');

        $userModel = new UserModel();
        $user = $userModel->where('no_telepon', $loginIdentifier)
                          ->orWhere('email', $loginIdentifier)
                          ->first();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return $this->respondError('Nomor telepon/email atau password salah.', 401);
        }

        if ($user['status_akun'] !== 'aktif') {
            return $this->respondError('Akun Anda sedang dinonaktifkan atau ditangguhkan.', 403);
        }

        $tokenModel = new UserTokenModel();
        $token = $tokenModel->generateToken(
            (int)$user['id'],
            $this->request->getIPAddress(),
            $this->request->getUserAgent()->getAgentString()
        );

        unset($user['password_hash']);

        return $this->respondSuccess([
            'user'  => $user,
            'token' => $token,
        ], 'Login berhasil!');
    }

    /**
     * Profil Pengguna
     * GET /api/auth/me
     */
    public function me(): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        unset($user['password_hash']);

        // Ambil ringkasan aktivitas trip milik user
        $db = \Config\Database::connect();
        $totalPendaftaran = $db->table('pendaftaran')->where('user_id', $user['id'])->countAllResults();
        $totalTripDisetujui = $db->table('peserta_trip')
            ->join('pendaftaran', 'pendaftaran.id = peserta_trip.pendaftaran_id')
            ->where('pendaftaran.user_id', $user['id'])
            ->whereIn('peserta_trip.status_persetujuan', ['disetujui', 'dikonfirmasi'])
            ->countAllResults();

        $user['ringkasan'] = [
            'total_pendaftaran'    => $totalPendaftaran,
            'total_trip_disetujui' => $totalTripDisetujui,
        ];

        return $this->respondSuccess($user, 'Data profil pengguna.');
    }

    /**
     * Logout & Revoke Token
     * POST /api/auth/logout
     */
    public function logout(): ResponseInterface
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
            $tokenModel = new UserTokenModel();
            $tokenModel->where('token', $token)->delete();
        }

        return $this->respondSuccess(null, 'Logout berhasil, sesi telah dihapus.');
    }

    /**
     * Update Profil
     * PUT /api/auth/profile
     */
    public function updateProfile(): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $userId = $user['id'];
        $rules = [
            'nama'       => 'required|min_length[3]|max_length[100]',
            'no_telepon' => "required|min_length[8]|max_length[20]|is_unique[users.no_telepon,id,{$userId}]",
            'email'      => "permit_empty|valid_email|is_unique[users.email,id,{$userId}]",
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Validasi gagal', 422, $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $updateData = [
            'nama'       => trim($this->request->getVar('nama')),
            'no_telepon' => trim($this->request->getVar('no_telepon')),
            'email'      => trim($this->request->getVar('email') ?? ''),
        ];

        $userModel->update($userId, $updateData);
        $updatedUser = $userModel->find($userId);
        unset($updatedUser['password_hash']);

        return $this->respondSuccess($updatedUser, 'Profil berhasil diperbarui.');
    }

    /**
     * Ubah Password
     * PUT /api/auth/change-password
     */
    public function changePassword(): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $rules = [
            'password_lama' => 'required',
            'password_baru' => 'required|min_length[6]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Validasi gagal', 422, $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $userData = $userModel->find($user['id']);

        if (!password_verify($this->request->getVar('password_lama'), $userData['password_hash'])) {
            return $this->respondError('Password lama Anda salah.', 400);
        }

        $userModel->update($user['id'], [
            'password_hash' => password_hash($this->request->getVar('password_baru'), PASSWORD_BCRYPT)
        ]);

        return $this->respondSuccess(null, 'Password berhasil diubah.');
    }

    /**
     * Daftarkan FCM Device Token untuk Push Notifications
     * POST /api/auth/fcm-token
     */
    public function registerFcmToken(): ResponseInterface
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->respondError('Tidak terautentikasi', 401);
        }

        $token = trim($this->request->getVar('fcm_token') ?? '');
        if (empty($token)) {
            return $this->respondError('FCM Token wajib diisi.', 422);
        }

        $deviceInfo = trim($this->request->getVar('device_info') ?? '');

        $fcmModel = new FcmTokenModel();
        $existing = $fcmModel->where('token', $token)->first();

        if ($existing) {
            $fcmModel->update($existing['id'], [
                'user_id'     => $user['id'],
                'device_info' => $deviceInfo,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        } else {
            $fcmModel->insert([
                'user_id'     => $user['id'],
                'token'       => $token,
                'device_info' => $deviceInfo,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->respondSuccess(null, 'FCM token berhasil didaftarkan.');
    }
}
