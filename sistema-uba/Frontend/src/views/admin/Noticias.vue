<script setup>
</script>

<template>
  <div class="animate-in fade-in duration-500 space-y-8">
    <!-- Header Section -->
    <div class="flex justify-between items-end mb-10">
      <div>
        <h2 class="text-4xl font-headline font-extrabold tracking-tight text-on-surface mb-2">Gestión de Noticias</h2>
        <p class="text-on-surface-variant font-body">Administra el contenido informativo para la ciudadanía y bienestar animal.</p>
      </div>
      <button class="bg-gradient-to-br from-primary to-primary-container text-white px-6 py-3 rounded-full font-headline font-bold flex items-center gap-2 shadow-lg hover:shadow-primary/20 hover:scale-105 transition-all">
        <span class="material-symbols-outlined">add</span>
        <span>Crear Post</span>
      </button>
    </div>

    <!-- Stats/Categories Bento Grid -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
      <div class="glass-card p-4 rounded-xl flex flex-col justify-between">
        <span class="text-xs font-bold text-primary opacity-60 uppercase tracking-widest">Campaña</span>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-2xl font-extrabold">12</span>
          <span class="text-xs text-tertiary-container font-bold">+2 este mes</span>
        </div>
      </div>
      <div class="glass-card p-4 rounded-xl flex flex-col justify-between">
        <span class="text-xs font-bold text-secondary opacity-60 uppercase tracking-widest">Rescate</span>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-2xl font-extrabold">45</span>
          <span class="material-symbols-outlined text-sm text-secondary" style="font-variation-settings: 'FILL' 1">pets</span>
        </div>
      </div>
      <div class="glass-card p-4 rounded-xl flex flex-col justify-between">
        <span class="text-xs font-bold text-on-surface-variant opacity-60 uppercase tracking-widest">Legislación</span>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-2xl font-extrabold">08</span>
        </div>
      </div>
      <div class="glass-card p-4 rounded-xl flex flex-col justify-between border-l-4 border-error">
        <span class="text-xs font-bold text-error opacity-60 uppercase tracking-widest">Alerta</span>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-2xl font-extrabold">03</span>
          <span class="animate-pulse w-2 h-2 rounded-full bg-error"></span>
        </div>
      </div>
      <div class="glass-card p-4 rounded-xl flex flex-col justify-between">
        <span class="text-xs font-bold text-tertiary-container opacity-60 uppercase tracking-widest">Evento</span>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-2xl font-extrabold">19</span>
        </div>
      </div>
    </div>

    <!-- News List Section -->
    <div class="space-y-4">
      <div class="flex items-center justify-between px-4 mb-2">
        <h3 class="font-headline font-bold text-lg">Últimas Publicaciones</h3>
        <div class="flex gap-2">
          <button class="px-3 py-1 rounded-full bg-surface-container-high text-xs font-bold">Todos</button>
          <button class="px-3 py-1 rounded-full hover:bg-surface-container-high text-xs font-medium transition-colors">Publicados</button>
          <button class="px-3 py-1 rounded-full hover:bg-surface-container-high text-xs font-medium transition-colors">Borradores</button>
        </div>
      </div>

      <!-- News Item 1 -->
      <div class="glass-card p-4 rounded-2xl flex items-center gap-6 group hover:bg-white transition-all cursor-pointer">
        <img alt="Campaña de vacunación" class="w-32 h-24 rounded-xl object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBgJHis6jPzSd-mxkAQa7GbY13MBJEUkldE1n5LdBDDTrgKBwDSk4zPvfQl6fNNlotO1vxg4ZVjB9zhFk6YXNK_y04TTr7USZSSclqHeaKHp8n1neWDRwV90AKyw9MMLyn7h44v0abAdChI0H990nKW2sH3AHZ2dNCMtuzANutkWUMFDc-3HdMaXyt5eSeMfVmp8-PBac-zIhMecYPZFotVNWhawXtM1NKgUrbWepeEtVojmvVdnv2s1iKlle1wRFmruf326NjnUpH2" />
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-1">
            <span class="px-2 py-0.5 rounded-md bg-primary-fixed text-on-primary-fixed text-[10px] font-bold uppercase tracking-wider">Campaña</span>
            <span class="text-xs text-on-surface-variant">Publicado el 15 Oct, 2023</span>
          </div>
          <h4 class="text-lg font-headline font-bold group-hover:text-primary transition-colors">Gran jornada de vacunación antirrábica en Mixco</h4>
          <p class="text-sm text-on-surface-variant line-clamp-1">Se espera atender a más de 500 mascotas durante el fin de semana en el parque central...</p>
        </div>
        <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity pr-4">
          <button class="p-2 rounded-full hover:bg-surface-container-high text-on-surface-variant"><span class="material-symbols-outlined">edit</span></button>
          <button class="p-2 rounded-full hover:bg-error-container hover:text-error text-on-surface-variant"><span class="material-symbols-outlined">delete</span></button>
        </div>
      </div>

      <!-- News Item 2 -->
      <div class="glass-card p-4 rounded-2xl flex items-center gap-6 group hover:bg-white transition-all cursor-pointer">
        <img alt="Alerta de rescate" class="w-32 h-24 rounded-xl object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCFCQcLLq06tUviYzvGrnq8HbW_eAg-dZCkWm7kEnoTbAczPzRuMIObcKyIw3XJzCEjmbe5ARgOIfDWfsPPKowHjFuNEKOjio6d51q-G4TRk5f5czDQ173CTpqdbEGzbzSF7r5XDw8LxBxln08-BuEPvCFShWceVMSMBsXKSfnPt_RSjmJAIAkixjJJNCk0wCOrqm6cGAnD_63wR4NBnYm5b5FlMEjcwXTIygP83qYQG-YQz64Ka5kn-R-3U_rYhB2FrNUKTc_5RORn" />
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-1">
            <span class="px-2 py-0.5 rounded-md bg-error-container text-on-error-container text-[10px] font-bold uppercase tracking-wider">Alerta</span>
            <span class="text-xs text-on-surface-variant">Publicado hace 2 horas</span>
          </div>
          <h4 class="text-lg font-headline font-bold group-hover:text-primary transition-colors">Protocolo de emergencia por inundaciones en Escuintla</h4>
          <p class="text-sm text-on-surface-variant line-clamp-1">Instrucciones claras para el resguardo de animales de granja y domésticos ante la crecida de ríos...</p>
        </div>
        <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity pr-4">
          <button class="p-2 rounded-full hover:bg-surface-container-high text-on-surface-variant"><span class="material-symbols-outlined">edit</span></button>
          <button class="p-2 rounded-full hover:bg-error-container hover:text-error text-on-surface-variant"><span class="material-symbols-outlined">delete</span></button>
        </div>
      </div>

      <!-- News Item 3 -->
      <div class="glass-card p-4 rounded-2xl flex items-center gap-6 group hover:bg-white transition-all cursor-pointer">
        <img alt="Nueva legislación" class="w-32 h-24 rounded-xl object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCiZkX1hahTkU0TYPAytazssJbWBHJQmyRxpxMmKx6FVM1WiFCphqe5Km_g1FWAY1hjc1X_P7764JzgYYEVuT9kZXuu-SBl0dWRQtt1_J-rh3QOu4aMEDDbaRjqYtYHpcBmZUk8JDtl8d1OIiMs2v8CKKqL1D_rhU0TMzRLATQQIRoCpqN4tPcFNLWMSKUDs9XdGAElB8lzIotfdKUB196MmKh3C-2aCU0Ar_AB0imLBi3SL1CV1_JjM2d9-f8P4skvx2TNkcAJ1NjC" />
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-1">
            <span class="px-2 py-0.5 rounded-md bg-secondary-container text-on-secondary-fixed-variant text-[10px] font-bold uppercase tracking-wider">Legislación</span>
            <span class="text-xs text-on-surface-variant">Publicado el 10 Oct, 2023</span>
          </div>
          <h4 class="text-lg font-headline font-bold group-hover:text-primary transition-colors">Actualización de multas por maltrato animal</h4>
          <p class="text-sm text-on-surface-variant line-clamp-1">El nuevo decreto entrará en vigencia a partir del próximo mes, endureciendo las sanciones...</p>
        </div>
        <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity pr-4">
          <button class="p-2 rounded-full hover:bg-surface-container-high text-on-surface-variant"><span class="material-symbols-outlined">edit</span></button>
          <button class="p-2 rounded-full hover:bg-error-container hover:text-error text-on-surface-variant"><span class="material-symbols-outlined">delete</span></button>
        </div>
      </div>

      <!-- News Item 4 -->
      <div class="glass-card p-4 rounded-2xl flex items-center gap-6 group hover:bg-white transition-all cursor-pointer border-l-4 border-tertiary">
        <img alt="Evento adopción" class="w-32 h-24 rounded-xl object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAEZekTGrULc5_CJViWQg-xtWOF0PwljEyIkNHa6gJ2PMhUG6W8MbMA4YOa5fxhZJ3c6J-IOz23sSslebBIUXwMg-TApPgX7wlSmXZtN0T_RKiFTymidHvjMp-c-LCItjh0ilK3Mnrv0IzMKxSVRAbMGGLPt3_2m4mkls1ZN9H_meRKGJiR8oGrsWgxWe-Wcg_O_xXMPw-ovLXw-SzAlUiwykhvyZh0eW9PTX-Q5Mibecz6Frga3IpF_5zN5Bmmuzq3_dxx0-u9XM-g" />
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-1">
            <span class="px-2 py-0.5 rounded-md bg-tertiary-fixed text-on-tertiary-fixed-variant text-[10px] font-bold uppercase tracking-wider">Evento</span>
            <span class="text-xs text-on-surface-variant">Programado para el 20 Oct</span>
          </div>
          <h4 class="text-lg font-headline font-bold group-hover:text-primary transition-colors">Festival de Adopción "Segunda Oportunidad"</h4>
          <p class="text-sm text-on-surface-variant line-clamp-1">Acompáñanos este domingo en Pasos y Pedales para conocer a tu nuevo mejor amigo...</p>
        </div>
        <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity pr-4">
          <button class="p-2 rounded-full hover:bg-surface-container-high text-on-surface-variant"><span class="material-symbols-outlined">edit</span></button>
          <button class="p-2 rounded-full hover:bg-error-container hover:text-error text-on-surface-variant"><span class="material-symbols-outlined">delete</span></button>
        </div>
      </div>
    </div>

    <!-- Footer Pagination -->
    <div class="mt-8 flex justify-center">
      <nav class="flex items-center gap-2 bg-surface-container-low p-2 rounded-full">
        <button class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-container-high transition-colors">
          <span class="material-symbols-outlined text-sm">chevron_left</span>
        </button>
        <button class="w-8 h-8 flex items-center justify-center rounded-full bg-primary text-white text-xs font-bold">1</button>
        <button class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-container-high text-xs font-medium transition-colors">2</button>
        <button class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-container-high text-xs font-medium transition-colors">3</button>
        <button class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-container-high transition-colors">
          <span class="material-symbols-outlined text-sm">chevron_right</span>
        </button>
      </nav>
    </div>
  </div>
</template>
