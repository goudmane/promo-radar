type User = { id: number; name: string; email: string; is_owner: boolean; email_mode: 'off'|'immediate'|'digest' }
export const useApi = () => {
  const user = useState<User|null>('user', () => null)
  const token = useState<string|null>('token', () => null)
  const config = useRuntimeConfig()
  async function request<T>(path: string, options: { method?: string; body?: any; query?: Record<string, any> } = {}): Promise<T> {
    return await $fetch<T>(config.public.apiBase + path, {
      method: (options.method || 'GET') as any,
      body: options.body,
      query: options.query,
      headers: token.value ? { Authorization: 'Bearer ' + token.value } : {},
    })
  }
  async function restore() {
    if (!import.meta.client) return
    token.value = sessionStorage.getItem('promo_token')
    if (token.value) {
      try { user.value = await request<User>('/me') }
      catch { token.value = null; user.value = null; sessionStorage.removeItem('promo_token') }
    }
  }
  function accept(data: { token: string; user: User }) {
    token.value = data.token; user.value = data.user; sessionStorage.setItem('promo_token', data.token)
  }
  async function logout() {
    try { if (token.value) await request('/logout', { method: 'POST' }) } finally {
      token.value = null; user.value = null; sessionStorage.removeItem('promo_token'); await navigateTo('/')
    }
  }
  return { user, token, request, restore, accept, logout }
}
