import 'package:flutter/material.dart';
import '../../constants/app_colors.dart';
import '../../models/pendaftaran_model.dart';
import '../../models/pembayaran_model.dart';
import '../../services/api_service.dart';
import 'detail_pendaftaran_screen.dart';

class RiwayatScreen extends StatefulWidget {
  const RiwayatScreen({super.key});

  @override
  State<RiwayatScreen> createState() => _RiwayatScreenState();
}

class _RiwayatScreenState extends State<RiwayatScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<PendaftaranModel> _registrations = [];
  List<PembayaranModel> _payments = [];
  bool _isLoading = false;

  final List<String> _tabs = [
    'Semua',
    'Menunggu',
    'Disetujui',
    'Ditolak',
    'Mendatang',
    'Pembayaran',
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: _tabs.length, vsync: this);
    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) {
        _fetchData();
      }
    });
    _fetchData();
  }

  Future<void> _fetchData() async {
    setState(() => _isLoading = true);
    final idx = _tabController.index;

    if (idx == 5) {
      // Tab Riwayat Pembayaran
      final pays = await ApiService().getMyPayments();
      if (mounted) setState(() { _payments = pays; _isLoading = false; });
    } else {
      String tabParam = 'all';
      if (idx == 1) tabParam = 'menunggu';
      else if (idx == 2) tabParam = 'disetujui';
      else if (idx == 3) tabParam = 'ditolak';
      else if (idx == 4) tabParam = 'mendatang';

      final regs = await ApiService().getMyRegistrations(tab: tabParam);
      if (mounted) setState(() { _registrations = regs; _isLoading = false; });
    }
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bgLight,
      appBar: AppBar(
        title: const Text('Riwayat Saya', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        backgroundColor: Colors.white,
        foregroundColor: AppColors.primaryNavy,
        elevation: 0.5,
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          labelColor: AppColors.oceanBlue,
          unselectedLabelColor: AppColors.textMuted,
          indicatorColor: AppColors.oceanBlue,
          indicatorWeight: 3,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
          tabs: _tabs.map((t) => Tab(text: t)).toList(),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _fetchData,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _tabController.index == 5
                ? _buildPaymentList()
                : _buildRegistrationList(),
      ),
    );
  }

  Widget _buildRegistrationList() {
    if (_registrations.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.confirmation_number_outlined, size: 64, color: Colors.grey.shade400),
            const SizedBox(height: 12),
            const Text('Belum ada pendaftaran pada kategori ini.', style: TextStyle(color: AppColors.textMuted)),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _registrations.length,
      itemBuilder: (ctx, i) {
        final reg = _registrations[i];
        return Card(
          margin: const EdgeInsets.only(bottom: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          elevation: 1,
          child: InkWell(
            borderRadius: BorderRadius.circular(14),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => DetailPendaftaranScreen(pendaftaranId: reg.id)),
              ).then((_) => _fetchData());
            },
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.between,
                    children: [
                      Text(
                        reg.kodePendaftaran,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.oceanBlue),
                      ),
                      _buildStatusBadge(reg.status),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Text(
                    reg.tujuan ?? 'Trip Pemancingan Laut',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.textDark),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${reg.tanggalTrip ?? '-'} • Kapal: ${reg.namaKapal ?? '-'}',
                    style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                  ),
                  const Divider(height: 20),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.between,
                    children: [
                      Text(
                        '${reg.peserta.length} Peserta Rombongan',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textDark),
                      ),
                      const Row(
                        children: [
                          Text('Lihat Detail', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.oceanBlue)),
                          Icon(Icons.chevron_right, size: 18, color: AppColors.oceanBlue),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _buildPaymentList() {
    if (_payments.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.receipt_long_outlined, size: 64, color: Colors.grey.shade400),
            const SizedBox(height: 12),
            const Text('Belum ada transaksi pembayaran.', style: TextStyle(color: AppColors.textMuted)),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _payments.length,
      itemBuilder: (ctx, i) {
        final pay = _payments[i];
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
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
                    Text(pay.kodePembayaran, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.oceanBlue)),
                    _buildVerifBadge(pay.statusVerifikasi),
                  ],
                ),
                const SizedBox(height: 8),
                Text(pay.namaPemancing ?? 'Peserta Trip', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                Text('${pay.tujuan ?? ''} • ${pay.tanggalTransaksi}', style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
                const Divider(height: 18),
                Row(
                  mainAxisAlignment: MainAxisAlignment.between,
                  children: [
                    Text(pay.metodePembayaran, style: const TextStyle(fontSize: 12, color: AppColors.textDark)),
                    Text(pay.nominalFormatted ?? 'Rp ${pay.nominal.toInt()}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.success)),
                  ],
                ),
                if (pay.statusVerifikasi == 'ditolak' && pay.alasanPenolakan != null) ...[
                  const SizedBox(height: 8),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(color: AppColors.danger.withOpacity(0.08), borderRadius: BorderRadius.circular(6)),
                    child: Text('Alasan Penolakan: ${pay.alasanPenolakan}', style: const TextStyle(fontSize: 11, color: AppColors.danger)),
                  ),
                ],
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildStatusBadge(String status) {
    Color bg = Colors.grey.shade100;
    Color fg = Colors.grey.shade700;
    String text = status;

    if (status == 'disetujui' || status == 'disetujui_sebagian') {
      bg = AppColors.success.withOpacity(0.12);
      fg = AppColors.success;
      text = 'Disetujui';
    } else if (status == 'menunggu_persetujuan') {
      bg = AppColors.warning.withOpacity(0.12);
      fg = AppColors.warning;
      text = 'Menunggu';
    } else if (status == 'ditolak') {
      bg = AppColors.danger.withOpacity(0.12);
      fg = AppColors.danger;
      text = 'Ditolak';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(6)),
      child: Text(text, style: TextStyle(color: fg, fontWeight: FontWeight.bold, fontSize: 11)),
    );
  }

  Widget _buildVerifBadge(String status) {
    Color bg = Colors.grey.shade100;
    Color fg = Colors.grey.shade700;
    String text = status;

    if (status == 'terverifikasi') {
      bg = AppColors.success.withOpacity(0.12);
      fg = AppColors.success;
      text = 'Terverifikasi';
    } else if (status == 'menunggu_verifikasi') {
      bg = AppColors.warning.withOpacity(0.12);
      fg = AppColors.warning;
      text = 'Menunggu Verifikasi';
    } else if (status == 'ditolak') {
      bg = AppColors.danger.withOpacity(0.12);
      fg = AppColors.danger;
      text = 'Ditolak';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(6)),
      child: Text(text, style: TextStyle(color: fg, fontWeight: FontWeight.bold, fontSize: 11)),
    );
  }
}
