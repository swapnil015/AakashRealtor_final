import { defineStore } from 'pinia'

export interface AuthUser {
  id: number
  name: string
  email: string
  phone: string | null
  role: 'user' | 'agent' | 'admin'
  avatar: string | null
}

/**
 * Auth state. The token is persisted to a cookie (via persistedstate) so it
 * survives reloads and is available during SSR for authenticated requests.
 */
export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as AuthUser | null,
    token: null as string | null,
  }),

  getters: {
    isLoggedIn: (s) => !!s.token,
    isStaff: (s) => s.user?.role === 'admin' || s.user?.role === 'agent',
  },

  actions: {
    async login(login: string, password: string) {
      const api = useApi()
      const { data } = await api.post<{ user: AuthUser; token: string }>('/auth/login', {
        login,
        password,
      })
      this.setSession(data.user, data.token)
      return data.user
    },

    async register(payload: {
      name: string
      email: string
      phone?: string
      password: string
      password_confirmation: string
    }) {
      const api = useApi()
      const { data } = await api.post<{ user: AuthUser; token: string }>('/auth/register', payload)
      this.setSession(data.user, data.token)
      return data.user
    },

    async fetchMe() {
      if (!this.token) return null
      const api = useApi()
      try {
        const { data } = await api.get<AuthUser>('/auth/me')
        this.user = data
        return data
      } catch {
        this.clear()
        return null
      }
    },

    async logout() {
      const api = useApi()
      try {
        await api.post('/auth/logout')
      } catch {
        /* token may already be invalid */
      }
      this.clear()
    },

    setSession(user: AuthUser, token: string) {
      this.user = user
      this.token = token
    },

    clear() {
      this.user = null
      this.token = null
    },
  },

  // Persist only the token. The pinia-plugin-persistedstate Nuxt module
  // defaults to cookie storage, so this survives SSR + reloads.
  persist: {
    key: 'akr_token',
    pick: ['token'],
  },
})
