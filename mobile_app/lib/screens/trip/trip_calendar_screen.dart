import 'package:flutter/material.dart';
import '../../constants/app_colors.dart';
import '../../models/trip_model.dart';
import '../../services/api_service.dart';
import 'trip_detail_screen.dart';
import '../pendaftaran/pendaftaran_form_screen.dart';

class TripCalendarScreen extends StatefulWidget {
  const TripCalendarScreen({super.key});

  @override
  State<TripCalendarScreen> createState() => _TripCalendarScreenState();
}

class _TripCalendarScreenState extends State<TripCalendarScreen> {
  int _selectedMonth = DateTime.now().month;
  int _selectedYear = DateTime.now().year;
  String _selectedStatus = '';
  final _searchCtrl = TextEditingController();

  List<TripModel> _trips = [];
  bool _isLoading = false;

  final List<String> _namaBulan = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ];

  @override
  void initState() {
    super.initState();
    _fetchTrips();
  }

  Future<void> _fetchTrips() async {
    setState(() => _isLoading = true);
    final list = await ApiService().getTrips(
      bulan: _selectedMonth,
      tahun: _selectedYear,
      status: _selectedStatus.isNotEmpty ? _selectedStatus : null,
      query: _searchCtrl.text.trim(),
    );
    if (!mounted) return;
    setState(() {
      _trips = list;
      _isLoading = false;
    });
  }

  void _prevMonth() {
    setState(() {
      if (_selectedMonth == 1) {
        _selectedMonth = 12;
        _selectedYear--;
      } else {
        _selectedMonth--;
      }
    });
    _fetchTrips();
  }

  void _nextMonth() {
    setState(() {
      if (_selectedMonth == 12) {
        _selectedMonth = 1;
        _selectedYear++;
      } else {
        _selectedMonth++;
      }
    });
    _fetchTrips();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bgLight,
      appBar: AppBar(
        title: const Text('Jadwal Trip Bulanan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        backgroundColor: Colors.white,
        foregroundColor: AppColors.primaryNavy,
        elevation: 0.5,
      ),
      body: Column(
        children: [
          // Navigasi Bulan & Tahun
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            color: Colors.white,
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.chevron_left_rounded, size: 28, color: AppColors.oceanBlue),
                      onPressed: _prevMonth,
                    ),
                    Text(
                      '${_namaBulan[_selectedMonth - 1]} $_selectedYear',
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17, color: AppColors.primaryNavy),
                    ),
                    IconButton(
                      icon: const Icon(Icons.chevron_right_rounded, size: 28, color: AppColors.oceanBlue),
                      onPressed: _nextMonth,
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                // Search Box
                TextField(
                  controller: _searchCtrl,
                  onSubmitted: (_) => _fetchTrips(),
                  decoration: InputDecoration(
                    hintText: 'Cari spot tujuan atau nama kapal...',
                    hintStyle: const TextStyle(fontSize: 13),
                    prefixIcon: const Icon(Icons.search, size: 20),
                    suffixIcon: _searchCtrl.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear, size: 18),
                            onPressed: () {
                              _searchCtrl.clear();
                              _fetchTrips();
                            },
                          )
                        : null,
                    isDense: true,
                    filled: true,
                    fillColor: AppColors.bgLight,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                // Filter Status Chips
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _buildFilterChip('Semua Status', ''),
                      _buildFilterChip('Dibuka', 'dibuka'),
                      _buildFilterChip('Hampir Penuh', 'hampir_penuh'),
                      _buildFilterChip('Penuh', 'penuh'),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Daftar Jadwal Trip
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _trips.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.calendar_month_outlined, size: 64, color: Colors.grey.shade400),
                            const SizedBox(height: 12),
                            Text(
                              'Tidak ada jadwal trip pada ${_namaBulan[_selectedMonth - 1]} $_selectedYear.',
                              style: const TextStyle(color: AppColors.textMuted, fontSize: 13),
                            ),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _fetchTrips,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _trips.length,
                          itemBuilder: (ctx, i) => _buildTripItem(_trips[i]),
                        ),
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String label, String value) {
    final isSelected = _selectedStatus == value;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(label, style: TextStyle(fontSize: 12, color: isSelected ? Colors.white : AppColors.textDark)),
        selected: isSelected,
        selectedColor: AppColors.oceanBlue,
        backgroundColor: AppColors.bgLight,
        onSelected: (selected) {
          setState(() => _selectedStatus = value);
          _fetchTrips();
        },
      ),
    );
  }

  Widget _buildTripItem(TripModel trip) {
    final isFull = trip.sisaKuota <= 0;
    final canRegister = (trip.status == 'dibuka' || trip.status == 'hampir_penuh') && !isFull;

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 1.5,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.between,
              children: [
                Row(
                  children: [
                    const Icon(Icons.event_note_rounded, size: 16, color: AppColors.oceanBlue),
                    const SizedBox(width: 6),
                    Text(trip.tanggalFormatted, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: isFull ? AppColors.danger.withOpacity(0.12) : AppColors.success.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    isFull ? 'PENUH' : '${trip.sisaKuota} Sisa',
                    style: TextStyle(
                      color: isFull ? AppColors.danger : AppColors.success,
                      fontWeight: FontWeight.bold,
                      fontSize: 11,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Text(
              trip.tujuan,
              style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: AppColors.textDark),
            ),
            const SizedBox(height: 6),
            Text(
              'Kapal: ${trip.namaKapal ?? '-'} • Kuota: ${trip.pesertaTerkonfirmasi}/${trip.kuota} orang',
              style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
            ),
            const SizedBox(height: 6),
            Row(
              children: [
                const Icon(Icons.access_time, size: 14, color: AppColors.textMuted),
                const SizedBox(width: 4),
                Text('Kumpul: ${trip.jamKumpul.substring(0, 5)} | Berangkat: ${trip.jamBerangkat.substring(0, 5)}', style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
              ],
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                const Icon(Icons.place_outlined, size: 14, color: AppColors.textMuted),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(trip.lokasiKumpul, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
                ),
              ],
            ),
            const Divider(height: 22),
            Row(
              mainAxisAlignment: MainAxisAlignment.between,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(trip.hargaFormatted, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.oceanBlue)),
                    Text('Panjar: ${trip.panjarFormatted}', style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
                  ],
                ),
                Row(
                  children: [
                    OutlinedButton(
                      onPressed: () {
                        Navigator.push(context, MaterialPageRoute(builder: (_) => TripDetailScreen(tripId: trip.id)));
                      },
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.primaryNavy,
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                      child: const Text('Detail', style: TextStyle(fontSize: 12)),
                    ),
                    const SizedBox(width: 8),
                    ElevatedButton(
                      onPressed: canRegister
                          ? () {
                              Navigator.push(context, MaterialPageRoute(builder: (_) => PendaftaranFormScreen(trip: trip)));
                            }
                          : null,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.oceanBlue,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                      child: const Text('Daftar Trip', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                    ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
