<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PesertaTripModel;
use App\Models\TripModel;
use App\Models\NotifikasiModel;

class ExpireUnpaid extends BaseCommand
{
    protected $group       = 'FishingTrip';
    protected $name        = 'trip:expire-unpaid';
    protected $description = 'Membatalkan otomatis peserta disetujui yang tidak membayar panjar dalam batas waktu yang ditentukan (default 24 jam).';
    protected $usage       = 'trip:expire-unpaid [options]';
    protected $arguments   = [];
    protected $options     = [
        '--hours' => 'Jumlah jam toleransi pembayaran panjar (default: 24 jam)'
    ];

    public function run(array $params)
    {
        $hours = (int)($params['hours'] ?? 24);
        if ($hours <= 0) $hours = 24;

        CLI::write("===[ CRON FISHING TRIP: CEK KADALUARSA PANJAR ($hours JAM) ]===", 'yellow');

        $db = \Config\Database::connect();
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        $builder = $db->table('peserta_trip pt')
            ->select('pt.id, pt.nama_pemancing, pt.pendaftaran_id, pt.trip_id, p.user_id, t.tujuan')
            ->join('pendaftaran p', 'p.id = pt.pendaftaran_id')
            ->join('trips t', 't.id = pt.trip_id')
            ->where('pt.status_persetujuan', 'menunggu_pembayaran_panjar')
            ->where('pt.tanggal_persetujuan <=', $cutoffTime);

        $expiredList = $builder->get()->getResultArray();
        $cancelledCount = 0;
        $notifModel = new NotifikasiModel();

        foreach ($expiredList as $item) {
            $payCount = $db->table('pembayaran')
                ->where('peserta_trip_id', $item['id'])
                ->whereIn('status_verifikasi', ['terverifikasi', 'menunggu_verifikasi'])
                ->countAllResults();

            if ($payCount === 0) {
                $db->table('peserta_trip')->where('id', $item['id'])->update([
                    'status_persetujuan' => 'dibatalkan',
                    'alasan_penolakan'   => "Batas waktu pembayaran panjar ($hours jam) telah berakhir.",
                    'updated_at'         => date('Y-m-d H:i:s'),
                ]);

                // Kirim notifikasi
                $notifModel->kirim(
                    (int)$item['user_id'],
                    'Pendaftaran Dibatalkan (Batas Waktu Habis)',
                    "Pendaftaran atas nama {$item['nama_pemancing']} pada trip ke {$item['tujuan']} dibatalkan otomatis karena tidak melakukan konfirmasi panjar dalam waktu $hours jam.",
                    'pembatalan',
                    ['peserta_id' => $item['id'], 'trip_id' => $item['trip_id']]
                );

                $cancelledCount++;
                CLI::write("- Peserta ID #{$item['id']} ({$item['nama_pemancing']}) berhasil dibatalkan.", 'red');
            }
        }

        CLI::write("Selesai. Total $cancelledCount peserta dibatalkan otomatis.", 'green');
    }
}
