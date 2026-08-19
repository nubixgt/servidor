<template>
  <div class="bg-keyline" aria-hidden="true">
    <svg viewBox="0 0 1600 900" preserveAspectRatio="xMidYMid slice">
      <defs>
        <linearGradient id="kl1" x1="0" x2="1" y1="0" y2="1">
          <stop offset="0%" stop-color="#0f5132" />
          <stop offset="100%" stop-color="#0b3d1f" />
        </linearGradient>
      </defs>
      <rect width="1600" height="900" fill="url(#kl1)" />
      <g stroke="rgba(255,255,255,0.055)" stroke-width="2" fill="none">
        <path d="M-100 780 C 300 700, 500 830, 900 720 S 1500 620, 1750 700" />
        <path d="M-100 700 C 300 620, 520 750, 920 640 S 1500 540, 1750 620" />
        <path d="M-100 620 C 300 540, 540 670, 940 560 S 1500 460, 1750 540" />
        <path d="M-100 540 C 300 460, 560 590, 960 480 S 1500 380, 1750 460" />
        <path d="M-100 460 C 300 380, 580 510, 980 400 S 1500 300, 1750 380" />
        <path d="M-100 380 C 300 300, 600 430, 1000 320 S 1500 220, 1750 300" />
      </g>
    </svg>
  </div>
  <router-view></router-view>
</template>

<script setup>
// Root Component
</script>
