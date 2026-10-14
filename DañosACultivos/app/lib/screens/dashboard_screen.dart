import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:intl/intl.dart';
import 'package:latlong2/latlong.dart';

import '../models/dano_cultivo.dart';
import '../models/usuario.dart';
import '../services/api_client.dart';
import '../services/dano_service.dart';
import '../services/offline_queue.dart';
import '../theme/app_theme.dart';
import '../widgets/app_shell.dart';
import '../widgets/common.dart';
import '../widgets/dano_detalle.dart';

const _nivelColor = [Color(0xFF3BB54A), Color(0xFFF5C400), Color(0xFFFF7A00), Color(0xFFEF3340)];

class DashboardScreen extends StatefulWidget {
  final AuthUser user;
  final VoidCallback onLogout;
  final VoidCallback onCrear;
  final bool active;
  const DashboardScreen({super.key, required this.user, required this.onLogout, required this.onCrear, required this.active});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final _service = DanoService();
  List<DanoCultivo> _todos = [];
  bool _loading = false;
  String? _error;
  DateTime? _desde;
  DateTime? _hasta;
  bool _verTodos = false;
  final OfflineQueue _queue = OfflineQueue.instance;
  int _exitos = 0;

  @override
  void initState() {
    super.initState();
    _exitos = _queue.exitos;
    _queue.addListener(_onQueue);
    _cargar();
  }

  @override
  void dispose() {
    _queue.removeListener(_onQueue);
    super.dispose();
  }

  void _onQueue() {
    if (_queue.exitos != _exitos) {
      _exitos = _queue.exitos;
      _cargar();
    }
  }

  @override
  void didUpdateWidget(covariant DashboardScreen old) {
    super.didUpdateWidget(old);
    if (widget.active && !old.active) _cargar();
  }

  Future<void> _cargar() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final l = await _service.list();
      if (mounted) setState(() => _todos = l);
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = 'No se pudieron cargar los registros. ${e.message}');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _iso(DateTime d) => DateFormat('yyyy-MM-dd').format(d);
  bool _enRango(DanoCultivo d, String? desde, String? hasta) => (desde == null || d.fecha.compareTo(desde) >= 0) && (hasta == null || d.fecha.compareTo(hasta) <= 0);

  List<DanoCultivo> get _lista => _todos.where((d) => _enRango(d, _desde == null ? null : _iso(_desde!), _hasta == null ? null : _iso(_hasta!))).toList();

  // Periodo anterior de igual duración (solo con rango completo).
  List<DanoCultivo>? get _previa {
    if (_desde == null || _hasta == null) return null;
    final dias = _hasta!.difference(_desde!).inDays + 1;
    final finPrev = _desde!.subtract(const Duration(days: 1));
    final iniPrev = finPrev.subtract(Duration(days: dias - 1));
    return _todos.where((d) => _enRango(d, _iso(iniPrev), _iso(finPrev))).toList();
  }

  int? _tendencia(num Function(List<DanoCultivo>) fn, List<DanoCultivo> lista) {
    final p = _previa;
    if (p == null) return null;
    final a = fn(lista), b = fn(p);
    if (b == 0) return null;
    return (((a - b) / b) * 100).round();
  }

  Future<void> _pickFecha(bool desde) async {
    final r = await showDatePicker(
      context: context,
      initialDate: (desde ? _desde : _hasta) ?? DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
      helpText: desde ? 'Desde' : 'Hasta',
    );
    if (r != null) setState(() => desde ? _desde = r : _hasta = r);
  }

  Future<void> _eliminar(DanoCultivo d) async {
    if (!await confirmDialog(context, 'Se eliminará el registro de ${d.cultivo} (${d.lote}).', title: '¿Eliminar registro?', danger: true, confirmText: 'Eliminar')) return;
    try {
      await _service.remove(d.id);
      if (!mounted) return;
      setState(() => _todos = _todos.where((x) => x.id != d.id).toList());
      showMsg(context, 'Registro eliminado');
    } on ApiException {
      if (mounted) showMsg(context, 'No se pudo eliminar', error: true);
    }
  }

  void _detalle(DanoCultivo d) => showDanoDetalle(context, d, onEliminar: widget.user.puedeEliminarRegistros ? _eliminar : null);

  List<({String nombre, int n, double pct, int share})> _agrupar(List<DanoCultivo> lista, String Function(DanoCultivo) key) {
    final total = lista.length;
    final cuenta = <String, int>{};
    for (final d in lista) {
      final k = key(d).isEmpty ? 'Sin dato' : key(d);
      cuenta[k] = (cuenta[k] ?? 0) + 1;
    }
    var filas = cuenta.entries.toList()..sort((a, b) => b.value.compareTo(a.value));
    if (filas.length > 7) {
      final resto = filas.skip(6).fold<int>(0, (a, e) => a + e.value);
      filas = [...filas.take(6), MapEntry('Otros', resto)];
    }
    final max = filas.isEmpty ? 1 : filas.first.value;
    return filas.map((e) => (nombre: e.key, n: e.value, pct: e.value / max * 100, share: (e.value / total * 100).round())).toList();
  }

  @override
  Widget build(BuildContext context) {
    final lista = _lista;
    final area = lista.fold<double>(0, (a, d) => a + d.areaAfectada);
    double areaFn(List<DanoCultivo> l) => l.fold<double>(0, (a, d) => a + d.areaAfectada);
    num prodFn(List<DanoCultivo> l) => l.map((d) => d.productor.trim().toLowerCase()).toSet().length;
    num totalFn(List<DanoCultivo> l) => l.where((d) => d.danoPorc >= 100).length;
    num cuentaFn(List<DanoCultivo> l) => l.length;

    String? foot(num Function(List<DanoCultivo>) fn) {
      final t = _tendencia(fn, lista);
      return t == null ? null : '${t >= 0 ? '↑' : '↓'} ${t.abs()}% vs. periodo anterior';
    }

    Color? fc(num Function(List<DanoCultivo>) fn) {
      final t = _tendencia(fn, lista);
      return t == null ? null : (t >= 0 ? const Color(0xFF008359) : AppColors.red);
    }

    final porCausa = _agrupar(lista, (d) => d.causa);
    final porCultivo = _agrupar(lista, (d) => d.cultivo);
    final visibles = _verTodos ? lista : lista.take(5).toList();

    return PageScroll(
      heroKey: 'dashboard',
      user: widget.user,
      onLogout: widget.onLogout,
      onRefresh: _cargar,
      children: [
        ContentHead(title: 'Panorama del campo', subtitle: 'Una mirada a los registros y las áreas afectadas.'),
        Row(children: [
          Expanded(child: _FechaChip(label: 'Desde', value: _desde, onTap: () => _pickFecha(true))),
          const SizedBox(width: 8),
          Expanded(child: _FechaChip(label: 'Hasta', value: _hasta, onTap: () => _pickFecha(false))),
          const SizedBox(width: 8),
          if (_desde != null || _hasta != null) ...[IconBtn(Icons.close, color: AppColors.navy, tooltip: 'Limpiar fechas', onPressed: () => setState(() => _desde = _hasta = null)), const SizedBox(width: 8)],
          IconBtn(Icons.refresh, tooltip: 'Actualizar', onPressed: _loading ? null : _cargar),
        ]),
        const SizedBox(height: 14),
        if (_error != null) ...[Panel(child: Text(_error!, textAlign: TextAlign.center, style: const TextStyle(color: AppColors.red))), const SizedBox(height: 14)],
        StatsGrid(cards: [
          StatCard(index: 0, title: 'Total de registros', value: '${lista.length}', icon: Icons.description_outlined, foot: foot(cuentaFn), footColor: fc(cuentaFn)),
          StatCard(index: 1, title: 'Área afectada', value: fmtNum(area), unit: 'ha', icon: Icons.layers_outlined, foot: foot(areaFn), footColor: fc(areaFn)),
          StatCard(index: 2, title: 'Productores reportados', value: '${prodFn(lista)}', icon: Icons.groups_outlined, foot: foot(prodFn), footColor: fc(prodFn)),
          StatCard(index: 3, title: 'Registros con pérdida total', value: '${totalFn(lista)}', icon: Icons.warning_amber_rounded, foot: foot(totalFn), footColor: fc(totalFn)),
        ]),
        const SizedBox(height: 16),
        _AreaPorMes(lista: lista),
        const SizedBox(height: 16),
        _NivelAfectacion(lista: lista),
        const SizedBox(height: 16),
        Panel(
          padding: const EdgeInsets.all(20),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const PanelHead(title: 'Principales causas', subtitle: 'Registros por tipo de daño', trailing: Icon(Icons.cloud_outlined, size: 20, color: AppColors.muted)),
            if (porCausa.isEmpty) const Text('Sin datos en este período.', style: TextStyle(fontSize: 12, color: AppColors.muted)),
            for (final r in porCausa.take(5))
              Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: Column(children: [
                  Row(children: [
                    Expanded(child: Text(r.nombre, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11))),
                    Text('${r.n} ', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
                    Text('· ${r.share}%', style: const TextStyle(fontSize: 11, color: AppColors.muted)),
                  ]),
                  const SizedBox(height: 8),
                  _Track(pct: r.pct),
                ]),
              ),
          ]),
        ),
        const SizedBox(height: 16),
        Panel(
          padding: const EdgeInsets.all(20),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const PanelHead(title: 'Cultivos afectados', subtitle: 'Registros por tipo de cultivo', trailing: Icon(Icons.eco_outlined, size: 20, color: AppColors.muted)),
            if (porCultivo.isEmpty) const Text('Sin cultivos reportados.', style: TextStyle(fontSize: 12, color: AppColors.muted)),
            for (final r in porCultivo.take(5))
              Container(
                padding: const EdgeInsets.symmetric(vertical: 10),
                decoration: const BoxDecoration(border: Border(bottom: BorderSide(color: Color(0xFFE0EAE4)))),
                child: Row(children: [
                  Container(width: 33, height: 33, decoration: BoxDecoration(color: const Color(0xFFE9F0DC), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.eco_outlined, size: 17, color: Color(0xFF668D32))),
                  const SizedBox(width: 10),
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(r.nombre, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                    Text('${r.n} registros', style: const TextStyle(fontSize: 9, color: AppColors.muted)),
                  ])),
                  Text('${r.share} %', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                ]),
              ),
          ]),
        ),
        const SizedBox(height: 16),
        _Cobertura(lista: lista),
        const SizedBox(height: 16),
        _Mapa(lista: lista, onTap: _detalle),
        const SizedBox(height: 16),
        Panel(
          padding: const EdgeInsets.all(20),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            PanelHead(
              title: _verTodos ? 'Todos los registros' : 'Últimos registros',
              subtitle: 'Lo más reciente del trabajo en campo',
              trailing: lista.length > 5 ? InkWell(onTap: () => setState(() => _verTodos = !_verTodos), child: Text(_verTodos ? 'Ver menos' : 'Ver todos →', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.green))) : null,
            ),
            if (lista.isEmpty) loadingOrEmpty(loading: _loading && _todos.isEmpty, emptyText: 'No hay registros para mostrar.'),
            for (final d in visibles) RegistroTile(d: d, onTap: () => _detalle(d)),
          ]),
        ),
      ],
    );
  }
}

class _FechaChip extends StatelessWidget {
  final String label;
  final DateTime? value;
  final VoidCallback onTap;
  const _FechaChip({required this.label, required this.value, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        height: 44,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(color: const Color(0xEBFFFFFF), borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.white)),
        child: Row(children: [
          const Icon(Icons.calendar_month_outlined, size: 18, color: AppColors.green),
          const SizedBox(width: 8),
          Expanded(child: Text(value == null ? label : DateFormat('dd/MM/yyyy').format(value!), style: TextStyle(fontSize: 12, color: value == null ? AppColors.muted : AppColors.navy))),
        ]),
      ),
    );
  }
}

class _Track extends StatelessWidget {
  final double pct;
  const _Track({required this.pct});
  @override
  Widget build(BuildContext context) => ClipRRect(
        borderRadius: BorderRadius.circular(10),
        child: Stack(children: [
          Container(height: 6, color: const Color(0xFFE3EEE8)),
          FractionallySizedBox(widthFactor: (pct / 100).clamp(0, 1), child: Container(height: 6, color: const Color(0xFF178C63))),
        ]),
      );
}

/// Tarjeta de registro (misma que "Últimos registros" de la versión móvil).
class RegistroTile extends StatelessWidget {
  final DanoCultivo d;
  final VoidCallback onTap;
  const RegistroTile({super.key, required this.d, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(17),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(color: const Color(0xFFFBFEF9), borderRadius: BorderRadius.circular(17), border: Border.all(color: const Color(0xFFD6E5DB))),
          child: Column(children: [
            Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Container(width: 27, height: 27, decoration: BoxDecoration(color: const Color(0xFFE7F3E9), borderRadius: BorderRadius.circular(8)), child: const Icon(Icons.eco_outlined, size: 16, color: AppColors.green)),
              const SizedBox(width: 10),
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(d.lote, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                const SizedBox(height: 4),
                Text('${d.cultivosTexto}${d.departamento.isNotEmpty ? ' · ${d.departamento}' : ''}', style: const TextStyle(fontSize: 10, color: AppColors.muted)),
              ])),
              AppBadge(d.nivel.label, kind: kindDeNivel(d.nivel.index)),
            ]),
            const SizedBox(height: 12),
            Row(children: [
              Expanded(child: Text('${fmtNum(d.areaAfectada)} ha · ${d.causaTexto}', style: const TextStyle(fontSize: 11), maxLines: 1, overflow: TextOverflow.ellipsis)),
              Text(fechaCorta(d.fecha), style: const TextStyle(fontSize: 10, color: AppColors.muted)),
            ]),
          ]),
        ),
      ),
    );
  }
}

class _AreaPorMes extends StatelessWidget {
  final List<DanoCultivo> lista;
  const _AreaPorMes({required this.lista});

  @override
  Widget build(BuildContext context) {
    final m = <String, double>{};
    for (final d in lista) {
      if (d.fecha.length < 7) continue;
      final k = d.fecha.substring(0, 7);
      m[k] = (m[k] ?? 0) + d.areaAfectada;
    }
    final keys = m.keys.toList()..sort();
    final meses = keys.length > 6 ? keys.sublist(keys.length - 6) : keys;
    final max = meses.isEmpty ? 1.0 : math.max(m.values.fold<double>(0, math.max), 0.0001);
    String corto(String k) => DateFormat('MMM', 'es').format(DateTime.parse('$k-15')).replaceAll('.', '');

    return Panel(
      padding: const EdgeInsets.all(20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const PanelHead(title: 'Área afectada en el tiempo', subtitle: 'Superficie reportada por mes · hectáreas', trailing: AppBadge('Seguimiento')),
        if (meses.isEmpty)
          const EmptyState('No hay registros en este período.')
        else
          SizedBox(
            height: 190,
            child: Row(crossAxisAlignment: CrossAxisAlignment.end, children: [
              SizedBox(
                width: 30,
                child: Column(mainAxisAlignment: MainAxisAlignment.spaceBetween, crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(fmtNum(max), style: const TextStyle(fontSize: 9, color: Color(0xFF8DA19D))),
                  Text(fmtNum(max / 2), style: const TextStyle(fontSize: 9, color: Color(0xFF8DA19D))),
                  const Text('0', style: TextStyle(fontSize: 9, color: Color(0xFF8DA19D))),
                  const SizedBox(height: 22),
                ]),
              ),
              Expanded(
                child: Row(crossAxisAlignment: CrossAxisAlignment.end, mainAxisAlignment: MainAxisAlignment.spaceAround, children: [
                  for (var i = 0; i < meses.length; i++)
                    Column(mainAxisAlignment: MainAxisAlignment.end, children: [
                      Text(fmtNum(m[meses[i]]), style: const TextStyle(fontSize: 10, color: Color(0xFF1C7052))),
                      const SizedBox(height: 3),
                      Container(
                        width: 34,
                        height: math.max(2, (m[meses[i]]! / max) * 128),
                        decoration: BoxDecoration(
                          borderRadius: const BorderRadius.vertical(top: Radius.circular(6)),
                          gradient: LinearGradient(
                            begin: Alignment.topCenter,
                            end: Alignment.bottomCenter,
                            colors: i == meses.length - 1 ? const [Color(0xFF048B61), Color(0xFF38B27C)] : const [Color(0xFF46B78D), Color(0xFFD4EADB)],
                          ),
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(corto(meses[i]), style: const TextStyle(fontSize: 10, color: Color(0xFF7B8F8B))),
                    ]),
                ]),
              ),
            ]),
          ),
        const SizedBox(height: 14),
        const Row(children: [
          CircleAvatar(radius: 3.5, backgroundColor: AppColors.green),
          SizedBox(width: 7),
          Text('Superficie de los registros seleccionados', style: TextStyle(fontSize: 10, color: Color(0xFF7B8F8B))),
        ]),
      ]),
    );
  }
}

class _NivelAfectacion extends StatelessWidget {
  final List<DanoCultivo> lista;
  const _NivelAfectacion({required this.lista});

  @override
  Widget build(BuildContext context) {
    final counts = [for (final n in Nivel.values) lista.where((d) => d.nivel == n).length];
    return Panel(
      padding: const EdgeInsets.all(20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const PanelHead(title: 'Nivel de afectación', subtitle: 'Distribución de los registros', trailing: Icon(Icons.layers_outlined, size: 20, color: AppColors.muted)),
        Row(children: [
          SizedBox(
            width: 135,
            height: 135,
            child: CustomPaint(
              painter: _DonutPainter(counts),
              child: Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
                Text('${lista.length}', style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w700)),
                const Text('registros', style: TextStyle(fontSize: 9, color: AppColors.muted)),
              ])),
            ),
          ),
          const SizedBox(width: 22),
          Expanded(
            child: Column(children: [
              for (final n in Nivel.values)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Row(children: [
                    CircleAvatar(radius: 3.5, backgroundColor: _nivelColor[n.index]),
                    const SizedBox(width: 8),
                    Expanded(child: Text(n.label, style: const TextStyle(fontSize: 11))),
                    Text('${counts[n.index]}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
                  ]),
                ),
            ]),
          ),
        ]),
      ]),
    );
  }
}

class _DonutPainter extends CustomPainter {
  final List<int> counts;
  _DonutPainter(this.counts);

  @override
  void paint(Canvas canvas, Size size) {
    final total = counts.fold<int>(0, (a, b) => a + b);
    final rect = Rect.fromLTWH(9, 9, size.width - 18, size.height - 18);
    final p = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 18
      ..strokeCap = StrokeCap.butt;
    if (total == 0) {
      canvas.drawArc(rect, 0, math.pi * 2, false, p..color = const Color(0xFFDCE8DF));
      return;
    }
    var start = -math.pi / 2;
    for (var i = 0; i < counts.length; i++) {
      if (counts[i] == 0) continue;
      final sweep = counts[i] / total * math.pi * 2;
      canvas.drawArc(rect, start, sweep, false, p..color = _nivelColor[i]);
      start += sweep;
    }
  }

  @override
  bool shouldRepaint(covariant _DonutPainter old) => old.counts.toString() != counts.toString();
}

class _Cobertura extends StatelessWidget {
  final List<DanoCultivo> lista;
  const _Cobertura({required this.lista});

  @override
  Widget build(BuildContext context) {
    final m = <String, ({int n, double ha})>{};
    for (final d in lista) {
      if (d.departamento.isEmpty) continue;
      final c = m[d.departamento] ?? (n: 0, ha: 0.0);
      m[d.departamento] = (n: c.n + 1, ha: c.ha + d.areaAfectada);
    }
    final filas = m.entries.toList()..sort((a, b) => b.value.n.compareTo(a.value.n));
    final top = filas.take(4).toList();
    return Panel(
      padding: const EdgeInsets.all(20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const PanelHead(title: 'Cobertura territorial', subtitle: 'Departamentos con reportes', trailing: Icon(Icons.place_outlined, size: 20, color: AppColors.muted)),
        if (top.isEmpty) const Text('Sin departamentos reportados.', style: TextStyle(fontSize: 12, color: AppColors.muted)),
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 9,
          crossAxisSpacing: 9,
          childAspectRatio: 1.55,
          children: [
            for (final e in top)
              Container(
                padding: const EdgeInsets.all(13),
                decoration: BoxDecoration(color: const Color(0xFFEFF6EE), borderRadius: BorderRadius.circular(11), border: Border.all(color: const Color(0xFFDBE9DF))),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [
                  const Icon(Icons.place_outlined, size: 16, color: Color(0xFF279365)),
                  const SizedBox(height: 6),
                  Text(e.key, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700), maxLines: 1, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 5),
                  Text('${e.value.n} reportes · ${fmtNum(e.value.ha)} ha', style: const TextStyle(fontSize: 10, color: Color(0xFF5F7F6E))),
                ]),
              ),
          ],
        ),
      ]),
    );
  }
}

class _Mapa extends StatelessWidget {
  final List<DanoCultivo> lista;
  final void Function(DanoCultivo) onTap;
  const _Mapa({required this.lista, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final con = lista.where((d) => d.gpsLat != null && d.gpsLon != null).toList();
    final puntos = [for (final d in con) LatLng(d.gpsLat!, d.gpsLon!)];
    return Panel(
      padding: const EdgeInsets.all(20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        PanelHead(title: 'Ubicación de registros', subtitle: 'Registros con GPS · ${con.length} de ${lista.length}', trailing: const Icon(Icons.place_outlined, size: 20, color: AppColors.muted)),
        ClipRRect(
          borderRadius: BorderRadius.circular(16),
          child: SizedBox(
            height: 300,
            child: FlutterMap(
              key: ValueKey(puntos.length),
              options: MapOptions(
                initialCenter: const LatLng(15.6, -90.4),
                initialZoom: 7,
                initialCameraFit: puntos.length >= 2 ? CameraFit.coordinates(coordinates: puntos, padding: const EdgeInsets.all(40), maxZoom: 13) : null,
                interactionOptions: const InteractionOptions(flags: InteractiveFlag.pinchZoom | InteractiveFlag.drag | InteractiveFlag.doubleTapZoom),
              ),
              children: [
                TileLayer(urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', userAgentPackageName: 'gt.nubix.danosacultivos'),
                MarkerLayer(markers: [
                  for (final d in con)
                    Marker(
                      point: LatLng(d.gpsLat!, d.gpsLon!),
                      width: 26,
                      height: 26,
                      child: GestureDetector(
                        onTap: () => onTap(d),
                        child: Container(decoration: BoxDecoration(color: _nivelColor[d.nivel.index], shape: BoxShape.circle, border: Border.all(color: Colors.white, width: 2))),
                      ),
                    ),
                ]),
                const RichAttributionWidget(attributions: [TextSourceAttribution('© OpenStreetMap')]),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        Wrap(spacing: 14, runSpacing: 6, children: [
          for (final n in Nivel.values)
            Row(mainAxisSize: MainAxisSize.min, children: [
              CircleAvatar(radius: 5, backgroundColor: _nivelColor[n.index]),
              const SizedBox(width: 6),
              Text(n.label, style: const TextStyle(fontSize: 11)),
            ]),
        ]),
      ]),
    );
  }
}
