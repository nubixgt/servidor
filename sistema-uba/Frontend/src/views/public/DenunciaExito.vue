<template>
  <div class="max-w-md mx-auto px-6 py-16 flex flex-col items-center text-center">
    <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mb-6">
      <span class="material-symbols-outlined text-green-600 text-5xl">check_circle</span>
    </div>
    <h1 class="text-2xl font-extrabold text-[#0f2a4a] mb-3">¡Denuncia enviada!</h1>
    <p class="text-[#424654] mb-8">
      Tu denuncia fue recibida correctamente. El equipo de la Unidad de Bienestar Animal la revisará a la brevedad.
    </p>
    <router-link
      to="/"
      class="bg-[#0f2a4a] text-white font-bold px-8 py-4 rounded-full hover:bg-[#1a3d6b] transition-colors"
    >
      Volver al inicio
    </router-link>
  </div>
</template>
