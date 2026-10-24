import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../constants/app_colors.dart';
import '../../models/pendaftaran_model.dart';
import '../../models/peserta_model.dart';
import '../../services/api_service.dart';
import '../pembayaran/pembayaran_screen.dart';

class DetailPendaftaranScreen extends StatefulWidget {
  final int pendaftaranId;
  const DetailPendaftaranScreen({super.key, required this.pendaftaranId});

  @override
  State<DetailPendaftaranScreen> createState() => _DetailPendaftaranScreenState();
}

class _DetailPendaftaranScreenState extends State<DetailPendaftaranScreen> {
  PendaftaranModel? _reg;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadDetail();
  }

  Future<void> _loadDetail() async {
    setState(() => _isLoading = true);
    final data = await ApiService().getPendaftaranDetail(widget.pendaftaranId);
    if (!mounted) return;
    setState(() {
      _reg = data;
      _isLoading = false;
    });
  }

  Future<void> _contactWhatsApp() async {
    final settings = await ApiService().getPengaturan();
    final wa = settings?.noWhatsapp ?? '6281234567890';
    final url = Uri.parse('https://wa.me/$wa?text=Halo%20Admin%20Fishing%20Trip,%20saya%20ingin%20konfirmasi%20pendaftaran%20${_reg?.kodePendaftaran}');
    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    }
  }

  void _showEditPesertaDialog(PesertaModel p) {
    final namaCtrl = TextEditingController(text: p.namaPemancing);
    final phoneCtrl = TextEditingController(text: p.noTelepon);

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Perbaiki Data Peserta', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(controller: namaCtrl, decoration: const InputDecoration(labelText: 'Nama Lengkap')),
            const SizedBox(height: 10),
            TextField(controller: phoneCtrl, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'Nomor WhatsApp')),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(ctx);
              final res = await ApiService().updatePeserta(p.id, namaCtrl.text.trim(), phoneCtrl.text.trim());
              ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(res['message'])));
              _loadDetail();
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.oceanBlue),
            child: const Text('Kirim Ulang'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (_reg == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Detail Pendaftaran')),
        body: const Center(child: Text('Data pendaftaran tidak ditemukan.')),
      );
    }

    final reg = _reg!;

    return Scaffold(
      backgroundColor: AppColors.bgLight,
      appBar: AppBar(
        title: Text(reg.kodePendaftaran, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        backgroundColor: Colors.white,
        foregroundColor: AppColors.primaryNavy,
        elevation: 0.5,
        actions: [
          IconButton(
            icon: const Icon(Icons.support_agent_rounded, color: AppColors.oceanBlue),
            tooltip: 'Hubungi WhatsApp Operator',
            onPressed: _contactWhatsApp,
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header Trip Info
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 8)],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(reg.tujuan ?? 'Spot Pemancingan', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  const SizedBox(height: 6),
                  Text('${reg.tanggalTrip} • Kapal: ${reg.namaKapal}', style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
                  const SizedBox(height: 4),
                  Text('Lokasi: ${reg.lokasiKumpul}', style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
                ],
              ),
            ),
            const SizedBox(height: 20),

            const Text(
              'Daftar Anggota Peserta & Tagihan',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.primaryNavy),
            ),
            const SizedBox(height: 10),

            ...reg.peserta.map((p) => _buildPesertaCard(p, reg)),

            const SizedBox(height: 20),
            // Tombol Kontak Operator
            SizedBox(
              width: double.infinity,
              height: 48,
              child: OutlinedButton.icon(
                onPressed: _contactWhatsApp,
                icon: const Icon(Icons.chat_bubble_outline, color: AppColors.success, size: 20),
                label: const Text('Hubungi Pemilik Kapal via WhatsApp', style: TextStyle(color: AppColors.success, fontWeight: FontWeight.bold)),
                style: OutlinedButton.styleFrom(
                  side: const BorderSide(color: AppColors.success),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildPesertaCard(PesertaModel p, PendaftaranModel reg) {
    final statusSetuju = p.statusPersetujuan;
    final fin = p.keuangan;
    final isApproved = statusSetuju == 'disetujui' || statusSetuju == 'dikonfirmasi' || statusSetuju == 'menunggu_pembayaran_panjar';
    final isRejected = statusSetuju == 'ditolak';

    return Card(
      margin: const EdgeInsets.only(bottom: 14),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      elevation: 1,
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.between,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(p.namaPemancing, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                      Text(p.noTelepon, style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
                    ],
                  ),
                ),
                _buildStatusPersetujuanBadge(statusSetuju),
              ],
            ),
            const Divider(height: 20),
            if (fin != null) ...[
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  _buildSubInfo('Tarif', 'Rp ${p.hargaFinal.toInt()}'),
                  _buildSubInfo('Masuk', 'Rp ${fin.pembayaranBersih.toInt()}', color: AppColors.success),
                  _buildSubInfo('Sisa', 'Rp ${fin.sisaTagihan.toInt()}', color: AppColors.danger, isBold: true),
                  _buildSubInfo('Status', fin.statusPembayaran, isBold: true),
                ],
              ),
              const SizedBox(height: 12),
            ],

            if (isRejected && p.alasanPenolakan != null) ...[
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: AppColors.danger.withOpacity(0.08), borderRadius: BorderRadius.circular(8)),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Alasan Penolakan:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: AppColors.danger)),
                    const SizedBox(height: 2),
                    Text(p.alasanPenolakan!, style: const TextStyle(fontSize: 12, color: AppColors.danger)),
                  ],
                ),
              ),
              const SizedBox(height: 10),
              ElevatedButton.icon(
                onPressed: () => _showEditPesertaDialog(p),
                icon: const Icon(Icons.edit, size: 16),
                label: const Text('Perbaiki Data Peserta', style: TextStyle(fontSize: 12)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primaryNavy,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
              ),
            ],

            if (isApproved && fin != null && fin.sisaTagihan > 0) ...[
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => PembayaranScreen(
                          peserta: p,
                          namaTrip: reg.tujuan ?? 'Trip',
                          tanggalTrip: reg.tanggalTrip ?? '',
                          nominalPanjar: reg.nominalPanjar ?? 0.0,
                        ),
                      ),
                    ).then((val) {
                      if (val == true) _loadDetail();
                    });
                  },
                  icon: const Icon(Icons.payment_rounded, size: 16),
                  label: Text(fin.statusPembayaran == 'Belum Bayar' ? 'Bayar Panjar / Pelunasan' : 'Bayar Sisa Tagihan'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.oceanBlue,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildSubInfo(String label, String val, {Color? color, bool isBold = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 10, color: AppColors.textMuted)),
        Text(val, style: TextStyle(fontSize: 12, fontWeight: isBold ? FontWeight.bold : FontWeight.w600, color: color ?? AppColors.textDark)),
      ],
    );
  }

  Widget _buildStatusPersetujuanBadge(String status) {
    Color bg = Colors.grey.shade100;
    Color fg = Colors.grey.shade700;
    String label = status;

    if (status == 'disetujui' || status == 'dikonfirmasi') {
      bg = AppColors.success.withOpacity(0.12);
      fg = AppColors.success;
      label = 'Disetujui';
    } else if (status == 'menunggu_persetujuan') {
      bg = AppColors.warning.withOpacity(0.12);
      fg = AppColors.warning;
      label = 'Menunggu';
    } else if (status == 'menunggu_pembayaran_panjar') {
      bg = AppColors.info.withOpacity(0.12);
      fg = AppColors.info;
      label = 'Perlu Panjar';
    } else if (status == 'ditolak') {
      bg = AppColors.danger.withOpacity(0.12);
      fg = AppColors.danger;
      label = 'Ditolak';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(6)),
      child: Text(label, style: TextStyle(color: fg, fontWeight: FontWeight.bold, fontSize: 11)),
    );
  }
}
