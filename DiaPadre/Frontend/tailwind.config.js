/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
                serif: ['Playfair Display', 'serif'],
                mono: ['JetBrains Mono', 'monospace'],
            },
            colors: {
                'primary': '#000a1e',
                'government-navy': '#002147',
                'error': '#ba1a1a',
                'secondary': '#255dad',
                'surface-container-lowest': '#ffffff',
                'outline-variant': '#c4c6cf',
                'on-surface-variant': '#44474e',
                'outline': '#74777f',
                'maga-blue': '#002855',
                'maga-light-blue': '#005696',
                'maga-bg-gray': '#f4f7f9',
            }
        },
    },
    plugins: [],
}
