/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "tertiary-container": "#8fa5d6",
                "secondary-fixed": "#ffdeaa",
                "surface-container": "#20201f",
                "surface-variant": "#353535",
                "tertiary-fixed-dim": "#b0c6f9",
                "surface-container-high": "#2a2a2a",
                "inverse-surface": "#e5e2e1",
                "primary-container": "#c5a059",
                "surface": "#131313",
                "primary-fixed-dim": "#e9c176",
                "secondary-container": "#614405",
                "on-tertiary-container": "#233a65",
                "secondary": "#ebc078",
                "secondary-fixed-dim": "#ebc078",
                "on-surface": "#e5e2e1",
                "on-primary-fixed": "#261900",
                "on-primary-container": "#4e3700",
                "primary-fixed": "#ffdea5",
                "on-tertiary-fixed": "#001a41",
                "on-secondary-fixed": "#271900",
                "error-container": "#93000a",
                "tertiary-fixed": "#d8e2ff",
                "primary": "#e9c176",
                "on-secondary-fixed-variant": "#5e4203",
                "on-primary": "#412d00",
                "on-tertiary-fixed-variant": "#304671",
                "background": "#131313",
                "on-secondary": "#422c00",
                "on-background": "#e5e2e1",
                "outline": "#9a8f80",
                "on-secondary-container": "#dbb26c",
                "tertiary": "#b0c6f9",
                "on-error": "#690005",
                "on-primary-fixed-variant": "#5d4201",
                "surface-tint": "#e9c176",
                "on-error-container": "#ffdad6",
                "surface-container-highest": "#353535",
                "outline-variant": "#4e4639",
                "inverse-on-surface": "#313030",
                "surface-container-low": "#1c1b1b",
                "surface-bright": "#393939",
                "on-tertiary": "#173059",
                "surface-dim": "#131313",
                "on-surface-variant": "#d1c5b4",
                "surface-container-lowest": "#0e0e0e",
                "error": "#ffb4ab",
                "inverse-primary": "#775a19",
                "success-container": "#dcfce7"
            },
            borderRadius: {
                "DEFAULT": "0.125rem",
                "lg": "0.25rem",
                "xl": "0.5rem",
                "full": "0.75rem"
            },
            spacing: {
                "gutter": "24px",
                "container-padding-desktop": "40px",
                "container-padding-mobile": "20px",
                "section-gap": "64px",
                "base": "8px"
            },
            fontFamily: {
                "title-md": ["Inter", "sans-serif"],
                "headline-lg-mobile": ["Inter", "sans-serif"],
                "label-caps": ["Inter", "sans-serif"],
                "display-lg": ["Inter", "sans-serif"],
                "body-lg": ["Inter", "sans-serif"],
                "headline-lg": ["Inter", "sans-serif"],
                "body-sm": ["Inter", "sans-serif"]
            },
            fontSize: {
                "title-md": ["20px", { "lineHeight": "28px", "letterSpacing": "0.01em", "fontWeight": "600" }],
                "headline-lg-mobile": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
                "label-caps": ["12px", { "lineHeight": "16px", "letterSpacing": "0.1em", "fontWeight": "700" }],
                "display-lg": ["48px", { "lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                "body-lg": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                "headline-lg": ["32px", { "lineHeight": "40px", "fontWeight": "600" }],
                "body-sm": ["14px", { "lineHeight": "20px", "fontWeight": "400" }]
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/container-queries')
    ],
}
