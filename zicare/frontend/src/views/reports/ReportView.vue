<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Laporan</h1>
        <p class="page-subtitle">Analisis penjualan dan pembayaran</p>
      </div>
      <button v-if="reportData" class="btn btn-success" @click="exportToCSV">
        <Download class="h-4 w-4" />
        Export CSV
      </button>
    </div>

    <div class="tabs no-print">
      <button
        v-for="t in tabs"
        :key="t.id"
        :class="['tab', { active: activeTab === t.id }]"
        @click="activeTab = t.id"
      >
        {{ t.label }}
      </button>
    </div>

    <div class="card" style="margin-top: 1rem">
      <div v-if="activeTab === 'daily'" class="filters">
        <div class="filter-group">
          <label>Tanggal</label>
          <input v-model="dailyDate" type="date" class="input" />
        </div>
        <button class="btn btn-primary" @click="loadDaily">
          <Search class="h-4 w-4" />
          Generate
        </button>
      </div>
      <div v-if="activeTab === 'monthly'" class="filters">
        <div class="filter-group">
          <label>Tahun</label>
          <input v-model.number="monthYear" type="number" placeholder="Tahun" class="input" />
        </div>
        <div class="filter-group">
          <label>Bulan</label>
          <input v-model.number="monthMonth" type="number" min="1" max="12" placeholder="Bulan" class="input" />
        </div>
        <button class="btn btn-primary" @click="loadMonthly">
          <Search class="h-4 w-4" />
          Generate
        </button>
      </div>
      <div v-if="activeTab === 'product' || activeTab === 'payment'" class="filters">
        <div class="filter-group">
          <label>Dari Tanggal</label>
          <input v-model="dateFrom" type="date" class="input" />
        </div>
        <div class="filter-group">
          <label>Sampai Tanggal</label>
          <input v-model="dateTo" type="date" class="input" />
        </div>
        <button class="btn btn-primary" @click="loadRange">
          <Search class="h-4 w-4" />
          Generate
        </button>
      </div>

      <div v-if="reportData" class="report-output animate-fade-in">
        <!-- Daily Sales Report -->
        <div v-if="activeTab === 'daily'" class="space-y-6">
          <div class="report-summary">
            <h3>Ringkasan</h3>
            <div class="summary-cards">
              <div class="stat-card">
                <div class="flex items-center gap-4">
                  <div class="icon-badge bg-gradient-to-br from-primary-400 to-primary-600">
                    <Receipt class="h-6 w-6" />
                  </div>
                  <div>
                    <p class="stat-label">Total Transaksi</p>
                    <p class="stat-value">{{ reportData.summary.total_transactions }}</p>
                  </div>
                </div>
              </div>
              <div class="stat-card">
                <div class="flex items-center gap-4">
                  <div class="icon-badge bg-gradient-to-br from-success-400 to-success-600">
                    <DollarSign class="h-6 w-6" />
                  </div>
                  <div>
                    <p class="stat-label">Total Revenue</p>
                    <p class="stat-value">{{ formatCurrency(reportData.summary.total_revenue) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div>
            <h3>Daftar Transaksi</h3>
            <div class="table-card">
              <div class="overflow-x-auto">
                <table class="table">
                  <thead>
                    <tr>
                      <th>No Invoice</th>
                      <th>Tanggal</th>
                      <th>Kasir</th>
                      <th>Pelanggan</th>
                      <th>Metode</th>
                      <th class="text-right">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="t in reportData.transactions" :key="t.id">
                      <td class="font-medium">{{ t.invoice_number }}</td>
                      <td>{{ formatDate(t.created_at) }}</td>
                      <td>{{ t.cashier_name }}</td>
                      <td>{{ t.customer_name || 'Walk-in' }}</td>
                      <td>
                        <span class="badge badge-primary capitalize">{{ t.payment_method }}</span>
                      </td>
                      <td class="text-right font-semibold">{{ formatCurrency(t.total_amount) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Monthly Sales Report -->
        <div v-if="activeTab === 'monthly'" class="space-y-6">
          <div class="report-summary">
            <h3>Ringkasan Bulanan</h3>
            <div class="summary-cards">
              <div class="stat-card">
                <div class="flex items-center gap-4">
                  <div class="icon-badge bg-gradient-to-br from-primary-400 to-primary-600">
                    <Receipt class="h-6 w-6" />
                  </div>
                  <div>
                    <p class="stat-label">Total Transaksi</p>
                    <p class="stat-value">{{ reportData.summary.total_transactions }}</p>
                  </div>
                </div>
              </div>
              <div class="stat-card">
                <div class="flex items-center gap-4">
                  <div class="icon-badge bg-gradient-to-br from-success-400 to-success-600">
                    <DollarSign class="h-6 w-6" />
                  </div>
                  <div>
                    <p class="stat-label">Total Revenue</p>
                    <p class="stat-value">{{ formatCurrency(reportData.summary.total_revenue) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div>
            <h3>Breakdown Harian</h3>
            <div class="table-card">
              <div class="overflow-x-auto">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Tanggal</th>
                      <th class="text-right">Jumlah Transaksi</th>
                      <th class="text-right">Revenue</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="d in reportData.daily_breakdown" :key="d.date">
                      <td class="font-medium">{{ formatDate(d.date) }}</td>
                      <td class="text-right">{{ d.transactions }}</td>
                      <td class="text-right font-semibold">{{ formatCurrency(d.revenue) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Product Sales Report -->
        <div v-if="activeTab === 'product'">
          <h3>Penjualan Produk</h3>
          <div class="table-card">
            <div class="overflow-x-auto">
              <table class="table">
                <thead>
                  <tr>
                    <th>Kode Produk</th>
                    <th>Nama Produk</th>
                    <th class="text-right">Qty Terjual</th>
                    <th class="text-right">Total Revenue</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in reportData" :key="p.product_code">
                    <td class="font-medium">{{ p.product_code }}</td>
                    <td>{{ p.product_name }}</td>
                    <td class="text-right">{{ p.total_qty }}</td>
                    <td class="text-right font-semibold">{{ formatCurrency(p.total_revenue) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Payment Report -->
        <div v-if="activeTab === 'payment'">
          <h3>Laporan Pembayaran</h3>
          <div class="table-card">
            <div class="overflow-x-auto">
              <table class="table">
                <thead>
                  <tr>
                    <th>No Invoice</th>
                    <th>Metode</th>
                    <th>Provider</th>
                    <th>Referensi</th>
                    <th class="text-right">Nominal</th>
                    <th>Status</th>
                    <th>Waktu</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in reportData" :key="p.id">
                    <td class="font-medium">{{ p.invoice_number }}</td>
                    <td>
                      <span class="badge badge-primary capitalize">{{ p.payment_method }}</span>
                    </td>
                    <td>{{ p.provider || '-' }}</td>
                    <td>{{ p.payment_reference || '-' }}</td>
                    <td class="text-right font-semibold">{{ formatCurrency(p.amount) }}</td>
                    <td>
                      <span class="badge badge-slate">{{ p.payment_status }}</span>
                    </td>
                    <td>{{ formatDateTime(p.payment_time || p.created_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon">
          <FileBarChart class="h-8 w-8" />
        </div>
        <p class="text-sm font-medium text-slate-500">Pilih filter dan klik Generate</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import api from '@/services/api'
import { toast } from '@/composables/feedback'
import { Download, Search, Receipt, DollarSign, FileBarChart } from 'lucide-vue-next'

const tabs = [
  { id: 'daily', label: 'Penjualan Harian' },
  { id: 'monthly', label: 'Penjualan Bulanan' },
  { id: 'product', label: 'Penjualan Produk' },
  { id: 'payment', label: 'Laporan Pembayaran' }
]

const activeTab = ref('daily')
const reportData = ref(null)

// Clear stale data whenever the user switches report type. Without this the
// previous report's shape (object vs array) leaks into the new tab's template.
watch(activeTab, () => {
  reportData.value = null
})
const dailyDate = ref(new Date().toISOString().slice(0, 10))
const monthYear = ref(new Date().getFullYear())
const monthMonth = ref(new Date().getMonth() + 1)
const dateFrom = ref(new Date().toISOString().slice(0, 7) + '-01')
const dateTo = ref(new Date().toISOString().slice(0, 10))

async function loadDaily() {
  try {
    const { data } = await api.get('/reports/daily-sales', { params: { date: dailyDate.value } })
    reportData.value = data.data
  } catch (e) {
    toast.error('Gagal memuat laporan penjualan harian.')
  }
}

async function loadMonthly() {
  try {
    const { data } = await api.get('/reports/monthly-sales', { params: { year: monthYear.value, month: monthMonth.value } })
    reportData.value = data.data
  } catch (e) {
    toast.error('Gagal memuat laporan penjualan bulanan.')
  }
}

async function loadRange() {
  const endpoint = activeTab.value === 'product' ? '/reports/product-sales' : '/reports/payments'
  try {
    const { data } = await api.get(endpoint, { params: { date_from: dateFrom.value, date_to: dateTo.value } })
    reportData.value = data.data
  } catch (e) {
    toast.error('Gagal memuat laporan.')
  }
}

function formatCurrency(val) {
  return 'Rp ' + Number(val || 0).toLocaleString('id-ID')
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID')
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID')
}

function exportToCSV() {
  if (!reportData.value) return

  let csv = ''
  let filename = ''

  if (activeTab.value === 'daily') {
    csv = 'No Invoice,Tanggal,Kasir,Pelanggan,Metode,Total\n'
    reportData.value.transactions.forEach(t => {
      csv += `"${t.invoice_number}","${formatDate(t.created_at)}","${t.cashier_name}","${t.customer_name || 'Walk-in'}","${t.payment_method}","${t.total_amount}"\n`
    })
    filename = `daily-sales-${dailyDate.value}.csv`
  } else if (activeTab.value === 'monthly') {
    csv = 'Tanggal,Jumlah Transaksi,Revenue\n'
    reportData.value.daily_breakdown.forEach(d => {
      csv += `"${formatDate(d.date)}","${d.transactions}","${d.revenue}"\n`
    })
    filename = `monthly-sales-${monthYear.value}-${monthMonth.value}.csv`
  } else if (activeTab.value === 'product') {
    csv = 'Kode Produk,Nama Produk,Qty Terjual,Total Revenue\n'
    reportData.value.forEach(p => {
      csv += `"${p.product_code}","${p.product_name}","${p.total_qty}","${p.total_revenue}"\n`
    })
    filename = `product-sales-${dateFrom.value}-to-${dateTo.value}.csv`
  } else if (activeTab.value === 'payment') {
    csv = 'No Invoice,Metode,Provider,Referensi,Nominal,Status,Waktu\n'
    reportData.value.forEach(p => {
      csv += `"${p.invoice_number}","${p.payment_method}","${p.provider || '-'}","${p.payment_reference || '-'}","${p.amount}","${p.payment_status}","${formatDateTime(p.payment_time || p.created_at)}"\n`
    })
    filename = `payments-${dateFrom.value}-to-${dateTo.value}.csv`
  }

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = filename
  link.click()
  URL.revokeObjectURL(link.href)
}
</script>

<style scoped>
.page-header { margin-bottom: 1rem; }

.tabs {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0.375rem;
  background: rgba(255, 255, 255, 0.7);
  border: 1px solid var(--border);
  border-radius: 9999px;
  backdrop-filter: blur(8px);
}

.tab {
  padding: 0.5rem 1.1rem;
  border: 1px solid transparent;
  background: transparent;
  border-radius: 9999px;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--secondary);
  transition: all 0.2s ease;
}

.tab:hover {
  color: #2563eb;
  background: var(--primary-light);
}

.tab.active {
  background: linear-gradient(135deg, #6366f1 0%, #2563eb 55%, #0ea5e9 100%);
  color: white;
  box-shadow: 0 10px 24px -14px rgba(37, 99, 235, 0.30), 0 4px 12px -8px rgba(15, 23, 42, 0.14);
}

.filters {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  min-width: 160px;
}

.filter-group label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--secondary);
}

.report-output h3 { margin-bottom: 1rem; font-size: 1.05rem; font-weight: 700; color: #1e293b; }

.report-summary { margin-bottom: 0; }
.report-summary h3 { margin-bottom: 1rem; font-size: 1.05rem; font-weight: 700; color: #1e293b; }

.summary-cards { display: flex; gap: 1rem; flex-wrap: wrap; }
.summary-cards .stat-card { flex: 1 1 220px; }

.icon-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 3rem;
  width: 3rem;
  flex-shrink: 0;
  border-radius: 1rem;
  color: white;
  box-shadow: 0 10px 24px -14px rgba(37, 99, 235, 0.30), 0 4px 12px -8px rgba(15, 23, 42, 0.14);
}

.stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  color: var(--secondary);
}

.stat-value {
  margin-top: 0.25rem;
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
}

@media print {
  .tabs { display: none; }
}
</style>
