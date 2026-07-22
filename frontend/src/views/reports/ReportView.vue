<template>
  <div>
    <div class="page-header">
      <h1>Laporan</h1>
      <button v-if="reportData" class="btn btn-success" @click="exportToCSV">📥 Export CSV</button>
    </div>
    <div class="tabs no-print">
      <button v-for="t in tabs" :key="t.id" :class="['tab', { active: activeTab === t.id }]" @click="activeTab = t.id">
        {{ t.label }}
      </button>
    </div>

    <div class="card" style="margin-top: 1rem">
      <div v-if="activeTab === 'daily'" class="filters">
        <input v-model="dailyDate" type="date" />
        <button class="btn btn-primary" @click="loadDaily">Generate</button>
      </div>
      <div v-if="activeTab === 'monthly'" class="filters">
        <input v-model.number="monthYear" type="number" placeholder="Tahun" />
        <input v-model.number="monthMonth" type="number" min="1" max="12" placeholder="Bulan" />
        <button class="btn btn-primary" @click="loadMonthly">Generate</button>
      </div>
      <div v-if="activeTab === 'product' || activeTab === 'payment'" class="filters">
        <input v-model="dateFrom" type="date" />
        <input v-model="dateTo" type="date" />
        <button class="btn btn-primary" @click="loadRange">Generate</button>
      </div>

      <div v-if="reportData" class="report-output">
        <!-- Daily Sales Report -->
        <div v-if="activeTab === 'daily'">
          <div class="report-summary">
            <h3>Ringkasan</h3>
            <div class="summary-cards">
              <div class="summary-card">
                <label>Total Transaksi</label>
                <strong>{{ reportData.summary.total_transactions }}</strong>
              </div>
              <div class="summary-card">
                <label>Total Revenue</label>
                <strong>{{ formatCurrency(reportData.summary.total_revenue) }}</strong>
              </div>
            </div>
          </div>
          <h3>Daftar Transaksi</h3>
          <table>
            <thead>
              <tr>
                <th>No Invoice</th>
                <th>Tanggal</th>
                <th>Kasir</th>
                <th>Pelanggan</th>
                <th>Metode</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in reportData.transactions" :key="t.id">
                <td>{{ t.invoice_number }}</td>
                <td>{{ formatDate(t.created_at) }}</td>
                <td>{{ t.cashier_name }}</td>
                <td>{{ t.customer_name || 'Walk-in' }}</td>
                <td>{{ t.payment_method }}</td>
                <td>{{ formatCurrency(t.total_amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Monthly Sales Report -->
        <div v-if="activeTab === 'monthly'">
          <div class="report-summary">
            <h3>Ringkasan Bulanan</h3>
            <div class="summary-cards">
              <div class="summary-card">
                <label>Total Transaksi</label>
                <strong>{{ reportData.summary.total_transactions }}</strong>
              </div>
              <div class="summary-card">
                <label>Total Revenue</label>
                <strong>{{ formatCurrency(reportData.summary.total_revenue) }}</strong>
              </div>
            </div>
          </div>
          <h3>Breakdown Harian</h3>
          <table>
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Jumlah Transaksi</th>
                <th>Revenue</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in reportData.daily_breakdown" :key="d.date">
                <td>{{ formatDate(d.date) }}</td>
                <td>{{ d.transactions }}</td>
                <td>{{ formatCurrency(d.revenue) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Product Sales Report -->
        <div v-if="activeTab === 'product'">
          <h3>Penjualan Produk</h3>
          <table>
            <thead>
              <tr>
                <th>Kode Produk</th>
                <th>Nama Produk</th>
                <th>Qty Terjual</th>
                <th>Total Revenue</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in reportData" :key="p.product_code">
                <td>{{ p.product_code }}</td>
                <td>{{ p.product_name }}</td>
                <td>{{ p.total_qty }}</td>
                <td>{{ formatCurrency(p.total_revenue) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Payment Report -->
        <div v-if="activeTab === 'payment'">
          <h3>Laporan Pembayaran</h3>
          <table>
            <thead>
              <tr>
                <th>No Invoice</th>
                <th>Metode</th>
                <th>Provider</th>
                <th>Referensi</th>
                <th>Nominal</th>
                <th>Status</th>
                <th>Waktu</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in reportData" :key="p.id">
                <td>{{ p.invoice_number }}</td>
                <td>{{ p.payment_method }}</td>
                <td>{{ p.provider || '-' }}</td>
                <td>{{ p.payment_reference || '-' }}</td>
                <td>{{ formatCurrency(p.amount) }}</td>
                <td>{{ p.payment_status }}</td>
                <td>{{ formatDateTime(p.payment_time || p.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <p v-else class="empty">Pilih filter dan klik Generate</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/services/api'

const tabs = [
  { id: 'daily', label: 'Penjualan Harian' },
  { id: 'monthly', label: 'Penjualan Bulanan' },
  { id: 'product', label: 'Penjualan Produk' },
  { id: 'payment', label: 'Laporan Pembayaran' }
]

const activeTab = ref('daily')
const reportData = ref(null)
const dailyDate = ref(new Date().toISOString().slice(0, 10))
const monthYear = ref(new Date().getFullYear())
const monthMonth = ref(new Date().getMonth() + 1)
const dateFrom = ref(new Date().toISOString().slice(0, 7) + '-01')
const dateTo = ref(new Date().toISOString().slice(0, 10))

async function loadDaily() {
  const { data } = await api.get('/reports/daily-sales', { params: { date: dailyDate.value } })
  reportData.value = data.data
}

async function loadMonthly() {
  const { data } = await api.get('/reports/monthly-sales', { params: { year: monthYear.value, month: monthMonth.value } })
  reportData.value = data.data
}

async function loadRange() {
  const endpoint = activeTab.value === 'product' ? '/reports/product-sales' : '/reports/payments'
  const { data } = await api.get(endpoint, { params: { date_from: dateFrom.value, date_to: dateTo.value } })
  reportData.value = data.data
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
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.page-header h1 { margin: 0; font-size: 1.5rem; font-weight: 700; }
.tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.tab { padding: 0.5rem 1rem; border: 1px solid var(--border); background: white; border-radius: var(--radius-sm); cursor: pointer; font-size: 0.875rem; transition: all 0.2s; }
.tab:hover { border-color: var(--primary); }
.tab.active { background: var(--primary); color: white; border-color: var(--primary); }
.filters { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filters input { padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); }
.report-output { background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); overflow: auto; max-height: 500px; }
.empty { color: var(--secondary); text-align: center; padding: 3rem 1rem; }
.report-summary { margin-bottom: 2rem; }
.report-summary h3 { margin-bottom: 1rem; font-size: 1.1rem; font-weight: 600; }
.summary-cards { display: flex; gap: 1rem; flex-wrap: wrap; }
.summary-card { background: white; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border); min-width: 180px; }
.summary-card label { display: block; font-size: 0.875rem; color: var(--secondary); margin-bottom: 0.25rem; font-weight: 500; }
.summary-card strong { font-size: 1.5rem; color: #1f2937; }
.report-output h3 { margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.1rem; font-weight: 600; }
.report-output table { width: 100%; border-collapse: collapse; background: white; border-radius: var(--radius-sm); overflow: hidden; }
.report-output th { background: var(--surface); padding: 0.875rem 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: #374151; border-bottom: 1px solid var(--border); text-transform: uppercase; letter-spacing: 0.025em; }
.report-output td { padding: 0.875rem 1rem; border-bottom: 1px solid var(--border); font-size: 0.875rem; }
.report-output tbody tr:hover { background: var(--surface); }
.report-output tbody tr:last-child td { border-bottom: none; }
</style>
