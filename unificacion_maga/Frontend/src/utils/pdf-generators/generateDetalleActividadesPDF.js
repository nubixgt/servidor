import { jsPDF } from 'jspdf';

export const generateDetalleActividadesPDF = (data) => {
    const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'letter'
    });

    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 20;

    // ============================================
    // Título
    // ============================================
    let yPos = 30;
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    const titulo = 'DETALLE DE ACTIVIDADES REALIZADAS';
    const tituloWidth = doc.getTextWidth(titulo);
    doc.text(titulo, (pageWidth - tituloWidth) / 2, yPos);

    yPos += 15;

    // ============================================
    // Información del comisionado
    // ============================================
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    
    // PERSONA DESIGNADA
    doc.setFont('helvetica', 'bold');
    doc.text('PERSONA DESIGNADA:', margin, yPos);
    doc.setFont('helvetica', 'normal');
    const nombres = data.nombres_comisionado || '';
    const apellidos = data.apellidos_comisionado || '';
    doc.text(`${nombres} ${apellidos}`, margin + 55, yPos);
    
    yPos += 8;

    // TIPO DE COMISIÓN
    doc.setFont('helvetica', 'bold');
    doc.text('TIPO DE COMISIÓN:', margin, yPos);
    doc.setFont('helvetica', 'normal');
    
    const actividad = data.actividad_realizar || '';
    const actividadLines = doc.splitTextToSize(actividad, pageWidth - margin - 55 - margin);
    doc.text(actividadLines, margin + 55, yPos);
    yPos += (actividadLines.length * 6) + 2;

    // LUGAR
    doc.setFont('helvetica', 'bold');
    doc.text('LUGAR:', margin, yPos);
    doc.setFont('helvetica', 'normal');
    
    const lugar = data.lugares_visitar || '';
    const lugarLines = doc.splitTextToSize(lugar, pageWidth - margin - 55 - margin);
    doc.text(lugarLines, margin + 55, yPos);
    yPos += (lugarLines.length * 6) + 2;

    // FECHA (Días)
    doc.setFont('helvetica', 'bold');
    doc.text('FECHA:', margin, yPos);
    doc.setFont('helvetica', 'normal');
    doc.text(`${data.dias_comision || 0}`, margin + 55, yPos);

    yPos += 15;

    // ============================================
    // Tabla de actividades
    // ============================================
    const tableStartY = yPos;
    const colWidths = [30, 50, 90]; // FECHA, LUGAR, ACTIVIDAD
    const tableWidth = colWidths.reduce((a, b) => a + b, 0);
    const tableX = (pageWidth - tableWidth) / 2;
    const rowHeight = 40;

    // Encabezados
    doc.setFillColor(255, 255, 255);
    doc.setDrawColor(0, 0, 0);
    doc.setLineWidth(0.3);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(9);

    let xPos = tableX;
    
    // FECHA
    doc.rect(xPos, yPos, colWidths[0], 8, 'S');
    doc.text('FECHA', xPos + colWidths[0]/2, yPos + 5, { align: 'center' });
    xPos += colWidths[0];

    // LUGAR
    doc.rect(xPos, yPos, colWidths[1], 8, 'S');
    doc.text('LUGAR', xPos + colWidths[1]/2, yPos + 5, { align: 'center' });
    xPos += colWidths[1];

    // ACTIVIDAD
    doc.rect(xPos, yPos, colWidths[2], 8, 'S');
    doc.text('ACTIVIDAD', xPos + colWidths[2]/2, yPos + 5, { align: 'center' });

    yPos += 8;

    // Fila vacía para llenar manualmente
    xPos = tableX;
    doc.rect(xPos, yPos, colWidths[0], rowHeight, 'S');
    xPos += colWidths[0];
    doc.rect(xPos, yPos, colWidths[1], rowHeight, 'S');
    xPos += colWidths[1];
    doc.rect(xPos, yPos, colWidths[2], rowHeight, 'S');

    yPos += rowHeight + 10;

    // ============================================
    // Líneas de firma
    // ============================================
    const lineY = yPos + 10; 
    const lineWidth = 70;
    const leftLineX = margin + 10;
    const rightLineX = pageWidth - margin - lineWidth - 10;

    doc.setLineWidth(0.3);
    doc.line(leftLineX, lineY, leftLineX + lineWidth, lineY);
    doc.line(rightLineX, lineY, rightLineX + lineWidth, lineY);

    // Texto VoBo a la izquierda de la línea derecha
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.text('VoBo', rightLineX - 10, lineY, { align: 'left' });

    // Datos del comisionado debajo de la línea izquierda
    doc.setFontSize(8);
    const nombreCompleto = `${nombres} ${apellidos}`;
    
    // Centrar texto con respecto a la línea
    const centerX = leftLineX + lineWidth/2;
    
    doc.text(nombreCompleto, centerX, lineY + 5, { align: 'center' });
    doc.text(data.puesto_funcional || '', centerX, lineY + 9, { align: 'center' });
    doc.text(data.ubicacion_laboral || '', centerX, lineY + 13, { align: 'center' });

    // Guardar PDF
    const nombreArchivo = `Detalle_Actividades_${nombres}_${apellidos}.pdf`;
    doc.save(nombreArchivo);
};
