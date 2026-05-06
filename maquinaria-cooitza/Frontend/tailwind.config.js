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
                display: ['Space Grotesk', 'sans-serif'],
                'mono-label': ['Space Grotesk', 'sans-serif'],
            },
            colors: {
                'primary': '#0054A3',
                'primary-container': '#FFD200',
                'on-primary': '#ffffff',
                'on-primary-container': '#002d58',
                'surface': '#f8f9fa',
                'surface-container': '#edeeef',
                'surface-container-high': '#e1e2e3',
                'surface-container-low': '#f3f4f5',
                'surface-container-lowest': '#ffffff',
                'on-surface': '#191c1d',
                'on-surface-variant': '#004586',
                'outline': '#0054A3',
                'outline-variant': '#cbd5e1',
            },
            borderRadius: {
                'sm': '0.125rem',
                'lg': '0.25rem',
                'xl': '0.5rem',
            }
        },
    },
    plugins: [],
}
