'use strict';
/**
 * «Plan bimestral en PDF», con el formato de la referencia PLANIFICACION_enlazada_5.pdf:
 *  - Papel legal 612 × 1008 pt, vertical; contenido de 492 pt centrado (márgenes laterales de 60 pt).
 *  - Encabezado verde oscuro con el logo CyD y «PLAN BIMESTRAL {ciclo} - BIMESTRE {n}».
 *  - Franja verde claro con los datos generales.
 *  - 1. Competencia e indicadores · 2. Dosificación y desarrollo · 3. Plan de mejoramiento ·
 *    4. Observaciones del coordinador · 5. Control de entrega y revisión · firmas.
 *  - Pie: «BIM n» y «Página x de y».
 * Se dibuja con PDFKit (el motor de pdfmake) para medir cada celda y paginar con control:
 * la semana y la evaluación del bloque son celdas agrupadas que nunca cruzan una página; si un
 * bloque o una semana continúan, se repite su contexto con la indicación «continuación».
 * Los datos salen de la planificación guardada; nada se completa con valores del ejemplo.
 */
const fs = require('fs');
const path = require('path');
const PDFDocument = require('@foliojs-fork/pdfkit');
const vfs = require('pdfmake/build/vfs_fonts');

const fontBuf = n => Buffer.from((vfs.pdfMake ? vfs.pdfMake.vfs : vfs)[n], 'base64');
const FONTS = { R: fontBuf('Roboto-Regular.ttf'), B: fontBuf('Roboto-Medium.ttf'), I: fontBuf('Roboto-Italic.ttf'), BI: fontBuf('Roboto-MediumItalic.ttf') };
const LOGO_FILE = path.join(__dirname, '..', 'assets', 'logo-plan-bimestral.png');
const LOGO = fs.existsSync(LOGO_FILE) ? fs.readFileSync(LOGO_FILE) : null;

const C = {
  dark: '#46682F', green: '#7FA838', light: '#D9E8BC', line: '#A6A6A6', yellow: '#FFF9C4', grey: '#E7E6E6',
  text: '#1A1A1A', muted: '#4D4D4D', white: '#FFFFFF',
};
const PAGE = { w: 612, h: 1008 };
const X0 = 60, W = 492;
const TOP = 24, BOTTOM = PAGE.h - 42;
const LINE_GAP = 0.8;
const BIMESTRE = { 1: 'Primero', 2: 'Segundo', 3: 'Tercero', 4: 'Cuarto' };
const RESULT = { aprobado: 'Aprobado', con_observaciones: 'Aprobado con observaciones', requiere_correcciones: 'Requiere correcciones' };
// Proporciones de la tabla principal: 7 / 13 / 24.5 / 24.5 / 21.5 / 9.5 %
const MAIN = [7, 13, 24.5, 24.5, 21.5, 9.5].map(p => W * p / 100);
const MIN_SPLIT = 36;   // altura mínima para dividir una fila al final de una página

function fmtDate(d) { if (!d) return ''; const [y, m, dd] = String(d).slice(0, 10).split('-'); return `${dd}/${m}/${y}`; }

// ------------------------------------------------------------------ celdas y medición
// Párrafo: { text, f: 'R'|'B'|'I'|'BI', size, color, align, gap }   Celda: { paras, fill, padX, padY, valign }
const P = (text, o = {}) => ({ text: String(text), f: 'R', size: 9, color: C.text, align: 'left', gap: 0, ...o });
const cell = (paras, o = {}) => ({ paras: paras.filter(p => p && p.text !== ''), padX: 4, padY: 3, valign: 'top', ...o });

class Measurer {
  constructor(doc) { this.doc = doc; }
  para(p, width) {
    this.doc.font(p.f).fontSize(p.size);
    return this.doc.heightOfString(p.text || ' ', { width, align: p.align, lineGap: LINE_GAP });
  }
  cell(c, w) {
    const inner = Math.max(4, w - 2 * c.padX);
    return 2 * c.padY + c.paras.reduce((a, p, i) => a + (i ? p.gap : 0) + this.para(p, inner), 0);
  }
  // Divide un párrafo para que quepa en maxH (por palabras). Devuelve [cabeza, resto] o null.
  splitPara(p, width, maxH) {
    const toks = p.text.split(/(?<=\s)/);
    let lo = 0, hi = toks.length;
    while (lo < hi) {
      const mid = Math.ceil((lo + hi) / 2);
      if (this.para({ ...p, text: toks.slice(0, mid).join('') }, width) <= maxH) lo = mid; else hi = mid - 1;
    }
    if (lo === 0) return null;
    if (lo === toks.length) return [p, null];
    return [{ ...p, text: toks.slice(0, lo).join('').trimEnd() }, { ...p, gap: 0, text: toks.slice(lo).join('') }];
  }
  // Divide una celda en [cabeza que cabe en maxH, resto]. cabeza = null si no cabe nada.
  splitCell(c, w, maxH, contLabel) {
    const inner = Math.max(4, w - 2 * c.padX);
    let avail = maxH - 2 * c.padY;
    const head = [];
    let i = 0;
    for (; i < c.paras.length; i++) {
      const p = c.paras[i];
      const gap = head.length ? p.gap : 0;
      const h = gap + this.para(p, inner);
      if (h <= avail) { head.push(p); avail -= h; continue; }
      const sp = avail - gap > 0 ? this.splitPara(p, inner, avail - gap) : null;
      if (sp) { head.push(sp[0]); i++; const rest = sp[1] ? [sp[1], ...c.paras.slice(i)] : c.paras.slice(i); return this.pack(c, head, rest, contLabel); }
      break;
    }
    if (i >= c.paras.length) return [c, null];
    return this.pack(c, head, c.paras.slice(i), contLabel);
  }
  pack(c, head, rest, contLabel) {
    if (!head.length) return [null, c];
    if (!rest.length) return [{ ...c, paras: head }, null];
    const tail = rest.map((p, k) => (k ? p : { ...p, gap: 0 }));
    return [{ ...c, paras: head }, { ...c, paras: contLabel ? [P('(continuación)', { f: 'I', size: 7.5, color: C.muted }), ...tail.map((p, k) => (k ? p : { ...p, gap: 2 }))] : tail }];
  }
}

// ------------------------------------------------------------------ páginas
class Layout {
  constructor(doc, snap) {
    this.doc = doc; this.m = new Measurer(doc); this.snap = snap;
    this.pages = []; this.y = TOP;
    this.newPage(true);
  }
  newPage(first = false) {
    this.ops = []; this.pages.push(this.ops); this.y = TOP;
    if (first) this.coverHeader(); else this.runningHeader();
  }
  space() { return BOTTOM - this.y; }
  draw(fn) { this.ops.push(fn); }

  // Dibujo de una celda (fondo, borde y texto) en la posición indicada
  drawCell(c, x, y, w, h, border = true) {
    this.draw(doc => {
      if (c.fill) doc.save().rect(x, y, w, h).fill(c.fill).restore();
      if (border) doc.save().lineWidth(0.5).strokeColor(C.line).rect(x, y, w, h).stroke().restore();
      const inner = Math.max(4, w - 2 * c.padX);
      const contentH = this.m.cell(c, w) - 2 * c.padY;
      let ty = y + c.padY;
      if (c.valign === 'middle') ty = y + Math.max(c.padY, (h - contentH) / 2);
      c.paras.forEach((p, i) => {
        if (i) ty += p.gap;
        if (p.runs) {
          // Etiqueta en negrita seguida del valor en texto normal, en el mismo párrafo
          p.runs.forEach((r, k) => {
            const more = k < p.runs.length - 1;
            doc.font(r.f).fontSize(p.size).fillColor(p.color);
            if (k === 0) doc.text(r.t, x + c.padX, ty, { width: inner, align: p.align, lineGap: LINE_GAP, continued: more });
            else doc.text(r.t, { continued: more });
          });
        } else {
          doc.font(p.f).fontSize(p.size).fillColor(p.color).text(p.text, x + c.padX, ty, { width: inner, align: p.align, lineGap: LINE_GAP });
        }
        ty += this.m.para(p, inner);
      });
    });
  }
  drawRow(cells, widths, h) {
    let x = X0;
    const y = this.y;
    cells.forEach((c, i) => { this.drawCell(c, x, y, widths[i], h); x += widths[i]; });
    this.y += h;
  }
  rowHeight(cells, widths, min = 0) { return Math.max(min, ...cells.map((c, i) => this.m.cell(c, widths[i]))); }

  // ---------------------------------------------------------------- encabezados
  coverHeader() {
    const s = this.snap, h = s.header;
    const title = P(`PLAN BIMESTRAL ${h.school_year_name} - BIMESTRE ${h.bimester_number}`, { f: 'B', size: 15, color: C.white });
    const titleX = X0 + 108, titleW = W - 108 - 14;
    const th = this.m.para(title, titleW);
    const H = Math.max(58, th + 22);
    const y = this.y;
    this.draw(doc => {
      doc.save().rect(X0, y, W, H).fill(C.dark).restore();
      if (LOGO) doc.image(LOGO, X0 + 4, y + (H - 54) / 2, { fit: [96, 54], align: 'center', valign: 'center' });
      doc.font('B').fontSize(15).fillColor(C.white).text(title.text, titleX, y + (H - th) / 2, { width: titleW, lineGap: LINE_GAP });
    });
    this.y += H + 5;
    // Franja de datos generales: izquierda establecimiento y curso; centro docente, grado y sección; derecha bimestre y período
    const widths = [220, 120, 152];
    const period = h.bimester_start || h.bimester_end ? `${fmtDate(h.bimester_start) || '—'} al ${fmtDate(h.bimester_end) || '—'}` : '';
    const rows = [
      [this.kv('Establecimiento:', h.institution_name), this.kv('Docente:', h.teacher_name), this.kv('Bimestre:', BIMESTRE[h.bimester_number] || String(h.bimester_number))],
      [this.kv('Curso:', h.course_name), this.kv('Grado:', h.grade_name, 'Sección:', h.section_name), this.kv('Período (inicio - fin):', period)],
    ];
    for (const r of rows) {
      const rh = this.rowHeight(r, widths, 15);
      let x = X0; const yy = this.y;
      r.forEach((c, i) => {
        const cx = x;
        this.draw(doc => { doc.save().rect(cx, yy, widths[i], rh).fill(C.light).restore(); doc.save().lineWidth(1.4).strokeColor(C.white).rect(cx, yy, widths[i], rh).stroke().restore(); });
        this.drawCell(c, cx, yy, widths[i], rh, false);
        x += widths[i];
      });
      this.y += rh;
    }
    this.y += 8;
  }
  // Etiqueta en negrita + valor (puede ocupar varias líneas)
  kv(label, value, label2, value2) {
    // Se mide con todo el texto en negrita (más ancho), así la celda nunca queda corta
    const run = (l, v) => P(`${l} ${v || ''}`.trim(), { f: 'B', size: 8.5, runs: [{ t: `${l} `, f: 'B' }, { t: v || '', f: 'R' }] });
    const paras = [run(label, value)];
    if (label2) paras.push({ ...run(label2, value2), gap: 1 });
    return cell(paras, { padY: 4, padX: 6, valign: 'middle' });
  }
  runningHeader() {
    const h = this.snap.header;
    const t = P(`PLAN BIMESTRAL ${h.school_year_name} - BIMESTRE ${h.bimester_number}`, { f: 'B', size: 9.5, color: C.white });
    const sub = P(`${h.course_name} · ${h.grade_name} · Sección ${h.section_name} · Docente: ${h.teacher_name}`, { size: 8, color: C.white, gap: 1 });
    const c = cell([t, sub], { fill: C.dark, padX: 8, padY: 5 });
    const w = W;
    const hh = this.m.cell(c, w);
    const y = this.y;
    this.draw(doc => doc.save().rect(X0, y, W, hh).fill(C.dark).restore());
    this.drawCell(c, X0, y, w, hh, false);
    this.y += hh + 8;
  }

  // ---------------------------------------------------------------- secciones
  sectionTitle(text, cont) {
    const c = cell([P(`${text}${cont ? '  (continuación)' : ''}`, { f: 'B', size: 9.5, color: C.white })], { fill: C.green, padX: 7, padY: 3.5 });
    const h = this.m.cell(c, W);
    this.drawCell(c, X0, this.y, W, h, false);
    this.y += h;
  }
  titleHeight() { return this.m.cell(cell([P('X', { f: 'B', size: 9.5 })], { padY: 3.5 }), W); }
  headerRow(labels, widths) {
    const cells = labels.map(l => cell([P(l, { f: 'B', size: l.length <= 8 ? 7 : 7.5, color: C.white, align: 'center' })], { fill: C.dark, padX: 1.5, padY: 4, valign: 'middle' }));
    return { cells, h: this.rowHeight(cells, widths, 16) };
  }
  gap(n = 9) { this.y += n; }

  /**
   * Tabla simple con título y encabezado de columnas. Las filas crecen con el texto; si una fila no cabe,
   * se divide (texto marcado como continuación) y la tabla sigue en la página siguiente con su título y encabezado.
   */
  flowTable(title, labels, widths, rows, { minRow = 18 } = {}) {
    const head = labels ? this.headerRow(labels, widths) : { h: 0 };
    const firstH = rows.length ? Math.min(this.rowHeight(rows[0], widths, minRow), 60) : minRow;
    const start = (cont) => {
      if (this.space() < this.titleHeight() + head.h + Math.min(firstH, MIN_SPLIT)) this.newPage();
      this.sectionTitle(title, cont);
      if (labels) this.drawRow(head.cells, widths, head.h);
    };
    start(false);
    for (let row of rows) {
      for (let guard = 0; guard < 200; guard++) {
        const h = this.rowHeight(row, widths, minRow);
        if (h <= this.space()) { this.drawRow(row, widths, h); break; }
        if (this.space() >= MIN_SPLIT) {
          const parts = row.map((c, i) => this.m.splitCell(c, widths[i], this.space(), true));
          if (parts.some(p => p[0])) {
            const headCells = parts.map((p, i) => p[0] || { ...row[i], paras: [] });
            this.drawRow(headCells, widths, this.space());
            row = parts.map((p, i) => p[1] || { ...row[i], paras: [] });
            this.newPage(); start(true);
            continue;
          }
        }
        this.newPage(); start(true);
      }
    }
  }
}

// ------------------------------------------------------------------ 2. Dosificación (celdas agrupadas)
function dosificacion(L, snap) {
  const blocks = snap.plan.blocks;
  const head = L.headerRow(['SEMANA', 'FECHAS', 'CONTENIDOS DE APRENDIZAJE - PÁG. LIBRO', 'ACTIVIDADES DE APRENDIZAJE', 'ACTIVIDADES DE EVALUACIÓN', 'PERÍODOS'], MAIN);
  const TITLE = '2.  DOSIFICACIÓN Y DESARROLLO DEL BIMESTRE';
  const weekCell = (n) => cell([P(String(n), { f: 'B', size: 12, color: C.white, align: 'center' })], { fill: C.green, valign: 'middle', padX: 2 });
  const dateCell = (w, compact) => cell(compact
    ? [P(`${fmtDate(w.start_date)} al ${fmtDate(w.end_date)}`, { f: 'B', size: 7, align: 'center' })]
    : [P(fmtDate(w.start_date) || ' ', { f: 'B', size: 8, align: 'center' }), P('al', { f: 'I', size: 7.5, color: C.muted, align: 'center', gap: 2 }), P(fmtDate(w.end_date) || ' ', { f: 'B', size: 8, align: 'center', gap: 2 })],
  { fill: C.light, valign: 'middle', padX: 2 });
  const weekMin = L.m.cell(dateCell({ start_date: '2000-01-01', end_date: '2000-01-01' }), MAIN[1]);
  let titleDrawn = false;

  blocks.forEach((b, bi) => {
    const label = b.label || `Bloque ${bi + 1}`;
    const range = b.start_date || b.end_date ? ` · ${fmtDate(b.start_date)} al ${fmtDate(b.end_date)}` : '';
    const evParas = b.evaluation.description ? [P(b.evaluation.description, { size: 9, align: 'center' })] : [];
    // Filas lógicas: una por contenido (contenido + páginas | actividades | períodos)
    const weeks = (b.weeks.length ? b.weeks : [null]).map(w => ({
      w, rows: (w && w.contents.length ? w.contents : [null]).map(c => (c ? [
        cell([P(c.topic || ' ', { size: 9 }), c.pages ? P(`Páginas: ${c.pages}`, { f: 'I', size: 8.5, color: C.muted, gap: 2 }) : null]),
        cell(c.activities.filter(a => a.text).map((a, i) => P(`•  ${a.text}`, { size: 9, gap: i ? 3 : 0 }))),
        cell(c.periods !== null && c.periods !== undefined ? [P(String(c.periods), { size: 9.5, align: 'center' })] : [], { valign: 'middle' }),
      ] : [cell([]), cell([]), cell([])])),
    }));

    let evalRest = null;     // texto pendiente si la descripción de la evaluación no cupo en la página
    let seg = null;          // segmento del bloque en la página actual
    const openSeg = (cont) => {
      const need = L.titleHeight() * (titleDrawn ? 0 : 1) + 18 + head.h + Math.max(weekMin, 20);
      if (L.space() < need) { L.newPage(); titleDrawn = false; }
      if (!titleDrawn) { L.sectionTitle(TITLE, bi > 0 || cont); titleDrawn = true; }
      const band = cell([P(`${label}${range}${cont ? '  (continuación)' : ''}`, { f: 'B', size: 8.5, color: C.dark })], { fill: C.light, padX: 7, padY: 3 });
      const bh = L.m.cell(band, W);
      L.drawCell(band, X0, L.y, W, bh); L.y += bh;
      L.drawRow(head.cells, MAIN, head.h);
      seg = { top: L.y, rows: [], cont };
    };
    const closeWeek = (wk) => {
      const rows = seg.rows.filter(r => r.wk === wk);
      if (!rows.length) return;
      const sum = rows.reduce((a, r) => a + r.h, 0);
      if (sum < weekMin) { const extra = Math.min(weekMin - sum, L.space()); rows[rows.length - 1].h += extra; L.y += extra; }
    };
    const contLabel = () => P('(continuación)', { f: 'I', size: 7.5, color: C.muted, align: 'center' });
    const closeSeg = () => {
      const rowsH = seg.rows.reduce((a, r) => a + r.h, 0);
      // Evaluación del bloque: una sola celda que abarca las filas del bloque en esta página.
      // En una continuación se repite (o se completa) con la indicación «continuación»; el registro es uno solo.
      const pending = !!evalRest;
      const isRepeat = seg.cont && !pending;
      let paras;
      if (!seg.cont) paras = evParas;
      else if (pending) paras = [contLabel(), ...evalRest.map((p, i) => (i ? p : { ...p, gap: 2 }))];
      else paras = evParas.length ? [contLabel(), ...evParas.map((p, i) => (i ? p : { ...p, gap: 2 }))] : [];
      let ev = cell(paras, { valign: 'middle' });
      const stretch = (extra) => { extra = Math.min(extra, L.space()); if (extra > 0 && seg.rows.length) { seg.rows[seg.rows.length - 1].h += extra; L.y += extra; } };
      const need = L.m.cell(ev, MAIN[4]);
      if (need <= rowsH) evalRest = null;
      else if (need <= rowsH + L.space()) { stretch(need - rowsH); evalRest = null; }
      else if (isRepeat) {
        // Repetición que no cabe completa: se remite a la página anterior (la información ya se mostró)
        ev = cell([contLabel(), P(`Evaluación del bloque «${label}»: ver página anterior.`, { size: 8, align: 'center', gap: 2 })], { valign: 'middle' });
        stretch(L.m.cell(ev, MAIN[4]) - rowsH);
      } else {
        // Descripción extensa: se muestra lo que cabe junto a las filas y el resto continúa en la página siguiente
        if (rowsH < 60) stretch(60 - rowsH);
        const avail = seg.rows.reduce((a, r) => a + r.h, 0);
        const [h1, h2] = L.m.splitCell(ev, MAIN[4], avail, false);
        ev = h1 || cell([]);
        evalRest = h2 ? h2.paras.filter(q => !(q.f === 'I' && q.text === '(continuación)')) : null;
      }
      const total = seg.rows.reduce((a, r) => a + r.h, 0);
      // Dibujo de las filas, la columna de semanas/fechas agrupada y la evaluación agrupada
      let y = seg.top;
      for (const r of seg.rows) {
        [2, 3, 5].forEach((ci, k) => {
          const x = X0 + MAIN.slice(0, ci).reduce((a, v) => a + v, 0);
          L.drawCell(r.cells[k], x, y, MAIN[ci], r.h);
        });
        r.y = y; y += r.h;
      }
      const groups = [];
      for (const r of seg.rows) { const g = groups[groups.length - 1]; if (g && g.wk === r.wk) g.h += r.h; else groups.push({ wk: r.wk, y: r.y, h: r.h, w: r.w, n: r.n }); }
      for (const g of groups) {
        if (!g.w) { L.drawCell(cell([]), X0, g.y, MAIN[0], g.h); L.drawCell(cell([], { fill: C.light }), X0 + MAIN[0], g.y, MAIN[1], g.h); continue; }
        L.drawCell(weekCell(g.n), X0, g.y, MAIN[0], g.h);
        const full = dateCell(g.w);
        L.drawCell(L.m.cell(full, MAIN[1]) <= g.h ? full : dateCell(g.w, true), X0 + MAIN[0], g.y, MAIN[1], g.h);
      }
      L.drawCell(ev, X0 + MAIN.slice(0, 4).reduce((a, v) => a + v, 0), seg.top, MAIN[4], total);
      seg = null;
    };
    const breakPage = () => { closeSeg(); L.newPage(); titleDrawn = false; openSeg(true); };

    openSeg(false);
    weeks.forEach(({ w, rows }, wi) => {
      const wk = `${bi}|${wi}`;
      const n = w ? w.number : null;
      // La semana empieza donde quepa al menos su celda de fechas
      if (seg.rows.length && L.space() < Math.max(weekMin, Math.min(L.rowHeight(rows[0], [MAIN[2], MAIN[3], MAIN[5]], 16), MIN_SPLIT))) breakPage();
      for (let r of rows) {
        for (let guard = 0; guard < 200; guard++) {
          const widths = [MAIN[2], MAIN[3], MAIN[5]];
          const h = L.rowHeight(r, widths, 16);
          if (h <= L.space()) { seg.rows.push({ cells: r, h, wk, w, n }); L.y += h; break; }
          if (L.space() >= MIN_SPLIT) {
            const parts = r.map((c, i) => L.m.splitCell(c, widths[i], L.space(), i < 2));
            if (parts.some(p => p[0]) && parts.some(p => p[1])) {
              const headCells = parts.map((p, i) => p[0] || { ...r[i], paras: [] });
              seg.rows.push({ cells: headCells, h: L.space(), wk, w, n }); L.y = BOTTOM;
              // El resto del contenido continúa; los períodos no se repiten
              r = parts.map((p, i) => (i === 2 ? cell([]) : (p[1] || cell([]))));
              closeWeek(wk); breakPage();
              continue;
            }
          }
          closeWeek(wk); breakPage();
        }
      }
      closeWeek(wk);
    });
    // Si la descripción de la evaluación no cupo, continúa en la página siguiente
    closeSeg();
    let guard = 0;
    while (evalRest && guard++ < 20) {
      L.newPage(); titleDrawn = false; openSeg(true);
      const evCell = cell(evalRest, { valign: 'middle' });
      const need = Math.min(L.m.cell(evCell, MAIN[4]) + 10, L.space());
      seg.rows.push({ cells: [cell([]), cell([]), cell([])], h: need, wk: 'x', w: null, n: null }); L.y += need;
      closeSeg();
    }
    L.gap(4);
  });
  if (!blocks.length) {
    L.sectionTitle(TITLE);
    L.drawRow(head.cells, MAIN, head.h);
    const empty = MAIN.map(() => cell([]));
    for (let i = 0; i < 3; i++) L.drawRow(empty, MAIN, 18);
  }
}

// ------------------------------------------------------------------ documento
function build(snap) {
  const doc = new PDFDocument({
    size: [PAGE.w, PAGE.h], margin: 0, autoFirstPage: false, bufferPages: true,
    info: { Title: snap.filename.replace(/\.pdf$/, ''), Author: snap.header.teacher_name, Subject: `Plan bimestral — ${snap.header.course_name} — ${snap.header.grade_name} ${snap.header.section_name}`, Creator: 'Sistema de Planificación Docente — Colegio CyD' },
  });
  for (const [k, b] of Object.entries(FONTS)) doc.registerFont(k, b);
  doc.addPage(); // página temporal para medir
  const L = new Layout(doc, snap);
  const p = snap.plan;

  // 1. Competencia e indicadores (dos listas independientes: no se emparejan por posición)
  const list = (items) => items.filter(t => t.trim()).map((t, i, a) => P(a.length > 1 ? `${i + 1}.  ${t}` : t, { size: 9, gap: i ? 6 : 0 }));
  const comp = list(p.competencies), ind = list(p.indicators);
  const rows1 = comp.length || ind.length ? [[cell(comp), cell(ind)]] : [[cell([]), cell([])], [cell([]), cell([])], [cell([]), cell([])]];
  L.flowTable('1.  COMPETENCIA E INDICADORES DE LOGRO', ['COMPETENCIA', 'INDICADOR DE LOGRO'], [W / 2, W / 2], rows1, { minRow: 22 });
  L.gap();

  // 2. Dosificación
  dosificacion(L, snap);
  L.gap(5);

  // 3. Plan de mejoramiento
  const w3 = [196, 132, 104, 60];
  const rows3 = p.improvement.map(a => [
    cell([P(a.description, { size: 9 })]), cell([P(a.strategies, { size: 9 })]), cell([P(a.students, { size: 9 })]), cell([P(a.date, { size: 9, align: 'center' })], { valign: 'middle' }),
  ]);
  while (rows3.length < 3) rows3.push(w3.map(() => cell([])));
  L.flowTable('3.  PLAN DE MEJORAMIENTO', ['DESCRIPCIÓN', 'ACCIONES / ESTRATEGIAS', 'ESTUDIANTES ATENDIDOS', 'FECHA'], w3, rows3, { minRow: 18 });
  L.gap();

  // 4. Observaciones del coordinador (sólo el registro institucional; nunca notas del docente)
  L.flowTable('4.  OBSERVACIONES DEL COORDINADOR', null, [W], [[cell(snap.review.observations ? [P(snap.review.observations, { size: 9.5 })] : [], { padX: 6, padY: 5 })]], { minRow: 72 });
  L.gap();

  // 5. Control de entrega y revisión + firmas (se mantienen juntos)
  const w5 = [96, 96, 160, 140];
  const r = snap.review;
  const head5 = L.headerRow(['FECHA DE ENTREGA', 'FECHA DE REVISIÓN', 'RESULTADO DE LA REVISIÓN', 'REVISADO POR (COORDINADOR/A)'], w5);
  const row5 = [
    cell([P(fmtDate(r.delivered_at), { size: 9, align: 'center' })], { fill: C.yellow, valign: 'middle' }),
    cell([P(fmtDate(r.reviewed_at), { size: 9, align: 'center' })], { fill: C.yellow, valign: 'middle' }),
    cell([P(r.result_label, { f: 'B', size: 9, align: 'center', color: C.muted })], { fill: C.grey, valign: 'middle' }),
    cell([P(r.reviewer_name, { size: 9, align: 'center' })], { fill: C.yellow, valign: 'middle' }),
  ];
  const h5 = L.rowHeight(row5, w5, 22);
  const wSig = [196, 196, 100];
  const sigLabel = (t) => cell([P(t, { size: 7.5, color: C.muted, align: 'center' })], { padY: 4 });
  const sigs = [sigLabel(`Firma del docente: ${snap.header.teacher_name}`), sigLabel(r.reviewer_name ? `Firma del coordinador(a): ${r.reviewer_name}` : 'Firma del coordinador(a)'), sigLabel('Vo.Bo. Dirección / Sello')];
  const sigH = Math.max(66, L.rowHeight(sigs, wSig) + 44);
  if (L.space() < L.titleHeight() + head5.h + h5 + 10 + sigH) L.newPage();
  L.sectionTitle('5.  CONTROL DE ENTREGA Y REVISIÓN');
  L.drawRow(head5.cells, w5, head5.h);
  L.drawRow(row5, w5, h5);
  L.gap(10);
  let x = X0; const y = L.y;
  sigs.forEach((c, i) => {
    const lh = L.m.cell(c, wSig[i]);
    const cx = x;
    L.draw(d => d.save().lineWidth(0.5).strokeColor(C.line).rect(cx, y, wSig[i], sigH).stroke().restore());
    L.drawCell(c, cx, y + sigH - lh, wSig[i], lh, false);
    x += wSig[i];
  });
  L.y += sigH;

  // Pintado final: ahora se conoce el total de páginas
  const total = L.pages.length;
  L.pages.forEach((ops, i) => {
    if (i > 0) doc.addPage();
    ops.forEach(fn => fn(doc));
    doc.font('B').fontSize(7.5).fillColor('#333333').text(`BIM ${snap.header.bimester_number}`, X0, PAGE.h - 28, { width: 120, lineBreak: false });
    doc.font('R').fontSize(7.5).fillColor('#333333').text(`Página ${i + 1} de ${total}`, X0 + W - 120, PAGE.h - 28, { width: 120, align: 'right', lineBreak: false });
  });
  return { doc, pages: total };
}

/** Genera el PDF y devuelve el Buffer. */
function generate(snap) {
  return new Promise((resolve, reject) => {
    let built;
    try { built = build(snap); } catch (e) { return reject(e); }
    const { doc } = built;
    const chunks = [];
    doc.on('data', c => chunks.push(c));
    doc.on('end', () => resolve(Buffer.concat(chunks)));
    doc.on('error', reject);
    doc.end();
  });
}

module.exports = { generate, BIMESTRE, RESULT };
