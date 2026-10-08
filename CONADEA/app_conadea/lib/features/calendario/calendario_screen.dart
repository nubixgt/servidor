import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/app_dialog.dart';
import '../../core/widgets/back_header.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/primary_button.dart';
import '../../data/models/curso.dart';
import '../../data/models/horario.dart';
import '../../data/services/api_exception.dart';
import '../../data/services/curso_service.dart';
import '../../data/services/horario_service.dart';

/// Calendario — equivalente a Calendario.vue. Muestra los horarios de
/// estudio que el usuario configuró por curso (los mismos que usa el bot de
/// WhatsApp para el recordatorio) y permite crearlos/editarlos/pausarlos.
class CalendarioScreen extends StatefulWidget {
  const CalendarioScreen({super.key});

  @override
  State<CalendarioScreen> createState() => _CalendarioScreenState();
}

class _CalendarioScreenState extends State<CalendarioScreen> {
  static const _diasSemana = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
  // DateTime.weekday: 1 = lunes … 7 = domingo
  static const _letraPorWeekday = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
  static const _meses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
  ];
  static const _nombresDias = {
    'L': 'Lun', 'M': 'Mar', 'X': 'Mié', 'J': 'Jue', 'V': 'Vie', 'S': 'Sáb', 'D': 'Dom',
  };

  final _horarioService = HorarioService();
  final _cursoService = CursoService();
  final DateTime _hoy = DateTime.now();

  late DateTime _mes = DateTime(_hoy.year, _hoy.month);
  int? _diaElegido;

  List<HorarioEstudio> _horarios = [];
  List<Curso> _cursos = [];
  bool _cargando = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  Future<void> _cargar() async {
    setState(() {
      _cargando = true;
      _error = null;
    });
    try {
      final resultados = await Future.wait([
        _horarioService.listar(),
        _cursoService.listarCursos(),
      ]);
      if (!mounted) return;
      setState(() {
        _horarios = resultados[0] as List<HorarioEstudio>;
        _cursos = resultados[1] as List<Curso>;
      });
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _cargando = false);
    }
  }

  List<HorarioEstudio> _sesionesDelDia(int dia) {
    final letra = _letraPorWeekday[DateTime(_mes.year, _mes.month, dia).weekday - 1];
    return _horarios.where((h) => h.dias.contains(letra)).toList();
  }

  bool _esHoy(int dia) => _mes.year == _hoy.year && _mes.month == _hoy.month && dia == _hoy.day;

  void _cambiarMes(int delta) {
    setState(() {
      _mes = DateTime(_mes.year, _mes.month + delta);
      _diaElegido = null;
    });
  }

  void _irAHoy() {
    setState(() {
      _mes = DateTime(_hoy.year, _hoy.month);
      _diaElegido = _hoy.day;
    });
  }

  String _iconoCurso(int cursoId) {
    for (final c in _cursos) {
      if (c.id == cursoId) return c.icono;
    }
    return '📘';
  }

  String _horaCorta(String hhmm) {
    final partes = hhmm.split(':');
    final h = int.parse(partes[0]);
    final sufijo = h >= 12 ? 'p.m.' : 'a.m.';
    return '${h % 12 == 0 ? 12 : h % 12}:${partes[1]} $sufijo';
  }

  String _nombresDeDias(Set<String> dias) =>
      _nombresDias.entries.where((e) => dias.contains(e.key)).map((e) => e.value).join(', ');

  Future<void> _abrirFormulario({HorarioEstudio? editar}) async {
    // Al crear solo se ofrecen cursos sin horario; al editar, el propio curso.
    final disponibles = editar != null
        ? _cursos
        : _cursos.where((c) => !_horarios.any((h) => h.cursoId == c.id)).toList();

    if (editar == null && disponibles.isEmpty) {
      await mostrarAlerta(
        context,
        tipo: AppDialogType.error,
        titulo: 'Sin cursos disponibles',
        mensaje: 'Ya configuraste un horario para todos los cursos.',
      );
      return;
    }

    final guardado = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.fondoBase,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => _FormularioHorario(
        cursos: disponibles,
        editar: editar,
        service: _horarioService,
      ),
    );

    if (guardado == true) {
      await _cargar();
      if (!mounted) return;
      await mostrarAlerta(
        context,
        tipo: AppDialogType.exito,
        titulo: 'Horario guardado',
        mensaje: 'Te avisaremos por WhatsApp 10 minutos antes de tu hora de estudio.',
        autoCerrar: const Duration(milliseconds: 2500),
      );
    }
  }

  Future<void> _alternarActivo(HorarioEstudio h) async {
    try {
      await _horarioService.actualizarActivo(h.cursoId, activo: !h.activo);
      if (!mounted) return;
      setState(() {
        _horarios = [for (final x in _horarios) x.cursoId == h.cursoId ? x.copyWith(activo: !h.activo) : x];
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      await mostrarAlerta(context, tipo: AppDialogType.error, titulo: 'Ocurrió un problema', mensaje: e.message);
    }
  }

  @override
  Widget build(BuildContext context) {
    final diasEnMes = DateUtils.getDaysInMonth(_mes.year, _mes.month);
    final desfase = DateTime(_mes.year, _mes.month, 1).weekday - 1; // semana inicia en lunes

    return Scaffold(
      body: AppBackground(
        child: SafeArea(
          child: RefreshIndicator(
            onRefresh: _cargar,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 40),
              children: [
                BackHeader(
                  title: 'Calendario · ${_meses[_mes.month - 1]} ${_mes.year}',
                  subtitle: 'Tus horarios de estudio por curso',
                ),
                const SizedBox(height: 6),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    IconButton(
                      onPressed: () => _cambiarMes(-1),
                      icon: const Icon(Icons.chevron_left_rounded, color: Colors.white),
                    ),
                    TextButton(onPressed: _irAHoy, child: const Text('Hoy')),
                    IconButton(
                      onPressed: () => _cambiarMes(1),
                      icon: const Icon(Icons.chevron_right_rounded, color: Colors.white),
                    ),
                  ],
                ),
                if (_error != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: Text(_error!, style: AppTextStyles.cuerpo(size: 12, color: AppColors.rojo)),
                  ),
                GlassCard(
                  child: Column(
                    children: [
                      GridView.count(
                        crossAxisCount: 7,
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        mainAxisSpacing: 6,
                        crossAxisSpacing: 6,
                        children: [
                          for (final d in _diasSemana)
                            Center(child: Text(d, style: AppTextStyles.etiqueta(size: 10))),
                          for (var i = 0; i < desfase; i++) const SizedBox.shrink(),
                          for (var dia = 1; dia <= diasEnMes; dia++)
                            _DiaCelda(
                              dia: dia,
                              sesiones: _sesionesDelDia(dia),
                              hoy: _esHoy(dia),
                              elegido: dia == _diaElegido,
                              onTap: () => setState(() => _diaElegido = dia == _diaElegido ? null : dia),
                            ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Align(
                        alignment: Alignment.centerLeft,
                        child: Text(
                          '🟩 Días con estudio programado · Borde azul: hoy · Toca un día para ver el detalle',
                          style: AppTextStyles.cuerpo(size: 10.5),
                        ),
                      ),
                      if (_diaElegido != null) ...[
                        const Divider(height: 24, color: AppColors.borde),
                        Align(
                          alignment: Alignment.centerLeft,
                          child: Text(
                            '${_diaElegido!} de ${_meses[_mes.month - 1]}',
                            style: AppTextStyles.subtitulo(size: 13),
                          ),
                        ),
                        const SizedBox(height: 6),
                        if (_sesionesDelDia(_diaElegido!).isEmpty)
                          Align(
                            alignment: Alignment.centerLeft,
                            child: Text('No tienes estudio programado este día.', style: AppTextStyles.cuerpo(size: 12)),
                          ),
                        for (final s in _sesionesDelDia(_diaElegido!)) _FilaHorario(
                          hora: _horaCorta(s.hora),
                          titulo: '${_iconoCurso(s.cursoId)} ${s.cursoTitulo}',
                          detalle: '${s.duracionMinutos} min${s.activo ? '' : ' · avisos pausados'}',
                          pausado: !s.activo,
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                GlassCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(child: Text('Mis horarios', style: AppTextStyles.subtitulo(size: 15))),
                          TextButton.icon(
                            onPressed: _cargando ? null : () => _abrirFormulario(),
                            icon: const Icon(Icons.add_rounded, size: 18),
                            label: const Text('Agregar'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      if (_cargando)
                        const Padding(
                          padding: EdgeInsets.symmetric(vertical: 16),
                          child: Center(child: CircularProgressIndicator()),
                        )
                      else if (_horarios.isEmpty)
                        Text(
                          'Aún no tienes horarios. Agrega uno para recibir tu recordatorio de estudio por WhatsApp 10 minutos antes.',
                          style: AppTextStyles.cuerpo(size: 12),
                        ),
                      for (final h in _horarios)
                        _FilaHorario(
                          hora: _horaCorta(h.hora),
                          titulo: '${_iconoCurso(h.cursoId)} ${h.cursoTitulo}',
                          detalle: '${_nombresDeDias(h.dias)} · ${h.duracionMinutos} min',
                          pausado: !h.activo,
                          acciones: [
                            IconButton(
                              tooltip: 'Editar',
                              visualDensity: VisualDensity.compact,
                              onPressed: () => _abrirFormulario(editar: h),
                              icon: const Icon(Icons.edit_outlined, size: 18, color: Colors.white),
                            ),
                            IconButton(
                              tooltip: h.activo ? 'Pausar avisos' : 'Reactivar avisos',
                              visualDensity: VisualDensity.compact,
                              onPressed: () => _alternarActivo(h),
                              icon: Icon(
                                h.activo ? Icons.notifications_active_outlined : Icons.notifications_off_outlined,
                                size: 18,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _FilaHorario extends StatelessWidget {
  const _FilaHorario({
    required this.hora,
    required this.titulo,
    required this.detalle,
    this.pausado = false,
    this.acciones = const [],
  });

  final String hora;
  final String titulo;
  final String detalle;
  final bool pausado;
  final List<Widget> acciones;

  @override
  Widget build(BuildContext context) {
    return Opacity(
      opacity: pausado ? 0.55 : 1,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Row(
          children: [
            Container(
              width: 70,
              padding: const EdgeInsets.symmetric(vertical: 10),
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: AppColors.vidrio2,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.borde),
              ),
              child: Text(hora, style: AppTextStyles.titulo(size: 11.5)),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(titulo, style: AppTextStyles.cuerpo(size: 12.5, color: Colors.white)),
                  const SizedBox(height: 3),
                  Text(detalle, style: AppTextStyles.cuerpo(size: 11)),
                ],
              ),
            ),
            ...acciones,
          ],
        ),
      ),
    );
  }
}

class _DiaCelda extends StatelessWidget {
  const _DiaCelda({
    required this.dia,
    required this.sesiones,
    required this.hoy,
    required this.elegido,
    required this.onTap,
  });

  final int dia;
  final List<HorarioEstudio> sesiones;
  final bool hoy;
  final bool elegido;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final evento = sesiones.isNotEmpty;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        alignment: Alignment.center,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(10),
          color: elegido
              ? AppColors.verde.withValues(alpha: 0.3)
              : (evento ? AppColors.verde.withValues(alpha: 0.15) : AppColors.vidrio),
          border: Border.all(
            color: hoy ? AppColors.azul : (evento ? AppColors.verde : AppColors.borde),
            width: hoy ? 2 : 1,
          ),
        ),
        child: Text(
          '$dia',
          style: AppTextStyles.cuerpo(
            size: 12,
            color: Colors.white,
            weight: evento || hoy ? FontWeight.w800 : FontWeight.w400,
          ),
        ),
      ),
    );
  }
}

/// Hoja inferior para crear o editar el horario de un curso.
class _FormularioHorario extends StatefulWidget {
  const _FormularioHorario({required this.cursos, required this.service, this.editar});

  final List<Curso> cursos;
  final HorarioService service;
  final HorarioEstudio? editar;

  @override
  State<_FormularioHorario> createState() => _FormularioHorarioState();
}

class _FormularioHorarioState extends State<_FormularioHorario> {
  static const _dias = [
    ('L', 'Lun'), ('M', 'Mar'), ('X', 'Mié'), ('J', 'Jue'), ('V', 'Vie'), ('S', 'Sáb'), ('D', 'Dom'),
  ];

  int? _cursoId;
  late Set<String> _diasElegidos;
  late TimeOfDay _hora;
  late final TextEditingController _duracion;
  bool _guardando = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final e = widget.editar;
    _cursoId = e?.cursoId;
    _diasElegidos = {...?e?.dias};
    _hora = e == null
        ? const TimeOfDay(hour: 19, minute: 0)
        : TimeOfDay(hour: int.parse(e.hora.substring(0, 2)), minute: int.parse(e.hora.substring(3, 5)));
    _duracion = TextEditingController(text: '${e?.duracionMinutos ?? 15}');
  }

  @override
  void dispose() {
    _duracion.dispose();
    super.dispose();
  }

  String get _horaTexto =>
      '${_hora.hour.toString().padLeft(2, '0')}:${_hora.minute.toString().padLeft(2, '0')}';

  Future<void> _elegirHora() async {
    final h = await showTimePicker(context: context, initialTime: _hora);
    if (h != null) setState(() => _hora = h);
  }

  Future<void> _guardar() async {
    final duracion = int.tryParse(_duracion.text.trim());
    if (_cursoId == null) return setState(() => _error = 'Elige un curso.');
    if (_diasElegidos.isEmpty) return setState(() => _error = 'Elige al menos un día de la semana.');
    if (duracion == null || duracion < 5 || duracion > 180) {
      return setState(() => _error = 'La duración debe estar entre 5 y 180 minutos.');
    }

    setState(() {
      _guardando = true;
      _error = null;
    });
    try {
      await widget.service.guardar(
        cursoId: _cursoId!,
        dias: _diasElegidos,
        hora: _horaTexto,
        duracionMinutos: duracion,
      );
      if (mounted) Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _guardando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.fromLTRB(20, 20, 20, 20 + MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(widget.editar == null ? 'Nuevo horario' : 'Editar horario', style: AppTextStyles.titulo(size: 18)),
            const SizedBox(height: 16),
            Text('Curso', style: AppTextStyles.etiqueta()),
            const SizedBox(height: 6),
            DropdownButtonFormField<int>(
              initialValue: _cursoId,
              isExpanded: true,
              dropdownColor: AppColors.fondoBase,
              hint: const Text('Elige un curso'),
              items: [
                for (final c in widget.cursos)
                  DropdownMenuItem(value: c.id, child: Text('${c.icono} ${c.titulo}', overflow: TextOverflow.ellipsis)),
              ],
              onChanged: widget.editar == null ? (v) => setState(() => _cursoId = v) : null,
            ),
            const SizedBox(height: 16),
            Text('Días', style: AppTextStyles.etiqueta()),
            const SizedBox(height: 6),
            Wrap(
              spacing: 6,
              runSpacing: 6,
              children: [
                for (final (k, n) in _dias)
                  FilterChip(
                    label: Text(n),
                    selected: _diasElegidos.contains(k),
                    onSelected: (v) => setState(() => v ? _diasElegidos.add(k) : _diasElegidos.remove(k)),
                  ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Hora', style: AppTextStyles.etiqueta()),
                      const SizedBox(height: 6),
                      OutlinedButton.icon(
                        onPressed: _elegirHora,
                        icon: const Icon(Icons.schedule_rounded, size: 18),
                        label: Text(_hora.format(context)),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Duración (min)', style: AppTextStyles.etiqueta()),
                      const SizedBox(height: 6),
                      TextField(controller: _duracion, keyboardType: TextInputType.number),
                    ],
                  ),
                ),
              ],
            ),
            if (_error != null) ...[
              const SizedBox(height: 12),
              Text(_error!, style: AppTextStyles.cuerpo(size: 12, color: AppColors.rojo)),
            ],
            const SizedBox(height: 20),
            PrimaryButton(label: _guardando ? 'Guardando…' : 'Guardar', onPressed: _guardando ? () {} : _guardar),
          ],
        ),
      ),
    );
  }
}
