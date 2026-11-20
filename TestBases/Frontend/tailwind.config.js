/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"IBM Plex Sans"', 'system-ui', 'sans-serif'],
                mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
            },
            colors: {
                ink: {
                    900: '#121c24',
                    800: '#1b2a35',
                    700: '#2c3f4d',
                    600: '#43586a',
                    500: '#5b6c78',
                    400: '#8493a0',
                    300: '#b4bec6',
                },
                paper: '#f4f2ed',
                rule: '#dcd8ce',
                brand: {
                    50: '#eef5f2',
                    100: '#d6e8e1',
                    200: '#a9cfc2',
                    500: '#2f7d68',
                    600: '#256653',
                    700: '#1d5042',
                },
                danger: '#a8321f',
                amber: '#b7791f',
            },
        },
    },
    plugins: [],
}
