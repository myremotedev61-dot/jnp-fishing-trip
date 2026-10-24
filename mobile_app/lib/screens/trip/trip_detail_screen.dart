import 'package:flutter/material.dart';
import '../../constants/app_colors.dart';
import '../../models/trip_model.dart';
import '../../services/api_service.dart';
import '../pendaftaran/pendaftaran_form_screen.dart';

class TripDetailScreen extends StatefulWidget {
  final int tripId;
  const TripDetailScreen({super.key, required this.tripId});

  @override
  State<TripDetailScreen> createState() => _TripDetailScreenState();
}

class _TripDetailScreenState extends State<TripDetailScreen> {
  TripModel? _trip;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadTrip();
  }

  Future<void> _loadTrip() async {
    setState(() => _isLoading = true);
    final detail = await ApiService().getTripDetail(widget.tripId);
    if (!mounted) return;
    setState(() {
      _trip = detail;
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    if (_trip == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Detail Trip')),
        body: const Center(child: Text('Data trip tidak ditemukan.')),
      );
    }

    final t = _trip!;
    final isFull = t.sisaKuota <= 0;
    final canRegister = (t.status == 'dibuka' || t.status == 'hampir_penuh') && !isFull;

    return Scaffold(
      backgroundColor: AppColors.bgLight,
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 220,
            pinned: true,
            backgroundColor: AppColors.primaryNavy,
            foregroundColor: Colors.white,
            flexibleSpace: FlexibleSpaceBar(
              title: Text(
                t.tujuan,
                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white),
              ),
              background: Container(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    colors: [AppColors.primaryNavy, AppColors.oceanBlue],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                  ),
                ),
                child: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.directions_boat_filled_rounded, size: 64, color: AppColors.sandGold),
                      const SizedBox(height: 8),
                      Text(
                        t.namaKapal ?? 'Kapal Pemancingan Turatea',
                        style: const TextStyle(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Status & Kuota Bar
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      boxShadow: [
                        BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 8),
                      ],
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        _buildStatBox('Tarif Tiket', t.hargaFormatted, AppColors.oceanBlue),
                        Container(height: 35, width: 1, color: Colors.grey.shade200),
                        _buildStatBox('Wajib Panjar', t.panjarFormatted, AppColors.textDark),
                        Container(height: 35, width: 1, color: Colors.grey.shade200),
                        _buildStatBox('Sisa Kuota', isFull ? 'PENUH' : '${t.sisaKuota} Slot', isFull ? AppColors.danger : AppColors.success),
                      ],
                    ),
                  ),
                  const SizedBox(height: 18),

                  // Detail Waktu & Lokasi
                  _buildSectionCard(
                    title: 'Jadwal & Lokasi Berkumpul',
                    icon: Icons.calendar_today_rounded,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildInfoRow(Icons.event, 'Hari & Tanggal', t.tanggalFormatted),
                        _buildInfoRow(Icons.alarm, 'Waktu Kumpul', '${t.jamKumpul.substring(0, 5)} WITA'),
                        _buildInfoRow(Icons.sailing, 'Waktu Keberangkatan', '${t.jamBerangkat.substring(0, 5)} WITA'),
                        _buildInfoRow(Icons.place, 'Dermaga Kumpul', t.lokasiKumpul),
                        _buildInfoRow(Icons.explore, 'Spot Tujuan', t.tujuan),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Fasilitas Kapal
                  _buildSectionCard(
                    title: 'Fasilitas Kapal & Perjalanan',
                    icon: Icons.check_circle_outline,
                    child: Text(
                      t.fasilitas ?? 'Umpan cumi/tongkol, Makan siang + snack, Air mineral, Pelampung keselamatan, Coolbox es.',
                      style: const TextStyle(fontSize: 13, height: 1.5, color: AppColors.textDark),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Ketentuan Perjalanan
                  _buildSectionCard(
                    title: 'Ketentuan Perjalanan',
                    icon: Icons.rule_rounded,
                    child: Text(
                      t.ketentuan ?? 'Peserta wajib dalam kondisi sehat, mematuhi arahan kapten kapal, dan memakai jaket pelampung (life jacket) selama berlayar.',
                      style: const TextStyle(fontSize: 13, height: 1.5, color: AppColors.textDark),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Kebijakan Pembatalan
                  _buildSectionCard(
                    title: 'Kebijakan Pembatalan & Refund',
                    icon: Icons.assignment_return_outlined,
                    child: Text(
                      t.kebijakanPembatalan ?? 'Pembatalan sebelum H-3 panjar dikembalikan 100%. Pembatalan kurang dari H-3 panjar hangus.',
                      style: const TextStyle(fontSize: 13, height: 1.5, color: AppColors.textDark),
                    ),
                  ),
                  const SizedBox(height: 80),
                ],
              ),
            ),
          ),
        ],
      ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [
            BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 10, offset: const Offset(0, -3)),
          ],
        ),
        child: SafeArea(
          child: Row(
            children: [
              Expanded(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Total Biaya', style: TextStyle(fontSize: 11, color: AppColors.textMuted)),
                    Text(t.hargaFormatted, style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: AppColors.oceanBlue)),
                  ],
                ),
              ),
              ElevatedButton(
                onPressed: canRegister
                    ? () {
                        Navigator.push(context, MaterialPageRoute(builder: (_) => PendaftaranFormScreen(trip: t)));
                      }
                    : null,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.oceanBlue,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                child: Text(
                  isFull ? 'Kuota Penuh' : 'Daftar Trip',
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatBox(String label, String value, Color color) {
    return Column(
      children: [
        Text(value, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: color)),
        const SizedBox(height: 2),
        Text(label, style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
      ],
    );
  }

  Widget _buildSectionCard({required String title, required IconData icon, required Widget child}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 6)],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 18, color: AppColors.oceanBlue),
              const SizedBox(width: 8),
              Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.primaryNavy)),
            ],
          ),
          const Divider(height: 18),
          child,
        ],
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 16, color: AppColors.textMuted),
          const SizedBox(width: 8),
          SizedBox(width: 120, child: Text(label, style: const TextStyle(fontSize: 12, color: AppColors.textMuted))),
          Expanded(child: Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textDark))),
        ],
      ),
    );
  }
}
