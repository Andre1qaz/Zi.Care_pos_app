$content = @'
<template>
  <div>
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-slate-900">Transaksi Penjualan</h1>
      <p class="mt-1 text-sm text-slate-500">Buat transaksi penjualan baru</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Bagian Kiri: Daftar Produk -->
      <div class="lg:col-span-2">
        <div class="card">
          <div class="mb-4 flex gap-3">
            <div class="flex-1">
              <input
                v-model="search"
                placeholder="Cari produk..."
                @input="loadProducts"
                class="w-full rounded-lg border border-slate-300 px-3 py-2"
              />
            </div>
            <select
              v-model="categoryFilter"
              @change="loadProducts"
              class="w-48 rounded-lg border border-slate-300 px-3 py-2 bg-white"
            >
              <option value="">Semua Kategori</option>
              <option v-for="c in productStore.categories" :key="c.id" :value="c.id">
                {{ c.category_name }}
              </option>
            </select>
          </div>

          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 max-h-[60vh] overflow-y-auto pr-2">
            <div
              v-for="product in productStore.products"
              :key="product.id"
              :class="[
                'relative cursor-pointer rounded-xl border-2 p-4 transition-all hover:shadow-lg',
                product.product_type === 'jasa' && product.service_status !== 'tersedia'
                  ? 'border-slate-200 bg-slate-50 opacity-50 cursor-not-allowed'
                  : 'border-slate-200 bg-white hover:border-primary-500 hover:bg-primary-50/50'
              ]"
              @click="product.product_type === 'jasa' && product.service_status !== 'tersedia' ? null : cart.addItem(product)"
            >
              <div class="mb-2 flex flex-wrap gap-1">
                <span
                  :class="[
                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold uppercase',
                    product.product_type === 'barang'
                      ? 'bg-blue-100 text-blue-800'
                      : 'bg-amber-100 text-amber-800'
                  ]"
                >
                  {{ product.product_type === 'barang' ? 'Barang' : 'Jasa' }}
                </span>
                <span
                  v-if="product.category_id"
                  class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                >
                  {{ getCategoryName(product.category_id) }}
                </span>
              </div>

              <h4 class="mb-1 font-semibold text-slate-900 line-clamp-2">{{ product.product_name }}</h4>
              <p class="mb-2 text-lg font-bold text-primary-600">{{ formatCurrency(product.price) }}</p>

              <div class="mt-auto">
                <span
                  v-if="product.product_type === 'barang'"
                  :class="product.stock <= 10 ? 'text-danger-600 font-semibold' : 'text-slate-600'"
                  class="text-sm"
                >
                  Stok: {{ product.stock }}
                </span>
                <span
                  v-else
                  :class="[
                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
                    product.service_status === 'tersedia'
                      ? 'bg-green-100 text-green-800'
                      : 'bg-slate-100 text-slate-600'
                  ]"
                >
                  {{ product.service_status === 'tersedia' ? 'Tersedia' : 'Tidak Tersedia' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bagian Kanan: Keranjang -->
      <div class="lg:col-span-1">
        <div class="card sticky top-8">

          <div class="mb-5 border-b border-slate-200 pb-4">
            <h3 class="text-lg font-semibold text-slate-900 mb-3">Keranjang</h3>
            <div class="space-y-1">
              <label class="text-sm font-medium text-slate-700">Pilih Pelanggan:</label>
              <select
                v-model="selectedCustomerId"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white"
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

          <div v-if="cart.items.length" class="space-y-3 max-h-64 overflow-y-auto">
            <div
              v-for="item in cart.items"
              :key="item.product_id"
              class="flex items-center gap-3 rounded-lg bg-slate-50 p-3"
            >
              <div class="flex-1 min-w-0">
                <p class="font-medium text-slate-900 truncate">{{ item.product_name }}</p>
                <p class="text-sm text-slate-500">{{ formatCurrency(item.price) }}</p>
              </div>

              <div class="flex items-center gap-1">
                <button
                  @click="cart.updateQuantity(item.product_id, item.quantity - 1)"
                  class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-200 text-slate-600 transition-all hover:bg-slate-300"
                >
                  <Minus class="h-4 w-4" />
                </button>
                <input
                  type="number"
                  :value="item.quantity"
                  min="1"
                  :max="item.product_type === 'barang' ? item.stock : 999"
                  @change="cart.updateQuantity(item.product_id, +$event.target.value)"
                  class="w-12 rounded-lg border border-slate-300 px-2 py-1 text-center text-sm"
                />
                <button
                  @click="cart.updateQuantity(item.product_id, item.quantity + 1)"
                  class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-200 text-slate-600 transition-all hover:bg-slate-300"
                >
                  <Plus class="h-4 w-4" />
                </button>
              </div>

              <button
                @click="cart.removeItem(item.product_id)"
                class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-danger-600 transition-all hover:bg-red-100"
              >
                <Trash2 class="h-4 w-4" />
              </button>
            </div>
          </div>

          <div v-else class="flex flex-col items-center justify-center py-8 text-slate-500">
            <ShoppingCart class="h-8 w-8 mb-2 opacity-50" />
            <p class="text-sm">Keranjang kosong</p>
          </div>

          <div class="mt-4 border-t border-slate-200 pt-4">
            <div class="flex items-center justify-between">
              <span class="text-lg font-semibold text-slate-900">Total</span>
              <span class="text-2xl font-bold text-primary-600">{{ formatCurrency(cart.total) }}</span>
            </div>
          </div>

          <div v-if="cart.items.length" class="mt-6">
            <div class="mb-4 flex items-center justify-between">
              <label class="font-medium text-slate-900">Metode Pembayaran</label>
              <button
                @click="addPaymentEntry"
                class="flex items-center gap-1 text-sm font-medium text-primary-600 transition-all hover:text-primary-700"
              >
                <Plus class="h-4 w-4" />
                Tambah
              </button>
            </div>

            <div class="space-y-3">
              <div
                v-for="(entry, index) in paymentEntries"
                :key="index"
                class="rounded-lg bg-slate-50 p-4"
              >
                <div class="mb-3 grid grid-cols-2 gap-2">
                  <button
                    v-for="method in paymentMethods"
                    :key="method.value"
                    :class="[
                      'flex items-center justify-center gap-2 rounded-lg border-2 px-3 py-2 text-sm font-medium transition-all',
                      entry.method === method.value
                        ? 'border-primary-500 bg-primary-50 text-primary-700'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                    ]"
                    @click="entry.method = method.value"
                  >
                    <component :is="method.icon" class="h-4 w-4" />
                    {{ method.label }}
                  </button>
                </div>

                <div class="form-group mb-2">
                  <label class="block text-sm text-slate-700 mb-1">Jumlah</label>
                  <input
                    v-model.number="entry.amount"
                    type="number"
                    min="0"
                    :placeholder="String(cart.total)"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                  />
                </div>

                <template v-if="entry.method === 'cash'">
                  <div class="form-group mb-2">
                    <label class="block text-sm text-slate-700 mb-1">Uang Diterima</label>
                    <input
                      v-model.number="entry.paidAmount"
                      type="number"
                      min="0"
                      placeholder="0"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                  </div>
                  <div
                    v-if="entry.paidAmount >= entry.amount"
                    class="rounded-lg bg-green-50 p-3 text-center text-sm font-medium text-green-700 mt-2"
                  >
                    Kembalian: {{ formatCurrency(entry.paidAmount - entry.amount) }}
                  </div>
                </template>

                <template v-else>
                  <div class="form-group mb-2">
                    <label class="block text-sm text-slate-700 mb-1">Provider</label>
                    <input
                      v-model="entry.provider"
                      placeholder="GoPay, BCA, dll"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                  </div>
                  <div class="form-group mb-2">
                    <label class="block text-sm text-slate-700 mb-1">Nomor Referensi</label>
                    <input
                      v-model="entry.paymentReference"
                      placeholder="Nomor referensi"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                  </div>
                </template>

                <button
                  v-if="paymentEntries.length > 1"
                  @click="removePaymentEntry(index)"
                  class="mt-2 w-full rounded-lg bg-red-50 py-2 text-sm font-medium text-danger-600 transition-all hover:bg-red-100"
                >
                  Hapus Pembayaran
                </button>
              </div>
            </div>

            <div class="mt-4 rounded-lg bg-slate-100 p-4">
              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-slate-600">Total Tagihan</span>
                <span class="font-semibold text-slate-900">{{ formatCurrency(cart.total) }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-slate-600">Total Dibayar</span>
                <span
                  :class="[
                    'font-semibold',
                    totalPaid >= cart.total ? 'text-green-600' : 'text-danger-600'
                  ]"
                >
                  {{ formatCurrency(totalPaid) }}
                </span>
              </div>
              <div v-if="totalPaid > cart.total" class="flex items-center justify-between py-2 border-t border-slate-200">
                <span class="text-sm text-slate-600">Kembalian</span>
                <span class="font-semibold text-green-600">{{ formatCurrency(totalPaid - cart.total) }}</span>
              </div>
              <div v-if="totalPaid < cart.total" class="flex items-center justify-between py-2 border-t border-slate-200">
                <span class="text-sm text-slate-600">Kurang Bayar</span>
                <span class="font-semibold text-danger-600">{{ formatCurrency(cart.total - totalPaid) }}</span>
              </div>
            </div>

            <div class="mt-4 space-y-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="allowPartialPayment"
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                />
                <span class="text-sm text-slate-700">Izinkan pembayaran sebagian</span>
              </label>

              <button
                @click="checkout"
                :disabled="processing || (totalPaid < cart.total && !allowPartialPayment)"
                class="w-full rounded-lg bg-primary-600 py-3 font-semibold text-white transition-all hover:bg-primary-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {{ processing ? 'Memproses...' : (allowPartialPayment && totalPaid < cart.total ? 'Buat Invoice (Partial)' : 'Bayar & Cetak Invoice') }}
              </button>

              <p v-if="allowPartialPayment && totalPaid < cart.total" class="text-center text-sm text-amber-600">
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
import { Plus, Minus, Trash2, ShoppingCart, DollarSign, Smartphone, Building2, Wallet } from 'lucide-vue-next'
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

watch(selectedCustomerId, (newId) => {
  const selected = customers.value.find(c => (c.id || c.customer_id) === newId)
  cart.setCustomer(selected || null)
})

async function checkout() {
  if (!allowPartialPayment.value && totalPaid.value < cart.total) {
    alert('Total pembayaran kurang dari tagihan')
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
    alert(e.response?.data?.message || 'Transaksi gagal')
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
'@

Set-Content -Path ".\src\views\transactions\TransactionView.vue" -Value $content -Encoding UTF8
Write-Host "=== SELESAI. Menampilkan 6 baris pertama untuk verifikasi: ==="
Get-Content -Path ".\src\views\transactions\TransactionView.vue" -TotalCount 6
