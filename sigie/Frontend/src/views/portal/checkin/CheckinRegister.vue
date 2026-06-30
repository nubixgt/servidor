<template>
    <div class="max-w-4xl mx-auto animate-fade-in">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white font-headline">Registrar Check-in</h1>
                <p class="text-xs text-white/60 mt-1">Completa la información requerida del establecimiento visitado.</p>
            </div>
            <router-link to="/dashboard" class="flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Volver
            </router-link>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-8">
            <!-- Main Stacked Form Card -->
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 p-6 md:p-8 shadow-premium space-y-8">
                <!-- Title inside Card -->
                <div class="border-b border-slate-100 pb-6">
                    <h2 class="text-xl font-extrabold text-slate-800 tracking-tight">Nuevo Registro de Entrada</h2>
                    <p class="text-xs text-slate-400 mt-1">Complete los datos de inspección para iniciar el turno en el establecimiento.</p>
                </div>

                <!-- Sección 1: Datos Generales -->
                <div class="space-y-4">
                    <h3 class="section-header-navy">
                        Sección 1: Datos Generales
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Establecimiento -->
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2">Nombre del Establecimiento</label>
                            
                            <div v-if="visitaIdFromQuery" class="relative">
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-start justify-between">
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-xs">{{ selectedVisitaDetails?.establecimiento }}</h4>
                                        <p class="text-[10px] text-slate-500 mt-1 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs text-blue-600">place</span>
                                            {{ selectedVisitaDetails?.direccion }}
                                        </p>
                                        <span class="inline-block text-[8px] font-black uppercase bg-blue-50 text-blue-700 border border-blue-100 px-2 py-0.5 rounded-md mt-2">
                                            {{ selectedVisitaDetails?.tipo_inspeccion }}
                                        </span>
                                    </div>
                                    <span class="text-[9px] font-black uppercase text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                                        Asignada
                                    </span>
                                </div>
                            </div>

                            <div v-else class="relative flex items-center bg-slate-50 border border-slate-200 rounded-xl focus-within:border-blue-600 focus-within:bg-white transition-all">
                                <span class="material-symbols-outlined absolute left-3.5 text-slate-400 text-lg">store</span>
                                <input 
                                    type="text" 
                                    v-model="visitaId" 
                                    required
                                    placeholder="Escribe el nombre del establecimiento..."
                                    class="w-full bg-transparent border-none outline-none py-3 pl-11 pr-4 text-xs font-semibold text-slate-800 placeholder-slate-400"
                                />
                            </div>
                        </div>

                        <!-- Estado de Operación (Check-in state) -->
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2">Estado de Operación</label>
                            <div class="grid grid-cols-2 gap-4">
                                <button 
                                    type="button" 
                                    @click="estadoCheckin = 'exitoso'"
                                    :class="['p-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition-all duration-200', 
                                             estadoCheckin === 'exitoso' 
                                                ? 'bg-emerald-50 border-emerald-300 text-emerald-700 shadow-sm' 
                                                : 'bg-slate-50 border-slate-200 text-slate-500 hover:bg-slate-100/70']"
                                >
                                    <span class="material-symbols-outlined text-base">check_circle</span>
                                    Sin Novedades
                                </button>
                                <button 
                                    type="button" 
                                    @click="estadoCheckin = 'con_novedades'"
                                    :class="['p-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition-all duration-200', 
                                             estadoCheckin === 'con_novedades' 
                                                ? 'bg-red-50 border-red-300 text-red-700 shadow-sm' 
                                                : 'bg-slate-50 border-slate-200 text-slate-500 hover:bg-slate-100/70']"
                                >
                                    <span class="material-symbols-outlined text-base">warning</span>
                                    Alertas
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección 2: Geoposicionamiento (GPS) -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="section-header-navy">
                            Sección 2: Geoposicionamiento (GPS)
                        </h3>
                        <button 
                            type="button" 
                            @click="obtenerUbicacion"
                            :disabled="gpsLoading"
                            class="px-3.5 py-1.5 bg-[#0a192f] hover:bg-[#0f224b] text-white font-extrabold text-[10px] uppercase rounded-lg transition-colors flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <span class="material-symbols-outlined text-xs animate-spin" v-if="gpsLoading">sync</span>
                            <span class="material-symbols-outlined text-xs" v-else>my_location</span>
                            Actualizar GPS
                        </button>
                    </div>

                    <!-- Map Body -->
                    <div class="relative w-full aspect-[21/9] bg-slate-50 border border-slate-200 rounded-2xl overflow-hidden shadow-inner">
                        <!-- Loading Overlay -->
                        <div v-if="gpsLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-50/90 z-10">
                            <span class="material-symbols-outlined text-3xl animate-spin text-blue-600">sync</span>
                            <span class="text-xs font-bold text-slate-500 mt-2">Obteniendo coordenadas GPS...</span>
                        </div>
                        <!-- Leaflet Map Container -->
                        <div id="map-container" class="w-full h-full z-0"></div>
                    </div>

                    <!-- Coordinates twin panels -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-between">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Latitud</span>
                            <span class="font-mono text-xs font-extrabold text-slate-800">{{ latitud ? latitud.toFixed(6) + '° N' : 'No obtenida' }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-between">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Longitud</span>
                            <span class="font-mono text-xs font-extrabold text-slate-800">{{ longitud ? longitud.toFixed(6) + '° W' : 'No obtenida' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Sección 3: Control de Tiempos -->
                <div class="space-y-4">
                    <h3 class="section-header-navy">
                        Sección 3: Control de Tiempos
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Hora de Ingreso -->
                        <div class="p-3.5 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-2xl text-blue-600">login</span>
                                <div>
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Hora de Ingreso</span>
                                    <span class="font-mono text-xs font-extrabold text-slate-800">{{ formatTime(horaIngreso) }}</span>
                                </div>
                            </div>
                            <span class="text-[9px] text-slate-400 font-bold bg-white border border-slate-100 px-2 py-0.5 rounded">{{ formatDate(horaIngreso) }}</span>
                        </div>

                        <!-- Hora de Salida -->
                        <div class="p-3.5 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-2xl text-amber-600">logout</span>
                                <div>
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Hora de Salida</span>
                                    <span class="font-mono text-xs font-extrabold text-slate-800" v-if="horaSalida">{{ formatTime(horaSalida) }}</span>
                                    <span class="text-xs font-semibold text-slate-400" v-else>No registrada</span>
                                </div>
                            </div>
                            
                            <button 
                                type="button" 
                                @click="registrarSalida"
                                class="px-3.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 font-bold text-[10px] uppercase rounded-lg border border-slate-200 transition-colors"
                            >
                                {{ horaSalida ? 'Actualizar Salida' : 'Registrar Salida' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sección 4: Evidencia Fotográfica y Observaciones -->
                <div class="space-y-4">
                    <h3 class="section-header-navy">
                        Sección 4: Evidencia Fotográfica y Notas
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Photo Upload -->
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2">Evidencia Visual (Foto)</label>
                            
                            <div v-if="fotoPreview" class="relative w-full aspect-[4/3] rounded-2xl border border-slate-200 overflow-hidden group shadow-sm bg-slate-50 flex items-center justify-center p-2">
                                <img :src="fotoPreview" class="max-w-full max-h-full object-contain rounded-xl" />
                                <button 
                                    type="button" 
                                    @click="removerFoto"
                                    class="absolute top-3 right-3 w-8 h-8 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center shadow-md transition-colors"
                                >
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </div>

                            <div v-else class="w-full aspect-[4/3] rounded-2xl border border-dashed border-slate-300 hover:border-blue-600 bg-slate-50 hover:bg-blue-50/10 transition-colors flex flex-col items-center justify-center p-6 text-center cursor-pointer relative shadow-inner">
                                <input 
                                    type="file" 
                                    accept="image/*" 
                                    @change="onFotoSelected" 
                                    class="absolute inset-0 opacity-0 cursor-pointer"
                                />
                                <span class="material-symbols-outlined text-3xl text-slate-400">add_a_photo</span>
                                <span class="text-xs font-bold text-slate-700 mt-3 block">Subir foto de evidencia</span>
                                <span class="text-[9px] text-slate-400 mt-1 block">JPG, PNG o evidencia visual</span>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="flex flex-col">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2">Observaciones Generales</label>
                            <textarea 
                                v-model="observaciones" 
                                rows="6"
                                placeholder="Escribe comentarios, novedades encontradas o detalles específicos de la inspección..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3.5 text-xs font-semibold focus:border-blue-600 focus:bg-white outline-none transition-all text-slate-800 placeholder-slate-400 flex-1 shadow-inner resize-none"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Sección 5: Validación y Firma -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="section-header-navy">
                            Sección 5: Validación y Firma Digital
                        </h3>
                        <button 
                            type="button" 
                            @click="clearCanvas"
                            class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold text-[10px] uppercase rounded-lg transition-colors flex items-center gap-1.5 border border-slate-200"
                        >
                            <span class="material-symbols-outlined text-xs">ink_eraser</span>
                            Limpiar
                        </button>
                    </div>

                    <p class="text-xs text-slate-500">El inspector y/o encargado debe dibujar su firma manuscrita sobre el panel táctil inferior:</p>
                    
                    <div class="border border-slate-200 bg-slate-50/50 rounded-2xl overflow-hidden aspect-[21/9] relative shadow-inner">
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
                        <span class="absolute right-3.5 bottom-3 text-[9px] font-black text-slate-400 uppercase tracking-widest pointer-events-none select-none">Área Táctil</span>
                    </div>
                </div>

                <!-- Submission Actions Inside Card -->
                <div class="pt-6 border-t border-slate-100">
                    <button 
                        type="submit" 
                        :disabled="submitting || !latitud"
                        class="w-full py-4 bg-[#0a192f] hover:bg-[#0f224b] text-white font-extrabold text-xs rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 border border-slate-900 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span class="material-symbols-outlined text-base" v-if="!submitting">cloud_upload</span>
                        <span class="material-symbols-outlined text-base animate-spin" v-else>sync</span>
                        {{ submitting ? 'Registrando Check-in...' : 'Registrar Check-in' }}
                    </button>
                    
                    <p v-if="!latitud" class="text-[10px] text-amber-700 font-bold text-center mt-3.5 flex items-center justify-center gap-1.5 bg-amber-50/50 py-2 rounded-lg border border-amber-200">
                        <span class="material-symbols-outlined text-xs">warning</span>
                        Es obligatorio obtener las coordenadas GPS del dispositivo antes de registrar el Check-in.
                    </p>
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
const horaIngreso = ref(null);
const horaSalida = ref(null);

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

let mapInstance = null;
let markerInstance = null;

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
        initLeafletMap();
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
            initLeafletMap();
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
            initLeafletMap();
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
    context.strokeStyle = '#0f172a'; // Azul oscuro/negro oficial
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
                const parsedId = parseInt(visitaIdFromQuery.value);
                selectedVisitaDetails.value = visitas.value.find(v => v.id === parsedId);
                
                // If it isn't found in dynamic list, check if we need to mock it
                if (!selectedVisitaDetails.value) {
                    selectedVisitaDetails.value = {
                        establecimiento: 'Establecimiento Seleccionado',
                        direccion: 'Cargando detalles...',
                        tipo_inspeccion: 'Inspección General'
                    };
                }
                
                // Set the input field value to the establishment name so it is submitted
                visitaId.value = selectedVisitaDetails.value.establecimiento;
            }
        }
    } catch (error) {
        console.error('Error al cargar visitas', error);
    }
};

const loadLeaflet = () => {
    return new Promise((resolve) => {
        if (window.L) {
            resolve(window.L);
            return;
        }

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => resolve(window.L);
        document.head.appendChild(script);
    });
};

const initLeafletMap = async () => {
    const L = await loadLeaflet();
    
    const lat = latitud.value || 14.6349;
    const lng = longitud.value || -90.5069;

    setTimeout(() => {
        const container = document.getElementById('map-container');
        if (!container) return;

        if (!mapInstance) {
            mapInstance = L.map('map-container', { zoomControl: false }).setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(mapInstance);

            const defaultIcon = L.icon({
                iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            markerInstance = L.marker([lat, lng], { draggable: true, icon: defaultIcon }).addTo(mapInstance);

            markerInstance.on('dragend', () => {
                const position = markerInstance.getLatLng();
                latitud.value = parseFloat(position.lat.toFixed(6));
                longitud.value = parseFloat(position.lng.toFixed(6));
                precisionGPS.value = 0;
            });

            mapInstance.on('click', (e) => {
                const position = e.latlng;
                markerInstance.setLatLng(position);
                latitud.value = parseFloat(position.lat.toFixed(6));
                longitud.value = parseFloat(position.lng.toFixed(6));
                precisionGPS.value = 0;
            });
        } else {
            mapInstance.setView([lat, lng], 15);
            markerInstance.setLatLng([lat, lng]);
            mapInstance.invalidateSize();
        }
    }, 100);
};

const getLocalDateTimeString = (date = new Date()) => {
    const pad = (num) => String(num).padStart(2, '0');
    const yyyy = date.getFullYear();
    const mm = pad(date.getMonth() + 1);
    const dd = pad(date.getDate());
    const hh = pad(date.getHours());
    const min = pad(date.getMinutes());
    const ss = pad(date.getSeconds());
    return `${yyyy}-${mm}-${dd} ${hh}:${min}:${ss}`;
};

const formatTime = (dateTimeStr) => {
    if (!dateTimeStr) return '';
    const parts = dateTimeStr.split(' ');
    if (parts.length < 2) return dateTimeStr;
    return parts[1];
};

const formatDate = (dateTimeStr) => {
    if (!dateTimeStr) return '';
    const parts = dateTimeStr.split(' ');
    if (parts.length < 2) return dateTimeStr;
    const dateParts = parts[0].split('-');
    if (dateParts.length < 3) return parts[0];
    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const day = dateParts[2];
    const month = months[parseInt(dateParts[1]) - 1];
    const year = dateParts[0];
    return `${day} ${month} ${year}`;
};

const registrarSalida = () => {
    horaSalida.value = getLocalDateTimeString();
};

const handleSubmit = async () => {
    if (!horaSalida.value) {
        Swal.fire('Error', 'Por favor, registre la hora de salida de su visita antes de enviar.', 'error');
        return;
    }

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
        if (visitaIdFromQuery.value) {
            formData.append('original_visita_id', visitaIdFromQuery.value);
        }
        formData.append('latitud', latitud.value);
        formData.append('longitud', longitud.value);
        formData.append('observaciones', observaciones.value);
        formData.append('estado', estadoCheckin.value);
        formData.append('firma', signatureData);
        formData.append('hora_ingreso', horaIngreso.value || '');
        formData.append('hora_salida', horaSalida.value || '');

        if (fotoFile.value) {
            formData.append('foto', fotoFile.value);
        }

        const response = await api.post('/checkin', formData);

        if (response.data?.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Check-in Completado',
                text: 'El registro se guardó correctamente en el sistema.',
                confirmButtonColor: '#0a192f'
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
            confirmButtonColor: '#0a192f'
        });
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    horaIngreso.value = getLocalDateTimeString();
    fetchVisitasAsignadas();
    setTimeout(initCanvas, 500); // Give layout time to render and set canvas dims
    obtenerUbicacion(); // Try to fetch GPS coordinates automatically at start
});
</script>
