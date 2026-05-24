import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../theme/app_theme.dart';

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final metrics = [
      {'label': 'INGRESOS', 'val': 'Q4.2M', 'icon': Icons.attach_money},
      {'label': 'GASTOS', 'val': 'Q2.8M', 'icon': Icons.receipt_long},
      {'label': 'FLOTA', 'val': '124', 'icon': Icons.fire_truck},
      {'label': 'SEGURIDAD', 'val': '98.2', 'icon': Icons.health_and_safety},
    ];

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
              childAspectRatio: 1.2,
            ),
            itemCount: metrics.length,
            itemBuilder: (context, index) {
              final m = metrics[index];
              return GlassCard(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: AppTheme.primaryColor.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(m['icon'] as IconData, color: AppTheme.primaryColor),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(m['label'] as String, style: const TextStyle(fontSize: 10, color: Colors.white54, fontWeight: FontWeight.bold, letterSpacing: 2)),
                        Text(m['val'] as String, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900)),
                      ],
                    ),
                  ],
                ),
              ).animate().scale(delay: (index * 100).ms, duration: 400.ms, curve: Curves.easeOutBack);
            },
          ),
          
          const SizedBox(height: 24),
          const Text('ALERTAS DE INVENTARIO', style: TextStyle(fontWeight: FontWeight.w900, letterSpacing: 1.5, fontSize: 12, color: Colors.white54)),
          const SizedBox(height: 16),
          
          GlassCard(
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                _buildAlertRow('Acero Estructural (Viga H)', 'Proyecto Delta', true),
                const Divider(color: Colors.white12, height: 30),
                _buildAlertRow('Concreto Premezclado', 'Torre Skyline', false),
              ],
            ),
          ).animate().slideX(begin: 0.1, end: 0, delay: 500.ms, curve: Curves.easeOutQuad).fadeIn(),
        ],
      ),
    );
  }

  Widget _buildAlertRow(String title, String desc, bool critical) {
    return Row(
      children: [
        Icon(critical ? Icons.warning_amber_rounded : Icons.info_outline, color: critical ? AppTheme.tertiaryColor : AppTheme.primaryColor),
        const SizedBox(width: 16),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
              Text(desc, style: const TextStyle(color: Colors.white54, fontSize: 12)),
            ],
          ),
        ),
      ],
    );
  }
}
