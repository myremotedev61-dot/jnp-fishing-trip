<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\KapalModel;
use App\Models\TripModel;
use App\Models\PendaftaranModel;
use App\Models\PesertaTripModel;
use App\Models\PembayaranModel;
use App\Models\PengembalianDanaModel;
use App\Models\ApprovalLogModel;
use App\Models\NotifikasiModel;
use App\Models\PengaturanUsahaModel;
use CodeIgniter\Controller;

class AdminController extends BaseController
{
    protected $session;
    protected $db;

    public function __construct()
    {
        $this->session = session();
        $this->db = \Config\Database::connect();
    }

    private function checkAuth()
    {
        if ($this->session->get('is_admin_logged_in')) {
            return;
        }

        // Cek Bearer token jika request menggunakan Authorization header
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
            $tokenModel = new \App\Models\UserTokenModel();
            $tokenData = $tokenModel->where('token', $token)->where('expires_at >', date('Y-m-d H:i:s'))->first();
            if ($tokenData) {
                $userModel = new \App\Models\UserModel();
                $u = $userModel->find($tokenData['user_id']);
                if ($u && in_array($u['role'], ['admin', 'owner'])) {
                    $this->session->set([
                        'admin_id'           => (int)$u['id'],
                        'admin_nama'         => $u['nama'],
                        'admin_role'         => $u['role'],
                        'is_admin_logged_in' => true,
                    ]);
                    return;
                }
            }
        }

        redirect()->to(base_url('admin/login'))->send();
        exit;
    }

    public function login()
    {
        if ($this->session->get('is_admin_logged_in')) {
            return redirect()->to(base_url('admin/dashboard'));
        }
        return view('admin/login');
    }

    public function doLogin()
    {
        $login    = trim($this->request->getPost('login'));
        $password = (string)$this->request->getPost('password');

        $userModel = new UserModel();
        $user = $userModel->where('no_telepon', $login)
                          ->orWhere('email', $login)
                          ->first();

        if (!$user || !in_array($user['role'], ['admin', 'owner'])) {
            return redirect()->back()->with('error', 'Akun administrator tidak ditemukan atau akses ditolak.')->withInput();
        }

        if (!password_verify($password, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Kata sandi salah.')->withInput();
        }

        if ($user['status_akun'] !== 'aktif') {
            return redirect()->back()->with('error', 'Akun sedang dinonaktifkan.')->withInput();
        }

        $this->session->set([
            'admin_id'            => $user['id'],
            'admin_nama'          => $user['nama'],
            'admin_role'          => $user['role'],
            'admin_telepon'       => $user['no_telepon'],
            'is_admin_logged_in'  => true,
        ]);

        return redirect()->to(base_url('admin/dashboard'))->with('success', 'Selamat datang kembali, ' . $user['nama'] . '!');
    }

    public function logout()
    {
        $this->session->destroy();
        return redirect()->to(base_url('admin/login'))->with('success', 'Anda telah berhasil logout.');
    }

    // ========================================================
    // 1. DASHBOARD UTAMA
    // ========================================================
    public function dashboard()
    {
        $this->checkAuth();

        $currentMonth = date('n');
        $currentYear  = date('Y');

        $tripModel        = new TripModel();
        $pesertaModel     = new PesertaTripModel();
        $pendaftaranModel = new PendaftaranModel();
        $pembayaranModel  = new PembayaranModel();

        // 1. Total Trip Bulan Berjalan
        $totalTripBulanIni = $tripModel->where('MONTH(tanggal_trip)', $currentMonth)
                                       ->where('YEAR(tanggal_trip)', $currentYear)
                                       ->countAllResults();

        // 2. Total Peserta Terkonfirmasi
        $totalPesertaKonfirmasi = $pesertaModel->whereIn('status_persetujuan', ['disetujui', 'dikonfirmasi', 'menunggu_pembayaran_panjar'])
                                               ->countAllResults();

        // 3. Pendaftaran Menunggu Persetujuan
        $antreanPeserta = $pesertaModel->where('status_persetujuan', 'menunggu_persetujuan')->countAllResults();

        // 4. Pembayaran Menunggu Verifikasi
        $bayarMenunggu = $pembayaranModel->where('status_verifikasi', 'menunggu_verifikasi')->countAllResults();

        // 5. Total Uang Diterima & Terverifikasi
        $sumBayar = $this->db->table('pembayaran')
            ->selectSum('nominal')
            ->where('status_verifikasi', 'terverifikasi')
            ->get()->getRow();
        $totalDiterima = (float)($sumBayar->nominal ?? 0);

        // 6. Total Refund
        $sumRefund = $this->db->table('pengembalian_dana')
            ->selectSum('nominal_refund')
            ->get()->getRow();
        $totalRefund = (float)($sumRefund->nominal_refund ?? 0);
        $totalBersihDiterima = max(0, $totalDiterima - $totalRefund);

        // 7. Total Sisa Tagihan Seluruh Peserta Aktif
        $pesertaAktif = $pesertaModel->whereIn('status_persetujuan', ['disetujui', 'dikonfirmasi', 'menunggu_pembayaran_panjar'])->findAll();
        $totalSisaTagihan = 0;
        foreach ($pesertaAktif as $p) {
            $fin = $pesertaModel->getFinancialSummary($p['id']);
            $totalSisaTagihan += $fin['sisa_tagihan'];
        }

        // 8. Trip Terdekat yang Akan Segera Berangkat
        $tripMendatang = $tripModel->select('trips.*, kapal.nama_kapal')
            ->join('kapal', 'kapal.id = trips.kapal_id', 'left')
            ->where('trips.tanggal_trip >=', date('Y-m-d'))
            ->orderBy('trips.tanggal_trip', 'ASC')
            ->findAll(5);

        foreach ($tripMendatang as &$tm) {
            $tm['terkonfirmasi'] = $tripModel->getConfirmedParticipantsCount($tm['id']);
            $tm['sisa_kuota'] = max(0, (int)$tm['kuota'] - $tm['terkonfirmasi']);
        }

        // 9. Aktivitas Terbaru (Log Persetujuan & Pendaftaran)
        $logAktivitas = $this->db->table('approval_log')
            ->select('approval_log.*, users.nama as nama_admin')
            ->join('users', 'users.id = approval_log.user_admin_id', 'left')
            ->orderBy('approval_log.id', 'DESC')
            ->limit(7)
            ->get()->getResultArray();

        $data = [
            'title'                  => 'Dashboard Administrator',
            'active_menu'            => 'dashboard',
            'total_trip_bulan_ini'   => $totalTripBulanIni,
            'total_peserta_konfirm'  => $totalPesertaKonfirmasi,
            'antrean_peserta'        => $antreanPeserta,
            'bayar_menunggu'         => $bayarMenunggu,
            'total_diterima'         => $totalBersihDiterima,
            'total_sisa_tagihan'     => $totalSisaTagihan,
            'trip_mendatang'         => $tripMendatang,
            'log_aktivitas'          => $logAktivitas,
        ];

        return view('admin/dashboard', $data);
    }

    // ========================================================
    // 2. MANAJEMEN KAPAL
    // ========================================================
    public function kapal()
    {
        $this->checkAuth();
        $kapalModel = new KapalModel();
        $data = [
            'title'       => 'Manajemen Armada Kapal',
            'active_menu' => 'kapal',
            'kapal'       => $kapalModel->orderBy('id', 'ASC')->findAll(),
        ];
        return view('admin/kapal', $data);
    }

    public function saveKapal()
    {
        $this->checkAuth();
        $id = $this->request->getPost('id');
        $kapalModel = new KapalModel();

        $rules = [
            'kode_kapal' => 'required|min_length[3]|max_length[30]',
            'nama_kapal' => 'required|min_length[3]|max_length[100]',
            'kapasitas'  => 'required|is_natural_no_zero',
            'status'     => 'required|in_list[aktif,nonaktif,pemeliharaan]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'Data kapal tidak valid.')->withInput();
        }

        $saveData = [
            'kode_kapal'           => trim($this->request->getPost('kode_kapal')),
            'nama_kapal'           => trim($this->request->getPost('nama_kapal')),
            'kapasitas'            => (int)$this->request->getPost('kapasitas'),
            'deskripsi'            => trim($this->request->getPost('deskripsi')),
            'fasilitas'            => trim($this->request->getPost('fasilitas')),
            'status'               => $this->request->getPost('status'),
            'catatan_pemeliharaan' => trim($this->request->getPost('catatan_pemeliharaan')),
        ];

        $foto = $this->request->getFile('foto');
        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $newName = $foto->getRandomName();
            $foto->move(FCPATH . 'uploads/kapal/', $newName);
            $saveData['foto'] = $newName;
        }

        if ($id) {
            $kapalModel->update($id, $saveData);
            $msg = 'Data kapal berhasil diperbarui.';
        } else {
            $kapalModel->insert($saveData);
            $msg = 'Kapal baru berhasil ditambahkan.';
        }

        return redirect()->to(base_url('admin/kapal'))->with('success', $msg);
    }

    public function toggleKapalStatus($id)
    {
        $this->checkAuth();
        $kapalModel = new KapalModel();
        $kapal = $kapalModel->find($id);
        if ($kapal) {
            $newStatus = ($kapal['status'] === 'aktif') ? 'nonaktif' : 'aktif';
            $kapalModel->update($id, ['status' => $newStatus]);
        }
        return redirect()->to(base_url('admin/kapal'))->with('success', 'Status kapal berhasil diubah.');
    }

    // ========================================================
    // 3. MANAJEMEN JADWAL TRIP
    // ========================================================
    public function trips()
    {
        $this->checkAuth();
        $tripModel  = new TripModel();
        $kapalModel = new KapalModel();

        $trips = $tripModel->select('trips.*, kapal.nama_kapal, kapal.kapasitas as kapasitas_kapal')
                           ->join('kapal', 'kapal.id = trips.kapal_id', 'left')
                           ->orderBy('trips.tanggal_trip', 'DESC')
                           ->findAll();

        foreach ($trips as &$t) {
            $t['peserta_terkonfirmasi'] = $tripModel->getConfirmedParticipantsCount($t['id']);
            $t['sisa_kuota'] = max(0, (int)$t['kuota'] - $t['peserta_terkonfirmasi']);
        }

        $data = [
            'title'       => 'Manajemen Jadwal Trip',
            'active_menu' => 'trips',
            'trips'       => $trips,
            'kapal_list'  => $kapalModel->where('status', 'aktif')->findAll(),
        ];
        return view('admin/trips', $data);
    }

    public function saveTrip()
    {
        $this->checkAuth();
        $id = $this->request->getPost('id');
        $tripModel = new TripModel();

        $rules = [
            'kapal_id'          => 'required|is_natural_no_zero',
            'tanggal_trip'      => 'required|valid_date[Y-m-d]',
            'jam_kumpul'        => 'required',
            'jam_berangkat'     => 'required',
            'jam_kembali'       => 'required',
            'lokasi_kumpul'     => 'required|min_length[3]',
            'tujuan'            => 'required|min_length[3]',
            'harga_per_peserta' => 'required|numeric|greater_than[0]',
            'nominal_panjar'    => 'required|numeric|greater_than_equal_to[0]',
            'kuota'             => 'required|is_natural_no_zero',
            'status'            => 'required|in_list[draft,dibuka,hampir_penuh,penuh,ditutup,selesai,dibatalkan]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'Input jadwal trip tidak valid: ' . implode(', ', $this->validator->getErrors()))->withInput();
        }

        $kapalId = (int)$this->request->getPost('kapal_id');
        $tanggal = $this->request->getPost('tanggal_trip');

        // Validasi bentrok penggunaan kapal di tanggal yang sama
        $bentrokBuilder = $tripModel->where('kapal_id', $kapalId)
                                    ->where('tanggal_trip', $tanggal)
                                    ->whereNotIn('status', ['dibatalkan']);
        if ($id) {
            $bentrokBuilder->where('id !=', $id);
        }
        if ($bentrokBuilder->countAllResults() > 0) {
            return redirect()->back()->with('error', 'Gagal: Kapal ini sudah dijadwalkan pada tanggal ' . $tanggal . '. Harap pilih tanggal atau kapal lain.')->withInput();
        }

        // Validasi kuota tidak melebihi kapasitas kapal
        $kapalModel = new KapalModel();
        $kapal = $kapalModel->find($kapalId);
        $kuota = (int)$this->request->getPost('kuota');
        if ($kapal && $kuota > (int)$kapal['kapasitas']) {
            return redirect()->back()->with('error', "Gagal: Kuota trip ({$kuota}) tidak boleh melebihi kapasitas fisik kapal ({$kapal['kapasitas']} orang).")->withInput();
        }

        $kodeTrip = $this->request->getPost('kode_trip');
        if (empty($kodeTrip)) {
            $kodeTrip = 'TRIP-' . date('Ym', strtotime($tanggal)) . '-' . strtoupper(substr(uniqid(), -4));
        }

        $batasDaftar = $this->request->getPost('batas_pendaftaran') ?: ($tanggal . ' ' . $this->request->getPost('jam_kumpul'));
        $batasBayar  = $this->request->getPost('batas_pembayaran') ?: ($tanggal . ' ' . $this->request->getPost('jam_berangkat'));

        $saveData = [
            'kode_trip'            => $kodeTrip,
            'kapal_id'             => $kapalId,
            'tanggal_trip'         => $tanggal,
            'jam_kumpul'           => $this->request->getPost('jam_kumpul'),
            'jam_berangkat'        => $this->request->getPost('jam_berangkat'),
            'jam_kembali'          => $this->request->getPost('jam_kembali'),
            'lokasi_kumpul'        => trim($this->request->getPost('lokasi_kumpul')),
            'tujuan'               => trim($this->request->getPost('tujuan')),
            'harga_per_peserta'    => (float)$this->request->getPost('harga_per_peserta'),
            'nominal_panjar'       => (float)$this->request->getPost('nominal_panjar'),
            'kuota'                => $kuota,
            'batas_pendaftaran'    => $batasDaftar,
            'batas_pembayaran'     => $batasBayar,
            'fasilitas'            => trim($this->request->getPost('fasilitas')),
            'ketentuan'            => trim($this->request->getPost('ketentuan')),
            'deskripsi'            => trim($this->request->getPost('deskripsi')),
            'kebijakan_pembatalan' => trim($this->request->getPost('kebijakan_pembatalan')),
            'status'               => $this->request->getPost('status'),
        ];

        if ($id) {
            $tripModel->update($id, $saveData);
            $msg = 'Jadwal trip berhasil diperbarui.';
        } else {
            $tripModel->insert($saveData);
            $msg = 'Jadwal trip baru berhasil dipublikasikan.';
        }

        return redirect()->to(base_url('admin/trips'))->with('success', $msg);
    }

    // ========================================================
    // 4. TABEL DATA PESERTA (SESUAI 7 KOLOM SPESIFIKASI)
    // ========================================================
    public function peserta()
    {
        $this->checkAuth();

        $tripId = $this->request->getGet('trip_id');
        $bulan  = $this->request->getGet('bulan');
        $tahun  = $this->request->getGet('tahun');
        $statusPersetujuan = $this->request->getGet('status_persetujuan');
        $statusBayar = $this->request->getGet('status_bayar');
        $q      = trim($this->request->getGet('q') ?? '');

        $pesertaModel = new PesertaTripModel();
        $tripModel    = new TripModel();

        $builder = $pesertaModel->select('peserta_trip.*, pendaftaran.kode_pendaftaran, trips.kode_trip, trips.tujuan, trips.tanggal_trip, trips.harga_per_peserta, trips.nominal_panjar, kapal.nama_kapal')
            ->join('pendaftaran', 'pendaftaran.id = peserta_trip.pendaftaran_id')
            ->join('trips', 'trips.id = peserta_trip.trip_id')
            ->join('kapal', 'kapal.id = trips.kapal_id', 'left');

        if ($tripId) {
            $builder->where('peserta_trip.trip_id', $tripId);
        }
        if ($bulan) {
            $builder->where('MONTH(trips.tanggal_trip)', $bulan);
        }
        if ($tahun) {
            $builder->where('YEAR(trips.tanggal_trip)', $tahun);
        }
        if ($statusPersetujuan) {
            $builder->where('peserta_trip.status_persetujuan', $statusPersetujuan);
        }
        if ($q) {
            $builder->groupStart()
                    ->like('peserta_trip.nama_pemancing', $q)
                    ->orLike('peserta_trip.no_telepon', $q)
                    ->orLike('pendaftaran.kode_pendaftaran', $q)
                    ->groupEnd();
        }

        $pesertaList = $builder->orderBy('trips.tanggal_trip', 'DESC')
                               ->orderBy('peserta_trip.id', 'DESC')
                               ->findAll();

        $filteredPeserta = [];
        $hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulanIndo = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        foreach ($pesertaList as $p) {
            $fin = $pesertaModel->getFinancialSummary($p['id']);
            $p['keuangan'] = $fin;

            // Filter status bayar jika dipilih
            if ($statusBayar && strtolower($fin['status_pembayaran']) !== strtolower($statusBayar)) {
                continue;
            }

            // Format tanggal Indonesia: Hari, Tanggal Bulan Tahun
            $ts = strtotime($p['tanggal_trip']);
            $p['tanggal_formatted'] = $hariIndo[date('w', $ts)] . ', ' . date('j', $ts) . ' ' . $bulanIndo[(int)date('n', $ts)] . ' ' . date('Y', $ts);

            $filteredPeserta[] = $p;
        }

        $data = [
            'title'             => 'Tabel Data Peserta Trip',
            'active_menu'       => 'peserta',
            'peserta'           => $filteredPeserta,
            'trips'             => $tripModel->orderBy('tanggal_trip', 'DESC')->findAll(),
            'filter_trip'       => $tripId,
            'filter_bulan'      => $bulan,
            'filter_tahun'      => $tahun,
            'filter_persetujuan'=> $statusPersetujuan,
            'filter_bayar'      => $statusBayar,
            'filter_q'          => $q,
        ];

        return view('admin/peserta', $data);
    }

    // ========================================================
    // 5. ANTREAN PERSETUJUAN DATA PESERTA (APPROVAL QUEUE)
    // ========================================================
    public function persetujuan()
    {
        $this->checkAuth();

        $pesertaModel = new PesertaTripModel();
        $tripModel    = new TripModel();

        $antrean = $pesertaModel->select('peserta_trip.*, pendaftaran.kode_pendaftaran, pendaftaran.tanggal_daftar, pendaftaran.jumlah_diajukan, pendaftaran.user_id, trips.kode_trip, trips.tujuan, trips.tanggal_trip, trips.kuota, trips.nominal_panjar, kapal.nama_kapal')
            ->join('pendaftaran', 'pendaftaran.id = peserta_trip.pendaftaran_id')
            ->join('trips', 'trips.id = peserta_trip.trip_id')
            ->join('kapal', 'kapal.id = trips.kapal_id', 'left')
            ->where('peserta_trip.status_persetujuan', 'menunggu_persetujuan')
            ->orderBy('pendaftaran.tanggal_daftar', 'ASC')
            ->findAll();

        foreach ($antrean as &$item) {
            $confirmed = $tripModel->getConfirmedParticipantsCount($item['trip_id']);
            $item['sisa_kuota'] = max(0, (int)$item['kuota'] - $confirmed);
        }

        $data = [
            'title'       => 'Antrean Persetujuan Data Peserta',
            'active_menu' => 'persetujuan',
            'antrean'     => $antrean,
        ];

        return view('admin/persetujuan', $data);
    }

    /**
     * Setujui Peserta dengan Transaksi & Penguncian Kuota (Pessimistic Lock)
     */
    public function setujuiPeserta($id)
    {
        $this->checkAuth();
        $adminId = (int)($this->session->get('admin_id') ?: 1);

        $pesertaModel = new PesertaTripModel();
        $peserta = $pesertaModel->find($id);

        if (!$peserta) {
            return redirect()->back()->with('error', 'Data peserta tidak ditemukan.');
        }

        if ($peserta['status_persetujuan'] !== 'menunggu_persetujuan') {
            return redirect()->back()->with('error', 'Status peserta ini sudah tidak dalam antrean persetujuan.');
        }

        $tripId = (int)$peserta['trip_id'];

        // START TRANSACTION
        $this->db->transStart();

        // Lock row trip for update
        $this->db->query('SELECT * FROM trips WHERE id = ? FOR UPDATE', [$tripId]);

        $tripData = (new TripModel())->find($tripId);
        $confirmedCount = (new TripModel())->getConfirmedParticipantsCount($tripId);
        $sisaKuota = (int)$tripData['kuota'] - $confirmedCount;

        if ($sisaKuota < 1) {
            $this->db->transRollback();
            return redirect()->back()->with('error', 'Gagal menyetujui: Kuota trip telah penuh. Peserta dapat dimasukkan ke daftar tunggu.');
        }

        // Tentukan status selanjutnya: jika ada ketentuan panjar -> menunggu_pembayaran_panjar, else -> disetujui
        $statusBaru = ((float)$tripData['nominal_panjar'] > 0) ? 'menunggu_pembayaran_panjar' : 'disetujui';

        $pesertaModel->update($id, [
            'status_persetujuan' => $statusBaru,
            'disetujui_oleh'     => $adminId,
            'tanggal_persetujuan'=> date('Y-m-d H:i:s'),
        ]);

        // Catat Audit Log
        $logModel = new ApprovalLogModel();
        $logModel->recordLog(
            $adminId,
            'peserta_trip',
            $id,
            'setujui_peserta',
            'menunggu_persetujuan',
            $statusBaru,
            'Data peserta dan kontak telah diverifikasi oleh administrator.'
        );

        // Notifikasi ke Pemancing
        $pendaftaran = (new PendaftaranModel())->find($peserta['pendaftaran_id']);
        if ($pendaftaran) {
            $notifModel = new NotifikasiModel();
            $notifModel->kirim(
                (int)$pendaftaran['user_id'],
                'Pendaftaran Peserta Disetujui!',
                "Peserta {$peserta['nama_pemancing']} untuk trip ke {$tripData['tujuan']} telah disetujui. Silakan lakukan pembayaran panjar/pelunasan.",
                'pendaftaran',
                ['peserta_id' => $id, 'trip_id' => $tripId]
            );
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat menyetujui peserta.');
        }

        return redirect()->to(base_url('admin/persetujuan'))->with('success', "Peserta {$peserta['nama_pemancing']} berhasil disetujui!");
    }

    /**
     * Tolak Peserta dengan Alasan Wajib
     */
    public function tolakPeserta($id)
    {
        $this->checkAuth();
        $adminId = (int)($this->session->get('admin_id') ?: 1);
        $alasan  = trim($this->request->getPost('alasan') ?? '');

        if (empty($alasan)) {
            return redirect()->back()->with('error', 'Alasan penolakan wajib diisi agar peserta dapat memperbaikinya.');
        }

        $pesertaModel = new PesertaTripModel();
        $peserta = $pesertaModel->find($id);

        if (!$peserta) {
            return redirect()->back()->with('error', 'Data peserta tidak ditemukan.');
        }

        $this->db->transStart();

        $pesertaModel->update($id, [
            'status_persetujuan' => 'ditolak',
            'alasan_penolakan'   => $alasan,
            'disetujui_oleh'     => $adminId,
            'tanggal_persetujuan'=> date('Y-m-d H:i:s'),
        ]);

        // Catat Audit Log
        $logModel = new ApprovalLogModel();
        $logModel->recordLog(
            $adminId,
            'peserta_trip',
            $id,
            'tolak_peserta',
            $peserta['status_persetujuan'],
            'ditolak',
            $alasan
        );

        // Notifikasi ke Pemancing
        $pendaftaran = (new PendaftaranModel())->find($peserta['pendaftaran_id']);
        if ($pendaftaran) {
            $notifModel = new NotifikasiModel();
            $notifModel->kirim(
                (int)$pendaftaran['user_id'],
                'Pengajuan Peserta Ditolak',
                "Peserta {$peserta['nama_pemancing']} ditolak dengan alasan: \"{$alasan}\". Anda dapat memperbarui data pada aplikasi.",
                'pendaftaran',
                ['peserta_id' => $id]
            );
        }

        $this->db->transComplete();

        return redirect()->to(base_url('admin/persetujuan'))->with('success', "Peserta {$peserta['nama_pemancing']} telah ditolak.");
    }

    // ========================================================
    // 6. VERIFIKASI PEMBAYARAN BUKTI TRANSFER
    // ========================================================
    public function pembayaran()
    {
        $this->checkAuth();

        $statusVerifikasi = $this->request->getGet('status') ?: 'menunggu_verifikasi';
        $pembayaranModel  = new PembayaranModel();
        $pesertaModel     = new PesertaTripModel();

        $builder = $pembayaranModel->select('pembayaran.*, peserta_trip.nama_pemancing, peserta_trip.no_telepon, peserta_trip.harga_final, trips.kode_trip, trips.tujuan, trips.tanggal_trip, users.nama as nama_admin_verifikator')
            ->join('peserta_trip', 'peserta_trip.id = pembayaran.peserta_trip_id')
            ->join('trips', 'trips.id = peserta_trip.trip_id')
            ->join('users', 'users.id = pembayaran.diverifikasi_oleh', 'left');

        if ($statusVerifikasi !== 'semua') {
            $builder->where('pembayaran.status_verifikasi', $statusVerifikasi);
        }

        $pembayaranList = $builder->orderBy('pembayaran.id', 'DESC')->findAll();

        foreach ($pembayaranList as &$p) {
            $p['keuangan'] = $pesertaModel->getFinancialSummary($p['peserta_trip_id']);
        }

        $data = [
            'title'             => 'Verifikasi Pembayaran & Bukti Transfer',
            'active_menu'       => 'pembayaran',
            'pembayaran_list'   => $pembayaranList,
            'filter_status'     => $statusVerifikasi,
        ];

        return view('admin/pembayaran', $data);
    }

    public function verifikasiPembayaranAksi($id)
    {
        $this->checkAuth();
        $adminId = (int)($this->session->get('admin_id') ?: 1);
        $catatan = trim($this->request->getPost('catatan_internal') ?? '');

        $pembayaranModel = new PembayaranModel();
        $bayar = $pembayaranModel->find($id);

        if (!$bayar) {
            return redirect()->back()->with('error', 'Data pembayaran tidak ditemukan.');
        }

        if ($bayar['status_verifikasi'] === 'terverifikasi') {
            return redirect()->back()->with('error', 'Transaksi ini sudah pernah diverifikasi sebelumnya.');
        }

        $this->db->transStart();

        $pembayaranModel->update($id, [
            'status_verifikasi'  => 'terverifikasi',
            'diverifikasi_oleh'  => $adminId,
            'tanggal_verifikasi' => date('Y-m-d H:i:s'),
            'catatan_internal'   => !empty($catatan) ? $catatan : null,
        ]);

        // Cek status keuangan peserta setelah verifikasi
        $pesertaModel = new PesertaTripModel();
        $fin = $pesertaModel->getFinancialSummary($bayar['peserta_trip_id']);
        $peserta = $pesertaModel->find($bayar['peserta_trip_id']);

        // Jika lunas atau panjar, ubah status persetujuan peserta jika masih menunggu_pembayaran_panjar
        if ($peserta['status_persetujuan'] === 'menunggu_pembayaran_panjar') {
            $pesertaModel->update($bayar['peserta_trip_id'], [
                'status_persetujuan' => 'dikonfirmasi'
            ]);
        }

        // Catat Audit Log
        $logModel = new ApprovalLogModel();
        $logModel->recordLog(
            $adminId,
            'pembayaran',
            $id,
            'verifikasi_pembayaran',
            $bayar['status_verifikasi'],
            'terverifikasi',
            'Pembayaran Rp ' . number_format($bayar['nominal'], 0, ',', '.') . ' telah dicek dan valid pada mutasi rekening.'
        );

        // Notifikasi ke Pemancing
        $pendaftaran = (new PendaftaranModel())->find($peserta['pendaftaran_id']);
        if ($pendaftaran) {
            $notifModel = new NotifikasiModel();
            $notifModel->kirim(
                (int)$pendaftaran['user_id'],
                'Pembayaran Terverifikasi!',
                "Pembayaran sebesar Rp " . number_format($bayar['nominal'], 0, ',', '.') . " untuk peserta {$peserta['nama_pemancing']} telah diverifikasi oleh pemilik usaha. Status peserta saat ini: {$fin['status_pembayaran']}.",
                'pembayaran',
                ['pembayaran_id' => $id, 'peserta_id' => $peserta['id']]
            );
        }

        $this->db->transComplete();

        return redirect()->to(base_url('admin/pembayaran'))->with('success', 'Pembayaran berhasil diverifikasi!');
    }

    public function tolakPembayaranAksi($id)
    {
        $this->checkAuth();
        $adminId = (int)($this->session->get('admin_id') ?: 1);
        $alasan  = trim($this->request->getPost('alasan_penolakan') ?? '');

        if (empty($alasan)) {
            return redirect()->back()->with('error', 'Alasan penolakan pembayaran wajib diisi.');
        }

        $pembayaranModel = new PembayaranModel();
        $bayar = $pembayaranModel->find($id);

        if (!$bayar) {
            return redirect()->back()->with('error', 'Data transaksi tidak ditemukan.');
        }

        $this->db->transStart();

        $pembayaranModel->update($id, [
            'status_verifikasi'  => 'ditolak',
            'alasan_penolakan'   => $alasan,
            'diverifikasi_oleh'  => $adminId,
            'tanggal_verifikasi' => date('Y-m-d H:i:s'),
        ]);

        // Catat Log
        $logModel = new ApprovalLogModel();
        $logModel->recordLog(
            $adminId,
            'pembayaran',
            $id,
            'tolak_pembayaran',
            $bayar['status_verifikasi'],
            'ditolak',
            $alasan
        );

        // Notifikasi ke Pemancing
        $pesertaModel = new PesertaTripModel();
        $peserta = $pesertaModel->find($bayar['peserta_trip_id']);
        if ($peserta) {
            $pendaftaran = (new PendaftaranModel())->find($peserta['pendaftaran_id']);
            if ($pendaftaran) {
                $notifModel = new NotifikasiModel();
                $notifModel->kirim(
                    (int)$pendaftaran['user_id'],
                    'Pembayaran Ditolak',
                    "Bukti pembayaran untuk peserta {$peserta['nama_pemancing']} ditolak. Alasan: \"{$alasan}\". Silakan unggah bukti yang benar.",
                    'pembayaran',
                    ['pembayaran_id' => $id]
                );
            }
        }

        $this->db->transComplete();

        return redirect()->to(base_url('admin/pembayaran'))->with('success', 'Transaksi pembayaran telah ditolak.');
    }

    public function viewBuktiAdmin($id)
    {
        $this->checkAuth();
        $pembayaranModel = new PembayaranModel();
        $bayar = $pembayaranModel->find($id);

        if (!$bayar || empty($bayar['bukti_pembayaran'])) {
            return $this->response->setStatusCode(404)->setBody('Bukti tidak ditemukan');
        }

        $path = WRITEPATH . 'uploads/bukti_pembayaran/' . $bayar['bukti_pembayaran'];
        if (!file_exists($path)) {
            return $this->response->setStatusCode(404)->setBody('File fisik tidak ditemukan di server.');
        }

        $mime = mime_content_type($path);
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($path) . '"')
            ->setBody(file_get_contents($path));
    }

    // ========================================================
    // 7. PENGELOLAAN PEMBATALAN & REFUND
    // ========================================================
    public function pembatalan()
    {
        $this->checkAuth();

        $pesertaModel = new PesertaTripModel();
        $refundModel  = new PengembalianDanaModel();

        $pesertaBatal = $pesertaModel->select('peserta_trip.*, pendaftaran.kode_pendaftaran, trips.kode_trip, trips.tujuan, trips.tanggal_trip, trips.harga_per_peserta')
            ->join('pendaftaran', 'pendaftaran.id = peserta_trip.pendaftaran_id')
            ->join('trips', 'trips.id = peserta_trip.trip_id')
            ->where('peserta_trip.status_persetujuan', 'dibatalkan')
            ->findAll();

        foreach ($pesertaBatal as &$pb) {
            $pb['keuangan'] = $pesertaModel->getFinancialSummary($pb['id']);
        }

        $refundList = $refundModel->select('pengembalian_dana.*, peserta_trip.nama_pemancing, pembayaran.kode_pembayaran, users.nama as nama_admin')
            ->join('peserta_trip', 'peserta_trip.id = pengembalian_dana.peserta_trip_id')
            ->join('pembayaran', 'pembayaran.id = pengembalian_dana.pembayaran_id')
            ->join('users', 'users.id = pengembalian_dana.diproses_oleh', 'left')
            ->orderBy('pengembalian_dana.id', 'DESC')
            ->findAll();

        $data = [
            'title'         => 'Pengelolaan Pembatalan & Pengembalian Dana (Refund)',
            'active_menu'   => 'pembatalan',
            'peserta_batal' => $pesertaBatal,
            'refund_list'   => $refundList,
        ];

        return view('admin/pembatalan', $data);
    }

    public function prosesBatalPeserta($pesertaId)
    {
        $this->checkAuth();
        $adminId = (int)($this->session->get('admin_id') ?: 1);
        $alasan  = trim($this->request->getPost('alasan') ?? 'Pembatalan oleh admin');

        $pesertaModel = new PesertaTripModel();
        $peserta = $pesertaModel->find($pesertaId);

        if (!$peserta) {
            return redirect()->back()->with('error', 'Peserta tidak ditemukan.');
        }

        $this->db->transStart();

        $pesertaModel->update($pesertaId, [
            'status_persetujuan' => 'dibatalkan',
            'alasan_penolakan'   => 'Dibatalkan: ' . $alasan,
        ]);

        $logModel = new ApprovalLogModel();
        $logModel->recordLog($adminId, 'peserta_trip', $pesertaId, 'batalkan_peserta', $peserta['status_persetujuan'], 'dibatalkan', $alasan);

        $this->db->transComplete();

        return redirect()->to(base_url('admin/peserta'))->with('success', "Peserta {$peserta['nama_pemancing']} telah dibatalkan dan kuota trip otomatis diperbarui.");
    }

    public function prosesRefund()
    {
        $this->checkAuth();
        $adminId = (int)($this->session->get('admin_id') ?: 1);

        $pembayaranId = (int)$this->request->getPost('pembayaran_id');
        $pesertaId    = (int)$this->request->getPost('peserta_trip_id');
        $nominal      = (float)$this->request->getPost('nominal_refund');
        $alasan       = trim($this->request->getPost('alasan_refund'));

        if ($nominal <= 0 || empty($alasan)) {
            return redirect()->back()->with('error', 'Nominal refund dan alasan wajib diisi.');
        }

        $this->db->transStart();

        $refundModel = new PengembalianDanaModel();
        $refundModel->insert([
            'pembayaran_id'  => $pembayaranId,
            'peserta_trip_id'=> $pesertaId,
            'nominal_refund' => $nominal,
            'alasan_refund'  => $alasan,
            'diproses_oleh'  => $adminId,
            'tanggal_refund' => date('Y-m-d H:i:s'),
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $pembayaranModel = new PembayaranModel();
        $pembayaranModel->update($pembayaranId, ['status_verifikasi' => 'dikembalikan']);

        $this->db->transComplete();

        return redirect()->to(base_url('admin/pembatalan'))->with('success', 'Pengembalian dana (refund) berhasil dicatat.');
    }

    // ========================================================
    // 8. LAPORAN & REKAPITULASI KEUANGAN
    // ========================================================
    public function laporan()
    {
        $this->checkAuth();

        $bulan  = $this->request->getGet('bulan') ?: date('n');
        $tahun  = $this->request->getGet('tahun') ?: date('Y');
        $tripId = $this->request->getGet('trip_id');

        $tripModel    = new TripModel();
        $pesertaModel = new PesertaTripModel();

        $builder = $tripModel->select('trips.*, kapal.nama_kapal')
                             ->join('kapal', 'kapal.id = trips.kapal_id', 'left')
                             ->where('YEAR(trips.tanggal_trip)', $tahun);

        if ($bulan && $bulan !== 'semua') {
            $builder->where('MONTH(trips.tanggal_trip)', $bulan);
        }
        if ($tripId) {
            $builder->where('trips.id', $tripId);
        }

        $trips = $builder->orderBy('trips.tanggal_trip', 'DESC')->findAll();

        $grandTotalPesertaKonfirm = 0;
        $grandTotalPenerimaan = 0;
        $grandTotalSisaTagihan = 0;

        foreach ($trips as &$t) {
            $pesertaList = $pesertaModel->where('trip_id', $t['id'])->findAll();
            $t['total_pendaftar'] = count($pesertaList);
            $konfirm = 0;
            $uangMasuk = 0;
            $sisaTagihan = 0;

            foreach ($pesertaList as $p) {
                if (in_array($p['status_persetujuan'], ['disetujui', 'dikonfirmasi', 'menunggu_pembayaran_panjar'])) {
                    $konfirm++;
                }
                $fin = $pesertaModel->getFinancialSummary($p['id']);
                $uangMasuk += $fin['pembayaran_bersih'];
                $sisaTagihan += $fin['sisa_tagihan'];
            }

            $t['terkonfirmasi'] = $konfirm;
            $t['uang_masuk']    = $uangMasuk;
            $t['sisa_tagihan']  = $sisaTagihan;

            $grandTotalPesertaKonfirm += $konfirm;
            $grandTotalPenerimaan     += $uangMasuk;
            $grandTotalSisaTagihan    += $sisaTagihan;
        }

        $data = [
            'title'               => 'Laporan Peserta & Pendapatan Kas',
            'active_menu'         => 'laporan',
            'trips'               => $trips,
            'all_trips'           => $tripModel->findAll(),
            'filter_bulan'        => $bulan,
            'filter_tahun'        => $tahun,
            'filter_trip'         => $tripId,
            'total_konfirm'       => $grandTotalPesertaKonfirm,
            'total_penerimaan'    => $grandTotalPenerimaan,
            'total_sisa_tagihan'  => $grandTotalSisaTagihan,
        ];

        return view('admin/laporan', $data);
    }

    // ========================================================
    // 9. PENGATURAN USAHA
    // ========================================================
    public function pengaturan()
    {
        $this->checkAuth();
        $pengaturanModel = new PengaturanUsahaModel();
        $data = [
            'title'       => 'Pengaturan Usaha & Rekening Bank',
            'active_menu' => 'pengaturan',
            'setting'     => $pengaturanModel->getPengaturan(),
        ];
        return view('admin/pengaturan', $data);
    }

    public function savePengaturan()
    {
        $this->checkAuth();
        $pengaturanModel = new PengaturanUsahaModel();

        $namaUsaha  = trim($this->request->getPost('nama_usaha'));
        $noWa       = trim($this->request->getPost('no_whatsapp'));
        $alamat     = trim($this->request->getPost('alamat'));
        $zonaWaktu  = trim($this->request->getPost('zona_waktu') ?? 'Asia/Makassar');
        $minPanjar  = (int)$this->request->getPost('minimal_panjar_persen');
        $kebijakan  = trim($this->request->getPost('kebijakan_refund'));

        // Parse rekening bank
        $bankNames = $this->request->getPost('bank_nama') ?? [];
        $bankNums  = $this->request->getPost('bank_nomor') ?? [];
        $bankOwners= $this->request->getPost('bank_pemilik') ?? [];

        $rekeningList = [];
        for ($i = 0; $i < count($bankNames); $i++) {
            if (!empty($bankNames[$i]) && !empty($bankNums[$i])) {
                $rekeningList[] = [
                    'bank'    => trim($bankNames[$i]),
                    'nomor'   => trim($bankNums[$i]),
                    'pemilik' => trim($bankOwners[$i] ?? ''),
                ];
            }
        }

        $pengaturanPanjar = [
            'minimal_panjar_persen' => $minPanjar,
            'batas_waktu_jam'       => 24,
            'kebijakan_refund'      => $kebijakan,
        ];

        $updateData = [
            'nama_usaha'          => $namaUsaha,
            'no_whatsapp'         => $noWa,
            'alamat'              => $alamat,
            'rekening_pembayaran' => json_encode($rekeningList),
            'pengaturan_panjar'   => json_encode($pengaturanPanjar),
            'zona_waktu'          => $zonaWaktu,
        ];

        $pengaturanModel->update(1, $updateData);
        return redirect()->to(base_url('admin/pengaturan'))->with('success', 'Pengaturan usaha berhasil diperbarui.');
    }
}
