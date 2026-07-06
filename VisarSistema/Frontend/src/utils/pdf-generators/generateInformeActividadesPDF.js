import jsPDF from 'jspdf';
import logoMaga from '../../assets/images/maga_logo1.png';

export const generateInformeActividadesPDF = (data) => {
    const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'letter'
    });

    const pageWidth = doc.internal.pageSize.getWidth();
    const margin = 20;

    const generateContent = () => {
        let yPos = 20;

        // Header Right
        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');
        const headerText = 'Viceministerio de Desarrollo Económico Rural';
        doc.text(headerText, pageWidth - margin - 5, yPos, { align: 'right' });

        yPos = 50;

        // Title
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        const titulo = 'INFORME';
        const tituloWidth = doc.getTextWidth(titulo);
        doc.text(titulo, (pageWidth - tituloWidth) / 2, yPos);

        yPos += 20;

        // Content
        doc.setFontSize(11);
        doc.setFont('helvetica', 'normal');

        // Nombre
        doc.setFont('helvetica', 'bold');
        doc.text('Nombre:', margin, yPos);
        doc.setFont('helvetica', 'normal');
        const nombreCompleto = `${data.nombres_comisionado || ''} ${data.apellidos_comisionado || ''}`;
        doc.text(nombreCompleto, margin + 25, yPos);
        
        yPos += 10;

        // No. Contrato
        doc.setFont('helvetica', 'bold');
        doc.text('No. Contrato Administrativo:', margin, yPos);
        doc.setFont('helvetica', 'normal');
        doc.text(data.numero_contrato || '', margin + 60, yPos);
        
        yPos += 15;

        // Lugares
        doc.setFont('helvetica', 'bold');
        doc.text('Lugar (es) visitado (s):', margin, yPos);
        doc.setFont('helvetica', 'normal');
        const lugaresLines = doc.splitTextToSize(data.lugares_visitar || '', pageWidth - margin - 60 - margin);
        doc.text(lugaresLines, margin + 55, yPos);
        yPos += (lugaresLines.length * 6) + 10;

        // Plazo
        doc.setFont('helvetica', 'bold');
        doc.text('Plazo Comprendido de:', margin, yPos);
        doc.setFont('helvetica', 'normal');
        
        // Replicating logic from reference: using days/dates if available
        // If fecha_inicio_viaje and fecha_fin_viaje are available in data (from Dashboard context), we could use them.
        // But Informe Modal mainly asks for "dias_comision".
        // The original vue file used `dias_comision` as the value for both fields which seems like a placeholder or mistake in original, 
        // but I will follow the user's implicit desire for functionality. 
        // Better: if we have fechas, use them, otherwise use the numeric days.
        
        const fechaDel = data.fecha_inicio_viaje ? formatFechaGlobal(data.fecha_inicio_viaje) : (data.dias_comision || '');
        const fechaAl = data.fecha_fin_viaje ? formatFechaGlobal(data.fecha_fin_viaje) : (data.dias_comision || '');

        doc.text(String(fechaDel), margin + 55, yPos);
        
        doc.setFont('helvetica', 'bold');
        doc.text('A:', margin + 85, yPos);
        doc.setFont('helvetica', 'normal');
        doc.text(String(fechaAl), margin + 95, yPos);
        
        yPos += 15;

        // Objetivos
        doc.setFont('helvetica', 'bold');
        doc.text('Objetivos:', margin, yPos);
        doc.setFont('helvetica', 'normal');
        
        // As per reference: Activity + Places + Days concatenated
        const objetivos = `${data.actividad_realizar || ''} ${data.lugares_visitar || ''} ${data.dias_comision || ''}`;
        const objetivosLines = doc.splitTextToSize(objetivos, pageWidth - margin - 30 - margin);
        doc.text(objetivosLines, margin + 25, yPos);
        yPos += (objetivosLines.length * 6) + 10;

        yPos += 10;

        // Logros Alcanzados
        doc.setFont('helvetica', 'bold');
        doc.text('Logros Alcanzados:', margin, yPos);
        // Leaving space for achievements or mapping if they existed. 
        // The original code left this blank or used static space.
        yPos += 50; 

        // Lugar y Fecha
        let fechaFormateada = '';
        if (data.fecha_liquidacion) {
            fechaFormateada = formatFechaGlobal(data.fecha_liquidacion, true);
        } else {
             // Fallback to today if not provided
             fechaFormateada = formatFechaGlobal(new Date(), true);
        }
        
        doc.setFont('helvetica', 'normal');
        const lugarFechaText = `Lugar y Fecha, Guatemala ${fechaFormateada}`;
        const lugarFechaWidth = doc.getTextWidth(lugarFechaText);
        doc.text(lugarFechaText, pageWidth - margin - lugarFechaWidth, yPos);

        yPos += 20;

        // Signatures
        const lineY = yPos;
        const lineWidth = 70;
        const leftLineX = margin + 10;
        const rightLineX = pageWidth - margin - lineWidth - 10;

        doc.setLineWidth(0.3);
        doc.line(leftLineX, lineY, leftLineX + lineWidth, lineY);
        doc.line(rightLineX, lineY, rightLineX + lineWidth, lineY);

        // VoBo
        doc.setFontSize(9);
        doc.text('VoBo', rightLineX - 10, lineY, { align: 'left' });

        // Comisionado
        doc.setFontSize(8);
        const centerX = leftLineX + lineWidth/2;
        
        doc.text(nombreCompleto, centerX, lineY + 5, { align: 'center' });
        doc.text(data.puesto_funcional || '', centerX, lineY + 9, { align: 'center' });
        doc.text(data.ubicacion_laboral || '', centerX, lineY + 13, { align: 'center' });

        doc.save(`Informe_Actividades_${data.nombres_comisionado || 'solicitud'}.pdf`);
    };

    const logoImg = new Image();
    logoImg.src = logoMaga;
    logoImg.onload = () => {
        doc.addImage(logoImg, 'PNG', margin, 10, 60, 30);
        generateContent();
    };
    logoImg.onerror = () => {
        generateContent();
    };
};

function formatFechaGlobal(fechaStr, withLongFormat = false) {
    if (!fechaStr) return '';
    try {
        // Handle YYYY-MM-DD string or Date object
        const date = new Date(fechaStr);
        // Adjust for timezone if it's a string like '2023-01-01' to prevent off-by-one
        // But for simplicity, we assume the string is local or we use it as parts.
        
        if (typeof fechaStr === 'string' && fechaStr.includes('-')) {
             const parts = fechaStr.split('-');
             if (parts.length === 3) {
                 const year = parseInt(parts[0]);
                 const month = parseInt(parts[1]) - 1;
                 const day = parseInt(parts[2]);
                 const d = new Date(year, month, day);
                 if (withLongFormat) {
                     return d.toLocaleDateString('es-GT', { year: 'numeric', month: 'long', day: 'numeric' });
                 }
                 return `${day}/${month + 1}/${year}`;
             }
        }
        
        if (withLongFormat) {
            return date.toLocaleDateString('es-GT', { year: 'numeric', month: 'long', day: 'numeric' });
        }
        return date.toLocaleDateString('es-GT');
    } catch (e) {
        return fechaStr;
    }
}
