import 'peserta_model.dart';

class PendaftaranModel {
  final int id;
  final String kodePendaftaran;
  final int tripId;
  final int userId;
  final String tanggalDaftar;
  final int jumlahDiajukan;
  final String status;
  final String? catatan;
  final String? kodeTrip;
  final String? tujuan;
  final String? tanggalTrip;
  final String? jamBerangkat;
  final String? lokasiKumpul;
  final double? nominalPanjar;
  final double? hargaPerPeserta;
  final String? statusTrip;
  final String? namaKapal;
  final List<PesertaModel> peserta;

  PendaftaranModel({
    required this.id,
    required this.kodePendaftaran,
    required this.tripId,
    required this.userId,
    required this.tanggalDaftar,
    required this.jumlahDiajukan,
    required this.status,
    this.catatan,
    this.kodeTrip,
    this.tujuan,
    this.tanggalTrip,
    this.jamBerangkat,
    this.lokasiKumpul,
    this.nominalPanjar,
    this.hargaPerPeserta,
    this.statusTrip,
    this.namaKapal,
    this.peserta = const [],
  });

  factory PendaftaranModel.fromJson(Map<String, dynamic> json) {
    var rawPeserta = json['peserta'] as List? ?? [];
    List<PesertaModel> listPeserta = rawPeserta.map((p) => PesertaModel.fromJson(p)).toList();

    return PendaftaranModel(
      id: int.tryParse(json['id'].toString()) ?? 0,
      kodePendaftaran: json['kode_pendaftaran'] ?? '',
      tripId: int.tryParse(json['trip_id'].toString()) ?? 0,
      userId: int.tryParse(json['user_id'].toString()) ?? 0,
      tanggalDaftar: json['tanggal_daftar'] ?? '',
      jumlahDiajukan: int.tryParse(json['jumlah_diajukan'].toString()) ?? 0,
      status: json['status'] ?? 'menunggu_persetujuan',
      catatan: json['catatan'],
      kodeTrip: json['kode_trip'],
      tujuan: json['tujuan'],
      tanggalTrip: json['tanggal_trip'],
      jamBerangkat: json['jam_berangkat'],
      lokasiKumpul: json['lokasi_kumpul'],
      nominalPanjar: double.tryParse(json['nominal_panjar']?.toString() ?? '0.0'),
      hargaPerPeserta: double.tryParse(json['harga_per_peserta']?.toString() ?? '0.0'),
      statusTrip: json['status_trip'],
      namaKapal: json['nama_kapal'],
      peserta: listPeserta,
    );
  }
}
