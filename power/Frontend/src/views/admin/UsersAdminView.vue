<template>
  <div id="users-admin-view-container" class="space-y-7 pb-20 select-none animate-fade-in-up">
    <!-- Header -->
    <header class="pb-5 border-b border-white/10">
      <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
        Usuarios de la Beta
      </h2>
      <p class="text-sm text-[#c5c9ac] mt-1 font-medium">
        Aprueba o rechaza las solicitudes de acceso de quienes inician sesión con Google.
      </p>
    </header>

    <!-- Search -->
    <div class="relative max-w-md">
      <span class="material-symbols-outlined absolute left-3.5 top-3.5 text-[#c5c9ac] text-[20px]">
        search
      </span>
      <input
        id="users-search-input"
        v-model="searchQuery"
        type="text"
        placeholder="Buscar por nombre o correo..."
        class="w-full glass-input rounded-xl pl-11 pr-4 py-3 text-white text-sm focus:outline-none transition-colors"
      />
    </div>

    <div v-if="error" class="p-4 bg-[#ffb4ab]/20 border border-[#ffb4ab]/40 rounded-xl text-[#ffb4ab] text-sm">
      {{ error }}
    </div>

    <!-- Pending Requests -->
    <section class="space-y-4">
      <h3 class="text-xs font-black text-[#c5c9ac] uppercase tracking-widest">
        Pendientes ({{ filteredPending.length }})
      </h3>

      <div v-if="!isLoading && filteredPending.length === 0" class="p-8 text-center glass-card rounded-3xl text-[#c5c9ac] text-sm border border-white/10">
        {{ searchQuery ? 'No hay solicitudes pendientes que coincidan con tu búsqueda.' : 'No hay solicitudes pendientes.' }}
      </div>

      <div
        v-for="user in filteredPending"
        :key="user.id"
        :id="`pending-user-${user.id}`"
        class="glass-card rounded-3xl p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-5 hover:border-[#caf300]/50 transition-all group border border-white/10 shadow-2xl"
      >
        <div class="flex items-center gap-4 min-w-0">
          <div class="w-14 h-14 rounded-2xl bg-white/10 border border-white/15 overflow-hidden shrink-0 group-hover:scale-105 group-hover:border-[#caf300]/50 transition-all shadow-[0_0_15px_rgba(202,243,0,0.15)]">
            <img v-if="user.avatarUrl" :src="user.avatarUrl" :alt="user.name" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center text-[#caf300]">
              <span class="material-symbols-outlined text-[28px]">person</span>
            </div>
          </div>
          <div class="min-w-0">
            <h3 class="text-lg sm:text-xl font-black text-white group-hover:text-[#caf300] transition-colors truncate">
              {{ user.name }}
            </h3>
            <p class="text-xs text-[#c5c9ac] mt-1 font-mono-data truncate">
              {{ user.email }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-white/10">
          <button
            :id="`btn-approve-${user.id}`"
            @click="approve(user.id)"
            class="px-6 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-sm active:scale-95 hover:scale-105"
          >
            Aprobar
          </button>
          <button
            :id="`btn-reject-${user.id}`"
            @click="reject(user.id)"
            class="px-6 py-2.5 glass-pill hover:bg-[#93000a]/30 text-[#ff8f83] rounded-xl text-xs font-black uppercase tracking-wider transition-all active:scale-95 hover:scale-105"
          >
            Rechazar
          </button>
        </div>
      </div>
    </section>

    <!-- Approved Users -->
    <section class="space-y-4">
      <h3 class="text-xs font-black text-[#c5c9ac] uppercase tracking-widest">
        Aprobados ({{ filteredApproved.length }})
      </h3>

      <div v-if="!isLoading && filteredApproved.length === 0" class="p-8 text-center glass-card rounded-3xl text-[#c5c9ac] text-sm border border-white/10">
        {{ searchQuery ? 'No hay usuarios aprobados que coincidan con tu búsqueda.' : 'Todavía no hay usuarios aprobados.' }}
      </div>

      <div
        v-for="user in filteredApproved"
        :key="user.id"
        :id="`approved-user-${user.id}`"
        class="glass-card rounded-3xl p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-5 hover:border-[#caf300]/50 transition-all group border border-white/10 shadow-2xl"
      >
        <div class="flex items-center gap-4 min-w-0">
          <div class="w-14 h-14 rounded-2xl bg-white/10 border border-white/15 overflow-hidden shrink-0 group-hover:scale-105 group-hover:border-[#caf300]/50 transition-all shadow-[0_0_15px_rgba(202,243,0,0.15)]">
            <img v-if="user.avatarUrl" :src="user.avatarUrl" :alt="user.name" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center text-[#caf300]">
              <span class="material-symbols-outlined text-[28px]">person</span>
            </div>
          </div>
          <div class="min-w-0">
            <h3 class="text-lg sm:text-xl font-black text-white group-hover:text-[#caf300] transition-colors truncate">
              {{ user.name }}
            </h3>
            <p class="text-xs text-[#c5c9ac] mt-1 font-mono-data truncate">
              {{ user.email }}
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
          <!-- Hasta cuándo puede generar, y el pago de la suscripción (hecho fuera de la app). -->
          <span v-if="user.acceso && user.role !== 'admin'" class="text-[11px] font-mono-data"
                :class="user.acceso.estado === 'vencido' ? 'text-[#ffd166]' : 'text-[#c5c9ac]'">
            {{ textoDeAcceso(user.acceso) }}
          </span>
          <button v-if="user.role !== 'admin' && user.acceso && user.acceso.estado !== 'sin_muro'" type="button"
                  @click="verPagos(user)"
                  class="px-3 py-1.5 rounded-lg border border-white/20 text-[#c5c9ac] text-[10px] font-black uppercase tracking-wider hover:bg-white/10">
            Pagos
          </button>
          <button v-if="user.role !== 'admin' && user.acceso && user.acceso.estado !== 'sin_muro'" type="button"
                  @click="registrarPago(user)"
                  class="px-3 py-1.5 rounded-lg border border-[#caf300]/40 text-[#caf300] text-[10px] font-black uppercase tracking-wider hover:bg-[#caf300]/10">
            Registrar pago
          </button>
          <span
            class="px-3 py-1 rounded-lg font-mono-data text-[10px] font-black uppercase tracking-wider border"
            :class="user.role === 'admin'
              ? 'text-[#a5eeff] bg-[#a5eeff]/15 border-[#a5eeff]/40'
              : 'text-[#caf300] bg-[#caf300]/15 border-[#caf300]/40'"
          >
            {{ user.role }}
          </span>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../../services/api';
import { useAppStore } from '../../stores/appStore';
import swal from '../../utils/swal';

const store = useAppStore();

const pendingUsers = ref([]);
const approvedUsers = ref([]);
const isLoading = ref(false);
const error = ref('');
const searchQuery = ref('');

const matchesQuery = (user) => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return true;
  return user.name.toLowerCase().includes(q) || user.email.toLowerCase().includes(q);
};

const filteredPending = computed(() => pendingUsers.value.filter(matchesQuery));
const filteredApproved = computed(() => approvedUsers.value.filter(matchesQuery));

const loadUsers = async () => {
  isLoading.value = true;
  error.value = '';
  try {
    const [pendingRes, approvedRes] = await Promise.all([
      api.get('/admin/users/pending'),
      api.get('/admin/users/approved'),
    ]);
    pendingUsers.value = pendingRes.data.data;
    approvedUsers.value = approvedRes.data.data;
  } catch (e) {
    error.value = 'No se pudieron cargar los usuarios.';
  } finally {
    isLoading.value = false;
  }
};

// Aprobar y rechazar tocan la cuenta de OTRA persona, asi que un fallo mudo
// aqui es peor que en cualquier otro boton: el administrador cree que ya dejo
// entrar a alguien que sigue esperando. Ver `avisarDelFallo` en PlanView.vue,
// mismo motivo.
const decidirSobre = async (id, accion, hecho) => {
  try {
    await api.post(`/admin/users/${id}/${accion}`);
  } catch (e) {
    console.error(`No se pudo ${accion} al usuario ${id}`, e);
    error.value = e?.response?.data?.message || `No se pudo ${accion === 'approve' ? 'aprobar' : 'rechazar'} al usuario.`;
    return;
  }
  error.value = '';
  store.showToast(hecho);
  await loadUsers();
};

const approve = (id) => decidirSobre(id, 'approve', 'Usuario aprobado.');

// El acceso de cada atleta (AccessService): semanas gratis o mes pagado.
// Fechas ISO a propósito: es la pantalla del administrador y se comparan de un vistazo.
const textoDeAcceso = (a) => ({
  prueba: `Prueba hasta ${a.acceso_hasta} (${a.dias_restantes} d)`,
  pagado: `Pagado hasta ${a.acceso_hasta}`,
  vencido: `Sin acceso desde ${a.acceso_hasta}`,
  sin_empezar: 'Sin Garmin conectado',
  garmin_usado: 'Garmin sin semanas gratis (conectado antes de la 035, o ya visto)',
  sin_muro: 'Sin muro (faltan migraciones)',
}[a.estado] || '');

// Un pago hecho FUERA de la app (transferencia, efectivo…): alarga su acceso desde donde terminaba.
const registrarPago = async (user) => {
  const precio = user.acceso?.precio?.gtq || 60;
  const anual = user.acceso?.precio?.anual;
  const r = await swal.fire({
    title: `Pago de ${user.name}`,
    html: `<p style="font-size:13px">${textoDeAcceso(user.acceso)}. Cada mes son Q${precio}`
      + (anual ? `; el plan anual, Q${anual.gtq} por 12 meses` : '') + '.</p>'
      + '<select id="pago-meses" class="swal2-select" style="display:flex;margin:12px auto">'
      + (anual ? `<option value="anual">Plan anual (12 meses) · Q${anual.gtq}</option>` : '')
      + [1, 2, 3, 6, 12].map((m) => `<option value="${m}">${m} ${m === 1 ? 'mes' : 'meses'} · Q${m * precio}</option>`).join('')
      // Un año sin cobro para los atletas de la beta (2026-10-23): Q0 y un aviso que no dice «tu pago».
      + '<option value="cortesia">Cortesía de la beta (12 meses) · Q0, sin cobro</option>'
      + '</select><input id="pago-nota" class="swal2-input" placeholder="Nota (cómo pagó, o por qué la cortesía)" maxlength="120">',
    showCancelButton: true, confirmButtonText: 'Registrar', cancelButtonText: 'Cancelar',
    // «anual» son 12 meses a su precio (AccessService::PRECIO_ANUAL_GTQ), no 12 × Q60.
    preConfirm: () => {
      const v = document.getElementById('pago-meses').value;
      const note = document.getElementById('pago-nota').value;
      if (v === 'anual') return { plan: 'anual', months: 12, note };
      if (v === 'cortesia') return { plan: 'cortesia', months: 12, note };
      return { months: Number(v), note };
    },
  });
  if (!r.isConfirmed) return;
  try {
    const { data } = await api.post(`/admin/users/${user.id}/payment`, r.value);
    store.showToast(data.data.cortesia
      ? `Cortesía registrada (Q0): acceso hasta el ${data.data.hasta}.`
      : `Pago registrado (Q${data.data.importe_gtq}): acceso hasta el ${data.data.hasta}.`);
    await loadUsers();
  } catch (e) {
    error.value = e?.response?.data?.message || 'No se pudo registrar el pago.';
  }
};
// Los pagos de un atleta y anular el último registrado por error (Database/037). Nunca se borra:
// queda anulado, con el motivo, y deja de dar acceso.
const escapar = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const verPagos = async (user) => {
  let datos;
  try {
    ({ data: { data: datos } } = await api.get(`/admin/users/${user.id}/payments`));
  } catch (e) {
    error.value = e?.response?.data?.message || 'No se pudieron leer los pagos.';
    return;
  }
  const filas = datos.pagos.map((p) => `<li style="display:flex;justify-content:space-between;gap:8px;align-items:center;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.1);${p.anulado ? 'opacity:.55' : ''}">`
    + `<span style="text-align:left;font-size:12px">${p.paid_from} → ${p.paid_until} · ${p.months} ${p.months === 1 ? 'mes' : 'meses'} · Q${Number(p.amount_gtq)}`
    + (p.note ? `<br><small>${escapar(p.note)}</small>` : '')
    + (p.anulado ? `<br><small>Anulado: ${escapar(p.void_reason)}</small>` : '') + '</span>'
    + (p.se_puede_anular ? `<button type="button" data-anular="${p.id}" style="font-size:11px;font-weight:800;color:#ff8f83;border:1px solid #ff8f8366;border-radius:8px;padding:4px 8px">Anular</button>` : '')
    + '</li>').join('');
  let anular = null;
  await swal.fire({
    title: `Pagos de ${user.name}`,
    html: datos.pagos.length
      ? `<ul style="list-style:none;padding:0;margin:0">${filas}</ul>`
        + (datos.admite_anular ? '<p style="font-size:11px;margin-top:10px">Sólo se puede anular el último pago vigente: los siguientes empiezan donde terminaba él.</p>'
          : '<p style="font-size:11px;margin-top:10px">Para anular un pago falta la migración 037.</p>')
      : '<p style="font-size:13px">Todavía no tiene pagos registrados.</p>',
    confirmButtonText: 'Cerrar',
    didOpen: (popup) => popup.querySelectorAll('[data-anular]').forEach((b) => b.addEventListener('click', () => {
      anular = Number(b.dataset.anular);
      swal.close();
    })),
  });
  if (!anular) return;
  const pago = datos.pagos.find((p) => Number(p.id) === anular);
  const r = await swal.fire({
    icon: 'warning',
    title: 'Anular este pago',
    html: `<p style="font-size:13px">${pago.paid_from} → ${pago.paid_until} · Q${Number(pago.amount_gtq)}. Deja de dar acceso; no se borra y se le avisa al atleta.</p>`,
    input: 'text', inputPlaceholder: 'Por qué (p. ej. «lo registré en la cuenta equivocada»)',
    inputValidator: (v) => (!v || !v.trim() ? 'Escribe por qué: queda en la contabilidad.' : undefined),
    showCancelButton: true, confirmButtonText: 'Anular pago', cancelButtonText: 'Cancelar',
  });
  if (!r.isConfirmed) return;
  try {
    const { data } = await api.post(`/admin/users/${user.id}/payments/${anular}/void`, { reason: r.value });
    const a = data.data;
    store.showToast(`Pago anulado. ${a.acceso_hasta && a.acceso_hasta >= a.hoy ? `Acceso hasta el ${a.acceso_hasta}.` : 'Ya no tiene acceso para generar.'}`);
    await loadUsers();
  } catch (e) {
    error.value = e?.response?.data?.message || 'No se pudo anular el pago.';
  }
};
const reject = (id) => decidirSobre(id, 'reject', 'Usuario rechazado.');

onMounted(loadUsers);
</script>
