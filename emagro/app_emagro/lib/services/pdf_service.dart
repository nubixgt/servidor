import 'dart:io';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';
import 'package:path_provider/path_provider.dart';
import 'package:open_filex/open_filex.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import '../models/item_carrito.dart';

class PdfService {
  /// Genera un PDF de la nota de envío
  Future<Map<String, dynamic>> generarNotaEnvioPDF({
    required String numeroNota,
    required String fecha,
    required String vendedor,
    required String clienteNombre,
    required String nit,
    required String direccion,
    required List<ItemCarrito> productos,
    required double subtotal,
    required double descuentoTotal,
    required double total,
    int? diasCredito,
  }) async {
    try {
      final pdf = pw.Document();
      
      // Cargar logo
      final logoBytes = await rootBundle.load('assets/images/logo_emagro.png');
      final logoImage = pw.MemoryImage(logoBytes.buffer.asUint8List());
      
      // Parsear fecha
      final fechaParsed = DateTime.parse(fecha);
      final dia = fechaParsed.day.toString();
      final mes = DateFormat('MMMM', 'es').format(fechaParsed);
      final anio = fechaParsed.year.toString();
      
      pdf.addPage(
        pw.Page(
          pageFormat: PdfPageFormat.letter,
          margin: const pw.EdgeInsets.all(40),
          build: (pw.Context context) {
            return pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                // Header
                _buildHeader(logoImage, numeroNota),
                pw.SizedBox(height: 20),
                pw.Divider(thickness: 2),
                pw.SizedBox(height: 20),
                
                // Información del cliente y fecha
                _buildClienteInfo(clienteNombre, direccion, nit, dia, mes, anio),
                pw.SizedBox(height: 20),
                
                if (diasCredito != null && diasCredito > 0) ...[
                  pw.Center(
                    child: pw.Text(
                      '**Esta venta tiene $diasCredito días de crédito',
                      style: pw.TextStyle(
                        fontWeight: pw.FontWeight.bold,
                        fontSize: 14,
                      ),
                    ),
                  ),
                  pw.SizedBox(height: 20),
                ],

                // Tabla de productos
                _buildTablaProductos(productos, descuentoTotal, total),
                pw.SizedBox(height: 30),
                
                // Firmas
                _buildFirmas(clienteNombre, vendedor),
              ],
            );
          },
        ),
      );
      
      // Guardar PDF
      final output = await getApplicationDocumentsDirectory();
      final file = File('${output.path}/nota_envio_$numeroNota.pdf');
      await file.writeAsBytes(await pdf.save());
      
      // Abrir PDF
      await OpenFilex.open(file.path);
      
      return {
        'success': true,
        'message': 'PDF generado exitosamente',
        'path': file.path,
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Error al generar PDF: $e',
      };
    }
  }
  
  pw.Widget _buildHeader(pw.MemoryImage logo, String numeroNota) {
    return pw.Row(
      mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
      crossAxisAlignment: pw.CrossAxisAlignment.start,
      children: [
        // Logo
        pw.Image(logo, width: 120, height: 70),
        
        // Título y número
        pw.Column(
          crossAxisAlignment: pw.CrossAxisAlignment.end,
          children: [
            pw.Text(
              'NOTA DE ENVÍO',
              style: pw.TextStyle(
                fontSize: 24,
                fontWeight: pw.FontWeight.bold,
                color: PdfColor.fromHex('#3949AB'),
              ),
            ),
            pw.Text(
              numeroNota,
              style: pw.TextStyle(
                fontSize: 32,
                fontWeight: pw.FontWeight.bold,
                color: PdfColors.red,
              ),
            ),
          ],
        ),
        
        // Logo EM
        pw.Container(
          width: 60,
          height: 60,
          decoration: pw.BoxDecoration(
            color: PdfColor.fromHex('#4CAF50'),
            shape: pw.BoxShape.circle,
          ),
          child: pw.Center(
            child: pw.Text(
              'EM',
              style: pw.TextStyle(
                color: PdfColors.white,
                fontSize: 24,
                fontWeight: pw.FontWeight.bold,
              ),
            ),
          ),
        ),
      ],
    );
  }
  
  pw.Widget _buildClienteInfo(
    String clienteNombre,
    String direccion,
    String nit,
    String dia,
    String mes,
    String anio,
  ) {
    return pw.Row(
      crossAxisAlignment: pw.CrossAxisAlignment.start,
      children: [
        pw.Expanded(
          flex: 2,
          child: pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.start,
            children: [
              _buildInfoRow('CLIENTE:', clienteNombre),
              pw.SizedBox(height: 8),
              _buildInfoRow('DIRECCIÓN:', direccion),
              pw.SizedBox(height: 8),
              _buildInfoRow('Código/NIT:', nit),
            ],
          ),
        ),
        pw.SizedBox(width: 20),
        pw.Expanded(
          child: pw.Container(
            padding: const pw.EdgeInsets.all(12),
            decoration: pw.BoxDecoration(
              border: pw.Border.all(color: PdfColors.grey400),
              borderRadius: pw.BorderRadius.circular(8),
            ),
            child: pw.Column(
              children: [
                pw.Text(
                  'FECHA',
                  style: pw.TextStyle(
                    fontWeight: pw.FontWeight.bold,
                    fontSize: 16,
                  ),
                ),
                pw.SizedBox(height: 8),
                pw.Row(
                  mainAxisAlignment: pw.MainAxisAlignment.spaceEvenly,
                  children: [
                    _buildFechaBox(dia),
                    _buildFechaBox(mes),
                    _buildFechaBox(anio),
                  ],
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
  
  pw.Widget _buildInfoRow(String label, String value) {
    return pw.Row(
      crossAxisAlignment: pw.CrossAxisAlignment.start,
      children: [
        pw.SizedBox(
          width: 100,
          child: pw.Text(
            label,
            style: pw.TextStyle(fontWeight: pw.FontWeight.bold),
          ),
        ),
        pw.Expanded(
          child: pw.Text(value),
        ),
      ],
    );
  }
  
  pw.Widget _buildFechaBox(String text) {
    return pw.Container(
      padding: const pw.EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: pw.BoxDecoration(
        border: pw.Border.all(color: PdfColors.grey400),
        borderRadius: pw.BorderRadius.circular(4),
      ),
      child: pw.Text(
        text,
        style: const pw.TextStyle(fontSize: 12),
      ),
    );
  }
  
  pw.Widget _buildTablaProductos(
    List<ItemCarrito> productos,
    double descuentoTotal,
    double total,
  ) {
    return pw.Table(
      border: pw.TableBorder.all(color: PdfColors.grey400),
      columnWidths: {
        0: const pw.FlexColumnWidth(1),
        1: const pw.FlexColumnWidth(3),
        2: const pw.FlexColumnWidth(2),
        3: const pw.FlexColumnWidth(2),
        4: const pw.FlexColumnWidth(2),
        5: const pw.FlexColumnWidth(2),
      },
      children: [
        // Header
        pw.TableRow(
          decoration: pw.BoxDecoration(
            color: PdfColor.fromHex('#4CAF50'),
          ),
          children: [
            _buildTableHeader('CANTIDAD'),
            _buildTableHeader('DESCRIPCIÓN'),
            _buildTableHeader('PRESENTACIÓN'),
            _buildTableHeader('PRECIO'),
            _buildTableHeader('DESCUENTO'),
            _buildTableHeader('VALOR TOTAL'),
          ],
        ),
        
        // Productos
        ...productos.map((item) => pw.TableRow(
          decoration: item.esBonificacion 
            ? pw.BoxDecoration(color: PdfColor.fromHex('#FFE0B2'))
            : null,
          children: [
            _buildTableCell(item.cantidad.toString()),
            _buildTableCell(
              item.esBonificacion 
                ? '${item.producto} (BONIFICACIÓN)' 
                : item.producto
            ),
            _buildTableCell(item.presentacion),
            _buildTableCell(
              item.esBonificacion 
                ? 'Q0.00' 
                : 'Q${item.precioUnitario.toStringAsFixed(0)}'
            ),
            _buildTableCell(item.esBonificacion 
                ? 'Q${(item.precioUnitario * item.cantidad).toStringAsFixed(0)}' 
                : (item.descuento > 0 ? 'Q${item.descuento.toStringAsFixed(0)}' : '')),
            _buildTableCell(
              item.esBonificacion 
                ? 'Q0.00' 
                : 'Q${item.total.toStringAsFixed(0)}'
            ),
          ],
        )),
        
        // Totales
        pw.TableRow(
          decoration: const pw.BoxDecoration(
            color: PdfColors.grey300,
          ),
          children: [
            pw.Container(),
            pw.Container(),
            pw.Container(),
            _buildTableCell('TOTALES', bold: true),
            _buildTableCell('Q${descuentoTotal.toStringAsFixed(0)}', bold: true),
            _buildTableCell('Q${total.toStringAsFixed(0)}', bold: true),
          ],
        ),
      ],
    );
  }
  
  pw.Widget _buildTableHeader(String text) {
    return pw.Padding(
      padding: const pw.EdgeInsets.all(8.0),
      child: pw.Text(
        text,
        textAlign: pw.TextAlign.center,
        style: pw.TextStyle(
          fontWeight: pw.FontWeight.bold,
          color: PdfColors.white,
          fontSize: 12,
        ),
      ),
    );
  }
  
  pw.Widget _buildTableCell(String text, {bool bold = false}) {
    return pw.Padding(
      padding: const pw.EdgeInsets.all(8.0),
      child: pw.Text(
        text,
        textAlign: pw.TextAlign.center,
        style: pw.TextStyle(
          fontWeight: bold ? pw.FontWeight.bold : pw.FontWeight.normal,
          fontSize: 12,
        ),
      ),
    );
  }
  
  pw.Widget _buildFirmas(String clienteNombre, String vendedor) {
    return pw.Row(
      children: [
        pw.Expanded(
          child: pw.Column(
            children: [
              pw.Container(
                height: 1,
                color: PdfColors.grey400,
                margin: const pw.EdgeInsets.only(bottom: 8),
              ),
              pw.Text(
                'RECIBIDO POR',
                style: const pw.TextStyle(fontSize: 12),
              ),
              pw.Text(
                clienteNombre,
                style: pw.TextStyle(
                  fontSize: 16,
                  fontWeight: pw.FontWeight.bold,
                  color: PdfColor.fromHex('#3949AB'),
                ),
              ),
            ],
          ),
        ),
        pw.SizedBox(width: 40),
        pw.Expanded(
          child: pw.Column(
            children: [
              pw.Container(
                height: 1,
                color: PdfColors.grey400,
                margin: const pw.EdgeInsets.only(bottom: 8),
              ),
              pw.Text(
                'ENTREGADO POR',
                style: const pw.TextStyle(fontSize: 12),
              ),
              pw.Text(
                vendedor,
                style: pw.TextStyle(
                  fontSize: 16,
                  fontWeight: pw.FontWeight.bold,
                  color: PdfColor.fromHex('#3949AB'),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
