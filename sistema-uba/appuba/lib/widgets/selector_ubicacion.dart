import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:geolocator/geolocator.dart';

class SelectorUbicacion extends StatefulWidget {
  final double? latitudInicial;
  final double? longitudInicial;
  final Function(double, double) onUbicacionSeleccionada;

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
  LatLng? _selectedLocation;
  GoogleMapController? _mapController;
  bool _loadingLocation = false;

  @override
  void initState() {
    super.initState();
    if (widget.latitudInicial != null && widget.longitudInicial != null) {
      _selectedLocation = LatLng(widget.latitudInicial!, widget.longitudInicial!);
    } else {
      _determinarPosicionActual();
    }
  }

  Future<void> _determinarPosicionActual() async {
    setState(() => _loadingLocation = true);
    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        throw 'Los servicios de ubicación están desactivados.';
      }

      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          throw 'Permisos de ubicación denegados.';
        }
      }

      if (permission == LocationPermission.deniedForever) {
        throw 'Los permisos de ubicación están denegados permanentemente.';
      }

      Position position = await Geolocator.getCurrentPosition();
      LatLng currentLatLng = LatLng(position.latitude, position.longitude);
      
      setState(() {
        _selectedLocation = currentLatLng;
        _loadingLocation = false;
      });

      if (_mapController != null) {
        _mapController!.animateCamera(CameraUpdate.newLatLngZoom(currentLatLng, 15));
      }
    } catch (e) {
      debugPrint('Error obteniendo ubicación: $e');
      setState(() => _loadingLocation = false);
      // Mantener ubicación por defecto si falla
      if (_selectedLocation == null) {
        _selectedLocation = const LatLng(14.6349, -90.5069); // Ciudad de Guatemala
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Seleccionar Ubicación'),
        actions: [
          if (_selectedLocation != null)
            IconButton(
              icon: const Icon(Icons.check),
              onPressed: () {
                widget.onUbicacionSeleccionada(
                  _selectedLocation!.latitude,
                  _selectedLocation!.longitude,
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
              target: _selectedLocation ?? const LatLng(14.6349, -90.5069),
              zoom: 15,
            ),
            onMapCreated: (controller) => _mapController = controller,
            onTap: (latLng) {
              setState(() => _selectedLocation = latLng);
            },
            myLocationEnabled: true,
            myLocationButtonEnabled: true,
            markers: _selectedLocation != null
                ? {
                    Marker(
                      markerId: const MarkerId('selected'),
                      position: _selectedLocation!,
                    ),
                  }
                : {},
          ),
          if (_loadingLocation)
            const Center(
              child: Card(
                child: Padding(
                  padding: EdgeInsets.all(16.0),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      CircularProgressIndicator(),
                      SizedBox(height: 16),
                      Text('Obteniendo ubicación actual...'),
                    ],
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}
