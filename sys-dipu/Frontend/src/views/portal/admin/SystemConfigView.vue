<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="mb-12 flex justify-between items-start">
            <div>
                <nav aria-label="Breadcrumb" class="flex text-[10px] font-bold text-outline uppercase tracking-widest mb-3">
                    <ol class="flex items-center space-x-2">
                        <li><a class="hover:text-primary transition-colors" href="#">Sistema</a></li>
                        <li><span class="material-symbols-outlined text-[12px]">chevron_right</span></li>
                        <li class="text-primary">Configuración</li>
                    </ol>
                </nav>
                <h2 class="text-4xl font-extrabold text-on-surface tracking-tighter leading-tight font-headline">Configuración del Sistema</h2>
                <p class="text-on-surface-variant text-base mt-2 max-w-2xl font-medium opacity-80">Control de acceso institucional y parámetros globales de catálogo.</p>
            </div>
            <div class="flex gap-4">
                <button class="px-8 py-3 bg-primary text-white font-bold rounded-lg flex items-center gap-2 hover:bg-on-surface transition-all shadow-sm">
                    <span class="material-symbols-outlined text-lg">person_add</span>
                    Añadir Usuario
                </button>
            </div>
        </div>

        <!-- Bento Grid -->
        <div class="grid grid-cols-12 gap-8">
            <!-- Users Table -->
            <div class="col-span-12 lg:col-span-8 space-y-8">
                <div class="bg-surface rounded-xl border border-outline-variant/30 overflow-hidden shadow-sm">
                    <div class="px-8 py-5 border-b border-surface-container-high flex justify-between items-center bg-surface-container-lowest">
                        <span class="text-[11px] font-extrabold text-outline uppercase tracking-[0.15em]">Directorio de Usuarios</span>
                        <div class="flex gap-4">
                            <button class="text-outline hover:text-primary transition-colors"><span class="material-symbols-outlined text-xl">tune</span></button>
                            <button class="text-outline hover:text-primary transition-colors"><span class="material-symbols-outlined text-xl">cloud_download</span></button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <div class="w-full overflow-x-auto pb-4">
                <table class="w-full text-left border-collapse min-w-[800px]">

                            <thead>
                                <tr class="bg-surface-container-lowest border-b border-surface-container-high">
                                    <th class="px-8 py-5 text-[10px] font-bold text-outline uppercase tracking-wider">Identidad</th>
                                    <th class="px-8 py-5 text-[10px] font-bold text-outline uppercase tracking-wider">Credencial</th>
                                    <th class="px-8 py-5 text-[10px] font-bold text-outline uppercase tracking-wider">Perfil RBAC</th>
                                    <th class="px-8 py-5 text-[10px] font-bold text-outline uppercase tracking-wider">Estado</th>
                                    <th class="px-8 py-5 text-[10px] font-bold text-outline uppercase tracking-wider"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-container-low">
                                <tr class="hover:bg-surface-container-low transition-colors group">
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-4">
                                            <div class="w-9 h-9 rounded-lg bg-primary-container text-primary flex items-center justify-center font-bold text-xs">MV</div>
                                            <span class="font-bold text-on-surface">Mariana Villanueva</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 text-on-surface-variant text-sm font-medium">m.villanueva@legis.gob</td>
                                    <td class="px-8 py-5"><span class="text-[10px] px-2.5 py-1 bg-surface-container text-on-surface-variant font-extrabold rounded uppercase">Superadmin</span></td>
                                    <td class="px-8 py-5">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-container text-primary text-[10px] font-extrabold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span> ACTIVO
                                        </span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex items-center justify-end gap-3 text-outline-variant group-hover:text-outline opacity-40 group-hover:opacity-100 transition-all">
                                            <span class="material-symbols-outlined text-lg cursor-pointer hover:text-primary">edit_note</span>
                                            <span class="material-symbols-outlined text-lg cursor-pointer hover:text-error">do_not_disturb_on</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="hover:bg-surface-container-low transition-colors group">
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-4">
                                            <div class="w-9 h-9 rounded-lg bg-surface-container text-outline flex items-center justify-center font-bold text-xs">RC</div>
                                            <span class="font-bold text-on-surface">Roberto Castillo</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 text-on-surface-variant text-sm font-medium">r.castillo@legis.gob</td>
                                    <td class="px-8 py-5"><span class="text-[10px] px-2.5 py-1 bg-surface-container text-on-surface-variant font-extrabold rounded uppercase">Diputado</span></td>
                                    <td class="px-8 py-5">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-container text-primary text-[10px] font-extrabold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span> ACTIVO
                                        </span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex items-center justify-end gap-3 text-outline-variant group-hover:text-outline opacity-40 group-hover:opacity-100 transition-all">
                                            <span class="material-symbols-outlined text-lg cursor-pointer hover:text-primary">edit_note</span>
                                            <span class="material-symbols-outlined text-lg cursor-pointer hover:text-error">do_not_disturb_on</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="hover:bg-surface-container-low transition-colors group">
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-4">
                                            <div class="w-9 h-9 rounded-lg bg-surface-container text-outline flex items-center justify-center font-bold text-xs">EL</div>
                                            <span class="font-bold text-on-surface">Elena Ledesma</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 text-on-surface-variant text-sm font-medium">e.ledesma@legis.gob</td>
                                    <td class="px-8 py-5"><span class="text-[10px] px-2.5 py-1 bg-surface-container text-on-surface-variant font-extrabold rounded uppercase">Analista</span></td>
                                    <td class="px-8 py-5">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-error-container text-error text-[10px] font-extrabold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-error"></span> INACTIVO
                                        </span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex items-center justify-end gap-3 text-outline-variant group-hover:text-outline opacity-40 group-hover:opacity-100 transition-all">
                                            <span class="material-symbols-outlined text-lg cursor-pointer hover:text-primary">edit_note</span>
                                            <span class="material-symbols-outlined text-lg cursor-pointer hover:text-secondary">check_circle</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        
                </table>
            </div>
                    </div>
                    <div class="px-8 py-5 border-t border-surface-container-low flex items-center justify-between">
                        <span class="text-xs text-outline font-medium italic">142 registros encontrados</span>
                        <div class="flex gap-2">
                            <button class="w-10 h-10 flex items-center justify-center rounded-lg border border-surface-container-high text-outline hover:bg-surface-container-low transition-colors"><span class="material-symbols-outlined text-lg">chevron_left</span></button>
                            <button class="w-10 h-10 flex items-center justify-center rounded-lg bg-primary text-white text-xs font-bold">1</button>
                            <button class="w-10 h-10 flex items-center justify-center rounded-lg border border-surface-container-high text-xs font-bold hover:bg-surface-container-low">2</button>
                            <button class="w-10 h-10 flex items-center justify-center rounded-lg border border-surface-container-high text-outline hover:bg-surface-container-low transition-colors"><span class="material-symbols-outlined text-lg">chevron_right</span></button>
                        </div>
                    </div>
                </div>

                <!-- Audit Log -->
                <div class="bg-surface-container-lowest rounded-xl p-8 border border-outline-variant/30 relative overflow-hidden shadow-sm">
                    <div class="absolute top-0 right-0 p-8 opacity-5">
                        <span class="material-symbols-outlined text-[120px]">verified_user</span>
                    </div>
                    <div class="flex items-center gap-3 mb-8">
                        <span class="material-symbols-outlined text-primary text-2xl">history_edu</span>
                        <h3 class="text-lg font-extrabold text-on-surface">Bitácora de Auditoría</h3>
                    </div>
                    <div class="space-y-6 relative z-10 border-l border-surface-container-high ml-2.5 pl-8">
                        <div class="relative">
                            <div class="absolute -left-[37px] top-1 w-4 h-4 rounded-full border-4 border-white bg-primary"></div>
                            <p class="text-sm text-on-surface font-bold">Cambio de Rol: Usuario Roberto Castillo</p>
                            <p class="text-xs text-outline mt-1">Ejecutado por Mariana Villanueva • Hace 12 minutos</p>
                        </div>
                        <div class="relative">
                            <div class="absolute -left-[37px] top-1 w-4 h-4 rounded-full border-4 border-white bg-error"></div>
                            <p class="text-sm text-on-surface font-bold">Intento de acceso fallido: IP 192.168.1.45</p>
                            <p class="text-xs text-outline mt-1">Módulo de Seguridad • Hace 45 minutos</p>
                        </div>
                        <div class="relative">
                            <div class="absolute -left-[37px] top-1 w-4 h-4 rounded-full border-4 border-white bg-secondary"></div>
                            <p class="text-sm text-on-surface font-bold">Actualización de Parámetros: Umbral de Alertas</p>
                            <p class="text-xs text-outline mt-1">Configuración Global • Hace 2 horas</p>
                        </div>
                    </div>
                    <button class="mt-10 inline-flex items-center gap-2 text-primary font-extrabold text-xs uppercase tracking-widest hover:opacity-70 transition-all border-b-2 border-primary/20 pb-1">
                        Ver bitácora completa
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </button>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-span-12 lg:col-span-4 space-y-8">
                <!-- RBAC Form -->
                <div class="bg-surface rounded-xl p-8 border border-outline-variant/30 shadow-sm">
                    <h3 class="text-xs font-extrabold text-outline uppercase tracking-[0.2em] mb-8">Nuevo Perfil (RBAC)</h3>
                    <form class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Nombre Completo</label>
                            <input class="w-full border border-surface-container-high bg-surface-container-low rounded-lg px-4 py-3 text-sm focus:bg-surface focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none" placeholder="p. ej. Luis Méndez" type="text" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Email Institucional</label>
                            <input class="w-full border border-surface-container-high bg-surface-container-low rounded-lg px-4 py-3 text-sm focus:bg-surface focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none" placeholder="l.mendez@legis.gob" type="email" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Rol de Acceso</label>
                            <div class="relative">
                                <select class="w-full border border-surface-container-high bg-surface-container-low rounded-lg px-4 py-3 text-sm focus:bg-surface focus:border-primary focus:ring-1 focus:ring-primary transition-all appearance-none outline-none cursor-pointer">
                                    <option>Seleccione un rol...</option>
                                    <option>Superadmin</option>
                                    <option>Diputado</option>
                                    <option>Coordinador</option>
                                    <option>Responsable</option>
                                    <option>Operativo</option>
                                    <option>Consulta</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-3.5 text-outline pointer-events-none">expand_more</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 pt-2">
                            <div class="flex items-center h-5">
                                <input class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary bg-surface-container-low" type="checkbox" />
                            </div>
                            <span class="text-xs text-outline font-medium leading-none">Notificar credenciales de acceso vía email institucional</span>
                        </div>
                        <button class="w-full py-3.5 bg-secondary text-white font-bold rounded-lg hover:bg-on-surface transition-all shadow-sm mt-4">Procesar Registro</button>
                    </form>
                </div>

                <!-- Parameters -->
                <div class="bg-primary text-white rounded-xl p-8 relative overflow-hidden shadow-sm">
                    <div class="absolute -right-8 -top-8 w-40 h-40 bg-white opacity-5 rounded-full blur-2xl"></div>
                    <h3 class="text-[10px] font-extrabold uppercase tracking-[0.25em] mb-8 text-primary-container">Estructura Crítica</h3>
                    <div class="space-y-8 relative z-10">
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-xs font-bold tracking-tight">Umbral Alertas Legislativas</span>
                                <span class="text-[10px] font-black bg-white/10 px-2 py-0.5 rounded">85%</span>
                            </div>
                            <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-white h-full" style="width: 85%"></div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-4 bg-white/5 border border-white/10 rounded-lg group cursor-pointer hover:bg-white/10 transition-all">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-white/70">folder_open</span>
                                    <span class="text-xs font-bold">Catálogos Maestros</span>
                                </div>
                                <span class="material-symbols-outlined text-white/40 group-hover:translate-x-1 transition-transform">chevron_right</span>
                            </div>
                            <div class="flex justify-between items-center p-4 bg-white/5 border border-white/10 rounded-lg group cursor-pointer hover:bg-white/10 transition-all">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-white/70">security</span>
                                    <span class="text-xs font-bold">Seguridad y Cifrado</span>
                                </div>
                                <span class="material-symbols-outlined text-white/40 group-hover:translate-x-1 transition-transform">chevron_right</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Distribution chart -->
                <div class="bg-surface-container-low rounded-xl p-8 border border-outline-variant/30 shadow-sm">
                    <p class="text-[10px] font-extrabold text-outline uppercase tracking-[0.2em] mb-10 text-center">Distribución Institucional</p>
                    <div class="flex items-center justify-center mb-10">
                        <div class="relative w-36 h-36 flex items-center justify-center">
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 160 160">
                                <circle cx="80" cy="80" fill="transparent" r="64" stroke="var(--color-surface-container-high)" stroke-width="12"></circle>
                                <circle cx="80" cy="80" fill="transparent" r="64" stroke="var(--color-primary)" stroke-dasharray="258 402" stroke-linecap="round" stroke-width="12"></circle>
                                <circle cx="80" cy="80" fill="transparent" r="64" stroke="var(--color-secondary)" stroke-dasharray="80 402" stroke-dashoffset="-258" stroke-linecap="round" stroke-width="12"></circle>
                            </svg>
                            <div class="absolute flex flex-col items-center">
                                <span class="text-3xl font-extrabold text-on-surface tracking-tighter">142</span>
                                <span class="text-[9px] uppercase tracking-widest font-bold opacity-50">Total</span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center gap-3 px-3 py-2 bg-surface rounded-lg border border-surface-container-high">
                            <span class="w-2 h-2 rounded-full bg-primary"></span>
                            <span class="text-[10px] font-bold text-on-surface">Admin (12%)</span>
                        </div>
                        <div class="flex items-center gap-3 px-3 py-2 bg-surface rounded-lg border border-surface-container-high">
                            <span class="w-2 h-2 rounded-full bg-secondary"></span>
                            <span class="text-[10px] font-bold text-on-surface">Diput. (48%)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup></script>
