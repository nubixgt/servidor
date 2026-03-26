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
            child: CachedNetworkImage(
              imageUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCHNfKqju7FrEltSB3vtAL59XzHqX_gFLzf5EpI_YtcknRZ4JRYm6UAXQ_F6Ovfr1viNer2InBhK3LDHGMlkwfQvRQtvYEILJCDkc8CIAOz7gb3YK2YwGsoLKhPtTaYPulEb1yi3GTr38gfGR544b-7RXaKRF8Z0jKueImO6qJVjzkrnqFxNJqY75uiRxZYesTy326FasE1rUjVbimsPoIphcORuWy5bAjL0Kddn0m4Ryxj4VwuC6Ji0KFw0jlOaE6xjPLCnShfd1E',
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
        _buildDiscoveryCard('Avistamiento Raro', 'Hace 2h', 'https://lh3.googleusercontent.com/aida-public/AB6AXuAPWqp_CWB_fJprbJts-ppkGa5nwcVBZR4mP_CVIe1dwZeAiwA8O4OxAnq7BPxWlFNnx-4KblDrm6ESJZS4hcLpm5y5Lzn3YQvcni6n9Kke1Y7r4kMFJL5wrGnrmZXDenk0dECaWzbvNSZ1we6xyT6lZ3fCdzEw-yuabkIUftBcwu_k1s6L5tiFAP1F1_4oR4qA-CsD1h7plm4AguP4ypLvp9-VOKEKgPmgGCdUS7DYrtVicLcIO1jXok-0bJeZ8CNTfsgGQUxtWc8'),
        _buildDiscoveryCard('Humedales', 'Hace 5h', 'https://lh3.googleusercontent.com/aida-public/AB6AXuCMO-pflygLmfGuqGa4Z3n6iJBtjvAZpW1GFZJuJODdxLR_tD7mEbXbaOWVXcg1InuGJy-vny0F7zXM-Gmxx2K0itnmmDjuCCe4udDAaF4OOdwB8UvUMeVmv0vK5stZx_QTuEpE8o8xdNmUwX6bie6wj1alpaA_px6zSLopBDbB46FffTfMlYsiDG8VDHCSgyFwEtczd5OcQiifDWaaQ-08hqwIwiO0Vju427uZASsNoMxG_N6D3eo2l5PHQ0oWfNKfoKDxITZ44Zw'),
      ],
    );
  }

  Widget _buildDiscoveryCard(String title, String time, String img) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 24),
      child: Row(
        children: [
          ClipRRect(borderRadius: BorderRadius.circular(16), child: CachedNetworkImage(imageUrl: img, width: 100, height: 100, fit: BoxFit.cover)),
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
