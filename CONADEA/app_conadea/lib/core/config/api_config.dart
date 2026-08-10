/// Configuración del Backend PHP (Backend/api/v1).
///
/// Apunta al dominio real (m.nubix.gt) para que las pruebas del registro/login
/// lleguen al servidor de verdad. Si alguna vez pruebas contra un backend
/// corriendo en tu propia PC en vez del hosting, cambia esto:
/// - Emulador Android -> http://10.0.2.2/CONADEA/Backend (10.0.2.2 = localhost de tu PC visto desde el emulador).
/// - Dispositivo físico en la misma red -> http://IP-LOCAL-DE-TU-PC/CONADEA/Backend.
class ApiConfig {
  ApiConfig._();

  static const String baseUrl = 'https://m.nubix.gt/CONADEA/Backend';
}
