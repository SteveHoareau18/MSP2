/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./src/Form/*.php",
        "./assets/**/*.js",
        "./public/email-template/*.html.twig",
        "./templates/**/*.html.twig",
    ],
    theme: {extend: {},},
    plugins: [require("daisyui")],
    daisyui: {
        themes: [
            {
                mytheme: {
                    "primary": "#2ecc71",     // Vert émeraude
                    "secondary": "#f39c12",   // Jaune
                    "accent": "#ecf0f1",      // Beige
                    "neutral": "#ffffff",     // Blanc
                    "base-100": "#bdc3c7",    // Gris clair
                    "info": "#3498db",        // Bleu
                    "success": "#27ae60",     // Vert émeraude plus foncé
                    "warning": "#e67e22",     // Orange
                    "error": "#c0392b",       // Rouge plus foncé
                },
            },
        ],
    },
}

