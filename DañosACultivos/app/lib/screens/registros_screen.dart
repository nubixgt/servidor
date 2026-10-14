import 'dart:io';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:path_provider/path_provider.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';

import '../models/dano_cultivo.dart';
import '../models/usuario.dart';
import '../services/api_client.dart';
import '../services/dano_service.dart';
import '../services/offline_queue.dart';
import '../theme/app_theme.dart';
import '../widgets/app_shell.dart';
import '../widgets/common.dart';
import '../widgets/dano_detalle.dart';

/// Registros guardados: el técnico ve los suyos; admin y supervisor ven todos (lo filtra el backend).
class RegistrosScreen extends StatefulWidget {
  final AuthUser user;
  final VoidCallback onLogout;
  final VoidCallback onCrear;
  final bool active;
  const RegistrosScreen({super.key, required this.user, required this.onLogout, required this.onCrear, required this.active});

  @override
  State<RegistrosScreen> createState() => _RegistrosScreenState();
}

class _RegistrosScreenState extends State<RegistrosScreen> {
  final _service = DanoService();
  List<DanoCultivo> _registros = [];
  bool _loading = true;
  String _busqueda = '';
  String _cultivo = '';
  String _nivel = '';
  bool _tabla = false;
  late final OfflineQueue _queue = OfflineQueue.instance;
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

  // Cuando se envía un pendiente, el listado se actualiza solo.
  void _onQueue() {
    if (_queue.exitos != _exitos) {
      _exitos = _queue.exitos;
      _cargar();
    }
  }

  @override
  void didUpdateWidget(covariant RegistrosScreen old) {
    super.didUpdateWidget(old);
    if (widget.active && !old.active) _cargar();
  }

  Future<void> _cargar() async {
    try {
      final l = await _service.list();
      if (mounted) setState(() => _registros = l);
    } on ApiException catch (e) {
      if (mounted) showMsg(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  List<DanoCultivo> get _filtrados {
    final q = _busqueda.trim().toLowerCase();
    return _registros.where((r) {
      final texto = [r.lote, r.productor, r.registradoPor, r.municipio, r.departamento, r.cultivo, r.causa].join(' ').toLowerCase();
      return (q.isEmpty || texto.contains(q)) && (_cultivo.isEmpty || r.cultivo == _cultivo) && (_nivel.isEmpty || r.nivel.label == _nivel);
    }).toList();
  }

  Future<void> _eliminar(DanoCultivo r) async {
    if (!await confirmDialog(context, 'Este registro se eliminará.', title: '¿Eliminar registro?', danger: true, confirmText: 'Eliminar')) return;
    try {
      await _service.remove(r.id);
      await _cargar();
    } on ApiException catch (e) {
      if (mounted) showMsg(context, e.message, error: true);
    }
  }

  Future<void> _exportarCSV() async {
    final l = _filtrados;
    if (l.isEmpty) return showMsg(context, 'No hay registros para exportar.', error: true);
    String c(Object? v) => '"${(v ?? '').toString().replaceAll('"', '""')}"';
    final filas = <List<Object?>>[
      ['REPORTE DE DAÑOS A CULTIVOS'],
      ['Generado', DateFormat('dd/MM/yyyy HH:mm').format(DateTime.now())],
      [],
      ['N°', 'Fecha', 'Registrado por', 'Productor', 'Departamento', 'Municipio', 'Centro poblado', 'Agricultor en RUA', 'Código RUA', 'Lote/Parcela', 'Cultivo', 'Etapa fenológica',
        'Cultivos asociados (cultivo | etapa | sup. lote | sup. afectada)', 'Causa del daño', 'Sup. total lote (ha)', 'Sup. afectada (ha)', '% daño', 'Rend. esperado', 'Unidad rend.',
        'Pérdida estimada', 'Latitud, Longitud (precisión m)', 'N° fotos', 'Nombres de fotos', 'Notas'],
    ];
    for (var i = 0; i < l.length; i++) {
      final r = l[i];
      filas.add([
        i + 1, r.fecha, r.registradoPor, r.productor, r.departamento, r.municipio, r.centroPoblado, r.agricultorRua ? 'Sí' : 'No', r.codigoRua, r.lote, r.cultivo, r.etapa,
        r.asociados.map((a) => [a.cultivo, a.etapa, a.areaLote ?? '', a.areaAfectada].join(' | ')).join(' ;; '), r.causaTexto, r.areaLote ?? '', r.areaAfectada, r.danoPorc,
        r.rendEsperado ?? '', r.unidad, r.perdidaEstimada ?? '', r.gps, r.fotos.length, r.fotos.map((f) => f.nombre).join(' ; '), r.notas,
      ]);
    }
    final totalHa = l.fold<double>(0, (a, r) => a + r.areaAfectada);
    final totalPerdida = l.fold<double>(0, (a, r) => a + (r.perdidaEstimada ?? 0));
    filas.add([]);
    filas.add(['', '', '', '', '', '', '', '', '', '', '', '', '', 'TOTALES', '', totalHa.toStringAsFixed(2), '', '', '', totalPerdida.toStringAsFixed(2)]);

    final csv = '﻿${filas.map((f) => f.map(c).join(';')).join('\r\n')}';
    try {
      final dir = await getTemporaryDirectory();
      final file = File('${dir.path}/danos_cultivos_${DateFormat('yyyy-MM-dd').format(DateTime.now())}.csv');
      await file.writeAsString(csv);
      await SharePlus.instance.share(ShareParams(files: [XFile(file.path, mimeType: 'text/csv')], subject: 'Reporte de daños a cultivos'));
    } catch (_) {
      if (mounted) showMsg(context, 'No se pudo exportar el archivo.', error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = _registros;
    final filtrados = _filtrados;
    final totalHa = l.fold<double>(0, (a, r) => a + r.areaAfectada);
    final totalPerdida = l.fold<double>(0, (a, r) => a + (r.perdidaEstimada ?? 0));
    final cultivos = l.map((r) => r.cultivo).toSet().toList()..sort();
    final deptos = l.map((r) => r.departamento).where((e) => e.isNotEmpty).toSet().length;
    final puedeEliminar = widget.user.puedeEliminarRegistros;
    final queue = context.watch<OfflineQueue>();

    return PageScroll(
      heroKey: 'registros',
      user: widget.user,
      onLogout: widget.onLogout,
      onRefresh: _cargar,
      children: [
        ContentHead(
          title: 'Registros de campo',
          subtitle: widget.user.esTecnico ? 'Los registros que has realizado.' : 'Consulta cada afectación reportada por los técnicos.',
          action: AppButton('Crear', primary: true, icon: Icons.add, onPressed: widget.onCrear),
        ),
        StatsGrid(cards: [
          StatCard(index: 0, title: 'Registros', value: '${l.length}', icon: Icons.description_outlined, foot: '$deptos depto(s). con reportes', footColor: const Color(0xFF008359)),
          StatCard(index: 1, title: 'Superficie afectada', value: fmtNum(totalHa), unit: 'ha', icon: Icons.layers_outlined, foot: 'Área acumulada reportada'),
          StatCard(index: 2, title: 'Productores', value: '${l.map((r) => r.productor.trim().toLowerCase()).toSet().length}', icon: Icons.groups_outlined, foot: 'Personas en los registros'),
          StatCard(index: 3, title: 'Afectación alta o total', value: '${l.where((r) => r.danoPorc >= 50).length}', icon: Icons.warning_amber_rounded, foot: 'Más del 50% de daño reportado'),
        ]),
        const SizedBox(height: 16),
        if (queue.items.isNotEmpty) ...[_Pendientes(queue: queue), const SizedBox(height: 16)],
        Panel(
          padding: const EdgeInsets.all(20),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            PanelHead(
              title: 'Bitácora de afectaciones',
              subtitle: 'Encuentra un registro y consulta toda su información · Pérdida estimada acumulada ${totalPerdida.toStringAsFixed(2)}',
            ),
            Align(alignment: Alignment.centerLeft, child: AppButton('Exportar CSV', icon: Icons.file_download_outlined, onPressed: _exportarCSV)),
            const SizedBox(height: 18),
            TextField(
              onChanged: (v) => setState(() => _busqueda = v),
              decoration: const InputDecoration(hintText: 'Buscar lote, productor o municipio…', prefixIcon: Icon(Icons.search, size: 20, color: Color(0xFF81978D))),
            ),
            const SizedBox(height: 10),
            Row(children: [
              Expanded(child: _Filtro(value: _cultivo, hint: 'Todos los cultivos', options: cultivos, onChanged: (v) => setState(() => _cultivo = v))),
              const SizedBox(width: 8),
              Expanded(child: _Filtro(value: _nivel, hint: 'Todos los niveles', options: Nivel.values.map((n) => n.label).toList(), onChanged: (v) => setState(() => _nivel = v))),
              const SizedBox(width: 8),
              _VistaToggle(tabla: _tabla, onChanged: (v) => setState(() => _tabla = v)),
            ]),
            const SizedBox(height: 18),
            if (_loading)
              loadingOrEmpty(loading: true, emptyText: '')
            else if (filtrados.isEmpty)
              EmptyState(l.isEmpty ? 'Aún no hay registros guardados.' : 'No se encontraron registros con estos filtros.')
            else if (_tabla)
              _Tabla(lista: filtrados, onTap: (r) => showDanoDetalle(context, r, onEliminar: puedeEliminar ? _eliminar : null))
            else
              for (final r in filtrados) _Tarjeta(r: r, onTap: () => showDanoDetalle(context, r, onEliminar: puedeEliminar ? _eliminar : null)),
            const SizedBox(height: 6),
            Row(children: [
              Expanded(child: Text('${filtrados.length} de ${l.length} registro(s)', style: const TextStyle(fontSize: 11, color: AppColors.muted))),
              InkWell(
                onTap: () => setState(() {
                  _busqueda = '';
                  _cultivo = '';
                  _nivel = '';
                }),
                child: const Text('Restablecer filtros', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.green)),
              ),
            ]),
          ]),
        ),
      ],
    );
  }
}

class _Filtro extends StatelessWidget {
  final String value;
  final String hint;
  final List<String> options;
  final ValueChanged<String> onChanged;
  const _Filtro({required this.value, required this.hint, required this.options, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return DropdownButtonFormField<String>(
      key: ValueKey('$value|${options.length}'),
      initialValue: options.contains(value) ? value : '',
      isExpanded: true,
      decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 12)),
      items: [
        DropdownMenuItem(value: '', child: Text(hint, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12))),
        for (final o in options) DropdownMenuItem(value: o, child: Text(o, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12))),
      ],
      onChanged: (v) => onChanged(v ?? ''),
    );
  }
}

class _VistaToggle extends StatelessWidget {
  final bool tabla;
  final ValueChanged<bool> onChanged;
  const _VistaToggle({required this.tabla, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    Widget b(IconData icon, bool sel, VoidCallback f, String tip) => InkWell(
          onTap: f,
          borderRadius: BorderRadius.circular(8),
          child: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(color: sel ? Colors.white : null, borderRadius: BorderRadius.circular(8), boxShadow: sel ? const [BoxShadow(color: Color(0x12123A20), blurRadius: 5, offset: Offset(0, 2))] : null),
            child: Tooltip(message: tip, child: Icon(icon, size: 20, color: sel ? const Color(0xFF008660) : const Color(0xFF7B9387))),
          ),
        );
    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(color: const Color(0xFFE9F1EC), borderRadius: BorderRadius.circular(11)),
      child: Row(children: [b(Icons.table_rows_outlined, tabla, () => onChanged(true), 'Tabla'), b(Icons.grid_view_rounded, !tabla, () => onChanged(false), 'Tarjetas')]),
    );
  }
}

class _Tarjeta extends StatelessWidget {
  final DanoCultivo r;
  final VoidCallback onTap;
  const _Tarjeta({required this.r, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(17),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(17),
          child: Container(
            decoration: BoxDecoration(color: const Color(0xFFFBFEF9), border: Border.all(color: const Color(0xFFD6E5DB)), borderRadius: BorderRadius.circular(17)),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              SizedBox(
                height: 66,
                child: Stack(fit: StackFit.expand, children: [
                  Image.asset('assets/images/fondo.jpg', fit: BoxFit.cover, alignment: const Alignment(0, .4)),
                  Container(color: const Color(0x7303272C)),
                  Padding(
                    padding: const EdgeInsets.all(15),
                    child: Row(children: [
                      const Icon(Icons.eco_outlined, size: 16, color: Colors.white),
                      const SizedBox(width: 8),
                      Expanded(child: Text(r.cultivosTexto, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.white))),
                      AppBadge(r.nivel.label, kind: kindDeNivel(r.nivel.index)),
                    ]),
                  ),
                ]),
              ),
              Padding(
                padding: const EdgeInsets.all(17),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(r.ubicacion.isEmpty ? '—' : r.ubicacion, style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                  const SizedBox(height: 6),
                  Text(r.lote, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 6),
                  Text('${r.productor}${r.registradoPor.isNotEmpty ? ' · registrado por ${r.registradoPor}' : ''}', style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                  const SizedBox(height: 16),
                  Row(children: [
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text('${fmtNum(r.areaAfectada)} ha', style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w700)),
                      const Text('Área afectada', style: TextStyle(fontSize: 10, color: AppColors.muted)),
                    ])),
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                      Text('${fmtNum(r.danoPorc)}%', style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w700)),
                      Text(r.causaTexto, textAlign: TextAlign.right, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 10, color: AppColors.muted)),
                    ])),
                  ]),
                  const Divider(height: 26, color: Color(0xFFE2EAE1)),
                  Row(children: [
                    Expanded(child: Text(fechaCorta(r.fecha), style: const TextStyle(fontSize: 11, color: AppColors.muted))),
                    const Text('Ver detalle →', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.green)),
                  ]),
                ]),
              ),
            ]),
          ),
        ),
      ),
    );
  }
}

class _Tabla extends StatelessWidget {
  final List<DanoCultivo> lista;
  final void Function(DanoCultivo) onTap;
  const _Tabla({required this.lista, required this.onTap});

  @override
  Widget build(BuildContext context) {
    const h = TextStyle(fontSize: 9, letterSpacing: .5, color: Color(0xFF7A928B), fontWeight: FontWeight.w600);
    const t = TextStyle(fontSize: 12);
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
        showCheckboxColumn: false,
        headingRowColor: const WidgetStatePropertyAll(Color(0xFFF0F6F2)),
        headingRowHeight: 40,
        dataRowMinHeight: 54,
        dataRowMaxHeight: 60,
        columnSpacing: 22,
        columns: [for (final c in ['FECHA', 'TÉCNICO', 'PRODUCTOR', 'UBICACIÓN', 'RUA', 'LOTE', 'CULTIVO', 'CAUSA', 'HA AFECT.', 'AFECTACIÓN', 'PÉRDIDA EST.', 'GPS', 'FOTOS']) DataColumn(label: Text(c, style: h))],
        rows: [
          for (final r in lista)
            DataRow(onSelectChanged: (_) => onTap(r), cells: [
              DataCell(Text(r.fecha, style: t)),
              DataCell(Text(r.registradoPor.isEmpty ? '—' : r.registradoPor, style: t)),
              DataCell(Text(r.productor, style: t)),
              DataCell(Text(r.ubicacion.isEmpty ? '—' : r.ubicacion, style: t)),
              DataCell(Text(r.agricultorRua ? 'Sí (${r.codigoRua.isEmpty ? '—' : r.codigoRua})' : 'No', style: t)),
              DataCell(Text(r.lote, style: t)),
              DataCell(Text(r.cultivosTexto, style: t)),
              DataCell(Text(r.causaTexto, style: t)),
              DataCell(Text(fmtNum(r.areaAfectada), style: t.copyWith(fontWeight: FontWeight.w700))),
              DataCell(Column(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.start, children: [
                AppBadge(r.nivel.label, kind: kindDeNivel(r.nivel.index)),
                Text('${fmtNum(r.danoPorc)}% de daño', style: const TextStyle(fontSize: 10, color: AppColors.muted)),
              ])),
              DataCell(Text(r.perdidaEstimada != null ? '${fmtNum(r.perdidaEstimada)} ${r.unidad}' : '—', style: t)),
              DataCell(Text(r.gps.isEmpty ? '—' : r.gps, style: const TextStyle(fontSize: 11, fontFamily: 'monospace'))),
              DataCell(Text(r.fotos.isEmpty ? '—' : '${r.fotos.length}', style: t)),
            ]),
        ],
      ),
    );
  }
}

/// Registros guardados en el teléfono que aún no se envían al servidor.
class _Pendientes extends StatelessWidget {
  final OfflineQueue queue;
  const _Pendientes({required this.queue});

  @override
  Widget build(BuildContext context) {
    final items = queue.items;
    return Panel(
      padding: const EdgeInsets.all(20),
      color: const Color(0xFFFFF8E8),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Container(width: 36, height: 36, decoration: BoxDecoration(color: const Color(0xFFFFEBC4), borderRadius: BorderRadius.circular(11)), child: const Icon(Icons.cloud_off_outlined, size: 19, color: Color(0xFFAA6B17))),
          const SizedBox(width: 12),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('${items.length} registro(s) pendiente(s) de envío', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
            const SizedBox(height: 3),
            const Text('Se enviarán solos cuando tengas internet.', style: TextStyle(fontSize: 11, color: AppColors.muted)),
          ])),
        ]),
        const SizedBox(height: 14),
        for (final p in items)
          Container(
            margin: const EdgeInsets.only(bottom: 10),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: const Color(0xFFF0DFB6))),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                Expanded(child: Text('${p.lote.isEmpty ? 'Registro' : p.lote} · ${p.cultivo}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700), maxLines: 1, overflow: TextOverflow.ellipsis)),
                AppBadge(p.error != null ? 'Con error' : (p.danoId != null ? 'Faltan fotos' : 'Pendiente'), kind: p.error != null ? BadgeKind.red : BadgeKind.amber),
              ]),
              const SizedBox(height: 6),
              Text('${p.fecha} · ${p.fotos.length} foto(s)', style: const TextStyle(fontSize: 10, color: AppColors.muted)),
              if (p.error != null) Padding(padding: const EdgeInsets.only(top: 6), child: Text(p.error!, style: const TextStyle(fontSize: 11, color: Color(0xFFBD4E3C)))),
              const SizedBox(height: 10),
              Row(mainAxisAlignment: MainAxisAlignment.end, children: [
                AppButton('Eliminar', icon: Icons.delete_outline, danger: true, onPressed: () async {
                  if (await confirmDialog(context, 'Este registro no se ha enviado y se perderá.', title: '¿Eliminar pendiente?', danger: true, confirmText: 'Eliminar')) queue.eliminar(p);
                }),
                if (p.error != null) ...[const SizedBox(width: 8), AppButton('Reintentar', icon: Icons.refresh, onPressed: () => queue.reintentar(p))],
              ]),
            ]),
          ),
        AppButton(queue.sincronizando ? 'Enviando…' : 'Enviar ahora', primary: true, icon: Icons.cloud_upload_outlined, expand: true, loading: queue.sincronizando, onPressed: () async {
          if (!await OfflineQueue.hayRed()) {
            if (context.mounted) showMsg(context, 'Aún no hay conexión a internet.', error: true);
            return;
          }
          await queue.sincronizar();
        }),
      ]),
    );
  }
}
