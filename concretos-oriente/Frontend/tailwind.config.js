/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            colors: {
                'surface': 'rgba(11, 28, 48, 0.4)',
                'on-surface': '#ffffff',
                'on-surface-variant': 'rgba(255, 255, 255, 0.7)',
                'primary': '#6366f1',
                'primary-container': 'rgba(99, 102, 241, 0.2)',
                'tertiary': '#f43f5e',
                'tertiary-container': 'rgba(244, 63, 94, 0.2)',
                'error': '#ef4444',
            }
        },
    },
    plugins: [],
}
