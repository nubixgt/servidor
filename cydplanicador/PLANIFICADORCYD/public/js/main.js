/* Componente raíz: barra superior, navegación, diálogos y arranque. */
'use strict';
(function () {
  const { store, api, go, toast, initials } = CYD;

  CYD.prompt = opts => new Promise(resolve => { store.modal = { kind: 'prompt', confirmText: 'Aceptar', cancelText: 'Cancelar', value: '', ...opts, resolve }; });

  const App = {
    data: () => ({ store, menu: false, base: CYD.base }),
    computed: {
      view() { return store.route.name ? 'view-' + store.route.name : null; },
      isPublic() { return CYD.PUBLIC.includes(store.route.name) || store.route.name === 'force-password'; },
      links() {
        if (!store.user) return [];
        if (store.user.role === 'admin') return [['/admin', 'Administración'], ['/perfil', 'Mi perfil']];
        return [['/inicio', 'Planificaciones'], ['/asignaciones', 'Mis cursos'], ['/cursos', 'Catálogo de cursos'], ['/perfil', 'Mi perfil']];
      },
    },
    watch: { 'store.route.path'() { this.menu = false; } },
    methods: {
      initials,
      active(p) { return store.route.path === p || (p === '/admin' && store.route.path.startsWith('/admin')) || (p === '/inicio' && store.route.path.startsWith('/plan')); },
      async logout() {
        if (store.leaveGuard && store.leaveGuard() && !await CYD.confirm({ title: 'Cambios sin guardar', body: ['Hay cambios que aún no se guardaron. Si cierra sesión podría perderlos.'], confirmText: 'Cerrar sesión', danger: true })) return;
        store.leaveGuard = null;
        await api('POST', '/api/auth/logout', {});
        store.user = null; go('/ingresar');
      },
      close(v) { const m = store.modal; store.modal = null; m.resolve(m.kind === 'prompt' ? (v ? m.value.trim() : null) : v); },
      async copy(t) { try { await navigator.clipboard.writeText(t); toast('Copiado.', 'ok', 2000); } catch { /* sin portapapeles */ } },
    },
    mounted() {
      document.addEventListener('keydown', e => { if (e.key === 'Escape' && store.modal) this.close(false); });
    },
    updated() {
      if (store.modal) this.$nextTick(() => { const el = document.querySelector('.modal [data-autofocus]'); if (el && document.activeElement && !document.activeElement.closest('.modal')) el.focus(); });
    },
    template: `
    <div>
      <header v-if="store.user && !isPublic" class="topbar">
        <div class="topbar-in" style="position:relative">
          <a class="brand" :href="'#' + CYD_home()">
            <img :src="base + '/api/institution/logo'" alt="" onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'mark',textContent:'CyD'}))">
            <div><b>{{ (store.institution && store.institution.short_name) || 'Colegio CyD' }}</b><span>Planificación docente</span></div>
          </a>
          <button class="btn sm ghost menu-btn" @click="menu = !menu" :aria-expanded="menu" aria-controls="main-nav">☰ Menú</button>
          <nav id="main-nav" class="nav" :class="{ open: menu }" aria-label="Principal">
            <a v-for="l in links" :key="l[0]" :href="'#' + l[0]" :class="{ active: active(l[0]) }" :aria-current="active(l[0]) ? 'page' : null">{{ l[1] }}</a>
          </nav>
          <div class="top-right">
            <div class="user-chip"><span class="avatar" aria-hidden="true">{{ initials(store.user.full_name) }}</span><span class="name">{{ store.user.full_name }}</span></div>
            <button class="btn sm" @click="logout">Cerrar sesión</button>
          </div>
        </div>
      </header>
      <main :style="isPublic ? 'padding:0;max-width:none' : ''">
        <component v-if="view && store.ready" :is="view" :key="store.route.name + (store.route.params.id || '')"></component>
        <loading-box v-else-if="!store.ready"></loading-box>
      </main>

      <div v-if="store.modal" class="overlay" @click.self="close(false)">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="mdl-t">
          <div class="modal-head"><h2 id="mdl-t">{{ store.modal.title }}</h2></div>
          <div class="modal-body">
            <p v-for="(l, i) in store.modal.body || []" :key="i">{{ l }}</p>
            <div v-if="store.modal.code" class="row" style="margin:8px 0"><code style="font-size:1.25rem;font-weight:700;background:var(--green-50);padding:8px 14px;border-radius:10px;color:var(--green-900);letter-spacing:.04em">{{ store.modal.code }}</code>
              <button class="btn sm" @click="copy(store.modal.code)">Copiar</button></div>
            <div v-if="store.modal.kind === 'prompt'" class="field"><label for="mdl-in">{{ store.modal.label }}</label>
              <textarea id="mdl-in" v-model="store.modal.value" rows="3" data-autofocus></textarea></div>
          </div>
          <div class="modal-foot">
            <button v-if="store.modal.kind !== 'alert'" class="btn" @click="close(false)" :data-autofocus="store.modal.kind === 'confirm' ? '' : null">{{ store.modal.cancelText }}</button>
            <button class="btn" :class="store.modal.danger ? 'danger solid' : 'primary'" @click="close(true)" :disabled="store.modal.kind === 'prompt' && !store.modal.value.trim()" :data-autofocus="store.modal.kind === 'alert' ? '' : null">{{ store.modal.confirmText }}</button>
          </div>
        </div>
      </div>
      <div class="toasts" aria-live="polite">
        <div v-for="t in store.toasts" :key="t.id" class="toast" :class="t.kind" role="status"><span>{{ t.kind === 'error' ? '⚠' : t.kind === 'ok' ? '✓' : 'ℹ' }}</span><span>{{ t.text }}</span></div>
      </div>
    </div>`,
  };

  const app = Vue.createApp(App);
  app.config.globalProperties.base = CYD.base; // subruta pública, usada en rutas de imágenes
  app.config.globalProperties.CYD_home = () => CYD.home();
  app.config.globalProperties.CYD = CYD;
  for (const [name, c] of Object.entries(CYD.components)) app.component(name, c);
  app.mount('#app');

  (async () => {
    try {
      await CYD.loadMe();
      if (store.user && !store.user.must_change_password) await CYD.loadYears();
    } catch (e) { toast(e.message, 'error'); }
    store.ready = true;
    await CYD.route();
  })();
})();
