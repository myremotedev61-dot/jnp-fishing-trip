class TripModel {
  final int id;
  final String kodeTrip;
  final int kapalId;
  final String tanggalTrip;
  final String jamKumpul;
  final String jamBerangkat;
  final String jamKembali;
  final String lokasiKumpul;
  final String tujuan;
  final double hargaPerPeserta;
  final double nominalPanjar;
  final int kuota;
  final String batasPendaftaran;
  final String batasPembayaran;
  final String? fasilitas;
  final String? ketentuan;
  final String? deskripsi;
  final String? kebijakanPembatalan;
  final String status;
  final String? namaKapal;
  final String? fotoKapal;
  final int? kapasitasKapal;
  final int pesertaTerkonfirmasi;
  final int sisaKuota;
  final double persentaseKuota;
  final String tanggalFormatted;
  final String hargaFormatted;
  final String panjarFormatted;

  TripModel({
    required this.id,
    required this.kodeTrip,
    required this.kapalId,
    required this.tanggalTrip,
    required this.jamKumpul,
    required this.jamBerangkat,
    required this.jamKembali,
    required this.lokasiKumpul,
    required this.tujuan,
    required this.hargaPerPeserta,
    required this.nominalPanjar,
    required this.kuota,
    required this.batasPendaftaran,
    required this.batasPembayaran,
    this.fasilitas,
    this.ketentuan,
    this.deskripsi,
    this.kebijakanPembatalan,
    required this.status,
    this.namaKapal,
    this.fotoKapal,
    this.kapasitasKapal,
    required this.pesertaTerkonfirmasi,
    required this.sisaKuota,
    required this.persentaseKuota,
    required this.tanggalFormatted,
    required this.hargaFormatted,
    required this.panjarFormatted,
  });

  factory TripModel.fromJson(Map<String, dynamic> json) {
    return TripModel(
      id: int.tryParse(json['id'].toString()) ?? 0,
      kodeTrip: json['kode_trip'] ?? '',
      kapalId: int.tryParse(json['kapal_id'].toString()) ?? 0,
      tanggalTrip: json['tanggal_trip'] ?? '',
      jamKumpul: json['jam_kumpul'] ?? '',
      jamBerangkat: json['jam_berangkat'] ?? '',
      jamKembali: json['jam_kembali'] ?? '',
      lokasiKumpul: json['lokasi_kumpul'] ?? '',
      tujuan: json['tujuan'] ?? '',
      hargaPerPeserta: double.tryParse(json['harga_per_peserta'].toString()) ?? 0.0,
      nominalPanjar: double.tryParse(json['nominal_panjar'].toString()) ?? 0.0,
      kuota: int.tryParse(json['kuota'].toString()) ?? 0,
      batasPendaftaran: json['batas_pendaftaran'] ?? '',
      batasPembayaran: json['batas_pembayaran'] ?? '',
      fasilitas: json['fasilitas'],
      ketentuan: json['ketentuan'],
      deskripsi: json['deskripsi'],
      kebijakanPembatalan: json['kebijakan_pembatalan'],
      status: json['status'] ?? 'draft',
      namaKapal: json['nama_kapal'],
      fotoKapal: json['foto_kapal'],
      kapasitasKapal: int.tryParse(json['kapasitas_kapal']?.toString() ?? '0'),
      pesertaTerkonfirmasi: int.tryParse(json['peserta_terkonfirmasi'].toString()) ?? 0,
      sisaKuota: int.tryParse(json['sisa_kuota'].toString()) ?? 0,
      persentaseKuota: double.tryParse(json['persentase_kuota'].toString()) ?? 0.0,
      tanggalFormatted: json['tanggal_formatted'] ?? '',
      hargaFormatted: json['harga_formatted'] ?? '',
      panjarFormatted: json['panjar_formatted'] ?? '',
    );
  }
}
