// app_uba/lib/servicios/api/cliente.dart

import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';
import 'package:mime/mime.dart';
import '../../modelos/noticia.dart';
import '../../modelos/clinica.dart';

/// URL base del backend en tu servidor
class ClienteAPI {
  // ⚠️ IMPORTANTE: Cambia esta URL por la IP/dominio de tu servidor
  static const String baseUrl = 'http://159.65.168.91/AppUBA/backend/api';
}

/// Sube un archivo individual (foto o documento) al backend
/// 
/// Parámetros:
/// - [rutaArchivo]: Ruta completa del archivo en el dispositivo
/// - [tipo]: Tipo de destino ('dpi', 'fachada', 'evidencia')
/// 
/// Retorna: Map con la respuesta del servidor (nombre_archivo, ruta_archivo, url, etc.)
Future<Map<String, dynamic>> subirArchivo(
  String rutaArchivo,
  String tipo,
) async {
  try {
    final uri = Uri.parse('${ClienteAPI.baseUrl}/uploads.php');
    final request = http.MultipartRequest('POST', uri);

    // Campo 'tipo' (dpi, fachada, evidencia)
    request.fields['tipo'] = tipo;

    // Archivo
    final archivo = File(rutaArchivo);
    if (!await archivo.exists()) {
      throw Exception('El archivo no existe: $rutaArchivo');
    }

    // Detectar MIME type
    final mimeType = lookupMimeType(rutaArchivo) ?? 'application/octet-stream';
    final mimeParts = mimeType.split('/');

    request.files.add(
      await http.MultipartFile.fromPath(
        'archivo',
        rutaArchivo,
        contentType: MediaType(mimeParts[0], mimeParts[1]),
      ),
    );

    // Enviar
    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);

    if (response.statusCode == 201 || response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['success'] == true) {
        return json['data'] as Map<String, dynamic>;
      } else {
        throw Exception(json['message'] ?? 'Error al subir archivo');
      }
    } else {
      throw Exception(
        'Error HTTP ${response.statusCode}: ${response.body}',
      );
    }
  } catch (e) {
    throw Exception('Error al subir archivo: $e');
  }
}

/// Envía una denuncia completa al backend
/// 
/// Flujo:
/// 1. Sube todas las fotos y archivos al servidor
/// 2. Recopila las rutas generadas
/// 3. Envía el JSON con toda la información de la denuncia
/// 
/// Parámetros:
/// - [denunciaData]: Map con todos los datos del formulario
/// - [fotosDpi]: Lista de rutas de fotos del DPI (frente y dorso)
/// - [fotoFachada]: Ruta de la foto de la fachada
/// - [fotosEvidencia]: Lista de rutas de fotos de evidencia
/// - [archivosEvidencia]: Lista de rutas de archivos adjuntos (PDF, DOC, etc.)
Future<void> enviarDenuncia({
  required Map<String, dynamic> denunciaData,
  required List<String> fotosDpi,
  required String fotoFachada,
  required List<String> fotosEvidencia,
  required List<String> archivosEvidencia,
}) async {
  try {
    // PASO 1: Subir fotos del DPI
    final List<String> rutasDpi = [];
    for (int i = 0; i < fotosDpi.length; i++) {
      final resultado = await subirArchivo(fotosDpi[i], 'dpi');
      rutasDpi.add(resultado['ruta_archivo']);
    }

    // PASO 2: Subir foto de fachada
    final resultadoFachada = await subirArchivo(fotoFachada, 'fachada');
    final rutaFachada = resultadoFachada['ruta_archivo'];

    // PASO 3: Subir fotos de evidencia
    final List<Map<String, dynamic>> evidencias = [];
    for (final rutaFoto in fotosEvidencia) {
      final resultado = await subirArchivo(rutaFoto, 'evidencia');
      evidencias.add({
        'tipo': 'imagen',
        'nombre': resultado['nombre_archivo'],
        'ruta': resultado['ruta_archivo'],
        'tamanio': resultado['tamanio_kb'],
      });
    }

    // PASO 4: Subir archivos adjuntos (PDF, DOC, etc.)
    for (final rutaArchivo in archivosEvidencia) {
      final resultado = await subirArchivo(rutaArchivo, 'evidencia');
      
      // Determinar tipo según extensión
      String tipoArchivo = 'otro';
      final ext = resultado['extension'].toString().toLowerCase();
      
      if (['jpg', 'jpeg', 'png', 'gif', 'webp'].contains(ext)) {
        tipoArchivo = 'imagen';
      } else if (['pdf'].contains(ext)) {
        tipoArchivo = 'pdf';
      } else if (['doc', 'docx'].contains(ext)) {
        tipoArchivo = 'doc';
      } else if (['mp3', 'wav', 'm4a'].contains(ext)) {
        tipoArchivo = 'audio';
      } else if (['mp4', 'mov', 'avi'].contains(ext)) {
        tipoArchivo = 'video';
      }

      evidencias.add({
        'tipo': tipoArchivo,
        'nombre': resultado['nombre_archivo'],
        'ruta': resultado['ruta_archivo'],
        'tamanio': resultado['tamanio_kb'],
      });
    }

    // PASO 5: Construir el payload completo con las rutas de archivos
    final payload = {
      ...denunciaData,
      'foto_dpi_frontal': rutasDpi.isNotEmpty ? rutasDpi[0] : '',
      'foto_dpi_trasera': rutasDpi.length > 1 ? rutasDpi[1] : '',
      'foto_fachada': rutaFachada,
      'evidencias': evidencias,
    };

    // PASO 6: Enviar denuncia al endpoint
    final uri = Uri.parse('${ClienteAPI.baseUrl}/denuncias.php');
    final response = await http.post(
      uri,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode(payload),
    );

    if (response.statusCode == 201 || response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['success'] != true) {
        throw Exception(json['message'] ?? 'Error al crear denuncia');
      }
    } else {
      throw Exception(
        'Error HTTP ${response.statusCode}: ${response.body}',
      );
    }
  } catch (e) {
    rethrow;
  }
}

/// Obtiene el listado de departamentos desde el backend
Future<List<Map<String, dynamic>>> obtenerDepartamentos() async {
  try {
    final uri = Uri.parse(
      '${ClienteAPI.baseUrl}/infracciones.php?tipo=departamentos',
    );
    final response = await http.get(uri);

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['success'] == true) {
        return List<Map<String, dynamic>>.from(json['data']);
      }
    }
    throw Exception('Error al obtener departamentos');
  } catch (e) {
    rethrow;
  }
}

/// Obtiene los municipios de un departamento específico
Future<List<Map<String, dynamic>>> obtenerMunicipios(
  String departamento,
) async {
  try {
    final uri = Uri.parse(
      '${ClienteAPI.baseUrl}/infracciones.php?tipo=municipios&departamento=$departamento',
    );
    final response = await http.get(uri);

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['success'] == true) {
        return List<Map<String, dynamic>>.from(json['data']);
      }
    }
    throw Exception('Error al obtener municipios');
  } catch (e) {
    rethrow;
  }
}

/// Obtiene el catálogo de tipos de infracción
Future<List<Map<String, dynamic>>> obtenerTiposInfraccion() async {
  try {
    final uri = Uri.parse(
      '${ClienteAPI.baseUrl}/infracciones.php?tipo=tipos_infraccion',
    );
    final response = await http.get(uri);

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['success'] == true) {
        return List<Map<String, dynamic>>.from(json['data']);
      }
    }
    throw Exception('Error al obtener tipos de infracción');
  } catch (e) {
    rethrow;
  }
}

/// Obtiene el catálogo de especies animales
Future<List<Map<String, dynamic>>> obtenerEspecies() async {
  try {
    final uri = Uri.parse(
      '${ClienteAPI.baseUrl}/infracciones.php?tipo=especies',
    );
    final response = await http.get(uri);

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['success'] == true) {
        return List<Map<String, dynamic>>.from(json['data']);
      }
    }
    throw Exception('Error al obtener especies');
  } catch (e) {
    rethrow;
  }
}

/// Obtiene el listado de noticias publicadas desde el backend
Future<List<Noticia>> obtenerNoticias() async {
  try {
    final uri = Uri.parse('${ClienteAPI.baseUrl}/noticias.php');
    final response = await http.get(uri);

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['success'] == true) {
        final List<dynamic> data = json['data'];
        return data.map((item) => Noticia.fromJson(item)).toList();
      } else {
        throw Exception(json['message'] ?? 'Error al obtener noticias');
      }
    } else {
      throw Exception('Error HTTP ${response.statusCode}');
    }
  } catch (e) {
    throw Exception('Error al obtener noticias: $e');
  }
}

/// Obtiene el listado de servicios autorizados activos desde el backend
Future<List<ServicioAutorizado>> obtenerServicios() async {
  try {
    final uri = Uri.parse('${ClienteAPI.baseUrl}/servicios.php');
    final response = await http.get(uri);

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['success'] == true) {
        final List<dynamic> data = json['data'];
        return data.map((item) => ServicioAutorizado.fromJson(item)).toList();
      } else {
        throw Exception(json['message'] ?? 'Error al obtener servicios');
      }
    } else {
      throw Exception('Error HTTP ${response.statusCode}');
    }
  } catch (e) {
    throw Exception('Error al obtener servicios: $e');
  }
}

/// Envía una calificación para un servicio autorizado
/// 
/// Parámetros:
/// - [idServicio]: ID del servicio a calificar
/// - [calificacion]: Calificación de 1 a 5 estrellas
/// 
/// Retorna: Map con la nueva calificación promedio y total de calificaciones
Future<Map<String, dynamic>> calificarServicio({
  required int idServicio,
  required double calificacion,
}) async {
  try {
    final uri = Uri.parse('${ClienteAPI.baseUrl}/calificar_servicio.php');
    final response = await http.post(
      uri,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'id_servicio': idServicio,
        'calificacion': calificacion,
      }),
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['success'] == true) {
        return json['data'] as Map<String, dynamic>;
      } else {
        throw Exception(json['message'] ?? 'Error al calificar servicio');
      }
    } else {
      throw Exception('Error HTTP ${response.statusCode}');
    }
  } catch (e) {
    throw Exception('Error al calificar servicio: $e');
  }
}

