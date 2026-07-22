<template>
  <div>
    <div class="page-header">
      <div>
        <button class="btn" @click="router.back()">← Kembali</button>
        <h1>Riwayat Transaksi Pelanggan</h1>
        <p class="customer-name">{{ customer?.customer_name }}</p>
      </div>
    </div>

    <div v-if="loading" class="loading">Memuat data...</div>

    <template v-else-if="customer">
      <div class="stats-grid">
        <div class="stat-card card">
          <span class="stat-icon">🧾</span>
          <div>
            <span class="stat-label">Total Transaksi</span>
            <span class="stat-value">{{ stats.total_transactions }}</span>
          </div>
        </div>
        <div class="stat-card card">
          <span class="stat-icon">💰</span>
          <div>
            <span class="stat-label">Total Belanja</span>
            <span class="stat-value">{{ formatCurrency(stats.total_spent) }}</span>
          </div>
        </div>
        <div class="stat-card card">
          <span class="stat-icon">📅</span>
          <div>
            <span class="stat-label">Transaksi Terakhir</span>
            <span class="stat-value">{{ formatDate(stats.last_transaction) }}</span>
          </div>
        </div>
      </div>

      <div class="card">
        <h3>Daftar Transaksi</h3>
        <table>
          <thead>
            <tr>
              <th>No. Invoice</th>
              <th>Tanggal</th>
              <th>Kasir</th>
              <th>Metode</th>
              <th>Status</th>
              <th>Total</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="invoice in invoices" :key="invoice.id">
              <td>{{ invoice.invoice_number }}</td>
              <td>{{ formatDate(invoice.created_at) }}</td>
              <td>{{ invoice.cashier_name || '-' }}</td>
              <td><span :class="['payment-badge', invoice.payment_method]">{{ invoice.payment_method }}</span></td>
              <td><span :class="['status-badge', invoice.payment_status]">{{ invoice.payment_status }}</span></td>
              <td>{{ formatCurrency(invoice.total_amount) }}</td>
              <td>
                <router-link :to="`/invoices/${invoice.id}`" class="btn btn-primary btn-sm">Detail</router-link>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-if="invoices.length === 0" class="empty">Belum ada transaksi</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const customer = ref(null)
const invoices = ref([])
const stats = ref({})
const loading = ref(true)

function formatCurrency(val) {
  return 'Rp ' + Number(val || 0).toLocaleString('id-ID')
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID')
}

async function loadCustomer() {
  try {
    const { data } = await api.get(`/customers/${route.params.id}`)
    customer.value = data
  } catch (e) {
    console.error('Failed to load customer:', e)
  }
}

async function loadTransactions() {
  try {
    const { data } = await api.get('/reports/customer-transactions', {
      params: { customer_id: route.params.id }
    })
    invoices.value = data.transactions || []
    stats.value = data.summary || {
      total_transactions: 0,
      total_spent: 0,
      last_transaction: null
    }
  } catch (e) {
    console.error('Failed to load transactions:', e)
    invoices.value = []
    stats.value = { total_transactions: 0, total_spent: 0, last_transaction: null }
  }
}

onMounted(async () => {
  loading.value = true
  await Promise.all([loadCustomer(), loadTransactions()])
  loading.value = false
})
</script>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
.page-header h1 { margin: 0.5rem 0 0; font-size: 1.5rem; font-weight: 700; }
.customer-name { color: var(--secondary); margin: 0; font-size: 0.875rem; }
.loading { text-align: center; padding: 3rem; color: var(--secondary); }
.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.stat-card { display: flex; align-items: center; gap: 1rem; padding: 1.25rem; }
.stat-icon { font-size: 1.5rem; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: var(--primary-light); border-radius: var(--radius-md); }
.stat-label { display: block; font-size: 0.875rem; color: var(--secondary); font-weight: 500; }
.stat-value { display: block; font-size: 1.25rem; font-weight: 700; color: #1f2937; }
h3 { margin-bottom: 1rem; font-size: 1.1rem; font-weight: 600; }
.payment-badge { padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
.payment-badge.cash { background: #d1fae5; color: #065f46; }
.payment-badge.qris { background: #dbeafe; color: #1e40af; }
.payment-badge.transfer { background: #fef3c7; color: #92400e; }
.payment-badge.ewallet { background: #fce7f3; color: #9d174d; }
.status-badge { padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
.status-badge.paid { background: #d1fae5; color: #065f46; }
.status-badge.pending { background: #fef3c7; color: #92400e; }
.empty { color: var(--secondary); text-align: center; padding: 3rem 1rem; }
@media (max-width: 768px) {
  .stats-grid { grid-template-columns: 1fr; }
}
</style>
