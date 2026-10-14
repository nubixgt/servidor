import 'dart:io';

import '../models/dano_cultivo.dart';
import 'api_client.dart';

class DanoService {
  final _api = ApiClient.instance;

  Future<List<DanoCultivo>> list() async {
    final data = await _api.get('/danos-cultivos');
    return ((data['danos'] as List?) ?? []).map((e) => DanoCultivo.fromJson(Map<String, dynamic>.from(e))).toList();
  }

  /// Crea el registro y luego sube las fotos. Si las fotos fallan, el registro ya quedó guardado:
  /// se devuelve el error en [fotosError]; [fotosSinConexion] indica que fue por falta de internet.
  Future<({DanoCultivo dano, String? fotosError, bool fotosSinConexion})> create(Map<String, dynamic> registro, List<File> fotos) async {
    final data = await _api.post('/danos-cultivos', registro);
    final dano = DanoCultivo.fromJson(Map<String, dynamic>.from(data['dano']));
    String? fotosError;
    var sinConexion = false;
    if (fotos.isNotEmpty) {
      try {
        await subirFotos(dano.id, fotos);
      } on ApiException catch (e) {
        fotosError = e.message;
        sinConexion = e.sinConexion;
      }
    }
    return (dano: dano, fotosError: fotosError, fotosSinConexion: sinConexion);
  }

  Future<void> subirFotos(int danoId, List<File> fotos) async {
    await _api.postFotos('/danos-cultivos/$danoId/fotos', fotos);
  }

  Future<void> remove(int id) async {
    await _api.delete('/danos-cultivos/$id');
  }
}
