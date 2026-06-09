<template>
  <div>
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2.5 py-1 rounded bg-secondary-container text-on-secondary-container font-label-caps text-label-caps border border-primary/30">ACTIVO</span>
                <span class="text-on-surface-variant font-body-sm text-body-sm">Ref: LN-2023-8942</span>
            </div>
            <h2 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface">Gestión de Préstamo</h2>
        </div>
        <div class="flex gap-3">
            <button class="px-4 py-2 rounded border border-outline-variant text-on-surface hover:text-primary hover:border-primary transition-colors font-title-md text-title-md text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">edit</span> Editar
            </button>
            <button class="px-4 py-2 rounded bg-primary text-on-primary hover:bg-primary-fixed-dim transition-colors font-title-md text-title-md text-sm flex items-center gap-2 shadow-[0_0_15px_rgba(197,160,89,0.2)]">
                <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">save</span> Guardar
            </button>
        </div>
    </div>

    <!-- Bento Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column: Configuration -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Config Card -->
            <div class="glass-panel p-6 rounded-xl flex flex-col gap-5 relative overflow-hidden group border border-surface-variant">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary-container via-primary to-transparent opacity-70"></div>
                <h3 class="font-title-md text-title-md text-on-surface border-b border-outline-variant pb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[20px]">tune</span> Configuración
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block font-label-caps text-label-caps text-on-surface-variant mb-1.5">CLIENTE</label>
                        <div class="relative">
                            <select class="w-full bg-surface-container border border-outline-variant rounded p-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary appearance-none font-body-lg text-body-lg text-sm">
                                <option>Inversiones Capital Sur S.A.</option>
                                <option>Desarrollos Inmobiliarios Zeta</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">expand_more</span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-label-caps text-label-caps text-on-surface-variant mb-1.5">MONTO PRINCIPAL</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">Q</span>
                                <input class="w-full bg-surface-container border border-outline-variant rounded py-2.5 pl-8 pr-3 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary font-body-lg text-body-lg text-sm text-right" type="text" value="250,000.00"/>
                            </div>
                        </div>
                        <div>
                            <label class="block font-label-caps text-label-caps text-on-surface-variant mb-1.5">TASA (%)</label>
                            <div class="relative">
                                <input class="w-full bg-surface-container border border-outline-variant rounded p-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary font-body-lg text-body-lg text-sm text-right pr-8" type="text" value="12.5"/>
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-label-caps text-label-caps text-on-surface-variant mb-1.5">TIPO INTERÉS</label>
                            <select class="w-full bg-surface-container border border-outline-variant rounded p-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary font-body-lg text-body-lg text-sm">
                                <option>Compuesto</option>
                                <option>Simple</option>
                                <option>Fijo</option>
                                <option>Decreciente</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-label-caps text-label-caps text-on-surface-variant mb-1.5">PLAZO</label>
                            <select class="w-full bg-surface-container border border-outline-variant rounded p-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary font-body-lg text-body-lg text-sm">
                                <option>Mensual (24)</option>
                                <option>Quincenal (48)</option>
                                <option>Semanal (96)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block font-label-caps text-label-caps text-on-surface-variant mb-1.5">FECHA DESEMBOLSO</label>
                        <div class="relative">
                            <input class="w-full bg-surface-container border border-outline-variant rounded p-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary font-body-lg text-body-lg text-sm [color-scheme:dark]" type="date" value="2023-10-15"/>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Summary Mini-card -->
            <div class="bg-surface-container-high p-5 rounded-xl border border-outline-variant flex items-start gap-4">
                <div class="w-12 h-12 rounded bg-surface-variant flex items-center justify-center border border-outline shrink-0">
                    <span class="material-symbols-outlined text-on-surface-variant" style="font-variation-settings: 'FILL' 1;">corporate_fare</span>
                </div>
                <div>
                    <h4 class="font-title-md text-title-md text-sm text-on-surface">Inversiones Capital Sur</h4>
                    <p class="font-body-sm text-body-sm text-on-surface-variant text-xs mt-1">Riesgo: Nivel A (Bajo)</p>
                    <a class="text-primary font-label-caps text-label-caps text-[10px] mt-2 inline-block hover:underline" href="#">VER PERFIL COMPLETO</a>
                </div>
            </div>
        </div>

        <!-- Right Column: Amortization & Summaries -->
        <div class="lg:col-span-8 flex flex-col gap-6">
            <!-- Summary Cards Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-surface-container p-4 rounded-xl border border-outline-variant relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-16 h-16 bg-primary/5 rounded-bl-full pointer-events-none"></div>
                    <p class="font-label-caps text-label-caps text-on-surface-variant mb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[14px]">trending_up</span> TOTAL INTERESES
                    </p>
                    <p class="font-display-lg text-display-lg text-2xl lg:text-xl xl:text-2xl text-primary whitespace-nowrap">Q 31,250.00</p>
                </div>
                
                <div class="bg-surface-container p-4 rounded-xl border border-outline-variant relative overflow-hidden">
                    <p class="font-label-caps text-label-caps text-on-surface-variant mb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[14px]">health_and_safety</span> TOTAL SEGURO
                    </p>
                    <p class="font-display-lg text-display-lg text-2xl lg:text-xl xl:text-2xl text-on-surface whitespace-nowrap">Q 2,400.00</p>
                </div>
                
                <div class="bg-surface-container p-4 rounded-xl border border-outline-variant relative overflow-hidden shadow-[inset_0_1px_0_rgba(197,160,89,0.2)]">
                    <p class="font-label-caps text-label-caps text-primary-fixed-dim mb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[14px]">account_balance</span> COSTO TOTAL
                    </p>
                    <p class="font-display-lg text-display-lg text-2xl lg:text-xl xl:text-2xl text-primary-fixed-dim whitespace-nowrap">Q 283,650.00</p>
                </div>
            </div>

            <!-- Amortization Table Panel -->
            <div class="glass-panel border border-surface-variant flex-1 rounded-xl flex flex-col overflow-hidden">
                <!-- Table Header Actions -->
                <div class="p-4 border-b border-outline-variant flex flex-col sm:flex-row justify-between items-center gap-4 bg-surface-container/50">
                    <h3 class="font-title-md text-title-md text-on-surface">Tabla de Amortización</h3>
                    <div class="flex gap-2">
                        <button class="p-2 rounded bg-surface border border-outline-variant text-on-surface-variant hover:text-primary hover:border-primary transition-colors group" title="Recalcular">
                            <span class="material-symbols-outlined text-[20px] group-active:rotate-180 transition-transform duration-300">calculate</span>
                        </button>
                        <button class="p-2 rounded bg-surface border border-outline-variant text-on-surface-variant hover:text-primary hover:border-primary transition-colors" title="Imprimir PDF">
                            <span class="material-symbols-outlined text-[20px]">picture_as_pdf</span>
                        </button>
                        <button class="p-2 rounded bg-surface border border-outline-variant text-on-surface-variant hover:text-primary hover:border-primary transition-colors" title="Exportar Excel">
                            <span class="material-symbols-outlined text-[20px]">table_view</span>
                        </button>
                    </div>
                </div>

                <!-- Data Table Container -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="bg-surface-container-highest border-b border-outline-variant">
                                <th class="py-3 px-4 font-label-caps text-label-caps text-on-surface-variant font-normal">N°</th>
                                <th class="py-3 px-4 font-label-caps text-label-caps text-on-surface-variant font-normal">FECHA</th>
                                <th class="py-3 px-4 font-label-caps text-label-caps text-on-surface-variant font-normal text-right">CAPITAL</th>
                                <th class="py-3 px-4 font-label-caps text-label-caps text-on-surface-variant font-normal text-right">INTERÉS</th>
                                <th class="py-3 px-4 font-label-caps text-label-caps text-on-surface-variant font-normal text-right">SEGURO</th>
                                <th class="py-3 px-4 font-label-caps text-label-caps text-on-surface-variant font-normal text-right">MORA</th>
                                <th class="py-3 px-4 font-label-caps text-label-caps text-primary-fixed-dim font-normal text-right">TOTAL CUOTA</th>
                                <th class="py-3 px-4 font-label-caps text-label-caps text-on-surface-variant font-normal text-right">SALDO PEND.</th>
                            </tr>
                        </thead>
                        <tbody class="font-body-sm text-body-sm text-on-surface divide-y divide-outline-variant/30">
                            <!-- Row 1 (Paid) -->
                            <tr class="hover:bg-surface-container-high transition-colors bg-success-container/5">
                                <td class="py-3 px-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[14px] text-primary" style="font-variation-settings: 'FILL' 1;">check_circle</span> 1
                                </td>
                                <td class="py-3 px-4 text-on-surface-variant">15/11/2023</td>
                                <td class="py-3 px-4 text-right">10,416.67</td>
                                <td class="py-3 px-4 text-right">2,604.17</td>
                                <td class="py-3 px-4 text-right">100.00</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">0.00</td>
                                <td class="py-3 px-4 text-right font-title-md text-title-md text-[14px] text-primary">13,120.84</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">239,583.33</td>
                            </tr>
                            
                            <!-- Row 2 (Paid) -->
                            <tr class="hover:bg-surface-container-high transition-colors bg-success-container/5">
                                <td class="py-3 px-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[14px] text-primary" style="font-variation-settings: 'FILL' 1;">check_circle</span> 2
                                </td>
                                <td class="py-3 px-4 text-on-surface-variant">15/12/2023</td>
                                <td class="py-3 px-4 text-right">10,416.67</td>
                                <td class="py-3 px-4 text-right">2,495.66</td>
                                <td class="py-3 px-4 text-right">100.00</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">0.00</td>
                                <td class="py-3 px-4 text-right font-title-md text-title-md text-[14px] text-primary">13,012.33</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">229,166.66</td>
                            </tr>

                            <!-- Row 3 (Current) -->
                            <tr class="hover:bg-surface-container-high transition-colors bg-secondary-container/10 border-l-2 border-l-primary relative">
                                <td class="py-3 px-4">3</td>
                                <td class="py-3 px-4 font-bold text-on-surface">15/01/2024</td>
                                <td class="py-3 px-4 text-right">10,416.67</td>
                                <td class="py-3 px-4 text-right">2,387.15</td>
                                <td class="py-3 px-4 text-right">100.00</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">0.00</td>
                                <td class="py-3 px-4 text-right font-title-md text-title-md text-[14px] text-primary">12,903.82</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">218,749.99</td>
                            </tr>
                            
                            <!-- Row 4 -->
                            <tr class="hover:bg-surface-container-high transition-colors">
                                <td class="py-3 px-4 text-on-surface-variant">4</td>
                                <td class="py-3 px-4 text-on-surface-variant">15/02/2024</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">10,416.67</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">2,278.64</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">100.00</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">0.00</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">12,795.31</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">208,333.32</td>
                            </tr>
                            
                            <!-- Row 5 -->
                            <tr class="hover:bg-surface-container-high transition-colors">
                                <td class="py-3 px-4 text-on-surface-variant">5</td>
                                <td class="py-3 px-4 text-on-surface-variant">15/03/2024</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">10,416.67</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">2,170.13</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">100.00</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">0.00</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">12,686.80</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">197,916.65</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer Pagination -->
                <div class="mt-auto p-4 border-t border-outline-variant flex justify-between items-center text-sm text-on-surface-variant bg-surface-container/30">
                    <span>Mostrando 1 a 5 de 24 cuotas</span>
                    <div class="flex gap-1">
                        <button class="w-8 h-8 rounded border border-outline-variant flex items-center justify-center hover:text-primary hover:border-primary disabled:opacity-50" disabled="">
                            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                        </button>
                        <button class="w-8 h-8 rounded border border-outline-variant flex items-center justify-center hover:text-primary hover:border-primary">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.glass-panel {
    background: linear-gradient(145deg, rgba(32, 32, 31, 0.7) 0%, rgba(28, 27, 27, 0.9) 100%);
    backdrop-filter: blur(10px);
}
</style>
