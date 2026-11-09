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
                Reservas <span class="nav-count">{{ store.groups.length }}</span>
            </button>
        </nav>
        <button class="help" @click="modal = { kind: 'help' }"><Info :size="16" /><span>Cómo funciona</span></button>
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
                        <button class="primary" :disabled="!chosen.length || store.loading || !!store.error" @click="modal = { kind: 'confirm' }">
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
        <section v-else class="reservations-view">
            <div class="section-top">
                <div><span class="section-number">02 /</span><h2>Reservas del evento</h2></div>
                <button class="secondary" @click="store.load"><RefreshCw :size="16" />Actualizar</button>
            </div>
            <p class="muted">Consulta las mesas ocupadas y libera una reserva si se cancela.</p>
            <p v-if="store.error" role="alert" class="error">{{ store.error }}</p>

            <div v-if="!store.groups.length" class="reservations-empty">
                <Ticket :size="44" :stroke-width="1" />
                <h2>{{ store.loading ? 'Consultando reservas…' : 'La primera mesa puede ser la tuya.' }}</h2>
                <p>Aquí aparecerán las reservas que confirmes.</p>
                <button class="primary" @click="tab = 'map'">Elegir una mesa<ArrowRight :size="18" /></button>
            </div>

            <div v-else class="booking-cards">
                <article v-for="rs in store.groups" :key="rs[0].bookingId" class="booking-card">
                    <div class="booking-card-top">
                        <span><i class="live-dot" />Confirmada</span>
                        <span>#{{ rs[0].bookingId.slice(0, 8).toUpperCase() }}</span>
                    </div>
                    <h3>{{ rs[0].name }}</h3>
                    <div class="booking-chips"><span v-for="r in rs" :key="r.tableId">A{{ r.tableId }}</span></div>
                    <p><Users :size="15" />{{ rs.length * 8 }} entradas · {{ money(groupTotal(rs)) }}</p>
                    <div class="booking-card-bottom">
                        <small>{{ new Date(rs[0].createdAt).toLocaleString('es-GT', { dateStyle: 'medium', timeStyle: 'short' }) }}</small>
                        <button @click="modal = { kind: 'cancel', bookingId: rs[0].bookingId, ids: rs.map((r) => r.tableId) }">
                            <Trash2 :size="14" />Cancelar reserva
                        </button>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <footer>
        <a :href="baseUrl" class="footer-brand"><img :src="logo" alt="" width="34" height="34" /> SANAFER</a>
        <span>Buena música. Buena compañía. Una gran noche.</span>
        <span>BAILE SOCIAL</span>
    </footer>

    <BookingDialog
        v-model:name="name"
        :modal="modal"
        :selected="selected"
        :total="total"
        :busy="busy"
        :error="store.error"
        @close="modal = null"
        @book="book"
        @cancel="cancel"
        @go-reservations="modal = null; tab = 'reservations'"
    />
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { ArrowRight, ChevronRight, Info, RefreshCw, Ticket, Trash2, Users, X } from 'lucide-vue-next';
import { useReservationsStore } from '../../stores/reservations';

const baseUrl = import.meta.env.BASE_URL;
import { categories, money, tableById, tables, TOTAL_TABLES } from '../../utils/tables';
import VenueMap from '../../components/sanafer/VenueMap.vue';
import BookingDialog from '../../components/sanafer/BookingDialog.vue';
import { notify } from '../../utils/alert';
import logo from '../../assets/images/sanafer-2026.png';

const POLL_MS = 15000;

const store = useReservationsStore();

const tab = ref('map');
const selected = ref([]);
const name = ref('');
const modal = ref(null);
const busy = ref(false);

const chosen = computed(() => tables.filter((t) => selected.value.includes(t.id)));
const total = computed(() => chosen.value.reduce((n, t) => n + categories[t.category].price, 0));
const availabilityText = computed(() => {
    if (store.loading) return 'Consultando…';
    if (store.error) return 'Sin conexión';
    return `${TOTAL_TABLES - store.occupied.size} de ${TOTAL_TABLES} mesas disponibles`;
});
const groupTotal = (rs) => rs.reduce((n, r) => n + categories[tableById(r.tableId).category].price, 0);

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
    if (busy.value) return;
    busy.value = true;
    const ids = [...selected.value];
    const res = await store.book(ids, name.value);
    busy.value = false;
    if (res.ok) {
        selected.value = [];
        name.value = '';
        modal.value = { kind: 'success', ids, bookingId: res.bookingId };
    } else {
        // Se cierra el diálogo para que el aviso de SweetAlert quede visible
        // (la selección y el nombre se conservan para reintentar).
        modal.value = null;
        notify('error', res.message);
    }
};

const cancel = async () => {
    busy.value = true;
    const res = await store.cancel(modal.value?.bookingId);
    busy.value = false;
    if (res.ok) {
        modal.value = null;
        notify('success', 'Reserva cancelada. Las mesas vuelven a estar disponibles.');
    } else {
        notify('error', res.message);
    }
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
