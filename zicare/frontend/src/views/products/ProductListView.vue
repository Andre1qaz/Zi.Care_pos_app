<template>
  <div>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen Produk</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola barang dan jasa untuk penjualan</p>
      </div>
      <button
        @click="showForm = true"
        class="flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white transition-all hover:bg-primary-700 active:scale-95"
      >
        <Plus class="h-4 w-4" />
        Tambah Produk
      </button>
    </div>

    <div class="card mb-6">
      <div class="grid gap-4 md:grid-cols-2">
        <div class="form-group">
          <label>Cari Produk</label>
          <input
            v-model="search"
            placeholder="Nama atau kode produk..."
            @input="load"
            class="w-full"
          />
        </div>
        <div class="form-group">
          <label>Kategori</label>
          <select v-model="categoryFilter" @change="load" class="w-full">
            <option value="">Semua Kategori</option>
            <option v-for="c in store.categories" :key="c.id" :value="c.id">
              {{ c.category_name }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <div class="card overflow-hidden">
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
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in store.products" :key="p.id">
              <td class="font-mono text-sm">{{ p.product_code }}</td>
              <td class="font-medium">{{ p.product_name }}</td>
              <td>
                <span
                  :class="[
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    p.product_type === 'barang'
                      ? 'bg-blue-100 text-blue-800'
                      : 'bg-amber-100 text-amber-800'
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
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    p.service_status === 'tersedia'
                      ? 'bg-green-100 text-green-800'
                      : 'bg-slate-100 text-slate-600'
                  ]"
                >
                  {{ p.service_status === 'tersedia' ? 'Tersedia' : 'Tidak Tersedia' }}
                </span>
              </td>
              <td class="text-right">
                <div class="flex justify-end gap-2">
                  <button
                    @click="editProduct(p)"
                    class="rounded-lg bg-slate-100 p-2 text-slate-600 transition-all hover:bg-slate-200 hover:text-slate-900"
                    title="Edit"
                  >
                    <Pencil class="h-4 w-4" />
                  </button>
                  <button
                    @click="removeProduct(p.id)"
                    class="rounded-lg bg-red-50 p-2 text-danger-600 transition-all hover:bg-red-100 hover:text-danger-700"
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
      <div
        v-if="store.products.length === 0"
        class="flex flex-col items-center justify-center py-12 text-slate-500"
      >
        <Package class="h-12 w-12 mb-3 opacity-50" />
        <p>Tidak ada produk ditemukan</p>
      </div>
    </div>

    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal">
        <div class="mb-6 flex items-center justify-between">
          <h3 class="text-lg font-semibold">{{ form.id ? 'Edit' : 'Tambah' }} Produk</h3>
          <button
            @click="showForm = false"
            class="rounded-lg p-2 text-slate-400 transition-all hover:bg-slate-100 hover:text-slate-600"
          >
            <X class="h-5 w-5" />
          </button>
        </div>
        
        <form @submit.prevent="saveProduct">
          <div class="form-group">
            <label>Kode Produk</label>
            <input v-model="form.product_code" required class="w-full" />
          </div>
          
          <div class="form-group">
            <label>Nama Produk</label>
            <input v-model="form.product_name" required class="w-full" />
          </div>
          
          <div class="form-group">
            <label>Tipe Produk</label>
            <select v-model="form.product_type" @change="onTypeChange" class="w-full">
              <option value="barang">Barang (Fisik)</option>
              <option value="jasa">Jasa (Layanan)</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Harga</label>
            <input v-model.number="form.price" type="number" required min="0" class="w-full" />
          </div>
          
          <div v-if="form.product_type === 'barang'" class="form-group">
            <label>Stok</label>
            <input
              v-model.number="form.stock"
              type="number"
              required
              min="0"
              class="w-full"
            />
            <p class="mt-1 text-xs text-slate-500">Jumlah barang fisik yang tersedia</p>
          </div>
          
          <div v-else class="form-group">
            <label>Status Ketersediaan</label>
            <select v-model="form.service_status" class="w-full">
              <option value="tersedia">Tersedia</option>
              <option value="tidak_tersedia">Tidak Tersedia</option>
            </select>
            <p class="mt-1 text-xs text-slate-500">Jasa tidak menggunakan manajemen stok</p>
          </div>
          
          <div class="form-group">
            <label>Kategori</label>
            <select v-model="form.category_id" class="w-full">
              <option :value="null">Tanpa Kategori</option>
              <option v-for="c in store.categories" :key="c.id" :value="c.id">
                {{ c.category_name }}
              </option>
            </select>
          </div>
          
          <div class="flex justify-end gap-3 pt-4">
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
import { ref, onMounted } from 'vue'
import { useProductStore } from '@/stores/products'
import { Plus, Pencil, Trash2, Package, X } from 'lucide-vue-next'

const store = useProductStore()
const search = ref('')
const categoryFilter = ref('')
const showForm = ref(false)
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
  await store.fetchProducts({
    search: search.value || undefined,
    category_id: categoryFilter.value || undefined
  })
}

function editProduct(p) {
  form.value = { ...p }
  showForm.value = true
}

async function saveProduct() {
  if (form.value.id) {
    await store.updateProduct(form.value.id, form.value)
  } else {
    await store.createProduct(form.value)
  }
  showForm.value = false
  form.value = {
    product_code: '',
    product_name: '',
    product_type: 'barang',
    price: 0,
    stock: 0,
    service_status: 'tersedia',
    category_id: null
  }
  await load()
}

async function removeProduct(id) {
  if (confirm('Hapus produk ini?')) {
    await store.deleteProduct(id)
    await load()
  }
}

onMounted(async () => {
  await store.fetchCategories()
  await load()
})
</script>
