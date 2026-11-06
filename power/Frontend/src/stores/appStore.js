import { defineStore } from 'pinia';
import { initialAthlete } from '../data/mockData';
import { getProfile, saveProfile, resetProfile } from '../services/profile';
import { fetchCurrentUser } from '../services/auth';
import { adaptProfileToAthlete } from '../utils/profile';
import {
  getNotebook,
  addNote as apiAddNote,
  patchNote as apiPatchNote,
  deleteNote as apiDeleteNote,
} from '../services/notebook';
import { getLatestCheckin, submitCheckin as apiSubmitCheckin } from '../services/checkin';
import { getPlan, getCharts, getStatus, getEstadoDeLasSesiones } from '../services/plan';
import { getUsage } from '../services/usage';
import {
  getNotifications,
  dismissNotification as apiDismissNotification,
} from '../services/notifications';
import { BACKGROUNDS, DEFAULT_BACKGROUND_ID, findCoach, normalizeCoachId } from '../utils/appearance';

// Preferencia personal (fondo, entrenador elegido y foto del atleta), guardada
// por usuario en localStorage -- igual que `garmin_filled_u${id}` en
// ProfileView.vue.
//
// El ENTRENADOR ya no es sólo cosmético: su `tone` decide cómo responde el
// chat, y por eso `coachId` viaja al backend en cada mensaje (ver
// services/coach.js). Lo que NO viaja es la foto del atleta ni el fondo.
//
// La foto se guarda aquí como data URL ya reducida a 256px (ver
// utils/imagen.js). Vive en ESTE navegador: no hay columna para ella en la
// base de datos, así que no sigue al atleta a otro dispositivo.
const settingsKey = (user) => `settings_u${user?.id ?? 'anon'}`;
const SETTINGS_VACIOS = { backgroundId: DEFAULT_BACKGROUND_ID, coachId: null, athletePhoto: null };
const readSettings = (user) => {
  try {
    const raw = JSON.parse(localStorage.getItem(settingsKey(user)) || '{}');
    return {
      backgroundId: raw.backgroundId || DEFAULT_BACKGROUND_ID,
      // `coachAvatarId` es la clave vieja, de cuando el coach era un avatar
      // sin nombre. normalizeCoachId traduce 'hombre'/'mujer' a Juan/Fernanda.
      coachId: normalizeCoachId(raw.coachId || raw.coachAvatarId),
      athletePhoto: raw.athletePhoto || null,
    };
  } catch (e) {
    return { ...SETTINGS_VACIOS };
  }
};

export const useAppStore = defineStore('app', {
  state: () => {
    const authUser = JSON.parse(localStorage.getItem('authUser') || 'null');
    return {
      athlete: { ...initialAthlete },

      // Coach's notebook (backend-backed; see src/services/notebook.js)
      coachNotes: [],
      notebookCategories: {},
      notebookCounts: { vigentes: 0, propuestas: 0, retiradas: 0 },
      notebookLoaded: false,
      notebookLoading: false,

      // Pre-week check-in (backend-backed; see src/services/checkin.js)
      checkin: null,
      checkinBaseline: null,
      checkinLoaded: false,
      isCheckinOpen: false,
      isCheckinSaving: false,
      // Las fechas de la semana que se va a generar (utils/semana.js).
      checkinDates: [],
      // Resolver de la promesa que devuelve askCheckin(); null cuando el modal
      // se abrio por su cuenta y nadie esta esperando una respuesta.
      _checkinResolve: null,

      // Generated training plan (backend-backed; see src/services/plan.js)
      plan: null,
      planLoaded: false,
      // ✓/✗ de cada sesión, leídos de su Garmin sin IA (2026-10-21). null = todavía no se sabe.
      estadoSesiones: null,
      estadoSesionesCargando: false,

      // Consumo de IA de esta cuenta. Se carga en cuanto se abre el menu del
      // atleta y no al arrancar: es una consulta que cruza el puente hasta el
      // libro de costos, y no tiene por que pagarla cada carga de pagina.
      usage: null,
      usageLoading: false,

      // Las series que dibuja la app (recuperacion, carga, forma...). Se
      // cargan bajo demanda: es una consulta que cruza el puente.
      charts: null,
      chartsMeta: null,
      chartsLoading: false,

      // Avisos del administrador (backend; ver src/services/notifications.js).
      // `notifications` es la BANDEJA de este atleta: lo que no ha descartado.
      notifications: [],
      notificationsLoaded: false,
      // Cuándo se pidieron los avisos por última vez: se vuelven a pedir al cambiar de
      // pantalla o al volver a la pestaña, como mucho cada 60 s (la respuesta a una
      // consulta de ayuda tiene que llegar sin recargar; Beta 3.0, F3).
      notificationsLoadedAt: 0,

      // Auth
      token: localStorage.getItem('token') || null,
      authUser,

      // Preferencia de apariencia (fondo + entrenador + foto). Ver
      // src/utils/appearance.js para el catálogo de opciones.
      settings: readSettings(authUser),
      isSettingsOpen: false,
      // La pestaña de la tuerca: 'apariencia' | 'precios' (2026-10-21).
      settingsTab: 'apariencia',

      // Athlete profile (backend-backed; see src/services/profile.js)
      profile: null,
      profileLoaded: false,
      profileLoading: false,
      isProfileSaving: false,

      // UI & Navigation
      mobileMenuOpen: false,
      notificationsOpen: false,
      // Menu del atleta (arriba a la derecha). Guarda lo que no es de uso
      // diario -- perfil e historial -- para que las pestañas principales
      // sean solo aquello a lo que se entra todos los dias.
      userMenuOpen: false,

      // Modals
      isAiCoachOpen: false,
      // «Ayuda / Opinar» (Beta 3.0, F3): abierto, con qué categoría y desde qué sesión.
      ayuda: { abierta: false, categoria: '', sessionKey: '', sesion: '' },
      // «Invitar a un atleta» (Beta 3.0, Database/033): components/modals/InvitarModal.vue.
      invitarAbierto: false,
      // true mientras el atleta elige entrenador o escribe su cuaderno en la bienvenida:
      // los botones flotantes se esconden para que cada toque sea de ese paso.
      altaEnCurso: false,
      isHealthOpen: false,

      // Toast Notifications
      toastMessage: null,
      toastTimer: null,
    };
  },

  getters: {
    isAdmin: (state) => state.authUser?.role === 'admin',
    // Hay una generacion esperando la respuesta del check-in: sólo entonces
    // tiene sentido ofrecer "Saltar", porque hay algo que saltar hacia.
    isAwaitingCheckin: (state) => state._checkinResolve !== null,
    // Cuantos avisos tiene sin descartar. Es lo que enciende el punto de la
    // campana -- y lo APAGA al vaciarla, que es lo que el punto anterior no
    // hacia: estaba fijo en el codigo y no se apagaba nunca.
    notificationCount: (state) => state.notifications.length,
    backgroundSrc: (state) =>
      BACKGROUNDS.find((b) => b.id === state.settings.backgroundId)?.src || BACKGROUNDS[0].src,
    // El entrenador elegido entero, o null si todavia no ha elegido. Todo lo
    // demas (nombre, retrato, metodo) cuelga de aqui.
    coach: (state) => findCoach(state.settings.coachId),
    // null cuando no hay coach elegido, o cuando el elegido aun no tiene
    // retrato (Ben y Natasha): los componentes caen al icono smart_toy.
    coachAvatarSrc: (state) => findCoach(state.settings.coachId)?.src || null,
    // "Coach" a secas mientras no elija: es lo que decia la app antes y sigue
    // siendo cierto. Nunca un nombre inventado.
    coachName: (state) => findCoach(state.settings.coachId)?.name || 'Coach',
    // La foto que suba manda sobre la de Google. Si no ha subido ninguna, la
    // de Google sigue siendo la buena.
    athleteAvatarSrc: (state) => state.settings.athletePhoto || state.athlete.avatarUrl,
  },

  actions: {
    setSession(token, user) {
      this.token = token;
      this.authUser = user;
      localStorage.setItem('token', token);
      localStorage.setItem('authUser', JSON.stringify(user));
      // Cambio de cuenta en el mismo navegador: la preferencia de apariencia
      // es por usuario, así que se vuelve a leer para el que acaba de entrar.
      this.settings = readSettings(user);
      this.applyAuthUserToAthlete();
    },

    /**
     * Vuelve a leer el usuario del servidor y lo persiste.
     *
     * `authUser` salia SOLO del login y de `localStorage`, asi que una cuenta
     * abierta desde hace semanas seguia trayendo lo que el backend contestaba
     * entonces. `fetchCurrentUser` existia y no lo llamaba nadie.
     *
     * Eso hacia dos cosas mal, y la segunda es la que obliga a arreglarlo:
     *
     *  - un atleta al que se le retira el acceso seguia entrando hasta que
     *    cerraba sesion a mano;
     *  - y `termsVersionRequired` -- que es como la puerta legal sabe que hay
     *    que aceptar -- no habria llegado NUNCA a quien ya tiene sesion
     *    abierta, que son todos los de la beta. La puerta no se habria
     *    enseñado a nadie: un no-op perfecto, sin error y sin sintoma.
     *
     * Si falla NO se cierra la sesion: puede ser el servidor caido o la red del
     * atleta, y echarle por eso es peor que dejarle con datos de ayer. El 401 lo
     * sigue tratando el interceptor de `api.js`, como siempre.
     */
    async refreshAuthUser() {
      if (!this.token) return;
      try {
        const res = await fetchCurrentUser();
        if (res && res.user) {
          this.authUser = res.user;
          localStorage.setItem('authUser', JSON.stringify(res.user));
          this.applyAuthUserToAthlete();
        }
      } catch (e) { /* red o servidor: se sigue con lo que ya habia */ }
    },

    /**
     * Re-applies the logged-in user's name/photo/role onto `athlete`.
     * Runs on every load (not just login) so a page refresh doesn't fall
     * back to the mock "Alex" / stock photo until the profile finishes loading.
     */
    applyAuthUserToAthlete() {
      const u = this.authUser;
      if (!u) return;
      // El nombre de la CUENTA sólo rellena hasta que llega el del PERFIL.
      //
      // `MainLayout` lanza `refreshAuthUser()` y `loadProfile()` a la vez, y
      // esta línea pisaba el nombre sin condición: ganaba el que resolviera
      // último. Con el mismo atleta y la misma sesión, el perfil decía "Jose
      // Ines Castillo" y el plan "Atleta 2" -- no según la pantalla, sino
      // según quién ganara la carrera esa vez. En producción no se nota
      // porque `users.name` viene de Google y suele ser el mismo, que es
      // justo por lo que podía quedarse aquí para siempre.
      if (u.name && !this.profileLoaded) this.athlete.name = u.name;
      if (u.avatarUrl) this.athlete.avatarUrl = u.avatarUrl;
      // El titulo sale del NIVEL que el atleta declaro, no de una cadena fija.
      // Antes todo el mundo era "Atleta Elite" -- administrador incluido en la
      // otra rama -- y un atleta de la beta lo leyo como un sistema de niveles
      // que no existe. Si todavia no ha declarado nivel, no se inventa: se
      // queda con lo que ya hubiera (lo pone `adaptProfileToAthlete`).
      if (u.role === 'admin') {
        this.athlete.roleTitle = 'Administrador';
      } else if (this.athlete.levelLabel) {
        this.athlete.roleTitle = this.athlete.levelLabel;
      }
    },

    logout() {
      this.token = null;
      this.authUser = null;
      this.profile = null;
      this.profileLoaded = false;
      this.coachNotes = [];
      this.notebookLoaded = false;
      this.checkin = null;
      this.checkinBaseline = null;
      this.checkinLoaded = false;
      this.plan = null;
      this.planLoaded = false;
      this.estadoSesiones = null;
      this.usage = null;
      this.notifications = [];
      this.notificationsLoaded = false;
      this.charts = null;
      this.chartsMeta = null;
      // Solo en memoria: no se borra del localStorage, así la preferencia
      // sigue ahí la próxima vez que este usuario entre.
      this.settings = { ...SETTINGS_VACIOS };
      localStorage.removeItem('token');
      localStorage.removeItem('authUser');
    },

    /** Elige fondo, entrenador y/o foto. Aplica al toque y persiste por usuario.
     *  Devuelve false si no se pudo GUARDAR (se aplicó igual en esta sesión). */
    updateSettings(patch) {
      this.settings = { ...this.settings, ...patch };
      try {
        localStorage.setItem(settingsKey(this.authUser), JSON.stringify(this.settings));
        return true;
      } catch (e) {
        // Modo privado u otro bloqueo de storage: la preferencia igual se
        // aplica en esta sesión, solo no sobrevive a un refresh. Quien llama
        // decide si eso merece avisar -- para la foto sí, porque el atleta
        // acaba de hacer un trabajo (elegirla y recortarla) que se perderá.
        return false;
      }
    },

    /** Guarda la foto del atleta (data URL ya reducida). Avisa si no cupo. */
    setAthletePhoto(dataUrl) {
      const anterior = this.settings.athletePhoto;
      if (this.updateSettings({ athletePhoto: dataUrl })) {
        this.showToast(dataUrl ? 'Foto de perfil actualizada.' : 'Foto de perfil eliminada.');
        return true;
      }
      // No cupo (cuota llena) o el navegador bloquea el almacenamiento. Se
      // deshace en vez de dejar una foto que desaparece al recargar sin que
      // el atleta entienda por qué.
      this.settings = { ...this.settings, athletePhoto: anterior };
      this.showToast('No se pudo guardar la foto en este navegador.');
      return false;
    },

    /** Load the athlete profile from the backend and merge it into `athlete`. */
    async loadProfile() {
      if (!this.token || this.profileLoading) return;
      this.profileLoading = true;
      try {
        const p = await getProfile();
        this.profile = p;
        this.athlete = { ...this.athlete, ...adaptProfileToAthlete(p, this.athlete) };
      } catch (e) {
        // Keep the mock fallback so the rest of the app still renders.
        console.warn('No se pudo cargar el perfil:', e?.message || e);
      } finally {
        this.profileLoaded = true;
        this.profileLoading = false;
      }
    },

    /** Persist a PaginaLocal-shaped profile payload. Returns the saved profile. */
    async persistProfile(payload) {
      this.isProfileSaving = true;
      try {
        const p = await saveProfile(payload);
        this.profile = p;
        this.athlete = { ...this.athlete, ...adaptProfileToAthlete(p, this.athlete) };
        this.showToast('Perfil de atleta guardado con éxito.');
        return p;
      } catch (e) {
        this.showToast('No se pudo guardar el perfil. Intenta de nuevo.');
        throw e;
      } finally {
        this.isProfileSaving = false;
      }
    },

    /** Wipe the athlete profile (row + competitions + injuries). Account stays. */
    async resetProfileData() {
      await resetProfile();
      this.profile = null;
      await this.loadProfile();
    },

    showToast(msg) {
      if (this.toastTimer) clearTimeout(this.toastTimer);
      this.toastMessage = msg;
      this.toastTimer = setTimeout(() => {
        this.toastMessage = null;
      }, 3500);
    },

    updateAthlete(updated) {
      this.athlete = { ...this.athlete, ...updated };
      this.showToast('Perfil de atleta guardado con éxito.');
    },

    // Aqui vivia syncGarmin(): llamaba a POST /api/garmin/sync, una ruta que
    // NO EXISTE en este backend, y su catch mostraba igualmente
    // "Sincronizacion con Garmin completada" -- exito anunciado sobre una
    // peticion que siempre fallaba. Ademas escribia sobre `athlete` unos
    // valores que nunca llegaban. La sincronizacion de verdad es
    // POST /plan/charts/refresh (ver services/plan.js). Ya no lo usaba nadie.

    /** Load the coach's notebook from the backend. */
    async loadNotebook() {
      if (!this.token || this.notebookLoading) return;
      this.notebookLoading = true;
      try {
        const data = await getNotebook();
        this.coachNotes = data.entries || [];
        this.notebookCategories = data.categories || {};
        this.notebookCounts = data.counts || { vigentes: 0, propuestas: 0, retiradas: 0 };
      } catch (e) {
        console.warn('No se pudo cargar el cuaderno:', e?.message || e);
      } finally {
        this.notebookLoaded = true;
        this.notebookLoading = false;
      }
    },

    async addNote(payload) {
      try {
        await apiAddNote(payload);
        await this.loadNotebook();
        this.showToast('Nota añadida a la bitácora del entrenador.');
      } catch (e) {
        this.showToast('No se pudo añadir la nota.');
        throw e;
      }
    },

    async updateNote(id, patch) {
      try {
        await apiPatchNote(id, patch);
        await this.loadNotebook();
      } catch (e) {
        this.showToast('No se pudo actualizar la nota.');
        throw e;
      }
    },

    /** Retira la nota (se conserva). `paraSiempre` borra de verdad una ya retirada. */
    async deleteNote(id, paraSiempre = false) {
      try {
        await apiDeleteNote(id, paraSiempre);
        await this.loadNotebook();
        this.showToast(paraSiempre ? 'Nota borrada para siempre.' : 'Nota retirada: ya no llega a tu plan.');
      } catch (e) {
        this.showToast('No se pudo quitar la nota.');
        throw e;
      }
    },

    /** Load the latest pre-week check-in + the athlete's Hooper baseline. */
    async loadCheckin() {
      if (!this.token) return;
      try {
        const data = await getLatestCheckin();
        this.checkin = data.available ? data.checkin : null;
        this.checkinBaseline = data.baseline || null;
      } catch (e) {
        console.warn('No se pudo cargar el check-in:', e?.message || e);
      } finally {
        this.checkinLoaded = true;
      }
    },

    /** Lo que esta cuenta ha costado en APIs. Silencioso si falla: es
     *  informacion, no algo que deba interrumpir al atleta.
     *
     *  SOLO ADMINISTRADOR. No se pide siquiera para un atleta: la ruta le
     *  responde 403, y pedirla igual seria un aviso en la consola en cada
     *  apertura del menu. El porque de esconderlo esta en TopHeader.vue. */
    async loadUsage(forzar = false) {
      if (!this.token || this.usageLoading || !this.isAdmin) return;
      if (this.usage && !forzar) return;
      this.usageLoading = true;
      try {
        this.usage = await getUsage();
      } catch (e) {
        console.warn('No se pudo cargar el consumo:', e?.message || e);
      } finally {
        this.usageLoading = false;
      }
    },

    /** La bandeja de avisos. Silenciosa si falla: es informacion, no algo
     *  que deba interrumpir al atleta. */
    /**
     * Abre «Ayuda / Opinar». Desde una sesión del plan lleva su clave (fecha|franja) y su
     * nombre, para que la consulta llegue con ESA sesión y no con «una de mi semana».
     */
    /** La tuerca: Apariencia o Precios. Todo «Quiero suscribirme» abre Precios (2026-10-21). */
    abrirAjustes(pestana = 'apariencia') {
      this.settingsTab = pestana;
      this.isSettingsOpen = true;
    },

    abrirPrecios() {
      this.abrirAjustes('precios');
    },

    abrirAyuda({ categoria = '', sessionKey = '', sesion = '', mensaje = '' } = {}) {
      this.ayuda = { abierta: true, categoria, sessionKey, sesion, mensaje };
    },

    cerrarAyuda() {
      this.ayuda = { abierta: false, categoria: '', sessionKey: '', sesion: '' };
    },

    async loadNotifications() {
      if (!this.token) return;
      this.notificationsLoadedAt = Date.now();
      try {
        this.notifications = await getNotifications();
      } catch (e) {
        console.warn('No se pudieron cargar los avisos:', e?.message || e);
      } finally {
        this.notificationsLoaded = true;
      }
    },

    /** Lo quita de la bandeja de ESTE atleta. Sigue vivo para el resto. */
    async dismissNotification(id) {
      // Se quita de la lista antes de que responda el servidor: el atleta
      // acaba de pulsar una X y esperar medio segundo a que desaparezca hace
      // que parezca que no funciono y la pulse otra vez.
      const antes = this.notifications;
      this.notifications = this.notifications.filter((n) => n.id !== id);
      try {
        await apiDismissNotification(id);
      } catch (e) {
        this.notifications = antes;
        this.showToast('No se pudo quitar el aviso. Intenta de nuevo.');
      }
    },

    /** Las series de Garmin. Una sola vez por sesion salvo que se fuerce. */
    async loadCharts(forzar = false) {
      if (!this.token || this.chartsLoading) return;
      if (this.charts && !forzar) return;
      this.chartsLoading = true;
      try {
        const data = await getCharts();
        this.charts = data?.charts || {};
        this.chartsMeta = { last_refresh: data?.last_refresh, stale: data?.stale, weeks: data?.weeks_recorded };
      } catch (e) {
        // Silencioso: es informacion, y el modal ya dice que no hay datos.
        console.warn('No se pudieron cargar las series:', e?.message || e);
        this.charts = this.charts || {};
      } finally {
        this.chartsLoading = false;
      }
    },

    /**
     * ✓/✗ de cada sesión (utils/hecho.js). El servicio guarda lo leído 15 minutos; `forzar` es
     * «Actualizar». Lee Garmin: la primera vez tarda unos segundos, y nunca bloquea la pantalla.
     */
    async cargarEstadoSesiones(forzar = false) {
      if (!this.token || this.estadoSesionesCargando) return;
      this.estadoSesionesCargando = true;
      try {
        this.estadoSesiones = await getEstadoDeLasSesiones(forzar);
      } catch (e) {
        this.estadoSesiones = { sesiones: {}, error: 'No pudimos leer tu Garmin ahora mismo.' };
      } finally {
        this.estadoSesionesCargando = false;
      }
    },

    async loadPlan() {
      if (!this.token) return;
      try {
        this.plan = await getPlan();
      } catch (e) {
        console.warn('No se pudo cargar el plan:', e?.message || e);
      } finally {
        this.planLoaded = true;
      }
      await this.rescatarGeneracion();
    },

    /**
     * Recoge una generación que terminó mientras el atleta no miraba.
     *
     * El backend guarda el resultado en el único momento en que lo tiene
     * delante: cuando la app pregunta por el estado. Y hasta ahora sólo
     * preguntaba la pantalla de Plan, así que quien pulsaba "generar" y cerraba
     * la pestaña perdía una semana **ya pagada**: se quedaba en el AiService y
     * su limpieza la borraba a los tres días. Preguntando también al cargar el
     * plan —que ocurre al entrar en la app, desde cualquier pantalla— el
     * resultado se persiste en la primera visita posterior.
     *
     * Silencioso a propósito: es un rescate, no una acción del atleta. Si el
     * AiService no contesta, el plan que ya se cargó sigue en pie.
     */
    async rescatarGeneracion() {
      if (!this.token) return;
      try {
        const s = await getStatus();
        if (s?.done && !s?.error && s?.plan) {
          this.plan = s.plan;
        }
      } catch (e) {
        console.warn('No se pudo comprobar si había una generación pendiente:', e?.message || e);
      }
    },

    /**
     * Abre el check-in y espera. Resuelve `true` si hay que seguir adelante
     * con la generacion (se guardo, o el atleta pulso "Saltar") y `false` si
     * cancelo.
     *
     * El check-in se pregunta AQUI y en ningun otro sitio: justo antes de
     * construir la semana es el unico momento en que la respuesta sigue
     * siendo cierta. Es OPCIONAL -- "Saltar" genera igual y nada de lo que
     * viene despues cambia. Lo que aporta es lo que ningun sensor ve: una
     * molestia localizada, que se atravesó la semana pasada, y como viene la
     * que entra; de ahi salen las adaptaciones por sesion.
     */
    async askCheckin(fechas = []) {
      if (!this.token) return 'saltado';
      this.checkinDates = Array.isArray(fechas) ? fechas : [];
      // Esperar a cargarlo ANTES de abrir: el modal siembra el formulario en
      // el watch de isCheckinOpen, que no vuelve a dispararse cuando la
      // peticion termina. Abrir primero mostraba el check-in anterior.
      await this.loadCheckin();
      // Los días del check-in vienen como su rejilla del perfil.
      if (!this.profile) await this.loadProfile();
      this.isCheckinOpen = true;
      return new Promise((resolve) => {
        this._checkinResolve = resolve;
      });
    },

    /**
     * Cierra el check-in resolviendo la promesa de askCheckin(), si la hay:
     * 'guardado', 'saltado' o false (cerrado con la X, no se genera). Saltar y
     * guardar se distinguen porque saltar significa "esta semana sin check-in":
     * antes los dos eran `true` y la semana usaba el de hace un rato.
     */
    closeCheckin(resultado) {
      this.isCheckinOpen = false;
      const resolve = this._checkinResolve;
      this._checkinResolve = null;
      if (resolve) resolve(resultado === 'guardado' || resultado === 'saltado' ? resultado : false);
    },

    async submitCheckin(payload) {
      this.isCheckinSaving = true;
      try {
        const data = await apiSubmitCheckin(payload);
        this.checkin = data.checkin;
        await this.loadCheckin();
        this.showToast('Check-in guardado. Tu entrenador lo tendrá en cuenta.');
        return data.checkin;
      } catch (e) {
        this.showToast('No se pudo guardar el check-in.');
        throw e;
      } finally {
        this.isCheckinSaving = false;
      }
    },
  },
});
