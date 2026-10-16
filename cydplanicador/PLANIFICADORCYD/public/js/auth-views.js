/* Pantallas de acceso: ingreso, registro, recuperación, restablecimiento y cambio obligatorio. */
'use strict';
(function () {
  const { store, api, go, toast, fieldErrors, loadMe } = CYD;

  const brand = `
    <div class="auth-brand">
      <img :src="base + '/api/institution/logo'" alt="Logo del colegio" onerror="this.style.display='none'">
      <div><b>{{ (store.institution && store.institution.full_name) || 'Colegio CyD' }}</b><span>Sistema de Planificación Docente</span></div>
    </div>`;

  // Íconos de trazo (24×24) usados en las pantallas de acceso
  const I = {
    user: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    lock: '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    eyeOff: '<path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17.7 17.7 0 0 1-2.4 3.4M6.6 6.6A17.4 17.4 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2M2 2l20 20"/>',
    arrow: '<path d="M5 12h14M13 6l6 6-6 6"/>',
    userPlus: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
    layers: '<path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5"/>',
    cloud: '<path d="M17.5 19H8a6 6 0 1 1 5.7-7.9A4.5 4.5 0 1 1 17.5 19z"/><path d="m9.5 14 2 2 3.5-3.5"/>',
    file: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
    alert: '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
  };
  const icon = (name, cls = 'ic-svg') => `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${I[name]}</svg>`;

  // Encabezado institucional (escudo + nombre) que comparten las pantallas de acceso
  const heroBrand = `
      <div class="hero-brand">
        <span class="hero-logo"><img :src="base + '/api/institution/logo'" alt="Escudo del colegio" onerror="this.parentNode.textContent='CyD'"></span>
        <div><small>Colegio Particular Mixto</small><b>Ciencia y Desarrollo</b><span>Sistema de Planificación Docente</span></div>
      </div>`;

  // Panel verde con borde curvo y líneas decorativas (dibujado en SVG para que escale con la pantalla)
  const heroShape = `
      <svg class="hero-shape" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
        <defs><linearGradient id="hero-g" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stop-color="#00301F"/><stop offset=".55" stop-color="#004A33"/><stop offset="1" stop-color="#00703F"/></linearGradient></defs>
        <path d="M0,0 H88 C74,22 60,40 64,58 C68,78 84,90 97,100 H0 Z" fill="url(#hero-g)"/>
        <path class="hero-line" d="M92,0 C79,22 66,40 69,58 C72,77 88,90 100,98" vector-effect="non-scaling-stroke"/>
        <path class="hero-line faint" d="M97,0 C85,24 72,42 74.5,60 C77,77 92,88 100,92" vector-effect="non-scaling-stroke"/>
      </svg>`;

  // Panel institucional del registro (columna verde a la izquierda; arriba en pantallas angostas)
  const hero = (inner) => `
    <aside class="auth-hero">
      ${heroBrand}
      ${inner}
      <p class="hero-foot hide-sm"><i class="mint-dash"></i>“Educar es Transformar Vidas”</p>
    </aside>`;

  CYD.components['view-login'] = {
    data: () => ({ store, username: '', password: '', remember: false, error: '', busy: false, show: false }),
    methods: {
      async submit() {
        this.error = '';
        if (!this.username || !this.password) { this.error = 'Escriba su usuario y su contraseña.'; return; }
        this.busy = true;
        try {
          await api('POST', '/api/auth/login', { username: this.username, password: this.password, remember: this.remember });
          await loadMe(); await CYD.loadYears();
          go(store.user.must_change_password ? '/cambiar-contrasena' : CYD.home());
        } catch (e) { this.error = e.message; }
        finally { this.busy = false; }
      },
    },
    template: `
    <div class="login-page">
      <div class="login-bg" aria-hidden="true"></div>
      <aside class="login-hero">
        ${heroShape}
        <div class="login-hero-in">
          ${heroBrand}
          <div class="hero-intro">
            <i class="mint-dash"></i>
            <h2>Planifique su <em>bimestre</em> en un solo lugar</h2>
            <ul class="hero-points">
              <li><span>${icon('layers')}</span><div><b>Sus cursos y grupos</b><small>Cada curso, grado y sección con su propia planificación.</small></div></li>
              <li><span>${icon('cloud')}</span><div><b>Guardado automático</b><small>Su trabajo se guarda mientras escribe.</small></div></li>
              <li><span>${icon('file')}</span><div><b>Hoja de actividades en PDF</b><small>Con el formato institucional, lista para imprimir.</small></div></li>
            </ul>
          </div>
          <p class="hero-foot"><i class="mint-dash"></i>“Educar es Transformar Vidas”</p>
        </div>
      </aside>
      <section class="login-main">
        <div class="login-card">
          <h1>Iniciar <span>sesión</span></h1>
          <i class="title-bar"></i>
          <p class="login-sub">Ingrese con el usuario y la contraseña de su cuenta en este sistema.</p>
          <form @submit.prevent="submit" novalidate>
            <label class="in-wrap" :class="{ bad: error && !username }">
              <span class="in-ic">${icon('user')}</span>
              <input id="lg-u" type="text" v-model.trim="username" placeholder="Usuario" aria-label="Usuario" autocomplete="username" autocapitalize="none" spellcheck="false" autofocus>
            </label>
            <label class="in-wrap" :class="{ bad: error && !password }">
              <span class="in-ic">${icon('lock')}</span>
              <input id="lg-p" :type="show ? 'text' : 'password'" v-model="password" placeholder="Contraseña" aria-label="Contraseña" autocomplete="current-password">
              <button type="button" class="in-eye" @click="show = !show" :aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'" :aria-pressed="show" :title="show ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                <template v-if="show">${icon('eyeOff')}</template><template v-else>${icon('eye')}</template></button>
            </label>
            <div class="login-opts">
              <label class="remember"><input type="checkbox" v-model="remember"> Mantener sesión iniciada</label>
              <a href="#/recuperar">¿Olvidó su contraseña?</a>
            </div>
            <div v-if="error" class="login-err" role="alert">${icon('alert')}<span>{{ error }}</span></div>
            <button class="login-btn" :disabled="busy">
              <span>{{ busy ? 'Ingresando…' : 'Ingresar' }}</span><i class="login-btn-arrow">${icon('arrow')}</i></button>
          </form>
          <div class="login-or"><span>o</span></div>
          <a class="login-alt" href="#/registro"><i>${icon('userPlus')}</i>Crear mi cuenta de docente</a>
          <p class="login-foot">Cuenta independiente de SICA</p>
        </div>
      </section>
    </div>`,
  };

  // ================================================================ Buscador del docente en el listado institucional
  CYD.components['teacher-picker'] = {
    props: { teachers: Array, invalid: String },
    emits: ['select', 'manual'],
    data: () => ({ text: '', open: false, active: -1 }),
    computed: {
      options() {
        const words = CYD.norm(this.text).split(' ').filter(Boolean);
        if (!words.length) return this.teachers.slice(0, 60);
        return this.teachers.filter(t => { const k = CYD.norm(t.full_name); return words.every(w => k.includes(w)); }).slice(0, 60);
      },
      selectable() { return this.options.filter(t => !t.taken); },
    },
    methods: {
      initials: CYD.initials,
      pick(t) { if (t.taken) return; this.$emit('select', t); this.open = false; this.text = ''; },
      blur() { setTimeout(() => { this.open = false; }, 150); },
      manual() { const n = this.text.trim(); this.open = false; this.$emit('manual', n); this.text = ''; },
      onKey(e) {
        const n = this.options.length + 1;
        if (e.key === 'ArrowDown') { e.preventDefault(); this.open = true; this.active = (this.active + 1) % n; }
        else if (e.key === 'ArrowUp') { e.preventDefault(); this.open = true; this.active = (this.active - 1 + n) % n; }
        else if (e.key === 'Enter') {
          e.preventDefault();
          if (this.active >= 0 && this.active < this.options.length) this.pick(this.options[this.active]);
          else if (this.active === this.options.length) this.manual();
          else if (this.selectable.length === 1) this.pick(this.selectable[0]);
        } else if (e.key === 'Escape') this.open = false;
      },
      mark(name) {
        // Resalta las palabras buscadas sin interpretar HTML
        const words = CYD.norm(this.text).split(' ').filter(Boolean);
        if (!words.length) return [{ t: name, on: false }];
        const out = []; let i = 0;
        const n = CYD.norm(name);
        const hits = new Array(name.length).fill(false);
        for (const w of words) { let p = n.indexOf(w); while (p >= 0) { for (let j = p; j < p + w.length; j++) hits[j] = true; p = n.indexOf(w, p + 1); } }
        while (i < name.length) { const on = hits[i]; let j = i; while (j < name.length && hits[j] === on) j++; out.push({ t: name.slice(i, j), on }); i = j; }
        return out;
      },
    },
    template: `
      <div class="combo">
        <div class="search-in">
          <span class="search-ic" aria-hidden="true">🔍</span>
          <input id="rg-n" type="text" role="combobox" :aria-expanded="open" aria-controls="rg-n-list" aria-autocomplete="list"
            v-model="text" @input="open = true; active = -1" @focus="open = true" @keydown="onKey" @blur="blur"
            placeholder="Escriba su nombre o apellido…" autocomplete="off" :aria-invalid="!!invalid">
        </div>
        <ul v-if="open" id="rg-n-list" class="combo-list people" role="listbox">
          <li v-for="(t, i) in options" :key="t.id" role="option" :aria-selected="i === active" :aria-disabled="t.taken" :class="{ taken: t.taken }" @mousedown.prevent="pick(t)">
            <span class="avatar sm" aria-hidden="true">{{ initials(t.full_name) }}</span>
            <span class="who"><template v-for="(p, j) in mark(t.full_name)" :key="j"><mark v-if="p.on">{{ p.t }}</mark><template v-else>{{ p.t }}</template></template></span>
            <small v-if="t.taken" class="badge none">Ya tiene cuenta</small>
          </li>
          <li v-if="!options.length" class="none" role="presentation">No encontramos “{{ text.trim() }}” en el listado.</li>
          <li class="create" role="option" :aria-selected="active === options.length" @mousedown.prevent="manual">✎ No aparezco en el listado: escribir mi nombre</li>
        </ul>
      </div>`,
  };

  // ================================================================ Registro: docente + credenciales + cursos, en un solo formulario
  CYD.components['view-register'] = {
    data: () => ({ store, f: { full_name: '', username: '', password: '', password_confirm: '' }, teacher: null, manual: false,
      rows: [CYD.newAssignmentRow()], loadInfo: '', opts: null, loadError: '', errs: {}, error: '', busy: false, show: false }),
    computed: {
      ready() { return this.opts && this.opts.year && this.opts.grades.length; },
      rowsDone() { return this.rows.some(r => r.course && r.grades.some(g => g.grade_id && g.section_ids.length)); },
      steps() {
        return [
          { n: 1, t: 'Busque su nombre', done: this.f.full_name.trim().length >= 5 },
          { n: 2, t: 'Cree su acceso', done: !!(this.f.username && this.f.password && this.f.password === this.f.password_confirm) },
          { n: 3, t: 'Revise sus cursos', done: this.rowsDone },
        ];
      },
      pwMismatch() { return this.f.password_confirm && this.f.password !== this.f.password_confirm; },
    },
    async mounted() {
      try { this.opts = await api('GET', '/api/auth/register-options'); }
      catch (e) { this.loadError = e.message; }
    },
    methods: {
      initials: CYD.initials,
      found(c) { if (!this.opts.courses.some(x => x.id === c.id)) this.opts.courses.push(c); },
      blankRows() { return this.rows.every(r => !r.course && r.grades.every(g => !g.grade_id && !g.section_ids.length)); },
      async pickTeacher(t) {
        this.teacher = t; this.manual = false; this.f.full_name = t.full_name; this.loadInfo = ''; delete this.errs.full_name;
        try {
          const r = await api('GET', `/api/auth/teachers/${t.id}/load`);
          if (!r.rows.length) { this.loadInfo = 'No tiene cursos registrados en el listado del colegio. Agréguelos en el paso 3.'; return; }
          if (!this.blankRows() && !await CYD.confirm({ title: 'Cargar sus cursos del listado', body: [`Encontramos ${r.rows.length} curso(s) asignados a ${t.full_name}. ¿Reemplazar las filas que ya llenó por estos cursos?`], confirmText: 'Reemplazar', cancelText: 'Conservar mis filas' })) return;
          this.rows = CYD.rowsFromLoad(r.rows);
          const n = r.rows.reduce((a, x) => a + x.section_ids.length, 0);
          this.loadInfo = `Precargamos ${this.rows.length} curso(s) en ${n} grupo(s) según el listado del colegio. Revíselos en el paso 3: puede quitar, corregir o agregar.`;
        } catch (e) { toast(e.message, 'error'); }
      },
      useManual(name) { this.teacher = null; this.manual = true; this.f.full_name = name; this.loadInfo = ''; this.$nextTick(() => { const el = document.getElementById('rg-name'); if (el) el.focus(); }); },
      reset() { this.teacher = null; this.manual = false; this.f.full_name = ''; this.loadInfo = ''; this.$nextTick(() => { const el = document.getElementById('rg-n'); if (el) el.focus(); }); },
      async submit() {
        this.error = ''; this.errs = {}; this.busy = true;
        try {
          const r = await api('POST', '/api/auth/register', { ...this.f, teacher_id: this.teacher ? this.teacher.id : null, assignments: CYD.rowsPayload(this.rows) });
          await loadMe(); await CYD.loadYears();
          if (r.school_year) CYD.setYear(r.school_year.id);
          const n = r.assignments.length;
          await CYD.alert({ title: '¡Registro completado!', body: [
            `Su cuenta “${this.f.username}” fue creada y se guardaron ${n} ${n === 1 ? 'grupo' : 'grupos'} (curso, grado y sección) para el ciclo ${r.school_year.name}:`,
            ...r.assignments.map(a => '• ' + a),
            'Ahora elija uno de sus grupos y un bimestre para comenzar a planificar.'], confirmText: 'Ir a mis planificaciones' });
          go('/inicio');
        } catch (e) {
          this.errs = CYD.rowErrors(this.rows, fieldErrors(e)); this.error = e.message;
          this.$nextTick(() => {
            const el = this.$el.querySelector('[aria-invalid="true"]') || this.$el.querySelector('.err-text');
            if (el) { el.scrollIntoView({ block: 'center', behavior: 'smooth' }); if (el.focus) el.focus({ preventScroll: true }); }
          });
        } finally { this.busy = false; }
      },
    },
    template: `
    <div class="auth-shell reg">
      ${hero(`
      <div class="hero-copy">
        <h2 class="hide-sm">Registro de docente</h2>
        <ol class="hero-steps">
          <li v-for="s in steps" :key="s.n" :class="{ done: s.done }"><span class="n">{{ s.done ? '✓' : s.n }}</span><span>{{ s.t }}</span></li>
        </ol>
        <p class="hide-sm" style="opacity:.85;font-size:.9rem;margin:0">Todo se guarda al final, con un solo botón. Si algo falta, le indicaremos qué corregir sin perder lo que escribió.</p>
      </div>`)}
      <section class="auth-main">
        <div class="reg-form">
          <div class="reg-title"><h1>Crear mi cuenta</h1><a href="#/ingresar">Ya tengo cuenta: iniciar sesión</a></div>
          <loading-box v-if="!opts && !loadError"></loading-box>
          <div v-else-if="loadError" class="alert error" role="alert"><span class="ic">⚠</span><span>{{ loadError }}</span></div>
          <div v-else-if="!ready" class="alert warn"><span class="ic">ℹ</span><div>
            La administración del colegio aún no termina la configuración inicial ({{ !opts.year ? 'ciclo escolar' : 'grados y secciones' }}).
            Podrá registrarse cuando esté lista.</div></div>
          <form v-else @submit.prevent="submit" novalidate class="stack">
            <section class="card step-card">
              <div class="step-head"><span class="step-n" :class="{ done: steps[0].done }">{{ steps[0].done ? '✓' : 1 }}</span>
                <div><h2>Datos del docente</h2><p>Busque su nombre en el listado de docentes del colegio.</p></div></div>
              <div class="step-body stack">
                <template v-if="!teacher && !manual">
                  <div class="field"><label for="rg-n">Nombre completo del docente</label>
                    <teacher-picker :teachers="opts.teachers" @select="pickTeacher" @manual="useManual" :invalid="errs.full_name"></teacher-picker>
                    <span class="hint">Escriba parte de su nombre o apellido; por ejemplo, “López” o “Ana López”.</span>
                    <field-err :msg="errs.full_name"></field-err></div>
                </template>
                <template v-else>
                  <div class="picked">
                    <span class="avatar lg" aria-hidden="true">{{ initials(f.full_name || '?') }}</span>
                    <div class="field" style="flex:1"><label for="rg-name">Nombre completo del docente</label>
                      <input id="rg-name" type="text" v-model="f.full_name" autocomplete="name" :aria-invalid="!!errs.full_name">
                      <span class="hint">{{ teacher ? 'Seleccionado del listado del colegio. Puede corregir cómo se escribe; así aparecerá en sus documentos.' : 'Escriba su nombre completo como debe aparecer en sus planificaciones.' }}</span>
                      <field-err :msg="errs.full_name"></field-err></div>
                    <button type="button" class="btn sm ghost" @click="reset">Cambiar</button>
                  </div>
                  <div v-if="loadInfo" class="alert ok"><span class="ic">✓</span><span>{{ loadInfo }}</span></div>
                </template>
              </div>
            </section>

            <section class="card step-card">
              <div class="step-head"><span class="step-n" :class="{ done: steps[1].done }">{{ steps[1].done ? '✓' : 2 }}</span>
                <div><h2>Credenciales de acceso</h2><p>Escriba su usuario de SICA y la contraseña que desee. Se crea una cuenta en este sistema; no se conecta con SICA.</p></div></div>
              <div class="step-body stack">
                <div class="field"><label for="rg-u">Usuario</label>
                  <input id="rg-u" type="text" v-model.trim="f.username" autocomplete="username" autocapitalize="none" spellcheck="false" :aria-invalid="!!errs.username" placeholder="El mismo usuario que usa en SICA">
                  <field-err :msg="errs.username"></field-err></div>
                <div class="grid g2">
                  <div class="field"><label for="rg-p">Contraseña</label>
                    <div class="pw-wrap"><input id="rg-p" :type="show ? 'text' : 'password'" v-model="f.password" autocomplete="new-password" :aria-invalid="!!errs.password">
                      <button type="button" class="pw-eye" @click="show = !show" :aria-label="show ? 'Ocultar contraseñas' : 'Mostrar contraseñas'" :aria-pressed="show">{{ show ? 'Ocultar' : 'Ver' }}</button></div>
                    <span class="hint">La que usted prefiera.</span>
                    <field-err :msg="errs.password"></field-err></div>
                  <div class="field"><label for="rg-c">Confirmar contraseña</label>
                    <input id="rg-c" :type="show ? 'text' : 'password'" v-model="f.password_confirm" autocomplete="new-password" :aria-invalid="!!errs.password_confirm || !!pwMismatch">
                    <span v-if="f.password_confirm && !pwMismatch" class="hint ok-text">✓ Las contraseñas coinciden</span>
                    <field-err :msg="errs.password_confirm || (pwMismatch ? 'Aún no coincide con la contraseña.' : '')"></field-err></div>
                </div>
              </div>
            </section>

            <section class="card step-card">
              <div class="step-head"><span class="step-n" :class="{ done: steps[2].done }">{{ steps[2].done ? '✓' : 3 }}</span>
                <div style="flex:1"><h2>Cursos, grados y secciones que imparto</h2><p>Por cada curso: elija los grados donde lo imparte y, en cada grado, sus secciones. Si su curso no existe, créelo con «＋ Crear nuevo curso».</p></div>
                <span class="badge info">Ciclo {{ opts.year.name }}</span></div>
              <div class="step-body">
                <assignment-rows :rows="rows" :grades="opts.grades" :courses="opts.courses" :errs="errs" @found="found" @removed="errs = {}"></assignment-rows>
              </div>
            </section>

            <div v-if="error" class="alert error" role="alert"><span class="ic">⚠</span><span>{{ error }}</span></div>
            <div class="reg-submit">
              <button class="btn primary lg" :disabled="busy">{{ busy ? 'Guardando…' : 'Crear mi cuenta y guardar mis cursos' }}</button>
            </div>
          </form>
        </div>
      </section>
    </div>`,
  };

  CYD.components['view-forgot'] = {
    data: () => ({ store, username: '', result: null, error: '', busy: false }),
    methods: {
      async submit() {
        this.error = ''; this.busy = true;
        try { this.result = await api('POST', '/api/auth/forgot', { username: this.username }); }
        catch (e) { this.error = e.message; }
        finally { this.busy = false; }
      },
    },
    template: `
    <div class="auth-wrap"><div class="card auth-card">
      ${brand}
      <h1 style="margin-bottom:6px">Recuperar acceso</h1>
      <template v-if="!store.mailEnabled">
        <div class="alert warn"><span class="ic">ℹ</span><div>
          La recuperación por correo <b>no está configurada</b> en este servidor.
          Solicite a la administración del colegio que restablezca su acceso: recibirá una contraseña temporal que deberá cambiar al ingresar.
          Por seguridad, nadie puede ver su contraseña anterior.</div></div>
      </template>
      <template v-else>
        <p class="muted">Si su cuenta tiene un correo de recuperación verificado, le enviaremos un enlace para crear una nueva contraseña.</p>
        <form v-if="!result" @submit.prevent="submit" class="stack">
          <div class="field"><label for="fg-u">Nombre de usuario</label><input id="fg-u" type="text" v-model.trim="username" autocapitalize="none" required></div>
          <div v-if="error" class="alert error" role="alert"><span class="ic">⚠</span>{{ error }}</div>
          <button class="btn primary" :disabled="busy || !username">{{ busy ? 'Enviando…' : 'Solicitar enlace' }}</button>
        </form>
        <div v-else class="alert ok"><span class="ic">✓</span><span>{{ result.message }}</span></div>
        <p class="muted" style="margin-top:12px">Si no registró un correo verificado, solicite el restablecimiento a la administración del colegio.</p>
      </template>
      <div class="divider"></div><a href="#/ingresar">Volver a iniciar sesión</a>
    </div></div>`,
  };

  CYD.components['view-reset'] = {
    data: () => ({ store, f: { password: '', password_confirm: '' }, errs: {}, error: '', done: false, busy: false }),
    methods: {
      async submit() {
        this.error = ''; this.errs = {}; this.busy = true;
        try { await api('POST', '/api/auth/reset', { token: store.route.query.token, ...this.f }); this.done = true; }
        catch (e) { this.errs = fieldErrors(e); this.error = e.message; }
        finally { this.busy = false; }
      },
    },
    template: `
    <div class="auth-wrap"><div class="card auth-card">
      ${brand}
      <h1 style="margin-bottom:6px">Nueva contraseña</h1>
      <div v-if="done" class="alert ok"><span class="ic">✓</span><span>Contraseña actualizada. <a href="#/ingresar">Inicie sesión</a> con su nueva contraseña.</span></div>
      <form v-else @submit.prevent="submit" class="stack">
        <div class="field"><label for="rs-p">Nueva contraseña</label><input id="rs-p" type="password" v-model="f.password" autocomplete="new-password"><field-err :msg="errs.password"></field-err></div>
        <div class="field"><label for="rs-c">Confirmar contraseña</label><input id="rs-c" type="password" v-model="f.password_confirm" autocomplete="new-password"><field-err :msg="errs.password_confirm"></field-err></div>
        <div v-if="error && !Object.keys(errs).length" class="alert error" role="alert"><span class="ic">⚠</span>{{ error }}</div>
        <button class="btn primary" :disabled="busy">Guardar contraseña</button>
      </form>
    </div></div>`,
  };

  CYD.components['password-form'] = {
    props: { forced: Boolean },
    emits: ['done'],
    data: () => ({ f: { current_password: '', password: '', password_confirm: '' }, errs: {}, error: '', busy: false }),
    methods: {
      async submit() {
        this.error = ''; this.errs = {}; this.busy = true;
        try {
          await api('PUT', '/api/me/password', this.f);
          this.f = { current_password: '', password: '', password_confirm: '' };
          toast('Contraseña actualizada.', 'ok');
          this.$emit('done');
        } catch (e) { this.errs = fieldErrors(e); this.error = e.message; }
        finally { this.busy = false; }
      },
    },
    template: `
      <form @submit.prevent="submit" class="stack" novalidate>
        <div class="field"><label for="pw-c">{{ forced ? 'Contraseña temporal' : 'Contraseña actual' }}</label>
          <input id="pw-c" type="password" v-model="f.current_password" autocomplete="current-password" :aria-invalid="!!errs.current_password"><field-err :msg="errs.current_password"></field-err></div>
        <div class="grid g2">
          <div class="field"><label for="pw-n">Nueva contraseña</label>
            <input id="pw-n" type="password" v-model="f.password" autocomplete="new-password" :aria-invalid="!!errs.password">
            <span class="hint">Puede usar la contraseña que prefiera.</span>
            <field-err :msg="errs.password"></field-err></div>
          <div class="field"><label for="pw-r">Confirmar nueva contraseña</label>
            <input id="pw-r" type="password" v-model="f.password_confirm" autocomplete="new-password" :aria-invalid="!!errs.password_confirm"><field-err :msg="errs.password_confirm"></field-err></div>
        </div>
        <div v-if="error && !Object.keys(errs).length" class="alert error" role="alert"><span class="ic">⚠</span>{{ error }}</div>
        <div class="row end"><button class="btn primary" :disabled="busy">Cambiar contraseña</button></div>
      </form>`,
  };

  CYD.components['view-force-password'] = {
    data: () => ({ store }),
    methods: { async done() { await loadMe(); await CYD.loadYears(); go(CYD.home()); } },
    template: `
    <div class="auth-wrap"><div class="card auth-card wide">
      ${brand}
      <h1 style="margin-bottom:6px">Cambie su contraseña temporal</h1>
      <p class="muted">La administración restableció su acceso. Antes de continuar, escriba la contraseña temporal recibida y elija una nueva.</p>
      <password-form forced @done="done"></password-form>
    </div></div>`,
  };
})();
