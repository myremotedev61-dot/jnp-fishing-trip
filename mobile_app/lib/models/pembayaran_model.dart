class PembayaranModel {
  final int id;
  final String kodePembayaran;
  final int pesertaTripId;
  final double nominal;
  final String metodePembayaran;
  final String tanggalTransaksi;
  final String? nomorReferensi;
  final String buktiPembayaran;
  final String statusVerifikasi;
  final String? namaPemancing;
  final String? tujuan;
  final String? tanggalTrip;
  final String? alasanPenolakan;
  final String? nominalFormatted;

  PembayaranModel({
    required this.id,
    required this.kodePembayaran,
    required this.pesertaTripId,
    required this.nominal,
    required this.metodePembayaran,
    required this.tanggalTransaksi,
    this.nomorReferensi,
    required this.buktiPembayaran,
    required this.statusVerifikasi,
    this.namaPemancing,
    this.tujuan,
    this.tanggalTrip,
    this.alasanPenolakan,
    this.nominalFormatted,
  });

  factory PembayaranModel.fromJson(Map<String, dynamic> json) {
    return PembayaranModel(
      id: int.tryParse(json['id'].toString()) ?? 0,
      kodePembayaran: json['kode_pembayaran'] ?? '',
      pesertaTripId: int.tryParse(json['peserta_trip_id'].toString()) ?? 0,
      nominal: double.tryParse(json['nominal'].toString()) ?? 0.0,
      metodePembayaran: json['metode_pembayaran'] ?? '',
      tanggalTransaksi: json['tanggal_transaksi'] ?? '',
      nomorReferensi: json['nomor_referensi'],
      buktiPembayaran: json['bukti_pembayaran'] ?? '',
      statusVerifikasi: json['status_verifikasi'] ?? 'menunggu_verifikasi',
      namaPemancing: json['nama_pemancing'],
      tujuan: json['tujuan'],
      tanggalTrip: json['tanggal_trip'],
      alasanPenolakan: json['alasan_penolakan'],
      nominalFormatted: json['nominal_formatted'],
    );
  }
}
