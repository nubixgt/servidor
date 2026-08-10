import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/curso_row.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/primary_button.dart';
import '../../core/widgets/top_header.dart';
import '../../data/models/curso.dart';
import '../../data/services/api_exception.dart';
import '../../data/services/curso_service.dart';
import '../../data/state/progreso_controller.dart';
import 'abrir_curso.dart';
import 'catalogo_screen.dart';

/// Pantalla "Mis cursos" — equivalente a MisCursos.vue, con el toggle
/// En progreso / Completados. Cursos reales (Backend/src/Controllers/CursoController.php).
class CursosScreen extends StatefulWidget {
  const CursosScreen({super.key});

  @override
  State<CursosScreen> createState() => _CursosScreenState();
}

class _CursosScreenState extends State<CursosScreen> {
  final _cursoService = CursoService();
  bool _completados = false;
  bool _cargando = true;
  String? _error;
  List<Curso> _cursos = [];

  @override
  void initState() {
    super.initState();
    _cargarCursos();
  }

  Future<void> _cargarCursos() async {
    setState(() {
      _cargando = true;
      _error = null;
    });
    try {
      final cursos = await _cursoService.listarCursos();
      setState(() => _cursos = cursos);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _cargando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final controller = ProgresoController.instance;

    return ListenableBuilder(
      listenable: controller,
      builder: (context, _) {
        final lista = _cursos
            .where((c) => _completados ? controller.aprobado(c.id) : controller.enProgreso(c))
            .toList();

        return ListView(
          padding: const EdgeInsets.fromLTRB(20, 0, 20, 110),
          children: [
            const TopHeader(title: 'Mis cursos'),
            const SizedBox(height: 12),
            _Toggle(
              completados: _completados,
              onChange: (v) => setState(() => _completados = v),
            ),
            const SizedBox(height: 16),
            if (_cargando)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 30),
                child: Center(child: CircularProgressIndicator(color: AppColors.verde)),
              )
            else if (_error != null)
              GlassCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(_error!, style: AppTextStyles.cuerpo(size: 13, color: AppColors.rojo)),
                    const SizedBox(height: 12),
                    PrimaryButton(label: 'Reintentar', expand: false, onPressed: _cargarCursos),
                  ],
                ),
              )
            else if (lista.isEmpty)
              GlassCard(
                child: Text(
                  _completados
                      ? 'Todavía no has completado ningún curso.'
                      : 'Todavía no has iniciado ningún curso. Explora el catálogo y comienza tu primera lección.',
                  style: AppTextStyles.cuerpo(size: 13),
                ),
              )
            else
              GlassCard(
                child: Column(
                  children: [
                    for (final c in lista)
                      CursoRow(
                        curso: c,
                        pct: controller.pctCurso(c),
                        completado: controller.aprobado(c.id),
                        onTap: () => abrirDetalleCurso(context, _cursoService, c.id),
                      ),
                  ],
                ),
              ),
            const SizedBox(height: 20),
            _BannerCatalogo(),
          ],
        );
      },
    );
  }
}

class _Toggle extends StatelessWidget {
  const _Toggle({required this.completados, required this.onChange});

  final bool completados;
  final ValueChanged<bool> onChange;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        _Pill(label: 'En progreso', active: !completados, onTap: () => onChange(false)),
        const SizedBox(width: 10),
        _Pill(label: 'Completados', active: completados, onTap: () => onChange(true)),
      ],
    );
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.label, required this.active, required this.onTap});

  final String label;
  final bool active;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(20),
          gradient: active
              ? const LinearGradient(colors: [AppColors.verde, AppColors.verdeFuerte])
              : null,
          color: active ? null : AppColors.vidrio2,
          border: Border.all(color: active ? Colors.transparent : AppColors.borde),
        ),
        child: Text(
          label,
          style: AppTextStyles.etiqueta(
            size: 12.5,
            color: active ? const Color(0xFF06281A) : AppColors.textoSuave,
          ).copyWith(letterSpacing: 0, fontWeight: FontWeight.w800),
        ),
      ),
    );
  }
}

class _BannerCatalogo extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: const LinearGradient(
          colors: [Color(0xFF15803D), Color(0xFF0369A1)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Explora más cursos', style: AppTextStyles.titulo(size: 16)),
          const SizedBox(height: 4),
          Text('Accede a todo nuestro catálogo de cursos disponibles.',
              style: AppTextStyles.cuerpo(size: 12.5, color: Colors.white.withValues(alpha: 0.85))),
          const SizedBox(height: 14),
          PrimaryButton(
            label: 'Ver catálogo →',
            expand: false,
            onPressed: () => Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => const CatalogoScreen()),
            ),
          ),
        ],
      ),
    );
  }
}
