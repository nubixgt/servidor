import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/app_dialog.dart';
import '../../core/widgets/back_header.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/primary_button.dart';
import '../../data/services/api_exception.dart';
import '../../data/services/curso_service.dart';

/// Pantalla exclusiva del rol Administrador para publicar un curso nuevo:
/// datos generales, lecciones (dinámicas) y quiz final (preguntas con
/// opciones, una marcada como correcta) — ver Backend/src/Controllers/CursoController.php.
class CrearCursoScreen extends StatefulWidget {
  const CrearCursoScreen({super.key});

  @override
  State<CrearCursoScreen> createState() => _CrearCursoScreenState();
}

class _CrearCursoScreenState extends State<CrearCursoScreen> {
  final _cursoService = CursoService();
  final _picker = ImagePicker();
  bool _guardando = false;
  XFile? _imagenSeleccionada;

  final _iconoCtrl = TextEditingController(text: '📘');
  final _tituloCtrl = TextEditingController();
  final _descripcionCtrl = TextEditingController();

  final List<_LeccionForm> _lecciones = [_LeccionForm()];
  final List<_PreguntaForm> _preguntas = [_PreguntaForm()];

  @override
  void dispose() {
    _iconoCtrl.dispose();
    _tituloCtrl.dispose();
    _descripcionCtrl.dispose();
    for (final l in _lecciones) {
      l.dispose();
    }
    for (final p in _preguntas) {
      p.dispose();
    }
    super.dispose();
  }

  Future<void> _elegirImagen() async {
    final fuente = await showModalBottomSheet<ImageSource>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => const _HojaFuenteImagen(),
    );
    if (fuente == null) return;

    final archivo = await _picker.pickImage(source: fuente, imageQuality: 85, maxWidth: 1600);
    if (archivo != null) setState(() => _imagenSeleccionada = archivo);
  }

  void _agregarLeccion() => setState(() => _lecciones.add(_LeccionForm()));

  void _quitarLeccion(int i) {
    if (_lecciones.length <= 1) return;
    setState(() => _lecciones.removeAt(i).dispose());
  }

  void _agregarPregunta() => setState(() => _preguntas.add(_PreguntaForm()));

  void _quitarPregunta(int i) {
    if (_preguntas.length <= 1) return;
    setState(() => _preguntas.removeAt(i).dispose());
  }

  void _agregarOpcion(_PreguntaForm pregunta) => setState(() => pregunta.opciones.add(_OpcionForm()));

  void _quitarOpcion(_PreguntaForm pregunta, int i) {
    if (pregunta.opciones.length <= 2) return;
    setState(() => pregunta.opciones.removeAt(i).dispose());
  }

  void _marcarCorrecta(_PreguntaForm pregunta, int i) {
    setState(() {
      for (var j = 0; j < pregunta.opciones.length; j++) {
        pregunta.opciones[j].esCorrecta = j == i;
      }
    });
  }

  String? _validar() {
    if (_iconoCtrl.text.trim().isEmpty) return 'Elige un ícono (emoji) para el curso.';
    if (_tituloCtrl.text.trim().length < 3) return 'El título del curso es obligatorio.';
    if (_descripcionCtrl.text.trim().isEmpty) return 'La descripción del curso es obligatoria.';
    if (_imagenSeleccionada == null) return 'Selecciona o toma una foto para el curso.';
    for (final l in _lecciones) {
      if (l.tituloCtrl.text.trim().isEmpty || l.contenidoCtrl.text.trim().isEmpty) {
        return 'Completa el título y el contenido de todas las lecciones.';
      }
    }
    for (final p in _preguntas) {
      if (p.preguntaCtrl.text.trim().isEmpty) return 'Completa el texto de todas las preguntas del quiz.';
      for (final o in p.opciones) {
        if (o.textoCtrl.text.trim().isEmpty) return 'Completa el texto de todas las opciones del quiz.';
      }
      if (!p.opciones.any((o) => o.esCorrecta)) {
        return 'Marca cuál opción es la correcta en cada pregunta del quiz.';
      }
    }
    return null;
  }

  Future<void> _guardar() async {
    if (_guardando) return;

    final error = _validar();
    if (error != null) {
      await mostrarAlerta(context, tipo: AppDialogType.error, titulo: 'Falta información', mensaje: error);
      return;
    }

    setState(() => _guardando = true);
    try {
      await _cursoService.crearCurso(
        datos: {
          'icono': _iconoCtrl.text.trim(),
          'titulo': _tituloCtrl.text.trim(),
          'descripcion': _descripcionCtrl.text.trim(),
          'lecciones': [
            for (final l in _lecciones)
              {'titulo': l.tituloCtrl.text.trim(), 'contenido': l.contenidoCtrl.text.trim()},
          ],
          'quiz': [
            for (final p in _preguntas)
              {
                'pregunta': p.preguntaCtrl.text.trim(),
                'opciones': [
                  for (final o in p.opciones) {'texto': o.textoCtrl.text.trim(), 'es_correcta': o.esCorrecta},
                ],
              },
          ],
        },
        imagenPath: _imagenSeleccionada!.path,
      );

      if (!mounted) return;
      await mostrarAlerta(
        context,
        tipo: AppDialogType.exito,
        titulo: '¡Curso publicado!',
        mensaje: 'El curso ya está disponible.',
      );
      if (!mounted) return;
      Navigator.of(context).pop();
    } on ApiException catch (e) {
      await mostrarAlerta(context, tipo: AppDialogType.error, titulo: 'No se pudo guardar', mensaje: e.message);
    } finally {
      if (mounted) setState(() => _guardando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: AppBackground(
        child: SafeArea(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 40),
            children: [
              const BackHeader(title: 'Crear curso', subtitle: 'Solo para Administrador'),
              const SizedBox(height: 10),
              GlassCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _tituloSeccion('Datos generales'),
                    const SizedBox(height: 14),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        SizedBox(width: 72, child: _Campo(label: 'Ícono', hint: '📘', controller: _iconoCtrl)),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _Campo(label: 'Título', hint: 'Ej. Ganadería Sostenible', controller: _tituloCtrl),
                        ),
                      ],
                    ),
                    _Campo(
                      label: 'Descripción',
                      hint: 'Breve resumen del curso',
                      controller: _descripcionCtrl,
                      lineas: 3,
                    ),
                    Padding(
                      padding: const EdgeInsets.only(top: 14),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('IMAGEN DEL CURSO', style: AppTextStyles.etiqueta()),
                          const SizedBox(height: 6),
                          _SelectorImagen(imagen: _imagenSeleccionada, onTap: _elegirImagen),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              _tituloSeccionConBoton('Lecciones', onAgregar: _agregarLeccion),
              const SizedBox(height: 10),
              for (var i = 0; i < _lecciones.length; i++) ...[
                _TarjetaLeccion(
                  numero: i + 1,
                  form: _lecciones[i],
                  onQuitar: _lecciones.length > 1 ? () => _quitarLeccion(i) : null,
                ),
                const SizedBox(height: 12),
              ],
              const SizedBox(height: 8),
              _tituloSeccionConBoton('Quiz final', onAgregar: _agregarPregunta),
              const SizedBox(height: 10),
              for (var i = 0; i < _preguntas.length; i++) ...[
                _TarjetaPregunta(
                  numero: i + 1,
                  form: _preguntas[i],
                  onQuitarPregunta: _preguntas.length > 1 ? () => _quitarPregunta(i) : null,
                  onAgregarOpcion: () => _agregarOpcion(_preguntas[i]),
                  onQuitarOpcion: (j) => _quitarOpcion(_preguntas[i], j),
                  onMarcarCorrecta: (j) => _marcarCorrecta(_preguntas[i], j),
                ),
                const SizedBox(height: 12),
              ],
              const SizedBox(height: 12),
              PrimaryButton(
                label: _guardando ? 'Guardando...' : 'Guardar curso',
                onPressed: _guardar,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _tituloSeccion(String texto) => Text(texto, style: AppTextStyles.subtitulo(size: 15));

  Widget _tituloSeccionConBoton(String texto, {required VoidCallback onAgregar}) {
    return Row(
      children: [
        Expanded(child: Text(texto, style: AppTextStyles.subtitulo(size: 15))),
        TextButton.icon(
          onPressed: onAgregar,
          icon: const Icon(Icons.add_circle_outline, size: 18, color: AppColors.verde),
          label: Text('Agregar', style: AppTextStyles.etiqueta(size: 12, color: AppColors.verde)),
        ),
      ],
    );
  }
}

class _LeccionForm {
  final tituloCtrl = TextEditingController();
  final contenidoCtrl = TextEditingController();

  void dispose() {
    tituloCtrl.dispose();
    contenidoCtrl.dispose();
  }
}

class _OpcionForm {
  _OpcionForm({this.esCorrecta = false});

  final textoCtrl = TextEditingController();
  bool esCorrecta;

  void dispose() => textoCtrl.dispose();
}

class _PreguntaForm {
  final preguntaCtrl = TextEditingController();
  final List<_OpcionForm> opciones = [_OpcionForm(esCorrecta: true), _OpcionForm()];

  void dispose() {
    preguntaCtrl.dispose();
    for (final o in opciones) {
      o.dispose();
    }
  }
}

class _TarjetaLeccion extends StatelessWidget {
  const _TarjetaLeccion({required this.numero, required this.form, required this.onQuitar});

  final int numero;
  final _LeccionForm form;
  final VoidCallback? onQuitar;

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text('Lección $numero', style: AppTextStyles.subtitulo(size: 13.5)),
              ),
              if (onQuitar != null)
                IconButton(
                  onPressed: onQuitar,
                  icon: const Icon(Icons.delete_outline, size: 20, color: AppColors.rojo),
                ),
            ],
          ),
          _Campo(label: 'Título', hint: 'Ej. Cómo enviar consultas', controller: form.tituloCtrl),
          _Campo(label: 'Contenido', hint: 'Explica el paso a paso...', controller: form.contenidoCtrl, lineas: 4),
        ],
      ),
    );
  }
}

class _TarjetaPregunta extends StatelessWidget {
  const _TarjetaPregunta({
    required this.numero,
    required this.form,
    required this.onQuitarPregunta,
    required this.onAgregarOpcion,
    required this.onQuitarOpcion,
    required this.onMarcarCorrecta,
  });

  final int numero;
  final _PreguntaForm form;
  final VoidCallback? onQuitarPregunta;
  final VoidCallback onAgregarOpcion;
  final ValueChanged<int> onQuitarOpcion;
  final ValueChanged<int> onMarcarCorrecta;

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text('Pregunta $numero', style: AppTextStyles.subtitulo(size: 13.5)),
              ),
              if (onQuitarPregunta != null)
                IconButton(
                  onPressed: onQuitarPregunta,
                  icon: const Icon(Icons.delete_outline, size: 20, color: AppColors.rojo),
                ),
            ],
          ),
          _Campo(label: 'Pregunta', hint: '¿Cuál es...?', controller: form.preguntaCtrl),
          const SizedBox(height: 4),
          Text('OPCIONES (toca ✓ para marcar la correcta)', style: AppTextStyles.etiqueta()),
          const SizedBox(height: 8),
          for (var i = 0; i < form.opciones.length; i++)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                children: [
                  GestureDetector(
                    onTap: () => onMarcarCorrecta(i),
                    child: Container(
                      width: 26,
                      height: 26,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: form.opciones[i].esCorrecta
                            ? AppColors.verde.withValues(alpha: 0.2)
                            : Colors.white.withValues(alpha: 0.06),
                        border: Border.all(
                          color: form.opciones[i].esCorrecta ? AppColors.verde : Colors.white.withValues(alpha: 0.2),
                        ),
                      ),
                      child: form.opciones[i].esCorrecta
                          ? const Icon(Icons.check, size: 15, color: AppColors.verde)
                          : null,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      controller: form.opciones[i].textoCtrl,
                      style: AppTextStyles.cuerpo(size: 13.5, color: Colors.white),
                      decoration: InputDecoration(
                        hintText: 'Opción ${i + 1}',
                        hintStyle: AppTextStyles.cuerpo(size: 13, color: Colors.white.withValues(alpha: 0.4)),
                        filled: true,
                        fillColor: Colors.white.withValues(alpha: 0.07),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(10),
                          borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.15)),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(10),
                          borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.15)),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(10),
                          borderSide: const BorderSide(color: AppColors.verde, width: 1.5),
                        ),
                      ),
                    ),
                  ),
                  if (form.opciones.length > 2)
                    IconButton(
                      onPressed: () => onQuitarOpcion(i),
                      icon: Icon(Icons.close, size: 18, color: Colors.white.withValues(alpha: 0.4)),
                    ),
                ],
              ),
            ),
          TextButton.icon(
            onPressed: onAgregarOpcion,
            icon: const Icon(Icons.add, size: 16, color: AppColors.verde),
            label: Text('Agregar opción', style: AppTextStyles.etiqueta(size: 11.5, color: AppColors.verde)),
          ),
        ],
      ),
    );
  }
}

class _Campo extends StatelessWidget {
  const _Campo({
    required this.label,
    required this.hint,
    required this.controller,
    this.lineas = 1,
  });

  final String label;
  final String hint;
  final TextEditingController controller;
  final int lineas;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label.toUpperCase(), style: AppTextStyles.etiqueta()),
          const SizedBox(height: 6),
          TextField(
            controller: controller,
            maxLines: lineas,
            style: AppTextStyles.cuerpo(size: 14, color: Colors.white),
            decoration: InputDecoration(
              hintText: hint,
              hintStyle: AppTextStyles.cuerpo(size: 13, color: Colors.white.withValues(alpha: 0.4)),
              filled: true,
              fillColor: Colors.white.withValues(alpha: 0.07),
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.15)),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.15)),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: AppColors.verde, width: 1.5),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Recuadro que muestra la foto elegida (o un placeholder) y abre el
/// selector de cámara/galería al tocarlo.
class _SelectorImagen extends StatelessWidget {
  const _SelectorImagen({required this.imagen, required this.onTap});

  final XFile? imagen;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(14),
      onTap: onTap,
      child: Container(
        height: 160,
        width: double.infinity,
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.07),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
        ),
        child: imagen == null
            ? Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.add_a_photo_outlined, size: 30, color: Colors.white.withValues(alpha: 0.4)),
                  const SizedBox(height: 8),
                  Text(
                    'Toca para tomar o elegir una foto',
                    style: AppTextStyles.cuerpo(size: 12.5, color: Colors.white.withValues(alpha: 0.5)),
                  ),
                ],
              )
            : Stack(
                fit: StackFit.expand,
                children: [
                  Image.file(File(imagen!.path), fit: BoxFit.cover),
                  Positioned(
                    right: 8,
                    bottom: 8,
                    child: Container(
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(
                        color: AppColors.fondoBase.withValues(alpha: 0.75),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.edit, size: 16, color: Colors.white),
                    ),
                  ),
                ],
              ),
      ),
    );
  }
}

/// Hoja inferior para elegir entre cámara y galería.
class _HojaFuenteImagen extends StatelessWidget {
  const _HojaFuenteImagen();

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      child: Container(
        color: AppColors.fondoBase,
        child: SafeArea(
          top: false,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(height: 10),
              Container(
                width: 36,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.25),
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
              const SizedBox(height: 8),
              ListTile(
                leading: const Icon(Icons.camera_alt_outlined, color: Colors.white),
                title: Text('Tomar foto', style: AppTextStyles.cuerpo(size: 14, color: Colors.white)),
                onTap: () => Navigator.of(context).pop(ImageSource.camera),
              ),
              ListTile(
                leading: const Icon(Icons.photo_library_outlined, color: Colors.white),
                title: Text('Elegir de la galería', style: AppTextStyles.cuerpo(size: 14, color: Colors.white)),
                onTap: () => Navigator.of(context).pop(ImageSource.gallery),
              ),
              const SizedBox(height: 8),
            ],
          ),
        ),
      ),
    );
  }
}
