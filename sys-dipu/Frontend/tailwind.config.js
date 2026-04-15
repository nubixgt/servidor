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
                headline: ['Public Sans', 'sans-serif'],
                body: ['Inter', 'sans-serif'],
                label: ['Inter', 'sans-serif'],
            },
            colors: {
                'surface': 'var(--color-surface)',
                'surface-container-lowest': 'var(--color-surface-container-lowest)',
                'surface-container-low': 'var(--color-surface-container-low)',
                'surface-container': 'var(--color-surface-container)',
                'surface-container-high': 'var(--color-surface-container-high)',
                'surface-container-highest': 'var(--color-surface-container-highest)',
                'on-surface': 'var(--color-on-surface)',
                'on-surface-variant': 'var(--color-on-surface-variant)',
                'primary': 'var(--color-primary)',
                'primary-dim': 'var(--color-primary-dim)',
                'on-primary': 'var(--color-on-primary)',
                'primary-container': 'var(--color-primary-container)',
                'on-primary-container': 'var(--color-on-primary-container)',
                'secondary': 'var(--color-secondary)',
                'secondary-container': 'var(--color-secondary-container)',
                'on-secondary-container': 'var(--color-on-secondary-container)',
                'tertiary': 'var(--color-tertiary)',
                'tertiary-container': 'var(--color-tertiary-container)',
                'on-tertiary-container': 'var(--color-on-tertiary-container)',
                'error': 'var(--color-error)',
                'error-container': 'var(--color-error-container)',
                'on-error-container': 'var(--color-on-error-container)',
                'outline': 'var(--color-outline)',
                'outline-variant': 'var(--color-outline-variant)',
                'background': 'var(--color-surface-container-low)',
            }
        },
    },
    plugins: [],
}
