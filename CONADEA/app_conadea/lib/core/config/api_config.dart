/// Configuración del Backend PHP (Backend/api/v1).
///
/// - Emulador Android -> usa 10.0.2.2 en vez de localhost.
/// - Dispositivo físico en la misma red -> usa la IP local de tu PC (ej. http://192.168.1.50/CONADEA/Backend).
/// - Producción -> el dominio final, respetando la ruta /CONADEA/Backend definida en el .htaccess de la raíz.
class ApiConfig {
  ApiConfig._();

  static const String baseUrl = 'http://10.0.2.2/CONADEA/Backend';
}
