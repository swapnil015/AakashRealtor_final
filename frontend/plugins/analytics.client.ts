// Google Tag Manager + GA4 injection (client-only). No-op if IDs aren't set.
export default defineNuxtPlugin(() => {
  const { gtmId, gaId } = useRuntimeConfig().public
  const router = useRouter()

  if (gtmId) {
    // GTM container.
    ;(function (w: any, d: Document, s: string, l: string, i: string) {
      w[l] = w[l] || []
      w[l].push({ 'gtm.start': Date.now(), event: 'gtm.js' })
      const f = d.getElementsByTagName(s)[0]
      const j = d.createElement(s) as HTMLScriptElement
      j.async = true
      j.src = `https://www.googletagmanager.com/gtm.js?id=${i}`
      f.parentNode?.insertBefore(j, f)
    })(window, document, 'script', 'dataLayer', gtmId)
  }

  if (gaId) {
    const s = document.createElement('script')
    s.async = true
    s.src = `https://www.googletagmanager.com/gtag/js?id=${gaId}`
    document.head.appendChild(s)
    ;(window as any).dataLayer = (window as any).dataLayer || []
    function gtag(...args: any[]) { (window as any).dataLayer.push(args) }
    ;(window as any).gtag = gtag
    gtag('js', new Date())
    gtag('config', gaId)

    // SPA pageview tracking.
    router.afterEach((to) => {
      gtag('event', 'page_view', { page_path: to.fullPath })
    })
  }
})
