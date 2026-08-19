<template>
    <div>
        <section class="bg-white rounded-lg shadow p-5 mb-6">
            <h1 class="text-2xl font-bold">Catálogo de variables técnicas</h1>
            <p class="text-sm text-slate-500 mt-1">
                Referencia rápida de las variables que se capturan al registrar una parcela keyline, agrupadas por diagnóstico.
                Usa esta guía como apoyo en campo: te recuerda qué observar y registrar en cada grupo del formulario de registro.
            </p>
        </section>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="g in GRUPOS" :key="g.id" class="bg-white rounded-lg shadow p-4">
                <h4 class="font-bold mb-3">{{ g.icon }} {{ g.label }}</h4>
                <div class="flex flex-wrap gap-2">
                    <span v-for="v in g.variables" :key="v" class="tag bg-slate-100 text-slate-600">{{ v }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const GRUPOS = [
    { id: 'soil', label: 'Suelo', icon: '🪨', variables: ['Profundidad de suelo', 'Tipo / textura', 'Estructura', 'Compactación', 'Materia orgánica', 'Infiltración', 'Riesgo de erosión', 'Color', 'pH', 'Humedad'] },
    { id: 'water', label: 'Agua', icon: '💧', variables: ['Escorrentía superficial', 'Tasa de infiltración', 'Encharcamiento', 'Drenaje', 'Fuentes de agua', 'Ríos / quebradas', 'Nacimientos', 'Reservorios', 'Sistema de riego'] },
    { id: 'rainfall', label: 'Lluvia', icon: '🌧️', variables: ['Lluvia anual acumulada', 'Lluvia mensual', 'Evento máximo', 'Días de lluvia', 'Fuente del dato', 'Fecha de captura'] },
    { id: 'talpetate', label: 'Talpetate', icon: '🪨', variables: ['Presencia', 'Profundidad', 'Espesor', 'Dureza', 'Ubicación en el perfil', 'Porcentaje estimado'] },
    { id: 'bioindicators', label: 'Bioindicadores', icon: '🌱', variables: ['Lombrices', 'Insectos', 'Hongos', 'Raíces', 'Plantas nativas', 'Organismos del suelo', 'Residuo orgánico / hojarasca'] },
    { id: 'topography', label: 'Topografía', icon: '⛰️', variables: ['Elevación', 'Pendiente', 'Orientación', 'Curvas de nivel', 'Punto clave (keypoint)', 'Línea keyline', 'Cresta', 'Valle'] },
];
</script>

<style scoped>
.tag { @apply text-xs font-semibold px-2 py-1 rounded-full; }
</style>
