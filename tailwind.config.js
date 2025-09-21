import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Montserrat', ...defaultTheme.fontFamily.sans],
                display: ['Orbitron', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Axontis Primary Colors
                primary: {
                    50: '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b', // Main Axontis amber
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                    950: '#451a03',
                },
                // Axontis Dark Theme
                dark: {
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b', // Main dark background
                    900: '#0f172a', // Darker background
                    950: '#020617',
                },
                // Axontis Blue Accent
                accent: {
                    50: '#f0f9ff',
                    100: '#e0f2fe',
                    200: '#bae6fd',
                    300: '#7dd3fc',
                    400: '#38bdf8',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    800: '#075985',
                    900: '#0c4a6e', // Axontis blue
                    950: '#082f49',
                },
                // Success, Warning, Error colors matching Axontis theme
                success: {
                    50: '#ecfdf5',
                    100: '#d1fae5',
                    200: '#a7f3d0',
                    300: '#6ee7b7',
                    400: '#34d399',
                    500: '#10b981',
                    600: '#059669',
                    700: '#047857',
                    800: '#065f46',
                    900: '#064e3b',
                    950: '#022c22',
                },
                warning: {
                    50: '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                    950: '#451a03',
                },
                error: {
                    50: '#fef2f2',
                    100: '#fee2e2',
                    200: '#fecaca',
                    300: '#fca5a5',
                    400: '#f87171',
                    500: '#ef4444',
                    600: '#dc2626',
                    700: '#b91c1c',
                    800: '#991b1b',
                    900: '#7f1d1d',
                    950: '#450a0a',
                },
            },
            backgroundImage: {
                'axontis-gradient': 'linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0c4a6e 100%)',
                'axontis-hero': 'linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.8) 50%, rgba(12, 74, 110, 0.9) 100%)',
                'axontis-card': 'linear-gradient(135deg, #1e293b 0%, #334155 100%)',
                'primary-gradient': 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                'accent-gradient': 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)',
            },
            boxShadow: {
                'axontis': '0 4px 15px rgba(0, 0, 0, 0.2)',
                'axontis-lg': '0 8px 25px rgba(0, 0, 0, 0.3)',
                'axontis-xl': '0 20px 60px rgba(0, 0, 0, 0.5)',
                'primary': '0 5px 15px rgba(245, 158, 11, 0.4)',
                'primary-lg': '0 10px 25px rgba(245, 158, 11, 0.3)',
            },
            backdropBlur: {
                'axontis': '10px',
                'axontis-lg': '20px',
            },
            animation: {
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'bounce-slow': 'bounce 2s infinite',
                'fade-in': 'fadeIn 0.5s ease-in-out',
                'slide-in': 'slideIn 0.6s ease-out',
                'scale-in': 'scaleIn 0.3s ease-out',
                'glow': 'glow 2s ease-in-out infinite alternate',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideIn: {
                    '0%': { opacity: '0', transform: 'translateX(-30px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                scaleIn: {
                    '0%': { opacity: '0', transform: 'scale(0.9)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                glow: {
                    '0%': { textShadow: '0 0 10px rgba(245, 158, 11, 0.5)' },
                    '100%': { textShadow: '0 0 20px rgba(245, 158, 11, 0.8)' },
                },
            },
            spacing: {
                '18': '4.5rem',
                '88': '22rem',
                '128': '32rem',
            },
            borderRadius: {
                'axontis': '16px',
                'axontis-lg': '20px',
            },
            fontSize: {
                '2xs': ['0.625rem', { lineHeight: '0.75rem' }],
            },
        },
    },

    plugins: [
        forms,
        typography,
        // Custom plugin for Axontis utilities
        function({ addUtilities, addComponents, theme }) {
            const newUtilities = {
                '.text-glow': {
                    textShadow: '0 0 10px rgba(245, 158, 11, 0.7)',
                },
                '.text-glow-lg': {
                    textShadow: '0 0 20px rgba(245, 158, 11, 0.8)',
                },
                '.backdrop-blur-axontis': {
                    backdropFilter: 'blur(10px)',
                },
                '.backdrop-blur-axontis-lg': {
                    backdropFilter: 'blur(20px)',
                },
            }

            const newComponents = {
                '.btn-axontis': {
                    background: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                    color: '#0f172a',
                    fontWeight: '700',
                    padding: '0.75rem 2rem',
                    borderRadius: '0.5rem',
                    transition: 'all 0.3s ease',
                    border: 'none',
                    cursor: 'pointer',
                    '&:hover': {
                        transform: 'translateY(-3px)',
                        boxShadow: '0 5px 15px rgba(245, 158, 11, 0.4)',
                    },
                },
                '.btn-axontis-secondary': {
                    background: 'transparent',
                    border: '2px solid #f59e0b',
                    color: '#f59e0b',
                    fontWeight: '700',
                    padding: '0.75rem 2rem',
                    borderRadius: '0.5rem',
                    transition: 'all 0.3s ease',
                    cursor: 'pointer',
                    '&:hover': {
                        background: 'rgba(245, 158, 11, 0.1)',
                        transform: 'translateY(-3px)',
                    },
                },
                '.card-axontis': {
                    background: 'rgba(30, 41, 59, 0.7)',
                    border: '1px solid rgba(245, 158, 11, 0.2)',
                    borderRadius: '16px',
                    padding: '2rem',
                    transition: 'all 0.3s ease',
                    backdropFilter: 'blur(10px)',
                    boxShadow: '0 4px 15px rgba(0, 0, 0, 0.2)',
                    '&:hover': {
                        transform: 'translateY(-2px)',
                        borderColor: 'rgba(245, 158, 11, 0.4)',
                        boxShadow: '0 8px 25px rgba(0, 0, 0, 0.3)',
                    },
                },
                '.header-axontis': {
                    background: 'rgba(15, 23, 42, 0.8)',
                    backdropFilter: 'blur(10px)',
                    borderBottom: '1px solid rgba(245, 158, 11, 0.2)',
                },
            }

            addUtilities(newUtilities)
            addComponents(newComponents)
        }
    ],
};
