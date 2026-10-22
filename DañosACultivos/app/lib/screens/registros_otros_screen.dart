import 'dart:io';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:path_provider/path_provider.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';

import '../models/dano_otros.dart';
import '../models/usuario.dart';
import '../services/api_client.dart';
import '../services/dano_otros_service.dart';
import '../services/offline_queue.dart';
import '../theme/app_theme.dart';
import '../widgets/app_shell.dart';
import '../widgets/common.dart';

/// Listado de Pecuario o Infraestructura. Mismo comportamiento que Registros de cultivos:
/// el técnico ve los suyos; admin y supervisor ven todos (lo filtra el backend).
class RegistrosModuloScreen<T extends DanoModulo> extends StatefulWidget {
  final AuthUser user;
  final VoidCallback onLogout;
  final bool active;
  final Widget selector;
  final DanoModuloService<T> service;
  final String titulo;
  final String subtitulo;
  final String tipoCola;
  final String etiquetaTitulo; // encabezado de la columna "especie / tipo"
  final List<(String, String, bool)> Function(T) detalle;
  final List<Object?> Function(T) filaCsv;
  final List<String> cabeceraCsv;
  final String archivoCsv;
  const RegistrosModuloScreen({
    super.key,
    required this.user,
    required this.onLogout,
    required this.active,
    required this.selector,
    required this.service,
    required this.titulo,
    required this.subtitulo,
    required this.tipoCola,
    required this.etiquetaTitulo,
    required this.detalle,
    required this.filaCsv,
    required this.cabeceraCsv,
    required this.archivoCsv,
  });

  @override
  State<RegistrosModuloScreen<T>> createState() => _RegistrosModuloState<T>();
}

class _RegistrosModuloState<T extends DanoModulo> extends State<RegistrosModuloScreen<T>> {
  List<T> _registros = [];
  bool _loading = true;
  String _busqueda = '';
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
  void didUpdateWidget(covariant RegistrosModuloScreen<T> old) {
    super.didUpdateWidget(old);
    if (widget.active && !old.active) _cargar();
  }

  Future<void> _cargar() async {
    try {
      final l = await widget.service.list();
      if (mounted) setState(() => _registros = l);
    } on ApiException catch (e) {
      if (mounted) showMsg(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  List<T> get _filtrados {
    final q = _busqueda.trim().toLowerCase();
    return _registros.where((r) => q.isEmpty || [r.titulo, r.causaTexto, r.municipio, r.departamento, r.centroPoblado].join(' ').toLowerCase().contains(q)).toList();
  }

  Future<void> _eliminar(T r) async {
    if (!await confirmDialog(context, 'Este registro se eliminará.', title: '¿Eliminar registro?', danger: true, confirmText: 'Eliminar')) return;
    try {
      await widget.service.remove(r.id);
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
      [widget.titulo.toUpperCase()],
      ['Generado', DateFormat('dd/MM/yyyy HH:mm').format(DateTime.now())],
      [],
      widget.cabeceraCsv,
      for (var i = 0; i < l.length; i++) [i + 1, ...widget.filaCsv(l[i])],
    ];
    final csv = '﻿${filas.map((f) => f.map(c).join(';')).join('\r\n')}';
    try {
      final dir = await getTemporaryDirectory();
      final file = File('${dir.path}/${widget.archivoCsv}_${DateFormat('yyyy-MM-dd').format(DateTime.now())}.csv');
      await file.writeAsString(csv);
      await SharePlus.instance.share(ShareParams(files: [XFile(file.path, mimeType: 'text/csv')], subject: widget.titulo));
    } catch (_) {
      if (mounted) showMsg(context, 'No se pudo exportar el archivo.', error: true);
    }
  }

  void _verDetalle(T r) {
    final puedeEliminar = widget.user.puedeEliminarRegistros;
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (c) => Container(
        constraints: BoxConstraints(maxHeight: MediaQuery.of(c).size.height * .88),
        decoration: const BoxDecoration(color: Color(0xFFFAFDF9), borderRadius: BorderRadius.vertical(top: Radius.circular(23))),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(22, 20, 16, 16),
            child: Row(children: [
              Expanded(child: Text(r.titulo, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700))),
              if (puedeEliminar)
                IconBtn(Icons.delete_outline, color: AppColors.red, tooltip: 'Eliminar', onPressed: () async {
                  Navigator.pop(c);
                  await _eliminar(r);
                }),
              const SizedBox(width: 8),
              IconBtn(Icons.close, color: AppColors.navy, tooltip: 'Cerrar', onPressed: () => Navigator.pop(c)),
            ]),
          ),
          const Divider(height: 1, color: AppColors.line),
          Flexible(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(22),
              child: Wrap(runSpacing: 16, children: [
                for (final f in widget.detalle(r))
                  FractionallySizedBox(
                    widthFactor: f.$3 ? 1 : .5,
                    child: Padding(
                      padding: const EdgeInsets.only(right: 10),
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text(f.$1, style: const TextStyle(fontSize: 10, color: Color(0xFF809689))),
                        const SizedBox(height: 4),
                        Text(f.$2, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                      ]),
                    ),
                  ),
              ]),
            ),
          ),
        ]),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l = _registros;
    final filtrados = _filtrados;
    final queue = context.watch<OfflineQueue>();
    final pendientes = queue.items.where((p) => p.tipo == widget.tipoCola).length;
    final fam = filtrados.fold<int>(0, (a, r) => a + (r.famAfectadas ?? 0));
    final perdida = filtrados.fold<double>(0, (a, r) => a + (r.perdidaEconomica ?? 0));

    return PageScroll(
      heroKey: 'registros',
      user: widget.user,
      onLogout: widget.onLogout,
      onRefresh: _cargar,
      children: [
        ContentHead(title: widget.titulo, subtitle: widget.subtitulo),
        widget.selector,
        StatsGrid(cards: [
          StatCard(index: 0, title: 'Registros', value: '${filtrados.length}', icon: Icons.description_outlined, foot: '${filtrados.map((r) => r.departamento).toSet().length} depto(s). con reportes', footColor: const Color(0xFF008359)),
          StatCard(index: 1, title: 'Familias afectadas', value: fmtNum(fam, 0), icon: Icons.groups_outlined, foot: 'Suma de los registros filtrados'),
          StatCard(index: 2, title: 'Pérdida económica', value: 'Q ${fmtNum(perdida)}', icon: Icons.payments_outlined, foot: 'Suma de los registros filtrados'),
          StatCard(index: 3, title: 'Pendientes de envío', value: '$pendientes', icon: Icons.cloud_off_outlined, foot: pendientes > 0 ? 'Se enviarán con internet' : 'Todo enviado'),
        ]),
        const SizedBox(height: 16),
        Panel(
          padding: const EdgeInsets.all(20),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            PanelHead(title: 'Bitácora', subtitle: 'Toca un registro para ver toda su información.'),
            Align(alignment: Alignment.centerLeft, child: AppButton('Exportar CSV', icon: Icons.file_download_outlined, onPressed: _exportarCSV)),
            const SizedBox(height: 18),
            TextField(
              onChanged: (v) => setState(() => _busqueda = v),
              decoration: InputDecoration(hintText: 'Buscar ${widget.etiquetaTitulo.toLowerCase()}, causa o municipio…', prefixIcon: const Icon(Icons.search, size: 20, color: Color(0xFF81978D))),
            ),
            const SizedBox(height: 18),
            if (_loading)
              loadingOrEmpty(loading: true, emptyText: '')
            else if (filtrados.isEmpty)
              EmptyState(l.isEmpty ? 'Aún no hay registros guardados.' : 'No se encontraron registros con este filtro.')
            else
              for (final r in filtrados) _Tarjeta<T>(r: r, onTap: () => _verDetalle(r)),
            Text('${filtrados.length} de ${l.length} registro(s)', style: const TextStyle(fontSize: 11, color: AppColors.muted)),
          ]),
        ),
      ],
    );
  }
}

class _Tarjeta<T extends DanoModulo> extends StatelessWidget {
  final T r;
  final VoidCallback onTap;
  const _Tarjeta({required this.r, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(17),
        child: Container(
          padding: const EdgeInsets.all(17),
          decoration: BoxDecoration(color: const Color(0xFFFBFEF9), border: Border.all(color: const Color(0xFFD6E5DB)), borderRadius: BorderRadius.circular(17)),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Expanded(child: Text(r.titulo, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700))),
              if (r.semana != null) AppBadge('Semana ${r.semana}', kind: BadgeKind.blue),
            ]),
            const SizedBox(height: 6),
            Text(r.ubicacion.isEmpty ? '—' : r.ubicacion, style: const TextStyle(fontSize: 12, color: AppColors.muted)),
            const SizedBox(height: 12),
            Row(children: [
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('${r.famAfectadas ?? '—'}', style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w700)),
                const Text('Familias afectadas', style: TextStyle(fontSize: 10, color: AppColors.muted)),
              ])),
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                Text(r.perdidaEconomica != null ? 'Q ${fmtNum(r.perdidaEconomica)}' : '—', style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w700)),
                Text(r.causaTexto, textAlign: TextAlign.right, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 10, color: AppColors.muted)),
              ])),
            ]),
            const Divider(height: 24, color: Color(0xFFE2EAE1)),
            Row(children: [
              Expanded(child: Text(fechaCorta(r.fechaInforme), style: const TextStyle(fontSize: 11, color: AppColors.muted))),
              const Text('Ver detalle →', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.green)),
            ]),
          ]),
        ),
      ),
    );
  }
}
