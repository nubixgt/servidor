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
            },
            colors: {
                'primary': {
                    500: '#18b75b',
                    600: '#0f8a45',
                },
                'forest': '#0b3d2e',
                'deep-green': '#082c22',
                'keyline-green': '#18b75b',
                'emerald': '#35d77c',
            },
        },
    },
    plugins: [],
}
