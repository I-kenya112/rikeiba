import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    // 🔥 Purgeで削除されないように重要クラスをsafelistに登録
    safelist: [
        {
            pattern: /(bg|text|hover:bg|focus:ring)-(sky|green|rose|blue|gray)-(100|200|300|400|500|600|700)/,
        },
        'rounded-lg',
        'shadow',
        'transition',
        'hover:bg-sky-100',
    ],

    plugins: [forms],
}
