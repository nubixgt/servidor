/* Editor del plan bimestral: datos generales, competencias, bloques mensuales, semanas,
   contenidos, actividades, evaluación única por bloque, apartados adicionales y revisión. */
'use strict';
(function () {
  const { store, api, toast, confirm, alert, fmtDate, fmtDateTime, uuid, ORD } = CYD;

  const EXTRAS = [
    { kind: 'mejoramiento', title: 'Actividad de mejoramiento', detailLabel: 'Texto adicional junto al título (opcional)', activityLabel: 'Descripción de la actividad', needsActivity: true },
    { kind: 'afectivo', title: 'Afectivo', detailLabel: 'Desglose junto al título (opcional)', detailHint: 'Aparece entre paréntesis después de “Afectivo”. Escriba aquí el desglose que defina el colegio.', activityLabel: 'Descripción (opcional)' },
    { kind: 'asistencia', title: 'Asistencia', detailLabel: 'Texto adicional junto al título (opcional)', activityLabel: 'Descripción (opcional)' },
    { kind: 'examen_final', title: 'Examen final', detailLabel: 'Texto adicional junto al título (opcional)', activityLabel: 'Descripción o temario (opcional)' },
  ];
  const TABS = [
    { id: 'generales', label: 'Datos generales', sections: ['generales'] },
    { id: 'competencias', label: 'Competencias e indicadores', sections: ['competencias'] },
    { id: 'bloques', label: 'Bloques y semanas', sections: ['bloques'] },
    { id: 'apartados', label: 'Apartados adicionales', sections: ['apartados'] },
    { id: 'revision', label: 'Revisión y PDF', sections: [] },
  ];

  const newCriterion = () => ({ id: uuid(), text: '', points: null });
  const newActivity = () => ({ id: uuid(), text: '' });
  const newContent = () => ({ id: uuid(), topic: '', pages: '', periods: null, activities: [newActivity()] });
  const newWeek = () => ({ id: uuid(), start_date: null, end_date: null, contents: [] });
  const newBlock = () => ({ id: uuid(), label: '', start_date: null, end_date: null, closed: false,
    evaluation: { id: uuid(), description: '', instructions: '', scoring_mode: 'criterios', total_points: null, due_date: null, criteria: [newCriterion()] }, weeks: [] });

  function move(arr, i, d) { const j = i + d; if (j < 0 || j >= arr.length) return; const [x] = arr.splice(i, 1); arr.splice(j, 0, x); }
  const clone = o => JSON.parse(JSON.stringify(o));

  CYD.components['view-editor'] = {
    data: () => ({
      store, loading: true, error: '', general: null, tree: null, version: 0, status: '', readyAt: null,
      validation: { issues: [], completeness: 0, errors: 0 }, documents: [], readonly: false,
      tab: 'generales', openBlocks: {}, openWeeks: {}, unlocked: false, showErrors: false,
      save: { state: 'idle', at: null, message: '' }, saving: false, savePromise: null, again: false, changeNo: 0, timer: null, suppress: false,
      restore: null, history: null, EXTRAS, TABS, ORD,
    }),
    computed: {
      planId() { return Number(store.route.params.id); },
      editable() { return !this.readonly && (this.status !== 'listo' || this.unlocked); },
      weekNumbers() { const m = {}; let n = 0; for (const b of this.tree.blocks) for (const w of b.weeks) m[w.id] = ++n; return m; },
      allWeeks() { const out = []; this.tree.blocks.forEach(b => b.weeks.forEach(w => out.push({ w, b }))); return out; },
      planPeriods() { return this.tree.blocks.reduce((a, b) => a + this.blockPeriods(b), 0); },
      contentCount() { return this.tree.blocks.reduce((a, b) => a + b.weeks.reduce((x, w) => x + w.contents.length, 0), 0); },
      errorsByTab() {
        const out = {};
        for (const t of TABS) out[t.id] = this.validation.issues.filter(i => i.level === 'error' && i.target && t.sections.includes(i.target.section)).length;
        return out;
      },
      dirty() { return ['dirty', 'saving', 'error', 'conflict'].includes(this.save.state); },
      title() { const g = this.general; return g ? `${g.course_name} — ${g.grade_name} — Sección ${g.section_name}` : ''; },
    },
    watch: {
      tree: { deep: true, handler() { if (this.suppress) return; this.changed(); } },
      'store.route.params.id'(n, o) { if (n && n !== o && store.route.name === 'editor') this.load(); },
    },
    async mounted() {
      store.leaveGuard = () => this.dirty;
      window.addEventListener('beforeunload', this.beforeUnload);
      await this.load();
    },
    beforeUnmount() {
      store.leaveGuard = null;
      window.removeEventListener('beforeunload', this.beforeUnload);
      clearTimeout(this.timer);
    },
    methods: {
      fmtDate, fmtDateTime,
      beforeUnload(e) { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } },
      backupKey() { return `cyd-plan-${this.planId}`; },
      // -------------------------------------------------- carga y guardado
      async load() {
        this.loading = true; this.error = '';
        try {
          const p = await api('GET', `/api/plans/${this.planId}`);
          this.apply(p);
          if (store.route.query.tab) this.tab = store.route.query.tab;
          // ¿Hay cambios no guardados de una sesión anterior en este navegador?
          try {
            const raw = localStorage.getItem(this.backupKey());
            if (raw && !this.readonly) {
              const b = JSON.parse(raw);
              if (JSON.stringify(b.tree) !== JSON.stringify(p.tree)) this.restore = b;
              else localStorage.removeItem(this.backupKey());
            }
          } catch { /* sin almacenamiento */ }
        } catch (e) { this.error = e.message; }
        finally { this.loading = false; }
      },
      apply(p) {
        this.suppress = true;
        this.general = p.general; this.tree = p.tree; this.version = p.general.version; this.status = p.general.status;
        this.readyAt = p.general.ready_at; this.validation = p.validation; this.documents = p.documents; this.readonly = p.readonly;
        this.save = { state: 'idle', at: p.general.updated_at, message: '' };
        if (this.status !== 'listo') this.unlocked = false;
        if (!Object.keys(this.openBlocks).length) {
          const firstOpen = this.tree.blocks.find(b => !b.closed) || this.tree.blocks[this.tree.blocks.length - 1];
          if (firstOpen) this.openBlocks[firstOpen.id] = true;
        }
        this.$nextTick(() => { this.suppress = false; });
      },
      changed() {
        if (!this.editable) return;
        this.changeNo++;
        this.save.state = 'dirty';
        try { localStorage.setItem(this.backupKey(), JSON.stringify({ version: this.version, tree: this.tree, at: new Date().toISOString() })); } catch { /* sin almacenamiento */ }
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.doSave(), 2500);
      },
      async doSave(manual = false) {
        clearTimeout(this.timer);
        if (!this.editable) return true;
        if (this.saving) { this.again = true; return this.savePromise; }
        if (this.save.state === 'conflict') { if (manual) toast('Hay un conflicto de versiones. Recargue la planificación.', 'error'); return false; }
        if (!manual && this.save.state !== 'dirty' && this.save.state !== 'error') return true;
        this.saving = true; this.save.state = 'saving';
        const no = this.changeNo;
        const body = { version: this.version, tree: clone(this.tree), edit_ready: this.unlocked };
        this.savePromise = (async () => {
          try {
            const r = await api('PUT', `/api/plans/${this.planId}`, body);
            this.version = r.version; this.status = r.status; this.readyAt = r.ready_at; this.validation = r.validation;
            this.save.at = r.updated_at;
            if (r.reverted_to_draft) {
              this.unlocked = false;
              alert({ title: 'La planificación volvió a borrador', body: ['Cambió información que aparece en la hoja de actividades. Revise la planificación y márquela nuevamente como lista cuando esté completa.'] });
            }
            if (this.changeNo === no) {
              this.save.state = 'saved'; this.save.message = '';
              try { localStorage.removeItem(this.backupKey()); } catch { /* sin almacenamiento */ }
            } else { this.save.state = 'dirty'; this.timer = setTimeout(() => this.doSave(), 1500); }
            return true;
          } catch (e) {
            if (e.status === 409 && e.data.code === 'VERSION_CONFLICT') {
              this.save.state = 'conflict';
              this.save.message = e.message;
            } else {
              this.save.state = 'error';
              this.save.message = e.message;
              if (e.status === 0) this.timer = setTimeout(() => this.doSave(), 15000);
            }
            return false;
          } finally {
            this.saving = false;
            if (this.again) { this.again = false; if (this.save.state === 'dirty') this.doSave(); }
          }
        })();
        return this.savePromise;
      },
      async flush() {
        if (this.saving) await this.savePromise;
        if (['dirty', 'error'].includes(this.save.state)) return this.doSave(true);
        return !['conflict'].includes(this.save.state);
      },
      async reloadLatest() {
        if (this.dirty && !await confirm({ title: 'Cargar la versión más reciente', body: ['Se mostrará la versión guardada en el servidor. Sus cambios no guardados de esta pestaña quedarán como copia en este navegador y podrá recuperarlos después.'], confirmText: 'Cargar versión del servidor' })) return;
        this.openBlocks = {}; this.restore = null;
        await this.load();
      },
      async applyRestore() {
        const b = this.restore;
        const body = ['Se reemplazará lo que se ve ahora con la copia local del ' + fmtDateTime(b.at) + '.'];
        if (b.version !== this.version) body.push('Atención: la planificación fue modificada después de esa copia (en otra pestaña o equipo). Si recupera la copia, esos cambios posteriores se sobrescribirán.');
        if (!await confirm({ title: 'Recuperar cambios no guardados', body, confirmText: 'Recuperar copia', danger: b.version !== this.version })) return;
        this.restore = null;
        this.tree = b.tree;
      },
      discardRestore() { this.restore = null; try { localStorage.removeItem(this.backupKey()); } catch { /* */ } },
      async unlock() {
        const ok = await confirm({ title: 'Editar una planificación lista', body: ['Podrá modificar la planificación.', 'Si cambia información que aparece en la hoja de actividades (bloques, contenidos, evaluaciones, punteos, fechas o apartados), la planificación volverá a estado Borrador y deberá validarla de nuevo.'], confirmText: 'Habilitar edición' });
        if (!ok) return;
        await api('POST', `/api/plans/${this.planId}/edit-ready`, {});
        this.unlocked = true;
      },
      async markReady() {
        if (!await this.flush()) { toast('Primero resuelva el problema de guardado.', 'error'); return; }
        try {
          const p = await api('POST', `/api/plans/${this.planId}/ready`, { version: this.version });
          this.apply(p);
          toast('Planificación marcada como lista. Ya puede generar la hoja de actividades.', 'ok', 6000);
        } catch (e) {
          if (e.status === 422 && e.data.validation) {
            this.validation = e.data.validation; this.showErrors = true; this.tab = 'revision';
            const n = e.data.validation.errors;
            alert({ title: 'Faltan datos para marcar como listo', body: [`Se encontraron ${n} incidencia(s). En “Revisión y PDF” puede ver cada una y acceder directamente al campo que requiere corrección.`] });
          } else toast(e.message, 'error');
        }
      },
      async returnToDraft() {
        const reason = await CYD.prompt({ title: 'Devolver a borrador', body: ['El docente verá el motivo en el registro de cambios de la planificación. El contenido académico no se modifica.'], label: 'Motivo', confirmText: 'Devolver a borrador' });
        if (!reason) return;
        try { await api('POST', `/api/admin/plans/${this.planId}/return-to-draft`, { reason }); toast('Planificación devuelta a borrador.', 'ok'); this.load(); }
        catch (e) { toast(e.message, 'error'); }
      },
      async toSheet() {
        if (!await this.flush()) { toast('No se pudo guardar. Resuelva el problema antes de generar la hoja.', 'error'); return; }
        CYD.go(`/plan/${this.planId}/hoja`);
      },
      // El plan bimestral en PDF usa exactamente lo guardado: primero se guardan los cambios pendientes.
      async toPlanPdf() {
        if (!await this.flush()) { toast('No se pudo guardar. Resuelva el problema antes de generar el plan bimestral.', 'error'); return; }
        CYD.go(`/plan/${this.planId}/plan-bimestral`);
      },
      addImprovement() { this.tree.improvement_actions.push({ id: uuid(), description: '', strategies: '', students: '', action_date: null }); },
      async loadHistory() { this.history = await api('GET', `/api/plans/${this.planId}/history`); },
      setTab(t) { this.tab = t; if (t === 'revision') { this.showErrors = true; this.flush(); } window.scrollTo({ top: 0 }); },
      // -------------------------------------------------- incidencias
      issuesWhere(f) {
        if (!this.showErrors) return [];
        const strict = ['weekId', 'contentId', 'part'];
        return this.validation.issues.filter(i => {
          const t = i.target || {};
          for (const [k, v] of Object.entries(f)) if (t[k] !== v) return false;
          for (const k of strict) if (!(k in f) && t[k] !== undefined) return false;
          return true;
        }).map(i => i.message);
      },
      blockPending(b) { return this.validation.issues.filter(i => i.level === 'error' && i.target && i.target.blockId === b.id).length; },
      targetId(t) {
        if (!t) return null;
        if (t.section === 'generales') return 'sec-generales';
        if (t.section === 'competencias') return t.id ? `li-${t.id}` : `sec-${t.field}`;
        if (t.section === 'apartados') return `x-${t.kind}-${t.field || 'applies'}`;
        if (t.section === 'bloques') {
          if (!t.blockId) return 'sec-bloques';
          if (t.contentId) return `c-${t.contentId}-${t.field || 'topic'}`;
          if (t.weekId) return t.part === 'contenidos' ? `w-${t.weekId}-contents` : `w-${t.weekId}-dates`;
          if (t.part === 'evaluacion') return `b-${t.blockId}-ev-${t.field}`;
          if (t.part === 'semanas') return `b-${t.blockId}-weeks`;
          return `b-${t.blockId}-${t.field || 'label'}`;
        }
        return null;
      },
      goTo(t) {
        if (!t) return;
        this.showErrors = true;
        this.tab = { generales: 'generales', competencias: 'competencias', apartados: 'apartados', bloques: 'bloques' }[t.section] || 'revision';
        if (t.blockId) this.openBlocks[t.blockId] = true;
        if (t.weekId) this.openWeeks[t.weekId] = true;
        this.$nextTick(() => setTimeout(() => {
          const el = document.getElementById(this.targetId(t)) || (t.blockId && document.getElementById(`blk-${t.blockId}`));
          if (!el) return;
          el.scrollIntoView({ behavior: 'smooth', block: 'center' });
          const f = el.matches('input,textarea,select,button') ? el : el.querySelector('input,textarea,select,button');
          if (f) setTimeout(() => f.focus({ preventScroll: true }), 300);
          el.classList.remove('flash'); void el.offsetWidth; el.classList.add('flash');
        }, 60));
      },
      // -------------------------------------------------- totales
      weekPeriods(w) { return w.contents.reduce((a, c) => a + (Number(c.periods) || 0), 0); },
      blockPeriods(b) { return b.weeks.reduce((a, w) => a + this.weekPeriods(w), 0); },
      blockContents(b) { return b.weeks.reduce((a, w) => a + w.contents.length, 0); },
      evalTotal(ev) {
        if (ev.scoring_mode === 'total') return ev.total_points;
        const p = ev.criteria.map(c => c.points).filter(x => x !== null && x !== '' && x !== undefined).map(Number);
        return p.length ? Math.round(p.reduce((a, b) => a + b, 0) * 100) / 100 : null;
      },
      // -------------------------------------------------- listas simples
      addItem(list, focusPrefix) { const it = { id: uuid(), text: '' }; list.push(it); this.focus(`${focusPrefix}${it.id}`); },
      async removeItem(list, i, what) {
        if (list[i].text && list[i].text.trim() && !await confirm({ title: `Eliminar ${what}`, body: [`Se eliminará: “${list[i].text.slice(0, 160)}”.`], confirmText: 'Eliminar', danger: true })) return;
        list.splice(i, 1);
      },
      move,
      focus(id) { this.$nextTick(() => { const el = document.getElementById(id); if (el) { el.focus(); } }); },
      // -------------------------------------------------- bloques
      addBlock() {
        const b = newBlock();
        this.tree.blocks.push(b);
        this.openBlocks[b.id] = true;
        this.focus(`b-${b.id}-label`);
        this.$nextTick(() => { const el = document.getElementById(`blk-${b.id}`); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
      },
      finishBlock(b, i) {
        b.closed = true;
        this.openBlocks[b.id] = false;
        if (i === this.tree.blocks.length - 1) this.addBlock();
        else { const nb = this.tree.blocks[i + 1]; this.openBlocks[nb.id] = true; }
        toast('Bloque organizado. Sigue siendo editable: ábralo cuando necesite cambiar algo.', 'ok');
      },
      toggleBlock(b) { this.openBlocks[b.id] = !this.openBlocks[b.id]; },
      async removeBlock(b, i) {
        const nW = b.weeks.length, nC = this.blockContents(b);
        const body = [`Se eliminará el bloque “${b.label || 'sin nombre'}” con:`, `• ${nW} semana(s) y ${nC} contenido(s) con sus actividades de aprendizaje.`, '• Su actividad de evaluación, criterios y punteos.'];
        if (!await confirm({ title: 'Eliminar bloque mensual', body, confirmText: 'Eliminar bloque', danger: true })) return;
        this.tree.blocks.splice(i, 1);
      },
      moveBlock(i, d) { move(this.tree.blocks, i, d); },
      // -------------------------------------------------- evaluación
      async setMode(ev, mode) {
        if (ev.scoring_mode === mode) return;
        if (mode === 'total' && ev.criteria.some(c => c.points !== null && c.points !== '')) {
          if (!await confirm({ title: 'Usar solo punteo total', body: ['Los punteos individuales de los criterios se descartarán. Los criterios se conservarán como texto y se usará un solo punteo total.'], confirmText: 'Cambiar' })) return;
        }
        if (mode === 'criterios' && ev.total_points !== null && ev.total_points !== '') {
          if (!await confirm({ title: 'Punteo por criterio', body: ['El punteo total escrito se descartará; el total se calculará sumando los punteos de cada criterio.'], confirmText: 'Cambiar' })) return;
        }
        ev.scoring_mode = mode;
        if (mode === 'total') ev.criteria.forEach(c => { c.points = null; }); else ev.total_points = null;
      },
      addCriterion(ev, prefix) { const c = newCriterion(); ev.criteria.push(c); this.focus(`${prefix}${c.id}`); },
      // -------------------------------------------------- semanas
      addWeek(b) {
        const w = newWeek(); b.weeks.push(w); this.openWeeks[w.id] = true;
        this.focus(`w-${w.id}-start`);
      },
      toggleWeek(w) { this.openWeeks[w.id] = !this.weekOpen(w); },
      weekOpen(w) { return this.openWeeks[w.id] !== false; },
      async moveWeek(b, i, d) {
        const j = i + d;
        if (j < 0 || j >= b.weeks.length) return;
        const test = b.weeks.slice(); const [x] = test.splice(i, 1); test.splice(j, 0, x);
        const seq = [];
        this.tree.blocks.forEach(bb => (bb === b ? test : bb.weeks).forEach(w => seq.push(w)));
        let conflict = null;
        for (let k = 1; k < seq.length; k++) {
          const p = seq[k - 1], c = seq[k];
          if (p.start_date && c.start_date && c.start_date < p.start_date) { conflict = [k, p, c]; break; }
        }
        if (conflict) {
          const [k, p, c] = conflict;
          const ok = await confirm({ title: 'Fechas incoherentes', body: [`Después de este cambio, la semana ${k + 1} (inicia ${fmtDate(c.start_date)}) quedaría después de la semana ${k} (inicia ${fmtDate(p.start_date)}).`, 'La numeración se recalculará y sus contenidos se mantendrán, pero deberá corregir las fechas antes de marcar el plan como listo.'], confirmText: 'Mover de todos modos' });
          if (!ok) return;
        }
        move(b.weeks, i, d);
      },
      async removeWeek(b, i) {
        const w = b.weeks[i];
        if ((w.contents.length || w.start_date) && !await confirm({ title: `Eliminar semana ${this.weekNumbers[w.id]}`, body: [`Se eliminará la semana con ${w.contents.length} contenido(s) y sus actividades de aprendizaje.`, 'Las semanas siguientes se renumerarán.'], confirmText: 'Eliminar semana', danger: true })) return;
        b.weeks.splice(i, 1);
      },
      // -------------------------------------------------- contenidos
      addContent(w) { const c = newContent(); w.contents.push(c); this.focus(`c-${c.id}-topic`); },
      async removeContent(w, i) {
        const c = w.contents[i];
        const has = c.topic.trim() || c.pages || c.periods || c.activities.some(a => a.text.trim());
        if (has && !await confirm({ title: 'Eliminar contenido', body: [`Se eliminará “${c.topic.slice(0, 120) || 'contenido sin tema'}” con ${c.activities.length} actividad(es) de aprendizaje.`], confirmText: 'Eliminar', danger: true })) return;
        w.contents.splice(i, 1);
      },
      async moveContentTo(c, fromWeek, fromBlock, toWeekId) {
        if (!toWeekId || toWeekId === fromWeek.id) return;
        const dest = this.allWeeks.find(x => x.w.id === toWeekId);
        if (dest.b !== fromBlock) {
          const ev = dest.b.evaluation;
          const ok = await confirm({ title: 'Mover a otro bloque mensual', body: [
            `El contenido pasará a la semana ${this.weekNumbers[toWeekId]}, del bloque “${dest.b.label || 'sin nombre'}”.`,
            `Quedará asociado a la evaluación de ese bloque: “${(ev.description || 'evaluación aún sin descripción').slice(0, 160)}”.`,
          ], confirmText: 'Mover contenido' });
          if (!ok) return;
        }
        fromWeek.contents.splice(fromWeek.contents.indexOf(c), 1);
        dest.w.contents.push(c);
        this.openWeeks[toWeekId] = true;
        toast(`Contenido movido a la semana ${this.weekNumbers[toWeekId]}.`, 'ok');
      },
      // -------------------------------------------------- apartados
      async setApplies(x, val) {
        if (val === false && (x.activity || x.total_points || x.criteria.length || x.detail)) {
          if (!await confirm({ title: 'Marcar como “No aplica”', body: ['La información escrita en este apartado se conservará, pero no aparecerá en la hoja mientras esté marcado como “No aplica”.'], confirmText: 'Marcar “No aplica”' })) return;
        }
        x.applies = val;
      },
    },
    template: `
<div>
  <loading-box v-if="loading"></loading-box>
  <div v-else-if="error" class="alert error"><span class="ic">⚠</span>{{ error }}</div>
  <template v-else>
    <!-- Encabezado fijo con estado de guardado -->
    <div class="editor-head">
      <div class="crumbs"><a :href="store.user.role === 'admin' ? '#/admin' : '#/inicio'">Inicio</a> › Plan bimestral</div>
      <div class="editor-title">
        <div style="min-width:0">
          <h1>{{ title }}</h1>
          <div class="sub">{{ ORD[general.bimester_number] }} bimestre · Ciclo {{ general.school_year_name }} · {{ general.teacher_name }}</div>
        </div>
        <div class="savebar">
          <status-badge :status="status"></status-badge>
          <span v-if="!readonly" class="save-state" :class="save.state" role="status" aria-live="polite">
            <span class="dot" aria-hidden="true"></span>
            <template v-if="save.state === 'saving'">Guardando…</template>
            <template v-else-if="save.state === 'saved'">Guardado · {{ fmtDateTime(save.at) }}</template>
            <template v-else-if="save.state === 'dirty'">Cambios sin guardar</template>
            <template v-else-if="save.state === 'error'">No se pudo guardar</template>
            <template v-else-if="save.state === 'conflict'">Conflicto de versiones</template>
            <template v-else>Último guardado: {{ fmtDateTime(save.at) }}</template>
          </span>
          <button v-if="editable" class="btn sm" @click="doSave(true)" :disabled="saving">💾 Guardar borrador</button>
          <button v-if="!readonly && status !== 'listo'" class="btn sm primary" @click="markReady">✓ Marcar como listo</button>
          <button v-if="!readonly && status === 'listo' && !unlocked" class="btn sm" @click="unlock">✎ Editar planificación</button>
          <button v-if="readonly && status === 'listo'" class="btn sm danger" @click="returnToDraft">Devolver a borrador</button>
          <button class="btn sm soft" @click="toSheet">📄 Hoja de actividades PDF</button>
          <button class="btn sm soft" @click="toPlanPdf">📘 Plan bimestral PDF</button>
        </div>
      </div>
      <div class="tabs" role="tablist" aria-label="Secciones del plan">
        <button v-for="t in TABS" :key="t.id" role="tab" :aria-selected="tab === t.id" @click="setTab(t.id)">
          {{ t.label }}<span v-if="showErrors && errorsByTab[t.id]" class="count" :aria-label="errorsByTab[t.id] + ' pendientes'">{{ errorsByTab[t.id] }}</span></button>
      </div>
    </div>

    <div v-if="save.state === 'error'" class="alert error" style="margin-bottom:14px" role="alert"><span class="ic">⚠</span>
      <div style="flex:1"><b>No se pudo guardar.</b> {{ save.message }} Lo escrito se conserva temporalmente en este navegador.</div>
      <button class="btn sm" @click="doSave(true)">Reintentar</button></div>
    <div v-if="save.state === 'conflict'" class="alert warn" style="margin-bottom:14px" role="alert"><span class="ic">⚠</span>
      <div style="flex:1"><b>Conflicto de versiones.</b> {{ save.message }} Sus cambios de esta pestaña no se guardaron para no sobrescribir la otra versión.</div>
      <button class="btn sm" @click="reloadLatest">Cargar versión más reciente</button></div>
    <div v-if="restore" class="alert info" style="margin-bottom:14px"><span class="ic">ℹ</span>
      <div style="flex:1">Se encontraron cambios no guardados en este navegador del {{ fmtDateTime(restore.at) }}.</div>
      <button class="btn sm" @click="applyRestore">Recuperar</button><button class="btn sm ghost" @click="discardRestore">Descartar</button></div>
    <div v-if="status === 'listo' && !unlocked && !readonly" class="alert ok" style="margin-bottom:14px"><span class="ic">✓</span>
      <div>Planificación <b>lista</b> desde el {{ fmtDateTime(readyAt) }}. Está protegida contra cambios accidentales; use “Editar planificación” para modificarla. “Listo” no equivale a aprobación de dirección.</div></div>
    <div v-if="readonly" class="alert info" style="margin-bottom:14px"><span class="ic">👁</span>
      <div>Vista de seguimiento institucional (solo lectura). El contenido académico sólo puede ser modificado por el docente autor. Las consultas quedan registradas.</div></div>

    <fieldset :disabled="!editable" style="border:0;padding:0;margin:0;min-width:0">

    <!-- ============ DATOS GENERALES ============ -->
    <section v-show="tab === 'generales'" id="sec-generales" class="card" role="tabpanel">
      <div class="card-head"><h2>Datos generales</h2><span class="muted">Se completan automáticamente</span></div>
      <div class="card-body">
        <dl class="kv">
          <dt>Establecimiento</dt><dd>{{ store.institution && store.institution.full_name }}</dd>
          <dt>Docente</dt><dd>{{ general.teacher_name }}</dd>
          <dt>Ciclo escolar</dt><dd>{{ general.school_year_name }}</dd>
          <dt>Curso o asignatura</dt><dd>{{ general.course_name }}</dd>
          <dt>Grado</dt><dd>{{ general.grade_name }}</dd>
          <dt>Sección</dt><dd>{{ general.section_name }}</dd>
          <dt>Bimestre</dt><dd>{{ ORD[general.bimester_number] }} bimestre</dd>
          <dt>Fechas del bimestre</dt><dd>{{ general.bimester_start ? fmtDate(general.bimester_start) + ' al ' + fmtDate(general.bimester_end) : 'Pendientes de configurar por la administración' }}</dd>
        </dl>
        <div class="divider"></div>
        <div class="stack">
          <label class="check" :class="{ on: tree.include_pages }"><input type="checkbox" v-model="tree.include_pages"> Mostrar las páginas del libro junto a cada tema en la hoja de actividades</label>
          <div class="field"><label for="g-obs">Observaciones de esta planificación <span class="muted">(opcional)</span></label>
            <textarea id="g-obs" v-model="tree.observations" rows="3"></textarea>
            <span class="hint">Se agregan en la hoja después de la observación institucional.</span></div>
        </div>
      </div>
    </section>

    <!-- ============ COMPETENCIAS ============ -->
    <div v-show="tab === 'competencias'" class="grid g2" style="align-items:start" role="tabpanel">
      <section v-for="sec in [{ key: 'competencies', id: 'competencias', title: 'Competencias', one: 'competencia' }, { key: 'indicators', id: 'indicadores', title: 'Indicadores de logro', one: 'indicador de logro' }]" :key="sec.key" class="card" :id="'sec-' + sec.id">
        <div class="card-head"><h2>{{ sec.title }}</h2><span class="muted">{{ tree[sec.key].length }}</span></div>
        <div class="card-body">
          <field-err v-for="m in issuesWhere({ section: 'competencias', field: sec.id })" :msg="m"></field-err>
          <div v-if="!tree[sec.key].length" class="hint" style="margin-bottom:10px">Aún no hay {{ sec.title.toLowerCase() }}.</div>
          <div v-for="(it, i) in tree[sec.key]" :key="it.id" class="list-row" :id="'li-' + it.id">
            <span class="num">{{ i + 1 }}.</span>
            <div class="field" style="flex:1"><label :for="'it-' + it.id" class="sr-only">{{ sec.one }} {{ i + 1 }}</label>
              <textarea :id="'it-' + it.id" v-model="it.text" rows="2" :aria-invalid="issuesWhere({ section: 'competencias', id: it.id }).length > 0"></textarea>
              <field-err v-for="m in issuesWhere({ section: 'competencias', id: it.id })" :msg="m"></field-err></div>
            <div class="row" style="flex-direction:column;gap:2px">
              <button class="btn sm icon ghost" @click="move(tree[sec.key], i, -1)" :disabled="i === 0" :aria-label="'Subir ' + sec.one + ' ' + (i + 1)">↑</button>
              <button class="btn sm icon ghost" @click="move(tree[sec.key], i, 1)" :disabled="i === tree[sec.key].length - 1" :aria-label="'Bajar ' + sec.one + ' ' + (i + 1)">↓</button>
              <button class="btn sm icon ghost danger" @click="removeItem(tree[sec.key], i, sec.one)" :aria-label="'Eliminar ' + sec.one + ' ' + (i + 1)">✕</button>
            </div>
          </div>
          <button class="btn soft" style="margin-top:12px" @click="addItem(tree[sec.key], 'it-')">＋ Agregar {{ sec.one }}</button>
        </div>
      </section>
    </div>

    <!-- ============ BLOQUES Y SEMANAS ============ -->
    <section v-show="tab === 'bloques'" id="sec-bloques" role="tabpanel">
      <div class="row between" style="margin-bottom:12px">
        <div class="totals"><span class="pill">Bloques: <b>{{ tree.blocks.length }}</b></span><span class="pill">Semanas: <b>{{ allWeeks.length }}</b></span>
          <span class="pill">Contenidos: <b>{{ contentCount }}</b></span><span class="pill">Períodos del bimestre: <b>{{ planPeriods }}</b></span></div>
        <span class="hint">Bimestre: {{ general.bimester_start ? fmtDate(general.bimester_start) + ' – ' + fmtDate(general.bimester_end) : 'fechas pendientes' }}</span>
      </div>
      <field-err v-for="m in issuesWhere({ section: 'bloques' })" :msg="m"></field-err>
      <div v-if="!tree.blocks.length" class="card"><div class="empty"><span class="ic">🗓</span>
        <h3>Organice el bimestre en bloques mensuales</h3><p>Cada bloque tiene sus semanas, contenidos y <b>una única actividad de evaluación</b>.</p>
        <button class="btn primary" @click="addBlock">＋ Agregar primer bloque</button></div></div>

      <article v-for="(b, bi) in tree.blocks" :key="b.id" :id="'blk-' + b.id" class="block" :class="{ open: openBlocks[b.id] }">
        <div class="block-head" @click="toggleBlock(b)">
          <span class="chev" aria-hidden="true">▶</span>
          <div class="ttl">
            <span role="button" tabindex="0" :aria-expanded="!!openBlocks[b.id]" @keydown.enter.prevent="toggleBlock(b)" @keydown.space.prevent="toggleBlock(b)"><b>{{ b.label || 'Bloque ' + (bi + 1) + ' (sin nombre)' }}</b></span>
            <div class="block-summary">
              <span>📅 {{ b.start_date ? fmtDate(b.start_date) : '—' }} – {{ b.end_date ? fmtDate(b.end_date) : '—' }}</span>
              <span>{{ b.weeks.length }} semana(s)</span><span>{{ blockContents(b) }} contenido(s)</span><span>{{ blockPeriods(b) }} período(s)</span>
              <span v-if="b.closed" class="badge ready" style="font-size:.7rem">✓ Organizado</span>
              <span v-if="blockPending(b)" class="badge err" style="font-size:.7rem">⚠ {{ blockPending(b) }} pendiente(s)</span>
            </div>
          </div>
          <div class="row" @click.stop>
            <button class="btn sm icon ghost" @click="moveBlock(bi, -1)" :disabled="bi === 0" :aria-label="'Subir bloque ' + (b.label || bi + 1)">↑</button>
            <button class="btn sm icon ghost" @click="moveBlock(bi, 1)" :disabled="bi === tree.blocks.length - 1" :aria-label="'Bajar bloque ' + (b.label || bi + 1)">↓</button>
            <button class="btn sm ghost danger" @click="removeBlock(b, bi)" :aria-label="'Eliminar bloque ' + (b.label || bi + 1)">✕ Eliminar</button>
          </div>
        </div>
        <div v-if="openBlocks[b.id]" class="block-body stack">
          <div class="grid g3">
            <div class="field"><label :for="'b-' + b.id + '-label'">Nombre o etiqueta del mes</label>
              <input :id="'b-' + b.id + '-label'" type="text" v-model.trim="b.label" maxlength="80" :aria-invalid="issuesWhere({ section: 'bloques', blockId: b.id, field: 'label' }).length > 0">
              <field-err v-for="m in issuesWhere({ section: 'bloques', blockId: b.id, field: 'label' })" :msg="m"></field-err></div>
            <div class="field" :id="'b-' + b.id + '-dates'"><label :for="'b-' + b.id + '-start'">Fecha de inicio</label>
              <input :id="'b-' + b.id + '-start'" type="date" v-model="b.start_date" :min="general.bimester_start" :max="general.bimester_end"></div>
            <div class="field"><label :for="'b-' + b.id + '-end'">Fecha de finalización</label>
              <input :id="'b-' + b.id + '-end'" type="date" v-model="b.end_date" :min="b.start_date || general.bimester_start" :max="general.bimester_end"></div>
          </div>
          <field-err v-for="m in issuesWhere({ section: 'bloques', blockId: b.id, field: 'dates' })" :msg="m"></field-err>
          <p class="hint" style="margin:0">El bloque puede abarcar días de otro mes calendario si así organiza la planificación (siempre dentro del bimestre).</p>

          <!-- Evaluación única del bloque -->
          <div class="eval-panel" :id="'b-' + b.id + '-ev'">
            <div class="row between" style="margin-bottom:8px">
              <div><div class="eval-tag">Actividad de evaluación del bloque · única</div>
                <div class="hint">Se aplica a todos los contenidos y semanas de este bloque. No se registra evaluación por contenido ni por semana.</div></div>
              <span class="pill">Total: <b>{{ evalTotal(b.evaluation) ?? '—' }}</b> pts</span>
            </div>
            <div class="stack">
              <div class="field"><label :for="'b-' + b.id + '-ev-description'">Descripción de la actividad de evaluación</label>
                <textarea :id="'b-' + b.id + '-ev-description'" v-model="b.evaluation.description" rows="3" :aria-invalid="issuesWhere({ section: 'bloques', blockId: b.id, part: 'evaluacion', field: 'description' }).length > 0"></textarea>
                <field-err v-for="m in issuesWhere({ section: 'bloques', blockId: b.id, part: 'evaluacion', field: 'description' })" :msg="m"></field-err></div>
              <div class="field"><label :for="'b-' + b.id + '-ev-instructions'">Indicaciones complementarias <span class="muted">(opcional)</span></label>
                <textarea :id="'b-' + b.id + '-ev-instructions'" v-model="b.evaluation.instructions" rows="2"></textarea>
                <span class="hint">Aparecen debajo de la descripción en la hoja (lugar de realización, horario, plataforma, etc.).</span></div>
              <div class="field" :id="'b-' + b.id + '-ev-criteria'">
                <div class="row between"><span class="label">Aspectos o criterios a evaluar</span>
                  <div class="seg" role="group" aria-label="Forma de punteo">
                    <button type="button" :aria-pressed="b.evaluation.scoring_mode === 'criterios'" @click="setMode(b.evaluation, 'criterios')">Punteo por criterio</button>
                    <button type="button" :aria-pressed="b.evaluation.scoring_mode === 'total'" @click="setMode(b.evaluation, 'total')">Solo punteo total</button>
                  </div></div>
                <div v-for="(c, ci) in b.evaluation.criteria" :key="c.id" class="crit-row" :class="{ 'no-pts': b.evaluation.scoring_mode === 'total' }">
                  <input :id="'cr-' + c.id" type="text" v-model="c.text" :aria-label="'Criterio ' + (ci + 1)" placeholder="Aspecto o criterio">
                  <input v-if="b.evaluation.scoring_mode === 'criterios'" type="number" min="0" step="0.01" v-model.number="c.points" :aria-label="'Punteo del criterio ' + (ci + 1)" placeholder="Puntos">
                  <div class="row" style="gap:2px;flex-wrap:nowrap">
                    <button class="btn sm icon ghost" @click="move(b.evaluation.criteria, ci, -1)" :disabled="ci === 0" :aria-label="'Subir criterio ' + (ci + 1)">↑</button>
                    <button class="btn sm icon ghost danger" @click="removeItem(b.evaluation.criteria, ci, 'criterio')" :aria-label="'Eliminar criterio ' + (ci + 1)">✕</button></div>
                </div>
                <field-err v-for="m in issuesWhere({ section: 'bloques', blockId: b.id, part: 'evaluacion', field: 'criteria' })" :msg="m"></field-err>
                <div><button class="btn sm soft" style="margin-top:6px" @click="addCriterion(b.evaluation, 'cr-')">＋ Agregar criterio</button></div>
                <span class="hint">Varios criterios siguen siendo una sola evaluación.</span>
              </div>
              <div class="grid g3">
                <div v-if="b.evaluation.scoring_mode === 'total'" class="field"><label :for="'b-' + b.id + '-ev-total_points'">Punteo total</label>
                  <input :id="'b-' + b.id + '-ev-total_points'" type="number" min="0" step="0.01" v-model.number="b.evaluation.total_points">
                  <field-err v-for="m in issuesWhere({ section: 'bloques', blockId: b.id, part: 'evaluacion', field: 'total_points' })" :msg="m"></field-err></div>
                <div v-else class="field"><span class="label">Punteo total</span><div style="padding:9px 0;font-weight:600">{{ evalTotal(b.evaluation) ?? '—' }} <small class="muted">(suma de criterios)</small></div></div>
                <div class="field"><label :for="'b-' + b.id + '-ev-due_date'">Fecha de entrega</label>
                  <input :id="'b-' + b.id + '-ev-due_date'" type="date" v-model="b.evaluation.due_date">
                  <field-err v-for="m in issuesWhere({ section: 'bloques', blockId: b.id, part: 'evaluacion', field: 'due_date' })" :msg="m"></field-err></div>
              </div>
            </div>
          </div>

          <!-- Semanas -->
          <div :id="'b-' + b.id + '-weeks'">
            <div class="row between" style="margin:6px 0 10px"><h3>Semanas del bloque</h3><span class="muted" style="font-size:.85rem">{{ blockPeriods(b) }} período(s) en el bloque</span></div>
            <field-err v-for="m in issuesWhere({ section: 'bloques', blockId: b.id, part: 'semanas' })" :msg="m"></field-err>
            <div v-for="(w, wi) in b.weeks" :key="w.id" class="week" :id="'wk-' + w.id">
              <div class="week-head">
                <span class="btn sm icon ghost" role="button" tabindex="0" @click="toggleWeek(w)" @keydown.enter.prevent="toggleWeek(w)" @keydown.space.prevent="toggleWeek(w)" :aria-expanded="weekOpen(w)" :aria-label="(weekOpen(w) ? 'Contraer' : 'Expandir') + ' semana ' + weekNumbers[w.id]">{{ weekOpen(w) ? '▾' : '▸' }}</span>
                <span class="week-no">Semana {{ weekNumbers[w.id] }}</span>
                <div class="row" :id="'w-' + w.id + '-dates'" style="flex:1;gap:6px">
                  <label class="sr-only" :for="'w-' + w.id + '-start'">Inicio de la semana {{ weekNumbers[w.id] }}</label>
                  <input :id="'w-' + w.id + '-start'" type="date" v-model="w.start_date" :min="b.start_date" :max="b.end_date" style="max-width:170px">
                  <span class="muted">a</span>
                  <label class="sr-only" :for="'w-' + w.id + '-end'">Fin de la semana {{ weekNumbers[w.id] }}</label>
                  <input :id="'w-' + w.id + '-end'" type="date" v-model="w.end_date" :min="w.start_date || b.start_date" :max="b.end_date" style="max-width:170px">
                </div>
                <span class="pill">{{ w.contents.length }} cont. · <b>{{ weekPeriods(w) }}</b> per.</span>
                <div class="row" style="gap:2px">
                  <button class="btn sm icon ghost" @click="moveWeek(b, wi, -1)" :disabled="wi === 0" :aria-label="'Subir semana ' + weekNumbers[w.id]">↑</button>
                  <button class="btn sm icon ghost" @click="moveWeek(b, wi, 1)" :disabled="wi === b.weeks.length - 1" :aria-label="'Bajar semana ' + weekNumbers[w.id]">↓</button>
                  <button class="btn sm icon ghost danger" @click="removeWeek(b, wi)" :aria-label="'Eliminar semana ' + weekNumbers[w.id]">✕</button></div>
              </div>
              <div style="padding:0 12px"><field-err v-for="m in issuesWhere({ section: 'bloques', blockId: b.id, weekId: w.id, field: 'dates' })" :msg="m"></field-err></div>
              <div v-if="weekOpen(w)" class="week-body" :id="'w-' + w.id + '-contents'">
                <field-err v-for="m in issuesWhere({ section: 'bloques', blockId: b.id, weekId: w.id, part: 'contenidos' })" :msg="m"></field-err>
                <div v-for="(c, ci) in w.contents" :key="c.id" class="content-card" :id="'ct-' + c.id">
                  <div class="row between" style="margin-bottom:6px">
                    <span class="content-no">Contenido {{ ci + 1 }}</span>
                    <div class="content-tools">
                      <button class="btn sm icon ghost" @click="move(w.contents, ci, -1)" :disabled="ci === 0" :aria-label="'Subir contenido ' + (ci + 1)">↑</button>
                      <button class="btn sm icon ghost" @click="move(w.contents, ci, 1)" :disabled="ci === w.contents.length - 1" :aria-label="'Bajar contenido ' + (ci + 1)">↓</button>
                      <label class="sr-only" :for="'mv-' + c.id">Mover contenido a otra semana</label>
                      <select :id="'mv-' + c.id" style="min-height:32px;padding:4px 8px;font-size:.8rem;width:auto" :value="''" @change="moveContentTo(c, w, b, $event.target.value); $event.target.value = ''">
                        <option value="">Mover a…</option>
                        <option v-for="x in allWeeks" :key="x.w.id" :value="x.w.id" :disabled="x.w.id === w.id">Semana {{ weekNumbers[x.w.id] }} ({{ x.b.label || 'bloque sin nombre' }})</option>
                      </select>
                      <button class="btn sm icon ghost danger" @click="removeContent(w, ci)" :aria-label="'Eliminar contenido ' + (ci + 1)">✕</button>
                    </div>
                  </div>
                  <div class="content-grid">
                    <div class="field"><label :for="'c-' + c.id + '-topic'">Tema o descripción del contenido</label>
                      <textarea :id="'c-' + c.id + '-topic'" v-model="c.topic" rows="2" style="min-height:44px" :aria-invalid="issuesWhere({ section: 'bloques', contentId: c.id, blockId: b.id, weekId: w.id, field: 'topic' }).length > 0"></textarea>
                      <field-err v-for="m in issuesWhere({ section: 'bloques', blockId: b.id, weekId: w.id, contentId: c.id, field: 'topic' })" :msg="m"></field-err></div>
                    <div class="field"><label :for="'c-' + c.id + '-pages'">Páginas del libro <span class="muted">(opcional)</span></label>
                      <input :id="'c-' + c.id + '-pages'" type="text" v-model="c.pages" maxlength="120" placeholder="Ej.: 15–18">
                      <span class="hint">Acepta 15, 15–18 o 15, 18 y 21.</span></div>
                    <div class="field"><label :for="'c-' + c.id + '-periods'">Períodos</label>
                      <input :id="'c-' + c.id + '-periods'" type="number" min="1" step="1" v-model.number="c.periods" :aria-invalid="issuesWhere({ section: 'bloques', blockId: b.id, weekId: w.id, contentId: c.id, field: 'periods' }).length > 0">
                      <field-err v-for="m in issuesWhere({ section: 'bloques', blockId: b.id, weekId: w.id, contentId: c.id, field: 'periods' })" :msg="m"></field-err></div>
                  </div>
                  <div class="acts" :id="'c-' + c.id + '-activities'">
                    <span class="label">Actividades de aprendizaje <span class="muted" style="font-weight:400">— cómo se trabajará el contenido (no aparecen en la columna de evaluación)</span></span>
                    <div v-for="(a, ai) in c.activities" :key="a.id" class="act-row">
                      <label class="sr-only" :for="'a-' + a.id">Actividad de aprendizaje {{ ai + 1 }}</label>
                      <input :id="'a-' + a.id" type="text" v-model="a.text" placeholder="Describa la actividad de aprendizaje">
                      <button class="btn sm icon ghost" @click="move(c.activities, ai, -1)" :disabled="ai === 0" :aria-label="'Subir actividad ' + (ai + 1)">↑</button>
                      <button class="btn sm icon ghost danger" @click="removeItem(c.activities, ai, 'actividad de aprendizaje')" :aria-label="'Eliminar actividad ' + (ai + 1)">✕</button>
                    </div>
                    <field-err v-for="m in issuesWhere({ section: 'bloques', blockId: b.id, weekId: w.id, contentId: c.id, field: 'activities' })" :msg="m"></field-err>
                    <button class="btn sm soft" style="margin-top:8px" @click="addItem(c.activities, 'a-')">＋ Agregar actividad de aprendizaje</button>
                    <div class="hint" style="margin-top:4px">Ejemplos de tipo de actividad: síntesis, lluvia de ideas, ejercicios, lectura comentada, trabajo en equipo.</div>
                  </div>
                </div>
                <button class="btn soft" style="margin-top:10px" @click="addContent(w)">＋ Agregar contenido</button>
              </div>
            </div>
            <button class="btn" style="margin-top:12px" @click="addWeek(b)">＋ Agregar semana</button>
          </div>
          <div class="divider"></div>
          <div class="row between">
            <span class="hint">El bloque seguirá siendo editable mientras trabaje en el plan.</span>
            <button class="btn primary" @click="finishBlock(b, bi)">Finalizar bloque y {{ bi === tree.blocks.length - 1 ? 'agregar siguiente' : 'abrir siguiente' }} →</button>
          </div>
        </div>
      </article>
      <div v-if="tree.blocks.length && editable" class="sticky-actions glass">
        <button class="btn" @click="addBlock">＋ Agregar bloque</button>
        <button class="btn" @click="doSave(true)" :disabled="saving">💾 Guardar</button>
        <button class="btn" @click="setTab('revision')">Revisar plan</button>
        <button class="btn soft" @click="toSheet">📄 Generar hoja de actividades PDF</button>
        <button class="btn soft" @click="toPlanPdf">📘 Generar plan bimestral PDF</button>
      </div>
    </section>

    <!-- ============ APARTADOS ADICIONALES ============ -->
    <section v-show="tab === 'apartados'" role="tabpanel">
      <div class="alert info" style="margin-bottom:14px"><span class="ic">ℹ</span><span>Apartados bimestrales de la hoja de actividades. <b>No son evaluaciones mensuales adicionales.</b> Si alguno no aplica, márquelo como “No aplica”.</span></div>
      <div class="grid g2" style="align-items:start">
        <article v-for="e in EXTRAS" :key="e.kind" class="card" :id="'x-' + e.kind">
          <template v-for="x in [tree.extras[e.kind]]">
            <div class="card-head"><h2>{{ e.title }}</h2>
              <div class="seg" role="group" :aria-label="'¿' + e.title + ' aplica?'" :id="'x-' + e.kind + '-applies'">
                <button type="button" :aria-pressed="x.applies === true" @click="setApplies(x, true)">Aplica</button>
                <button type="button" :aria-pressed="x.applies === false" @click="setApplies(x, false)">No aplica</button>
              </div></div>
            <div class="card-body stack">
              <field-err v-for="m in issuesWhere({ section: 'apartados', kind: e.kind, field: 'applies' })" :msg="m"></field-err>
              <p v-if="x.applies === null" class="hint" style="margin:0">Sin definir: indique si este apartado aplica.</p>
              <p v-else-if="x.applies === false" class="hint" style="margin:0">Marcado como “No aplica”. En la hoja se indicará “No aplica”.</p>
              <template v-else>
                <div class="field"><label :for="'x-' + e.kind + '-detail'">{{ e.detailLabel }}</label>
                  <input :id="'x-' + e.kind + '-detail'" type="text" v-model="x.detail" maxlength="300"><span v-if="e.detailHint" class="hint">{{ e.detailHint }}</span></div>
                <div class="field"><label :for="'x-' + e.kind + '-activity'">{{ e.activityLabel }}</label>
                  <textarea :id="'x-' + e.kind + '-activity'" v-model="x.activity" rows="2"></textarea>
                  <field-err v-for="m in issuesWhere({ section: 'apartados', kind: e.kind, field: 'activity' })" :msg="m"></field-err></div>
                <div class="field" :id="'x-' + e.kind + '-criteria'">
                  <div class="row between"><span class="label">Aspectos y punteo</span>
                    <div class="seg" role="group" aria-label="Forma de punteo">
                      <button type="button" :aria-pressed="x.scoring_mode === 'total'" @click="setMode(x, 'total')">Solo punteo total</button>
                      <button type="button" :aria-pressed="x.scoring_mode === 'criterios'" @click="setMode(x, 'criterios')">Por criterio</button></div></div>
                  <div v-for="(c, ci) in x.criteria" :key="c.id" class="crit-row" :class="{ 'no-pts': x.scoring_mode === 'total' }">
                    <input :id="'xc-' + c.id" type="text" v-model="c.text" :aria-label="'Aspecto ' + (ci + 1)" placeholder="Aspecto o criterio">
                    <input v-if="x.scoring_mode === 'criterios'" type="number" min="0" step="0.01" v-model.number="c.points" :aria-label="'Punteo ' + (ci + 1)" placeholder="Puntos">
                    <button class="btn sm icon ghost danger" @click="removeItem(x.criteria, ci, 'aspecto')" :aria-label="'Eliminar aspecto ' + (ci + 1)">✕</button>
                  </div>
                  <field-err v-for="m in issuesWhere({ section: 'apartados', kind: e.kind, field: 'criteria' })" :msg="m"></field-err>
                  <div><button class="btn sm soft" style="margin-top:6px" @click="addCriterion(x, 'xc-')">＋ Agregar aspecto</button></div>
                </div>
                <div class="grid g2">
                  <div v-if="x.scoring_mode === 'total'" class="field"><label :for="'x-' + e.kind + '-total_points'">Punteo</label>
                    <input :id="'x-' + e.kind + '-total_points'" type="number" min="0" step="0.01" v-model.number="x.total_points">
                    <field-err v-for="m in issuesWhere({ section: 'apartados', kind: e.kind, field: 'total_points' })" :msg="m"></field-err></div>
                  <div v-else class="field"><span class="label">Punteo total</span><div style="padding:9px 0;font-weight:600">{{ evalTotal(x) ?? '—' }}</div></div>
                </div>
                <div class="field"><label :for="'x-' + e.kind + '-note'">Nota en “Aspectos a evaluar” <span class="muted">(opcional)</span></label>
                  <textarea :id="'x-' + e.kind + '-note'" v-model="x.aspects_note" rows="2"></textarea></div>
                <div class="grid g2" :id="'x-' + e.kind + '-due'">
                  <div class="field"><label :for="'x-' + e.kind + '-date'">Fecha de entrega o aplicación</label>
                    <input :id="'x-' + e.kind + '-date'" type="date" v-model="x.due_date"></div>
                  <div class="field"><label :for="'x-' + e.kind + '-duetext'">o período en texto</label>
                    <input :id="'x-' + e.kind + '-duetext'" type="text" v-model="x.due_text" maxlength="120" placeholder="Ej.: período del bimestre">
                    <span class="hint">Si escribe una fecha, se mostrará la fecha.</span></div>
                </div>
                <field-err v-for="m in issuesWhere({ section: 'apartados', kind: e.kind, field: 'due' })" :msg="m"></field-err>
                <div v-if="e.kind === 'mejoramiento'" class="grid g2">
                  <div class="field"><label :for="'x-mej-strategies'">Acciones / estrategias <span class="muted">(plan bimestral)</span></label>
                    <textarea id="x-mej-strategies" v-model="x.strategies" rows="2"></textarea></div>
                  <div class="field"><label :for="'x-mej-students'">Estudiantes atendidos <span class="muted">(plan bimestral)</span></label>
                    <textarea id="x-mej-students" v-model="x.students" rows="2" placeholder="Nombres o cantidad"></textarea></div>
                </div>
                <div class="field"><label :for="'x-' + e.kind + '-obs'">Observaciones internas <span class="muted">(no aparecen en la hoja)</span></label>
                  <textarea :id="'x-' + e.kind + '-obs'" v-model="x.observations" rows="2"></textarea></div>
              </template>
            </div>
          </template>
        </article>
      </div>
      <article class="card" style="margin-top:16px" id="x-mejora-acciones">
        <div class="card-head"><div><h2>Plan de mejoramiento — acciones adicionales</h2>
          <small class="muted">Sólo para el plan bimestral en PDF. La primera acción es la «Actividad de mejoramiento» de arriba (descripción, acciones, estudiantes y fecha); aquí agregue otras si las necesita.</small></div>
          <button class="btn sm soft" @click="addImprovement">＋ Agregar acción</button></div>
        <div class="card-body stack">
          <p v-if="!tree.improvement_actions.length" class="hint" style="margin:0">Sin acciones adicionales.</p>
          <div v-for="(a, ai) in tree.improvement_actions" :key="a.id" class="content-card">
            <div class="row between"><span class="content-no">Acción {{ ai + 2 }}</span>
              <button class="btn sm icon ghost danger" @click="removeItem(tree.improvement_actions, ai, 'acción de mejoramiento')" :aria-label="'Eliminar acción ' + (ai + 2)">✕</button></div>
            <div class="grid g2" style="margin-top:6px">
              <div class="field"><label :for="'ia-d-' + a.id">Descripción</label><textarea :id="'ia-d-' + a.id" v-model="a.description" rows="2"></textarea></div>
              <div class="field"><label :for="'ia-s-' + a.id">Acciones / estrategias</label><textarea :id="'ia-s-' + a.id" v-model="a.strategies" rows="2"></textarea></div>
              <div class="field"><label :for="'ia-e-' + a.id">Estudiantes atendidos</label><input :id="'ia-e-' + a.id" type="text" v-model="a.students"></div>
              <div class="field"><label :for="'ia-f-' + a.id">Fecha</label><input :id="'ia-f-' + a.id" type="date" v-model="a.action_date"></div>
            </div>
          </div>
        </div>
      </article>
    </section>
    </fieldset>

    <!-- ============ REVISIÓN ============ -->
    <section v-show="tab === 'revision'" role="tabpanel" class="stack">
      <div class="card card-pad">
        <div class="row between"><h2>Completitud de la planificación</h2><b style="font-size:1.3rem">{{ validation.completeness }}%</b></div>
        <div class="progress" style="margin:10px 0 6px" role="progressbar" :aria-valuenow="validation.completeness" aria-valuemin="0" aria-valuemax="100"><i :style="{ width: validation.completeness + '%' }"></i></div>
        <p class="hint">Calculada con los campos requeridos para la hoja de actividades. No mide avance de enseñanza ni cumplimiento académico.</p>
        <div class="row" style="margin-top:6px">
          <status-badge :status="status"></status-badge>
          <span v-if="readyAt" class="muted">Marcado como listo: {{ fmtDateTime(readyAt) }}</span>
        </div>
      </div>
      <div class="card">
        <div class="card-head"><h2>Incidencias</h2>
          <span><span class="badge err" v-if="validation.errors">⚠ {{ validation.errors }} por corregir</span>
          <span class="badge ready" v-else>✓ Sin datos faltantes</span></span></div>
        <div v-if="!validation.issues.length" class="empty">La planificación tiene los datos requeridos.</div>
        <div v-for="(i, k) in validation.issues" :key="k" class="issue">
          <span class="ic" :aria-label="i.level === 'error' ? 'Requerido' : 'Advertencia'">{{ i.level === 'error' ? '⚠️' : 'ℹ️' }}</span>
          <span class="msg"><b v-if="i.level === 'warning'">Advertencia: </b>{{ i.message }}</span>
          <button v-if="i.target" class="btn sm" @click="goTo(i.target)">Ir al campo</button>
        </div>
        <div class="card-body" v-if="!readonly"><div class="row end">
          <button v-if="status !== 'listo'" class="btn primary" @click="markReady">✓ Marcar como listo</button>
          <button class="btn soft" @click="toSheet">📄 Generar hoja de actividades PDF</button>
          <button class="btn soft" @click="toPlanPdf">📘 Generar plan bimestral PDF</button></div></div>
      </div>
      <div class="card">
        <div class="card-head"><h2>Períodos por semana y bloque</h2><span class="pill">Bimestre: <b>{{ planPeriods }}</b> períodos</span></div>
        <div class="table-wrap"><table class="data">
          <thead><tr><th>Bloque</th><th>Semana</th><th>Fechas</th><th>Contenidos</th><th>Períodos</th></tr></thead>
          <tbody>
            <template v-for="b in tree.blocks" :key="b.id">
              <tr v-for="(w, wi) in b.weeks" :key="w.id"><td>{{ wi === 0 ? (b.label || 'Sin nombre') : '' }}</td><td>Semana {{ weekNumbers[w.id] }}</td>
                <td>{{ fmtDate(w.start_date) }} – {{ fmtDate(w.end_date) }}</td><td>{{ w.contents.length }}</td><td>{{ weekPeriods(w) }}</td></tr>
              <tr style="background:var(--green-50)"><td colspan="4"><b>Total del bloque {{ b.label }}</b></td><td><b>{{ blockPeriods(b) }}</b></td></tr>
            </template>
          </tbody></table></div>
      </div>
      <div class="card">
        <div class="card-head"><h2>Registro de cambios</h2><button class="btn sm" @click="loadHistory">{{ history ? 'Actualizar' : 'Mostrar' }}</button></div>
        <div v-if="history" class="table-wrap"><table class="data cards"><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Detalle</th></tr></thead>
          <tbody><tr v-for="(h, k) in history" :key="k"><td data-label="Fecha">{{ fmtDateTime(h.at) }}</td><td data-label="Usuario">{{ h.user_name || '—' }}{{ h.user_role === 'admin' ? ' (administración)' : '' }}</td>
            <td data-label="Acción">{{ h.action.replace(/_/g, ' ') }}</td><td data-label="Detalle"><small class="muted">{{ h.details }}</small></td></tr></tbody></table></div>
      </div>
    </section>
  </template>
</div>`,
  };
})();
