<template>
    <div class="max-w-4xl mx-auto space-y-6 pb-12">
        <!-- Header -->
        <div class="flex justify-between items-center gap-3">
            <div>
                <h2 class="text-2xl font-bold text-white tracking-tight">{{ isEditing ? 'Editar parcela' : 'Registrar nueva parcela' }}</h2>
                <p class="text-xs text-white/80 mt-0.5">
                    Completa los datos técnicos de la parcela keyline. Los campos con * son obligatorios.
                    <span v-if="parcela?.codigo" class="ml-2 text-xs bg-[#38bdf8]/15 text-[#38bdf8] px-2 py-0.5 rounded border border-[#38bdf8]/30">{{ parcela.codigo }}</span>
                </p>
            </div>
            <button @click="cancelar" class="text-xs text-white/80 hover:text-white px-3.5 py-1.5 rounded-xl border border-white/15 hover:bg-white/5 transition-colors flex-shrink-0">
                Cancelar
            </button>
        </div>

        <div v-if="loadingParcela" class="py-12 text-center text-xs text-white/60">Cargando…</div>

        <template v-else>
            <!-- 4-step visual timeline -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div
                    v-for="(step, i) in STEPS"
                    :key="step.key"
                    @click="stepIndex = i"
                    class="p-3 rounded-2xl border transition-all cursor-pointer"
                    :class="i === stepIndex ? 'bg-white/10 border-[#4ade80]/60 shadow-[0_0_15px_rgba(74,222,128,0.2)]' : i < stepIndex ? 'bg-black/30 border-[#4ade80]/30' : 'bg-black/20 border-white/10 opacity-60'"
                >
                    <div class="flex items-center gap-2 mb-1">
                        <span
                            class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                            :class="i < stepIndex ? 'bg-[#22c55e] text-white' : i === stepIndex ? 'bg-[#4ade80]/20 text-[#4ade80] border border-[#4ade80]' : 'bg-white/10 text-white/60'"
                        >
                            <Check v-if="i < stepIndex" class="w-3.5 h-3.5" />
                            <template v-else>{{ i + 1 }}</template>
                        </span>
                        <span class="text-xs font-bold text-white truncate">{{ step.title }}</span>
                    </div>
                    <p class="text-[10px] text-white/60 truncate pl-8">{{ step.desc }}</p>
                </div>
            </div>

            <!-- Wizard content card -->
            <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-6">
                <form @submit.prevent="submit" ref="formRef">
                    <!-- Paso 1: Ubicación -->
                    <div v-show="stepIndex === 0" class="space-y-5">
                        <h3 class="text-base font-bold text-white flex items-center gap-2 border-b border-white/15 pb-3">
                            <MapPin class="w-5 h-5 text-[#38bdf8]" />
                            <span>Paso 1: Identificación y ubicación georreferenciada</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="text-xs font-medium text-white/80 block mb-1">Nombre de la parcela / finca / terreno *</label>
                                <input v-model="form.nombreParcela" required placeholder="Ej. Finca El Pinar" class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Departamento *</label>
                                <CustomSelect v-model="form.departamento" @change="onDepartamentoChange" :options="DEPARTAMENTOS" placeholder="Seleccione..." />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Municipio *</label>
                                <CustomSelect
                                    v-model="form.municipio"
                                    :disabled="!form.departamento"
                                    :options="municipioOptions"
                                    :placeholder="form.departamento ? 'Seleccione...' : 'Elige primero un departamento'"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Aldea / comunidad</label>
                                <input v-model="form.comunidad" placeholder="Ej. Chisecito" class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Fecha de registro</label>
                                <input v-model="form.fechaRegistro" type="date" class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white focus:outline-none transition-colors" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Tenencia de la tierra</label>
                                <CustomSelect v-model="form.tenenciaTierra" :options="[{ value: '', label: 'Sin especificar' }, ...TENENCIA_TIERRA]" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Productor / responsable</label>
                                <input v-model="form.propietario" placeholder="Nombre del responsable" class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Teléfono / contacto</label>
                                <input v-model="form.telefono" placeholder="Opcional" class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Familias beneficiadas</label>
                                <input v-model.number="form.numFamiliasBeneficiadas" type="number" min="0" step="1" placeholder="Ej. 4" class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                            </div>
                        </div>

                        <!-- GPS Section -->
                        <div class="bg-black/30 p-4 rounded-xl border border-white/10 space-y-3">
                            <div class="flex flex-wrap justify-between items-center gap-2">
                                <span class="text-xs font-bold text-white flex items-center gap-1.5">
                                    <Compass class="w-4 h-4 text-[#4ade80]" />
                                    <span>Ubicación GPS</span>
                                </span>
                                <button type="button" @click="capturarGeo" class="px-3.5 py-1.5 bg-[#22c55e] hover:bg-[#16a34a] text-black rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm">
                                    <MapPin class="w-3.5 h-3.5" />
                                    <span>Capturar ubicación actual</span>
                                </button>
                            </div>
                            <p class="text-[11px] text-white/60">{{ geoStatus }}</p>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                <div>
                                    <span class="text-[10px] text-white/60 uppercase font-bold block mb-1">Latitud</span>
                                    <input v-model="form.latitud" type="number" step="any" placeholder="15.4700" class="w-full bg-white/5 border border-white/15 rounded-xl p-2 text-xs text-white focus:outline-none focus:border-white/50" />
                                </div>
                                <div>
                                    <span class="text-[10px] text-white/60 uppercase font-bold block mb-1">Longitud</span>
                                    <input v-model="form.longitud" type="number" step="any" placeholder="-90.3700" class="w-full bg-white/5 border border-white/15 rounded-xl p-2 text-xs text-white focus:outline-none focus:border-white/50" />
                                </div>
                                <div>
                                    <span class="text-[10px] text-white/60 uppercase font-bold block mb-1">Altitud (msnm)</span>
                                    <input v-model="form.altitud" type="number" min="0" step="1" placeholder="650" class="w-full bg-white/5 border border-white/15 rounded-xl p-2 text-xs text-white focus:outline-none focus:border-white/50" />
                                </div>
                                <div>
                                    <span class="text-[10px] text-white/60 uppercase font-bold block mb-1">Precisión GPS (m)</span>
                                    <input v-model="form.gpsPrecision" type="number" min="0" step="1" readonly placeholder="Automático" class="w-full bg-white/5 border border-white/15 rounded-xl p-2 text-xs text-white/70 focus:outline-none" />
                                </div>
                            </div>

                            <div class="pt-1">
                                <span class="text-[10px] text-white/60 uppercase font-bold block mb-1.5">Contorno de la parcela (opcional)</span>
                                <MapaParcela v-model="form.poligono" :focus="focusPoint" @change="onPoligonoChange" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Área (hectáreas) *</label>
                                <input v-model.number="form.areaHa" type="number" min="0" step="0.01" required placeholder="12.50" class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Estado del proceso *</label>
                                <CustomSelect v-model="form.estado" :options="ESTADOS_PROCESO" />
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2: Suelo y agua -->
                    <div v-show="stepIndex === 1" class="space-y-5">
                        <h3 class="text-base font-bold text-white flex items-center gap-2 border-b border-white/15 pb-3">
                            <Droplets class="w-5 h-5 text-[#facc15]" />
                            <span>Paso 2: Diagnóstico edáfico, topografía e hidrología</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Uso actual del suelo</label>
                                <CustomSelect v-model="form.usoActual" :options="USOS_ACTUALES" placeholder="Sin especificar" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Cultivo principal</label>
                                <input v-model="form.cultivoPrincipal" placeholder="Maíz, café, pastos..." class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Clase textural del suelo</label>
                                <CustomSelect v-model="form.claseTextural" :options="CLASE_TEXTURAL_OPTS" placeholder="Sin especificar" />
                                <input v-if="form.claseTextural === OTRO" v-model="form.claseTexturalOtro" placeholder="Especifica la clase textural" class="mt-2 w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Pendiente estimada (%)</label>
                                <input v-model.number="form.pendiente" type="number" min="0" step="0.1" placeholder="8.5" class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                            </div>
                        </div>

                        <div class="bg-black/30 p-4 rounded-xl border border-white/10 space-y-3">
                            <span class="text-xs font-bold text-white flex items-center gap-1.5">
                                <Droplets class="w-4 h-4 text-[#38bdf8]" />
                                <span>Fuente original de agua</span>
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-medium text-white/80 block mb-1">Fuente principal</label>
                                    <CustomSelect v-model="form.fuenteAguaPrincipal" :options="FUENTE_PRINCIPAL_OPTS" placeholder="Sin especificar" />
                                    <input v-if="form.fuenteAguaPrincipal === OTRA" v-model="form.fuenteAguaPrincipalOtro" placeholder="Especifica la fuente principal" class="mt-2 w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-white/80 block mb-1">Fuentes secundarias</label>
                                    <MultiSelect v-model="form.fuenteAguaSecundaria" :options="FUENTE_SECUNDARIA_OPTS" placeholder="Ninguna / selecciona..." />
                                    <input v-if="form.fuenteAguaSecundaria.includes(OTRA)" v-model="form.fuenteAguaSecundariaOtro" placeholder="Otras fuentes (sepáralas con comas)" class="mt-2 w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Riesgo de erosión</label>
                                <CustomSelect v-model="form.riesgoErosion" :options="RIESGO_EROSION" placeholder="Sin especificar" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Sistema de riego</label>
                                <input v-model="form.sistemaRiego" placeholder="Goteo, aspersión, ninguno..." class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                            </div>
                        </div>

                        <div class="bg-black/30 p-4 rounded-xl border border-white/10 space-y-3">
                            <span class="text-xs font-bold text-white flex items-center gap-1.5">
                                <Mountain class="w-4 h-4 text-[#facc15]" />
                                <span>Diagnóstico físico del suelo</span>
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-medium text-white/80 block mb-1">Profundidad de suelo (cm)</label>
                                    <input v-model.number="form.profundidadSuelo" type="number" min="0" step="1" placeholder="45" class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-white/80 block mb-1">¿Se encharca el agua?</label>
                                    <CustomSelect v-model="form.encharca" :options="[{ value: '', label: 'Sin evaluar' }, 'No', 'Sí']" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="text-xs font-medium text-white/80 block mb-1">Limitante de uso</label>
                                    <MultiSelect v-model="form.limitantesUso" :options="LIMITANTES_OPTS" placeholder="Selecciona las limitantes..." />
                                    <input v-if="form.limitantesUso.includes(OTRA)" v-model="form.limitantesUsoOtro" placeholder="Otras limitantes (sepáralas con comas)" class="mt-2 w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="text-xs font-medium text-white/80 block mb-1">Bioindicadores del suelo</label>
                                    <MultiSelect v-model="form.bioindicadores" :options="BIOINDICADORES_OPTS" placeholder="Selecciona los bioindicadores..." />
                                    <input v-if="form.bioindicadores.includes(OTRO)" v-model="form.bioindicadoresOtro" placeholder="Otros bioindicadores (sepáralos con comas)" class="mt-2 w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-black/30 p-4 rounded-xl border border-white/10 space-y-3">
                            <span class="text-xs font-bold text-white flex items-center gap-1.5">
                                <CloudRain class="w-4 h-4 text-[#38bdf8]" />
                                <span>Lluvia (opcional)</span>
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-medium text-white/80 block mb-1">Lluvia acumulada anual (mm)</label>
                                    <input v-model.number="form.lluviaAnual" type="number" min="0" step="1" placeholder="1800" class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-white/80 block mb-1">Fuente / año</label>
                                    <input v-model="form.lluviaFuente" placeholder="INSIVUMEH 2025, estación local..." class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Intervención -->
                    <div v-show="stepIndex === 2" class="space-y-5">
                        <h3 class="text-base font-bold text-white flex items-center gap-2 border-b border-white/15 pb-3">
                            <Sliders class="w-5 h-5 text-[#4ade80]" />
                            <span>Paso 3: Intervenciones y seguimiento</span>
                        </h3>

                        <div>
                            <label class="text-xs font-medium text-white/80 block mb-1">Intervenciones previstas / ejecutadas</label>
                            <input v-model="form.intervenciones" placeholder="Canales keyline, reservorios, reforestación, zanjas de infiltración..." class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                        </div>

                        <div>
                            <label class="text-xs font-medium text-white/80 block mb-1">Especies usadas en reforestación / cobertura</label>
                            <input v-model="form.especiesReforestacion" placeholder="Ej. madrecacao, gravilea, pasto de corte..." class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-white/80 block mb-1">Fecha próxima visita de seguimiento</label>
                                <input v-model="form.fechaProximaVisita" type="date" class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white focus:outline-none transition-colors" />
                            </div>
                            <label class="flex items-center gap-2.5 text-xs text-white/80 cursor-pointer select-none pt-5">
                                <input v-model="form.consentimientoProductor" type="checkbox" class="rounded border-white/20 text-[#22c55e] focus:ring-0 bg-white/10 w-4 h-4" />
                                <span>El productor autoriza el uso de esta información</span>
                            </label>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-white/80 block mb-1">Observaciones</label>
                            <textarea v-model="form.observaciones" rows="4" placeholder="Notas técnicas, restricciones, acuerdos con el productor, próximos pasos..." class="w-full bg-white/5 border border-white/15 focus:border-white/50 rounded-xl p-2.5 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Paso 4: Fotos -->
                    <div v-show="stepIndex === 3" class="space-y-5">
                        <h3 class="text-base font-bold text-white flex items-center gap-2 border-b border-white/15 pb-3">
                            <Camera class="w-5 h-5 text-[#38bdf8]" />
                            <span>Paso 4: Registro fotográfico y validación</span>
                        </h3>

                        <div>
                            <label class="text-xs font-medium text-white/80 block mb-2">Fotografías del levantamiento</label>
                            <label class="border-2 border-dashed border-white/20 hover:border-[#4ade80] rounded-2xl p-6 flex flex-col items-center justify-center cursor-pointer bg-black/30 transition-colors">
                                <Upload class="w-8 h-8 text-[#4ade80] mb-2" />
                                <span class="text-xs font-bold text-white">Toca para tomar o seleccionar fotos</span>
                                <span class="text-[10px] text-white/60 mt-1">JPG, PNG o WEBP · máx. 12MB cada una · hasta 8 fotos por carga</span>
                                <input type="file" accept="image/*" multiple capture="environment" @change="onPhotoInput" class="hidden" />
                            </label>

                            <div v-if="existingFotos.length || pendingPhotos.length" class="grid grid-cols-3 sm:grid-cols-4 gap-3 mt-3">
                                <div v-for="f in existingFotos" :key="f.id" class="relative h-20 rounded-xl overflow-hidden border border-white/10">
                                    <img :src="fotoUrl(f.miniatura || f.archivo)" class="w-full h-full object-cover" />
                                    <button type="button" @click="removeExistingFoto(f)" class="absolute top-1 right-1 bg-black/60 text-white rounded-full w-5 h-5 flex items-center justify-center hover:bg-[#ef4444]">
                                        <X class="w-3 h-3" />
                                    </button>
                                </div>
                                <div v-for="(file, i) in pendingPhotos" :key="'p' + i" class="relative h-20 rounded-xl overflow-hidden border border-white/10">
                                    <img :src="previewUrl(file)" class="w-full h-full object-cover" />
                                    <button type="button" @click="pendingPhotos.splice(i, 1)" class="absolute top-1 right-1 bg-black/60 text-white rounded-full w-5 h-5 flex items-center justify-center hover:bg-[#ef4444]">
                                        <X class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-white/10 border border-white/15 rounded-xl flex items-center gap-3">
                            <FileCheck2 class="w-6 h-6 text-[#4ade80] flex-shrink-0" />
                            <div class="text-xs text-[#f1f5f9]">
                                <p class="font-bold text-white">Listo para envío de ficha técnica</p>
                                <p class="text-[11px] text-white/80">La parcela se guardará con el estado seleccionado y su geolocalización.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer navigation -->
                    <div class="mt-8 pt-4 border-t border-white/10 flex justify-between items-center">
                        <button
                            type="button"
                            :disabled="stepIndex === 0"
                            @click="stepIndex--"
                            class="flex items-center gap-1.5 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-xl text-xs font-semibold text-white disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                        >
                            <ArrowLeft class="w-4 h-4" />
                            <span>Anterior</span>
                        </button>

                        <button
                            v-if="stepIndex < STEPS.length - 1"
                            type="button"
                            @click="nextStep"
                            class="flex items-center gap-1.5 px-5 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-black rounded-xl text-xs font-bold transition-all shadow-md"
                        >
                            <span>Siguiente</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                        <button
                            v-else
                            type="submit"
                            :disabled="saving"
                            class="flex items-center gap-1.5 px-6 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-black rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-lg disabled:opacity-60"
                        >
                            <Check class="w-4 h-4" />
                            <span>{{ saving ? 'Guardando…' : 'Guardar parcela' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import parcelaService from '../../services/parcelaService';
import { parcelaFotoUrl } from '../../services/api';
import { toastSuccess, toastInfo, alertError, confirmDialog } from '../../utils/alerts';
import CustomSelect from '../../components/ui/CustomSelect.vue';
import MultiSelect from '../../components/ui/MultiSelect.vue';
import MapaParcela from '../../components/parcelas/MapaParcela.vue';
import {
    DEPARTAMENTOS, ESTADOS_PROCESO, USOS_ACTUALES, RIESGO_EROSION, TENENCIA_TIERRA,
    MUNICIPIOS_POR_DEPARTAMENTO, BIOINDICADORES_SUELO, CLASES_TEXTURALES,
    FUENTE_AGUA_ORIGINAL, LIMITANTES_USO,
} from '../../constants/keyline';
import {
    Check, MapPin, Sliders, Upload, Compass, Camera, ArrowLeft, ArrowRight,
    Droplets, Mountain, CloudRain, FileCheck2, X,
} from '@lucide/vue';

const props = defineProps({ id: { type: String, default: null } });
const route = useRoute();
const router = useRouter();

const STEPS = [
    { key: 'ubicacion', title: 'Ubicación', desc: 'Identificación y GPS' },
    { key: 'suelo', title: 'Suelo y agua', desc: 'Diagnóstico físico' },
    { key: 'intervencion', title: 'Intervención', desc: 'Prácticas y seguimiento' },
    { key: 'fotos', title: 'Fotos', desc: 'Evidencias de campo' },
];

// Etiquetas de "Otro / especificar" para los menús normalizados.
const OTRO = 'Otro';
const OTRA = 'Otra';
const CLASE_TEXTURAL_OPTS = [{ value: '', label: 'Sin especificar' }, ...CLASES_TEXTURALES, OTRO];
const FUENTE_PRINCIPAL_OPTS = [{ value: '', label: 'Sin especificar' }, ...FUENTE_AGUA_ORIGINAL, OTRA];
const FUENTE_SECUNDARIA_OPTS = [...FUENTE_AGUA_ORIGINAL, OTRA];
const BIOINDICADORES_OPTS = [...BIOINDICADORES_SUELO, OTRO];
const LIMITANTES_OPTS = [...LIMITANTES_USO, OTRA];

// --- Serialización de los menús multi-valor <-> texto separado por comas ---
function splitList(str) {
    return String(str || '').split(/\s*[,;]\s*/).map((s) => s.trim()).filter(Boolean);
}

function serializeMulti(arr, otroText) {
    const base = (arr || []).filter((v) => v !== OTRO && v !== OTRA);
    return [...base, ...splitList(otroText)].join(', ');
}

function deserializeMulti(str, known) {
    const selected = [];
    const otros = [];
    splitList(str).forEach((token) => {
        const match = known.find((k) => k.toLowerCase() === token.toLowerCase());
        if (match) selected.push(match);
        else otros.push(token);
    });
    return { selected, otro: otros.join(', ') };
}

function deserializeSingle(value, known, otroFlag) {
    if (value && !known.some((k) => k.toLowerCase() === String(value).toLowerCase())) {
        return { value: otroFlag, otro: String(value) };
    }
    return { value: value || '', otro: '' };
}

const editingId = computed(() => props.id || route.params.id || null);
const isEditing = computed(() => !!editingId.value);

const stepIndex = ref(0);
const loadingParcela = ref(false);
const saving = ref(false);
const parcela = ref(null);
const geoStatus = ref('Aún no capturada. Puedes ingresarla manualmente si lo prefieres.');
const existingFotos = ref([]);
const pendingPhotos = ref([]);
const formRef = ref(null);

function blankForm() {
    return {
        nombreParcela: '', departamento: '', municipio: '', comunidad: '', fechaRegistro: todayISO(),
        propietario: '', telefono: '', tenenciaTierra: '', numFamiliasBeneficiadas: '',
        latitud: '', longitud: '', altitud: '', gpsPrecision: '', poligono: [], areaHa: '', estado: 'Levantamiento',
        usoActual: '', cultivoPrincipal: '', claseTextural: '', claseTexturalOtro: '', pendiente: '',
        fuenteAguaPrincipal: '', fuenteAguaPrincipalOtro: '',
        fuenteAguaSecundaria: [], fuenteAguaSecundariaOtro: '',
        riesgoErosion: '', sistemaRiego: '', profundidadSuelo: '', encharca: '',
        limitantesUso: [], limitantesUsoOtro: '',
        bioindicadores: [], bioindicadoresOtro: '',
        lluviaAnual: '', lluviaFuente: '', intervenciones: '', especiesReforestacion: '',
        fechaProximaVisita: '', consentimientoProductor: false, observaciones: '',
    };
}

const form = reactive(blankForm());

const municipiosDisponibles = computed(() => MUNICIPIOS_POR_DEPARTAMENTO[form.departamento] || []);
const municipioOptions = computed(() => (
    form.municipio && !municipiosDisponibles.value.includes(form.municipio)
        ? [...municipiosDisponibles.value, form.municipio]
        : municipiosDisponibles.value
));

function onDepartamentoChange() {
    if (!municipiosDisponibles.value.includes(form.municipio)) {
        form.municipio = '';
    }
}

function todayISO() {
    const now = new Date();
    const tz = now.getTimezoneOffset();
    return new Date(now.getTime() - tz * 60000).toISOString().slice(0, 10);
}

// Contorno de la parcela dibujado en el mapa.
const focusPoint = computed(() => (
    form.latitud !== '' && form.longitud !== '' && Number.isFinite(Number(form.latitud))
        ? [Number(form.latitud), Number(form.longitud)]
        : null
));

function safeParsePoligono(str) {
    try {
        const arr = JSON.parse(str);
        return Array.isArray(arr) ? arr : [];
    } catch {
        return [];
    }
}

function onPoligonoChange({ areaHa, centroid }) {
    if (areaHa > 0) form.areaHa = Number(areaHa.toFixed(2));
    // Solo autocompleta las coordenadas si aún no hay un punto capturado a mano/por GPS.
    if (centroid && form.latitud === '' && form.longitud === '') {
        form.latitud = Number(centroid[0].toFixed(6));
        form.longitud = Number(centroid[1].toFixed(6));
        geoStatus.value = `Ubicación derivada del contorno: ${form.latitud}, ${form.longitud}`;
    }
}

onMounted(async () => {
    if (!editingId.value) return;
    loadingParcela.value = true;
    try {
        const { data } = await parcelaService.obtener(editingId.value);
        const p = data.parcela;
        parcela.value = p;

        // Campos con formato propio (menús normalizados, contorno): se rehidratan aparte (abajo).
        const CAMPOS_APARTE = new Set([
            'claseTextural', 'fuenteAguaPrincipal', 'fuenteAguaSecundaria', 'limitantesUso', 'bioindicadores', 'poligono',
        ]);
        Object.keys(form).forEach((key) => {
            if (!CAMPOS_APARTE.has(key) && p[key] !== undefined && p[key] !== null) {
                form[key] = p[key];
            }
        });

        form.poligono = safeParsePoligono(p.poligono);

        const ct = deserializeSingle(p.claseTextural, CLASES_TEXTURALES, OTRO);
        form.claseTextural = ct.value;
        form.claseTexturalOtro = ct.otro;

        const fp = deserializeSingle(p.fuenteAguaPrincipal, FUENTE_AGUA_ORIGINAL, OTRA);
        form.fuenteAguaPrincipal = fp.value;
        form.fuenteAguaPrincipalOtro = fp.otro;

        const sec = deserializeMulti(p.fuenteAguaSecundaria, FUENTE_AGUA_ORIGINAL);
        form.fuenteAguaSecundaria = sec.otro ? [...sec.selected, OTRA] : sec.selected;
        form.fuenteAguaSecundariaOtro = sec.otro;

        const lim = deserializeMulti(p.limitantesUso, LIMITANTES_USO);
        form.limitantesUso = lim.otro ? [...lim.selected, OTRA] : lim.selected;
        form.limitantesUsoOtro = lim.otro;

        const bio = deserializeMulti(p.bioindicadores, BIOINDICADORES_SUELO);
        form.bioindicadores = bio.otro ? [...bio.selected, OTRO] : bio.selected;
        form.bioindicadoresOtro = bio.otro;

        existingFotos.value = p.fotos || [];
        if (form.latitud !== '' && form.longitud !== '') {
            geoStatus.value = `Ubicación guardada: ${form.latitud}, ${form.longitud}`;
        }
    } finally {
        loadingParcela.value = false;
    }
});

function nextStep() {
    if (stepIndex.value === 0) {
        if (!form.departamento) {
            alertError('Selecciona un departamento antes de continuar.');
            return;
        }
        if (!form.municipio) {
            alertError('Selecciona un municipio antes de continuar.');
            return;
        }
    }

    const panel = formRef.value?.querySelectorAll('[required]');
    let firstInvalid = null;
    panel?.forEach((el) => {
        if (isVisible(el) && !el.checkValidity() && !firstInvalid) firstInvalid = el;
    });
    if (firstInvalid) {
        firstInvalid.reportValidity();
        return;
    }
    stepIndex.value = Math.min(STEPS.length - 1, stepIndex.value + 1);
}

function isVisible(el) {
    return el.offsetParent !== null;
}

function capturarGeo() {
    if (!navigator.geolocation) {
        geoStatus.value = 'Tu navegador no soporta geolocalización.';
        return;
    }
    geoStatus.value = 'Obteniendo ubicación…';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            form.latitud = Number(pos.coords.latitude.toFixed(6));
            form.longitud = Number(pos.coords.longitude.toFixed(6));
            form.gpsPrecision = Math.round(pos.coords.accuracy);
            if (pos.coords.altitude) form.altitud = Math.round(pos.coords.altitude);
            geoStatus.value = `Ubicación capturada (precisión ±${Math.round(pos.coords.accuracy)} m)`;
        },
        (err) => { geoStatus.value = `No se pudo obtener la ubicación (${err.message}). Puedes ingresarla manualmente.`; },
        { enableHighAccuracy: true, timeout: 12000 }
    );
}

const MAX_FOTOS = 8;
const MAX_FOTO_MB = 12;

function onPhotoInput(e) {
    const nuevos = Array.from(e.target.files || []);
    e.target.value = '';

    const sobrepeso = nuevos.filter((f) => f.size > MAX_FOTO_MB * 1024 * 1024);
    const validos = nuevos.filter((f) => f.size <= MAX_FOTO_MB * 1024 * 1024);
    if (sobrepeso.length) {
        alertError(`${sobrepeso.length} foto(s) superan los ${MAX_FOTO_MB}MB y no se agregaron.`);
    }

    const combinados = pendingPhotos.value.concat(validos);
    if (combinados.length > MAX_FOTOS) {
        alertError(`Máximo ${MAX_FOTOS} fotos por carga. Se tomaron las primeras ${MAX_FOTOS}.`);
    }
    pendingPhotos.value = combinados.slice(0, MAX_FOTOS);
}

function previewUrl(file) {
    return URL.createObjectURL(file);
}

function fotoUrl(name) {
    return parcelaFotoUrl(editingId.value, name);
}

async function removeExistingFoto(foto) {
    const ok = await confirmDialog('Esta foto se eliminará de la parcela.', { title: '¿Eliminar esta foto?', danger: true, confirmText: 'Eliminar' });
    if (!ok) return;
    await parcelaService.eliminarFoto(editingId.value, foto.id);
    existingFotos.value = existingFotos.value.filter((f) => f.id !== foto.id);
    toastInfo('Foto eliminada.');
}

async function submit() {
    if (!form.departamento || !form.municipio) {
        alertError('Selecciona departamento y municipio antes de guardar.');
        stepIndex.value = 0;
        return;
    }
    if (formRef.value && !formRef.value.checkValidity()) {
        formRef.value.reportValidity();
        return;
    }
    saving.value = true;
    try {
        const payload = { ...form };

        // Menús normalizados -> texto separado por comas para la API.
        payload.claseTextural = form.claseTextural === OTRO ? form.claseTexturalOtro.trim() : form.claseTextural;
        payload.fuenteAguaPrincipal = form.fuenteAguaPrincipal === OTRA ? form.fuenteAguaPrincipalOtro.trim() : form.fuenteAguaPrincipal;
        payload.fuenteAguaSecundaria = serializeMulti(form.fuenteAguaSecundaria, form.fuenteAguaSecundariaOtro);
        payload.limitantesUso = serializeMulti(form.limitantesUso, form.limitantesUsoOtro);
        payload.bioindicadores = serializeMulti(form.bioindicadores, form.bioindicadoresOtro);
        payload.poligono = JSON.stringify(form.poligono || []);
        ['claseTexturalOtro', 'fuenteAguaPrincipalOtro', 'fuenteAguaSecundariaOtro', 'limitantesUsoOtro', 'bioindicadoresOtro']
            .forEach((k) => delete payload[k]);

        let saved;
        if (isEditing.value) {
            const { data } = await parcelaService.actualizar(editingId.value, payload);
            saved = data.parcela;
        } else {
            const { data } = await parcelaService.crear(payload);
            saved = data.parcela;
        }
        if (pendingPhotos.value.length) {
            const fd = new FormData();
            pendingPhotos.value.forEach((f) => fd.append('fotos[]', f));
            await parcelaService.subirFotos(saved.id, fd);
        }
        await toastSuccess(isEditing.value ? 'Parcela actualizada correctamente.' : `Parcela guardada (código ${saved.codigo}).`);
        router.push(homeAfterSave());
    } catch (err) {
        alertError(err.message || 'No se pudo guardar la parcela.');
    } finally {
        saving.value = false;
    }
}

function homeAfterSave() {
    const auth = JSON.parse(localStorage.getItem('user') || 'null');
    if (auth?.role === 'tecnico') return { name: 'MisParcelas' };
    return { name: 'ParcelasList' };
}

function cancelar() {
    router.push(homeAfterSave());
}
</script>
