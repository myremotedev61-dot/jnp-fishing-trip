import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../constants/app_constants.dart';
import '../models/user_model.dart';
import '../models/trip_model.dart';
import '../models/pendaftaran_model.dart';
import '../models/pembayaran_model.dart';
import '../models/notifikasi_model.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  String _baseUrl = AppConstants.defaultBaseUrl;
  String? _token;

  String get baseUrl => _baseUrl;

  /// Helper penanganan pesan ramah ketika internet terputus atau server mati
  String handleNetworkError(dynamic e) {
    if (e is SocketException) {
      return 'Koneksi internet terputus. Silakan periksa jaringan HP Anda.';
    } else if (e is TimeoutException) {
      return 'Waktu koneksi habis. Server Fishing Trip sedang tidak dapat dijangkau.';
    } else {
      return 'Tidak dapat terhubung ke server. Silakan coba beberapa saat lagi.';
    }
  }

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(AppConstants.keyToken);
    final customUrl = prefs.getString(AppConstants.keyBaseUrl);
    if (customUrl != null && customUrl.isNotEmpty) {
      _baseUrl = customUrl;
    }
  }

  Future<void> setBaseUrl(String url) async {
    _baseUrl = url.endsWith('/') ? url.substring(0, url.length - 1) : url;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(AppConstants.keyBaseUrl, _baseUrl);
  }

  bool get isAuthenticated => _token != null && _token!.isNotEmpty;

  Map<String, String> _headers({bool withAuth = true, bool isJson = true}) {
    final headers = <String, String>{
      'Accept': 'application/json',
    };
    if (isJson) {
      headers['Content-Type'] = 'application/json';
    }
    if (withAuth && _token != null) {
      headers['Authorization'] = 'Bearer $_token';
    }
    return headers;
  }

  Future<void> saveSession(String token, UserModel user) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(AppConstants.keyToken, token);
    await prefs.setString(AppConstants.keyUser, jsonEncode(user.toJson()));
  }

  Future<void> clearSession() async {
    _token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(AppConstants.keyToken);
    await prefs.remove(AppConstants.keyUser);
  }

  // ==========================================
  // AUTH
  // ==========================================
  Future<Map<String, dynamic>> login(String identifier, String password) async {
    try {
      final res = await http.post(
        Uri.parse('$_baseUrl/auth/login'),
        headers: _headers(withAuth: false),
        body: jsonEncode({'login': identifier, 'password': password}),
      ).timeout(const Duration(seconds: 15));

      final data = jsonDecode(res.body);
      if (res.statusCode == 200 && data['status'] == true) {
        final user = UserModel.fromJson(data['data']['user']);
        final token = data['data']['token'];
        await saveSession(token, user);
        return {'success': true, 'user': user, 'token': token};
      }
      return {'success': false, 'message': data['message'] ?? 'Login gagal'};
    } catch (e) {
      return {'success': false, 'message': 'Gagal terhubung ke server: Periksa koneksi internet Anda.'};
    }
  }

  Future<Map<String, dynamic>> register(String nama, String noTelepon, String password, {String? email}) async {
    try {
      final body = {
        'nama': nama,
        'no_telepon': noTelepon,
        'password': password,
      };
      if (email != null && email.isNotEmpty) body['email'] = email;

      final res = await http.post(
        Uri.parse('$_baseUrl/auth/register'),
        headers: _headers(withAuth: false),
        body: jsonEncode(body),
      ).timeout(const Duration(seconds: 15));

      final data = jsonDecode(res.body);
      if (res.statusCode == 201 && data['status'] == true) {
        final user = UserModel.fromJson(data['data']['user']);
        final token = data['data']['token'];
        await saveSession(token, user);
        return {'success': true, 'user': user, 'token': token};
      }
      return {'success': false, 'message': data['message'] ?? 'Registrasi gagal'};
    } catch (e) {
      return {'success': false, 'message': 'Gagal terhubung ke server.'};
    }
  }

  Future<UserModel?> getProfile() async {
    try {
      final res = await http.get(
        Uri.parse('$_baseUrl/auth/me'),
        headers: _headers(),
      ).timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == true) {
          return UserModel.fromJson(data['data']);
        }
      }
      return null;
    } catch (_) {
      return null;
    }
  }

  // ==========================================
  // TRIPS & KAPAL
  // ==========================================
  Future<List<TripModel>> getTrips({int? bulan, int? tahun, String? status, String? query}) async {
    try {
      var uri = '$_baseUrl/trips?';
      if (bulan != null) uri += 'bulan=$bulan&';
      if (tahun != null) uri += 'tahun=$tahun&';
      if (status != null) uri += 'status=$status&';
      if (query != null && query.isNotEmpty) uri += 'q=${Uri.encodeComponent(query)}';

      final res = await http.get(Uri.parse(uri), headers: _headers(withAuth: false))
          .timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == true && data['data'] != null) {
          return (data['data'] as List).map((t) => TripModel.fromJson(t)).toList();
        }
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  Future<TripModel?> getTripDetail(int tripId) async {
    try {
      final res = await http.get(Uri.parse('$_baseUrl/trips/$tripId'), headers: _headers(withAuth: false))
          .timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == true && data['data'] != null) {
          return TripModel.fromJson(data['data']);
        }
      }
      return null;
    } catch (_) {
      return null;
    }
  }

  Future<PengaturanUsahaModel?> getPengaturan() async {
    try {
      final res = await http.get(Uri.parse('$_baseUrl/pengaturan'), headers: _headers(withAuth: false))
          .timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == true && data['data'] != null) {
          return PengaturanUsahaModel.fromJson(data['data']);
        }
      }
      return null;
    } catch (_) {
      return null;
    }
  }

  // ==========================================
  // PENDAFTARAN TRIP
  // ==========================================
  Future<Map<String, dynamic>> submitPendaftaran(int tripId, List<Map<String, String>> peserta, {String catatan = ''}) async {
    try {
      final body = {
        'trip_id': tripId,
        'catatan': catatan,
        'peserta': peserta,
      };

      final res = await http.post(
        Uri.parse('$_baseUrl/pendaftaran'),
        headers: _headers(),
        body: jsonEncode(body),
      ).timeout(const Duration(seconds: 15));

      final data = jsonDecode(res.body);
      if (res.statusCode == 201 && data['status'] == true) {
        return {'success': true, 'data': data['data'], 'message': data['message']};
      }
      return {'success': false, 'message': data['message'] ?? 'Gagal mendaftar trip.'};
    } catch (e) {
      return {'success': false, 'message': 'Gagal terhubung ke server saat pendaftaran.'};
    }
  }

  Future<List<PendaftaranModel>> getMyRegistrations({String tab = 'all'}) async {
    try {
      final res = await http.get(
        Uri.parse('$_baseUrl/pendaftaran/saya?tab=$tab'),
        headers: _headers(),
      ).timeout(const Duration(seconds: 12));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == true && data['data'] != null) {
          return (data['data'] as List).map((p) => PendaftaranModel.fromJson(p)).toList();
        }
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  Future<PendaftaranModel?> getPendaftaranDetail(int id) async {
    try {
      final res = await http.get(
        Uri.parse('$_baseUrl/pendaftaran/$id'),
        headers: _headers(),
      ).timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == true && data['data'] != null) {
          return PendaftaranModel.fromJson(data['data']);
        }
      }
      return null;
    } catch (_) {
      return null;
    }
  }

  Future<Map<String, dynamic>> updatePeserta(int pesertaId, String nama, String noTelepon) async {
    try {
      final res = await http.put(
        Uri.parse('$_baseUrl/peserta/$pesertaId'),
        headers: _headers(),
        body: jsonEncode({'nama_pemancing': nama, 'no_telepon': noTelepon}),
      ).timeout(const Duration(seconds: 10));

      final data = jsonDecode(res.body);
      return {'success': data['status'] == true, 'message': data['message']};
    } catch (_) {
      return {'success': false, 'message': 'Gagal memperbarui data peserta.'};
    }
  }

  // ==========================================
  // PEMBAYARAN & UPLOAD BUKTI TRANSFER
  // ==========================================
  Future<Map<String, dynamic>> submitPembayaran({
    required int pesertaTripId,
    required double nominal,
    required String metode,
    required String tanggalTransaksi,
    String? nomorRef,
    required File fileBukti,
  }) async {
    try {
      var request = http.MultipartRequest('POST', Uri.parse('$_baseUrl/pembayaran'));
      request.headers.addAll({
        'Accept': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      });

      request.fields['peserta_trip_id'] = pesertaTripId.toString();
      request.fields['nominal'] = nominal.toStringAsFixed(0);
      request.fields['metode_pembayaran'] = metode;
      request.fields['tanggal_transaksi'] = tanggalTransaksi;
      if (nomorRef != null && nomorRef.isNotEmpty) {
        request.fields['nomor_referensi'] = nomorRef;
      }

      request.files.add(await http.MultipartFile.fromPath('bukti_pembayaran', fileBukti.path));

      var streamedRes = await request.send().timeout(const Duration(seconds: 30));
      var res = await http.Response.fromStream(streamedRes);

      final data = jsonDecode(res.body);
      if (res.statusCode == 201 && data['status'] == true) {
        return {'success': true, 'data': data['data'], 'message': data['message']};
      }
      return {'success': false, 'message': data['message'] ?? 'Gagal mengunggah pembayaran.'};
    } catch (e) {
      return {'success': false, 'message': 'Gagal mengunggah berkas bukti transfer: $e'};
    }
  }

  Future<List<PembayaranModel>> getMyPayments() async {
    try {
      final res = await http.get(Uri.parse('$_baseUrl/pembayaran/saya'), headers: _headers())
          .timeout(const Duration(seconds: 12));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == true && data['data'] != null) {
          return (data['data'] as List).map((p) => PembayaranModel.fromJson(p)).toList();
        }
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  // ==========================================
  // NOTIFIKASI
  // ==========================================
  Future<Map<String, dynamic>> getNotifikasi() async {
    try {
      final res = await http.get(Uri.parse('$_baseUrl/notifikasi'), headers: _headers())
          .timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == true && data['data'] != null) {
          int unread = int.tryParse(data['data']['belum_dibaca']?.toString() ?? '0') ?? 0;
          List<NotifikasiModel> list = (data['data']['notifikasi'] as List)
              .map((n) => NotifikasiModel.fromJson(n))
              .toList();
          return {'success': true, 'unread': unread, 'list': list};
        }
      }
      return {'success': true, 'unread': 0, 'list': <NotifikasiModel>[]};
    } catch (_) {
      return {'success': false, 'unread': 0, 'list': <NotifikasiModel>[]};
    }
  }

  Future<void> markNotifRead(int id) async {
    try {
      await http.put(Uri.parse('$_baseUrl/notifikasi/$id/read'), headers: _headers());
    } catch (_) {}
  }
}
