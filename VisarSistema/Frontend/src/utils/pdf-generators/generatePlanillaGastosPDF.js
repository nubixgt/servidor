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

const formatDate = (dateString) => {
    if (!dateString) return '';
    let dateStr = dateString;
    if(dateStr.includes('T')) dateStr = dateStr.split('T')[0];
    const parts = dateStr.split('-');
    if(parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
    return dateStr;
};

export const generatePlanillaGastosPDF = async (data) => {
    try {
        const doc = new jsPDF('portrait');
        const pageWidth = doc.internal.pageSize.getWidth();
        const margin = 20;

        const logoImg = await loadImage(logoMaga);
        if (logoImg) {
            doc.addImage(logoImg, 'PNG', margin, 10, 50, 30);
        }

        // Header Right
        doc.setFontSize(9);
        doc.setFont('helvetica', 'bold');
        const headerText = 'Viceministerio de Desarrollo Económico Rural';
        const headerWidth = doc.getTextWidth(headerText);
        doc.text(headerText, pageWidth - margin - headerWidth, 20);

        // Titles
        let yPos = 50;
        doc.setFontSize(9);
        const titulo1 = 'PLANILLA DETALLADA DE LOS GASTOS EFECTUADOS EN EL TRASLADO';
        const titulo2 = 'AL INTERIOR DE LA REPÚBLICA';

        let tituloWidth = doc.getTextWidth(titulo1);
        doc.text(titulo1, (pageWidth - tituloWidth) / 2, yPos);
        yPos += 5;
        tituloWidth = doc.getTextWidth(titulo2);
        doc.text(titulo2, (pageWidth - tituloWidth) / 2, yPos);

        yPos += 10;

        // --- Table ---
        const tableStartY = yPos;
        const col1 = 12;
        const col2 = 35;
        const col3_1 = 20;
        const col3_2 = 20;
        const col4 = 25;
        const col5 = 25;
        const col6 = 30;

        const tableWidth = col1 + col2 + col3_1 + col3_2 + col4 + col5 + col6;
        const tableX = (pageWidth - tableWidth) / 2;

        doc.setLineWidth(0.2);
        doc.setFontSize(8);
        doc.setFont('helvetica', 'bold');

        let xPos = tableX;

        // Headers
        doc.rect(xPos, yPos, col1, 12);
        doc.text('No.', xPos + col1/2, yPos + 7, { align: 'center' });
        xPos += col1;

        doc.rect(xPos, yPos, col2, 12);
        doc.text('PROVEEDOR', xPos + col2/2, yPos + 7, { align: 'center' });
        xPos += col2;

        doc.rect(xPos, yPos, col3_1 + col3_2, 6);
        doc.text('FACTURA', xPos + (col3_1 + col3_2) / 2, yPos + 4, { align: 'center' });
        doc.rect(xPos, yPos + 6, col3_1, 6);
        doc.text('SERIE', xPos + col3_1/2, yPos + 10, { align: 'center' });
        doc.rect(xPos + col3_1, yPos + 6, col3_2, 6);
        doc.text('No.', xPos + col3_1 + col3_2/2, yPos + 10, { align: 'center' });
        xPos += col3_1 + col3_2;

        doc.rect(xPos, yPos, col4, 12);
        doc.text('FECHA', xPos + col4/2, yPos + 7, { align: 'center' });
        xPos += col4;

        doc.rect(xPos, yPos, col5, 12);
        doc.text('CONCEPTO', xPos + col5/2, yPos + 7, { align: 'center' });
        xPos += col5;

        doc.rect(xPos, yPos, col6, 12);
        doc.text('MONTO', xPos + col6/2, yPos + 4, { align: 'center' });
        doc.text('FACTURADO', xPos + col6/2, yPos + 9, { align: 'center' });

        yPos += 12;

        // Data Rows (Always 4)
        doc.setFont('helvetica', 'normal');
        
        // Ensure gastos is an array
        const gastos = Array.isArray(data.gastos) ? data.gastos : [];
        
        for (let i = 0; i < 4; i++) {
            xPos = tableX;
            const rowHeight = 8;
            const gasto = gastos[i];

            doc.rect(xPos, yPos, col1, rowHeight);
            doc.text((i + 1).toString(), xPos + col1/2, yPos + 5, { align: 'center' });
            xPos += col1;

            doc.rect(xPos, yPos, col2, rowHeight);
            // Proveedor hardcoded or not? The form doesn't have provider field. 
            // Assuming empty or generic if not in data. The form only has concept, date, total, invoices.
            // Leaving provider empty for now as it's not in the form data spec.
            xPos += col2;

            doc.rect(xPos, yPos, col3_1, rowHeight);
            if (gasto) doc.text(gasto.serie_factura || '', xPos + 2, yPos + 5);
            xPos += col3_1;

            doc.rect(xPos, yPos, col3_2, rowHeight);
            if (gasto) doc.text(gasto.numero_factura || '', xPos + 2, yPos + 5);
            xPos += col3_2;

            doc.rect(xPos, yPos, col4, rowHeight);
            if (gasto) {
                doc.text(formatDate(gasto.fecha), xPos + col4/2, yPos + 5, { align: 'center' });
            }
            xPos += col4;

            doc.rect(xPos, yPos, col5, rowHeight);
            if (gasto) doc.text(gasto.concepto || '', xPos + col5/2, yPos + 5, { align: 'center' });
            xPos += col5;

            doc.rect(xPos, yPos, col6, rowHeight);
            if (gasto) doc.text(parseFloat(gasto.total).toFixed(2), xPos + col6/2, yPos + 5, { align: 'center' });

            yPos += rowHeight;
        }

        // Total Row
        xPos = tableX;
        const totalRowHeight = 8;
        doc.setFont('helvetica', 'bold');
        const totalLabelWidth = col1 + col2 + col3_1 + col3_2 + col4 + col5;

        doc.rect(xPos, yPos, totalLabelWidth, totalRowHeight);
        doc.text('TOTAL EN LETRAS:', xPos + 2, yPos + 5);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(6);
        doc.text(data.monto_letras || '', xPos + 40, yPos + 5);
        xPos += totalLabelWidth;

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8);
        doc.rect(xPos, yPos, col6, totalRowHeight);
        doc.text(parseFloat(data.total_facturas).toFixed(2), xPos + col6/2, yPos + 5, { align: 'center' });

        // Signatures
        yPos += totalRowHeight + 40;
        const lineWidth = 70;
        const leftLineX = tableX;
        const rightLineX = tableX + tableWidth - lineWidth;

        doc.line(leftLineX, yPos, leftLineX + lineWidth, yPos);
        doc.line(rightLineX, yPos, rightLineX + lineWidth, yPos);

        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');
        doc.text('Vo.Bo', rightLineX - 10, yPos);

        const nombreCompleto = `${data.nombres_comisionado} ${data.apellidos_comisionado}`.toUpperCase();
        doc.text(nombreCompleto, leftLineX + lineWidth/2, yPos + 5, { align: 'center' });
        doc.text((data.puesto_funcional || '').toUpperCase(), leftLineX + lineWidth/2, yPos + 10, { align: 'center' });
        doc.text((data.ubicacion_laboral || '').toUpperCase(), leftLineX + lineWidth/2, yPos + 15, { align: 'center' });

        doc.save(`Planilla_Gastos_${data.id || 'borrador'}.pdf`);

    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Error al generar el PDF: ' + error.message);
    }
};
