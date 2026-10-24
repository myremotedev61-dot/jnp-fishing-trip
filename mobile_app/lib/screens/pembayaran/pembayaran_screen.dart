import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../../constants/app_colors.dart';
import '../../models/peserta_model.dart';
import '../../models/pengaturan_model.dart';
import '../../services/api_service.dart';

class PembayaranScreen extends StatefulWidget {
  final PesertaModel peserta;
  final String namaTrip;
  final String tanggalTrip;
  final double nominalPanjar;

  const PembayaranScreen({
    super.key,
    required this.peserta,
    required this.namaTrip,
    required this.tanggalTrip,
    required this.nominalPanjar,
  });

  @override
  State<PembayaranScreen> createState() => _PembayaranScreenState();
}

class _PembayaranScreenState extends State<PembayaranScreen> {
  final _nominalCtrl = TextEditingController();
  final _refCtrl = TextEditingController();
  String _selectedMetode = 'Transfer Bank BCA';
  DateTime _tanggalTransaksi = DateTime.now();
  File? _imageFile;
  bool _isLoading = false;
  String? _errorMessage;
  PengaturanUsahaModel? _pengaturan;

  final List<String> _metodeList = [
    'Transfer Bank BCA',
    'Transfer Bank BRI',
    'Transfer Bank Mandiri',
    'Transfer Bank BNI',
    'E-Wallet DANA / OVO / GoPay',
  ];

  @override
  void initState() {
    super.initState();
    _loadPengaturan();
    // Default isi dengan nominal panjar jika belum bayar, atau sisa tagihan jika ada sisa
    final sisa = widget.peserta.keuangan?.sisaTagihan ?? widget.peserta.hargaFinal;
    if (widget.peserta.keuangan?.statusPembayaran == 'Belum Bayar' && widget.nominalPanjar > 0) {
      _nominalCtrl.text = widget.nominalPanjar.toInt().toString();
    } else {
      _nominalCtrl.text = sisa.toInt().toString();
    }
  }

  Future<void> _loadPengaturan() async {
    final p = await ApiService().getPengaturan();
    if (mounted) setState(() => _pengaturan = p);
  }

  Future<void> _pickImage(ImageSource source) async {
    try {
      final picker = ImagePicker();
      final picked = await picker.pickImage(source: source, imageQuality: 85);
      if (picked != null) {
        setState(() => _imageFile = File(picked.path));
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal memilih gambar: $e')),
      );
    }
  }

  Future<void> _submitBayar() async {
    final nominal = double.tryParse(_nominalCtrl.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0.0;
    if (nominal <= 0) {
      setState(() => _errorMessage = 'Nominal pembayaran tidak valid.');
      return;
    }
    if (_imageFile == null) {
      setState(() => _errorMessage = 'Harap sertakan foto struk / tangkapan layar bukti transfer.');
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final tglStr = '${_tanggalTransaksi.year}-${_tanggalTransaksi.month.toString().padLeft(2, '0')}-${_tanggalTransaksi.day.toString().padLeft(2, '0')}';

    final res = await ApiService().submitPembayaran(
      pesertaTripId: widget.peserta.id,
      nominal: nominal,
      metode: _selectedMetode,
      tanggalTransaksi: tglStr,
      nomorRef: _refCtrl.text.trim(),
      fileBukti: _imageFile!,
    );

    if (!mounted) return;
    setState(() => _isLoading = false);

    if (res['success'] == true) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          title: Column(
            children: const [
              Icon(Icons.check_circle_rounded, color: AppColors.success, size: 50),
              SizedBox(height: 8),
              Text('Bukti Bayar Terkirim', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('Kode Pembayaran: ${res['data']['kode_pembayaran']}', style: const TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: AppColors.warning.withOpacity(0.12), borderRadius: BorderRadius.circular(8)),
                child: const Text(
                  'Status saat ini adalah MENUNGGU VERIFIKASI. Saldo dan status panjar/lunas akan aktif setelah pemilik kapal memverifikasi mutasi bank.',
                  style: TextStyle(fontSize: 12, height: 1.4),
                  textAlign: TextAlign.center,
                ),
              ),
            ],
          ),
          actions: [
            ElevatedButton(
              onPressed: () {
                Navigator.pop(ctx);
                Navigator.pop(context, true);
              },
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.oceanBlue),
              child: const Text('Kembali ke Riwayat'),
            ),
          ],
        ),
      );
    } else {
      setState(() => _errorMessage = res['message']);
    }
  }

  @override
  Widget build(BuildContext context) {
    final sisa = widget.peserta.keuangan?.sisaTagihan ?? widget.peserta.hargaFinal;

    return Scaffold(
      backgroundColor: AppColors.bgLight,
      appBar: AppBar(
        title: const Text('Pembayaran Trip', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
        backgroundColor: Colors.white,
        foregroundColor: AppColors.primaryNavy,
        elevation: 0.5,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Kartu Ringkasan Tagihan
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
                  Row(
                    mainAxisAlignment: MainAxisAlignment.between,
                    children: [
                      Text(widget.peserta.namaPemancing, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: AppColors.oceanBlue.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          widget.peserta.keuangan?.statusPembayaran ?? 'Belum Bayar',
                          style: const TextStyle(color: AppColors.oceanBlue, fontWeight: FontWeight.bold, fontSize: 11),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text('${widget.namaTrip} • ${widget.tanggalTrip}', style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
                  const Divider(height: 20),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      _buildInfoColumn('Harga Tiket', 'Rp ${widget.peserta.hargaFinal.toInt()}'),
                      _buildInfoColumn('Terverifikasi', 'Rp ${(widget.peserta.keuangan?.pembayaranBersih ?? 0).toInt()}', color: AppColors.success),
                      _buildInfoColumn('Sisa Tagihan', 'Rp ${sisa.toInt()}', color: AppColors.danger, isBold: true),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),

            // Rekening Pembayaran Resmi Usaha
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.primaryNavy.withOpacity(0.04),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.oceanBlue.withOpacity(0.2)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.account_balance, size: 18, color: AppColors.oceanBlue),
                      SizedBox(width: 8),
                      Text('Rekening Tujuan Resmi Usaha', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.primaryNavy)),
                    ],
                  ),
                  const SizedBox(height: 10),
                  if (_pengaturan != null && _pengaturan!.rekening.isNotEmpty)
                    ..._pengaturan!.rekening.map((r) => Padding(
                          padding: const EdgeInsets.only(bottom: 6),
                          child: Text('• ${r.bank}: ${r.nomor} a.n ${r.pemilik}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                        ))
                  else
                    const Text('• BCA: 1234567890 a.n FISHING TRIP TURATEA\n• BRI: 098765432112345 a.n FISHING TRIP TURATEA', style: TextStyle(fontSize: 12)),
                ],
              ),
            ),
            const SizedBox(height: 18),

            if (_errorMessage != null)
              Container(
                padding: const EdgeInsets.all(12),
                margin: const EdgeInsets.bottom(16),
                decoration: BoxDecoration(
                  color: AppColors.danger.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(_errorMessage!, style: const TextStyle(color: AppColors.danger, fontSize: 13)),
              ),

            // Form Setoran
            const Text('Form Pengajuan Pembayaran', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
            const SizedBox(height: 12),

            // Tombol Opsi Cepat (Bayar Panjar / Bayar Lunas)
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () {
                      _nominalCtrl.text = widget.nominalPanjar.toInt().toString();
                    },
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.oceanBlue,
                      side: const BorderSide(color: AppColors.oceanBlue),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    child: Text('Panjar (Rp ${widget.nominalPanjar.toInt()})', style: const TextStyle(fontSize: 11)),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: OutlinedButton(
                    onPressed: () {
                      _nominalCtrl.text = sisa.toInt().toString();
                    },
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.success,
                      side: const BorderSide(color: AppColors.success),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    child: Text('Pelunasan (Rp ${sisa.toInt()})', style: const TextStyle(fontSize: 11)),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            TextField(
              controller: _nominalCtrl,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                labelText: 'Nominal Yang Ditransfer (Rp) *',
                prefixText: 'Rp ',
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
            const SizedBox(height: 14),

            DropdownButtonFormField<String>(
              value: _selectedMetode,
              decoration: InputDecoration(
                labelText: 'Metode Pembayaran *',
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              ),
              items: _metodeList.map((m) => DropdownMenuItem(value: m, child: Text(m, style: const TextStyle(fontSize: 13)))).toList(),
              onChanged: (v) => setState(() => _selectedMetode = v ?? _selectedMetode),
            ),
            const SizedBox(height: 14),

            TextField(
              controller: _refCtrl,
              decoration: InputDecoration(
                labelText: 'Nomor Referensi Transaksi (Opsional)',
                hintText: 'Contoh: TRX-987654 atau 4 digit terakhir kartu',
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
            const SizedBox(height: 16),

            // Foto Bukti Transfer
            const Text('Unggah Foto Bukti Transfer *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
            const SizedBox(height: 8),

            GestureDetector(
              onTap: () {
                showModalBottomSheet(
                  context: context,
                  builder: (ctx) => SafeArea(
                    child: Wrap(
                      children: [
                        ListTile(
                          leading: const Icon(Icons.camera_alt),
                          title: const Text('Buka Kamera HP'),
                          onTap: () {
                            Navigator.pop(ctx);
                            _pickImage(ImageSource.camera);
                          },
                        ),
                        ListTile(
                          leading: const Icon(Icons.photo_library),
                          title: const Text('Pilih dari Galeri Foto'),
                          onTap: () {
                            Navigator.pop(ctx);
                            _pickImage(ImageSource.gallery);
                          },
                        ),
                      ],
                    ),
                  ),
                );
              },
              child: Container(
                width: double.infinity,
                height: 180,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.grey.shade300, style: BorderStyle.solid),
                ),
                child: _imageFile != null
                    ? ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: Stack(
                          fit: StackFit.expand,
                          children: [
                            Image.file(_imageFile!, fit: BoxFit.contain),
                            Positioned(
                              top: 8,
                              right: 8,
                              child: CircleAvatar(
                                backgroundColor: Colors.black54,
                                radius: 16,
                                child: IconButton(
                                  icon: const Icon(Icons.refresh, color: Colors.white, size: 16),
                                  onPressed: () => _pickImage(ImageSource.gallery),
                                ),
                              ),
                            ),
                          ],
                        ),
                      )
                    : Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: const [
                          Icon(Icons.cloud_upload_outlined, size: 48, color: AppColors.oceanBlue),
                          SizedBox(height: 8),
                          Text('Ketuk untuk unggah bukti transfer', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.oceanBlue)),
                          SizedBox(height: 4),
                          Text('Format JPG, PNG, atau WEBP (Maksimal 5MB)', style: TextStyle(fontSize: 11, color: AppColors.textMuted)),
                        ],
                      ),
              ),
            ),
            const SizedBox(height: 24),

            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                onPressed: _isLoading ? null : _submitBayar,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.oceanBlue,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                child: _isLoading
                    ? const CircularProgressIndicator(color: Colors.white, strokeWidth: 2)
                    : const Text('Kirim Bukti Pembayaran', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
              ),
            ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoColumn(String label, String value, {Color? color, bool isBold = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
        const SizedBox(height: 2),
        Text(value, style: TextStyle(fontSize: 14, fontWeight: isBold ? FontWeight.bold : FontWeight.w600, color: color ?? AppColors.textDark)),
      ],
    );
  }
}
