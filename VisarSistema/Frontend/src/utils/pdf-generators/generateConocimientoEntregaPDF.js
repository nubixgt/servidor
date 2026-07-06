import { jsPDF } from 'jspdf';
import logoMaga from '@/assets/images/maga_logo1.png';
import Swal from 'sweetalert2';

const formatearFecha = (fecha) => {
    if (!fecha) return '';
    const parts = fecha.split('-');
    const date = new Date(parts[0], parts[1] - 1, parts[2]);
    return `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear()}`;
};

export const generateConocimientoEntregaPDF = (data) => {
    const doc = new jsPDF();
    const margenIzq = 25;
    const margenDer = 185;
    const anchoUtil = margenDer - margenIzq;
    let y = 12;

    const logoImg = new Image();
    logoImg.src = logoMaga;

    logoImg.onload = function() {
        const logoWidth = 90;
        const logoHeight = 35;
        const centerX = 105;

        doc.addImage(logoImg, 'PNG', centerX - (logoWidth / 2), 10, logoWidth, logoHeight);
        y = 48;

        const lineHeight = 4;
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.text('Ministerio de Agricultura, Ganadería y Alimentación', centerX, y, { align: 'center' });
        y += lineHeight;
        
        doc.setFont(undefined, 'normal');
        doc.text('Viceministerio de Desarrollo Económico Rural', centerX, y, { align: 'center' });
        y += lineHeight;
        doc.text('Unidad Desconcentrada de Administración Financiera', centerX, y, { align: 'center' });
        y += lineHeight;
        doc.text('y Administrativa', centerX, y, { align: 'center' });
        y += 8;

        doc.setFont(undefined, 'bold');
        doc.setFontSize(12);
        doc.text('CONOCIMIENTO', centerX, y, { align: 'center' });
        doc.setFont(undefined, 'normal');
        y += 10;

        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.text(`No: ${data.numero_correlativo}`, margenDer, y, { align: 'right' });
        y += 5;
        
        const fechaTexto = formatearFecha(data.fecha_entrega);
        doc.text(`FECHA: ${fechaTexto}`, margenDer, y, { align: 'right' });
        y += 10;

        const colEtiquetas = margenIzq;
        const colDatos = margenIzq + 40; 
        const spacingDatos = 4;
        
        doc.setFont(undefined, 'bold');
        doc.text('PARA:', colEtiquetas, y);
        
        doc.text(data.profesion || '', colDatos, y);
        y += spacingDatos;
        
        doc.text(`${data.nombres_comisionado || ''} ${data.apellidos_comisionado || ''}`, colDatos, y);
        y += spacingDatos;
        
        doc.text(data.puesto_funcional || '', colDatos, y);
        y += spacingDatos;
        
        doc.text(data.ubicacion_laboral || '', colDatos, y);
        y += 10;
        
        doc.setFont(undefined, 'normal');
        
        doc.setFont(undefined, 'bold');
        doc.text('DE:', colEtiquetas, y);
        doc.setFont(undefined, 'normal');
        doc.text('TESORERÍA de VIDER del MAGA', colDatos, y);
        y += 12;

        const bodyText = `Por medio del presente hacemos entrega de un juego de formularios de Reconocimiento de Gastos (RG-A y RG-L) y sus anexos para el cumplimiento de su comisión de ${data.dias_comision || 0} días para realizar ${data.actividad_realizar || ''} en ${data.lugares_visitar || ''}.`;
        
        const linesBody = doc.splitTextToSize(bodyText, anchoUtil);
        doc.text(linesBody, margenIzq, y);
        y += (linesBody.length * 5) + 4;

        doc.setFontSize(10); 
        const legalText = "Así mismo todo documento de legítimo abono que ampare la comprobación de los gastos que se realicen en las comisiones oficiales como los de Reconocimiento de Gastos, consta que al momento de recibir este documento usted conoce y aplica la normativa vigente establecida en el Acuerdo Ministerial 150-2022; Manual de Normas y Procedimientos de Administración Financiera, Acuerdo Ministerial 174-2023; MANUAL TÉCNICO PARA LA ADMINISTRACIÓN DEL FONDO ROTATIVO INSTITUCIONAL CON TARJETA DE COMPRAS INSTITUCIONAL -TCI, Acuerdo Ministerial No. 162-2024 y Ley del Impuesto Al Valor Agregado, Decreto número 27-92.";
        
        const linesLegal = doc.splitTextToSize(legalText, anchoUtil);
        doc.text(linesLegal, margenIzq, y);
        y += (linesLegal.length * 5) + 10;

        doc.setFont(undefined, 'normal');
        doc.text('Para conocimiento y efectos correspondientes.', margenIzq, y);
        y += 12;

        if (y > 245) {
            doc.addPage();
            y = 20;
        }

        doc.setFont(undefined, 'bold');
        doc.text('Quien Recibe:', margenIzq, y);
        y += 10;
        
        doc.setFont(undefined, 'normal');
        
        const lineaX = margenIzq + 35;
        const espacioFirmas = 8;
        const finLinea = lineaX + 100;
        
        doc.text('Nombre completo:', margenIzq, y);
        doc.line(lineaX, y, finLinea, y);
        y += espacioFirmas;
        
        doc.text('Firma:', margenIzq, y);
        doc.line(lineaX, y, finLinea, y);
        y += espacioFirmas;
        
        doc.text('Fecha:', margenIzq, y);
        doc.line(lineaX, y, finLinea, y);

        const piePagina = 270;
        doc.setDrawColor(41, 128, 185);
        doc.setLineWidth(1);
        doc.line(margenIzq, piePagina + 5, margenDer, piePagina + 5);
        
        doc.setTextColor(0, 0, 0);
        doc.setFontSize(9);
        doc.text('7a. avenida 6-80 zona 13, interior oficinas INAB', 105, piePagina + 12, { align: 'center' });
        doc.text('Teléfono: 1557 extensión 7072', 105, piePagina + 17, { align: 'center' });

        doc.save(`Conocimiento_${data.numero_correlativo || 'borrador'}.pdf`);
    };

    logoImg.onerror = () => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo cargar el logo para el PDF'
        });
    };
};
