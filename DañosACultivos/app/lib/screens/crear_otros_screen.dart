import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:geolocator/geolocator.dart';

import '../data/catalogos.dart';
import '../data/guatemala.dart';
import '../models/usuario.dart';
import '../services/api_client.dart';
import '../services/dano_otros_service.dart';
import '../services/offline_queue.dart';
import '../theme/app_theme.dart';
import '../widgets/app_shell.dart';
import '../widgets/common.dart';
import '../widgets/fecha_field.dart';
import '../widgets/search_select.dart';

/// Lógica compartida de los formularios Pecuario e Infraestructura: período, ubicación,
/// causa, evento, familias, pérdida y envío (con cola sin conexión).
abstract class _CrearModuloState<W extends StatefulWidget> extends State<W> {
  final formKey = GlobalKey<FormState>();
  int version = 0; // fuerza a reconstruir los campos al limpiar
  bool guardando = false;

  DateTime informe = DateTime.now();
  DateTime? inicio, fin;
  String departamento = '';
  String municipio = '';
  final centro = TextEditingController();
  String causa = '';
  final causaOtra = TextEditingController();
  final evento = TextEditingController();
  final familias = TextEditingController();
  final perdida = TextEditingController();
  final notas = TextEditingController();

  AuthUser get user;
  VoidCallback get onLogout;
  Widget? get selector;

  /// Texto de la pantalla.
  String get titulo;
  String get subtitulo;
  String get tipoCola;
  String get mensajeGuardado;

  /// Campos propios del módulo (se muestran en la sección "Afectación").
  List<Widget> camposModulo();
  List<TextEditingController> get controladoresModulo => const [];
  void limpiarModulo();
  Map<String, dynamic> datosModulo();
  Future<void> crearEnServidor(Map<String, dynamic> reg);

  @override
  void dispose() {
    for (final c in [centro, causaOtra, evento, familias, perdida, notas, ...controladoresModulo]) {
      c.dispose();
    }
    super.dispose();
  }

  void limpiar() {
    setState(() {
      version++;
      informe = DateTime.now();
      inicio = fin = null;
      departamento = municipio = causa = '';
      for (final c in [centro, causaOtra, evento, familias, perdida, notas, ...controladoresModulo]) {
        c.clear();
      }
      limpiarModulo();
    });
  }

  String? req(String? v) => (v == null || v.trim().isEmpty) ? 'Campo obligatorio' : null;
  String? optNum(String? v) => (v == null || v.trim().isEmpty) ? null : (double.tryParse(v.trim()) == null || double.parse(v.trim()) < 0 ? 'Valor inválido' : null);
  static final entero = [FilteringTextInputFormatter.digitsOnly];
  static final decimal = [FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))];
  static const numKb = TextInputType.numberWithOptions(decimal: true);

  Map<String, dynamic> _registro() => {
        'fechaInforme': fechaIso(informe),
        'monitoreoInicio': fechaIso(inicio),
        'monitoreoFin': fechaIso(fin),
        'departamento': departamento,
        'municipio': municipio,
        'centroPoblado': centro.text.trim(),
        'causa': causa,
        'causaOtra': causa == 'Otra' ? causaOtra.text.trim() : '',
        'eventoReportado': evento.text.trim(),
        'famAfectadas': familias.text.trim(),
        'perdidaEconomica': perdida.text.trim(),
        'notas': notas.text.trim(),
        ...datosModulo(),
      };

  Future<void> enviar() async {
    if (!(formKey.currentState?.validate() ?? true)) {
      showMsg(context, 'Completa los campos obligatorios.', error: true);
      return;
    }
    final errPeriodo = validarPeriodo(inicio, fin);
    if (errPeriodo != null) return showMsg(context, errPeriodo, error: true);
    final reg = _registro();
    setState(() => guardando = true);
    final queue = OfflineQueue.instance;
    try {
      // Sin internet: el registro se guarda en el teléfono y se envía solo cuando vuelva la conexión.
      if (!await OfflineQueue.hayRed()) return await _guardarPendiente(reg, queue);
      await crearEnServidor(reg);
      if (!mounted) return;
      limpiar();
      showMsg(context, mensajeGuardado);
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.sinConexion) {
        await _guardarPendiente(reg, queue);
      } else {
        showMsg(context, e.message, error: true);
      }
    } finally {
      if (mounted) setState(() => guardando = false);
    }
  }

  Future<void> _guardarPendiente(Map<String, dynamic> reg, OfflineQueue queue) async {
    try {
      await queue.encolar(reg, const [], user.id, tipo: tipoCola);
      if (!mounted) return;
      limpiar();
      showMsg(context, 'Sin conexión: el registro se guardó en el teléfono y se enviará cuando tengas internet.');
    } catch (_) {
      if (mounted) errorDialog(context, 'No se pudo guardar el registro en el teléfono. Revisa el espacio disponible.', title: 'No se pudo guardar');
    }
  }

  Widget sectionHead(IconData icon, String title, String desc, {Color? bg, Color? fg}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 22),
      child: Row(children: [
        Container(width: 42, height: 42, decoration: BoxDecoration(color: bg ?? const Color(0xFFE2F1E9), borderRadius: BorderRadius.circular(13)), child: Icon(icon, size: 21, color: fg ?? AppColors.green)),
        const SizedBox(width: 13),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(title, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w700, letterSpacing: -.5)),
          const SizedBox(height: 3),
          Text(desc, style: const TextStyle(fontSize: 11, color: AppColors.muted)),
        ])),
      ]),
    );
  }

  /// Bloque extra de la sección de ubicación (el GPS de infraestructura).
  Widget? bloqueUbicacion() => null;

  @override
  Widget build(BuildContext context) {
    return PageScroll(
      heroKey: 'crear',
      user: user,
      onLogout: onLogout,
      children: [
        ContentHead(title: titulo, subtitle: subtitulo, action: AppButton('Limpiar', icon: Icons.refresh, onPressed: limpiar)),
        ?selector,
        Panel(
          padding: const EdgeInsets.all(20),
          child: Form(
            key: formKey,
            child: KeyedSubtree(
              key: ValueKey('$version'),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                sectionHead(Icons.place_outlined, 'Período y ubicación', 'Cuándo se monitoreó y dónde ocurrió.'),
                PeriodoSection(informe: informe, inicio: inicio, fin: fin, onInforme: (d) => setState(() => informe = d), onInicio: (d) => setState(() => inicio = d), onFin: (d) => setState(() => fin = d)),
                LabeledField(
                  label: 'Departamento *',
                  child: SearchSelectField(
                    value: departamento,
                    options: kDepartamentos,
                    title: 'Departamento',
                    placeholder: 'Buscar departamento…',
                    onChanged: (v) => setState(() {
                      if (v != departamento) municipio = '';
                      departamento = v;
                    }),
                  ),
                ),
                LabeledField(
                  label: 'Municipio *',
                  child: SearchSelectField(
                    value: municipio,
                    options: kMunicipios[departamento] ?? const [],
                    title: 'Municipio',
                    enabled: departamento.isNotEmpty,
                    placeholder: departamento.isEmpty ? 'Seleccione primero el departamento' : 'Buscar municipio…',
                    onChanged: (v) => setState(() => municipio = v),
                  ),
                ),
                LabeledField(label: 'Centro poblado *', child: TextFormField(controller: centro, validator: req, textCapitalization: TextCapitalization.words, decoration: const InputDecoration(hintText: 'Ej. Aldea El Rosario'))),
                if (bloqueUbicacion() != null) bloqueUbicacion()!,
                const SizedBox(height: 14),
                sectionHead(Icons.warning_amber_rounded, 'Afectación', 'Evento y magnitud del daño.', bg: const Color(0xFFFFF0D8), fg: const Color(0xFFAF7D2B)),
                ...camposModulo(),
                LabeledField(label: 'Causa del daño *', child: SearchSelectField(value: causa, options: kCausasDano, title: 'Causa del daño', placeholder: 'Buscar causa…', onChanged: (v) => setState(() => causa = v))),
                if (causa == 'Otra') LabeledField(label: 'Especifique la causa *', child: TextFormField(controller: causaOtra, validator: req, decoration: const InputDecoration(hintText: 'Describa la causa'))),
                LabeledField(label: 'Evento reportado', child: TextFormField(controller: evento, maxLength: 150, decoration: const InputDecoration(hintText: 'Como lo reportó el técnico (ej. Inundación)', counterText: ''))),
                LabeledField(label: 'Familias afectadas', child: TextFormField(controller: familias, keyboardType: TextInputType.number, inputFormatters: entero, decoration: const InputDecoration(hintText: 'Ej. 12'))),
                ...camposDespuesDeFamilias(),
                LabeledField(label: 'Pérdida económica (Q)', child: TextFormField(controller: perdida, validator: optNum, keyboardType: numKb, inputFormatters: decimal, decoration: const InputDecoration(hintText: 'Ej. 15000'))),
                LabeledField(label: 'Descripción', child: TextFormField(controller: notas, maxLines: 4, maxLength: 3000, decoration: const InputDecoration(hintText: 'Describe lo observado…'))),
                const Divider(color: AppColors.line, height: 28),
                Align(
                  alignment: Alignment.centerRight,
                  child: AppButton(guardando ? 'Enviando…' : 'Enviar registro', primary: true, icon: Icons.send_outlined, loading: guardando, onPressed: enviar),
                ),
              ]),
            ),
          ),
        ),
      ],
    );
  }

  /// Campos que van justo después de "Familias afectadas" (los animales perdidos de pecuario).
  List<Widget> camposDespuesDeFamilias() => const [];
}

// ---------------------------------------------------------------- Pecuario

class CrearPecuarioScreen extends StatefulWidget {
  final AuthUser user;
  final VoidCallback onLogout;
  final Widget? selector;
  const CrearPecuarioScreen({super.key, required this.user, required this.onLogout, this.selector});

  @override
  State<CrearPecuarioScreen> createState() => _CrearPecuarioState();
}

class _CrearPecuarioState extends _CrearModuloState<CrearPecuarioScreen> {
  String especie = '';
  final especieOtra = TextEditingController();
  final animales = TextEditingController();

  @override
  AuthUser get user => widget.user;
  @override
  VoidCallback get onLogout => widget.onLogout;
  @override
  Widget? get selector => widget.selector;
  @override
  String get titulo => 'Nuevo registro pecuario';
  @override
  String get subtitulo => 'Afectaciones en animales: especie, familias y pérdidas.';
  @override
  String get tipoCola => kTipoPecuario;
  @override
  String get mensajeGuardado => 'Registro guardado. Puedes verlo en Registros › Pecuario.';
  @override
  List<TextEditingController> get controladoresModulo => [especieOtra, animales];

  @override
  void limpiarModulo() => especie = '';

  @override
  List<Widget> camposModulo() => [
        LabeledField(label: 'Especie *', child: SearchSelectField(value: especie, options: kEspecies, title: 'Especie', placeholder: 'Buscar especie…', onChanged: (v) => setState(() => especie = v))),
        if (especie == 'Otra') LabeledField(label: 'Especifique la especie *', child: TextFormField(controller: especieOtra, validator: req, decoration: const InputDecoration(hintText: 'Ej. Conejos'))),
      ];

  @override
  List<Widget> camposDespuesDeFamilias() => [
        LabeledField(label: 'Animales perdidos', child: TextFormField(controller: animales, keyboardType: TextInputType.number, inputFormatters: _CrearModuloState.entero, decoration: const InputDecoration(hintText: 'Ej. 40'))),
      ];

  @override
  Map<String, dynamic> datosModulo() => {
        'especie': especie,
        'especieOtra': especie == 'Otra' ? especieOtra.text.trim() : '',
        'animalesPerdidos': animales.text.trim(),
      };

  @override
  Future<void> crearEnServidor(Map<String, dynamic> reg) => pecuarioService.create(reg);
}

// ---------------------------------------------------------- Infraestructura

class CrearInfraestructuraScreen extends StatefulWidget {
  final AuthUser user;
  final VoidCallback onLogout;
  final Widget? selector;
  const CrearInfraestructuraScreen({super.key, required this.user, required this.onLogout, this.selector});

  @override
  State<CrearInfraestructuraScreen> createState() => _CrearInfraestructuraState();
}

class _CrearInfraestructuraState extends _CrearModuloState<CrearInfraestructuraScreen> {
  String tipo = '';
  final tipoOtra = TextEditingController();
  double? lat, lon, acc;
  bool gpsBuscando = false;

  @override
  AuthUser get user => widget.user;
  @override
  VoidCallback get onLogout => widget.onLogout;
  @override
  Widget? get selector => widget.selector;
  @override
  String get titulo => 'Nuevo registro de infraestructura';
  @override
  String get subtitulo => 'Afectaciones en infraestructura productiva: tipo, ubicación y pérdidas.';
  @override
  String get tipoCola => kTipoInfraestructura;
  @override
  String get mensajeGuardado => 'Registro guardado. Puedes verlo en Registros › Infraestructura.';
  @override
  List<TextEditingController> get controladoresModulo => [tipoOtra];

  @override
  void limpiarModulo() {
    tipo = '';
    lat = lon = acc = null;
  }

  Future<void> _capturarGPS() async {
    setState(() => gpsBuscando = true);
    try {
      if (!await Geolocator.isLocationServiceEnabled()) throw 'Activa el GPS (servicios de ubicación) de tu dispositivo.';
      var perm = await Geolocator.checkPermission();
      if (perm == LocationPermission.denied) perm = await Geolocator.requestPermission();
      if (perm == LocationPermission.denied) throw 'Se necesita el permiso de ubicación para capturar el GPS.';
      if (perm == LocationPermission.deniedForever) throw 'El permiso de ubicación está bloqueado. Habilítalo en los ajustes de la aplicación.';
      final pos = await Geolocator.getCurrentPosition(locationSettings: const LocationSettings(accuracy: LocationAccuracy.best, timeLimit: Duration(seconds: 20)));
      if (!mounted) return;
      setState(() {
        lat = double.parse(pos.latitude.toStringAsFixed(6));
        lon = double.parse(pos.longitude.toStringAsFixed(6));
        acc = pos.accuracy.roundToDouble();
      });
    } catch (e) {
      if (mounted) errorDialog(context, e is String ? e : 'No se pudo obtener la ubicación. Intenta de nuevo al aire libre.', title: 'No se pudo obtener la ubicación');
    } finally {
      if (mounted) setState(() => gpsBuscando = false);
    }
  }

  @override
  Widget? bloqueUbicacion() => Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(15),
          border: Border.all(color: const Color(0xFFD2E6DC)),
          gradient: const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Color(0xFFE6F3EC), Color(0xFFF5FAF5)]),
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            const Icon(Icons.my_location, size: 19, color: AppColors.green),
            const SizedBox(width: 8),
            const Expanded(child: Text('Ubicación GPS (opcional)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700))),
            AppBadge(lat != null ? 'Capturada' : 'Sin capturar', kind: lat != null ? BadgeKind.green : BadgeKind.gray),
          ]),
          const SizedBox(height: 10),
          Text(lat != null ? '$lat, $lon (±${acc?.round()} m)' : 'Sin coordenadas', style: const TextStyle(fontSize: 12)),
          const SizedBox(height: 12),
          SizedBox(width: double.infinity, child: AppButton(gpsBuscando ? 'Buscando…' : 'Usar mi ubicación actual', icon: Icons.my_location, expand: true, loading: gpsBuscando, onPressed: _capturarGPS)),
        ]),
      );

  @override
  List<Widget> camposModulo() => [
        LabeledField(label: 'Tipo de infraestructura *', child: SearchSelectField(value: tipo, options: kTiposInfraestructura, title: 'Tipo de infraestructura', placeholder: 'Buscar tipo…', onChanged: (v) => setState(() => tipo = v))),
        if (tipo == 'Otra') LabeledField(label: 'Especifique el tipo *', child: TextFormField(controller: tipoOtra, validator: req, decoration: const InputDecoration(hintText: 'Ej. Beneficio húmedo'))),
      ];

  @override
  Map<String, dynamic> datosModulo() => {
        'tipoInfraestructura': tipo,
        'tipoOtra': tipo == 'Otra' ? tipoOtra.text.trim() : '',
        'gpsLat': lat,
        'gpsLon': lon,
        'gpsPrecision': acc,
      };

  @override
  Future<void> crearEnServidor(Map<String, dynamic> reg) => infraestructuraService.create(reg);
}
