<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Pembayaran Tertunda</h1>
        <p class="page-subtitle">Kelola invoice dengan pembayaran yang belum lunas</p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="stat-card">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-danger-100 text-danger-600">
            <AlertCircle class="h-6 w-6" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-slate-500">Total Tertunda</p>
            <p class="truncate text-xl font-extrabold text-slate-900 sm:text-2xl">{{ formatCurrency(stats.total_outstanding || 0) }}</p>
          </div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
            <FileText class="h-6 w-6" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-slate-500">Total Invoice</p>
            <p class="truncate text-xl font-extrabold text-slate-900 sm:text-2xl">{{ stats.total_invoices || 0 }}</p>
          </div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-warning-100 text-warning-600">
            <Clock class="h-6 w-6" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-slate-500">Overdue</p>
            <p class="truncate text-xl font-extrabold text-slate-900 sm:text-2xl">{{ stats.by_status?.overdue?.count || 0 }}</p>
          </div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary-100 text-primary-600">
            <PieChart class="h-6 w-6" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-slate-500">Partial</p>
            <p class="truncate text-xl font-extrabold text-slate-900 sm:text-2xl">{{ stats.by_status?.partial?.count || 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="flex flex-wrap items-end gap-4">
        <div class="min-w-64 flex-1">
          <label class="mb-2 block text-sm font-semibold text-slate-700">Pencarian</label>
          <div class="relative">
            <Search class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              v-model="search"
              placeholder="Cari invoice atau pelanggan..."
              @input="loadInvoices"
              class="input pl-11"
            />
          </div>
        </div>
        <div>
          <label class="mb-2 block text-sm font-semibold text-slate-700">Status</label>
          <select v-model="statusFilter" @change="loadInvoices" class="input w-48">
            <option value="">Semua Status</option>
            <option value="unpaid">Belum Dibayar</option>
            <option value="partial">Dibayar Sebagian</option>
            <option value="overdue">Jatuh Tempo</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Outstanding Invoices Table -->
    <div class="table-card">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th>Invoice</th>
              <th>Pelanggan</th>
              <th>Tanggal</th>
              <th>Jatuh Tempo</th>
              <th class="text-right">Total</th>
              <th class="text-right">Dibayar</th>
              <th class="text-right">Sisa</th>
              <th>Status</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="invoice in invoices" :key="invoice.id">
              <td class="font-mono text-sm">{{ invoice.invoice_number }}</td>
              <td class="font-medium">{{ invoice.customer?.customer_name || 'Walk-in' }}</td>
              <td class="text-sm">{{ formatDate(invoice.created_at) }}</td>
              <td :class="{ 'text-danger-600 font-semibold': isOverdue(invoice.due_date) }">
                {{ formatDate(invoice.due_date) }}
              </td>
              <td class="text-right font-medium">{{ formatCurrency(invoice.total_amount) }}</td>
              <td class="text-right">{{ formatCurrency(invoice.paid_amount) }}</td>
              <td class="text-right font-bold" :class="{ 'text-danger-600': invoice.outstanding_balance > 0 }">
                {{ formatCurrency(invoice.outstanding_balance) }}
              </td>
              <td>
                <span class="badge" :class="getStatusClass(invoice.payment_status)">
                  {{ getStatusLabel(invoice.payment_status) }}
                </span>
              </td>
              <td class="text-right">
                <button
                  @click="openPaymentModal(invoice)"
                  class="btn btn-primary btn-sm"
                >
                  <DollarSign class="h-4 w-4" />
                  Bayar
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div
        v-if="invoices.length === 0"
        class="empty-state"
      >
        <div class="empty-icon">
          <CheckCircle class="h-8 w-8" />
        </div>
        <p class="text-slate-500">Tidak ada pembayaran tertunda</p>
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
            <div v-if="selectedInvoice" class="mb-6 rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
              <div class="flex justify-between">
                <span class="text-sm text-slate-500">Invoice</span>
                <span class="font-mono font-semibold text-slate-800">{{ selectedInvoice.invoice_number }}</span>
              </div>
              <div class="mt-2 flex justify-between">
                <span class="text-sm text-slate-500">Total Tagihan</span>
                <span class="font-bold text-slate-800">{{ formatCurrency(selectedInvoice.total_amount) }}</span>
              </div>
              <div class="mt-2 flex justify-between">
                <span class="text-sm text-slate-500">Sudah Dibayar</span>
                <span class="text-slate-700">{{ formatCurrency(selectedInvoice.paid_amount) }}</span>
              </div>
              <div class="mt-2 flex justify-between border-t border-dashed border-slate-200 pt-2">
                <span class="text-sm font-semibold text-slate-900">Sisa Pembayaran</span>
                <span class="text-lg font-bold text-danger-600">
                  {{ formatCurrency(selectedInvoice.outstanding_balance) }}
                </span>
              </div>
            </div>

            <div class="form-group">
              <label>Jumlah Pembayaran</label>
              <input
                v-model.number="paymentForm.amount"
                type="number"
                :max="selectedInvoice?.outstanding_balance"
                min="1"
                required
                class="w-full"
              />
              <p class="form-hint">
                Maksimum: {{ formatCurrency(selectedInvoice?.outstanding_balance || 0) }}
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { toast } from '@/composables/feedback'
import {
  AlertCircle,
  FileText,
  Clock,
  PieChart,
  CheckCircle,
  DollarSign,
  X,
  Search
} from 'lucide-vue-next'

const router = useRouter()

const search = ref('')
const statusFilter = ref('')
const invoices = ref([])
const stats = ref({
  total_outstanding: 0,
  total_invoices: 0,
  by_status: {
    unpaid: { count: 0, amount: 0 },
    partial: { count: 0, amount: 0 },
    overdue: { count: 0, amount: 0 }
  }
})

const showPaymentModal = ref(false)
const selectedInvoice = ref(null)
const processing = ref(false)
const paymentForm = ref({
  amount: 0,
  payment_method: 'cash',
  provider: '',
  payment_reference: '',
  notes: ''
})

function formatCurrency(val) {
  return 'Rp ' + Number(val || 0).toLocaleString('id-ID')
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
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

async function loadInvoices() {
  try {
    const params = {
      page: 1,
      limit: 100
    }
    
    if (search.value) params.search = search.value
    if (statusFilter.value) params.status = statusFilter.value

    const { data } = await api.get('/outstanding-payments', { params })
    invoices.value = data.data || []
  } catch (error) {
    console.error('Failed to load invoices:', error)
  }
}

async function loadStats() {
  try {
    const { data } = await api.get('/outstanding-payments/stats')
    stats.value = data.data || stats.value
  } catch (error) {
    console.error('Failed to load stats:', error)
  }
}

function openPaymentModal(invoice) {
  selectedInvoice.value = invoice
  paymentForm.value = {
    amount: invoice.outstanding_balance,
    payment_method: 'cash',
    provider: '',
    payment_reference: '',
    notes: ''
  }
  showPaymentModal.value = true
}

async function submitPayment() {
  if (!selectedInvoice.value) return

  processing.value = true
  try {
    await api.post(`/outstanding-payments/${selectedInvoice.value.id}/payment`, paymentForm.value)
    
    showPaymentModal.value = false
    await loadInvoices()
    await loadStats()
    
    toast.success('Pembayaran berhasil ditambahkan!')
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menambah pembayaran')
  } finally {
    processing.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadInvoices(), loadStats()])
})
</script>
