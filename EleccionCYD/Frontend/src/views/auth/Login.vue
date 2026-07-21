<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { LockClosedIcon } from '@heroicons/vue/24/outline';

const router = useRouter();

const judgeId = ref('JUDGE-04');
const accessCode = ref('PARIS2024');
const isAuthenticating = ref(false);
const error = ref('');

const handleSubmit = () => {
  if (!judgeId.value.trim() || !accessCode.value.trim()) {
    error.value = 'Please fill in all fields.';
    return;
  }
  error.value = '';
  isAuthenticating.value = true;

  setTimeout(() => {
    isAuthenticating.value = false;
    router.push({ name: 'ModelDirectory' });
  }, 1200);
};
</script>

<template>
  <div class="relative min-h-screen w-full flex flex-col items-center justify-center overflow-hidden font-sans select-none selection:bg-black selection:text-white">
    <!-- Background Wrapper -->
    <div class="absolute inset-0 w-full h-full z-0">
      <div 
        class="w-full h-full bg-cover bg-center brightness-90 scale-105"
        style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuCDJeQAA-guOjP4reFjlb6XiXKFZxhzk6rU-8FB2X1PBiO7WqVil1cB07rqW0k_ybTgsNfvsg8q1kPvL6FhTt41XVGj0tqVjd8sI5fSrE91oBrLI2ePtoo-rgD2UWF18V_Nt6STP5WZwiymABkEA3jbOYUCGm9Z_wFW9L40P05_B62HlnPhx5Zm2cLRis-kbXCTF9t3afjAIKGoAi3Qkit4FqqC-aixGiXPDnusQ3uznkYzDx3MSua9')"
      ></div>
      <div class="absolute inset-0 bg-white/40 backdrop-blur-[2px]"></div>
    </div>

    <!-- Main Content -->
    <main class="relative z-10 flex flex-col items-center justify-center px-6 md:px-16 py-12 w-full max-w-lg">
      <!-- Header Branding -->
      <Transition appear name="fade-down" style="transition-delay: 0.1s">
        <div class="mb-12 text-center">
          <h1 class="font-serif text-3xl md:text-4xl tracking-[0.25em] text-black font-normal uppercase">
            AURA FASHION WEEK
          </h1>
          <p class="text-xs text-gray-500 mt-2 tracking-[0.3em] font-semibold uppercase">
            JUDICIARY PORTAL
          </p>
        </div>
      </Transition>

      <!-- Central Login Card -->
      <Transition appear name="fade-up" style="transition-delay: 0.3s">
        <div class="w-full bg-white border border-black/5 p-8 md:p-12 shadow-[0_0_50px_rgba(0,0,0,0.04)] rounded-none">
          <header class="mb-10">
            <h2 class="text-3xl text-black font-light tracking-tight mb-2">Login</h2>
            <p class="text-gray-500 text-sm">Please authenticate to access the judging dashboard.</p>
          </header>

          <form @submit.prevent="handleSubmit" class="space-y-8">
            <!-- Judge ID Field -->
            <div class="relative group">
              <label 
                for="judge_id" 
                class="text-[10px] text-gray-400 font-semibold uppercase tracking-[0.15em] mb-1 block group-focus-within:text-black group-focus-within:tracking-[0.2em] transition-all duration-300"
              >
                Judge ID
              </label>
              <input 
                id="judge_id"
                type="text"
                placeholder="Enter identification number"
                v-model="judgeId"
                required
                class="w-full bg-transparent border-t-0 border-x-0 border-b border-gray-300 py-3 px-0 text-sm text-black focus:ring-0 focus:border-black transition-all duration-300 rounded-none placeholder:text-gray-300"
              />
            </div>

            <!-- Access Code Field -->
            <div class="relative group">
              <div class="flex justify-between items-end mb-1">
                <label 
                  for="access_code" 
                  class="text-[10px] text-gray-400 font-semibold uppercase tracking-[0.15em] group-focus-within:text-black group-focus-within:tracking-[0.2em] transition-all duration-300"
                >
                  Access Code
                </label>
                <button 
                  type="button"
                  @click="() => alert('Access code has been sent to your official device. (Default is PARIS2024)')"
                  class="text-[9px] text-gray-400 hover:text-black transition-colors tracking-wider uppercase"
                >
                  Forgot Code?
                </button>
              </div>
              <input 
                id="access_code"
                type="password"
                placeholder="••••••••"
                v-model="accessCode"
                required
                class="w-full bg-transparent border-t-0 border-x-0 border-b border-gray-300 py-3 px-0 text-sm text-black focus:ring-0 focus:border-black transition-all duration-300 rounded-none placeholder:text-gray-300"
              />
            </div>

            <p v-if="error" class="text-red-600 text-xs tracking-wide">{{ error }}</p>

            <!-- Action Button -->
            <div class="pt-4">
              <button 
                type="submit"
                :disabled="isAuthenticating"
                class="w-full bg-black text-white py-4 text-xs font-semibold tracking-[0.25em] uppercase hover:bg-neutral-800 active:scale-[0.99] transition-all duration-300 rounded-none cursor-pointer disabled:opacity-75"
              >
                {{ isAuthenticating ? 'Authenticating...' : 'Sign In' }}
              </button>
            </div>
          </form>

          <footer class="mt-12 text-center">
            <div class="flex items-center justify-center gap-2 text-gray-400">
              <LockClosedIcon class="w-3.5 h-3.5 opacity-80" />
              <span class="text-[10px] uppercase tracking-[0.2em]">Secure Judging Environment</span>
            </div>
          </footer>
        </div>
      </Transition>

      <!-- Footer Decoration -->
      <Transition appear name="fade-in" style="transition-delay: 0.5s">
        <div class="mt-12 flex items-center gap-4 opacity-60">
          <span class="w-8 h-[1px] bg-gray-300"></span>
          <span class="text-[10px] text-gray-500 uppercase tracking-[0.25em]">Paris Collection 2024</span>
          <span class="w-8 h-[1px] bg-gray-300"></span>
        </div>
      </Transition>
    </main>

    <!-- Subtle Grain Overlay for Texture -->
    <div class="fixed inset-0 pointer-events-none opacity-[0.025] z-50" style="background-image: url('https://grainy-gradients.vercel.app/noise.svg')"></div>
  </div>
</template>

<style scoped>
.fade-down-enter-active,
.fade-up-enter-active,
.fade-in-enter-active {
  transition: all 0.8s ease;
}

.fade-down-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}
.fade-down-enter-to {
  opacity: 1;
  transform: translateY(0);
}

.fade-up-enter-from {
  opacity: 0;
  transform: translateY(15px);
}
.fade-up-enter-to {
  opacity: 1;
  transform: translateY(0);
}

.fade-in-enter-from {
  opacity: 0;
}
.fade-in-enter-to {
  opacity: 0.6;
}
</style>
