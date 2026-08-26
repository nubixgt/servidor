import { jsPDF } from "jspdf";
import logoMaga from '@/assets/images/maga_logo1.png';

export const generateNombramientoPDF = (data) => {
  const doc = new jsPDF();

  const formatearFecha = (fecha) => {
    if (!fecha) return "...";
    const meses = [
      "enero", "febrero", "marzo", "abril", "mayo", "junio",
      "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"
    ];
    // Handle both YYYY-MM-DD and Date objects if necessary
    if (fecha instanceof Date) {
        return fecha.getDate() + " de " + meses[fecha.getMonth()] + " de " + fecha.getFullYear();
    }
    const parts = fecha.split("-");
    if (parts.length === 3) {
         const f = new Date(parts[0], parts[1] - 1, parts[2]);
         return f.getDate() + " de " + meses[f.getMonth()] + " de " + f.getFullYear();
    }
    return fecha;
  };

  const margenIzq = 20;
  const margenDer = 190;
  const anchoUtil = margenDer - margenIzq;
  let y = 20;

  const logoImg = new Image();
  logoImg.src = logoMaga;

  logoImg.onload = function () {
    try {
        doc.addImage(logoImg, "PNG", margenIzq, 10, 60, 30);
    } catch (e) {
        console.warn("Could not add image to PDF", e);
    }

    doc.setFontSize(9);
    doc.text(
      "Viceministerio de Desarrollo Económico Rural",
      margenDer - 5,
      20,
      { align: "right" },
    );
    doc.text("Despacho", margenDer - 5, 25, { align: "right" });

    y = 50;

    doc.setFontSize(12);
    doc.setFont(undefined, "bold");
    const fechaTexto = formatearFecha(data.fecha_elaboracion);
    doc.text("Guatemala, " + fechaTexto, margenDer - 5, y, { align: "right" });
    y += 7;

    doc.text(data.numero_oficio || "S/N", margenDer - 5, y, { align: "right" });
    doc.setFont(undefined, "normal");
    y += 15;

    doc.setFontSize(11);
    doc.setFont(undefined, "bold");
    doc.text(data.profesion || "", margenIzq, y);
    doc.setFont(undefined, "normal");
    y += 7;

    doc.setFont(undefined, "bold");
    doc.text(
      (data.nombres_comisionado || "") +
        " " +
        (data.apellidos_comisionado || ""),
      margenIzq,
      y,
    );
    doc.setFont(undefined, "normal");
    y += 7;

    doc.setFont(undefined, "bold");
    doc.text(data.puesto_funcional || "", margenIzq, y);
    doc.setFont(undefined, "normal");
    y += 7;

    doc.setFont(undefined, "bold");
    doc.text(data.ubicacion_laboral || "", margenIzq, y);
    doc.setFont(undefined, "normal");
    y += 15;

    const genero = (data.designado_designada || "")
      .toLowerCase()
      .includes("designada")
      ? "a"
      : "o";

    doc.setFont(undefined, "bold");
    doc.text("Estimad" + genero + " ", margenIzq, y);
    const xPos = margenIzq + doc.getTextWidth("Estimad" + genero + " ");

    const textoNegrita =
      (data.profesion || "") + " " + (data.apellidos_comisionado || "") + ":";
    doc.text(textoNegrita, xPos, y);
    doc.setFont(undefined, "normal");
    y += 10;

    doc.setFontSize(11);
    const textoCompleto =
      "Saludándole cordialmente, me dirijo a usted para hacer de su conocimiento que ha sido " +
      (data.designado_designada || "").toLowerCase() +
      " para atender la comisión: " +
      (data.actividad_realizar || "") +
      " " +
      (data.lugares_visitar || "") +
      " en " +
      (data.dias_comision || 0) +
      " días.";

    const lineas = doc.splitTextToSize(textoCompleto, anchoUtil);
    doc.text(lineas, margenIzq, y);
    y += lineas.length * 7 + 10;

    const textoSecundario =
      "Agradeciendo de antemano su fina atención al presente, sin otro particular me suscribo de usted.";
    const lineasSecundario = doc.splitTextToSize(textoSecundario, anchoUtil);
    doc.text(lineasSecundario, margenIzq, y);
    y += lineasSecundario.length * 7 + 5;

    doc.text("Atentamente,", margenIzq, y);
    y += 40;

    doc.line(margenIzq, y, margenIzq + 80, y);

    const piePagina = 270;
    doc.setFontSize(8);
    doc.text("c.c./ archivo", margenIzq, piePagina);

    doc.setDrawColor(41, 128, 185);
    doc.setLineWidth(1);
    doc.line(margenIzq, piePagina + 5, margenDer, piePagina + 5);

    doc.setTextColor(0, 0, 0);
    doc.setFontSize(9);
    doc.text(
      "7a. avenida 12-90 zona 13, Edificio Monja Blanca",
      105,
      piePagina + 12,
      { align: "center" },
    );
    doc.text("Teléfono: 1557 extensión 7043", 105, piePagina + 17, {
      align: "center",
    });

    // Save Logic
    const fileName = `Nombramiento_${data.numero_oficio || 'Draft'}.pdf`;
    doc.save(fileName);
    // Or open in new tab if requested, but save is usually better for "Download" button
    // window.open(doc.output("bloburl"), "_blank");
  };

  logoImg.onerror = function () {
    console.error("Error loading logo for PDF");
    // Generate without logo if fails
    // ...
  };
};
