<template>
  <div class="p-4 md:p-8 max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative z-10">
      <div data-aos="fade-right" data-aos-duration="1000">
        <h1 class="text-4xl font-black text-white italic uppercase tracking-tighter leading-none mb-2">Control <span class="text-primary">Concreto</span></h1>
        <p class="text-white/60 font-bold uppercase tracking-widest text-sm">Gestión de Despachos y Colocación</p>
      </div>

      <div data-aos="fade-left" data-aos-duration="1000" class="flex gap-4">
        <button v-if="authStore.userRole === 'admin'" @click="openStep1Modal" class="glass-button-primary text-white py-4 px-8 rounded-2xl font-bold flex items-center gap-2 shadow-[0_0_20px_rgba(var(--primary),0.3)] hover:shadow-[0_0_40px_rgba(var(--primary),0.5)] transition-all">
          <PlusIcon class="w-6 h-6" /> Nuevo Despacho (Planta)
        </button>
      </div>
    </div>

    <!-- Kanban / List -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
      
      <!-- Col 1: Pendiente Piloto (Paso 2) -->
      <div v-if="['admin', 'tecnico'].includes(authStore.userRole)" class="glass-card rounded-[32px] p-6 border border-white/5 relative overflow-hidden" data-aos="zoom-in-up" data-aos-duration="1000" data-aos-delay="100">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-amber-500/10 blur-3xl rounded-full pointer-events-none"></div>
        <h2 class="text-lg font-black text-white uppercase tracking-widest mb-6 flex items-center gap-3">
          <TruckIcon class="w-6 h-6 text-amber-400" /> Pendiente Piloto
        </h2>
        <div class="space-y-4">
           <div v-for="trip in pendingPilot" :key="trip.id" class="bg-black/40 p-5 rounded-2xl border border-white/10 cursor-pointer hover:border-amber-400/50 hover:shadow-[0_0_20px_rgba(251,191,36,0.1)] transition-all group" @click="openStep2Modal(trip)">
             <div class="flex justify-between items-start mb-3">
               <span class="text-[10px] font-black uppercase tracking-widest bg-white/5 px-2 py-1 rounded-md text-white/50 group-hover:text-amber-400 transition-colors">{{ trip.placa }}</span>
               <span class="text-xs font-black text-primary bg-primary/10 px-2 py-1 rounded-md">{{ trip.hora_planta }}</span>
             </div>
             <p class="text-base font-bold text-white mb-1">{{ trip.proyecto_nombre }}</p>
             <p class="text-[11px] text-white/40 uppercase font-bold">{{ productSummary(trip) }}</p>
             <p class="text-[10px] text-white/20 uppercase mt-2">Piloto: {{ trip.piloto_nombre }}</p>
           </div>
           <p v-if="pendingPilot.length === 0" class="text-center text-white/30 text-xs font-bold uppercase tracking-widest py-8">No hay viajes pendientes</p>
        </div>
      </div>

      <!-- Col 2: Pendiente Colocación (Paso 3) -->
      <div v-if="['admin', 'supervisor'].includes(authStore.userRole)" class="glass-card rounded-[32px] p-6 border border-white/5 relative overflow-hidden" data-aos="zoom-in-up" data-aos-duration="1000" data-aos-delay="200">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-sky-500/10 blur-3xl rounded-full pointer-events-none"></div>
        <h2 class="text-lg font-black text-white uppercase tracking-widest mb-6 flex items-center gap-3">
          <MapPinIcon class="w-6 h-6 text-sky-400" /> En Tránsito
        </h2>
        <div class="space-y-4">
           <div v-for="trip in pendingPlacement" :key="trip.id" class="bg-black/40 p-5 rounded-2xl border border-white/10 cursor-pointer hover:border-sky-400/50 hover:shadow-[0_0_20px_rgba(56,189,248,0.1)] transition-all group" @click="openStep3Modal(trip)">
             <div class="flex justify-between items-start mb-3">
               <span class="text-[10px] font-black uppercase tracking-widest bg-white/5 px-2 py-1 rounded-md text-white/50 group-hover:text-sky-400 transition-colors">{{ trip.placa }}</span>
               <span class="text-xs font-black text-sky-400 bg-sky-400/10 px-2 py-1 rounded-md">Salió: {{ trip.hora_salida }}</span>
             </div>
             <p class="text-base font-bold text-white mb-1">{{ trip.proyecto_nombre }}</p>
             <p class="text-[11px] text-white/40 uppercase font-bold">{{ productSummary(trip) }}</p>
           </div>
           <p v-if="pendingPlacement.length === 0" class="text-center text-white/30 text-xs font-bold uppercase tracking-widest py-8">No hay viajes en tránsito</p>
        </div>
      </div>

      <!-- Col 3: Completados -->
      <div v-if="['admin', 'supervisor', 'tecnico'].includes(authStore.userRole)" class="glass-card rounded-[32px] p-6 border border-white/5 relative overflow-hidden" data-aos="zoom-in-up" data-aos-duration="1000" data-aos-delay="300">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-emerald-500/10 blur-3xl rounded-full pointer-events-none"></div>
        <h2 class="text-lg font-black text-white uppercase tracking-widest mb-6 flex items-center gap-3">
          <CheckCircleIcon class="w-6 h-6 text-emerald-400" /> Completados
        </h2>
        <div class="space-y-4">
           <div v-for="trip in completedTrips" :key="trip.id"
             class="bg-black/40 p-5 rounded-2xl border border-white/5 opacity-70 hover:opacity-100 transition-all"
             :class="authStore.userRole === 'admin' ? 'cursor-pointer hover:border-emerald-400/40 hover:shadow-[0_0_20px_rgba(52,211,153,0.08)]' : ''"
             @click="authStore.userRole === 'admin' && openDetailModal(trip)">
             <div class="flex justify-between items-start mb-3">
               <span class="text-[10px] font-black uppercase tracking-widest bg-white/5 px-2 py-1 rounded-md text-white/40">{{ trip.placa }}</span>
               <span class="text-xs font-black text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded-md">Llegada: {{ trip.hora_llegada_obra }}</span>
             </div>
             <p class="text-base font-bold text-white mb-1">{{ trip.proyecto_nombre }}</p>
             <p class="text-[11px] text-white/40 uppercase font-bold">{{ trip.tipo_concreto || productSummary(trip) }}</p>
             <p v-if="authStore.userRole === 'admin'" class="text-[9px] text-emerald-400/40 uppercase font-bold mt-2 tracking-widest">Ver detalle completo →</p>
           </div>
           <p v-if="completedTrips.length === 0" class="text-center text-white/30 text-xs font-bold uppercase tracking-widest py-8">No hay viajes completados hoy</p>
        </div>
      </div>
    </div>


    <!-- ============================================== -->
    <!-- MODALS -->
    <!-- ============================================== -->

    <!-- Modal 1: Planta Concreto -->
    <transition name="fade">
      <div v-if="showModalStep1" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6">
        <div @click="closeModalStep1" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-2xl glass-card rounded-[40px] overflow-hidden border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
          <div class="p-8 md:p-10">
            <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter mb-8 flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-primary/20 flex items-center justify-center text-primary text-sm not-italic">1</span> 
              Planta Concreto
            </h3>
            
            <form @submit.prevent="submitStep1" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Proyecto -->
                <div class="md:col-span-2">
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">Proyecto</label>
                  <select v-model="formStep1.proyecto_id" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-primary transition-all font-bold">
                    <option value="" disabled>Seleccione un proyecto...</option>
                    <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                  </select>
                </div>

                <!-- Placa -->
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">Vehículo (Placa)</label>
                  <select v-model="formStep1.vehiculo_id" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-primary transition-all font-bold">
                    <option value="" disabled>Seleccione...</option>
                    <!-- Filter only operative vehicles -->
                    <option v-for="v in operativeVehicles" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca }}</option>
                  </select>
                </div>

                <!-- Piloto -->
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">Piloto</label>
                  <select v-model="formStep1.piloto_id" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-primary transition-all font-bold">
                    <option value="" disabled>Seleccione...</option>
                    <option v-for="t in technicians" :key="t.id" :value="t.id">{{ t.nombres }} {{ t.apellidos }}</option>
                  </select>
                </div>

                <!-- Productos -->
                <div class="md:col-span-2">
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-3 pl-2">Producto</label>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                      <label class="block text-[10px] font-black uppercase tracking-widest text-white/30 mb-2 pl-2">Arena (M3)</label>
                      <input type="number" step="0.01" min="0" v-model="formStep1.m3_arena" placeholder="0.00" class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-primary transition-all font-bold" />
                    </div>
                    <div>
                      <label class="block text-[10px] font-black uppercase tracking-widest text-white/30 mb-2 pl-2">Piedrín (M3)</label>
                      <input type="number" step="0.01" min="0" v-model="formStep1.m3_piedrin" placeholder="0.00" class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-primary transition-all font-bold" />
                    </div>
                    <div>
                      <label class="block text-[10px] font-black uppercase tracking-widest text-white/30 mb-2 pl-2">Cemento (Unidades)</label>
                      <input type="number" step="1" min="0" v-model="formStep1.m3_cemento" placeholder="0" class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-primary transition-all font-bold" />
                    </div>
                  </div>
                </div>

                <!-- M3 del camión -->
                <div class="md:col-span-2">
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">M3 (Capacidad del camión)</label>
                  <input type="number" step="0.01" min="0" v-model="formStep1.m3" required placeholder="0.00" class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-primary transition-all font-bold" />
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="pt-6 flex justify-end gap-4 border-t border-white/5">
                <button type="button" @click="closeModalStep1" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
                <button type="submit" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 transition-all">
                  Crear Despacho
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </transition>

    <!-- Modal 2: Piloto -->
    <transition name="fade">
      <div v-if="showModalStep2" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6">
        <div @click="closeModalStep2" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-2xl glass-card rounded-[40px] overflow-hidden border border-white/10 shadow-2xl border-amber-500/20" data-aos="zoom-in-up" data-aos-duration="1000">
          <div class="p-8 md:p-10">
            <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter mb-8 flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-amber-500/20 flex items-center justify-center text-amber-500 text-sm not-italic">2</span> 
              Formulario Piloto
            </h3>
            
            <form @submit.prevent="submitStep2" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Placa Asignada (Readonly) -->
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">Placa Asignada</label>
                  <input type="text" :value="formStep2.placa" disabled class="w-full bg-white/5 border border-white/5 rounded-2xl px-6 py-4 text-white/60 font-bold" />
                </div>
                <!-- Proyecto (Readonly) -->
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">Proyecto</label>
                  <input type="text" :value="formStep2.proyecto_nombre" disabled class="w-full bg-white/5 border border-white/5 rounded-2xl px-6 py-4 text-white/60 font-bold truncate" />
                </div>
                <!-- Productos (Readonly) -->
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">Producto / Cantidad</label>
                  <input type="text" :value="productSummary(formStep2)" disabled class="w-full bg-white/5 border border-white/5 rounded-2xl px-6 py-4 text-white/60 font-bold" />
                </div>
                <div class="hidden md:block"></div> <!-- Spacer -->

                <!-- Hora Salida -->
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">Hora Salida</label>
                  <input type="time" v-model="formStep2.hora_salida" required class="w-full bg-black/40 border border-amber-500/30 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-amber-400 transition-all font-bold" />
                </div>

                <!-- Hora Llegada -->
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">Hora Llegada</label>
                  <input type="time" v-model="formStep2.hora_llegada" required class="w-full bg-black/40 border border-amber-500/30 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-amber-400 transition-all font-bold" />
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="pt-6 flex justify-end gap-4 border-t border-white/5">
                <button type="button" @click="closeModalStep2" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
                <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-black py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-amber-500/20 transition-all">
                  Guardar y Enviar
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </transition>

    <!-- Modal 3: Colocación -->
    <transition name="fade">
      <div v-if="showModalStep3" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6">
        <div @click="closeModalStep3" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-2xl glass-card rounded-[40px] overflow-hidden border border-white/10 shadow-2xl border-sky-500/20" data-aos="zoom-in-up" data-aos-duration="1000">
          <div class="p-8 md:p-10">
            <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter mb-8 flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-sky-500/20 flex items-center justify-center text-sky-400 text-sm not-italic">3</span> 
              Encargado Colocación
            </h3>
            
            <form @submit.prevent="submitStep3" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Hora Salida Camión (Readonly) -->
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">Salida de Camión</label>
                  <input type="text" :value="formStep3.hora_salida" disabled class="w-full bg-white/5 border border-white/5 rounded-2xl px-6 py-4 text-white/60 font-bold" />
                </div>
                <!-- Proyecto (Readonly) -->
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">Proyecto</label>
                  <input type="text" :value="formStep3.proyecto_nombre" disabled class="w-full bg-white/5 border border-white/5 rounded-2xl px-6 py-4 text-white/60 font-bold truncate" />
                </div>

                <!-- Tipo Concreto -->
                <div :class="formStep3.tipo_concreto === 'Otro' ? 'md:col-span-1' : 'md:col-span-2'">
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">Tipo de Concreto</label>
                  <select v-model="formStep3.tipo_concreto" required class="w-full bg-black/40 border border-sky-500/30 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-sky-400 transition-all font-bold">
                    <option value="" disabled>Seleccione...</option>
                    <option value="Carpeta">Carpeta</option>
                    <option value="Cuneta">Cuneta</option>
                    <option value="Bordillo">Bordillo</option>
                    <option value="Otro">Otro</option>
                  </select>
                </div>

                <div v-if="formStep3.tipo_concreto === 'Otro'" class="md:col-span-1">
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">Especificar Tipo</label>
                  <input type="text" v-model="formStep3.tipo_concreto_otro" required class="w-full bg-black/40 border border-sky-500/30 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-sky-400 transition-all font-bold" placeholder="Escriba el tipo..." />
                </div>

                <!-- Hora Llegada Obra -->
                <div class="md:col-span-2">
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-2">Hora Llegada (Obra)</label>
                  <input type="time" v-model="formStep3.hora_llegada_obra" required class="w-full bg-black/40 border border-sky-500/30 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-sky-400 transition-all font-bold" />
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="pt-6 flex justify-end gap-4 border-t border-white/5">
                <button type="button" @click="closeModalStep3" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
                <button type="submit" class="bg-sky-500 hover:bg-sky-400 text-black py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-sky-500/20 transition-all">
                  Finalizar Viaje
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </transition>

    <!-- Modal Detalle Completo (solo Admin) -->
    <transition name="fade">
      <div v-if="showModalDetail" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6">
        <div @click="closeDetailModal" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-2xl glass-card rounded-[40px] overflow-hidden border border-white/10 shadow-2xl border-emerald-500/20 max-h-[90vh] flex flex-col">
          <div class="p-8 md:p-10 overflow-y-auto">
            <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter mb-2 flex items-center gap-3">
              <CheckCircleIcon class="w-7 h-7 text-emerald-400" /> Detalle del Viaje
            </h3>
            <p class="text-[10px] font-black text-white/30 uppercase tracking-widest mb-8">Registro completo · ID #{{ tripDetail?.id }}</p>

            <!-- Bloque 1: Planta -->
            <div class="mb-6">
              <div class="flex items-center gap-2 mb-4">
                <span class="w-6 h-6 rounded-lg bg-primary/20 flex items-center justify-center text-primary text-[10px] font-black">1</span>
                <span class="text-[10px] font-black uppercase tracking-widest text-primary">Planta Concreto</span>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div class="bg-black/30 rounded-2xl p-4 border border-white/5">
                  <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">Proyecto</p>
                  <p class="text-sm font-bold text-white">{{ tripDetail?.proyecto_nombre }}</p>
                </div>
                <div class="bg-black/30 rounded-2xl p-4 border border-white/5">
                  <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">Vehículo</p>
                  <p class="text-sm font-bold text-white">{{ tripDetail?.placa }}</p>
                </div>
                <div class="bg-black/30 rounded-2xl p-4 border border-white/5">
                  <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">Piloto</p>
                  <p class="text-sm font-bold text-white">{{ tripDetail?.piloto_nombre }}</p>
                </div>
                <div class="bg-black/30 rounded-2xl p-4 border border-white/5 md:col-span-2">
                  <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-2">Producto</p>
                  <div class="flex flex-wrap gap-3">
                    <span v-if="tripDetail?.m3_arena" class="text-sm font-bold text-white">Arena: <span class="text-primary">{{ tripDetail.m3_arena }} M3</span></span>
                    <span v-if="tripDetail?.m3_piedrin" class="text-sm font-bold text-white">Piedrín: <span class="text-primary">{{ tripDetail.m3_piedrin }} M3</span></span>
                    <span v-if="tripDetail?.m3_cemento" class="text-sm font-bold text-white">Cemento: <span class="text-primary">{{ tripDetail.m3_cemento }} Uds</span></span>
                    <span v-if="!tripDetail?.m3_arena && !tripDetail?.m3_piedrin && !tripDetail?.m3_cemento" class="text-sm font-bold text-white/30">—</span>
                  </div>
                </div>
                <div class="bg-black/30 rounded-2xl p-4 border border-white/5">
                  <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">M3 del Camión</p>
                  <p class="text-sm font-bold text-white">{{ tripDetail?.m3 ? tripDetail.m3 + ' M3' : '—' }}</p>
                </div>
                <div class="bg-black/30 rounded-2xl p-4 border border-white/5">
                  <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">Hora Planta</p>
                  <p class="text-sm font-bold text-white">{{ tripDetail?.hora_planta }}</p>
                </div>
                <a v-if="tripDetail?.lat_planta && tripDetail?.lng_planta"
                  :href="`https://www.google.com/maps?q=${tripDetail.lat_planta},${tripDetail.lng_planta}`"
                  target="_blank"
                  class="bg-primary/10 rounded-2xl p-4 border border-primary/20 hover:border-primary/50 transition-all group">
                  <p class="text-[9px] font-black uppercase tracking-widest text-primary/60 mb-1">GPS Planta</p>
                  <p class="text-sm font-bold text-primary group-hover:underline">Ver en mapa →</p>
                </a>
                <div v-else class="bg-black/30 rounded-2xl p-4 border border-white/5">
                  <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">GPS Planta</p>
                  <p class="text-sm font-bold text-white/30">No disponible</p>
                </div>
              </div>
            </div>

            <!-- Bloque 2: Piloto -->
            <div class="mb-6">
              <div class="flex items-center gap-2 mb-4">
                <span class="w-6 h-6 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-400 text-[10px] font-black">2</span>
                <span class="text-[10px] font-black uppercase tracking-widest text-amber-400">Formulario Piloto</span>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div class="bg-black/30 rounded-2xl p-4 border border-white/5">
                  <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">Hora Salida</p>
                  <p class="text-sm font-bold text-white">{{ tripDetail?.hora_salida }}</p>
                </div>
                <div class="bg-black/30 rounded-2xl p-4 border border-white/5">
                  <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">Hora Llegada</p>
                  <p class="text-sm font-bold text-white">{{ tripDetail?.hora_llegada }}</p>
                </div>
                <a v-if="tripDetail?.lat_piloto && tripDetail?.lng_piloto"
                  :href="`https://www.google.com/maps?q=${tripDetail.lat_piloto},${tripDetail.lng_piloto}`"
                  target="_blank"
                  class="bg-amber-500/10 rounded-2xl p-4 border border-amber-500/20 hover:border-amber-400/50 transition-all group">
                  <p class="text-[9px] font-black uppercase tracking-widest text-amber-400/60 mb-1">GPS Piloto</p>
                  <p class="text-sm font-bold text-amber-400 group-hover:underline">Ver en mapa →</p>
                </a>
                <div v-else class="bg-black/30 rounded-2xl p-4 border border-white/5">
                  <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">GPS Piloto</p>
                  <p class="text-sm font-bold text-white/30">No disponible</p>
                </div>
              </div>
            </div>

            <!-- Bloque 3: Colocación -->
            <div class="mb-8">
              <div class="flex items-center gap-2 mb-4">
                <span class="w-6 h-6 rounded-lg bg-sky-500/20 flex items-center justify-center text-sky-400 text-[10px] font-black">3</span>
                <span class="text-[10px] font-black uppercase tracking-widest text-sky-400">Encargado Colocación</span>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div class="bg-black/30 rounded-2xl p-4 border border-white/5">
                  <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">Tipo de Concreto</p>
                  <p class="text-sm font-bold text-white">{{ tripDetail?.tipo_concreto }}</p>
                </div>
                <div class="bg-black/30 rounded-2xl p-4 border border-white/5">
                  <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">Hora Llegada Obra</p>
                  <p class="text-sm font-bold text-white">{{ tripDetail?.hora_llegada_obra }}</p>
                </div>
                <a v-if="tripDetail?.lat_colocacion && tripDetail?.lng_colocacion"
                  :href="`https://www.google.com/maps?q=${tripDetail.lat_colocacion},${tripDetail.lng_colocacion}`"
                  target="_blank"
                  class="bg-sky-500/10 rounded-2xl p-4 border border-sky-500/20 hover:border-sky-400/50 transition-all group">
                  <p class="text-[9px] font-black uppercase tracking-widest text-sky-400/60 mb-1">GPS Colocación</p>
                  <p class="text-sm font-bold text-sky-400 group-hover:underline">Ver en mapa →</p>
                </a>
                <div v-else class="bg-black/30 rounded-2xl p-4 border border-white/5">
                  <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">GPS Colocación</p>
                  <p class="text-sm font-bold text-white/30">No disponible</p>
                </div>
              </div>
            </div>

            <div class="flex justify-end">
              <button @click="closeDetailModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';
import Swal from 'sweetalert2';
import { 
  PlusIcon, TruckIcon, MapPinIcon, CheckCircleIcon
} from '@heroicons/vue/24/outline';

const authStore = useAuthStore();

// REAL DATA STATE
const projects = ref([]);
const vehicles = ref([]);
const technicians = ref([]);
const trips = ref([]);

const pendingPilot = computed(() => trips.value.filter(t => parseInt(t.estado) === 2));
const pendingPlacement = computed(() => trips.value.filter(t => parseInt(t.estado) === 3));
const completedTrips = computed(() => trips.value.filter(t => parseInt(t.estado) === 4));

const operativeVehicles = computed(() => vehicles.value.filter(v => v.estatus === 'En Funcionamiento' || v.estatus === 'Nuevo'));


onMounted(() => {
  fetchProjects();
  fetchVehicles();
  fetchTechnicians();
  fetchTrips();
});

const fetchProjects = async () => {
  try {
    const res = await api.get('/projects');
    if (res.data.status === 'success') projects.value = res.data.data;
  } catch (e) { console.error(e); }
};

const fetchVehicles = async () => {
  try {
    const res = await api.get('/vehicles');
    if (res.data.success === true || res.data.status === 'success') {
      vehicles.value = res.data.data;
    }
  } catch (e) { console.error(e); }
};

const fetchTechnicians = async () => {
  try {
    const res = await api.get('/payrolls/active-personnel');
    if (res.data.success === true || res.data.status === 'success') {
      technicians.value = res.data.data.filter(p => {
        const title = ((p.puesto || '') + ' ' + (p.tipo_empleado || '')).toLowerCase();
        return title.includes('piloto') || title.includes('técnico') || title.includes('tecnico') || title.includes('operador');
      });
    }
  } catch (e) { console.error(e); }
};

const fetchTrips = async () => {
  try {
    const res = await api.get('/concrete/trips');
    if (res.data.status === 'success') trips.value = res.data.data;
  } catch (e) { console.error(e); }
};

// Modals
const showModalStep1 = ref(false);
const showModalStep2 = ref(false);
const showModalStep3 = ref(false);

const formStep1 = ref({
  proyecto_id: '',
  vehiculo_id: '',
  piloto_id: '',
  m3_arena: '',
  m3_piedrin: '',
  m3_cemento: '',
  m3: ''
});

const formStep2 = ref({
  tripId: null,
  placa: '',
  proyecto_nombre: '',
  m3_arena: null,
  m3_piedrin: null,
  m3_cemento: null,
  hora_salida: '',
  hora_llegada: ''
});

const productSummary = (obj) => {
  const parts = [];
  if (obj.m3_arena)   parts.push(`Arena ${obj.m3_arena} M3`);
  if (obj.m3_piedrin) parts.push(`Piedrín ${obj.m3_piedrin} M3`);
  if (obj.m3_cemento) parts.push(`Cemento ${obj.m3_cemento} Uds`);
  return parts.join(' · ') || '—';
};

const formStep3 = ref({
  tripId: null,
  hora_salida: '',
  proyecto_nombre: '',
  tipo_concreto: '',
  tipo_concreto_otro: '',
  hora_llegada_obra: ''
});

// Step 1: Planta
const openStep1Modal = () => {
  formStep1.value = { proyecto_id: '', vehiculo_id: '', piloto_id: '', m3_arena: '', m3_piedrin: '', m3_cemento: '', m3: '' };
  showModalStep1.value = true;
};

const closeModalStep1 = () => { showModalStep1.value = false; };

const submitStep1 = async () => {
  const arena   = parseFloat(formStep1.value.m3_arena)   || 0;
  const piedrin = parseFloat(formStep1.value.m3_piedrin) || 0;
  const cemento = parseFloat(formStep1.value.m3_cemento) || 0;

  if (arena <= 0 && piedrin <= 0 && cemento <= 0) {
    Swal.fire({ icon: 'warning', title: 'Ingresa al menos un producto', text: 'Debes ingresar una cantidad mayor a 0 en Arena, Piedrín o Cemento.', background: '#0f172a', color: '#fff' });
    return;
  }

  let lat = null;
  let lng = null;

  if (navigator.geolocation) {
    try {
      const position = await new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject);
      });
      lat = position.coords.latitude;
      lng = position.coords.longitude;
    } catch (err) {
      console.warn("No GPS data available");
    }
  }

  try {
    const res = await api.post('/concrete/trips', {
      ...formStep1.value,
      lat_planta: lat,
      lng_planta: lng
    });

    if (res.data.status === 'success') {
      Swal.fire({ icon: 'success', title: 'Despacho Creado', background: '#0f172a', color: '#fff', timer: 2000, showConfirmButton: false });
      closeModalStep1();
      fetchTrips();
    } else {
      Swal.fire({ icon: 'error', title: 'Error', text: res.data.message || 'Error al crear el despacho', background: '#0f172a', color: '#fff' });
    }
  } catch (error) {
    console.error(error);
    let msg = 'Revisa el inventario disponible.';
    if (error.response?.data) {
      msg = typeof error.response.data === 'string' ? error.response.data : (error.response.data.message || msg);
    }
    Swal.fire({
      icon: 'error',
      title: 'Error de Servidor',
      text: msg,
      background: '#0f172a',
      color: '#fff'
    });
  }
};

// Detail Modal (admin only)
const showModalDetail = ref(false);
const tripDetail = ref(null);

const openDetailModal = (trip) => {
  tripDetail.value = trip;
  showModalDetail.value = true;
};

const closeDetailModal = () => { showModalDetail.value = false; };

// Step 2: Piloto
const openStep2Modal = (trip) => {
  // Only Admin or Tecnico can open
  if (!['admin', 'tecnico'].includes(authStore.userRole)) return;

  formStep2.value = {
    tripId: trip.id,
    placa: trip.placa,
    proyecto_nombre: trip.proyecto_nombre,
    m3_arena: trip.m3_arena,
    m3_piedrin: trip.m3_piedrin,
    m3_cemento: trip.m3_cemento,
    hora_salida: '',
    hora_llegada: ''
  };
  showModalStep2.value = true;
};

const closeModalStep2 = () => { showModalStep2.value = false; };

const submitStep2 = async () => {
  let lat = null;
  let lng = null;

  if (navigator.geolocation) {
    try {
      const position = await new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject);
      });
      lat = position.coords.latitude;
      lng = position.coords.longitude;
    } catch (err) {
      console.warn("No GPS data available");
    }
  }

  try {
    const res = await api.put(`/concrete/trips/${formStep2.value.tripId}/pilot`, {
      hora_salida: formStep2.value.hora_salida,
      hora_llegada: formStep2.value.hora_llegada,
      lat_piloto: lat,
      lng_piloto: lng
    });

    if (res.data.status === 'success') {
      Swal.fire({ icon: 'success', title: 'Guardado', background: '#0f172a', color: '#fff', timer: 2000, showConfirmButton: false });
      closeModalStep2();
      fetchTrips();
    } else {
      Swal.fire({ icon: 'error', title: 'Error', text: res.data.message || 'Error al actualizar', background: '#0f172a', color: '#fff' });
    }
  } catch (error) {
    Swal.fire({ icon: 'error', title: 'Error', text: 'Error al comunicarse con el servidor', background: '#0f172a', color: '#fff' });
  }
};

// Step 3: Colocación
const openStep3Modal = (trip) => {
  // Only Admin or Supervisor can open
  if (!['admin', 'supervisor'].includes(authStore.userRole)) return;

  formStep3.value = {
    tripId: trip.id,
    hora_salida: trip.hora_salida,
    proyecto_nombre: trip.proyecto_nombre,
    tipo_concreto: '',
    tipo_concreto_otro: '',
    hora_llegada_obra: ''
  };
  showModalStep3.value = true;
};

const closeModalStep3 = () => { showModalStep3.value = false; };

const submitStep3 = async () => {
  let lat = null;
  let lng = null;

  if (navigator.geolocation) {
    try {
      const position = await new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject);
      });
      lat = position.coords.latitude;
      lng = position.coords.longitude;
    } catch (err) {
      console.warn("No GPS data available");
    }
  }

  let finalType = formStep3.value.tipo_concreto === 'Otro' ? formStep3.value.tipo_concreto_otro : formStep3.value.tipo_concreto;

  try {
    const res = await api.put(`/concrete/trips/${formStep3.value.tripId}/placement`, {
      tipo_concreto: finalType,
      hora_llegada_obra: formStep3.value.hora_llegada_obra,
      lat_colocacion: lat,
      lng_colocacion: lng
    });

    if (res.data.status === 'success') {
      Swal.fire({ icon: 'success', title: 'Viaje Completado', background: '#0f172a', color: '#fff', timer: 2000, showConfirmButton: false });
      closeModalStep3();
      fetchTrips();
    } else {
      Swal.fire({ icon: 'error', title: 'Error', text: res.data.message || 'Error al finalizar', background: '#0f172a', color: '#fff' });
    }
  } catch (error) {
    Swal.fire({ icon: 'error', title: 'Error', text: 'Error al comunicarse con el servidor', background: '#0f172a', color: '#fff' });
  }
};

</script>
