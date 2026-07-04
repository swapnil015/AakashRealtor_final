import type { Config } from 'tailwindcss'

/**
 * "Navy & Gold" premium design system (Aakash Realtor — dc.html comp).
 * Deep navy ink (#0B132B), warm cream surfaces (#FAF8F4), champagne gold
 * accent (#C7A76C), peach panels (#F6E7D8), forest "verified" green.
 * Playfair Display (display) + Inter (body). Rounded 16px cards, pill CTAs,
 * soft navy shadows.
 *
 * Legacy aliases (gold/ink/canvas/sand/muted, Material-style token names)
 * are kept so not-yet-migrated pages keep rendering.
 */
export default <Config>{
  darkMode: 'class',
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
        // ── Core palette ────────────────────────────────────────────
        surface: '#FAF8F4',
        'surface-dim': '#EDE9E1',
        'surface-bright': '#FAF8F4',
        'surface-container-lowest': '#ffffff',
        'surface-container-low': '#F5F1E9',
        'surface-container': '#F1ECE3',
        'surface-container-high': '#ECE6DB',
        'surface-container-highest': '#F6E7D8',
        'surface-variant': '#EFE9DE',
        'on-surface': '#1C1C1C',
        'on-surface-variant': '#55524B',
        'inverse-surface': '#0B132B',
        'inverse-on-surface': '#FAF8F4',
        outline: '#8A857C',
        'outline-variant': '#E0DACE',
        primary: '#0B132B',              // deep navy (design's true primary)
        'on-primary': '#FAF8F4',
        'primary-container': '#16224A',
        'on-primary-container': '#AEB6CF',
        'primary-fixed': '#E4E7F0',
        'primary-fixed-dim': '#C6CCDD',
        secondary: '#C7A76C',            // champagne gold
        'on-secondary': '#0B132B',
        'secondary-container': '#F6E7D8',
        'on-secondary-container': '#7A5C2B',
        'secondary-fixed': '#F6E7D8',
        'secondary-fixed-dim': '#d4b67e',
        tertiary: '#3E5C4B',             // forest green (verified markers)
        'tertiary-container': '#2C4437',
        'on-tertiary': '#FAF8F4',
        'on-tertiary-container': '#9DBCAA',
        background: '#FAF8F4',
        'on-background': '#1C1C1C',
        error: '#ba1a1a',
        'on-error': '#ffffff',
        navy: '#0B132B',
        brass: '#C7A76C',
        forest: '#3E5C4B',
        peach: '#F6E7D8',
        hairline: '#E4DDD0',

        // ── Legacy aliases (kept for un-migrated pages) ─────────────
        gold: { DEFAULT: '#C7A76C', hover: '#d4b67e', 400: '#C7A76C', 500: '#B08D57' },
        ink: { DEFAULT: '#1C1C1C', soft: '#0B132B', line: '#3a3a3a' },
        canvas: '#FAF8F4',
        sand: '#F1ECE3',
        muted: '#55524B',
      },
      fontFamily: {
        display: ['"Playfair Display"', 'Georgia', 'serif'],
        serif: ['"Playfair Display"', 'Georgia', 'serif'],
        sans: ['Inter', 'system-ui', 'sans-serif'],
        'headline-md': ['"Playfair Display"', 'serif'],
        'headline-sm': ['"Playfair Display"', 'serif'],
        'display-lg': ['"Playfair Display"', 'serif'],
        'display-lg-mobile': ['"Playfair Display"', 'serif'],
        'body-md': ['Inter', 'sans-serif'],
        'body-lg': ['Inter', 'sans-serif'],
        'label-caps': ['Inter', 'sans-serif'],
        'technical-data': ['Inter', 'sans-serif'],
      },
      fontSize: {
        'technical-data': ['14px', { lineHeight: '20px', fontWeight: '500' }],
        'body-md': ['16px', { lineHeight: '24px', fontWeight: '400' }],
        'body-lg': ['18px', { lineHeight: '28px', fontWeight: '400' }],
        'display-lg': ['72px', { lineHeight: '1.06', fontWeight: '500' }],
        'display-lg-mobile': ['44px', { lineHeight: '1.1', fontWeight: '500' }],
        'headline-sm': ['24px', { lineHeight: '32px', fontWeight: '500' }],
        'headline-md': ['40px', { lineHeight: '1.1', fontWeight: '500' }],
        'label-caps': ['12px', { lineHeight: '16px', letterSpacing: '0.1em', fontWeight: '600' }],
      },
      spacing: {
        base: '8px',
        'margin-desktop': '48px',
        'margin-mobile': '24px',
        'section-gap': '120px',
        'content-gap': '32px',
        gutter: '24px',
      },
      maxWidth: {
        editorial: '1120px',
        shell: '1280px',
      },
      borderRadius: {
        DEFAULT: '12px',
        none: '0px',
        lg: '12px',
        xl: '16px',
        '2xl': '20px',
        full: '9999px',
      },
      boxShadow: {
        card: '0 24px 48px rgba(11,19,43,0.18)',
        lift: '0 24px 64px rgba(11,19,43,0.18)',
        gold: '0 8px 24px rgba(199,167,108,0.35)',
        search: '0 24px 64px rgba(11,19,43,0.18)',
      },
      transitionTimingFunction: {
        smooth: 'cubic-bezier(0.22,1,0.36,1)',
      },
      keyframes: {
        'fade-up': {
          from: { opacity: '0', transform: 'translateY(32px)' },
          to: { opacity: '1', transform: 'translateY(0)' },
        },
        fade: {
          from: { opacity: '0' },
          to: { opacity: '1' },
        },
        rise: {
          from: { transform: 'translateY(110%)' },
          to: { transform: 'translateY(0)' },
        },
        kenburns: {
          from: { transform: 'scale(1.14)' },
          to: { transform: 'scale(1.02)' },
        },
        float: {
          '0%,100%': { transform: 'translateY(0)', opacity: '0.9' },
          '50%': { transform: 'translateY(9px)', opacity: '0.4' },
        },
      },
      animation: {
        'fade-up': 'fade-up .9s cubic-bezier(0.22,1,0.36,1) both',
        fade: 'fade .8s cubic-bezier(0.22,1,0.36,1) both',
        rise: 'rise 1s cubic-bezier(0.22,1,0.36,1) both',
        kenburns: 'kenburns 16s cubic-bezier(0.22,1,0.36,1) both',
        float: 'float 1.8s ease-in-out infinite',
      },
    },
  },
  plugins: [],
}
