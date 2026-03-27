import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../theme/app_theme.dart';

class ReportScreen extends StatelessWidget {
  const ReportScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildHero(context),
          const SizedBox(height: 48),
          _buildIdentificationSection(context),
          const SizedBox(height: 48),
          _buildStepsSection(context),
          const SizedBox(height: 48),
          _buildResources(context),
        ],
      ),
    );
  }

  Widget _buildHero(BuildContext context) {
    return Container(
      height: 450,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(24),
        child: Stack(
          fit: StackFit.expand,
          children: [
            Image.asset(
              'assets/images/hero_home.jpg',
              fit: BoxFit.cover,
            ),
            const DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [Colors.transparent, Colors.black87],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(32),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.end,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                    decoration: BoxDecoration(
                      color: AppColors.secondaryContainer.withOpacity(0.8),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: const Text('Acción Ciudadana', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.white)),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Tu voz protege a los que no tienen una.',
                    style: GoogleFonts.plusJakartaSans(fontSize: 36, fontWeight: FontWeight.w800, color: Colors.white, height: 1.1),
                  ),
                  const SizedBox(height: 12),
                  const Text(
                    'Cada reporte es una oportunidad de salvar una vida silvestre en peligro.',
                    style: TextStyle(color: Colors.white70, fontSize: 18),
                  ),
                  const SizedBox(height: 32),
                  FilledButton.icon(
                    onPressed: () {},
                    icon: const Icon(Icons.arrow_forward),
                    label: const Text('Iniciar Reporte Ciudadano'),
                    style: FilledButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 20),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildIdentificationSection(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Cómo identificar un animal en riesgo', style: GoogleFonts.plusJakartaSans(fontSize: 28, fontWeight: FontWeight.w800, color: Colors.blue.shade900)),
        const SizedBox(height: 8),
        const Text('Observa estos patrones que podrían indicar que un animal necesita ayuda.', style: TextStyle(color: AppColors.onSurfaceVariant)),
        const SizedBox(height: 24),
        _buildInfoCard(
          'Aves Silvestres',
          'Incapacidad de vuelo',
          'Alas caídas, plumaje extremadamente erizado o si el ave no se inmuta ante la presencia humana.',
          'assets/images/onboarding_3.jpg',
        ),
        const SizedBox(height: 16),
        _buildInfoCard(
          'Mamíferos Pequeños',
          'Desorientación diurna',
          'Animales nocturnos vistos a plena luz del día mostrando confusión o movimientos erráticos.',
          'assets/images/onboarding_4.jpg',
        ),
      ],
    );
  }

  Widget _buildInfoCard(String tag, String title, String desc, String img) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.outlineVariant.withOpacity(0.2)),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: Image.asset(img, width: 100, height: 100, fit: BoxFit.cover),
          ),
          const SizedBox(width: 20),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(tag.toUpperCase(), style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppColors.secondary, letterSpacing: 1.2)),
                const SizedBox(height: 4),
                Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                const SizedBox(height: 4),
                Text(desc, style: const TextStyle(fontSize: 14, color: AppColors.onSurfaceVariant)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStepsSection(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(color: AppColors.surfaceContainerLow, borderRadius: BorderRadius.circular(24)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Pasos para una denuncia efectiva', style: GoogleFonts.plusJakartaSans(fontSize: 24, fontWeight: FontWeight.w800)),
          const SizedBox(height: 32),
          _buildStep(1, 'Registro Visual', 'Toma fotos o videos desde una distancia segura.'),
          const SizedBox(height: 24),
          _buildStep(2, 'Ubicación Precisa', 'Identifica puntos de referencia cercanos.'),
          const SizedBox(height: 24),
          _buildStep(3, 'Detalles del Caso', 'Describe brevemente lo que observas.'),
        ],
      ),
    );
  }

  Widget _buildStep(int num, String title, String desc) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CircleAvatar(backgroundColor: AppColors.primary, child: Text('$num', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold))),
        const SizedBox(width: 20),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
              Text(desc, style: const TextStyle(color: AppColors.onSurfaceVariant)),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildResources(BuildContext context) {
    return Column(
      children: [
        _buildActionCard(Icons.help_outline, '¿Necesitas ayuda?', 'Llama a nuestra línea 24/7.', Colors.blue.withOpacity(0.1)),
        const SizedBox(height: 16),
        _buildActionCard(Icons.library_books, 'Guía de Rescate', 'Descarga el manual en PDF.', Colors.orange.withOpacity(0.1)),
      ],
    );
  }

  Widget _buildActionCard(IconData icon, String title, String desc, Color bg) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(borderRadius: BorderRadius.circular(20), border: Border.all(color: AppColors.outlineVariant.withOpacity(0.2))),
      child: Row(
        children: [
          CircleAvatar(backgroundColor: bg, child: Icon(icon, color: AppColors.primary)),
          const SizedBox(width: 20),
          Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
            Text(desc, style: const TextStyle(fontSize: 12, color: AppColors.onSurfaceVariant)),
          ]),
        ],
      ),
    );
  }
}
