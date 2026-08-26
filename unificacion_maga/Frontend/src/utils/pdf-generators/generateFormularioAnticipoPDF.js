import { jsPDF } from 'jspdf';
import logoMaga from '@/assets/images/maga.png';
import logoGobierno from '@/assets/images/gobierno.png';
import Swal from 'sweetalert2';

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

export const generateFormularioAnticipoPDF = async (data) => {
    const doc = new jsPDF();
    
    // Load Images
    // Note: In Vite/Webpack, imports return urls. 
    // We assume the logoMaga/logoGobierno are accessible URL/paths.
    const logoMagaImg = await loadImage(logoMaga);
    const logoGobImg = await loadImage(logoGobierno);

    const margenIzq = 15;
    const margenDer = 195;
    const anchoUtil = margenDer - margenIzq;
    
    // Header
    if (logoMagaImg) {
        doc.addImage(logoMagaImg, 'PNG', 15, 10, 25, 25);
    }
    
    doc.setFont("helvetica", "normal");
    doc.setFontSize(9);
    doc.text("VICEMINISTERIO DE DESARROLLO ECONÓMICO RURAL", 105, 18, { align: "center" });
    doc.text("MINISTERIO DE AGRICULTURA, GANADERÍA Y ALIMENTACIÓN", 105, 23, { align: "center" });

    if (logoGobImg) {
        doc.addImage(logoGobImg, 'PNG', 170, 10, 25, 25);
    }

    // Title
    doc.setFont("helvetica", "bold");
    doc.setFontSize(16);
    doc.text("VIÁTICO ANTICIPO", 105, 40, { align: "center" });

    // Body Rect
    const yInicio = 50;
    const altoTotal = 210;
    doc.rect(margenIzq, yInicio, anchoUtil, altoTotal);

    // Formulario V-A Box
    doc.setLineWidth(0.2); 
    doc.rect(145, 36, 50, 14); 
    doc.line(145, 43, 195, 43);
    doc.line(170, 36, 170, 43);
    doc.line(180, 36, 180, 43);
    
    doc.setFontSize(7);
    doc.text("Formulario V - A", 157.5, 40.5, { align: "center" });
    doc.setFontSize(8);
    doc.setTextColor(200, 0, 0); 
    doc.text("No.", 175, 40.5, { align: "center" });
    doc.text("00000", 187.5, 40.5, { align: "center" }); 

    doc.setTextColor(0, 0, 0); 
    doc.setFontSize(8);
    doc.text("POR Q.", 147, 46.5);
    doc.setFontSize(9);
    doc.text(data.monto_calculado?.toString() || '0.00', 170, 46.5);
    doc.setFontSize(6);
    doc.text("(EN NÚMEROS)", 175, 49, { align: "center" });

    let y = yInicio;

    // Recibí
    y += 8;
    doc.setFont("helvetica", "normal");
    doc.setFontSize(10);
    doc.text("RECIBÍ DE:", margenIzq + 2, y);
    
    y += 5; 
    doc.setFontSize(9);
    doc.text("VICEMINISTERIO DE DESARROLLO ECONÓMICO RURAL", 105, y, { align: "center" });
    doc.line(margenIzq + 25, y + 1, margenDer - 2, y + 1);
    
    y += 4;
    doc.setFontSize(7);
    doc.text("(NOMBRE DE LA DEPENDENCIA)", 105, y, { align: "center" });
    
    doc.line(margenIzq, y + 2, margenDer, y + 2);
    y += 2;

    // Cantidad
    y += 8;
    doc.setFontSize(10);
    doc.text("LA CANTIDAD DE:", margenIzq + 2, y);
    
    y += 5;
    doc.setFontSize(9);
    doc.text(data.monto_letras || '', 105, y, { align: "center" });
    doc.line(margenIzq + 35, y + 1, margenDer - 2, y + 1);
    
    y += 4;
    doc.setFontSize(7);
    doc.text("(EN LETRAS)", 105, y, { align: "center" });
    
    doc.line(margenIzq, y + 2, margenDer, y + 2);
    y += 2;

    // Concepto
    y += 6;
    doc.setFontSize(10);
    doc.text("POR CONCEPTO DE ANTICIPO DE VIÁTICOS EN CUMPLIMIENTO DE LA SIGUIENTE COMISIÓN OFICIAL:", margenIzq + 2, y);
    
    y += 2;
    doc.line(margenIzq, y, margenDer, y);

    // Tabla Headers
    const yHeaderTabla = y + 5;
    doc.setFontSize(8);
    doc.setFont("helvetica", "bold");
    
    const col1W = 77.5; 
    const col2W = 170; 
    
    const centroCol1 = margenIzq + (col1W / 2);
    const centroCol2 = margenIzq + col1W + ((col2W - (margenIzq + col1W)) / 2);

    doc.text("TIPO DE COMISIÓN", centroCol1, yHeaderTabla, { align: "center" }); 
    doc.text("LUGARES EN QUE SE REALIZARÁ", centroCol2, yHeaderTabla, { align: "center" }); 
    doc.text(`NÚMERO DE DÍAS`, 182.5, yHeaderTabla, { align: "center" }); 

    y += 8; 
    doc.line(margenIzq, y, margenDer, y); 

    const yFinTabla = 152; 
    doc.line(margenIzq + col1W, yHeaderTabla - 5, margenIzq + col1W, yFinTabla);
    doc.line(col2W, yHeaderTabla - 5, col2W, yFinTabla);

    // Tabla Content
    const yContenido = y + 5;
    doc.setFont("helvetica", "normal");
    doc.setFontSize(8); 
    
    const splitActividad = doc.splitTextToSize(data.actividad_realizar || '', col1W - 4);
    doc.text(splitActividad, margenIzq + 2, yContenido);

    const anchoCol2 = col2W - (margenIzq + col1W);
    const splitLugares = doc.splitTextToSize(data.lugares_visitar || '', anchoCol2 - 4);
    doc.text(splitLugares, margenIzq + col1W + 2, yContenido);

    doc.setFontSize(9);
    doc.text((data.dias_ultimo_dia || 0).toString(), 182.5, yContenido, { align: "center" });

    doc.line(margenIzq, yFinTabla, margenDer, yFinTabla);

    // Footer
    let yF = yFinTabla + 2; 

    // Bloque 1
    const yInicioBloque1 = yF;
    yF += 5;
    doc.setFontSize(9);
    doc.setFont("helvetica", "bold");
    doc.text("SEGÚN NOMBRAMIENTO NÚMERO: " + (data.numero_oficio || ''), margenIzq + 2, yF);
    
    doc.line(145, yInicioBloque1, 145, yInicioBloque1 + 8); 
    doc.text("FECHA: " + formatearFecha(data.fecha_elaboracion), 147, yF);
    
    yF += 3;
    doc.rect(margenIzq, yInicioBloque1, anchoUtil, 8);
    
    // Bloque 2
    yF += 1; 
    const yInicioBloque2 = yF;
    
    yF += 5;
    doc.text("PERSONA NOMBRADA", margenIzq + 2, yF);
    
    yF += 7; 
    doc.setFont("helvetica", "normal");
    doc.text("NOMBRE: " + `${data.nombres_comisionado || ''} ${data.apellidos_comisionado || ''}`, margenIzq + 2, yF);
    doc.text("SUELDO:", 145, yF);
    doc.line(margenIzq, yF + 2, margenDer, yF + 2); 
    
    yF += 7; 
    doc.text("CARGO: " + (data.puesto_funcional || ''), margenIzq + 2, yF);
    doc.line(margenIzq, yF + 2, 140, yF + 2); 

    yF += 7; 
    doc.text("NÚMERO DE PARTIDA PRESUPUESTARÍA:", margenIzq + 2, yF);
    doc.line(margenIzq, yF + 2, 140, yF + 2);

    yF += 7; 
    doc.text("NIT:", margenIzq + 2, yF);
    doc.line(145, yF + 2, margenDer, yF + 2); 
    doc.setFontSize(6);
    doc.text("Firma", 170, yF + 4, { align: "center" });
    doc.setFontSize(9);
    
    yF += 6; 
    doc.rect(margenIzq, yInicioBloque2, anchoUtil, yF - yInicioBloque2);

    // Bloque 3
    yF += 1; 
    const yInicioBloque3 = yF;

    yF += 5;
    doc.setFont("helvetica", "bold");
    doc.text("NOMBRAMIENTO EMITIDO POR:", margenIzq + 2, yF);
    
    yF += 5;
    doc.setFont("helvetica", "normal");
    doc.text("NOMBRE: " + (data.nombre_jefe || ''), margenIzq + 2, yF);
    doc.line(margenIzq, yF + 1, 145, yF + 1); 
    
    yF += 5;
    doc.text("CARGO: " + (data.puesto_jefe || ''), margenIzq + 2, yF);
    doc.line(145, yF + 1, margenDer, yF + 1); 
    doc.setFontSize(6);
    doc.text("Firma y Sello", 170, yF + 3, { align: "center" });
    doc.setFontSize(9);
    
    yF += 4;
    doc.rect(margenIzq, yInicioBloque3, anchoUtil, yF - yInicioBloque3);
    
    // Bloque 4
    yF += 1; 
    const yInicioBloque4 = yF;
    
    yF += 5;
    doc.text("LUGAR Y FECHA: Guatemala, " + formatearFecha(data.fecha_elaboracion), margenIzq + 2, yF);
    
    yF += 3;
    doc.rect(margenIzq, yInicioBloque4, anchoUtil, 8);

    // Bloque 5
    yF += 1; 
    const yInicioBloque5 = yF;
    
    yF += 5;
    doc.setFont("helvetica", "bold");
    doc.text("REVISADO POR:", margenIzq + 2, yF);
    doc.text("Vo.Bo.", 155, yF); 
    
    yF += 6;
    doc.setFont("helvetica", "normal");
    doc.text("NOMBRE:", margenIzq + 2, yF);
    doc.line(margenIzq + 18, yF + 1, 140, yF + 1); 
    
    yF += 12;
    doc.text("FIRMA:", margenIzq + 2, yF);
    doc.line(margenIzq + 15, yF, 140, yF); 
    
    doc.line(145, yF, 190, yF); 
    
    doc.setFontSize(7);
    doc.text("Jefe Inmediato", 167, yF + 4, { align: "center" });
    
    yF += 8; 
    doc.rect(margenIzq, yInicioBloque5, anchoUtil, yF - yInicioBloque5);
    
    // Legal Text
    yF += 8; 
    
    doc.setFont("helvetica", "bold");
    doc.setFontSize(9);
    doc.text("ORIGINAL: EXPEDIENTE", 105, yF, { align: "center" });
    
    yF += 5;
    doc.setFont("helvetica", "normal");
    doc.setFontSize(7); 
    const legalText = "AUTORIZACIÓN SEGÚN RESOLUCIÓN DE LA CONTRALORÍA GENERAL DE CUENTAS NO. F.O.-JO-422-2025 00004221 GESTIÓN: 1109319 DE FECHA 05-11-2025 IMPRESO POR EL ÁREA DE TESORERÍA DEL VICEMINISTERIO DE DESARROLLO ECONÓMICO RURAL DEL MINISTERIO DE AGRICULTURA GANADERÍA Y ALIMENTACIÓN NIT: 114587523 TEL: 2413-7534 - 500 J. SIN SERIE DEL 000001 AL 000500, ENVÍO FISCAL 4-ASCC 24678 DE FECHA 19-11-2025 CORRELATIVO 992-2025 DE FECHA 19-11-2025 CUENTADANCIA 2022-100-101-18-331.";
    const splitLegal = doc.splitTextToSize(legalText, anchoUtil);
    doc.text(splitLegal, 105, yF, { align: "center" });
    
    doc.save(`Anticipo_${data.id || 'borrador'}.pdf`);
};
