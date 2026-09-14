<template>
  <div>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Ringkasan performa bisnis Anda</p>
      </div>
      <select
        v-model="dateRange"
        @change="fetchStats"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-all hover:border-slate-400 focus:border-primary-500 focus:outline-none focus:ring-4 focus:ring-primary-500/10"
      >
        <option value="today">Hari Ini</option>
        <option value="week">7 Hari Terakhir</option>
        <option value="month">30 Hari Terakhir</option>
      </select>
    </div>

    <div v-if="store.loading" class="flex items-center justify-center py-12 text-slate-500">
      <div class="flex items-center gap-2">
        <div class="h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-primary-600"></div>
        <span>Memuat data...</span>
      </div>
    </div>

    <template v-else-if="store.stats">
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-100">
              <DollarSign class="h-6 w-6 text-green-600" />
            </div>
            <div>
              <p class="text-sm font-medium text-slate-500">Penjualan Hari Ini</p>
              <p class="text-2xl font-bold text-slate-900">{{ formatCurrency(store.stats.total_sales_today) }}</p>
            </div>
          </div>
        </div>
        
        <div class="card">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100">
              <Receipt class="h-6 w-6 text-blue-600" />
            </div>
            <div>
              <p class="text-sm font-medium text-slate-500">Total Transaksi</p>
              <p class="text-2xl font-bold text-slate-900">{{ store.stats.total_transactions }}</p>
            </div>
          </div>
        </div>
        
        <div class="card">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100">
              <Package class="h-6 w-6 text-purple-600" />
            </div>
            <div>
              <p class="text-sm font-medium text-slate-500">Total Produk</p>
              <p class="text-2xl font-bold text-slate-900">{{ store.stats.total_products || 0 }}</p>
            </div>
          </div>
        </div>
        
        <div class="card">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100">
              <TrendingUp class="h-6 w-6 text-amber-600" />
            </div>
            <div>
              <p class="text-sm font-medium text-slate-500">Revenue Bulan Ini</p>
              <p class="text-2xl font-bold text-slate-900">{{ formatCurrency(store.stats.monthly_revenue) }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card mt-6">
        <h3 class="mb-4 text-lg font-semibold text-slate-900">Revenue 30 Hari Terakhir</h3>
        <div class="h-80">
          <canvas ref="revenueChart"></canvas>
        </div>
      </div>

      <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card">
          <h3 class="mb-4 text-lg font-semibold text-slate-900">Produk Terlaris</h3>
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
            class="flex flex-col items-center justify-center py-8 text-slate-500"
          >
            <Trophy class="h-8 w-8 mb-2 opacity-50" />
            <p class="text-sm">Belum ada data</p>
          </div>
        </div>
        
        <div class="card">
          <h3 class="mb-4 text-lg font-semibold text-slate-900">Statistik Pembayaran</h3>
          <div class="h-48">
            <canvas ref="paymentChart"></canvas>
          </div>
          <div class="mt-4 overflow-x-auto">
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
                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
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
            class="flex flex-col items-center justify-center py-8 text-slate-500"
          >
            <CreditCard class="h-8 w-8 mb-2 opacity-50" />
            <p class="text-sm">Belum ada data</p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
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
const revenueChart = ref(null)
const paymentChart = ref(null)
let revenueChartInstance = null
let paymentChartInstance = null

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
  await store.fetchStats()
  await nextTick()
  initCharts()
}

function initCharts() {
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
          tension: 0.4,
          smooth: true
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
