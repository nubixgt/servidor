/* Hoja de actividades: comprobación de datos, vista previa, generación y descarga del PDF. */
'use strict';
(function () {
  const { store, api, toast, fmtDate, fmtDateTime, ORD } = CYD;

  CYD.components['view-sheet'] = {
    data: () => ({ store, loading: true, error: '', plan: null, docId: null, previewUrl: '', busy: false, ORD, u: CYD.url }),
    computed: {
      planId() { return Number(store.route.params.id); },
      g() { return this.plan && this.plan.general; },
      v() { return this.plan && this.plan.validation; },
      latest() { return this.plan && this.plan.documents.find(d => d.latest); },
      shown() { return this.plan && this.plan.documents.find(d => d.id === this.docId); },
      isMobile() { return window.matchMedia('(max-width: 640px)').matches; },
    },
    async mounted() { await this.load(); },
    methods: {
      fmtDate, fmtDateTime,
      async load() {
        this.loading = true; this.error = '';
        try {
          this.plan = await api('GET', `/api/plans/${this.planId}`);
          const cur = this.plan.documents.find(d => d.latest && d.current);
          if (cur) this.docId = cur.id;
          else this.previewUrl = CYD.url(`/api/plans/${this.planId}/preview.pdf?v=${this.plan.general.version}`);
        } catch (e) { this.error = e.message; }
        finally { this.loading = false; }
      },
      async generate() {
        this.busy = true;
        try {
          const r = await api('POST', `/api/plans/${this.planId}/documents`, { version: this.g.version });
          await this.load();
          this.docId = r.id; this.previewUrl = '';
          toast('Hoja de actividades generada. La vista previa muestra exactamente el PDF guardado.', 'ok', 6000);
        } catch (e) {
          if (e.data && e.data.validation) this.plan.validation = e.data.validation;
          toast(e.message, 'error', 7000);
        } finally { this.busy = false; }
      },
      showDoc(d) { this.docId = d.id; this.previewUrl = ''; window.scrollTo({ top: 0, behavior: 'smooth' }); },
      showPreview() { this.docId = null; this.previewUrl = CYD.url(`/api/plans/${this.planId}/preview.pdf?v=${this.g.version}&t=${Date.now()}`); },
    },
    template: `
<div>
  <loading-box v-if="loading"></loading-box>
  <div v-else-if="error" class="alert error"><span class="ic">⚠</span>{{ error }}</div>
  <template v-else>
    <div class="page-head">
      <div><div class="crumbs"><a :href="store.user.role === 'admin' ? '#/admin' : '#/inicio'">Inicio</a> › <a :href="'#/plan/' + planId">Plan bimestral</a> › Hoja de actividades</div>
        <h1>Hoja de actividades</h1>
        <p>{{ g.course_name }} · {{ g.grade_name }} · Sección {{ g.section_name }} · {{ ORD[g.bimester_number] }} bimestre · Ciclo {{ g.school_year_name }}</p></div>
      <div class="row"><status-badge :status="g.status"></status-badge><a class="btn" :href="'#/plan/' + planId">← Volver al plan</a></div>
    </div>

    <div class="grid" style="grid-template-columns:minmax(0,1fr);gap:16px">
      <section class="card card-pad">
        <div class="grid g3">
          <div><div class="label">Docente</div><div>{{ g.teacher_name }}</div></div>
          <div><div class="label">Estado del plan</div><div>{{ g.status === 'listo' ? 'Listo desde ' + fmtDateTime(g.ready_at) : 'Borrador' }}</div></div>
          <div><div class="label">Versión guardada</div><div>v{{ g.version }} · {{ fmtDateTime(g.updated_at) }}</div></div>
        </div>
        <div class="divider"></div>
        <div v-if="v.errors" class="alert warn"><span class="ic">⚠</span><div>
          <b>Faltan {{ v.errors }} dato(s) para la hoja final.</b> Puede ver una vista previa de borrador, pero la hoja final sólo se genera con la planificación completa y marcada como lista.
          <ul style="margin:6px 0 0;padding-left:18px"><li v-for="(i, k) in v.issues.filter(x => x.level === 'error').slice(0, 6)" :key="k">{{ i.message }}</li></ul>
          <a :href="'#/plan/' + planId + '?tab=revision'">Ver todas las incidencias →</a></div></div>
        <div v-else-if="g.status !== 'listo'" class="alert info"><span class="ic">ℹ</span><div>La planificación tiene los datos completos. <b>Márquela como lista</b> en el editor para generar la hoja final. Mientras tanto se muestra una vista previa de borrador.</div></div>
        <div v-else class="alert ok"><span class="ic">✓</span><div>La planificación está lista y validada. La hoja se genera con la versión guardada (v{{ g.version }}).</div></div>
        <div v-for="w in v.issues.filter(x => x.level === 'warning')" class="alert warn" style="margin-top:8px"><span class="ic">ℹ</span>{{ w.message }}</div>
        <div class="row end" style="margin-top:14px">
          <button v-if="docId || !previewUrl" class="btn" @click="showPreview">👁 {{ g.status === 'listo' ? 'Vista previa' : 'Vista previa de borrador' }}</button>
          <button v-if="g.status === 'listo' && !v.errors" class="btn primary" @click="generate" :disabled="busy">{{ busy ? 'Generando…' : '📄 Generar hoja de actividades' }}</button>
        </div>
      </section>

      <section class="card">
        <div class="card-head">
          <div>
            <h2 v-if="shown">PDF generado el {{ fmtDateTime(shown.generated_at) }}</h2>
            <h2 v-else>{{ g.status === 'listo' ? 'Vista previa (aún no generada)' : 'Vista previa' }}</h2>
            <small class="muted" v-if="shown">{{ shown.filename }} · versión del plan v{{ shown.plan_version }}{{ shown.current ? '' : ' · desactualizada respecto al plan' }}</small>
            <small class="muted" v-else>Construida con la información guardada (v{{ g.version }}). {{ g.status === 'listo' ? '' : 'La planificación aún no está marcada como lista.' }}</small>
          </div>
          <div class="row">
            <a v-if="shown" class="btn primary" :href="u('/api/documents/' + shown.id + '/pdf?download=1')">⬇ Descargar PDF</a>
            <a v-else-if="previewUrl" class="btn" :href="previewUrl" target="_blank" rel="noopener">Abrir en otra pestaña</a>
          </div>
        </div>
        <div class="card-body">
          <iframe v-if="shown" class="pdf-frame" :src="u('/api/documents/' + shown.id + '/pdf')" title="Hoja de actividades en PDF"></iframe>
          <iframe v-else-if="previewUrl" class="pdf-frame" :src="previewUrl" title="Vista previa de la hoja de actividades"></iframe>
          <p class="hint" style="margin-top:8px">Si su dispositivo no muestra el PDF aquí, use {{ shown ? '“Descargar PDF”' : '“Abrir en otra pestaña”' }}.</p>
        </div>
      </section>

      <section class="card" v-if="plan.documents.length">
        <div class="card-head"><h2>Hojas generadas</h2><span class="muted">Se conservan tal como se generaron</span></div>
        <div class="table-wrap"><table class="data cards">
          <thead><tr><th>Fecha de generación</th><th>Versión del plan</th><th>Archivo</th><th><span class="sr-only">Acciones</span></th></tr></thead>
          <tbody><tr v-for="d in plan.documents" :key="d.id">
            <td data-label="Generada">{{ fmtDateTime(d.generated_at) }} <span v-if="d.latest" class="badge ready">Más reciente</span></td>
            <td data-label="Versión">v{{ d.plan_version }} <span v-if="!d.current" class="badge none">Plan modificado después</span></td>
            <td data-label="Archivo"><small>{{ d.filename }}</small></td>
            <td><div class="row end"><button class="btn sm" @click="showDoc(d)">Ver</button><a class="btn sm" :href="u('/api/documents/' + d.id + '/pdf?download=1')">Descargar</a></div></td>
          </tr></tbody></table></div>
      </section>
    </div>
  </template>
</div>`,
  };
})();
