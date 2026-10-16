/* Panel administrativo: seguimiento, institución, ciclos, grados, cursos, usuarios y bitácora. */
'use strict';
(function () {
  const { store, api, toast, confirm, fmtDate, fmtDateTime, ORD } = CYD;

  const adminNav = `
    <nav class="tabs" aria-label="Administración" style="margin-bottom:16px">
      <a v-for="t in [['/admin','Seguimiento'],['/admin/institucion','Institución'],['/admin/ciclos','Ciclos y bimestres'],['/admin/grados','Grados y secciones'],['/admin/cursos','Catálogo de cursos'],['/admin/docentes','Docentes'],['/admin/usuarios','Usuarios'],['/admin/bitacora','Bitácora']]"
        :href="'#' + t[0]" class="btn sm" :class="store.route.path === t[0] ? 'soft' : 'ghost'" :aria-current="store.route.path === t[0] ? 'page' : null">{{ t[1] }}</a>
    </nav>`;

  // ================================================================ Seguimiento
  CYD.components['view-admin-plans'] = {
    data: () => ({ store, data: null, loading: false, q: '', status: '', ORD }),
    computed: {
      rows() {
        if (!this.data) return [];
        const k = this.q.toLowerCase();
        return this.data.rows.filter(r => (!k || `${r.teacher_name} ${r.course_name} ${r.grade_name} ${r.section_name}`.toLowerCase().includes(k))
          && (!this.status || r.bimesters.some(b => b.status === this.status)));
      },
      counts() {
        const c = { sin_iniciar: 0, borrador: 0, listo: 0 };
        if (this.data) this.data.rows.forEach(r => r.bimesters.forEach(b => { c[b.status]++; }));
        return c;
      },
    },
    watch: { 'store.yearId'() { this.load(); } },
    mounted() { this.load(); },
    methods: {
      fmtDate, fmtDateTime,
      async load() {
        if (!store.yearId) return;
        this.loading = true;
        try { this.data = await api('GET', `/api/admin/plans?school_year_id=${store.yearId}`); } finally { this.loading = false; }
      },
    },
    template: `
    <div>
      <div class="page-head"><div><h1>Administración del colegio</h1><p>Seguimiento del estado de las planificaciones. “Listo” indica que el docente completó y validó su plan; no equivale a aprobación.</p></div><year-select></year-select></div>
      ${adminNav}
      <div v-if="!store.years.length" class="alert warn"><span class="ic">ℹ</span><span>Primero cree un ciclo escolar en <a href="#/admin/ciclos">Ciclos y bimestres</a>, luego configure grados y secciones.</span></div>
      <template v-else>
        <div class="grid g3" style="margin-bottom:16px">
          <div class="card card-pad"><div class="muted">Sin iniciar</div><div style="font-size:1.6rem;font-weight:700">○ {{ counts.sin_iniciar }}</div></div>
          <div class="card card-pad"><div class="muted">Borrador</div><div style="font-size:1.6rem;font-weight:700;color:var(--amber)">✎ {{ counts.borrador }}</div></div>
          <div class="card card-pad"><div class="muted">Listo</div><div style="font-size:1.6rem;font-weight:700;color:var(--green-800)">✓ {{ counts.listo }}</div></div>
        </div>
        <section class="card">
          <div class="card-head">
            <div class="row" style="flex:1">
              <input type="search" v-model="q" placeholder="Buscar docente, curso, grado…" style="max-width:320px" aria-label="Buscar">
              <select v-model="status" style="max-width:200px" aria-label="Filtrar por estado"><option value="">Todos los estados</option><option value="sin_iniciar">Con bimestres sin iniciar</option><option value="borrador">Con borradores</option><option value="listo">Con planes listos</option></select>
            </div>
            <span class="muted">{{ rows.length }} grupo(s)</span>
          </div>
          <loading-box v-if="loading"></loading-box>
          <div v-else-if="!rows.length" class="empty">No hay asignaciones registradas por docentes en este ciclo.</div>
          <div v-else class="table-wrap"><table class="data">
            <thead><tr><th>Docente</th><th>Curso y grupo</th><th v-for="b in data.bimesters">{{ ORD[b.number] }} bim.</th></tr></thead>
            <tbody><tr v-for="r in rows" :key="r.assignment_id">
              <td><b>{{ r.teacher_name }}</b><div v-if="!r.teacher_active" class="badge none">Cuenta desactivada</div></td>
              <td>{{ r.course_name }}<div class="muted" style="font-size:.82rem">{{ r.grade_name }} · Sección {{ r.section_name }}</div></td>
              <td v-for="b in r.bimesters" :key="b.bimester_id">
                <status-badge :status="b.status"></status-badge>
                <template v-if="b.plan_id"><div class="muted" style="font-size:.75rem;margin-top:3px">Completitud: {{ b.completeness }}%</div>
                  <div class="muted" style="font-size:.75rem" v-if="b.ready_at">Listo: {{ fmtDateTime(b.ready_at) }}</div>
                  <div class="row" style="gap:4px;margin-top:4px"><a class="btn sm" :href="'#/plan/' + b.plan_id">Ver</a><a class="btn sm" :href="'#/plan/' + b.plan_id + '/hoja'">Hoja</a><a class="btn sm" :href="'#/plan/' + b.plan_id + '/plan-bimestral'">Plan</a></div></template>
              </td>
            </tr></tbody></table></div>
        </section>
      </template>
    </div>`,
  };

  // ================================================================ Institución
  CYD.components['view-admin-institution'] = {
    data: () => ({ store, f: null, busy: false, logoV: Date.now() }),
    async mounted() { this.f = await api('GET', '/api/admin/institution'); },
    methods: {
      async save() {
        this.busy = true;
        try { await api('PUT', '/api/admin/institution', this.f); await CYD.loadMe(); toast('Configuración institucional guardada. Se aplicará a las hojas que se generen desde ahora.', 'ok'); }
        catch (e) { toast(e.message, 'error'); } finally { this.busy = false; }
      },
      async upload(ev) {
        const file = ev.target.files[0]; if (!file) return;
        try { await api('POST', '/api/admin/institution/logo', file); this.f.has_logo = true; this.logoV = Date.now(); toast('Logo actualizado.', 'ok'); }
        catch (e) { toast(e.message, 'error'); }
        ev.target.value = '';
      },
      async removeLogo() {
        if (!await confirm({ title: 'Quitar logo', body: ['Las hojas nuevas se generarán sin logo. Las ya generadas no cambian.'], confirmText: 'Quitar', danger: true })) return;
        await api('DELETE', '/api/admin/institution/logo'); this.f.has_logo = false; this.logoV = Date.now();
      },
    },
    template: `
    <div>
      <div class="page-head"><div><h1>Configuración institucional</h1><p>Textos fijos del encabezado, observaciones, nota y firmas de la hoja de actividades.</p></div></div>
      ${adminNav}
      <loading-box v-if="!f"></loading-box>
      <div v-else class="grid g-split2">
        <section class="card"><div class="card-head"><h2>Datos del establecimiento</h2></div>
          <div class="card-body stack">
            <div class="grid g2">
              <div class="field"><label for="in-fn">Nombre completo del establecimiento</label><input id="in-fn" type="text" v-model="f.full_name"></div>
              <div class="field"><label for="in-sn">Nombre corto</label><input id="in-sn" type="text" v-model="f.short_name"></div>
              <div class="field"><label for="in-l1">Encabezado — línea 1</label><input id="in-l1" type="text" v-model="f.header_line1"></div>
              <div class="field"><label for="in-l2">Encabezado — línea 2</label><input id="in-l2" type="text" v-model="f.header_line2"></div>
            </div>
            <div class="field"><label for="in-mt">Lema</label><input id="in-mt" type="text" v-model="f.motto"></div>
            <div class="grid g2">
              <div class="field"><label for="in-st">Título de la hoja</label><input id="in-st" type="text" v-model="f.sheet_title"><span class="hint">Se completa con el bimestre: “… SEGUNDO BIMESTRE”.</span></div>
              <div class="field"><label for="in-cb">Texto de la franja</label><input id="in-cb" type="text" v-model="f.contents_banner"></div>
            </div>
            <div class="field"><label for="in-ob">Observaciones (texto institucional)</label><textarea id="in-ob" v-model="f.default_observations" rows="2"></textarea></div>
            <div class="field"><label for="in-nt">Nota resaltada</label><textarea id="in-nt" v-model="f.note_text" rows="2"></textarea></div>
            <div class="grid g3">
              <div class="field"><label for="in-s1">Firma 1</label><input id="in-s1" type="text" v-model="f.sign_teacher"></div>
              <div class="field"><label for="in-s2">Firma 2</label><input id="in-s2" type="text" v-model="f.sign_parent"></div>
              <div class="field"><label for="in-s3">Visto bueno</label><input id="in-s3" type="text" v-model="f.sign_direction"></div>
            </div>
            <div class="row end"><button class="btn primary" @click="save" :disabled="busy">Guardar configuración</button></div>
          </div></section>
        <section class="card"><div class="card-head"><h2>Logo institucional</h2></div>
          <div class="card-body stack">
            <div style="display:grid;place-items:center;min-height:150px;background:var(--surface-2);border-radius:12px;border:1px dashed var(--line-strong)">
              <img v-if="f.has_logo" :src="base + '/api/institution/logo?v=' + logoV" alt="Logo actual" style="max-width:150px;max-height:150px">
              <span v-else class="muted">Sin logo</span></div>
            <label class="btn" style="width:100%">Subir imagen (PNG, JPG o WEBP)<input type="file" accept="image/png,image/jpeg,image/webp" @change="upload" class="sr-only"></label>
            <button v-if="f.has_logo" class="btn danger" @click="removeLogo">Quitar logo</button>
            <p class="hint">Se usa en la hoja de actividades y en la aplicación. El logo inicial se tomó de la plantilla Word proporcionada.</p>
          </div></section>
      </div>
    </div>`,
  };

  // ================================================================ Ciclos y bimestres
  CYD.components['view-admin-years'] = {
    data: () => ({ store, name: '', busy: false, edit: {} }),
    mounted() { this.reset(); },
    methods: {
      ORD: n => ORD[n],
      reset() { const e = {}; store.years.forEach(y => { e[y.id] = { name: y.name, active: !!y.active, bimesters: y.bimesters.map(b => ({ ...b })) }; }); this.edit = e; },
      async create() {
        this.busy = true;
        try { const r = await api('POST', '/api/admin/school-years', { name: this.name }); this.name = ''; await CYD.loadYears(); CYD.setYear(r.id); this.reset(); toast('Ciclo creado con sus cuatro bimestres. Configure las fechas.', 'ok'); }
        catch (e) { toast(e.message, 'error'); } finally { this.busy = false; }
      },
      async saveYear(y) {
        const e = this.edit[y.id];
        try {
          await api('PUT', `/api/admin/school-years/${y.id}`, { name: e.name, active: e.active });
          await api('PUT', `/api/admin/school-years/${y.id}/bimesters`, { bimesters: e.bimesters });
          await CYD.loadYears(); this.reset(); toast('Ciclo actualizado.', 'ok');
        } catch (err) { toast(err.message, 'error', 7000); }
      },
    },
    template: `
    <div>
      <div class="page-head"><div><h1>Ciclos escolares y bimestres</h1><p>Cada ciclo tiene cuatro bimestres con fecha de inicio y fin.</p></div></div>
      ${adminNav}
      <section class="card card-pad" style="margin-bottom:16px">
        <form class="row" @submit.prevent="create"><div class="field" style="flex:1;max-width:260px"><label for="ny">Nuevo ciclo escolar</label><input id="ny" type="text" v-model.trim="name" placeholder="Ej.: 2027"></div>
          <button class="btn primary" :disabled="!name || busy" style="align-self:flex-end">＋ Crear ciclo</button></form>
      </section>
      <section v-for="y in store.years" :key="y.id" class="card" style="margin-bottom:16px">
        <template v-if="edit[y.id]">
        <div class="card-head"><div class="row"><div class="field"><label :for="'yn' + y.id">Nombre</label><input :id="'yn' + y.id" type="text" v-model="edit[y.id].name" style="max-width:200px"></div>
          <label class="check" :class="{ on: edit[y.id].active }" style="align-self:flex-end"><input type="checkbox" v-model="edit[y.id].active"> Activo (visible para docentes)</label></div>
          <button class="btn primary" @click="saveYear(y)">Guardar ciclo</button></div>
        <div class="card-body"><div class="grid g4">
          <div v-for="b in edit[y.id].bimesters" :key="b.id" class="stack" style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:12px">
            <b>{{ ORD(b.number) }} bimestre</b>
            <div class="field"><label :for="'bs' + b.id">Inicio</label><input :id="'bs' + b.id" type="date" v-model="b.start_date"></div>
            <div class="field"><label :for="'be' + b.id">Fin</label><input :id="'be' + b.id" type="date" v-model="b.end_date" :min="b.start_date"></div>
          </div></div></div>
        </template>
      </section>
    </div>`,
  };

  // ================================================================ Grados y secciones
  CYD.components['view-admin-grades'] = {
    data: () => ({ store, grades: [], f: { name: '', level: '', sort_order: 0 }, sec: {} }),
    mounted() { this.load(); },
    methods: {
      async load() { this.grades = await api('GET', '/api/grades'); },
      async add() {
        try { await api('POST', '/api/admin/grades', this.f); this.f = { name: '', level: this.f.level, sort_order: Number(this.f.sort_order) + 1 }; this.load(); toast('Grado agregado.', 'ok'); }
        catch (e) { toast(e.message, 'error'); }
      },
      async saveGrade(g) { try { await api('PUT', `/api/admin/grades/${g.id}`, g); toast('Grado actualizado.', 'ok'); this.load(); } catch (e) { toast(e.message, 'error'); } },
      async delGrade(g) {
        if (!await confirm({ title: 'Eliminar grado', body: [`Se eliminará “${g.name}” y sus secciones. Sólo es posible si no tiene asignaciones.`], confirmText: 'Eliminar', danger: true })) return;
        try { await api('DELETE', `/api/admin/grades/${g.id}`); this.load(); } catch (e) { toast(e.message, 'error'); }
      },
      async addSection(g) {
        const name = (this.sec[g.id] || '').trim(); if (!name) return;
        try { await api('POST', `/api/admin/grades/${g.id}/sections`, { name }); this.sec[g.id] = ''; this.load(); } catch (e) { toast(e.message, 'error'); }
      },
      async toggleSection(s) { try { await api('PUT', `/api/admin/sections/${s.id}`, { name: s.name, active: !s.active }); this.load(); } catch (e) { toast(e.message, 'error'); } },
      async delSection(s) {
        if (!await confirm({ title: 'Eliminar sección', body: [`Se eliminará la sección ${s.name}. Sólo es posible si no tiene asignaciones.`], confirmText: 'Eliminar', danger: true })) return;
        try { await api('DELETE', `/api/admin/sections/${s.id}`); this.load(); } catch (e) { toast(e.message, 'error'); }
      },
    },
    template: `
    <div>
      <div class="page-head"><div><h1>Grados, niveles y secciones</h1><p>Las secciones disponibles dependen de cada grado.</p></div></div>
      ${adminNav}
      <section class="card card-pad" style="margin-bottom:16px">
        <form class="grid g4" @submit.prevent="add" style="align-items:end">
          <div class="field"><label for="gn">Grado</label><input id="gn" type="text" v-model="f.name" placeholder="Ej.: Quinto primaria"></div>
          <div class="field"><label for="gl">Nivel educativo</label><input id="gl" type="text" v-model="f.level" placeholder="Ej.: Primaria"></div>
          <div class="field"><label for="go">Orden</label><input id="go" type="number" v-model.number="f.sort_order"></div>
          <button class="btn primary" :disabled="!f.name">＋ Agregar grado</button>
        </form>
      </section>
      <div v-if="!grades.length" class="card"><div class="empty">Aún no hay grados configurados.</div></div>
      <div class="grid g2">
        <section v-for="g in grades" :key="g.id" class="card">
          <div class="card-head">
            <div class="grid g3" style="flex:1;gap:8px">
              <input type="text" v-model="g.name" :aria-label="'Nombre del grado ' + g.name">
              <input type="text" v-model="g.level" aria-label="Nivel" placeholder="Nivel">
              <input type="number" v-model.number="g.sort_order" aria-label="Orden">
            </div>
          </div>
          <div class="card-body stack">
            <div class="row">
              <span v-for="s in g.sections" :key="s.id" class="badge" :class="s.active ? 'ready' : 'none'" style="font-size:.85rem;padding:4px 6px 4px 10px">
                Sección {{ s.name }}{{ s.active ? '' : ' (inactiva)' }}
                <button class="btn sm icon ghost" style="min-height:22px;padding:0 6px" @click="toggleSection(s)" :aria-label="(s.active ? 'Desactivar' : 'Activar') + ' sección ' + s.name">{{ s.active ? '⏸' : '▶' }}</button>
                <button class="btn sm icon ghost danger" style="min-height:22px;padding:0 6px" @click="delSection(s)" :aria-label="'Eliminar sección ' + s.name">✕</button></span>
              <span v-if="!g.sections.length" class="muted">Sin secciones</span>
            </div>
            <form class="row" @submit.prevent="addSection(g)"><input type="text" v-model="sec[g.id]" placeholder="Nueva sección (ej.: A)" style="max-width:180px" :aria-label="'Nueva sección para ' + g.name"><button class="btn sm soft">＋ Sección</button></form>
            <div class="row between">
              <label class="check" :class="{ on: g.active }"><input type="checkbox" v-model="g.active" :true-value="1" :false-value="0"> Activo</label>
              <div class="row"><button class="btn sm danger" @click="delGrade(g)">Eliminar</button><button class="btn sm primary" @click="saveGrade(g)">Guardar grado</button></div>
            </div>
          </div>
        </section>
      </div>
    </div>`,
  };

  // ================================================================ Cursos
  CYD.components['view-admin-courses'] = {
    data: () => ({ store, list: [], q: '', editing: null, f: {} }),
    computed: { filtered() { const k = this.q.toLowerCase(); return this.list.filter(c => !k || c.name.toLowerCase().includes(k)); } },
    mounted() { this.load(); },
    methods: {
      fmtDateTime,
      async load() { this.list = await api('GET', '/api/admin/courses'); },
      start(c) { this.editing = c.id; this.f = { name: c.name, active: !!c.active }; },
      async save(c) {
        if (this.f.name !== c.name && c.teachers > 0 && !await confirm({ title: 'Renombrar curso compartido', body: [`“${c.name}” lo usan ${c.teachers} docente(s). El nuevo nombre se verá en sus planificaciones y en las hojas que se generen desde ahora. Las hojas ya generadas no cambian.`], confirmText: 'Renombrar' })) return;
        try { await api('PUT', `/api/admin/courses/${c.id}`, this.f); this.editing = null; this.load(); toast('Curso actualizado.', 'ok'); } catch (e) { toast(e.message, 'error'); }
      },
      async del(c) {
        if (!await confirm({ title: 'Eliminar curso', body: [`Se eliminará “${c.name}”.`], confirmText: 'Eliminar', danger: true })) return;
        try { await api('DELETE', `/api/admin/courses/${c.id}`); this.load(); } catch (e) { toast(e.message, 'error'); }
      },
    },
    template: `
    <div>
      <div class="page-head"><div><h1>Catálogo compartido de cursos</h1><p>Los docentes crean cursos cuando no existen; la administración corrige nombres o desactiva cursos. No se fusionan cursos automáticamente.</p></div></div>
      ${adminNav}
      <section class="card">
        <div class="card-head"><input type="search" v-model="q" placeholder="Buscar curso…" style="max-width:320px" aria-label="Buscar curso"><span class="muted">{{ filtered.length }} curso(s)</span></div>
        <div class="table-wrap"><table class="data cards">
          <thead><tr><th>Curso</th><th>Docentes</th><th>Asignaciones</th><th>Creado por</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead>
          <tbody><tr v-for="c in filtered" :key="c.id">
            <td data-label="Curso"><input v-if="editing === c.id" type="text" v-model="f.name"><b v-else>{{ c.name }}</b></td>
            <td data-label="Docentes">{{ c.teachers }}</td><td data-label="Asignaciones">{{ c.assignments }}</td>
            <td data-label="Creado por"><small>{{ c.created_by_name || '—' }}<br>{{ fmtDateTime(c.created_at) }}</small></td>
            <td data-label="Estado"><label v-if="editing === c.id" class="check"><input type="checkbox" v-model="f.active"> Activo</label><span v-else class="badge" :class="c.active ? 'ready' : 'none'">{{ c.active ? 'Activo' : 'Inactivo' }}</span></td>
            <td><div class="row end"><template v-if="editing === c.id"><button class="btn sm primary" @click="save(c)">Guardar</button><button class="btn sm ghost" @click="editing = null">Cancelar</button></template>
              <template v-else><button class="btn sm" @click="start(c)">Editar</button><button class="btn sm danger" @click="del(c)" :disabled="c.assignments > 0">Eliminar</button></template></div></td>
          </tr></tbody></table></div>
      </section>
    </div>`,
  };

  // ================================================================ Listado de docentes
  CYD.components['view-admin-teachers'] = {
    data: () => ({ store, list: [], q: '', filter: '', editing: null, f: {}, name: '' }),
    computed: {
      filtered() {
        const k = CYD.norm(this.q);
        return this.list.filter(t => (!k || CYD.norm(`${t.full_name} ${t.username || ''} ${t.courses || ''}`).includes(k))
          && (!this.filter || (this.filter === 'con' ? t.username : !t.username)));
      },
      withAccount() { return this.list.filter(t => t.username).length; },
    },
    mounted() { this.load(); },
    methods: {
      async load() { this.list = await api('GET', '/api/admin/teachers'); },
      start(t) { this.editing = t.id; this.f = { full_name: t.full_name, active: !!t.active }; },
      async save(t) {
        try { await api('PUT', `/api/admin/teachers/${t.id}`, this.f); this.editing = null; this.load(); toast('Docente actualizado.', 'ok'); } catch (e) { toast(e.message, 'error'); }
      },
      async add() {
        try { await api('POST', '/api/admin/teachers', { full_name: this.name }); this.name = ''; this.load(); toast('Docente agregado al listado.', 'ok'); } catch (e) { toast(e.message, 'error'); }
      },
      async unlink(t) {
        if (!await confirm({ title: 'Liberar nombre', body: [`“${t.full_name}” dejará de estar vinculado a la cuenta “${t.username}”. La cuenta y sus planificaciones no se borran; el nombre podrá elegirse de nuevo al registrarse.`], confirmText: 'Liberar' })) return;
        try { await api('POST', `/api/admin/teachers/${t.id}/unlink`, {}); this.load(); } catch (e) { toast(e.message, 'error'); }
      },
    },
    template: `
    <div>
      <div class="page-head"><div><h1>Listado de docentes</h1><p>Al registrarse, cada docente busca su nombre en este listado y se precargan los cursos que tiene asignados. Un nombre sólo puede tener una cuenta.</p></div></div>
      ${adminNav}
      <div class="stat-row">
        <div class="stat"><b>{{ list.length }}</b><span>Docentes en el listado</span></div>
        <div class="stat"><b>{{ withAccount }}</b><span>Con cuenta creada</span></div>
        <div class="stat"><b>{{ list.length - withAccount }}</b><span>Pendientes de registrarse</span></div>
      </div>
      <section class="card">
        <div class="card-head">
          <div class="row" style="flex:1">
            <input type="search" v-model="q" placeholder="Buscar docente, usuario o curso…" style="max-width:340px" aria-label="Buscar docente">
            <select v-model="filter" style="max-width:220px" aria-label="Filtrar por cuenta"><option value="">Todos</option><option value="con">Con cuenta</option><option value="sin">Sin cuenta</option></select>
          </div>
          <form class="row" @submit.prevent="add"><input type="text" v-model="name" placeholder="Nombre completo del docente" style="width:260px" aria-label="Nombre del nuevo docente"><button class="btn sm soft" :disabled="name.trim().length < 5">＋ Agregar docente</button></form>
        </div>
        <div class="table-wrap"><table class="data cards">
          <thead><tr><th>Docente</th><th>Cursos asignados</th><th>Cuenta</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead>
          <tbody><tr v-for="t in filtered" :key="t.id">
            <td data-label="Docente"><input v-if="editing === t.id" type="text" v-model="f.full_name"><b v-else>{{ t.full_name }}</b><br v-if="t.external_id && editing !== t.id"><small v-if="t.external_id && editing !== t.id">ID {{ t.external_id }}</small></td>
            <td data-label="Cursos"><small>{{ t.loads ? t.loads + ' grupo(s) · ' + t.courses.split(',').slice(0, 4).join(', ') + (t.courses.split(',').length > 4 ? '…' : '') : '—' }}</small></td>
            <td data-label="Cuenta"><span v-if="t.username" class="badge ready">✓ {{ t.username }}</span><span v-else class="badge none">Sin cuenta</span></td>
            <td data-label="Estado"><label v-if="editing === t.id" class="check"><input type="checkbox" v-model="f.active"> Activo</label><span v-else class="badge" :class="t.active ? 'ready' : 'none'">{{ t.active ? 'Activo' : 'Inactivo' }}</span></td>
            <td><div class="row end"><template v-if="editing === t.id"><button class="btn sm primary" @click="save(t)">Guardar</button><button class="btn sm ghost" @click="editing = null">Cancelar</button></template>
              <template v-else><button class="btn sm" @click="start(t)">Editar</button><button v-if="t.username" class="btn sm ghost" @click="unlink(t)">Liberar</button></template></div></td>
          </tr></tbody></table></div>
      </section>
    </div>`,
  };

  // ================================================================ Usuarios
  CYD.components['view-admin-users'] = {
    data: () => ({ store, list: [], q: '', f: { full_name: '', username: '', role: 'docente' } }),
    computed: { filtered() { const k = this.q.toLowerCase(); return this.list.filter(u => !k || `${u.full_name} ${u.username}`.toLowerCase().includes(k)); } },
    mounted() { this.load(); },
    methods: {
      fmtDateTime,
      async load() { this.list = await api('GET', '/api/admin/users'); },
      showTemp(user, temp) {
        CYD.alert({ title: 'Contraseña temporal', body: [`Entregue esta contraseña temporal a ${user} por un medio seguro. Se muestra una sola vez y deberá cambiarse al ingresar:`], code: temp });
      },
      async create() {
        try { const r = await api('POST', '/api/admin/users', this.f); this.showTemp(this.f.username, r.temporary_password); this.f = { full_name: '', username: '', role: 'docente' }; this.load(); }
        catch (e) { toast(e.message, 'error'); }
      },
      async toggle(u) {
        const act = !u.active;
        if (!await confirm({ title: act ? 'Activar cuenta' : 'Desactivar cuenta', body: [act ? `${u.full_name} podrá volver a ingresar.` : `${u.full_name} no podrá ingresar y se cerrarán sus sesiones. Sus planificaciones se conservan.`], confirmText: act ? 'Activar' : 'Desactivar', danger: !act })) return;
        try { await api('PUT', `/api/admin/users/${u.id}/active`, { active: act }); this.load(); } catch (e) { toast(e.message, 'error'); }
      },
      async reset(u) {
        if (!await confirm({ title: 'Restablecer acceso', body: [`Se generará una contraseña temporal para ${u.full_name} (${u.username}). La contraseña actual dejará de funcionar y se cerrarán sus sesiones.`, 'Nadie puede ver la contraseña anterior.'], confirmText: 'Restablecer' })) return;
        try { const r = await api('POST', `/api/admin/users/${u.id}/reset-password`, {}); this.showTemp(u.username, r.temporary_password); this.load(); } catch (e) { toast(e.message, 'error'); }
      },
    },
    template: `
    <div>
      <div class="page-head"><div><h1>Usuarios</h1><p>Activar o desactivar cuentas y apoyar el restablecimiento de acceso.</p></div></div>
      ${adminNav}
      <section class="card card-pad" style="margin-bottom:16px">
        <form class="grid g4" style="align-items:end" @submit.prevent="create">
          <div class="field"><label for="un">Nombre completo</label><input id="un" type="text" v-model="f.full_name"></div>
          <div class="field"><label for="uu">Usuario</label><input id="uu" type="text" v-model.trim="f.username" autocapitalize="none"></div>
          <div class="field"><label for="ur">Rol</label><select id="ur" v-model="f.role"><option value="docente">Docente</option><option value="admin">Administrador</option></select></div>
          <button class="btn primary" :disabled="!f.full_name || !f.username">＋ Crear cuenta</button>
        </form>
        <p class="hint" style="margin-top:8px">Los docentes también pueden registrarse por su cuenta desde la pantalla de ingreso.</p>
      </section>
      <section class="card">
        <div class="card-head"><input type="search" v-model="q" placeholder="Buscar usuario…" style="max-width:320px" aria-label="Buscar usuario"><span class="muted">{{ filtered.length }} usuario(s)</span></div>
        <div class="table-wrap"><table class="data cards">
          <thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Correo de recuperación</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead>
          <tbody><tr v-for="u in filtered" :key="u.id">
            <td data-label="Nombre"><b>{{ u.full_name }}</b><div class="muted" style="font-size:.78rem">Desde {{ fmtDateTime(u.created_at) }}</div></td>
            <td data-label="Usuario">{{ u.username }}</td><td data-label="Rol">{{ u.role === 'admin' ? 'Administrador' : 'Docente' }}</td>
            <td data-label="Correo">{{ u.recovery_email || '—' }} <span v-if="u.recovery_email" class="badge" :class="u.email_verified ? 'ready' : 'draft'">{{ u.email_verified ? 'Verificado' : 'Sin verificar' }}</span></td>
            <td data-label="Estado"><span class="badge" :class="u.active ? 'ready' : 'none'">{{ u.active ? '✓ Activa' : '⏸ Desactivada' }}</span><span v-if="u.must_change_password" class="badge info" style="margin-left:4px">Clave temporal</span></td>
            <td><div class="row end"><button class="btn sm" @click="reset(u)">Restablecer acceso</button>
              <button class="btn sm" :class="u.active ? 'danger' : ''" @click="toggle(u)" :disabled="u.id === store.user.id">{{ u.active ? 'Desactivar' : 'Activar' }}</button></div></td>
          </tr></tbody></table></div>
      </section>
    </div>`,
  };

  // ================================================================ Bitácora
  CYD.components['view-admin-audit'] = {
    data: () => ({ store, list: [] }),
    async mounted() { this.list = await api('GET', '/api/admin/audit'); },
    methods: { fmtDateTime },
    template: `
    <div>
      <div class="page-head"><div><h1>Bitácora</h1><p>Últimas 300 acciones registradas (creación, modificaciones, cambios de estado, consultas e intervenciones administrativas).</p></div></div>
      ${adminNav}
      <section class="card"><div class="table-wrap"><table class="data cards">
        <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Elemento</th><th>Detalle</th></tr></thead>
        <tbody><tr v-for="l in list" :key="l.id">
          <td data-label="Fecha">{{ fmtDateTime(l.at) }}</td><td data-label="Usuario">{{ l.user_name || '—' }} <small class="muted">{{ l.user_role }}</small></td>
          <td data-label="Acción">{{ l.action.replace(/_/g, ' ') }}</td>
          <td data-label="Elemento">{{ l.entity }} {{ l.entity_id }} <a v-if="l.plan_id" :href="'#/plan/' + l.plan_id">(plan)</a></td>
          <td data-label="Detalle"><small class="muted">{{ l.details }}</small></td>
        </tr></tbody></table></div></section>
    </div>`,
  };
})();
