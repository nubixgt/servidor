<template>
  <div class="min-h-screen bg-background text-on-background font-body-md flex flex-col md:flex-row">
    <!-- Sidebar -->
    <aside class="w-full md:w-64 md:h-screen md:sticky md:top-0 bg-surface-container-lowest border-b md:border-b-0 md:border-r border-white/10 flex flex-col shrink-0 z-20">
      <div class="p-gutter border-b border-white/10">
        <div class="text-headline-lg-mobile font-headline-lg-mobile text-primary-fixed tracking-tighter uppercase leading-none">DEPORTES</div>
        <p class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-widest mt-1">Panel de Administración</p>
      </div>

      <nav class="flex-grow p-gutter space-y-2">
        <button @click="equipoSeleccionado = null" :class="['w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 text-label-sm font-label-sm', !equipoSeleccionado ? 'bg-primary-container/10 text-primary-fixed ring-1 ring-primary-fixed/40' : 'text-on-surface-variant hover:bg-surface-variant/50 hover:text-on-surface']">
          <span class="material-symbols-outlined">dashboard</span>
          Directorio Equipos
        </button>
        <router-link to="/registrar-partido" class="w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-variant/50 hover:text-on-surface">
          <span class="material-symbols-outlined">sports_soccer</span>
          Registrar Partido
        </router-link>
        <router-link to="/historial-partidos" class="w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-variant/50 hover:text-on-surface">
          <span class="material-symbols-outlined">history</span>
          Historial Partidos
        </router-link>
      </nav>

      <div class="p-gutter border-t border-white/10">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-surface-container-high flex items-center justify-center">
              <span class="material-symbols-outlined text-on-surface text-[20px]">shield_person</span>
            </div>
            <div>
              <p class="text-label-sm font-label-sm text-on-surface">Administrador</p>
              <p class="text-[10px] text-on-surface-variant uppercase tracking-widest">Superusuario</p>
            </div>
          </div>
          <button @click="logout" class="text-on-surface-variant hover:text-error transition-colors" title="Cerrar sesión">
            <span class="material-symbols-outlined">logout</span>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-container-margin md:p-stack-lg max-w-7xl mx-auto w-full">
      <div v-if="isLoading" class="text-center py-24 text-on-surface-variant">Cargando información...</div>

      <div v-else-if="error" class="bg-error/10 border border-error/40 p-4 rounded-lg text-error mb-stack-md">
        {{ error }}
      </div>

      <div v-else>
        <!-- Vista Directorio de Equipos -->
        <div v-if="!equipoSeleccionado">
          <header class="mb-stack-lg">
            <h1 class="text-headline-lg-mobile md:text-display-lg font-display-lg text-primary-fixed tracking-tighter uppercase mb-2">Overview</h1>
            <p class="text-body-md font-body-md text-on-surface-variant">Directorio y estadísticas en tiempo real.</p>
          </header>

          <!-- Stats Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter mb-stack-lg">
            <div class="glass-card rounded-xl p-stack-md flex flex-col justify-between">
              <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center">
                  <span class="material-symbols-outlined text-on-surface">directions_run</span>
                </div>
              </div>
              <div>
                <div class="text-headline-lg font-headline-lg text-on-surface mb-1">{{ animacionStats.total_jugadores }}</div>
                <div class="text-label-sm font-label-sm text-on-surface-variant uppercase">Jugadores Activos</div>
              </div>
            </div>

            <div class="glass-card rounded-xl p-stack-md flex flex-col justify-between">
              <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center">
                  <span class="material-symbols-outlined text-on-surface">shield</span>
                </div>
              </div>
              <div>
                <div class="text-headline-lg font-headline-lg text-on-surface mb-1">{{ animacionStats.total_equipos }}</div>
                <div class="text-label-sm font-label-sm text-on-surface-variant uppercase">Total Equipos</div>
              </div>
            </div>

            <div class="glass-card rounded-xl p-stack-md flex flex-col justify-between">
              <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center">
                  <span class="material-symbols-outlined text-on-surface">sports</span>
                </div>
              </div>
              <div>
                <div class="text-headline-lg font-headline-lg text-on-surface mb-1">{{ animacionStats.total_partidos }}</div>
                <div class="text-label-sm font-label-sm text-on-surface-variant uppercase">Partidos Registrados</div>
              </div>
            </div>

            <div
              @click="estadisticasGenerales.equipos_incompletos_count > 0 ? (showModalIncompletos = true) : null"
              :class="['glass-card-featured rounded-xl p-stack-md flex flex-col justify-between transition-colors', estadisticasGenerales.equipos_incompletos_count > 0 ? 'border-error/30 ring-1 ring-error/20 cursor-pointer' : '']"
            >
              <div class="flex justify-between items-start mb-4">
                <div :class="['w-10 h-10 rounded-full flex items-center justify-center', estadisticasGenerales.equipos_incompletos_count > 0 ? 'bg-error/20' : 'bg-surface-container-high']">
                  <span :class="['material-symbols-outlined', estadisticasGenerales.equipos_incompletos_count > 0 ? 'text-error' : 'text-on-surface']">warning</span>
                </div>
              </div>
              <div>
                <div :class="['text-headline-lg font-headline-lg mb-1', estadisticasGenerales.equipos_incompletos_count > 0 ? 'text-error' : 'text-on-surface-variant']">{{ animacionStats.equipos_incompletos }}</div>
                <div class="text-label-sm font-label-sm text-on-surface-variant uppercase">Equipos &lt; 10 Jugadores</div>
                <p v-if="estadisticasGenerales.equipos_incompletos_count > 0" class="text-[10px] text-error mt-2 font-bold uppercase">Ver detalles &rarr;</p>
              </div>
            </div>
          </div>

          <!-- Lista de Encargados -->
          <div class="mb-stack-lg">
            <header class="mb-stack-md">
              <p class="text-primary-fixed text-label-sm font-label-sm tracking-widest uppercase flex items-center gap-2 mb-1">
                <span class="w-6 h-px bg-primary-fixed"></span> Contactos
              </p>
              <h2 class="text-title-md font-title-md text-on-surface">Lista de Encargados</h2>
            </header>
            <div class="glass-card rounded-xl overflow-hidden">
              <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                  <thead>
                    <tr class="border-b border-white/10 text-label-sm font-label-sm text-on-surface-variant bg-surface-container-lowest">
                      <th class="p-4 font-normal">Representante</th>
                      <th class="p-4 font-normal">Equipo</th>
                      <th class="p-4 font-normal">Teléfono</th>
                      <th class="p-4 font-normal text-center">Jugadores</th>
                    </tr>
                  </thead>
                  <tbody class="text-body-md font-body-md">
                    <tr v-if="encargados.length === 0">
                      <td colspan="4" class="p-8 text-center text-on-surface-variant text-sm">No hay encargados registrados.</td>
                    </tr>
                    <tr v-for="enc in encargados" :key="enc.equipo_id" @click="verDetalleEncargado(enc)" class="border-b border-white/5 hover:bg-white/5 transition-colors cursor-pointer">
                      <td class="p-4">
                        <div class="flex items-center gap-3">
                          <div class="w-10 h-10 rounded-full border border-white/10 bg-surface-container-high overflow-hidden shrink-0 flex items-center justify-center">
                            <img v-if="enc.foto_representante_ruta" :src="IMAGE_BASE_URL + enc.foto_representante_ruta" class="w-full h-full object-cover">
                            <span v-else class="material-symbols-outlined text-on-surface-variant">person</span>
                          </div>
                          <p class="font-bold text-sm text-on-surface">{{ enc.representante }}</p>
                        </div>
                      </td>
                      <td class="p-4 text-sm font-bold text-primary-fixed">{{ enc.equipo_nombre }}</td>
                      <td class="p-4 text-xs font-mono text-on-surface-variant">{{ enc.telefono || 'N/A' }}</td>
                      <td class="p-4 text-center">
                        <span :class="['inline-flex items-center px-2 py-1 rounded-full text-label-sm font-label-sm border', enc.cantidad_jugadores < 10 ? 'bg-error/10 text-error border-error/30' : 'bg-primary-container/10 text-primary-fixed border-primary-fixed/20']">
                          {{ enc.cantidad_jugadores }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Equipos Registrados -->
          <header class="mb-stack-md mt-stack-lg">
            <p class="text-primary-fixed text-label-sm font-label-sm tracking-widest uppercase flex items-center gap-2 mb-1">
              <span class="w-6 h-px bg-primary-fixed"></span> Directorio General
            </p>
            <h2 class="text-title-md font-title-md text-on-surface">Equipos Registrados</h2>
          </header>

          <div v-if="equipos.length === 0" class="text-center py-12 glass-card rounded-xl mb-stack-lg">
            <p class="text-on-surface-variant font-bold mb-2">No se encontraron equipos.</p>
            <p class="text-xs text-on-surface-variant/70">Asegúrate de que haya equipos registrados y que la API esté funcionando.</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter mb-stack-lg">
            <div v-for="eq in equipos" :key="eq.id" @click="verEquipo(eq.id)" class="glass-card rounded-xl p-stack-md flex items-center gap-4 hover:border-primary-fixed/50 cursor-pointer transition-colors group">
              <div class="w-16 h-16 bg-surface-container-high border border-white/10 rounded-lg flex items-center justify-center overflow-hidden shrink-0 group-hover:border-primary-fixed/30 transition-colors">
                <img v-if="eq.foto_ruta" :src="IMAGE_BASE_URL + eq.foto_ruta" class="w-full h-full object-cover">
                <span v-else class="material-symbols-outlined text-on-surface-variant text-[28px]">shield</span>
              </div>
              <div class="overflow-hidden">
                <h3 class="font-bold text-sm uppercase truncate text-on-surface">{{ eq.nombre }}</h3>
                <p class="text-[10px] text-on-surface-variant mt-1 uppercase tracking-widest truncate">Rep: {{ eq.representante }}</p>
                <div class="mt-2 inline-block px-2 py-1 bg-surface-container-high text-primary-fixed text-[10px] font-bold uppercase rounded border border-white/10">
                  {{ eq.cantidad_jugadores || 0 }} Jugadores
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Vista Detalle de Equipo -->
        <div v-else>
          <button @click="equipoSeleccionado = null" class="mb-stack-md flex items-center gap-2 text-label-sm font-label-sm text-on-surface-variant hover:text-on-surface transition-colors glass-card px-4 py-2 rounded-lg">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Regresar
          </button>

          <header class="mb-stack-lg">
            <div class="flex items-center gap-4 mb-4">
              <div class="w-16 h-16 bg-surface-container-high border border-white/10 rounded-lg overflow-hidden shrink-0">
                <img v-if="equipoSeleccionado.foto_ruta" :src="IMAGE_BASE_URL + equipoSeleccionado.foto_ruta" class="w-full h-full object-cover">
              </div>
              <div>
                <h1 class="text-headline-lg font-headline-lg text-on-surface tracking-tighter uppercase">{{ equipoSeleccionado.nombre }}</h1>
                <p class="text-sm text-on-surface-variant">Representante: {{ equipoSeleccionado.representante }}</p>
                <p v-if="equipoSeleccionado.sub_representante_nombre" class="text-xs text-on-surface-variant/70 mt-1">Sub Rep: {{ equipoSeleccionado.sub_representante_nombre }} ({{ equipoSeleccionado.sub_representante_telefono }})</p>
              </div>
            </div>
          </header>

          <div class="mb-stack-lg">
            <div class="pb-4 border-b border-white/10 flex justify-between items-center mb-stack-md">
              <h3 class="text-label-sm font-label-sm uppercase tracking-wider text-primary-fixed">Jugadores Activos</h3>
            </div>
            <div v-if="!equipoSeleccionado.jugadores_activos?.length" class="p-8 text-center text-on-surface-variant text-sm glass-card rounded-xl">
              No hay jugadores activos.
            </div>
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-gutter">
              <div v-for="jugador in equipoSeleccionado.jugadores_activos" :key="jugador.id" class="glass-card rounded-xl overflow-hidden group transition-colors hover:border-primary-fixed/50">
                <div class="relative w-full aspect-square bg-surface-container-high overflow-hidden cursor-pointer" @click="verDetalle(jugador)">
                  <img v-if="jugador.foto_ruta" :src="IMAGE_BASE_URL + jugador.foto_ruta" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                  <div v-else class="w-full h-full flex items-center justify-center">
                    <span class="material-symbols-outlined text-[48px] text-on-surface-variant">person</span>
                  </div>
                </div>
                <div class="p-4 cursor-pointer" @click="verDetalle(jugador)">
                  <h3 class="text-sm font-bold uppercase text-on-surface truncate group-hover:text-primary-fixed transition-colors">{{ jugador.nombre }}</h3>
                  <p class="text-[10px] font-label-sm text-primary-fixed uppercase tracking-widest mt-1">{{ getNombrePosicion(jugador.posicion) }}</p>
                  <div class="mt-3 space-y-1.5 text-[11px]">
                    <div class="flex justify-between border-b border-white/5 pb-1"><span class="text-on-surface-variant uppercase">DPI</span><span class="font-mono text-on-surface">{{ jugador.dpi }}</span></div>
                    <div class="flex justify-between"><span class="text-on-surface-variant uppercase">Teléfono</span><span class="text-on-surface">{{ jugador.telefono }}</span></div>
                  </div>
                </div>
                <div class="flex justify-center gap-2 p-3 border-t border-white/10 bg-surface-container-lowest/50">
                  <button @click.stop="abrirModalEdit(jugador)" class="p-1.5 text-on-surface-variant hover:text-primary-fixed bg-surface-container-high rounded transition-colors" title="Editar">
                    <span class="material-symbols-outlined text-[16px]">edit</span>
                  </button>
                  <button @click.stop="abrirModalBaja(jugador)" class="p-1.5 text-on-surface-variant hover:text-error bg-surface-container-high rounded transition-colors" title="Dar de baja">
                    <span class="material-symbols-outlined text-[16px]">person_remove</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="glass-card rounded-xl overflow-hidden opacity-80">
            <div class="p-4 border-b border-white/10 flex justify-between items-center bg-surface-container-lowest">
              <h3 class="text-label-sm font-label-sm uppercase tracking-wider text-error">Jugadores Inactivos</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-white/10 text-[10px] uppercase tracking-widest text-on-surface-variant bg-surface-container-lowest">
                    <th class="p-4 font-bold">Jugador</th>
                    <th class="p-4 font-bold text-center">Fecha Baja</th>
                    <th class="p-4 font-bold">Razón</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!equipoSeleccionado.jugadores_inactivos?.length">
                    <td colspan="3" class="p-8 text-center text-on-surface-variant text-sm">No hay jugadores inactivos.</td>
                  </tr>
                  <tr v-for="jugador in equipoSeleccionado.jugadores_inactivos" :key="jugador.id" @click="verDetalle(jugador)" class="border-b border-white/5 hover:bg-white/5 transition-colors cursor-pointer">
                    <td class="p-4">
                      <div class="flex items-center gap-3 grayscale">
                        <img v-if="jugador.foto_ruta" :src="IMAGE_BASE_URL + jugador.foto_ruta" class="w-8 h-8 rounded-full object-cover border border-white/10">
                        <span class="font-bold text-sm text-on-surface-variant">{{ jugador.nombre }}</span>
                      </div>
                    </td>
                    <td class="p-4 text-center text-xs text-on-surface-variant">{{ formatearFecha(jugador.fecha_baja) }}</td>
                    <td class="p-4 text-xs text-on-surface-variant max-w-xs truncate" :title="jugador.razon_baja">{{ jugador.razon_baja }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal Baja -->
    <div v-if="showModalBaja" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
      <div class="glass-card p-6 rounded-xl w-full max-w-md">
        <h3 class="text-xl font-bold text-on-surface mb-4">Dar de baja a jugador (Admin)</h3>
        <p class="text-sm text-on-surface-variant mb-4">¿Deseas forzar la baja de <strong class="text-on-surface">{{ jugadorABajar?.nombre }}</strong>?</p>

        <div class="mb-4">
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Razón de la baja *</label>
          <textarea v-model="razonBaja" rows="3" class="w-full input-dark rounded-lg p-3 focus:outline-none" placeholder="Escribe el motivo aquí..." required></textarea>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button @click="showModalBaja = false" class="px-4 py-2 rounded-lg font-bold text-on-surface-variant hover:text-on-surface transition-colors flex items-center gap-2 bg-surface-container-high">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Regresar
          </button>
          <button @click="confirmarBaja" :disabled="!razonBaja.trim()" class="bg-error hover:brightness-110 text-on-error px-4 py-2 rounded-lg font-bold transition-all disabled:opacity-50">Confirmar baja</button>
        </div>
      </div>
    </div>

    <!-- Modal Detalle -->
    <div v-if="jugadorDetalle" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50" @click="jugadorDetalle = null">
      <div class="glass-card p-6 rounded-xl w-full max-w-md relative" @click.stop>
        <div class="flex flex-col items-center mb-6 mt-4">
          <div
            :class="['w-24 h-24 rounded-full overflow-hidden mb-4 border-2 flex items-center justify-center bg-surface-container-high', jugadorDetalle.estado === 'inactivo' ? 'border-error grayscale' : 'border-primary-fixed', jugadorDetalle.foto_ruta ? 'cursor-pointer' : '']"
            @click="jugadorDetalle.foto_ruta && (fotoAmpliada = IMAGE_BASE_URL + jugadorDetalle.foto_ruta)"
          >
            <img v-if="jugadorDetalle.foto_ruta" :src="IMAGE_BASE_URL + jugadorDetalle.foto_ruta" class="w-full h-full object-cover">
            <span v-else class="material-symbols-outlined text-on-surface-variant text-[48px]">person</span>
          </div>
          <h3 class="font-black text-2xl uppercase tracking-tight text-center text-on-surface">{{ jugadorDetalle.nombre }}</h3>
          <span :class="['mt-2 inline-block px-3 py-1 text-[10px] font-bold uppercase rounded-full border', jugadorDetalle.estado === 'inactivo' ? 'bg-error/10 text-error border-error/30' : 'bg-primary-container/10 text-primary-fixed border-primary-fixed/30']">
            {{ jugadorDetalle.estado }}
          </span>
        </div>

        <div class="space-y-3 bg-surface-container-high p-4 rounded-lg border border-white/10">
          <div class="flex justify-between border-b border-white/10 pb-2">
            <span class="text-xs text-on-surface-variant uppercase font-bold">DPI</span>
            <span class="text-sm font-mono text-on-surface">{{ jugadorDetalle.dpi }}</span>
          </div>
          <div class="flex justify-between border-b border-white/10 pb-2">
            <span class="text-xs text-on-surface-variant uppercase font-bold">Teléfono</span>
            <span class="text-sm text-on-surface">{{ jugadorDetalle.telefono }}</span>
          </div>
          <div class="flex justify-between border-b border-white/10 pb-2">
            <span class="text-xs text-on-surface-variant uppercase font-bold">Posición</span>
            <span class="text-sm text-on-surface">{{ jugadorDetalle.posicion || 'N/A' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-xs text-on-surface-variant uppercase font-bold">Equipo</span>
            <span class="text-sm text-primary-fixed font-bold truncate max-w-[150px]">{{ equipoSeleccionado?.nombre }}</span>
          </div>
        </div>

        <div v-if="jugadorDetalle.estado === 'inactivo'" class="mt-4 bg-error/10 p-4 rounded-lg border border-error/30 mb-4">
          <label class="block text-[10px] font-bold text-error uppercase tracking-wider mb-2">Detalles de Baja ({{ formatearFecha(jugadorDetalle.fecha_baja) }}):</label>
          <p class="text-on-surface-variant text-sm whitespace-pre-line">{{ jugadorDetalle.razon_baja || 'Sin motivo especificado.' }}</p>
        </div>

        <div class="flex justify-start mt-4">
          <button @click="jugadorDetalle = null" class="flex items-center gap-2 text-sm font-bold text-on-surface-variant hover:text-on-surface transition-colors bg-surface-container-high px-4 py-2 rounded-lg">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Regresar
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Editar Jugador -->
    <div v-if="showModalEdit" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50 overflow-y-auto">
      <div class="glass-card p-6 rounded-xl w-full max-w-md">
        <h3 class="text-xl font-bold text-on-surface mb-4">Editar Jugador</h3>
        <form @submit.prevent="guardarEdicionJugador">
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Nombre *</label>
              <input v-model="editJugadorForm.nombre" type="text" class="w-full input-dark rounded-lg p-3 focus:outline-none" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">DPI *</label>
              <input v-model="editJugadorForm.dpi" type="text" class="w-full input-dark rounded-lg p-3 focus:outline-none" minlength="13" maxlength="13" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Foto (opcional para actualizar)</label>
              <input type="file" @change="handleEditFileChange" accept="image/*" class="w-full input-dark rounded-lg p-2">
              <div v-if="editJugadorForm.fotoUrl" class="mt-2 w-24 h-24 bg-black rounded overflow-hidden border border-white/10">
                <img :src="editJugadorForm.fotoUrl" class="w-full h-full object-cover">
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="showModalEdit = false" class="px-4 py-2 rounded-lg font-bold text-on-surface-variant hover:text-on-surface transition-colors flex items-center gap-2 bg-surface-container-high">
              <span class="material-symbols-outlined text-[18px]">arrow_back</span> Regresar
            </button>
            <button type="submit" class="btn-primary px-4 py-2 rounded-lg font-bold">Guardar cambios</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Equipos Incompletos -->
    <div v-if="showModalIncompletos" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
      <div class="glass-card rounded-xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
        <div class="p-6 border-b border-white/10 bg-error/10 flex flex-col gap-4">
          <div class="flex justify-start">
            <button @click="showModalIncompletos = false" class="flex items-center gap-2 text-sm font-bold text-on-surface-variant hover:text-on-surface transition-colors bg-surface-container-high px-4 py-2 rounded-lg">
              <span class="material-symbols-outlined text-[18px]">arrow_back</span> Regresar
            </button>
          </div>
          <h3 class="text-xl font-bold text-error flex items-center gap-2">
            <span class="material-symbols-outlined">warning</span> Equipos Incompletos
          </h3>
        </div>

        <div class="p-6 overflow-y-auto">
          <p class="text-sm text-on-surface-variant mb-4">Los siguientes equipos tienen menos de 10 jugadores activos registrados:</p>
          <ul class="space-y-3 mb-6">
            <li v-for="eq in estadisticasGenerales.equipos_incompletos_list" :key="eq.id" class="flex items-center justify-between p-3 bg-surface-container-high rounded-lg border border-white/10">
              <div class="flex items-center gap-3">
                <img :src="IMAGE_BASE_URL + eq.foto_ruta" @error="handleImageError" class="w-8 h-8 rounded-full object-cover">
                <span class="font-bold text-sm text-on-surface">{{ eq.nombre }}</span>
              </div>
              <span class="text-xs font-bold bg-error/20 text-error px-2 py-1 rounded">
                {{ eq.cantidad_jugadores }} jug.
              </span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Modal Detalle Encargado -->
    <div v-if="encargadoDetalle" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50 backdrop-blur-sm" @click="encargadoDetalle = null">
      <div class="w-full max-w-sm relative flex flex-col items-center" @click.stop>
        <div class="w-full flex justify-start mb-6">
          <button @click="encargadoDetalle = null" class="flex items-center gap-2 text-sm font-bold text-on-surface-variant hover:text-on-surface transition-colors bg-surface-container-lowest/90 px-4 py-2 rounded-lg border border-white/10 shadow-lg">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Regresar
          </button>
        </div>

        <!-- Premium Card -->
        <div class="relative w-[280px] h-[420px] mx-auto rounded-xl shadow-2xl overflow-hidden text-on-surface font-serif transform transition-transform hover:scale-105" style="background: linear-gradient(135deg, #1f2410 0%, #0e0e0e 100%); border: 1px solid rgba(185,246,63,0.4); box-shadow: 0 20px 25px -5px rgba(185, 246, 63, 0.15), 0 10px 10px -5px rgba(185, 246, 63, 0.08);">
          <!-- Top section (Foto Representante) -->
          <div class="w-full h-[240px] absolute top-0 left-0 flex justify-center items-end" style="mask-image: linear-gradient(to bottom, black 65%, transparent 100%); -webkit-mask-image: linear-gradient(to bottom, black 65%, transparent 100%);">
            <img v-if="encargadoDetalle.foto_representante_ruta" :src="IMAGE_BASE_URL + encargadoDetalle.foto_representante_ruta" class="w-full h-full object-cover object-top filter contrast-110">
            <div v-else class="w-full h-full bg-black/30 flex items-center justify-center">
              <span class="material-symbols-outlined text-[80px] text-white/40 mb-10">person</span>
            </div>
          </div>

          <!-- Info section -->
          <div class="absolute bottom-5 left-0 right-0 px-4 text-center">
            <h3 class="text-base leading-tight font-black uppercase border-b border-white/20 pb-2 mb-2 mx-2 flex items-center justify-center drop-shadow-md min-h-[40px]">{{ encargadoDetalle.representante }}</h3>

            <p class="text-[10px] font-black uppercase tracking-widest text-primary-fixed mb-4 drop-shadow-md">Representante</p>

            <div class="flex flex-col gap-y-2 text-[11px] font-bold px-2">
              <div class="flex justify-between items-center bg-black/30 p-2 rounded-lg border border-white/5">
                <span class="text-white/60 tracking-wider text-[9px]">EQUIPO</span>
                <div class="flex items-center gap-2">
                  <img v-if="encargadoDetalle.foto_ruta" :src="IMAGE_BASE_URL + encargadoDetalle.foto_ruta" class="w-5 h-5 rounded-full object-cover border border-primary-fixed/50 shadow-sm">
                  <span v-else class="material-symbols-outlined text-[14px] text-on-surface-variant bg-black/50 rounded-full p-1">shield</span>
                  <span class="text-on-surface truncate max-w-[100px]">{{ encargadoDetalle.equipo_nombre }}</span>
                </div>
              </div>
              <div class="flex justify-between items-center px-2">
                <span class="text-white/60 tracking-wider text-[9px]">DPI</span>
                <span class="font-mono text-on-surface">{{ encargadoDetalle.dpi || 'N/A' }}</span>
              </div>
              <div class="flex justify-between items-center px-2">
                <span class="text-white/60 tracking-wider text-[9px]">TELÉFONO</span>
                <span class="text-on-surface">{{ encargadoDetalle.telefono || 'N/A' }}</span>
              </div>
              <div class="flex justify-between items-center px-2">
                <span class="text-white/60 tracking-wider text-[9px]">JUGADORES ACT.</span>
                <span class="text-on-surface">{{ encargadoDetalle.cantidad_jugadores }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lightbox Foto (estilo WhatsApp) -->
    <div v-if="fotoAmpliada" class="fixed inset-0 bg-black/95 flex items-center justify-center z-[60] p-4" @click="fotoAmpliada = null">
      <button @click="fotoAmpliada = null" class="absolute top-4 right-4 text-white/80 hover:text-white bg-black/40 hover:bg-black/60 rounded-full p-2 transition-colors">
        <span class="material-symbols-outlined">close</span>
      </button>
      <img :src="fotoAmpliada" class="max-w-full max-h-full rounded-lg object-contain shadow-2xl" @click.stop>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api, { IMAGE_BASE_URL } from '../services/api'

const router = useRouter()
const equipos = ref([])
const equipoSeleccionado = ref(null)
const isLoading = ref(true)
const error = ref('')

const estadisticasGenerales = ref({ total_jugadores: 0, total_equipos: 0, equipos_incompletos_count: 0, total_partidos: 0, equipos_incompletos_list: [] })
const encargados = ref([])
const showModalIncompletos = ref(false)
const encargadoDetalle = ref(null)

const animacionStats = ref({ total_jugadores: 0, total_equipos: 0, equipos_incompletos: 0, total_partidos: 0 })

const animarContador = (target, refKey, duration = 1500) => {
  let startTimestamp = null;
  const step = (timestamp) => {
    if (!startTimestamp) startTimestamp = timestamp;
    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
    animacionStats.value[refKey] = Math.floor(progress * target);
    if (progress < 1) {
      window.requestAnimationFrame(step);
    }
  };
  window.requestAnimationFrame(step);
}

const showModalBaja = ref(false)
const jugadorABajar = ref(null)
const razonBaja = ref('')

const getNombrePosicion = (pos) => {
  const posiciones = {
    'POR': 'PORTERO',
    'DEF': 'DEFENSA',
    'MED': 'MEDIOCAMPISTA',
    'DEL': 'DELANTERO'
  }
  return posiciones[pos] || pos || 'N/A'
}

// Edit Player State
const showModalEdit = ref(false)
const editJugadorForm = ref({ id: '', nombre: '', dpi: '', foto: null, fotoUrl: '' })
const editJugadorFile = ref(null)

const jugadorDetalle = ref(null)
const fotoAmpliada = ref(null)

const verDetalle = (jugador) => {
  jugadorDetalle.value = jugador
}

const verDetalleEncargado = (encargado) => {
  encargadoDetalle.value = encargado
}

const abrirModalEdit = (jugador) => {
  editJugadorForm.value = {
    id: jugador.id,
    nombre: jugador.nombre,
    dpi: jugador.dpi,
    fotoUrl: jugador.foto_ruta ? IMAGE_BASE_URL + jugador.foto_ruta : ''
  }
  editJugadorFile.value = null
  showModalEdit.value = true
}

const handleEditFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    editJugadorFile.value = file
    editJugadorForm.value.fotoUrl = URL.createObjectURL(file)
  }
}

const guardarEdicionJugador = async () => {
  try {
    const token = localStorage.getItem('deportes_token')
    const formData = new FormData()
    formData.append('nombre', editJugadorForm.value.nombre)
    formData.append('dpi', editJugadorForm.value.dpi)
    if (editJugadorFile.value) {
      formData.append('foto', editJugadorFile.value)
    }

    await api.post(`/jugadores/${editJugadorForm.value.id}/edit`, formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'multipart/form-data'
      }
    })

    showModalEdit.value = false
    await verEquipo(equipoSeleccionado.value.id)
  } catch (err) {
    alert('Error al editar jugador: ' + (err.response?.data?.error || err.message))
  }
}


onMounted(async () => {
  await cargarEquipos()
})

const cargarEquipos = async () => {
  isLoading.value = true
  try {
    const token = localStorage.getItem('deportes_token')

    const [resEquipos, resStats, resEncargados] = await Promise.all([
      api.get('/admin/equipos', { headers: { Authorization: `Bearer ${token}` } }),
      api.get('/admin/estadisticas-generales', { headers: { Authorization: `Bearer ${token}` } }),
      api.get('/admin/encargados', { headers: { Authorization: `Bearer ${token}` } })
    ]);

    equipos.value = resEquipos.data
    estadisticasGenerales.value = resStats.data
    encargados.value = resEncargados.data

    // Iniciar animaciones
    animarContador(resStats.data.total_jugadores, 'total_jugadores');
    animarContador(resStats.data.total_equipos, 'total_equipos');
    animarContador(resStats.data.equipos_incompletos_count, 'equipos_incompletos');
    animarContador(resStats.data.total_partidos, 'total_partidos');

  } catch (err) {
    handleError(err)
  } finally {
    isLoading.value = false
  }
}

const verEquipo = async (id) => {
  isLoading.value = true
  try {
    const token = localStorage.getItem('deportes_token')
    const response = await api.get(`/admin/equipos/${id}/jugadores`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    equipoSeleccionado.value = response.data
  } catch (err) {
    handleError(err)
  } finally {
    isLoading.value = false
  }
}

const abrirModalBaja = (jugador) => {
  jugadorABajar.value = jugador
  razonBaja.value = ''
  showModalBaja.value = true
}

const confirmarBaja = async () => {
  if (!razonBaja.value.trim()) return

  try {
    const token = localStorage.getItem('deportes_token')
    await api.patch(`/jugadores/${jugadorABajar.value.id}/baja`, {
      razon_baja: razonBaja.value
    }, {
      headers: { Authorization: `Bearer ${token}` }
    })

    showModalBaja.value = false

    // Refresh equipo actual
    await verEquipo(equipoSeleccionado.value.id)
  } catch (err) {
    alert('Error al dar de baja: ' + (err.response?.data?.error || err.message))
  }
}

const logout = () => {
  localStorage.removeItem('deportes_token')
  localStorage.removeItem('deportes_equipo')
  localStorage.removeItem('deportes_rol')
  router.push('/')
}

const formatearFecha = (fechaString) => {
  if (!fechaString) return 'N/A'
  const date = new Date(fechaString)
  return new Intl.DateTimeFormat('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(date)
}

const handleError = (err) => {
  if (err.response?.status === 401 || err.response?.status === 403) {
    logout()
  } else {
    error.value = 'Error de conexión.'
  }
}
</script>
