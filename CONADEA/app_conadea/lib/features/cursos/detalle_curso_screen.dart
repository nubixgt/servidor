import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/primary_button.dart';
import '../../core/widgets/progress_track.dart';
import '../../data/mock/mock_cursos.dart';
import '../../data/models/curso.dart';
import '../../data/state/progreso_controller.dart';
import 'certificado_dialog.dart';

/// Detalle de curso — equivalente a DetalleCurso.vue: lecciones expandibles
/// y evaluación final interactiva (3 preguntas) que desbloquea el certificado.
class DetalleCursoScreen extends StatefulWidget {
  const DetalleCursoScreen({super.key, required this.cursoId});

  final int cursoId;

  @override
  State<DetalleCursoScreen> createState() => _DetalleCursoScreenState();
}

class _DetalleCursoScreenState extends State<DetalleCursoScreen> {
  final Set<int> _leccionesAbiertas = {};
  bool _quizActivo = false;
  bool _quizCalificado = false;
  final Map<int, int> _quizSel = {};
  int _notaObtenida = 0;

  late final curso = cursoPorId(widget.cursoId);
  final controller = ProgresoController.instance;

  void _toggleLeccion(int i) {
    setState(() {
      if (!_leccionesAbiertas.remove(i)) _leccionesAbiertas.add(i);
    });
  }

  void _marcarLeccion(int i) {
    controller.completarLeccion(curso.id, i);
    setState(() {
      final siguiente = i + 1;
      if (siguiente < curso.lecciones.length) _leccionesAbiertas.add(siguiente);
    });
  }

  void _iniciarQuiz() {
    setState(() {
      _quizActivo = true;
      _quizCalificado = false;
      _quizSel.clear();
      _notaObtenida = 0;
    });
  }

  void _elegir(int qi, int oi) {
    if (_quizCalificado) return;
    setState(() => _quizSel[qi] = oi);
  }

  void _calificar() {
    if (_quizSel.length < curso.quiz.length) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Responde todas las preguntas antes de enviar.'),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }
    var nota = 0;
    for (var qi = 0; qi < curso.quiz.length; qi++) {
      if (_quizSel[qi] == curso.quiz[qi].respuesta) nota++;
    }
    setState(() {
      _notaObtenida = nota;
      _quizCalificado = true;
    });
    controller.aprobarCurso(curso.id, nota);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: AppBackground(
        child: SafeArea(
          child: ListenableBuilder(
            listenable: controller,
            builder: (context, _) {
              final prog = controller.progresoDe(curso.id);
              final pct = controller.pctCurso(curso);
              final todasHechas = controller.todasLeccionesHechas(curso);

              return ListView(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 40),
                children: [
                  _VolverBtn(onTap: () => Navigator.of(context).pop()),
                  const SizedBox(height: 14),
                  GlassCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              width: 64,
                              height: 64,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(16),
                                gradient: LinearGradient(
                                  colors: AppColors.gradMod(curso.id),
                                  begin: Alignment.topLeft,
                                  end: Alignment.bottomRight,
                                ),
                              ),
                              alignment: Alignment.center,
                              child: Text(curso.icono, style: const TextStyle(fontSize: 30)),
                            ),
                            const SizedBox(width: 16),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'MÓDULO ${curso.id} · ${curso.lecciones.length} LECCIONES · EVALUACIÓN FINAL',
                                    style: AppTextStyles.etiqueta(size: 9.5),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(curso.titulo, style: AppTextStyles.titulo(size: 18)),
                                  const SizedBox(height: 4),
                                  Text(curso.descripcion, style: AppTextStyles.cuerpo(size: 12.5)),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            Column(
                              children: [
                                Text('$pct%',
                                    style: AppTextStyles.titulo(size: 18).copyWith(color: AppColors.verde)),
                                Text('Avance', style: AppTextStyles.etiqueta(size: 8.5)),
                              ],
                            ),
                          ],
                        ),
                        const SizedBox(height: 14),
                        ProgressTrack(pct: pct, height: 9),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  for (var i = 0; i < curso.lecciones.length; i++)
                    _LeccionTile(
                      indice: i,
                      leccion: curso.lecciones[i],
                      hecha: prog.leccionesCompletadas.contains(i),
                      abierta: _leccionesAbiertas.contains(i),
                      onToggle: () => _toggleLeccion(i),
                      onMarcar: () => _marcarLeccion(i),
                    ),
                  const SizedBox(height: 8),
                  GlassCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('📝 Evaluación del módulo', style: AppTextStyles.subtitulo(size: 15)),
                        const SizedBox(height: 8),
                        Text(
                          prog.aprobado
                              ? 'Evaluación aprobada con nota ${prog.nota}/3 el ${prog.fecha}.'
                              : todasHechas
                                  ? 'Responde 3 preguntas. Necesitas al menos 2 correctas para aprobar y obtener tu certificado.'
                                  : 'Completa todas las lecciones para habilitar la evaluación.',
                          style: AppTextStyles.cuerpo(size: 12.5),
                        ),
                        const SizedBox(height: 14),
                        if (prog.aprobado && !_quizActivo)
                          Wrap(
                            spacing: 10,
                            runSpacing: 10,
                            children: [
                              PrimaryButton(
                                label: '🎓 Ver mi certificado',
                                expand: false,
                                onPressed: () => mostrarCertificado(
                                  context,
                                  curso: curso,
                                  nota: prog.nota ?? 0,
                                  fecha: prog.fecha ?? '',
                                ),
                              ),
                              OutlinedButton(
                                onPressed: _iniciarQuiz,
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: Colors.white,
                                  side: const BorderSide(color: AppColors.borde),
                                ),
                                child: const Text('Repetir evaluación'),
                              ),
                            ],
                          )
                        else if (!_quizActivo)
                          Opacity(
                            opacity: todasHechas ? 1 : 0.4,
                            child: IgnorePointer(
                              ignoring: !todasHechas,
                              child: PrimaryButton(
                                label: 'Iniciar evaluación',
                                expand: false,
                                onPressed: _iniciarQuiz,
                              ),
                            ),
                          ),
                        if (_quizActivo) ...[
                          for (var qi = 0; qi < curso.quiz.length; qi++)
                            _PreguntaWidget(
                              indice: qi,
                              pregunta: curso.quiz[qi],
                              seleccion: _quizSel[qi],
                              calificado: _quizCalificado,
                              onElegir: (oi) => _elegir(qi, oi),
                            ),
                          if (!_quizCalificado)
                            PrimaryButton(label: 'Enviar respuestas', onPressed: _calificar),
                          if (_quizCalificado)
                            _ResultadoQuiz(
                              nota: _notaObtenida,
                              onVerCertificado: () => mostrarCertificado(
                                context,
                                curso: curso,
                                nota: _notaObtenida,
                                fecha: prog.fecha ?? '',
                              ),
                              onReintentar: _iniciarQuiz,
                            ),
                        ],
                      ],
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

class _VolverBtn extends StatelessWidget {
  const _VolverBtn({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.chevron_left_rounded, color: AppColors.verde),
          Text('Volver', style: AppTextStyles.etiqueta(size: 13, color: AppColors.verde).copyWith(letterSpacing: 0)),
        ],
      ),
    );
  }
}

class _LeccionTile extends StatelessWidget {
  const _LeccionTile({
    required this.indice,
    required this.leccion,
    required this.hecha,
    required this.abierta,
    required this.onToggle,
    required this.onMarcar,
  });

  final int indice;
  final Leccion leccion;
  final bool hecha;
  final bool abierta;
  final VoidCallback onToggle;
  final VoidCallback onMarcar;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: AppColors.vidrio,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.borde),
      ),
      child: Column(
        children: [
          InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: onToggle,
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Row(
                children: [
                  Container(
                    width: 24,
                    height: 24,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: hecha ? AppColors.verde : Colors.transparent,
                      border: Border.all(color: hecha ? AppColors.verde : AppColors.bordeClaro, width: 2),
                    ),
                    alignment: Alignment.center,
                    child: hecha ? const Icon(Icons.check_rounded, size: 14, color: Color(0xFF06281A)) : null,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text('${indice + 1}. ${leccion.titulo}', style: AppTextStyles.subtitulo(size: 13.5)),
                  ),
                  Icon(
                    abierta ? Icons.expand_more_rounded : Icons.chevron_right_rounded,
                    color: AppColors.textoSuave,
                  ),
                ],
              ),
            ),
          ),
          if (abierta)
            Padding(
              padding: const EdgeInsets.fromLTRB(52, 0, 16, 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(leccion.contenido, style: AppTextStyles.cuerpo(size: 13)),
                  const SizedBox(height: 12),
                  if (hecha)
                    Text('✓ Lección completada',
                        style: AppTextStyles.etiqueta(size: 12, color: AppColors.verde).copyWith(letterSpacing: 0))
                  else
                    PrimaryButton(label: 'Marcar como completada', expand: false, onPressed: onMarcar),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _PreguntaWidget extends StatelessWidget {
  const _PreguntaWidget({
    required this.indice,
    required this.pregunta,
    required this.seleccion,
    required this.calificado,
    required this.onElegir,
  });

  final int indice;
  final PreguntaQuiz pregunta;
  final int? seleccion;
  final bool calificado;
  final ValueChanged<int> onElegir;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('${indice + 1}. ${pregunta.pregunta}', style: AppTextStyles.subtitulo(size: 13.5)),
          const SizedBox(height: 10),
          for (var oi = 0; oi < pregunta.opciones.length; oi++)
            _OpcionQuiz(
              letra: 'ABC'[oi],
              texto: pregunta.opciones[oi],
              estado: !calificado
                  ? (seleccion == oi ? _EstadoOpcion.seleccionada : _EstadoOpcion.normal)
                  : (oi == pregunta.respuesta
                      ? _EstadoOpcion.correcta
                      : (seleccion == oi ? _EstadoOpcion.incorrecta : _EstadoOpcion.normal)),
              onTap: () => onElegir(oi),
            ),
        ],
      ),
    );
  }
}

enum _EstadoOpcion { normal, seleccionada, correcta, incorrecta }

class _OpcionQuiz extends StatelessWidget {
  const _OpcionQuiz({required this.letra, required this.texto, required this.estado, required this.onTap});

  final String letra;
  final String texto;
  final _EstadoOpcion estado;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    Color borde = AppColors.borde;
    Color fondo = AppColors.vidrio;
    switch (estado) {
      case _EstadoOpcion.normal:
        break;
      case _EstadoOpcion.seleccionada:
        borde = AppColors.verde;
        fondo = AppColors.verde.withValues(alpha: 0.12);
        break;
      case _EstadoOpcion.correcta:
        borde = AppColors.verde;
        fondo = AppColors.verde.withValues(alpha: 0.2);
        break;
      case _EstadoOpcion.incorrecta:
        borde = AppColors.rojo;
        fondo = AppColors.rojo.withValues(alpha: 0.15);
        break;
    }

    return InkWell(
      onTap: estado == _EstadoOpcion.correcta || estado == _EstadoOpcion.incorrecta ? null : onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: fondo,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: borde, width: 1.4),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('$letra.',
                style: AppTextStyles.subtitulo(size: 13).copyWith(color: AppColors.verde)),
            const SizedBox(width: 10),
            Expanded(child: Text(texto, style: AppTextStyles.cuerpo(size: 12.5, color: Colors.white))),
          ],
        ),
      ),
    );
  }
}

class _ResultadoQuiz extends StatelessWidget {
  const _ResultadoQuiz({required this.nota, required this.onVerCertificado, required this.onReintentar});

  final int nota;
  final VoidCallback onVerCertificado;
  final VoidCallback onReintentar;

  @override
  Widget build(BuildContext context) {
    final aprobado = nota >= 2;
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: Column(
        children: [
          Text('$nota/3', style: AppTextStyles.titulo(size: 44).copyWith(color: AppColors.verde)),
          const SizedBox(height: 4),
          Text(
            aprobado ? '¡Módulo aprobado! 🎉' : 'Aún no apruebas',
            style: AppTextStyles.subtitulo(size: 14).copyWith(color: aprobado ? AppColors.verde : AppColors.rojo),
          ),
          const SizedBox(height: 6),
          Text(
            aprobado
                ? 'Tu certificado ya está disponible y tu avance quedó registrado.'
                : 'Repasa las lecciones y vuelve a intentarlo. Necesitas 2 respuestas correctas.',
            textAlign: TextAlign.center,
            style: AppTextStyles.cuerpo(size: 12.5),
          ),
          const SizedBox(height: 14),
          if (aprobado)
            PrimaryButton(label: '🎓 Ver mi certificado', expand: false, onPressed: onVerCertificado)
          else
            OutlinedButton(
              onPressed: onReintentar,
              style: OutlinedButton.styleFrom(foregroundColor: Colors.white, side: const BorderSide(color: AppColors.borde)),
              child: const Text('Intentar de nuevo'),
            ),
        ],
      ),
    );
  }
}
