import 'package:flutter/material.dart';
import '../theme/app_text_styles.dart';

/// Encabezado con flecha de volver + título, para pantallas empujadas con
/// Navigator (Perfil, Certificados, Configuración, Calendario, Foros, Ayuda,
/// Catálogo) — a diferencia de TopHeader, que es para las pestañas del shell.
class BackHeader extends StatelessWidget {
  const BackHeader({super.key, required this.title, this.subtitle});

  final String title;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 8, 20, 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          IconButton(
            onPressed: () => Navigator.of(context).pop(),
            icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 18),
          ),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(top: 10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: AppTextStyles.titulo(size: 19)),
                  if (subtitle != null) ...[
                    const SizedBox(height: 4),
                    Text(subtitle!, style: AppTextStyles.cuerpo(size: 12)),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
