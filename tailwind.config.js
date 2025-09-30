/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./views/**/*.php",
    "./public/**/*.html",
    "./public/assets/js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        // Paleta Chilena según requerimientos
        brand: {
          50: '#fef2f2',
          100: '#fee2e2',
          200: '#fecaca',
          300: '#fca5a5',
          400: '#f87171',
          500: '#dc2626', // Rojo Copihue principal
          600: '#b91c1c',
          700: '#991b1b',
          800: '#7f1d1d',
          900: '#6b1d1d'
        },
        // Alias para mantener consistencia con las vistas
        copihue: {
          50: '#fef2f2',
          100: '#fee2e2',
          200: '#fecaca',
          300: '#fca5a5',
          400: '#f87171',
          500: '#dc2626',
          600: '#b91c1c',
          700: '#991b1b',
          800: '#7f1d1d',
          900: '#6b1d1d'
        },
        navy: {
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1', // Azul Marino principal
          800: '#075985',
          900: '#0c4a6e'
        },
        // Alias en español para vistas que usen marino-*
        marino: {
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          800: '#075985',
          900: '#0c4a6e'
        },
        pacific: {
          50: '#ecfeff',
          100: '#cffafe',
          200: '#a5f3fc',
          300: '#67e8f9',
          400: '#22d3ee',
          500: '#06b6d4', // Azul Pacífico principal
          600: '#0891b2',
          700: '#0e7490',
          800: '#155e75',
          900: '#164e63'
        },
        // Alias en español para vistas que usen pacifico-*
        pacifico: {
          50: '#ecfeff',
          100: '#cffafe',
          200: '#a5f3fc',
          300: '#67e8f9',
          400: '#22d3ee',
          500: '#06b6d4',
          600: '#0891b2',
          700: '#0e7490',
          800: '#155e75',
          900: '#164e63'
        },
        // Estados y utilidades
        success: {
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
          800: '#166534',
          900: '#14532d'
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
          900: '#78350f'
        },
        danger: {
          50: '#fef2f2',
          100: '#fee2e2',
          200: '#fecaca',
          300: '#fca5a5',
          400: '#f87171',
          500: '#ef4444',
          600: '#dc2626',
          700: '#b91c1c',
          800: '#991b1b',
          900: '#7f1d1d'
        },
        // Neutrales mejorados
        neutral: {
          50: '#fafafa',
          100: '#f5f5f5',
          200: '#e5e5e5',
          300: '#d4d4d4',
          400: '#a3a3a3',
          500: '#737373',
          600: '#525252',
          700: '#404040',
          800: '#262626',
          900: '#171717',
          950: '#0a0a0a'
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        display: ['Inter', 'system-ui', 'sans-serif']
      },
      fontSize: {
        'xs': ['0.75rem', { lineHeight: '1rem' }],
        'sm': ['0.875rem', { lineHeight: '1.25rem' }],
        'base': ['1rem', { lineHeight: '1.5rem' }],
        'lg': ['1.125rem', { lineHeight: '1.75rem' }],
        'xl': ['1.25rem', { lineHeight: '1.75rem' }],
        '2xl': ['1.5rem', { lineHeight: '2rem' }],
        '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
        '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
        '5xl': ['3rem', { lineHeight: '1' }],
        '6xl': ['3.75rem', { lineHeight: '1' }]
      },
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
        '128': '32rem'
      },
      borderRadius: {
        'xl': '0.75rem',
        '2xl': '1rem',
        '3xl': '1.5rem'
      },
      boxShadow: {
        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
        'medium': '0 4px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
        'strong': '0 10px 40px -10px rgba(0, 0, 0, 0.15), 0 2px 10px -2px rgba(0, 0, 0, 0.04)'
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite'
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' }
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' }
        }
      }
    }
  },
  plugins: [
    require('@tailwindcss/forms')({
      strategy: 'class'
    }),
    require('@tailwindcss/typography'),
    require('@tailwindcss/aspect-ratio')
  ],
  // Safelist para clases dinámicas según requerimientos
  safelist: [
    // Alias copihue usados en vistas
    'bg-copihue-50','bg-copihue-100','bg-copihue-200','bg-copihue-400','bg-copihue-500','bg-copihue-600','bg-copihue-700','bg-copihue-800','bg-copihue-900',
    'text-copihue-500','text-copihue-600','text-copihue-700','text-copihue-800','text-copihue-900',
    'border-copihue-500','border-copihue-600',
    'focus:ring-copihue-500','hover:text-copihue-600','hover:bg-copihue-600','hover:bg-copihue-700','group-hover:text-copihue-600','from-copihue-500','to-copihue-600','from-copihue-600','to-copihue-700',
    // Hover states y interacciones adicionales
    'hover:bg-white/20','hover:border-white/60','hover:text-white','hover:drop-shadow-lg','group-hover:drop-shadow-lg',
    'z-20','group-hover:text-neutral-700','group-hover:text-copihue-700','hover:opacity-40','hover:opacity-50',
    // Estados de campañas
    'bg-yellow-100', 'text-yellow-800', 'border-yellow-200',
    'bg-green-100', 'text-green-800', 'border-green-200',
    'bg-red-100', 'text-red-800', 'border-red-200',
    'bg-blue-100', 'text-blue-800', 'border-blue-200',
    'bg-gray-100', 'text-gray-800', 'border-gray-200',
    
    // Colores de marca dinámicos
    'bg-brand-50', 'bg-brand-100', 'bg-brand-500', 'bg-brand-600',
    'text-brand-500', 'text-brand-600', 'text-brand-700',
    'border-brand-200', 'border-brand-300', 'border-brand-500',
    
    // Utilidades de progreso
    'w-0', 'w-1/12', 'w-2/12', 'w-3/12', 'w-4/12', 'w-5/12',
    'w-6/12', 'w-7/12', 'w-8/12', 'w-9/12', 'w-10/12', 'w-11/12', 'w-full',
    
    // Animaciones y transiciones
    'animate-pulse', 'animate-fade-in', 'animate-slide-up',
    'transition-all', 'duration-200', 'duration-300', 'ease-in-out',
    
    // Responsive utilities
    'sm:block', 'sm:hidden', 'md:block', 'md:hidden',
    'lg:block', 'lg:hidden', 'xl:block', 'xl:hidden',
    // Transforms controlados por JS
    '-translate-y-full'
  ]
};
