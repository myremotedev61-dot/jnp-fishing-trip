class UserModel {
  final int id;
  final String nama;
  final String? email;
  final String noTelepon;
  final String role;
  final String statusAkun;
  final String? fotoProfil;
  final Map<String, dynamic>? ringkasan;

  UserModel({
    required this.id,
    required this.nama,
    this.email,
    required this.noTelepon,
    required this.role,
    required this.statusAkun,
    this.fotoProfil,
    this.ringkasan,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: int.tryParse(json['id'].toString()) ?? 0,
      nama: json['nama'] ?? '',
      email: json['email'],
      noTelepon: json['no_telepon'] ?? '',
      role: json['role'] ?? 'pemancing',
      statusAkun: json['status_akun'] ?? 'aktif',
      fotoProfil: json['foto_profil'],
      ringkasan: json['ringkasan'] != null ? Map<String, dynamic>.from(json['ringkasan']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'nama': nama,
      'email': email,
      'no_telepon': noTelepon,
      'role': role,
      'status_akun': statusAkun,
      'foto_profil': fotoProfil,
    };
  }
}
