'use strict';
/**
 * Generación de la Hoja de Actividades en PDF.
 * Estructura tomada de la plantilla "HOJA DE ACTIVIDADES.docx":
 *  - Papel oficio (8.5" × 14"), orientación vertical.
 *  - Encabezado institucional: logo, "Colegio Particular Mixto" / "CIENCIA Y DESARROLLO",
 *    línea azul y línea amarilla, lema.
 *  - Título "HOJA DE ACTIVIDADES <ORDINAL> BIMESTRE".
 *  - DOCENTE / GRADO / SECCIÓN, ASIGNATURA, NOMBRE DEL ALUMNO / CLAVE (en blanco).
 *  - Franja azul "CONTENIDOS".
 *  - Tabla: Contenido | Actividades de evaluación | Aspectos a evaluar | Fecha de entrega | Recepción de Tareas
 *    (la última columna queda en blanco para que el docente la llene a mano)
 *    (encabezado verde claro C5E0B3; subtabla Aspectos/Calificación con encabezado
 *    amarillo FFD966 y fila Total verde 00B050).
 *  - Filas de Actividad de mejoramiento, Afectivo, Asistencia y Examen final.
 *  - Observaciones, NOTA resaltada y firmas (Catedrático, Padre de familia, Vo. Bo. Dirección).
 * El documento se construye de forma dinámica: las filas crecen con el texto y la tabla
 * continúa en las páginas que sean necesarias, repitiendo el encabezado de columnas.
 */
const PdfPrinter = require('pdfmake');
const vfs = require('pdfmake/build/vfs_fonts');

const fontBuf = n => Buffer.from((vfs.pdfMake ? vfs.pdfMake.vfs : vfs)[n], 'base64');
const printer = new PdfPrinter({
  Roboto: {
    normal: fontBuf('Roboto-Regular.ttf'),
    bold: fontBuf('Roboto-Medium.ttf'),
    italics: fontBuf('Roboto-Italic.ttf'),
    bolditalics: fontBuf('Roboto-MediumItalic.ttf'),
  },
});

const ORDINAL = { 1: 'PRIMER', 2: 'SEGUNDO', 3: 'TERCER', 4: 'CUARTO' };
const C = {
  headerGreen: '#1F3A5F', banner: '#1F3A5F', bannerLine: '#F2C230', aspectsHead: '#FBE7A1', totalGreen: '#2E8B57',
  lineBlue: '#1F3A5F', lineYellow: '#F2C230', noteFill: '#FFF6D6', noteLine: '#E0B100', grid: '#9AA5B1',
  infoFill: '#F2F5F9', infoLine: '#C9D3DF',
};
// Tinte suave por bloque en la celda «Contenido» (separa visualmente cada actividad de evaluación)
const BLOCK_TINTS = ['#EAF6EF', '#EAF1FC', '#FFF4E0', '#F3EBFC', '#FDEBF0', '#E4F6F8'];
const PAGE = { width: 612, height: 1008 };            // oficio en puntos
const MARGINS = [51, 104, 51, 46];                     // izq, sup, der, inf
// Proporción de la plantilla con la columna «Recepción de Tareas» (mismo ancho que «Fecha de entrega»).
// Anchos de contenido: con el relleno de 4 pt por lado, la tabla mide lo mismo que la de cuatro columnas (542 pt).
const COLS = [129, 128, 122, 61.5, 61.5];

function fmtDate(d) { if (!d) return ''; const [y, m, dd] = d.split('-'); return `${dd}-${m}-${y}`; }
function fmtPts(n) {
  if (n === null || n === undefined) return '';
  const s = Number.isInteger(n) ? String(n) : String(n).replace('.', '.');
  return `${s} ${n === 1 ? 'punto' : 'puntos'}`;
}
function paragraphs(text, style = {}) {
  return String(text || '').split(/\n{2,}/).map(p => p.trim()).filter(Boolean)
    .map((p, i) => ({ text: p, margin: [0, i ? 6 : 0, 0, 0], ...style }));
}

// ------------------------------------------------------------------ celdas
function topicsCell(block, topics, { continuation, includePages }) {
  const items = topics.map(t => ({
    id: `t|${block.id}|${t.index}`,
    text: includePages && t.pages ? [t.topic, { text: `  (pág. ${t.pages})`, color: '#333333' }] : t.topic,
  }));
  const head = continuation
    ? { text: [{ text: 'Temas ', bold: true }, { text: `(continuación${block.label ? ' — ' + block.label : ''}):`, italics: true }], fontSize: 9.5 }
    : { text: 'Temas:', bold: true, fontSize: 10 };
  return { stack: [head, items.length ? { ul: items, fontSize: 9, margin: [2, 1, 0, 0] } : ''], fillColor: tint(block) };
}
const tint = block => BLOCK_TINTS[(block.colorIndex || 0) % BLOCK_TINTS.length];

function aspectsCell(criteria, scoringMode, total, note) {
  const out = [];
  const crit = criteria.filter(c => c.text);
  if (scoringMode === 'criterios' && crit.length) {
    const body = [[
      { text: 'Aspectos', bold: true, fillColor: C.aspectsHead, alignment: 'center', fontSize: 8 },
      { text: 'Calificación', bold: true, fillColor: C.aspectsHead, alignment: 'center', fontSize: 8 },
    ]];
    crit.forEach(c => body.push([{ text: c.text, fontSize: 8 }, { text: fmtPts(c.points), fontSize: 8 }]));
    body.push([
      { text: 'Total', bold: true, fillColor: C.totalGreen, color: '#FFFFFF', fontSize: 8 },
      { text: fmtPts(total), bold: true, fillColor: C.totalGreen, color: '#FFFFFF', alignment: 'center', fontSize: 8 },
    ]);
    out.push({
      table: { widths: ['56%', '44%'], body, dontBreakRows: true },
      layout: { hLineWidth: () => 0.5, vLineWidth: () => 0.5, paddingLeft: () => 2, paddingRight: () => 2, paddingTop: () => 1, paddingBottom: () => 1 },
      margin: [2, 4, 2, 4],
    });
  } else {
    crit.forEach(c => out.push({ text: c.text, alignment: 'center', fontSize: 9, margin: [0, 2, 0, 0] }));
    if (total !== null && total !== undefined) out.push({ text: crit.length ? `TOTAL ${fmtPts(total)}` : fmtPts(total), bold: true, alignment: 'center', fontSize: 9, margin: [0, 6, 0, 0] });
  }
  if (note) out.push(...paragraphs(note, { bold: true, alignment: 'center', fontSize: 9 }).map(p => ({ ...p, margin: [0, 6, 0, 0] })));
  return { stack: out };
}

function evaluationCell(ev) {
  return { stack: [...paragraphs(ev.description, { alignment: 'justify', fontSize: 9.5 }),
    ...paragraphs(ev.instructions, { fontSize: 9.5 }).map(p => ({ ...p, margin: [0, 8, 0, 0] }))] };
}

const dateCell = text => ({ text, alignment: 'center', fontSize: 10, margin: [0, 6, 0, 0] });

// ------------------------------------------------------------------ filas
function blockRows(block, includePages, chunks) {
  const topics = block.contents.map((c, index) => ({ ...c, index })).filter(c => c.topic);
  const parts = (chunks && chunks.length) ? chunks : [topics.map(t => t.index)];
  return parts.map((idxs, i) => {
    const subset = topics.filter(t => idxs.includes(t.index));
    if (i === 0) {
      return [
        topicsCell(block, subset, { continuation: false, includePages }),
        evaluationCell(block.evaluation),
        aspectsCell(block.evaluation.criteria, block.evaluation.scoring_mode, block.evaluation.total, ''),
        dateCell(fmtDate(block.evaluation.due_date)),
      ];
    }
    // Continuación: misma evaluación mensual (mismo registro), referida para mantener el contexto.
    // La fila comienza en una página nueva: la tabla se corta ahí (ver buildDoc).
    return markBreak([
      topicsCell(block, subset, { continuation: true, includePages }),
      { text: `(Continuación) Corresponde a la misma actividad de evaluación del bloque${block.label ? ' “' + block.label + '”' : ''}.`, italics: true, fontSize: 8.5, color: '#333333' },
      { text: block.evaluation.total !== null && block.evaluation.total !== undefined ? `TOTAL ${fmtPts(block.evaluation.total)}` : '', alignment: 'center', fontSize: 8.5, color: '#333333' },
      dateCell(fmtDate(block.evaluation.due_date)),
    ]);
  });
}
function markBreak(row) { row.breakBefore = true; return row; }

function extraPoints(x) {
  if (!x.applies) return '';
  return aspectsCell(x.criteria, x.scoring_mode, x.total, x.aspects_note);
}
function extraDate(x) { return x.applies ? (x.due_date ? fmtDate(x.due_date) : x.due_text) : ''; }
const NA = { text: 'No aplica', italics: true, fontSize: 9, color: '#333333' };

function extrasRows(extras) {
  const by = Object.fromEntries(extras.map(x => [x.kind, x]));
  const rows = [];
  const m = by.mejoramiento;
  if (m) {
    rows.push([
      { stack: [{ text: 'ACTIVIDAD DE MEJORAMIENTO', bold: true, alignment: 'center', fontSize: 9.5 },
        ...(m.applies && m.detail ? paragraphs(m.detail, { fontSize: 9, alignment: 'center' }) : [])] },
      m.applies ? { stack: paragraphs(m.activity, { fontSize: 9.5 }) } : NA,
      extraPoints(m),
      dateCell(extraDate(m)),
    ]);
  }
  const af = by.afectivo, as = by.asistencia;
  const label = (x, title) => ({ text: [{ text: title, bold: true }, x.applies && x.detail ? { text: ` (${x.detail.replace(/^\(|\)$/g, '')})` } : ''], fontSize: 9.5 });
  const shareRow = af && as && af.applies && as.applies && extraDate(af) === extraDate(as) && !af.activity && !as.activity;
  if (shareRow) {
    // Como en la plantilla: Afectivo y Asistencia comparten fila y fecha
    rows.push([
      { stack: [label(af, 'Afectivo'), { text: ' ', fontSize: 6 }, label(as, 'Asistencia')] },
      '',
      { stack: [extraPoints(af), extraPoints(as)] },
      dateCell(extraDate(af)),
    ]);
  } else {
    for (const [x, title] of [[af, 'Afectivo'], [as, 'Asistencia']]) {
      if (!x) continue;
      rows.push([label(x, title), x.applies ? { stack: paragraphs(x.activity, { fontSize: 9.5 }) } : NA, extraPoints(x), dateCell(extraDate(x))]);
    }
  }
  const ex = by.examen_final;
  if (ex) {
    rows.push([
      { text: [{ text: 'Examen final.', fontSize: 9.5 }, ex.applies && ex.detail ? { text: ` ${ex.detail}`, fontSize: 9 } : ''] },
      ex.applies ? { stack: paragraphs(ex.activity, { fontSize: 9.5 }) } : NA,
      extraPoints(ex),
      dateCell(extraDate(ex)),
    ]);
  }
  return rows;
}

// ------------------------------------------------------------------ documento
function headerFor(h, logo) {
  return () => ({
    margin: [0, 0, 0, 0],
    stack: [
      {
        columns: [
          logo ? { image: 'logo', fit: [70, 66], width: 80, margin: [56, 20, 0, 0] } : { text: '', width: 80 },
          {
            width: '*',
            stack: [
              { text: h.header_line1, fontSize: 13, alignment: 'center', margin: [0, 22, 30, 0] },
              { text: h.header_line2, fontSize: 21, alignment: 'center', margin: [0, -2, 30, 0], characterSpacing: 0.4 },
              {
                canvas: [
                  { type: 'line', x1: 0, y1: 3, x2: 435, y2: 3, lineWidth: 3, lineColor: C.lineBlue },
                  { type: 'line', x1: 95, y1: 9, x2: 435, y2: 9, lineWidth: 3, lineColor: C.lineYellow },
                ],
                margin: [0, 0, 0, 0],
              },
              { text: h.motto, fontSize: 11, italics: true, alignment: 'right', margin: [0, 3, 44, 0], characterSpacing: 0.6 },
            ],
          },
        ],
      },
    ],
  });
}

function buildDoc(snap, chunksByBlock) {
  const h = snap.header, p = snap.projection;
  const logo = h.logo ? `data:${h.logo_mime};base64,${h.logo}` : null;

  const headerFn = headerFor(h, logo);

  const headRow = () => ['Contenido', 'Actividades de evaluación', 'Aspectos a evaluar', 'Fecha de entrega', 'Recepción de Tareas']
    .map(t => ({ text: t, bold: true, alignment: 'center', fillColor: C.headerGreen, color: '#FFFFFF', fontSize: 10 }));
  const rows = [];
  p.blocks.forEach((b, bi) => rows.push(...blockRows({ ...b, colorIndex: bi }, p.include_pages, chunksByBlock[b.id])));
  rows.push(...extrasRows(p.extras));
  // Columna «Recepción de Tareas»: siempre vacía (se llena a mano en la hoja impresa)
  for (const r of rows) r.push({ text: '' });
  // La tabla se divide en segmentos donde una fila de continuación debe iniciar página nueva;
  // cada segmento repite el encabezado de columnas.
  const segments = [[]];
  for (const r of rows) {
    if (r.breakBefore && segments[segments.length - 1].length) segments.push([]);
    segments[segments.length - 1].push(r);
  }
  const tableLayout = {
    hLineWidth: () => 0.6, vLineWidth: () => 0.6, hLineColor: () => C.grid, vLineColor: () => C.grid,
    paddingLeft: () => 4, paddingRight: () => 4, paddingTop: () => 3, paddingBottom: () => 4,
  };
  const tables = segments.map((seg, i) => ({
    table: { headerRows: 1, dontBreakRows: false, widths: COLS, body: [headRow(), ...seg.map(r => r.slice())] },
    layout: tableLayout,
    pageBreak: i > 0 ? 'before' : undefined,
  }));

  const obs = [h.default_observations, p.observations].filter(Boolean).join(' ');
  const line = '_'.repeat(38);

  const content = [
    { text: `${h.sheet_title} ${ORDINAL[h.bimester_number] || ''} BIMESTRE`.replace(/\s+/g, ' '), bold: true, alignment: 'center', fontSize: 11, margin: [0, 0, 0, 1] },
    { text: `Ciclo escolar ${h.school_year_name}`, alignment: 'center', fontSize: 9, margin: [0, 0, 0, 10] },
    {
      margin: [0, 0, 0, 0], fontSize: 9.5,
      table: { widths: ['*'], body: [[{ fillColor: C.infoFill, margin: [8, 4, 8, 4], stack: [
        { text: [{ text: 'DOCENTE: ', bold: true }, h.teacher_name, { text: '    GRADO: ', bold: true }, h.grade_name, { text: '    SECCIÓN: ', bold: true }, `“${h.section_name}”`] },
        { text: [{ text: 'ASIGNATURA: ', bold: true }, h.course_name] },
        { text: [{ text: 'NOMBRE DEL ALUMNO: ', bold: true }, '_'.repeat(54), { text: 'CLAVE: ', bold: true }, '_'.repeat(10)] },
      ] }]] },
      layout: { hLineWidth: () => 0.6, vLineWidth: () => 0.6, hLineColor: () => C.infoLine, vLineColor: () => C.infoLine },
    },
    {
      table: { widths: [440], body: [[{ text: h.contents_banner, bold: true, color: '#FFFFFF', fontSize: 15, alignment: 'center', fillColor: C.banner, margin: [0, 2, 0, 2], characterSpacing: 1.5 }]] },
      layout: { hLineWidth: (i) => (i === 1 ? 3 : 0), vLineWidth: () => 0, hLineColor: () => C.bannerLine },
      margin: [35, 8, 0, 10],
      id: 'banner',
    },
    ...tables,
    {
      unbreakable: true,
      stack: [
        obs ? { text: [{ text: 'Observaciones: ', bold: true }, obs], fontSize: 9.5, margin: [30, 8, 0, 0] } : '',
        h.note_text ? {
          table: { widths: ['*'], body: [[{ text: h.note_text, bold: true, fontSize: 10, alignment: 'center', fillColor: C.noteFill, margin: [8, 5, 8, 5] }]] },
          layout: { hLineWidth: () => 1, vLineWidth: () => 1, hLineColor: () => C.noteLine, vLineColor: () => C.noteLine },
          margin: [30, 10, 30, 0],
        } : '',
        {
          columns: [
            { stack: [{ text: `F. ${line}`, fontSize: 8 }, { text: h.sign_teacher, fontSize: 8, alignment: 'center' }], width: '*' },
            { stack: [{ text: `F. ${line}`, fontSize: 8 }, { text: h.sign_parent, fontSize: 8, alignment: 'center' }], width: '*' },
          ],
          columnGap: 30, margin: [30, 24, 20, 0],
        },
        { text: `Vo. Bo. ${'_'.repeat(44)}`, fontSize: 8, alignment: 'center', margin: [0, 24, 0, 0] },
        { text: h.sign_direction, fontSize: 8, alignment: 'center', margin: [0, 2, 0, 0] },
      ],
    },
  ];

  return {
    pageSize: { width: PAGE.width, height: PAGE.height },
    pageOrientation: 'portrait',
    pageMargins: MARGINS,
    info: {
      title: snap.filename.replace(/\.pdf$/, ''),
      author: h.teacher_name,
      subject: `Hoja de actividades — ${h.course_name} — ${h.grade_name} ${h.section_name}`,
      creator: `${h.short_name} — Sistema de Planificación Docente`,
    },
    defaultStyle: { font: 'Roboto', fontSize: 9.5, lineHeight: 1.08 },
    header: headerFn,
    footer: (current, total) => ({
      columns: [
        { text: '' },
        { text: `Página ${current} de ${total}`, alignment: 'right', fontSize: 7.5, color: '#555555' },
      ],
      margin: [51, 16, 51, 0],
    }),
    images: logo ? { logo } : {},
    content,
  };
}

function render(docDef) {
  return new Promise((resolve, reject) => {
    const pages = {};
    const orig = docDef.pageBreakBefore;
    docDef.pageBreakBefore = (node, ...rest) => {
      if (node.id && node.id.startsWith('t|')) pages[node.id] = node.pageNumbers;
      return orig ? orig(node, ...rest) : false;
    };
    const doc = printer.createPdfKitDocument(docDef);
    const chunks = [];
    doc.on('data', c => chunks.push(c));
    doc.on('end', () => resolve({ buffer: Buffer.concat(chunks), pages }));
    doc.on('error', reject);
    doc.end();
  });
}

/**
 * Busca el primer bloque cuyos temas (dentro de una misma fila) continúan en otra página
 * y devuelve dónde dividirlo. Se procesa un corte a la vez porque cada corte desplaza
 * el resto del documento.
 */
function firstSplit(pages, projection, chunks) {
  for (const b of projection.blocks) {
    const parts = chunks[b.id] || [b.contents.map((c, i) => (c.topic ? i : null)).filter(i => i !== null)];
    for (let pi = 0; pi < parts.length; pi++) {
      const part = parts[pi];
      if (part.length < 2) continue;
      const first = pages[`t|${b.id}|${part[0]}`];
      if (!first || !first.length) continue;
      const startPage = Math.min(...first);
      for (let k = 1; k < part.length; k++) {
        const pg = pages[`t|${b.id}|${part[k]}`];
        if (pg && pg.length && Math.max(...pg) > startPage) {
          const next = parts.slice();
          next.splice(pi, 1, part.slice(0, k), part.slice(k));
          return { blockId: b.id, parts: next };
        }
      }
    }
  }
  return null;
}

/**
 * Genera el PDF. Si los temas de un bloque se reparten entre páginas, se vuelve a componer
 * la tabla con filas de continuación que indican el bloque mensual al que pertenecen.
 */
async function generate(snapshot) {
  const chunks = {};
  let result = await render(buildDoc(snapshot, chunks));
  for (let i = 0; i < 60; i++) {
    const s = firstSplit(result.pages, snapshot.projection, chunks);
    if (!s) break;
    chunks[s.blockId] = s.parts;
    result = await render(buildDoc(snapshot, chunks));
  }
  return result.buffer;
}

// ------------------------------------------------------------------ Hoja de sellos
/** Hoja de sellos: sólo datos del docente, grado y curso; el resto se llena a mano. Mismo diseño que la hoja de actividades. */
function buildSellosDoc(snap) {
  const h = snap.header;
  const logo = h.logo ? `data:${h.logo_mime};base64,${h.logo}` : null;
  const ROWS = 12;
  const head = ['No.', 'Nombre de la actividad', 'Fecha', 'Firma o sello docente']
    .map(t => ({ text: t, bold: true, alignment: 'center', fillColor: C.headerGreen, color: '#FFFFFF', fontSize: 10, margin: [0, 2, 0, 2] }));
  const body = [head];
  for (let i = 1; i <= ROWS; i++) body.push([{ text: String(i), alignment: 'center', fontSize: 9, color: '#555555', margin: [0, 16, 0, 0] }, '', '', '']);
  const line = '_'.repeat(34);
  return {
    pageSize: { width: PAGE.width, height: PAGE.height }, pageOrientation: 'portrait', pageMargins: MARGINS,
    info: { title: snap.filename.replace(/\.pdf$/, ''), author: h.teacher_name, subject: `Hoja de sellos — ${h.course_name} — ${h.grade_name}`, creator: `${h.short_name} — Sistema de Planificación Docente` },
    defaultStyle: { font: 'Roboto', fontSize: 9.5, lineHeight: 1.08 },
    header: headerFor(h, logo),
    footer: () => ({ text: '' }),
    images: logo ? { logo } : {},
    content: [
      { text: `HOJA DE SELLOS ${ORDINAL[h.bimester_number] || ''} BIMESTRE`.replace(/\s+/g, ' '), bold: true, alignment: 'center', fontSize: 12, margin: [0, 0, 0, 1] },
      { text: `Ciclo escolar ${h.school_year_name}`, alignment: 'center', fontSize: 9, margin: [0, 0, 0, 10] },
      {
        fontSize: 9.5,
        table: { widths: ['*'], body: [[{ fillColor: C.infoFill, margin: [8, 5, 8, 5], stack: [
          { text: [{ text: 'DOCENTE: ', bold: true }, h.teacher_name, { text: '    GRADO: ', bold: true }, h.grade_name] },
          { text: [{ text: 'CURSO: ', bold: true }, h.course_name], margin: [0, 3, 0, 0] },
          { text: [{ text: 'ALUMNO(A): ', bold: true }, '_'.repeat(70)], margin: [0, 8, 0, 0] },
        ] }]] },
        layout: { hLineWidth: () => 0.6, vLineWidth: () => 0.6, hLineColor: () => C.infoLine, vLineColor: () => C.infoLine },
        margin: [0, 0, 0, 14],
      },
      {
        table: { headerRows: 1, widths: [34, '*', 80, 120], heights: (r) => (r === 0 ? 22 : 46), body },
        layout: { hLineWidth: () => 0.6, vLineWidth: () => 0.6, hLineColor: () => C.grid, vLineColor: () => C.grid },
      },
      {
        unbreakable: true, margin: [0, 34, 0, 0],
        columns: [
          { stack: [{ text: `F. ${line}`, fontSize: 8, alignment: 'center' }, { text: 'Prof. ' + h.teacher_name, fontSize: 8, alignment: 'center', margin: [0, 2, 0, 0] }], width: '*' },
          { stack: [{ text: `F. ${line}`, fontSize: 8, alignment: 'center' }, { text: 'FIRMA DEL PADRE DE FAMILIA', fontSize: 8, alignment: 'center', margin: [0, 2, 0, 0] }], width: '*' },
        ],
        columnGap: 30,
      },
    ],
  };
}
function generateSellos(snapshot) {
  return new Promise((resolve, reject) => {
    const doc = printer.createPdfKitDocument(buildSellosDoc(snapshot));
    const chunks = [];
    doc.on('data', c => chunks.push(c)); doc.on('end', () => resolve(Buffer.concat(chunks))); doc.on('error', reject);
    doc.end();
  });
}

module.exports = { generate, generateSellos, ORDINAL };
