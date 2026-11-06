/**
 * Tiny, safe markdown → HTML for chat bubbles. No dependency.
 * Handles what the coach actually emits: **bold**, *italic*, `code`,
 * line breaks and simple "- " lists. Everything is HTML-escaped first,
 * so the output is safe to use with v-html.
 */
const ESCAPE = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, (c) => ESCAPE[c]);
}

function inline(text) {
  return text
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    .replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>')
    .replace(/`([^`]+)`/g, '<code class="font-mono-data text-[0.85em] bg-white/10 rounded px-1">$1</code>');
}

/**
 * Documentos largos (las condiciones de uso): las líneas de un mismo párrafo se juntan, como en Markdown.
 * En el chat cada salto es un salto; en el Legal partía las frases a mitad («Al pulsar / Acepto…»,
 * revisión del 2026-10-21) porque el texto se escribe a 110 columnas. `---` es una línea separadora y
 * una línea sangrada bajo un punto de lista lo continúa.
 */
export function renderDocumento(src) {
  const lines = escapeHtml(src || '').split('\n');
  const out = [];
  let parrafo = [];
  let lista = null;
  const H = { 1: 'text-xl font-black text-white mt-4 mb-2', 2: 'text-lg font-black text-white mt-6 mb-2',
    3: 'text-sm font-black text-[#caf300] uppercase tracking-wider mt-4 mb-1' };
  const cerrarParrafo = () => { if (parrafo.length) { out.push(`<p class="my-2">${inline(parrafo.join(' '))}</p>`); parrafo = []; } };
  const cerrarLista = () => { if (lista) { out.push(`<ul class="list-disc pl-5 space-y-1 my-2">${lista.map((x) => `<li>${inline(x)}</li>`).join('')}</ul>`); lista = null; } };
  for (const raw of lines) {
    const line = raw.trimEnd();
    const head = line.match(/^(#{1,3})\s+(.*)$/);
    const li = line.match(/^[-*]\s+(.*)$/);
    if (!line.trim()) { cerrarParrafo(); cerrarLista(); continue; }
    if (/^-{3,}$/.test(line.trim())) { cerrarParrafo(); cerrarLista(); out.push('<hr class="my-4 border-white/10">'); continue; }
    if (head) { cerrarParrafo(); cerrarLista(); out.push(`<div class="${H[head[1].length]}">${inline(head[2])}</div>`); continue; }
    if (li) { cerrarParrafo(); (lista ||= []).push(li[1]); continue; }
    if (lista && /^\s+/.test(raw)) { lista[lista.length - 1] += ` ${line.trim()}`; continue; }
    cerrarLista();
    parrafo.push(line.trim());
  }
  cerrarParrafo();
  cerrarLista();
  return out.join('');
}

export function renderMarkdown(src) {
  const lines = escapeHtml(src || '').split('\n');
  const out = [];
  let inList = false;

  const H = {
    1: 'text-lg font-black text-white mt-3 mb-1',
    2: 'text-base font-black text-white mt-3 mb-1',
    3: 'text-sm font-black text-[#caf300] uppercase tracking-wider mt-2 mb-1',
  };

  for (const raw of lines) {
    const line = raw.trimEnd();
    const head = line.match(/^(#{1,3})\s+(.*)$/);
    const li = line.match(/^\s*[-*]\s+(.*)$/);
    if (head) {
      if (inList) { out.push('</ul>'); inList = false; }
      const lvl = head[1].length;
      out.push(`<div class="${H[lvl] || H[3]}">${inline(head[2])}</div>`);
    } else if (li) {
      if (!inList) { out.push('<ul class="list-disc pl-5 space-y-0.5 my-1">'); inList = true; }
      out.push(`<li>${inline(li[1])}</li>`);
    } else {
      if (inList) { out.push('</ul>'); inList = false; }
      out.push(line === '' ? '<br>' : `${inline(line)}<br>`);
    }
  }
  if (inList) out.push('</ul>');

  return out.join('').replace(/(<br>)+$/, '');
}
