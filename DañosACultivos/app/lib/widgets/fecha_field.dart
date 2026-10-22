import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import 'common.dart';

/// Selector de fecha. Si [clearable] es true permite dejarla vacía (campos opcionales).
class FechaField extends StatelessWidget {
  final DateTime? value;
  final ValueChanged<DateTime?> onChanged;
  final bool clearable;
  final DateTime? firstDate;
  final DateTime? lastDate;
  const FechaField({super.key, required this.value, required this.onChanged, this.clearable = false, this.firstDate, this.lastDate});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(10),
      onTap: () async {
        final r = await showDatePicker(
          context: context,
          initialDate: value ?? DateTime.now(),
          firstDate: firstDate ?? DateTime(2020),
          lastDate: lastDate ?? DateTime(2100),
        );
        if (r != null) onChanged(r);
      },
      child: InputDecorator(
        decoration: InputDecoration(
          suffixIcon: clearable && value != null
              ? IconButton(icon: const Icon(Icons.close, size: 18), onPressed: () => onChanged(null))
              : const Icon(Icons.calendar_month_outlined, size: 20),
        ),
        child: Text(value == null ? 'Sin fecha' : DateFormat('dd/MM/yyyy').format(value!), style: TextStyle(fontSize: 14, color: value == null ? const Color(0xFF8AA196) : null)),
      ),
    );
  }
}

/// yyyy-MM-dd o '' si no hay fecha.
String fechaIso(DateTime? d) => d == null ? '' : DateFormat('yyyy-MM-dd').format(d);

/// Semana ISO 8601 de una fecha (igual que el servidor).
int semanaIso(DateTime d) {
  final u = DateTime.utc(d.year, d.month, d.day);
  final jueves = u.add(Duration(days: 4 - u.weekday));
  return jueves.difference(DateTime.utc(jueves.year, 1, 1)).inDays ~/ 7 + 1;
}

/// Mensaje de error del período de monitoreo (ambas fechas o ninguna; el fin no puede ser anterior al inicio).
String? validarPeriodo(DateTime? inicio, DateTime? fin) {
  if ((inicio == null) != (fin == null)) return 'Indica el inicio y el fin del período de monitoreo.';
  if (inicio != null && fin != null && fin.isBefore(inicio)) return 'El fin del monitoreo no puede ser anterior al inicio.';
  return null;
}

/// Fecha de informe + período de monitoreo, compartidos por los tres formularios.
class PeriodoSection extends StatelessWidget {
  final DateTime informe;
  final DateTime? inicio;
  final DateTime? fin;
  final ValueChanged<DateTime> onInforme;
  final ValueChanged<DateTime?> onInicio;
  final ValueChanged<DateTime?> onFin;
  final String informeLabel;
  const PeriodoSection({
    super.key,
    required this.informe,
    required this.inicio,
    required this.fin,
    required this.onInforme,
    required this.onInicio,
    required this.onFin,
    this.informeLabel = 'Fecha de informe *',
  });

  @override
  Widget build(BuildContext context) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      LabeledField(label: informeLabel, hint: 'Semana ${semanaIso(informe)} del año (se guarda automáticamente).', child: FechaField(value: informe, onChanged: (d) => onInforme(d!))),
      LabeledField(label: 'Monitoreo: inicio', child: FechaField(value: inicio, clearable: true, onChanged: onInicio)),
      LabeledField(label: 'Monitoreo: fin', child: FechaField(value: fin, clearable: true, onChanged: onFin)),
    ]);
  }
}
