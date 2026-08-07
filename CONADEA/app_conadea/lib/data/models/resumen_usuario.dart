/// Resumen de progreso del usuario — equivalente a los computeds del store
/// (pctGlobal, modulosCompletos, enProgreso, horasCapacitacion, racha) en
/// Frontend/src/stores/app.js. Datos de ejemplo mientras no hay backend.
class ResumenUsuario {
  const ResumenUsuario({
    required this.nombre,
    required this.progresoGlobalPct,
    required this.cursosCompletados,
    required this.enProgreso,
    required this.horasCapacitacion,
    required this.rachaDias,
  });

  final String nombre;
  final int progresoGlobalPct;
  final int cursosCompletados;
  final int enProgreso;
  final int horasCapacitacion;
  final int rachaDias;
}
