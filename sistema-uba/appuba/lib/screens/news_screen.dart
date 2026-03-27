import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../theme/app_theme.dart';

class NewsScreen extends StatelessWidget {
  const NewsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildHeader(),
          const SizedBox(height: 32),
          _buildFeaturedNews(),
          const SizedBox(height: 32),
          _buildRecentSection(),
          const SizedBox(height: 32),
          _buildNewsletter(),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('EXPLORA EL ECOSISTEMA', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, letterSpacing: 1.5, color: AppColors.primary)),
        const SizedBox(height: 8),
        Text('Crónicas de la Vida Silvestre', style: GoogleFonts.plusJakartaSans(fontSize: 32, fontWeight: FontWeight.w900, height: 1.1)),
      ],
    );
  }

  Widget _buildFeaturedNews() {
    return Container(
      decoration: BoxDecoration(borderRadius: BorderRadius.circular(24), color: Colors.white, boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 20, offset: const Offset(0, 10))]),
      child: Column(
        children: [
          ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
             child: Image.asset(
              'assets/images/gallery_felines.jpg',
              height: 250, width: double.infinity, fit: BoxFit.cover,
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('LEGISLACIÓN URGENTE', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppColors.secondary)),
                const SizedBox(height: 12),
                Text('Nueva Ley de Protección de Corredores Biológicos', style: GoogleFonts.plusJakartaSans(fontSize: 22, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                const Text('El parlamento aprueba por unanimidad la protección de rutas migratorias críticas.', style: TextStyle(color: AppColors.onSurfaceVariant)),
                const SizedBox(height: 20),
                FilledButton(onPressed: () {}, child: const Text('Leer artículo')),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRecentSection() {
    return Column(
      children: [
        _buildDiscoveryCard('Avistamiento Raro', 'Hace 2h', 'assets/images/onboarding_2.jpg'),
        _buildDiscoveryCard('Humedales', 'Hace 5h', 'assets/images/onboarding_4.jpg'),
      ],
    );
  }

  Widget _buildDiscoveryCard(String title, String time, String img) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 24),
      child: Row(
        children: [
          ClipRRect(borderRadius: BorderRadius.circular(16), child: Image.asset(img, width: 100, height: 100, fit: BoxFit.cover)),
          const SizedBox(width: 20),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(time, style: const TextStyle(fontSize: 10, color: AppColors.outline)),
            const SizedBox(height: 4),
            Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          ])),
        ],
      ),
    );
  }

  Widget _buildNewsletter() {
    return Container(
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(24)),
      child: Column(
        children: [
          const Text('Mantente Informado', style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold)),
          const SizedBox(height: 24),
          TextField(decoration: InputDecoration(filled: true, fillColor: Colors.white10, hintText: 'tu@email.com', hintStyle: const TextStyle(color: Colors.white38), border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none))),
          const SizedBox(height: 16),
          SizedBox(width: double.infinity, child: FilledButton(onPressed: () {}, style: FilledButton.styleFrom(backgroundColor: Colors.white, foregroundColor: AppColors.primary), child: const Text('Suscribirme'))),
        ],
      ),
    );
  }
}
