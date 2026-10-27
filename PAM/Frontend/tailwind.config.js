/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    // El diseño trae su propio reset y tokens (src/assets/css/app.css)
    corePlugins: {
        preflight: false,
    },
    theme: {
        extend: {
            fontFamily: {
                sans: ['IBM Plex Sans', 'sans-serif'],
                serif: ['Montserrat', 'sans-serif'],
            },
            colors: {
                'primary': {
                    500: '#2E6FD1',
                    600: '#1450A3',
                }
            }
        },
    },
    plugins: [],
}
