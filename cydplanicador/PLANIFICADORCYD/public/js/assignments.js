/* Cursos, grados y secciones del docente: selector de curso con creación, filas de asignación y administrador de asignaciones.
   Se usan en el registro, en «Mis cursos» y en el perfil. */
'use strict';
(function () {
  const { store, api, toast, confirm, fieldErrors, uuid } = CYD;

  // Misma normalización que el servidor: sin acentos, minúsculas, espacios colapsados.
  const norm = s => String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();
  const clean = s => String(s || '').replace(/\s+/g, ' ').trim();

  // Una fila = un curso con uno o varios grados; cada grado lleva sus secciones.
  CYD.newGradeEntry = (over = {}) => ({ key: uuid(), grade_id: '', section_ids: [], ...over });
  CYD.newAssignmentRow = (over = {}) => ({ key: uuid(), course: null, grades: [CYD.newGradeEntry()], ...over });
  // Al servidor se envía una fila por cada grado del curso; el servidor crea una relación por sección seleccionada.
  CYD.rowsPayload = rows => rows.flatMap(r => r.grades.map(g => ({
    course_id: r.course && r.course.id ? r.course.id : null,
    new_course_name: r.course && r.course.isNew ? r.course.name : '',
    grade_id: g.grade_id || null,
    section_ids: g.section_ids,
  })));
  // Traduce los errores del servidor (rows.<n>.campo sobre la lista enviada) a la fila y al grado del formulario.
  CYD.rowErrors = (rows, fields) => {
    const out = {};
    let n = 0;
    rows.forEach((r, i) => r.grades.forEach((g, j) => {
      if (fields[`rows.${n}.course`]) out[`rows.${i}.course`] = fields[`rows.${n}.course`];
      for (const f of ['grade', 'sections']) if (fields[`rows.${n}.${f}`]) out[`rows.${i}.g${j}.${f}`] = fields[`rows.${n}.${f}`];
      n++;
    }));
    for (const [k, v] of Object.entries(fields)) if (!k.startsWith('rows.')) out[k] = v;
    return out;
  };
  // Agrupa filas «curso + grado + secciones» (de la carga académica) en filas por curso con varios grados.
  CYD.rowsFromLoad = list => {
    const m = new Map();
    for (const x of list) {
      if (!m.has(x.course_id)) m.set(x.course_id, CYD.newAssignmentRow({ course: { id: x.course_id, name: x.course_name }, grades: [] }));
      m.get(x.course_id).grades.push(CYD.newGradeEntry({ grade_id: x.grade_id, section_ids: [...x.section_ids] }));
    }
    return [...m.values()];
  };

  // ================================================================ Selector de curso con buscador y «Crear nuevo curso»
  // Valor: { id, name } para un curso del catálogo, o { id: null, name, isNew: true } para uno nuevo, que se guarda
  // en el catálogo compartido junto con el formulario (así no quedan cursos sueltos si el formulario no se envía).
  CYD.components['course-picker'] = {
    props: { modelValue: Object, courses: { type: Array, default: () => [] }, pending: { type: Array, default: () => [] }, invalid: String, uid: String },
    emits: ['update:modelValue', 'found'],
    data() {
      return { text: this.modelValue ? this.modelValue.name : '', open: false, active: -1,
        creating: false, newName: '', msg: '', dup: null, similar: [], checking: false };
    },
    computed: {
      all() {
        const list = this.courses.map(c => ({ id: c.id, name: c.name }));
        for (const n of this.pending) if (!list.some(c => norm(c.name) === norm(n))) list.push({ id: null, name: n, isNew: true });
        return list.sort((a, b) => a.name.localeCompare(b.name, 'es'));
      },
      options() {
        const k = this.modelValue && this.text === this.modelValue.name ? '' : norm(this.text);
        return this.all.filter(o => !k || norm(o.name).includes(k)).slice(0, 50);
      },
    },
    watch: { modelValue(v) { if (v && v.name !== this.text) this.text = v.name; if (!v && !this.open) this.text = ''; } },
    methods: {
      pick(o) {
        this.$emit('update:modelValue', { ...o });
        this.text = o.name; this.open = false; this.creating = false; this.msg = ''; this.dup = null; this.similar = [];
      },
      onInput() { this.open = true; this.active = -1; if (this.modelValue) this.$emit('update:modelValue', null); },
      onKey(e) {
        const n = this.options.length + 1;
        if (e.key === 'ArrowDown') { e.preventDefault(); this.open = true; this.active = (this.active + 1) % n; }
        else if (e.key === 'ArrowUp') { e.preventDefault(); this.open = true; this.active = (this.active - 1 + n) % n; }
        else if (e.key === 'Enter') {
          e.preventDefault();
          if (this.active >= 0 && this.active < this.options.length) this.pick(this.options[this.active]);
          else if (this.active === this.options.length) this.startCreate();
          else { const ex = this.all.find(o => norm(o.name) === norm(this.text)); if (ex) this.pick(ex); }
        } else if (e.key === 'Escape') this.open = false;
      },
      startCreate() {
        this.open = false; this.creating = true; this.msg = ''; this.dup = null; this.similar = [];
        this.newName = this.modelValue ? '' : clean(this.text);
        this.$nextTick(() => { const el = document.getElementById(this.uid + '-new'); if (el) el.focus(); });
      },
      cancel() { this.creating = false; this.msg = ''; this.dup = null; this.similar = []; },
      async create(force) {
        const name = clean(this.newName);
        this.msg = ''; this.dup = null;
        if (norm(name).length < 2) { this.msg = 'Escriba el nombre del curso.'; return; }
        const local = this.all.find(o => norm(o.name) === norm(name));
        if (local) { this.dup = local; this.msg = 'Este curso ya está registrado. Puedes seleccionarlo.'; this.similar = []; return; }
        this.checking = true;
        try {
          const chk = await api('GET', `/api/courses/check?name=${encodeURIComponent(name)}`);
          if (chk.exact) {
            if (!chk.exact.active) { this.msg = `El curso “${chk.exact.name}” existe pero está desactivado. Solicite a la administración que lo active.`; return; }
            const c = { id: chk.exact.id, name: chk.exact.name };
            this.$emit('found', c);
            this.dup = c; this.msg = 'Este curso ya está registrado. Puedes seleccionarlo.'; this.similar = [];
            return;
          }
          if (!force && chk.similar.length) { this.similar = chk.similar; return; }
          this.pick({ id: null, name: chk.display, isNew: true });
          toast(`Curso “${chk.display}” creado y seleccionado. Quedará en el catálogo compartido al guardar.`, 'ok', 6000);
        } catch (e) { this.msg = e.message; }
        finally { this.checking = false; }
      },
      blur() { setTimeout(() => { this.open = false; if (!this.modelValue && !this.creating) { const ex = this.all.find(o => norm(o.name) === norm(this.text)); if (ex) this.pick(ex); } }, 150); },
    },
    template: `
      <div class="combo">
        <div class="combo-in">
          <input :id="uid" type="text" role="combobox" :aria-expanded="open" :aria-controls="uid + '-list'" aria-autocomplete="list"
            v-model="text" @input="onInput" @focus="open = true" @keydown="onKey" @blur="blur" placeholder="Buscar curso…"
            :aria-invalid="!!invalid" autocomplete="off">
          <span v-if="modelValue && modelValue.isNew" class="badge info combo-tag">Nuevo</span>
        </div>
        <ul v-if="open" :id="uid + '-list'" class="combo-list" role="listbox">
          <li v-for="(o, i) in options" :key="(o.id || 'n') + o.name" role="option" :aria-selected="i === active" @mousedown.prevent="pick(o)">
            <span>{{ o.name }}</span><small v-if="o.isNew" class="muted">nuevo</small></li>
          <li v-if="!options.length" class="none" role="presentation">No hay cursos con “{{ text.trim() }}”.</li>
          <li class="create" role="option" :aria-selected="active === options.length" @mousedown.prevent="startCreate">＋ Crear nuevo curso</li>
        </ul>
        <div v-if="creating" class="combo-create">
          <label :for="uid + '-new'" class="label">Nombre del nuevo curso</label>
          <div class="row">
            <input :id="uid + '-new'" type="text" v-model="newName" @keydown.enter.prevent="create(false)" @keydown.esc.prevent="cancel" style="flex:1;min-width:160px" autocomplete="off">
            <button type="button" class="btn sm primary" @click="create(false)" :disabled="checking">{{ checking ? 'Comprobando…' : 'Crear' }}</button>
            <button type="button" class="btn sm ghost" @click="cancel">Cancelar</button>
          </div>
          <div v-if="msg" class="alert warn" role="alert"><span class="ic">ℹ</span><div class="stack" style="gap:6px">
            <span>{{ msg }}</span>
            <div v-if="dup"><button type="button" class="btn sm soft" @click="pick(dup)">Seleccionar “{{ dup.name }}”</button></div></div></div>
          <div v-if="similar.length" class="alert info"><span class="ic">ℹ</span><div class="stack" style="gap:6px">
            <span>Ya existen cursos con nombre parecido. Si es uno de ellos, selecciónelo:</span>
            <div class="row"><button v-for="s in similar" :key="s.id" type="button" class="btn sm soft" @click="pick({ id: s.id, name: s.name })">{{ s.name }}</button></div>
            <div><button type="button" class="btn sm" @click="create(true)">Es diferente: crear “{{ newName.trim() }}”</button></div></div></div>
        </div>
      </div>`,
  };

  // ================================================================ Filas «curso → grados → secciones»
  CYD.components['assignment-rows'] = {
    props: { rows: Array, grades: Array, courses: Array, errs: { type: Object, default: () => ({}) }, taken: { type: Object, default: null } },
    emits: ['found', 'removed'],
    computed: {
      pending() { return [...new Set(this.rows.filter(r => r.course && r.course.isNew).map(r => r.course.name))]; },
      // Grados agrupados por nivel (Preprimaria, Primaria, Básico, Diversificado…) en el orden configurado
      gradeGroups() {
        const m = new Map();
        for (const g of this.grades) { const k = g.level || 'Grados'; if (!m.has(k)) m.set(k, []); m.get(k).push(g); }
        return [...m].map(([level, list]) => ({ level, list }));
      },
      summary() {
        const out = [];
        for (const r of this.rows) {
          if (!r.course) continue;
          for (const ge of r.grades) {
            const g = this.grade(ge);
            if (!g) continue;
            for (const sid of ge.section_ids) { const s = g.sections.find(x => x.id === sid); if (s) out.push(`${r.course.name} — ${g.name} — ${s.name}`); }
          }
        }
        return out;
      },
    },
    methods: {
      e(i, f) { return this.errs[`rows.${i}.${f}`]; },
      eg(i, j, f) { return this.errs[`rows.${i}.g${j}.${f}`]; },
      bad(i) { return Object.keys(this.errs).some(k => k.startsWith(`rows.${i}.`)); },
      grade(ge) { return this.grades.find(g => g.id === ge.grade_id); },
      usedElsewhere(r, ge, gid) { return r.grades.some(x => x !== ge && x.grade_id === gid); },
      setGrade(r, ge, v) {
        ge.grade_id = v; ge.section_ids = [];
        const g = this.grade(ge);
        if (g && g.sections.length === 1 && !this.isTaken(r, g.sections[0].id)) ge.section_ids = [g.sections[0].id];
      },
      toggle(ge, id) { const i = ge.section_ids.indexOf(id); i >= 0 ? ge.section_ids.splice(i, 1) : ge.section_ids.push(id); },
      isTaken(r, sid) { return !!(this.taken && r.course && r.course.id && this.taken.has(`${r.course.id}|${sid}`)); },
      focus(id) { this.$nextTick(() => { const el = document.getElementById(id); if (el) el.focus(); }); },
      add() { const row = CYD.newAssignmentRow(); this.rows.push(row); this.focus('cp-' + row.key); },
      addGrade(r) { const ge = CYD.newGradeEntry(); r.grades.push(ge); this.focus('gr-' + ge.key); },
      removeGrade(r, j) { r.grades.splice(j, 1); if (!r.grades.length) r.grades.push(CYD.newGradeEntry()); this.$emit('removed'); },
      remove(i) { this.rows.splice(i, 1); if (!this.rows.length) this.rows.push(CYD.newAssignmentRow()); this.$emit('removed'); },
    },
    template: `
      <div class="stack">
        <div v-for="(r, i) in rows" :key="r.key" class="asg-row" :class="{ bad: bad(i) }">
          <div class="asg-n" aria-hidden="true">{{ i + 1 }}</div>
          <div class="field asg-course"><label :for="'cp-' + r.key">Curso</label>
            <course-picker :uid="'cp-' + r.key" v-model="r.course" :courses="courses" :pending="pending" :invalid="e(i, 'course')" @found="$emit('found', $event)"></course-picker>
            <field-err :msg="e(i, 'course')"></field-err></div>
          <div class="asg-act"><button type="button" class="btn sm ghost danger-text" @click="remove(i)" :aria-label="'Quitar el curso de la fila ' + (i + 1)" title="Quitar este curso">✕ Quitar curso</button></div>
          <div class="asg-grades">
            <div v-for="(ge, j) in r.grades" :key="ge.key" class="grade-entry">
              <div class="field"><label :for="'gr-' + ge.key">{{ r.grades.length > 1 ? 'Grado ' + (j + 1) : 'Grado' }}</label>
                <select :id="'gr-' + ge.key" :title="grade(ge) ? grade(ge).name : ''" :value="ge.grade_id" @change="setGrade(r, ge, Number($event.target.value))" :aria-invalid="!!eg(i, j, 'grade')">
                  <option value="" disabled>Seleccione el grado…</option>
                  <optgroup v-for="grp in gradeGroups" :key="grp.level" :label="grp.level">
                    <option v-for="g in grp.list" :key="g.id" :value="g.id" :disabled="usedElsewhere(r, ge, g.id)">{{ g.name }}</option></optgroup></select>
                <field-err :msg="eg(i, j, 'grade')"></field-err></div>
              <fieldset class="field ge-secs"><legend class="label">Secciones</legend>
                <div v-if="!grade(ge)" class="hint">Primero seleccione el grado.</div>
                <div v-else-if="!grade(ge).sections.length" class="hint">Este grado no tiene secciones configuradas.</div>
                <div v-else class="row sec-picks" :aria-invalid="!!eg(i, j, 'sections')">
                  <label v-for="s in grade(ge).sections" :key="s.id" class="check sec" :class="{ on: ge.section_ids.includes(s.id) }">
                    <input type="checkbox" :checked="ge.section_ids.includes(s.id)" :disabled="isTaken(r, s.id)" @change="toggle(ge, s.id)">
                    Sección {{ s.name }}<small v-if="isTaken(r, s.id)" class="muted">(ya asignada)</small></label>
                </div>
                <field-err :msg="eg(i, j, 'sections')"></field-err></fieldset>
              <button v-if="r.grades.length > 1" type="button" class="chip-x ge-x" @click="removeGrade(r, j)" :aria-label="'Quitar el grado ' + (j + 1) + ' de este curso'" title="Quitar este grado">✕</button>
            </div>
            <button type="button" class="btn sm soft" @click="addGrade(r)">＋ Agregar otro grado para {{ r.course ? '“' + r.course.name + '”' : 'este curso' }}</button>
          </div>
        </div>
        <field-err :msg="errs.rows"></field-err>
        <button type="button" class="add-row" @click="add">＋ Agregar otro curso</button>
        <div v-if="summary.length" class="asg-summary" aria-live="polite">
          <b>Se guardarán {{ summary.length }} {{ summary.length === 1 ? 'relación' : 'relaciones' }}:</b>
          <ul><li v-for="s in summary" :key="s">{{ s }}</li></ul>
        </div>
      </div>`,
  };

  // ================================================================ Mis cursos (después del registro)
  CYD.components['assignments-manager'] = {
    data: () => ({ store, list: [], grades: [], courses: [], rows: [CYD.newAssignmentRow()], errs: {}, loading: true, busy: false }),
    computed: {
      year() { return store.years.find(y => y.id === store.yearId); },
      groups() {
        const m = new Map();
        for (const a of this.list) {
          const k = `${a.course_id}|${a.grade_id}`;
          if (!m.has(k)) m.set(k, { key: k, course_id: a.course_id, course_name: a.course_name, grade_id: a.grade_id, grade_name: a.grade_name, items: [] });
          m.get(k).items.push(a);
        }
        return [...m.values()];
      },
      taken() { return new Set(this.list.filter(a => a.active).map(a => `${a.course_id}|${a.section_id}`)); },
    },
    watch: { 'store.yearId'() { this.load(); } },
    async mounted() {
      try {
        const [grades, courses] = await Promise.all([api('GET', '/api/grades'), api('GET', '/api/courses')]);
        this.grades = grades; this.courses = courses.map(c => ({ id: c.id, name: c.name }));
      } catch (e) { toast(e.message, 'error'); }
      this.load();
    },
    methods: {
      async load() {
        if (!store.yearId) { this.loading = false; return; }
        this.loading = true;
        try { this.list = await api('GET', `/api/me/assignments?school_year_id=${store.yearId}`); }
        catch (e) { toast(e.message, 'error'); }
        finally { this.loading = false; }
      },
      found(c) { if (!this.courses.some(x => x.id === c.id)) this.courses.push(c); },
      addSectionsTo(g) {
        // Si el curso ya está en el formulario se le agrega el grado; si no, se crea una fila para ese curso.
        const ge = CYD.newGradeEntry({ grade_id: g.grade_id });
        const same = this.rows.find(r => r.course && r.course.id === g.course_id);
        if (same) { const blank = same.grades.find(x => !x.grade_id); if (blank) Object.assign(blank, { grade_id: g.grade_id }); else same.grades.push(ge); }
        else {
          const row = CYD.newAssignmentRow({ course: { id: g.course_id, name: g.course_name }, grades: [ge] });
          const only = this.rows.length === 1 && !this.rows[0].course && this.rows[0].grades.every(x => !x.grade_id);
          if (only) this.rows.splice(0, 1, row); else this.rows.push(row);
        }
        this.$nextTick(() => { const el = document.getElementById('gr-' + ge.key) || this.$el.querySelector('.asg-row:last-of-type select'); if (el) { el.scrollIntoView({ block: 'center', behavior: 'smooth' }); el.focus(); } });
      },
      async save() {
        this.errs = {}; this.busy = true;
        try {
          const r = await api('POST', '/api/me/assignments/batch', { school_year_id: store.yearId, rows: CYD.rowsPayload(this.rows) });
          if (r.created.length) toast(`Se guardaron ${r.created.length} relación(es) de curso, grado y sección.`, 'ok');
          if (r.existing.length) toast(`Ya tenía registrada(s): ${r.existing.join('; ')}. No se duplicaron.`, 'info', 7000);
          this.rows = [CYD.newAssignmentRow()];
          const courses = await api('GET', '/api/courses');
          this.courses = courses.map(c => ({ id: c.id, name: c.name }));
          await this.load();
        } catch (e) {
          this.errs = CYD.rowErrors(this.rows, fieldErrors(e)); toast(e.message, 'error');
          this.$nextTick(() => { const el = this.$el.querySelector('[aria-invalid="true"]'); if (el) el.focus(); });
        } finally { this.busy = false; }
      },
      async remove(a) {
        const label = `${a.course_name} — ${a.grade_name} — ${a.section_name}`;
        const body = a.plans
          ? [`“${label}” tiene ${a.plans} planificación(es). No se borrará nada: la asignación se desactivará para usos futuros y sus planificaciones se conservan.`]
          : [`Se quitará “${label}” de sus asignaciones del ciclo. El curso seguirá en el catálogo compartido.`];
        if (!await confirm({ title: a.plans ? 'Desactivar asignación' : 'Quitar asignación', body, confirmText: a.plans ? 'Desactivar' : 'Quitar', danger: true })) return;
        try {
          const r = await api('DELETE', `/api/me/assignments/${a.id}`);
          toast(r.deactivated ? 'Asignación desactivada. Sus planificaciones se conservan.' : 'Asignación quitada.', 'ok');
          this.load();
        } catch (e) { toast(e.message, 'error'); }
      },
      async reactivate(a) {
        try { await api('PUT', `/api/me/assignments/${a.id}/active`, { active: true }); toast('Asignación reactivada.', 'ok'); this.load(); }
        catch (e) { toast(e.message, 'error'); }
      },
    },
    template: `
      <div class="stack">
        <div v-if="!store.years.length" class="alert warn"><span class="ic">ℹ</span>La administración aún no ha configurado ciclos escolares.</div>
        <div v-else-if="!loading && !grades.length" class="alert warn"><span class="ic">ℹ</span>La administración aún no ha configurado grados y secciones.</div>
        <template v-else>
          <section class="card">
            <div class="card-head"><h2>Mis cursos y grupos</h2><year-select></year-select></div>
            <loading-box v-if="loading"></loading-box>
            <div v-else-if="!groups.length" class="empty"><span class="ic">🗂</span>Aún no tiene cursos registrados en este ciclo. Agréguelos abajo.</div>
            <ul v-else class="asg-groups">
              <li v-for="g in groups" :key="g.key">
                <div class="asg-g-title"><b>{{ g.course_name }}</b><span class="muted">{{ g.grade_name }}</span></div>
                <div class="row">
                  <span v-for="a in g.items" :key="a.id" class="sec-chip" :class="{ off: !a.active }">
                    Sección {{ a.section_name }}
                    <small v-if="a.plans">· {{ a.plans }} plan(es)</small><small v-if="!a.active">· desactivada</small>
                    <button v-if="a.active" type="button" class="chip-x" @click="remove(a)" :aria-label="'Quitar sección ' + a.section_name + ' de ' + g.course_name + ', ' + g.grade_name" title="Quitar">✕</button>
                    <button v-else type="button" class="btn sm ghost" @click="reactivate(a)">Reactivar</button>
                  </span>
                  <button type="button" class="btn sm soft" @click="addSectionsTo(g)">＋ Secciones</button>
                </div>
              </li>
            </ul>
          </section>
          <section class="card">
            <div class="card-head"><h2>Agregar cursos, grados o secciones</h2><span class="badge info" v-if="year">Ciclo {{ year.name }}</span></div>
            <div class="card-body stack">
              <p class="hint" style="margin:0">Busque el curso o créelo con «＋ Crear nuevo curso». Agregue los grados donde lo imparte y, en cada uno, sus secciones.</p>
              <assignment-rows :rows="rows" :grades="grades" :courses="courses" :errs="errs" :taken="taken" @found="found" @removed="errs = {}"></assignment-rows>
              <div class="row end"><button type="button" class="btn primary" @click="save" :disabled="busy">{{ busy ? 'Guardando…' : 'Guardar asignaciones' }}</button></div>
            </div>
          </section>
        </template>
      </div>`,
  };

  CYD.components['view-assignments'] = {
    data: () => ({ store }),
    template: `
    <div>
      <div class="page-head">
        <div><div class="crumbs"><a href="#/inicio">Inicio</a> › Mis cursos</div><h1>Mis cursos, grados y secciones</h1>
          <p>Cada combinación de curso, grado y sección se planifica por separado.</p></div>
        <a class="btn soft" href="#/inicio">Ir a mis planificaciones →</a>
      </div>
      <assignments-manager></assignments-manager>
    </div>`,
  };
})();
