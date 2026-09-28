export default defineNuxtConfig({
  compatibilityDate: '2026-09-01',
  ssr: false,
  css: ['~/assets/main.css'],
  runtimeConfig: { public: { apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api/v1' } },
  app: { head: { title: 'Promo Radar', meta: [{ name: 'viewport', content: 'width=device-width, initial-scale=1' }] } }
})
