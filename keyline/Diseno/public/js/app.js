const App = (() => {
  let currentUser = null;
  let currentView = null;

  const MENUS = {
    tecnico: [
      { key: 'nueva-parcela', label: 'Registrar parcela', icon: '📝' },
      { key: 'mis-parcelas', label: 'Mis parcelas', icon: '🗂️' },
      { key: 'variables', label: 'Variables técnicas', icon: '💧' },
      { key: 'configuracion', label: 'Configuración', icon: '⚙️' },
    ],
    supervisor: [
      { key: 'dashboard', label: 'Panel ejecutivo', icon: '📊' },
      { key: 'parcelas', label: 'Parcelas de mi región', icon: '🗺️' },
      { key: 'nueva-parcela', label: 'Registrar parcela', icon: '📝' },
      { key: 'bioindicadores', label: 'Bioindicadores', icon: '🌱' },
      { key: 'reportes', label: 'Reportes y análisis', icon: '📈' },
      { key: 'variables', label: 'Variables técnicas', icon: '💧' },
      { key: 'equipo', label: 'Equipo técnico', icon: '👥' },
      { key: 'configuracion', label: 'Configuración', icon: '⚙️' },
    ],
    administrador: [
      { key: 'dashboard', label: 'Panel ejecutivo', icon: '📊' },
      { key: 'parcelas', label: 'Todas las parcelas', icon: '🗺️' },
      { key: 'nueva-parcela', label: 'Registrar parcela', icon: '📝' },
      { key: 'bioindicadores', label: 'Bioindicadores', icon: '🌱' },
      { key: 'reportes', label: 'Reportes y análisis', icon: '📈' },
      { key: 'variables', label: 'Variables técnicas', icon: '💧' },
      { key: 'equipo', label: 'Usuarios y equipo', icon: '👥' },
      { key: 'configuracion', label: 'Configuración', icon: '⚙️' },
    ],
  };

  const TITLES = {
    dashboard: ['Panel ejecutivo', 'Vista general del avance nacional de parcelas Keyline'],
    parcelas: ['Base de parcelas', 'Consulta, filtra y valida la información técnica cargada'],
    'nueva-parcela': ['Registrar parcela', 'Captura los datos técnicos de campo'],
    'editar-parcela': ['Editar parcela', 'Actualiza los datos técnicos de campo'],
    'mis-parcelas': ['Mis parcelas', 'Parcelas que has registrado'],
    equipo: ['Equipo del proyecto', 'Gestión de usuarios y roles'],
    bioindicadores: ['Bioindicadores', 'Salud biológica del suelo reportada en campo'],
    reportes: ['Reportes y análisis', 'Centro de exportación e indicadores del proyecto'],
    variables: ['Variables técnicas', 'Catálogo de variables capturadas por parcela'],
    configuracion: ['Configuración', 'Preferencias de tu cuenta'],
  };

  async function boot() {
    try {
      window.KL.constants = await Api.constantes();
    } catch (e) { console.error('No se pudieron cargar constantes', e); }

    try {
      const { user } = await Api.me();
      currentUser = user;
      showApp();
    } catch (e) {
      showLogin();
    }
    bindLogin();
    bindChrome();
  }

  function bindLogin() {
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = document.getElementById('loginEmail').value.trim();
      const password = document.getElementById('loginPassword').value;
      const errBox = document.getElementById('loginError');
      errBox.hidden = true;
      try {
        const { user } = await Api.login(email, password);
        currentUser = user;
        showApp();
      } catch (err) {
        errBox.textContent = err.message || 'No se pudo iniciar sesión.';
        errBox.hidden = false;
      }
    });
    document.getElementById('logoutBtn').addEventListener('click', async () => {
      await Api.logout();
      currentUser = null;
      location.reload();
    });
  }

  function bindChrome() {
    document.getElementById('sidebarCollapseBtn').addEventListener('click', () => {
      const shell = document.getElementById('viewApp');
      const collapsed = shell.classList.toggle('sidebar-collapsed');
      document.getElementById('sidebarCollapseBtn').textContent = collapsed ? '»' : '«';
    });

    let searchTimer;
    document.getElementById('globalSearch').addEventListener('keydown', (e) => {
      if (e.key !== 'Enter') return;
      const q = e.target.value.trim();
      if (!q) return;
      const target = currentUser.role === 'tecnico' ? 'mis-parcelas' : 'parcelas';
      navigate(target, { presetSearch: q });
    });

    document.getElementById('notifBtn').addEventListener('click', async () => {
      if (currentUser.role === 'tecnico') {
        UI.toast('No tienes notificaciones nuevas.', 'info');
        return;
      }
      try {
        const { actividad } = await Api.actividad();
        showNotifications(actividad);
      } catch (e) {
        UI.toast('No se pudo cargar la actividad reciente.', 'error');
      }
    });
  }

  function showNotifications(actividad) {
    const items = actividad.slice(0, 12);
    UI.openModal(`
      <div class="modal-head"><h3>Actividad reciente</h3><button class="modal-close" id="notifX">✕</button></div>
      <div class="insight-list">
        ${items.length ? items.map((a) => `
          <div class="insight-card trend">
            <span class="ic-icon">🔔</span>
            <div><p class="ic-tag">${UI.escapeHtml(a.usuarioNombre || 'Sistema')} · ${UI.fmtFechaHora(a.fecha)}</p><p>${UI.escapeHtml(a.detalle || a.tipo)}</p></div>
          </div>
        `).join('') : '<p class="hint">Sin actividad registrada todavía.</p>'}
      </div>
    `, { wide: true });
    document.getElementById('notifX').onclick = UI.closeModal;
  }

  function showLogin() {
    document.getElementById('viewLogin').hidden = false;
    document.getElementById('viewApp').hidden = true;
  }

  function showApp() {
    document.getElementById('viewLogin').hidden = true;
    document.getElementById('viewApp').hidden = false;
    document.getElementById('userName').textContent = currentUser.nombre;
    document.getElementById('userRole').textContent = roleLabel(currentUser.role);
    document.getElementById('userAvatar').textContent = UI.initials(currentUser.nombre);
    renderNav();
    if (currentUser.role !== 'tecnico') checkNotifDot();
    const home = currentUser.role === 'tecnico' ? 'mis-parcelas' : 'dashboard';
    navigate(home);
  }

  async function checkNotifDot() {
    try {
      const { actividad } = await Api.actividad();
      document.getElementById('notifDot').hidden = !actividad.length;
    } catch (e) { /* silencioso */ }
  }

  function roleLabel(r) { return { tecnico: 'Técnico de campo', supervisor: 'Supervisor regional', administrador: 'Administrador' }[r] || r; }

  function renderNav() {
    const items = MENUS[currentUser.role] || [];
    const nav = document.getElementById('navMenu');
    nav.innerHTML = items.map((it) => `<div class="nav-item" data-nav="${it.key}"><span class="ic">${it.icon}</span><span class="label">${it.label}</span></div>`).join('');
    nav.querySelectorAll('[data-nav]').forEach((el) => el.addEventListener('click', () => navigate(el.dataset.nav)));
  }

  function setActiveNav(key) {
    document.querySelectorAll('.nav-item').forEach((el) => el.classList.toggle('active', el.dataset.nav === key || (key === 'editar-parcela' && el.dataset.nav === 'mis-parcelas')));
  }

  async function navigate(view, opts = {}) {
    currentView = view;
    setActiveNav(view);
    const [title, subtitle] = TITLES[view] || [view, ''];
    document.getElementById('pageTitle').textContent = title;
    document.getElementById('pageSubtitle').textContent = subtitle;
    document.getElementById('topbarActions').innerHTML = view === 'parcelas' || view === 'mis-parcelas'
      ? `<button class="btn btn-primary" id="topbarNewParcela">+ Nueva parcela</button>` : '';
    const btn = document.getElementById('topbarNewParcela');
    if (btn) btn.addEventListener('click', () => navigate('nueva-parcela'));

    const container = document.getElementById('viewContainer');
    container.innerHTML = '';
    window.scrollTo({ top: 0, behavior: 'smooth' });

    try {
      if (view === 'dashboard') await ViewDashboard.render(container);
      else if (view === 'parcelas') await ViewParcelas.render(container, opts);
      else if (view === 'mis-parcelas') await ViewTecnico.renderMisParcelas(container);
      else if (view === 'nueva-parcela') await ViewTecnico.render(container, {});
      else if (view === 'editar-parcela') await ViewTecnico.render(container, { editingId: opts.editingId });
      else if (view === 'equipo') await ViewUsuarios.render(container);
      else if (view === 'bioindicadores') await ViewBioindicadores.render(container);
      else if (view === 'reportes') await ViewReportes.render(container);
      else if (view === 'variables') await ViewVariables.render(container);
      else if (view === 'configuracion') await ViewConfiguracion.render(container);
    } catch (err) {
      console.error(err);
      container.innerHTML = `<div class="panel glass empty-state">Ocurrió un error al cargar esta sección: ${UI.escapeHtml(err.message || '')}</div>`;
    }
  }

  return {
    boot, navigate,
    get currentUser() { return currentUser; },
  };
})();

document.addEventListener('DOMContentLoaded', () => App.boot());
