// app_uba/lib/widgets/selector_ubicacion.dart

import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:geolocator/geolocator.dart';

class SelectorUbicacion extends StatefulWidget {
  final double? latitudInicial;
  final double? longitudInicial;
  final Function(double lat, double lng) onUbicacionSeleccionada;

  const SelectorUbicacion({
    super.key,
    this.latitudInicial,
    this.longitudInicial,
    required this.onUbicacionSeleccionada,
  });

  @override
  State<SelectorUbicacion> createState() => _SelectorUbicacionState();
}

class _SelectorUbicacionState extends State<SelectorUbicacion> {
  GoogleMapController? _mapController;
  LatLng _posicionActual = const LatLng(
    14.6349,
    -90.5069,
  ); // Ciudad de Guatemala por defecto
  bool _cargandoUbicacion = false;

  @override
  void initState() {
    super.initState();
    if (widget.latitudInicial != null && widget.longitudInicial != null) {
      _posicionActual = LatLng(widget.latitudInicial!, widget.longitudInicial!);
    } else {
      _obtenerUbicacionActual();
    }
  }

  Future<void> _obtenerUbicacionActual() async {
    setState(() => _cargandoUbicacion = true);

    try {
      // Verificar si el servicio de ubicación está habilitado
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        _mostrarMensaje('El servicio de ubicación está desactivado');
        setState(() => _cargandoUbicacion = false);
        return;
      }

      // Verificar permisos
      LocationPermission permiso = await Geolocator.checkPermission();
      if (permiso == LocationPermission.denied) {
        permiso = await Geolocator.requestPermission();
        if (permiso == LocationPermission.denied) {
          _mostrarMensaje('Permiso de ubicación denegado');
          setState(() => _cargandoUbicacion = false);
          return;
        }
      }

      if (permiso == LocationPermission.deniedForever) {
        _mostrarMensaje(
          'Permiso de ubicación denegado permanentemente. Habilítalo en configuración.',
        );
        setState(() => _cargandoUbicacion = false);
        return;
      }

      // Obtener ubicación
      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );

      setState(() {
        _posicionActual = LatLng(position.latitude, position.longitude);
        _cargandoUbicacion = false;
      });

      // Mover cámara a la ubicación actual
      _mapController?.animateCamera(
        CameraUpdate.newLatLngZoom(_posicionActual, 16),
      );
    } catch (e) {
      _mostrarMensaje('Error al obtener ubicación: $e');
      setState(() => _cargandoUbicacion = false);
    }
  }

  void _mostrarMensaje(String mensaje) {
    if (!mounted) return;
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(mensaje)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Selecciona la ubicación'),
        backgroundColor: Colors.red,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.check),
            onPressed: () {
              widget.onUbicacionSeleccionada(
                _posicionActual.latitude,
                _posicionActual.longitude,
              );
              Navigator.pop(context);
            },
          ),
        ],
      ),
      body: Stack(
        children: [
          GoogleMap(
            initialCameraPosition: CameraPosition(
              target: _posicionActual,
              zoom: 16,
            ),
            onMapCreated: (controller) {
              _mapController = controller;
            },
            onTap: (LatLng posicion) {
              setState(() => _posicionActual = posicion);
            },
            markers: {
              Marker(
                markerId: const MarkerId('ubicacion_seleccionada'),
                position: _posicionActual,
                draggable: true,
                onDragEnd: (nuevaPosicion) {
                  setState(() => _posicionActual = nuevaPosicion);
                },
              ),
            },
            myLocationEnabled: true,
            myLocationButtonEnabled: false,
            zoomControlsEnabled: false,
            mapType: MapType.normal,
          ),

          // Instrucciones
          Positioned(
            top: 16,
            left: 16,
            right: 16,
            child: Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(8),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.1),
                    blurRadius: 8,
                  ),
                ],
              ),
              child: const Text(
                'Toca en el mapa o arrastra el marcador 📍 para seleccionar la ubicación exacta del lugar',
                style: TextStyle(fontSize: 14),
                textAlign: TextAlign.center,
              ),
            ),
          ),

          // Botón de mi ubicación
          Positioned(
            bottom: 100,
            right: 16,
            child: FloatingActionButton(
              onPressed: _cargandoUbicacion ? null : _obtenerUbicacionActual,
              backgroundColor: Colors.white,
              child: _cargandoUbicacion
                  ? const SizedBox(
                      width: 24,
                      height: 24,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        valueColor: AlwaysStoppedAnimation<Color>(Colors.red),
                      ),
                    )
                  : const Icon(Icons.my_location, color: Colors.red),
            ),
          ),

          // Botón de confirmar
          Positioned(
            bottom: 20,
            left: 16,
            right: 16,
            child: ElevatedButton.icon(
              onPressed: () {
                widget.onUbicacionSeleccionada(
                  _posicionActual.latitude,
                  _posicionActual.longitude,
                );
                Navigator.pop(context);
              },
              icon: const Icon(Icons.check_circle),
              label: const Text(
                'Confirmar ubicación',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                elevation: 4,
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _mapController?.dispose();
    super.dispose();
  }
}
