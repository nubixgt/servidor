//app_uba/lib/pantallas/pantalla_denuncias.dart

import 'dart:io';
import 'dart:convert'; // jsonEncode

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';
import 'package:open_filex/open_filex.dart';

// <-- cliente HTTP (usa ClienteAPI.baseUrl y la función enviarDenunciaConArchivos)
import '../servicios/api/cliente.dart';

import '../servicios/servicios_multimedia.dart';
import '../utilidades/colores.dart';
import '../utilidades/formateadores_texto.dart';
import '../utilidades/datos_guatemala.dart';
import '../utilidades/validadores.dart';
import '../widgets/selector_ubicacion.dart'; // ← NUEVO
import 'package:google_maps_flutter/google_maps_flutter.dart';

enum TipoFoto { dpi, fachada, evidencia }

class PantallaDenuncias extends StatefulWidget {
  const PantallaDenuncias({super.key});

  @override
  State<PantallaDenuncias> createState() => _PantallaDenunciasState();
}

class _PantallaDenunciasState extends State<PantallaDenuncias> {
  int pasoActual = 1;
  final int totalPasos = 4;
  bool _enviando = false; // estado de envío

  // ---------- Form controllers (Paso 1)
  final _nombreController = TextEditingController();
  final _dpiController = TextEditingController();
  final _edadController = TextEditingController();
  final _celularController = TextEditingController();
  final _correoController = TextEditingController();

  String tipoPersona = 'Individual';
  String? generoSeleccionado;

  // ---------- Ubicación (Paso 2)
  String? departamentoSeleccionado;
  String? municipioSeleccionado;
  List<String> municipiosDisponibles = [];

  // Coordenadas del mapa ← NUEVO
  double? _latitud;
  double? _longitud;

  // Controladores Paso 2
  final _responsableCtrl = TextEditingController(); // opcional
  final _direccionCtrl = TextEditingController(); // requerido
  final _colorCasaCtrl = TextEditingController(); // opcional
  final _colorPuertaCtrl = TextEditingController(); // opcional

  // ---------- Paso 3
  String _especieSeleccionada = 'Caninos';
  final TextEditingController _especieOtrosCtrl = TextEditingController();

  final _cantidadCtrl = TextEditingController(); // requerido
  final _razaCtrl = TextEditingController(); // opcional
  final _descripcionCtrl = TextEditingController(); // requerido

  // ---------- Infracciones
  static const List<String> _catalogoInfracciones = [
    'Actos de Crueldad',
    'Abandono',
    'No garantizar condiciones de bienestar',
    'Maltrato físico',
    'Mutilaciones',
    'Envenenar o intoxicar a un animal',
    'Peleas de perros',
    'Técnicas de adiestramiento que causen sufrimiento',
    'Otros',
  ];
  final Set<String> _infraccionesSeleccionadas = {};
  final TextEditingController _otrosInfraccionController =
      TextEditingController();

  // ---------- Declaración legal (Paso 4)
  bool _aceptaDeclaracion = false;

  // ---------- Multimedia
  final ServiciosMultimedia _media = ServiciosMultimedia();

  final int _maxFotosDpi = 2; // frente/dorso
  final int _maxFotosEvidencia = 5; // fotos evidencia
  final int _maxArchivosEvidencia = 5;
  final int _pesoMaxArchivoMB = 20;
  List<PlatformFile> _archivosEvidencia = [];

  // Buckets
  List<XFile> _fotosDpi = []; // Paso 1
  XFile? _fotoFachada; // Paso 2
  List<XFile> _fotosEvidencia = []; // Paso 4

  @override
  void dispose() {
    _nombreController.dispose();
    _dpiController.dispose();
    _edadController.dispose();
    _celularController.dispose();
    _correoController.dispose();

    _responsableCtrl.dispose();
    _direccionCtrl.dispose();
    _colorCasaCtrl.dispose();
    _colorPuertaCtrl.dispose();

    _cantidadCtrl.dispose();
    _razaCtrl.dispose();
    _descripcionCtrl.dispose();

    _otrosInfraccionController.dispose();
    _especieOtrosCtrl.dispose();
    super.dispose();
  }

  // ==================== Captura de fotos ====================
  Future<void> _tomarFoto(TipoFoto destino) async {
    try {
      if (destino == TipoFoto.dpi && _fotosDpi.length >= _maxFotosDpi) {
        _toast('Máximo $_maxFotosDpi fotos de DPI (frente y dorso).');
        return;
      }
      if (destino == TipoFoto.fachada && _fotoFachada != null) {
        _toast('Solo se permite 1 foto de fachada.');
        return;
      }
      if (destino == TipoFoto.evidencia &&
          _fotosEvidencia.length >= _maxFotosEvidencia) {
        _toast('Máximo $_maxFotosEvidencia fotos de evidencia.');
        return;
      }

      final foto = await _media.tomarFotoCamara(
        imageQuality: 60,
      ); // ← CAMBIADO de 90 a 60
      if (foto == null) return;

      setState(() {
        switch (destino) {
          case TipoFoto.dpi:
            _fotosDpi.add(foto);
            break;
          case TipoFoto.fachada:
            _fotoFachada = foto;
            break;
          case TipoFoto.evidencia:
            _fotosEvidencia.add(foto);
            break;
        }
      });
    } catch (e) {
      _toast('Error al tomar foto: $e');
    }
  }

  // ==================== Adjuntar archivos ====================
  Future<void> _adjuntarArchivos() async {
    final restantes = _maxArchivosEvidencia - _archivosEvidencia.length;
    if (restantes <= 0) {
      _toast('Máximo $_maxArchivosEvidencia archivos adjuntos.');
      return;
    }

    try {
      final nuevos = await _media.seleccionarArchivos(
        maxCantidad: restantes,
        sizeMaxMB: _pesoMaxArchivoMB,
      );
      if (nuevos.isEmpty) return;

      setState(() => _archivosEvidencia.addAll(nuevos));
    } catch (e) {
      _toast('Error al adjuntar archivos: $e');
    }
  }

  void _eliminarArchivoEvidencia(int i) {
    setState(() => _archivosEvidencia.removeAt(i));
  }

  void _abrirArchivo(PlatformFile f) {
    final p = f.path;
    if (p == null || p.isEmpty) {
      _toast('No se puede abrir este archivo en este dispositivo.');
      return;
    }
    OpenFilex.open(p);
  }

  // Eliminar por bucket (fotos)
  void _eliminarFotoDpi(int i) => setState(() => _fotosDpi.removeAt(i));
  void _eliminarFachada() => setState(() => _fotoFachada = null);
  void _eliminarFotoEvidencia(int i) =>
      setState(() => _fotosEvidencia.removeAt(i));

  void _toast(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
  }

  // ==================== Validación por paso ====================
  bool _validarPaso(int paso) {
    switch (paso) {
      case 1:
        if (!Validadores.validarNombre(_nombreController.text)) {
          _toast('Ingresa tu nombre completo (mín. 3 caracteres).');
          return false;
        }
        if (!Validadores.validarDPI(_dpiController.text)) {
          _toast('El DPI debe tener 13 dígitos.');
          return false;
        }
        if (!Validadores.validarEdad(_edadController.text)) {
          _toast('La edad debe estar entre 18 y 120.');
          return false;
        }
        if (generoSeleccionado == null) {
          _toast('Selecciona el género.');
          return false;
        }
        if (!Validadores.validarCelular(_celularController.text)) {
          _toast('El celular debe tener 8 dígitos.');
          return false;
        }
        if (!Validadores.validarCorreo(_correoController.text.trim())) {
          _toast('El correo no tiene un formato válido.');
          return false;
        }
        if (_fotosDpi.length < _maxFotosDpi) {
          _toast('Adjunta las $_maxFotosDpi fotos del DPI (frente y dorso).');
          return false;
        }
        return true;

      case 2:
        if (_direccionCtrl.text.trim().isEmpty) {
          _toast('La dirección del lugar es obligatoria.');
          return false;
        }
        if (departamentoSeleccionado == null) {
          _toast('Selecciona el departamento.');
          return false;
        }
        if (municipioSeleccionado == null) {
          _toast('Selecciona el municipio.');
          return false;
        }
        if (_fotoFachada == null) {
          _toast('Toma la foto de la fachada.');
          return false;
        }
        return true;

      case 3:
        if (_especieSeleccionada == 'Otros' &&
            _especieOtrosCtrl.text.trim().isEmpty) {
          _toast('Especifica la especie afectada.');
          return false;
        }
        final cant = int.tryParse(_cantidadCtrl.text.trim());
        if (cant == null || cant <= 0) {
          _toast('La cantidad debe ser un número mayor que 0.');
          return false;
        }
        if (_infraccionesSeleccionadas.isEmpty) {
          _toast('Selecciona al menos un tipo de infracción.');
          return false;
        }
        if (_infraccionesSeleccionadas.contains('Otros') &&
            _otrosInfraccionController.text.trim().isEmpty) {
          _toast('Describe la infracción (Otros).');
          return false;
        }
        if (_descripcionCtrl.text.trim().isEmpty) {
          _toast('La descripción detallada es obligatoria.');
          return false;
        }
        return true;

      case 4:
        if (!_aceptaDeclaracion) {
          _toast('Debes aceptar la declaración legal para enviar.');
          return false;
        }
        if (_fotosEvidencia.isEmpty && _archivosEvidencia.isEmpty) {
          _toast('Adjunta al menos una evidencia (foto o archivo).');
          return false;
        }
        return true;

      default:
        return true;
    }
  }

  // ==================== Envío al backend ====================
  Future<void> _enviarAlBackend() async {
    setState(() => _enviando = true);

    try {
      // Construir especie
      final especie = _especieSeleccionada == 'Otros'
          ? _especieOtrosCtrl.text.trim()
          : _especieSeleccionada;

      // Construir infracciones
      final List<Map<String, dynamic>> infracciones = [];
      for (final tipo in _infraccionesSeleccionadas) {
        infracciones.add({
          'tipo': tipo,
          'otro': tipo == 'Otros'
              ? _otrosInfraccionController.text.trim()
              : null,
        });
      }

      // Datos de la denuncia
      final Map<String, dynamic> denunciaData = {
        'tipo_persona': tipoPersona,
        'nombre_completo': _nombreController.text.trim(),
        'dpi': Validadores.obtenerSoloNumeros(_dpiController.text),
        'edad': int.parse(_edadController.text.trim()),
        'genero': generoSeleccionado!,
        'celular': Validadores.obtenerSoloNumeros(_celularController.text),
        'nombre_responsable': _responsableCtrl.text.trim().isEmpty
            ? null
            : _responsableCtrl.text.trim(),
        'direccion_infraccion': _direccionCtrl.text.trim(),
        'departamento': departamentoSeleccionado!,
        'municipio': municipioSeleccionado!,
        'color_casa': _colorCasaCtrl.text.trim().isEmpty
            ? null
            : _colorCasaCtrl.text.trim(),
        'color_puerta': _colorPuertaCtrl.text.trim().isEmpty
            ? null
            : _colorPuertaCtrl.text.trim(),
        'latitud': _latitud, // ← ACTUALIZADO
        'longitud': _longitud, // ← ACTUALIZADO
        'especie_animal': especie,
        'especie_otro': _especieSeleccionada == 'Otros'
            ? _especieOtrosCtrl.text.trim()
            : null,
        'cantidad': int.parse(_cantidadCtrl.text.trim()),
        'raza': _razaCtrl.text.trim().isEmpty ? null : _razaCtrl.text.trim(),
        'descripcion_detallada': _descripcionCtrl.text.trim(),
        'infracciones': infracciones,
      };

      // Rutas de archivos
      final fotosDpi = _fotosDpi.map((f) => f.path).toList();
      final fotoFachada = _fotoFachada!.path;
      final fotosEvidencia = _fotosEvidencia.map((f) => f.path).toList();
      final archivosEvidencia = _archivosEvidencia
          .map((a) => a.path)
          .whereType<String>()
          .toList();

      // Llamar al servicio API
      await enviarDenuncia(
        denunciaData: denunciaData,
        fotosDpi: fotosDpi,
        fotoFachada: fotoFachada,
        fotosEvidencia: fotosEvidencia,
        archivosEvidencia: archivosEvidencia,
      );

      _toast('✅ Denuncia enviada correctamente');

      // Esperar un momento para que vean el mensaje
      await Future.delayed(const Duration(seconds: 1));

      if (mounted) Navigator.pop(context);
    } catch (e) {
      _toast('❌ Error al enviar: $e');
    } finally {
      if (mounted) setState(() => _enviando = false);
    }
  }

  // ==================== UI ====================
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      body: SafeArea(
        child: Column(
          children: [
            _construirEncabezado(),
            _construirBarraProgreso(),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: _construirContenidoPaso(),
              ),
            ),
            _construirBotonesNavegacion(),
          ],
        ),
      ),
    );
  }

  Widget _construirEncabezado() {
    return Container(
      color: AppColores.rojoPrimario,
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          IconButton(
            icon: const Icon(Icons.arrow_back, color: Colors.white),
            onPressed: _enviando
                ? null
                : () {
                    if (pasoActual > 1) {
                      setState(() => pasoActual--);
                    } else {
                      Navigator.pop(context);
                    }
                  },
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Nueva Denuncia',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(
                  'Paso $pasoActual de $totalPasos',
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.9),
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _construirBarraProgreso() {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        children: List.generate(totalPasos, (index) {
          return Expanded(
            child: Container(
              height: 8,
              margin: EdgeInsets.only(right: index < totalPasos - 1 ? 8 : 0),
              decoration: BoxDecoration(
                color: index < pasoActual
                    ? AppColores.rojoPrimario
                    : Colors.grey.shade200,
                borderRadius: BorderRadius.circular(4),
              ),
            ),
          );
        }),
      ),
    );
  }

  Widget _construirContenidoPaso() {
    switch (pasoActual) {
      case 1:
        return _construirPaso1();
      case 2:
        return _construirPaso2();
      case 3:
        return _construirPaso3();
      case 4:
        return _construirPaso4();
      default:
        return const SizedBox.shrink();
    }
  }

  // -------- Paso 1: Denunciante (DPI) --------
  Widget _construirPaso1() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: _card,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Datos del Denunciante', style: _tituloSeccion),
          const SizedBox(height: 24),
          const Text('Tipo de persona', style: _etiqueta),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _botonSeleccion(
                  'Individual',
                  tipoPersona == 'Individual',
                  () => setState(() => tipoPersona = 'Individual'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _botonSeleccion(
                  'Jurídica',
                  tipoPersona == 'Jurídica',
                  () => setState(() => tipoPersona = 'Jurídica'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          _campoTexto(
            'Nombre Completo *',
            'Ingrese su nombre completo',
            _nombreController,
          ),
          const SizedBox(height: 16),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('DPI *', style: _etiqueta),
              const SizedBox(height: 8),
              TextField(
                controller: _dpiController,
                keyboardType: TextInputType.number,
                maxLength: 15, // 13 dígitos + 2 espacios del formato
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                  LengthLimitingTextInputFormatter(13),
                  FormateadorDPI(),
                ],
                decoration: InputDecoration(
                  hintText: '0000 00000 0000',
                  counterText: '',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 12,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _campoTexto(
                  'Edad *',
                  '18',
                  _edadController,
                  tipoTeclado: TextInputType.number,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Género *', style: _etiqueta),
                    const SizedBox(height: 8),
                    DropdownButtonFormField<String>(
                      value: generoSeleccionado,
                      decoration: InputDecoration(
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                      ),
                      hint: const Text('Seleccione'),
                      items: ['Masculino', 'Femenino']
                          .map(
                            (g) => DropdownMenuItem(value: g, child: Text(g)),
                          )
                          .toList(),
                      onChanged: (v) => setState(() => generoSeleccionado = v),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Celular *', style: _etiqueta),
              const SizedBox(height: 8),
              TextField(
                controller: _celularController,
                keyboardType: TextInputType.number,
                maxLength: 9,
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                  LengthLimitingTextInputFormatter(8),
                  FormateadorCelular(),
                ],
                decoration: InputDecoration(
                  hintText: '0000-0000',
                  counterText: '',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 12,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          _campoTexto(
            'Correo Electrónico',
            'correo@ejemplo.com',
            _correoController,
            tipoTeclado: TextInputType.emailAddress,
          ),
          const SizedBox(height: 16),

          const Text('Foto de DPI (ambos lados) *', style: _etiqueta),
          const SizedBox(height: 8),
          _botonAccion(
            'Tomar foto',
            Icons.camera_alt,
            onTap: () => _tomarFoto(TipoFoto.dpi),
          ),
          const SizedBox(height: 8),
          if (_fotosDpi.isNotEmpty)
            _gridMiniaturas(_fotosDpi, onDelete: _eliminarFotoDpi),

          const SizedBox(height: 16),
          _bannerInfo(),
        ],
      ),
    );
  }

  // -------- Paso 2: Denunciado (Fachada) --------
  Widget _construirPaso2() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: _card,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Datos del Denunciado', style: _tituloSeccion),
          const SizedBox(height: 24),
          _campoTexto(
            'Nombre del responsable',
            'Si conoce el nombre',
            _responsableCtrl,
          ),
          const SizedBox(height: 16),
          _campoTextoMultilinea(
            'Dirección donde ocurre la infracción *',
            'Descripción detallada del lugar',
            controller: _direccionCtrl,
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Departamento *', style: _etiqueta),
                    const SizedBox(height: 8),
                    DropdownButtonFormField<String>(
                      value: departamentoSeleccionado,
                      isExpanded: true,
                      menuMaxHeight: 320,
                      itemHeight: kMinInteractiveDimension,
                      style: const TextStyle(
                        fontSize: 14,
                        color: Colors.black87,
                      ),
                      decoration: InputDecoration(
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 8,
                        ),
                      ),
                      hint: const Text(
                        'Seleccione',
                        overflow: TextOverflow.ellipsis,
                      ),
                      items: DatosGuatemala.departamentos
                          .map(
                            (d) => DropdownMenuItem(
                              value: d,
                              child: Text(
                                d,
                                overflow: TextOverflow.ellipsis,
                                maxLines: 1,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (valor) {
                        setState(() {
                          departamentoSeleccionado = valor;
                          municipioSeleccionado = null;
                          municipiosDisponibles =
                              DatosGuatemala.obtenerMunicipios(valor ?? '');
                        });
                      },
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Municipio *', style: _etiqueta),
                    const SizedBox(height: 8),
                    DropdownButtonFormField<String>(
                      value: municipioSeleccionado,
                      isExpanded: true,
                      menuMaxHeight: 320,
                      itemHeight: kMinInteractiveDimension,
                      style: const TextStyle(
                        fontSize: 14,
                        color: Colors.black87,
                      ),
                      decoration: InputDecoration(
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 8,
                        ),
                      ),
                      hint: const Text(
                        'Seleccione',
                        overflow: TextOverflow.ellipsis,
                      ),
                      items: municipiosDisponibles
                          .map(
                            (m) => DropdownMenuItem(
                              value: m,
                              child: Text(
                                m,
                                overflow: TextOverflow.ellipsis,
                                maxLines: 1,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: departamentoSeleccionado == null
                          ? null
                          : (valor) =>
                                setState(() => municipioSeleccionado = valor),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _campoTexto(
                  'Color de casa',
                  'Ej: Blanca',
                  _colorCasaCtrl,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _campoTexto(
                  'Color de puerta',
                  'Ej: Café',
                  _colorPuertaCtrl,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // ========== Selector de ubicación en mapa ==========
          const Text('Ubicación en el mapa', style: _etiqueta),
          const SizedBox(height: 8),
          _botonAccion(
            '📍 Marcar ubicación en el mapa',
            Icons.location_on,
            onTap: () async {
              await Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => SelectorUbicacion(
                    latitudInicial: _latitud,
                    longitudInicial: _longitud,
                    onUbicacionSeleccionada: (lat, lng) {
                      setState(() {
                        _latitud = lat;
                        _longitud = lng;
                      });
                      _toast('✅ Ubicación guardada correctamente');
                    },
                  ),
                ),
              );
            },
          ),

          // ========== NUEVO: Preview del mapa si ya seleccionó ubicación ==========
          if (_latitud != null && _longitud != null) ...[
            const SizedBox(height: 12),
            Container(
              height: 200,
              decoration: BoxDecoration(
                border: Border.all(color: Colors.grey.shade300, width: 2),
                borderRadius: BorderRadius.circular(12),
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: Stack(
                  children: [
                    GoogleMap(
                      initialCameraPosition: CameraPosition(
                        target: LatLng(_latitud!, _longitud!),
                        zoom: 16,
                      ),
                      markers: {
                        Marker(
                          markerId: const MarkerId('ubicacion_preview'),
                          position: LatLng(_latitud!, _longitud!),
                        ),
                      },
                      zoomControlsEnabled: false,
                      scrollGesturesEnabled: false,
                      zoomGesturesEnabled: false,
                      tiltGesturesEnabled: false,
                      rotateGesturesEnabled: false,
                      myLocationButtonEnabled: false,
                      mapToolbarEnabled: false,
                    ),
                    // Overlay para mostrar coordenadas
                    Positioned(
                      bottom: 8,
                      left: 8,
                      right: 8,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 8,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.95),
                          borderRadius: BorderRadius.circular(8),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.1),
                              blurRadius: 4,
                            ),
                          ],
                        ),
                        child: Row(
                          children: [
                            const Icon(
                              Icons.location_on,
                              color: Colors.red,
                              size: 18,
                            ),
                            const SizedBox(width: 6),
                            Expanded(
                              child: Text(
                                'Lat: ${_latitud!.toStringAsFixed(6)}, Lng: ${_longitud!.toStringAsFixed(6)}',
                                style: const TextStyle(fontSize: 11),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            IconButton(
                              icon: const Icon(Icons.edit, size: 18),
                              padding: EdgeInsets.zero,
                              constraints: const BoxConstraints(),
                              onPressed: () async {
                                await Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (_) => SelectorUbicacion(
                                      latitudInicial: _latitud,
                                      longitudInicial: _longitud,
                                      onUbicacionSeleccionada: (lat, lng) {
                                        setState(() {
                                          _latitud = lat;
                                          _longitud = lng;
                                        });
                                        _toast('✅ Ubicación actualizada');
                                      },
                                    ),
                                  ),
                                );
                              },
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],

          // ==========================================================
          const SizedBox(height: 16),

          // ==========================================================
          const Text('Foto de la fachada *', style: _etiqueta),
          const SizedBox(height: 8),
          _botonAccion(
            'Tomar foto',
            Icons.camera_alt,
            onTap: () => _tomarFoto(TipoFoto.fachada),
          ),
          const SizedBox(height: 8),
          if (_fotoFachada != null)
            _miniaturaSimple(_fotoFachada!, onDelete: _eliminarFachada),
        ],
      ),
    );
  }

  // -------- Paso 3: Detalles --------
  Widget _construirPaso3() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: _card,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Detalles del Caso', style: _tituloSeccion),
          const SizedBox(height: 24),
          const Text('Especie animal afectada *', style: _etiqueta),
          const SizedBox(height: 12),

          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 12,
            crossAxisSpacing: 12,
            childAspectRatio: 2.5,
            children: [
              _botonEspecie(
                '🐕',
                'Caninos',
                seleccionado: _especieSeleccionada == 'Caninos',
                onTap: () => setState(() => _especieSeleccionada = 'Caninos'),
              ),
              _botonEspecie(
                '🐈',
                'Felinos',
                seleccionado: _especieSeleccionada == 'Felinos',
                onTap: () => setState(() => _especieSeleccionada = 'Felinos'),
              ),
              _botonEspecie(
                '🐎',
                'Equinos',
                seleccionado: _especieSeleccionada == 'Equinos',
                onTap: () => setState(() => _especieSeleccionada = 'Equinos'),
              ),
              _botonEspecie(
                '🦜',
                'Otros',
                seleccionado: _especieSeleccionada == 'Otros',
                onTap: () => setState(() => _especieSeleccionada = 'Otros'),
              ),
            ],
          ),

          if (_especieSeleccionada == 'Otros') ...[
            const SizedBox(height: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Especifique la especie', style: _etiqueta),
                const SizedBox(height: 8),
                TextField(
                  controller: _especieOtrosCtrl,
                  decoration: InputDecoration(
                    hintText: 'Ej.: Ave silvestre, reptil, etc.',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 12,
                    ),
                  ),
                ),
              ],
            ),
          ],

          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _campoTexto(
                  'Cantidad *',
                  '1',
                  _cantidadCtrl,
                  tipoTeclado: TextInputType.number,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(child: _campoTexto('Raza', 'Si conoce', _razaCtrl)),
            ],
          ),
          const SizedBox(height: 16),
          const Text('Tipo de infracción *', style: _etiqueta),
          const SizedBox(height: 8),

          Container(
            constraints: const BoxConstraints(maxHeight: 250),
            child: ListView(
              shrinkWrap: true,
              children: _catalogoInfracciones
                  .map((t) => _checkboxInfraccion(t))
                  .toList(),
            ),
          ),

          if (_infraccionesSeleccionadas.contains('Otros')) ...[
            const SizedBox(height: 12),
            _campoTextoMultilinea(
              'Describe la infracción (Otros)',
              'Especifica el tipo de infracción que no aparece en la lista',
              lineas: 3,
              controller: _otrosInfraccionController,
            ),
          ],

          const SizedBox(height: 16),
          _campoTextoMultilinea(
            'Descripción detallada *',
            'Describa lo que ocurrió, cuándo, con qué frecuencia, etc.',
            lineas: 4,
            controller: _descripcionCtrl,
          ),
        ],
      ),
    );
  }

  // -------- Paso 4: Evidencias --------
  Widget _construirPaso4() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: _card,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Evidencias', style: _tituloSeccion),
          const SizedBox(height: 24),
          const Text('Medios probatorios *', style: _etiqueta),
          const SizedBox(height: 12),

          _botonAccion(
            'Tomar foto de evidencia\n(Máximo $_maxFotosEvidencia)',
            Icons.camera_alt,
            onTap: () => _tomarFoto(TipoFoto.evidencia),
          ),
          const SizedBox(height: 12),

          _botonAccion(
            'Adjuntar archivo (PDF, DOC, XLS, AUDIO, VIDEO, etc.)\n'
            '(Máximo $_maxArchivosEvidencia / $_pesoMaxArchivoMB MB c/u)',
            Icons.attach_file,
            onTap: _adjuntarArchivos,
          ),
          const SizedBox(height: 16),

          if (_fotosEvidencia.isNotEmpty) ...[
            const Text(
              'Fotos de evidencia',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 8),
            _gridMiniaturas(_fotosEvidencia, onDelete: _eliminarFotoEvidencia),
          ],

          if (_archivosEvidencia.isNotEmpty) ...[
            const SizedBox(height: 16),
            const Text(
              'Archivos adjuntos',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 8),
            _listaArchivosAdjuntos(),
          ],

          const SizedBox(height: 20),
          _bannerLegal(),
          const SizedBox(height: 16),

          CheckboxListTile(
            value: _aceptaDeclaracion,
            onChanged: (valor) =>
                setState(() => _aceptaDeclaracion = valor ?? false),
            controlAffinity: ListTileControlAffinity.leading,
            contentPadding: EdgeInsets.zero,
            title: const Text(
              'Declaro bajo juramento que la información proporcionada es verídica y cuento con las pruebas necesarias para sustentar esta denuncia.',
              style: TextStyle(fontSize: 14),
            ),
            activeColor: AppColores.rojoPrimario,
            checkColor: Colors.white,
          ),
        ],
      ),
    );
  }

  // ================== Helpers visuales ==================
  BoxDecoration get _card => BoxDecoration(
    color: Colors.white,
    borderRadius: BorderRadius.circular(16),
    boxShadow: [
      BoxShadow(
        color: Colors.black.withOpacity(0.05),
        blurRadius: 10,
        offset: const Offset(0, 2),
      ),
    ],
  );

  static const _tituloSeccion = TextStyle(
    fontSize: 20,
    fontWeight: FontWeight.bold,
    color: Color(0xFF1F2937),
  );
  static const _etiqueta = TextStyle(fontSize: 14, fontWeight: FontWeight.w500);

  Widget _construirBotonesNavegacion() {
    final bool enUltimoPaso = pasoActual == totalPasos;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: Row(
        children: [
          if (pasoActual > 1) ...[
            Expanded(
              child: ElevatedButton(
                onPressed: _enviando
                    ? null
                    : () => setState(() => pasoActual--),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.grey.shade200,
                  foregroundColor: Colors.grey.shade700,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text(
                  'Anterior',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                ),
              ),
            ),
            const SizedBox(width: 12),
          ],
          Expanded(
            child: ElevatedButton(
              onPressed: _enviando
                  ? null
                  : () async {
                      if (!_validarPaso(pasoActual)) return;

                      if (!enUltimoPaso) {
                        setState(() => pasoActual++);
                      } else {
                        await _enviarAlBackend();
                      }
                    },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColores.rojoPrimario,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                elevation: 4,
              ),
              child: _enviando
                  ? const SizedBox(
                      height: 22,
                      width: 22,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : Text(
                      enUltimoPaso ? 'Enviar Denuncia' : 'Siguiente',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _campoTexto(
    String etiqueta,
    String placeholder,
    TextEditingController controller, {
    TextInputType tipoTeclado = TextInputType.text,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(etiqueta, style: _etiqueta),
        const SizedBox(height: 8),
        TextField(
          controller: controller,
          keyboardType: tipoTeclado,
          decoration: InputDecoration(
            hintText: placeholder,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 12,
            ),
          ),
        ),
      ],
    );
  }

  Widget _campoTextoMultilinea(
    String etiqueta,
    String placeholder, {
    int lineas = 3,
    TextEditingController? controller,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(etiqueta, style: _etiqueta),
        const SizedBox(height: 8),
        TextField(
          controller:
              controller ??
              (etiqueta.contains('Otros') ? _otrosInfraccionController : null),
          maxLines: lineas,
          decoration: InputDecoration(
            hintText: placeholder,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            contentPadding: const EdgeInsets.all(16),
          ),
        ),
      ],
    );
  }

  Widget _botonSeleccion(
    String texto,
    bool seleccionado,
    VoidCallback alPresionar,
  ) {
    return InkWell(
      onTap: alPresionar,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: seleccionado ? Colors.blue.shade50 : Colors.white,
          border: Border.all(
            color: seleccionado ? Colors.blue.shade500 : Colors.grey.shade300,
            width: 2,
          ),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Text(
          texto,
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 14,
            fontWeight: seleccionado ? FontWeight.w600 : FontWeight.normal,
            color: seleccionado ? Colors.blue.shade700 : Colors.grey.shade700,
          ),
        ),
      ),
    );
  }

  Widget _botonAccion(
    String texto,
    IconData icono, {
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          border: Border.all(color: Colors.grey.shade300, width: 2),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icono, color: Colors.grey.shade600),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                texto,
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 14, color: Colors.grey.shade600),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _botonEspecie(
    String emoji,
    String nombre, {
    required bool seleccionado,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: seleccionado ? Colors.blue.shade50 : Colors.white,
          border: Border.all(
            color: seleccionado ? Colors.blue.shade500 : Colors.grey.shade300,
            width: 2,
          ),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(emoji, style: const TextStyle(fontSize: 24)),
            const SizedBox(width: 8),
            Text(
              nombre,
              style: TextStyle(
                fontSize: 14,
                fontWeight: seleccionado ? FontWeight.w600 : FontWeight.normal,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _checkboxInfraccion(String texto) {
    final selected = _infraccionesSeleccionadas.contains(texto);

    return Container(
      margin: const EdgeInsets.symmetric(vertical: 6),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: selected ? AppColores.rojoPrimario : Colors.grey.shade300,
          width: 1.2,
        ),
        color: selected
            ? AppColores.rojoPrimario.withOpacity(0.06)
            : Colors.white,
      ),
      child: CheckboxListTile(
        value: selected,
        onChanged: (valor) {
          setState(() {
            if (valor == true) {
              _infraccionesSeleccionadas.add(texto);
            } else {
              _infraccionesSeleccionadas.remove(texto);
              if (texto == 'Otros') _otrosInfraccionController.clear();
            }
          });
        },
        title: Text(texto, style: const TextStyle(fontSize: 14)),
        controlAffinity: ListTileControlAffinity.leading,
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
        activeColor: AppColores.rojoPrimario,
        checkColor: Colors.white,
      ),
    );
  }

  Widget _gridMiniaturas(
    List<XFile> archivos, {
    required void Function(int i) onDelete,
  }) {
    return GridView.builder(
      itemCount: archivos.length,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 3,
        mainAxisSpacing: 8,
        crossAxisSpacing: 8,
      ),
      itemBuilder: (_, i) {
        final f = archivos[i];
        return Stack(
          children: [
            Positioned.fill(child: Image.file(File(f.path), fit: BoxFit.cover)),
            Positioned(
              top: 4,
              right: 4,
              child: InkWell(
                onTap: () => onDelete(i),
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.black54,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  padding: const EdgeInsets.all(2),
                  child: const Icon(Icons.close, size: 16, color: Colors.white),
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _miniaturaSimple(XFile archivo, {required VoidCallback onDelete}) {
    return SizedBox(
      width: 110,
      height: 110,
      child: Stack(
        children: [
          Positioned.fill(
            child: Image.file(File(archivo.path), fit: BoxFit.cover),
          ),
          Positioned(
            top: 4,
            right: 4,
            child: InkWell(
              onTap: onDelete,
              child: Container(
                decoration: BoxDecoration(
                  color: Colors.black54,
                  borderRadius: BorderRadius.circular(12),
                ),
                padding: const EdgeInsets.all(2),
                child: const Icon(Icons.close, size: 16, color: Colors.white),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Icon _iconoPorExtension(String? ext) {
    final e = (ext ?? '').toLowerCase();
    if (['jpg', 'jpeg', 'png', 'heic', 'gif', 'webp'].contains(e)) {
      return const Icon(Icons.image);
    }
    if (['mp4', 'mov', 'avi', 'mkv', 'webm'].contains(e)) {
      return const Icon(Icons.video_file);
    }
    if (['mp3', 'wav', 'm4a', 'aac', 'flac'].contains(e)) {
      return const Icon(Icons.audio_file);
    }
    if (['pdf'].contains(e)) return const Icon(Icons.picture_as_pdf);
    if (['doc', 'docx', 'odt', 'rtf', 'txt', 'md'].contains(e)) {
      return const Icon(Icons.description);
    }
    if (['xls', 'xlsx', 'csv', 'ods'].contains(e)) {
      return const Icon(Icons.table_chart);
    }
    if (['ppt', 'pptx', 'odp'].contains(e)) return const Icon(Icons.slideshow);
    return const Icon(Icons.insert_drive_file);
  }

  String _tamanoLegible(int bytes) {
    if (bytes < 1024) return '$bytes B';
    final kb = bytes / 1024;
    if (kb < 1024) return '${kb.toStringAsFixed(1)} KB';
    final mb = kb / 1024;
    return '${mb.toStringAsFixed(1)} MB';
  }

  Widget _listaArchivosAdjuntos() {
    if (_archivosEvidencia.isEmpty) return const SizedBox.shrink();
    return ListView.separated(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: _archivosEvidencia.length,
      separatorBuilder: (_, __) => const Divider(height: 1),
      itemBuilder: (_, i) {
        final f = _archivosEvidencia[i];
        return ListTile(
          leading: _iconoPorExtension(f.extension),
          title: Text(f.name, maxLines: 1, overflow: TextOverflow.ellipsis),
          subtitle: Text(_tamanoLegible(f.size)),
          onTap: () => _abrirArchivo(f),
          trailing: IconButton(
            icon: const Icon(Icons.delete_forever),
            onPressed: () => _eliminarArchivoEvidencia(i),
          ),
        );
      },
    );
  }

  Widget _bannerInfo() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.yellow.shade50,
        border: Border.all(color: Colors.yellow.shade200),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.info_outline, color: Colors.yellow.shade800, size: 20),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              'Confidencialidad: Sus datos serán tratados con total confidencialidad según la Ley de Acceso a la Información Pública.',
              style: TextStyle(fontSize: 12, color: Colors.yellow.shade900),
            ),
          ),
        ],
      ),
    );
  }

  Widget _bannerLegal() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.red.shade50,
        border: Border.all(color: Colors.red.shade200),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.warning_amber_rounded, color: Colors.red.shade800),
              const SizedBox(width: 8),
              Text(
                'Advertencia Legal',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: Colors.red.shade800,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            'Según el Código Penal (Decreto 17-73), la acusación y denuncia falsa está penada con prisión de 1 a 6 años. El perjurio y falso testimonio con prisión de 6 meses a 3 años.',
            style: TextStyle(
              fontSize: 12,
              color: Colors.red.shade900,
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }
}
