// Reporte de ventas (Excel y PDF). Se genera en el navegador con los datos de /reservations,
// que el servidor solo entrega completos al administrador.
import { categories, SEATS_PER_TABLE, TOTAL_TABLES, tableById } from './tables';
import { bookingCode } from './receipt';

const TZ = 'America/Guatemala';
const CAT_KEYS = Object.keys(categories);

const fmtDate = (iso) =>
    new Date(iso).toLocaleString('es-GT', { dateStyle: 'short', timeStyle: 'short', timeZone: TZ });
const money = (n) => 'Q ' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const stamp = () => {
    const p = Object.fromEntries(
        new Intl.DateTimeFormat('en-GB', { timeZone: TZ, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })
            .formatToParts(new Date()).map((x) => [x.type, x.value])
    );
    return { text: `${p.day}/${p.month}/${p.year} ${p.hour}:${p.minute}`, file: `${p.year}-${p.month}-${p.day}` };
};

/** Calcula el resumen a partir de las filas de reservas (una por mesa). */
export function buildReport(reservations) {
    const rows = reservations.map((r) => {
        const category = r.category || tableById(r.tableId)?.category;
        const price = r.price != null ? Number(r.price) : categories[category]?.price ?? 0;
        return { ...r, category, price };
    });

    const byCategory = CAT_KEYS.map((key) => {
        const mine = rows.filter((r) => r.category === key);
        const total = mine.reduce((n, r) => n + r.price, 0);
        return { key, name: categories[key].name, tables: mine.length, tickets: mine.length * SEATS_PER_TABLE, total };
    });

    const sellers = new Map();
    const bookings = new Map();
    for (const r of rows) {
        let b = bookings.get(r.bookingId);
        if (!b) {
            b = { bookingId: r.bookingId, code: bookingCode(r.bookingId), name: r.name, phone: r.phone || '', seller: r.userName || 'Sin registro', createdAt: r.createdAt, tables: [], total: 0 };
            bookings.set(r.bookingId, b);
        }
        b.tables.push(r.tableId);
        b.total += r.price;
    }
    for (const b of bookings.values()) {
        b.tables.sort((x, y) => x - y);
        const s = sellers.get(b.seller) ?? { name: b.seller, bookings: 0, tables: 0, tickets: 0, total: 0 };
        s.bookings += 1;
        s.tables += b.tables.length;
        s.tickets += b.tables.length * SEATS_PER_TABLE;
        s.total += b.total;
        sellers.set(b.seller, s);
    }

    const soldTables = rows.length;
    return {
        generatedAt: stamp().text,
        fileDate: stamp().file,
        soldTables,
        availableTables: TOTAL_TABLES - soldTables,
        totalTables: TOTAL_TABLES,
        soldTickets: soldTables * SEATS_PER_TABLE,
        availableTickets: (TOTAL_TABLES - soldTables) * SEATS_PER_TABLE,
        bookingCount: bookings.size,
        total: rows.reduce((n, r) => n + r.price, 0),
        byCategory,
        bySeller: [...sellers.values()].sort((a, b) => b.total - a.total),
        bookings: [...bookings.values()].sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt)),
    };
}

const summaryLines = (rep) => [
    ['Reservas confirmadas', rep.bookingCount],
    ['Mesas vendidas', `${rep.soldTables} de ${rep.totalTables}`],
    ['Mesas disponibles', rep.availableTables],
    ['Entradas vendidas', rep.soldTickets],
    ['Entradas disponibles', rep.availableTickets],
];

const save = (blob, filename) => {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
};

/** Descarga el reporte en Excel (.xlsx) con cuatro hojas. */
export async function downloadReportExcel(reservations) {
    const rep = buildReport(reservations);
    const { default: ExcelJS } = await import('exceljs');
    const wb = new ExcelJS.Workbook();
    wb.created = new Date();

    const MONEY = '"Q"#,##0.00';
    const style = (sheet, headerRow) => {
        const row = sheet.getRow(headerRow);
        row.font = { bold: true, color: { argb: 'FFFFFFFF' } };
        row.eachCell((c) => { c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1C52C9' } }; });
    };

    // Hoja 1: resumen
    const s1 = wb.addWorksheet('Resumen');
    s1.columns = [{ width: 28 }, { width: 22 }];
    s1.addRow(['SANAFER 2026 - Reporte de ventas']).font = { bold: true, size: 14 };
    s1.addRow([`Generado: ${rep.generatedAt}`]);
    s1.addRow([]);
    for (const [k, v] of summaryLines(rep)) s1.addRow([k, v]);
    const t = s1.addRow(['TOTAL DE VENTA', rep.total]);
    t.font = { bold: true };
    t.getCell(2).numFmt = MONEY;
    s1.eachRow((r) => { r.getCell(2).alignment = { horizontal: 'right' }; });

    // Hoja 2: por localidad
    const s2 = wb.addWorksheet('Por localidad');
    s2.columns = [{ header: 'Localidad', width: 18 }, { header: 'Precio por mesa', width: 18 }, { header: 'Mesas vendidas', width: 16 }, { header: 'Entradas vendidas', width: 18 }, { header: 'Total', width: 18 }];
    rep.byCategory.forEach((c) => s2.addRow([c.name, categories[c.key].price, c.tables, c.tickets, c.total]));
    const tot2 = s2.addRow(['TOTAL', '', rep.soldTables, rep.soldTickets, rep.total]);
    tot2.font = { bold: true };
    style(s2, 1);
    s2.getColumn(2).numFmt = MONEY;
    s2.getColumn(5).numFmt = MONEY;

    // Hoja 3: por vendedor
    const s3 = wb.addWorksheet('Por vendedor');
    s3.columns = [{ header: 'Vendedor', width: 28 }, { header: 'Reservas', width: 12 }, { header: 'Mesas', width: 10 }, { header: 'Entradas', width: 12 }, { header: 'Total', width: 18 }];
    rep.bySeller.forEach((s) => s3.addRow([s.name, s.bookings, s.tables, s.tickets, s.total]));
    style(s3, 1);
    s3.getColumn(5).numFmt = MONEY;

    // Hoja 4: detalle de reservas
    const s4 = wb.addWorksheet('Detalle de reservas');
    s4.columns = [
        { header: 'Código', width: 14 }, { header: 'Titular', width: 30 }, { header: 'Teléfono', width: 12 },
        { header: 'Mesas', width: 26 }, { header: 'Entradas', width: 10 }, { header: 'Total', width: 16 },
        { header: 'Registró', width: 24 }, { header: 'Fecha', width: 18 },
    ];
    rep.bookings.forEach((b) => s4.addRow([b.code, b.name, b.phone, b.tables.map((i) => 'A' + i).join(', '), b.tables.length * SEATS_PER_TABLE, b.total, b.seller, fmtDate(b.createdAt)]));
    style(s4, 1);
    s4.getColumn(6).numFmt = MONEY;
    s4.views = [{ state: 'frozen', ySplit: 1 }];

    const buffer = await wb.xlsx.writeBuffer();
    save(new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }), `Reporte-ventas-SANAFER-${rep.fileDate}.xlsx`);
}

/** Descarga el reporte en PDF (A4). */
export async function downloadReportPdf(reservations) {
    const rep = buildReport(reservations);
    const [{ jsPDF }, { default: autoTable }] = await Promise.all([import('jspdf'), import('jspdf-autotable')]);
    const doc = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait', compress: true });
    const BLUE = [28, 82, 201];
    const NAVY = [10, 18, 40];
    const margin = 14;

    doc.setFillColor(...NAVY);
    doc.rect(0, 0, 210, 30, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(18);
    doc.text('SANAFER 2026 - Reporte de ventas', margin, 14);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    doc.text(`Baile Social · Generado el ${rep.generatedAt}`, margin, 22);

    // Resumen
    doc.setTextColor(...NAVY);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(12);
    doc.text('Resumen general', margin, 40);
    autoTable(doc, {
        startY: 43,
        body: [...summaryLines(rep).map(([k, v]) => [k, String(v)]), [{ content: 'TOTAL DE VENTA', styles: { fontStyle: 'bold' } }, { content: money(rep.total), styles: { fontStyle: 'bold' } }]],
        theme: 'grid',
        styles: { fontSize: 10 },
        columnStyles: { 0: { cellWidth: 70 }, 1: { halign: 'right' } },
        tableWidth: 120,
        margin: { left: margin },
    });

    const section = (title, head, body, foot) => {
        const y = doc.lastAutoTable.finalY + 10;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(12);
        doc.setTextColor(...NAVY);
        doc.text(title, margin, y);
        autoTable(doc, {
            startY: y + 3,
            head: [head],
            body,
            foot: foot ? [foot] : undefined,
            headStyles: { fillColor: BLUE },
            footStyles: { fillColor: [230, 235, 245], textColor: NAVY },
            styles: { fontSize: 9 },
            margin: { left: margin, right: margin },
        });
    };

    section('Ventas por localidad', ['Localidad', 'Precio por mesa', 'Mesas', 'Entradas', 'Total'],
        rep.byCategory.map((c) => [c.name, money(categories[c.key].price), c.tables, c.tickets, money(c.total)]),
        ['TOTAL', '', rep.soldTables, rep.soldTickets, money(rep.total)]);

    section('Ventas por vendedor', ['Vendedor', 'Reservas', 'Mesas', 'Entradas', 'Total'],
        rep.bySeller.map((s) => [s.name, s.bookings, s.tables, s.tickets, money(s.total)]));

    section('Detalle de reservas', ['Código', 'Titular', 'Mesas', 'Total', 'Registró', 'Fecha'],
        rep.bookings.map((b) => [b.code, b.name, b.tables.map((i) => 'A' + i).join(', '), money(b.total), b.seller, fmtDate(b.createdAt)]));

    const pages = doc.getNumberOfPages();
    for (let i = 1; i <= pages; i++) {
        doc.setPage(i);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(120);
        doc.text(`Página ${i} de ${pages}`, 196, 290, { align: 'right' });
    }

    doc.save(`Reporte-ventas-SANAFER-${rep.fileDate}.pdf`);
}
