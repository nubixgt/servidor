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
  /// se devuelve el mensaje de error de las fotos en [fotosError].
  Future<({DanoCultivo dano, String? fotosError})> create(Map<String, dynamic> registro, List<File> fotos) async {
    final data = await _api.post('/danos-cultivos', registro);
    var dano = DanoCultivo.fromJson(Map<String, dynamic>.from(data['dano']));
    String? fotosError;
    if (fotos.isNotEmpty) {
      try {
        await _api.postFotos('/danos-cultivos/${dano.id}/fotos', fotos);
      } on ApiException catch (e) {
        fotosError = e.message;
      }
    }
    return (dano: dano, fotosError: fotosError);
  }

  Future<void> remove(int id) async {
    await _api.delete('/danos-cultivos/$id');
  }
}
