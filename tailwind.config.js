import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                maxxi: {
                    navy: '#1A2E45',
                    'navy-dark': '#14233A',
                    'navy-light': '#25405C',
                    orange: '#F87613',
                    'orange-dark': '#D36B15',
                    slate: '#485767',
                    cream: '#FFFAE8',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
