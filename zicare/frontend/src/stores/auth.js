import { defineStore } from 'pinia'
import api from '@/services/api'

const TOKEN_KEY = 'token'
const USER_KEY = 'user'
const REMEMBER_KEY = 'remember'

function readStored(key) {
  // localStorage (persisted, "Ingat saya") menang atas sessionStorage.
  return localStorage.getItem(key) ?? sessionStorage.getItem(key)
}

function readUser() {
  try {
    return JSON.parse(readStored(USER_KEY) || 'null')
  } catch {
    return null
  }
}

export function decodeJwt(token) {
  if (!token) return null
  try {
    const part = token.split('.')[1]
    if (!part) return null
    const normalized = part.replace(/-/g, '+').replace(/_/g, '/')
    const json = decodeURIComponent(
      atob(normalized)
        .split('')
        .map((c) => '%' + c.charCodeAt(0).toString(16).padStart(2, '0'))
        .join('')
    )
    return JSON.parse(json)
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: readUser(),
    token: readStored(TOKEN_KEY) || null,
    remember: localStorage.getItem(REMEMBER_KEY) === '1',
    // Menyimpan challenge 2FA selama proses login tahap dua.
    pending2fa: null,
    // Info login sebelumnya untuk ditampilkan setelah login sukses.
    lastLogin: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    userRole: (state) => state.user?.role || null,
    isAdmin: (state) => state.user?.role === 'administrator',
    isManager: (state) => state.user?.role === 'manager',
    isCashier: (state) => state.user?.role === 'cashier',
    tokenExpiresAt: (state) => {
      const decoded = decodeJwt(state.token)
      return decoded?.exp ? decoded.exp * 1000 : null
    }
  },

  actions: {
    setSession(payload, remember) {
      this.token = payload.token
      this.user = payload.user
      this.remember = !!remember

      const store = remember ? localStorage : sessionStorage
      const other = remember ? sessionStorage : localStorage
      other.removeItem(TOKEN_KEY)
      other.removeItem(USER_KEY)
      store.setItem(TOKEN_KEY, this.token)
      store.setItem(USER_KEY, JSON.stringify(this.user))

      if (remember) localStorage.setItem(REMEMBER_KEY, '1')
      else localStorage.removeItem(REMEMBER_KEY)
    },

    async login(email, password, remember = false) {
      const { data } = await api.post('/auth/login', { email, password, remember })
      const payload = data.data || {}

      // Akun dengan 2FA aktif: simpan challenge, jangan set sesi dulu.
      if (payload.requires_2fa) {
        this.pending2fa = { challenge: payload.challenge_token, remember }
        return payload
      }

      this.setSession(payload, remember)
      this.lastLogin = payload.last_login || null
      this.pending2fa = null
      return payload
    },

    async verifyTwoFactor(code) {
      const pending = this.pending2fa
      if (!pending?.challenge) {
        throw new Error('Sesi 2FA tidak ditemukan. Silakan login ulang.')
      }
      const { data } = await api.post('/auth/verify-2fa', {
        challenge_token: pending.challenge,
        code,
        remember: pending.remember
      })
      const payload = data.data || {}
      this.setSession(payload, pending.remember)
      this.lastLogin = payload.last_login || null
      this.pending2fa = null
      return payload
    },

    async refreshSession() {
      const { data } = await api.post('/auth/refresh', { remember: this.remember })
      const payload = data.data || {}
      if (payload.token) {
        // Pertahankan preferensi penyimpanan yang sedang dipakai.
        this.setSession({ token: payload.token, user: payload.user || this.user }, this.remember)
      }
      return payload
    },

    logout() {
      this.token = null
      this.user = null
      this.pending2fa = null
      this.lastLogin = null
      for (const store of [localStorage, sessionStorage]) {
        store.removeItem(TOKEN_KEY)
        store.removeItem(USER_KEY)
      }
    },

    async fetchMe() {
      const { data } = await api.get('/auth/me')
      this.user = data.data
      const store = this.remember ? localStorage : sessionStorage
      store.setItem(USER_KEY, JSON.stringify(this.user))
    }
  }
})
