<template>
    <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden p-6 animate-fade-in">
        <h3 class="text-xl font-bold text-brand-dark dark:text-white mb-6">Dashboard Climatológico</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Widgets de estado -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-blue-100 text-sm font-medium mb-1">Registros Totales</p>
                    <h4 class="text-3xl font-black">{{ totalRegistros }}</h4>
                </div>
            </div>

            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-emerald-100 text-sm font-medium mb-1">Condiciones Normales</p>
                    <h4 class="text-3xl font-black">{{ condiciones }}</h4>
                </div>
            </div>

            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-red-100 text-sm font-medium mb-1">Desastres</p>
                    <h4 class="text-3xl font-black">{{ desastres }}</h4>
                </div>
            </div>

            <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-amber-100 text-sm font-medium mb-1">Alertas Activas</p>
                    <h4 class="text-3xl font-black">{{ alertasActivas }}</h4>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">Clima Actual</h4>
                <div v-if="loadingWeather" class="animate-pulse flex space-x-4">
                    <div class="h-20 w-20 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                    <div class="flex-1 space-y-4 py-1">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>
                </div>
                <div v-else-if="weather && weather.weather" class="flex items-center gap-6">
                    <div class="text-center">
                        <img :src="`https://openweathermap.org/img/wn/${weather.weather[0].icon}@4x.png`" class="w-32 h-32 mx-auto drop-shadow-md" />
                        <p class="text-sm font-bold text-gray-500 uppercase">{{ weather.weather[0].description }}</p>
                    </div>
                    <div>
                        <h2 class="text-5xl font-black text-gray-800 dark:text-white mb-2">{{ Math.round(weather.main.temp) }}°C</h2>
                        <div class="grid grid-cols-2 gap-4 mt-4 text-sm text-gray-600 dark:text-gray-400">
                            <div><span class="font-bold">Humedad:</span> {{ weather.main.humidity }}%</div>
                            <div><span class="font-bold">Viento:</span> {{ weather.wind.speed }} m/s</div>
                            <div><span class="font-bold">Mín:</span> {{ weather.main.temp_min }}°C</div>
                            <div><span class="font-bold">Máx:</span> {{ weather.main.temp_max }}°C</div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-red-500 text-sm mt-4 font-bold bg-red-50 p-4 rounded-xl border border-red-100">
                    No se pudo cargar la información climática (Verifique la API Key).
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">Acciones Rápidas</h4>
                <div class="space-y-3">
                    <router-link to="/admin/clima/mapa" class="block w-full text-left px-4 py-3 bg-white dark:bg-gray-800 rounded-xl hover:shadow-md transition text-sm font-bold text-brand-dark dark:text-white">
                        🗺️ Ver Mapa de Registros
                    </router-link>
                    <router-link to="/admin/clima/registros" class="block w-full text-left px-4 py-3 bg-white dark:bg-gray-800 rounded-xl hover:shadow-md transition text-sm font-bold text-brand-dark dark:text-white">
                        📝 Gestionar Registros
                    </router-link>
                    <router-link to="/admin/clima/alertas" class="block w-full text-left px-4 py-3 bg-white dark:bg-gray-800 rounded-xl hover:shadow-md transition text-sm font-bold text-brand-dark dark:text-white">
                        🚨 Gestionar Alertas
                    </router-link>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <!-- Gráfico de Historial de Temperaturas -->
            <div class="bg-white dark:bg-[#1E293B] rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm relative overflow-hidden">
                <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">Historial de Temperatura (7 días)</h4>
                <div v-if="loadingHistory" class="animate-pulse h-[300px] bg-gray-100 dark:bg-gray-800 rounded-xl"></div>
                <div v-else class="relative z-10 w-full h-[300px]">
                    <VueApexCharts type="area" height="100%" :options="chartOptions" :series="chartSeries" />
                </div>
            </div>

            <!-- Pronóstico 4-5 Días -->
            <div class="bg-white dark:bg-[#1E293B] rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm">
                <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">Pronóstico Próximos Días</h4>
                <div v-if="loadingForecast" class="animate-pulse space-y-4">
                    <div v-for="i in 4" :key="i" class="h-16 bg-gray-100 dark:bg-gray-800 rounded-xl"></div>
                </div>
                <div v-else class="space-y-3">
                    <div v-for="(dia, index) in forecast" :key="index" 
                         class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-800/50 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition">
                        <div class="flex items-center gap-4">
                            <span class="text-2xl">{{ dia.icono }}</span>
                            <div>
                                <h5 class="font-bold text-gray-800 dark:text-gray-200">{{ dia.dia }}</h5>
                                <p class="text-xs text-gray-500 capitalize">{{ dia.descripcion }}</p>
                            </div>
                        </div>
                        <div class="text-xl font-black text-brand-dark dark:text-white">
                            {{ dia.temperatura }}°C
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import VueApexCharts from 'vue3-apexcharts';

const totalRegistros = ref(0);
const condiciones = ref(0);
const desastres = ref(0);
const alertasActivas = ref(0);

const weather = ref(null);
const loadingWeather = ref(true);

const forecast = ref([]);
const loadingForecast = ref(true);

const historial = ref([]);
const loadingHistory = ref(true);

const chartSeries = ref([{
    name: 'Temperatura',
    data: []
}]);

const chartOptions = ref({
    chart: {
        type: 'area',
        fontFamily: 'Inter, sans-serif',
        toolbar: { show: false },
        zoom: { enabled: false }
    },
    colors: ['#3b82f6'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 3 },
    xaxis: {
        categories: [],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: '#64748b', fontWeight: 500 }
        }
    },
    yaxis: {
        labels: {
            formatter: (val) => { return val + "°C" },
            style: { colors: '#64748b', fontWeight: 500 }
        }
    },
    grid: {
        borderColor: '#f1f5f9',
        strokeDashArray: 4,
        yaxis: { lines: { show: true } }
    },
    tooltip: {
        theme: 'dark'
    }
});

const loadDashboard = async () => {
    try {
        const [regResp, alertResp, weatherResp, forecastResp, historyResp] = await Promise.all([
            api.get('/clima/registros'),
            api.get('/clima/alertas'),
            api.get('/clima/weather/current?lat=14.6349&lon=-90.5069'),
            api.get('/clima/weather/forecast?lat=14.6349&lon=-90.5069'),
            api.get('/clima/weather/historial?lat=14.6349&lon=-90.5069')
        ]);

        if (regResp.data.status === 'success') {
            const regs = regResp.data.data;
            totalRegistros.value = regs.length;
            condiciones.value = regs.filter(r => r.categoria === 'condicion').length;
            desastres.value = regs.filter(r => r.categoria === 'desastre').length;
        }

        if (alertResp.data.status === 'success') {
            const alertas = alertResp.data.data;
            alertasActivas.value = alertas.filter(a => a.estado === 'Activa').length;
        }

        if (weatherResp.data.status === 'success') {
            weather.value = weatherResp.data.data;
        }

        if (forecastResp.data.status === 'success' && forecastResp.data.data.pronostico) {
            forecast.value = forecastResp.data.data.pronostico;
        }

        if (historyResp.data.status === 'success' && historyResp.data.data.historial) {
            historial.value = historyResp.data.data.historial;
            // Update chart data
            chartSeries.value = [{
                name: 'Temperatura',
                data: historial.value.map(h => Number(h.temperatura) || 0)
            }];
            chartOptions.value = {
                ...chartOptions.value,
                xaxis: {
                    ...chartOptions.value.xaxis,
                    categories: historial.value.map(h => h.dia)
                }
            };
        }
    } catch (e) {
        console.error(e);
    } finally {
        loadingWeather.value = false;
        loadingForecast.value = false;
        loadingHistory.value = false;
    }
};

onMounted(() => {
    loadDashboard();
});
</script>
