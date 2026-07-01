// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2024-09-01',
  ssr: true, // SSR on for SEO (listing + detail pages must be server-rendered)
  devtools: { enabled: true },

  modules: [
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt',
    'pinia-plugin-persistedstate/nuxt',
    '@vueuse/nuxt',
    '@nuxtjs/sitemap',
  ],

  // Public runtime config — overridable via env without a rebuild.
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api/v1',
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'http://localhost:3000',
      whatsapp: process.env.NUXT_PUBLIC_WHATSAPP || '9771XXXXXXX',
      gtmId: process.env.NUXT_PUBLIC_GTM_ID || '',
      gaId: process.env.NUXT_PUBLIC_GA_ID || '',
      googlePlaceId: process.env.NUXT_PUBLIC_GOOGLE_PLACE_ID || '',
    },
  },

  app: {
    head: {
      htmlAttrs: { lang: 'en' },
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'theme-color', content: '#0F172A' },
      ],
      link: [
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Manrope:wght@400;500;600;700;800&display=swap',
        },
      ],
    },
    pageTransition: { name: 'page', mode: 'out-in' },
  },

  css: ['~/assets/css/main.css'],

  // Generated dynamically in server/api/__sitemap__/urls (properties + cities).
  sitemap: {
    sources: ['/api/__sitemap__/urls'],
  },

  nitro: {
    compressPublicAssets: true,
  },
})
