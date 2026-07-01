import type { Config } from 'tailwindcss'

// Brand: gold #C9A227 on near-black ink, warm off-white canvas — premium,
// editorial real-estate aesthetic (Cormorant Garamond display + Manrope body).
export default <Config>{
  content: [
    './components/**/*.{vue,js,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './app.vue',
    './error.vue',
  ],
  theme: {
    extend: {
      colors: {
        gold: {
          DEFAULT: '#C9A227',
          hover: '#B38F1E',
          50: '#FBF7EA',
          100: '#F5ECCB',
          200: '#EAD78F',
          300: '#DFC257',
          400: '#C9A227',
          500: '#B38F1E',
          600: '#917319',
          700: '#6E5713',
        },
        ink: {
          DEFAULT: '#0F172A',
          soft: '#1A2438',
          line: '#2A3650',
        },
        canvas: '#F8F7F4',
        sand: '#ECEAE4',
        muted: '#475569',
      },
      fontFamily: {
        display: ['"Cormorant Garamond"', 'Georgia', 'serif'],
        sans: ['Manrope', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        card: '0 1px 2px rgba(15,23,42,.04)',
        lift: '0 30px 60px -34px rgba(15,23,42,.5)',
        gold: '0 16px 36px -10px rgba(201,162,39,.55)',
      },
      borderRadius: {
        xl: '14px',
        '2xl': '18px',
      },
      transitionTimingFunction: {
        smooth: 'cubic-bezier(.16,1,.3,1)',
      },
      keyframes: {
        'fade-up': {
          from: { opacity: '0', transform: 'translateY(20px)' },
          to: { opacity: '1', transform: 'translateY(0)' },
        },
        // Slow cinematic zoom for hero imagery (Ken Burns).
        kenburns: {
          from: { transform: 'scale(1.02)' },
          to: { transform: 'scale(1.14)' },
        },
        // Gentle bob for the scroll-cue arrow.
        float: {
          '0%,100%': { transform: 'translateY(0)', opacity: '0.9' },
          '50%': { transform: 'translateY(9px)', opacity: '0.4' },
        },
        // Sweeping light across gold CTAs.
        shine: {
          '0%': { transform: 'translateX(-120%) skewX(-20deg)' },
          '60%,100%': { transform: 'translateX(220%) skewX(-20deg)' },
        },
        // Pop for badges / tags entering.
        pop: {
          from: { opacity: '0', transform: 'scale(.85)' },
          to: { opacity: '1', transform: 'scale(1)' },
        },
        // Animated gradient wash for dark CTA panels.
        'gradient-pan': {
          '0%,100%': { backgroundPosition: '0% 50%' },
          '50%': { backgroundPosition: '100% 50%' },
        },
      },
      animation: {
        'fade-up': 'fade-up .7s cubic-bezier(.16,1,.3,1) both',
        kenburns: 'kenburns 22s cubic-bezier(.16,1,.3,1) infinite alternate',
        float: 'float 1.8s ease-in-out infinite',
        pop: 'pop .5s cubic-bezier(.16,1,.3,1) both',
        'gradient-pan': 'gradient-pan 8s ease infinite',
      },
    },
  },
  plugins: [],
}
