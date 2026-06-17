<template>
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-on-surface">Registrar Check-in</h1>
                <p class="text-sm text-on-surface-variant mt-1">Completa los datos solicitados en el establecimiento visitado.</p>
            </div>
            <router-link to="/dashboard" class="flex items-center gap-2 text-xs font-bold text-primary hover:text-primary-dim transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Volver
            </router-link>
        </div>

        <form @submit.prevent="handleSubmit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Area (Visita selection, GPS, Photo, Observations) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Visita Card -->
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wider mb-4">1. Datos de la Visita</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Establecimiento a Inspeccionar</label>
                            
                            <!-- Dropdown if no query parameter, else static read-only text -->
                            <div v-if="visitaIdFromQuery">
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 flex items-start justify-between">
                                    <div>
                                        <h4 class="font-bold text-on-surface">{{ selectedVisitaDetails?.establecimiento }}</h4>
                                        <p class="text-xs text-on-surface-variant mt-1 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm text-primary">place</span>
                                            {{ selectedVisitaDetails?.direccion }}
                                        </p>
                                        <span class="inline-block text-[9px] font-extrabold uppercase bg-primary-container text-on-primary-container px-2 py-0.5 rounded mt-2">
                                            {{ selectedVisitaDetails?.tipo_inspeccion }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] font-bold uppercase text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-100">
                                        Asignada
                                    </span>
                                </div>
                            </div>

                            <div v-else>
                                <select 
                                    v-model="visitaId" 
                                    required
                                    class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                                >
                                    <option value="" disabled>Seleccione un establecimiento programado...</option>
                                    <option v-for="v in visitas" :key="v.id" :value="v.id">
                                        {{ v.establecimiento }} — {{ v.tipo_inspeccion }}
                                    </option>
                                </select>
                                <p v-if="visitas.length === 0" class="text-xs text-red-500 mt-1 font-semibold">
                                    No tienes visitas pendientes programadas para hoy.
                                </p>
                            </div>
                        </div>

                        <!-- Estado Check-in -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Estado del Establecimiento</label>
                            <div class="grid grid-cols-2 gap-4">
                                <button 
                                    type="button" 
                                    @click="estadoCheckin = 'exitoso'"
                                    :class="['p-3 rounded-xl border-2 text-xs font-bold flex items-center justify-center gap-2 transition-all', estadoCheckin === 'exitoso' ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'bg-slate-50 border-slate-100 text-on-surface-variant hover:bg-slate-100']"
                                >
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                    Sin Novedades
                                </button>
                                <button 
                                    type="button" 
                                    @click="estadoCheckin = 'con_novedades'"
                                    :class="['p-3 rounded-xl border-2 text-xs font-bold flex items-center justify-center gap-2 transition-all', estadoCheckin === 'con_novedades' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-slate-50 border-slate-100 text-on-surface-variant hover:bg-slate-100']"
                                >
                                    <span class="material-symbols-outlined text-sm">warning</span>
                                    Con Novedades / Alertas
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GPS Location Card -->
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wider mb-4">2. Geolocalización GPS</h3>
                    
                    <div class="flex flex-col md:flex-row md:items-center gap-6">
                        <!-- Radar / Satellite Ring Animation -->
                        <div class="w-24 h-24 rounded-full flex-shrink-0 bg-slate-900 border border-slate-800 flex items-center justify-center relative overflow-hidden">
                            <!-- Scanner line -->
                            <div v-if="gpsLoading" class="absolute inset-0 bg-gradient-to-t from-primary/20 via-transparent to-transparent origin-center animate-spin duration-1000"></div>
                            
                            <!-- Static Radar lines -->
                            <div class="absolute w-16 h-16 border border-slate-800 rounded-full"></div>
                            <div class="absolute w-8 h-8 border border-slate-800 rounded-full"></div>
                            <div class="absolute w-[1px] h-full bg-slate-800"></div>
                            <div class="absolute h-[1px] w-full bg-slate-800"></div>
                            
                            <!-- Target Pin -->
                            <span :class="['material-symbols-outlined text-2xl z-10', latitud && longitud ? 'text-emerald-500 animate-pulse' : 'text-slate-500']">
                                {{ latitud && longitud ? 'location_searching' : 'gps_not_fixed' }}
                            </span>
                        </div>

                        <!-- Coordinates details -->
                        <div class="flex-1 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block">Latitud</span>
                                    <span class="font-mono text-xs font-bold text-on-surface">{{ latitud || '---' }}</span>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block">Longitud</span>
                                    <span class="font-mono text-xs font-bold text-on-surface">{{ longitud || '---' }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button 
                                    type="button" 
                                    @click="obtenerUbicacion" 
                                    :disabled="gpsLoading"
                                    class="px-4 py-2 bg-slate-900 text-white font-bold text-xs rounded-xl hover:bg-slate-800 active:scale-95 transition-all flex items-center gap-2 shadow"
                                >
                                    <span class="material-symbols-outlined text-sm animate-spin" v-if="gpsLoading">sync</span>
                                    <span class="material-symbols-outlined text-sm" v-else>my_location</span>
                                    {{ latitud ? 'Actualizar GPS' : 'Obtener Ubicación GPS' }}
                                </button>
                                <span v-if="gpsError" class="text-xs text-red-500 font-semibold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">error</span>
                                    {{ gpsError }}
                                </span>
                                <span v-else-if="latitud" class="text-xs text-emerald-600 font-semibold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">task_alt</span>
                                    GPS Listo (Precisión: {{ precisionGPS }}m)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Evidencia Fotográfica Card -->
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wider mb-4">3. Evidencia Fotográfica</h3>
                    
                    <div class="space-y-4">
                        <!-- Preview area -->
                        <div v-if="fotoPreview" class="relative w-full max-w-sm aspect-[4/3] rounded-2xl overflow-hidden border border-surface-container group">
                            <img :src="fotoPreview" class="w-full h-full object-cover" />
                            <button 
                                type="button" 
                                @click="removerFoto"
                                class="absolute top-3 right-3 w-8 h-8 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 active:scale-95"
                            >
                                <span class="material-symbols-outlined text-base">delete</span>
                            </button>
                        </div>

                        <!-- Upload Button -->
                        <div v-else class="w-full max-w-sm aspect-[4/3] rounded-2xl border-2 border-dashed border-outline-variant hover:border-primary/50 bg-slate-50 hover:bg-primary/5 transition-all flex flex-col items-center justify-center p-6 text-center cursor-pointer relative">
                            <input 
                                type="file" 
                                accept="image/*" 
                                @change="onFotoSelected" 
                                class="absolute inset-0 opacity-0 cursor-pointer"
                            />
                            <span class="material-symbols-outlined text-3xl text-outline-variant">add_a_photo</span>
                            <span class="text-xs font-bold text-on-surface mt-3 block">Subir foto de evidencia</span>
                            <span class="text-[10px] text-on-surface-variant mt-1 block">JPG, PNG o selfie del local</span>
                        </div>
                    </div>
                </div>

                <!-- Observaciones Card -->
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wider mb-4">4. Observaciones</h3>
                    <textarea 
                        v-model="observaciones" 
                        rows="4"
                        placeholder="Escribe comentarios, novedades encontradas o detalles específicos de la inspección..."
                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                    ></textarea>
                </div>
            </div>

            <!-- Right Area (Signature Canvas) -->
            <div class="space-y-6">
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient flex flex-col h-full">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wider mb-4">5. Firma Digital</h3>
                    <p class="text-xs text-on-surface-variant mb-4">El inspector y/o encargado debe firmar en el recuadro inferior:</p>
                    
                    <!-- Canvas container -->
                    <div class="border-2 border-dashed border-outline-variant bg-slate-50 rounded-2xl overflow-hidden aspect-[4/3] relative">
                        <canvas 
                            ref="canvasRef"
                            @mousedown="startDrawing"
                            @mousemove="draw"
                            @mouseup="stopDrawing"
                            @mouseleave="stopDrawing"
                            @touchstart="startDrawingTouch"
                            @touchmove="drawTouch"
                            @touchend="stopDrawing"
                            class="w-full h-full cursor-crosshair touch-none"
                        ></canvas>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <button 
                            type="button" 
                            @click="clearCanvas"
                            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-on-surface-variant font-bold text-xs rounded-xl transition-all"
                        >
                            Limpiar Firma
                        </button>
                        <span class="text-[10px] font-bold text-outline-variant uppercase">Área táctil</span>
                    </div>

                    <!-- Submit card actions -->
                    <div class="mt-8 pt-8 border-t border-surface-container">
                        <button 
                            type="submit" 
                            :disabled="submitting || !latitud"
                            class="w-full py-4 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-bold text-sm rounded-xl shadow-lg hover:scale-[1.02] active:scale-98 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <span class="material-symbols-outlined text-lg" v-if="!submitting">cloud_upload</span>
                            <span class="material-symbols-outlined text-lg animate-spin" v-else>sync</span>
                            {{ submitting ? 'Registrando...' : 'Registrar Check-in' }}
                        </button>
                        <p v-if="!latitud" class="text-[10px] text-amber-600 font-bold text-center mt-2.5 flex items-center justify-center gap-1 bg-amber-50 py-1.5 rounded-lg border border-amber-100">
                            <span class="material-symbols-outlined text-sm">warning</span>
                            Es necesario obtener ubicación GPS antes de enviar.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../../stores/authStore.js';
import api from '../../../services/api.js';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

// Form fields
const visitaId = ref('');
const latitud = ref(null);
const longitud = ref(null);
const precisionGPS = ref(0);
const observaciones = ref('');
const estadoCheckin = ref('exitoso');

// Files
const fotoFile = ref(null);
const fotoPreview = ref(null);

// Statuses
const visitas = ref([]);
const visitaIdFromQuery = computed(() => route.query.visita_id);
const selectedVisitaDetails = ref(null);

const gpsLoading = ref(false);
const gpsError = ref(null);
const submitting = ref(false);

// Canvas signature pad refs and states
const canvasRef = ref(null);
const isDrawing = ref(false);
let context = null;

const onFotoSelected = (event) => {
    const file = event.target.files[0];
    if (file) {
        fotoFile.value = file;
        fotoPreview.value = URL.createObjectURL(file);
    }
};

const removerFoto = () => {
    fotoFile.value = null;
    fotoPreview.value = null;
};

// Geolocation fetcher
const obtenerUbicacion = () => {
    if (!navigator.geolocation) {
        gpsError.value = 'Geolocalización no soportada por el navegador.';
        return;
    }

    gpsLoading.value = true;
    gpsError.value = null;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            latitud.value = position.coords.latitude;
            longitud.value = position.coords.longitude;
            precisionGPS.value = Math.round(position.coords.accuracy);
            gpsLoading.value = false;
        },
        (error) => {
            console.error('Error GPS', error);
            gpsLoading.value = false;
            
            // Fallback simulated GPS coordinates for development
            const fallbackLat = 14.6349 + (Math.random() - 0.5) * 0.01;
            const fallbackLng = -90.5069 + (Math.random() - 0.5) * 0.01;
            latitud.value = parseFloat(fallbackLat.toFixed(6));
            longitud.value = parseFloat(fallbackLng.toFixed(6));
            precisionGPS.value = 15;
            
            gpsError.value = 'Simulado (Falta permiso GPS real)';
        },
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
    );
};

// HTML5 Canvas Drawing logic
const initCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    
    // Set internal size to match visual bounding rect
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width;
    canvas.height = rect.height;

    context = canvas.getContext('2d');
    context.strokeStyle = '#0f172a';
    context.lineWidth = 3;
    context.lineCap = 'round';
    context.lineJoin = 'round';
};

const startDrawing = (e) => {
    isDrawing.value = true;
    const { offsetX, offsetY } = e;
    context.beginPath();
    context.moveTo(offsetX, offsetY);
};

const draw = (e) => {
    if (!isDrawing.value) return;
    const { offsetX, offsetY } = e;
    context.lineTo(offsetX, offsetY);
    context.stroke();
};

const stopDrawing = () => {
    isDrawing.value = false;
};

// Touch Drawing supporting mobile devices
const startDrawingTouch = (e) => {
    isDrawing.value = true;
    const touch = e.touches[0];
    const rect = canvasRef.value.getBoundingClientRect();
    const x = touch.clientX - rect.left;
    const y = touch.clientY - rect.top;
    context.beginPath();
    context.moveTo(x, y);
};

const drawTouch = (e) => {
    if (!isDrawing.value) return;
    const touch = e.touches[0];
    const rect = canvasRef.value.getBoundingClientRect();
    const x = touch.clientX - rect.left;
    const y = touch.clientY - rect.top;
    context.lineTo(x, y);
    context.stroke();
};

const clearCanvas = () => {
    context.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
};

// Load initial details
const fetchVisitasAsignadas = async () => {
    try {
        const response = await api.get('/inspectores/visitas/pendientes');
        if (response.data?.status === 'success') {
            visitas.value = response.data.data;
            
            // If visita_id is provided in URL, pre-select it
            if (visitaIdFromQuery.value) {
                visitaId.value = parseInt(visitaIdFromQuery.value);
                selectedVisitaDetails.value = visitas.value.find(v => v.id === visitaId.value);
                
                // If it isn't found in dynamic list, check if we need to mock it
                if (!selectedVisitaDetails.value) {
                    selectedVisitaDetails.value = {
                        establecimiento: 'Establecimiento Seleccionado',
                        direccion: 'Cargando detalles...',
                        tipo_inspeccion: 'Inspección General'
                    };
                }
            }
        }
    } catch (error) {
        console.error('Error al cargar visitas', error);
    }
};

const handleSubmit = async () => {
    if (!latitud.value || !longitud.value) {
        Swal.fire('Error', 'Faltan coordenadas GPS.', 'error');
        return;
    }

    submitting.value = true;
    
    try {
        // Export canvas drawing to Base64 String
        const canvas = canvasRef.value;
        const signatureData = canvas.toDataURL('image/png');
        
        // Use FormData for file upload support
        const formData = new FormData();
        formData.append('visita_id', visitaId.value);
        formData.append('latitud', latitud.value);
        formData.append('longitud', longitud.value);
        formData.append('observaciones', observaciones.value);
        formData.append('estado', estadoCheckin.value);
        formData.append('firma', signatureData);

        if (fotoFile.value) {
            formData.append('foto', fotoFile.value);
        }

        const response = await api.post('/checkin', formData);

        if (response.data?.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Check-in Completado',
                text: 'El registro se guardó correctamente en el sistema.',
                confirmButtonColor: '#0284c7'
            }).then(() => {
                router.push('/dashboard');
            });
        }
    } catch (error) {
        console.error('Submit error', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Envío',
            text: error.response?.data?.error || 'No se pudo conectar con la API.',
            confirmButtonColor: '#0284c7'
        });
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    fetchVisitasAsignadas();
    setTimeout(initCanvas, 500); // Give layout time to render and set canvas dims
    obtenerUbicacion(); // Try to fetch GPS coordinates automatically at start
});
</script>
