<template>
    <div class="animate-fade-in w-full max-w-4xl mx-auto pt-4 pb-20">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6 px-2">Configuración</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Profile Section -->
            <div class="col-span-1 md:col-span-2 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 p-6">
                <div class="flex items-center justify-between mb-5 border-b border-gray-100 dark:border-white/5 pb-4">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Perfil de Usuario</h3>
                    <button @click="updateProfile" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 dark:text-emerald-400 px-4 py-1.5 rounded-lg transition-colors">
                        Guardar Perfil
                    </button>
                </div>

                <div class="flex flex-col md:flex-row items-start gap-6">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-rose-400 to-orange-500 flex items-center justify-center text-white text-2xl font-bold shadow-lg shrink-0">
                        {{ initials }}
                    </div>
                    <div class="flex-1 w-full">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Nombre</label>
                                <input type="text" v-model="profile.nombres" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-white/10 rounded-xl text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-400 transition" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Apellidos</label>
                                <input type="text" v-model="profile.apellidos" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-white/10 rounded-xl text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-400 transition" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Email</label>
                                <input type="email" v-model="profile.email" disabled class="w-full px-4 py-2.5 bg-gray-100 dark:bg-slate-700/50 border border-gray-200 dark:border-white/5 rounded-xl text-gray-400 dark:text-gray-500 text-sm cursor-not-allowed" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Puesto Funcional</label>
                                <input type="text" v-model="profile.puesto" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-white/10 rounded-xl text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-400 transition" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Ubicación / Dependencia</label>
                                <input type="text" v-model="profile.ubicacion" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-white/10 rounded-xl text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-400 transition" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security (Password) -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 p-6 flex flex-col">
                <div class="flex items-center gap-3 mb-5 border-b border-gray-100 dark:border-white/5 pb-4">
                    <KeyIcon class="w-5 h-5 text-rose-500" />
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Seguridad</h3>
                </div>
                <div class="space-y-3 flex-1">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Contraseña Actual</label>
                        <input type="password" v-model="passwordForm.current_password" placeholder="••••••••" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-white/10 rounded-xl text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rose-500/30 focus:border-rose-400 transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Nueva Contraseña</label>
                        <input type="password" v-model="passwordForm.new_password" placeholder="••••••••" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-white/10 rounded-xl text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rose-500/30 focus:border-rose-400 transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Confirmar Nueva Contraseña</label>
                        <input type="password" v-model="passwordForm.confirm_password" placeholder="••••••••" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-white/10 rounded-xl text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rose-500/30 focus:border-rose-400 transition" />
                    </div>
                </div>
                <button @click="updatePassword" class="mt-5 w-full py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-semibold rounded-xl shadow-md shadow-rose-500/20 transition-all text-sm">
                    Cambiar Contraseña
                </button>
            </div>

            <!-- Preferences -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 p-6 flex flex-col">
                <div class="flex items-center gap-3 mb-5 border-b border-gray-100 dark:border-white/5 pb-4">
                    <BellIcon class="w-5 h-5 text-blue-500" />
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Preferencias</h3>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl border border-gray-100 dark:border-white/5">
                        <div>
                            <span class="text-sm font-medium text-gray-800 dark:text-white block">Email Alertas</span>
                            <span class="text-xs text-gray-400">Recibir notificaciones por correo</span>
                        </div>
                        <div @click="togglePreference('email_alerts')" 
                             :class="preferences.email_alerts ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"
                             class="w-11 h-6 rounded-full relative cursor-pointer shadow-inner transition-colors shrink-0">
                            <div :class="preferences.email_alerts ? 'translate-x-5' : 'translate-x-1'" class="absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-transform"></div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl border border-gray-100 dark:border-white/5">
                        <div>
                            <span class="text-sm font-medium text-gray-800 dark:text-white block">Tema Oscuro</span>
                            <span class="text-xs text-gray-400">Cambiar apariencia del sistema</span>
                        </div>
                        <div @click="togglePreference('dark_mode')" 
                             :class="preferences.dark_mode ? 'bg-purple-500' : 'bg-gray-300 dark:bg-gray-600'"
                             class="w-11 h-6 rounded-full relative cursor-pointer shadow-inner transition-colors shrink-0">
                            <div :class="preferences.dark_mode ? 'translate-x-5' : 'translate-x-1'" class="absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-transform"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Avisos Globales -->
        <div class="mt-6 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-emerald-100 dark:border-emerald-500/10 p-6">
            <div class="flex items-center gap-3 mb-5 border-b border-gray-100 dark:border-white/5 pb-4">
                <GlobeAltIcon class="w-5 h-5 text-emerald-500" />
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Avisos Globales del Sistema</h3>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Envía una notificación a todos los usuarios (ej. mantenimientos).</p>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Título de la Alerta</label>
                    <input type="text" v-model="notificationForm.title" placeholder="Ej: Mantenimiento Programado" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-white/10 rounded-xl text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Mensaje</label>
                    <textarea v-model="notificationForm.message" rows="2" placeholder="Detalles del aviso..." class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-white/10 rounded-xl text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-400 transition resize-none"></textarea>
                </div>
                <div class="flex gap-4 flex-wrap">
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="radio" v-model="notificationForm.type" value="info">
                        <span class="text-blue-600 dark:text-blue-400 font-medium">Información</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="radio" v-model="notificationForm.type" value="warning">
                        <span class="text-yellow-600 dark:text-yellow-400 font-medium">Advertencia</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="radio" v-model="notificationForm.type" value="success">
                        <span class="text-green-600 dark:text-green-400 font-medium">Éxito</span>
                    </label>
                </div>
                
                <button @click="sendGlobalNotification" :disabled="sendingNotif" class="w-full sm:w-auto px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-xl shadow-md shadow-emerald-500/20 transition-all text-sm disabled:opacity-50">
                    {{ sendingNotif ? 'Enviando...' : 'Enviar a Todos' }}
                </button>
            </div>
        </div>

        <!-- Ajustes del Sistema (Solo Admin) -->
        <div v-if="isAdmin" class="mt-6 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-purple-100 dark:border-purple-500/10 p-6">
            <div class="flex items-center gap-3 mb-5 border-b border-gray-100 dark:border-white/5 pb-4">
                <Cog6ToothIcon class="w-5 h-5 text-purple-500" />
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Ajustes del Sistema</h3>
                <span class="text-xs bg-purple-100 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 px-2 py-0.5 rounded-full font-medium">Solo Admins</span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Configuraciones que aplican a toda la plataforma.</p>
            
            <div v-if="Object.keys(systemSettings).length > 0" class="space-y-4">
                <div v-for="(setting, key) in systemSettings" :key="key" class="p-4 bg-gray-50 dark:bg-slate-700/50 rounded-xl border border-gray-100 dark:border-white/5">
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 uppercase mb-0.5">{{ key.replace(/_/g, ' ') }}</label>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">{{ setting.description }}</p>
                    <div v-if="setting.value === 'true' || setting.value === 'false'" class="flex items-center gap-3">
                        <div @click="setting.value = setting.value === 'true' ? 'false' : 'true'" 
                             :class="setting.value === 'true' ? 'bg-purple-500' : 'bg-gray-300 dark:bg-gray-600'"
                             class="w-11 h-6 rounded-full relative cursor-pointer shadow-inner transition-colors">
                            <div :class="setting.value === 'true' ? 'translate-x-5' : 'translate-x-1'" class="absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-transform"></div>
                        </div>
                        <span class="text-sm font-medium" :class="setting.value === 'true' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-500'">
                            {{ setting.value === 'true' ? 'Activado' : 'Desactivado' }}
                        </span>
                    </div>
                    <input v-else type="text" v-model="setting.value" class="w-full px-4 py-2.5 bg-white dark:bg-slate-700 border border-gray-200 dark:border-white/10 rounded-xl text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-400 transition" />
                </div>
                
                <button @click="saveSystemSettings" :disabled="savingSystemSettings" class="px-6 py-2.5 bg-purple-500 hover:bg-purple-600 text-white font-semibold rounded-xl shadow-md shadow-purple-500/20 transition-all text-sm disabled:opacity-50">
                    {{ savingSystemSettings ? 'Guardando...' : 'Guardar Ajustes del Sistema' }}
                </button>
            </div>
            <div v-else class="text-center py-8 text-gray-400 text-sm">
                <Cog6ToothIcon class="w-10 h-10 mx-auto mb-2 opacity-30" />
                Ejecuta la migración SQL para habilitar los ajustes del sistema.
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { 
    BellIcon, 
    GlobeAltIcon,
    KeyIcon,
    Cog6ToothIcon
} from '@heroicons/vue/24/outline';
import api from '../../services/api';
import Swal from 'sweetalert2';

// State
const isAdmin = ref(false);
const systemSettings = ref({});
const savingSystemSettings = ref(false);
const profile = ref({
    nombres: '',
    apellidos: '',
    email: '',
    puesto: '',
    ubicacion: ''
});
const passwordForm = ref({
    current_password: '',
    new_password: '',
    confirm_password: ''
});
const preferences = ref({
    email_alerts: true,
    dark_mode: false
});

const notificationForm = ref({
    title: '',
    message: '',
    type: 'info'
});
const sendingNotif = ref(false);

const initials = ref('US');

// Load Data
const loadProfile = async () => {
    try {
        const { data } = await api.get('/settings/profile');
        if (data.success) {
            const userData = data.data;
            profile.value = {
                nombres: userData.nombres || '',
                apellidos: userData.apellidos || '',
                email: userData.email || '',
                puesto: userData.puesto_funcional || '',
                ubicacion: userData.ubicacion_laboral || ''
            };
            
            // Generate initials
            const first = userData.nombres ? userData.nombres[0] : '';
            const second = userData.apellidos ? userData.apellidos[0] : '';
            initials.value = (first + second).toUpperCase() || 'US';

            if (userData.preferences) {
                preferences.value = { ...preferences.value, ...userData.preferences };
            }
        } else {
            Swal.fire('Error', data.message || 'No se pudieron cargar los datos', 'error');
        }
    } catch (e) {
        console.error("Error loading profile", e);
        Swal.fire('Error', e.response?.data?.message || 'No se pudo cargar el perfil', 'error');
    }
};

const loadSystemSettings = async () => {
    try {
        const { data } = await api.get('/settings/system');
        if (data.success) {
            systemSettings.value = data.data;
            isAdmin.value = true;
        }
    } catch (e) {
        // If 403, user is not admin. Ignore silently.
        isAdmin.value = false;
    }
};

onMounted(() => {
    loadProfile();
    loadSystemSettings();
});

// Actions
const updateProfile = async () => {
    try {
        const payload = {
            nombres: profile.value.nombres,
            apellidos: profile.value.apellidos,
            puesto_funcional: profile.value.puesto,
            ubicacion_laboral: profile.value.ubicacion
        };
        await api.post('/settings/profile', payload);
        Swal.fire('¡Guardado!', 'Perfil actualizado correctamente', 'success');
        loadProfile();
    } catch (e) {
        Swal.fire('Error', e.response?.data?.error || 'Error al actualizar perfil', 'error');
    }
};

const updatePassword = async () => {
    if (passwordForm.value.new_password !== passwordForm.value.confirm_password) {
        Swal.fire('Error', 'Las contraseñas no coinciden', 'warning');
        return;
    }

    try {
        await api.post('/settings/password', {
            current_password: passwordForm.value.current_password,
            new_password: passwordForm.value.new_password
        });
        Swal.fire('¡Éxito!', 'Contraseña actualizada', 'success');
        passwordForm.value = { current_password: '', new_password: '', confirm_password: '' };
    } catch (e) {
        Swal.fire('Error', e.response?.data?.error || 'Error al cambiar contraseña', 'error');
    }
};

const togglePreference = async (key) => {
    preferences.value[key] = !preferences.value[key];
    try {
        await api.post('/settings/preferences', { preferences: preferences.value });
        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500 });
        Toast.fire({ icon: 'success', title: 'Preferencia guardada' });
    } catch (e) {
        console.error(e);
    }
};

const sendGlobalNotification = async () => {
    if (!notificationForm.value.title || !notificationForm.value.message) {
        Swal.fire('Error', 'Por favor ingresa un título y un mensaje para la alerta', 'warning');
        return;
    }

    try {
        sendingNotif.value = true;
        const { data } = await api.post('/notifications/global', notificationForm.value);
        if (data.status === 'success') {
            Swal.fire('¡Enviado!', 'La notificación se ha enviado a todos los usuarios', 'success');
            notificationForm.value = { title: '', message: '', type: 'info' };
        } else {
            Swal.fire('Error', data.message || 'No se pudo enviar la notificación', 'error');
        }
    } catch (e) {
        Swal.fire('Error', e.response?.data?.message || 'Hubo un error al enviar la notificación', 'error');
    } finally {
        sendingNotif.value = false;
    }
};

const saveSystemSettings = async () => {
    try {
        savingSystemSettings.value = true;
        const payload = {};
        for (const [key, obj] of Object.entries(systemSettings.value)) {
            payload[key] = obj.value;
        }
        
        const { data } = await api.post('/settings/system', { settings: payload });
        if (data.success) {
            Swal.fire('¡Guardado!', 'Ajustes del sistema actualizados', 'success');
        } else {
            Swal.fire('Error', data.message || 'No se pudieron guardar los ajustes', 'error');
        }
    } catch (e) {
        Swal.fire('Error', e.response?.data?.message || 'Error al guardar configuración global', 'error');
    } finally {
        savingSystemSettings.value = false;
    }
};
</script>
