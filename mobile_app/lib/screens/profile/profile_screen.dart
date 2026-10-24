import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../constants/app_colors.dart';
import '../../constants/app_constants.dart';
import '../../models/user_model.dart';
import '../../services/api_service.dart';
import '../auth/login_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  UserModel? _user;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    setState(() => _isLoading = true);
    final u = await ApiService().getProfile();
    if (mounted) setState(() { _user = u; _isLoading = false; });
  }

  Future<void> _handleLogout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Konfirmasi Logout'),
        content: const Text('Apakah Anda yakin ingin keluar dari akun ini?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            child: const Text('Keluar'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await ApiService().clearSession();
      if (!mounted) return;
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => const LoginScreen()),
        (route) => false,
      );
    }
  }

  Future<void> _contactOperator() async {
    final settings = await ApiService().getPengaturan();
    final wa = settings?.noWhatsapp ?? '6281234567890';
    final url = Uri.parse('https://wa.me/$wa?text=Halo%20Fishing%20Trip%20Turatea,%20saya%20ingin%20bertanya%20seputar%20layanan%20kapal');
    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    }
  }

  void _showChangeServerDialog() {
    final urlCtrl = TextEditingController(text: ApiService().baseUrl);
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Pengaturan Host API'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: urlCtrl,
              decoration: const InputDecoration(labelText: 'Alamat Server API'),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () async {
              await ApiService().setBaseUrl(urlCtrl.text.trim());
              if (!mounted) return;
              Navigator.pop(ctx);
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text('Alamat server diatur ke: ${ApiService().baseUrl}')),
              );
              _loadUser();
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bgLight,
      appBar: AppBar(
        title: const Text('Profil Pemancing', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
        backgroundColor: Colors.white,
        foregroundColor: AppColors.primaryNavy,
        elevation: 0.5,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  // Avatar & Nama
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 10)],
                    ),
                    child: Column(
                      children: [
                        CircleAvatar(
                          radius: 36,
                          backgroundColor: AppColors.oceanBlue.withOpacity(0.12),
                          child: const Icon(Icons.person, size: 40, color: AppColors.oceanBlue),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          _user?.nama ?? 'Pemancing Turatea',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: AppColors.primaryNavy),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          _user?.noTelepon ?? '-',
                          style: const TextStyle(fontSize: 13, color: AppColors.textMuted),
                        ),
                        if (_user?.email != null) ...[
                          const SizedBox(height: 2),
                          Text(
                            _user!.email!,
                            style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                          ),
                        ],
                        const SizedBox(height: 10),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                          decoration: BoxDecoration(
                            color: AppColors.success.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: const Text(
                            'Akun Terverifikasi',
                            style: TextStyle(color: AppColors.success, fontWeight: FontWeight.bold, fontSize: 11),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Menu Pengaturan Akun
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 8)],
                    ),
                    child: Column(
                      children: [
                        _buildMenuTile(
                          icon: Icons.chat_bubble_outline,
                          iconColor: AppColors.success,
                          title: 'Hubungi WhatsApp Operator Kapal',
                          subtitle: 'Bantuan jadwal, pemesanan, & konfirmasi',
                          onTap: _contactOperator,
                        ),
                        const Divider(height: 1),
                        _buildMenuTile(
                          icon: Icons.wifi,
                          iconColor: AppColors.oceanBlue,
                          title: 'Konfigurasi Server API',
                          subtitle: ApiService().baseUrl,
                          onTap: _showChangeServerDialog,
                        ),
                        const Divider(height: 1),
                        _buildMenuTile(
                          icon: Icons.shield_outlined,
                          iconColor: AppColors.toscaTeal,
                          title: 'Kebijakan Privasi & Ketentuan',
                          subtitle: 'Standar keselamatan berlayar SOLAS',
                          onTap: () {
                            showDialog(
                              context: context,
                              builder: (ctx) => AlertDialog(
                                title: const Text('Ketentuan Perjalanan Laut'),
                                content: const SingleChildScrollView(
                                  child: Text(
                                    '1. Seluruh peserta wajib dalam kondisi fisik yang sehat dan siap berlayar.\n2. Wajib menggunakan jaket pelampung (life jacket) yang disediakan.\n3. Panjar pemesanan diperlukan untuk mengunci slot kursi kapal.\n4. Dilarang membawa zat terlarang atau membahayakan keselamatan pelayaran.',
                                    style: TextStyle(fontSize: 13, height: 1.5),
                                  ),
                                ),
                                actions: [
                                  TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Tutup')),
                                ],
                              ),
                            );
                          },
                        ),
                        const Divider(height: 1),
                        _buildMenuTile(
                          icon: Icons.logout_rounded,
                          iconColor: AppColors.danger,
                          title: 'Keluar dari Akun (Logout)',
                          subtitle: 'Hapus sesi login pada perangkat ini',
                          onTap: _handleLogout,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 30),

                  const Text(
                    'Fishing Trip v1.0.0 (Release Build)',
                    style: TextStyle(color: AppColors.textMuted, fontSize: 12),
                  ),
                  const Text(
                    '© 2026 Fishing Trip Turatea',
                    style: TextStyle(color: AppColors.textLight, fontSize: 11),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _buildMenuTile({
    required IconData icon,
    required Color iconColor,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return ListTile(
      leading: CircleAvatar(
        radius: 18,
        backgroundColor: iconColor.withOpacity(0.12),
        child: Icon(icon, color: iconColor, size: 20),
      ),
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.textDark)),
      subtitle: Text(subtitle, style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
      trailing: const Icon(Icons.chevron_right, size: 20, color: AppColors.textLight),
      onTap: onTap,
    );
  }
}
