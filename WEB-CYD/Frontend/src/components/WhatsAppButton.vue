<script setup>
import { MessageCircle } from 'lucide-vue-next'
import { ref } from 'vue'

const isHovered = ref(false)
const whatsappNumber = '50257001515'
const whatsappUrl = `https://api.whatsapp.com/send?phone=${whatsappNumber}`

const handleClick = () => {
  window.open(whatsappUrl, '_blank', 'noopener,noreferrer')
}
</script>

<template>
  <div class="fixed bottom-6 right-6 z-50">
    <button
      @click="handleClick"
      @mouseenter="isHovered = true"
      @mouseleave="isHovered = false"
      class="group relative bg-gradient-to-br from-green-500 to-green-600 text-white rounded-full p-4 shadow-2xl hover:shadow-[0_0_40px_rgba(34,197,94,0.6)] transition-all duration-300 hover:scale-110 animate-bounce hover:animate-none"
      aria-label="Contactar por WhatsApp"
    >
      <!-- Animated Rings -->
      <div class="absolute inset-0 rounded-full bg-green-400 animate-ping opacity-30"></div>
      <div class="absolute inset-0 rounded-full bg-green-400 animate-pulse"></div>
      
      <!-- Icon -->
      <div class="relative">
        <MessageCircle 
          class="w-8 h-8 transition-transform duration-300 group-hover:rotate-12" 
          :strokeWidth="2.5"
        />
      </div>

      <!-- Tooltip -->
      <div 
        :class="`absolute right-full mr-4 top-1/2 -translate-y-1/2 whitespace-nowrap bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-xl transition-all duration-300 ${
          isHovered ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-2 pointer-events-none'
        }`"
      >
        ¿Necesitas ayuda? ¡Escríbenos!
        <div class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 rotate-45 w-2 h-2 bg-gray-900"></div>
      </div>

      <!-- Pulse Effect -->
      <div class="absolute inset-0 rounded-full bg-green-400 opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
    </button>
  </div>
</template>
