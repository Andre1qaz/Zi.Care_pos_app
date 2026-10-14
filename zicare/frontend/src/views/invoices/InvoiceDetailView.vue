<template>
  <div class="page">
    <div class="page-header no-print">
      <div class="flex items-center gap-3">
        <router-link
          to="/invoices"
          class="btn btn-icon btn-secondary shrink-0"
          aria-label="Kembali ke daftar invoice"
        >
          <ArrowLeft class="h-5 w-5" />
        </router-link>
        <div>
          <h1 class="page-title">Detail Invoice</h1>
          <p class="page-subtitle">{{ invoice?.invoice?.invoice_number }}</p>
        </div>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-if="canContinuePayment"
          @click="showPaymentModal = true"
          class="btn btn-primary"
        >
          <DollarSign class="h-4 w-4" />
          Lanjut Pembayaran
        </button>
        <button
          v-if="canSync"
          @click="syncToOdoo"
          :disabled="syncing"
          class="btn btn-secondary"
          :title="syncButtonTitle"
        >
          <RefreshCw :class="['h-4 w-4', syncing && 'animate-spin']" />
          {{ syncing ? 'Menyinkron...' : syncButtonLabel }}
        </button>
        <button
          @click="printInvoice"
          class="btn btn-secondary"
        >
          <Printer class="h-4 w-4" />
          Cetak
        </button>
        <button
          @click="downloadPdf"
          class="btn btn-secondary"
        >
          <Download class="h-4 w-4" />
          Download PDF
        </button>
        <router-link
          to="/invoices"
          class="btn btn-secondary"
        >
          Kembali
        </router-link>
      </div>
    </div>

    <div v-if="invoice" class="grid gap-6 lg:grid-cols-3">
      <!-- Invoice Details -->
      <div class="lg:col-span-2 space-y-6">
        <div class="card">
          <!-- Receipt header -->
          <div class="mb-6 flex flex-wrap items-start justify-between gap-4 border-b border-dashed border-slate-200 pb-5">
            <div class="flex items-center gap-4">
              <img
                src="@/assets/zicare-logo.jpg"
                alt="Zi.CARE Logo"
                class="h-14 w-14 rounded-2xl object-contain"
              />
              <div>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900">INVOICE</h2>
                <p class="font-mono text-sm text-slate-500">{{ invoice.invoice.invoice_number }}</p>
              </div>
            </div>
            <span class="badge" :class="getStatusClass(invoice.invoice.payment_status)">
              {{ getStatusLabel(invoice.invoice.payment_status) }}
            </span>
            <span class="badge ml-2" :class="syncBadgeClass" :title="syncButtonTitle">
              <Cloud v-if="invoice.invoice.sync_status === 'synced'" class="mr-1 h-3 w-3" />
              {{ syncLabel }}
            </span>
          </div>

          <!-- Meta -->
          <div class="mb-6 grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl bg-slate-50/80 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal</p>
              <p class="mt-1 font-semibold text-slate-800">{{ formatDate(invoice.invoice.created_at) }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50/80 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Jatuh Tempo</p>
              <p class="mt-1 font-semibold text-slate-800" :class="{ 'text-danger-600': isOverdue(invoice.invoice.due_date) }">
                {{ formatDate(invoice.invoice.due_date) }}
              </p>
            </div>
            <div class="rounded-2xl bg-slate-50/80 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kasir</p>
              <p class="mt-1 font-semibold text-slate-800">{{ invoice.cashier?.name || '-' }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50/80 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Pelanggan</p>
              <p class="mt-1 font-semibold text-slate-800">{{ invoice.customer?.customer_name || 'Walk-in Customer' }}</p>
            </div>
          </div>

          <!-- Items -->
          <div class="table-card">
            <table class="table">
              <thead>
                <tr>
                  <th>Produk</th>
                  <th class="text-center">Qty</th>
                  <th class="text-right">Harga</th>
                  <th class="text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in invoice.details" :key="item.id">
                  <td class="font-medium">{{ item.product_name }}</td>
                  <td class="text-center">{{ item.quantity }}</td>
                  <td class="text-right">{{ formatCurrency(item.price) }}</td>
                  <td class="text-right font-semibold">{{ formatCurrency(item.subtotal) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Totals -->
          <div class="mt-6 rounded-2xl border border-slate-100 bg-gradient-to-br from-slate-50 to-primary-50/40 p-5">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-slate-500">Total Tagihan</span>
              <span class="text-xl font-extrabold text-slate-900">{{ formatCurrency(invoice.invoice.total_amount) }}</span>
            </div>
            <div class="mt-3 flex items-center justify-between">
              <span class="text-sm font-medium text-slate-500">Total Dibayar</span>
              <span class="font-semibold text-success-600">{{ formatCurrency(invoice.invoice.paid_amount) }}</span>
            </div>
            <div v-if="invoice.invoice.outstanding_balance > 0" class="mt-3 flex items-center justify-between border-t border-dashed border-slate-200 pt-3">
              <span class="text-sm font-semibold text-slate-900">Sisa Pembayaran</span>
              <span class="text-lg font-extrabold text-danger-600">
                {{ formatCurrency(invoice.invoice.outstanding_balance) }}
              </span>
            </div>
            <div v-if="invoice.invoice.change_amount > 0" class="mt-3 flex items-center justify-between">
              <span class="text-sm font-medium text-slate-500">Kembalian</span>
              <span class="font-semibold text-success-600">{{ formatCurrency(invoice.invoice.change_amount) }}</span>
            </div>
          </div>
        </div>

        <!-- Payment History -->
        <div class="card">
          <h3 class="section-title mb-4">Riwayat Pembayaran</h3>
          <div v-if="paymentHistory.length > 0" class="space-y-3">
            <div
              v-for="(payment, index) in paymentHistory"
              :key="payment.id"
              class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50/70 p-4 transition-colors hover:bg-primary-50/40"
            >
              <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-600">
                  {{ index + 1 }}
                </div>
                <div>
                  <p class="font-semibold text-slate-800">{{ formatCurrency(payment.amount) }}</p>
                  <p class="text-sm text-slate-500">
                    {{ payment.payment_method }} • {{ formatDate(payment.payment_time) }}
                  </p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm text-slate-500">{{ payment.cashier_name || '-' }}</p>
                <p v-if="payment.notes" class="text-xs text-slate-400">{{ payment.notes }}</p>
              </div>
            </div>
          </div>
          <div v-else class="empty-state">
            <div class="empty-icon">
              <Receipt class="h-7 w-7" />
            </div>
            <p class="text-sm text-slate-500">Belum ada pembayaran</p>
          </div>
        </div>
      </div>

      <!-- Payment Progress -->
      <div class="lg:col-span-1">
        <div class="card">
          <h3 class="section-title mb-4">Progress Pembayaran</h3>

          <div class="mb-4">
            <div class="mb-2 flex justify-between text-sm">
              <span class="text-slate-500">Persentase</span>
              <span class="font-bold text-slate-800">{{ paymentPercentage }}%</span>
            </div>
            <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100">
              <div
                class="h-full rounded-full transition-all duration-500"
                :class="progressBarClass"
                :style="{ width: paymentPercentage + '%' }"
              ></div>
            </div>
          </div>

          <div class="space-y-3">
            <div class="flex justify-between">
              <span class="text-sm text-slate-500">Total Tagihan</span>
              <span class="font-semibold text-slate-800">{{ formatCurrency(invoice.invoice.total_amount) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-500">Dibayar</span>
              <span class="font-semibold text-success-600">{{ formatCurrency(invoice.invoice.paid_amount) }}</span>
            </div>
            <div v-if="invoice.invoice.outstanding_balance > 0" class="flex justify-between border-t border-dashed border-slate-200 pt-3">
              <span class="text-sm font-semibold text-slate-900">Sisa</span>
              <span class="font-bold text-danger-600">{{ formatCurrency(invoice.invoice.outstanding_balance) }}</span>
            </div>
          </div>

          <div v-if="invoice.invoice.payment_status === 'paid'" class="mt-6 rounded-2xl bg-success-50 p-4 text-center">
            <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-success-100">
              <CheckCircle class="h-7 w-7 text-success-600" />
            </div>
            <p class="font-bold text-success-700">LUNAS</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Modal -->
    <div v-if="showPaymentModal" class="modal-overlay" @click.self="showPaymentModal = false">
      <div class="modal">
        <form @submit.prevent="submitPayment" class="flex flex-col">
          <div class="modal-header">
            <h3 class="modal-title">Tambah Pembayaran</h3>
            <button
              type="button"
              @click="showPaymentModal = false"
              class="btn-icon-sm btn btn-ghost"
              aria-label="Tutup"
            >
              <X class="h-5 w-5" />
            </button>
          </div>

          <div class="modal-body">
            <div v-if="invoice" class="mb-6 rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
              <div class="flex justify-between">
                <span class="text-sm text-slate-500">Invoice</span>
                <span class="font-mono font-semibold text-slate-800">{{ invoice.invoice.invoice_number }}</span>
              </div>
              <div class="mt-2 flex justify-between">
                <span class="text-sm text-slate-500">Total Tagihan</span>
                <span class="font-bold text-slate-800">{{ formatCurrency(invoice.invoice.total_amount) }}</span>
              </div>
              <div class="mt-2 flex justify-between">
                <span class="text-sm text-slate-500">Sudah Dibayar</span>
                <span class="text-slate-700">{{ formatCurrency(invoice.invoice.paid_amount) }}</span>
              </div>
              <div class="mt-2 flex justify-between border-t border-dashed border-slate-200 pt-2">
                <span class="text-sm font-semibold text-slate-900">Sisa Pembayaran</span>
                <span class="text-lg font-bold text-danger-600">
                  {{ formatCurrency(invoice.invoice.outstanding_balance) }}
                </span>
              </div>
            </div>

            <div class="form-group">
              <label>Jumlah Pembayaran</label>
              <input
                v-model.number="paymentForm.amount"
                type="number"
                :max="invoice?.invoice.outstanding_balance"
                min="1"
                required
                class="w-full"
              />
              <p class="form-hint">
                Maksimum: {{ formatCurrency(invoice?.invoice.outstanding_balance || 0) }}
              </p>
            </div>

            <div class="form-group">
              <label>Metode Pembayaran</label>
              <select v-model="paymentForm.payment_method" class="w-full" required>
                <option value="cash">Tunai</option>
                <option value="qris">QRIS</option>
                <option value="transfer">Transfer</option>
                <option value="ewallet">E-Wallet</option>
              </select>
            </div>

            <template v-if="paymentForm.payment_method !== 'cash'">
              <div class="form-group">
                <label>Provider</label>
                <input v-model="paymentForm.provider" placeholder="GoPay, BCA, dll" class="w-full" />
              </div>
              <div class="form-group">
                <label>Nomor Referensi</label>
                <input v-model="paymentForm.payment_reference" placeholder="Nomor referensi" class="w-full" />
              </div>
            </template>

            <div class="form-group">
              <label>Catatan (Opsional)</label>
              <textarea v-model="paymentForm.notes" rows="2" class="w-full"></textarea>
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              @click="showPaymentModal = false"
              class="btn btn-secondary"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="processing"
              class="btn btn-primary"
            >
              {{ processing ? 'Memproses...' : 'Simpan Pembayaran' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import { toast } from '@/composables/feedback'
import { ArrowLeft, DollarSign, CheckCircle, Receipt, X, Printer, Download, RefreshCw, Cloud } from 'lucide-vue-next'

const route = useRoute()
const auth = useAuthStore()
const invoice = ref(null)
const paymentHistory = ref([])
const showPaymentModal = ref(false)
const processing = ref(false)
const syncing = ref(false)
const paymentForm = ref({
  amount: 0,
  payment_method: 'cash',
  provider: '',
  payment_reference: '',
  notes: ''
})

async function downloadPdf() {
  try {
    const response = await api.get(`/invoices/${route.params.id}/pdf`, {
      responseType: 'blob'
    })
    
    // Create blob URL and trigger download
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `invoice-${route.params.id}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to download PDF:', error)
    toast.error('Gagal mendownload PDF. Silakan coba lagi.')
  }
}

const canContinuePayment = computed(() => {
  return invoice.value && invoice.value.invoice.outstanding_balance > 0
})

// ---- Integrasi Odoo (modul Accounting) ----
const canSync = computed(() => ['administrator', 'manager'].includes(auth.userRole))
const isSynced = computed(() => invoice.value?.invoice?.sync_status === 'synced')

const syncButtonLabel = computed(() => (isSynced.value ? 'Sync Ulang' : 'Sync ke Odoo'))
const syncButtonTitle = computed(() =>
  isSynced.value
    ? 'Hapus & buat ulang invoice di Odoo (re-sync)'
    : 'Kirim invoice ini ke modul Accounting Odoo'
)

const syncLabels = {
  pending: 'Belum Sync',
  syncing: 'Sinkron...',
  synced: 'Odoo: Terkirim',
  failed: 'Odoo: Gagal',
  manual_review: 'Odoo: Perlu Review',
}
const syncLabel = computed(() => syncLabels[invoice.value?.invoice?.sync_status] || 'Belum Sync')

const syncBadgeClass = computed(() => {
  const s = invoice.value?.invoice?.sync_status
  if (s === 'synced') return 'badge-success'
  if (s === 'failed') return 'badge-danger'
  if (s === 'syncing') return 'badge-primary'
  return 'badge-warning'
})

async function syncToOdoo() {
  if (!invoice.value) return
  syncing.value = true
  try {
    // Bila sudah pernah tersinkron, lakukan re-sync (force) agar tidak dobel.
    const { data } = await api.post(`/invoices/${route.params.id}/sync`, null, {
      params: isSynced.value ? { force: 1 } : {}
    })
    await loadInvoice()
    toast.success(data?.message || 'Invoice berhasil disinkronkan ke Odoo.')
  } catch (error) {
    await loadInvoice()
    toast.error(error.response?.data?.message || 'Gagal menyinkronkan ke Odoo. Pastikan Odoo berjalan.')
  } finally {
    syncing.value = false
  }
}

const paymentPercentage = computed(() => {
  if (!invoice.value) return 0
  const total = invoice.value.invoice.total_amount
  const paid = invoice.value.invoice.paid_amount
  return total > 0 ? Math.round((paid / total) * 100) : 0
})

const progressBarClass = computed(() => {
  const pct = paymentPercentage.value
  if (pct >= 100) return 'bg-green-500'
  if (pct >= 50) return 'bg-blue-500'
  if (pct >= 25) return 'bg-amber-500'
  return 'bg-red-500'
})

function formatCurrency(v) {
  return 'Rp ' + Number(v || 0).toLocaleString('id-ID')
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function isOverdue(dueDate) {
  if (!dueDate) return false
  return new Date(dueDate) < new Date()
}

function getStatusClass(status) {
  const classes = {
    unpaid: 'badge-slate',
    partial: 'badge-primary',
    paid: 'badge-success',
    overdue: 'badge-danger'
  }
  return classes[status] || classes.unpaid
}

function getStatusLabel(status) {
  const labels = {
    unpaid: 'Belum Dibayar',
    partial: 'Dibayar Sebagian',
    paid: 'Lunas',
    overdue: 'Jatuh Tempo'
  }
  return labels[status] || status
}

function printInvoice() {
  window.print()
}

async function loadInvoice() {
  try {
    const { data } = await api.get(`/invoices/${route.params.id}`)
    invoice.value = data.data
  } catch (error) {
    toast.error('Gagal memuat data invoice.')
  }
}

async function loadPaymentHistory() {
  try {
    const { data } = await api.get(`/invoices/${route.params.id}/payments`)
    paymentHistory.value = data.data.payments || []
  } catch (error) {
    paymentHistory.value = []
  }
}

async function submitPayment() {
  if (!invoice.value) return

  processing.value = true
  try {
    await api.post(`/invoices/${route.params.id}/payment`, paymentForm.value)
    
    showPaymentModal.value = false
    await Promise.all([loadInvoice(), loadPaymentHistory()])
    
    toast.success('Pembayaran berhasil ditambahkan!')
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menambah pembayaran')
  } finally {
    processing.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadInvoice(), loadPaymentHistory()])
})
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>
