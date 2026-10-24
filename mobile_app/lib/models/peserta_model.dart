class FinancialSummary {
  final double hargaTotal;
  final double totalTerverifikasi;
  final double totalRefund;
  final double pembayaranBersih;
  final double sisaTagihan;
  final String statusPembayaran;
  final double pembayaranMenunggu;

  FinancialSummary({
    required this.hargaTotal,
    required this.totalTerverifikasi,
    required this.totalRefund,
    required this.pembayaranBersih,
    required this.sisaTagihan,
    required this.statusPembayaran,
    required this.pembayaranMenunggu,
  });

  factory FinancialSummary.fromJson(Map<String, dynamic> json) {
    return FinancialSummary(
      hargaTotal: double.tryParse(json['harga_total'].toString()) ?? 0.0,
      totalTerverifikasi: double.tryParse(json['total_terverifikasi'].toString()) ?? 0.0,
      totalRefund: double.tryParse(json['total_refund'].toString()) ?? 0.0,
      pembayaranBersih: double.tryParse(json['pembayaran_bersih'].toString()) ?? 0.0,
      sisaTagihan: double.tryParse(json['sisa_tagihan'].toString()) ?? 0.0,
      statusPembayaran: json['status_pembayaran'] ?? 'Belum Bayar',
      pembayaranMenunggu: double.tryParse(json['pembayaran_menunggu'].toString()) ?? 0.0,
    );
  }
}

class PesertaModel {
  final int id;
  final int pendaftaranId;
  final int tripId;
  final String namaPemancing;
  final String noTelepon;
  final String statusPersetujuan;
  final String? alasanPenolakan;
  final double hargaFinal;
  final FinancialSummary? keuangan;

  PesertaModel({
    required this.id,
    required this.pendaftaranId,
    required this.tripId,
    required this.namaPemancing,
    required this.noTelepon,
    required this.statusPersetujuan,
    this.alasanPenolakan,
    required this.hargaFinal,
    this.keuangan,
  });

  factory PesertaModel.fromJson(Map<String, dynamic> json) {
    return PesertaModel(
      id: int.tryParse(json['id'].toString()) ?? 0,
      pendaftaranId: int.tryParse(json['pendaftaran_id'].toString()) ?? 0,
      tripId: int.tryParse(json['trip_id'].toString()) ?? 0,
      namaPemancing: json['nama_pemancing'] ?? '',
      noTelepon: json['no_telepon'] ?? '',
      statusPersetujuan: json['status_persetujuan'] ?? 'menunggu_persetujuan',
      alasanPenolakan: json['alasan_penolakan'],
      hargaFinal: double.tryParse(json['harga_final'].toString()) ?? 0.0,
      keuangan: json['keuangan'] != null ? FinancialSummary.fromJson(json['keuangan']) : null,
    );
  }
}
