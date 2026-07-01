import { useAuthStore } from '~/stores/auth'

/**
 * On first client load, if a persisted token exists, hydrate the user profile
 * so the header reflects the logged-in state immediately.
 */
export default defineNuxtPlugin(async () => {
  const auth = useAuthStore()
  if (auth.token && !auth.user) {
    await auth.fetchMe()
  }
})
