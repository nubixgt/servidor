import 'package:flutter/material.dart';
import '../utilidades/colores.dart';
import 'pantalla_inicio.dart';

class PantallaPrincipal extends StatefulWidget {
  const PantallaPrincipal({super.key});

  @override
  State<PantallaPrincipal> createState() => _PantallaPrincipalState();
}

class _PantallaPrincipalState extends State<PantallaPrincipal> {
  bool _cargando = false;

  Future<void> _ingresar(BuildContext context) async {
    if (_cargando) return;
    setState(() => _cargando = true);

    // Pequeño delay para ver el spinner (ajústalo si quieres)
    await Future.delayed(const Duration(milliseconds: 500));

    if (!mounted) return;
    Navigator.of(
      context,
    ).pushReplacement(_rutaConAnimacion(const PantallaInicio()));
  }

  PageRoute _rutaConAnimacion(Widget page) {
    return PageRouteBuilder(
      transitionDuration: const Duration(milliseconds: 500),
      reverseTransitionDuration: const Duration(milliseconds: 400),
      pageBuilder: (context, animation, secondaryAnimation) => page,
      transitionsBuilder: (context, animation, secondaryAnimation, child) {
        // Curva suave para el zoom
        var curvedAnimation = CurvedAnimation(
          parent: animation,
          curve: Curves.easeInOutQuart,
        );

        // Zoom de la nueva página (crece desde 0.8 a 1.0)
        var scaleAnimation = Tween<double>(
          begin: 0.8,
          end: 1.0,
        ).animate(curvedAnimation);

        // Fade para que aparezca suavemente
        var fadeAnimation = Tween<double>(
          begin: 0.0,
          end: 1.0,
        ).animate(curvedAnimation);

        // La página actual se hace más pequeña al salir
        var exitScale = Tween<double>(begin: 1.0, end: 1.1).animate(
          CurvedAnimation(
            parent: secondaryAnimation,
            curve: Curves.easeInOutQuart,
          ),
        );

        var exitFade = Tween<double>(begin: 1.0, end: 0.0).animate(
          CurvedAnimation(
            parent: secondaryAnimation,
            curve: Curves.easeInOutQuart,
          ),
        );

        return FadeTransition(
          opacity: exitFade,
          child: ScaleTransition(
            scale: exitScale,
            child: FadeTransition(
              opacity: fadeAnimation,
              child: ScaleTransition(scale: scaleAnimation, child: child),
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFFEFF6FF), Color(0xFFF0FDF4)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: SafeArea(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Card de encabezado/branding
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      gradient: AppColores.gradienteAzulVerde,
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.08),
                          blurRadius: 16,
                          offset: const Offset(0, 8),
                        ),
                      ],
                    ),
                    child: const Column(
                      children: [
                        Text(
                          '🐾',
                          style: TextStyle(fontSize: 56, color: Colors.white),
                        ),
                        SizedBox(height: 8),
                        Text(
                          'UBA Guatemala',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        SizedBox(height: 4),
                        Text(
                          'Unidad de Bienestar Animal',
                          style: TextStyle(
                            color: Colors.white70,
                            fontSize: 13,
                            fontWeight: FontWeight.w300,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 32),

                  const Text(
                    '¡Bienvenido!',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 26,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF1F2937),
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Esta aplicación es de acceso público. Pulsa "Ingresar" para continuar.',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 14, color: Color(0xFF6B7280)),
                  ),
                  const SizedBox(height: 28),

                  // Botón Ingresar con animación de carga
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _cargando ? null : () => _ingresar(context),
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        backgroundColor: AppColores.azulPrimario,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                        elevation: 4,
                      ),
                      child: AnimatedSwitcher(
                        duration: const Duration(milliseconds: 200),
                        transitionBuilder: (child, anim) =>
                            FadeTransition(opacity: anim, child: child),
                        child: _cargando
                            ? const SizedBox(
                                key: ValueKey('loader'),
                                height: 22,
                                width: 22,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2.6,
                                ),
                              )
                            : const Text(
                                'Ingresar',
                                key: ValueKey('label'),
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                      ),
                    ),
                  ),

                  const SizedBox(height: 16),
                  const Text(
                    'Juntos por el bienestar animal 🐶🐱',
                    style: TextStyle(fontSize: 13, color: Color(0xFF6B7280)),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
