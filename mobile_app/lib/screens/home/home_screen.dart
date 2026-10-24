import 'package:flutter/material.dart';
import '../../constants/app_colors.dart';
import '../../models/trip_model.dart';
import '../../models/user_model.dart';
import '../../services/api_service.dart';
import '../trip/trip_detail_screen.dart';
import '../notifikasi/notifikasi_screen.dart';
import '../main_navigation_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  UserModel? _user;
  List<TripModel> _trips = [];
  bool _isLoading = true;
  int _unreadNotif = 0;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final user = await ApiService().getProfile();
    final trips = await ApiService().getTrips();
    final notifData = await ApiService().getNotifikasi();

    if (!mounted) return;
    setState(() {
      _user = user;
      _trips = trips;
      _unreadNotif = notifData['unread'] ?? 0;
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    // Trip terdekat (yang dibuka atau hampir penuh)
    final upcomingTrips = _trips.where((t) => t.status != 'dibatalkan' && t.status != 'selesai').toList();
    final almostFullTrips = _trips.where((t) => t.status == 'hampir_penuh' || (t.sisaKuota <= 3 && t.sisaKuota > 0)).toList();

    return Scaffold(
      backgroundColor: AppColors.bgLight,
      appBar: AppBar(
        backgroundColor: AppColors.primaryNavy,
        elevation: 0,
        title: Row(
          children: [
            const Icon(Icons.directions_boat_filled_rounded, color: AppColors.sandGold, size: 24),
            const SizedBox(width: 8),
            const Text(
              'Fishing Trip',
              style: TextStyle(fontWeight: FontWeight.w800, color: Colors.white, fontSize: 18),
            ),
          ],
        ),
        actions: [
          Stack(
            children: [
              IconButton(
                icon: const Icon(Icons.notifications_none_rounded, color: Colors.white),
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const NotifikasiScreen()),
                  ).then((_) => _loadData());
                },
              ),
              if (_unreadNotif > 0)
                Positioned(
                  right: 8,
                  top: 8,
                  child: Container(
                    padding: const EdgeInsets.all(4),
                    decoration: const BoxDecoration(color: AppColors.danger, shape: BoxShape.circle),
                    constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                    child: Text(
                      '$_unreadNotif',
                      style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadData,
        color: AppColors.oceanBlue,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Header Greeting Card
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [AppColors.primaryNavy, AppColors.oceanBlue],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.oceanBlue.withOpacity(0.25),
                            blurRadius: 15,
                            offset: const Offset(0, 6),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              CircleAvatar(
                                radius: 22,
                                backgroundColor: Colors.white.withOpacity(0.2),
                                child: const Icon(Icons.person, color: Colors.white),
                              ),
                              const SizedBox(width: 12),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'Halo, ${_user?.nama ?? 'Pemancing'}!',
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontWeight: FontWeight.bold,
                                      fontSize: 18,
                                    ),
                                  ),
                                  const Text(
                                    'Siap berburu monster laut Turatea?',
                                    style: TextStyle(color: Colors.white70, fontSize: 12),
                                  ),
                                ],
                              ),
                            ],
                          ),
                          const SizedBox(height: 18),
                          // Status Registrasi Milik User
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.12),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceAround,
                              children: [
                                _buildSummaryItem(
                                  'Pendaftaran',
                                  '${_user?.ringkasan?['total_pendaftaran'] ?? 0}',
                                  Icons.assignment_outlined,
                                ),
                                Container(height: 24, width: 1, color: Colors.white24),
                                _buildSummaryItem(
                                  'Trip Disetujui',
                                  '${_user?.ringkasan?['total_trip_disetujui'] ?? 0}',
                                  Icons.verified_outlined,
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Pengumuman Pemilik Usaha
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: AppColors.sandGold.withOpacity(0.12),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.sandGold.withOpacity(0.4)),
                      ),
                      child: Row(
                        children: const [
                          Icon(Icons.campaign_rounded, color: AppColors.sandGold, size: 28),
                          SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              'Pengumuman: Musim Ikan GT & Tenggiri sedang puncak! Segera amankan kuota trip Anda sebelum penuh.',
                              style: TextStyle(fontSize: 12, color: AppColors.textDark, height: 1.4),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Seksi Trip Hampir Penuh (Urgent)
                    if (almostFullTrips.isNotEmpty) ...[
                      Row(
                        children: const [
                          Icon(Icons.local_fire_department_rounded, color: AppColors.danger, size: 22),
                          SizedBox(width: 6),
                          Text(
                            'Trip Hampir Penuh!',
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.danger),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      SizedBox(
                        height: 175,
                        child: ListView.builder(
                          scrollDirection: Axis.horizontal,
                          itemCount: almostFullTrips.length,
                          itemBuilder: (ctx, i) => _buildUrgentTripCard(almostFullTrips[i]),
                        ),
                      ),
                      const SizedBox(height: 24),
                    ],

                    // Seksi Jadwal Trip Tersedia
                    Row(
                      mainAxisAlignment: MainAxisAlignment.between,
                      children: [
                        const Text(
                          'Jadwal Trip Mendatang',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17, color: AppColors.primaryNavy),
                        ),
                        TextButton(
                          onPressed: () {
                            Navigator.pushReplacement(
                              context,
                              MaterialPageRoute(builder: (_) => const MainNavigationScreen(initialIndex: 1)),
                            );
                          },
                          child: const Text('Lihat Kalender', style: TextStyle(color: AppColors.oceanBlue, fontSize: 13, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),

                    if (upcomingTrips.isEmpty)
                      const Center(
                        child: Padding(
                          padding: EdgeInsets.all(32),
                          child: Text('Belum ada jadwal trip yang tersedia saat ini.', style: TextStyle(color: AppColors.textMuted)),
                        ),
                      )
                    else
                      ListView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: upcomingTrips.length,
                        itemBuilder: (ctx, i) => _buildTripCard(upcomingTrips[i]),
                      ),
                  ],
                ),
              ),
      ),
    );
  }

  Widget _buildSummaryItem(String label, String value, IconData icon) {
    return Row(
      children: [
        Icon(icon, color: Colors.white70, size: 18),
        const SizedBox(width: 8),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(value, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
            Text(label, style: const TextStyle(color: Colors.white70, fontSize: 11)),
          ],
        ),
      ],
    );
  }

  Widget _buildUrgentTripCard(TripModel trip) {
    return Container(
      width: 250,
      margin: const EdgeInsets.only(right: 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.danger.withOpacity(0.3)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.between,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(color: AppColors.danger.withOpacity(0.12), borderRadius: BorderRadius.circular(6)),
                child: Text('Tersisa ${trip.sisaKuota} slot', style: const TextStyle(color: AppColors.danger, fontSize: 11, fontWeight: FontWeight.bold)),
              ),
              Text(trip.panjarFormatted, style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
            ],
          ),
          const SizedBox(height: 8),
          Text(trip.tujuan, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
          const SizedBox(height: 4),
          Text('${trip.tanggalFormatted} • ${trip.namaKapal}', style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
          const Spacer(),
          Row(
            mainAxisAlignment: MainAxisAlignment.between,
            children: [
              Text(trip.hargaFormatted, style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.oceanBlue, fontSize: 14)),
              ElevatedButton(
                onPressed: () {
                  Navigator.push(context, MaterialPageRoute(builder: (_) => TripDetailScreen(tripId: trip.id)));
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.oceanBlue,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                  minimumSize: const Size(60, 30),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                ),
                child: const Text('Detail', style: TextStyle(fontSize: 11)),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTripCard(TripModel trip) {
    final isFull = trip.sisaKuota <= 0;

    return Card(
      margin: const EdgeInsets.only(bottom: 14),
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: () {
          Navigator.push(context, MaterialPageRoute(builder: (_) => TripDetailScreen(tripId: trip.id)));
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.between,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: AppColors.oceanBlue.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(trip.kodeTrip, style: const TextStyle(color: AppColors.oceanBlue, fontWeight: FontWeight.bold, fontSize: 11)),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: isFull ? AppColors.danger.withOpacity(0.12) : AppColors.success.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      isFull ? 'KUOTA PENUH' : 'Sisa ${trip.sisaKuota} Kursi',
                      style: TextStyle(
                        color: isFull ? AppColors.danger : AppColors.success,
                        fontWeight: FontWeight.bold,
                        fontSize: 11,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                trip.tujuan,
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.textDark),
              ),
              const SizedBox(height: 6),
              Row(
                children: [
                  const Icon(Icons.directions_boat_outlined, size: 16, color: AppColors.textMuted),
                  const SizedBox(width: 6),
                  Text(trip.namaKapal ?? 'Kapal Pemancingan', style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
                  const SizedBox(width: 14),
                  const Icon(Icons.access_time_rounded, size: 16, color: AppColors.textMuted),
                  const SizedBox(width: 6),
                  Text('Kumpul: ${trip.jamKumpul.substring(0, 5)}', style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
                ],
              ),
              const SizedBox(height: 6),
              Row(
                children: [
                  const Icon(Icons.calendar_today_rounded, size: 16, color: AppColors.textMuted),
                  const SizedBox(width: 6),
                  Text(trip.tanggalFormatted, style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
                ],
              ),
              const Divider(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.between,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Biaya Per Peserta', style: TextStyle(fontSize: 11, color: AppColors.textMuted)),
                      Text(trip.hargaFormatted, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.oceanBlue)),
                    ],
                  ),
                  ElevatedButton(
                    onPressed: () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => TripDetailScreen(tripId: trip.id)));
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primaryNavy,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    child: const Text('Detail Trip', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
