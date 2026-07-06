/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ["Inter", "sans-serif"],
                brand: ["Outfit", "sans-serif"],
                logo: ["Montserrat", "sans-serif"],
            },
            colors: {
                brand: "#047857",
                primary: "#047857",
                "primary-light": "#10b981",
                "primary-dark": "#064e3b",
                secondary: "#d97706",
                "secondary-light": "#f59e0b",
                "secondary-dark": "#b45309",
                success: "#059669",
                "brand-dark": "#022c22",
                "brand-primary": "#047857",
                "deep-navy": "#06090e",
                "midnight": "#0f172a",
            },
            animation: {
                'blob':           'blob 10s infinite',
                'fade-in':        'fadeIn 0.5s ease-out forwards',
                'gradient-slow':  'gradient 15s ease infinite',
                'pulse-slow':     'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'float':          'float 4s ease-in-out infinite',
                'slide-up':       'slideUp 0.5s cubic-bezier(0.16,1,0.3,1) forwards',
                'slide-down':     'slideDown 0.4s cubic-bezier(0.16,1,0.3,1) forwards',
                'scale-in':       'scaleIn 0.35s cubic-bezier(0.16,1,0.3,1) forwards',
                'shimmer':        'shimmer 2s linear infinite',
                'glow-pulse':     'glowPulse 2.5s ease-in-out infinite',
                'bounce-in':      'bounceIn 0.6s cubic-bezier(0.34,1.56,0.64,1) forwards',
                'spin-slow':      'spin 8s linear infinite',
            },
            keyframes: {
                blob: {
                    '0%':   { transform: 'translate(0px,0px) scale(1)' },
                    '33%':  { transform: 'translate(30px,-50px) scale(1.1)' },
                    '66%':  { transform: 'translate(-20px,20px) scale(0.9)' },
                    '100%': { transform: 'translate(0px,0px) scale(1)' },
                },
                fadeIn: {
                    '0%':   { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%':      { transform: 'translateY(-10px)' },
                },
                gradient: {
                    '0%':   { 'background-position': '0% 50%' },
                    '50%':  { 'background-position': '100% 50%' },
                    '100%': { 'background-position': '0% 50%' },
                },
                slideUp: {
                    '0%':   { opacity: '0', transform: 'translateY(24px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideDown: {
                    '0%':   { opacity: '0', transform: 'translateY(-12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                scaleIn: {
                    '0%':   { opacity: '0', transform: 'scale(0.92)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                shimmer: {
                    '0%':   { 'background-position': '-200% 0' },
                    '100%': { 'background-position': '200% 0' },
                },
                glowPulse: {
                    '0%, 100%': { 'box-shadow': '0 0 0 0 rgba(16,185,129,0)' },
                    '50%':      { 'box-shadow': '0 0 20px 4px rgba(16,185,129,0.25)' },
                },
                bounceIn: {
                    '0%':   { opacity: '0', transform: 'scale(0.8)' },
                    '60%':  { opacity: '1', transform: 'scale(1.05)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
            }
        },
    },
    plugins: [],
}
