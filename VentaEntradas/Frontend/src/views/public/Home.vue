<template>
    <!-- Encabezado -->
    <header class="header">
        <a :href="baseUrl" class="brand" aria-label="SANAFER, inicio">
            <img class="brand-logo" :src="logo" alt="Logo de la Feria SANAFER 2026" width="52" height="52" />
            SANAFER<span class="brand-sub">BAILE<br />SOCIAL</span>
        </a>
        <nav aria-label="Navegación principal">
            <button :class="{ active: tab === 'map' }" @click="tab = 'map'">Elige tu mesa</button>
            <button :class="{ active: tab === 'reservations' }" @click="tab = 'reservations'">
                Reservas <span class="nav-count">{{ visibleGroups.length }}</span>
            </button>
            <button v-if="auth.isAdmin" :class="{ active: tab === 'users' }" @click="tab = 'users'">Usuarios</button>
        </nav>
        <div class="header-actions">
            <button class="help" @click="helpBox"><Info :size="16" /><span>Cómo funciona</span></button>
            <span class="user-chip">
                <UserRound :size="18" />
                <span>{{ auth.user.fullName }}<small>{{ auth.roleLabel }}</small></span>
            </span>
            <button class="help" @click="logout"><LogOut :size="16" /><span>Salir</span></button>
        </div>
    </header>

    <main>
        <!-- Hero -->
        <section class="hero">
            <div>
                <div class="eyebrow"><span /> BAILE SOCIAL SANAFER</div>
                <h1>Una gran noche.<br /><em>Tu mejor lugar.</em></h1>
                <p>Elige tu mesa, reúne a los tuyos y que empiece el baile.</p>
            </div>
            <div class="hero-art">
                <img :src="logo" alt="Feria SANAFER 2026" width="320" height="320" fetchpriority="high" />
            </div>
            <div class="hero-ticket">
                <Ticket :size="23" :stroke-width="1.4" />
                <div><strong>Una mesa, 8 historias.</strong><span>8 entradas incluidas por mesa</span></div>
            </div>
        </section>

        <!-- Pestaña: mapa + panel de selección -->
        <template v-if="tab === 'map'">
            <div class="section-top">
                <div><span class="section-number">01 /</span><h2>Encuentra tu lugar</h2></div>
                <span class="availability"><span class="live-dot" />{{ availabilityText }}</span>
            </div>

            <section class="booking-layout">
                <VenueMap
                    :selected="selected"
                    :occupied="store.occupied"
                    :loading="store.loading"
                    :error="store.error"
                    :last-update="store.lastUpdate"
                    @toggle="toggle"
                    @reload="store.load"
                />

                <aside class="reservation-panel">
                    <div class="panel-top">
                        <div><span class="eyebrow">LA NOCHE ES TUYA</span><h2>Tu selección</h2></div>
                        <Ticket :size="25" :stroke-width="1.3" />
                    </div>
                    <div class="panel-body">
                        <template v-if="chosen.length">
                            <div class="selection-heading">
                                <span>{{ chosen.length }} {{ chosen.length === 1 ? 'mesa seleccionada' : 'mesas seleccionadas' }}</span>
                                <button class="text-button" @click="selected = []">Limpiar</button>
                            </div>
                            <div class="selection-list">
                                <div v-for="t in chosen" :key="t.id" class="selection-item">
                                    <span :class="['table-badge', t.category]">A{{ t.id }}</span>
                                    <div><strong>{{ categories[t.category].name }}</strong><span>8 entradas</span></div>
                                    <strong>{{ money(categories[t.category].price) }}</strong>
                                    <button :aria-label="`Quitar mesa A${t.id}`" @click="toggle(t.id)"><X :size="15" /></button>
                                </div>
                            </div>
                            <div class="guest-count"><Users :size="16" />{{ chosen.length * 8 }} entradas incluidas</div>
                        </template>
                        <div v-else class="empty-selection">
                            <div class="empty-icon"><Ticket :size="33" :stroke-width="1" /><span>+</span></div>
                            <h3>Tu mesa te espera</h3>
                            <p>Selecciona en el mapa el lugar<br />donde empieza tu noche.</p>
                        </div>

                        <div class="total"><span>Total de la reserva</span><strong>{{ money(total) }}<small>.00</small></strong></div>
                        <button class="primary" :disabled="!chosen.length || store.loading || !!store.error" @click="book">
                            Reservar {{ chosen.length > 1 ? 'mesas' : 'mesa' }}<ArrowRight :size="18" />
                        </button>
                        <div class="payment-note">Reserva de mesas · No se realiza ningún cobro</div>
                    </div>
                    <div class="panel-tip">
                        <Users :size="19" />
                        <p><strong>Mejor, juntos.</strong>Cada mesa incluye 8 entradas y se reserva completa.</p>
                    </div>
                    <div class="ticket-perforation" />
                </aside>
            </section>

            <div class="how-strip">
                <div><span>01</span><p><strong>Encuentra tu mesa</strong>Explora las tres localidades.</p></div>
                <ChevronRight :size="17" />
                <div><span>02</span><p><strong>Hazla tuya</strong>Registra el nombre de tu reserva.</p></div>
                <ChevronRight :size="17" />
                <div><span>03</span><p><strong>¡Nos vemos en la pista!</strong>Tu mesa quedará como ocupada.</p></div>
            </div>
        </template>

        <!-- Pestaña: reservas -->
        <section v-else-if="tab === 'reservations'" class="reservations-view">
            <div class="section-top">
                <div><span class="section-number">02 /</span><h2>Reservas del evento</h2></div>
                <button class="secondary" @click="store.load"><RefreshCw :size="16" />Actualizar</button>
            </div>
            <p class="muted">{{ auth.isAdmin ? 'Consulta todas las reservas y libera una si se cancela.' : 'Aquí aparecen las reservas que tú registraste.' }}</p>
            <p v-if="store.error" role="alert" class="error">{{ store.error }}</p>

            <div v-if="!visibleGroups.length" class="reservations-empty">
                <Ticket :size="44" :stroke-width="1" />
                <h2>{{ store.loading ? 'Consultando reservas…' : 'La primera mesa puede ser la tuya.' }}</h2>
                <p>Aquí aparecerán las reservas que confirmes.</p>
                <button class="primary" @click="tab = 'map'">Elegir una mesa<ArrowRight :size="18" /></button>
            </div>

            <div v-else class="booking-cards">
                <article v-for="rs in visibleGroups" :key="rs[0].bookingId" class="booking-card">
                    <div class="booking-card-top">
                        <span><i class="live-dot" />Confirmada</span>
                        <span>#{{ rs[0].bookingId.slice(0, 8).toUpperCase() }}</span>
                    </div>
                    <h3>{{ rs[0].name }}</h3>
                    <div class="booking-chips"><span v-for="r in rs" :key="r.tableId">A{{ r.tableId }}</span></div>
                    <p><Users :size="15" />{{ rs.length * 8 }} entradas · {{ money(groupTotal(rs)) }}</p>
                    <p v-if="auth.isAdmin" class="booked-by"><UserRound :size="15" />Registró: {{ rs[0].userName || 'Sin registro' }}</p>
                    <div class="booking-card-bottom">
                        <small>{{ new Date(rs[0].createdAt).toLocaleString('es-GT', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'America/Guatemala' }) }}</small>
                        <button @click="cancel(rs)">
                            <Trash2 :size="14" />Cancelar reserva
                        </button>
                    </div>
                </article>
            </div>
        </section>

        <!-- Pestaña: usuarios (solo administrador) -->
        <UsersPanel v-else-if="tab === 'users' && auth.isAdmin" />
    </main>

    <footer>
        <a :href="baseUrl" class="footer-brand"><img :src="logo" alt="" width="34" height="34" /> SANAFER</a>
        <span>Buena música. Buena compañía. Una gran noche.</span>
        <span>BAILE SOCIAL</span>
    </footer>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowRight, ChevronRight, Info, LogOut, RefreshCw, Ticket, Trash2, UserRound, Users, X } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import { useReservationsStore } from '../../stores/reservations';

const baseUrl = import.meta.env.BASE_URL;
import { categories, money, tableById, tables, TOTAL_TABLES } from '../../utils/tables';
import VenueMap from '../../components/sanafer/VenueMap.vue';
import UsersPanel from '../../components/sanafer/UsersPanel.vue';
import { bookingBox, cancelBookingBox, confirmBox, helpBox, notify, successBox } from '../../utils/alert';
import logo from '../../assets/images/sanafer-2026.png';

const POLL_MS = 15000;

const store = useReservationsStore();
const auth = useAuthStore();
const router = useRouter();

const tab = ref('map');
const selected = ref([]);
const name = ref('');

const chosen = computed(() => tables.filter((t) => selected.value.includes(t.id)));
const total = computed(() => chosen.value.reduce((n, t) => n + categories[t.category].price, 0));
const availabilityText = computed(() => {
    if (store.loading) return 'Consultando…';
    if (store.error) return 'Sin conexión';
    return `${TOTAL_TABLES - store.occupied.size} de ${TOTAL_TABLES} mesas disponibles`;
});
// El administrador ve todas las reservas; el vendedor, solo las suyas.
const visibleGroups = computed(() => (auth.isAdmin ? store.groups : store.groups.filter((rs) => rs[0].mine)));
// Monto guardado por el servidor; si la reserva es antigua (sin precio), se calcula con las categorías.
const groupTotal = (rs) =>
    rs.every((r) => r.price != null)
        ? rs.reduce((n, r) => n + r.price, 0)
        : rs.reduce((n, r) => n + categories[tableById(r.tableId).category].price, 0);

const logout = async () => {
    if (!(await confirmBox('¿Cerrar sesión?', 'Tendrás que ingresar de nuevo con tu usuario y contraseña.', 'Sí, salir'))) return;
    auth.logout();
    store.reset();
    router.replace({ name: 'Login' });
};

const toggle = (id) => {
    if (store.occupied.has(id) || store.loading || store.error) return;
    selected.value = selected.value.includes(id) ? selected.value.filter((i) => i !== id) : [...selected.value, id];
};

// Si alguien más ocupa una mesa que tenías seleccionada, se quita de la selección.
watch(
    () => store.occupied,
    (occupied) => {
        selected.value = selected.value.filter((id) => !occupied.has(id));
    }
);

const book = async () => {
    const ids = [...selected.value];
    const out = await bookingBox({ ids, total: total.value, name: name.value, submit: (n) => store.book(ids, n) });
    name.value = out.name;
    if (out.cancelled) return;
    if (out.result.ok) {
        selected.value = [];
        name.value = '';
        if (await successBox(ids, out.result.bookingId)) tab.value = 'reservations';
    } else {
        // La selección y el nombre se conservan para reintentar.
        notify('error', out.result.message);
    }
};

const cancel = async (rs) => {
    const res = await cancelBookingBox(
        rs.map((r) => r.tableId),
        () => store.cancel(rs[0].bookingId)
    );
    if (!res) return;
    if (res.ok) notify('success', 'Reserva cancelada. Las mesas vuelven a estar disponibles.');
    else notify('error', res.message);
};

let pollTimer;
onMounted(() => {
    store.load();
    pollTimer = setInterval(store.load, POLL_MS);
});
onBeforeUnmount(() => {
    clearInterval(pollTimer);
});
</script>
