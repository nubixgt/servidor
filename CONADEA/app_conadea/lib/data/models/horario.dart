/// Horario de estudio de un curso para el usuario logueado — fila de
/// `horarios_curso` (GET /horarios). Es el mismo horario que se configura
/// por WhatsApp y que usa el bot para el recordatorio.
class HorarioEstudio {
  const HorarioEstudio({
    required this.cursoId,
    required this.cursoTitulo,
    required this.dias,
    required this.hora,
    required this.duracionMinutos,
    required this.activo,
  });

  final int cursoId;
  final String cursoTitulo;

  /// Letras del Backend: L M X J V S D (X = miércoles).
  final Set<String> dias;

  /// "HH:MM" en 24 horas.
  final String hora;
  final int duracionMinutos;
  final bool activo;

  // PDO puede devolver los enteros como texto según la configuración del
  // servidor, así que se parsean con tolerancia en vez de hacer `as int`.
  factory HorarioEstudio.fromJson(Map<String, dynamic> json) {
    return HorarioEstudio(
      cursoId: int.parse('${json['curso_id']}'),
      cursoTitulo: json['curso_titulo'] as String,
      dias: (json['dias'] as String).split(',').toSet(),
      hora: (json['hora'] as String).substring(0, 5),
      duracionMinutos: int.parse('${json['duracion_minutos']}'),
      activo: '${json['activo']}' == '1' || json['activo'] == true,
    );
  }

  HorarioEstudio copyWith({bool? activo}) => HorarioEstudio(
        cursoId: cursoId,
        cursoTitulo: cursoTitulo,
        dias: dias,
        hora: hora,
        duracionMinutos: duracionMinutos,
        activo: activo ?? this.activo,
      );
}
