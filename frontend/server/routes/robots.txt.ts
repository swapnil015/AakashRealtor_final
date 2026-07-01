// Served at /robots.txt — points crawlers at the dynamic sitemap.
export default defineEventHandler((event) => {
  const config = useRuntimeConfig()
  const site = config.public.siteUrl?.replace(/\/$/, '') || ''

  setHeader(event, 'Content-Type', 'text/plain')
  return [
    'User-agent: *',
    'Allow: /',
    'Disallow: /dashboard',
    'Disallow: /post',
    'Disallow: /login',
    'Disallow: /register',
    'Disallow: /forgot-password',
    'Disallow: /reset-password',
    '',
    `Sitemap: ${site}/sitemap.xml`,
    '',
  ].join('\n')
})
