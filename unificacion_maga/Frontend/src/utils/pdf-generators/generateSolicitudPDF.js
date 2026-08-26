import { jsPDF } from 'jspdf';
import logoMaga from '@/assets/images/maga_logo1.png';
import Swal from 'sweetalert2';

const formatearFecha = (fecha) => {
    if (!fecha) return '...';
    const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                   'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const parts = fecha.split('-');
    const f = new Date(parts[0], parts[1] - 1, parts[2]); 
    return f.getDate() + ' de ' + meses[f.getMonth()] + ' de ' + f.getFullYear();
};

export const generateSolicitudPDF = (data) => {
    const doc = new jsPDF();
    const margenIzq = 20;
    const margenDer = 190;
    const anchoUtil = margenDer - margenIzq;
    let y = 20;

    const logoImg = new Image();
    logoImg.src = logoMaga;

    logoImg.onload = function() {
        doc.addImage(logoImg, 'PNG', margenIzq, 10, 60, 30);
        
        doc.setFontSize(9);
        doc.text('Viceministerio de Desarrollo Económico Rural', margenDer - 5, 20, { align: 'right' });
        doc.text('Despacho', margenDer - 5, 25, { align: 'right' });

        y = 50;

        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        const fechaTexto = formatearFecha(data.fecha_elaboracion);
        doc.text('Guatemala, ' + fechaTexto, margenDer - 5, y, { align: 'right' });
        doc.setFont(undefined, 'normal');
        y += 15;

        doc.setFontSize(11);
        doc.setFont(undefined, 'bold');
        doc.text('Licenciado', margenIzq, y);
        y += 7;
        doc.text('Ehver Aroldo García Mansilla', margenIzq, y);
        y += 7;
        doc.text('Jefe Financiero/Administrativo', margenIzq, y);
        y += 7;
        doc.text('UDAFA-VIDER-MAGA', margenIzq, y);
        y += 7;
        doc.text('Presente', margenIzq, y);
        doc.setFont(undefined, 'normal');
        y += 15;

        doc.setFont(undefined, 'bold');
        doc.text('Estimado Licenciado García:', margenIzq, y);
        doc.setFont(undefined, 'normal');
        y += 10;

        const texto1 = 'De la manera más atenta me dirijo a usted, deseándole éxitos en sus labores diarias.';
        const lineas1 = doc.splitTextToSize(texto1, anchoUtil);
        doc.text(lineas1, margenIzq, y);
        y += (lineas1.length * 7) + 5;

        doc.setFontSize(11);
        
        const partes = [
            { texto: 'El motivo de la presente es para solicitarle un juego de formularios de Reconocimiento de Gastos ', negrita: false },
            { texto: '(RG-A, RG-L y ANEXOS) ', negrita: true },
            { texto: 'los cuales serán utilizados para atender la comisión de ', negrita: false },
            { texto: (data.actividad_realizar || '') + ' ', negrita: true },
            { texto: (data.lugares_visitar || '') + ' ', negrita: true },
            { texto: 'en ', negrita: false },
            { texto: (data.dias_comision || 0) + ' días', negrita: true },
            { texto: '.', negrita: false }
        ];

        let xActual = margenIzq;
        let yActual = y;

        for (let i = 0; i < partes.length; i++) {
            const parte = partes[i];
            if (parte.negrita) doc.setFont(undefined, 'bold');
            else doc.setFont(undefined, 'normal');
            
            const palabras = parte.texto.split(' ');
            for (let j = 0; j < palabras.length; j++) {
                const palabra = palabras[j];
                const textoConEspacio = (j < palabras.length - 1) ? palabra + ' ' : palabra;
                const anchoTexto = doc.getTextWidth(textoConEspacio);
                
                if (xActual + anchoTexto > margenDer && xActual > margenIzq) {
                    yActual += 7;
                    xActual = margenIzq;
                }
                
                doc.text(textoConEspacio, xActual, yActual);
                xActual += anchoTexto;
            }
             if (!parte.texto.endsWith(' ') && i < partes.length - 1) {
                 const spaceWidth = doc.getTextWidth(' ');
                 if (xActual + spaceWidth > margenDer) {
                     yActual += 7;
                     xActual = margenIzq;
                 }
                 doc.text(' ', xActual, yActual);
                 xActual += spaceWidth;
             }
        }
        
        y = yActual + 15;

        doc.setFont(undefined, 'normal');
        const texto3 = 'Agradeciendo de antemano su fina atención a la presente, sin otro particular me suscribo de usted,';
        const lineas3 = doc.splitTextToSize(texto3, anchoUtil);
        doc.text(lineas3, margenIzq, y);
        y += (lineas3.length * 7) + 5;

        doc.text('Atentamente,', margenIzq, y);
        y += 40;

        doc.line(margenIzq, y, margenIzq + 80, y);
        y += 7;

        doc.setFont(undefined, 'bold');
        doc.text((data.nombres_comisionado || '') + ' ' + (data.apellidos_comisionado || ''), margenIzq, y);
        y += 7;
        doc.text(data.puesto_funcional || '', margenIzq, y);
        y += 7;
        doc.text(data.ubicacion_laboral || '', margenIzq, y);
        doc.setFont(undefined, 'normal');
        y += 20;

        const piePagina = 270;
        doc.setFontSize(8);
        doc.text('c.c./ archivo', margenIzq, piePagina);

        doc.setDrawColor(41, 128, 185);
        doc.setLineWidth(1);
        doc.line(margenIzq, piePagina + 5, margenDer, piePagina + 5);

        doc.setTextColor(0, 0, 0);
        doc.setFontSize(9);
        doc.text('7a. avenida 12-90 zona 13, Edificio Monja Blanca', 105, piePagina + 12, { align: 'center' });
        doc.text('Teléfono: 1557 extensión 7043', 105, piePagina + 17, { align: 'center' });

        doc.save(`Solicitud_Formularios_${data.nombres_comisionado}_${data.apellidos_comisionado}.pdf`);
    };

    logoImg.onerror = function() {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar el logo para el PDF'
        });
    };
};
