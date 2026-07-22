<template>
  <div>
    <div class="mb-8 flex items-center justify-between no-print">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Detail Invoice</h1>
        <p class="mt-1 text-sm text-slate-500">{{ invoice?.invoice?.invoice_number }}</p>
      </div>
      <div class="flex gap-2">
        <button
          v-if="canContinuePayment"
          @click="showPaymentModal = true"
          class="flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition-all hover:bg-primary-700"
        >
          <DollarSign class="h-4 w-4" />
          Lanjut Pembayaran
        </button>
        <button
          @click="printInvoice"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50"
        >
          Cetak
        </button>
        <a
          :href="pdfUrl"
          target="_blank"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50"
        >
          Download PDF
        </a>
        <router-link
          to="/invoices"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50"
        >
          Kembali
        </router-link>
      </div>
    </div>

    <div v-if="invoice" class="grid gap-6 lg:grid-cols-3">
      <!-- Invoice Details -->
      <div class="lg:col-span-2 space-y-6">
        <div class="card">
          <div class="mb-6 border-b border-slate-200 pb-4">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-xl font-bold text-slate-900">INVOICE</h2>
                <p class="text-sm text-slate-500">{{ invoice.invoice.invoice_number }}</p>
              </div>
              <span
                :class="[
                  'inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold',
                  getStatusClass(invoice.invoice.payment_status)
                ]"
              >
                {{ getStatusLabel(invoice.invoice.payment_status) }}
              </span>
            </div>
          </div>

          <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <div>
              <p class="text-sm text-slate-500">Tanggal</p>
              <p class="font-medium">{{ formatDate(invoice.invoice.created_at) }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500">Jatuh Tempo</p>
              <p class="font-medium" :class="{ 'text-danger-600': isOverdue(invoice.invoice.due_date) }">
                {{ formatDate(invoice.invoice.due_date) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-slate-500">Kasir</p>
              <p class="font-medium">{{ invoice.cashier?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500">Pelanggan</p>
              <p class="font-medium">{{ invoice.customer?.customer_name || 'Walk-in Customer' }}</p>
            </div>
          </div>

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
                <td class="text-right font-medium">{{ formatCurrency(item.subtotal) }}</td>
              </tr>
            </tbody>
          </table>

          <div class="mt-6 rounded-lg bg-slate-50 p-4">
            <div class="flex justify-between">
              <span class="text-slate-600">Total Tagihan</span>
              <span class="text-xl font-bold">{{ formatCurrency(invoice.invoice.total_amount) }}</span>
            </div>
            <div class="mt-2 flex justify-between">
              <span class="text-slate-600">Total Dibayar</span>
              <span class="font-medium">{{ formatCurrency(invoice.invoice.paid_amount) }}</span>
            </div>
            <div v-if="invoice.invoice.outstanding_balance > 0" class="mt-2 flex justify-between border-t border-slate-200 pt-2">
              <span class="font-semibold text-slate-900">Sisa Pembayaran</span>
              <span class="text-lg font-bold text-danger-600">
                {{ formatCurrency(invoice.invoice.outstanding_balance) }}
              </span>
            </div>
            <div v-if="invoice.invoice.change_amount > 0" class="mt-2 flex justify-between">
              <span class="text-slate-600">Kembalian</span>
              <span class="font-medium text-green-600">{{ formatCurrency(invoice.invoice.change_amount) }}</span>
            </div>
          </div>
        </div>

        <!-- Payment History -->
        <div class="card">
          <h3 class="mb-4 text-lg font-semibold">Riwayat Pembayaran</h3>
          <div v-if="paymentHistory.length > 0" class="space-y-3">
            <div
              v-for="(payment, index) in paymentHistory"
              :key="payment.id"
              class="flex items-center justify-between rounded-lg bg-slate-50 p-4"
            >
              <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 text-primary-600">
                  {{ index + 1 }}
                </div>
                <div>
                  <p class="font-medium">{{ formatCurrency(payment.amount) }}</p>
                  <p class="text-sm text-slate-500">
                    {{ payment.payment_method }} • {{ formatDate(payment.payment_time) }}
                  </p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm text-slate-500">{{ payment.cashier?.name || '-' }}</p>
                <p v-if="payment.notes" class="text-xs text-slate-400">{{ payment.notes }}</p>
              </div>
            </div>
          </div>
          <div v-else class="flex flex-col items-center justify-center py-8 text-slate-500">
            <Receipt class="h-8 w-8 mb-2 opacity-50" />
            <p class="text-sm">Belum ada pembayaran</p>
          </div>
        </div>
      </div>

      <!-- Payment Progress -->
      <div class="lg:col-span-1">
        <div class="card">
          <h3 class="mb-4 text-lg font-semibold">Progress Pembayaran</h3>
          
          <div class="mb-4">
            <div class="mb-2 flex justify-between text-sm">
              <span class="text-slate-600">Persentase</span>
              <span class="font-semibold">{{ paymentPercentage }}%</span>
            </div>
            <div class="h-3 w-full rounded-full bg-slate-200">
              <div
                class="h-full rounded-full transition-all"
                :class="progressBarClass"
                :style="{ width: paymentPercentage + '%' }"
              ></div>
            </div>
          </div>

          <div class="space-y-3">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Total Tagihan</span>
              <span class="font-medium">{{ formatCurrency(invoice.invoice.total_amount) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Dibayar</span>
              <span class="font-medium text-green-600">{{ formatCurrency(invoice.invoice.paid_amount) }}</span>
            </div>
            <div v-if="invoice.invoice.outstanding_balance > 0" class="flex justify-between border-t border-slate-200 pt-3">
              <span class="text-sm font-semibold text-slate-900">Sisa</span>
              <span class="font-bold text-danger-600">{{ formatCurrency(invoice.invoice.outstanding_balance) }}</span>
            </div>
          </div>

          <div v-if="invoice.invoice.payment_status === 'paid'" class="mt-6 rounded-lg bg-green-50 p-4 text-center">
            <CheckCircle class="mx-auto h-8 w-8 text-green-600 mb-2" />
            <p class="font-semibold text-green-800">LUNAS</p>
          </div>
        </div>
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

        <div v-if="invoice" class="mb-6 rounded-lg bg-slate-50 p-4">
          <div class="flex justify-between">
            <span class="text-sm text-slate-600">Invoice</span>
            <span class="font-mono font-semibold">{{ invoice.invoice.invoice_number }}</span>
          </div>
          <div class="mt-2 flex justify-between">
            <span class="text-sm text-slate-600">Total Tagihan</span>
            <span class="font-bold">{{ formatCurrency(invoice.invoice.total_amount) }}</span>
          </div>
          <div class="mt-2 flex justify-between">
            <span class="text-sm text-slate-600">Sudah Dibayar</span>
            <span>{{ formatCurrency(invoice.invoice.paid_amount) }}</span>
          </div>
          <div class="mt-2 flex justify-between border-t border-slate-200 pt-2">
            <span class="text-sm font-semibold text-slate-900">Sisa Pembayaran</span>
            <span class="text-lg font-bold text-danger-600">
              {{ formatCurrency(invoice.invoice.outstanding_balance) }}
            </span>
          </div>
        </div>

        <form @submit.prevent="submitPayment">
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
            <p class="mt-1 text-xs text-slate-500">
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
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import { DollarSign, CheckCircle, Receipt, X } from 'lucide-vue-next'

const route = useRoute()
const invoice = ref(null)
const paymentHistory = ref([])
const showPaymentModal = ref(false)
const processing = ref(false)
const paymentForm = ref({
  amount: 0,
  payment_method: 'cash',
  provider: '',
  payment_reference: '',
  notes: ''
})

const pdfUrl = computed(() => {
  const base = import.meta.env.VITE_API_BASE_URL || '/api/v1'
  const token = localStorage.getItem('token')
  return `${base}/invoices/${route.params.id}/pdf?token=${token}`
})

const canContinuePayment = computed(() => {
  return invoice.value && invoice.value.invoice.outstanding_balance > 0
})

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
    partial: 'Partially Paid',
    paid: 'Paid',
    overdue: 'Overdue'
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
    console.error('Failed to load invoice:', error)
  }
}

async function loadPaymentHistory() {
  try {
    const { data } = await api.get(`/invoices/${route.params.id}/payments`)
    paymentHistory.value = data.data.payments || []
  } catch (error) {
    console.error('Failed to load payment history:', error)
  }
}

async function submitPayment() {
  if (!invoice.value) return

  processing.value = true
  try {
    await api.post(`/invoices/${route.params.id}/payment`, paymentForm.value)
    
    showPaymentModal.value = false
    await Promise.all([loadInvoice(), loadPaymentHistory()])
    
    alert('Pembayaran berhasil ditambahkan!')
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menambah pembayaran')
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
