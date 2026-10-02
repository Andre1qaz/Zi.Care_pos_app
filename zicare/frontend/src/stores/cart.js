import { defineStore } from 'pinia'

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
    customerId: null,
    customerName: 'Walk-in Customer'
  }),

  getters: {
    totalItems: (state) => state.items.reduce((sum, i) => sum + i.quantity, 0),
    subtotal: (state) => state.items.reduce((sum, i) => sum + i.price * i.quantity, 0),
    total: (state) => state.items.reduce((sum, i) => sum + i.price * i.quantity, 0)
  },

  actions: {
    addItem(product) {
      const existing = this.items.find((i) => i.product_id === product.id)
      const productType = product.product_type || 'barang'

      if (existing) {
        if (productType === 'barang') {
          if (existing.quantity < product.stock) {
            existing.quantity++
          }
        } else {
          // For jasa, quantity doesn't increase (service is singular)
          // Or you could allow multiple if the service can be booked multiple times
          existing.quantity++
        }
      } else {
        this.items.push({
          product_id: product.id,
          product_name: product.product_name,
          product_code: product.product_code,
          price: parseFloat(product.price),
          quantity: 1,
          stock: product.stock,
          product_type: productType,
          service_status: product.service_status
        })
      }
    },

    updateQuantity(productId, quantity) {
      const item = this.items.find((i) => i.product_id === productId)
      if (item) {
        if (item.product_type === 'barang') {
          item.quantity = Math.max(1, Math.min(quantity, item.stock))
        } else {
          // For jasa, allow any quantity (service can be booked multiple times)
          item.quantity = Math.max(1, quantity)
        }
      }
    },

    removeItem(productId) {
      this.items = this.items.filter((i) => i.product_id !== productId)
    },

    setCustomer(id, name) {
      this.customerId = id || null
      this.customerName = name || 'Walk-in Customer'
    },

    clear() {
      this.items = []
      this.customerId = null
      this.customerName = 'Walk-in Customer'
    }
  }
})