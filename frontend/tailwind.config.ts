import type { Config } from 'tailwindcss'

/**
 * "Trust as Luxury" editorial design system (Aakash Realtor redesign).
 * Ported from the Stitch design tokens: warm off-white surfaces, charcoal ink,
 * brass accent, EB Garamond (display) + Hanken Grotesk (body/technical),
 * sharp 0px corners, hairline borders, no shadows.
 *
 * Legacy aliases (gold/ink/canvas/sand/muted, font-display/sans, radius xl/2xl)
 * are kept so not-yet-migrated pages keep rendering during the migration.
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
        // ── New Material token palette ──────────────────────────────
        surface: '#fef9f1',
        'surface-dim': '#ded9d2',
        'surface-bright': '#fef9f1',
        'surface-container-lowest': '#ffffff',
        'surface-container-low': '#f8f3eb',
        'surface-container': '#f2ede5',
        'surface-container-high': '#ece8e0',
        'surface-container-highest': '#e7e2da',
        'surface-variant': '#e7e2da',
        'on-surface': '#1d1c17',
        'on-surface-variant': '#4d4540',
        'inverse-surface': '#32302b',
        'inverse-on-surface': '#f5f0e8',
        outline: '#7e7570',
        'outline-variant': '#d0c4be',
        primary: '#161311',              // charcoal (design's true primary)
        'on-primary': '#ffffff',
        'primary-container': '#1e1b19',
        'on-primary-container': '#89837f',
        'primary-fixed': '#e9e1dd',
        'primary-fixed-dim': '#ccc5c1',
        secondary: '#775928',            // brass
        'on-secondary': '#ffffff',
        'secondary-container': '#ffd79b',
        'on-secondary-container': '#7a5c2b',
        'secondary-fixed': '#ffdeae',
        'secondary-fixed-dim': '#e8c086',
        tertiary: '#2f4a40',             // forest green (trust markers)
        'tertiary-container': '#042018',
        'on-tertiary': '#ffffff',
        'on-tertiary-container': '#6d8a7e',
        background: '#fef9f1',
        'on-background': '#1d1c17',
        error: '#ba1a1a',
        'on-error': '#ffffff',
        brass: '#B08D57',
        forest: '#2F4A40',
        hairline: '#DED5C6',

        // ── Legacy aliases (kept for un-migrated pages) ─────────────
        gold: { DEFAULT: '#B08D57', hover: '#775928', 400: '#B08D57', 500: '#775928' },
        ink: { DEFAULT: '#161311', soft: '#1e1b19', line: '#3a352f' },
        canvas: '#fef9f1',
        sand: '#f2ede5',
        muted: '#4d4540',
      },
      fontFamily: {
        display: ['"EB Garamond"', 'Georgia', 'serif'],
        serif: ['"EB Garamond"', 'Georgia', 'serif'],
        sans: ['"Hanken Grotesk"', 'system-ui', 'sans-serif'],
        'headline-md': ['"EB Garamond"', 'serif'],
        'headline-sm': ['"EB Garamond"', 'serif'],
        'display-lg': ['"EB Garamond"', 'serif'],
        'display-lg-mobile': ['"EB Garamond"', 'serif'],
        'body-md': ['"Hanken Grotesk"', 'sans-serif'],
        'body-lg': ['"Hanken Grotesk"', 'sans-serif'],
        'label-caps': ['"Hanken Grotesk"', 'sans-serif'],
        'technical-data': ['"Hanken Grotesk"', 'sans-serif'],
      },
      fontSize: {
        'technical-data': ['14px', { lineHeight: '20px', fontWeight: '500' }],
        'body-md': ['16px', { lineHeight: '24px', fontWeight: '400' }],
        'body-lg': ['18px', { lineHeight: '28px', fontWeight: '400' }],
        'display-lg': ['64px', { lineHeight: '72px', letterSpacing: '-0.02em', fontWeight: '400' }],
        'display-lg-mobile': ['40px', { lineHeight: '48px', letterSpacing: '-0.01em', fontWeight: '400' }],
        'headline-sm': ['24px', { lineHeight: '32px', fontWeight: '500' }],
        'headline-md': ['32px', { lineHeight: '40px', fontWeight: '500' }],
        'label-caps': ['12px', { lineHeight: '16px', letterSpacing: '0.1em', fontWeight: '600' }],
      },
      spacing: {
        base: '8px',
        'margin-desktop': '64px',
        'margin-mobile': '20px',
        'section-gap': '80px',
        'content-gap': '32px',
        gutter: '24px',
      },
      maxWidth: {
        editorial: '1120px',
        shell: '1440px',
      },
      borderRadius: {
        // Sharp by default (editorial). Legacy xl/2xl kept for old pages.
        DEFAULT: '0px',
        none: '0px',
        xl: '14px',
        '2xl': '18px',
        full: '9999px',
      },
      boxShadow: {
        // Design uses tonal layers, not shadows. Keep legacy names as no-ops.
        card: 'none',
        lift: '0 24px 50px -30px rgba(22,19,17,.35)',
        gold: 'none',
      },
      transitionTimingFunction: {
        smooth: 'cubic-bezier(.16,1,.3,1)',
      },
      keyframes: {
        'fade-up': {
          from: { opacity: '0', transform: 'translateY(20px)' },
          to: { opacity: '1', transform: 'translateY(0)' },
        },
        float: {
          '0%,100%': { transform: 'translateY(0)', opacity: '0.9' },
          '50%': { transform: 'translateY(9px)', opacity: '0.4' },
        },
      },
      animation: {
        'fade-up': 'fade-up .7s cubic-bezier(.16,1,.3,1) both',
        float: 'float 1.8s ease-in-out infinite',
      },
    },
  },
  plugins: [],
}
