/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
      colors: {
        'primary-fixed': '#caf300',
        'primary-fixed-dim': '#b0d500',
        'on-primary-fixed': '#171e00',
        'secondary-fixed': '#a5eeff',
        'secondary-fixed-dim': '#00daf8',
        'surface-container-high': '#24262b',
        'surface-container-highest': '#30333a',
        'surface-container': '#1b1c20',
        'surface-container-low': '#151619',
        'surface-container-lowest': '#0c0d0e',
        'on-surface': '#f1f0ee',
        'on-surface-variant': '#c5c9ac',
        'outline-variant': 'rgba(255, 255, 255, 0.08)',
        'outline': '#8f9378',
        'error': '#ffb4ab',
        'error-container': '#93000a',
      }
    },
  },
  plugins: [],
}
