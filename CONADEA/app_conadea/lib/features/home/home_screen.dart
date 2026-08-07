import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/curso_row.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/hex_badge.dart';
import '../../core/widgets/primary_button.dart';
import '../../core/widgets/ruta_card.dart';
import '../../core/widgets/section_header.dart';
import '../../data/mock/mock_data.dart';
import '../../data/models/insignia.dart';
import '../../data/state/progreso_controller.dart';
import '../cursos/detalle_curso_screen.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({
    super.key,
    this.onVerCursos,
    this.onVerRutas,
    this.onVerInsignias,
  });

  final VoidCallback? onVerCursos;
  final VoidCallback? onVerRutas;
  final VoidCallback? onVerInsignias;

  @override
  Widget build(BuildContext context) {
    final r = resumenUsuario;
    final rutasPreview = rutas.take(2).toList();
    final insigniasObtenidas = insignias.where((i) => i.obtenida).toList();
    final insigniasPreview = insigniasObtenidas.length >= 3
        ? insigniasObtenidas.sublist(insigniasObtenidas.length - 3)
        : insigniasObtenidas;
    final controller = ProgresoController.instance;

    return ListenableBuilder(
      listenable: controller,
      builder: (context, _) {
        final cursosEnProgreso = cursos.where(controller.enProgreso).take(3).toList();

        return ListView(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 110),
          children: [
            _TopBar(nombre: r.nombre),
            const SizedBox(height: 22),
            _ProgresoGeneralCard(),
            const SizedBox(height: 16),
            _RachaCard(),
            const SizedBox(height: 16),
            _NovedadesCard(),
            const SizedBox(height: 24),
            SectionHeader(title: 'Continuar aprendiendo', actionLabel: 'Ver todos', onAction: onVerCursos),
            GlassCard(
              child: Column(
                children: [
                  for (final c in cursosEnProgreso)
                    CursoRow(
                      curso: c,
                      pct: controller.pctCurso(c),
                      completado: controller.aprobado(c.id),
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => DetalleCursoScreen(cursoId: c.id)),
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            SectionHeader(title: 'Rutas de aprendizaje', actionLabel: 'Ver todas', onAction: onVerRutas),
            for (final ruta in rutasPreview)
              RutaCard(
                ruta: ruta,
                pct: controller.pctRuta(ruta),
                imagenUrl: ruta.cursos.first.imagenUrl,
                onTap: onVerRutas,
              ),
            const SizedBox(height: 10),
            SectionHeader(title: 'Insignias recientes', actionLabel: 'Ver todas', onAction: onVerInsignias),
            GlassCard(
              child: Column(
                children: [
                  for (final b in insigniasPreview) _InsigniaFila(insignia: b),
                ],
              ),
            ),
            const SizedBox(height: 24),
            _Banner(nombre: r.nombre),
          ],
        );
      },
    );
  }
}

class _TopBar extends StatelessWidget {
  const _TopBar({required this.nombre});

  final String nombre;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        _CircleBtn(
          icon: Icons.menu_rounded,
          onTap: () => Scaffold.of(context).openDrawer(),
        ),
        const Spacer(),
        _CircleBtn(
          icon: Icons.notifications_none_rounded,
          badge: '5',
          onTap: () {},
        ),
        const SizedBox(width: 10),
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: const LinearGradient(colors: [AppColors.verde, Color(0xFF16A34A)]),
            border: Border.all(color: AppColors.bordeClaro, width: 2),
          ),
          alignment: Alignment.center,
          child: Text(
            nombre.isNotEmpty ? nombre[0].toUpperCase() : '?',
            style: AppTextStyles.subtitulo(size: 15).copyWith(color: const Color(0xFF06281A)),
          ),
        ),
      ],
    );
  }
}

class _CircleBtn extends StatelessWidget {
  const _CircleBtn({required this.icon, required this.onTap, this.badge});

  final IconData icon;
  final VoidCallback onTap;
  final String? badge;

  @override
  Widget build(BuildContext context) {
    return InkResponse(
      onTap: onTap,
      radius: 24,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: AppColors.vidrio2,
              border: Border.all(color: AppColors.borde),
            ),
            child: Icon(icon, color: Colors.white, size: 20),
          ),
          if (badge != null)
            Positioned(
              top: -2,
              right: -2,
              child: Container(
                padding: const EdgeInsets.all(3),
                constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                decoration: const BoxDecoration(color: AppColors.verde, shape: BoxShape.circle),
                alignment: Alignment.center,
                child: Text(
                  badge!,
                  style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w800, color: Color(0xFF06281A)),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _ProgresoGeneralCard extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final r = resumenUsuario;
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Tu progreso general', style: AppTextStyles.subtitulo(size: 15)),
          const SizedBox(height: 16),
          Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              SizedBox(
                width: 96,
                height: 96,
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    SizedBox(
                      width: 96,
                      height: 96,
                      child: CircularProgressIndicator(
                        value: r.progresoGlobalPct / 100,
                        strokeWidth: 8,
                        backgroundColor: Colors.white.withValues(alpha: 0.08),
                        valueColor: const AlwaysStoppedAnimation(AppColors.verde),
                        strokeCap: StrokeCap.round,
                      ),
                    ),
                    Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text('${r.progresoGlobalPct}%', style: AppTextStyles.titulo(size: 20)),
                        Text('Completado', style: AppTextStyles.etiqueta(size: 9)),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 20),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _StatLinea(emoji: '📗', label: 'Cursos completados', valor: '${r.cursosCompletados}'),
                    const SizedBox(height: 10),
                    _StatLinea(emoji: '📈', label: 'En progreso', valor: '${r.enProgreso}'),
                    const SizedBox(height: 10),
                    _StatLinea(emoji: '⏱️', label: 'Horas de capacitación', valor: '${r.horasCapacitacion}h'),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          PrimaryButton(label: 'Ver mi progreso →', onPressed: () {}),
        ],
      ),
    );
  }
}

class _StatLinea extends StatelessWidget {
  const _StatLinea({required this.emoji, required this.label, required this.valor});

  final String emoji;
  final String label;
  final String valor;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 28,
          height: 28,
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(9), color: AppColors.vidrio2),
          alignment: Alignment.center,
          child: Text(emoji, style: const TextStyle(fontSize: 13)),
        ),
        const SizedBox(width: 10),
        Expanded(child: Text(label, style: AppTextStyles.cuerpo(size: 12))),
        Text(valor, style: AppTextStyles.subtitulo(size: 14)),
      ],
    );
  }
}

class _RachaCard extends StatelessWidget {
  static const _dias = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

  @override
  Widget build(BuildContext context) {
    final racha = resumenUsuario.rachaDias;
    final hoyIdx = (DateTime.now().weekday - 1).clamp(0, 6);
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Racha de aprendizaje 🔥', style: AppTextStyles.subtitulo(size: 15)),
          const SizedBox(height: 6),
          Center(
            child: Column(
              children: [
                Text('$racha', style: AppTextStyles.titulo(size: 44)),
                Text(
                  racha == 1 ? 'día consecutivo' : 'días consecutivos',
                  style: AppTextStyles.cuerpo(size: 12.5),
                ),
                const SizedBox(height: 4),
                Text('¡Sigue así!', style: AppTextStyles.etiqueta(size: 12.5, color: AppColors.verde)),
              ],
            ),
          ),
          const SizedBox(height: 18),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              for (int i = 0; i < _dias.length; i++) _DiaCirculo(letra: _dias[i], hecho: i <= hoyIdx, hoy: i == hoyIdx),
            ],
          ),
        ],
      ),
    );
  }
}

class _DiaCirculo extends StatelessWidget {
  const _DiaCirculo({required this.letra, required this.hecho, required this.hoy});

  final String letra;
  final bool hecho;
  final bool hoy;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Container(
          width: 26,
          height: 26,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: hecho ? AppColors.verdeFuerte : Colors.transparent,
            border: Border.all(color: hoy ? Colors.white : AppColors.bordeClaro, width: hoy ? 2 : 1.4),
          ),
          alignment: Alignment.center,
          child: hecho
              ? const Icon(Icons.check_rounded, size: 14, color: Color(0xFF06281A))
              : null,
        ),
        const SizedBox(height: 6),
        Text(letra, style: AppTextStyles.etiqueta(size: 10.5)),
      ],
    );
  }
}

class _NovedadesCard extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Novedades', style: AppTextStyles.subtitulo(size: 15)),
          const SizedBox(height: 10),
          for (final n in novedades)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Row(
                children: [
                  Container(
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(borderRadius: BorderRadius.circular(11), color: AppColors.vidrio2),
                    alignment: Alignment.center,
                    child: Text(n.emoji, style: const TextStyle(fontSize: 16)),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(n.titulo, style: AppTextStyles.subtitulo(size: 12.5), maxLines: 2, overflow: TextOverflow.ellipsis),
                        const SizedBox(height: 2),
                        Text('${n.chip} · ${n.fecha}', style: AppTextStyles.cuerpo(size: 11)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _InsigniaFila extends StatelessWidget {
  const _InsigniaFila({required this.insignia});

  final Insignia insignia;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          HexBadge(emoji: insignia.icono, colors: insignia.color.gradiente, size: 40),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(insignia.titulo, style: AppTextStyles.subtitulo(size: 13)),
                Text(insignia.descripcion, style: AppTextStyles.cuerpo(size: 11.5)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Banner extends StatelessWidget {
  const _Banner({required this.nombre});

  final String nombre;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
        gradient: LinearGradient(
          colors: [AppColors.verdeFuerte.withValues(alpha: 0.25), AppColors.azul.withValues(alpha: 0.18)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('🏆', style: TextStyle(fontSize: 34)),
          const SizedBox(height: 10),
          Text('¡Sigue así, $nombre!', style: AppTextStyles.titulo(size: 16)),
          const SizedBox(height: 4),
          Text('Vas por buen camino. Cada curso te acerca más a tus objetivos.',
              style: AppTextStyles.cuerpo(size: 12.5)),
        ],
      ),
    );
  }
}
