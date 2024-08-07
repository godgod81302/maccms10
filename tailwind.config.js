/** @type {import('tailwindcss').Config} */
const { fontFamily } = require('tailwindcss/defaultTheme')

module.exports = {
  purge: {
    mode: 'layers',
    layers: ['base', 'components', 'utilities'],
    content: ["./application/view/**/*.{html,js,css}",
  "./static/css/*.{html,js,css}",
  "./static/**/*.{html,js,css}",
  "./upload/**/*.{html,js,css}",
  "./application/admin/view//customui/greem/type/*.{html,js,css}",
  'node_modules/preline/dist/*.js',
    ],
  },
  theme: {
    container: {
      center: true,
      padding: {
        default: '1rem',
        sm: '2rem',
        lg: '3rem',
        xl: '4rem',
      },
    },
    extend: {
      fontFamily: {
        sans: ['Inter var', 'Noto Sans TC', ...fontFamily.sans],
        mono: [...fontFamily.mono, 'Inter var', 'Noto Sans TC'],
      },
    },
  },
  variants: {},

  plugins: ["tailwindcss ,autoprefixer,postcss-import,preline/plugin"], 
}