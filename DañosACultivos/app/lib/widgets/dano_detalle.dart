import 'package:flutter/material.dart';

import '../config/api_config.dart';
import '../models/dano_cultivo.dart';
import '../theme/app_theme.dart';
import 'common.dart';

/// Detalle de un registro (equivale al modal de detalle de la web).
Future<void> showDanoDetalle(BuildContext context, DanoCultivo d, {Future<void> Function(DanoCultivo)? onEliminar}) {
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (c) => _Detalle(d: d, onEliminar: onEliminar),
  );
}

class _Detalle extends StatelessWidget {
  final DanoCultivo d;
  final Future<void> Function(DanoCultivo)? onEliminar;
  const _Detalle({required this.d, this.onEliminar});

  List<(String, String, bool)> get filas => [
        ('Fecha', d.fecha, false),
        ('Productor', d.productor, false),
        ('Ubicación', d.ubicacion.isEmpty ? '—' : d.ubicacion, true),
        ('Agricultor en RUA', d.agricultorRua ? 'Sí (${d.codigoRua.isEmpty ? '—' : d.codigoRua})' : 'No', false),
        ('Lote', d.lote, false),
        ('Cultivo', d.cultivo, false),
        ('Etapa', d.etapa.isEmpty ? '—' : d.etapa, false),
        for (var i = 0; i < d.asociados.length; i++)
          (
            'Cultivo asociado ${i + 1}',
            '${d.asociados[i].cultivo}${d.asociados[i].etapa.isNotEmpty ? ' · ${d.asociados[i].etapa}' : ''} · ${fmtNum(d.asociados[i].areaAfectada)} ha afectadas',
            true
          ),
        ('Causa', d.causaTexto, false),
        ('Daño', '${fmtNum(d.danoPorc)}% · ${d.nivel.label}', false),
        ('Área del lote', d.areaLote != null ? '${fmtNum(d.areaLote)} ha' : '—', false),
        ('Área afectada', '${fmtNum(d.areaAfectada)} ha', false),
        ('Rendimiento esperado', d.rendEsperado != null ? '${fmtNum(d.rendEsperado)} ${d.unidad}' : '—', false),
        ('Pérdida estimada', d.perdidaEstimada != null ? fmtNum(d.perdidaEstimada) : '—', false),
        ('Registrado por', d.registradoPor.isEmpty ? '—' : d.registradoPor, false),
        ('GPS', d.gps.isEmpty ? '—' : d.gps, true),
        ('Notas', d.notas.isEmpty ? '—' : d.notas, true),
      ];

  @override
  Widget build(BuildContext context) {
    final h = MediaQuery.of(context).size.height;
    return Container(
      constraints: BoxConstraints(maxHeight: h * .88),
      decoration: const BoxDecoration(color: Color(0xFFFAFDF9), borderRadius: BorderRadius.vertical(top: Radius.circular(23))),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(22, 20, 16, 16),
          child: Row(children: [
            Expanded(child: Text('${d.cultivo} · ${d.lote}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700))),
            if (onEliminar != null) IconBtn(Icons.delete_outline, color: AppColors.red, tooltip: 'Eliminar', onPressed: () async {
              Navigator.pop(context);
              await onEliminar!(d);
            }),
            const SizedBox(width: 8),
            IconBtn(Icons.close, color: AppColors.navy, tooltip: 'Cerrar', onPressed: () => Navigator.pop(context)),
          ]),
        ),
        const Divider(height: 1, color: AppColors.line),
        Flexible(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(22),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Wrap(runSpacing: 16, children: [
                for (final f in filas)
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
              if (d.fotos.isNotEmpty) ...[
                const SizedBox(height: 20),
                const FieldLabel('Fotos'),
                GridView.count(
                  crossAxisCount: 3,
                  mainAxisSpacing: 8,
                  crossAxisSpacing: 8,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  children: [
                    for (final f in d.fotos)
                      GestureDetector(
                        onTap: () => _verFoto(context, ApiConfig.fotoUrl(d.id, f.archivo)),
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: Image.network(ApiConfig.fotoUrl(d.id, f.archivo), fit: BoxFit.cover, errorBuilder: (_, _, _) => Container(color: AppColors.line, child: const Icon(Icons.broken_image_outlined))),
                        ),
                      ),
                  ],
                ),
              ],
            ]),
          ),
        ),
      ]),
    );
  }

  void _verFoto(BuildContext context, String url) {
    showDialog<void>(
      context: context,
      builder: (c) => Dialog(
        backgroundColor: Colors.black,
        insetPadding: const EdgeInsets.all(12),
        child: Stack(children: [
          InteractiveViewer(child: Image.network(url, fit: BoxFit.contain)),
          Positioned(top: 4, right: 4, child: IconButton(onPressed: () => Navigator.pop(c), icon: const Icon(Icons.close, color: Colors.white))),
        ]),
      ),
    );
  }
}
