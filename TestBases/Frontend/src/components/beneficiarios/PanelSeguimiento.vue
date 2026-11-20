<template>
    <div class="fixed inset-0 z-40">
        <div class="absolute inset-0 bg-ink-900/40" @click="intentarCerrar"></div>

        <aside class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl" role="dialog"
            aria-modal="true" aria-labelledby="panel-titulo">
            <!-- Encabezado -->
            <header class="border-b border-rule bg-paper px-5 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wide text-ink-500">Seguimiento</p>
                        <h2 id="panel-titulo" class="mt-0.5 text-lg font-semibold leading-snug text-ink-900">
                            {{ fila.nombre }}
                        </h2>
                    </div>
                    <button class="rounded p-1 text-ink-400 hover:bg-white hover:text-ink-800" aria-label="Cerrar"
                        @click="intentarCerrar">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                    <div>
                        <dt class="text-ink-500">CUI</dt>
                        <dd class="font-mono text-ink-800">{{ cui(fila.cui) }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Teléfono</dt>
                        <dd class="font-mono text-ink-800">{{ telefono(fila.telefono) || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Comunidad</dt>
                        <dd class="text-ink-800">{{ fila.comunidad }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Comunidad base</dt>
                        <dd class="text-ink-800">{{ fila.comunidad_base || '—' }}</dd>
                    </div>
                </dl>
            </header>

            <!-- Contenido -->
            <div class="flex-1 space-y-6 overflow-y-auto px-5 py-5">
                <section v-if="!fila.telefono">
                    <label class="etiqueta" for="telefono">Teléfono</label>
                    <input id="telefono" v-model="telefonoNuevo" class="campo font-mono" type="tel" inputmode="numeric"
                        autocomplete="off" maxlength="9" placeholder="8 dígitos" @input="soloDigitos" />
                    <p v-if="errorTelefono" class="mt-1 text-sm text-danger">{{ errorTelefono }}</p>
                    <p v-else class="mt-1 text-xs text-ink-500">
                        Este beneficiario no tiene teléfono. Si lo consigues, agrégalo aquí y se guardará en la base de datos.
                    </p>
                </section>

                <section>
                    <h3 class="etiqueta">Imagen adjunta</h3>

                    <div v-if="vista" class="overflow-hidden rounded border border-rule bg-paper">
                        <img :src="vista" alt="Imagen adjunta del beneficiario"
                            class="mx-auto block max-h-72 w-full object-contain" />
                    </div>
                    <div v-else-if="cargandoImagen"
                        class="grid h-40 place-items-center rounded border border-rule bg-paper">
                        <span class="h-5 w-5 animate-spin rounded-full border-2 border-rule border-t-brand-600"></span>
                    </div>
                    <label v-else
                        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded border border-dashed px-4 py-8 text-center transition-colors"
                        :class="arrastrando ? 'border-brand-500 bg-brand-50' : 'border-ink-300 hover:border-brand-500 hover:bg-brand-50/50'"
                        @dragover.prevent="arrastrando = true" @dragleave.prevent="arrastrando = false"
                        @drop.prevent="soltar">
                        <ArrowUpTrayIcon class="h-6 w-6 text-ink-400" />
                        <span class="text-sm text-ink-700">Arrastra una imagen o <span
                                class="font-medium text-brand-600 underline">selecciónala</span></span>
                        <span class="text-xs text-ink-500">JPG, PNG o WebP</span>
                        <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp"
                            @change="elegir($event.target.files[0]); $event.target.value = ''" />
                    </label>

                    <p v-if="errorImagen" class="mt-2 text-sm text-danger">{{ errorImagen }}</p>

                    <div v-if="vista" class="mt-3 flex flex-wrap items-center gap-2">
                        <label class="btn-neutro cursor-pointer px-3 py-1.5">
                            <ArrowPathIcon class="h-4 w-4" />
                            Reemplazar
                            <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp"
                                @change="elegir($event.target.files[0]); $event.target.value = ''" />
                        </label>
                        <button v-if="nuevaImagen" class="btn-neutro px-3 py-1.5" @click="descartarNueva">
                            Descartar cambio
                        </button>
                        <a v-else :href="vista" target="_blank" rel="noopener" class="btn-neutro px-3 py-1.5">
                            Ver completa
                        </a>

                        <template v-if="esAdmin && !nuevaImagen && fila.seguimiento.tiene_imagen">
                            <button v-if="!confirmandoQuitar" class="btn-peligro px-3 py-1.5"
                                @click="confirmandoQuitar = true">
                                <TrashIcon class="h-4 w-4" />
                                Eliminar
                            </button>
                            <span v-else class="flex items-center gap-2 text-sm">
                                <span class="text-ink-700">¿Eliminar la imagen?</span>
                                <button class="btn-peligro px-3 py-1" :disabled="guardando" @click="quitar">Sí,
                                    eliminar</button>
                                <button class="btn-neutro px-3 py-1" @click="confirmandoQuitar = false">No</button>
                            </span>
                        </template>
                    </div>
                    <p v-if="nuevaImagen" class="mt-2 text-xs text-ink-500">
                        {{ nuevaImagen.name }} · {{ kb(nuevaImagen.size) }} · se subirá al guardar
                    </p>
                </section>

                <section>
                    <div class="flex items-baseline justify-between">
                        <label class="etiqueta" for="comentario">Comentario</label>
                        <span class="text-xs tabular-nums" :class="comentario.length > MAX ? 'text-danger' : 'text-ink-400'">
                            {{ comentario.length }}/{{ MAX }}
                        </span>
                    </div>
                    <textarea id="comentario" ref="campoComentario" v-model="comentario" class="campo min-h-[9rem] resize-y"
                        placeholder="Escribe una observación sobre este beneficiario…"></textarea>
                </section>

                <p v-if="fila.seguimiento.actualizado_en" class="border-t border-rule pt-4 text-xs text-ink-500">
                    Última actualización: {{ fechaHora(fila.seguimiento.actualizado_en) }}
                    <template v-if="fila.seguimiento.actualizado_por"> · {{ fila.seguimiento.actualizado_por }}</template>
                </p>
            </div>

            <!-- Acciones -->
            <footer class="flex items-center justify-end gap-3 border-t border-rule bg-paper px-5 py-3">
                <button class="btn-neutro" @click="intentarCerrar">Cancelar</button>
                <button class="btn-primario min-w-[7.5rem]" :disabled="!hayCambios || guardando || comentario.length > MAX"
                    @click="guardar">
                    <span v-if="guardando"
                        class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    {{ guardando ? 'Guardando…' : 'Guardar' }}
                </button>
            </footer>
        </aside>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { ArrowPathIcon, ArrowUpTrayIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/20/solid';
import beneficiarioService from '../../services/beneficiarioService';
import { mensajeDeError } from '../../services/api';
import { cui, fechaHora, kb, telefono } from '../../utils/formato';
import { prepararImagen, TIPOS_ACEPTADOS } from '../../utils/imagen';
import { toast } from '../../utils/toast';

const MAX = 2000;
const MAX_BYTES = 5 * 1024 * 1024;

const props = defineProps({
    fila: { type: Object, required: true },
    esAdmin: { type: Boolean, default: false },
});
const emit = defineEmits(['cerrar', 'guardado']);

const comentarioInicial = props.fila.seguimiento.comentario ?? '';
const comentario = ref(comentarioInicial);
const telefonoNuevo = ref('');
const errorTelefono = ref('');
const campoComentario = ref(null);

const imagenActual = ref(null);       // object URL de la imagen que ya está en el servidor
const nuevaImagen = ref(null);        // File elegido, aún sin subir
const vistaNueva = ref(null);         // object URL de la imagen elegida
const cargandoImagen = ref(false);
const errorImagen = ref('');
const arrastrando = ref(false);
const guardando = ref(false);
const confirmandoQuitar = ref(false);

const vista = computed(() => vistaNueva.value || imagenActual.value);
const hayCambios = computed(() =>
    !!nuevaImagen.value || telefonoNuevo.value !== '' || comentario.value.trim() !== comentarioInicial.trim());

// El teléfono solo acepta números, máximo 8 dígitos
const soloDigitos = () => {
    telefonoNuevo.value = telefonoNuevo.value.replace(/\D/g, '').slice(0, 8);
    errorTelefono.value = '';
};

const liberar = (url) => url && URL.revokeObjectURL(url);

const cargarImagen = async () => {
    if (!props.fila.seguimiento.tiene_imagen) return;
    cargandoImagen.value = true;
    try {
        imagenActual.value = URL.createObjectURL(await beneficiarioService.imagen(props.fila.id));
    } catch (e) {
        errorImagen.value = mensajeDeError(e, 'No se pudo cargar la imagen');
    } finally {
        cargandoImagen.value = false;
    }
};

const elegir = async (archivo) => {
    errorImagen.value = '';
    if (!archivo) return;
    if (!TIPOS_ACEPTADOS.includes(archivo.type)) {
        errorImagen.value = 'Solo se aceptan imágenes JPG, PNG o WebP';
        return;
    }
    const listo = await prepararImagen(archivo);
    if (listo.size > MAX_BYTES) {
        errorImagen.value = 'La imagen supera el máximo de 5 MB';
        return;
    }
    liberar(vistaNueva.value);
    nuevaImagen.value = listo;
    vistaNueva.value = URL.createObjectURL(listo);
    confirmandoQuitar.value = false;
};

const soltar = (e) => {
    arrastrando.value = false;
    elegir(e.dataTransfer.files[0]);
};

const descartarNueva = () => {
    liberar(vistaNueva.value);
    vistaNueva.value = null;
    nuevaImagen.value = null;
    errorImagen.value = '';
};

const guardar = async () => {
    errorTelefono.value = '';
    if (telefonoNuevo.value !== '' && telefonoNuevo.value.length !== 8) {
        errorTelefono.value = 'El teléfono debe tener 8 dígitos';
        return;
    }
    guardando.value = true;
    try {
        const respuesta = await beneficiarioService.guardarSeguimiento(props.fila.id, {
            comentario: comentario.value.trim(),
            imagen: nuevaImagen.value,
            telefono: telefonoNuevo.value,
        });
        toast.ok('Cambios guardados');
        emit('guardado', props.fila.id, respuesta);
        emit('cerrar');
    } catch (e) {
        toast.error(mensajeDeError(e, 'No se pudo guardar'));
    } finally {
        guardando.value = false;
    }
};

const quitar = async () => {
    guardando.value = true;
    try {
        const respuesta = await beneficiarioService.quitarImagen(props.fila.id);
        liberar(imagenActual.value);
        imagenActual.value = null;
        confirmandoQuitar.value = false;
        toast.ok('Imagen eliminada');
        // el listado actualiza la fila; el panel sigue abierto para editar el comentario
        emit('guardado', props.fila.id, respuesta);
    } catch (e) {
        toast.error(mensajeDeError(e, 'No se pudo eliminar la imagen'));
    } finally {
        guardando.value = false;
    }
};

const intentarCerrar = () => {
    if (hayCambios.value && !window.confirm('Hay cambios sin guardar. ¿Cerrar de todos modos?')) return;
    emit('cerrar');
};

const alPresionarTecla = (e) => {
    if (e.key === 'Escape') intentarCerrar();
};

onMounted(() => {
    document.addEventListener('keydown', alPresionarTecla);
    document.body.classList.add('overflow-hidden');
    cargarImagen();
    nextTick(() => campoComentario.value?.focus());
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', alPresionarTecla);
    document.body.classList.remove('overflow-hidden');
    liberar(imagenActual.value);
    liberar(vistaNueva.value);
});
</script>
