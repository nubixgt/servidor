import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/progress_track.dart';
import '../../data/mock/mock_cursos.dart';
import '../../data/models/curso.dart';
import '../../data/state/progreso_controller.dart';
import 'detalle_curso_screen.dart';

/// Catálogo completo de cursos — equivalente a Catalogo.vue, con buscador.
class CatalogoScreen extends StatefulWidget {
  const CatalogoScreen({super.key});

  @override
  State<CatalogoScreen> createState() => _CatalogoScreenState();
}

class _CatalogoScreenState extends State<CatalogoScreen> {
  final _busquedaCtrl = TextEditingController();
  String _termino = '';

  @override
  void dispose() {
    _busquedaCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final f = _termino.toLowerCase().trim();
    final lista = f.isEmpty
        ? cursos
        : cursos
            .where((c) =>
                c.titulo.toLowerCase().contains(f) ||
                c.descripcion.toLowerCase().contains(f) ||
                c.lecciones.any((l) => l.titulo.toLowerCase().contains(f)))
            .toList();
    final controller = ProgresoController.instance;

    return Scaffold(
      body: AppBackground(
        child: SafeArea(
          child: ListenableBuilder(
            listenable: controller,
            builder: (context, _) {
              return CustomScrollView(
                slivers: [
                  SliverPadding(
                    padding: const EdgeInsets.fromLTRB(20, 12, 20, 4),
                    sliver: SliverToBoxAdapter(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              IconButton(
                                onPressed: () => Navigator.of(context).pop(),
                                icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 18),
                              ),
                              Expanded(child: Text('Catálogo de cursos', style: AppTextStyles.titulo(size: 19))),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Text(
                            '${lista.length} de ${cursos.length} cursos',
                            style: AppTextStyles.cuerpo(size: 11.5),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _busquedaCtrl,
                            onChanged: (v) => setState(() => _termino = v),
                            style: AppTextStyles.cuerpo(size: 13.5, color: Colors.white),
                            decoration: InputDecoration(
                              hintText: 'Buscar cursos...',
                              hintStyle: AppTextStyles.cuerpo(size: 13, color: Colors.white.withValues(alpha: 0.4)),
                              prefixIcon: const Icon(Icons.search_rounded, color: Colors.white70, size: 20),
                              filled: true,
                              fillColor: AppColors.vidrio2,
                              contentPadding: const EdgeInsets.symmetric(vertical: 12),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(14),
                                borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.15)),
                              ),
                              enabledBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(14),
                                borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.15)),
                              ),
                              focusedBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(14),
                                borderSide: const BorderSide(color: AppColors.verde, width: 1.5),
                              ),
                            ),
                          ),
                          const SizedBox(height: 14),
                        ],
                      ),
                    ),
                  ),
                  if (lista.isEmpty)
                    SliverPadding(
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      sliver: SliverToBoxAdapter(
                        child: Text('No se encontraron cursos para esa búsqueda.',
                            style: AppTextStyles.cuerpo(size: 13)),
                      ),
                    )
                  else
                    SliverPadding(
                      padding: const EdgeInsets.fromLTRB(20, 0, 20, 110),
                      sliver: SliverList.separated(
                        itemCount: lista.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 14),
                        itemBuilder: (context, i) => _CursoCard(
                          curso: lista[i],
                          pct: controller.pctCurso(lista[i]),
                          aprobado: controller.aprobado(lista[i].id),
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute(builder: (_) => DetalleCursoScreen(cursoId: lista[i].id)),
                          ),
                        ),
                      ),
                    ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}

class _CursoCard extends StatelessWidget {
  const _CursoCard({required this.curso, required this.pct, required this.aprobado, required this.onTap});

  final Curso curso;
  final int pct;
  final bool aprobado;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        decoration: BoxDecoration(
          color: AppColors.vidrio,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.borde),
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              height: 110,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  Image.network(
                    curso.imagenUrl,
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) => Container(color: AppColors.vidrio2),
                  ),
                  Positioned(
                    top: 10,
                    left: 12,
                    child: _Chip(text: 'Módulo ${curso.id}', bg: Colors.black.withValues(alpha: 0.55)),
                  ),
                  if (aprobado)
                    Positioned(
                      top: 10,
                      right: 12,
                      child: _Chip(text: '✓ Completado', bg: AppColors.oro, fg: const Color(0xFF3A2A00)),
                    ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(curso.titulo, style: AppTextStyles.subtitulo(size: 15)),
                  const SizedBox(height: 6),
                  Text(curso.descripcion, style: AppTextStyles.cuerpo(size: 12)),
                  const SizedBox(height: 10),
                  ProgressTrack(pct: pct),
                  const SizedBox(height: 10),
                  Wrap(
                    spacing: 12,
                    runSpacing: 4,
                    children: [
                      Text('📖 ${curso.lecciones.length} lecciones', style: AppTextStyles.cuerpo(size: 11)),
                      Text('📝 Evaluación', style: AppTextStyles.cuerpo(size: 11)),
                      Text('🎓 Certificado', style: AppTextStyles.cuerpo(size: 11)),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({required this.text, required this.bg, this.fg = Colors.white});

  final String text;
  final Color bg;
  final Color fg;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
      child: Text(
        text,
        style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: fg, letterSpacing: 0.4),
      ),
    );
  }
}
