<template>
    <div>
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
            <div>
                <h2 class="font-headline-lg text-primary mb-2 tracking-wide font-medium">Dashboard Ejecutivo</h2>
                <p class="font-body-sm text-on-surface-variant">Resumen consolidado de operaciones y rendimiento financiero.</p>
            </div>
            <div class="flex items-center gap-1 bg-surface-dim border border-outline-variant/50 rounded-xl p-1 shadow-inner">
                <button class="px-5 py-2 rounded-lg bg-transparent border border-primary text-primary font-body-sm shadow-[0_0_8px_rgba(233,193,118,0.15)] font-medium">Hoy</button>
                <button class="px-5 py-2 rounded-lg text-on-surface-variant hover:text-on-surface font-body-sm transition-colors">Esta Semana</button>
                <button class="px-5 py-2 rounded-lg text-on-surface-variant hover:text-on-surface font-body-sm transition-colors">Este Mes</button>
            </div>
        </div>

        <!-- KPIs Grid (Bento Style) -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Card 1: Capital Colocado -->
            <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-primary/30 transition-all shadow-[0_8px_32px_rgba(233,193,118,0.05)]">
                <!-- Glow effect in background -->
                <div class="absolute inset-0 bg-gradient-to-br from-primary/20 via-transparent to-transparent opacity-60"></div>
                <div class="flex justify-between items-start mb-4 relative z-10">
                    <h3 class="font-body-sm text-on-surface-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-lg">trending_up</span>
                        Capital Colocado
                    </h3>
                    <button class="text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined text-lg">more_vert</span></button>
                </div>
                <p class="font-headline-lg text-on-surface mb-1 relative z-10">Q12,450,000</p>
                <div class="flex items-center justify-between relative z-10 mt-6">
                    <div class="flex items-center gap-1 text-primary font-body-sm">
                        <span class="material-symbols-outlined text-[16px]">arrow_upward</span>
                        <span>4.2% vs mes anterior</span>
                    </div>
                    <!-- Mini chart representation -->
                    <svg width="60" height="24" viewBox="0 0 60 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 20C10 20 15 5 25 10C35 15 45 5 59 5" stroke="#e9c176" stroke-width="2" stroke-linecap="round"/>
                        <path d="M1 20C10 20 15 5 25 10C35 15 45 5 59 5" stroke="#e9c176" stroke-width="4" opacity="0.3" stroke-linecap="round" filter="url(#glow1)"/>
                        <defs>
                            <filter id="glow1" x="-4" y="-4" width="68" height="32" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                <feGaussianBlur stdDeviation="2" result="effect1_foregroundBlur"/>
                            </filter>
                        </defs>
                    </svg>
                </div>
            </div>

            <!-- Card 2: Capital Disponible -->
            <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-[#8a4fff]/30 transition-all shadow-[0_8px_32px_rgba(138,79,255,0.05)]">
                <div class="absolute inset-0 bg-gradient-to-br from-[#8a4fff]/20 via-transparent to-transparent opacity-60"></div>
                <div class="flex justify-between items-start mb-4 relative z-10">
                    <h3 class="font-body-sm text-on-surface-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#8a4fff] text-lg">account_balance_wallet</span>
                        Capital Disponible
                    </h3>
                    <button class="text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined text-lg">more_vert</span></button>
                </div>
                <p class="font-headline-lg text-on-surface mb-1 relative z-10">Q3,200,000</p>
                <div class="flex items-center justify-between relative z-10 mt-6">
                    <div class="text-on-surface-variant font-body-sm">
                        <span>20.4% de liquidez</span>
                    </div>
                    <!-- Mini chart representation -->
                    <svg width="60" height="24" viewBox="0 0 60 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 20C15 20 20 15 30 18C40 21 45 5 59 5" stroke="#8a4fff" stroke-width="2" stroke-linecap="round"/>
                        <path d="M1 20C15 20 20 15 30 18C40 21 45 5 59 5" stroke="#8a4fff" stroke-width="4" opacity="0.3" stroke-linecap="round" filter="url(#glow2)"/>
                        <defs>
                            <filter id="glow2" x="-4" y="-4" width="68" height="32" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                <feGaussianBlur stdDeviation="2" result="effect1_foregroundBlur"/>
                            </filter>
                        </defs>
                    </svg>
                </div>
            </div>

            <!-- Card 3: Ingresos por Intereses -->
            <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-[#3b82f6]/30 transition-all lg:col-span-2 shadow-[0_8px_32px_rgba(59,130,246,0.05)]">
                <div class="absolute inset-0 bg-gradient-to-bl from-[#3b82f6]/20 via-transparent to-transparent opacity-50"></div>
                <div class="flex flex-col sm:flex-row justify-between h-full relative z-10">
                    <div class="flex flex-col justify-between">
                        <h3 class="font-body-sm text-on-surface-variant flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-[#3b82f6] text-lg">monetization_on</span>
                            Ingresos por Intereses (YTD)
                        </h3>
                        <div>
                            <p class="font-headline-lg text-on-surface mb-1">Q1,840,500</p>
                            <div class="flex items-center gap-1 text-primary font-body-sm mt-4">
                                <span class="material-symbols-outlined text-[16px]">arrow_upward</span>
                                <span>12.5% anual</span>
                            </div>
                        </div>
                    </div>
                    <div class="w-full sm:w-1/2 h-20 flex items-end justify-end gap-2 opacity-90 mt-4 sm:mt-0">
                        <!-- Simple CSS Bar Chart -->
                        <div class="w-4 bg-[#2563eb] h-[20%] rounded-t-sm"></div>
                        <div class="w-4 bg-[#2563eb] h-[35%] rounded-t-sm"></div>
                        <div class="w-4 bg-[#2563eb] h-[25%] rounded-t-sm"></div>
                        <div class="w-4 bg-[#2563eb] h-[40%] rounded-t-sm"></div>
                        <div class="w-4 bg-[#2563eb] h-[60%] rounded-t-sm"></div>
                        <div class="w-4 bg-[#2563eb] h-[55%] rounded-t-sm"></div>
                        <div class="w-4 bg-[#2563eb] h-[80%] rounded-t-sm shadow-[0_0_8px_rgba(59,130,246,0.6)]"></div>
                        <div class="w-4 bg-[#3b82f6] h-[100%] rounded-t-sm shadow-[0_0_8px_rgba(59,130,246,0.8)]"></div>
                    </div>
                </div>
            </div>

            <!-- Single Consolidated Row for KPIs -->
            <div class="bg-surface-container-high/30 backdrop-blur-xl py-5 px-6 rounded-2xl border border-white/5 lg:col-span-4 flex flex-wrap lg:flex-nowrap justify-between items-center gap-4 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                
                <div class="flex flex-col items-center flex-1 min-w-[100px]">
                    <div class="flex items-center gap-2 text-on-surface-variant mb-1">
                        <span class="material-symbols-outlined text-primary text-[16px]">description</span>
                        <span class="font-body-sm text-xs">Préstamos Activos</span>
                    </div>
                    <p class="font-title-md text-on-surface">342</p>
                </div>

                <div class="h-8 w-px bg-outline-variant/50 hidden lg:block"></div>

                <div class="flex flex-col items-center flex-1 min-w-[100px]">
                    <div class="flex items-center gap-2 text-on-surface-variant mb-1">
                        <span class="material-symbols-outlined text-primary text-[16px]">check_circle</span>
                        <span class="font-body-sm text-xs">Finalizados</span>
                    </div>
                    <p class="font-title-md text-on-surface">1,204</p>
                </div>

                <div class="h-8 w-px bg-outline-variant/50 hidden lg:block"></div>

                <div class="flex flex-col items-center flex-1 min-w-[100px]">
                    <div class="flex items-center gap-2 text-on-surface-variant mb-1">
                        <span class="material-symbols-outlined text-primary text-[16px]">person</span>
                        <span class="font-body-sm text-xs">Clientes</span>
                    </div>
                    <p class="font-title-md text-on-surface">856</p>
                </div>

                <div class="h-8 w-px bg-outline-variant/50 hidden lg:block"></div>

                <div class="flex flex-col items-center flex-1 min-w-[100px]">
                    <div class="flex items-center gap-2 text-on-surface-variant mb-1">
                        <span class="material-symbols-outlined text-primary text-[16px]">groups</span>
                        <span class="font-body-sm text-xs">Inversionistas</span>
                    </div>
                    <p class="font-title-md text-on-surface">45</p>
                </div>

                <div class="h-8 w-px bg-outline-variant/50 hidden lg:block"></div>

                <div class="flex flex-col items-center flex-1 min-w-[100px]">
                    <div class="flex items-center gap-2 text-on-surface-variant mb-1">
                        <span class="material-symbols-outlined text-error text-[16px]">percent</span>
                        <span class="font-body-sm text-xs">Morosidad Total</span>
                    </div>
                    <p class="font-title-md text-error">4.8%</p>
                </div>

                <div class="h-8 w-px bg-outline-variant/50 hidden lg:block"></div>

                <div class="flex flex-col items-center flex-1 min-w-[100px]">
                    <div class="flex items-center gap-2 text-on-surface-variant mb-1">
                        <span class="material-symbols-outlined text-error text-[16px]">calendar_today</span>
                        <span class="font-body-sm text-xs">P. Vencidos</span>
                    </div>
                    <p class="font-title-md text-on-surface">18</p>
                </div>

                <div class="h-8 w-px bg-outline-variant/50 hidden lg:block"></div>

                <div class="flex flex-col items-center flex-1 min-w-[100px]">
                    <div class="flex items-center gap-2 text-on-surface-variant mb-1">
                        <span class="material-symbols-outlined text-primary text-[16px]">payments</span>
                        <span class="font-body-sm text-xs">Cobros (Hoy)</span>
                    </div>
                    <p class="font-title-md text-on-surface">Q45,000</p>
                </div>

                <div class="h-8 w-px bg-outline-variant/50 hidden lg:block"></div>

                <div class="flex flex-col items-center flex-1 min-w-[100px]">
                    <div class="flex items-center gap-2 text-on-surface-variant mb-1">
                        <span class="material-symbols-outlined text-primary text-[16px]">show_chart</span>
                        <span class="font-body-sm text-xs">Semana</span>
                    </div>
                    <p class="font-title-md text-on-surface">Q210,000</p>
                </div>
            </div>
        </section>

        <!-- Charts Section -->
        <section>
            <h2 class="font-headline-lg text-primary mb-6">Análisis Visual</h2>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Bar Chart -->
                <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl border border-white/5 lg:col-span-2 flex flex-col h-96 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                    <div class="absolute inset-0 bg-gradient-to-t from-primary/5 to-transparent opacity-30 pointer-events-none rounded-2xl"></div>
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-body-sm text-on-surface font-medium">Préstamos Otorgados vs Intereses</h3>
                        <div class="flex items-center gap-4 text-xs">
                            <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-primary"></span><span class="text-on-surface-variant">Capital Otorgado (M)</span></div>
                            <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-[#8a4fff]"></span><span class="text-on-surface-variant">Intereses Generados (k)</span></div>
                            <button class="text-on-surface-variant hover:text-primary ml-2"><span class="material-symbols-outlined text-lg">more_vert</span></button>
                        </div>
                    </div>
                    <div class="flex-1 relative w-full">
                        <canvas id="loansChart"></canvas>
                    </div>
                </div>

                <!-- Side Donut Chart -->
                <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl border border-white/5 flex flex-col h-96 shadow-[0_8px_32px_rgba(0,0,0,0.2)] relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-[#8a4fff]/5 opacity-30 pointer-events-none rounded-2xl"></div>
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-body-sm text-on-surface font-medium">Salud de Cartera</h3>
                        <button class="text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined text-lg">more_vert</span></button>
                    </div>
                    <div class="flex-1 relative w-full flex items-center justify-center">
                        <canvas id="healthChart"></canvas>
                        <!-- Center text for donut -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="font-headline-lg text-on-surface">95.2%</span>
                            <span class="font-body-sm text-on-surface-variant">Al Corriente</span>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-full bg-primary"></div>
                            <span class="font-body-sm text-xs text-on-surface-variant">Sana</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-full bg-[#8a4fff]"></div>
                            <span class="font-body-sm text-xs text-on-surface-variant">Mora</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-full bg-error"></div>
                            <span class="font-body-sm text-xs text-on-surface-variant">Vencida</span>
                        </div>
                    </div>
                </div>

                <!-- Line Chart (Rentabilidad) -->
                <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl border border-white/5 lg:col-span-3 flex flex-col h-80 shadow-[0_8px_32px_rgba(0,0,0,0.2)] relative">
                    <div class="absolute inset-0 bg-gradient-to-t from-primary/5 to-transparent opacity-30 pointer-events-none rounded-2xl"></div>
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-body-sm text-on-surface font-medium flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-lg">workspace_premium</span>
                            Rentabilidad vs Capital Invertido (Top Socios)
                        </h3>
                        <div class="flex items-center gap-4">
                            <button class="text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined text-sm">chevron_left</span></button>
                            <span class="px-3 py-1 bg-primary text-on-primary font-medium text-xs rounded-lg">01</span>
                            <span class="px-3 py-1 text-on-surface-variant text-xs">02</span>
                            <button class="text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined text-sm">chevron_right</span></button>
                        </div>
                    </div>
                    <div class="flex-1 relative w-full">
                        <canvas id="profitChart"></canvas>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
    if(window.Chart) {
        // Common Chart.js Defaults for Dark Theme
        Chart.defaults.color = '#d1c5b4'; // on-surface-variant
        Chart.defaults.font.family = 'Hanken Grotesk';
        Chart.defaults.scale.grid.color = '#353535'; // surface-variant
        Chart.defaults.plugins.tooltip.backgroundColor = '#252525';
        Chart.defaults.plugins.tooltip.titleColor = '#e9c176'; // primary
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;

        // Colors from Tailwind config
        const colors = {
            primary: '#e9c176',
            primaryContainer: '#c5a059',
            tertiary: '#b0c6f9',
            error: '#ffb4ab',
            surfaceContainer: '#20201f'
        };

        // Neon Glow Plugin for Chart.js
        const neonPlugin = {
            id: 'neon',
            beforeDatasetDraw(chart, args) {
                const dataset = chart.data.datasets[args.index];
                if (dataset.neon) {
                    chart.ctx.save();
                    chart.ctx.shadowColor = dataset.neon;
                    chart.ctx.shadowBlur = 15;
                    chart.ctx.shadowOffsetX = 0;
                    chart.ctx.shadowOffsetY = 0;
                }
            },
            afterDatasetDraw(chart, args) {
                const dataset = chart.data.datasets[args.index];
                if (dataset.neon) {
                    chart.ctx.restore();
                }
            }
        };
        Chart.register(neonPlugin);

        // 1. Loans vs Interests Bar Chart
        const ctxLoans = document.getElementById('loansChart').getContext('2d');
        new Chart(ctxLoans, {
            type: 'bar',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul'],
                datasets: [
                    {
                        label: 'Capital Otorgado (M)',
                        data: [4.2, 3.8, 5.1, 4.8, 6.2, 5.9, 7.1],
                        backgroundColor: colors.primary, // Gold bars matching the screenshot
                        borderColor: colors.primaryContainer,
                        borderWidth: 0,
                        borderRadius: 4,
                        barPercentage: 0.6,
                        neon: 'rgba(233, 193, 118, 0.4)' // Soft gold neon for bars
                    },
                    {
                        label: 'Intereses Generados (k)',
                        data: [420, 380, 510, 480, 620, 590, 710],
                        type: 'line',
                        borderColor: '#8a4fff', // Purple line matching the screenshot
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        pointBackgroundColor: '#8a4fff',
                        pointBorderColor: '#8a4fff',
                        pointRadius: 4,
                        tension: 0.4,
                        yAxisID: 'y1',
                        neon: 'rgba(138, 79, 255, 0.8)' // Strong purple neon for line
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false } }, // Hiding legend as we built custom one
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, border: { dash: [4, 4] } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { display: false } }
                }
            }
        });

        // 2. Health Donut Chart
        const ctxHealth = document.getElementById('healthChart').getContext('2d');
        new Chart(ctxHealth, {
            type: 'doughnut',
            data: {
                labels: ['Cartera Sana', 'Morosidad < 30d', 'Morosidad > 30d'],
                datasets: [{
                    data: [95.2, 3.1, 1.7],
                    backgroundColor: [colors.primary, '#8a4fff', colors.error],
                    borderWidth: 0,
                    hoverOffset: 4,
                    neon: 'rgba(233, 193, 118, 0.2)' // Gentle overarching glow
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function(context) { return context.label + ': ' + context.parsed + '%'; } } }
                }
            }
        });

        // 3. Profitability Line Chart
        const ctxProfit = document.getElementById('profitChart').getContext('2d');
        
        // Create gradient for line chart
        const gradient = ctxProfit.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(233, 193, 118, 0.2)'); // primary with opacity
        gradient.addColorStop(1, 'rgba(233, 193, 118, 0)');

        new Chart(ctxProfit, {
            type: 'line',
            data: {
                labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4', 'Sem 5', 'Sem 6', 'Sem 7', 'Sem 8'],
                datasets: [{
                    label: 'Rentabilidad Promedio (%)',
                    data: [12.4, 12.6, 12.5, 13.1, 13.4, 13.2, 14.0, 14.5],
                    borderColor: colors.primary,
                    backgroundColor: gradient,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: colors.surfaceContainer,
                    pointBorderColor: colors.primary,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    neon: 'rgba(233, 193, 118, 0.8)' // Strong gold neon for profit line
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: false, min: 10, border: { dash: [4, 4] } }
                }
            }
        });
    }
});
</script>
