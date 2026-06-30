<template>
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white font-headline">Registrar Sacrificio</h1>
                <p class="text-xs text-white/60 mt-1">Completa los datos de trazabilidad y sacrificio de animales en el establecimiento.</p>
            </div>
            <router-link :to="auth.role === 'administrador' ? '/sacrificios' : '/dashboard'" class="flex items-center gap-2 text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Volver
            </router-link>
        </div>

        <form @submit.prevent="handleSubmit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left & Middle Areas (Form inputs) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- 1. Datos Generales -->
                <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-white text-sm">feed</span>
                        1. Información General
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Fecha de Sacrificio *</label>
                            <input 
                                v-model="fechaSacrificio" 
                                type="date" 
                                required
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Propietario del Lote *</label>
                            <input 
                                v-model="propietario" 
                                type="text" 
                                required
                                placeholder="Nombre completo del dueño..."
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- 2. Procedencia -->
                <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-white text-sm">distance</span>
                        2. Procedencia del Ganado
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Departamento *</label>
                                <select 
                                    v-model="departamento" 
                                    required
                                    @change="onDepartamentoChange"
                                    class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                                >
                                    <option value="" disabled>Seleccione un departamento...</option>
                                    <option v-for="dept in deptoKeys" :key="dept" :value="dept">{{ dept }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Municipio *</label>
                                <input 
                                    v-model="municipio" 
                                    list="municipios-list"
                                    required
                                    placeholder="Seleccione o escriba..."
                                    class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                                />
                                <datalist id="municipios-list">
                                    <option v-for="muni in municipiosFiltrados" :key="muni" :value="muni">{{ muni }}</option>
                                </datalist>
                                <p class="text-[10px] text-slate-400 mt-1 italic">Puedes seleccionar de la lista o escribir uno diferente.</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Finca de Procedencia *</label>
                            <input 
                                v-model="finca" 
                                type="text" 
                                required
                                placeholder="Nombre de la finca de origen..."
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- 3. Clasificación e Impacto -->
                <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-white text-sm">pets</span>
                        3. Clasificación del Animal
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Clasificación *</label>
                                <select 
                                    v-model="clasificacion" 
                                    required
                                    class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                                >
                                    <option value="" disabled>Seleccione...</option>
                                    <option value="Vaca">Vaca</option>
                                    <option value="Novillo">Novillo</option>
                                    <option value="Toro">Toro</option>
                                    <option value="Ternero">Ternero</option>
                                    <option value="Novilla">Novilla</option>
                                    <option value="Buey">Buey</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Lote Asignado *</label>
                                <input 
                                    v-model="lote" 
                                    type="text" 
                                    required
                                    placeholder="Código de lote..."
                                    class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Cantidad de Animales *</label>
                                <input 
                                    v-model="cantidad" 
                                    type="number" 
                                    min="1"
                                    required
                                    placeholder="Ej: 5"
                                    class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Decomisos Realizados</label>
                            <input 
                                v-model="decomisos" 
                                type="text" 
                                placeholder="Especificar órganos o canales decomisados si aplica..."
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Area (Sampling, Upload Document & Submit) -->
            <div class="space-y-6">
                <!-- Trazabilidad / Muestreo -->
                <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-white text-sm">verified_user</span>
                        4. Trazabilidad
                    </h3>
                    
                    <div class="flex items-start gap-3 p-3 bg-[#0a192f]/5 rounded border border-primary/10">
                        <input 
                            id="muestreo" 
                            type="checkbox" 
                            v-model="muestreoOficial"
                            class="w-4 h-4 text-white focus:ring-primary border-slate-300 rounded mt-0.5 cursor-pointer"
                        />
                        <label for="muestreo" class="text-xs font-bold text-white select-none cursor-pointer leading-relaxed">
                            Lote sometido a muestreo oficial (Trazabilidad)
                        </label>
                    </div>
                </div>

                <!-- Adjuntar Documentación -->
                <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-white text-sm">upload_file</span>
                        5. Documentación
                    </h3>
                    
                    <div class="space-y-4">
                        <div v-if="documentoPreview" class="relative w-full aspect-[4/3] rounded border border-white/10 bg-black/20 flex items-center justify-center p-4">
                            <template v-if="isPdf">
                                <div class="text-center">
                                    <span class="material-symbols-outlined text-red-500 text-5xl">picture_as_pdf</span>
                                    <p class="text-xs font-bold text-white mt-2 truncate max-w-[200px]">{{ documentoFile?.name }}</p>
                                </div>
                            </template>
                            <template v-else>
                                <img :src="documentoPreview" class="w-full h-full object-cover rounded" />
                            </template>
                            
                            <button 
                                type="button" 
                                @click="removerDocumento"
                                class="absolute top-3 right-3 w-8 h-8 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center shadow transition-colors"
                            >
                                <span class="material-symbols-outlined text-base">delete</span>
                            </button>
                        </div>

                        <div v-else class="w-full aspect-[4/3] rounded border border-dashed border-white/10-variant hover:border-primary/50 bg-black/20 hover:bg-[#0a192f]/5 transition-all flex flex-col items-center justify-center p-6 text-center cursor-pointer relative">
                            <input 
                                type="file" 
                                accept="application/pdf,image/*" 
                                @change="onDocumentoSelected" 
                                class="absolute inset-0 opacity-0 cursor-pointer"
                            />
                            <span class="material-symbols-outlined text-3xl text-slate-400">file_upload</span>
                            <span class="text-xs font-bold text-white mt-3 block">Adjuntar Documento</span>
                            <span class="text-[10px] text-slate-400 mt-1 block">PDF o imágenes de guías sanitarias</span>
                        </div>
                    </div>
                </div>

                <!-- Observaciones y Enviar -->
                <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-white text-sm">chat</span>
                        Observaciones
                    </h3>
                    <textarea 
                        v-model="observaciones" 
                        rows="3"
                        placeholder="Observaciones o notas sobre el lote..."
                        class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white mb-4"
                    ></textarea>

                    <button 
                        type="submit" 
                        :disabled="submitting"
                        class="w-full py-3.5 bg-[#0a192f] hover:bg-[#122347] text-white font-bold text-xs rounded shadow transition-colors flex items-center justify-center gap-2 border border-slate-800 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span class="material-symbols-outlined text-sm animate-spin" v-if="submitting">sync</span>
                        <span class="material-symbols-outlined text-sm" v-else>cloud_upload</span>
                        {{ submitting ? 'Registrando...' : 'Registrar Sacrificio' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../../stores/authStore.js';
import api from '../../../services/api.js';
import Swal from 'sweetalert2';

const router = useRouter();
const auth = useAuthStore();

// Form Fields
const fechaSacrificio = ref(new Date().toISOString().split('T')[0]);
const propietario = ref('');
const departamento = ref('');
const municipio = ref('');
const finca = ref('');
const clasificacion = ref('');
const lote = ref('');
const cantidad = ref(null);
const decomisos = ref('');
const muestreoOficial = ref(false);
const observaciones = ref('');

// Document Upload
const documentoFile = ref(null);
const documentoPreview = ref(null);
const isPdf = ref(false);
const submitting = ref(false);

// Guatemala Departments & Municipalities Map
const municipiosPorDepartamento = {
    'Alta Verapaz': ['Cobán', 'Santa Cruz Verapaz', 'San Cristóbal Verapaz', 'Tactic', 'Tamahú', 'Tucurú', 'Panzós', 'Senahú', 'San Pedro Carchá', 'San Juan Chamelco', 'Lanquín', 'Cahabón', 'Chisec', 'Chahal', 'Fray Bartolomé de las Casas', 'Santa Catalina La Tinta', 'Raxruhá'],
    'Baja Verapaz': ['Salamá', 'San Miguel Chicaj', 'Granados', 'Santa Cruz El Chol', 'San Jerónimo', 'Purulhá', 'Rabinal', 'Cubulco'],
    'Chimaltenango': ['Chimaltenango', 'San José Poaquil', 'San Martín Jilotepeque', 'San Juan Comalapa', 'Santa Apolonia', 'Tecpán Guatemala', 'Patzún', 'Pochuta', 'Patzicía', 'Santa Cruz Balanyá', 'Acatenango', 'San Pedro Yepocapa', 'San Andrés Itzapa', 'Parramos', 'Zaragoza', 'El Tejar'],
    'Chiquimula': ['Chiquimula', 'San José la Arada', 'San Juan Ermita', 'Jocotán', 'Camotán', 'Olopa', 'Esquipulas', 'Concepción Las Minas', 'Quezaltepeque', 'San Jacinto', 'Ipala'],
    'El Progreso': ['Guastatoya', 'Morazán', 'San Agustín Acasaguastlán', 'San Cristóbal Acasaguastlán', 'El Jícaro', 'Sansare', 'Sanarate', 'San Antonio La Paz'],
    'Escuintla': ['Escuintla', 'Santa Lucía Cotzumalguapa', 'La Democracia', 'Siquinalá', 'Masagua', 'Tiquisate', 'La Gomera', 'Guanagazapa', 'San José', 'Iztapa', 'Palín', 'San Vicente Pacaya', 'Nueva Concepción'],
    'Guatemala': ['Guatemala', 'Santa Catarina Pinula', 'San José Pinula', 'San José del Golfo', 'Palencia', 'Chinautla', 'San Pedro Ayampuc', 'Mixco', 'San Pedro Sacatepéquez', 'San Juan Sacatepéquez', 'San Raymundo', 'Chuarrancho', 'Fraijanes', 'Amatitlán', 'Villa Nueva', 'Villa Canales', 'San Miguel Petapa'],
    'Huehuetenango': ['Huehuetenango', 'Chiantla', 'Malacatancito', 'Cuilco', 'Nentón', 'San Pedro Necta', 'Jacaltenango', 'Soloma', 'Ixtahuacán', 'Santa Bárbara', 'La Libertad', 'La Democracia', 'San Miguel Acatán', 'San Rafael La Independencia', 'Todos Santos Cuchumatán', 'San Juan Atitán', 'Santa Eulalia', 'San Mateo Ixtatán', 'Colotenango', 'San Sebastián Huehuetenango', 'Tectitán', 'Concepción Huista', 'San Juan Ixcoy', 'San Antonio Huista', 'San Sebastián Coatán', 'Santa Cruz Barillas', 'Aguacatán', 'San Rafael Petzal', 'San Gaspar Ixchil', 'Santiago Chimaltenango', 'Santa Ana Huista', 'Unión Cantinil'],
    'Izabal': ['Puerto Barrios', 'Livingston', 'El Estor', 'Morales', 'Los Amates'],
    'Jalapa': ['Jalapa', 'San Pedro Pinula', 'San Luis Jilotepeque', 'San Manuel Chaparrón', 'San Carlos Alzatate', 'Monjas', 'Mataquescuintla'],
    'Jutiapa': ['Jutiapa', 'El Progreso', 'Santa Catarina Mita', 'Agua Blanca', 'Asunción Mita', 'Yupiltepeque', 'Atescatempa', 'Jerez', 'El Adelanto', 'Zapotitlán', 'Comapa', 'Jalpatagua', 'Conguaco', 'Moyuta', 'Pasaco', 'San José Acatempa', 'Quezada'],
    'Petén': ['Flores', 'San José', 'San Benito', 'San Andrés', 'La Libertad', 'San Francisco', 'Santa Ana', 'Dolores', 'San Luis', 'Sayaxché', 'Melchor de Mencos', 'Poptún', 'Las Cruces', 'El Chal'],
    'Quetzaltenango': ['Quetzaltenango', 'Salcajá', 'Olintepeque', 'San Carlos Sija', 'Sibilia', 'Cabricán', 'Cajolá', 'San Miguel Siguilá', 'San Juan Ostuncalco', 'San Mateo', 'Concepción Chiquirichapa', 'San Martín Sacatepéquez', 'Almolonga', 'Cantel', 'Huitán', 'Zunil', 'Colomba Costa Cuca', 'El Palmar', 'Coatepeque', 'Génova', 'Flores Costa Cuca', 'La Esperanza', 'Palestina de Los Altos'],
    'Quiché': ['Santa Cruz del Quiché', 'Chiché', 'Chinique', 'Zacualpa', 'Chajul', 'Santo Tomás Chichicastenango', 'Patzité', 'San Antonio Ilotenango', 'San Pedro Jocopilas', 'Cunén', 'San Juan Cotzal', 'Joyabaj', 'Santa María Nebaj', 'San Andrés Sajcabajá', 'Uspantán', 'Sacapulas', 'San Bartolomé Jocotenango', 'Canillá', 'Chicamán', 'Ixcán', 'Pachalum'],
    'Retalhuleu': ['Retalhuleu', 'San Sebastián', 'Santa Cruz Muluá', 'San Martín Zapotitlán', 'San Felipe', 'San Andrés Villa Seca', 'Champerico', 'Nuevo San Carlos', 'El Asintal'],
    'Sacatepéquez': ['Antigua Guatemala', 'Jocotenango', 'Pastores', 'Sumpango', 'Santo Domingo Xenacoj', 'Santiago Sacatepéquez', 'San Bartolomé Milpas Altas', 'San Lucas Sacatepéquez', 'Santa Lucía Milpas Altas', 'Magdalena Milpas Altas', 'Santa María de Jesús', 'Ciudad Vieja', 'San Miguel Dueñas', 'San Juan Alotenango', 'San Antonio Aguas Calientes', 'Santa Catarina Barahona'],
    'San Marcos': ['San Marcos', 'San Pedro Sacatepéquez', 'San Antonio Sacatepéquez', 'San Rafael Pie de la Cuesta', 'Nuevo Progreso', 'El Tumbador', 'El Rodeo', 'Malacatán', 'Catarina', 'Ayutla', 'Ocós', 'San Pablo', 'El Quetzal', 'La Reforma', 'Pajapita', 'Ixchiguán', 'San José Ojetenam', 'San Cristóbal Cucho', 'Sipacapa', 'Esquipulas Palo Gordo', 'Río Blanco', 'San Lorenzo', 'Tejutla', 'San Rafael Oriente', 'Sibinal', 'Tajumulco', 'Concepción Tutuapa', 'Comitancillo', 'San Miguel Ixtahuacán', 'La Blanca'],
    'Santa Rosa': ['Cuilapa', 'Barberena', 'Santa Rosa de Lima', 'Casillas', 'San Rafael Las Flores', 'Oratorio', 'San Juan Tecuaco', 'Chiquimulilla', 'Taxisco', 'Santa María Ixhuatán', 'Guazacapán', 'Santa Cruz Naranjo', 'Pueblo Nuevo Viñas', 'Nueva Santa Rosa'],
    'Sololá': ['Sololá', 'San José Chacayá', 'Santa María Visitación', 'Santa Lucía Utatlán', 'Nahualá', 'Santa Catarina Ixtahuacán', 'Santa Clara La Laguna', 'Concepción', 'San Andrés Semetabaj', 'Panajachel', 'Santa Catarina Palopó', 'San Antonio Palopó', 'San Lucas Tolimán', 'Santa Cruz La Laguna', 'San Pablo La Laguna', 'San Marcos La Laguna', 'San Juan La Laguna', 'San Pedro La Laguna', 'Santiago Atitlán'],
    'Suchitepéquez': ['Mazatenango', 'Cuyotenango', 'San Francisco Zapotitlán', 'San Bernardino', 'San José El Ídolo', 'Santo Domingo Suchitepéquez', 'San Lorenzo', 'Samayac', 'San Pablo Jocopilas', 'Chicacao', 'Patulul', 'Santa Bárbara', 'San Juan Bautista', 'Santo Tomás La Unión', 'Zunilito', 'Pueblo Nuevo', 'Río Bravo'],
    'Totonicapán': ['Totonicapán', 'San Cristóbal Totonicapán', 'San Francisco El Alto', 'San Andrés Xecul', 'Momostenango', 'Santa María Chiquimula', 'Santa Lucía La Reforma', 'San Bartolo'],
    'Zacapa': ['Zacapa', 'Estanzuela', 'Río Hondo', 'Gualán', 'Teculután', 'Usumatlán', 'Cabañas', 'San Diego', 'La Unión', 'Huité']
};

const deptoKeys = Object.keys(municipiosPorDepartamento);

const municipiosFiltrados = computed(() => {
    return departamento.value ? municipiosPorDepartamento[departamento.value] : [];
});

const onDepartamentoChange = () => {
    municipio.value = '';
};

const onDocumentoSelected = (event) => {
    const file = event.target.files[0];
    if (file) {
        documentoFile.value = file;
        isPdf.value = file.type === 'application/pdf';
        
        if (isPdf.value) {
            documentoPreview.value = 'pdf_preview';
        } else {
            documentoPreview.value = URL.createObjectURL(file);
        }
    }
};

const removerDocumento = () => {
    documentoFile.value = null;
    documentoPreview.value = null;
    isPdf.value = false;
};

const handleSubmit = async () => {
    submitting.value = true;
    try {
        const formData = new FormData();
        formData.append('fecha_sacrificio', fechaSacrificio.value);
        formData.append('procedencia_departamento', departamento.value);
        formData.append('procedencia_municipio', municipio.value);
        formData.append('procedencia_finca', finca.value);
        formData.append('clasificacion', clasificacion.value);
        formData.append('lote', lote.value);
        formData.append('propietario', propietario.value);
        formData.append('cantidad', cantidad.value);
        formData.append('decomisos', decomisos.value);
        formData.append('muestreo_oficial', muestreoOficial.value);
        formData.append('observaciones', observaciones.value);

        if (documentoFile.value) {
            formData.append('documento', documentoFile.value);
        }

        const response = await api.post('/animales', formData);

        if (response.data?.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Registro Exitoso',
                text: 'El reporte de animales sacrificados se guardó correctamente.',
                confirmButtonColor: '#0a192f'
            }).then(() => {
                if (auth.role === 'administrador') {
                    router.push('/sacrificios');
                } else {
                    router.push('/dashboard');
                }
            });
        }
    } catch (error) {
        console.error('Error al registrar sacrificio', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Envío',
            text: error.response?.data?.error || 'No se pudo conectar con el servidor.',
            confirmButtonColor: '#0a192f'
        });
    } finally {
        submitting.value = false;
    }
};
</script>
