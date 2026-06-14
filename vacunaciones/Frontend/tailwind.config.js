/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            spacing: {
                '4.5': '1.125rem',
            },
            boxShadow: {
                '2xs': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                '3xs': '0 1px 1px 0 rgba(0, 0, 0, 0.02)',
            },
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
                serif: ['Playfair Display', 'serif'],
            },
            colors: {
                'primary': {
                    500: '#3b82f6',
                    600: '#2563eb',
                }
            }
        },
    },
    plugins: [],
}
