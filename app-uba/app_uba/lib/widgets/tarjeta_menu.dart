import 'package:flutter/material.dart';

class TarjetaMenu extends StatelessWidget {
  final String titulo;
  final String subtitulo;
  final IconData icono;
  final Color colorFondo;
  final Color colorIcono;
  final VoidCallback alPresionar;

  const TarjetaMenu({
    super.key,
    required this.titulo,
    required this.subtitulo,
    required this.icono,
    required this.colorFondo,
    required this.colorIcono,
    required this.alPresionar,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      elevation: 4,
      shadowColor: Colors.black.withOpacity(0.1),
      child: InkWell(
        onTap: alPresionar,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(20),
          child: Row(
            children: [
              // Ícono
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: colorFondo,
                  borderRadius: BorderRadius.circular(50),
                ),
                child: Icon(icono, size: 28, color: colorIcono),
              ),

              const SizedBox(width: 16),

              // Texto
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      titulo,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF1F2937),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      subtitulo,
                      style: const TextStyle(
                        fontSize: 14,
                        color: Color(0xFF6B7280),
                      ),
                    ),
                  ],
                ),
              ),

              // Flecha
              const Icon(
                Icons.chevron_right,
                color: Color(0xFF9CA3AF),
                size: 24,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
