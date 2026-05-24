import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../theme/app_theme.dart';

class InventoryScreen extends StatelessWidget {
  const InventoryScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final List<Map<String, dynamic>> inventory = [
      {'item': 'Cemento Portland Gris', 'stock': '1,200 Sacos', 'status': 'Óptimo', 'icon': Icons.category_outlined, 'color': Colors.green},
      {'item': 'Arena de Río', 'stock': '350 m³', 'status': 'Alerta', 'icon': Icons.landscape_outlined, 'color': Colors.orange},
      {'item': 'Piedrín Triturado', 'stock': '800 m³', 'status': 'Óptimo', 'icon': Icons.terrain_outlined, 'color': Colors.green},
      {'item': 'Aditivo Acelerante', 'stock': '15 Canecas', 'status': 'Crítico', 'icon': Icons.water_drop_outlined, 'color': Colors.red},
      {'item': 'Acero Estructural (Viga H)', 'stock': '45 Unidades', 'status': 'Crítico', 'icon': Icons.view_headline_outlined, 'color': Colors.red},
    ];

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: inventory.length,
      separatorBuilder: (_, __) => const SizedBox(height: 16),
      itemBuilder: (context, index) {
        final item = inventory[index];
        return GlassCard(
          padding: const EdgeInsets.all(20),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppTheme.primaryColor.withOpacity(0.15),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(item['icon'] as IconData, color: AppTheme.primaryColor),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(item['item'] as String, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    const SizedBox(height: 4),
                    Text('Stock Actual: ${item['stock']}', style: const TextStyle(color: Colors.white54, fontSize: 12)),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: (item['color'] as Color).withOpacity(0.2),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: (item['color'] as Color).withOpacity(0.3)),
                ),
                child: Text(
                  (item['status'] as String).toUpperCase(),
                  style: TextStyle(
                    fontSize: 8,
                    fontWeight: FontWeight.w900,
                    color: item['color'] as Color,
                    letterSpacing: 1,
                  ),
                ),
              ),
            ],
          ),
        ).animate().slideX(begin: 0.2, end: 0, delay: (index * 100).ms, curve: Curves.easeOutQuad).fadeIn();
      },
    );
  }
}
