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
                    500: '#22c55e',
                    600: '#16a34a',
                },
                'card': '#0c1e17',
                'inset': '#081611',
                'line': '#17382b',
                'accent': '#22c55e',
                'accent-light': '#4ade80',
            },
            keyframes: {
                fadeIn: {
                    from: { opacity: 0, transform: 'translateY(6px)' },
                    to: { opacity: 1, transform: 'translateY(0)' },
                },
            },
            animation: {
                fadeIn: 'fadeIn .25s ease',
            },
        },
    },
    plugins: [],
}
