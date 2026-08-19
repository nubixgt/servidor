<template>
    <div class="space-y-6 max-w-[1600px] mx-auto pb-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Configuración del sistema</h2>
            <p class="text-xs sm:text-sm text-white/80 mt-0.5">Preferencias de usuario, unidades de medida y seguridad de la cuenta.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left column -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Profile card (read-only) -->
                <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-white/15 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-black/30 border border-white/15 flex items-center justify-center text-[#4ade80]">
                            <User class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">Tu cuenta</h3>
                            <p class="text-xs text-white/80">Información asociada a tu usuario en el sistema.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-white/60 block mb-1">Nombre completo</span>
                            <span class="text-white font-medium">{{ auth.user?.nombre }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-white/60 block mb-1">Usuario</span>
                            <span class="text-white font-medium">@{{ auth.user?.usuario }}</span>
                        </div>
                        <div v-if="auth.user?.email">
                            <span class="text-[10px] uppercase font-bold text-white/60 block mb-1">Correo electrónico</span>
                            <span class="text-white font-medium">{{ auth.user.email }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-white/60 block mb-1">Rol</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full inline-block border" :class="ROLE_BADGE[auth.role]">{{ ROLE_LABELS[auth.role] }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="text-[10px] uppercase font-bold text-white/60 block mb-1">Región asignada</span>
                            <span class="text-white font-medium">{{ auth.user?.regionAsignada || 'Nacional / sin asignar' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Password & security -->
                <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-white/15 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-black/30 border border-white/15 flex items-center justify-center text-[#facc15]">
                            <Lock class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">Seguridad y acceso</h3>
                            <p class="text-xs text-white/80">Actualización de contraseña institucional.</p>
                        </div>
                    </div>

                    <form @submit.prevent="cambiarPassword" class="space-y-3 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-white/80 block mb-1 font-medium">Contraseña actual</label>
                                <input v-model="actual" type="password" required placeholder="••••••••" class="w-full bg-white/5 border border-white/15 rounded-xl p-2.5 text-white focus:outline-none focus:border-[#22c55e]/60" />
                            </div>
                            <div>
                                <label class="text-white/80 block mb-1 font-medium">Nueva contraseña</label>
                                <input v-model="nueva" type="password" required minlength="6" placeholder="Mínimo 6 caracteres" class="w-full bg-white/5 border border-white/15 rounded-xl p-2.5 text-white focus:outline-none focus:border-[#22c55e]/60" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-2">
                            <button type="submit" :disabled="saving" class="px-5 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-black font-bold rounded-xl flex items-center gap-2 shadow-lg transition-all disabled:opacity-60">
                                <Save class="w-4 h-4" />
                                <span>{{ saving ? 'Guardando…' : 'Actualizar contraseña' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right column -->
            <div class="lg:col-span-5 space-y-6">
                <!-- Preferences -->
                <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-6 space-y-5">
                    <h3 class="text-base font-bold text-white pb-3 border-b border-white/15">Preferencias del sistema</h3>

                    <div>
                        <label class="text-xs font-semibold text-white flex items-center gap-2 mb-2">
                            <Ruler class="w-4 h-4 text-[#38bdf8]" />
                            <span>Unidades de medida de superficie</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="unitSystem = 'metric'"
                                class="p-3 rounded-xl text-xs font-medium border text-left transition-all"
                                :class="unitSystem === 'metric' ? 'bg-[#22c55e]/25 text-[#4ade80] border-[#4ade80]/40' : 'bg-black/30 text-white/80 border-white/10 hover:bg-white/5'"
                            >
                                <p class="font-bold">Métrico decimal</p>
                                <p class="text-[10px] text-white/60">Hectáreas (ha), metros (m)</p>
                            </button>
                            <button
                                type="button"
                                @click="unitSystem = 'guatemala'"
                                class="p-3 rounded-xl text-xs font-medium border text-left transition-all"
                                :class="unitSystem === 'guatemala' ? 'bg-[#22c55e]/25 text-[#4ade80] border-[#4ade80]/40' : 'bg-black/30 text-white/80 border-white/10 hover:bg-white/5'"
                            >
                                <p class="font-bold">Tradicional Guatemala</p>
                                <p class="text-[10px] text-white/60">Manzanas (mz), cuerdas</p>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="text-xs font-semibold text-white flex items-center justify-between mb-1 cursor-pointer">
                            <div class="flex items-center gap-2">
                                <Bell class="w-4 h-4 text-[#facc15]" />
                                <span>Notificaciones de alerta</span>
                            </div>
                            <input v-model="notificationsEnabled" type="checkbox" class="rounded bg-black/40 border-white/20 text-[#22c55e] focus:ring-[#22c55e] w-4 h-4" />
                        </label>
                        <p class="text-[11px] text-white/60">Recibir alertas en la campana de la barra superior cuando el sistema detecte novedades relevantes.</p>
                    </div>
                </div>

                <!-- System info -->
                <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-6 text-xs space-y-3">
                    <div class="flex items-center gap-2 text-white font-bold">
                        <Info class="w-4 h-4 text-[#4ade80]" />
                        <span>Acerca del sistema</span>
                    </div>
                    <div class="bg-black/30 p-3 rounded-xl border border-white/5 space-y-2 text-white/80">
                        <p><strong class="text-white">KeylineGT</strong> · Sistema Nacional de Gestión de Parcelas Keyline · Guatemala</p>
                        <p>Registra parcelas de diseño keyline, captura variables técnicas de suelo, agua, lluvia, bioindicadores y condiciones físicas del terreno, y visualiza el avance nacional mediante un dashboard ejecutivo.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import authService from '../../services/authService';
import { ROLE_LABELS } from '../../constants/keyline';
import { toastSuccess, alertError } from '../../utils/alerts';
import { User, Lock, Save, Ruler, Bell, Info } from '@lucide/vue';

const ROLE_BADGE = {
    administrador: 'bg-[#ef4444]/20 text-[#fca5a5] border-[#ef4444]/30',
    supervisor: 'bg-[#38bdf8]/20 text-[#7dd3fc] border-[#38bdf8]/30',
    tecnico: 'bg-[#22c55e]/20 text-[#4ade80] border-[#4ade80]/30',
};

const auth = useAuthStore();
const actual = ref('');
const nueva = ref('');
const saving = ref(false);
const unitSystem = ref('metric');
const notificationsEnabled = ref(true);

async function cambiarPassword() {
    saving.value = true;
    try {
        await authService.cambiarPassword(actual.value, nueva.value);
        toastSuccess('Contraseña actualizada correctamente.');
        actual.value = '';
        nueva.value = '';
    } catch (err) {
        alertError(err.message || 'No se pudo actualizar la contraseña.');
    } finally {
        saving.value = false;
    }
}
</script>
