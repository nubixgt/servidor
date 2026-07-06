import jsPDF from 'jspdf';
import logoMaga from '../../assets/images/maga.png';
import logoGob from '../../assets/images/gobierno.png';
import { numeroALetras } from '../numeroALetras';
import Swal from 'sweetalert2';

export const generateFormularioLiquidacionPDF = async (data) => {
    try {
        const doc = new jsPDF();
        
        // Cargar imágenes
        const loadImage = (src) => new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = () => resolve(null);
            img.src = src;
        });

        const [logoMagaImg, logoGobImg] = await Promise.all([
            loadImage(logoMaga),
            loadImage(logoGob)
        ]);

        // Configuración general
        const margenIzq = 15;
        const margenDer = 195;
        const anchoUtil = margenDer - margenIzq;
        const anchoPagina = doc.internal.pageSize.width; // 210

        // --- ENCABEZADO ---
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

        // Título Principal
        doc.setFont("helvetica", "bold");
        doc.setFontSize(16);
        doc.text("VIÁTICO LIQUIDACIÓN", 105, 40, { align: "center" });

        // ==========================================
        // CUERPO PRINCIPAL (Gran Rectángulo)
        // ==========================================
        const yInicio = 50;
        
        // Cuadro Superior Derecho (Formulario V-L)
        doc.setLineWidth(0.2); 
        doc.rect(145, 36, 50, 14); 
        
        // Líneas internas del cuadro derecho
        doc.line(145, 43, 195, 43); // Línea horizontal media
        doc.line(170, 43, 170, 50); // Vertical mitad inferior
        
        doc.setFontSize(7);
        doc.text("Formulario V - L", 170, 40.5, { align: "center" });
        
        doc.setFontSize(8);
        doc.setTextColor(200, 0, 0); // Rojo
        doc.text("No.", 157.5, 47.5, { align: "center" });
        doc.text("00000", 182.5, 47.5, { align: "center" }); 

        doc.setTextColor(0, 0, 0); // Negro

        let y = yInicio;

        // Fila 1: Recibí de
        doc.rect(margenIzq, y, anchoUtil, 8);
        doc.setFont("helvetica", "normal"); 
        doc.setFontSize(9);
        doc.text("RECIBÍ DE: VICEMINISTERIO DE DESARROLLO ECONÓMICO RURAL", margenIzq + 2, y + 5);
        y += 8;

        // Fila 2: La Cantidad de
        const montoLetrasPDF = numeroALetras(parseFloat(data.total_facturas) || 0);
        
        doc.rect(margenIzq, y, anchoUtil, 8);
        doc.text("LA CANTIDAD DE: " + montoLetrasPDF, margenIzq + 2, y + 5);
        y += 8;

        doc.rect(margenIzq, y, anchoUtil, 12);
        doc.setFontSize(7.5);
        const splitConcepto = doc.splitTextToSize("POR CONCEPTO DE GASTOS DE VIÁTICOS Y OTROS GASTOS DERIVADOS DEL CUMPLIMIENTO DE LA SIGUIENTE COMISIÓN OFICIAL:", anchoUtil - 4);
        doc.text(splitConcepto, margenIzq + 2, y + 4);
        y += 12;

        // --- TABLA DE DETALLES ---
        const col1 = anchoUtil * 0.23;
        const col2 = anchoUtil * 0.35;
        const col3 = anchoUtil * 0.10;
        const col4 = anchoUtil * 0.17;
        const col5 = anchoUtil * 0.15;

        doc.setFillColor(220, 220, 220);
        doc.rect(margenIzq, y, anchoUtil, 10, 'F'); 
        doc.rect(margenIzq, y, anchoUtil, 10, 'S');

        let x = margenIzq;
        doc.line(x + col1, y, x + col1, y + 10);
        doc.line(x + col1 + col2, y, x + col1 + col2, y + 10);
        doc.line(x + col1 + col2 + col3, y, x + col1 + col2 + col3, y + 10);
        doc.line(x + col1 + col2 + col3 + col4, y, x + col1 + col2 + col3 + col4, y + 10);

        doc.setFont("helvetica", "bold");
        doc.setFontSize(7);
        doc.text("TIPO DE COMISIÓN", x + (col1/2), y + 6, { align: "center" });
        doc.text("LUGARES DE PERMANENCIA", x + col1 + (col2/2), y + 6, { align: "center" });
        doc.text("NO. DE DÍAS", x + col1 + col2 + (col3/2), y + 6, { align: "center" });
        doc.text("RESUMEN DE GASTOS", x + col1 + col2 + col3 + (col4/2), y + 6, { align: "center" }); 
        doc.text("TOTAL Q.", x + col1 + col2 + col3 + col4 + (col5/2), y + 6, { align: "center" });
        doc.setFontSize(9);
        y += 10;

        const altoTabla = 40;
        doc.rect(margenIzq, y, anchoUtil, altoTabla);
        
        doc.line(x + col1, y, x + col1, y + altoTabla);
        doc.line(x + col1 + col2, y, x + col1 + col2, y + altoTabla);
        doc.line(x + col1 + col2 + col3, y, x + col1 + col2 + col3, y + altoTabla);
        doc.line(x + col1 + col2 + col3 + col4, y, x + col1 + col2 + col3 + col4, y + altoTabla);

        const altoFila = altoTabla / 4;
        
        doc.setFontSize(8);
        doc.setFont("helvetica", "normal");
        
        const xStartSub = x + col1 + col2 + col3;
        
        doc.line(xStartSub, y + altoFila, margenDer, y + altoFila);
        doc.line(xStartSub, y + altoFila * 2, margenDer, y + altoFila * 2);
        doc.line(xStartSub, y + altoFila * 3, margenDer, y + altoFila * 3);

        doc.text("DESAYUNO", xStartSub + (col4/2), y + altoFila - 3, {align: "center"});
        doc.text("ALMUERZO", xStartSub + (col4/2), y + (altoFila*2) - 3, {align: "center"});
        doc.text("CENA", xStartSub + (col4/2), y + (altoFila*3) - 3, {align: "center"});
        doc.text("HOSPEDAJE", xStartSub + (col4/2), y + altoTabla - 3, {align: "center"});

        doc.setFontSize(9);
        doc.text(doc.splitTextToSize(data.actividad_realizar || "", col1 - 4), x + 2, y + 5);
        doc.text(doc.splitTextToSize(data.lugares_visitar || "", col2 - 4), x + col1 + 2, y + 5);
        doc.text((data.dias_comision || "0").toString(), x + col1 + col2 + (col3/2), y + 5, {align: "center"});

        y += altoTabla;

        // Totales
        doc.rect(margenIzq, y, anchoUtil, 8);
        doc.text("SUMAN LOS GASTOS DE VIÁTICOS", margenIzq + 2, y + 5);
        doc.line(margenDer - col5, y, margenDer - col5, y + 8);
        doc.text("Q.", margenDer - col5 + 2, y + 5);
        y += 8;

        doc.rect(margenIzq, y, anchoUtil, 8);
        doc.text("OTROS GASTOS DERIVADOS SEGÚN COMPROBANTES Y PLANILLA ADJUNTA", margenIzq + 2, y + 5);
        doc.line(margenDer - col5, y, margenDer - col5, y + 8);
        doc.text("Q. " + (parseFloat(data.total_facturas) || 0).toFixed(2), margenDer - col5 + 2, y + 5);
        y += 8;

        doc.rect(margenIzq, y, anchoUtil, 8);
        doc.setFont("helvetica", "bold");
        doc.text("TOTAL", margenIzq + 2, y + 5);
        doc.line(margenDer - col5, y, margenDer - col5, y + 8);
        doc.text("Q. " + (parseFloat(data.total_facturas) || 0).toFixed(2), margenDer - col5 + 2, y + 5);
        doc.setFont("helvetica", "normal");
        
        y += 8; 

        // Liquidación Footer
        doc.rect(margenIzq, y, anchoUtil, 7); 
        doc.setFont("helvetica", "bold");
        doc.text("LIQUIDACIÓN", anchoPagina / 2, y + 5, { align: "center" }); 
        y += 7; 

        doc.rect(margenIzq, y, anchoUtil, 7); 
        doc.text("RECIBIDO POR MEDIO DE FORMULARIO V - A No.", margenIzq + 2, y + 5);
        doc.line(margenDer - col5, y, margenDer - col5, y + 7);
        doc.text("Q. " + (parseFloat(data.monto_dias_ultimo) || 0).toFixed(2), margenDer - col5 + 2, y + 5);
        y += 7;

        doc.rect(margenIzq, y, anchoUtil, 7);
        doc.text("REINTEGRO A LA DEPENDENCIA ( - )", margenIzq + 2, y + 5);
        doc.line(margenDer - col5, y, margenDer - col5, y + 7);
        doc.text("Q. " + (parseFloat(data.reintegro_dependencia) || 0).toFixed(2), margenDer - col5 + 2, y + 5);
        y += 7;

        doc.rect(margenIzq, y, anchoUtil, 7);
        doc.text("COMPLEMENTO A MI FAVOR ( + )", margenIzq + 2, y + 5);
        doc.line(margenDer - col5, y, margenDer - col5, y + 7);
        doc.text("Q.", margenDer - col5 + 2, y + 5);
        y += 7;

        doc.rect(margenIzq, y, anchoUtil, 7);
        doc.setFont("helvetica", "bold");
        doc.text("TOTAL", margenIzq + 2, y + 5);
        doc.line(margenDer - col5, y, margenDer - col5, y + 7);
        doc.text("Q. " + (parseFloat(data.total_liquidacion) || 0).toFixed(2), margenDer - col5 + 2, y + 5);
        doc.setFont("helvetica", "normal");
        y += 8;

        // Firmas
        doc.setFont("helvetica", "bold");
        doc.text("  PERSONA NOMBRADA", margenIzq, y + 4);
        
        doc.rect(margenIzq, y, anchoUtil, 32); 
        
        y += 8; 
        doc.setFont("helvetica", "normal");
        doc.text("NOMBRE: " + `${data.nombres_comisionado || ''} ${data.apellidos_comisionado || ''}`, margenIzq + 2, y);
        doc.text("SUELDO:", 140, y);
        doc.line(margenIzq, y+1, margenDer, y+1);
        
        y += 7; 
        doc.text("CARGO: " + (data.puesto_funcional || ""), margenIzq + 2, y);
        doc.line(margenIzq, y+1, 140, y+1); 

        y += 7; 
        doc.text("NÚMERO DE PARTIDA PRESUPUESTARÍA:", margenIzq + 2, y);
        doc.line(margenIzq, y+1, 140, y+1); 
        
        y += 6; 
        doc.text("NIT: " + (data.nit_comisionado || ""), margenIzq + 2, y); 
        doc.line(margenIzq, y+1, 140, y+1); 
        
        doc.line(145, y+1, margenDer, y+1);
        doc.setFontSize(7);
        doc.text("Firma", 170, y+3, {align: "center"});
        doc.setFontSize(9);
        
        y += 4; 
        doc.rect(margenIzq, y, anchoUtil, 30); 
        doc.setFont("helvetica", "bold");
        doc.text("NOMBRAMIENTO NÚMERO:", margenIzq + 2, y + 5);
        doc.line(margenIzq, y+6, margenDer, y+6);
        y += 6;

        doc.setFont("helvetica", "normal");
        y += 5; 
        doc.text("EMITIDO POR:", margenIzq + 2, y);
        doc.line(margenIzq, y+1, margenDer, y+1);
        
        y += 5; 
        doc.text("CARGO:", margenIzq + 2, y);
        doc.line(margenIzq, y+1, margenDer, y+1);
        
        y += 5; 
        const formatearFecha = (f) => {
            if (!f) return "";
            try {
                 const fecha = new Date(f);
                 // Fix timezone offset issue if string is YYYY-MM-DD
                 if (typeof f === 'string' && f.includes('-')) {
                    const parts = f.split('-');
                    if(parts.length === 3) {
                         return `${parts[2]}/${parts[1]}/${parts[0]}`;
                    }
                 }
                 return `${fecha.getDate()}/${fecha.getMonth() + 1}/${fecha.getFullYear()}`;
            } catch(e) { return f; }
        };

        doc.text("LUGAR Y FECHA:", margenIzq + 2, y);
        doc.text("Guatemala, " + formatearFecha(data.fecha_liquidacion), margenIzq + 35, y);
        
        doc.line(145, y+5, margenDer, y+5);
        doc.setFontSize(7);
        doc.text("Firma y Sello", 170, y+8, {align: "center"});
        doc.setFontSize(9);

        y += 9; 
        y += 8; 

        doc.text("REVISADO POR:", margenIzq, y);
        doc.text("Vo.Bo.", 145, y);
        doc.line(margenIzq, y+1, margenDer, y+1);

        y += 2; 

        doc.setFontSize(8);
        doc.setFont("helvetica", "bold");
        doc.text("ORIGINAL: EXPEDIENTE", anchoPagina/2, y + 5, {align: "center"});
        
        y += 10;
        doc.setFontSize(6); 
        doc.setFont("helvetica", "normal");
        
        const textoLegal = "AUTORIZACIÓN SEGÚN RESOLUCIÓN DE LA CONTRALORÍA GENERAL DE CUENTAS NO. F.O.-JO-422-2025 00004221 GESTIÓN: 1109319 DE FECHA 05-11-2025 IMPRESO POR EL ÁREA DE TESORERÍA DEL VICEMINISTERIO DE DESARROLLO ECONÓMICO RURAL DEL MINISTERIO DE AGRICULTURA GANADERÍA Y ALIMENTACIÓN NIT: 114587523 TEL: 2413-7534 - 500 J. SIN SERIE DEL 000001 AL 000500, ENVÍO FISCAL 4-ASCC 24678 DE FECHA 19-11-2025 CORRELATIVO 992-2025 DE FECHA 19-11-2025 CUENTADANCIA 2022-100-101-18-331.";
        
        const splitLegal = doc.splitTextToSize(textoLegal, anchoUtil);
        doc.text(splitLegal, anchoPagina/2, y, {align: "center"});
        
        y += (splitLegal.length * 2.5) + 2; 
        doc.text("ORIGINAL: EXPEDIENTE - DUPLICADO: ARCHIVO", anchoPagina/2, y, {align: "center"});

        doc.save(`Liquidacion_${data.nombres_comisionado || 'solicitud'}.pdf`);
    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Error al generar el PDF de Liquidación');
    }
};
