import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import '../theme/app_theme.dart';
import 'denuncia_form_screen.dart';

class DenunciasScreen extends StatelessWidget {
  const DenunciasScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Institutional Logos Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Image.asset(
                  'assets/images/LogoUBA3.png',
                  height: 80,
                  fit: BoxFit.contain,
                ),
                Image.asset(
                  'assets/images/maga_logo.png',
                  height: 80,
                  fit: BoxFit.contain,
                ),
              ],
            ),
            const SizedBox(height: 12),
            Center(
              child: Text(
                'Información',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 32,
                  fontWeight: FontWeight.w900,
                  color: AppColors.primary,
                ),
              ),
            ),
            const SizedBox(height: 12),
            _buildContactCard(),
            const SizedBox(height: 32),
            _buildEditorialSection(),
          ],
        ),
      ),
    );
  }

  Widget _buildContactCard() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.outlineVariant.withOpacity(0.2)),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 20)],
      ),
      child: Column(
        children: [
          Row(
            children: [
              const CircleAvatar(
                backgroundColor: Color(0x15FFA454),
                child: Icon(Icons.wb_sunny_outlined, color: Color(0xFFC06C00)),
              ),
              const SizedBox(width: 12),
              const Text('Contacto', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.2, fontSize: 14, color: Color(0xFFC06C00))),
              const Spacer(),
              FilledButton.icon(
                onPressed: () async {
                  final Uri url = Uri.parse('tel:+50224137070');
                  if (await canLaunchUrl(url)) {
                    await launchUrl(url);
                  }
                },
                icon: const Icon(Icons.phone),
                label: const Text('Llamar'),
                style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFF0F172A), // Dark Navy
                  foregroundColor: Colors.white,
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          const Text(
            'UBA',
            style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18, color: Color(0xFF0F172A)),
          ),
          const Text(
            'UNIDAD DE BIENESTAR ANIMAL',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
          ),
          const SizedBox(height: 16),
          RichText(
            textAlign: TextAlign.center,
            text: const TextSpan(
              style: TextStyle(color: Color(0xFF0F172A), fontSize: 14),
              children: [
                TextSpan(text: 'Dirección: ', style: TextStyle(fontWeight: FontWeight.bold)),
                TextSpan(text: '4A Calle 0-15, Cdad. de Guatemala\n'),
                TextSpan(text: 'Teléfono: ', style: TextStyle(fontWeight: FontWeight.bold)),
                TextSpan(text: '2413 7070'),
              ],
            ),
          ),
        ],
      ),
    );
  }


  Widget _buildEditorialSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Recursos Destacados', style: GoogleFonts.plusJakartaSans(fontSize: 24, fontWeight: FontWeight.bold)),
        const SizedBox(height: 24),
        GridView.count(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisCount: 1,
          childAspectRatio: 1.6,
          mainAxisSpacing: 24,
          children: [
            _buildResourceCard('Protección de Quelonios', 'Guía PDF', 'assets/images/onboarding_1.jpg'),
            _buildResourceCard('Aves Caídas del Nido', 'Video Tutorial', 'assets/images/onboarding_2.jpg'),
          ],
        ),
      ],
    );
  }

  Widget _buildResourceCard(String title, String tag, String img) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(child: ClipRRect(borderRadius: BorderRadius.circular(20), child: Image.asset(img, fit: BoxFit.cover, width: double.infinity))),
        const SizedBox(height: 12),
        Text(tag.toUpperCase(), style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppColors.primary)),
        Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
      ],
    );
  }
}
