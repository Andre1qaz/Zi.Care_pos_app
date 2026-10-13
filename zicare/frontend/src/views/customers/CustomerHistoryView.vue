<template>
  <div class="page">
    <div class="page-header">
      <div>
        <button class="btn btn-secondary btn-sm" @click="router.back()">
          <ArrowLeft class="h-4 w-4" />
          Kembali
        </button>
        <h1 class="page-title mt-4">Riwayat Transaksi Pelanggan</h1>
        <p class="page-subtitle">{{ customer?.customer_name }}</p>
      </div>
    </div>

    <div v-if="loading" class="flex flex-col items-center justify-center gap-3 py-20 text-slate-500">
      <span class="spinner"></span>
      <span class="text-sm">Memuat data...</span>
    </div>

    <template v-else-if="customer">
      <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="stat-card">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
              <Receipt class="h-6 w-6" />
            </div>
            <div>
              <p class="text-sm font-medium text-slate-500">Total Transaksi</p>
              <p class="text-2xl font-bold text-slate-900">{{ stats.total_transactions }}</p>
            </div>
          </div>
        </div>
        <div class="stat-card">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-success-50 text-success-600">
              <Wallet class="h-6 w-6" />
            </div>
            <div>
              <p class="text-sm font-medium text-slate-500">Total Belanja</p>
              <p class="text-2xl font-bold text-slate-900">{{ formatCurrency(stats.total_spent) }}</p>
            </div>
          </div>
        </div>
        <div class="stat-card">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-warning-50 text-warning-600">
              <Calendar class="h-6 w-6" />
            </div>
            <div>
              <p class="text-sm font-medium text-slate-500">Transaksi Terakhir</p>
              <p class="text-2xl font-bold text-slate-900">{{ formatDate(stats.last_transaction) }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="table-card">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <h3 class="section-title">Daftar Transaksi</h3>
          <span class="chip">{{ invoices.length }} transaksi</span>
        </div>
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th>No. Invoice</th>
                <th>Tanggal</th>
                <th>Kasir</th>
                <th>Metode</th>
                <th>Status</th>
                <th>Total</th>
                <th class="text-right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="invoice in invoices" :key="invoice.id">
                <td class="font-mono text-sm">{{ invoice.invoice_number }}</td>
                <td>{{ formatDate(invoice.created_at) }}</td>
                <td>{{ invoice.cashier_name || '-' }}</td>
                <td><span class="badge" :class="invoice.payment_method">{{ invoice.payment_method }}</span></td>
                <td><span class="badge" :class="invoice.payment_status">{{ invoice.payment_status }}</span></td>
                <td class="font-medium">{{ formatCurrency(invoice.total_amount) }}</td>
                <td class="text-right">
                  <router-link :to="`/invoices/${invoice.id}`" class="btn btn-primary btn-sm">
                    <Eye class="h-3.5 w-3.5" />
                    Detail
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="invoices.length === 0" class="empty-state">
          <div class="empty-icon">
            <Receipt class="h-7 w-7" />
          </div>
          <p class="text-sm font-medium text-slate-500">Belum ada transaksi</p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { Receipt, Wallet, Calendar, ArrowLeft, Eye } from 'lucide-vue-next'

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
    customer.value = data.data
  } catch (e) {
    console.error('Failed to load customer:', e)
  }
}

async function loadTransactions() {
  try {
    const { data } = await api.get('/reports/customer-transactions', {
      params: { customer_id: route.params.id }
    })
    invoices.value = (data.data && data.data.transactions) || []
    stats.value = (data.data && data.data.summary) || {
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
.badge {
  text-transform: capitalize;
}

.badge.cash,
.badge.paid,
.badge.synced {
  background: #d1fae5;
  color: #065f46;
}

.badge.qris {
  background: #dbeafe;
  color: #1e40af;
}

.badge.transfer,
.badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.badge.ewallet {
  background: #fce7f3;
  color: #9d174d;
}
</style>
