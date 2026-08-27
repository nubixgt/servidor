<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap } from '@/lib/gsap.js'

const btnRef = ref(null)
const isVisible = ref(false)
let ctx = null

const whatsappUrl = 'https://api.whatsapp.com/send?phone=50257001515&text=Hola,%20me%20interesa%20información%20sobre%20el%20Colegio%20CYD.'

const handleClick = () => {
  window.open(whatsappUrl, '_blank', 'noopener,noreferrer')
}

// Efecto magnético en el botón
const handleMouseMove = (e) => {
  const btn = btnRef.value
  if (!btn) return
  const rect = btn.getBoundingClientRect()
  const dx = e.clientX - (rect.left + rect.width / 2)
  const dy = e.clientY - (rect.top + rect.height / 2)
  const dist = Math.sqrt(dx * dx + dy * dy)
  const maxDist = 80

  if (dist < maxDist) {
    const strength = (1 - dist / maxDist) * 10
    gsap.to(btn, {
      x: dx * strength / dist,
      y: dy * strength / dist,
      duration: 0.3,
      ease: 'power2.out',
    })
  } else {
    gsap.to(btn, { x: 0, y: 0, duration: 0.5, ease: 'elastic.out(1, 0.4)' })
  }
}

const handleMouseLeave = () => {
  gsap.to(btnRef.value, { x: 0, y: 0, duration: 0.6, ease: 'elastic.out(1, 0.4)' })
}

// Mostrar solo después de scroll
const handleScroll = () => {
  isVisible.value = window.scrollY > 300
}

onMounted(() => {
  window.addEventListener('mousemove', handleMouseMove)
  window.addEventListener('scroll', handleScroll, { passive: true })

  // Entrada animada
  gsap.fromTo(btnRef.value,
    { scale: 0, opacity: 0 },
    { scale: 1, opacity: 1, duration: 0.6, ease: 'back.out(2)', delay: 2 }
  )

  ctx = gsap.context(() => {
    // Pulso periódico sutil
    gsap.to('.wa-pulse-ring', {
      scale: 1.8,
      opacity: 0,
      duration: 1.8,
      ease: 'power2.out',
      repeat: -1,
      repeatDelay: 1.5,
    })
  })
})

onUnmounted(() => {
  window.removeEventListener('mousemove', handleMouseMove)
  window.removeEventListener('scroll', handleScroll)
  ctx?.revert()
})
</script>

<template>
  <div class="fixed bottom-7 right-7 z-50">
    <!-- Tooltip -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 translate-x-3"
      enter-to-class="opacity-100 translate-x-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 translate-x-0"
      leave-to-class="opacity-0 translate-x-3"
    >
    </Transition>

    <button
      ref="btnRef"
      @click="handleClick"
      @mouseleave="handleMouseLeave"
      class="relative w-16 h-16 rounded-full flex items-center justify-center will-change-transform group overflow-hidden"
      style="
        background: linear-gradient(135deg, #25D366, #128C7E);
        box-shadow: 0 10px 35px rgba(37, 211, 102, 0.4), inset 0 2px 0 rgba(255,255,255,0.3);
      "
      aria-label="Contactar por WhatsApp"
    >
      <!-- Brillo radial premium en hover -->
      <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none" style="background: radial-gradient(circle, rgba(255,255,255,0.4) 0%, transparent 70%);"></div>

      <!-- Anillo de pulso -->
      <span
        class="wa-pulse-ring absolute inset-0 rounded-full will-change-transform"
        style="background: rgba(37,211,102,0.4); z-index: -1;"
        aria-hidden="true"
      />

      <!-- WhatsApp Oficial Premium -->
      <svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-white drop-shadow-md transition-transform duration-300 group-hover:scale-110 relative z-10">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
      </svg>
    </button>

    <!-- Label flotante -->
    <div
      class="absolute right-full mr-4 top-1/2 -translate-y-1/2 whitespace-nowrap text-xs font-semibold text-white px-3 py-2 rounded-lg pointer-events-none"
      style="background: rgba(10,26,18,0.85); backdrop-filter: blur(8px);"
    >
      ¿Necesitas ayuda?
      <!-- Flecha -->
      <div class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 rotate-45 w-2 h-2" style="background: rgba(10,26,18,0.85);" />
    </div>
  </div>
</template>
