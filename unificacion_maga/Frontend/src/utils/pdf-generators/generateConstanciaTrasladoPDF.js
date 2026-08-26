import { jsPDF } from 'jspdf';
import logoMaga from '@/assets/images/maga_logo1.png';

const loadImage = (src) => {
    return new Promise((resolve) => {
        const img = new Image();
        img.src = src;
        img.onload = () => resolve(img);
        img.onerror = () => {
            console.warn(`Could not load image: ${src}`);
            resolve(null);
        };
    });
};

export const generateConstanciaTrasladoPDF = async (data) => {
    try {
        const doc = new jsPDF();
        
        // Configuración de página
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const margin = 20;
        const contentWidth = pageWidth - (margin * 2);

        // ============================================
        // Logo MAGA (izquierda)
        // ============================================
        const logoImg = await loadImage(logoMaga);
        if (logoImg) {
            doc.addImage(logoImg, 'PNG', margin, 10, 60, 30);
        }

        // ============================================
        // Encabezado (derecha)
        // ============================================
        doc.setFontSize(10);
        doc.setFont('helvetica', 'bold');
        const headerText = 'Viceministerio de Desarrollo Económico Rural';
        const headerWidth = doc.getTextWidth(headerText);
        doc.text(headerText, pageWidth - margin - headerWidth, 25);

        // ============================================
        // Título
        // ============================================
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        const titulo = 'CONSTANCIA DE TRASLADO AL INTERIOR DE LA REPÚBLICA';
        const tituloWidth = doc.getTextWidth(titulo);
        doc.text(titulo, (pageWidth - tituloWidth) / 2, 40);

        // ============================================
        // Información del Comisionado
        // ============================================
        let yPos = 50;
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');

        // Nombre
        doc.text('Nombre:', margin, yPos);
        doc.setFont('helvetica', 'bold');
        doc.text(`${data.nombres_comisionado || ''} ${data.apellidos_comisionado || ''}`, margin + 25, yPos);

        // Tipo de Servicios
        yPos += 8;
        doc.setFont('helvetica', 'normal');
        doc.text('Tipo de Servicios:', margin, yPos);
        doc.setFont('helvetica', 'bold');
        doc.text(data.tipo_servicio || '', margin + 38, yPos);

        // Entidad
        yPos += 8;
        doc.setFont('helvetica', 'normal');
        doc.text('Entidad: VICEMINISTERIO DE DESARROLLO ECONOMICO RURAL', margin, yPos);

        // ============================================
        // Tabla
        // ============================================
        yPos += 10;
        const tableStartY = yPos;
        const rowHeight = 20;
        const numRows = 6;

        const columns = [
            { header: 'Lugar a Visitar', width: 40 },
            { header: 'Hora', width: 17.5 },
            { header: 'Fecha', width: 17.5 },
            { header: 'Hora', width: 17.5 },
            { header: 'Fecha', width: 17.5 },
            { header: 'Autorizado\nPor', width: 25 },
            { header: 'Firma y Sello', width: 35 }
        ];

        // Encabezados principales
        doc.setFontSize(8);
        doc.setFont('helvetica', 'bold');

        let xPos = margin;
        
        // Primera fila de encabezados
        doc.rect(xPos, yPos, columns[0].width, 10);
        doc.text('Lugar a Visitar', xPos + 2, yPos + 7);
        xPos += columns[0].width;

        // Ingreso
        doc.rect(xPos, yPos, columns[1].width + columns[2].width, 5);
        doc.text('Ingreso', xPos + 12, yPos + 4);
        xPos += columns[1].width + columns[2].width;

        // Salida
        doc.rect(xPos, yPos, columns[3].width + columns[4].width, 5);
        doc.text('Salida', xPos + 14, yPos + 4);
        xPos += columns[3].width + columns[4].width;

        // Autorizado Por
        doc.rect(xPos, yPos, columns[5].width, 10);
        doc.text('Autorizado', xPos + 5, yPos + 5);
        doc.text('Por', xPos + 8, yPos + 8);
        xPos += columns[5].width;

        // Firma y Sello
        doc.rect(xPos, yPos, columns[6].width, 10);
        doc.text('Firma y Sello', xPos + 7, yPos + 7);

        // Segunda fila de encabezados (Hora/Fecha)
        yPos += 5;
        xPos = margin + columns[0].width;

        // Ingreso - Hora
        doc.rect(xPos, yPos, columns[1].width, 5);
        doc.text('Hora', xPos + 3, yPos + 4);
        xPos += columns[1].width;

        // Ingreso - Fecha
        doc.rect(xPos, yPos, columns[2].width, 5);
        doc.text('Fecha', xPos + 5, yPos + 4);
        xPos += columns[2].width;

        // Salida - Hora
        doc.rect(xPos, yPos, columns[3].width, 5);
        doc.text('Hora', xPos + 3, yPos + 4);
        xPos += columns[3].width;

        // Salida - Fecha
        doc.rect(xPos, yPos, columns[4].width, 5);
        doc.text('Fecha', xPos + 5, yPos + 4);

        // Filas de datos (vacías)
        yPos += 5;
        doc.setFont('helvetica', 'normal');
        for (let i = 0; i < numRows; i++) {
            xPos = margin;
            for (let j = 0; j < columns.length; j++) {
                doc.rect(xPos, yPos, columns[j].width, rowHeight);
                xPos += columns[j].width;
            }
            yPos += rowHeight;
        }

        // ============================================
        // Observaciones
        // ============================================
        yPos += 5;
        doc.setFont('helvetica', 'bold');
        doc.text('Observaciones:', margin, yPos);
        
        yPos += 5;
        doc.setFont('helvetica', 'normal');
        for (let i = 0; i < 3; i++) {
            doc.line(margin, yPos, pageWidth - margin, yPos);
            yPos += 7;
        }

        // ============================================
        // Líneas de firma
        // ============================================
        yPos += 15;
        const firmaWidth = 60;
        const firma1X = margin + 10;
        const firma2X = pageWidth - margin - firmaWidth - 10;

        doc.line(firma1X, yPos, firma1X + firmaWidth, yPos);
        doc.text('Firma del Comisionado', firma1X + 10, yPos + 5);

        doc.line(firma2X, yPos, firma2X + firmaWidth, yPos);
        doc.text('Vo.Bo. Jefe Inmediato', firma2X + 10, yPos + 5);

        // Guardar PDF
        const nombreArchivo = `Constancia_Traslado_${data.id || 'borrador'}.pdf`;
        doc.save(nombreArchivo);

    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Error al generar el PDF: ' + error.message);
    }
};
