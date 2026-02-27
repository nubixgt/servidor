import 'package:flutter/material.dart';
import '../utilidades/colores.dart';
import '../widgets/tarjeta_menu.dart';
import 'pantalla_denuncias.dart';
import 'pantalla_noticias.dart';
import 'pantalla_servicios.dart';

class PantallaInicio extends StatefulWidget {
  const PantallaInicio({super.key});

  @override
  State<PantallaInicio> createState() => _PantallaInicioState();
}

class _PantallaInicioState extends State<PantallaInicio> {
  bool menuAbierto = false;

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
          child: Column(
            children: [
              // Header
              _construirEncabezado(),

              // Hero Section
              _construirSeccionHero(),

              // Menú Principal
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      TarjetaMenu(
                        titulo: 'Denuncias',
                        subtitulo: 'Reporta casos de maltrato animal',
                        icono: Icons.description,
                        colorFondo: Colors.red.shade50,
                        colorIcono: Colors.red.shade600,
                        alPresionar: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => const PantallaDenuncias(),
                            ),
                          );
                        },
                      ),
                      const SizedBox(height: 16),

                      TarjetaMenu(
                        titulo: 'Últimas Noticias',
                        subtitulo: 'Mantente informado',
                        icono: Icons.newspaper,
                        colorFondo: Colors.blue.shade50,
                        colorIcono: Colors.blue.shade600,
                        alPresionar: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => const PantallaNoticias(),
                            ),
                          );
                        },
                      ),
                      const SizedBox(height: 16),

                      TarjetaMenu(
                        titulo: 'Servicios Autorizados',
                        subtitulo: 'Clínicas y veterinarias registradas',
                        icono: Icons.business,
                        colorFondo: Colors.green.shade50,
                        colorIcono: Colors.green.shade600,
                        alPresionar: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => const PantallaServicios(),
                            ),
                          );
                        },
                      ),
                    ],
                  ),
                ),
              ),

              // Footer con íconos de animales
              _construirFooter(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _construirEncabezado() {
    return Container(
      decoration: BoxDecoration(
        gradient: AppColores.gradienteAzulVerde,
        borderRadius: const BorderRadius.only(
          bottomLeft: Radius.circular(24),
          bottomRight: Radius.circular(24),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'UBA Guatemala',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                ),
              ),
              IconButton(
                icon: Icon(
                  menuAbierto ? Icons.close : Icons.menu,
                  color: Colors.white,
                ),
                onPressed: () {
                  setState(() {
                    menuAbierto = !menuAbierto;
                  });
                },
              ),
            ],
          ),
          const SizedBox(height: 4),
          const Text(
            'Unidad de Bienestar Animal',
            style: TextStyle(
              color: Colors.white,
              fontSize: 14,
              fontWeight: FontWeight.w300,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Protegiendo a quienes no tienen voz',
            style: TextStyle(
              color: Colors.white.withOpacity(0.8),
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }

  Widget _construirSeccionHero() {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 32),
      child: Column(
        children: [
          Text('🐾', style: TextStyle(fontSize: 60)),
          SizedBox(height: 16),
          Text(
            'Bienvenido',
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1F2937),
            ),
          ),
          SizedBox(height: 8),
          Text(
            'Juntos por el bienestar animal',
            style: TextStyle(fontSize: 14, color: Color(0xFF6B7280)),
          ),
        ],
      ),
    );
  }

  Widget _construirFooter() {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 24),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text('🐕', style: TextStyle(fontSize: 40)),
          SizedBox(width: 12),
          Text('🐈', style: TextStyle(fontSize: 40)),
          SizedBox(width: 12),
          Text('🐎', style: TextStyle(fontSize: 40)),
          SizedBox(width: 12),
          Text('🦜', style: TextStyle(fontSize: 40)),
        ],
      ),
    );
  }
}
