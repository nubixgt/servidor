<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap } from '@/lib/gsap.js'

const sectionRef = ref(null)
let ctx = null

onMounted(() => {
  ctx = gsap.context(() => {
    gsap.from('.app-promo-inner > *', {
      scrollTrigger: { trigger: sectionRef.value, start: 'top 82%' },
      y: 24, opacity: 0, duration: 0.65, stagger: 0.12, ease: 'power3.out'
    })
  }, sectionRef.value)
})

onUnmounted(() => { ctx?.revert() })
</script>

<template>
  <section ref="sectionRef" id="app" class="py-8 lg:py-10 bg-[#f8fafc]">
    <div class="cyd-container">
      <div
        class="relative rounded-[2rem] overflow-hidden"
        style="background: linear-gradient(120deg, #eef7f9 0%, #f0faf8 50%, #f4fbff 100%); border: 1px solid rgba(200,230,230,0.5);"
      >
        <!-- Decoración de fondo -->
        <div class="absolute top-0 right-0 w-72 h-72 rounded-full opacity-30 -translate-y-1/2 translate-x-1/4" style="background: radial-gradient(circle, #bae6fd, transparent);"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full opacity-20 translate-y-1/2 -translate-x-1/4" style="background: radial-gradient(circle, #bbf7d0, transparent);"></div>

        <div class="app-promo-inner relative z-10 grid sm:grid-cols-12 gap-6 lg:gap-8 items-center px-8 sm:px-10 lg:px-14 py-10 lg:py-12">

          <!-- Mockup del teléfono -->
          <div class="sm:col-span-3 flex justify-center">
            <div
              class="relative rounded-[2.2rem] overflow-hidden"
              style="width: 140px; height: 280px; background: #0f172a; border: 6px solid #1e293b; box-shadow: 0 20px 60px rgba(0,0,0,0.25), inset 0 1px 0 rgba(255,255,255,0.1);"
            >
              <!-- Notch -->
              <div class="absolute top-0 left-1/2 -translate-x-1/2 w-20 h-5 bg-[#0f172a] rounded-b-2xl z-20"></div>
              <!-- Pantalla de la app -->
              <div class="w-full h-full bg-white flex flex-col">
                <!-- Header de la app -->
                <div class="w-full flex items-center justify-center pt-6 pb-3" style="background: linear-gradient(180deg, #164627 0%, #1e5c33 100%);">
                  <img src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/LOGO-2020-1761860820111.png" alt="Logo CYD" class="w-12 h-12 object-contain" />
                </div>
                <!-- Contenido de la app simulado -->
                <div class="flex-1 p-3 flex flex-col gap-2">
                  <div class="w-full h-2 rounded-full bg-slate-200"></div>
                  <div class="w-3/4 h-2 rounded-full bg-slate-100"></div>
                  <div class="grid grid-cols-2 gap-2 mt-2 flex-1">
                    <div class="rounded-xl bg-green-50 h-full"></div>
                    <div class="rounded-xl bg-blue-50 h-full"></div>
                    <div class="rounded-xl bg-yellow-50 h-full"></div>
                    <div class="rounded-xl bg-red-50 h-full"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Texto central -->
          <div class="sm:col-span-5">
            <h2 class="font-black text-slate-800 leading-tight mb-3" style="font-family: var(--font-display); font-size: clamp(1.6rem, 3vw, 2.2rem);">
              Lleva tu colegio<br class="hidden lg:block"/> siempre contigo
            </h2>
            <p class="text-slate-500 font-medium leading-relaxed">
              Descarga la APP CYD y accede a calificaciones, tareas, comunicados y mucho más.
            </p>
          </div>

          <!-- Botones de descarga -->
          <div class="sm:col-span-4 flex flex-col sm:flex-row lg:flex-col xl:flex-row gap-4 justify-center lg:justify-end">

            <!-- Google Play -->
            <div class="flex items-center gap-3 bg-white rounded-2xl p-4 shadow-sm border border-slate-200 cursor-pointer hover:shadow-md transition-shadow min-w-[170px]">
              <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M3.18 23.76c.34.18.72.21 1.08.1l12.35-7.12-2.63-2.64zm14.79-9.79L5.41.76C5.06.6 4.68.59 4.34.73L13.7 10.1zm3-2.04l-3.12-1.8-2.93 2.94 2.92 2.93 3.13-1.8c.9-.52.9-1.75 0-2.27zM4.34 2.24a1.5 1.5 0 0 0-.16.71v18.1c0 .25.06.5.16.7l.1.1L13.7 12v-.22z"/></svg>
              </div>
              <div>
                <div class="text-[0.6rem] font-bold text-slate-400 uppercase tracking-wider leading-none mb-0.5">Disponible en</div>
                <div class="font-black text-slate-800 leading-tight">Google Play</div>
              </div>
            </div>

            <!-- App Store -->
            <div class="flex items-center gap-3 bg-white rounded-2xl p-4 shadow-sm border border-slate-200 cursor-pointer hover:shadow-md transition-shadow min-w-[170px]">
              <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M15.111 12.012c.018-2.673 2.186-3.947 2.285-4.01-1.246-1.821-3.18-2.072-3.87-2.112-1.644-.165-3.21.968-4.048.968-.84 0-2.12-.953-3.486-.928-1.782.023-3.424 1.037-4.34 2.632-1.85 3.208-.473 7.962 1.332 10.573.882 1.272 1.93 2.7 3.292 2.646 1.312-.055 1.822-.851 3.414-.851 1.573 0 2.046.851 3.432.825 1.405-.027 2.316-1.293 3.195-2.56 1.02-1.488 1.442-2.929 1.464-3.003-.032-.014-2.687-1.026-2.67-3.18m-2.176-6.666c.712-.862 1.192-2.062 1.061-3.255-1.024.042-2.274.682-3.004 1.543-.583.684-1.157 1.905-1.006 3.076 1.144.089 2.234-.501 2.949-1.364"/></svg>
              </div>
              <div>
                <div class="text-[0.6rem] font-bold text-slate-400 uppercase tracking-wider leading-none mb-0.5">Disponible en</div>
                <div class="font-black text-slate-800 leading-tight">App Store</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
</style>
