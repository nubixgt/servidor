import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'denuncia_form_screen.dart';

import '../theme/app_theme.dart';

class ReportScreen extends StatelessWidget {
  const ReportScreen({super.key});

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
            _buildHero(context),
          const SizedBox(height: 48),
          _buildStepsSection(context),
          const SizedBox(height: 48),
          _buildResources(context),
        ],
      ),
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
              'assets/images/onboarding_3.jpg',
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
                    'Cada reporte es una oportunidad de salvar una vida animal en peligro.',
                    style: TextStyle(color: Colors.white70, fontSize: 18),
                  ),
                  const SizedBox(height: 32),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton.icon(
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => DenunciaFormScreen()),
                        );
                      },
                      icon: const Icon(Icons.gavel),
                      label: const Text('Realizar Denuncia', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
                      style: FilledButton.styleFrom(
                        backgroundColor: const Color(0xFF8B0000), // Dark Red
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 20),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      ),
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
        _buildActionCard(Icons.help_outline, '¿Necesitas ayuda?', '', Colors.blue.withOpacity(0.1)),
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
