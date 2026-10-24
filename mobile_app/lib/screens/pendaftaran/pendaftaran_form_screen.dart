import 'package:flutter/material.dart';
import '../../constants/app_colors.dart';
import '../../models/trip_model.dart';
import '../../services/api_service.dart';
import '../main_navigation_screen.dart';

class PendaftaranFormScreen extends StatefulWidget {
  final TripModel trip;
  const PendaftaranFormScreen({super.key, required this.trip});

  @override
  State<PendaftaranFormScreen> createState() => _PendaftaranFormScreenState();
}

class _PesertaFormEntry {
  final TextEditingController namaCtrl = TextEditingController();
  final TextEditingController phoneCtrl = TextEditingController();
}

class _PendaftaranFormScreenState extends State<PendaftaranFormScreen> {
  final List<_PesertaFormEntry> _pesertaList = [];
  final _catatanCtrl = TextEditingController();
  bool _agreeTerms = true;
  bool _isLoading = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    // Default 1 peserta awal
    _addPesertaField();
  }

  void _addPesertaField() {
    if (_pesertaList.length >= widget.trip.sisaKuota) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Maksimal penambahan peserta sesuai sisa kuota (${widget.trip.sisaKuota} orang).')),
      );
      return;
    }
    setState(() {
      _pesertaList.add(_PesertaFormEntry());
    });
  }

  void _removePesertaField(int index) {
    if (_pesertaList.length <= 1) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pendaftaran minimal menyertakan 1 peserta.')),
      );
      return;
    }
    setState(() {
      _pesertaList[index].namaCtrl.dispose();
      _pesertaList[index].phoneCtrl.dispose();
      _pesertaList.removeAt(index);
    });
  }

  @override
  void dispose() {
    for (var p in _pesertaList) {
      p.namaCtrl.dispose();
      p.phoneCtrl.dispose();
    }
    _catatanCtrl.dispose();
    super.dispose();
  }

  Future<void> _handleSubmit() async {
    // Validasi data
    List<Map<String, String>> payload = [];
    for (int i = 0; i < _pesertaList.length; i++) {
      final nama = _pesertaList[i].namaCtrl.text.trim();
      final phone = _pesertaList[i].phoneCtrl.text.trim();

      if (nama.isEmpty || nama.length < 3) {
        setState(() => _errorMessage = 'Nama peserta nomor ${i + 1} tidak valid (minimal 3 karakter).');
        return;
      }
      if (phone.isEmpty || phone.length < 8) {
        setState(() => _errorMessage = 'Nomor WhatsApp peserta nomor ${i + 1} tidak valid.');
        return;
      }

      payload.add({'nama_pemancing': nama, 'no_telepon': phone});
    }

    if (!_agreeTerms) {
      setState(() => _errorMessage = 'Anda wajib menyetujui ketentuan perjalanan dan kebijakan pembatalan.');
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final res = await ApiService().submitPendaftaran(
      widget.trip.id,
      payload,
      catatan: _catatanCtrl.text.trim(),
    );

    if (!mounted) return;
    setState(() => _isLoading = false);

    if (res['success'] == true) {
      _showSuccessDialog(res['data']['kode_pendaftaran']);
    } else {
      setState(() => _errorMessage = res['message']);
    }
  }

  void _showSuccessDialog(String kodePendaftaran) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Column(
          children: const [
            Icon(Icons.check_circle_outline, color: AppColors.success, size: 54),
            SizedBox(height: 10),
            Text('Pendaftaran Terkirim!', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18), textAlign: TextAlign.center),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(color: AppColors.bgLight, borderRadius: BorderRadius.circular(8)),
              child: Text(
                'Kode: $kodePendaftaran',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.primaryNavy),
              ),
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: AppColors.warning.withOpacity(0.12),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Text(
                'PERHATIAN: Pendaftaran berstatus MENUNGGU PERSETUJUAN. Tempat Anda BELUM dikonfirmasi sebelum disetujui oleh pemilik usaha.',
                style: TextStyle(fontSize: 12, color: AppColors.textDark, height: 1.4),
                textAlign: TextAlign.center,
              ),
            ),
          ],
        ),
        actions: [
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (_) => const MainNavigationScreen(initialIndex: 2)),
                (route) => false,
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.oceanBlue,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: const Text('Lihat Riwayat Saya'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final totalBiaya = widget.trip.hargaPerPeserta * _pesertaList.length;
    final totalPanjar = widget.trip.nominalPanjar * _pesertaList.length;

    return Scaffold(
      backgroundColor: AppColors.bgLight,
      appBar: AppBar(
        title: const Text('Form Pendaftaran Peserta', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
        backgroundColor: Colors.white,
        foregroundColor: AppColors.primaryNavy,
        elevation: 0.5,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Ringkasan Trip Singkat
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.sailing_rounded, color: AppColors.oceanBlue, size: 20),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          widget.trip.tujuan,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    '${widget.trip.tanggalFormatted} • Kapal: ${widget.trip.namaKapal}',
                    style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Sisa Kursi Tersedia: ${widget.trip.sisaKuota} Orang',
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.success),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            if (_errorMessage != null)
              Container(
                padding: const EdgeInsets.all(12),
                margin: const EdgeInsets.bottom(16),
                decoration: BoxDecoration(
                  color: AppColors.danger.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: AppColors.danger.withOpacity(0.3)),
                ),
                child: Text(_errorMessage!, style: const TextStyle(color: AppColors.danger, fontSize: 13)),
              ),

            // Form Peserta Bertahap
            Row(
              mainAxisAlignment: MainAxisAlignment.between,
              children: [
                const Text(
                  'Data Pemancing / Peserta',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.primaryNavy),
                ),
                TextButton.icon(
                  onPressed: _addPesertaField,
                  icon: const Icon(Icons.person_add_alt_1, size: 18),
                  label: const Text('Tambah Peserta', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  style: TextButton.styleFrom(foregroundColor: AppColors.oceanBlue),
                ),
              ],
            ),
            const SizedBox(height: 8),

            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _pesertaList.length,
              itemBuilder: (ctx, i) => _buildPesertaCard(i),
            ),

            const SizedBox(height: 16),
            const Text('Catatan Tambahan (Opsional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
            const SizedBox(height: 6),
            TextField(
              controller: _catatanCtrl,
              maxLines: 2,
              decoration: InputDecoration(
                hintText: 'Contoh: Rombongan membawa joran popping sendiri...',
                hintStyle: const TextStyle(fontSize: 12),
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
            const SizedBox(height: 16),

            // Rincian Biaya
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 6)],
              ),
              child: Column(
                children: [
                  _buildCostRow('Jumlah Peserta', '${_pesertaList.length} Orang'),
                  _buildCostRow('Harga Tiket / Orang', widget.trip.hargaFormatted),
                  const Divider(height: 18),
                  _buildCostRow('Total Biaya Estimasi', 'Rp ${totalBiaya.toInt()}', isBold: true, color: AppColors.oceanBlue),
                  _buildCostRow('Estimasi Minimal Panjar', 'Rp ${totalPanjar.toInt()}', color: AppColors.textMuted),
                ],
              ),
            ),
            const SizedBox(height: 16),

            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Checkbox(
                  value: _agreeTerms,
                  activeColor: AppColors.oceanBlue,
                  onChanged: (v) => setState(() => _agreeTerms = v ?? false),
                ),
                const Expanded(
                  child: Padding(
                    padding: EdgeInsets.only(top: 10),
                    child: Text(
                      'Saya memahami bahwa pengajuan pendaftaran ini harus disetujui terlebih dahulu oleh pihak kapal sebelum kuota dipastikan.',
                      style: TextStyle(fontSize: 12, color: AppColors.textMuted, height: 1.4),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                onPressed: _isLoading ? null : _handleSubmit,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.oceanBlue,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                child: _isLoading
                    ? const CircularProgressIndicator(color: Colors.white, strokeWidth: 2)
                    : const Text('Kirim Pengajuan Pendaftaran', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildPesertaCard(int index) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.between,
            children: [
              Text(
                'Peserta ${index + 1}',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.primaryNavy),
              ),
              if (_pesertaList.length > 1)
                IconButton(
                  icon: const Icon(Icons.remove_circle_outline, color: AppColors.danger, size: 20),
                  onPressed: () => _removePesertaField(index),
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                ),
            ],
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _pesertaList[index].namaCtrl,
            decoration: InputDecoration(
              labelText: 'Nama Lengkap Pemancing',
              hintText: 'Nama sesuai KTP/identitas',
              isDense: true,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: _pesertaList[index].phoneCtrl,
            keyboardType: TextInputType.phone,
            decoration: InputDecoration(
              labelText: 'Nomor WhatsApp / HP',
              hintText: '081234567890',
              isDense: true,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCostRow(String label, String value, {bool isBold = false, Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.between,
        children: [
          Text(label, style: TextStyle(fontSize: 12, color: isBold ? AppColors.textDark : AppColors.textMuted, fontWeight: isBold ? FontWeight.bold : FontWeight.normal)),
          Text(value, style: TextStyle(fontSize: 13, fontWeight: isBold ? FontWeight.bold : FontWeight.w600, color: color ?? AppColors.textDark)),
        ],
      ),
    );
  }
}
