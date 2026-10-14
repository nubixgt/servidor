import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import '../data/catalogos.dart';
import '../data/guatemala.dart';
import '../models/usuario.dart';
import '../services/api_client.dart';
import '../services/dano_service.dart';
import '../services/offline_queue.dart';
import '../theme/app_theme.dart';
import '../widgets/app_shell.dart';
import '../widgets/common.dart';
import '../widgets/search_select.dart';

class _Asociado {
  String cultivo = '';
  String etapa = '';
  final areaLote = TextEditingController();
  final areaAfectada = TextEditingController();
  void dispose() {
    areaLote.dispose();
    areaAfectada.dispose();
  }
}

/// Formulario de registro por pasos (Ubicación, Cultivo, Daños, Evidencia).
class CrearRegistroScreen extends StatefulWidget {
  final AuthUser user;
  final VoidCallback onLogout;
  const CrearRegistroScreen({super.key, required this.user, required this.onLogout});

  @override
  State<CrearRegistroScreen> createState() => _CrearRegistroScreenState();
}

class _CrearRegistroScreenState extends State<CrearRegistroScreen> {
  static const _maxFotos = 10;
  final _service = DanoService();
  final _forms = List.generate(4, (_) => GlobalKey<FormState>());

  int _paso = 0;
  bool _guardando = false;
  int _version = 0; // fuerza a reconstruir los campos al limpiar

  // Ubicación
  late DateTime _fecha;
  final _productor = TextEditingController();
  String _departamento = '';
  String _municipio = '';
  final _centro = TextEditingController();
  String _rua = '';
  final _codigoRua = TextEditingController();
  final _lote = TextEditingController();
  double? _lat, _lon, _acc;
  bool _gpsBuscando = false;

  // Cultivo
  String _cultivo = '';
  String _etapa = '';
  final _areaLote = TextEditingController();
  final _areaAfectada = TextEditingController();
  final List<_Asociado> _asociados = [];

  // Daños
  String _causa = '';
  final _causaOtra = TextEditingController();
  final _danoPorc = TextEditingController();
  final _rend = TextEditingController();
  String _unidad = 't/ha';
  final _perdida = TextEditingController();
  bool _perdidaManual = false;

  // Evidencia
  final List<XFile> _fotos = [];
  final _notas = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fecha = DateTime.now();
    for (final c in [_areaAfectada, _rend, _danoPorc]) {
      c.addListener(_calcPerdida);
    }
  }

  @override
  void dispose() {
    for (final c in [_productor, _centro, _codigoRua, _lote, _areaLote, _areaAfectada, _causaOtra, _danoPorc, _rend, _perdida, _notas]) {
      c.dispose();
    }
    for (final a in _asociados) {
      a.dispose();
    }
    super.dispose();
  }

  // Pérdida = ha afectadas × rendimiento × %daño, salvo que el usuario la haya editado.
  void _calcPerdida() {
    if (_perdidaManual) return;
    final s = double.tryParse(_areaAfectada.text), r = double.tryParse(_rend.text), p = double.tryParse(_danoPorc.text);
    final v = (s != null && r != null && p != null && s > 0 && r > 0 && p > 0) ? (s * r * p / 100).toStringAsFixed(2) : '';
    if (_perdida.text != v) _perdida.text = v;
  }

  void _limpiar() {
    setState(() {
      _paso = 0;
      _version++;
      _fecha = DateTime.now();
      for (final c in [_productor, _centro, _codigoRua, _lote, _areaLote, _areaAfectada, _causaOtra, _danoPorc, _rend, _perdida, _notas]) {
        c.clear();
      }
      _departamento = _municipio = _rua = _cultivo = _etapa = _causa = '';
      _unidad = 't/ha';
      _perdidaManual = false;
      _lat = _lon = _acc = null;
      for (final a in _asociados) {
        a.dispose();
      }
      _asociados.clear();
      _fotos.clear();
    });
  }

  // ---------- GPS ----------
  Future<void> _capturarGPS() async {
    setState(() => _gpsBuscando = true);
    try {
      if (!await Geolocator.isLocationServiceEnabled()) {
        throw 'Activa el GPS (servicios de ubicación) de tu dispositivo.';
      }
      var perm = await Geolocator.checkPermission();
      if (perm == LocationPermission.denied) perm = await Geolocator.requestPermission();
      if (perm == LocationPermission.denied) throw 'Se necesita el permiso de ubicación para capturar el GPS.';
      if (perm == LocationPermission.deniedForever) {
        throw 'El permiso de ubicación está bloqueado. Habilítalo en los ajustes de la aplicación.';
      }
      final pos = await Geolocator.getCurrentPosition(locationSettings: const LocationSettings(accuracy: LocationAccuracy.best, timeLimit: Duration(seconds: 20)));
      if (!mounted) return;
      setState(() {
        _lat = double.parse(pos.latitude.toStringAsFixed(6));
        _lon = double.parse(pos.longitude.toStringAsFixed(6));
        _acc = pos.accuracy.roundToDouble();
      });
    } catch (e) {
      if (mounted) errorDialog(context, e is String ? e : 'No se pudo obtener la ubicación. Intenta de nuevo al aire libre.', title: 'No se pudo obtener la ubicación');
    } finally {
      if (mounted) setState(() => _gpsBuscando = false);
    }
  }

  // ---------- Fotos ----------
  Future<void> _agregarFotos() async {
    final origen = await showModalBottomSheet<ImageSource>(
      context: context,
      backgroundColor: const Color(0xFFFAFDF9),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(23))),
      builder: (c) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const SizedBox(height: 8),
          ListTile(leading: const Icon(Icons.photo_camera_outlined, color: AppColors.green), title: const Text('Tomar fotografía'), onTap: () => Navigator.pop(c, ImageSource.camera)),
          ListTile(leading: const Icon(Icons.photo_library_outlined, color: AppColors.green), title: const Text('Elegir de la galería'), onTap: () => Navigator.pop(c, ImageSource.gallery)),
          const SizedBox(height: 8),
        ]),
      ),
    );
    if (origen == null) return;
    try {
      final picker = ImagePicker();
      final nuevas = origen == ImageSource.camera
          ? [?await picker.pickImage(source: ImageSource.camera, imageQuality: 80, maxWidth: 2048)]
          : await picker.pickMultiImage(imageQuality: 80, maxWidth: 2048);
      if (!mounted) return;
      setState(() {
        for (final f in nuevas) {
          if (_fotos.length >= _maxFotos) {
            showMsg(context, 'Máximo $_maxFotos fotos por registro.');
            break;
          }
          _fotos.add(f);
        }
      });
    } catch (_) {
      if (mounted) errorDialog(context, 'No se pudo acceder a la cámara o galería. Revisa los permisos de la aplicación.', title: 'Permiso necesario');
    }
  }

  // ---------- Navegación entre pasos ----------
  void _ir(int i) => setState(() => _paso = i.clamp(0, 3));

  void _siguiente() {
    if (!(_forms[_paso].currentState?.validate() ?? true)) return;
    if (_paso == 0 && _lat == null) {
      errorDialog(context, 'Captura tu ubicación actual con el botón "Usar mi ubicación actual".', title: 'Ubicación obligatoria');
      return;
    }
    _ir(_paso + 1);
  }

  Future<void> _enviar() async {
    for (var i = 0; i < 4; i++) {
      if (!(_forms[i].currentState?.validate() ?? true)) {
        _ir(i);
        showMsg(context, 'Completa los campos obligatorios de este paso.', error: true);
        return;
      }
    }
    if (_lat == null) {
      _ir(0);
      errorDialog(context, 'Captura tu ubicación actual con el botón "Usar mi ubicación actual".', title: 'Ubicación obligatoria');
      return;
    }
    final reg = <String, dynamic>{
      'fecha': DateFormat('yyyy-MM-dd').format(_fecha),
      'tecnico': _productor.text.trim(),
      'departamento': _departamento,
      'municipio': _municipio,
      'centroPoblado': _centro.text.trim(),
      'agricultorRua': _rua == 'Sí',
      'codigoRua': _rua == 'Sí' ? _codigoRua.text.trim() : '',
      'lote': _lote.text.trim(),
      'cultivo': _cultivo,
      'etapa': _etapa,
      'asociados': [
        for (final a in _asociados) {'cultivo': a.cultivo, 'etapa': a.etapa, 'areaLote': a.areaLote.text.trim(), 'areaAfectada': a.areaAfectada.text.trim()},
      ],
      'causa': _causa,
      'causaOtra': _causa == 'Otra' ? _causaOtra.text.trim() : '',
      'areaLote': _areaLote.text.trim(),
      'areaAfectada': _areaAfectada.text.trim(),
      'danoPorc': _danoPorc.text.trim(),
      'rendEsperado': _rend.text.trim(),
      'unidad': _unidad,
      'perdidaEstimada': _perdida.text.trim(),
      'gpsLat': _lat,
      'gpsLon': _lon,
      'gpsPrecision': _acc,
      'notas': _notas.text.trim(),
    };
    setState(() => _guardando = true);
    final fotos = _fotos.map((f) => File(f.path)).toList();
    final queue = OfflineQueue.instance;
    try {
      // Sin internet: el registro se guarda en el teléfono y se envía solo cuando vuelva la conexión.
      if (!await OfflineQueue.hayRed()) {
        await _guardarPendiente(reg, fotos, queue);
        return;
      }
      final r = await _service.create(reg, fotos);
      if (!mounted) return;
      if (r.fotosError != null && r.fotosSinConexion) {
        // El registro ya está en el servidor; solo faltan las fotos.
        await queue.encolar(reg, fotos, widget.user.id, danoId: r.dano.id);
        if (!mounted) return;
        _limpiar();
        showMsg(context, 'Registro guardado. Las fotos se enviarán cuando vuelvas a tener internet.');
      } else if (r.fotosError != null) {
        _limpiar();
        showMsg(context, 'Registro guardado, pero las fotos no se subieron: ${r.fotosError}');
      } else {
        _limpiar();
        showMsg(context, 'Registro guardado. Puedes verlo en el apartado Registros.');
      }
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.sinConexion) {
        await _guardarPendiente(reg, fotos, queue);
      } else {
        showMsg(context, e.message, error: true);
      }
    } finally {
      if (mounted) setState(() => _guardando = false);
    }
  }

  Future<void> _guardarPendiente(Map<String, dynamic> reg, List<File> fotos, OfflineQueue queue) async {
    try {
      await queue.encolar(reg, fotos, widget.user.id);
      if (!mounted) return;
      _limpiar();
      showMsg(context, 'Sin conexión: el registro se guardó en el teléfono y se enviará cuando tengas internet.');
    } catch (_) {
      if (mounted) errorDialog(context, 'No se pudo guardar el registro en el teléfono. Revisa el espacio disponible.', title: 'No se pudo guardar');
    }
  }

  // ---------- UI ----------
  static const _pasos = [
    ('Ubicación', Icons.place_outlined),
    ('Cultivo', Icons.eco_outlined),
    ('Daños', Icons.warning_amber_rounded),
    ('Evidencia', Icons.photo_camera_outlined),
  ];

  bool _hecho(int i) => switch (i) {
        0 => _productor.text.isNotEmpty && _lote.text.isNotEmpty && _departamento.isNotEmpty && _municipio.isNotEmpty && _centro.text.isNotEmpty && _rua.isNotEmpty && _lat != null,
        1 => _cultivo.isNotEmpty && _areaAfectada.text.isNotEmpty,
        2 => _causa.isNotEmpty && _danoPorc.text.isNotEmpty,
        _ => _fotos.isNotEmpty,
      };

  @override
  Widget build(BuildContext context) {
    return PageScroll(
      heroKey: 'crear',
      user: widget.user,
      onLogout: widget.onLogout,
      children: [
        ContentHead(title: 'Nuevo registro de afectación', subtitle: 'Completa la información del levantamiento en campo.', action: AppButton('Limpiar', icon: Icons.refresh, onPressed: _limpiar)),
        _tabs(),
        const SizedBox(height: 16),
        Panel(
          padding: const EdgeInsets.all(20),
          child: Column(children: [
            for (var i = 0; i < 4; i++) Offstage(offstage: _paso != i, child: Form(key: _forms[i], child: KeyedSubtree(key: ValueKey('$_version-$i'), child: [_paso0, _paso1, _paso2, _paso3][i]()))),
            const Divider(color: AppColors.line, height: 28),
            Row(children: [
              AppButton('Anterior', icon: Icons.arrow_back, onPressed: _paso == 0 ? null : () => _ir(_paso - 1)),
              const Spacer(),
              if (_paso < 3) AppButton('Continuar', primary: true, trailingIcon: Icons.arrow_forward, onPressed: _siguiente) else AppButton(_guardando ? 'Enviando…' : 'Enviar registro', primary: true, icon: Icons.send_outlined, loading: _guardando, onPressed: _enviar),
            ]),
          ]),
        ),
        const SizedBox(height: 16),
        _resumen(),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(color: const Color(0xED0B6149), borderRadius: BorderRadius.circular(14)),
          child: const Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Icon(Icons.eco_outlined, color: Color(0xFFC1EAA7), size: 20),
            SizedBox(height: 10),
            Text('Un buen registro hace la diferencia.', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Colors.white)),
            SizedBox(height: 7),
            Text('Verifica la ubicación, conserva la misma unidad de medida y toma fotografías claras del daño.', style: TextStyle(fontSize: 11, color: Color(0xFFD1E8D7), height: 1.5)),
          ]),
        ),
      ],
    );
  }

  Widget _tabs() {
    return Container(
      padding: const EdgeInsets.all(5),
      decoration: BoxDecoration(color: const Color(0xAFFFFFFF), borderRadius: BorderRadius.circular(17), border: Border.all(color: Colors.white)),
      child: Row(children: [
        for (var i = 0; i < 4; i++)
          Expanded(
            child: InkWell(
              borderRadius: BorderRadius.circular(12),
              onTap: () => _ir(i),
              child: Container(
                padding: const EdgeInsets.symmetric(vertical: 9),
                decoration: i == _paso ? BoxDecoration(color: const Color(0xFFEFF9F0), borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0xFF9DCCB0))) : null,
                child: Column(mainAxisSize: MainAxisSize.min, children: [
                  Container(
                    width: 27,
                    height: 27,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: i == _paso ? const LinearGradient(colors: [Color(0xFF15A76A), Color(0xFF007B54)]) : null,
                      color: i == _paso ? null : (_hecho(i) ? const Color(0xFFE2F1E9) : Colors.white),
                      border: Border.all(color: i == _paso ? const Color(0xFF00A864) : const Color(0xFFD3E4DB)),
                    ),
                    child: _hecho(i) && i != _paso
                        ? const Icon(Icons.check, size: 15, color: AppColors.green)
                        : Text('0${i + 1}', style: TextStyle(fontSize: 11, color: i == _paso ? Colors.white : const Color(0xFF66807B))),
                  ),
                  const SizedBox(height: 6),
                  Text(_pasos[i].$1, style: TextStyle(fontSize: 10, color: i == _paso ? const Color(0xFF057C55) : const Color(0xFF66807B), fontWeight: i == _paso ? FontWeight.w700 : FontWeight.w400)),
                ]),
              ),
            ),
          ),
      ]),
    );
  }

  Widget _sectionHead(IconData icon, String title, String desc, {Color? bg, Color? fg, Widget? action}) {
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
        ?action,
      ]),
    );
  }

  String? _req(String? v) => (v == null || v.trim().isEmpty) ? 'Campo obligatorio' : null;
  String? _reqNum(String? v, {double? max}) {
    if (v == null || v.trim().isEmpty) return 'Campo obligatorio';
    final n = double.tryParse(v.trim());
    if (n == null || n < 0) return 'Valor inválido';
    if (max != null && n > max) return 'Máximo $max';
    return null;
  }

  String? _optNum(String? v) => (v == null || v.trim().isEmpty) ? null : (double.tryParse(v.trim()) == null || double.parse(v.trim()) < 0 ? 'Valor inválido' : null);

  static final _decimal = [FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))];
  static const _numKb = TextInputType.numberWithOptions(decimal: true);

  Widget _paso0() {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      _sectionHead(Icons.place_outlined, 'Ubicación del registro', 'Identifica al productor y el lugar de la afectación.'),
      LabeledField(
        label: 'Fecha de levantamiento *',
        child: InkWell(
          borderRadius: BorderRadius.circular(10),
          onTap: () async {
            final r = await showDatePicker(context: context, initialDate: _fecha, firstDate: DateTime(2020), lastDate: DateTime(2100));
            if (r != null) setState(() => _fecha = r);
          },
          child: InputDecorator(decoration: const InputDecoration(suffixIcon: Icon(Icons.calendar_month_outlined, size: 20)), child: Text(DateFormat('dd/MM/yyyy').format(_fecha), style: const TextStyle(fontSize: 14))),
        ),
      ),
      LabeledField(label: 'Productor *', child: TextFormField(controller: _productor, validator: _req, textCapitalization: TextCapitalization.words, decoration: const InputDecoration(hintText: 'Ej. Juan Pérez'), onChanged: (_) => setState(() {}))),
      LabeledField(
        label: 'Departamento *',
        child: SearchSelectField(
          value: _departamento,
          options: kDepartamentos,
          title: 'Departamento',
          placeholder: 'Buscar departamento…',
          onChanged: (v) => setState(() {
            if (v != _departamento) _municipio = '';
            _departamento = v;
          }),
        ),
      ),
      LabeledField(
        label: 'Municipio *',
        child: SearchSelectField(
          value: _municipio,
          options: kMunicipios[_departamento] ?? const [],
          title: 'Municipio',
          enabled: _departamento.isNotEmpty,
          placeholder: _departamento.isEmpty ? 'Seleccione primero el departamento' : 'Buscar municipio…',
          onChanged: (v) => setState(() => _municipio = v),
        ),
      ),
      LabeledField(label: 'Centro poblado *', child: TextFormField(controller: _centro, validator: _req, textCapitalization: TextCapitalization.words, decoration: const InputDecoration(hintText: 'Ej. Aldea El Rosario'), onChanged: (_) => setState(() {}))),
      LabeledField(label: 'Agricultor en RUA *', child: ChoiceButtons(value: _rua, options: const ['Sí', 'No'], onChanged: (v) => setState(() => _rua = v))),
      if (_rua == 'Sí') LabeledField(label: 'Código de RUA *', child: TextFormField(controller: _codigoRua, validator: _req, decoration: const InputDecoration(hintText: 'Escriba el código de RUA'))),
      LabeledField(label: 'Lote / parcela *', child: TextFormField(controller: _lote, validator: _req, decoration: const InputDecoration(hintText: 'Ej. Lote 3 - El Potrero'), onChanged: (_) => setState(() {}))),
      _gpsBox(),
    ]);
  }

  Widget _gpsBox() {
    Widget valor(String t, String v) => Expanded(
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 11),
            decoration: BoxDecoration(color: const Color(0x99FFFFFF), borderRadius: BorderRadius.circular(10), border: Border.all(color: const Color(0xFFDCE9E0))),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(t, style: const TextStyle(fontSize: 9, color: Color(0xFF719081))),
              const SizedBox(height: 5),
              Text(v, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700), maxLines: 1, overflow: TextOverflow.ellipsis),
            ]),
          ),
        );
    return Container(
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
          const Expanded(child: Text('Ubicación GPS *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700))),
          AppBadge(_lat != null ? 'Capturada' : 'Sin capturar', kind: _lat != null ? BadgeKind.green : BadgeKind.gray),
        ]),
        const SizedBox(height: 14),
        Row(children: [
          valor('Latitud', _lat?.toString() ?? '—'),
          const SizedBox(width: 6),
          valor('Longitud', _lon?.toString() ?? '—'),
          const SizedBox(width: 6),
          valor('Precisión', _acc != null ? '${_acc!.round()} m' : '— m'),
        ]),
        const SizedBox(height: 14),
        SizedBox(width: double.infinity, child: AppButton(_gpsBuscando ? 'Buscando…' : 'Usar mi ubicación actual', icon: Icons.my_location, expand: true, loading: _gpsBuscando, onPressed: _capturarGPS)),
        const SizedBox(height: 10),
        const Text('En campo, procura una precisión menor a 10 m. La ubicación actual es obligatoria.', style: TextStyle(fontSize: 10, color: Color(0xFF739285))),
      ]),
    );
  }

  Widget _paso1() {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      _sectionHead(Icons.eco_outlined, 'Información del cultivo', 'Registra el cultivo y la superficie observada.'),
      LabeledField(
        label: 'Cultivo *',
        child: SearchSelectField(value: _cultivo, options: kCultivos, title: 'Cultivo', placeholder: 'Buscar cultivo…', onChanged: (v) => setState(() => _cultivo = v)),
      ),
      LabeledField(label: 'Estado fenológico', child: SelectBox(value: _etapa, options: kEtapas, onChanged: (v) => setState(() => _etapa = v))),
      LabeledField(label: 'Superficie total del lote (ha)', child: TextFormField(controller: _areaLote, validator: _optNum, keyboardType: _numKb, inputFormatters: _decimal, decoration: const InputDecoration(hintText: 'Ej. 25.5'))),
      LabeledField(label: 'Superficie afectada (ha) *', child: TextFormField(controller: _areaAfectada, validator: _reqNum, keyboardType: _numKb, inputFormatters: _decimal, decoration: const InputDecoration(hintText: 'Ej. 8.2'), onChanged: (_) => setState(() {}))),
      Align(alignment: Alignment.centerLeft, child: AppButton('Agregar cultivo asociado', icon: Icons.add, onPressed: () => setState(() => _asociados.add(_Asociado())))),
      for (var i = 0; i < _asociados.length; i++) _asociadoCard(i),
    ]);
  }

  Widget _asociadoCard(int i) {
    final a = _asociados[i];
    return Container(
      key: ObjectKey(a),
      margin: const EdgeInsets.only(top: 18),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(15),
        border: Border.all(color: const Color(0xFFD2E6DC)),
        gradient: const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Color(0xFFE6F3EC), Color(0xFFF5FAF5)]),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Expanded(child: Text('Cultivo asociado ${i + 1}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700))),
          AppButton('Quitar', icon: Icons.delete_outline, danger: true, onPressed: () => setState(() => _asociados.removeAt(i).dispose())),
        ]),
        const SizedBox(height: 14),
        LabeledField(label: 'Cultivo *', child: SearchSelectField(value: a.cultivo, options: kCultivos, title: 'Cultivo asociado', placeholder: 'Buscar cultivo…', onChanged: (v) => setState(() => a.cultivo = v))),
        LabeledField(label: 'Estado fenológico', child: SelectBox(value: a.etapa, options: kEtapas, onChanged: (v) => setState(() => a.etapa = v))),
        LabeledField(label: 'Superficie total del lote (ha)', child: TextFormField(controller: a.areaLote, validator: _optNum, keyboardType: _numKb, inputFormatters: _decimal, decoration: const InputDecoration(hintText: 'Ej. 25.5'))),
        LabeledField(label: 'Superficie afectada (ha) *', child: TextFormField(controller: a.areaAfectada, validator: _reqNum, keyboardType: _numKb, inputFormatters: _decimal, decoration: const InputDecoration(hintText: 'Ej. 8.2'))),
      ]),
    );
  }

  Widget _paso2() {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      _sectionHead(Icons.warning_amber_rounded, 'Evaluación de daños', 'Causa, porcentaje de daño y pérdida estimada.', bg: const Color(0xFFFFF0D8), fg: const Color(0xFFAF7D2B)),
      LabeledField(label: 'Causa del daño *', child: SearchSelectField(value: _causa, options: kCausasDano, title: 'Causa del daño', placeholder: 'Buscar causa…', onChanged: (v) => setState(() => _causa = v))),
      if (_causa == 'Otra') LabeledField(label: 'Especifique la causa *', child: TextFormField(controller: _causaOtra, validator: _req, decoration: const InputDecoration(hintText: 'Describa la causa'))),
      LabeledField(label: '% de daño promedio del lote *', child: TextFormField(controller: _danoPorc, validator: (v) => _reqNum(v, max: 100), keyboardType: _numKb, inputFormatters: _decimal, decoration: const InputDecoration(hintText: '0–100'), onChanged: (_) => setState(() {}))),
      LabeledField(
        label: 'Rendimiento esperado sin daño',
        hint: 'Mantenga la misma unidad en todo el relevamiento.',
        child: Row(children: [
          Expanded(child: TextFormField(controller: _rend, validator: _optNum, keyboardType: _numKb, inputFormatters: _decimal, decoration: const InputDecoration(hintText: 'Ej. 8.5'))),
          const SizedBox(width: 8),
          SizedBox(width: 110, child: SelectBox(value: _unidad, options: const ['t/ha', 'kg/ha'], onChanged: (v) => setState(() => _unidad = v.isEmpty ? 't/ha' : v))),
        ]),
      ),
      LabeledField(
        label: 'Pérdida de rendimiento estimada',
        hint: '= Sup. afectada × Rend. esperado × (% daño ÷ 100). Puede editarla.',
        child: TextFormField(controller: _perdida, validator: _optNum, keyboardType: _numKb, inputFormatters: _decimal, decoration: const InputDecoration(hintText: 'Se calcula automáticamente'), onChanged: (_) => _perdidaManual = true),
      ),
    ]);
  }

  Widget _paso3() {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      _sectionHead(Icons.photo_camera_outlined, 'Evidencia del daño', 'Agrega fotografías y describe lo observado.', action: Text('${_fotos.length}/$_maxFotos', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.muted))),
      InkWell(
        onTap: _agregarFotos,
        borderRadius: BorderRadius.circular(15),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 28, horizontal: 20),
          decoration: BoxDecoration(color: const Color(0xFFECF7EF), borderRadius: BorderRadius.circular(15), border: Border.all(color: const Color(0xFF73B897), width: 1.5)),
          child: const Column(children: [
            Icon(Icons.photo_camera_outlined, size: 30, color: AppColors.green),
            SizedBox(height: 12),
            Text('Agregar fotografías', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
            SizedBox(height: 6),
            Text('Toca para capturar o seleccionar · hasta $_maxFotos imágenes', textAlign: TextAlign.center, style: TextStyle(fontSize: 10, color: Color(0xFF749284))),
          ]),
        ),
      ),
      if (_fotos.isNotEmpty) ...[
        const SizedBox(height: 14),
        GridView.count(
          crossAxisCount: 3,
          mainAxisSpacing: 10,
          crossAxisSpacing: 10,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          children: [
            for (var i = 0; i < _fotos.length; i++)
              Stack(fit: StackFit.expand, children: [
                ClipRRect(borderRadius: BorderRadius.circular(10), child: Image.file(File(_fotos[i].path), fit: BoxFit.cover, cacheWidth: 300)),
                Positioned(
                  top: 3,
                  right: 3,
                  child: GestureDetector(
                    onTap: () => setState(() => _fotos.removeAt(i)),
                    child: Container(width: 24, height: 24, decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle), child: const Icon(Icons.close, size: 15, color: Color(0xFFA03737))),
                  ),
                ),
              ]),
          ],
        ),
      ],
      const SizedBox(height: 20),
      LabeledField(label: 'Descripción del daño', child: TextFormField(controller: _notas, maxLines: 5, maxLength: 3000, decoration: const InputDecoration(hintText: 'Describe los daños observados, las condiciones del cultivo y cualquier información relevante…'))),
    ]);
  }

  Widget _resumen() {
    final causa = _causa == 'Otra' ? (_causaOtra.text.isEmpty ? 'Otra' : _causaOtra.text) : _causa;
    final cultivos = [_cultivo, ..._asociados.map((a) => a.cultivo)].where((e) => e.isNotEmpty).join(', ');
    final filas = <(String, String)>[
      ('Productor', _productor.text),
      ('Lote / parcela', _lote.text),
      ('Municipio', _municipio.isEmpty ? '' : '$_municipio, $_departamento'),
      ('Ubicación GPS', _lat != null ? '$_lat, $_lon' : 'Pendiente'),
      ('Cultivo', cultivos),
      ('Causa', causa),
      ('Daño promedio', _danoPorc.text.isEmpty ? '' : '${_danoPorc.text}%'),
      ('Fotografías', '${_fotos.length} / $_maxFotos'),
    ];
    return Panel(
      padding: const EdgeInsets.all(20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const Row(children: [Icon(Icons.description_outlined, size: 20, color: AppColors.green), SizedBox(width: 10), Text('Resumen del registro', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700))]),
        const SizedBox(height: 16),
        ClipRRect(borderRadius: BorderRadius.circular(4), child: LinearProgressIndicator(value: (_paso + 1) / 4, minHeight: 4, backgroundColor: const Color(0xFFDFEAE2), color: const Color(0xFF219E65))),
        const SizedBox(height: 5),
        Text('Paso ${_paso + 1} de 4', style: const TextStyle(fontSize: 9, color: Color(0xFF829687))),
        const SizedBox(height: 10),
        for (final f in filas)
          Container(
            padding: const EdgeInsets.symmetric(vertical: 11),
            decoration: const BoxDecoration(border: Border(bottom: BorderSide(color: Color(0xFFE0E9E2)))),
            child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Expanded(child: Text(f.$1, style: const TextStyle(fontSize: 11, color: Color(0xFF759085)))),
              const SizedBox(width: 12),
              Flexible(child: Text(f.$2.isEmpty ? '—' : f.$2, textAlign: TextAlign.right, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600))),
            ]),
          ),
        const SizedBox(height: 16),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(color: const Color(0xFFE6F3E9), borderRadius: BorderRadius.circular(13)),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Row(children: [Expanded(child: Text('ÁREA AFECTADA', style: TextStyle(fontSize: 10, color: Color(0xFF70917D)))), Icon(Icons.layers_outlined, size: 19, color: Color(0xFF3B9165))]),
            const SizedBox(height: 6),
            Row(crossAxisAlignment: CrossAxisAlignment.baseline, textBaseline: TextBaseline.alphabetic, children: [
              Text(_areaAfectada.text.isEmpty ? '0' : _areaAfectada.text, style: const TextStyle(fontSize: 25, fontWeight: FontWeight.w700)),
              const SizedBox(width: 4),
              const Text('ha', style: TextStyle(fontSize: 13)),
            ]),
            const SizedBox(height: 4),
            const Text('Superficie registrada en hectáreas', style: TextStyle(fontSize: 10, color: Color(0xFF70917D))),
          ]),
        ),
      ]),
    );
  }
}
