import { defineStore } from 'pinia'
import api from '@/services/api'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    stats: null,
    loading: false
  }),

  actions: {
    async fetchStats(range = 'today') {
      this.loading = true
      try {
        const { data } = await api.get('/dashboard', { params: { range } })
        this.stats = data.data
      } finally {
        this.loading = false
      }
    }
  }
})
