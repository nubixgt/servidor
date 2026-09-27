/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                serif: ['Playfair Display', 'serif'],
            },
            colors: {
                brand: {
                    forest: '#1E4D3A',
                    'forest-dark': '#14382A',
                    'forest-hover': '#184232',
                    emerald: '#27AE60',
                    accent: '#B5337A',
                    sage: '#EBF4F0',
                    sand: '#F7F8F6',
                    dark: '#111827',
                },
                primary: {
                    500: '#3b82f6',
                    600: '#2563eb',
                }
            }
        },
    },
    plugins: [],
}
