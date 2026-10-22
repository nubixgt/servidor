import 'package:flutter/material.dart';

import '../models/dano_otros.dart';
import '../models/usuario.dart';
import '../services/dano_otros_service.dart';
import '../services/offline_queue.dart';
import '../widgets/common.dart';
import '../widgets/tipo_registro_selector.dart';
import 'registros_otros_screen.dart';
import 'registros_screen.dart';

/// "Registros": cultivos, pecuario o infraestructura.
class RegistrosHubScreen extends StatefulWidget {
  final AuthUser user;
  final VoidCallback onLogout;
  final VoidCallback onCrear;
  final bool active;
  const RegistrosHubScreen({super.key, required this.user, required this.onLogout, required this.onCrear, required this.active});

  @override
  State<RegistrosHubScreen> createState() => _RegistrosHubScreenState();
}

class _RegistrosHubScreenState extends State<RegistrosHubScreen> {
  String _tipo = kTiposRegistro[0];

  static String _num(num? v) => v == null ? '' : fmtNum(v);

  @override
  Widget build(BuildContext context) {
    final selector = TipoRegistroSelector(value: _tipo, onChanged: (v) => setState(() => _tipo = v));
    return switch (_tipo) {
      'Pecuario' => RegistrosModuloScreen<DanoPecuario>(
          key: const ValueKey('pecuario'),
          user: widget.user,
          onLogout: widget.onLogout,
          active: widget.active,
          selector: selector,
          service: pecuarioService,
          titulo: 'Afectaciones pecuarias',
          subtitulo: widget.user.esTecnico ? 'Los registros que has realizado.' : 'Animales y familias afectadas reportadas en campo.',
          tipoCola: kTipoPecuario,
          etiquetaTitulo: 'Especie',
          detalle: (r) => [
            ('Fecha de informe', r.fechaInforme, false),
            ('Semana', r.semana != null ? '${r.semana} (${r.anioSemana})' : '—', false),
            ('Período de monitoreo', r.periodoTexto.isEmpty ? '—' : r.periodoTexto, true),
            ('Ubicación', r.ubicacion.isEmpty ? '—' : r.ubicacion, true),
            ('Especie', r.titulo, false),
            ('Causa', r.causaTexto, false),
            ('Evento reportado', r.eventoReportado.isEmpty ? '—' : r.eventoReportado, true),
            ('Familias afectadas', '${r.famAfectadas ?? '—'}', false),
            ('Animales perdidos', '${r.animalesPerdidos ?? '—'}', false),
            ('Pérdida económica', r.perdidaEconomica != null ? 'Q ${_num(r.perdidaEconomica)}' : '—', false),
            ('Registrado por', r.registradoPor.isEmpty ? '—' : r.registradoPor, false),
            ('Descripción', r.notas.isEmpty ? '—' : r.notas, true),
          ],
          cabeceraCsv: const ['N°', 'Fecha de informe', 'Monitoreo inicio', 'Monitoreo fin', 'Semana', 'Departamento', 'Municipio', 'Centro poblado', 'Especie', 'Evento reportado', 'Causa', 'Familias afectadas', 'Animales perdidos', 'Pérdida económica (Q)', 'Registrado por', 'Notas'],
          filaCsv: (r) => [r.fechaInforme, r.monitoreoInicio, r.monitoreoFin, r.semana != null ? '${r.semana}/${r.anioSemana}' : '', r.departamento, r.municipio, r.centroPoblado, r.titulo, r.eventoReportado, r.causaTexto, r.famAfectadas ?? '', r.animalesPerdidos ?? '', r.perdidaEconomica ?? '', r.registradoPor, r.notas],
          archivoCsv: 'pecuario',
        ),
      'Infraestructura' => RegistrosModuloScreen<DanoInfraestructura>(
          key: const ValueKey('infraestructura'),
          user: widget.user,
          onLogout: widget.onLogout,
          active: widget.active,
          selector: selector,
          service: infraestructuraService,
          titulo: 'Afectaciones en infraestructura',
          subtitulo: widget.user.esTecnico ? 'Los registros que has realizado.' : 'Infraestructura productiva afectada y familias impactadas.',
          tipoCola: kTipoInfraestructura,
          etiquetaTitulo: 'Tipo',
          detalle: (r) => [
            ('Fecha de informe', r.fechaInforme, false),
            ('Semana', r.semana != null ? '${r.semana} (${r.anioSemana})' : '—', false),
            ('Período de monitoreo', r.periodoTexto.isEmpty ? '—' : r.periodoTexto, true),
            ('Ubicación', r.ubicacion.isEmpty ? '—' : r.ubicacion, true),
            ('Tipo de infraestructura', r.titulo, false),
            ('Causa', r.causaTexto, false),
            ('Evento reportado', r.eventoReportado.isEmpty ? '—' : r.eventoReportado, true),
            ('Familias afectadas', '${r.famAfectadas ?? '—'}', false),
            ('Pérdida económica', r.perdidaEconomica != null ? 'Q ${_num(r.perdidaEconomica)}' : '—', false),
            ('GPS', r.gps.isEmpty ? '—' : r.gps, true),
            ('Registrado por', r.registradoPor.isEmpty ? '—' : r.registradoPor, false),
            ('Descripción', r.notas.isEmpty ? '—' : r.notas, true),
          ],
          cabeceraCsv: const ['N°', 'Fecha de informe', 'Monitoreo inicio', 'Monitoreo fin', 'Semana', 'Departamento', 'Municipio', 'Centro poblado', 'Tipo de infraestructura', 'GPS', 'Evento reportado', 'Causa', 'Familias afectadas', 'Pérdida económica (Q)', 'Registrado por', 'Notas'],
          filaCsv: (r) => [r.fechaInforme, r.monitoreoInicio, r.monitoreoFin, r.semana != null ? '${r.semana}/${r.anioSemana}' : '', r.departamento, r.municipio, r.centroPoblado, r.titulo, r.gps, r.eventoReportado, r.causaTexto, r.famAfectadas ?? '', r.perdidaEconomica ?? '', r.registradoPor, r.notas],
          archivoCsv: 'infraestructura',
        ),
      _ => RegistrosScreen(key: const ValueKey('cultivos'), user: widget.user, onLogout: widget.onLogout, onCrear: widget.onCrear, active: widget.active, selector: selector),
    };
  }
}
