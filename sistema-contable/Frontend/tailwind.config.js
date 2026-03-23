/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Manrope', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                body: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            colors: {
                'primary': 'var(--color-primary)',
                'secondary': 'var(--color-secondary)',
                'error': 'var(--color-error)',
                'surface': 'var(--color-surface)',
                'surface-container-lowest': 'var(--color-surface-container-lowest)',
                'surface-container-low': 'var(--color-surface-container-low)',
                'surface-container': 'var(--color-surface-container)',
                'surface-container-high': 'var(--color-surface-container-high)',
                'surface-container-highest': 'var(--color-surface-container-highest)',
                'on-surface': 'var(--color-on-surface)',
                'on-surface-variant': 'var(--color-on-surface-variant)',
                'outline': 'var(--color-outline)',
                'outline-variant': 'var(--color-outline-variant)',
                'primary-container': 'var(--color-primary-container)',
                'on-primary-container': 'var(--color-on-primary-container)',
                'primary-fixed': 'var(--color-primary-fixed)',
                'primary-fixed-dim': 'var(--color-primary-fixed-dim)',
                'on-primary-fixed': 'var(--color-on-primary-fixed)',
                'secondary-container': 'var(--color-secondary-container)',
                'on-secondary-container': 'var(--color-on-secondary-container)',
                'secondary-fixed': 'var(--color-secondary-fixed)',
                'secondary-fixed-dim': 'var(--color-secondary-fixed-dim)',
                'error-container': 'var(--color-error-container)',
                'on-error-container': 'var(--color-on-error-container)',
                'tertiary': 'var(--color-tertiary)',
                'tertiary-fixed-dim': 'var(--color-tertiary-fixed-dim)',
            }
        },
    },
    plugins: [],
}
