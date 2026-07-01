import { useAuthStore } from '~/stores/auth'

/**
 * Route guard for protected pages — add `definePageMeta({ middleware: 'auth' })`.
 * Redirects guests to /login with a ?redirect back to the intended page.
 */
export default defineNuxtRouteMiddleware((to) => {
  const auth = useAuthStore()

  if (!auth.isLoggedIn) {
    return navigateTo({ path: '/login', query: { redirect: to.fullPath } })
  }
})
