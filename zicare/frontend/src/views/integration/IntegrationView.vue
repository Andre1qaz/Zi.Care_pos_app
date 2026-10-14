<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Integrasi Odoo</h1>
        <p class="page-subtitle">Koneksi ke modul Accounting Odoo & sinkronisasi invoice</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button class="btn btn-primary" :disabled="syncingMaster" @click="syncMaster">
          <Database :class="['h-4 w-4', syncingMaster && 'animate-spin']" />
          {{ syncingMaster ? 'Menyinkron...' : 'Sinkron Produk & Pelanggan' }}
        </button>
        <button class="btn btn-secondary" :disabled="loadingStatus" @click="loadStatus">
          <RefreshCw :class="['h-4 w-4', loadingStatus && 'animate-spin']" />
          Uji Koneksi
        </button>
      </div>
    </div>

    <!-- Connection status -->
    <div class="card mb-6">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <span
            class="flex h-14 w-14 items-center justify-center rounded-2xl"
            :class="statusIconClass"
          >
            <Loader2 v-if="loadingStatus" class="h-6 w-6 animate-spin" />
            <Wifi v-else-if="connected" class="h-6 w-6" />
            <WifiOff v-else class="h-6 w-6" />
          </span>
          <div>
            <p class="text-sm font-semibold text-slate-500">Status Koneksi</p>
            <p class="text-lg font-extrabold" :class="connected ? 'text-success-600' : 'text-danger-600'">
              {{ statusTitle }}
            </p>
            <p class="text-sm text-slate-500">{{ odoo?.message || '-' }}</p>
          </div>
        </div>
        <span class="badge" :class="connected ? 'badge-success' : 'badge-danger'">
          {{ connected ? 'Terhubung' : 'Tidak Terhubung' }}
        </span>
      </div>

      <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-slate-50/80 p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Server</p>
          <p class="mt-1 truncate font-semibold text-slate-800" :title="odoo?.url">{{ odoo?.url || '-' }}</p>
        </div>
        <div class="rounded-2xl bg-slate-50/80 p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Database</p>
          <p class="mt-1 font-semibold text-slate-800">{{ odoo?.database || '-' }}</p>
        </div>
        <div class="rounded-2xl bg-slate-50/80 p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Versi Odoo</p>
          <p class="mt-1 font-semibold text-slate-800">{{ odoo?.server_version || '-' }}</p>
        </div>
        <div class="rounded-2xl bg-slate-50/80 p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Journal Penjualan</p>
          <p class="mt-1 font-semibold text-slate-800">{{ odoo?.sale_journals ?? '-' }}</p>
        </div>
      </div>
    </div>

    <!-- Sync queue -->
    <div class="card">
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h3 class="section-title">Invoice Perlu Sinkron</h3>
          <p class="text-sm text-slate-500">Invoice yang belum / gagal terkirim ke Odoo Accounting.</p>
        </div>
        <button
          class="btn btn-primary btn-sm"
          :disabled="syncingAll || pendingInvoices.length === 0"
          @click="syncAll"
        >
          <RefreshCw :class="['h-4 w-4', syncingAll && 'animate-spin']" />
          Sync Semua ({{ pendingInvoices.length }})
        </button>
      </div>

      <div class="table-card">
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th>No. Invoice</th>
                <th>Tanggal</th>
                <th class="text-right">Total</th>
                <th class="text-center">Status Odoo</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inv in pendingInvoices" :key="inv.id">
                <td class="font-mono text-sm font-semibold text-slate-900">{{ inv.invoice_number }}</td>
                <td>{{ formatDate(inv.created_at) }}</td>
                <td class="text-right font-medium">{{ formatCurrency(inv.total_amount) }}</td>
                <td class="text-center">
                  <span class="badge" :class="inv.sync_status === 'failed' ? 'badge-danger' : 'badge-warning'">
                    {{ syncLabel(inv.sync_status) }}
                  </span>
                </td>
                <td class="text-center">
                  <button
                    class="btn btn-primary btn-sm"
                    :disabled="syncingId === inv.id"
                    @click="syncOne(inv)"
                  >
                    <RefreshCw :class="['h-3.5 w-3.5', syncingId === inv.id && 'animate-spin']" />
                    Sync
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="!loadingInvoices && pendingInvoices.length === 0" class="empty-state">
          <div class="empty-icon">
            <CheckCircle2 class="h-7 w-7" />
          </div>
          <p class="text-sm font-medium text-slate-500">Semua invoice sudah tersinkron ke Odoo 🎉</p>
        </div>
        <div v-if="loadingInvoices" class="flex items-center justify-center gap-2 py-10 text-sm text-slate-500">
          <span class="spinner"></span> Memuat...
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { toast } from '@/composables/feedback'
import { RefreshCw, Wifi, WifiOff, Loader2, CheckCircle2, Database } from 'lucide-vue-next'

const odoo = ref(null)
const connected = ref(false)
const loadingStatus = ref(false)

const invoices = ref([])
const loadingInvoices = ref(false)
const syncingId = ref(null)
const syncingAll = ref(false)
const syncingMaster = ref(false)

const pendingInvoices = computed(() =>
  invoices.value.filter((inv) => inv.sync_status !== 'synced')
)

const statusTitle = computed(() => {
  if (loadingStatus.value && !odoo.value) return 'Memeriksa...'
  if (odoo.value && odoo.value.enabled === false) return 'Nonaktif'
  return connected.value ? 'Terhubung ke Odoo' : 'Tidak Terhubung'
})

const statusIconClass = computed(() => {
  if (connected.value) return 'bg-success-100 text-success-600'
  return 'bg-danger-100 text-danger-600'
})

function formatCurrency(v) {
  return 'Rp ' + Number(v || 0).toLocaleString('id-ID')
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID')
}

function syncLabel(status) {
  const labels = {
    pending: 'Belum Sync',
    syncing: 'Sinkron...',
    failed: 'Gagal',
    manual_review: 'Perlu Review',
  }
  return labels[status] || status
}

async function loadStatus() {
  loadingStatus.value = true
  try {
    const { data } = await api.get('/integration/odoo/status')
    odoo.value = data.data
    connected.value = !!data.data?.connected
  } catch (e) {
    connected.value = false
    odoo.value = { message: e.response?.data?.message || 'Gagal menghubungi server.' }
  } finally {
    loadingStatus.value = false
  }
}

async function loadInvoices() {
  loadingInvoices.value = true
  try {
    const { data } = await api.get('/invoices', { params: { limit: 100 } })
    invoices.value = Array.isArray(data.data) ? data.data : []
  } catch (e) {
    invoices.value = []
    toast.error('Gagal memuat daftar invoice.')
  } finally {
    loadingInvoices.value = false
  }
}

async function syncOne(inv) {
  syncingId.value = inv.id
  try {
    const force = inv.sync_status === 'synced'
    const { data } = await api.post(`/invoices/${inv.id}/sync`, null, {
      params: force ? { force: 1 } : {}
    })
    toast.success(data?.message || `Invoice ${inv.invoice_number} tersinkron.`)
  } catch (e) {
    toast.error(e.response?.data?.message || `Gagal sinkron invoice ${inv.invoice_number}.`)
  } finally {
    syncingId.value = null
    await loadInvoices()
  }
}

async function syncMaster() {
  syncingMaster.value = true
  try {
    const { data } = await api.post('/integration/odoo/sync-master')
    const p = data.data.products
    const c = data.data.customers
    toast.success(`Master data tersinkron: ${p.synced}/${p.total} produk, ${c.synced}/${c.total} pelanggan.`)
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal sinkron master data ke Odoo.')
  } finally {
    syncingMaster.value = false
  }
}

async function syncAll() {
  if (pendingInvoices.value.length === 0) return
  syncingAll.value = true
  let ok = 0
  let fail = 0
  for (const inv of [...pendingInvoices.value]) {
    try {
      await api.post(`/invoices/${inv.id}/sync`)
      ok++
    } catch (e) {
      fail++
    }
  }
  syncingAll.value = false
  await Promise.all([loadInvoices(), loadStatus()])
  if (fail === 0) {
    toast.success(`${ok} invoice berhasil disinkronkan ke Odoo.`)
  } else {
    toast.warning(`${ok} berhasil, ${fail} gagal. Cek koneksi Odoo.`)
  }
}

onMounted(() => {
  loadStatus()
  loadInvoices()
})
</script>
