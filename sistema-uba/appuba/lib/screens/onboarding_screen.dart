import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:smooth_page_indicator/smooth_page_indicator.dart';
import 'package:google_fonts/google_fonts.dart';
import '../theme/app_theme.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final PageController _controller = PageController();
  int _currentIndex = 0;

  final List<_Slide> _slides = [
    _Slide(
      title: 'Bienvenido a AppUBA',
      subtitle: 'Unidad de Bienestar Animal — MAGA Guatemala',
      text:
          'Tu herramienta oficial para proteger y defender los derechos de los animales en Guatemala. Reporta, da seguimiento y mantente informado.',
      imageUrl: 'assets/images/onboarding_1.jpg',
    ),
    _Slide(
      title: 'Denuncia un caso de maltrato',
      text:
          '¿Encontraste un animal en situación de abandono, maltrato o peligro? Repórtalo aquí de forma rápida y segura. Nuestros inspectores de la Unidad de Bienestar Animal recibirán tu denuncia y actuarán a la brevedad.\n\nCada denuncia cuenta. Tu reporte puede salvar una vida.',
      imageUrl: 'assets/images/onboarding_2.jpg',
    ),
    _Slide(
      title: 'Servicios de Bienestar Animal',
      text:
          'Accede a los servicios que la Unidad de Bienestar Animal del MAGA pone a tu disposición: registro de mascotas, solicitud de inspecciones, campañas de vacunación, esterilización y atención veterinaria en tu municipio.\n\nTodos los servicios son gratuitos y están disponibles para ciudadanos guatemaltecos.',
      imageUrl: 'assets/images/onboarding_3.jpg',
    ),
    _Slide(
      title: 'Noticias y Actualizaciones',
      text:
          'Mantente al día con las últimas acciones, operativos y campañas de la Unidad de Bienestar Animal. Conoce los casos resueltos, próximas jornadas de vacunación y novedades en la legislación de protección animal en Guatemala.\n\nInformación oficial, directamente desde el MAGA.',
      imageUrl: 'assets/images/onboarding_4.jpg',
    ),
  ];

  Future<void> _finishOnboarding() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('onboardingComplete', true);
    if (mounted) Navigator.pushReplacementNamed(context, '/home');
  }

  void _handleNext() {
    if (_currentIndex < _slides.length - 1) {
      _controller.nextPage(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    } else {
      _finishOnboarding();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.surface,
      body: Stack(
        children: [
          PageView.builder(
            controller: _controller,
            onPageChanged: (i) => setState(() => _currentIndex = i),
            itemCount: _slides.length,
            itemBuilder: (context, index) =>
                _SlidePage(slide: _slides[index]),
          ),
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: _buildBottomBar(),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomBar() {
    return Container(
      padding: const EdgeInsets.fromLTRB(24, 16, 24, 40),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.85),
        border: Border(
          top: BorderSide(color: AppColors.outlineVariant.withOpacity(0.3)),
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          TextButton(
            onPressed: _finishOnboarding,
            style: TextButton.styleFrom(
              foregroundColor: AppColors.onSurfaceVariant,
              shape: const StadiumBorder(),
            ),
            child: const Text('Omitir',
                style: TextStyle(fontWeight: FontWeight.bold)),
          ),
          SmoothPageIndicator(
            controller: _controller,
            count: _slides.length,
            effect: ExpandingDotsEffect(
              activeDotColor: AppColors.primary,
              dotColor: AppColors.primary.withOpacity(0.2),
              dotHeight: 8,
              dotWidth: 8,
              expansionFactor: 3,
            ),
          ),
          FilledButton(
            onPressed: _handleNext,
            style: FilledButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              shape: const StadiumBorder(),
              padding:
                  const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
            ),
            child: Text(
              _currentIndex == _slides.length - 1 ? 'Comenzar' : 'Siguiente',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }
}

class _Slide {
  final String title;
  final String? subtitle;
  final String text;
  final String imageUrl;

  const _Slide({
    required this.title,
    this.subtitle,
    required this.text,
    required this.imageUrl,
  });
}

class _SlidePage extends StatelessWidget {
  final _Slide slide;
  const _SlidePage({required this.slide});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        SizedBox(
          height: MediaQuery.of(context).size.height * 0.55,
          width: double.infinity,
          child: Stack(
            fit: StackFit.expand,
            children: [
              Image.asset(
                slide.imageUrl,
                fit: BoxFit.cover,
              ),
              DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.transparent,
                      Colors.black.withOpacity(0.7),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(32, 32, 32, 120),
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Text(
                    slide.title,
                    textAlign: TextAlign.center,
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 26,
                      fontWeight: FontWeight.w900,
                      color: AppColors.primary,
                      height: 1.2,
                    ),
                  ),
                  if (slide.subtitle != null && slide.subtitle!.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(
                      slide.subtitle!,
                      textAlign: TextAlign.center,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: AppColors.secondary,
                        letterSpacing: 1.5,
                      ),
                    ),
                  ],
                  const SizedBox(height: 16),
                  Text(
                    slide.text,
                    textAlign: TextAlign.center,
                    style: GoogleFonts.inter(
                      fontSize: 15,
                      color: AppColors.onSurfaceVariant,
                      height: 1.6,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}
