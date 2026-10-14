<template>
  <div class="page">
    <div class="page-header">
      <div class="flex items-center gap-3">
        <button class="btn btn-secondary btn-icon" title="Kembali" @click="router.push('/products')">
          <ArrowLeft class="h-4 w-4" />
        </button>
        <div>
          <h1 class="page-title">Kartu Stok</h1>
          <p class="page-subtitle">{{ product ? product.product_name : 'Memuat...' }}</p>
        </div>
      </div>
      <button
        v-if="product && product.product_type === 'barang'"
        class="btn btn-primary"
        @click="openAdjust"
      >
        <SlidersHorizontal class="h-4 w-4" />
        Sesuaikan Stok
      </button>
    </div>

    <div v-if="loading && !product" class="flex items-center justify-center gap-3 py-16 text-slate-500">
      <span class="spinner"></span>
      <span class="text-sm font-medium">Memuat kartu stok...</span>
    </div>

    <template v-else-if="product">
      <!-- Info produk -->
      <div class="card mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-400 to-primary-600 text-white shadow-bubble-sm">
              <Package class="h-7 w-7" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-bold text-slate-900">{{ product.product_name }}</h3>
                <span class="chip font-mono">{{ product.product_code }}</span>
                <span :class="['badge', product.product_type === 'barang' ? 'badge-primary' : 'badge-warning']">
                  {{ product.product_type === 'barang' ? 'Barang' : 'Jasa' }}
                </span>
              </div>
              <p class="mt-1 text-sm text-slate-500">
                {{ categoryName }} · Harga {{ formatCurrency(product.price) }}
              </p>
            </div>
          </div>
          <div v-if="product.product_type === 'barang'" class="text-right">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stok Saat Ini</p>
            <p :class="['text-3xl font-extrabold', product.stock <= 0 ? 'text-danger-600' : 'text-slate-900']">
              {{ product.stock }}
            </p>
          </div>
        </div>
      </div>

      <!-- Ringkasan -->
      <div class="grid gap-5 sm:grid-cols-3 mb-6">
        <div class="stat-card">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-success-400 to-success-600 text-white shadow-bubble-sm">
              <ArrowDownToLine class="h-6 w-6" />
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Masuk</p>
              <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ summary.total_in }}</p>
            </div>
          </div>
        </div>
        <div class="stat-card">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-danger-400 to-danger-600 text-white shadow-bubble-sm">
              <ArrowUpFromLine class="h-6 w-6" />
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Keluar</p>
              <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ summary.total_out }}</p>
            </div>
          </div>
        </div>
        <div class="stat-card">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-400 to-slate-600 text-white shadow-bubble-sm">
              <Activity class="h-6 w-6" />
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah Pergerakan</p>
              <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ summary.total_movements }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter -->
      <div class="card mb-6">
        <div class="grid gap-4 sm:grid-cols-4">
          <div class="form-group mb-0">
            <label>Jenis Pergerakan</label>
            <select v-model="filters.type">
              <option value="">Semua Jenis</option>
              <option value="sale">Penjualan</option>
              <option value="in">Masuk</option>
              <option value="return">Retur</option>
              <option value="adjustment">Penyesuaian</option>
              <option value="opname">Opname</option>
            </select>
          </div>
          <div class="form-group mb-0">
            <label>Dari Tanggal</label>
            <input v-model="filters.date_from" type="date" />
          </div>
          <div class="form-group mb-0">
            <label>Sampai Tanggal</label>
            <input v-model="filters.date_to" type="date" />
          </div>
          <div class="flex items-end gap-2">
            <button class="btn btn-primary w-full" @click="applyFilter">
              <Search class="h-4 w-4" />
              Terapkan
            </button>
            <button class="btn btn-secondary btn-icon" title="Reset" @click="resetFilter">
              <RotateCcw class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Tabel pergerakan -->
      <div class="table-card">
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th>Waktu</th>
                <th>Jenis</th>
                <th class="text-right">Perubahan</th>
                <th class="text-right">Stok</th>
                <th>Alasan</th>
                <th>Referensi</th>
                <th>Oleh</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in movements" :key="m.id">
                <td class="whitespace-nowrap text-sm text-slate-600">{{ formatDateTime(m.created_at) }}</td>
                <td>
                  <span :class="['badge', typeMeta(m.movement_type).cls]">
                    {{ typeMeta(m.movement_type).label }}
                  </span>
                </td>
                <td class="text-right font-semibold whitespace-nowrap" :class="m.quantity > 0 ? 'text-success-600' : 'text-danger-600'">
                  {{ m.quantity > 0 ? '+' : '' }}{{ m.quantity }}
                </td>
                <td class="text-right whitespace-nowrap text-slate-600">
                  {{ m.stock_before }} <span class="text-slate-300">→</span> <span class="font-semibold text-slate-800">{{ m.stock_after }}</span>
                </td>
                <td class="text-slate-600">{{ m.reason || '-' }}</td>
                <td class="font-mono text-xs text-slate-500">{{ m.invoice_number || m.reference || '-' }}</td>
                <td class="text-slate-600">{{ m.user_name || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="!movements.length && !loading" class="empty-state">
          <div class="empty-icon">
            <Activity class="h-7 w-7" />
          </div>
          <p>Belum ada pergerakan stok</p>
        </div>
        <div v-else-if="loading" class="flex items-center justify-center gap-2 py-10 text-sm text-slate-500">
          <span class="spinner"></span> Memuat...
        </div>
      </div>

      <div
        v-if="pagination.pages > 1"
        class="pagination mt-5 justify-center rounded-3xl border border-slate-200/80 bg-white shadow-soft"
      >
        <button class="page-btn" :disabled="pagination.page === 1" @click="changePage(pagination.page - 1)">Prev</button>
        <span>Halaman {{ pagination.page }} dari {{ pagination.pages }}</span>
        <button class="page-btn" :disabled="pagination.page === pagination.pages" @click="changePage(pagination.page + 1)">Next</button>
      </div>
    </template>

    <!-- Modal penyesuaian stok -->
    <div v-if="showAdjust" class="modal-overlay" @click.self="showAdjust = false">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">Sesuaikan Stok</h3>
          <button class="btn btn-ghost btn-icon-sm" title="Tutup" @click="showAdjust = false">
            <X class="h-5 w-5" />
          </button>
        </div>

        <form @submit.prevent="submitAdjust">
          <div class="modal-body">
            <p class="mb-4 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-inset ring-slate-100">
              Stok saat ini: <span class="font-bold text-slate-900">{{ product?.stock ?? 0 }}</span>
            </p>

            <div class="form-group">
              <label>Metode Penyesuaian</label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  :class="[
                    'rounded-2xl border-2 px-3 py-2.5 text-sm font-semibold transition-all',
                    adjustMode === 'opname' ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-slate-200 bg-white text-slate-600 hover:border-primary-300'
                  ]"
                  @click="adjustMode = 'opname'"
                >
                  Stok Opname
                </button>
                <button
                  type="button"
                  :class="[
                    'rounded-2xl border-2 px-3 py-2.5 text-sm font-semibold transition-all',
                    adjustMode === 'delta' ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-slate-200 bg-white text-slate-600 hover:border-primary-300'
                  ]"
                  @click="adjustMode = 'delta'"
                >
                  Tambah / Kurangi
                </button>
              </div>
            </div>

            <div v-if="adjustMode === 'opname'" class="form-group">
              <label>Stok Fisik Hasil Hitung</label>
              <input v-model.number="adjustForm.new_stock" type="number" min="0" required placeholder="0" />
              <p class="form-hint">
                Selisih: <span class="font-semibold">{{ opnameDiff > 0 ? '+' : '' }}{{ opnameDiff }}</span>
              </p>
            </div>

            <div v-else class="form-group">
              <label>Perubahan Stok</label>
              <input v-model.number="adjustForm.delta" type="number" required placeholder="contoh: 10 atau -3" />
              <p class="form-hint">Gunakan angka negatif untuk mengurangi stok.</p>
            </div>

            <div class="form-group">
              <label>Alasan <span class="text-danger-600">*</span></label>
              <textarea v-model="adjustForm.reason" rows="2" required placeholder="contoh: Hasil stok opname, barang rusak, retur supplier..."></textarea>
            </div>

            <div class="form-group mb-0">
              <label>Referensi (opsional)</label>
              <input v-model="adjustForm.reference" placeholder="No. dokumen / catatan" />
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showAdjust = false">Batal</button>
            <button type="submit" class="btn btn-primary" :disabled="adjusting">
              <span v-if="adjusting" class="spinner h-4 w-4 border-white/40 border-t-white"></span>
              {{ adjusting ? 'Menyimpan...' : 'Simpan Penyesuaian' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProductStore } from '@/stores/products'
import api from '@/services/api'
import { toast } from '@/composables/feedback'
import {
  ArrowLeft,
  Package,
  SlidersHorizontal,
  ArrowDownToLine,
  ArrowUpFromLine,
  Activity,
  Search,
  RotateCcw,
  X
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const store = useProductStore()

const loading = ref(false)
const product = ref(null)
const summary = ref({ total_in: 0, total_out: 0, total_movements: 0 })
const movements = ref([])
const filters = reactive({ type: '', date_from: '', date_to: '' })
const pagination = ref({ page: 1, limit: 20, total: 0, pages: 1 })

const showAdjust = ref(false)
const adjusting = ref(false)
const adjustMode = ref('opname')
const adjustForm = reactive({ new_stock: 0, delta: 0, reason: '', reference: '' })

const categoryName = computed(() => {
  const id = product.value?.category_id
  if (!id) return 'Tanpa Kategori'
  return store.categories.find((c) => c.id === id)?.category_name || 'Tanpa Kategori'
})

const opnameDiff = computed(() => {
  const current = Number(product.value?.stock ?? 0)
  return Number(adjustForm.new_stock ?? 0) - current
})

function typeMeta(type) {
  const map = {
    sale: { label: 'Penjualan', cls: 'badge-danger' },
    in: { label: 'Masuk', cls: 'badge-success' },
    return: { label: 'Retur', cls: 'badge-warning' },
    adjustment: { label: 'Penyesuaian', cls: 'badge-primary' },
    opname: { label: 'Opname', cls: 'badge-primary' }
  }
  return map[type] || { label: type, cls: 'badge-slate' }
}

function formatCurrency(v) {
  return 'Rp ' + Number(v || 0).toLocaleString('id-ID')
}

function formatDateTime(v) {
  if (!v) return '-'
  return new Date(v).toLocaleString('id-ID')
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get(`/products/${route.params.id}/stock-movements`, {
      params: {
        type: filters.type || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        page: pagination.value.page,
        limit: pagination.value.limit
      }
    })
    movements.value = data.data || []
    const meta = data.meta || {}
    product.value = meta.product || product.value
    summary.value = meta.summary || summary.value
    pagination.value.total = meta.total || 0
    pagination.value.pages = Math.max(1, Math.ceil((meta.total || 0) / pagination.value.limit))
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal memuat kartu stok.')
  } finally {
    loading.value = false
  }
}

function applyFilter() {
  pagination.value.page = 1
  load()
}

function resetFilter() {
  filters.type = ''
  filters.date_from = ''
  filters.date_to = ''
  pagination.value.page = 1
  load()
}

function changePage(page) {
  pagination.value.page = page
  load()
}

function openAdjust() {
  adjustMode.value = 'opname'
  adjustForm.new_stock = Number(product.value?.stock ?? 0)
  adjustForm.delta = 0
  adjustForm.reason = ''
  adjustForm.reference = ''
  showAdjust.value = true
}

async function submitAdjust() {
  if (!adjustForm.reason.trim()) {
    toast.error('Alasan penyesuaian wajib diisi.')
    return
  }

  const payload = { reason: adjustForm.reason.trim() }
  if (adjustForm.reference) payload.reference = adjustForm.reference

  if (adjustMode.value === 'opname') {
    payload.new_stock = Number(adjustForm.new_stock)
  } else {
    payload.delta = Number(adjustForm.delta)
    if (!payload.delta) {
      toast.error('Perubahan stok tidak boleh 0.')
      return
    }
  }

  adjusting.value = true
  try {
    const { data } = await api.post(`/products/${route.params.id}/stock-adjustments`, payload)
    toast.success(data?.message || 'Stok berhasil disesuaikan.')
    showAdjust.value = false
    pagination.value.page = 1
    await load()
  } catch (e) {
    const errs = e.response?.data?.errors
    const firstErr = errs ? Object.values(errs)[0] : null
    toast.error(firstErr || e.response?.data?.message || 'Gagal menyesuaikan stok.')
  } finally {
    adjusting.value = false
  }
}

watch(() => route.params.id, () => {
  pagination.value.page = 1
  product.value = null
  load()
})

onMounted(async () => {
  if (!store.categories.length) {
    try {
      await store.fetchCategories()
    } catch (e) {
      // Kategori hanya pelengkap tampilan.
    }
  }
  load()
})
</script>
