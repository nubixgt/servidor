<template>
  <div class="min-h-screen bg-[#121212] flex flex-col md:flex-row text-white">
    <!-- Sidebar -->
    <aside class="w-full md:w-64 bg-[#0a0a0a] border-r border-gray-800 flex flex-col">
      <div class="p-6 border-b border-gray-800">
        <div class="flex items-center gap-3">
          <div class="w-10 h-12 bg-gray-900 border border-purple-500 rounded flex items-center justify-center overflow-hidden">
             <svg class="w-6 h-6 text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
          </div>
          <div>
            <h2 class="font-bold text-sm uppercase">ADMIN PANEL</h2>
            <p class="text-[10px] text-purple-500 uppercase tracking-widest">Sistema DEPORTES</p>
          </div>
        </div>
      </div>
      
      <nav class="flex-grow p-4 space-y-2">
        <button @click="equipoSeleccionado = null" :class="['w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-bold', !equipoSeleccionado ? 'bg-purple-500/10 text-purple-500 border border-purple-500/20' : 'text-gray-400 hover:bg-gray-900 hover:text-white']">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          Directorio Equipos
        </button>
        <router-link to="/registrar-partido" class="w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-bold text-gray-400 hover:bg-gray-900 hover:text-white">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path><path d="M2 12h20"></path></svg>
          Registrar Partido
        </router-link>
        <router-link to="/historial-partidos" class="w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-bold text-gray-400 hover:bg-gray-900 hover:text-white">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          Historial Partidos
        </router-link>
      </nav>

      <div class="p-4 border-t border-gray-800">
        <div class="flex items-center justify-between">
           <div class="flex items-center gap-2">
             <div class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center overflow-hidden">
               <svg class="w-4 h-4 text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
             </div>
             <div>
               <p class="text-xs font-bold">Administrador</p>
               <p class="text-[10px] text-gray-500">Superusuario</p>
             </div>
           </div>
           <button @click="logout" class="text-gray-500 hover:text-red-500 transition-colors" title="Cerrar sesión">
             <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
           </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-grow p-4 md:p-8 overflow-y-auto">
      <div v-if="isLoading" class="text-center py-12 text-gray-500">Cargando información...</div>
      
      <div v-else-if="error" class="bg-red-500/10 border border-red-500/50 p-4 rounded-lg text-red-400 mb-6">
        {{ error }}
      </div>

      <div v-else>
        <!-- Vista Directorio de Equipos -->
        <div v-if="!equipoSeleccionado">
          <!-- DASHBOARD START -->
          <div class="mb-12">
            <h1 class="text-3xl font-black italic tracking-tight mb-6">DIRECTORIO DE EQUIPOS</h1>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              
              <!-- Total Jugadores -->
              <div class="bg-[#1e1e1e] border-l-4 border-blue-500 rounded-xl p-6 shadow-lg relative overflow-hidden">
                <i class="fas fa-users absolute -bottom-4 -right-4 text-6xl text-gray-800/50"></i>
                <div class="relative z-10">
                  <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Jugadores Activos</p>
                  <h3 class="text-4xl font-black text-blue-400">{{ animacionStats.total_jugadores }}</h3>
                </div>
              </div>

              <!-- Total Equipos -->
              <div class="bg-[#1e1e1e] border-l-4 border-green-500 rounded-xl p-6 shadow-lg relative overflow-hidden">
                <i class="fas fa-shield-alt absolute -bottom-4 -right-4 text-6xl text-gray-800/50"></i>
                <div class="relative z-10">
                  <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Total Equipos</p>
                  <h3 class="text-4xl font-black text-green-400">{{ animacionStats.total_equipos }}</h3>
                </div>
              </div>

              <!-- Partidos Registrados -->
              <div class="bg-[#1e1e1e] border-l-4 border-purple-500 rounded-xl p-6 shadow-lg relative overflow-hidden">
                <i class="fas fa-futbol absolute -bottom-4 -right-4 text-6xl text-gray-800/50"></i>
                <div class="relative z-10">
                  <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Partidos Registrados</p>
                  <h3 class="text-4xl font-black text-purple-400">{{ animacionStats.total_partidos }}</h3>
                </div>
              </div>

              <!-- Equipos Incompletos -->
              <div @click="estadisticasGenerales.equipos_incompletos_count > 0 ? (showModalIncompletos = true) : null" 
                   :class="['border-l-4 rounded-xl p-6 shadow-lg relative overflow-hidden transition-colors', estadisticasGenerales.equipos_incompletos_count > 0 ? 'bg-[#2a1a1a] border-red-500 cursor-pointer hover:bg-[#351a1a]' : 'bg-[#1e1e1e] border-gray-600']">
                <i class="fas fa-exclamation-triangle absolute -bottom-4 -right-4 text-6xl text-gray-800/50"></i>
                <div class="relative z-10">
                  <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Equipos Incompletos</p>
                  <h3 :class="['text-4xl font-black', estadisticasGenerales.equipos_incompletos_count > 0 ? 'text-red-500' : 'text-gray-500']">{{ animacionStats.equipos_incompletos }}</h3>
                  <p v-if="estadisticasGenerales.equipos_incompletos_count > 0" class="text-[10px] text-red-400 mt-2 font-bold uppercase">Ver detalles &rarr;</p>
                </div>
              </div>

            </div>
          </div>
          
          <!-- DIRECTORIO DE ENCARGADOS -->
          <div class="mb-12">
             <header class="mb-4">
              <p class="text-purple-500 text-xs font-bold tracking-widest uppercase flex items-center gap-2 mb-1">
                <span class="w-6 h-px bg-purple-500"></span> Contactos
              </p>
              <h2 class="text-2xl font-black italic tracking-tight">LISTA DE ENCARGADOS</h2>
            </header>
            <div class="bg-[#1e1e1e] border border-gray-800 rounded-xl overflow-hidden shadow-lg overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-gray-800 text-[10px] uppercase tracking-widest text-gray-500 bg-[#161616]">
                    <th class="p-4 font-bold">Representante</th>
                    <th class="p-4 font-bold">Equipo</th>
                    <th class="p-4 font-bold">Teléfono</th>
                    <th class="p-4 font-bold text-center">Jugadores</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="encargados.length === 0">
                    <td colspan="4" class="p-8 text-center text-gray-500 text-sm">No hay encargados registrados.</td>
                  </tr>
                  <tr v-for="enc in encargados" :key="enc.equipo_id" class="border-b border-gray-800 hover:bg-[#252525] transition-colors">
                    <td class="p-4">
                      <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full border border-gray-700 bg-gray-900 overflow-hidden shrink-0">
                          <img v-if="enc.foto_representante_ruta" :src="IMAGE_BASE_URL + enc.foto_representante_ruta" class="w-full h-full object-cover">
                          <svg v-else class="w-full h-full text-gray-500 p-2" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        </div>
                        <div>
                          <p class="font-bold text-sm text-gray-200">{{ enc.representante }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="p-4">
                      <span class="text-sm font-bold text-purple-400">{{ enc.equipo_nombre }}</span>
                    </td>
                    <td class="p-4 text-xs font-mono text-gray-400">
                      {{ enc.telefono || 'N/A' }}
                    </td>
                    <td class="p-4 text-center">
                      <span :class="['px-2 py-1 rounded text-xs font-bold', enc.cantidad_jugadores < 10 ? 'bg-red-500/20 text-red-400 border border-red-500/30' : 'bg-gray-800 text-gray-300']">
                        {{ enc.cantidad_jugadores }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <!-- DASHBOARD END -->

          <header class="mb-8">
            <p class="text-purple-500 text-xs font-bold tracking-widest uppercase flex items-center gap-2 mb-1">
              <span class="w-6 h-px bg-purple-500"></span> Directorio General
            </p>
            <h2 class="text-2xl font-black italic tracking-tight">EQUIPOS REGISTRADOS</h2>
          </header>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="eq in equipos" :key="eq.id" @click="verEquipo(eq.id)" class="bg-[#1e1e1e] border border-gray-800 rounded-xl p-6 flex items-center gap-4 hover:border-purple-500/50 cursor-pointer transition-colors group">
              <div class="w-16 h-16 bg-gray-900 border border-gray-700 rounded-lg flex items-center justify-center overflow-hidden shrink-0 group-hover:border-purple-500/30 transition-colors">
                 <img v-if="eq.foto_ruta" :src="IMAGE_BASE_URL + eq.foto_ruta" class="w-full h-full object-cover">
                 <svg v-else class="w-8 h-8 text-gray-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l9 4v6c0 5.55-3.84 10.74-9 12-5.16-1.26-9-6.45-9-12V6l9-4z"/></svg>
              </div>
              <div class="overflow-hidden">
                <h3 class="font-bold text-sm uppercase truncate text-white">{{ eq.nombre }}</h3>
                <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-widest truncate">Rep: {{ eq.representante }}</p>
                <div class="mt-2 inline-block px-2 py-1 bg-gray-900 text-purple-400 text-[10px] font-bold uppercase rounded border border-gray-800">
                  {{ eq.cantidad_jugadores || 0 }} Jugadores Activos
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Vista Detalle de Equipo -->
        <div v-else>
          <button @click="equipoSeleccionado = null" class="mb-6 flex items-center text-sm font-bold text-gray-400 hover:text-white transition-colors bg-gray-800/50 px-4 py-2 rounded-lg">
            <i class="fas fa-arrow-left mr-2"></i> Regresar
          </button>

          <header class="mb-8">
            <div class="flex items-center gap-4 mb-4">
              <div class="w-16 h-16 bg-gray-900 border border-gray-700 rounded-lg overflow-hidden shrink-0">
                 <img v-if="equipoSeleccionado.foto_ruta" :src="IMAGE_BASE_URL + equipoSeleccionado.foto_ruta" class="w-full h-full object-cover">
              </div>
              <div>
                <h1 class="text-3xl font-black italic tracking-tight">{{ equipoSeleccionado.nombre }}</h1>
                <p class="text-sm text-gray-400">Representante: {{ equipoSeleccionado.representante }}</p>
                <p v-if="equipoSeleccionado.sub_representante_nombre" class="text-xs text-gray-500 mt-1">Sub Rep: {{ equipoSeleccionado.sub_representante_nombre }} ({{ equipoSeleccionado.sub_representante_telefono }})</p>
              </div>
            </div>
          </header>

          <div class="mb-8">
            <div class="pb-4 border-b border-gray-800 flex justify-between items-center mb-6">
              <h3 class="font-bold text-sm uppercase tracking-wider text-purple-400">Jugadores Activos</h3>
            </div>
            <div v-if="!equipoSeleccionado.jugadores_activos?.length" class="p-8 text-center text-gray-500 text-sm bg-[#1e1e1e] border border-gray-800 rounded-xl">
              No hay jugadores activos.
            </div>
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
              <div v-for="jugador in equipoSeleccionado.jugadores_activos" :key="jugador.id" @click="verDetalle(jugador)" class="relative w-48 h-72 mx-auto rounded-lg shadow-2xl overflow-hidden text-[#3b2800] font-serif transform transition-transform hover:scale-105 cursor-pointer group" style="background: linear-gradient(135deg, #e6c875 0%, #b28a38 100%); border: 1px solid #ffe9a6;">
                <!-- Top section -->
                <div class="w-full h-40 absolute top-0 left-0 flex justify-center items-end" style="mask-image: linear-gradient(to bottom, black 75%, transparent 100%); -webkit-mask-image: linear-gradient(to bottom, black 75%, transparent 100%);">
                   <img v-if="jugador.foto_ruta" :src="IMAGE_BASE_URL + jugador.foto_ruta" class="w-full h-full object-cover object-top filter contrast-125">
                   <div v-else class="w-full h-full bg-black/20 flex items-center justify-center"><svg class="w-12 h-12 text-[#3b2800]/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                </div>
                
                <!-- Info section -->
                <div class="absolute bottom-3 left-0 right-0 px-3 text-center">
                  <h3 class="text-[11px] leading-tight font-black uppercase border-b border-[#3b2800]/30 pb-1 mb-2 mx-1 h-8 flex items-center justify-center">{{ jugador.nombre }}</h3>
                  <div class="flex flex-col gap-y-1 text-[9px] font-bold px-1">
                    <div class="flex justify-between"><span>DPI</span><span class="font-mono">{{ jugador.dpi }}</span></div>
                    <div class="flex justify-between"><span>TELÉFONO</span><span>{{ jugador.telefono }}</span></div>
                    <div class="flex justify-between"><span>POSICIÓN</span><span>{{ getNombrePosicion(jugador.posicion) }}</span></div>
                  </div>
                  <div class="flex justify-center gap-2 mt-2 pt-2 border-t border-[#3b2800]/30 mx-1">
                     <button @click.stop="abrirModalEdit(jugador)" class="p-1.5 hover:text-white bg-black/10 hover:bg-black/40 rounded transition-colors" title="Editar"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
                     <button @click.stop="abrirModalBaja(jugador)" class="p-1.5 hover:text-red-500 bg-black/10 hover:bg-black/40 rounded transition-colors" title="Dar de baja"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-[#1e1e1e] border border-gray-800 rounded-xl overflow-hidden opacity-80">
            <div class="p-4 border-b border-gray-800 flex justify-between items-center bg-[#1a1a1a]">
              <h3 class="font-bold text-sm uppercase tracking-wider text-red-400">Jugadores Inactivos</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-gray-800 text-[10px] uppercase tracking-widest text-gray-500 bg-[#161616]">
                    <th class="p-4 font-bold">Jugador</th>
                    <th class="p-4 font-bold text-center">Fecha Baja</th>
                    <th class="p-4 font-bold">Razón</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!equipoSeleccionado.jugadores_inactivos?.length">
                    <td colspan="3" class="p-8 text-center text-gray-500 text-sm">No hay jugadores inactivos.</td>
                  </tr>
                  <tr v-for="jugador in equipoSeleccionado.jugadores_inactivos" :key="jugador.id" @click="verDetalle(jugador)" class="border-b border-gray-800 hover:bg-[#252525] transition-colors cursor-pointer">
                    <td class="p-4">
                      <div class="flex items-center gap-3 grayscale">
                        <img v-if="jugador.foto_ruta" :src="IMAGE_BASE_URL + jugador.foto_ruta" class="w-8 h-8 rounded-full object-cover border border-gray-700">
                        <span class="font-bold text-sm text-gray-400">{{ jugador.nombre }}</span>
                      </div>
                    </td>
                    <td class="p-4 text-center text-xs text-gray-500">{{ formatearFecha(jugador.fecha_baja) }}</td>
                    <td class="p-4 text-xs text-gray-400 max-w-xs truncate" :title="jugador.razon_baja">{{ jugador.razon_baja }}</td>
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
      <div class="bg-[#1a1a1a] border border-gray-800 p-6 rounded-xl w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Dar de baja a jugador (Admin)</h3>
        <p class="text-sm text-gray-400 mb-4">¿Deseas forzar la baja de <strong class="text-white">{{ jugadorABajar?.nombre }}</strong>?</p>
        
        <div class="mb-4">
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Razón de la baja *</label>
          <textarea v-model="razonBaja" rows="3" class="w-full bg-[#2a2a2a] border border-gray-700 rounded-lg p-3 text-white focus:outline-none focus:border-purple-500" placeholder="Escribe el motivo aquí..." required></textarea>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button @click="showModalBaja = false" class="px-4 py-2 rounded-lg font-bold text-gray-400 hover:text-white transition-colors flex items-center bg-gray-800/80">
            <i class="fas fa-arrow-left mr-2"></i> Regresar
          </button>
          <button @click="confirmarBaja" :disabled="!razonBaja.trim()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-bold transition-colors disabled:opacity-50">Confirmar baja</button>
        </div>
      </div>
    </div>

    <!-- Modal Detalle -->
    <div v-if="jugadorDetalle" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50" @click="jugadorDetalle = null">
      <div class="bg-[#1a1a1a] border border-gray-800 p-6 rounded-xl w-full max-w-md relative" @click.stop>
        
        <div class="flex flex-col items-center mb-6 mt-4">
          <div :class="['w-24 h-24 rounded-full overflow-hidden mb-4 border-2', jugadorDetalle.estado === 'inactivo' ? 'border-red-500 grayscale' : 'border-purple-500']">
            <img v-if="jugadorDetalle.foto_ruta" :src="IMAGE_BASE_URL + jugadorDetalle.foto_ruta" class="w-full h-full object-cover">
            <svg v-else class="w-full h-full text-gray-500 bg-gray-800 p-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          </div>
          <h3 class="font-black text-2xl uppercase tracking-tight text-center">{{ jugadorDetalle.nombre }}</h3>
          <span :class="['mt-2 inline-block px-3 py-1 text-[10px] font-bold uppercase rounded-full border', jugadorDetalle.estado === 'inactivo' ? 'bg-red-500/10 text-red-500 border-red-500/30' : 'bg-purple-500/10 text-purple-500 border-purple-500/30']">
            {{ jugadorDetalle.estado }}
          </span>
        </div>
        
        <div class="space-y-3 bg-[#2a2a2a] p-4 rounded-lg border border-gray-700">
          <div class="flex justify-between border-b border-gray-700 pb-2">
            <span class="text-xs text-gray-500 uppercase font-bold">DPI</span>
            <span class="text-sm font-mono text-gray-300">{{ jugadorDetalle.dpi }}</span>
          </div>
          <div class="flex justify-between border-b border-gray-700 pb-2">
            <span class="text-xs text-gray-500 uppercase font-bold">Teléfono</span>
            <span class="text-sm text-gray-300">{{ jugadorDetalle.telefono }}</span>
          </div>
          <div class="flex justify-between border-b border-gray-700 pb-2">
            <span class="text-xs text-gray-500 uppercase font-bold">Posición</span>
            <span class="text-sm text-gray-300">{{ jugadorDetalle.posicion || 'N/A' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-xs text-gray-500 uppercase font-bold">Equipo</span>
            <span class="text-sm text-purple-400 font-bold truncate max-w-[150px]">{{ equipoSeleccionado?.nombre }}</span>
          </div>
        </div>

        <div v-if="jugadorDetalle.estado === 'inactivo'" class="mt-4 bg-red-500/10 p-4 rounded-lg border border-red-500/30 mb-4">
          <label class="block text-[10px] font-bold text-red-400 uppercase tracking-wider mb-2">Detalles de Baja ({{ formatearFecha(jugadorDetalle.fecha_baja) }}):</label>
          <p class="text-gray-300 text-sm whitespace-pre-line">{{ jugadorDetalle.razon_baja || 'Sin motivo especificado.' }}</p>
        </div>

        <div class="flex justify-start mt-4">
          <button @click="jugadorDetalle = null" class="flex items-center text-sm font-bold text-gray-400 hover:text-white transition-colors bg-gray-800/80 px-4 py-2 rounded-lg">
            <i class="fas fa-arrow-left mr-2"></i> Regresar
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Editar Jugador -->
    <div v-if="showModalEdit" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50 overflow-y-auto">
      <div class="bg-[#1a1a1a] border border-gray-800 p-6 rounded-xl w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Editar Jugador</h3>
        <form @submit.prevent="guardarEdicionJugador">
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nombre *</label>
              <input v-model="editJugadorForm.nombre" type="text" class="w-full bg-[#2a2a2a] border border-gray-700 rounded-lg p-3 text-white focus:outline-none focus:border-purple-500" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">DPI *</label>
              <input v-model="editJugadorForm.dpi" type="text" class="w-full bg-[#2a2a2a] border border-gray-700 rounded-lg p-3 text-white focus:outline-none focus:border-purple-500" minlength="13" maxlength="13" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Foto (opcional para actualizar)</label>
              <input type="file" @change="handleEditFileChange" accept="image/*" class="w-full bg-[#2a2a2a] border border-gray-700 rounded-lg p-2 text-white">
              <div v-if="editJugadorForm.fotoUrl" class="mt-2 w-24 h-24 bg-black rounded overflow-hidden border border-gray-700">
                 <img :src="editJugadorForm.fotoUrl" class="w-full h-full object-cover">
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="showModalEdit = false" class="px-4 py-2 rounded-lg font-bold text-gray-400 hover:text-white transition-colors flex items-center bg-gray-800/80">
              <i class="fas fa-arrow-left mr-2"></i> Regresar
            </button>
            <button type="submit" class="bg-purple-500 text-white px-4 py-2 rounded-lg font-bold transition-colors hover:bg-purple-600">Guardar cambios</button>
          </div>
        </form>
      </div>
    </div>
    <!-- Modal Equipos Incompletos -->
    <div v-if="showModalIncompletos" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
      <div class="bg-[#1a1a1a] border border-gray-800 rounded-xl w-full max-w-lg overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-gray-800 bg-red-500/10 flex justify-between items-center">
          <h3 class="text-xl font-bold text-red-500 flex items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i> Equipos Incompletos
          </h3>
          <button @click="showModalIncompletos = false" class="text-gray-400 hover:text-white">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
        <div class="p-6 max-h-[60vh] overflow-y-auto">
          <p class="text-sm text-gray-400 mb-4">Los siguientes equipos tienen menos de 10 jugadores activos registrados:</p>
          <ul class="space-y-3 mb-6">
            <li v-for="eq in estadisticasGenerales.equipos_incompletos_list" :key="eq.id" class="flex items-center justify-between p-3 bg-gray-900 rounded-lg border border-gray-800">
              <div class="flex items-center gap-3">
                <img :src="IMAGE_BASE_URL + eq.foto_ruta" @error="handleImageError" class="w-8 h-8 rounded-full object-cover">
                <span class="font-bold text-sm text-white">{{ eq.nombre }}</span>
              </div>
              <span class="text-xs font-bold bg-red-500/20 text-red-400 px-2 py-1 rounded">
                {{ eq.cantidad_jugadores }} jug.
              </span>
            </li>
          </ul>
          <div class="flex justify-start">
            <button @click="showModalIncompletos = false" class="flex items-center text-sm font-bold text-gray-400 hover:text-white transition-colors bg-gray-800/80 px-4 py-2 rounded-lg">
              <i class="fas fa-arrow-left mr-2"></i> Regresar
            </button>
          </div>
        </div>
      </div>
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

const verDetalle = (jugador) => {
  jugadorDetalle.value = jugador
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
