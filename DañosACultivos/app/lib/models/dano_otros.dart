double? _num(dynamic v) {
  if (v == null || v == '') return null;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString());
}

/// Campos comunes de los módulos Pecuario e Infraestructura (Afectaciones 2026).
abstract class DanoModulo {
  final int id;
  final String registradoPor;
  final String fechaInforme; // yyyy-MM-dd
  final String monitoreoInicio;
  final String monitoreoFin;
  final int? semana;
  final int? anioSemana;
  final String departamento;
  final String municipio;
  final String centroPoblado;
  final String eventoReportado;
  final String causa;
  final String causaOtra;
  final int? famAfectadas;
  final double? perdidaEconomica;
  final String notas;

  DanoModulo.fromJson(Map<String, dynamic> j)
      : id = int.tryParse('${j['id']}') ?? 0,
        registradoPor = (j['registradoPor'] ?? '').toString(),
        fechaInforme = (j['fechaInforme'] ?? '').toString(),
        monitoreoInicio = (j['monitoreoInicio'] ?? '').toString(),
        monitoreoFin = (j['monitoreoFin'] ?? '').toString(),
        semana = int.tryParse('${j['semana']}'),
        anioSemana = int.tryParse('${j['anioSemana']}'),
        departamento = (j['departamento'] ?? '').toString(),
        municipio = (j['municipio'] ?? '').toString(),
        centroPoblado = (j['centroPoblado'] ?? '').toString(),
        eventoReportado = (j['eventoReportado'] ?? '').toString(),
        causa = (j['causa'] ?? '').toString(),
        causaOtra = (j['causaOtra'] ?? '').toString(),
        famAfectadas = int.tryParse('${j['famAfectadas']}'),
        perdidaEconomica = _num(j['perdidaEconomica']),
        notas = (j['notas'] ?? '').toString();

  /// Título corto de la tarjeta (especie o tipo de infraestructura).
  String get titulo;

  String get causaTexto => causa == 'Otra' && causaOtra.isNotEmpty ? 'Otra: $causaOtra' : causa;
  String get ubicacion => [centroPoblado, municipio, departamento].where((e) => e.isNotEmpty).join(', ');
  String get periodoTexto => monitoreoInicio.isEmpty ? '' : '$monitoreoInicio al $monitoreoFin';
}

class DanoPecuario extends DanoModulo {
  final String especie;
  final String especieOtra;
  final int? animalesPerdidos;

  DanoPecuario.fromJson(super.j)
      : especie = (j['especie'] ?? '').toString(),
        especieOtra = (j['especieOtra'] ?? '').toString(),
        animalesPerdidos = int.tryParse('${j['animalesPerdidos']}'),
        super.fromJson();

  @override
  String get titulo => especie == 'Otra' && especieOtra.isNotEmpty ? 'Otra: $especieOtra' : especie;
}

class DanoInfraestructura extends DanoModulo {
  final String tipoInfraestructura;
  final String tipoOtra;
  final String gps;

  DanoInfraestructura.fromJson(super.j)
      : tipoInfraestructura = (j['tipoInfraestructura'] ?? '').toString(),
        tipoOtra = (j['tipoOtra'] ?? '').toString(),
        gps = (j['gps'] ?? '').toString(),
        super.fromJson();

  @override
  String get titulo => tipoInfraestructura == 'Otra' && tipoOtra.isNotEmpty ? 'Otra: $tipoOtra' : tipoInfraestructura;
}
