double? _num(dynamic v) {
  if (v == null || v == '') return null;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString());
}

class CultivoAsociado {
  final String cultivo;
  final String etapa;
  final double? areaLote;
  final double areaAfectada;

  CultivoAsociado({required this.cultivo, this.etapa = '', this.areaLote, this.areaAfectada = 0});

  factory CultivoAsociado.fromJson(Map<String, dynamic> j) => CultivoAsociado(
        cultivo: (j['cultivo'] ?? '').toString(),
        etapa: (j['etapa'] ?? '').toString(),
        areaLote: _num(j['areaLote']),
        areaAfectada: _num(j['areaAfectada']) ?? 0,
      );
}

class FotoDano {
  final int id;
  final String archivo;
  final String nombre;
  FotoDano({required this.id, required this.archivo, required this.nombre});

  factory FotoDano.fromJson(Map<String, dynamic> j) =>
      FotoDano(id: int.tryParse('${j['id']}') ?? 0, archivo: (j['archivo'] ?? '').toString(), nombre: (j['nombre'] ?? '').toString());
}

/// Nivel de afectación según el % de daño (misma regla que la web).
enum Nivel { bajo, medio, alto, total }

extension NivelX on Nivel {
  String get label => const ['Bajo', 'Medio', 'Alto', 'Pérdida total'][index];
}

Nivel nivelDePorc(double p) => p >= 100 ? Nivel.total : p >= 50 ? Nivel.alto : p >= 25 ? Nivel.medio : Nivel.bajo;

class DanoCultivo {
  final int id;
  final int? usuarioId;
  final String registradoPor;
  final String fecha; // yyyy-MM-dd
  final String productor;
  final String departamento;
  final String municipio;
  final String centroPoblado;
  final bool agricultorRua;
  final String codigoRua;
  final String lote;
  final String cultivo;
  final String etapa;
  final String causa;
  final String causaOtra;
  final List<CultivoAsociado> asociados;
  final double? areaLote;
  final double areaAfectada;
  final double danoPorc;
  final double? rendEsperado;
  final String unidad;
  final double? perdidaEstimada;
  final String gps;
  final double? gpsLat;
  final double? gpsLon;
  final String notas;
  final List<FotoDano> fotos;
  // Afectaciones 2026
  final String fechaInforme;
  final String monitoreoInicio;
  final String monitoreoFin;
  final int? semana;
  final int? anioSemana;
  final String fechaSiembra;
  final String eventoReportado;
  final int? famAfectadas;
  final int? famAutoconsumo;
  final int? famIntermedia;
  final int? famConsolidada;

  DanoCultivo({
    required this.id,
    this.usuarioId,
    this.registradoPor = '',
    required this.fecha,
    required this.productor,
    this.departamento = '',
    this.municipio = '',
    this.centroPoblado = '',
    this.agricultorRua = false,
    this.codigoRua = '',
    required this.lote,
    required this.cultivo,
    this.etapa = '',
    required this.causa,
    this.causaOtra = '',
    this.asociados = const [],
    this.areaLote,
    this.areaAfectada = 0,
    this.danoPorc = 0,
    this.rendEsperado,
    this.unidad = 't/ha',
    this.perdidaEstimada,
    this.gps = '',
    this.gpsLat,
    this.gpsLon,
    this.notas = '',
    this.fotos = const [],
    this.fechaInforme = '',
    this.monitoreoInicio = '',
    this.monitoreoFin = '',
    this.semana,
    this.anioSemana,
    this.fechaSiembra = '',
    this.eventoReportado = '',
    this.famAfectadas,
    this.famAutoconsumo,
    this.famIntermedia,
    this.famConsolidada,
  });

  factory DanoCultivo.fromJson(Map<String, dynamic> j) {
    String s(String k) => (j[k] ?? '').toString();
    return DanoCultivo(
      id: int.tryParse('${j['id']}') ?? 0,
      usuarioId: int.tryParse('${j['usuarioId']}'),
      registradoPor: s('registradoPor'),
      fecha: s('fecha'),
      productor: s('tecnico'),
      departamento: s('departamento'),
      municipio: s('municipio'),
      centroPoblado: s('centroPoblado'),
      agricultorRua: j['agricultorRua'] == true || j['agricultorRua'] == 1 || j['agricultorRua'] == '1',
      codigoRua: s('codigoRua'),
      lote: s('lote'),
      cultivo: s('cultivo'),
      etapa: s('etapa'),
      causa: s('causa'),
      causaOtra: s('causaOtra'),
      asociados: ((j['asociados'] as List?) ?? []).map((e) => CultivoAsociado.fromJson(Map<String, dynamic>.from(e))).toList(),
      areaLote: _num(j['areaLote']),
      areaAfectada: _num(j['areaAfectada']) ?? 0,
      danoPorc: _num(j['danoPorc']) ?? 0,
      rendEsperado: _num(j['rendEsperado']),
      unidad: s('unidad').isEmpty ? 't/ha' : s('unidad'),
      perdidaEstimada: _num(j['perdidaEstimada']),
      gps: s('gps'),
      gpsLat: _num(j['gpsLat']),
      gpsLon: _num(j['gpsLon']),
      notas: s('notas'),
      fotos: ((j['fotos'] as List?) ?? []).map((e) => FotoDano.fromJson(Map<String, dynamic>.from(e))).toList(),
      fechaInforme: s('fechaInforme'),
      monitoreoInicio: s('monitoreoInicio'),
      monitoreoFin: s('monitoreoFin'),
      semana: int.tryParse('${j['semana']}'),
      anioSemana: int.tryParse('${j['anioSemana']}'),
      fechaSiembra: s('fechaSiembra'),
      eventoReportado: s('eventoReportado'),
      famAfectadas: int.tryParse('${j['famAfectadas']}'),
      famAutoconsumo: int.tryParse('${j['famAutoconsumo']}'),
      famIntermedia: int.tryParse('${j['famIntermedia']}'),
      famConsolidada: int.tryParse('${j['famConsolidada']}'),
    );
  }

  Nivel get nivel => nivelDePorc(danoPorc);

  /// "Otra: detalle" cuando la causa es Otra.
  String get causaTexto => causa == 'Otra' && causaOtra.isNotEmpty ? 'Otra: $causaOtra' : causa;

  String get ubicacion => [centroPoblado, municipio, departamento].where((e) => e.isNotEmpty).join(', ');

  /// "2026-07-01 al 2026-07-07" o vacío si no se registró período de monitoreo.
  String get periodoTexto => monitoreoInicio.isEmpty ? '' : '$monitoreoInicio al $monitoreoFin';

  String get cultivosTexto => [cultivo, ...asociados.map((a) => a.cultivo)].where((e) => e.isNotEmpty).join(', ');
}
