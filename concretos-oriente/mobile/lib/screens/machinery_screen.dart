import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../theme/app_theme.dart';

class MachineryScreen extends StatelessWidget {
  const MachineryScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final List<Map<String, dynamic>> machinery = [
      {'code': 'MIX-01', 'type': 'Camión Mezclador', 'status': 'Operativo', 'fuel': '85%', 'location': 'Planta Central'},
      {'code': 'BOM-04', 'type': 'Bomba de Concreto', 'status': 'En Mantenimiento', 'fuel': '10%', 'location': 'Taller Principal'},
      {'code': 'MIX-12', 'type': 'Camión Mezclador', 'status': 'En Ruta', 'fuel': '60%', 'location': 'Ruta 5 (Skyline)'},
      {'code': 'EXC-02', 'type': 'Excavadora Hidráulica', 'status': 'Operativo', 'fuel': '45%', 'location': 'Proyecto Eco-Tech'},
    ];

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: machinery.length,
      separatorBuilder: (_, __) => const SizedBox(height: 16),
      itemBuilder: (context, index) {
        final machine = machinery[index];
        final bool isMaintenance = machine['status'] == 'En Mantenimiento';
        final bool inRoute = machine['status'] == 'En Ruta';

        return GlassCard(
          padding: const EdgeInsets.all(20),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: isMaintenance ? Colors.red.withOpacity(0.15) : (inRoute ? Colors.blue.withOpacity(0.15) : AppTheme.primaryColor.withOpacity(0.15)),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: isMaintenance ? Colors.red.withOpacity(0.3) : Colors.transparent),
                ),
                child: Icon(
                  isMaintenance ? Icons.build_circle_outlined : (inRoute ? Icons.local_shipping_outlined : Icons.fire_truck_outlined),
                  color: isMaintenance ? Colors.red : (inRoute ? Colors.blue : AppTheme.primaryColor),
                  size: 32,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(machine['code'] as String, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 18, fontFamily: 'monospace')),
                        Text(
                          machine['status'] as String,
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w900,
                            color: isMaintenance ? Colors.red[400] : (inRoute ? Colors.blue[400] : Colors.green[400]),
                            letterSpacing: 1,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(machine['type'] as String, style: const TextStyle(color: Colors.white70)),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        const Icon(Icons.location_on_outlined, size: 14, color: Colors.white54),
                        const SizedBox(width: 4),
                        Text(machine['location'] as String, style: const TextStyle(fontSize: 12, color: Colors.white54)),
                        const Spacer(),
                        const Icon(Icons.local_gas_station_outlined, size: 14, color: Colors.white54),
                        const SizedBox(width: 4),
                        Text(machine['fuel'] as String, style: const TextStyle(fontSize: 12, color: Colors.white54)),
                      ],
                    )
                  ],
                ),
              ),
            ],
          ),
        ).animate().slideX(begin: -0.1, end: 0, delay: (index * 150).ms, curve: Curves.easeOutCubic).fadeIn();
      },
    );
  }
}
