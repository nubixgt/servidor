import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'denuncia_form_screen.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import '../theme/app_theme.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

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
          const SizedBox(height: 12), // Reduced space

          // Hero Story
          _buildHero(context),
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
              icon: const Icon(Icons.gavel),
              label: const Text('Realizar Denuncia', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFF8B0000), // Dark Red
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 20),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
              ),
            ),
          ),
          const SizedBox(height: 12),

          // Gallery (Only remaining section)
          _buildGallery(context),
          const SizedBox(height: 48),
        ],
      ),
    ),
    );
  }

  Widget _buildHero(BuildContext context) {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(24),
        child: Stack(
          alignment: Alignment.bottomCenter,
          children: [
            Image.asset(
              'assets/images/imagen2.jpeg',
              width: double.infinity,
              fit: BoxFit.fitWidth,
            ),
            const Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [Colors.transparent, Colors.black45],
                  ),
                ),
              ),
            ),
            Positioned(
              bottom: 50, // Moved significantly higher per user request
              left: 48,
              right: 48,
              child: OutlinedButton(
                onPressed: () async {
                  final url = Uri.parse('https://www.facebook.com/share/p/1bPKokgG61/');
                  if (await canLaunchUrl(url)) {
                    await launchUrl(url, mode: LaunchMode.externalApplication);
                  }
                },
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.white,
                  side: const BorderSide(color: Colors.white, width: 2),
                  shape: const StadiumBorder(),
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
                child: const Text('Ir al enlace', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
              ),
            ),
          ],
        ),
      ),
    );
  }


  Widget _buildGallery(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Galería',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 24,
            fontWeight: FontWeight.w800,
            color: AppColors.primary,
          ),
        ),
        const SizedBox(height: 8),
        GridView.count(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisCount: 2,
          mainAxisSpacing: 4,
          crossAxisSpacing: 4,
          childAspectRatio: 0.85, // Adjusted for better vertical look
          children: [
            _buildGalleryItem('Felinos', 'Recuperación Exitosa', 'assets/images/gallery_felines.jpg'),
            _buildGalleryItem('Decomiso', 'El Guarda', 'assets/images/onboarding_3.jpg'),
          ],
        ),
      ],
    );
  }

  Widget _buildGalleryItem(String category, String title, String img) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(20),
      child: Stack(
        fit: StackFit.expand,
        children: [
          Image.asset(img, fit: BoxFit.cover),
          Positioned(
            bottom: 12,
            left: 12,
            right: 12,
            child: Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.9),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(category.toUpperCase(), style: const TextStyle(fontSize: 8, fontWeight: FontWeight.bold, letterSpacing: 1)),
                  Text(title, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
