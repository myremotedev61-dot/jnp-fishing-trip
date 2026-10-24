class NotifikasiModel {
  final int id;
  final int userId;
  final String judul;
  final String pesan;
  final String jenis;
  final Map<String, dynamic>? dataReferensi;
  final String? dibacaPada;
  final String createdAt;

  NotifikasiModel({
    required this.id,
    required this.userId,
    required this.judul,
    required this.pesan,
    required this.jenis,
    this.dataReferensi,
    this.dibacaPada,
    required this.createdAt,
  });

  bool get isRead => dibacaPada != null;

  factory NotifikasiModel.fromJson(Map<String, dynamic> json) {
    return NotifikasiModel(
      id: int.tryParse(json['id'].toString()) ?? 0,
      userId: int.tryParse(json['user_id'].toString()) ?? 0,
      judul: json['judul'] ?? '',
      pesan: json['pesan'] ?? '',
      jenis: json['jenis'] ?? 'sistem',
      dataReferensi: json['data_referensi'] != null ? Map<String, dynamic>.from(json['data_referensi']) : null,
      dibacaPada: json['dibaca_pada'],
      createdAt: json['created_at'] ?? '',
    );
  }
}

class RekeningBank {
  final String bank;
  final String nomor;
  final String pemilik;

  RekeningBank({required this.bank, required this.nomor, required this.pemilik});

  factory RekeningBank.fromJson(Map<String, dynamic> json) {
    return RekeningBank(
      bank: json['bank'] ?? '',
      nomor: json['nomor'] ?? '',
      pemilik: json['pemilik'] ?? '',
    );
  }
}

class PengaturanUsahaModel {
  final String namaUsaha;
  final String noWhatsapp;
  final String? alamat;
  final String zonaWaktu;
  final List<RekeningBank> rekening;
  final int minimalPanjarPersen;
  final String? kebijakanRefund;

  PengaturanUsahaModel({
    required this.namaUsaha,
    required this.noWhatsapp,
    this.alamat,
    required this.zonaWaktu,
    required this.rekening,
    required this.minimalPanjarPersen,
    this.kebijakanRefund,
  });

  factory PengaturanUsahaModel.fromJson(Map<String, dynamic> json) {
    var rawRek = json['rekening_pembayaran'] as List? ?? [];
    List<RekeningBank> listRek = rawRek.map((r) => RekeningBank.fromJson(r)).toList();

    var panjar = json['pengaturan_panjar'] as Map<String, dynamic>? ?? {};

    return PengaturanUsahaModel(
      namaUsaha: json['nama_usaha'] ?? 'Fishing Trip Turatea',
      noWhatsapp: json['no_whatsapp'] ?? '6281234567890',
      alamat: json['alamat'],
      zonaWaktu: json['zona_waktu'] ?? 'Asia/Makassar',
      rekening: listRek,
      minimalPanjarPersen: int.tryParse(panjar['minimal_panjar_persen']?.toString() ?? '30') ?? 30,
      kebijakanRefund: panjar['kebijakan_refund'],
    );
  }
}
