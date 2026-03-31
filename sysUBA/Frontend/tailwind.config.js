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
                headline: ['Manrope', 'sans-serif'],
                body: ['Inter', 'sans-serif']
            },
            colors: {
                primary: 'var(--color-primary)',
                'primary-container': 'var(--color-primary-container)',
                'on-primary': 'var(--color-on-primary)',
                'primary-fixed': 'var(--color-primary-fixed)',
                'on-primary-fixed': 'var(--color-on-primary-fixed)',
                
                secondary: 'var(--color-secondary)',
                'secondary-container': 'var(--color-secondary-container)',
                'on-secondary': 'var(--color-on-secondary)',
                'secondary-fixed': 'var(--color-secondary-fixed)',
                'on-secondary-fixed': 'var(--color-on-secondary-fixed)',
                
                tertiary: 'var(--color-tertiary)',
                'tertiary-container': 'var(--color-tertiary-container)',
                'on-tertiary': 'var(--color-on-tertiary)',
                'tertiary-fixed': 'var(--color-tertiary-fixed)',
                'on-tertiary-fixed': 'var(--color-on-tertiary-fixed)',
                
                error: 'var(--color-error)',
                'error-container': 'var(--color-error-container)',
                'on-error': 'var(--color-on-error)',
                'on-error-container': 'var(--color-on-error-container)',
                
                surface: 'var(--color-surface)',
                'surface-dim': 'var(--color-surface-dim)',
                'surface-bright': 'var(--color-surface-bright)',
                'surface-container-lowest': 'var(--color-surface-container-lowest)',
                'surface-container-low': 'var(--color-surface-container-low)',
                'surface-container': 'var(--color-surface-container)',
                'surface-container-high': 'var(--color-surface-container-high)',
                'surface-container-highest': 'var(--color-surface-container-highest)',
                
                'on-surface': 'var(--color-on-surface)',
                'on-surface-variant': 'var(--color-on-surface-variant)',
                outline: 'var(--color-outline)',
                'outline-variant': 'var(--color-outline-variant)',
            }
        },
    },
    plugins: [],
}
