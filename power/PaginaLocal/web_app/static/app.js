// State
let currentProfile = {};
// The adjustment the athlete asked for on the last rejected plan. One-shot:
// consumed by the next generation and then cleared, so a note meant for one
// plan never silently steers every future one.
let pendingAdjustment = '';
// Last payload from /api/plans/current, so the home view can summarise the
// plan without refetching it.
let latestPlanData = null;
// Costo ACUMULADO del atleta, del libro append-only. Separado de
// latestPlanData a propósito: aquel sale de summary.json, que sólo conoce la
// corrida más reciente y desaparece cuando el atleta borra su plan.
let usageData = null;
let chatHistory = [];
let generationInterval = null;

// Scalar profile fields filled/saved generically (text/number/select inputs
// whose element id matches the profile key exactly). Complex fields (days
// grid, competitions, injuries, checkboxes, ai_mode) are handled separately.
const SIMPLE_PROFILE_FIELDS = [
    'name', 'sports_preference', 'garmin_email', 'garmin_password', 'target_distance', 'limitations',
    'lactate_threshold_hr', 'lactate_threshold_pace_sec_per_km', 'ftp_watts',
    'swim_threshold_pace_sec_per_100m',
    'training_age_level', 'years_training', 'current_weekly_volume_hours', 'longest_recent_session_hours',
    'weight_kg', 'height_cm', 'gender', 'birth_date', 'resting_heart_rate', 'vo2max_running', 'vo2max_cycling',
    'pool_access', 'bike_setup', 'altitude_m', 'climate', 'terrain', 'brick_preference',
    'occupation_type', 'avg_sleep_hours', 'stress_level_self_report', 'travel_frequency', 'nutrition_notes',
    'menstrual_cycle_notes',
    'past_injuries_surgeries', 'chronic_conditions', 'medications',
    'coaching_tone', 'strength_training_preference', 'preferred_rest_day',
];
const NUMBER_PROFILE_FIELDS = new Set([
    'lactate_threshold_hr', 'lactate_threshold_pace_sec_per_km', 'ftp_watts',
    'swim_threshold_pace_sec_per_100m',
    'years_training', 'current_weekly_volume_hours', 'longest_recent_session_hours',
    'weight_kg', 'height_cm', 'resting_heart_rate', 'vo2max_running', 'vo2max_cycling', 'altitude_m', 'avg_sleep_hours',
]);
const CHECKBOX_PROFILE_FIELDS = ['treadmill_access', 'menstrual_tracking_opt_in', 'cross_training_open', 'ai_load_guardrail_enabled'];
const PAR_Q_KEYS = ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7'];
const MEDICAL_SNAPSHOT_FIELDS = ['current_injuries', 'past_injuries_surgeries', 'chronic_conditions', 'medications', 'par_q_flags'];
// Structured recent_prs keys — inputs are id="pr_<key>" (see Historial panel).
const PR_FIELD_KEYS = ['run_5k', 'run_10k', 'run_half_marathon', 'run_marathon', 'ftp_test_20min', 'swim_1500m'];

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    initTheme();
    initSession();
    initFeedback();
    initCoachWidget();
    initNotebook();
    mermaid.initialize({ startOnLoad: false, theme: currentTheme() === 'dark' ? 'dark' : 'default', securityLevel: 'loose' });
    initDaysGrid();
    initProfileTabs();
    initRouter();
    await loadProfiles();

    // The plan already existed on disk; the old UI simply never asked for it,
    // so reopening the app always showed an empty Step 3.
    fetchCurrentPlan();

    document.querySelectorAll('.nav-links li').forEach(li => {
        li.addEventListener('click', () => navigate(li.getAttribute('data-view')));
    });

    document.getElementById('btnThemeToggle').addEventListener('click', toggleTheme);
    document.getElementById('btnShutdown').addEventListener('click', shutdownServer);
    document.getElementById('btnDeleteRun').addEventListener('click', deleteCurrentRun);

    // Profile handling
    document.getElementById('btnSaveProfile').addEventListener('click', () => {
        clearTimeout(_autosaveTimer);
        saveProfile(false).then(() => setSaveIndicator(`Guardado ✓ ${new Date().toLocaleTimeString()}`));
    });
    document.getElementById('profileSelect').addEventListener('change', onProfileSelect);
    document.getElementById('profileForm').addEventListener('input', updateProfileCompleteness);
    document.getElementById('profileForm').addEventListener('input', autosaveProfileDebounced);
    document.getElementById('profileForm').addEventListener('change', autosaveProfileDebounced);

    // Dynamic competitions / injuries
    document.getElementById('btnAddCompetition').addEventListener('click', () => addCompetitionField());
    // El catalogo de distancias llega del servidor. Si tarda mas que el
    // primer render del perfil, los desplegables ya pintados se rellenan al
    // llegar en vez de quedarse con la opcion vacia.
    loadRaceDistances().then(refreshRaceDistanceSelects);
    document.getElementById('btnAddInjury').addEventListener('click', () => addInjuryField());

    // Garmin auto-import
    document.getElementById('btnGarminImport').addEventListener('click', garminAutoImport);

    // Menstrual tracking opt-in toggles the notes field
    document.getElementById('menstrual_tracking_opt_in').addEventListener('change', toggleMenstrualNotes);

    document.getElementById('btnProfileToPlan').addEventListener('click', () => {
        if (!requireAvailableDay()) return;
        saveProfile(true);
        navigate('plan');
    });
    document.getElementById('btnDeleteProfile').addEventListener('click', deleteCurrentProfile);

    // Ficha de salud. The two buttons ran the same handler; the second is now
    // a plain shortcut into the same flow, labelled for the re-evaluation case.
    document.getElementById('btnStartDoctor').addEventListener('click', startHealthIntake);
    document.getElementById('btnSendChat').addEventListener('click', sendChatMessage);

    // Generation
    document.getElementById('btnGeneratePlan').addEventListener('click', startGeneration);
    document.getElementById('btnGenerateWeek').addEventListener('click', startWeekGeneration);
    document.getElementById('brick_preference')?.addEventListener('change', updateBrickFeasibility);

    // Coach chat. Enter sends, Shift+Enter makes a new line -- the athlete is
    // asking short questions, not writing paragraphs.
    document.getElementById('btnCoachSend').addEventListener('click', sendCoachMessage);
    document.getElementById('coachInput').addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendCoachMessage();
        }
    });
    document.getElementById('btnHomeGenerate').addEventListener('click', () => {
        navigate('plan');
        startGeneration();
    });

    // Opens the print-optimised sheet in its own tab, which offers "Save as
    // PDF" in the browser's own print dialog — no PDF library, and the
    // athlete keeps control over paper size and margins.
    document.getElementById('btnPrintPlan').addEventListener('click', () => openPrintable('plan'));
    document.getElementById('btnPrintSeason').addEventListener('click', () => openPrintable('season'));
    document.getElementById('btnHomePrint').addEventListener('click', () => openPrintable('plan'));
    document.getElementById('btnHomeSeason').addEventListener('click', () => openPrintable('season'));
    document.getElementById('btnHomeAnalysis').addEventListener('click', openAnalysisReport);
    // Los cuatro informes, en las DOS pantallas. El de salud existía desde v5
    // y sólo lo leía la máquina: es un informe sobre la salud del atleta,
    // escrito con lo que él contó, y no había dónde leerlo.
    document.getElementById('btnPlanAnalysis').addEventListener('click', openAnalysisReport);
    document.getElementById('btnHomeMedical').addEventListener('click', openMedicalReport);
    document.getElementById('btnPlanMedical').addEventListener('click', openMedicalReport);

    // Reject / Adjust: capture what the athlete wants changed and regenerate.
    document.getElementById('btnRejectPlan').addEventListener('click', () => {
        document.getElementById('rejectReason').value = '';
        document.getElementById('rejectModal').classList.add('active');
    });
    // Rehacer SÓLO la semana: reescribe los mismos 7 días en su sitio, con el
    // ajuste del atleta. Hasta v7 no existía este camino -- el único botón
    // rehacía análisis + macrociclo + semana, así que mover un miércoles
    // costaba una generación entera y le cambiaba la temporada.
    document.getElementById('btnConfirmReject').addEventListener('click', () => {
        const reason = document.getElementById('rejectReason').value.trim();
        if (!reason) { alert('Escribe qué quieres ajustar antes de rehacerla.'); return; }
        document.getElementById('rejectModal').classList.remove('active');
        navigate('plan');
        rehacerLaSemana(reason);
    });

    document.getElementById('btnRejectAll').addEventListener('click', () => {
        const reason = document.getElementById('rejectReason').value.trim();
        if (!reason) { alert('Escribe qué quieres ajustar antes de regenerar.'); return; }
        if (!confirm('Esto rehace el análisis, el macrociclo y la semana, y reemplaza tu plan de '
                   + 'temporada actual.\n\n¿Seguro? Para cambiar sólo esta semana, usa el otro botón.')) return;
        document.getElementById('rejectModal').classList.remove('active');
        // Goes straight into the profile's planning_context instead of a
        // prompt textarea. One-shot on purpose: this used to append to a
        // saved field that was never cleared, so an adjustment asked for once
        // silently steered every future plan the athlete ever generated.
        pendingAdjustment = `Ajuste solicitado tras revisar el plan: ${reason}`;
        navigate('plan');
        startGeneration();
    });

    document.getElementById('historyList').addEventListener('click', onHistoryClick);

    // Garmin upload: whole-plan button + per-day buttons (event delegation,
    // since day cards are rendered dynamically after generation).
    document.getElementById('btnUploadGarmin').addEventListener('click', subirTodoAGarmin);
    document.getElementById('planResultsContainer').addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-upload-day');
        if (!btn) return;
        // Composite "date|slot" key so re-uploading one side of a two-a-day
        // (see WorkoutDay.session_slot) never re-uploads the other session.
        const key = btn.dataset.slot && btn.dataset.slot !== 'unica' ? `${btn.dataset.date}|${btn.dataset.slot}` : btn.dataset.date;
        uploadToGarmin([key]);
    });
});

/* ==========================================================================
   Routing.

   Hash-based on purpose: server.py mounts StaticFiles at "/" (it must be last
   so the API routes win), which would swallow any real path like /historial.
   A hash keeps deep links and the back button working without touching that.
   ========================================================================== */
const VIEWS = ['inicio', 'perfil', 'plan', 'historial', 'cuaderno'];

function initRouter() {
    window.addEventListener('hashchange', renderRoute);
    renderRoute();
}

function navigate(view) {
    if (location.hash === `#/${view}`) renderRoute();
    else location.hash = `#/${view}`;
}

function renderRoute() {
    const view = (location.hash.replace(/^#\/?/, '') || 'inicio').split('?')[0];
    const target = VIEWS.includes(view) ? view : 'inicio';

    document.querySelectorAll('.view').forEach(s => s.classList.toggle('active', s.id === `view-${target}`));
    document.querySelectorAll('.nav-links li').forEach(li =>
        li.classList.toggle('active', li.getAttribute('data-view') === target));
    window.scrollTo(0, 0);

    if (target === 'inicio') renderHome();
    if (target === 'historial') loadHistory();
    if (target === 'cuaderno') loadNotebook();
    // El plan se relee al entrar. Antes se pintaba con lo que hubiera en
    // memoria desde la carga de la página, así que un plan generado en otra
    // pestaña -- o simplemente dejar la app abierta -- mostraba el anterior
    // sin ninguna señal de estar desactualizado. Es una llamada barata y es
    // la vista donde una cifra vieja se lee como la verdad.
    if (target === 'plan') fetchCurrentPlan();
    if (target === 'perfil') {
        // Mermaid can only measure a node that is actually laid out, so the
        // diagram is rendered when its view becomes visible, not at boot.
        setTimeout(() => {
            try { mermaid.run({ nodes: document.querySelectorAll('.mermaid:not([data-processed])') }); }
            catch (e) { console.warn('Mermaid render failed:', e); }
        }, 60);
    }
}

/* ==========================================================================
   Coach chat (v5 Track 8). Read-only: it explains the plan, never edits it.
   Context is capped server-side (services/ai/coach_chat.py) because every
   message re-sends all of it -- an unbounded context taxes every question.
   ========================================================================== */
let coachHistory = [];

function appendCoachMessage(role, text) {
    const box = document.getElementById('coachHistory');
    const div = document.createElement('div');
    div.className = `coach-msg ${role}`;
    div.innerHTML = `<div class="msg-content">${chatMarkdown(text)}</div>`;
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
    return div;
}

async function sendCoachMessage() {
    const profileName = currentProfile?.name;
    if (!profileName) { alert('Selecciona un atleta primero.'); return; }

    const input = document.getElementById('coachInput');
    const message = (input.value || '').trim();
    if (!message) return;

    input.value = '';
    appendCoachMessage('user', message);
    const pending = appendCoachMessage('assistant', 'Pensando…');

    const btn = document.getElementById('btnCoachSend');
    btn.disabled = true;
    try {
        const res = await fetch('/api/coach/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ profile: profileName, message, history: coachHistory }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            pending.querySelector('.msg-content').textContent =
                data.detail || 'No pude responder ahora mismo.';
            return;
        }
        pending.querySelector('.msg-content').innerHTML = chatMarkdown(data.reply);
        coachHistory.push({ role: 'user', content: message });
        coachHistory.push({ role: 'assistant', content: data.reply });

        // The size of the context is shown rather than left to be discovered
        // on a bill.
        const stats = data.context_stats || {};
        const coste = data.cost_usd != null ? ` · $${Number(data.cost_usd).toFixed(4)}` : '';
        document.getElementById('coachMeta').textContent =
            `Contexto: ~${stats.approx_tokens || 0} tokens · ${stats.runs_included || 0} corrida(s)${coste}`;

        // v7 Track 7: lo que el coach quiere anotar de este intercambio. Se
        // avisa siempre que anota algo -- una memoria que crece en silencio
        // sobre alguien es exactamente lo que nadie quiere de un producto que
        // le guarda datos de salud.
        if ((data.notebook_proposals || []).length) {
            // Se anota SIEMPRE que anota, y se dice QUÉ. El aviso es lo que
            // sustituye a la confirmación previa: si el coach guardara en
            // silencio, la única forma de enterarse sería ir a buscarlo.
            // Citar el texto exacto permite corregir en el acto lo que
            // entendió mal, que es el fallo probable.
            const cuerpo = data.notebook_proposals
                .map(p => `• ${p.text}`)
                .join('\n');
            appendCoachMessage('note',
                `📓 Lo anoté en tu cuaderno:\n${cuerpo}\n\n` +
                `Si no es así, bórralo en la pestaña Cuaderno.`);
            if (location.hash === '#/cuaderno') loadNotebook();
        }
    } catch (e) {
        pending.querySelector('.msg-content').textContent = 'No pude responder ahora mismo.';
    } finally {
        btn.disabled = false;
    }
}

/* ==========================================================================
   Theme: system by default, with an explicit override the athlete controls.
   ========================================================================== */
function currentTheme() {
    const stored = localStorage.getItem('theme');
    if (stored === 'light' || stored === 'dark') return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function initTheme() {
    const stored = localStorage.getItem('theme');
    if (stored === 'light' || stored === 'dark') {
        document.documentElement.setAttribute('data-theme', stored);
    }
}

function toggleTheme() {
    const next = currentTheme() === 'dark' ? 'light' : 'dark';
    localStorage.setItem('theme', next);
    document.documentElement.setAttribute('data-theme', next);
}

// Profile section tabs (pills)
function initProfileTabs() {
    document.querySelectorAll('.profile-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            // Autosave whatever's in the current tab before switching away —
            // athletes shouldn't have to wonder if moving between the 8
            // sections silently drops what they just typed.
            autosaveProfile();
            document.querySelectorAll('.profile-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.profile-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.querySelector(`.profile-panel[data-panel="${btn.dataset.tab}"]`).classList.add('active');
        });
    });
}

// Autosave: silent save with no alert(), skipped until the athlete has at
// least typed a name (saving an unnamed profile would either fail or create
// a stray "" entry), debounced so rapid typing doesn't fire a request per
// keystroke. Updates a small "Guardado" indicator so the athlete never has
// to wonder whether switching tabs or closing the browser lost their data.
let _autosaveTimer = null;
function autosaveProfile() {
    clearTimeout(_autosaveTimer);
    const nameEl = document.getElementById('name');
    if (!nameEl || !nameEl.value.trim()) {
        setSaveIndicator('Escribe un nombre para empezar a guardar automáticamente.');
        latestPlanData = null;
        renderHome();
        return;
    }
    saveProfile(true).then(() => setSaveIndicator(`Guardado ✓ ${new Date().toLocaleTimeString()}`));
}

function autosaveProfileDebounced() {
    if (window.__loadingProfile) return;  // profile fill in progress, not a real edit
    clearTimeout(_autosaveTimer);
    setSaveIndicator('Guardando…');
    _autosaveTimer = setTimeout(autosaveProfile, 1200);
}

function setSaveIndicator(text) {
    const el = document.getElementById('saveIndicator');
    if (el) el.textContent = text;
}

function updateProfileCompleteness() {
    let filled = 0;
    SIMPLE_PROFILE_FIELDS.forEach(id => {
        const el = document.getElementById(id);
        if (el && el.value && el.value.toString().trim() !== '') filled++;
    });
    const pct = Math.round((filled / SIMPLE_PROFILE_FIELDS.length) * 100);
    const bar = document.getElementById('profileCompletenessBar');
    const label = document.getElementById('profileCompletenessLabel');
    if (bar) bar.style.width = `${pct}%`;
    if (label) label.textContent = `${pct}% completo`;
}

/* Un brick es UNA sesión: bici y carrera seguidas, sin pausa. Así que no basta
   con tener bici un día y carrera otro -- hace falta una franja donde quepan
   las dos. Si el atleta pide bricks y no tiene ninguna, el planificador no
   puede dárselos, y hasta ahora nadie se lo decía: pedía bricks, no llegaban,
   y parecía que el cerebro le ignoraba. */
const _DEPORTES_QUE_CORREN = ['Correr', 'Trail'];

function franjaAdmiteBrick(sports) {
    if (!sports || !sports.length || sports.includes('Cualquiera')) return true;
    return sports.includes('Bici') && _DEPORTES_QUE_CORREN.some(d => sports.includes(d));
}

function updateBrickFeasibility() {
    const aviso = document.getElementById('brickFeasibilityWarning');
    if (!aviso) return;
    if (document.getElementById('brick_preference')?.value !== 'si') {
        aviso.classList.add('hidden');
        return;
    }
    const posibles = daysOfWeek.filter(day => {
        const id = day.toLowerCase();
        if (!document.getElementById(`chk_${id}`)?.checked) return false;
        const blocks = getDayBlocks(id);
        return blocks.length ? blocks.some(b => franjaAdmiteBrick(b.sports)) : true;
    });
    if (posibles.length) {
        aviso.classList.add('hidden');
        return;
    }
    aviso.textContent = '⚠️ Pediste entrenos brick, pero ningún día tiene una franja donde quepan '
        + 'bici y carrera a la vez. Un brick no se puede partir entre la mañana y la tarde: lo que se '
        + 'entrena es justamente no parar. Marca "Bici" y "Correr" en la misma franja de algún día '
        + '(el más largo que tengas) o tu entrenador no podrá programarte ninguno.';
    aviso.classList.remove('hidden');
}

function toggleMenstrualNotes() {
    const optedIn = document.getElementById('menstrual_tracking_opt_in').checked;
    document.getElementById('menstrualNotesGroup').classList.toggle('hidden', !optedIn);
}

// Garmin auto-import — lightweight connect that only pulls fields Garmin
// already measured (fisiología + sueño), never the full activity extraction.
async function garminAutoImport() {
    const email = document.getElementById('garmin_email').value;
    const password = document.getElementById('garmin_password').value;
    const statusEl = document.getElementById('garminImportStatus');
    if (!email || !password) {
        statusEl.textContent = 'Ingresa tu email y contraseña de Garmin primero.';
        return;
    }
    statusEl.textContent = 'Conectando con Garmin...';
    try {
        const res = await fetch('/api/profile/garmin-import', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({garmin_email: email, garmin_password: password})
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.detail || 'Error desconocido');

        const imported = data.imported || {};
        window.garminAutofilledFields = new Set(Object.keys(imported));
        Object.entries(imported).forEach(([key, value]) => {
            const el = document.getElementById(key);
            if (el) el.value = value;
        });
        applyGarminBadges();
        updateProfileCompleteness();
        autosaveProfileDebounced();
        const n = Object.keys(imported).length;
        statusEl.textContent = n
            ? `✅ ${n} campo(s) auto-completados desde Garmin. Revísalos y ajusta si hace falta.`
            : 'Conectado, pero Garmin no tenía datos nuevos para prellenar.';

        renderGarminDaySuggestion(data.suggested_long_days || [], data.suggested_available_days || []);
    } catch (e) {
        statusEl.textContent = `❌ ${e.message}`;
    }
}

// Garmin's "preferred long training days" / "available training days" are
// suggestions, not auto-applied — silently toggling which days the athlete
// trains would override a real schedule decision (see /api/profile/garmin-import
// in web_app/server.py). Only offer to mark the days as available; the
// athlete still has to fill in the actual time block themselves.
function renderGarminDaySuggestion(longDays, availableDays) {
    const el = document.getElementById('garminDaySuggestion');
    if (!el) return;
    const days = [...new Set([...availableDays, ...longDays])];
    if (!days.length) { el.classList.add('hidden'); el.innerHTML = ''; return; }
    const longLabel = longDays.length ? ` (día(s) largo(s) sugerido(s): ${longDays.join(', ')})` : '';
    el.classList.remove('hidden');
    el.innerHTML = `
        Garmin sugiere estos días de entrenamiento${longLabel}: <strong>${days.join(', ')}</strong>.
        <button type="button" class="btn btn-outline btn-sm" id="btnApplyGarminDays">Marcar estos días como disponibles</button>
    `;
    document.getElementById('btnApplyGarminDays').addEventListener('click', () => {
        days.forEach(day => {
            const id = day.toLowerCase();
            const chk = document.getElementById(`chk_${id}`);
            if (chk && !chk.checked) { chk.checked = true; toggleDay(id); }
        });
        el.classList.add('hidden');
        document.querySelector('.profile-tab[data-tab="objetivos"]')?.click();
        updateProfileCompleteness();
    });
}

function applyGarminBadges() {
    document.querySelectorAll('.garmin-badge').forEach(b => b.remove());
    (window.garminAutofilledFields || new Set()).forEach(key => {
        const el = document.getElementById(key);
        const label = el?.closest('.input-group')?.querySelector('label');
        if (label && !label.querySelector('.garmin-badge')) {
            const badge = document.createElement('span');
            badge.className = 'garmin-badge';
            badge.textContent = '🔗 Garmin';
            label.appendChild(badge);
        }
    });
}

// Injuries (Salud section)
function addInjuryField(inj = {}) {
    const container = document.getElementById('injuriesContainer');
    const div = document.createElement('div');
    div.className = 'grid-2 injury-row';
    div.innerHTML = `
        <div class="input-group">
            <label>Ubicación</label>
            <input type="text" class="inj-location" placeholder="Ej. Rodilla" value="${inj.location || ''}">
        </div>
        <div class="input-group">
            <label>Lateralidad</label>
            <select class="inj-laterality">
                <option value="">--</option>
                <option value="izquierda" ${inj.laterality === 'izquierda' ? 'selected' : ''}>Izquierda</option>
                <option value="derecha" ${inj.laterality === 'derecha' ? 'selected' : ''}>Derecha</option>
                <option value="bilateral" ${inj.laterality === 'bilateral' ? 'selected' : ''}>Bilateral</option>
            </select>
        </div>
        <div class="input-group">
            <label>Inicio</label>
            <input type="text" class="inj-onset" placeholder="Ej. Hace 2 semanas" value="${inj.onset || ''}">
        </div>
        <div class="input-group">
            <label>Escala de Dolor (0-10)</label>
            <input type="number" min="0" max="10" class="inj-pain" value="${inj.pain_scale ?? ''}">
        </div>
        <div class="input-group full-width">
            <label>Factores Agravantes / que Alivian</label>
            <input type="text" class="inj-aggravating" value="${inj.aggravating_factors || ''}">
        </div>
        <div class="input-group full-width">
            <label>Tratamiento Previo/Actual</label>
            <input type="text" class="inj-treatment" value="${inj.treatment || ''}">
        </div>
        <div class="full-width">
            <button type="button" class="btn btn-outline btn-sm inj-remove">Quitar Lesión</button>
        </div>
    `;
    div.querySelector('.inj-remove').addEventListener('click', () => { div.remove(); autosaveProfileDebounced(); });
    container.appendChild(div);
}

// Medical re-evaluation trigger (PAR-Q+ best practice: re-screen ~every 90
// days or whenever the structured medical fields actually change).
function medicalSnapshotJSON(profile) {
    const obj = {};
    MEDICAL_SNAPSHOT_FIELDS.forEach(k => { obj[k] = profile[k]; });
    return JSON.stringify(obj);
}

function updateReevalBanner() {
    const banner = document.getElementById('reevalBanner');
    const dot = document.getElementById('reevalDot');
    if (!banner) return;
    const last = currentProfile.last_medical_evaluation;
    const daysSince = last ? (Date.now() - new Date(last).getTime()) / 86400000 : Infinity;
    const changed = currentProfile.medical_snapshot_hash !== medicalSnapshotJSON(currentProfile);
    const needsReeval = !last || daysSince > 90 || changed;
    banner.classList.toggle('hidden', !needsReeval);
    if (dot) dot.classList.toggle('hidden', !needsReeval);
    const lastLabel = document.getElementById('lastMedicalEvalLabel');
    if (lastLabel) {
        lastLabel.textContent = last
            ? `Última evaluación: ${new Date(last).toLocaleDateString()}`
            : 'Aún no se ha revisado tu ficha de salud.';
    }
}

// Days Grid — up to 2 real time blocks per day (start/end clock time +
// allowed sports per block), so the planner knows exactly when a session is
// possible (heat, pool hours, work schedule) and can schedule real two-a-days
// instead of a single vague "hours available" number.
const daysOfWeek = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
// Must match SPORT_LABEL_TO_KEY in web_app/server.py exactly — these labels
// are sent as-is and matched server-side into canonical sport keys.
// 'Trail' es TERRENO, no un deporte aparte: marca las franjas en las que este
// atleta tiene monte de verdad. Casi nadie lo tiene a diario -- lo tiene el
// domingo por la mañana, y entre semana escaleras o una cuesta. Decirlo por
// franja es lo que deja al entrenador saber cuándo mandarle al sendero y
// cuándo simularlo.
const SPORT_LABELS = ['Correr', 'Trail', 'Bici', 'Nadar', 'Fuerza', 'Cualquiera'];
// Cuantas ventanas de tiempo distintas puede declarar el atleta en un dia.
// Debe coincidir con MAX_BLOCKS_PER_DAY en web_app/server.py.
//
// Un bloque NO es una sesion: dentro de un bloque de 05:30-07:00 caben una
// carrera de 45' y una natacion de 40'. Los bloques son las ventanas en las
// que la vida del atleta le deja entrenar; cuantas sesiones entran en cada
// una lo decide el tiempo, no este numero.
const MAX_BLOCKS_PER_DAY = 3;

function _blockHtml(dayId, blockIdx) {
    const sportChecks = SPORT_LABELS.map(label => `
        <label class="sport-chk">
            <input type="checkbox" class="spt_${dayId}_${blockIdx}" value="${label}"> ${label}
        </label>
    `).join('');
    const removeBtn = blockIdx > 0
        ? `<button type="button" class="btn btn-outline btn-sm btn-remove-block" data-day="${dayId}" data-block="${blockIdx}">Quitar bloque</button>`
        : '';
    return `
        <div class="time-block" id="block_${dayId}_${blockIdx}">
            <div class="time-block-times">
                <input type="time" id="start_${dayId}_${blockIdx}" aria-label="Hora inicio">
                <span>a</span>
                <input type="time" id="end_${dayId}_${blockIdx}" aria-label="Hora fin">
                <span class="block-hours-preview" id="hrsPreview_${dayId}_${blockIdx}"></span>
            </div>
            <div class="sport-checks">${sportChecks}</div>
            ${removeBtn}
        </div>
    `;
}

function initDaysGrid() {
    const container = document.getElementById('daysContainer');
    daysOfWeek.forEach(day => {
        const id = day.toLowerCase();
        const html = `
            <div class="day-card" id="card_${id}">
                <label class="day-header">
                    <input type="checkbox" id="chk_${id}" onchange="toggleDay('${id}')">
                    ${day}
                </label>
                <div class="day-inputs" id="inputs_${id}">
                    <div id="blocksContainer_${id}">${_blockHtml(id, 0)}</div>
                    <button type="button" class="btn btn-outline btn-sm btn-add-block" id="btnAddBlock_${id}" data-day="${id}">+ Agregar otra franja horaria</button>
                    <p class="block-hint">Marca en cada franja TODOS los deportes que puedes hacer en ella. Si en una misma franja te da tiempo a dos cosas (correr y nadar, por ejemplo), el entrenador puede ponerte las dos.</p>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        document.getElementById(`btnAddBlock_${id}`).addEventListener('click', () => addBlock(id));
        setupBlockListeners(id, 0);
    });
    container.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-remove-block');
        if (btn) removeBlock(btn.dataset.day, Number(btn.dataset.block));
    });
}

/* Anade la siguiente franja libre del dia. Antes era `addSecondBlock` y solo
   sabia del indice 1; ahora busca el primer hueco, que es lo unico que cambia
   al pasar de dos franjas a tres. */
function addBlock(dayId, silencioso) {
    for (let i = 1; i < MAX_BLOCKS_PER_DAY; i++) {
        if (document.getElementById(`block_${dayId}_${i}`)) continue;
        document.getElementById(`blocksContainer_${dayId}`).insertAdjacentHTML('beforeend', _blockHtml(dayId, i));
        setupBlockListeners(dayId, i);
        _syncAddBlockButton(dayId);
        if (!silencioso) updateProfileCompleteness();
        return i;
    }
    return null;
}

function removeBlock(dayId, blockIdx) {
    document.getElementById(`block_${dayId}_${blockIdx || 1}`)?.remove();
    _syncAddBlockButton(dayId);
    updateProfileCompleteness();
    autosaveProfileDebounced();
}

function removeExtraBlocks(dayId) {
    for (let i = 1; i < MAX_BLOCKS_PER_DAY; i++) {
        document.getElementById(`block_${dayId}_${i}`)?.remove();
    }
    _syncAddBlockButton(dayId);
}

/* El boton desaparece cuando ya no queda hueco. */
function _syncAddBlockButton(dayId) {
    const usados = document.querySelectorAll(`#blocksContainer_${dayId} .time-block`).length;
    document.getElementById(`btnAddBlock_${dayId}`)?.classList.toggle('hidden', usados >= MAX_BLOCKS_PER_DAY);
}

function setupBlockListeners(dayId, blockIdx) {
    // "Cualquiera" and a specific sport are mutually exclusive within a
    // block — previously both could be checked at once and the specific
    // sport got silently discarded server-side with no indication why.
    const anyChk = document.querySelector(`.spt_${dayId}_${blockIdx}[value="Cualquiera"]`);
    const specificChks = Array.from(document.querySelectorAll(`.spt_${dayId}_${blockIdx}`)).filter(el => el.value !== 'Cualquiera');
    anyChk?.addEventListener('change', () => {
        if (anyChk.checked) specificChks.forEach(el => { el.checked = false; });
        updateBrickFeasibility();
    });
    specificChks.forEach(el => el.addEventListener('change', () => {
        if (el.checked && anyChk) anyChk.checked = false;
        updateBrickFeasibility();
    }));

    // Live hours preview + a soft warning above 6h (likely a typo, not a
    // real single training block).
    const startEl = document.getElementById(`start_${dayId}_${blockIdx}`);
    const endEl = document.getElementById(`end_${dayId}_${blockIdx}`);
    const preview = document.getElementById(`hrsPreview_${dayId}_${blockIdx}`);
    const updatePreview = () => {
        const hrs = _blockHours(startEl.value, endEl.value);
        if (hrs === null) { preview.textContent = ''; return; }
        preview.textContent = hrs > 6 ? `⚠️ ${hrs}h — ¿seguro?` : `${hrs}h`;
        preview.classList.toggle('warning', hrs > 6);
    };
    startEl.addEventListener('change', updatePreview);
    endEl.addEventListener('change', updatePreview);
}

function _blockHours(start, end) {
    if (!start || !end) return null;
    const [sh, sm] = start.split(':').map(Number);
    const [eh, em] = end.split(':').map(Number);
    const hrs = ((eh * 60 + em) - (sh * 60 + sm)) / 60;
    return hrs > 0 ? Math.round(hrs * 100) / 100 : null;
}

function getSelectedSports(dayId, blockIdx) {
    return Array.from(document.querySelectorAll(`.spt_${dayId}_${blockIdx}:checked`)).map(el => el.value);
}

function setSelectedSports(dayId, blockIdx, labels) {
    const selected = new Set(Array.isArray(labels) ? labels : []);
    document.querySelectorAll(`.spt_${dayId}_${blockIdx}`).forEach(el => {
        el.checked = selected.has(el.value);
    });
}

function getDayBlocks(dayId) {
    const blocks = [];
    for (let i = 0; i < MAX_BLOCKS_PER_DAY; i++) {
        const startEl = document.getElementById(`start_${dayId}_${i}`);
        if (!startEl) continue;
        const start = startEl.value, end = document.getElementById(`end_${dayId}_${i}`).value;
        if (!start || !end) continue;
        blocks.push({ start, end, sports: getSelectedSports(dayId, i) });
    }
    return blocks;
}

function setDayBlocks(dayId, blocks) {
    removeExtraBlocks(dayId);
    document.getElementById(`start_${dayId}_0`).value = '';
    document.getElementById(`end_${dayId}_0`).value = '';
    setSelectedSports(dayId, 0, []);
    (blocks || []).slice(0, MAX_BLOCKS_PER_DAY).forEach((b, i) => {
        if (i > 0) addBlock(dayId, true);
        document.getElementById(`start_${dayId}_${i}`).value = b.start || '';
        document.getElementById(`end_${dayId}_${i}`).value = b.end || '';
        setSelectedSports(dayId, i, b.sports);
        const preview = document.getElementById(`hrsPreview_${dayId}_${i}`);
        const hrs = _blockHours(b.start, b.end);
        if (preview) {
            preview.textContent = hrs !== null ? `${hrs}h`
                : b.legacyHours ? `(antes: ${b.legacyHours}h sin horario — agrega el horario real)`
                : '';
        }
    });
}

// Converts a legacy single-hours-number day (profiles saved before this
// time-block redesign) into the new block shape WITHOUT fabricating a start
// time we don't actually have — leaves start/end blank so the sports
// selection stays visible and the user is nudged to fill in the real time,
// while the underlying legacy hours keep working server-side via
// web_app/server.py's _day_availability fallback until they do.
function legacyDayToBlocks(hours, sports) {
    if (!hours && (!sports || !sports.length)) return [];
    return [{ start: '', end: '', sports: sports || [], legacyHours: hours || null }];
}

window.toggleDay = function(id) {
    const isChecked = document.getElementById(`chk_${id}`).checked;
    const card = document.getElementById(`card_${id}`);
    if (isChecked) card.classList.add('active');
    else card.classList.remove('active');
    updateBrickFeasibility();
}

// Competitions
//
// El catálogo de distancias lo sirve el backend (`/api/race-distances`), no
// está escrito aquí: es la MISMA lista que usa el evaluador de metas. Dos
// listas que puedan divergir devolverían "sin referencia" para una distancia
// que el atleta sí eligió, que es justo el fallo que esto viene a arreglar.
let RACE_DISTANCE_GROUPS = null;

async function loadRaceDistances() {
    if (RACE_DISTANCE_GROUPS) return RACE_DISTANCE_GROUPS;
    try {
        const res = await fetch('/api/race-distances');
        if (res.ok) RACE_DISTANCE_GROUPS = (await res.json()).groups || [];
    } catch { RACE_DISTANCE_GROUPS = []; }
    return RACE_DISTANCE_GROUPS || [];
}

/* Repinta los desplegables ya en pantalla conservando lo elegido.

   `data-guardada` y no `sel.value`, y ahí estaba el fallo: el catálogo de
   distancias lo sirve el backend, así que en la primera carga del perfil las
   opciones todavía no existen. Un <select> al que se le pide un valor que no
   está entre sus opciones se queda VACÍO -- y a partir de ahí `sel.value` es
   "", el repintado lo daba por elegido, y el siguiente autoguardado escribía
   distancia vacía en el perfil. No es que se "deseleccionara" en pantalla: se
   estaba BORRANDO del perfil, y el evaluador de metas se quedaba sin nada con
   qué comparar el tiempo objetivo. */
function refreshRaceDistanceSelects() {
    document.querySelectorAll('select.comp-distance').forEach(sel => {
        const elegido = sel.value || sel.dataset.guardada || '';
        sel.innerHTML = raceDistanceOptions(elegido);
        sel.value = elegido;
        sel.dataset.guardada = elegido;
    });
}

function raceDistanceOptions(selected) {
    const marcado = v => v === (selected || '') ? ' selected' : '';
    let html = `<option value=""${marcado('')}>Otra / no aplica</option>`;
    const conocidas = new Set();
    for (const grupo of (RACE_DISTANCE_GROUPS || [])) {
        html += `<optgroup label="${escapeHtml(grupo.group)}">`;
        for (const o of grupo.options) {
            conocidas.add(o.value);
            html += `<option value="${escapeHtml(o.value)}"${marcado(o.value)}>${escapeHtml(o.label)}</option>`;
        }
        html += '</optgroup>';
    }
    // Red de seguridad: si el catálogo no ha llegado (o esa distancia ya no
    // está en él), se le hace una opción para que el valor del atleta no
    // desaparezca sólo porque el desplegable no supo representarlo.
    if (selected && !conocidas.has(selected)) {
        html += `<option value="${escapeHtml(selected)}" selected>${escapeHtml(selected)}</option>`;
    }
    return html;
}

function addCompetitionField(comp = {}) {
    const container = document.getElementById('competitionsContainer');
    const idx = container.children.length;
    
    const div = document.createElement('div');
    div.className = 'grid-2 comp-row';
    div.style.marginBottom = '1rem';
    div.style.padding = '1rem';
    div.style.border = '1px dashed var(--border)';
    
    div.innerHTML = `
        <div class="input-group">
            <label>Nombre Competición</label>
            <input type="text" class="comp-name" value="${comp.name || ''}">
        </div>
        <div class="input-group">
            <label>Fecha</label>
            <input type="date" class="comp-date" value="${comp.date || ''}">
        </div>
        <div class="input-group">
            <label>Prioridad</label>
            <select class="comp-priority">
                <option value="A" ${comp.priority==='A'?'selected':''}>Prioridad A</option>
                <option value="B" ${comp.priority==='B'?'selected':''}>Prioridad B</option>
                <option value="C" ${comp.priority==='C'?'selected':''}>Prioridad C</option>
            </select>
        </div>
        <div class="input-group">
            <label>Distancia</label>
            <select class="comp-distance" data-guardada="${escapeHtml(comp.distance || '')}">${raceDistanceOptions(comp.distance)}</select>
        </div>
        <div class="input-group comp-elevation-group">
            <label>Desnivel positivo (m)</label>
            <input type="number" class="comp-elevation" min="0" step="50" placeholder="Ej. 2400"
                   value="${comp.elevation_gain_m || ''}">
            <small style="color:var(--text-muted)">En montaña el desnivel define la carrera más que la distancia.</small>
        </div>
        <div class="input-group">
            <label>Tiempo Objetivo</label>
            <input type="text" class="comp-time" placeholder="HH:MM:SS" value="${comp.target_time || ''}">
            <small class="comp-distance-hint" style="display:none;color:var(--warning,#b45309)">Ponle la distancia para que el entrenador pueda decirte si esta meta es alcanzable. Sin ella no hay con qué comparar el tiempo.</small>
        </div>
    `;
    container.appendChild(div);
    // v6 Track 4: a target time with no distance is the one thing an athlete
    // can fix in two seconds that silently disables goal evaluation for their
    // whole season -- three real competitions came back "sin referencia" for
    // exactly this. Shown here rather than only in the generation log, which
    // the athlete has no reason to read.
    div.querySelector('.comp-time').addEventListener('input', () => updateCompetitionHint(div));
    div.querySelector('.comp-distance').addEventListener('change', (e) => {
        // Elegir "Otra / no aplica" a propósito TIENE que poder vaciarla: sin
        // esta línea la red de seguridad de más abajo le devolvería la
        // distancia vieja y el atleta no podría quitarla nunca.
        e.target.dataset.guardada = e.target.value;
        updateCompetitionHint(div);
    });
    updateCompetitionHint(div);
}

function updateCompetitionHint(row) {
    const hint = row.querySelector('.comp-distance-hint');
    if (!hint) return;
    const hasTarget = (row.querySelector('.comp-time')?.value || '').trim().length > 0;
    const hasDistance = (row.querySelector('.comp-distance')?.value || '').trim().length > 0;
    hint.style.display = hasTarget && !hasDistance ? 'block' : 'none';
}

// Profiles API
async function loadProfiles() {
    try {
        const res = await fetch('/api/profiles');
        const profiles = await res.json();
        const select = document.getElementById('profileSelect');
        select.innerHTML = '<option value="">-- Nuevo Perfil --</option>';
        window.profilesDb = profiles;
        const names = Object.keys(profiles);
        for (const name of names) {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            select.appendChild(opt);
        }

        // Open straight into an athlete instead of an empty form: the last one
        // used, or the only one there is. A dashboard that greets you with
        // "select a profile" is a wizard wearing a dashboard's clothes.
        //
        // But ONLY when no athlete is loaded yet. Setting select.innerHTML
        // above clears select.value, so the old `!select.value` test was true
        // on EVERY call -- and saveProfile() ends by calling loadProfiles().
        // The result: every autosave re-ran onProfileSelect(), which repopulates
        // the whole form from the last SAVED copy. Anything typed and not yet
        // saved was thrown away mid-keystroke, most visibly the health
        // consultation in progress, which snapped back to the previous
        // conversation and lost the new one.
        const remembered = localStorage.getItem('lastProfile');
        const preferred = names.includes(remembered) ? remembered : (names.length === 1 ? names[0] : '');
        if (currentProfile?.name && names.includes(currentProfile.name)) {
            // Same athlete still loaded: restore the dropdown's selection only.
            select.value = currentProfile.name;
        } else if (preferred) {
            select.value = preferred;
            onProfileSelect();
        }
    } catch(e) { console.error(e); }
}

function onProfileSelect() {
    const name = document.getElementById('profileSelect').value;
    if (!name || !window.profilesDb[name]) {
        document.getElementById('profileForm').reset();
        document.getElementById('competitionsContainer').innerHTML = '';
        document.getElementById('injuriesContainer').innerHTML = '';
        currentProfile = {};
        chatHistory = [];
        document.getElementById('chatHistory').innerHTML = '';
        window.garminAutofilledFields = new Set();
        applyGarminBadges();
        toggleMenstrualNotes();
        updateReevalBanner();
        updateProfileCompleteness();
        setSaveIndicator('Escribe un nombre para empezar a guardar automáticamente.');
        return;
    }
    const p = window.profilesDb[name];
    currentProfile = { ...p };
    localStorage.setItem('lastProfile', name);
    setSaveIndicator('Todo guardado ✓');
    // Each athlete has their own plan; switching must not leave the previous
    // athlete's plan on screen.
    fetchCurrentPlan();
    window.__loadingProfile = true;

    // Fill every simple scalar field generically
    SIMPLE_PROFILE_FIELDS.forEach(k => {
        const el = document.getElementById(k);
        if (el) el.value = (p[k] ?? '') === null ? '' : p[k] ?? '';
    });
    CHECKBOX_PROFILE_FIELDS.forEach(k => {
        const el = document.getElementById(k);
        if (el) el.checked = !!p[k];
    });
    // ai_mode is no longer a form field. v7 retired the Lite/Pro selector --
    // it shipped with Lite pre-selected, which is why the product's first
    // three real runs were all Lite and Pro never executed once. The field
    // survives in the profile as a cost kill-switch, edited by hand in
    // profiles.json; the UI deliberately neither reads nor writes it, so a
    // save from here can't quietly flip it back.

    // PAR-Q+ flags
    const parQ = p.par_q_flags || {};
    PAR_Q_KEYS.forEach(k => {
        const el = document.getElementById(`parq_${k}`);
        if (el) el.checked = !!parQ[k];
    });

    // Health disclaimer. The DATE is what makes this worth storing: an
    // acknowledgement nobody can date is an acknowledgement nobody can rely on.
    const disclaimerEl = document.getElementById('health_disclaimer_accepted');
    if (disclaimerEl) disclaimerEl.checked = !!p.health_disclaimer_accepted;
    const disclaimerDateEl = document.getElementById('healthDisclaimerDate');
    if (disclaimerDateEl) {
        disclaimerDateEl.textContent = p.health_disclaimer_accepted_at
            ? `Aceptado el ${new Date(p.health_disclaimer_accepted_at).toLocaleDateString()}.`
            : '';
    }

    // recent_prs is a dict with fixed keys for the common marks (so prompts
    // can reference them precisely) plus a free-text "notes" catch-all.
    const prs = p.recent_prs || {};
    PR_FIELD_KEYS.forEach(key => {
        const el = document.getElementById(`pr_${key}`);
        if (el) el.value = prs[key] || '';
    });
    const prsEl = document.getElementById('recent_prs_notes');
    if (prsEl) prsEl.value = prs.notes || '';


    // Fill days: prefer the new day_time_blocks (real clock times); fall
    // back to the legacy single hours_per_day number + activities_per_day
    // list (as one block with no specific time) for profiles saved before
    // this redesign — see legacyDayToBlocks().
    daysOfWeek.forEach(day => {
        const id = day.toLowerCase();
        document.getElementById(`chk_${id}`).checked = false;
        toggleDay(id);
        setDayBlocks(id, []);
    });
    if (p.available_days) {
        p.available_days.forEach(day => {
            const id = day.toLowerCase();
            const chk = document.getElementById(`chk_${id}`);
            if (!chk) return;
            chk.checked = true;
            toggleDay(id);
            const blocks = (p.day_time_blocks && p.day_time_blocks[day] && p.day_time_blocks[day].length)
                ? p.day_time_blocks[day]
                : legacyDayToBlocks(p.hours_per_day?.[day], p.activities_per_day?.[day]);
            setDayBlocks(id, blocks);
        });
    }

    // Fill competitions
    document.getElementById('competitionsContainer').innerHTML = '';
    if (p.competitions) {
        p.competitions.forEach(c => addCompetitionField(c));
    }

    // Fill injuries
    document.getElementById('injuriesContainer').innerHTML = '';
    if (p.current_injuries) {
        p.current_injuries.forEach(inj => addInjuryField(inj));
    }

    // Health intake chat history + Garmin badges + re-eval banner
    chatHistory = Array.isArray(p.doctor_chat_history) ? [...p.doctor_chat_history] : [];
    const chatEl = document.getElementById('chatHistory');
    chatEl.innerHTML = '';
    if (chatHistory.length) {
        chatHistory.forEach(m => appendChatDom(m.role, m.content));
    } else {
        chatEl.innerHTML = '<div class="chat-message assistant"><div class="msg-content">Haz clic en "Iniciar / Reevaluar" para que la IA revise tu ficha.</div></div>';
    }

    window.garminAutofilledFields = new Set(p.garmin_autofilled_fields || []);
    applyGarminBadges();
    toggleMenstrualNotes();
    updateReevalBanner();
    updateProfileCompleteness();
    updateBrickFeasibility();
    window.__loadingProfile = false;
}

function gatherProfileFromForm() {
    const profile = {
        available_days: [], hours_per_day: {}, activities_per_day: {}, day_time_blocks: {},
        competitions: [], current_injuries: [],
    };

    SIMPLE_PROFILE_FIELDS.forEach(k => {
        const el = document.getElementById(k);
        if (!el) return;
        const raw = el.value;
        profile[k] = NUMBER_PROFILE_FIELDS.has(k) ? (parseFloat(raw) || null) : raw;
    });
    CHECKBOX_PROFILE_FIELDS.forEach(k => {
        const el = document.getElementById(k);
        if (el) profile[k] = el.checked;
    });

    profile.par_q_flags = {};
    PAR_Q_KEYS.forEach(k => {
        const el = document.getElementById(`parq_${k}`);
        if (el) profile.par_q_flags[k] = el.checked;
    });

    const disclaimerEl = document.getElementById('health_disclaimer_accepted');
    if (disclaimerEl) {
        profile.health_disclaimer_accepted = disclaimerEl.checked;
        // Stamped only on the transition to accepted, so re-saving the profile
        // does not keep refreshing the date and make an old acknowledgement
        // look like it was given today.
        profile.health_disclaimer_accepted_at = disclaimerEl.checked
            ? (currentProfile.health_disclaimer_accepted_at || new Date().toISOString())
            : null;
    }

    const prsEl = document.getElementById('recent_prs_notes');
    profile.recent_prs = { notes: prsEl ? prsEl.value : '' };
    PR_FIELD_KEYS.forEach(key => {
        const el = document.getElementById(`pr_${key}`);
        if (el && el.value.trim()) profile.recent_prs[key] = el.value.trim();
    });

    // day_time_blocks (real clock times, up to 2/day) is the source of
    // truth; hours_per_day/activities_per_day are derived aggregates kept
    // in sync so every legacy consumer of those two fields (weekly-hours
    // total, old profiles' code paths) keeps working unchanged.
    let totalHrs = 0;
    daysOfWeek.forEach(day => {
        const id = day.toLowerCase();
        if (!document.getElementById(`chk_${id}`).checked) return;
        profile.available_days.push(day);
        const blocks = getDayBlocks(id);

        if (blocks.length) {
            profile.day_time_blocks[day] = blocks;
            const dayHours = blocks.reduce((sum, b) => sum + (_blockHours(b.start, b.end) || 0), 0);
            profile.hours_per_day[day] = dayHours;
            // "Cualquiera" in any block makes the whole day unconstrained —
            // same semantics as the server's _canonical_sports_for_day.
            const anyUnconstrained = blocks.some(b => b.sports.includes('Cualquiera'));
            profile.activities_per_day[day] = anyUnconstrained
                ? ['Cualquiera']
                : [...new Set(blocks.flatMap(b => b.sports))];
            totalHrs += dayHours;
        } else {
            // No real time block filled in for this day yet (still showing
            // the "antes: Xh sin horario" legacy hint) — preserve whatever
            // was already saved instead of silently zeroing it out just
            // because the athlete hasn't migrated this specific day yet.
            const legacyHours = parseFloat(currentProfile.hours_per_day?.[day]) || 0;
            profile.day_time_blocks[day] = currentProfile.day_time_blocks?.[day] || [];
            profile.hours_per_day[day] = legacyHours;
            profile.activities_per_day[day] = currentProfile.activities_per_day?.[day] || [];
            totalHrs += legacyHours;
        }
    });
    profile.available_hours = totalHrs;

    document.querySelectorAll('.comp-row').forEach(row => {
        profile.competitions.push({
            name: row.querySelector('.comp-name').value,
            date: row.querySelector('.comp-date').value,
            priority: row.querySelector('.comp-priority').value,
            distance: (row.querySelector('.comp-distance').value
                       || row.querySelector('.comp-distance').dataset.guardada || ''),
            // El desnivel se guarda como numero o no se guarda: un "" en un
            // campo que el evaluador lee como metros lo dejaria en cero, y
            // cero desnivel declarado y desnivel sin declarar son cosas
            // distintas para una carrera de montana.
            elevation_gain_m: Number(row.querySelector('.comp-elevation')?.value) || null,
            target_time: row.querySelector('.comp-time').value
        });
    });

    document.querySelectorAll('.injury-row').forEach(row => {
        const painRaw = row.querySelector('.inj-pain').value;
        profile.current_injuries.push({
            location: row.querySelector('.inj-location').value,
            laterality: row.querySelector('.inj-laterality').value,
            onset: row.querySelector('.inj-onset').value,
            pain_scale: painRaw !== '' ? parseInt(painRaw, 10) : null,
            aggravating_factors: row.querySelector('.inj-aggravating').value,
            treatment: row.querySelector('.inj-treatment').value,
        });
    });

    // Fields not represented by a form input, carried over from the
    // in-memory profile (intake report/history, Garmin auto-import badges,
    // planning_context is a one-shot adjustment note, not a saved setting).
    profile.doctor_performance_report = currentProfile.doctor_performance_report || '';
    profile.analysis_context = currentProfile.analysis_context || profile.doctor_performance_report;
    profile.doctor_chat_history = currentProfile.doctor_chat_history || [];
    profile.last_medical_evaluation = currentProfile.last_medical_evaluation || null;
    profile.medical_snapshot_hash = currentProfile.medical_snapshot_hash || null;
    profile.garmin_autofilled_fields = Array.from(window.garminAutofilledFields || []);
    // Deliberately NOT persisted: it applies to one generation only.
    profile.planning_context = '';

    return profile;
}

async function saveProfile(silent) {
    currentProfile = gatherProfileFromForm();

    try {
        await fetch('/api/profiles', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(currentProfile)
        });
        if (!silent) alert('Perfil guardado exitosamente.');
        loadProfiles();
    } catch(e) { console.error(e); }
}

// Chat Logic
function appendChatDom(role, msg) {
    const history = document.getElementById('chatHistory');
    const div = document.createElement('div');
    div.className = `chat-message ${role}`;

    // chatMarkdown, no marked.parse: el segundo convierte `##` en encabezados
    // y `---` en reglas horizontales, que dentro de una burbuja de chat
    // producen un documento en vez de una conversación -- y además deja pasar
    // HTML crudo del texto de origen. Ver chatMarkdown.
    const content = chatMarkdown(msg);

    div.innerHTML = `<div class="msg-content">${content}</div>`;
    history.appendChild(div);
    history.scrollTop = history.scrollHeight;
}

function appendChat(role, msg) {
    appendChatDom(role, msg);
    chatHistory.push({role: role, content: msg});
}

async function startHealthIntake() {
    document.getElementById('chatInput').disabled = false;
    document.getElementById('btnSendChat').disabled = false;
    document.getElementById('btnStartDoctor').disabled = true;

    // Clear history — this is also the "Reevaluar" entry point, so a fresh
    // consultation always starts from the current structured profile data.
    document.getElementById('chatHistory').innerHTML = '';
    chatHistory = [];

    appendChat('assistant', "Revisando tu ficha... Dame un momento.");
    await callHealthIntake();
}

async function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const text = input.value.trim();
    if (!text) return;
    
    appendChat('user', text);
    input.value = '';
    await callHealthIntake();
}

async function callHealthIntake() {
    // El asistente de salud ("el Doctor") se retiró en la Beta 3.0 (F0): su ruta
    // /api/health-intake ya no existe. Esta pantalla vieja sólo lo dice.
    appendChat('assistant', "El asistente de salud se retiró. Rellena tus datos de salud en esta pestaña y guarda.");
    document.getElementById('btnStartDoctor').disabled = false;
    document.getElementById('chatInput').disabled = true;
}

// Generation Logic
async function startGeneration() {
    // Add prompts
    currentProfile.planning_context = pendingAdjustment;
    pendingAdjustment = '';
    // Extraction windows are decided in services/garmin/extraction_windows.py
    // now, not by the athlete -- see "Qué historial lee tu entrenador".
    
    document.getElementById('executionProgress').classList.remove('hidden');
    setGenerationButtonsDisabled(true);

    try {
        const res = await fetch('/api/generate', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(currentProfile)
        });
        if (!res.ok) {
            // Surface the real reason (e.g. "An analysis is already running"
            // or a Pydantic validation error) instead of a generic message
            // that gives no clue what to fix.
            let detail = `HTTP ${res.status}`;
            try {
                const data = await res.json();
                detail = data.detail ? (typeof data.detail === 'string' ? data.detail : JSON.stringify(data.detail)) : detail;
            } catch (_) { /* body wasn't JSON */ }
            throw new Error(detail);
        }

        generationInterval = setInterval(pollStatus, 2000);
    } catch(e) {
        alert(`Error al iniciar generación: ${e.message}`);
        document.getElementById('executionProgress').classList.add('hidden');
        setGenerationButtonsDisabled(false);
    }
}

// Plan Maestro v4 Track B: generates only the next 7 days, against fresh
// short-window Garmin data, reusing the season plan already on disk. Shares
// execution_status/pollStatus with startGeneration -- the backend only ever
// runs one generation of either kind at a time.
// --- Pre-week check-in -----------------------------------------------------
// Four Hooper items (sleep / fatigue / stress / soreness, 1-7 where 7 is worse
// on all four -- one direction for all of them so a rendered "5" never means
// "good" in one row and "bad" in the next), plus what no sensor sees: a
// localised niggle, what got in the way last week, and what the week ahead
// looks like. See services/ai/checkin.py for why there is no rule behind it.
const HOOPER_ITEMS = [
    { key: 'sleep', label: 'Calidad de sueño', low: 'Muy buena', high: 'Muy mala' },
    { key: 'fatigue', label: 'Fatiga', low: 'Ninguna', high: 'Agotado' },
    { key: 'stress', label: 'Estrés', low: 'Ninguno', high: 'Muchísimo' },
    { key: 'soreness', label: 'Dolor muscular', low: 'Ninguno', high: 'Mucho' },
];

function askCheckin(profileName) {
    return new Promise(resolve => {
        const modal = document.getElementById('checkinModal');
        const body = document.getElementById('checkinBody');
        if (!modal || !body) { resolve(true); return; }

        const scale = (key, label, low, high) => `
            <div class="checkin-item">
                <label for="checkin_${key}">${label}</label>
                <div class="checkin-scale">
                    <span>${low}</span>
                    <input type="range" id="checkin_${key}" min="1" max="7" value="4"
                           oninput="document.getElementById('checkin_${key}_out').textContent = this.value">
                    <span>${high}</span>
                    <output id="checkin_${key}_out">4</output>
                </div>
            </div>`;

        body.innerHTML = HOOPER_ITEMS.map(i => scale(i.key, i.label, i.low, i.high)).join('')
            + scale('motivation', 'Ganas de entrenar', 'Ninguna', 'Muchas')
            + `
            <div class="checkin-item">
                <label for="checkin_niggle_location">¿Alguna molestia? ¿Dónde?</label>
                <input type="text" id="checkin_niggle_location" placeholder="Ej. tendón de Aquiles izquierdo">
            </div>
            <div class="checkin-item">
                <label for="checkin_niggle_pain">Dolor de esa molestia (0-10)</label>
                <div class="checkin-scale">
                    <span>0</span>
                    <input type="range" id="checkin_niggle_pain" min="0" max="10" value="0"
                           oninput="document.getElementById('checkin_niggle_pain_out').textContent = this.value">
                    <span>10</span>
                    <output id="checkin_niggle_pain_out">0</output>
                </div>
            </div>
            <div class="checkin-item">
                <label for="checkin_last_week_note">¿Qué se atravesó la semana pasada?</label>
                <textarea id="checkin_last_week_note" rows="2" placeholder="Opcional"></textarea>
            </div>
            <div class="checkin-item">
                <label for="checkin_week_ahead">¿Cómo viene esta semana?</label>
                <select id="checkin_week_ahead">
                    <option value="normal">Normal</option>
                    <option value="viaje">Viaje</option>
                    <option value="carga_laboral">Carga laboral alta</option>
                    <option value="poco_sueno">Poco sueño previsto</option>
                </select>
            </div>`;

        modal.classList.add('active');

        const close = () => {
            modal.classList.remove('active');
            document.getElementById('btnCheckinSend').onclick = null;
            document.getElementById('btnCheckinSkip').onclick = null;
        };

        document.getElementById('btnCheckinSkip').onclick = () => { close(); resolve(true); };

        document.getElementById('btnCheckinSend').onclick = async () => {
            const num = id => {
                const el = document.getElementById(id);
                return el ? parseInt(el.value, 10) : null;
            };
            const payload = {
                profile: profileName,
                sleep: num('checkin_sleep'),
                fatigue: num('checkin_fatigue'),
                stress: num('checkin_stress'),
                soreness: num('checkin_soreness'),
                motivation: num('checkin_motivation'),
                niggle_location: document.getElementById('checkin_niggle_location').value,
                niggle_pain: num('checkin_niggle_pain'),
                last_week_note: document.getElementById('checkin_last_week_note').value,
                week_ahead: document.getElementById('checkin_week_ahead').value,
            };
            try {
                await fetch('/api/checkin', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
            } catch (e) {
                // A failed save must never cost the athlete their generation:
                // the week is built without the check-in, exactly as if they
                // had skipped it.
                console.warn('No se pudo guardar el check-in, se genera igual', e);
            }
            close();
            resolve(true);
        };
    });
}

async function startWeekGeneration() {
    const profileName = currentProfile?.name;
    if (!profileName) {
        alert('Guarda el perfil antes de generar una semana.');
        return;
    }

    // ¿Toca ya? Se pregunta ANTES del check-in a propósito: si el atleta va a
    // decidir esperar, no tiene sentido haberle hecho rellenar el cuestionario
    // primero. El servidor vuelve a comprobarlo (409), esto sólo evita el
    // viaje inútil y explica el porqué.
    let forzar = false;
    try {
        const w = await (await fetch(`/api/generate-week/window?profile=${encodeURIComponent(profileName)}`)).json();
        if (w && w.allowed === false) {
            if (!confirm(`${w.reason}

¿Generarla de todas formas?`)) return;
            forzar = true;
        }
    } catch { /* si la comprobación falla, el servidor sigue teniendo la última palabra */ }

    // The check-in is asked here and nowhere else: right before the week is
    // built is the only moment the answer is still true. It is OPTIONAL --
    // "Saltar" goes straight to generating, and nothing downstream changes.
    const proceed = await askCheckin(profileName);
    if (!proceed) return;

    document.getElementById('executionProgress').classList.remove('hidden');
    setGenerationButtonsDisabled(true);

    try {
        const res = await fetch('/api/generate-week', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({profile: profileName, force: forzar})
        });
        if (!res.ok) {
            let detail = `HTTP ${res.status}`;
            try {
                const data = await res.json();
                // El 409 de la ventana llega con el objeto entero; al atleta
                // le sirve la explicación, no el JSON.
                detail = data.detail?.reason
                    || (data.detail ? (typeof data.detail === 'string' ? data.detail : JSON.stringify(data.detail)) : detail);
            } catch (_) { /* body wasn't JSON */ }
            throw new Error(detail);
        }

        generationInterval = setInterval(pollStatus, 2000);
    } catch(e) {
        alert(`Error al iniciar la generación de la semana: ${e.message}`);
        document.getElementById('executionProgress').classList.add('hidden');
        setGenerationButtonsDisabled(false);
    }
}

async function rehacerLaSemana(ajuste) {
    const profileName = currentProfile?.name;
    if (!profileName) { alert('Guarda el perfil antes de rehacer la semana.'); return; }

    // Sin check-in: no se pregunta dos veces cómo viene el atleta por rehacer
    // la misma semana. Se usa el que ya respondió, que sigue siendo el de hoy.
    document.getElementById('executionProgress').classList.remove('hidden');
    setGenerationButtonsDisabled(true);
    try {
        const res = await fetch('/api/generate-week', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({profile: profileName, redo: true, adjustment: ajuste})
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(data.detail?.reason || data.detail || `HTTP ${res.status}`);
        }
        generationInterval = setInterval(pollStatus, 2000);
    } catch (e) {
        alert(`Error al rehacer la semana: ${e.message}`);
        document.getElementById('executionProgress').classList.add('hidden');
        setGenerationButtonsDisabled(false);
    }
}

function setGenerationButtonsDisabled(disabled) {
    document.getElementById('btnGeneratePlan').disabled = disabled;
    document.getElementById('btnGenerateWeek').disabled = disabled;
}

// El log crudo del servidor, para el operador. Aislado en su propia función
// con su propio try por una razón concreta: es un diagnóstico, y un
// diagnóstico que se cae no puede llevarse por delante la generación que lo
// rodea. Un atleta normal recibe 403 aquí y no pasa nada más.
let logTecnicoDisponible = true;

async function refreshTechnicalLog() {
    if (!logTecnicoDisponible) return;
    try {
        const res = await fetch('/api/logs');
        if (!res.ok) {
            // 403 = no es operador. Se deja de pedir y se esconde el
            // desplegable, en vez de ofrecer algo que nunca se abrirá.
            logTecnicoDisponible = false;
            document.querySelector('.execution-progress details')?.classList.add('hidden');
            return;
        }
        const data = await res.json();
        const term = document.getElementById('terminalLogs');
        if (!term) return;
        term.innerHTML = (data.logs || []).join('<br>');
        term.scrollTop = term.scrollHeight;
    } catch (e) {
        logTecnicoDisponible = false;
    }
}

async function pollStatus() {
    try {
        const res = await fetch('/api/generate/status');
        const st = await res.json();

        document.getElementById('progressBar').style.width = `${st.progress}%`;
        document.getElementById('progressStatus').innerText = st.status;

        // El log del servidor es SÓLO del operador desde v7 (es uno para todo
        // el servidor, con nombres y trazas de todos los atletas dentro). A un
        // atleta le responde 403 -- y esta llamada estaba ANTES de mirar el
        // estado, sin protección propia, así que el 403 rompía la función
        // entera: barra congelada, botones bloqueados para siempre y el plan
        // sin aparecer, aunque el servidor lo hubiera generado bien.
        //
        // Ahora falla sola y en silencio, que es lo que le corresponde a un
        // adorno de diagnóstico: nunca puede llevarse por delante el
        // resultado que el atleta ya pagó.
        await refreshTechnicalLog();

        if (st.status === 'success') {
            clearInterval(generationInterval);
            setGenerationButtonsDisabled(false);
            fetchCurrentPlan();
            navigate('plan');
        } else if (st.status === 'failed') {
            clearInterval(generationInterval);
            setGenerationButtonsDisabled(false);
            alert("Error en la generación: " + st.error);
        }
    } catch(e) {}
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.innerText = str ?? '';
    return div.innerHTML;
}

// A repeat block carries its rounds in `substeps`; showing only its own
// description would hide the work and recovery the athlete actually does.
// La sesión de fuerza, escrita para hacerse. Deliberadamente detallada: es
// lo único que el atleta tiene delante en el gimnasio, y resumirla aquí sería
// devolverle el problema que este bloque existe para resolver.
const DIAS_ES = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
const SLOT_ORDER = ['unica', 'am', 'pm', 's3', 's4'];

/* La etiqueta de una sesión cuando el día tiene varias.

   Antes decía "(AM)" y "(PM)", y eso era mentira en el caso que más importa:
   un atleta que corre y nada el mismo miércoles a las 5:30 veía su segunda
   sesión etiquetada como "PM". Los slots siempre fueron una CLAVE de orden,
   nunca una hora — así que la hora se saca de la franja que el atleta declaró
   en su perfil, y si no la hay se dice el orden y ya. */
function sessionSlotLabel(day) {
    if (!day || !day.session_slot || day.session_slot === 'unica') return '';
    const idx = day.block_index;
    if (idx !== null && idx !== undefined) {
        const nombreDia = DIAS_ES[new Date(day.date + 'T12:00:00').getDay()];
        const clave = nombreDia.charAt(0).toUpperCase() + nombreDia.slice(1);
        const bloques = currentProfile?.day_time_blocks?.[clave];
        const b = bloques && bloques[idx];
        if (b?.start && b?.end) return ` · ${b.start}–${b.end}`;
    }
    const orden = SLOT_ORDER.indexOf(day.session_slot);
    return orden > 0 ? ` · ${orden}ª sesión` : '';
}

function renderStrengthBlock(bloque) {
    if (!bloque || !(bloque.exercises || []).length) return '';
    const ejercicios = bloque.exercises.map(e => {
        const carga = e.load ? ` · ${escapeHtml(e.load)}` : '';
        const descanso = e.rest_seconds ? ` · ${e.rest_seconds}s de descanso` : '';
        return `<li class="strength-ex">
            <div class="strength-ex-head">
                <strong>${escapeHtml(e.name)}</strong>
                <span class="strength-dose">${e.sets}×${escapeHtml(e.reps)}${carga}${descanso}</span>
            </div>
            ${e.cues ? `<div class="strength-cue">${escapeHtml(e.cues)}</div>` : ''}
            ${e.targets ? `<div class="strength-targets">${escapeHtml(e.targets)}</div>` : ''}
        </li>`;
    }).join('');
    return `<div class="plan-day-strength">
        ${bloque.warmup ? `<div class="strength-phase"><span>Calentamiento</span> ${escapeHtml(bloque.warmup)}</div>` : ''}
        <ol class="strength-list">${ejercicios}</ol>
        ${bloque.cooldown ? `<div class="strength-phase"><span>Vuelta a la calma</span> ${escapeHtml(bloque.cooldown)}</div>` : ''}
        ${bloque.notes ? `<div class="strength-notes">${escapeHtml(bloque.notes)}</div>` : ''}
    </div>`;
}

function renderPlanSteps(steps, date, prefix) {
    if (!steps || !steps.length) return '';
    const items = steps.map((step, i) => {
        const key = `${prefix}${i}`;
        if (step.substeps && step.substeps.length) {
            // Un hijo que repite la línea del bloque no se pinta: la serie se
            // leía dos veces seguidas ("6x(...)" / "6x(...)"), como si fueran
            // doce repeticiones. El origen está corregido en expand(), pero
            // los planes YA GUARDADOS siguen trayendo la descripción
            // duplicada y no se van a regenerar solos.
            const hijos = step.substeps.filter(s => s.description !== step.description);
            return `<li class="plan-step-repeat">
                <span class="plan-repeat-label">${escapeHtml(step.description)}</span>
                ${renderPlanSteps(hijos.length ? hijos : step.substeps, date, `${key}_`)}
            </li>`;
        }
        return `<li>
            <label>
                <input type="checkbox" data-date="${date}" data-step="${key}">
                ${escapeHtml(step.description)}
            </label>
        </li>`;
    }).join('');
    return `<ul class="plan-steps">${items}</ul>`;
}

const ADHERENCE_LABELS = {
    completed: { icon: '✅', text: 'Completada' },
    modified: { icon: '🔁', text: 'Modificada' },
    missed: { icon: '⭕', text: 'No realizada' },
};

function renderAdherenceBadge(entry) {
    if (!entry || !ADHERENCE_LABELS[entry.status]) return '';
    const { icon, text } = ADHERENCE_LABELS[entry.status];
    const bits = [];
    if (entry.actual_minutes) bits.push(`${Math.round(entry.actual_minutes)} min`);
    if (entry.actual_distance_km) bits.push(`${entry.actual_distance_km} km`);
    if (entry.actual_avg_hr) bits.push(`${entry.actual_avg_hr} ppm`);
    const detail = bits.length ? ` · ${bits.join(' · ')}` : '';
    return `<div class="plan-day-adherence ${entry.status}">${icon} ${text}${detail}</div>`;
}

async function shutdownServer() {
    if (!confirm('¿Apagar el servidor de la app?\n\nSe cerrará la conexión y esta página dejará de funcionar. Para volver a usarla, abre "Iniciar Triatlon 2.0.bat" desde la carpeta del proyecto.')) return;

    try {
        const res = await fetch('/api/server/shutdown', { method: 'POST' });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            alert(data.detail || 'No se pudo apagar el servidor.');
            return;
        }
        document.body.innerHTML = `<div style="font-family:Inter,sans-serif;padding:3rem;text-align:center;color:#5b6270">
            <h1 style="font-family:Outfit,sans-serif;color:#14171f">Servidor apagado</h1>
            <p>Puedes cerrar esta pestaña.</p>
            <p>Para volver a abrir la app, ejecuta <strong>Iniciar Triatlon 2.0.bat</strong> desde la carpeta del proyecto.</p>
        </div>`;
    } catch (e) {
        // The server dying mid-request is the expected outcome, not a failure.
        document.body.innerHTML = '<div style="font-family:sans-serif;padding:3rem;text-align:center">Servidor apagado. Puedes cerrar esta pestaña.</div>';
    }
}

// What each scope means, in the athlete's words. The old dialog said one fixed
// sentence no matter what was on disk, and always deleted everything -- so
// wanting to redo a single week silently cost the macrocycle underneath it.
const DELETE_SCOPES = {
    week: 'Borrar solo la semana generada',
    season: 'Borrar solo el macrociclo',
    all: 'Borrar todo el plan (semana + macrociclo)',
    reasoning: 'Borrar el razonamiento archivado de los agentes',
};

async function confirmDeletion(profileName, scope) {
    let preview = { items: [], warnings: [], empty: true };
    try {
        const res = await fetch(
            `/api/plans/deletion-preview?profile=${encodeURIComponent(profileName)}&scope=${scope}`);
        if (res.ok) preview = await res.json();
    } catch (e) {
        // Falling back to a vaguer confirm is acceptable; deleting silently is not.
        console.warn('No se pudo leer la vista previa de borrado', e);
        preview = { items: ['(no se pudo leer que se va a borrar)'], warnings: [], empty: false };
    }

    if (preview.empty) {
        alert('No hay nada que borrar con esa opcion.');
        return false;
    }

    const lines = [
        `${DELETE_SCOPES[scope]}.`,
        '',
        'Se va a eliminar:',
        ...preview.items.map(i => `  - ${i}`),
    ];
    if (preview.warnings.length) lines.push('', ...preview.warnings);
    lines.push('', 'Esta accion no se puede deshacer. Continuar?');
    return confirm(lines.join('\n'));
}

async function deleteReasoning(profileName) {
    if (!await confirmDeletion(profileName, 'reasoning')) return;
    try {
        const res = await fetch(`/api/reasoning?profile=${encodeURIComponent(profileName)}`, { method: 'DELETE' });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) { alert(data.detail || 'No se pudo borrar el razonamiento.'); return; }
        alert(`Se borraron ${data.deleted} traza(s) de razonamiento.`);
    } catch (e) {
        alert('No se pudo borrar el razonamiento.');
    }
}

async function deleteCurrentRun() {
    const profileName = currentProfile?.name || '';
    if (!profileName) {
        alert('Selecciona un atleta primero.');
        return;
    }

    const scope = (document.getElementById('deleteScope')?.value) || 'all';
    if (scope === 'reasoning') { await deleteReasoning(profileName); return; }
    if (!await confirmDeletion(profileName, scope)) return;

    try {
        const res = await fetch(
            `/api/plans/current?profile=${encodeURIComponent(profileName)}&scope=${scope}`,
            { method: 'DELETE' });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            alert(data.detail || 'No se pudo eliminar la generación.');
            return;
        }
        // Scope-aware, because "ya no tiene ningún plan activo" is simply
        // false after deleting only the week: the macrocycle is still there.
        let message;
        if (data.restored) {
            message = `Listo. Se restauró el plan del ${data.restored.replace('T', ' ').slice(0, 16)}.`;
        } else if (scope === 'week') {
            message = 'Semana eliminada. Tu macrociclo sigue intacto: pulsa "Generar Semana" cuando quieras.';
        } else if (scope === 'season') {
            message = 'Macrociclo eliminado. Las semanas ya generadas siguen ahí, pero para generar otra necesitarás un macrociclo nuevo.';
        } else {
            message = 'Generación eliminada. Este atleta ya no tiene ningún plan activo.';
        }
        // Lo que pasó con el reloj se cuenta aparte: borrar el plan y dejarlo
        // en la muñeca era el desajuste que esto viene a cerrar, así que el
        // atleta tiene que saber en cuál de los dos estados quedó.
        const g = data.garmin;
        if (g && g.retirados) message += ` Se quitaron ${g.retirados} sesión(es) de tu Garmin.`;
        if (g && g.pendientes) {
            message += ` ⚠️ ${g.pendientes} sesión(es) siguen en tu Garmin: no se pudo conectar`
                     + ` (${g.error || 'sin detalle'}). Bórralas desde Garmin Connect o vuelve a intentarlo.`;
        }
        alert(message);
        await fetchCurrentPlan();
        if (location.hash === '#/historial') loadHistory();
    } catch (e) {
        alert('No se pudo eliminar la generación.');
    }
}

/* ==========================================================================
   Home — what the athlete opens the app to see.
   ========================================================================== */
const SPORT_ES = { run: 'Carrera', bike: 'Bici', swim: 'Natación', strength: 'Fuerza', walk: 'Caminata', brick: 'Combinado', rest: 'Descanso' };

function todayISO() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function allPlanDays(weeklyPlan) {
    return (weeklyPlan?.weeks || []).flatMap(w => w.days || []);
}

function nextSessionFrom(weeklyPlan) {
    const today = todayISO();
    return allPlanDays(weeklyPlan)
        .filter(d => !d.is_rest && d.date >= today)
        .sort((a, b) => (a.date + a.session_slot).localeCompare(b.date + b.session_slot))[0] || null;
}

function nextCompetition(profile) {
    const today = todayISO();
    return (profile?.competitions || [])
        .filter(c => c.date && c.date >= today)
        .sort((a, b) => a.date.localeCompare(b.date))[0] || null;
}

function daysUntil(dateStr) {
    const ms = new Date(`${dateStr}T00:00:00`) - new Date(`${todayISO()}T00:00:00`);
    return Math.round(ms / 86400000);
}

function card(title, value, sub) {
    return `<div class="dash-card"><h3>${escapeHtml(title)}</h3>
        <div class="dash-value">${value}</div>
        ${sub ? `<div class="dash-sub">${sub}</div>` : ''}</div>`;
}

function renderHome() {
    const name = currentProfile?.name || '';
    document.getElementById('homeGreeting').innerText = name ? `Hola, ${name.split(' ')[0]}` : 'Inicio';

    const cards = document.getElementById('homeCards');
    const weekBox = document.getElementById('homeWeek');

    if (!name) {
        document.getElementById('homeSubtitle').innerText = 'Crea tu perfil para empezar.';
        cards.innerHTML = `<div class="dash-card"><div class="dash-empty">Todavía no hay ningún atleta seleccionado. Ve a <strong>Perfil</strong> para crear uno.</div></div>`;
        weekBox.innerHTML = '';
        return;
    }

    const plan = latestPlanData?.weekly_plan;
    const hasPlan = plan && !plan.error && (plan.weeks || []).length;
    document.getElementById('homeSubtitle').innerText = hasPlan
        ? 'Tu entrenamiento de un vistazo.'
        : 'Aún no has generado ningún plan.';

    const parts = [];

    // Next session first: it is the reason to open the app on a given morning.
    const next = hasPlan ? nextSessionFrom(plan) : null;
    if (next) {
        const when = daysUntil(next.date);
        const whenLabel = when === 0 ? 'Hoy' : when === 1 ? 'Mañana' : `${next.day_name_es} ${next.date.slice(5)}`;
        const steps = (next.steps || []).slice(0, 4)
            .map(s => `<li>${s.substeps?.length ? '<strong>' : ''}${escapeHtml(s.description)}${s.substeps?.length ? '</strong>' : ''}</li>`)
            .join('');
        parts.push(`<div class="dash-card span-2"><h3>Próxima sesión</h3>
            <div class="next-session">
                <span class="when">${escapeHtml(whenLabel)}</span>
                <span class="what">${escapeHtml(SPORT_ES[next.sport] || next.sport)} · ${escapeHtml(next.focus || '')}</span>
                ${next.total_duration_minutes ? `<span class="how-long">${Math.round(next.total_duration_minutes)} min</span>` : ''}
            </div>
            ${steps ? `<ul class="next-session-steps">${steps}</ul>` : ''}
            <div class="dash-sub">${escapeHtml(next.purpose || '')}</div>
        </div>`);
    } else if (hasPlan) {
        parts.push(`<div class="dash-card span-2"><h3>Próxima sesión</h3>
            <div class="dash-empty">Ya completaste la ventana de este plan. Genera uno nuevo para seguir.</div></div>`);
    }

    const adherence = latestPlanData?.adherence;
    if (adherence?.evaluated) {
        parts.push(card('Adherencia', `${adherence.adherence_pct}%`,
            `${adherence.completed} completadas · ${adherence.modified} modificadas · ${adherence.missed} no realizadas`));
    }

    const race = nextCompetition(currentProfile);
    if (race) {
        const away = daysUntil(race.date);
        parts.push(card('Próxima competición',
            escapeHtml(race.name || 'Competición'),
            `${race.date} · faltan ${away} día${away === 1 ? '' : 's'}`));
    }

    if (hasPlan) {
        const sessions = allPlanDays(plan).filter(d => !d.is_rest).length;
        const minutes = allPlanDays(plan).reduce((t, d) => t + (d.total_duration_minutes || 0), 0);
        parts.push(card('Plan actual', `${sessions} sesiones`,
            `${plan.weeks.length} semanas · ${Math.round(minutes / 60)} h en total`));
    }

    // El ACUMULADO, no la última generación. summary.json sólo guarda la
    // corrida más reciente, así que la temporada desaparecía en cuanto se
    // generaba una semana; y borrar el plan se llevaba la cifra entera. Esto
    // viene del libro append-only (core/usage_ledger.py), que sobrevive a las
    // dos cosas. Se le muestra al atleta sólo durante la beta -- el servidor
    // devuelve 404 cuando SHOW_COST_TO_ATHLETE está en False.
    if (usageData && usageData.total_cost_usd != null) {
        const kinds = Object.values(usageData.by_kind || {})
            .map(k => `${k.label}: $${Number(k.cost_usd).toFixed(2)}`)
            .join(' · ');
        parts.push(card('Lo que ha costado tu entrenamiento',
            `$${Number(usageData.total_cost_usd).toFixed(2)}`,
            kinds || `${usageData.runs} generación${usageData.runs === 1 ? '' : 'es'}`));
    }

    cards.innerHTML = parts.join('') || `<div class="dash-card"><div class="dash-empty">Genera tu primer plan para ver aquí tu resumen.</div></div>`;
    weekBox.innerHTML = hasPlan ? renderWeekStrip(plan) : '';
}

function renderWeekStrip(plan) {
    // The seven days from today, so the shape of the week reads at a glance.
    const byDate = {};
    allPlanDays(plan).forEach(d => { if (!byDate[d.date]) byDate[d.date] = d; });

    const cells = [];
    for (let i = 0; i < 7; i++) {
        const d = new Date();
        d.setDate(d.getDate() + i);
        const iso = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        const day = byDate[iso];
        const label = d.toLocaleDateString('es', { weekday: 'short' });
        const cls = ['d', i === 0 ? 'today' : '', !day || day.is_rest ? 'rest' : ''].filter(Boolean).join(' ');
        const body = day && !day.is_rest
            ? `<span class="sport">${escapeHtml(SPORT_ES[day.sport] || day.sport)}</span>${day.total_duration_minutes ? Math.round(day.total_duration_minutes) + "'" : ''}`
            : '<span class="sport">—</span>';
        cells.push(`<div class="${cls}">${escapeHtml(label)}${body}</div>`);
    }
    return `<h3>Los próximos 7 días</h3><div class="week-strip">${cells.join('')}</div>`;
}

/* ==========================================================================
   History — every plan ever generated, none of them destroyed.
   ========================================================================== */
async function loadHistory() {
    const list = document.getElementById('historyList');
    const profileName = currentProfile?.name || '';
    if (!profileName) {
        list.innerHTML = '<div class="placeholder-text">Selecciona un atleta para ver su historial.</div>';
        return;
    }

    list.innerHTML = '<div class="placeholder-text">Cargando…</div>';
    try {
        const res = await fetch(`/api/plans/history?profile=${encodeURIComponent(profileName)}`);
        if (!res.ok) throw new Error(res.status);
        const entries = (await res.json()).entries || [];
        if (!entries.length) {
            list.innerHTML = '<div class="placeholder-text">Todavía no hay planes archivados: tu plan actual se archiva aquí automáticamente cuando generas uno nuevo. Para borrar el plan activo, ve a <b>Plan</b> y usa el selector junto a 🗑️ Eliminar.</div>';
            return;
        }
        // The active plan is deliberately NOT in this list -- a generation is
        // archived only when the next one replaces it. The athlete went
        // looking for "borrar" here and found only old plans, so the list has
        // to say where the current one lives.
        const activeNote = '<div class="placeholder-text" style="margin-bottom:.6rem">'
            + 'Aquí sólo están los planes <b>archivados</b>. Tu plan activo no aparece en esta lista: '
            + 'para borrarlo o rehacerlo, ve a <b>Plan</b> y usa el selector junto a 🗑️ Eliminar '
            + '(«Solo la semana», «Solo el macrociclo» o «Todo el plan»).</div>';
        list.innerHTML = activeNote + entries.map((e, i) => {
            const when = (e.generated_at || e.id).replace('T', ' ').slice(0, 16);
            return `<div class="history-row${i === 0 ? ' current' : ''}">
                <span class="when">${escapeHtml(when)}</span>
                ${i === 0 ? '<span class="history-badge">Último archivado</span>' : ''}
                <span class="history-kind">${e.kind === 'week' ? 'Semana' : 'Macrociclo'}</span>
                <span class="stat">${e.weeks} semanas · ${e.sessions} sesiones</span>
                ${e.total_cost_usd != null ? `<span class="stat">$${Number(e.total_cost_usd).toFixed(2)}</span>` : ''}
                <span class="spacer"></span>
                <button class="btn btn-outline btn-sm" data-history-print="${escapeHtml(e.id)}">🖨️ PDF</button>
                ${e.has_planning_html ? `<button class="btn btn-outline btn-sm" data-history-report="${escapeHtml(e.id)}">Ver informe</button>` : ''}
                <button class="btn btn-danger btn-sm" data-history-delete="${escapeHtml(e.id)}" title="Eliminar este plan del historial">🗑️</button>
            </div>`;
        }).join('');
    } catch (e) {
        list.innerHTML = '<div class="placeholder-text">No se pudo cargar el historial.</div>';
    }
}

function onHistoryClick(event) {
    const profileName = encodeURIComponent(currentProfile?.name || '');
    const printBtn = event.target.closest('[data-history-print]');
    if (printBtn) {
        window.open(`/api/reports/print?kind=plan&profile=${profileName}&entry=${encodeURIComponent(printBtn.dataset.historyPrint)}`, '_blank');
        return;
    }
    const reportBtn = event.target.closest('[data-history-report]');
    if (reportBtn) {
        window.open(`/api/reports/history/${encodeURIComponent(reportBtn.dataset.historyReport)}?profile=${profileName}&kind=planning`, '_blank');
        return;
    }
    const deleteBtn = event.target.closest('[data-history-delete]');
    if (deleteBtn) deleteHistoryEntry(deleteBtn.dataset.historyDelete);
}

async function deleteHistoryEntry(entryId) {
    const when = entryId.replace('T', ' ').slice(0, 16);
    if (!confirm(`¿Eliminar del historial el plan del ${when}?

Esto no afecta a tu plan activo.`)) return;

    try {
        const res = await fetch(
            `/api/plans/history/${encodeURIComponent(entryId)}?profile=${encodeURIComponent(currentProfile?.name || '')}`,
            { method: 'DELETE' },
        );
        if (!res.ok) throw new Error(res.status);
        loadHistory();
    } catch (e) {
        alert('No se pudo eliminar ese plan del historial.');
    }
}

/* ==========================================================================
   Profile actions the backend supported but nothing ever called.
   ========================================================================== */
function requireAvailableDay() {
    const anyDayChecked = daysOfWeek.some(day => document.getElementById(`chk_${day.toLowerCase()}`)?.checked);
    if (!anyDayChecked) {
        alert('Marca al menos un día disponible — sin eso la IA no puede armar ningún plan.');
        document.querySelector('.profile-tab[data-tab="objetivos"]')?.click();
        return false;
    }
    return true;
}

async function deleteCurrentProfile() {
    const name = currentProfile?.name || document.getElementById('profileSelect').value;
    if (!name) {
        alert('No hay ningún perfil seleccionado.');
        return;
    }
    if (!confirm(`¿Eliminar el perfil de "${name}"? Sus planes guardados no se borran, pero el perfil sí.`)) return;

    try {
        const res = await fetch(`/api/profiles/${encodeURIComponent(name)}`, { method: 'DELETE' });
        if (!res.ok) throw new Error(res.status);
        currentProfile = {};
        latestPlanData = null;
        await loadProfiles();
        document.getElementById('profileSelect').value = '';
        onProfileSelect();
        navigate('inicio');
    } catch (e) {
        alert('No se pudo eliminar el perfil.');
    }
}

function openAnalysisReport() {
    const profileName = currentProfile?.name || '';
    if (!profileName) {
        alert('Selecciona un perfil primero.');
        return;
    }
    if (!latestPlanData?.has_analysis_html) {
        alert('Todavía no hay informe de análisis para este atleta. Genera un plan primero.');
        return;
    }
    window.open(`/api/reports/analysis?profile=${encodeURIComponent(profileName)}`, '_blank');
}

function openMedicalReport() {
    const profileName = currentProfile?.name || '';
    if (!profileName) { alert('Selecciona un atleta primero.'); return; }
    window.open(`/api/reports/medical?profile=${encodeURIComponent(profileName)}`, '_blank');
}

// Un botón que sólo puede dar 404 no es un botón: es una promesa incumplida.
// Se esconden los informes que este atleta todavía no tiene generados, en las
// dos pantallas a la vez.
function syncReportButtons() {
    const hayPlan = !!(latestPlanData?.weekly_plan?.weeks || []).length;
    const haySeason = !!latestPlanData?.season_plan;
    const disponibles = {
        btnHomePrint: hayPlan, btnPrintPlan: hayPlan,
        btnHomeSeason: haySeason, btnPrintSeason: haySeason,
        btnHomeAnalysis: !!latestPlanData?.has_analysis_html,
        btnPlanAnalysis: !!latestPlanData?.has_analysis_html,
        btnHomeMedical: !!latestPlanData?.has_medical_report,
        btnPlanMedical: !!latestPlanData?.has_medical_report,
    };
    for (const [id, hay] of Object.entries(disponibles)) {
        document.getElementById(id)?.classList.toggle('hidden', !hay);
    }
}

function openPrintable(kind) {
    const profileName = currentProfile?.name || document.getElementById('profileSelect')?.value || '';
    if (!profileName) {
        alert('Selecciona un perfil antes de imprimir.');
        return;
    }
    window.open(`/api/reports/print?kind=${kind}&profile=${encodeURIComponent(profileName)}`, '_blank');
}

function renderAdherenceSummary(adherence) {
    if (!adherence || !adherence.evaluated) return '';
    const unplanned = (adherence.unplanned || []).length;
    const extra = unplanned ? ` · ${unplanned} entreno${unplanned > 1 ? 's' : ''} fuera del plan` : '';
    return `<div class="adherence-summary">
        <strong>Adherencia al plan anterior: ${adherence.adherence_pct}%</strong>
        <span>${adherence.completed} completadas · ${adherence.modified} modificadas · ${adherence.missed} no realizadas${extra}</span>
    </div>`;
}

function renderWeeklyPlanCards(weeklyPlan, adherence) {
    if (!weeklyPlan || weeklyPlan.error) {
        // Plan Maestro v4 Track B: after "Generar Plan de Temporada" there is
        // no weekly_plan yet on purpose -- the athlete generates each week on
        // demand. Point them at the button that actually produces one instead
        // of a bare "not found".
        const msg = weeklyPlan?.detail
            || (latestPlanData?.season_plan
                ? 'Ya tienes un plan de temporada. Pulsa "Generar Semana" para crear los próximos 7 días.'
                : 'Todavía no hay ningún plan. Pulsa "Generar Plan de Temporada" para empezar.');
        return `<div class="placeholder-text">${escapeHtml(msg)}</div>`;
    }

    // Adherence is reported against the plan the athlete was PREVIOUSLY given,
    // so it only lines up with days this plan shares with that one.
    const adherenceByDay = {};
    (adherence?.sessions || []).forEach(s => {
        adherenceByDay[`${s.date}|${s.session_slot || 'unica'}`] = s;
    });

    const zonesHtml = weeklyPlan.zones_table
        ? `<div class="plan-zones">${escapeHtml(weeklyPlan.zones_table)}</div>` : '';

    const weeksHtml = (weeklyPlan.weeks || []).map(week => {
        const daysHtml = (week.days || []).map(day => {
            const stepsHtml = !day.is_rest ? renderPlanSteps(day.steps, day.date, '') : '';
            const adherenceHtml = renderAdherenceBadge(
                adherenceByDay[`${day.date}|${day.session_slot || 'unica'}`]
            );
            const duration = day.total_duration_minutes ? `<div class="plan-day-duration">${Math.round(day.total_duration_minutes)} min</div>` : '';
            const slotLabel = sessionSlotLabel(day);
            // A session Garmin has no faithful shape for (plyometrics, rehab,
            // prehab, mobility, brick) lives here as written instructions --
            // that text IS the session, so it must never be hidden behind a
            // "no se pudo subir" error the way it used to be.
            const offWatch = !day.is_rest && day.garmin_exportable === false;
            const manualHtml = offWatch
                ? `<div class="plan-day-manual">
                       <span class="manual-tag">Fuera del reloj</span>
                       <div class="manual-body">${escapeHtml(day.manual_instructions || day.purpose)}</div>
                   </div>`
                : '';
            // v7: la sesión de fuerza, desglosada. No va al reloj: se lee aquí,
            // en el gimnasio, entre serie y serie. Por eso se pinta entera y
            // no plegada.
            const strengthHtml = renderStrengthBlock(day.strength_block);
            const disciplineHtml = day.discipline && day.discipline !== day.sport
                ? `<div class="plan-day-discipline">${escapeHtml(day.discipline)}</div>` : '';
            // Subido o pendiente: el botón lo dice, y el de una sesión ya
            // subida REEMPLAZA la que hay en Garmin en vez de duplicarla.
            const yaSubida = subidasPorSesion[`${day.date}|${day.session_slot || 'unica'}`];
            // `stale` = la sesión se subió, pero el plan cambió después. En el
            // reloj está la versión anterior, y eso hay que decirlo: si no, el
            // atleta sale a hacer un entreno que su plan ya no pide.
            const desfasada = yaSubida?.stale;
            const etiquetaBoton = !yaSubida ? 'Subir' : (desfasada ? 'Actualizar en Garmin' : 'Volver a subir');
            const uploadBtn = !day.is_rest && !offWatch
                ? `<button type="button" class="btn btn-outline btn-sm btn-upload-day" data-date="${day.date}" data-slot="${day.session_slot || 'unica'}">${etiquetaBoton}</button>`
                : '';
            const estadoSubida = !yaSubida ? ''
                : desfasada
                    ? '⚠️ En tu reloj está la versión anterior de esta sesión — actualízala.'
                    : `✅ Subido${yaSubida.uploaded_at ? ` · ${_fechaCorta(yaSubida.uploaded_at)}` : ''}`;
            return `
                <div class="plan-day-card${day.is_rest ? ' rest' : ''}" data-date="${day.date}" data-sport="${day.sport}" data-slot="${day.session_slot || 'unica'}">
                    <div class="plan-day-date">${escapeHtml(day.day_name_es)} · ${day.date}${slotLabel}</div>
                    <div class="plan-day-sport-badge">${escapeHtml(day.sport)}</div>
                    ${disciplineHtml}
                    <div class="plan-day-focus">${escapeHtml(day.focus)}</div>
                    ${adherenceHtml}
                    ${manualHtml}${strengthHtml}
                    ${stepsHtml}
                    ${duration}
                    <div class="plan-day-purpose">${escapeHtml(day.purpose)}</div>
                    <div class="plan-day-adaptation">${escapeHtml(day.adaptation)}</div>
                    <div class="plan-day-upload-status" data-date="${day.date}" data-slot="${day.session_slot || 'unica'}">${estadoSubida}</div>
                    ${uploadBtn}
                </div>`;
        }).join('');
        // Why this week looks the way it does, written by the decision step
        // itself (see WeekIntentContent.week_rationale). Absent on plans
        // generated before this existed, so it renders nothing rather than
        // an empty box.
        const rationaleHtml = week.week_rationale
            ? `<p class="plan-week-rationale">${escapeHtml(week.week_rationale)}</p>`
            : '';
        return `
            <div class="plan-week">
                <h3>Semana ${week.week_number}</h3>
                ${rationaleHtml}
                <div class="plan-day-grid">${daysHtml}</div>
            </div>`;
    }).join('');

    return `${zonesHtml}${weeksHtml}`;
}

async function fetchCurrentPlan() {
    try {
        const profileName = currentProfile?.name || '';
        if (!profileName) { latestPlanData = null; usageData = null; renderHome(); return; }
        const res = await fetch(`/api/plans/current?profile=${encodeURIComponent(profileName)}`);
        const data = await res.json();
        latestPlanData = data;
        // 404 es una respuesta válida: significa que el costo ya no se le
        // muestra al atleta (SHOW_COST_TO_ATHLETE en False cuando la beta
        // termine). No es un error que haya que reportar.
        try {
            const usageRes = await fetch(`/api/usage?profile=${encodeURIComponent(profileName)}`);
            usageData = usageRes.ok ? await usageRes.json() : null;
        } catch { usageData = null; }
        syncReportButtons();
        loadCharts();
        await cargarEstadoDeSubidas();
        const cont = document.getElementById('planResultsContainer');
        cont.innerHTML = renderAdherenceSummary(data.adherence)
            + renderWeeklyPlanCards(data.weekly_plan, data.adherence);

        const seasonEl = document.getElementById('seasonPlanContent');
        if (seasonEl) {
            seasonEl.innerHTML = data.season_plan
                ? marked.parse(data.season_plan)
                : 'El plan de temporada aparecerá aquí.';
        }

        // "Generar Semana" needs a season plan to anchor against (the
        // backend rejects the call otherwise) -- disable it until one exists
        // instead of letting the athlete hit a doomed request.
        const btnGenerateWeek = document.getElementById('btnGenerateWeek');
        if (btnGenerateWeek && !generationInterval) {
            btnGenerateWeek.disabled = !data.season_plan;
        }

        renderHome();

        // Volume reconciliation: self-reported vs real Garmin volume diverged
        const volEl = document.getElementById('volumeReconciliationBanner');
        if (volEl) {
            if (data.volume_reconciliation_note) {
                volEl.textContent = `📊 ${data.volume_reconciliation_note}`;
                volEl.classList.remove('hidden');
            } else {
                volEl.classList.add('hidden');
            }
        }

        // Goal realism: the athlete's declared target times against what their
        // own Garmin predictions say. Shown as a read-out, never as a rejection
        // — the plan is built either way (see services/ai/goal_realism.py).
        const goalEl = document.getElementById('goalRealismBanner');
        if (goalEl) {
            const assessments = data.goal_realism || [];
            if (assessments.length) {
                goalEl.innerHTML = assessments.map(a => {
                    const detail = a.predicted_clock
                        ? `Predicción actual ${escapeHtml(a.predicted_clock)} · meta ${escapeHtml(a.target_clock)} `
                          + `(${a.required_improvement_pct}% más rápido en ${a.weeks_available} semanas)`
                        : `Meta ${escapeHtml(a.target_clock)} — sin predicción de Garmin para esa distancia`;
                    return `<div class="goal-row goal-${escapeHtml(a.verdict)}">`
                        + `<strong>${escapeHtml(a.name)}</strong> · ${detail}`
                        + `<br><span class="goal-verdict">${escapeHtml(a.verdict_es)}</span></div>`;
                }).join('');
                goalEl.classList.remove('hidden');
            } else {
                goalEl.classList.add('hidden');
            }
        }

        // Real per-generation cost, broken down by node — computed from each
        // LLM call's actual token usage (see node_base.create_cost_entry),
        // not a placeholder.
        const costEl = document.getElementById('costBreakdownContent');
        if (costEl) {
            if (typeof data.total_cost_usd === 'number') {
                const rows = (data.cost_breakdown || [])
                    .filter(a => a.cost_usd > 0 || a.tokens > 0)
                    .map(a => `<tr><td>${escapeHtml(a.name || '')}</td><td>$${a.cost_usd.toFixed(4)}</td><td>${(a.tokens || 0).toLocaleString()}</td></tr>`)
                    .join('');
                costEl.innerHTML = `
                    <p><strong>Total: $${data.total_cost_usd.toFixed(4)}</strong> (${(data.total_tokens || 0).toLocaleString()} tokens)</p>
                    ${rows ? `<table class="cost-breakdown-table"><thead><tr><th>Nodo</th><th>Costo</th><th>Tokens</th></tr></thead><tbody>${rows}</tbody></table>` : ''}
                `;
            } else {
                costEl.innerHTML = 'Sin datos de costo.';
            }
        }

        // Automatic adjustments the validator made — specific lines, not a
        // silent correction the athlete never finds out about.
        const adjBanner = document.getElementById('planAdjustmentsBanner');
        const adjList = document.getElementById('planAdjustmentsList');
        const violations = data.plan_validation?.violations_translated || [];
        if (adjBanner && adjList) {
            if (violations.length) {
                adjList.innerHTML = violations.map(v => `<li>${escapeHtml(v)}</li>`).join('');
                adjBanner.classList.remove('hidden');
            } else {
                adjBanner.classList.add('hidden');
                adjList.innerHTML = '';
            }
        }
    } catch(e) {
        console.error(e);
    }
}

// Garmin upload
let garminUploadInterval = null;

/* ── Qué está ya en el reloj ───────────────────────────────────────────────
   El "✅ Subido" que se veía antes era del sondeo de ESA subida: al recargar
   la página desaparecía, el botón volvía a decir "Subir", y cada clic dejaba
   otra entrada en el calendario del atleta. Ahora el servidor lo recuerda
   (services/garmin/upload_ledger.py) y la pantalla lo lee. */
let subidasPorSesion = {};

async function cargarEstadoDeSubidas() {
    const nombre = currentProfile?.name;
    if (!nombre) { subidasPorSesion = {}; return; }
    try {
        const r = await fetch(`/api/garmin/uploads?profile=${encodeURIComponent(nombre)}`);
        subidasPorSesion = r.ok ? ((await r.json()).sessions || {}) : {};
    } catch { subidasPorSesion = {}; }
}

function _fechaCorta(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return isNaN(d) ? '' : d.toLocaleString('es', {day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'});
}

/* "Subir Todo" dejó de significar "subirlo todo otra vez".
   Antes daba igual cuántas veces se pulsara: cada clic creaba entrenos nuevos
   en Garmin y dejaba entradas duplicadas en el calendario. Ahora sube lo que
   falta, y re-subir todo es una decisión explícita. */
async function subirTodoAGarmin() {
    const dias = (latestPlanData?.weekly_plan?.weeks || [])
        .flatMap(w => w.days || [])
        .filter(d => !d.is_rest && d.garmin_exportable !== false);
    if (!dias.length) { uploadToGarmin(null); return; }

    const claves = dias.map(d => `${d.date}|${d.session_slot || 'unica'}`);
    const pendientes = claves.filter(k => !subidasPorSesion[k] || subidasPorSesion[k].stale);

    if (!pendientes.length) {
        const cuantas = claves.length;
        if (!confirm(`Las ${cuantas} sesiones de este plan ya están en tu Garmin.

`
                   + `¿Volver a subirlas todas? Se reemplazan las que ya hay: no se duplican, `
                   + `pero tampoco hace falta si no ha cambiado nada.`)) return;
        uploadToGarmin(null);
        return;
    }
    uploadToGarmin(pendientes);
}

async function uploadToGarmin(dates) {
    const statusEl = document.getElementById('garminUploadStatus');
    const profileName = currentProfile?.name;
    if (!profileName) {
        alert('Guarda el perfil antes de subir a Garmin.');
        return;
    }

    try {
        await fetch('/api/garmin/upload', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({profile_name: profileName, dates: dates || null})
        });
        statusEl.innerText = 'Subiendo a Garmin...';
        garminUploadInterval = setInterval(pollGarminUploadStatus, 2000);
    } catch(e) {
        statusEl.innerText = 'Error al iniciar la subida a Garmin.';
    }
}

async function pollGarminUploadStatus() {
    try {
        const res = await fetch('/api/garmin/upload/status');
        const st = await res.json();
        const statusEl = document.getElementById('garminUploadStatus');

        if (st.status === 'success') {
            clearInterval(garminUploadInterval);
            const r = st.result;
            statusEl.innerText = `✅ ${r.uploaded}/${r.total} entrenamientos subidos a Garmin.`;
            const withWarnings = [];
            (r.details || []).forEach(d => {
                const el = document.querySelector(`.plan-day-upload-status[data-date="${d.date}"][data-slot="${d.session_slot || 'unica'}"]`);
                if (!el) return;
                if (!d.success) {
                    el.innerText = `❌ ${d.error || 'Error'}`;
                } else if (d.warnings && d.warnings.length) {
                    // Uploaded, but Garmin did not store what we sent. The
                    // athlete needs to know before they go out and run it.
                    el.innerText = `⚠️ Subido con avisos: ${d.warnings.join(' ')}`;
                    withWarnings.push(d.date);
                } else {
                    el.innerText = '✅ Subido';
                }
                // El botón de esa sesión pasa a decir la verdad sin esperar a
                // que se recargue la página: a partir de ahora reemplaza.
                if (d.success) {
                    const b = document.querySelector(
                        `.btn-upload-day[data-date="${d.date}"][data-slot="${d.session_slot || 'unica'}"]`);
                    if (b) b.textContent = 'Volver a subir';
                }
            });
            if (withWarnings.length) {
                statusEl.innerText += ` ⚠️ ${withWarnings.length} con diferencias respecto al plan — revísalos en Garmin Connect.`;
            }
            if (r.replaced) statusEl.innerText += ` ♻️ ${r.replaced} reemplazado(s), sin duplicar.`;
            if (r.pruned) statusEl.innerText += ` 🧹 ${r.pruned} entreno(s) ya pasado(s) retirado(s) de tu biblioteca.`;
            // El registro cambió: se relee para que los botones digan la verdad
            // sin tener que recargar la página.
            cargarEstadoDeSubidas();
        } else if (st.status === 'failed') {
            clearInterval(garminUploadInterval);
            statusEl.innerText = `❌ Error al subir a Garmin: ${st.error}`;
        } else if (st.running) {
            statusEl.innerText = st.status || 'Subiendo a Garmin...';
        }
    } catch(e) {}
}


/* ── Sesión y feedback (v7) ────────────────────────────────────────────────
   La app dejó de ser de una sola persona, así que hay dos cosas nuevas en el
   pie de la barra: quién eres (y cómo salir) y por dónde reportar. */

async function initSession() {
    try {
        const me = await (await fetch('/api/auth/me')).json();
        // setup_required = instalación sin usuarios todavía, que está abierta
        // a propósito para poder crear el primero. Ahí no se ofrece "Salir":
        // no hay sesión de la que salir.
        if (!me.authenticated) {
            // El cascarón puede llegar aquí sin sesión: el navegador tenía
            // index.html en caché de antes de que existiera el login, y lo
            // sirvió sin preguntar. Sin esto el atleta se queda mirando una
            // app muerta -- sin perfil, sin datos y sin forma de entrar,
            // porque cada llamada devuelve 401 en silencio. Pasó de verdad.
            if (!me.setup_required) location.href = '/login';
            return;
        }
        // La contraseña temporal sólo sirve para cambiarla. Si la app cargara
        // igual, el atleta vería todo vacío (el servidor le responde 403 a
        // todo) sin saber por qué.
        if (me.must_change_password) { location.href = '/login'; return; }
        const btn = document.getElementById('btnLogout');
        if (!btn) return;
        btn.classList.remove('hidden');
        btn.title = `Cerrar la sesión de ${me.username}`;
        btn.addEventListener('click', async () => {
            await fetch('/api/auth/logout', { method: 'POST' });
            location.href = '/login';
        });
    } catch { /* sin sesión no hay nada que mostrar */ }
}

function initFeedback() {
    const link = document.getElementById('btnFeedback');
    if (!link) return;
    // El mensaje se arma al pulsar, no al cargar: así lleva la vista en la
    // que estaba el atleta cuando decidió reportar. "No me funciona" sin
    // saber dónde estaba no se puede accionar.
    link.addEventListener('click', () => {
        const vista = (location.hash || '#/').replace('#/', '') || 'inicio';
        const texto =
            `Hola Jose, reporte de la beta de Triatlón 2.0.

` +
            `Pantalla: ${vista}
` +
            `Atleta: ${currentProfile?.name || '(sin perfil)'}

` +
            `Qué pasó:


` +
            `(adjunta una captura si puedes)`;
        link.href = `https://wa.me/50230965754?text=${encodeURIComponent(texto)}`;
    });
}


/* ==========================================================================
   AI Coach flotante + Cuaderno — Plan Maestro v7, tracks 7 y 8.

   El chat vive ahora en un panel flotante presente en todas las vistas, y la
   pestaña que ocupaba pasa a ser el cuaderno: la conversación se hace mirando
   el plan, y lo que sale de ella se lee y se corrige en su propio sitio.
   ========================================================================== */

let coachPanelOpen = false;

function initCoachWidget() {
    const fab = document.getElementById('coachFab');
    const panel = document.getElementById('coachPanel');
    if (!fab || !panel) return;

    fab.addEventListener('click', () => toggleCoachPanel());
    document.getElementById('btnCoachClose').addEventListener('click', () => toggleCoachPanel(false));
    document.getElementById('btnCoachNotebook').addEventListener('click', () => {
        toggleCoachPanel(false);
        navigate('cuaderno');
    });

    // Escape cierra, como cualquier panel superpuesto. Sin esto el único modo
    // de salir es apuntar a una ✕ de 15 píxeles.
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && coachPanelOpen) toggleCoachPanel(false);
    });

    // El textarea crece con el texto hasta un tope. Un campo de una línea
    // fija esconde lo que el atleta acaba de escribir en cuanto se pasa.
    const input = document.getElementById('coachInput');
    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 96)}px`;
    });
}

function toggleCoachPanel(force) {
    const panel = document.getElementById('coachPanel');
    const fab = document.getElementById('coachFab');
    coachPanelOpen = force === undefined ? !coachPanelOpen : force;
    panel.classList.toggle('hidden', !coachPanelOpen);
    fab.setAttribute('aria-expanded', String(coachPanelOpen));
    if (!coachPanelOpen) return;

    // El saludo se escribe al abrir, no al cargar la página: así puede citar
    // el plan que el atleta tiene AHORA. Un saludo genérico en un coach que
    // presume de conocer tu plan es la peor primera frase posible.
    if (!document.getElementById('coachHistory').children.length) greetFromCoach();
    document.getElementById('coachInput').focus();
}

function greetFromCoach() {
    const nombre = (currentProfile?.name || '').split(' ')[0];
    const partes = [nombre ? `Hola ${nombre}.` : 'Hola.'];

    const dias = allPlanDays(latestPlanData?.weekly_plan);
    const hoy = new Date().toISOString().slice(0, 10);
    const sesionHoy = dias.find(d => d.date === hoy && !d.is_rest);
    if (sesionHoy) {
        const mins = sesionHoy.total_duration_minutes ? ` (${Math.round(sesionHoy.total_duration_minutes)}′)` : '';
        partes.push(`Hoy te toca ${(sesionHoy.focus || sesionHoy.sport || 'entrenar').toLowerCase()}${mins}.`);
    } else if (dias.some(d => d.date === hoy)) {
        partes.push('Hoy descansas.');
    }
    partes.push('Pregúntame lo que quieras de tu plan: por qué una sesión está donde está, qué hacer si te sientes mal, cómo va tu progresión.');
    appendCoachMessage('assistant', partes.join(' '));
}

/* ── Cuaderno ─────────────────────────────────────────────────────────── */

let notebookCategories = {};

const NOTEBOOK_STATUS_LABEL = {
    vigente: 'En uso',
    propuesta: 'Propuesta del coach — confírmala',
    retirada: 'Retirada',
};

async function loadNotebook() {
    const cont = document.getElementById('notebookList');
    const profileName = currentProfile?.name;
    if (!profileName) {
        cont.innerHTML = '<div class="placeholder-text">Selecciona un atleta para ver su cuaderno.</div>';
        return;
    }
    cont.innerHTML = '<div class="placeholder-text">Cargando…</div>';
    try {
        const res = await fetch(`/api/notebook?profile=${encodeURIComponent(profileName)}`);
        const data = await res.json();
        notebookCategories = data.categories || {};
        fillNotebookCategorySelect();
        renderNotebook(data.entries || []);
    } catch (e) {
        cont.innerHTML = '<div class="placeholder-text">No pude leer el cuaderno.</div>';
    }
}

function fillNotebookCategorySelect() {
    const sel = document.getElementById('notebookCategory');
    if (!sel || sel.options.length) return;
    sel.innerHTML = Object.entries(notebookCategories)
        .map(([k, v]) => `<option value="${escapeHtml(k)}">${escapeHtml(v)}</option>`).join('');
}

function renderNotebook(entries) {
    const cont = document.getElementById('notebookList');
    const props = document.getElementById('notebookProposals');

    // Las propuestas van ARRIBA y separadas: son lo único que pide una
    // decisión del atleta. Mezcladas con lo confirmado, nadie las confirma.
    const propuestas = entries.filter(e => e.status === 'propuesta');
    props.innerHTML = propuestas.length
        ? `<h3 class="notebook-group-title">Tu coach quiere anotar esto</h3>`
          + propuestas.map(notebookEntryHtml).join('')
        : '';

    const vigentes = entries.filter(e => e.status === 'vigente');
    const retiradas = entries.filter(e => e.status === 'retirada');

    if (!entries.length) {
        cont.innerHTML = `<div class="placeholder-text">Tu cuaderno está vacío. Se irá llenando cuando
            hables con tu coach, o puedes escribir algo tú mismo aquí arriba.</div>`;
        return;
    }

    let html = '';
    if (vigentes.length) {
        html += `<h3 class="notebook-group-title">En uso (${vigentes.length}) — esto llega a tu plan</h3>`;
        html += vigentes.map(notebookEntryHtml).join('');
    }
    if (retiradas.length) {
        // Se muestran a propósito: un cuaderno que sólo enseña lo vigente
        // esconde su propio historial, y entonces no es auditable.
        html += `<h3 class="notebook-group-title">Retiradas (${retiradas.length}) — ya no se aplican</h3>`;
        html += retiradas.map(notebookEntryHtml).join('');
    }
    cont.innerHTML = html;
}

function notebookEntryHtml(e) {
    const cat = notebookCategories[e.category] || 'Otros';
    const fecha = (e.created_at || '').slice(0, 10);
    const origen = e.source === 'chat' ? 'del chat' : 'escrito por ti';
    const baja = e.retired_at ? ` · retirada el ${e.retired_at.slice(0, 10)}` : '';

    let acciones = '';
    if (e.status === 'propuesta') {
        acciones = `<button data-nb-confirm="${e.id}">✓ Confirmar</button>
                    <button class="danger" data-nb-delete="${e.id}">Descartar</button>`;
    } else if (e.status === 'vigente') {
        acciones = `<button data-nb-edit="${e.id}">Editar</button>
                    <button data-nb-retire="${e.id}">Retirar</button>
                    <button class="danger" data-nb-delete="${e.id}">Borrar</button>`;
    } else {
        acciones = `<button data-nb-restore="${e.id}">Volver a usar</button>
                    <button class="danger" data-nb-delete="${e.id}">Borrar</button>`;
    }

    return `
    <div class="notebook-entry ${escapeHtml(e.status)}">
        <div class="notebook-text">${escapeHtml(e.text)}</div>
        ${e.quote ? `<div class="notebook-quote">«${escapeHtml(e.quote)}»</div>` : ''}
        <div class="notebook-meta">
            <span class="notebook-source">${escapeHtml(cat)}</span>
            <span>${escapeHtml(fecha)} · ${origen}${escapeHtml(baja)}</span>
            ${e.status === 'propuesta' ? `<span>${NOTEBOOK_STATUS_LABEL.propuesta}</span>` : ''}
            <span class="notebook-actions">${acciones}</span>
        </div>
    </div>`;
}

async function notebookPatch(entryId, fields) {
    const profileName = currentProfile?.name;
    const res = await fetch(`/api/notebook/${entryId}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ profile: profileName, ...fields }),
    });
    if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        alert(data.detail || 'No pude guardar el cambio.');
    }
    await loadNotebook();
}

function initNotebook() {
    const add = document.getElementById('btnNotebookAdd');
    if (!add) return;

    add.addEventListener('click', async () => {
        const texto = (document.getElementById('notebookText').value || '').trim();
        if (!texto) return;
        const res = await fetch('/api/notebook', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                profile: currentProfile?.name,
                text: texto,
                category: document.getElementById('notebookCategory').value || 'otro',
            }),
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            alert(data.detail || 'No pude anotarlo.');
            return;
        }
        document.getElementById('notebookText').value = '';
        loadNotebook();
    });

    // Delegación: las entradas se repintan enteras en cada cambio, así que
    // enganchar cada botón por separado dejaría oyentes muertos.
    document.getElementById('view-cuaderno').addEventListener('click', async event => {
        const btn = event.target.closest('button[data-nb-confirm], button[data-nb-retire], ' +
            'button[data-nb-restore], button[data-nb-delete], button[data-nb-edit]');
        if (!btn) return;

        const { nbConfirm, nbRetire, nbRestore, nbDelete, nbEdit } = btn.dataset;

        if (nbConfirm) return notebookPatch(nbConfirm, { status: 'vigente' });
        if (nbRestore) return notebookPatch(nbRestore, { status: 'vigente' });

        if (nbRetire) {
            const motivo = prompt('¿Por qué deja de aplicar? (opcional)') ?? '';
            return notebookPatch(nbRetire, { status: 'retirada', retired_reason: motivo });
        }

        if (nbEdit) {
            const actual = btn.closest('.notebook-entry').querySelector('.notebook-text').textContent;
            const nuevo = prompt('Edita la anotación:', actual);
            if (nuevo === null || !nuevo.trim()) return;
            return notebookPatch(nbEdit, { text: nuevo.trim() });
        }

        if (nbDelete) {
            // Borrar es distinto de retirar y se dice: retirar deja rastro,
            // borrar no. Es la única vía que elimina de verdad, y es del
            // atleta porque son sus datos.
            if (!confirm('Se elimina del todo, sin dejar rastro. ¿Seguro?')) return;
            const res = await fetch(
                `/api/notebook/${nbDelete}?profile=${encodeURIComponent(currentProfile?.name || '')}`,
                { method: 'DELETE' });
            if (!res.ok) alert('No pude borrarla.');
            return loadNotebook();
        }
    });
}


/* ==========================================================================
   Formato de los mensajes de chat — v7.

   Los dos chatbots escribían como si redactaran un informe: encabezados `##`,
   reglas `---` y asteriscos por todas partes. En el del coach ni siquiera se
   convertían — se veían los asteriscos literales, porque el mensaje se pintaba
   con textContent. En el del doctor se pasaban por `marked.parse` completo,
   que sí los convertía... en encabezados y líneas horizontales, o sea en un
   documento dentro de una burbuja de chat.

   Esto convierte lo que un chat necesita y nada más: negrita, cursiva,
   viñetas, listas numeradas y código. Los encabezados se degradan a una línea
   en negrita y las reglas horizontales desaparecen, porque en una conversación
   no significan nada.

   Y es seguro por construcción: primero se escapa TODO el HTML, y sólo
   después se reintroducen las etiquetas que este conversor genera. `marked`
   deja pasar HTML crudo del texto de origen, que en la salida de un modelo es
   una vía de inyección que no hace falta correr.
   ========================================================================== */

function chatMarkdown(texto) {
    const escapar = s => s
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

    const enLinea = s => escapar(s)
        // Código antes que nada: dentro de él, los asteriscos son literales.
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        .replace(/(^|[\s(])\*([^*\n]+)\*(?=[\s.,;:)!?]|$)/g, '$1<em>$2</em>')
        .replace(/(^|[\s(])_([^_\n]+)_(?=[\s.,;:)!?]|$)/g, '$1<em>$2</em>');

    const salida = [];
    let lista = null;   // 'ul' | 'ol' | null

    const cerrarLista = () => { if (lista) { salida.push(`</${lista}>`); lista = null; } };
    const abrirLista = tipo => { if (lista !== tipo) { cerrarLista(); salida.push(`<${tipo}>`); lista = tipo; } };

    for (const cruda of String(texto ?? '').split('\n')) {
        const linea = cruda.trim();

        // Las reglas horizontales no dicen nada en una conversación.
        if (/^([-*_])\1{2,}$/.test(linea)) { cerrarLista(); continue; }
        if (!linea) { cerrarLista(); continue; }

        // Un encabezado en un chat es, como mucho, una frase destacada.
        const encabezado = linea.match(/^#{1,6}\s+(.*)$/);
        if (encabezado) {
            cerrarLista();
            salida.push(`<p class="chat-lead"><strong>${enLinea(encabezado[1])}</strong></p>`);
            continue;
        }

        const vineta = linea.match(/^[-*•]\s+(.*)$/);
        if (vineta) { abrirLista('ul'); salida.push(`<li>${enLinea(vineta[1])}</li>`); continue; }

        const numerada = linea.match(/^\d+[.)]\s+(.*)$/);
        if (numerada) { abrirLista('ol'); salida.push(`<li>${enLinea(numerada[1])}</li>`); continue; }

        cerrarLista();
        salida.push(`<p>${enLinea(linea)}</p>`);
    }
    cerrarLista();
    return salida.join('');
}


/* ==========================================================================
   Gráficas — v7. SVG en línea, sin librerías.

   Cero coste de IA: todo sale de `/api/charts`, que a su vez sale de archivos
   que la app ya escribe. Y cero dependencias nuevas: el proyecto no tiene
   proceso de compilación, así que meter una librería de gráficas significaría
   o un CDN (que la app ya evita para el resto) o 200 KB de JavaScript para
   dibujar cuatro barras.

   Los colores salen de las variables del tema, así que el modo oscuro
   funciona sin escribir una regla más.

   Dos reglas de dibujo que la primera versión se saltó y costaron legibilidad:

   1. **Nada de `preserveAspectRatio="none"`.** Estiraba el `viewBox` cuadrado
      al ancho de la tarjeta, así que los puntos de la línea salían como
      elipses aplastadas. Ahora el `viewBox` tiene la proporción real del
      dibujo y escala uniforme: un círculo es un círculo.
   2. **Un eje sin números no se puede leer.** Todas las series llevan sus
      extremos escritos y las barras su valor encima. Una barra sin cifra sólo
      dice "más que la de al lado", que no es analizar nada.
   ========================================================================== */

const CHART_COLORS = ['var(--accent)', 'var(--success)', 'var(--warning)', 'var(--danger)', 'var(--text-muted)'];

/* Proporción del área de dibujo. Escala uniforme, así que estos son los
   únicos números que hay que tener en la cabeza: el resto es aritmética. */
const PLOT = { w: 320, h: 132, l: 36, r: 10, t: 12, b: 20 };

function chartCard(titulo, subtitulo, cuerpo, nota) {
    return `<div class="chart-card">
        <div class="chart-head">
            <h3>${escapeHtml(titulo)}</h3>
            ${subtitulo ? `<span class="chart-sub">${subtitulo}</span>` : ''}
        </div>
        ${cuerpo}
        ${nota ? `<p class="chart-note">${escapeHtml(nota)}</p>` : ''}
    </div>`;
}

/* Un número de reloj a partir de segundos: 1639 → "27:19". El eje de la
   gráfica de predicción de carrera no puede ir en segundos, nadie lee 1639
   como veintisiete minutos. */
function relojDesdeSegundos(s) {
    s = Math.round(s);
    const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), seg = s % 60;
    const dosCifras = n => String(n).padStart(2, '0');
    return h ? `${h}:${dosCifras(m)}:${dosCifras(seg)}` : `${m}:${dosCifras(seg)}`;
}

/* ---------------------------------------------------------------------------
   Serie temporal genérica. La usan la carga, el VO2max, la HRV, el pulso en
   reposo y la predicción de carrera: una sola implementación bien hecha en
   vez de cinco a medias.

   Opciones:
     banda    [bajo, alto] sombreado de fondo (rango normal / zona habitual)
     umbral   una raya horizontal de aviso
     meta     una raya horizontal que es un objetivo del atleta
     formato  cómo se escribe un valor en el eje
     invertir true cuando MENOS es mejor (un tiempo de carrera)
--------------------------------------------------------------------------- */
function lineaSerie(puntos, opciones) {
    const o = Object.assign({
        banda: null, umbral: null, meta: null, color: 'var(--accent)',
        formato: v => String(Math.round(v * 10) / 10), invertir: false,
    }, opciones || {});

    const valores = puntos.map(p => p.value);
    let min = Math.min(...valores), max = Math.max(...valores);
    // La banda, el umbral y la meta tienen que CABER en el eje: dibujar una
    // meta fuera del rango visible es no dibujarla.
    const extras = [o.umbral, o.meta].concat(o.banda || []).filter(v => v !== null && v !== undefined);
    extras.forEach(v => { min = Math.min(min, v); max = Math.max(max, v); });

    // El eje NO empieza en cero a propósito. La variación útil de un VO2max
    // son dos o tres puntos y la de una HRV unos pocos ms: un eje desde cero
    // los aplasta hasta hacerlos invisibles. Por eso los dos extremos van
    // escritos — un eje recortado sin números sí sería engañoso.
    const margen = (max - min) * 0.15 || Math.max(Math.abs(max) * 0.05, 1);
    min -= margen; max += margen;
    const rango = max - min || 1;

    const anchoUtil = PLOT.w - PLOT.l - PLOT.r;
    const altoUtil = PLOT.h - PLOT.t - PLOT.b;
    const x = i => PLOT.l + (puntos.length > 1 ? (i / (puntos.length - 1)) * anchoUtil : anchoUtil / 2);
    const y = v => PLOT.t + altoUtil - ((v - min) / rango) * altoUtil;

    const linea = puntos.map((p, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(p.value).toFixed(1)}`).join(' ');
    // El área bajo la línea da volumen a la tendencia sin añadir un dato.
    const area = `${linea} L${x(puntos.length - 1).toFixed(1)},${(PLOT.t + altoUtil).toFixed(1)} L${x(0).toFixed(1)},${(PLOT.t + altoUtil).toFixed(1)} Z`;

    // Con muchos puntos los círculos se convierten en una tira: se marcan
    // sólo los extremos, que son los que el atleta lee.
    const marcados = puntos.length > 14 ? [0, puntos.length - 1] : puntos.map((_, i) => i);
    const circulos = marcados.map(i => {
        const p = puntos[i];
        const fuera = (o.umbral !== null && o.umbral !== undefined && p.value > o.umbral);
        return `<circle cx="${x(i).toFixed(1)}" cy="${y(p.value).toFixed(1)}" r="2.6"
            fill="${fuera ? 'var(--danger)' : o.color}" stroke="var(--bg-secondary)" stroke-width="1"
        ><title>${escapeHtml(p.label)}: ${escapeHtml(o.formato(p.value))}</title></circle>`;
    }).join('');

    let fondo = '';
    if (o.banda) {
        const arriba = y(Math.max(o.banda[0], o.banda[1]));
        const abajo = y(Math.min(o.banda[0], o.banda[1]));
        fondo += `<rect x="${PLOT.l}" y="${arriba.toFixed(1)}" width="${anchoUtil}"
            height="${Math.max(abajo - arriba, 1).toFixed(1)}" fill="var(--success)" opacity="0.13"></rect>`;
    }
    if (o.umbral !== null && o.umbral !== undefined) {
        fondo += `<line x1="${PLOT.l}" y1="${y(o.umbral).toFixed(1)}" x2="${PLOT.w - PLOT.r}" y2="${y(o.umbral).toFixed(1)}"
            stroke="var(--danger)" stroke-width="1" stroke-dasharray="3 3" opacity="0.65"></line>`;
    }
    if (o.meta !== null && o.meta !== undefined) {
        fondo += `<line x1="${PLOT.l}" y1="${y(o.meta).toFixed(1)}" x2="${PLOT.w - PLOT.r}" y2="${y(o.meta).toFixed(1)}"
            stroke="var(--success)" stroke-width="1.4" stroke-dasharray="4 2"></line>
        <text x="${PLOT.w - PLOT.r}" y="${(y(o.meta) - 3).toFixed(1)}" class="chart-axis" text-anchor="end">meta ${escapeHtml(o.formato(o.meta))}</text>`;
    }

    // Ejes: los dos extremos del eje vertical y tres fechas del horizontal.
    const ejes = `
        <line x1="${PLOT.l}" y1="${PLOT.t}" x2="${PLOT.l}" y2="${PLOT.t + altoUtil}" stroke="var(--border)" stroke-width="0.8"></line>
        <line x1="${PLOT.l}" y1="${PLOT.t + altoUtil}" x2="${PLOT.w - PLOT.r}" y2="${PLOT.t + altoUtil}" stroke="var(--border)" stroke-width="0.8"></line>
        <text x="${PLOT.l - 4}" y="${PLOT.t + 4}" class="chart-axis" text-anchor="end">${escapeHtml(o.formato(max))}</text>
        <text x="${PLOT.l - 4}" y="${PLOT.t + altoUtil}" class="chart-axis" text-anchor="end">${escapeHtml(o.formato(min))}</text>`;

    const indicesX = puntos.length > 2 ? [0, Math.floor((puntos.length - 1) / 2), puntos.length - 1] : puntos.map((_, i) => i);
    const etiquetasX = [...new Set(indicesX)].map(i => {
        const anclaje = i === 0 ? 'start' : (i === puntos.length - 1 ? 'end' : 'middle');
        return `<text x="${x(i).toFixed(1)}" y="${PLOT.h - 6}" class="chart-axis" text-anchor="${anclaje}">${escapeHtml(puntos[i].label)}</text>`;
    }).join('');

    return `<svg class="chart-svg" viewBox="0 0 ${PLOT.w} ${PLOT.h}" role="img"
        aria-label="${escapeHtml(o.aria || 'Serie temporal')}">
        ${fondo}${ejes}
        <path d="${area}" fill="${o.color}" opacity="0.10"></path>
        <path d="${linea}" fill="none" stroke="${o.color}" stroke-width="1.8"
              stroke-linejoin="round" stroke-linecap="round"></path>
        ${circulos}${etiquetasX}
    </svg>`;
}

/* Medidor: un solo valor sobre su escala, con la banda de referencia detrás.

   Existe porque una línea de UN punto no es una gráfica -- es un punto suelto
   en medio de un rectángulo, que es exactamente como se veía la carga de un
   atleta en su primera semana. Con un valor solo lo informativo no es la
   tendencia (no hay), es DÓNDE cae respecto al rango, y eso un medidor lo
   dice mejor que cualquier línea. */
function medidorEnBanda(valor, banda, alto, formato) {
    const escalaMin = Math.min(valor, banda[0]) - 0.2;
    const escalaMax = Math.max(valor, alto) + 0.15;
    const pos = v => ((v - escalaMin) / (escalaMax - escalaMin)) * 100;
    const dentro = valor >= banda[0] && valor <= banda[1];
    return `<div class="gauge">
        <div class="gauge-track">
            <span class="gauge-band" style="left:${pos(banda[0])}%;width:${pos(banda[1]) - pos(banda[0])}%"></span>
            <span class="gauge-limit" style="left:${pos(alto)}%"></span>
            <span class="gauge-needle ${dentro ? 'ok' : 'off'}" style="left:${pos(valor)}%"></span>
        </div>
        <div class="gauge-scale">
            <span>${formato(escalaMin)}</span>
            <span class="gauge-now ${dentro ? 'ok' : 'off'}">${formato(valor)}</span>
            <span>${formato(escalaMax)}</span>
        </div>
        <div class="chart-legend">
            <span><i class="swatch" style="background:var(--success);opacity:.35"></i>Zona habitual ${formato(banda[0])}–${formato(banda[1])}</span>
            <span><i class="swatch dashed"></i>Referencia alta ${formato(alto)}</span>
        </div>
    </div>`;
}

/* Barras pareadas: lo prescrito contra lo hecho. La comparación es el
   contenido de la gráfica, así que las dos barras van juntas y no apiladas —
   apiladas se leería como si sumaran, y no suman: una es el objetivo. */
function barrasPareadas(puntos, claveA, claveB, etiquetaA, etiquetaB, unidad) {
    const max = Math.max(...puntos.flatMap(p => [p[claveA], p[claveB]]), 0.1);
    const barras = puntos.map(p => {
        const altoA = Math.max(2, (p[claveA] / max) * 100);
        const altoB = Math.max(2, (p[claveB] / max) * 100);
        // Cumplido = verde; por debajo = acento. No al revés: el rojo para
        // "entrenaste menos de lo previsto" convertiría una semana de la vida
        // real en un error.
        const color = p[claveB] >= p[claveA] * 0.9 ? 'var(--success)' : 'var(--accent)';
        return `<div class="chart-bar-group" title="${escapeHtml(p.label)}: ${p[claveB]} de ${p[claveA]} ${unidad}">
            <span class="chart-bar-value">${p[claveB]}</span>
            <div class="chart-bars">
                <span class="chart-bar ghost" style="height:${altoA}%"></span>
                <span class="chart-bar" style="height:${altoB}%;background:${color}"></span>
            </div>
            <span class="chart-xlabel">${escapeHtml(p.label)}</span>
        </div>`;
    }).join('');
    return `<div class="chart-plot">${barras}</div>
        <div class="chart-legend">
            <span><i class="swatch ghost"></i>${escapeHtml(etiquetaA)}</span>
            <span><i class="swatch" style="background:var(--success)"></i>${escapeHtml(etiquetaB)}</span>
            <span class="chart-axis-note">Escala hasta ${Math.round(max * 10) / 10} ${escapeHtml(unidad)}</span>
        </div>`;
}

/* Reparto por disciplina. Barras horizontales y no un donut: comparar
   longitudes es más fácil que comparar ángulos, y los nombres caben. */
function barrasReparto(puntos) {
    return `<div class="chart-split">${puntos.map((p, i) => `
        <div class="split-row">
            <span class="split-label">${escapeHtml(p.label)}</span>
            <span class="split-track">
                <span class="split-fill" style="width:${p.pct}%;background:${CHART_COLORS[i % CHART_COLORS.length]}"></span>
            </span>
            <span class="split-value">${Math.round(p.minutes / 60 * 10) / 10} h</span>
        </div>`).join('')}</div>`;
}

/* Volumen acumulado por deporte. Las horas mandan la barra porque son lo
   único que compara de verdad un deporte con otro (40 km de bici y 40 km de
   carrera no son el mismo entrenamiento); los km van al lado, y sólo donde
   existen — una sesión de fuerza no tiene kilómetros y un cero ahí se leería
   como que no se entrenó. */
function barrasVolumen(puntos) {
    const max = Math.max(...puntos.map(p => p.hours), 0.1);
    return `<div class="chart-split">${puntos.map((p, i) => `
        <div class="split-row wide">
            <span class="split-label">${escapeHtml(p.label)}</span>
            <span class="split-track">
                <span class="split-fill" style="width:${Math.max(2, p.hours / max * 100)}%;background:${CHART_COLORS[i % CHART_COLORS.length]}"></span>
            </span>
            <span class="split-value">${p.hours} h${p.km ? ` · ${p.km} km` : ''}</span>
        </div>`).join('')}</div>`;
}

/* Balance de carga: la barra es lo que llevas, la banda gris de detrás es el
   rango que Garmin calculó para ti. Sin la banda, "506" no significa nada. */
function barrasBalance(puntos) {
    const tope = Math.max(...puntos.map(p => Math.max(p.load, p.target_max)), 1);
    return `<div class="chart-split">${puntos.map(p => {
        const dentro = p.load >= p.target_min && p.load <= p.target_max;
        return `<div class="balance-row">
            <span class="split-label">${escapeHtml(p.label)}</span>
            <span class="balance-track" title="${escapeHtml(p.label)}: ${p.load} (rango ${p.target_min}–${p.target_max})">
                <span class="balance-band" style="left:${p.target_min / tope * 100}%;width:${(p.target_max - p.target_min) / tope * 100}%"></span>
                <span class="balance-fill" style="width:${p.load / tope * 100}%;background:${dentro ? 'var(--success)' : 'var(--warning)'}"></span>
            </span>
            <span class="split-value">${p.load}</span>
        </div>`;
    }).join('')}
    <div class="chart-legend">
        <span><i class="swatch" style="background:var(--success)"></i>Dentro del rango</span>
        <span><i class="swatch" style="background:var(--bg-tertiary);border:1px solid var(--border)"></i>Rango que Garmin calculó para ti</span>
    </div></div>`;
}

/* Adherencia por día de la semana. El porcentaje va escrito encima de cada
   día: la altura sola no distingue "3 de 4" de "6 de 8". */
function barrasDiaSemana(puntos) {
    const max = Math.max(...puntos.map(p => p.prescribed), 1);
    return `<div class="chart-plot">${puntos.map(p => {
        const flojo = p.prescribed >= 2 && (p.pct || 0) < 60;
        return `<div class="chart-bar-group" title="${escapeHtml(p.full)}: ${p.done} de ${p.prescribed} sesiones">
            <span class="chart-bar-value ${flojo ? 'weak' : ''}">${p.pct === null ? '—' : p.pct + '%'}</span>
            <div class="chart-bars">
                <span class="chart-bar ghost" style="height:${Math.max(2, p.prescribed / max * 100)}%"></span>
                <span class="chart-bar" style="height:${Math.max(2, p.done / max * 100)}%;background:${flojo ? 'var(--warning)' : 'var(--success)'}"></span>
            </div>
            <span class="chart-xlabel">${escapeHtml(p.label)}</span>
        </div>`;
    }).join('')}</div>
    <div class="chart-legend">
        <span><i class="swatch ghost"></i>Prescritas</span>
        <span><i class="swatch" style="background:var(--success)"></i>Hechas</span>
    </div>`;
}

async function loadCharts() {
    const cont = document.getElementById('homeCharts');
    if (!cont) return;
    const profileName = currentProfile?.name;
    if (!profileName) { cont.innerHTML = ''; return; }

    let datos;
    try {
        const res = await fetch(`/api/charts?profile=${encodeURIComponent(profileName)}`);
        if (!res.ok) { cont.innerHTML = ''; return; }
        datos = await res.json();
    } catch { cont.innerHTML = ''; return; }

    const g = datos.charts || {};
    const tarjetas = [];

    // El orden es el de la pregunta que responden, de la más importante a la
    // más de contexto: ¿voy a llegar? → ¿cumplo? → ¿cuándo fallo? → ¿cómo voy
    // de forma y de frescura? → ¿cómo está repartido?
    if (g.race_goal) {
        const r = g.race_goal;
        tarjetas.push(chartCard(
            'Tu meta y lo que predice tu reloj',
            `${r.race_name} · ${r.race_date} · ahora ${r.current_clock}, meta ${r.goal_clock}` +
            (r.reached ? ' · ya estás dentro' : ` · faltan ${r.gap_clock}`),
            lineaSerie(r.points, {
                meta: r.goal_seconds, formato: relojDesdeSegundos, color: 'var(--accent)',
                aria: 'Predicción de carrera frente a la meta',
            }),
            r.note,
        ));
    }
    if (g.plan_vs_reality) {
        tarjetas.push(chartCard(
            'Lo que te mandé y lo que hiciste',
            `${g.plan_vs_reality.total_executed} h de ${g.plan_vs_reality.total_prescribed} h · ${g.plan_vs_reality.pct}%`,
            barrasPareadas(g.plan_vs_reality.points, 'prescribed', 'executed', 'Prescrito', 'Hecho', 'h'),
        ));
    }
    if (g.quality) {
        tarjetas.push(chartCard(
            'Sesiones de calidad',
            `${g.quality.total_done} de ${g.quality.total_prescribed}${g.quality.pct !== null ? ` · ${g.quality.pct}%` : ''}`,
            barrasPareadas(g.quality.points, 'prescribed', 'done', 'Prescritas', 'Hechas', 'sesiones'),
            'Series, umbral, VO2max y día de carrera. El rodaje suave se hace casi siempre; estas no. '
            + 'Por eso van aparte del total de horas.',
        ));
    }
    if (g.weekday) {
        tarjetas.push(chartCard(
            'Qué día se te cae el entreno',
            g.weekday.weakest ? `El día que peor se te da es el ${g.weekday.weakest}` : 'Sin un día que falle más que otro',
            barrasDiaSemana(g.weekday.points),
            g.weekday.note,
        ));
    }
    if (g.load) {
        // Con un solo valor no hay tendencia que dibujar, pero el valor SÍ
        // dice algo contra su banda: se enseña como medidor y no como una
        // línea de un punto, que es como se veía en la primera semana.
        const cuerpo = g.load.points.length > 1
            ? lineaSerie(g.load.points.map(p => ({ label: p.label, value: p.acwr })), {
                banda: g.load.sweet_spot, umbral: g.load.high,
                formato: v => (Math.round(v * 100) / 100).toFixed(2),
                aria: 'Evolución de la carga aguda/crónica',
            }) + `<div class="chart-legend">
                    <span><i class="swatch" style="background:var(--success);opacity:.35"></i>Zona habitual ${g.load.sweet_spot[0]}–${g.load.sweet_spot[1]}</span>
                    <span><i class="swatch dashed"></i>Referencia alta ${g.load.high}</span>
                  </div>`
            : medidorEnBanda(g.load.current, g.load.sweet_spot, g.load.high,
                v => (Math.round(v * 100) / 100).toFixed(2));
        tarjetas.push(chartCard(
            'Tu carga de entrenamiento',
            `Ahora: ${g.load.current}${g.load.garmin_status ? ` · Garmin dice ${g.load.garmin_status}` : ''}`,
            cuerpo,
            g.load.points.length > 1 ? g.load.note
                : g.load.note + ' Con una sola semana registrada todavía no hay tendencia: aquí ves dónde cae tu valor de hoy.',
        ));
    }
    if (g.fitness) {
        const f = g.fitness;
        const signo = f.change > 0 ? '+' : '';
        tarjetas.push(chartCard(
            'Tu VO2max',
            `${f.current} ${f.unit}${f.change ? ` · ${signo}${f.change} desde el primer dato` : ''}`,
            lineaSerie(f.points, { color: 'var(--success)', aria: 'Evolución del VO2max' }),
            f.note,
        ));
    }
    if (g.recovery) {
        const r = g.recovery;
        let cuerpo = '';
        if (r.hrv) {
            cuerpo += `<div class="chart-sub">HRV nocturna — ahora ${r.hrv.current} ${r.hrv.unit}${r.hrv.status ? ` (${escapeHtml(String(r.hrv.status).toLowerCase())})` : ''}</div>`
                + lineaSerie(r.hrv.points, { banda: r.hrv.band, color: 'var(--accent)', aria: 'Evolución de la HRV' });
        }
        if (r.rhr) {
            cuerpo += `<div class="chart-sub">Pulso en reposo — ahora ${r.rhr.current} ${r.rhr.unit}</div>`
                + lineaSerie(r.rhr.points, { color: 'var(--warning)', formato: v => String(Math.round(v)), aria: 'Evolución del pulso en reposo' });
        }
        tarjetas.push(chartCard('Tu recuperación', '', cuerpo, r.note));
    }
    if (g.balance) {
        tarjetas.push(chartCard(
            'Cómo se reparte tu carga',
            g.balance.feedback ? `Garmin dice: ${g.balance.feedback}` : '',
            barrasBalance(g.balance.points),
            g.balance.note,
        ));
    }
    if (g.volume) {
        tarjetas.push(chartCard(
            'Cuánto llevas entrenado',
            `${g.volume.total_hours} h reales en ${g.volume.weeks} semana(s)` + (g.volume.total_km ? ` · ${g.volume.total_km} km` : ''),
            barrasVolumen(g.volume.points),
            'Sólo lo que de verdad hiciste y emparejó con una sesión del plan.',
        ));
    }
    if (g.discipline) {
        tarjetas.push(chartCard(
            'Tu semana por deporte',
            `${g.discipline.total_hours} h en total`,
            barrasReparto(g.discipline.points),
        ));
    }

    // Sin gráficas no se deja un hueco ni un esqueleto vacío: se dice por qué
    // todavía no hay nada y qué las hará aparecer.
    cont.innerHTML = (tarjetas.length ? cabeceraGraficas(datos) + `<div class="chart-grid">${tarjetas.join('')}</div>` : '')
        + (!tarjetas.length && datos.weeks_recorded
            ? `<div class="chart-empty">Tus gráficas aparecerán cuando termines tu primera semana: hacen falta
               dos semanas para que haya una tendencia que enseñar.</div>`
            : '');
    document.getElementById('btnRefreshCharts')?.addEventListener('click', refreshCharts);
}

/* La barra de "cuándo se leyó esto por última vez".

   Un tablero que enseña un número viejo sin decir que es viejo es peor que uno
   que no lo enseña. El refresco lee el reloj y no llama a ningún modelo: son
   tres peticiones a Garmin, así que puede pulsarse a diario sin coste de IA. */
function cabeceraGraficas(datos) {
    const cuando = datos.last_refresh
        ? (datos.stale ? `Datos del ${datos.last_refresh}` : 'Datos de hoy')
        : 'Datos de tu última generación';
    return `<div class="charts-bar">
        <span class="charts-freshness${datos.stale === false ? ' fresh' : ''}">${escapeHtml(cuando)}</span>
        <button type="button" class="btn btn-outline btn-sm" id="btnRefreshCharts">Actualizar desde Garmin</button>
    </div>`;
}

async function refreshCharts() {
    const boton = document.getElementById('btnRefreshCharts');
    if (!boton || !currentProfile?.name) return;
    boton.disabled = true;
    boton.textContent = 'Leyendo tu reloj…';
    try {
        const res = await fetch(`/api/charts/refresh?profile=${encodeURIComponent(currentProfile.name)}`,
                                { method: 'POST' });
        if (!res.ok) {
            const d = await res.json().catch(() => ({}));
            boton.textContent = d.detail || 'No se pudo actualizar';
            boton.disabled = false;
            return;
        }
        await loadCharts();
    } catch {
        boton.textContent = 'No se pudo actualizar';
        boton.disabled = false;
    }
}
