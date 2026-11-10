// Comprobante de reserva en PDF (se genera en el navegador, no pasa por el servidor).
// Diseño basado en src/assets/images/ImagenReservaMesa.png (1024 x 1536 px).
import logoUrl from '../assets/images/sanafer-2026.png';
import infoUrl from '../assets/images/InfoEventoPDF.jpeg';

export const bookingCode = (bookingId) => 'RSV-' + String(bookingId).slice(0, 8).toUpperCase();

const REF_W = 1024; // ancho de la imagen de referencia, en px
const W = 148; // ancho de la página en mm
const K = W / REF_W; // mm por px de referencia
const px = (n) => n * K;
const pt = (n) => n * K * (72 / 25.4); // tamaño de letra: px de referencia -> puntos

const C = {
    paper: '#f8f4ec',
    navy: '#0a1228',
    header: '#021a47', // igual al fondo del logo, para que se funda
    blue: '#1c52c9',
    sky: '#8fcaff',
    ink: '#0a1530',
    text: '#1a2336',
    red: '#d6322f',
};

const TZ = 'America/Guatemala';
const INFO_W = 964; // la imagen informativa ocupa el ancho de las bandas
const INFO_H = Math.round((INFO_W * 600) / 1500); // proporción de InfoEventoPDF.jpeg (1500 x 600)

const dateParts = (iso) => {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-GB', {
            timeZone: TZ, day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
        }).formatToParts(new Date(iso)).map((p) => [p.type, p.value])
    );
    return { date: `${parts.day}/${parts.month}/${parts.year}`, time: `${parts.hour}:${parts.minute}` };
};

const money = (n) => 'Q ' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/**
 * Dibuja el comprobante en `doc` (una instancia nueva de jsPDF creada con `createDoc`).
 * data: { bookingId, name, phone?, tableIds:number[], total:number, createdAt:ISO }
 */
export function drawReceipt(doc, data, logoDataUrl, infoDataUrl) {
    const ids = [...data.tableIds].sort((a, b) => a - b);
    const labels = ids.map((i) => 'A' + i);
    const { date } = dateParts(data.createdAt);
    const phone = data.phone ? String(data.phone) : '';

    // Ajusta el tamaño de letra para que el texto quepa en `maxPx`.
    const fit = (text, sizePx, maxPx, minPx = 20) => {
        let s = sizePx;
        doc.setFontSize(pt(s));
        while (s > minPx && doc.getTextWidth(text) > px(maxPx)) {
            s -= 2;
            doc.setFontSize(pt(s));
        }
        return s;
    };
    const text = (str, x, y, { size, font = 'helvetica', style = 'normal', color = C.ink, align, space } = {}) => {
        doc.setFont(font, style);
        doc.setFontSize(pt(size));
        doc.setTextColor(color);
        doc.text(String(str), px(x), px(y), { align, charSpace: space ? px(space) : undefined });
    };
    const line = (x1, y1, x2, y2, color = C.navy, w = 2) => {
        doc.setDrawColor(color);
        doc.setLineWidth(px(w));
        doc.line(px(x1), px(y1), px(x2), px(y2));
    };
    const rect = (x, y, w, h, color) => {
        doc.setFillColor(color);
        doc.rect(px(x), px(y), px(w), px(h), 'F');
    };
    const circle = (x, y, r, color) => {
        doc.setFillColor(color);
        doc.circle(px(x), px(y), px(r), 'F');
    };

    // --- Filas de datos (la de mesas puede ocupar varias líneas) ---
    const mesasText = labels.join(', ') + `  ·  ${ids.length * 8} entradas`;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(pt(27));
    let mesasLines = doc.splitTextToSize(mesasText, px(540));
    if (mesasLines.length > 3) mesasLines = [...mesasLines.slice(0, 2), mesasLines[2].replace(/.{2}$/, '…')];
    const nameLines = (() => {
        doc.setFontSize(pt(29));
        return doc.splitTextToSize(data.name, px(540)).slice(0, 2);
    })();
    const rows = [
        { label: 'Titular', lines: nameLines, size: 29 },
        { label: ids.length === 1 ? 'Mesa' : 'Mesas', lines: mesasLines, size: 27 },
        ...(phone ? [{ label: 'Teléfono', lines: [phone], size: 29 }] : []),
        { label: 'Fecha de reserva', lines: [date], size: 29 },
    ];
    const rowH = (r) => 57 + (r.lines.length - 1) * 31;
    const rowsHeight = rows.reduce((n, r) => n + rowH(r), 0);

    // Altura total de la página según el contenido
    const tableTop = 826;
    const totalTop = tableTop + 28 + rowsHeight + 33;
    const H_PX = totalTop + 120 + 30 + INFO_H + 40;
    doc.internal.pageSize.height = px(H_PX);
    doc.internal.pageSize.width = W;
    const pageH = px(H_PX);

    // Fondo
    doc.setFillColor(C.paper);
    doc.rect(0, 0, W, pageH, 'F');

    // --- Encabezado oscuro ---
    rect(0, 0, 1024, 385, C.header);
    circle(-40, -90, 230, '#0b3a86');
    circle(-40, -90, 175, C.header);
    rect(0, 385, 1024, 30, C.paper);
    for (let x = 6; x < 1030; x += 22) circle(x, 387, 8.5, C.paper);
    rect(0, 385, 1024, 3, C.paper);

    rect(85, 68, 6, 32, '#3d8bff');
    text('BAILE SOCIAL', 118, 96, { size: 30, style: 'bold', color: '#ffffff', space: 4 });
    text('Tu mesa está', 70, 185, { size: 82, font: 'times', style: 'bold', color: '#ffffff' });
    text('confirmada.', 70, 262, { size: 86, font: 'times', style: 'bolditalic', color: C.sky });
    circle(95, 325, 23, '#a8d5ff');
    doc.setDrawColor(C.navy);
    doc.setLineWidth(px(5));
    doc.line(px(85), px(325), px(92), px(333));
    doc.line(px(92), px(333), px(107), px(315));
    line(141, 303, 141, 347, '#8aa3c8', 2);
    text('RESERVA CONFIRMADA', 166, 334, { size: 27, color: '#ffffff', space: 2.5 });
    if (logoDataUrl) doc.addImage(logoDataUrl, 'PNG', px(585), px(6), px(385), px(385));

    // --- Código ---
    const code = bookingCode(data.bookingId);
    text('CÓDIGO DE RESERVA', 65, 446, { size: 29, style: 'bold', space: 1 });
    const codeSize = fit(code, 104, 900, 50);
    text(code, 65, 540, { size: codeSize, style: 'bold' });
    text('Guarda este código para consultar tu reserva.', 66, 580, { size: 25, color: C.text });

    // --- Banda azul: mesa(s) ---
    rect(30, 608, 964, 162, C.blue);
    text(ids.length === 1 ? 'MESA\nRESERVADA' : 'MESAS\nRESERVADAS', 70, 682, { size: 37, style: 'bold', color: '#ffffff' });
    const big = ids.length <= 3 ? labels.join(' ') : String(ids.length);
    doc.setFont('times', 'bold');
    const bigSize = fit(big, 160, 250, 50);
    text(big, 450, 738, { size: bigSize, font: 'times', style: 'bold', color: '#ffffff', align: 'center' });
    line(600, 634, 600, 748, '#ffffff', 2);
    text('Una gran noche.', 645, 672, { size: 39, font: 'times', style: 'italic', color: '#ffffff' });
    text('Tu mejor lugar.', 645, 722, { size: 39, font: 'times', style: 'italic', color: '#ffffff' });

    // --- Datos de la reserva ---
    text('DATOS DE LA RESERVA', 40, tableTop + 4, { size: 30, style: 'bold' });
    line(468, tableTop - 8, 985, tableTop - 8, C.navy, 2);
    let y = tableTop + 28;
    for (const r of rows) {
        const h = rowH(r);
        text(r.label, 65, y + 42, { size: 29, style: 'bold' });
        r.lines.forEach((l, i) => text(l, 422, y + 42 + i * 31, { size: r.size, color: C.text }));
        line(42, y + h - 4, 982, y + h - 4, C.navy, 2);
        y += h;
    }

    // --- Total ---
    rect(30, totalTop, 964, 120, C.navy);
    text('TOTAL A PAGAR', 75, totalTop + 74, { size: 38, style: 'bold', color: '#ffffff' });
    line(470, totalTop + 22, 470, totalTop + 98, '#8aa3c8', 2);
    const totalStr = money(data.total);
    const totalSize = fit(totalStr, 104, 455, 50);
    text(totalStr, 955, totalTop + 84, { size: totalSize, style: 'bold', color: '#ffffff', align: 'right' });

    // --- Imagen informativa del evento ---
    const n = totalTop + 120; // fin de la banda
    if (infoDataUrl) doc.addImage(infoDataUrl, 'JPEG', px(30), px(n + 30), px(INFO_W), px(INFO_H));

    return doc;
}

let logoCache;
// El logo original pesa casi 3 MB: se reduce para que el PDF sea liviano.
const loadLogo = () => {
    logoCache ??= new Promise((resolve) => {
        const img = new Image();
        img.onload = () => {
            const size = 560;
            const canvas = document.createElement('canvas');
            canvas.width = canvas.height = size;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, size, size);
            // El logo trae fondo azul: se difuminan solo los bordes para fundirlo con el encabezado.
            ctx.globalCompositeOperation = 'destination-in';
            for (const [x1, y1] of [[size, 0], [0, size]]) {
                const g = ctx.createLinearGradient(0, 0, x1, y1);
                g.addColorStop(0, 'rgba(0,0,0,0)');
                g.addColorStop(0.07, 'rgba(0,0,0,1)');
                g.addColorStop(0.93, 'rgba(0,0,0,1)');
                g.addColorStop(1, 'rgba(0,0,0,0)');
                ctx.fillStyle = g;
                ctx.fillRect(0, 0, size, size);
            }
            resolve(canvas.toDataURL('image/png'));
        };
        img.onerror = () => resolve(null); // sin logo, el PDF se genera igual
        img.src = logoUrl;
    });
    return logoCache;
};

let infoCache;
// Imagen informativa (fecha, artistas, patrocinadores). Si no carga, el PDF se genera sin ella.
const loadInfo = () => {
    infoCache ??= new Promise((resolve) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth;
            canvas.height = img.naturalHeight;
            canvas.getContext('2d').drawImage(img, 0, 0);
            resolve(canvas.toDataURL('image/jpeg', 0.9));
        };
        img.onerror = () => resolve(null);
        img.src = infoUrl;
    });
    return infoCache;
};

/** Genera y descarga el PDF. data: { bookingId, name, tableIds, total, createdAt } */
export async function downloadReceipt(data) {
    const [{ jsPDF }, logo, info] = await Promise.all([import('jspdf'), loadLogo(), loadInfo()]);
    const doc = new jsPDF({ unit: 'mm', format: [W, 230], orientation: 'portrait', compress: true });
    drawReceipt(doc, data, logo, info);
    doc.save(`Reserva-${bookingCode(data.bookingId)}.pdf`);
}
