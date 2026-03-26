import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

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

  @override
  void initState() {
    super.initState();
    if (widget.latitudInicial != null && widget.longitudInicial != null) {
      _selectedLocation = LatLng(widget.latitudInicial!, widget.longitudInicial!);
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
      body: GoogleMap(
        initialCameraPosition: CameraPosition(
          target: _selectedLocation ?? const LatLng(14.6349, -90.5069), // Guatemala City
          zoom: 12,
        ),
        onTap: (latLng) {
          setState(() => _selectedLocation = latLng);
        },
        markers: _selectedLocation != null
            ? {
                Marker(
                  markerId: const MarkerId('selected'),
                  position: _selectedLocation!,
                ),
              }
            : {},
      ),
    );
  }
}
