import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:path/path.dart';
import 'package:file_picker/file_picker.dart';
import 'package:image_picker/image_picker.dart';

class ApiService {
  // Ajustar a la URL de tu servidor
  // Para Android Emulator use 10.0.2.2 si es localhost
  static const String baseUrl = 'https://ubapp.maga.gob.gt/sistema-uba/Backend/api/v1';

  Future<bool> enviarDenuncia({
    required Map<String, String> data,
    required List<XFile> fotosDpi,
    required XFile? fotoFachada,
    required List<XFile> fotosEvidencia,
    required List<PlatformFile> archivosEvidencia,
  }) async {
    try {
      var request = http.MultipartRequest('POST', Uri.parse('$baseUrl/denuncias'));

      // Agregar campos de texto
      request.fields.addAll(data);

      // Agregar Fotos DPI
      if (fotosDpi.isNotEmpty) {
        request.files.add(await _crearMultipartFile(fotosDpi[0], 'dpi_frontal'));
        if (fotosDpi.length > 1) {
          request.files.add(await _crearMultipartFile(fotosDpi[1], 'dpi_reverso'));
        }
      }

      // Agregar Foto Fachada
      if (fotoFachada != null) {
        request.files.add(await _crearMultipartFile(fotoFachada, 'fachada'));
      }

      // Agregar Fotos Evidencia
      for (var i = 0; i < fotosEvidencia.length; i++) {
        request.files.add(await _crearMultipartFile(fotosEvidencia[i], 'evidencia_foto[]'));
      }

      // Agregar Archivos Evidencia (Documentos)
      for (var i = 0; i < archivosEvidencia.length; i++) {
        if (archivosEvidencia[i].path != null) {
           request.files.add(await http.MultipartFile.fromPath(
            'evidencia_documento[]',
            archivosEvidencia[i].path!,
            filename: archivosEvidencia[i].name,
          ));
        }
      }

      var response = await request.send();
      
      if (response.statusCode == 201) {
        return true;
      } else {
        var responseData = await response.stream.bytesToString();
        print('Error en el servidor: $responseData');
        return false;
      }
    } catch (e) {
      print('Error al enviar denuncia: $e');
      return false;
    }
  }

  Future<http.MultipartFile> _crearMultipartFile(XFile file, String fieldName) async {
    return await http.MultipartFile.fromPath(
      fieldName,
      file.path,
      filename: basename(file.path),
    );
  }
}
