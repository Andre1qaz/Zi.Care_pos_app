<template>
  <div>
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-slate-900">Pembayaran Tertunda</h1>
      <p class="mt-1 text-sm text-slate-500">Kelola invoice dengan pembayaran yang belum lunas</p>
    </div>

    <!-- Stats Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="card">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-100">
            <AlertCircle class="h-6 w-6 text-red-600" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500">Total Tertunda</p>
            <p class="text-2xl font-bold text-slate-900">{{ formatCurrency(stats.total_outstanding || 0) }}</p>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
            <FileText class="h-6 w-6 text-slate-600" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500">Total Invoice</p>
            <p class="text-2xl font-bold text-slate-900">{{ stats.total_invoices || 0 }}</p>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100">
            <Clock class="h-6 w-6 text-amber-600" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500">Overdue</p>
            <p class="text-2xl font-bold text-slate-900">{{ stats.by_status?.overdue?.count || 0 }}</p>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100">
            <PieChart class="h-6 w-6 text-blue-600" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500">Partial</p>
            <p class="text-2xl font-bold text-slate-900">{{ stats.by_status?.partial?.count || 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
          <input
            v-model="search"
            placeholder="Cari invoice atau pelanggan..."
            @input="loadInvoices"
            class="w-full"
          />
        </div>
        <select v-model="statusFilter" @change="loadInvoices" class="w-48">
          <option value="">Semua Status</option>
          <option value="unpaid">Unpaid</option>
          <option value="partial">Partially Paid</option>
          <option value="overdue">Overdue</option>
        </select>
      </div>
    </div>

    <!-- Outstanding Invoices Table -->
    <div class="card overflow-hidden">
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
                <span
                  :class="[
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    getStatusClass(invoice.payment_status)
                  ]"
                >
                  {{ getStatusLabel(invoice.payment_status) }}
                </span>
              </td>
              <td class="text-right">
                <button
                  @click="openPaymentModal(invoice)"
                  class="flex items-center gap-1 rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white transition-all hover:bg-primary-700"
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
        class="flex flex-col items-center justify-center py-12 text-slate-500"
      >
        <CheckCircle class="h-12 w-12 mb-3 opacity-50" />
        <p>Tidak ada pembayaran tertunda</p>
      </div>
    </div>

    <!-- Payment Modal -->
    <div v-if="showPaymentModal" class="modal-overlay" @click.self="showPaymentModal = false">
      <div class="modal">
        <div class="mb-6 flex items-center justify-between">
          <h3 class="text-lg font-semibold">Tambah Pembayaran</h3>
          <button
            @click="showPaymentModal = false"
            class="rounded-lg p-2 text-slate-400 transition-all hover:bg-slate-100 hover:text-slate-600"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <div v-if="selectedInvoice" class="mb-6 rounded-lg bg-slate-50 p-4">
          <div class="flex justify-between">
            <span class="text-sm text-slate-600">Invoice</span>
            <span class="font-mono font-semibold">{{ selectedInvoice.invoice_number }}</span>
          </div>
          <div class="mt-2 flex justify-between">
            <span class="text-sm text-slate-600">Total Tagihan</span>
            <span class="font-bold">{{ formatCurrency(selectedInvoice.total_amount) }}</span>
          </div>
          <div class="mt-2 flex justify-between">
            <span class="text-sm text-slate-600">Sudah Dibayar</span>
            <span>{{ formatCurrency(selectedInvoice.paid_amount) }}</span>
          </div>
          <div class="mt-2 flex justify-between border-t border-slate-200 pt-2">
            <span class="text-sm font-semibold text-slate-900">Sisa Pembayaran</span>
            <span class="text-lg font-bold text-danger-600">
              {{ formatCurrency(selectedInvoice.outstanding_balance) }}
            </span>
          </div>
        </div>

        <form @submit.prevent="submitPayment">
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
            <p class="mt-1 text-xs text-slate-500">
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

          <div class="flex justify-end gap-3 pt-4">
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
import {
  AlertCircle,
  FileText,
  Clock,
  PieChart,
  CheckCircle,
  DollarSign,
  X
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
    unpaid: 'bg-slate-100 text-slate-600',
    partial: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800'
  }
  return classes[status] || classes.unpaid
}

function getStatusLabel(status) {
  const labels = {
    unpaid: 'Unpaid',
    partial: 'Partial',
    paid: 'Paid',
    overdue: 'Overdue'
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
    
    alert('Pembayaran berhasil ditambahkan!')
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menambah pembayaran')
  } finally {
    processing.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadInvoices(), loadStats()])
})
</script>
