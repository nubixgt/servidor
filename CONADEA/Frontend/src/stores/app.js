import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { MODULOS, INSIGNIAS } from '../data/local.js';
import authService from '../services/authService.js';

/**
 * Store Principal de la App AgroIA
 *
 * 📌 NOTA: Login y registro ya usan el Backend real (JWT, ver
 * iniciarSesion()/registrar()). El progreso de cursos/insignias todavía
 * vive en localStorage — se migrará a la API cuando tenga su endpoint.
 */
export const useAppStore = defineStore('app', () => {

  // ========================
  // ESTADO
  // ========================
  const usuario = ref(null);      // { nombre, org, muni, act, desde }
  const prog = ref({});           // { [moduloId]: { lec: [], nota: null, ok: false, fecha: null } }
  const insignias = ref([]);      // ['semilla', 'modulo1', ...]
  const abiertos = ref([]);       // IDs de módulos abiertos
  const actividad = ref([]);      // Array de fechas ISO 'YYYY-MM-DD' con actividad
  const novVisto = ref(false);
  const config = ref({
    notif: true,
    datos: false,       // Modo ahorro de datos
    recordatorio: true
  });

  // Para módulo en detalle (usado por DetalleCurso)
  const modActual = ref(null);

  // ========================
  // COMPUTADAS
  // ========================
  const estaLogueado = computed(() => !!usuario.value);

  const modulosCompletos = computed(() =>
    MODULOS.filter(m => progDe(m.id).ok).length
  );

  const enProgreso = computed(() =>
    MODULOS.filter(m => {
      const p = progDe(m.id);
      return !p.ok && p.lec.length > 0;
    }).length
  );

  const totalLecciones = computed(() =>
    MODULOS.reduce((s, m) => s + m.lecciones.length, 0)
  );

  const leccionesHechas = computed(() =>
    MODULOS.reduce((s, m) => s + progDe(m.id).lec.length, 0)
  );

  const horasCapacitacion = computed(() =>
    Math.round(leccionesHechas.value * 0.5 + modulosCompletos.value * 0.5)
  );

  const pctGlobal = computed(() =>
    Math.round(MODULOS.reduce((s, m) => s + pctModulo(m), 0) / MODULOS.length)
  );

  const racha = computed(() => {
    const set = new Set(actividad.value);
    let n = 0;
    const d = new Date();
    while (set.has(d.toISOString().slice(0, 10))) {
      n++;
      d.setDate(d.getDate() - 1);
    }
    return n;
  });

  const semanaActual = computed(() => {
    const hoy = new Date();
    const dow = (hoy.getDay() + 6) % 7;
    const lunes = new Date(hoy);
    lunes.setDate(hoy.getDate() - dow);
    const set = new Set(actividad.value);
    return ['L', 'M', 'M', 'J', 'V', 'S', 'D'].map((l, i) => {
      const f = new Date(lunes);
      f.setDate(lunes.getDate() + i);
      const iso = f.toISOString().slice(0, 10);
      return { l, hecho: set.has(iso), hoy: iso === hoyISO() };
    });
  });

  // ========================
  // UTILIDADES INTERNAS
  // ========================
  function hoyISO() {
    return new Date().toISOString().slice(0, 10);
  }

  function progDe(id) {
    if (!prog.value[id]) {
      prog.value[id] = { lec: [], nota: null, ok: false, fecha: null };
    }
    if (!prog.value[id].lec) {
      prog.value[id].lec = [];
    }
    return prog.value[id];
  }

  function pctModulo(m) {
    const p = progDe(m.id);
    const t = m.lecciones.length + 1;
    return Math.round((p.lec.length + (p.ok ? 1 : 0)) / t * 100);
  }

  function pctRuta(r) {
    return Math.round(
      r.mods.reduce((s, id) => s + pctModulo(MODULOS.find(m => m.id === id)), 0) / r.mods.length
    );
  }

  // ========================
  // ACCIONES
  // ========================

  function registrarActividad() {
    const h = hoyISO();
    if (!actividad.value.includes(h)) {
      actividad.value.push(h);
      guardar();
    }
  }

  function revisarInsignias() {
    const c = modulosCompletos.value;
    if (leccionesHechas.value >= 1) otorgar('semilla');
    if (c >= 1) otorgar('modulo1');
    if (c >= 3) otorgar('tres');
    if (c >= 5) otorgar('cinco');
    if (c >= 21) otorgar('todas');
    if (abiertos.value.length >= 4) otorgar('explorador');
  }

  function otorgar(id) {
    if (insignias.value.includes(id)) return;
    insignias.value.push(id);
    guardar();
    const badge = INSIGNIAS.find(x => x.id === id);
    if (badge) {
      mostrarToastGlobal(badge.ic, `¡Nueva insignia: ${badge.t}!`, badge.d, true);
    }
  }

  // Toast global (evento simple)
  const toastData = ref({ visible: false, ic: '', titulo: '', texto: '', hex: false });
  let toastTimer = null;
  function mostrarToastGlobal(ic, titulo, texto, hex = false) {
    toastData.value = { visible: true, ic, titulo, texto, hex };
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toastData.value.visible = false;
    }, 4200);
  }

  // ========================
  // AUTH - LOGIN / REGISTRO
  // ========================

  // Carga (o inicializa) el progreso/insignias locales del usuario que
  // acaba de iniciar sesión. Se mantiene aparte de los datos de la cuenta
  // (que ahora vienen del Backend) porque el progreso de cursos todavía
  // no tiene su propio endpoint de sincronización.
  function cargarDatosLocales(clave) {
    const raw = localStorage.getItem(`agroia_user_${clave}`);
    const data = raw ? JSON.parse(raw) : null;
    prog.value      = data?.prog || {};
    insignias.value = data?.insignias || [];
    abiertos.value  = data?.abiertos || [];
    actividad.value = data?.actividad || [];
    config.value    = data?.config || { notif: true, datos: false, recordatorio: true };
    novVisto.value  = false;
  }

  // Login real contra Backend/src/Controllers/AuthController.php: guarda el
  // JWT (lo usa el interceptor de services/api.js) y los datos de la cuenta.
  async function iniciarSesion(usuarioLogin, password) {
    try {
      const { data } = await authService.login(usuarioLogin, password);
      const u = data.data.usuario;

      localStorage.setItem('token', data.data.token);

      usuario.value = {
        id: u.id,
        usuario: u.usuario,
        nombre: u.nombre_completo,
        telefono: u.telefono,
        rol: u.rol,
        departamentoId: u.departamento_id,
        municipioId: u.municipio_id,
        org: 'Programa CONADEA',
        muni: '',
        act: '',
        desde: new Date().toLocaleDateString('es-GT', { day: 'numeric', month: 'long', year: 'numeric' })
      };
      localStorage.setItem('conadea_usuario', JSON.stringify(usuario.value));

      cargarDatosLocales(u.usuario);
      registrarActividad();
      return { ok: true };
    } catch (e) {
      const msg = e.response?.data?.message || 'No se pudo conectar con el servidor. Intenta de nuevo.';
      return { ok: false, msg };
    }
  }

  // Registro real contra Backend/src/Controllers/AuthController.php. Igual
  // que en la app móvil, crear la cuenta NO inicia sesión automáticamente:
  // la persona vuelve a escribir usuario y contraseña en "Iniciar Sesión".
  async function registrar(datos) {
    try {
      await authService.register(datos);
      return { ok: true };
    } catch (e) {
      const msg = e.response?.data?.message || 'No se pudo conectar con el servidor. Intenta de nuevo.';
      return { ok: false, msg };
    }
  }

  function cerrarSesion() {
    guardar();
    localStorage.removeItem('token');
    localStorage.removeItem('conadea_usuario');
    usuario.value    = null;
    prog.value       = {};
    insignias.value  = [];
    abiertos.value   = [];
    actividad.value  = [];
    novVisto.value   = false;
  }

  // ========================
  // PERSISTENCIA LOCAL
  // (progreso/insignias — la cuenta en sí ya viene del Backend vía JWT)
  // ========================
  function claveLocal() {
    if (!usuario.value) return null;
    // Login real: clave estable = usuario de acceso. Registro demo (local,
    // todavía no conectado al Backend): clave = nombre en slug, como antes.
    return usuario.value.usuario || usuario.value.nombre.toLowerCase().replace(/\s+/g, '_');
  }

  function guardar() {
    const key = claveLocal();
    if (!key) return;
    localStorage.setItem(`agroia_user_${key}`, JSON.stringify({
      nombre:   usuario.value.nombre,
      org:      usuario.value.org,
      muni:     usuario.value.muni,
      act:      usuario.value.act,
      desde:    usuario.value.desde,
      prog:     prog.value,
      insignias: insignias.value,
      abiertos: abiertos.value,
      actividad: actividad.value,
      config:   config.value
    }));
  }

  // Restaura la sesión al recargar la página: si hay un JWT guardado,
  // recupera los datos de cuenta que se guardaron junto a él en el login.
  (function restaurarSesion() {
    const token = localStorage.getItem('token');
    const guardado = localStorage.getItem('conadea_usuario');
    if (!token || !guardado) return;
    try {
      usuario.value = JSON.parse(guardado);
      cargarDatosLocales(usuario.value.usuario);
    } catch (e) {
      localStorage.removeItem('token');
      localStorage.removeItem('conadea_usuario');
    }
  })();

  // ========================
  // ACCIONES DE CURSOS
  // ========================
  function abrirModulo(id) {
    if (!abiertos.value.includes(id)) {
      abiertos.value.push(id);
      guardar();
      revisarInsignias();
    }
    modActual.value = MODULOS.find(m => m.id === id) || null;
  }

  function completarLeccion(moduloId, leccionIdx) {
    const p = progDe(moduloId);
    if (!p.lec.includes(leccionIdx)) {
      p.lec.push(leccionIdx);
    }
    registrarActividad();
    guardar();
    revisarInsignias();
  }

  function aprobarModulo(moduloId, nota) {
    const p = progDe(moduloId);
    p.nota = Math.max(p.nota || 0, nota);
    if (nota >= 2 && !p.ok) {
      p.ok = true;
      p.fecha = new Date().toLocaleDateString('es-GT', { day: 'numeric', month: 'long', year: 'numeric' });
    }
    if (nota === 3 && !insignias.value.includes('perfecto')) {
      otorgar('perfecto');
    }
    registrarActividad();
    guardar();
    setTimeout(revisarInsignias, 700);
  }

  function actualizarConfig(key, value) {
    config.value[key] = value;
    guardar();
  }

  // ========================
  // EXPOSE
  // ========================
  return {
    // Estado
    usuario, prog, insignias, abiertos, actividad, novVisto, config, modActual, toastData,
    // Computadas
    estaLogueado, modulosCompletos, enProgreso, totalLecciones, leccionesHechas,
    horasCapacitacion, pctGlobal, racha, semanaActual,
    // Funciones de utilidad (exportadas para usar en componentes)
    progDe, pctModulo, pctRuta, hoyISO,
    // Acciones
    iniciarSesion, registrar, cerrarSesion,
    completarLeccion, aprobarModulo, abrirModulo,
    actualizarConfig, registrarActividad, guardar,
    mostrarToastGlobal, revisarInsignias
  };
});
