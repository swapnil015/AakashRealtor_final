// Dynamic sitemap source consumed by @nuxtjs/sitemap.
// Emits every active property + city/category landing page so search engines
// can discover the full inventory.
import type { Property, City, Category } from '~/types'

export default defineSitemapEventHandler(async () => {
  const config = useRuntimeConfig()
  const base = config.public.apiBase

  async function fetchAll<T>(path: string, params: Record<string, any> = {}): Promise<T[]> {
    try {
      const res = await $fetch<any>(path, { baseURL: base, params })
      return (res?.data ?? []) as T[]
    } catch {
      return []
    }
  }

  // Cities + categories for landing pages.
  const [cities, categories] = await Promise.all([
    fetchAll<City>('/cities'),
    fetchAll<Category>('/categories'),
  ])

  const urls: any[] = []

  // Listing landing pages: /{txn}{Category}/{City-token}
  for (const txn of ['buy', 'rent']) {
    for (const cat of categories) {
      const seg = `${txn}${cat.name.replace(/\s+/g, '')}`
      urls.push({ loc: `/${seg}`, changefreq: 'daily', priority: 0.7 })
      for (const city of cities) {
        urls.push({ loc: `/${seg}/${city.url_token}`, changefreq: 'daily', priority: 0.6 })
      }
    }
  }

  // Every active property (paginate through the API).
  let page = 1
  let hasMore = true
  while (hasMore && page <= 100) {
    const res = await $fetch<any>('/properties', {
      baseURL: base,
      params: { per_page: 48, page, sort: 'newest' },
    }).catch(() => null)
    const items: Property[] = res?.data ?? []
    for (const p of items) {
      urls.push({
        loc: `/property/${p.slug}`,
        changefreq: 'weekly',
        priority: 0.8,
        lastmod: p.published_at || p.created_at,
      })
    }
    hasMore = !!res?.meta?.pagination?.has_more
    page++
  }

  // Static pages.
  for (const loc of ['/', '/exclusive', '/about', '/team', '/branches', '/blogs', '/faqs', '/contact',
    '/requirements', '/tools/emi', '/tools/land-converter', '/tools/date-converter']) {
    urls.push({ loc, changefreq: 'weekly', priority: loc === '/' ? 1.0 : 0.5 })
  }

  return urls
})
