import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/config/asistente_config.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/primary_button.dart';

/// Hoja de acceso rápido al asistente AgroIA por WhatsApp — se abre desde el
/// botón central del bottom nav y abre un chat con el número del Asistente
/// (whatsapp-bot/), que saluda por nombre y conduce un menú de opciones.
class AsistenteScreen extends StatelessWidget {
  const AsistenteScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: const Color(0xFF0D2630).withValues(alpha: 0.92),
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: AppColors.borde),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                margin: const EdgeInsets.only(bottom: 18),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.25),
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
              Container(
                width: 72,
                height: 72,
                decoration: const BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: LinearGradient(colors: [AppColors.verde, AppColors.verdeFuerte]),
                ),
                alignment: Alignment.center,
                child: const Icon(Icons.eco_rounded, color: Color(0xFF06281A), size: 34),
              ),
              const SizedBox(height: 16),
              Text('Asistente AgroIA', style: AppTextStyles.titulo(size: 19)),
              const SizedBox(height: 8),
              Text(
                'Consulta por WhatsApp sobre tu cultivo o tu hato: envía fotos, audios o tu '
                'ubicación y recibe orientación inicial. Revisa el curso "Uso de WhatsApp AgroIA" '
                'para aprender a sacarle el máximo provecho.',
                textAlign: TextAlign.center,
                style: AppTextStyles.cuerpo(size: 13),
              ),
              const SizedBox(height: 20),
              PrimaryButton(
                label: 'Abrir WhatsApp AgroIA →',
                icon: Icons.chat_bubble_rounded,
                onPressed: () async {
                  Navigator.of(context).pop();
                  final uri = Uri.parse('https://wa.me/${AsistenteConfig.numeroWhatsapp}');
                  final abierto = await launchUrl(uri, mode: LaunchMode.externalApplication);
                  if (!abierto && context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('No se pudo abrir WhatsApp. ¿Está instalado?'),
                        behavior: SnackBarBehavior.floating,
                      ),
                    );
                  }
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}
