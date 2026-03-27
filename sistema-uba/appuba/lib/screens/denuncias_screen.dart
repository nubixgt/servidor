import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../theme/app_theme.dart';
import 'denuncia_form_screen.dart';

class DenunciasScreen extends StatelessWidget {
  const DenunciasScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Denuncias y Servicios',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 32,
                  fontWeight: FontWeight.w900,
                  color: AppColors.primary,
                ),
              ),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const DenunciaFormScreen()),
                    );
                  },
                  icon: const Icon(Icons.add_circle_outline),
                  label: const Text('Iniciar Nueva Denuncia'),
                  style: FilledButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 32),
          _buildUrgencyCard(),
          const SizedBox(height: 32),
          _buildBentoSections(),
          const SizedBox(height: 32),
          _buildEditorialSection(),
        ],
      ),
    );
  }

  Widget _buildUrgencyCard() {
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
              const CircleAvatar(backgroundColor: Color(0x15FFA454), child: Icon(Icons.emergency, color: AppColors.secondary)),
              const SizedBox(width: 12),
              const Text('URGENCIAS', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.2, fontSize: 10, color: AppColors.secondary)),
              const Spacer(),
              FilledButton.icon(onPressed: () {}, icon: const Icon(Icons.phone), label: const Text('Llamar')),
            ],
          ),
          const SizedBox(height: 24),
          _buildVetItem('Clínica Central Fauna', 'Especialistas en vida silvestre', 'A 2.4 km'),
          const Divider(height: 32),
          _buildVetItem('Hospital Vet-H24', 'Atención inmediata', 'A 5.1 km'),
        ],
      ),
    );
  }

  Widget _buildVetItem(String name, String desc, String dist) {
    return Row(
      children: [
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(name, style: const TextStyle(fontWeight: FontWeight.bold)),
          Text(desc, style: const TextStyle(fontSize: 12, color: AppColors.onSurfaceVariant)),
        ])),
        Text(dist, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppColors.primary)),
      ],
    );
  }

  Widget _buildBentoSections() {
    return Column(
      children: [
        Container(
          height: 150,
          decoration: BoxDecoration(color: Colors.blue.shade50, borderRadius: BorderRadius.circular(24)),
          padding: const EdgeInsets.all(24),
          child: const Row(children: [
            Icon(Icons.map, size: 40, color: AppColors.primary),
            SizedBox(width: 20),
            Expanded(child: Text('Zonas Protegidas\nGPS en tiempo real', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18))),
          ]),
        ),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(24)),
          child: const Row(children: [
            Icon(Icons.medical_services, color: Colors.white, size: 40),
            SizedBox(width: 20),
            Expanded(child: Text('Primeros Auxilios\nGuías interactivas', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18))),
          ]),
        ),
      ],
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
