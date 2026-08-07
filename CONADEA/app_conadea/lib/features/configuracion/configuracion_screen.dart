import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/back_header.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/primary_button.dart';
import '../auth/login_screen.dart';

/// Configuración — equivalente a Configuracion.vue. Los toggles son solo de
/// interfaz (sin persistencia todavía, pendiente de Backend).
class ConfiguracionScreen extends StatefulWidget {
  const ConfiguracionScreen({super.key});

  @override
  State<ConfiguracionScreen> createState() => _ConfiguracionScreenState();
}

class _ConfiguracionScreenState extends State<ConfiguracionScreen> {
  bool _notif = true;
  bool _recordatorio = true;
  bool _datos = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: AppBackground(
        child: SafeArea(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 40),
            children: [
              const BackHeader(title: 'Configuración'),
              const SizedBox(height: 10),
              GlassCard(
                child: Column(
                  children: [
                    _OpcionConfig(
                      titulo: 'Notificaciones de novedades',
                      descripcion: 'Alertas fitosanitarias, climáticas y avisos del programa.',
                      activo: _notif,
                      onChanged: (v) => setState(() => _notif = v),
                    ),
                    const Divider(color: AppColors.borde, height: 28),
                    _OpcionConfig(
                      titulo: 'Recordatorio de racha',
                      descripcion: 'Aviso diario para mantener tu racha de aprendizaje.',
                      activo: _recordatorio,
                      onChanged: (v) => setState(() => _recordatorio = v),
                    ),
                    const Divider(color: AppColors.borde, height: 28),
                    _OpcionConfig(
                      titulo: 'Modo ahorro de datos',
                      descripcion: 'Reduce imágenes y animaciones para conexiones lentas.',
                      activo: _datos,
                      onChanged: (v) => setState(() => _datos = v),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              GlassCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Datos de la cuenta', style: AppTextStyles.subtitulo(size: 14.5)),
                    const SizedBox(height: 8),
                    Text(
                      'Tu avance se guarda en este dispositivo. Si cierras sesión, podrás retomarlo al volver a ingresar en este mismo equipo.',
                      style: AppTextStyles.cuerpo(size: 12.5),
                    ),
                    const SizedBox(height: 14),
                    PrimaryButton(
                      label: '🚪 Cerrar sesión',
                      expand: false,
                      onPressed: () => Navigator.of(context).pushAndRemoveUntil(
                        MaterialPageRoute(builder: (_) => const LoginScreen()),
                        (route) => false,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _OpcionConfig extends StatelessWidget {
  const _OpcionConfig({
    required this.titulo,
    required this.descripcion,
    required this.activo,
    required this.onChanged,
  });

  final String titulo;
  final String descripcion;
  final bool activo;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(titulo, style: AppTextStyles.subtitulo(size: 13.5)),
              const SizedBox(height: 3),
              Text(descripcion, style: AppTextStyles.cuerpo(size: 11.5)),
            ],
          ),
        ),
        const SizedBox(width: 10),
        InkWell(
          onTap: () => onChanged(!activo),
          borderRadius: BorderRadius.circular(12),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(12),
              color: activo ? AppColors.verde.withValues(alpha: 0.1) : AppColors.vidrio2,
              border: Border.all(color: activo ? AppColors.verde : AppColors.bordeClaro),
            ),
            child: Text(
              activo ? 'Activado ✓' : 'Desactivado',
              style: AppTextStyles.etiqueta(size: 11, color: activo ? AppColors.verde : AppColors.textoSuave)
                  .copyWith(letterSpacing: 0),
            ),
          ),
        ),
      ],
    );
  }
}
