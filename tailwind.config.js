import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './node_modules/preline/dist/*.js',
    ],

    safelist: [
        // Theme colors
        'bg-indigo-600', 'text-indigo-500', 'hover:bg-indigo-700',
        'bg-purple-600', 'text-purple-500', 'hover:bg-purple-700',
        'bg-emerald-600', 'text-emerald-500', 'hover:bg-emerald-700',
        'bg-blue-600', 'text-blue-500', 'hover:bg-blue-700',
        'bg-green-600', 'text-green-500', 'hover:bg-green-700',
        // Border colors for light themes
        'border-green-200',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Outfit', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                cyan: {
                    50: '#F4F6F5',
                    100: '#E6EBE8',
                    200: '#C2D1C8',
                    300: '#9BB3A5',
                    400: '#70917E',
                    500: '#2A4B3A', // Primary Forest Green
                    600: '#20392C',
                    700: '#16281E',
                    800: '#0C1611',
                    900: '#030806',
                },
                blue: {
                    50: '#F4F6F5',
                    100: '#E6EBE8',
                    200: '#C2D1C8',
                    300: '#9BB3A5',
                    400: '#70917E',
                    500: '#2A4B3A', // Primary Forest Green
                    600: '#20392C',
                    700: '#16281E',
                    800: '#0C1611',
                    900: '#030806',
                },
                amber: {
                    50: '#FDF7F5',
                    100: '#FBECE7',
                    200: '#F6D2C8',
                    300: '#EFB3A3',
                    400: '#E68D76',
                    500: '#D47A60', // Accent Terracotta
                    600: '#C26349',
                    700: '#9B4C37',
                    800: '#763828',
                    900: '#53251A',
                },
                indigo: {
                    50: '#FDF7F5',
                    100: '#FBECE7',
                    200: '#F6D2C8',
                    300: '#EFB3A3',
                    400: '#E68D76',
                    500: '#D47A60', // Accent Terracotta
                    600: '#C26349',
                    700: '#9B4C37',
                    800: '#763828',
                    900: '#53251A',
                }
            }
        },
    },

    plugins: [
        forms,
    ],
};
