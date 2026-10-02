import { defineStore } from 'pinia'
import api from '@/services/api'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    stats: null,
    loading: false
  }),

  actions: {
    async fetchStats() {
      this.loading = true
      try {
        const { data } = await api.get('/dashboard')
        this.stats = data.data
      } finally {
        this.loading = false
      }
    }
  }
})
