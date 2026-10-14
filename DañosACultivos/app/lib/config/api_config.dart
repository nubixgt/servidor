/// URL base del backend (misma API que usa la web).
/// La ñ va codificada para que coincida con la ruta pública del servidor.
/// Puede cambiarse al compilar: flutter run --dart-define=API_BASE=https://host/ruta
class ApiConfig {
  static const String base = String.fromEnvironment(
    'API_BASE',
    defaultValue: 'https://m.nubix.gt/Da%C3%B1osACultivos/Backend',
  );

  static const String api = '$base/api/v1';

  /// URL pública de una foto subida a un registro.
  static String fotoUrl(int danoId, String archivo) =>
      '$base/uploads/danos-cultivos/$danoId/fotos/${Uri.encodeComponent(archivo)}';
}
