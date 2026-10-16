/* Plan bimestral en PDF: vista previa y descarga (el mismo archivo), entrega a coordinación,
   observaciones y revisión del coordinador. */
'use strict';
(function () {
  const { store, api, toast, confirm, fmtDateTime, ORD } = CYD;
  const RESULTS = [
    ['aprobado', 'Aprobado'], ['con_observaciones', 'Aprobado con observaciones'], ['requiere_correcciones', 'Requiere correcciones'],
  ];
  const resultLabel = r => (RESULTS.find(x => x[0] === r) || [, r])[1];

  CYD.components['view-plan-pdf'] = {
    data: () => ({ store, ORD, RESULTS, loading: true, error: '', plan: null, pdfUrl: '', pdfName: '', pdfError: '', building: false, busy: false, obs: '', result: '' }),
    computed: {
      planId() { return Number(store.route.params.id); },
      g() { return this.plan && this.plan.general; },
      co() { return this.plan && this.plan.coordination; },
      isAdmin() { return store.user.role === 'admin'; },
      lastReview() { return this.co && this.co.reviews[0]; },
      deliveryText() {
        if (!this.co) return '';
        if (this.lastReview) return `${resultLabel(this.lastReview.result)} · revisado por ${this.lastReview.reviewer_name} el ${fmtDateTime(this.lastReview.reviewed_at)}`;
        if (this.co.delivered_at) return `Entregado a coordinación el ${fmtDateTime(this.co.delivered_at)} · pendiente de revisión`;
        return 'Sin entregar a coordinación';
      },
    },
    async mounted() { await this.load(); },
    beforeUnmount() { if (this.pdfUrl) URL.revokeObjectURL(this.pdfUrl); },
    methods: {
      fmtDateTime, resultLabel,
      async load() {
        this.loading = true; this.error = '';
        try {
          this.plan = await api('GET', `/api/plans/${this.planId}`);
          this.obs = this.co.coord_observations || '';
          this.loading = false;
          await this.build();
        } catch (e) { this.error = e.message; this.loading = false; }
      },
      // Se descarga el PDF una sola vez: la vista previa y el botón «Descargar» usan exactamente el mismo archivo.
      async build() {
        this.building = true; this.pdfError = '';
        if (this.pdfUrl) { URL.revokeObjectURL(this.pdfUrl); this.pdfUrl = ''; }
        try {
          const res = await fetch(CYD.url(`/api/plans/${this.planId}/plan-bimestral.pdf?t=${Date.now()}`), { credentials: 'same-origin', headers: { 'X-Requested-With': 'cyd-app' } });
          const type = res.headers.get('content-type') || '';
          if (!res.ok || !type.includes('application/pdf')) {
            let msg = `No se pudo generar el PDF (error ${res.status}).`;
            try { const j = await res.json(); if (j.error) msg = j.error; } catch { /* sin detalle */ }
            throw new Error(msg);
          }
          const blob = await res.blob();
          if (blob.size < 500) throw new Error('El PDF generado está vacío. Intente de nuevo.');
          const m = (res.headers.get('content-disposition') || '').match(/filename="([^"]+)"/);
          this.pdfName = m ? m[1] : 'Plan_Bimestral.pdf';
          this.pdfUrl = URL.createObjectURL(blob);
        } catch (e) { this.pdfError = e.message || 'No hay conexión con el servidor.'; }
        finally { this.building = false; }
      },
      async deliver() {
        if (!await confirm({ title: 'Entregar a coordinación', body: ['Se registrará la fecha de entrega institucional de este plan. Coordinación podrá revisarlo y anotar observaciones.', 'Entregar no impide seguir descargando el PDF.'], confirmText: 'Entregar plan' })) return;
        this.busy = true;
        try { const r = await api('POST', `/api/plans/${this.planId}/deliver`, {}); this.plan.coordination = r.coordination; toast('Plan entregado a coordinación.', 'ok'); await this.build(); }
        catch (e) { toast(e.message, 'error'); }
        finally { this.busy = false; }
      },
      async saveObs() {
        this.busy = true;
        try { const r = await api('PUT', `/api/admin/plans/${this.planId}/coordination`, { observations: this.obs }); this.plan.coordination = r.coordination; toast('Observaciones guardadas.', 'ok'); await this.build(); }
        catch (e) { toast(e.message, 'error'); }
        finally { this.busy = false; }
      },
      async review() {
        if (!this.result) { toast('Seleccione el resultado de la revisión.', 'error'); return; }
        this.busy = true;
        try { const r = await api('POST', `/api/admin/plans/${this.planId}/reviews`, { result: this.result }); this.plan.coordination = r.coordination; this.result = ''; toast('Revisión registrada.', 'ok'); await this.build(); }
        catch (e) { toast(e.message, 'error'); }
        finally { this.busy = false; }
      },
    },
    template: `
<div>
  <loading-box v-if="loading"></loading-box>
  <div v-else-if="error" class="alert error"><span class="ic">⚠</span>{{ error }}</div>
  <template v-else>
    <div class="page-head">
      <div><div class="crumbs"><a :href="isAdmin ? '#/admin' : '#/inicio'">Inicio</a> › <a :href="'#/plan/' + planId">Planificación</a> › Plan bimestral en PDF</div>
        <h1>Plan bimestral en PDF</h1>
        <p>{{ g.course_name }} · {{ g.grade_name }} · Sección {{ g.section_name }} · {{ ORD[g.bimester_number] }} bimestre · Ciclo {{ g.school_year_name }}</p></div>
      <div class="row"><status-badge :status="g.status"></status-badge>
        <a class="btn" :href="'#/plan/' + planId + '/hoja'">📄 Hoja de actividades</a>
        <a class="btn" :href="'#/plan/' + planId">← Volver al plan</a></div>
    </div>

    <div class="grid g-split2">
      <section class="card">
        <div class="card-head">
          <div><h2>Vista previa</h2>
            <small class="muted">Generado con la versión guardada (v{{ g.version }}). La descarga es exactamente este mismo archivo.</small></div>
          <div class="row">
            <button class="btn" @click="build" :disabled="building">↻ Actualizar</button>
            <a v-if="pdfUrl" class="btn primary" :href="pdfUrl" :download="pdfName">⬇ Descargar PDF</a>
          </div>
        </div>
        <div class="card-body">
          <loading-box v-if="building" text="Generando el PDF…"></loading-box>
          <div v-else-if="pdfError" class="alert error" role="alert"><span class="ic">⚠</span><div>{{ pdfError }} <button class="btn sm" @click="build" style="margin-left:6px">Reintentar</button></div></div>
          <template v-else-if="pdfUrl">
            <iframe class="pdf-frame" :src="pdfUrl" title="Plan bimestral en PDF"></iframe>
            <p class="hint" style="margin-top:8px">Si su dispositivo no muestra el PDF aquí, use «Descargar PDF».</p>
          </template>
        </div>
      </section>

      <div class="stack">
        <section class="card card-pad stack">
          <h2>Datos del documento</h2>
          <div v-if="g.status !== 'listo'" class="alert info"><span class="ic">ℹ</span><span>El plan aún está en <b>borrador</b>: el PDF muestra sólo lo que ya registró. Nada se completa automáticamente.</span></div>
          <dl class="kv" style="grid-template-columns:120px 1fr">
            <dt>Docente</dt><dd>{{ g.teacher_name }}</dd>
            <dt>Período</dt><dd>{{ g.bimester_start ? CYD.fmtDate(g.bimester_start) + ' al ' + CYD.fmtDate(g.bimester_end) : 'Fechas del bimestre pendientes' }}</dd>
            <dt>Entrega</dt><dd>{{ deliveryText }}</dd>
          </dl>
          <div v-if="!isAdmin" class="stack">
            <p class="hint" style="margin:0">Entregar es opcional y no es necesario para descargar. Guardar, marcar como listo o generar el PDF no cuentan como entrega.</p>
            <button class="btn soft" style="align-self:flex-start" @click="deliver" :disabled="busy || g.status !== 'listo'" :title="g.status !== 'listo' ? 'Marque el plan como listo para poder entregarlo' : ''">
              📤 {{ co.delivered_at ? 'Entregar de nuevo a coordinación' : 'Entregar a coordinación' }}</button>
            <p v-if="g.status !== 'listo'" class="hint" style="margin:0">Para entregarlo, primero márquelo como listo en el editor.</p>
          </div>
        </section>

        <section v-if="isAdmin" class="card card-pad stack">
          <h2>Coordinación</h2>
          <div class="field"><label for="co-obs">Observaciones del coordinador</label>
            <textarea id="co-obs" v-model="obs" rows="5" placeholder="Aparecen en la sección 4 del plan bimestral."></textarea>
            <span v-if="co.coord_observations_at" class="hint">Última edición: {{ co.coord_observations_by }} · {{ fmtDateTime(co.coord_observations_at) }}</span></div>
          <div class="row end"><button class="btn" @click="saveObs" :disabled="busy || obs === (co.coord_observations || '')">Guardar observaciones</button></div>
          <div class="divider" style="margin:4px 0"></div>
          <div class="field"><label for="co-res">Registrar revisión</label>
            <select id="co-res" v-model="result" :disabled="!co.delivered_at"><option value="">Seleccione el resultado…</option><option v-for="r in RESULTS" :key="r[0]" :value="r[0]">{{ r[1] }}</option></select>
            <span class="hint">{{ co.delivered_at ? 'Quedarán registrados la fecha de hoy y su nombre como revisor.' : 'Disponible cuando el docente entregue el plan.' }}</span></div>
          <div class="row end"><button class="btn primary" @click="review" :disabled="busy || !co.delivered_at || !result">Registrar revisión</button></div>
          <div v-if="co.reviews.length" class="stack">
            <span class="label">Historial de revisiones</span>
            <div v-for="r in co.reviews" :key="r.id" class="hint">{{ fmtDateTime(r.reviewed_at) }} · {{ resultLabel(r.result) }} · {{ r.reviewer_name }} (plan v{{ r.plan_version }})</div>
          </div>
        </section>
      </div>
    </div>
  </template>
</div>`,
  };
})();
