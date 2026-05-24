import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../theme/app_theme.dart';

class FinanceScreen extends StatelessWidget {
  const FinanceScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final List<Map<String, dynamic>> transactions = [
      {'concept': 'Pago Proveedor Aceros', 'amount': '-Q45,000.00', 'date': '24/05/2026', 'type': 'expense'},
      {'CFDI': 'FAC-9821'},
      {'concept': 'Cobro Factura Skyline', 'amount': '+Q120,500.00', 'date': '22/05/2026', 'type': 'income'},
      {'CFDI': 'FAC-3312'},
      {'concept': 'Nómina Quincenal', 'amount': '-Q85,200.00', 'date': '15/05/2026', 'type': 'expense'},
      {'CFDI': 'NOM-001'},
    ];

    // Filter out only transactions for the list
    final filtered = transactions.where((t) => t.containsKey('concept')).toList();

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Balance general
          GlassCard(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('BALANCE ACTUAL', style: TextStyle(color: Colors.white54, fontSize: 12, fontWeight: FontWeight.w900, letterSpacing: 2)),
                    Icon(Icons.account_balance_wallet_outlined, color: AppTheme.primaryColor.withOpacity(0.5)),
                  ],
                ),
                const SizedBox(height: 8),
                const Text('Q 1,450,230.00', style: TextStyle(fontSize: 32, fontWeight: FontWeight.w900)),
                const SizedBox(height: 20),
                Row(
                  children: [
                    Expanded(
                      child: _buildMiniStat(Icons.arrow_upward, 'INGRESOS (MES)', 'Q 450K', Colors.green),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: _buildMiniStat(Icons.arrow_downward, 'GASTOS (MES)', 'Q 280K', Colors.red),
                    ),
                  ],
                )
              ],
            ),
          ).animate().slideY(begin: -0.1, end: 0, curve: Curves.easeOutCubic).fadeIn(),

          const SizedBox(height: 30),
          const Text('ÚLTIMOS MOVIMIENTOS', style: TextStyle(fontWeight: FontWeight.w900, letterSpacing: 1.5, fontSize: 12, color: Colors.white54)),
          const SizedBox(height: 16),

          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: filtered.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final t = filtered[index];
              final isIncome = t['type'] == 'income';
              final color = isIncome ? Colors.green : Colors.red;

              return GlassCard(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: color.withOpacity(0.15),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        isIncome ? Icons.call_received : Icons.call_made,
                        color: color,
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(t['concept'], style: const TextStyle(fontWeight: FontWeight.bold)),
                          Text(t['date'], style: const TextStyle(color: Colors.white54, fontSize: 12)),
                        ],
                      ),
                    ),
                    Text(
                      t['amount'],
                      style: TextStyle(
                        fontWeight: FontWeight.w900,
                        color: color,
                        fontSize: 16,
                      ),
                    ),
                  ],
                ),
              ).animate().slideX(begin: 0.1, end: 0, delay: (index * 100).ms, curve: Curves.easeOutCubic).fadeIn();
            },
          ),
        ],
      ),
    );
  }

  Widget _buildMiniStat(IconData icon, String label, String value, Color color) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withOpacity(0.05)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 14, color: color),
              const SizedBox(width: 4),
              Text(label, style: const TextStyle(fontSize: 8, color: Colors.white54, fontWeight: FontWeight.bold, letterSpacing: 1)),
            ],
          ),
          const SizedBox(height: 4),
          Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: color)),
        ],
      ),
    );
  }
}
