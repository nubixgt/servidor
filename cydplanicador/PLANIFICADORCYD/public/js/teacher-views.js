/* Pantallas del docente: resumen, asignaciones, catálogo de cursos y perfil. */
'use strict';
(function () {
  const { store, api, go, toast, confirm, fmtDate, fmtDateTime, ORD, fieldErrors } = CYD;

  // ================================================================ Resumen
  CYD.components['view-dashboard'] = {
    data: () => ({ store, ORD, data: null, loading: true, error: '', q: { assignment: '', bimester: '' }, busy: false }),
    computed: {
      year() { return store.years.find(y => y.id === store.yearId); },
      rows() { return this.data ? this.data.rows : []; },
      bims() { return this.data ? this.data.bimesters : []; },
      activeRows() { return this.rows.filter(r => r.active); },
      stats() {
        const c = { sin_iniciar: 0, borrador: 0, listo: 0 };
        this.activeRows.forEach(r => r.bimesters.forEach(b => { c[b.status]++; }));
        return c;
      },
    },
    watch: { 'store.yearId'() { this.load(); } },
    mounted() { this.load(); },
    methods: {
      async load() {
        if (!store.yearId) { this.loading = false; this.data = null; return; }
        this.loading = true; this.error = '';
        try {
          this.data = await api('GET', `/api/me/dashboard?school_year_id=${store.yearId}`);
          // Preselección cuando sólo existe una opción
          if (this.activeRows.length === 1) this.q.assignment = this.activeRows[0].assignment_id;
          else if (!this.activeRows.some(r => r.assignment_id === this.q.assignment)) this.q.assignment = '';
          if (this.bims.length === 1) this.q.bimester = this.bims[0].id;
          else if (!this.q.bimester) {
            const today = new Date().toISOString().slice(0, 10);
            const cur = this.bims.find(b => b.start_date && b.end_date && b.start_date <= today && today <= b.end_date);
            if (cur) this.q.bimester = cur.id;
          }
        } catch (e) { this.error = e.message; }
        finally { this.loading = false; }
      },
      async open(assignmentId, bimesterId, target = '') {
        this.busy = true;
        try {
          const r = await api('POST', '/api/plans', { assignment_id: assignmentId, bimester_id: bimesterId });
          go(`/plan/${r.id}${target}`);
        } catch (e) { toast(e.message, 'error'); }
        finally { this.busy = false; }
      },
      cell(row, b) { return row.bimesters.find(x => x.bimester_id === b.id); },
      label(r) { return `${r.course_name} — ${r.grade_name} — ${r.section_name}`; },
      fmtDate, fmtDateTime,
    },
    template: `
    <div>
      <div class="page-head">
        <div><h1>Hola, {{ store.user.full_name }}</h1><p>Resumen de sus planificaciones bimestrales.</p></div>
        <year-select></year-select>
      </div>

      <div v-if="!store.years.length" class="card card-pad"><div class="alert warn"><span class="ic">ℹ</span>La administración aún no ha configurado ciclos escolares. Cuando exista uno podrá registrar sus asignaciones.</div></div>
      <loading-box v-else-if="loading"></loading-box>
      <div v-else-if="error" class="alert error"><span class="ic">⚠</span>{{ error }}</div>
      <template v-else>
        <div v-if="!rows.length" class="card"><div class="empty"><span class="ic">📚</span>
          <h2>Aún no tiene cursos asignados en el ciclo {{ year && year.name }}</h2>
          <p>Configure los cursos, grados y secciones que atiende para empezar a planificar.</p>
          <a class="btn primary" href="#/asignaciones">Registrar mis cursos</a></div></div>
        <template v-else>
          <div class="stat-row" aria-label="Resumen">
            <div class="stat"><b>{{ activeRows.length }}</b><span>Grupos a su cargo</span></div>
            <div class="stat"><b>{{ stats.listo }}</b><span>Planes listos</span></div>
            <div class="stat amber"><b>{{ stats.borrador }}</b><span>Planes en borrador</span></div>
            <div class="stat grey"><b>{{ stats.sin_iniciar }}</b><span>Planes sin iniciar</span></div>
          </div>
          <section class="card glass" style="margin-bottom:18px" aria-label="Abrir planificación">
            <form class="quick" @submit.prevent="q.assignment && q.bimester && open(q.assignment, q.bimester)">
              <div class="field"><label>Ciclo escolar</label><input type="text" :value="year && year.name" disabled></div>
              <div class="field"><label for="qa">Curso y grupo</label>
                <select id="qa" v-model="q.assignment"><option value="" disabled>Seleccione…</option>
                  <option v-for="r in activeRows" :value="r.assignment_id">{{ label(r) }}</option></select></div>
              <div class="field"><label for="qb">Bimestre</label>
                <select id="qb" v-model="q.bimester"><option value="" disabled>Seleccione…</option>
                  <option v-for="b in bims" :value="b.id">{{ ORD[b.number] }} bimestre</option></select></div>
              <div class="row" style="gap:8px">
                <button class="btn primary" :disabled="!q.assignment || !q.bimester || busy">Abrir planificación</button>
                <button type="button" class="btn soft" :disabled="!q.assignment || !q.bimester || busy" @click="open(q.assignment, q.bimester, '/plan-bimestral')">📘 Plan bimestral PDF</button>
              </div>
            </form>
          </section>

          <div class="stack">
            <article v-for="r in rows" :key="r.assignment_id" class="card assign-card">
              <div class="assign-head">
                <div><h3>{{ r.course_name }}</h3><div class="muted" style="font-size:.88rem">{{ r.grade_name }} · Sección {{ r.section_name }}</div></div>
                <span v-if="!r.active" class="badge none" title="Se conservan sus planificaciones; reactívela en Mis cursos para crear nuevas">Asignación desactivada</span>
              </div>
              <div class="bim-grid">
                <div v-for="b in bims" :key="b.id" class="bim">
                  <template v-for="c in [cell(r, b)]">
                    <div class="bim-title"><b>{{ ORD[b.number] }} bimestre</b><status-badge :status="c.status"></status-badge></div>
                    <div class="dates">{{ b.start_date ? fmtDate(b.start_date) + ' – ' + fmtDate(b.end_date) : 'Fechas pendientes de configurar' }}</div>
                    <template v-if="c.plan_id">
                      <div>
                        <div class="row between" style="font-size:.75rem;color:var(--text-3)"><span>Completitud de la planificación</span><b>{{ c.completeness }}%</b></div>
                        <div class="progress" role="progressbar" :aria-valuenow="c.completeness" aria-valuemin="0" aria-valuemax="100" :aria-label="'Completitud de la planificación ' + c.completeness + '%'"><i :style="{ width: c.completeness + '%' }"></i></div>
                      </div>
                      <div class="meta">Modificado: {{ fmtDateTime(c.updated_at) }}</div>
                      <div v-if="c.ready_at" class="meta">Listo desde: {{ fmtDateTime(c.ready_at) }}</div>
                      <div v-if="c.last_document" class="meta">Última hoja generada: {{ fmtDateTime(c.last_document) }}</div>
                      <div class="actions">
                        <a class="btn sm" :class="c.status === 'listo' ? '' : 'soft'" :href="'#/plan/' + c.plan_id">{{ c.status === 'listo' ? 'Consultar plan' : 'Continuar' }}</a>
                        <a class="btn sm" :href="'#/plan/' + c.plan_id + '/hoja'">📄 Hoja de actividades</a>
                        <a class="btn sm" :href="'#/plan/' + c.plan_id + '/plan-bimestral'">📘 Plan bimestral</a>
                      </div>
                    </template>
                    <div v-else-if="r.active" class="actions"><button class="btn sm soft" :disabled="busy" @click="open(r.assignment_id, b.id)">＋ Crear plan</button></div>
                  </template>
                </div>
              </div>
            </article>
          </div>
        </template>
      </template>
    </div>`,
  };

  // ================================================================ Catálogo de cursos (docente)
  CYD.components['view-courses'] = {
    data: () => ({ store, list: [], q: '', loading: true, editing: null, name: '' }),
    computed: {
      filtered() { const k = this.norm(this.q); return this.list.filter(c => !k || this.norm(c.name).includes(k)); },
    },
    mounted() { this.load(); },
    methods: {
      norm(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim(); },
      async load() { this.loading = true; try { this.list = await api('GET', '/api/courses'); } finally { this.loading = false; } },
      startEdit(c) { this.editing = c.id; this.name = c.name; },
      async save(c) {
        try { await api('PUT', `/api/courses/${c.id}`, { name: this.name }); toast('Curso actualizado.', 'ok'); this.editing = null; this.load(); }
        catch (e) { toast(e.message, 'error'); }
      },
      async del(c) {
        if (!await confirm({ title: 'Eliminar curso', body: [`Se eliminará “${c.name}” del catálogo.`], confirmText: 'Eliminar', danger: true })) return;
        try { await api('DELETE', `/api/courses/${c.id}`); toast('Curso eliminado.', 'ok'); this.load(); }
        catch (e) { toast(e.message, 'error'); }
      },
    },
    template: `
    <div>
      <div class="page-head"><div><div class="crumbs"><a href="#/inicio">Inicio</a> › Catálogo de cursos</div><h1>Catálogo compartido de cursos</h1>
        <p>Todos los docentes usan el mismo catálogo. Para agregar un curso, hágalo desde <a href="#/asignaciones">Mis cursos</a>.</p></div></div>
      <section class="card">
        <div class="card-head"><div class="field" style="flex:1;max-width:420px"><label for="cq" class="sr-only">Buscar curso</label>
          <input id="cq" type="search" v-model="q" placeholder="Buscar curso…"></div><span class="muted">{{ filtered.length }} curso(s)</span></div>
        <loading-box v-if="loading"></loading-box>
        <div v-else class="table-wrap"><table class="data cards">
          <thead><tr><th>Curso</th><th>Docentes que lo usan</th><th><span class="sr-only">Acciones</span></th></tr></thead>
          <tbody><tr v-for="c in filtered" :key="c.id">
            <td data-label="Curso"><input v-if="editing === c.id" type="text" v-model="name" @keydown.enter="save(c)"><b v-else>{{ c.name }}</b></td>
            <td data-label="Docentes">{{ c.teachers }}</td>
            <td><div class="row end" v-if="c.mine && c.teachers <= 1">
              <template v-if="editing === c.id"><button class="btn sm primary" @click="save(c)">Guardar</button><button class="btn sm ghost" @click="editing = null">Cancelar</button></template>
              <template v-else><button class="btn sm" @click="startEdit(c)">Corregir nombre</button><button class="btn sm danger" @click="del(c)">Eliminar</button></template>
            </div><small v-else class="muted">Sólo la administración puede modificarlo</small></td>
          </tr></tbody></table></div>
      </section>
    </div>`,
  };

  // ================================================================ Perfil
  CYD.components['view-profile'] = {
    data: () => ({ store, name: '', email: '', errs: {}, busy: false }),
    mounted() {
      this.name = store.user.full_name; this.email = store.user.recovery_email || '';
      const c = store.route.query.correo;
      if (c === 'verificado') toast('Correo de recuperación verificado.', 'ok');
      if (c === 'invalido') toast('El enlace de verificación no es válido o venció.', 'error');
    },
    methods: {
      async saveName() {
        this.errs = {}; this.busy = true;
        try { await api('PUT', '/api/me/profile', { full_name: this.name }); await CYD.loadMe(); toast('Nombre actualizado. Las hojas ya generadas no cambian.', 'ok'); }
        catch (e) { this.errs = fieldErrors(e); toast(e.message, 'error'); }
        finally { this.busy = false; }
      },
      async saveEmail() {
        this.busy = true;
        try {
          const r = await api('PUT', '/api/me/email', { email: this.email });
          await CYD.loadMe();
          if (!this.email) toast('Correo de recuperación eliminado.', 'ok');
          else if (r.verificationSent) toast('Le enviamos un mensaje para verificar el correo.', 'ok');
          else toast('Correo guardado. No se envió verificación porque el correo no está configurado en este servidor.', 'info', 7000);
        } catch (e) { toast(e.message, 'error'); }
        finally { this.busy = false; }
      },
    },
    template: `
    <div>
      <div class="page-head"><div><div class="crumbs"><a :href="'#' + CYD.home()">Inicio</a> › Perfil</div><h1>Mi perfil</h1></div></div>
      <div class="grid g2" style="align-items:start">
        <section class="card">
          <div class="card-head"><h2>Datos del docente</h2></div>
          <div class="card-body stack">
            <dl class="kv"><dt>Usuario</dt><dd>{{ store.user.username }}</dd><dt>Rol</dt><dd>{{ store.user.role === 'admin' ? 'Administrador del colegio' : 'Docente' }}</dd></dl>
            <div class="field"><label for="pf-n">Nombre completo</label><input id="pf-n" type="text" v-model="name" :aria-invalid="!!errs.full_name">
              <span class="hint">Se usa automáticamente en sus planificaciones y hojas de actividades nuevas.</span><field-err :msg="errs.full_name"></field-err></div>
            <div class="row end"><button class="btn primary" @click="saveName" :disabled="busy || name === store.user.full_name">Guardar nombre</button></div>
            <div class="alert info"><span class="ic">ℹ</span><span>Esta cuenta es independiente de SICA. Cambiar aquí su contraseña no afecta SICA y viceversa.</span></div>
          </div>
        </section>
        <section class="card">
          <div class="card-head"><h2>Correo de recuperación</h2>
            <span v-if="store.user.recovery_email" class="badge" :class="store.user.email_verified ? 'ready' : 'draft'">{{ store.user.email_verified ? '✓ Verificado' : '! Sin verificar' }}</span></div>
          <div class="card-body stack">
            <div class="field"><label for="pf-e">Correo <span class="muted">(opcional)</span></label><input id="pf-e" type="email" v-model.trim="email"></div>
            <p class="hint" v-if="!store.mailEnabled">El envío de correos no está configurado en este servidor, por lo que no es posible verificar el correo ni recuperar la contraseña por este medio. Si olvida su contraseña, la administración puede restablecerla.</p>
            <div class="row end"><button class="btn" @click="saveEmail" :disabled="busy || email === (store.user.recovery_email || '')">Guardar correo</button></div>
          </div>
        </section>
      </div>
      <section v-if="store.user.role === 'docente'" style="margin-top:16px" aria-labelledby="pf-asg">
        <h2 id="pf-asg" style="margin:0 0 10px">Mis cursos, grados y secciones</h2>
        <assignments-manager></assignments-manager>
      </section>
      <section class="card" style="margin-top:16px">
        <div class="card-head"><h2>Cambiar contraseña</h2></div>
        <div class="card-body"><password-form></password-form></div>
      </section>
    </div>`,
  };
})();
