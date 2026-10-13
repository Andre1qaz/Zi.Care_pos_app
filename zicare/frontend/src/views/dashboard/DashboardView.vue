<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Ringkasan performa bisnis Anda</p>
      </div>
      <select
        v-model="dateRange"
        @change="fetchStats"
        class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-inner-soft transition-all duration-200 hover:border-slate-300 focus:border-primary-400 focus:outline-none focus:ring-4 focus:ring-primary-500/10"
      >
        <option value="today">Hari Ini</option>
        <option value="week">7 Hari Terakhir</option>
        <option value="month">30 Hari Terakhir</option>
      </select>
    </div>

    <div v-if="store.loading" class="flex items-center justify-center gap-3 py-16 text-slate-500">
      <span class="spinner"></span>
      <span class="text-sm font-medium">Memuat data...</span>
    </div>

    <template v-else-if="store.stats">
      <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-card animate-fade-in-up">
          <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-success-400 to-success-600 text-white shadow-bubble-sm">
              <DollarSign class="h-7 w-7" />
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Penjualan ({{ periodLabel }})</p>
              <p class="mt-1 truncate text-2xl font-extrabold text-slate-900">{{ formatCurrency(store.stats.total_sales_today) }}</p>
            </div>
          </div>
        </div>

        <div class="stat-card animate-fade-in-up">
          <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-400 to-primary-600 text-white shadow-bubble-sm">
              <Receipt class="h-7 w-7" />
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Transaksi</p>
              <p class="mt-1 truncate text-2xl font-extrabold text-slate-900">{{ store.stats.total_transactions }}</p>
            </div>
          </div>
        </div>

        <div class="stat-card animate-fade-in-up">
          <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-400 to-violet-600 text-white shadow-bubble-sm">
              <Package class="h-7 w-7" />
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Produk</p>
              <p class="mt-1 truncate text-2xl font-extrabold text-slate-900">{{ store.stats.total_products || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="stat-card animate-fade-in-up">
          <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-warning-400 to-warning-600 text-white shadow-bubble-sm">
              <TrendingUp class="h-7 w-7" />
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Revenue Bulan Ini</p>
              <p class="mt-1 truncate text-2xl font-extrabold text-slate-900">{{ formatCurrency(store.stats.monthly_revenue) }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card mt-6">
        <div class="mb-5 flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-400 to-primary-600 text-white shadow-bubble-sm">
            <TrendingUp class="h-5 w-5" />
          </div>
          <div>
            <h3 class="section-title">Revenue 30 Hari Terakhir</h3>
            <p class="mt-0.5 text-xs text-slate-400">Tren pendapatan harian</p>
          </div>
        </div>
        <div class="h-80">
          <canvas ref="revenueChart"></canvas>
        </div>
      </div>

      <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="table-card">
          <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-success-400 to-success-600 text-white shadow-bubble-sm">
              <Trophy class="h-5 w-5" />
            </div>
            <h3 class="section-title">Produk Terlaris</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="table">
              <thead>
                <tr>
                  <th>Produk</th>
                  <th class="text-right">Qty</th>
                  <th class="text-right">Revenue</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in store.stats.top_selling_products" :key="p.product_name">
                  <td class="font-medium">{{ p.product_name }}</td>
                  <td class="text-right">{{ p.total_qty }}</td>
                  <td class="text-right font-medium">{{ formatCurrency(p.revenue) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div
            v-if="!store.stats.top_selling_products?.length"
            class="empty-state"
          >
            <div class="empty-icon">
              <Trophy class="h-7 w-7" />
            </div>
            <p class="text-sm font-medium text-slate-500">Belum ada data</p>
          </div>
        </div>

        <div class="card">
          <div class="mb-5 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-violet to-brand-indigo text-white shadow-bubble-sm">
              <CreditCard class="h-5 w-5" />
            </div>
            <h3 class="section-title">Statistik Pembayaran</h3>
          </div>
          <div class="h-48">
            <canvas ref="paymentChart"></canvas>
          </div>
          <div class="mt-5 overflow-x-auto">
            <table class="table">
              <thead>
                <tr>
                  <th>Metode</th>
                  <th class="text-right">Jumlah</th>
                  <th class="text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in store.stats.payment_statistics" :key="p.payment_method">
                  <td>
                    <span
                      :class="[
                        'badge capitalize',
                        getPaymentBadgeClass(p.payment_method)
                      ]"
                    >
                      {{ p.payment_method }}
                    </span>
                  </td>
                  <td class="text-right">{{ p.count }}</td>
                  <td class="text-right font-medium">{{ formatCurrency(p.total) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div
            v-if="!store.stats.payment_statistics?.length"
            class="empty-state"
          >
            <div class="empty-icon">
              <CreditCard class="h-7 w-7" />
            </div>
            <p class="text-sm font-medium text-slate-500">Belum ada data</p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import { toast } from '@/composables/feedback'
import Chart from 'chart.js/auto'
import {
  DollarSign,
  Receipt,
  Package,
  TrendingUp,
  Trophy,
  CreditCard
} from 'lucide-vue-next'

const store = useDashboardStore()
const dateRange = ref('today')
const periodLabel = computed(
  () => ({ today: 'Hari Ini', week: '7 Hari Terakhir', month: '30 Hari Terakhir' })[dateRange.value] || 'Hari Ini'
)
const revenueChart = ref(null)
const paymentChart = ref(null)
let revenueChartInstance = null
let paymentChartInstance = null

onUnmounted(() => {
  if (revenueChartInstance) revenueChartInstance.destroy()
  if (paymentChartInstance) paymentChartInstance.destroy()
})

function formatCurrency(val) {
  return 'Rp ' + Number(val || 0).toLocaleString('id-ID')
}

function getPaymentBadgeClass(method) {
  const classes = {
    cash: 'bg-green-100 text-green-800',
    qris: 'bg-blue-100 text-blue-800',
    transfer: 'bg-amber-100 text-amber-800',
    ewallet: 'bg-pink-100 text-pink-800'
  }
  return classes[method] || 'bg-slate-100 text-slate-800'
}

async function fetchStats() {
  try {
    await store.fetchStats(dateRange.value)
    await nextTick()
    initCharts()
  } catch (e) {
    toast.error('Gagal memuat data dashboard.')
  }
}

function initCharts() {
  if (!store.stats) return

  // Revenue Chart
  if (revenueChart.value && store.stats.monthly_revenue_chart) {
    if (revenueChartInstance) revenueChartInstance.destroy()
    
    const ctx = revenueChart.value.getContext('2d')
    const data = store.stats.monthly_revenue_chart
    revenueChartInstance = new Chart(ctx, {
      type: 'line',
      data: {
        labels: data.map(d => new Date(d.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })),
        datasets: [{
          label: 'Revenue',
          data: data.map(d => d.revenue),
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37, 99, 235, 0.1)',
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { 
            beginAtZero: true,
            grid: { color: 'rgba(0, 0, 0, 0.05)' }
          },
          x: {
            grid: { display: false }
          }
        }
      }
    })
  }

  // Payment Chart
  if (paymentChart.value && store.stats.payment_statistics) {
    if (paymentChartInstance) paymentChartInstance.destroy()
    
    const ctx = paymentChart.value.getContext('2d')
    const data = store.stats.payment_statistics
    paymentChartInstance = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: data.map(d => d.payment_method),
        datasets: [{
          data: data.map(d => d.total),
          backgroundColor: ['#16a34a', '#2563eb', '#d97706', '#dc2626'],
          borderWidth: 0,
          cutout: '70%'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
          legend: { 
            position: 'bottom',
            labels: {
              padding: 20,
              usePointStyle: true
            }
          } 
        }
      }
    })
  }
}

onMounted(() => fetchStats())
</script>
