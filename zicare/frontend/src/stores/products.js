import { defineStore } from 'pinia'
import api from '@/services/api'

export const useProductStore = defineStore('products', {
  state: () => ({
    products: [],
    categories: [],
    loading: false,
    total: 0
  }),

  actions: {
    async fetchProducts(params = {}) {
      this.loading = true
      try {
        const { data } = await api.get('/products', { params })
        this.products = data.data
        this.total = data.meta?.total || 0
      } finally {
        this.loading = false
      }
    },

    async fetchCategories() {
      const { data } = await api.get('/categories')
      this.categories = data.data
    },

    async createProduct(payload) {
      const { data } = await api.post('/products', payload)
      return data.data
    },

    async updateProduct(id, payload) {
      const { data } = await api.put(`/products/${id}`, payload)
      return data.data
    },

    async deleteProduct(id) {
      await api.delete(`/products/${id}`)
    }
  }
})
