import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
        './app/View/Components/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Noto Sans Georgian"', ...defaultTheme.fontFamily.sans],
                display: ['"Noto Sans Georgian"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ink: {
                    DEFAULT: '#0F172A',
                    soft: '#334155',
                    muted: '#64748B',
                    faint: '#94A3B8',
                },
                accent: {
                    DEFAULT: '#0F172A',
                    fg: '#FFFFFF',
                },
                deal: {
                    DEFAULT: '#047857',
                    fg: '#FFFFFF',
                    soft: '#ECFDF5',
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(15 23 42 / 0.04)',
                'card-hover': '0 8px 24px -8px rgb(15 23 42 / 0.12)',
            },
            transitionDuration: {
                DEFAULT: '200ms',
            },
        },
    },
    plugins: [forms],
};
