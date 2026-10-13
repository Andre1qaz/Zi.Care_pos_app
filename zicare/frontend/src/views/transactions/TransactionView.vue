<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Transaksi Penjualan</h1>
        <p class="page-subtitle">Buat transaksi penjualan baru</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <span class="badge badge-primary">
          <ShoppingCart class="h-3.5 w-3.5" />
          {{ cart.totalItems }} item
        </span>
        <span v-if="cart.items.length" class="badge badge-slate">
          {{ formatCurrency(cart.total) }}
        </span>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
      <!-- Bagian Kiri: Daftar Produk -->
      <div class="lg:col-span-3">
        <div class="card">
          <div class="mb-5 flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
              <Search class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <input
                v-model="search"
                placeholder="Cari produk..."
                @input="loadProducts"
                class="input pl-11"
              />
            </div>
            <select
              v-model="categoryFilter"
              @change="loadProducts"
              class="input sm:w-56"
            >
              <option value="">Semua Kategori</option>
              <option v-for="c in productStore.categories" :key="c.id" :value="c.id">
                {{ c.category_name }}
              </option>
            </select>
          </div>

          <div class="grid max-h-[60vh] gap-4 overflow-y-auto pr-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div
              v-for="product in productStore.products"
              :key="product.id"
              :class="[
                'group relative flex h-full cursor-pointer flex-col rounded-3xl border-2 p-4 transition-all duration-200',
                product.product_type === 'jasa' && product.service_status !== 'tersedia'
                  ? 'cursor-not-allowed border-slate-200 bg-slate-50 opacity-50'
                  : (product.product_type === 'barang' && product.stock <= 0
                      ? 'cursor-not-allowed border-slate-200 bg-slate-50 opacity-60'
                      : 'border-slate-200 bg-white hover:-translate-y-1 hover:border-primary-400 hover:bg-primary-50/40 hover:shadow-bubble')
              ]"
              @click="product.product_type === 'jasa' && product.service_status !== 'tersedia' ? null : cart.addItem(product)"
            >
              <div class="mb-2 flex flex-wrap items-center gap-1.5">
                <span
                  :class="[
                    'badge',
                    product.product_type === 'barang' ? 'badge-primary' : 'badge-warning'
                  ]"
                >
                  {{ product.product_type === 'barang' ? 'Barang' : 'Jasa' }}
                </span>
                <span
                  v-if="product.category_id"
                  class="chip"
                >
                  {{ getCategoryName(product.category_id) }}
                </span>
              </div>

              <h4 class="mb-1 font-semibold text-slate-900 line-clamp-2">{{ product.product_name }}</h4>
              <p class="mb-3 text-lg font-bold text-primary-600">{{ formatCurrency(product.price) }}</p>

              <div class="mt-auto">
                <span
                  v-if="product.product_type === 'barang'"
                  :class="['badge', product.stock <= 10 ? 'badge-danger' : 'badge-slate']"
                >
                  Stok: {{ product.stock }}
                </span>
                <span
                  v-else
                  :class="['badge', product.service_status === 'tersedia' ? 'badge-success' : 'badge-slate']"
                >
                  {{ product.service_status === 'tersedia' ? '✓ Tersedia' : '✗ Tidak Tersedia' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bagian Kanan: Keranjang -->
      <div class="lg:col-span-2">
        <div class="card lg:sticky lg:top-6">

          <div class="mb-5 border-b border-slate-100 pb-4">
            <div class="mb-3 flex items-center justify-between">
              <h3 class="section-title flex items-center gap-2">
                <ShoppingCart class="h-4 w-4 text-primary-500" />
                Keranjang
              </h3>
              <span v-if="cart.items.length" class="badge badge-primary">{{ cart.totalItems }} item</span>
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-semibold text-slate-700">Pilih Pelanggan:</label>
              <select
                v-model="selectedCustomerId"
                class="input"
              >
                <option :value="null">Walk-in Customer (Umum)</option>
                <option
                  v-for="customer in customers"
                  :key="customer.id || customer.customer_id"
                  :value="customer.id || customer.customer_id"
                >
                  {{ customer.nama || customer.customer_name || customer.name || 'Pelanggan Tanpa Nama' }}
                  {{ customer.telepon || customer.phone ? ' - ' + (customer.telepon || customer.phone) : '' }}
                </option>
              </select>
            </div>
          </div>

          <div v-if="cart.items.length" class="max-h-72 space-y-2.5 overflow-y-auto pr-1">
            <div
              v-for="item in cart.items"
              :key="item.product_id"
              class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 p-3 transition-colors hover:bg-slate-100/70"
            >
              <div class="flex-1 min-w-0">
                <p class="font-medium text-slate-900 truncate">{{ item.product_name }}</p>
                <p class="text-sm text-slate-500">{{ formatCurrency(item.price) }}</p>
              </div>

              <div class="flex items-center gap-1.5">
                <button
                  @click="cart.updateQuantity(item.product_id, item.quantity - 1)"
                  :disabled="item.quantity <= 1"
                  class="btn btn-secondary btn-icon-sm"
                >
                  <Minus class="h-3.5 w-3.5" />
                </button>
                <input
                  type="number"
                  :value="item.quantity"
                  min="1"
                  :max="item.product_type === 'barang' ? item.stock : 999"
                  @change="cart.updateQuantity(item.product_id, +$event.target.value)"
                  class="w-12 rounded-full border border-slate-200 bg-white px-1 py-1.5 text-center text-sm font-semibold text-slate-800 transition-all focus:border-primary-400 focus:outline-none focus:ring-4 focus:ring-primary-500/10"
                />
                <button
                  @click="cart.updateQuantity(item.product_id, item.quantity + 1)"
                  class="btn btn-secondary btn-icon-sm"
                >
                  <Plus class="h-3.5 w-3.5" />
                </button>
              </div>

              <button
                @click="cart.removeItem(item.product_id)"
                class="btn btn-icon-sm text-danger-600 hover:bg-danger-50 hover:text-danger-700"
              >
                <Trash2 class="h-4 w-4" />
              </button>
            </div>
          </div>

          <div v-else class="empty-state">
            <div class="empty-icon">
              <ShoppingCart class="h-7 w-7" />
            </div>
            <div>
              <p class="font-semibold text-slate-700">Keranjang kosong</p>
              <p class="text-sm text-slate-500">Pilih produk untuk memulai transaksi.</p>
            </div>
          </div>

          <div class="mt-5 border-t border-slate-100 pt-4">
            <div class="flex items-center justify-between">
              <span class="text-base font-semibold text-slate-700">Total</span>
              <span class="text-2xl font-extrabold text-primary-600">{{ formatCurrency(cart.total) }}</span>
            </div>
          </div>

          <div v-if="cart.items.length" class="mt-6">
            <div class="mb-4 flex items-center justify-between">
              <label class="font-semibold text-slate-900">Metode Pembayaran</label>
              <button
                @click="addPaymentEntry"
                class="btn btn-ghost btn-sm text-primary-600 hover:bg-primary-50 hover:text-primary-700"
              >
                <Plus class="h-4 w-4" />
                Tambah
              </button>
            </div>

            <div class="space-y-3">
              <div
                v-for="(entry, index) in paymentEntries"
                :key="index"
                class="rounded-3xl border border-slate-100 bg-slate-50/80 p-4"
              >
                <div class="mb-3 grid grid-cols-2 gap-2">
                  <button
                    v-for="method in paymentMethods"
                    :key="method.value"
                    :class="[
                      'flex items-center justify-center gap-2 rounded-2xl border-2 px-3 py-2.5 text-sm font-semibold transition-all',
                      entry.method === method.value
                        ? 'border-primary-500 bg-primary-50 text-primary-700'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-primary-300 hover:bg-primary-50/40'
                    ]"
                    @click="entry.method = method.value"
                  >
                    <component :is="method.icon" class="h-4 w-4" />
                    {{ method.label }}
                  </button>
                </div>

                <div class="form-group mb-3">
                  <label>Jumlah</label>
                  <input
                    v-model.number="entry.amount"
                    type="number"
                    min="0"
                    :placeholder="String(cart.total)"
                  />
                </div>

                <template v-if="entry.method === 'cash'">
                  <div class="form-group mb-3">
                    <label>Uang Diterima</label>
                    <input
                      v-model.number="entry.paidAmount"
                      type="number"
                      min="0"
                      placeholder="0"
                    />
                  </div>
                  <div
                    v-if="entry.paidAmount >= entry.amount"
                    class="mt-2 rounded-2xl bg-success-50 p-3 text-center text-sm font-semibold text-success-700 ring-1 ring-inset ring-success-100"
                  >
                    Kembalian: {{ formatCurrency(entry.paidAmount - entry.amount) }}
                  </div>
                </template>

                <template v-else>
                  <div class="form-group mb-3">
                    <label>Provider</label>
                    <input
                      v-model="entry.provider"
                      placeholder="GoPay, BCA, dll"
                    />
                  </div>
                  <div class="form-group mb-3">
                    <label>Nomor Referensi</label>
                    <input
                      v-model="entry.paymentReference"
                      placeholder="Nomor referensi"
                    />
                  </div>
                </template>

                <button
                  v-if="paymentEntries.length > 1"
                  @click="removePaymentEntry(index)"
                  class="btn btn-ghost btn-sm mt-2 w-full text-danger-600 hover:bg-danger-50 hover:text-danger-700"
                >
                  Hapus Pembayaran
                </button>
              </div>
            </div>

            <div class="mt-4 rounded-3xl border border-slate-100 bg-slate-50/80 p-4">
              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-slate-600">Total Tagihan</span>
                <span class="font-semibold text-slate-900">{{ formatCurrency(cart.total) }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-slate-600">Total Dibayar</span>
                <span
                  :class="[
                    'font-semibold',
                    totalPaid >= cart.total ? 'text-success-600' : 'text-danger-600'
                  ]"
                >
                  {{ formatCurrency(totalPaid) }}
                </span>
              </div>
              <div v-if="totalPaid > cart.total" class="flex items-center justify-between border-t border-slate-200 py-2">
                <span class="text-sm text-slate-600">Kembalian</span>
                <span class="font-semibold text-success-600">{{ formatCurrency(totalPaid - cart.total) }}</span>
              </div>
              <div v-if="totalPaid < cart.total" class="flex items-center justify-between border-t border-slate-200 py-2">
                <span class="text-sm text-slate-600">Kurang Bayar</span>
                <span class="font-semibold text-danger-600">{{ formatCurrency(cart.total - totalPaid) }}</span>
              </div>
            </div>

            <div class="mt-4 space-y-3">
              <label class="flex cursor-pointer items-center gap-2.5 rounded-2xl bg-slate-50/80 px-4 py-3 ring-1 ring-inset ring-slate-100 transition-colors hover:bg-slate-100/70">
                <input
                  v-model="allowPartialPayment"
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                />
                <span class="text-sm font-medium text-slate-700">Izinkan pembayaran sebagian</span>
              </label>

              <button
                @click="checkout"
                :disabled="processing || (totalPaid < cart.total && !allowPartialPayment)"
                class="btn btn-primary btn-lg w-full"
              >
                <span v-if="processing" class="spinner h-4 w-4 border-white/40 border-t-white"></span>
                {{ processing ? 'Memproses...' : (allowPartialPayment && totalPaid < cart.total ? 'Buat Invoice (Partial)' : 'Bayar & Cetak Invoice') }}
              </button>

              <button
                @click="cart.clear()"
                class="btn btn-secondary w-full"
              >
                <Trash2 class="h-4 w-4" />
                Kosongkan Keranjang
              </button>

              <p v-if="allowPartialPayment && totalPaid < cart.total" class="text-center text-sm text-warning-600">
                Invoice akan dibuat dengan status "Partially Paid". Sisa pembayaran dapat dilakukan nanti.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { useProductStore } from '@/stores/products'
import { Plus, Minus, Trash2, ShoppingCart, DollarSign, Smartphone, Building2, Wallet, Search } from 'lucide-vue-next'
import { toast } from '@/composables/feedback'
import api from '@/services/api'

const cart = useCartStore()
const productStore = useProductStore()
const router = useRouter()

const search = ref('')
const categoryFilter = ref('')
const processing = ref(false)
const allowPartialPayment = ref(false)

const customers = ref([])
const selectedCustomerId = ref(null)

const paymentEntries = ref([
  { method: 'cash', amount: 0, paidAmount: 0, provider: '', paymentReference: '' }
])

const paymentMethods = [
  { value: 'cash', label: 'Tunai', icon: DollarSign },
  { value: 'qris', label: 'QRIS', icon: Smartphone },
  { value: 'transfer', label: 'Transfer', icon: Building2 },
  { value: 'ewallet', label: 'E-Wallet', icon: Wallet }
]

const totalPaid = computed(() => {
  return paymentEntries.value.reduce((sum, entry) => sum + (entry.amount || 0), 0)
})

function getCategoryName(id) {
  return productStore.categories.find(c => c.id === id)?.category_name || ''
}

function formatCurrency(val) {
  return 'Rp ' + Number(val || 0).toLocaleString('id-ID')
}

function addPaymentEntry() {
  paymentEntries.value.push({ method: 'cash', amount: 0, paidAmount: 0, provider: '', paymentReference: '' })
}

function removePaymentEntry(index) {
  paymentEntries.value.splice(index, 1)
}

async function loadProducts() {
  await productStore.fetchProducts({
    search: search.value || undefined,
    category_id: categoryFilter.value || undefined,
    limit: 1000
  })
}

async function loadCustomers() {
  try {
    const response = await api.get('/customers')
    console.log('Response API Pelanggan:', response)

    let dataPelanggan = []
    if (response.data && Array.isArray(response.data.data)) {
      dataPelanggan = response.data.data
    } else if (response.data && Array.isArray(response.data)) {
      dataPelanggan = response.data
    } else if (Array.isArray(response)) {
      dataPelanggan = response
    }

    customers.value = Array.isArray(dataPelanggan) ? dataPelanggan : []
  } catch (e) {
    console.error('Gagal mengambil data pelanggan', e)
  }
}

// Sinkronkan pilihan dropdown ke store cart
watch(selectedCustomerId, (newId) => {
  const selected = customers.value.find(c => (c.id || c.customer_id) === newId)
  cart.setCustomer(
    selected ? (selected.id || selected.customer_id) : null,
    selected ? (selected.nama || selected.customer_name || selected.name) : 'Walk-in Customer'
  )
})

async function checkout() {
  if (!allowPartialPayment.value && totalPaid.value < cart.total) {
    toast.error('Total pembayaran kurang dari tagihan')
    return
  }

  processing.value = true
  try {
    const selectedCustomer = customers.value.find(c =>
      (c.id || c.customer_id) === selectedCustomerId.value
    )

    const customerName = selectedCustomer
      ? (selectedCustomer.nama || selectedCustomer.customer_name || selectedCustomer.name)
      : 'Walk-in Customer'

    const customerEmail = selectedCustomer
      ? (selectedCustomer.email || selectedCustomer.customer_email)
      : ''

    const customerPhone = selectedCustomer
      ? (selectedCustomer.telepon || selectedCustomer.phone || selectedCustomer.customer_phone)
      : ''

    const payload = {
      customer_id: selectedCustomerId.value,
      customer_name: customerName,
      customer_email: customerEmail,
      customer_phone: customerPhone,
      payments: paymentEntries.value.map(entry => ({
        payment_method: entry.method,
        amount: entry.amount,
        paid_amount: entry.method === 'cash' ? entry.paidAmount : entry.amount,
        provider: entry.provider || undefined,
        payment_reference: entry.paymentReference || undefined
      })),
      items: cart.items.map((i) => ({ product_id: i.product_id, quantity: i.quantity }))
    }

    const { data } = await api.post('/transactions', payload)
    cart.clear()
    paymentEntries.value = [{ method: 'cash', amount: 0, paidAmount: 0, provider: '', paymentReference: '' }]
    allowPartialPayment.value = false
    selectedCustomerId.value = null

    router.push(`/invoices/${data.data.invoice.id}`)
  } catch (e) {
    toast.error(e.response?.data?.message || 'Transaksi gagal')
  } finally {
    processing.value = false
  }
}

onMounted(async () => {
  await loadCustomers()
  await productStore.fetchCategories()
  await loadProducts()
})
</script>
