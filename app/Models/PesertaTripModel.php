<?php

namespace App\Models;

use CodeIgniter\Model;

class PesertaTripModel extends Model
{
    protected $table            = 'peserta_trip';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'pendaftaran_id',
        'trip_id',
        'nama_pemancing',
        'no_telepon',
        'status_persetujuan',
        'alasan_penolakan',
        'harga_final',
        'disetujui_oleh',
        'tanggal_persetujuan'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Hitung ringkasan keuangan riil peserta (total terverifikasi, refund, sisa tagihan, dan status)
     */
    public function getFinancialSummary(int $pesertaTripId): array
    {
        $peserta = $this->find($pesertaTripId);
        if (!$peserta) {
            return [
                'harga_total'              => 0,
                'total_terverifikasi'      => 0,
                'total_refund'             => 0,
                'pembayaran_bersih'        => 0,
                'sisa_tagihan'             => 0,
                'status_pembayaran'        => 'Belum Bayar',
                'pembayaran_menunggu'      => 0,
            ];
        }

        $db = \Config\Database::connect();

        // 1. Total pembayaran terverifikasi
        $resBayar = $db->table('pembayaran')
            ->selectSum('nominal')
            ->where('peserta_trip_id', $pesertaTripId)
            ->where('status_verifikasi', 'terverifikasi')
            ->get()->getRow();
        $totalTerverifikasi = (float)($resBayar->nominal ?? 0);

        // 2. Pembayaran menunggu verifikasi
        $resMenunggu = $db->table('pembayaran')
            ->selectSum('nominal')
            ->where('peserta_trip_id', $pesertaTripId)
            ->where('status_verifikasi', 'menunggu_verifikasi')
            ->get()->getRow();
        $pembayaranMenunggu = (float)($resMenunggu->nominal ?? 0);

        // 3. Total refund
        $resRefund = $db->table('pengembalian_dana')
            ->selectSum('nominal_refund')
            ->where('peserta_trip_id', $pesertaTripId)
            ->get()->getRow();
        $totalRefund = (float)($resRefund->nominal_refund ?? 0);

        $hargaTotal = (float)$peserta['harga_final'];
        $pembayaranBersih = max(0, $totalTerverifikasi - $totalRefund);
        $sisaTagihan = max(0, $hargaTotal - $pembayaranBersih);

        if ($pembayaranBersih <= 0) {
            $statusPembayaran = 'Belum Bayar';
        } elseif ($pembayaranBersih >= $hargaTotal) {
            $statusPembayaran = 'Lunas';
        } else {
            $statusPembayaran = 'Panjar';
        }

        return [
            'harga_total'         => $hargaTotal,
            'total_terverifikasi' => $totalTerverifikasi,
            'total_refund'        => $totalRefund,
            'pembayaran_bersih'   => $pembayaranBersih,
            'sisa_tagihan'        => $sisaTagihan,
            'status_pembayaran'   => $statusPembayaran,
            'pembayaran_menunggu' => $pembayaranMenunggu,
        ];
    }
}
