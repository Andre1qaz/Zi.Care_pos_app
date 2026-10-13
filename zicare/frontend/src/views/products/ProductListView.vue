<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Manajemen Produk</h1>
        <p class="page-subtitle">Kelola barang dan jasa untuk penjualan</p>
      </div>
      <button v-if="canManage" @click="showForm = true" class="btn btn-primary">
        <Plus class="h-4 w-4" />
        Tambah Produk
      </button>
    </div>

    <div class="card mb-6">
      <div class="grid gap-4 md:grid-cols-2">
        <div class="form-group mb-0">
          <label>Cari Produk</label>
          <input
            v-model="search"
            placeholder="Nama atau kode produk..."
            @input="onFilterChange"
          />
        </div>
        <div class="form-group mb-0">
          <label>Kategori</label>
          <select v-model="categoryFilter" @change="onFilterChange">
            <option value="">Semua Kategori</option>
            <option v-for="c in store.categories" :key="c.id" :value="c.id">
              {{ c.category_name }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <div class="table-card">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Nama</th>
              <th>Tipe</th>
              <th>Kategori</th>
              <th>Harga</th>
              <th>Stok/Status</th>
              <th v-if="canManage" class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in store.products" :key="p.id">
              <td class="font-mono text-sm">{{ p.product_code }}</td>
              <td class="font-medium">{{ p.product_name }}</td>
              <td>
                <span
                  :class="[
                    'badge',
                    p.product_type === 'barang' ? 'badge-primary' : 'badge-warning'
                  ]"
                >
                  {{ p.product_type === 'barang' ? 'Barang' : 'Jasa' }}
                </span>
              </td>
              <td>{{ getCategoryName(p.category_id) }}</td>
              <td class="font-medium">{{ formatCurrency(p.price) }}</td>
              <td>
                <span
                  v-if="p.product_type === 'barang'"
                  :class="p.stock <= 10 ? 'text-danger-600 font-semibold' : 'text-slate-600'"
                >
                  {{ p.stock }}
                </span>
                <span
                  v-else
                  :class="[
                    'badge',
                    p.service_status === 'tersedia' ? 'badge-success' : 'badge-slate'
                  ]"
                >
                  {{ p.service_status === 'tersedia' ? 'Tersedia' : 'Tidak Tersedia' }}
                </span>
              </td>
              <td v-if="canManage" class="text-right">
                <div class="flex justify-end gap-2">
                  <button
                    @click="editProduct(p)"
                    class="btn btn-secondary btn-icon-sm"
                    title="Edit"
                  >
                    <Pencil class="h-4 w-4" />
                  </button>
                  <button
                    @click="removeProduct(p.id)"
                    class="btn btn-danger btn-icon-sm"
                    title="Hapus"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="store.products.length === 0" class="empty-state">
        <div class="empty-icon">
          <Package class="h-7 w-7" />
        </div>
        <p>Tidak ada produk ditemukan</p>
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

    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">{{ form.id ? 'Edit' : 'Tambah' }} Produk</h3>
          <button
            @click="showForm = false"
            class="btn btn-ghost btn-icon-sm"
            title="Tutup"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <form @submit.prevent="saveProduct">
          <div class="modal-body">
            <div class="form-group">
              <label>Kode Produk</label>
              <input v-model="form.product_code" required />
            </div>

            <div class="form-group">
              <label>Nama Produk</label>
              <input v-model="form.product_name" required />
            </div>

            <div class="form-group">
              <label>Tipe Produk</label>
              <select v-model="form.product_type" @change="onTypeChange">
                <option value="barang">Barang (Fisik)</option>
                <option value="jasa">Jasa (Layanan)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Harga</label>
              <input v-model.number="form.price" type="number" required min="0" />
            </div>

            <div v-if="form.product_type === 'barang'" class="form-group">
              <label>Stok</label>
              <input
                v-model.number="form.stock"
                type="number"
                required
                min="0"
              />
              <p class="form-hint">Jumlah barang fisik yang tersedia</p>
            </div>

            <div v-else class="form-group">
              <label>Status Ketersediaan</label>
              <select v-model="form.service_status">
                <option value="tersedia">Tersedia</option>
                <option value="tidak_tersedia">Tidak Tersedia</option>
              </select>
              <p class="form-hint">Jasa tidak menggunakan manajemen stok</p>
            </div>

            <div class="form-group mb-0">
              <label>Kategori</label>
              <select v-model="form.category_id">
                <option :value="null">Tanpa Kategori</option>
                <option v-for="c in store.categories" :key="c.id" :value="c.id">
                  {{ c.category_name }}
                </option>
              </select>
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              @click="showForm = false"
              class="btn btn-secondary"
            >
              Batal
            </button>
            <button type="submit" class="btn btn-primary">
              {{ form.id ? 'Simpan Perubahan' : 'Tambah Produk' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useProductStore } from '@/stores/products'
import { useAuthStore } from '@/stores/auth'
import { Plus, Pencil, Trash2, Package, X } from 'lucide-vue-next'
import { toast, confirmDialog } from '@/composables/feedback'

const store = useProductStore()
const auth = useAuthStore()
const canManage = computed(() => auth.userRole === 'administrator')
const search = ref('')
const categoryFilter = ref('')
const showForm = ref(false)
const pagination = ref({ page: 1, limit: 20, total: 0, pages: 1 })
const form = ref({
  product_code: '',
  product_name: '',
  product_type: 'barang',
  price: 0,
  stock: 0,
  service_status: 'tersedia',
  category_id: null
})

function formatCurrency(v) {
  return 'Rp ' + Number(v).toLocaleString('id-ID')
}

function getCategoryName(id) {
  return store.categories.find(c => c.id === id)?.category_name || '-'
}

function onTypeChange() {
  if (form.value.product_type === 'barang') {
    form.value.service_status = null
  } else {
    form.value.stock = 0
    form.value.service_status = 'tersedia'
  }
}

async function load() {
  try {
    await store.fetchProducts({
      search: search.value || undefined,
      category_id: categoryFilter.value || undefined,
      page: pagination.value.page,
      limit: pagination.value.limit
    })
    const total = store.total || 0
    pagination.value.total = total
    pagination.value.pages = Math.max(1, Math.ceil(total / pagination.value.limit))
  } catch (e) {
    toast.error('Gagal memuat produk. Silakan coba lagi.')
  }
}

function onFilterChange() {
  pagination.value.page = 1
  load()
}

function changePage(page) {
  pagination.value.page = page
  load()
}

function editProduct(p) {
  form.value = { ...p }
  showForm.value = true
}

function resetForm() {
  form.value = {
    product_code: '',
    product_name: '',
    product_type: 'barang',
    price: 0,
    stock: 0,
    service_status: 'tersedia',
    category_id: null
  }
}

async function saveProduct() {
  const isEdit = !!form.value.id
  try {
    if (isEdit) {
      await store.updateProduct(form.value.id, form.value)
    } else {
      await store.createProduct(form.value)
    }
    showForm.value = false
    resetForm()
    await load()
    toast.success(isEdit ? 'Produk berhasil diperbarui' : 'Produk berhasil ditambahkan')
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Gagal menyimpan produk.')
  }
}

async function removeProduct(id) {
  const confirmed = await confirmDialog('Hapus produk ini?', {
    title: 'Hapus Produk',
    confirmText: 'Ya, Hapus',
    variant: 'danger'
  })
  if (confirmed) {
    try {
      await store.deleteProduct(id)
      toast.success('Produk berhasil dihapus')
      await load()
    } catch (e) {
      toast.error(e?.response?.data?.message || 'Gagal menghapus produk.')
    }
  }
}

onMounted(async () => {
  try {
    await store.fetchCategories()
  } catch (e) {
    // Categories only power the filter dropdown.
  }
  await load()
})
</script>
