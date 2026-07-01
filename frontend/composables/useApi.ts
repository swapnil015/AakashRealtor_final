import { useAuthStore } from '~/stores/auth'

/**
 * Standard API envelope returned by every backend endpoint.
 */
export interface ApiEnvelope<T = any> {
  success: boolean
  data: T
  message: string
  meta?: { pagination?: Pagination }
  errors?: Record<string, string[]>
}

export interface Pagination {
  current_page: number
  per_page: number
  total: number
  last_page: number
  from: number | null
  to: number | null
  has_more: boolean
}

export interface ApiResult<T> {
  data: T
  meta?: ApiEnvelope['meta']
  message: string
}

/**
 * Thin wrapper over $fetch that:
 *   - prefixes the versioned API base,
 *   - attaches the Sanctum bearer token,
 *   - unwraps the { success, data, meta } envelope,
 *   - throws a typed error carrying validation messages.
 *
 * Works in both SSR and client contexts.
 */
export function useApi() {
  const config = useRuntimeConfig()
  const auth = import.meta.client ? useAuthStore() : null

  async function request<T = any>(
    path: string,
    options: Parameters<typeof $fetch>[1] = {},
  ): Promise<ApiResult<T>> {
    const headers: Record<string, string> = {
      Accept: 'application/json',
      ...(options.headers as Record<string, string>),
    }

    // Attach token from the store (client) or the persisted cookie (SSR).
    const token = auth?.token || useCookie('akr_token').value
    if (token) headers.Authorization = `Bearer ${token}`

    try {
      const res = await $fetch<ApiEnvelope<T>>(path, {
        baseURL: config.public.apiBase,
        ...options,
        headers,
      })
      return { data: res.data, meta: res.meta, message: res.message }
    } catch (err: any) {
      const payload = err?.data as ApiEnvelope | undefined
      const apiError = new ApiError(
        payload?.message || err?.message || 'Request failed',
        err?.status || err?.statusCode || 0,
        payload?.errors || {},
      )
      // Auto-logout on a hard 401.
      if (apiError.status === 401 && auth) auth.clear()
      throw apiError
    }
  }

  return {
    get: <T = any>(path: string, params?: Record<string, any>) =>
      request<T>(path, { method: 'GET', query: params }),
    post: <T = any>(path: string, body?: any) =>
      request<T>(path, { method: 'POST', body }),
    put: <T = any>(path: string, body?: any) =>
      request<T>(path, { method: 'PUT', body }),
    patch: <T = any>(path: string, body?: any) =>
      request<T>(path, { method: 'PATCH', body }),
    del: <T = any>(path: string) => request<T>(path, { method: 'DELETE' }),
    raw: request,
  }
}

/** Typed API error with field-level validation messages (422). */
export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors: Record<string, string[]> = {},
  ) {
    super(message)
    this.name = 'ApiError'
  }

  first(field: string): string | undefined {
    return this.errors[field]?.[0]
  }
}
