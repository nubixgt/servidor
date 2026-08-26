import { jsPDF } from 'jspdf';
import logoMaga from '@/assets/images/maga_logo1.png';

const formatearFecha = (fecha) => {
    if (!fecha) return '';
    const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                   'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const parts = fecha.split('-');
    const f = new Date(parts[0], parts[1] - 1, parts[2]);
    return f.getDate() + ' de ' + meses[f.getMonth()] + ' de ' + f.getFullYear();
};

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

export const generateOficioSolicitudPDF = async (data) => {
    const doc = new jsPDF();
    
    // Configuración
    const margenIzq = 20;
    const margenDer = 190;
    const anchoUtil = margenDer - margenIzq;
    let y = 20;
    
    // Logo MAGA
    const logoImg = await loadImage(logoMaga);
    if (logoImg) {
        doc.addImage(logoImg, 'PNG', margenIzq, 10, 60, 30);
    }
    
    // Configurar líneas delgadas
    doc.setDrawColor(0, 0, 0);
    doc.setLineWidth(0.3);
    
    // Viceministerio en negrita (derecha)
    doc.setFontSize(10);
    doc.setFont("helvetica", "bold");
    doc.text('Viceministerio de Desarrollo Económico Rural', margenDer, 25, { align: 'right' });
    doc.setFont("helvetica", "normal");
    
    y = 50;
    
    // Fecha (campo 13)
    const fechaEntrega = formatearFecha(data.fecha_entrega);
    doc.setFontSize(11);
    doc.text('Guatemala, ' + fechaEntrega, margenDer, y, { align: 'right' });
    
    y += 15;
    
    // Destinatario
    doc.setFont("helvetica", "bold");
    doc.text('Licenciado', margenIzq, y);
    y += 5;
    doc.text('Ehver Aroldo García Mansilla', margenIzq, y);
    y += 5;
    doc.text('Jefe Financiero/Administrativo', margenIzq, y);
    y += 5;
    doc.text('UDAFA-VIDER-MAGA', margenIzq, y);
    y += 5;
    doc.text('Presente', margenIzq, y);
    doc.setFont("helvetica", "normal");
    
    y += 15;
    
    // Saludo
    doc.setFont("helvetica", "bold");
    doc.text('Estimado Licenciado García:', margenIzq, y);
    doc.setFont("helvetica", "normal");
    
    y += 10;
    
    // Primer párrafo
    const texto1 = 'De la manera más atenta me dirijo a usted, deseándole éxitos en sus labores diarias.';
    const lineas1 = doc.splitTextToSize(texto1, anchoUtil);
    doc.text(lineas1, margenIzq, y);
    y += (lineas1.length * 5) + 3;
    
    // Segundo párrafo con campos 9, 10, 11 en negrita
    const segmentos = [
        { texto: 'El motivo de la presente es para solicitarle se me autorice y genere Anticipo por Reconocimiento de Gastos el cual será utilizado para los gastos que se generarán durante la realización de la Comisión: ', bold: false },
        { texto: data.actividad_realizar || '', bold: true },
        { texto: ' ', bold: false },
        { texto: data.lugares_visitar || '', bold: true },
        { texto: ' ', bold: false },
        { texto: (data.dias_comision || 1) + ' días', bold: true },
        { texto: '.', bold: false }
    ];
    
    // Imprimir segmentos con manejo de líneas
    let x = margenIzq;
    
    for (const seg of segmentos) {
        const palabras = seg.texto.split(' ');
        
        for (let i = 0; i < palabras.length; i++) {
            const palabra = palabras[i];
            if (!palabra) continue;
            
            doc.setFont("helvetica", seg.bold ? 'bold' : 'normal');
            const anchoPalabra = doc.getTextWidth(palabra);
            const anchoEspacio = doc.getTextWidth(' ');
            
            const anchoNecesario = (x > margenIzq ? anchoEspacio : 0) + anchoPalabra;
            
            if (x + anchoNecesario > margenIzq + anchoUtil) {
                y += 5;
                x = margenIzq;
            }
            
            if (x > margenIzq) {
                doc.setFont("helvetica", 'normal');
                doc.text(' ', x, y);
                x += anchoEspacio;
            }
            
            doc.setFont("helvetica", seg.bold ? 'bold' : 'normal');
            doc.text(palabra, x, y);
            x += anchoPalabra;
        }
    }
    
    y += 10;
    doc.setFont("helvetica", "normal");
    
    // Tercer párrafo
    const texto3 = 'Adjunto sírvase encontrar la documentación de respaldo correspondiente:';
    doc.text(texto3, margenIzq, y);
    y += 7;
    
    // Lista de documentos
    doc.setFontSize(10);
    doc.text('• Fotocopia de Contrato vigente,', margenIzq + 5, y);
    y += 5;
    doc.text('• Fotocopia de Acuerdo Ministerial,', margenIzq + 5, y);
    y += 5;
    doc.text('• Fotocopia de Nombramiento,', margenIzq + 5, y);
    y += 5;
    doc.text('• Requerimiento de traslado al interior de la República y,', margenIzq + 5, y);
    y += 5;
    doc.text('• Formulario RG-A.', margenIzq + 5, y);
    y += 10;
    
    // Cuarto párrafo
    doc.setFontSize(11);
    const texto4 = 'Agradeciendo de antemano su fina atención a la presente, sin otro particular me suscribo de usted,';
    const lineas4 = doc.splitTextToSize(texto4, anchoUtil);
    doc.text(lineas4, margenIzq, y);
    y += (lineas4.length * 5) + 5;
    
    // Cordialmente
    doc.text('Cordialmente,', margenIzq, y);
    y += 40;
    
    // Línea de firma
    doc.line(margenIzq, y, margenIzq + 80, y);
    
    // Nombre y Cargo debajo de la firma
    y += 5;
    doc.setFont("helvetica", "bold");
    doc.text(`${data.nombres_comisionado || ''} ${data.apellidos_comisionado || ''}`, margenIzq, y);
    y += 5;
    doc.text(data.puesto_funcional || '', margenIzq, y);
    y += 5;
    doc.text(data.ubicacion_laboral || '', margenIzq, y);

    // Pie de página (posición fija)
    const piePagina = 270;
    
    // c.c. Archivo
    doc.setFontSize(8);
    doc.setFont("helvetica", "normal");
    doc.text('c.c. Archivo', margenIzq, piePagina);
    
    // Línea azul
    doc.setDrawColor(41, 128, 185); // Azul 
    doc.setLineWidth(1);
    doc.line(margenIzq, piePagina + 5, margenDer, piePagina + 5);
    
    // Dirección y teléfono centrados
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(9);
    doc.text('7a. avenida 6-80 zona 13, interior oficinas INAB', 105, piePagina + 10, { align: 'center' });
    doc.text('Teléfono: 1557 extensión 7072', 105, piePagina + 15, { align: 'center' });
    
    doc.save(`Oficio_Solicitud_${data.id || 'borrador'}.pdf`);
};
