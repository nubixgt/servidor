import { jsPDF } from 'jspdf';
import logoMaga from '@/assets/images/maga_logo1.png';
import Swal from 'sweetalert2';

const formatearFecha = (fecha) => {
    if (!fecha) return '';
    const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                   'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const parts = fecha.split('-');
    const f = new Date(parts[0], parts[1] - 1, parts[2]);
    return f.getDate() + ' de ' + meses[f.getMonth()] + ' de ' + f.getFullYear();
};

const formatearFechaCorta = (fecha) => {
    if (!fecha) return '';
    const parts = fecha.split('-');
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
};

export const generateRequerimientoTrasladoPDF = (data) => {
    const doc = new jsPDF();
    const margenIzq = 20;
    const margenDer = 190;
    const anchoUtil = margenDer - margenIzq;
    let y = 20;

    const logoImg = new Image();
    logoImg.src = logoMaga;

    logoImg.onload = function() {
        // Logo
        doc.addImage(logoImg, 'PNG', margenIzq, 10, 60, 30);
        
        doc.setDrawColor(0, 0, 0);
        doc.setLineWidth(0.3);
        
        // Header Right
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.text('Viceministerio de Desarrollo Económico Rural', margenDer, 25, { align: 'right' });
        doc.setFont(undefined, 'normal');
        
        y = 50;
        
        // Title
        doc.setFontSize(11);
        doc.setFont(undefined, 'bold');
        doc.text('FORMULARIO DE REQUERIMIENTO DE TRASLADO AL INTERIOR DE LA REPÚBLICA', 105, y, { align: 'center' });
        doc.setFont(undefined, 'normal');
        
        y += 15;
        
        // Date
        const fechaEntrega = formatearFecha(data.fecha_entrega);
        doc.setFontSize(10);
        doc.text('Lugar y Fecha, Guatemala ' + fechaEntrega, margenDer, y, { align: 'right' });
        
        y += 15;
        
        // Name
        doc.text('Nombre:', margenIzq, y);
        const nombreCompleto = `${data.nombres_comisionado || ''} ${data.apellidos_comisionado || ''}`;
        doc.text(nombreCompleto, margenIzq + 30, y);
        doc.line(margenIzq + 29, y + 1, margenDer, y + 1);
        
        y += 15;
        
        // Service Type
        doc.text('Tipo de Servicios:', margenIzq, y);
        doc.text(data.tipo_servicio || '', margenIzq + 40, y);
        doc.line(margenIzq + 39, y + 1, margenDer, y + 1);
        
        y += 10;
        
        // Contract & NIT
        doc.text('No. Contrato Administrativo:', margenIzq, y);
        doc.text(data.numero_contrato || '', margenIzq + 55, y);
        
        doc.text('NIT:', 120, y);
        doc.text(data.nit_comisionado || '', 135, y);
        
        doc.line(margenIzq + 54, y + 1, 115, y + 1);
        doc.line(134, y + 1, margenDer, y + 1);
        
        y += 15;
        
        // Places to visit
        doc.text('Lugar (es) a visitar:', margenIzq, y);
        
        const lineasLugares = doc.splitTextToSize(data.lugares_visitar || '', anchoUtil - 45);
        doc.text(lineasLugares, margenIzq + 40, y);
        doc.line(margenIzq + 39, y + 1, margenDer, y + 1);
        
        y += (lineasLugares.length * 7) + 15;
        
        // Date Range
        const fechaIngreso = formatearFechaCorta(data.fecha_ingreso);
        const fechaSalida = formatearFechaCorta(data.fecha_salida);
        
        doc.text('Plazo Comprendido de:', margenIzq, y);
        doc.text(fechaIngreso, margenIzq + 50, y);
        doc.setFontSize(8);
        doc.text('(Fecha de Ingreso)', margenIzq + 50, y + 4);
        doc.setFontSize(10);
        
        doc.text('A:', 105, y);
        doc.text(fechaSalida, 115, y);
        doc.setFontSize(8);
        doc.text('(Fecha de Salida)', 115, y + 4);
        doc.setFontSize(10);
        
        doc.line(margenIzq + 49, y + 1, 100, y + 1);
        doc.line(114, y + 1, margenDer, y + 1);
        
        y += 15;
        
        // Activity
        doc.text('a)', margenIzq, y);
        
        const lineasActividad = doc.splitTextToSize(data.actividad_realizar || '', anchoUtil - 10);
        doc.text(lineasActividad, margenIzq + 7, y);
        
        y += (lineasActividad.length * 7) + 10;
        
        // Budget Partida
        doc.text('Partida Presupuestaria:', margenIzq, y);
        doc.text('2026-1113-0012-205-12-00-000-001-000-136-0101-11', margenIzq + 50, y);
        doc.line(margenIzq + 49, y + 1, margenDer, y + 1);
        
        y += 7;
        
        // Centro Costo
        doc.text('Centro de Costo:', margenIzq, y);
        doc.text('2327/5414   Dirección de Desarrollo Económico Rural', margenIzq + 50, y);
        doc.line(margenIzq + 49, y + 1, margenDer, y + 1);
        
        y += 7;
        
        // Sub Producto
        doc.text('Sub Producto:', margenIzq, y);
        doc.text('006-001-0001   Dirección y Coordinación', margenIzq + 50, y);
        doc.line(margenIzq + 49, y + 1, margenDer, y + 1);
        
        y += 20;
        
        // Signature Line
        doc.line(margenIzq + 60, y, margenIzq + 130, y);

        doc.save(`Requerimiento_Traslado_${data.id || 'borrador'}.pdf`);
    };
    
    logoImg.onerror = () => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar el logo'
        });
    };
};
