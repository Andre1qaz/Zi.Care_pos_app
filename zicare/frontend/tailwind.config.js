/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        border: '#e2e8f0',
        primary: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
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
          900: '#14532d',
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
          900: '#7f1d1d',
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
        },
        brand: {
          indigo: '#6366f1',
          violet: '#8b5cf6',
          sky: '#0ea5e9',
          teal: '#14b8a6',
        },
      },
      fontFamily: {
        sans: ['Inter', 'Segoe UI', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        '4xl': '2rem',
        '5xl': '2.75rem',
      },
      boxShadow: {
        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
        'bubble': '0 18px 40px -18px rgba(37, 99, 235, 0.35), 0 8px 20px -12px rgba(15, 23, 42, 0.18)',
        'bubble-sm': '0 10px 24px -14px rgba(37, 99, 235, 0.30), 0 4px 12px -8px rgba(15, 23, 42, 0.14)',
        'bubble-lg': '0 30px 60px -22px rgba(37, 99, 235, 0.40), 0 12px 28px -14px rgba(15, 23, 42, 0.22)',
        'glow': '0 0 0 4px rgba(59, 130, 246, 0.15)',
        'inner-soft': 'inset 0 1px 2px 0 rgba(15, 23, 42, 0.04)',
      },
      backgroundImage: {
        'aurora': 'radial-gradient(60% 80% at 15% 10%, rgba(99,102,241,0.18) 0%, rgba(99,102,241,0) 60%), radial-gradient(50% 70% at 90% 20%, rgba(14,165,233,0.16) 0%, rgba(14,165,233,0) 55%), radial-gradient(60% 90% at 60% 100%, rgba(139,92,246,0.14) 0%, rgba(139,92,246,0) 60%)',
        'brand-gradient': 'linear-gradient(135deg, #6366f1 0%, #2563eb 55%, #0ea5e9 100%)',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        fadeInUp: {
          '0%': { opacity: '0', transform: 'translateY(14px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        scaleIn: {
          '0%': { opacity: '0', transform: 'scale(0.94)' },
          '100%': { opacity: '1', transform: 'scale(1)' },
        },
        slideInRight: {
          '0%': { opacity: '0', transform: 'translateX(24px)' },
          '100%': { opacity: '1', transform: 'translateX(0)' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-10px)' },
        },
        shimmer: {
          '100%': { transform: 'translateX(100%)' },
        },
      },
      animation: {
        'fade-in': 'fadeIn 0.3s ease-out both',
        'fade-in-up': 'fadeInUp 0.45s cubic-bezier(0.22, 1, 0.36, 1) both',
        'scale-in': 'scaleIn 0.28s cubic-bezier(0.22, 1, 0.36, 1) both',
        'slide-in-right': 'slideInRight 0.35s cubic-bezier(0.22, 1, 0.36, 1) both',
        'float': 'float 6s ease-in-out infinite',
      },
    },
  },
  plugins: [],
}
