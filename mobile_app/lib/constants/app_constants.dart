class AppConstants {
  static const String appName = 'Fishing Trip';
  static const String appTagline = 'Aplikasi Reservasi Kapal Pemancingan Laut';

  /// Mendukung konfigurasi URL dinamis saat build:
  /// flutter build apk --release --dart-define=API_URL=https://fishingtrip.yourdomain.com/api
  /// Jika tidak dispesifikasikan, default adalah emulator loopback / local dev
  static const String defaultBaseUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'http://10.0.2.2:8080/api',
  );

  static const String keyToken = 'auth_token';
  static const String keyUser = 'user_data';
  static const String keyBaseUrl = 'custom_base_url';
}
