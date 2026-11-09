import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import reservationService from '../services/reservationService';

const errorMessage = (err, fallback) => err?.response?.data?.error || fallback;

export const useReservationsStore = defineStore('reservations', () => {
    const reservations = ref([]);
    const loading = ref(true);
    const error = ref('');
    const lastUpdate = ref('');

    const occupied = computed(() => new Set(reservations.value.map((r) => r.tableId)));

    // Una reserva puede tener varias mesas: se agrupan por bookingId.
    const groups = computed(() => {
        const acc = {};
        for (const r of reservations.value) (acc[r.bookingId] ??= []).push(r);
        return Object.values(acc).map((rs) => [...rs].sort((a, b) => a.tableId - b.tableId));
    });

    async function load() {
        try {
            const { data } = await reservationService.getAll();
            reservations.value = data;
            error.value = '';
            lastUpdate.value = new Date().toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit', timeZone: 'America/Guatemala' });
        } catch {
            error.value = 'No pudimos actualizar la disponibilidad. Reintenta antes de reservar.';
        } finally {
            loading.value = false;
        }
    }

    // Devuelve { ok, bookingId } o { ok: false, message }
    async function book(tableIds, name) {
        try {
            const { data } = await reservationService.create(tableIds, name);
            await load();
            return { ok: true, bookingId: data.bookingId };
        } catch (err) {
            if (err.response) {
                await load();
                return { ok: false, message: errorMessage(err, 'No se pudo confirmar la reserva.') };
            }
            return { ok: false, network: true, message: 'No se pudo confirmar. Revisa la conexión e intenta de nuevo.' };
        }
    }

    async function cancel(bookingId) {
        try {
            await reservationService.cancel(bookingId);
            await load();
            return { ok: true };
        } catch (err) {
            return { ok: false, message: errorMessage(err, 'No se pudo cancelar.') };
        }
    }

    return { reservations, loading, error, lastUpdate, occupied, groups, load, book, cancel };
});
