import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:path_provider/path_provider.dart';

import 'api_client.dart';
import 'dano_otros_service.dart';
import 'dano_service.dart';

/// Tipos de registro que puede guardar la cola.
const kTipoCultivo = 'cultivo';
const kTipoPecuario = 'pecuario';
const kTipoInfraestructura = 'infraestructura';

/// Registro guardado en el teléfono a la espera de enviarse al servidor.
class PendingItem {
  final String id;
  final int usuarioId;
  final DateTime creado;
  final Map<String, dynamic> registro;
  final String tipo; // cultivo | pecuario | infraestructura (los pendientes antiguos son de cultivo)
  final List<String> fotos; // rutas dentro del almacenamiento de la app
  int? danoId; // si ya se creó en el servidor, solo faltan las fotos
  String? error; // rechazo del servidor (no se reintenta solo)

  PendingItem({required this.id, required this.usuarioId, required this.creado, required this.registro, required this.fotos, this.tipo = kTipoCultivo, this.danoId, this.error});

  factory PendingItem.fromJson(Map<String, dynamic> j) => PendingItem(
        id: j['id'],
        usuarioId: j['usuarioId'],
        creado: DateTime.parse(j['creado']),
        registro: Map<String, dynamic>.from(j['registro']),
        fotos: List<String>.from(j['fotos']),
        tipo: (j['tipo'] ?? kTipoCultivo).toString(),
        danoId: j['danoId'],
        error: j['error'],
      );

  Map<String, dynamic> toJson() => {'id': id, 'usuarioId': usuarioId, 'creado': creado.toIso8601String(), 'registro': registro, 'fotos': fotos, 'tipo': tipo, 'danoId': danoId, 'error': error};

  String get lote => (registro['lote'] ?? '').toString();
  String get cultivo => (registro['cultivo'] ?? '').toString();
  String get fecha => (registro[tipo == kTipoCultivo ? 'fecha' : 'fechaInforme'] ?? '').toString();

  /// Título para la lista de pendientes.
  String get titulo => switch (tipo) {
        kTipoPecuario => 'Pecuario · ${registro['especie'] ?? ''}',
        kTipoInfraestructura => 'Infraestructura · ${registro['tipoInfraestructura'] ?? ''}',
        _ => '${lote.isEmpty ? 'Registro' : lote} · $cultivo',
      };
}

/// Cola de registros sin enviar. Se guarda en disco (sobrevive a cerrar la app) y se envía sola
/// cuando vuelve la conexión, al abrir la app o cada minuto mientras haya pendientes.
class OfflineQueue extends ChangeNotifier {
  OfflineQueue._();
  static final OfflineQueue instance = OfflineQueue._();

  final _service = DanoService();
  final List<PendingItem> _items = [];
  StreamSubscription<List<ConnectivityResult>>? _sub;
  Timer? _timer;
  bool _sincronizando = false;
  int? _usuarioId;

  /// Número de registros enviados con éxito desde que se abrió la app (para refrescar listados).
  int exitos = 0;

  /// Último aviso para mostrar al usuario (lo consume la pantalla principal).
  String? aviso;

  bool get sincronizando => _sincronizando;
  List<PendingItem> get items => _items.where((i) => i.usuarioId == _usuarioId).toList();
  int get pendientes => items.length;

  Future<void> iniciar(int usuarioId) async {
    _usuarioId = usuarioId;
    await _cargar();
    _sub ??= Connectivity().onConnectivityChanged.listen((r) {
      if (_hayRed(r)) sincronizar();
    });
    _timer ??= Timer.periodic(const Duration(seconds: 60), (_) {
      if (pendientes > 0) sincronizar();
    });
    notifyListeners();
    sincronizar();
  }

  void detener() {
    _usuarioId = null;
    _sub?.cancel();
    _sub = null;
    _timer?.cancel();
    _timer = null;
    notifyListeners();
  }

  static bool _hayRed(List<ConnectivityResult> r) => r.any((e) => e != ConnectivityResult.none);

  static Future<bool> hayRed() async => _hayRed(await Connectivity().checkConnectivity());

  Future<Directory> _dir() async {
    final base = await getApplicationDocumentsDirectory();
    final d = Directory('${base.path}/pendientes');
    if (!await d.exists()) await d.create(recursive: true);
    return d;
  }

  Future<File> _archivo() async => File('${(await _dir()).path}/cola.json');

  Future<void> _cargar() async {
    try {
      final f = await _archivo();
      if (!await f.exists()) return;
      final list = jsonDecode(await f.readAsString()) as List;
      _items
        ..clear()
        ..addAll(list.map((e) => PendingItem.fromJson(Map<String, dynamic>.from(e))));
    } catch (_) {
      // Archivo dañado: se ignora para no bloquear la app.
    }
  }

  Future<void> _guardar() async {
    final f = await _archivo();
    await f.writeAsString(jsonEncode(_items.map((i) => i.toJson()).toList()));
  }

  /// Guarda un registro (y copia sus fotos) para enviarlo después.
  /// Si [danoId] viene informado, el registro ya existe en el servidor y solo faltan las fotos.
  Future<void> encolar(Map<String, dynamic> registro, List<File> fotos, int usuarioId, {int? danoId, String tipo = kTipoCultivo}) async {
    final id = DateTime.now().microsecondsSinceEpoch.toString();
    final carpeta = Directory('${(await _dir()).path}/$id');
    await carpeta.create(recursive: true);
    final copias = <String>[];
    for (var i = 0; i < fotos.length; i++) {
      final ext = fotos[i].path.contains('.') ? fotos[i].path.split('.').last : 'jpg';
      copias.add((await fotos[i].copy('${carpeta.path}/$i.$ext')).path);
    }
    _items.add(PendingItem(id: id, usuarioId: usuarioId, creado: DateTime.now(), registro: registro, fotos: copias, tipo: tipo, danoId: danoId));
    await _guardar();
    notifyListeners();
  }

  Future<void> eliminar(PendingItem item) async {
    _items.removeWhere((i) => i.id == item.id);
    await _limpiarArchivos(item);
    await _guardar();
    notifyListeners();
  }

  Future<void> reintentar(PendingItem item) async {
    item.error = null;
    await _guardar();
    notifyListeners();
    sincronizar();
  }

  Future<void> _limpiarArchivos(PendingItem item) async {
    try {
      final d = Directory('${(await _dir()).path}/${item.id}');
      if (await d.exists()) await d.delete(recursive: true);
    } catch (_) {}
  }

  /// Envía los pendientes del usuario en orden. Se detiene si no hay conexión.
  Future<void> sincronizar() async {
    if (_sincronizando || _usuarioId == null || ApiClient.instance.token == null) return;
    if (!await hayRed()) return;
    _sincronizando = true;
    notifyListeners();
    var enviados = 0;
    try {
      for (final item in List<PendingItem>.from(items)) {
        if (item.error != null) continue;
        try {
          if (item.tipo == kTipoPecuario) {
            await pecuarioService.create(item.registro);
          } else if (item.tipo == kTipoInfraestructura) {
            await infraestructuraService.create(item.registro);
          } else if (item.danoId == null) {
            final r = await _service.create(item.registro, item.fotos.map((p) => File(p)).toList());
            item.danoId = r.dano.id;
            await _guardar();
            if (r.fotosError != null) {
              if (r.fotosSinConexion) break;
              item.error = 'El registro se guardó pero las fotos no se subieron: ${r.fotosError}';
              await _guardar();
              continue;
            }
          } else if (item.fotos.isNotEmpty) {
            await _service.subirFotos(item.danoId!, item.fotos.map((p) => File(p)).toList());
          }
          _items.removeWhere((i) => i.id == item.id);
          await _limpiarArchivos(item);
          await _guardar();
          enviados++;
          exitos++;
        } on ApiException catch (e) {
          if (e.sinConexion || e.status == null || e.status == 401) break; // se reintenta más tarde
          item.error = e.message;
          await _guardar();
        }
      }
    } finally {
      _sincronizando = false;
      if (enviados > 0) aviso = enviados == 1 ? 'Se envió 1 registro pendiente.' : 'Se enviaron $enviados registros pendientes.';
      notifyListeners();
    }
  }
}
