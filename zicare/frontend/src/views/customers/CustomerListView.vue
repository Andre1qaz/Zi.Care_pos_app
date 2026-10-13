<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Manajemen Pelanggan</h1>
        <p class="page-subtitle">Kelola data pelanggan dan lihat riwayat transaksi mereka</p>
      </div>
      <button class="btn btn-primary" @click="openForm()">
        <Plus class="h-4 w-4" />
        Tambah Pelanggan
      </button>
    </div>

    <div class="card mb-6">
      <div class="grid gap-4 md:grid-cols-3">
        <div class="form-group mb-0 md:col-span-2">
          <label>Cari Pelanggan</label>
          <input v-model="search" placeholder="Nama pelanggan..." @input="onFilterChange" />
        </div>
      </div>
    </div>

    <div class="table-card">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Telepon</th>
              <th>Email</th>
              <th>Alamat</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in customers" :key="c.id">
              <td class="font-medium text-slate-900">{{ c.customer_name }}</td>
              <td>{{ c.phone || '-' }}</td>
              <td>{{ c.email || '-' }}</td>
              <td>{{ c.address || '-' }}</td>
              <td>
                <div class="flex justify-center gap-2">
                  <button class="btn btn-icon-sm btn-secondary" title="Edit" @click="openForm(c)">
                    <Pencil class="h-4 w-4" />
                  </button>
                  <button class="btn btn-icon-sm btn-primary" title="Riwayat" @click="viewHistory(c)">
                    <History class="h-4 w-4" />
                  </button>
                  <button v-if="canDelete" class="btn btn-icon-sm btn-danger" title="Hapus" @click="remove(c.id)">
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="customers.length === 0" class="empty-state">
        <div class="empty-icon">
          <Users class="h-7 w-7" />
        </div>
        <p class="text-sm font-medium text-slate-500">Tidak ada pelanggan ditemukan</p>
      </div>

      <div class="pagination" v-if="pagination.pages > 1">
        <span>Halaman {{ pagination.page }} dari {{ pagination.pages }}</span>
        <div class="flex gap-2">
          <button class="page-btn" :disabled="pagination.page === 1" @click="changePage(pagination.page - 1)">Prev</button>
          <button class="page-btn" :disabled="pagination.page === pagination.pages" @click="changePage(pagination.page + 1)">Next</button>
        </div>
      </div>
    </div>

    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">{{ form.id ? 'Edit' : 'Tambah' }} Pelanggan</h3>
          <button class="btn btn-ghost btn-icon-sm" title="Tutup" @click="showForm = false">
            <X class="h-4 w-4" />
          </button>
        </div>
        <form @submit.prevent="save">
          <div class="modal-body">
            <div class="form-group"><label>Nama</label><input v-model="form.customer_name" required /></div>
            <div class="form-group"><label>Telepon</label><input v-model="form.phone" /></div>
            <div class="form-group"><label>Email</label><input v-model="form.email" type="email" /></div>
            <div class="form-group"><label>Alamat</label><textarea v-model="form.address" rows="2"></textarea></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showForm = false">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { toast, confirmDialog } from '@/composables/feedback'
import { Plus, Pencil, Trash2, History, Users, X } from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()
// Backend allows customer deletion for administrator + manager only.
const canDelete = computed(() => ['administrator', 'manager'].includes(auth.userRole))
const customers = ref([])
const search = ref('')
const showForm = ref(false)
const form = ref({ customer_name: '', phone: '', email: '', address: '' })
const pagination = ref({ page: 1, limit: 20, total: 0, pages: 1 })

async function load() {
  try {
    const { data } = await api.get('/customers', {
      params: {
        search: search.value,
        page: pagination.value.page,
        limit: pagination.value.limit
      }
    })
    customers.value = data.data
    const total = data.meta?.total || 0
    pagination.value.total = total
    pagination.value.pages = Math.max(1, Math.ceil(total / pagination.value.limit))
  } catch (e) {
    toast.error('Gagal memuat data pelanggan.')
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

function openForm(c = null) {
  form.value = c ? { ...c } : { customer_name: '', phone: '', email: '', address: '' }
  showForm.value = true
}

function viewHistory(customer) {
  router.push({ name: 'customer-history', params: { id: customer.id } })
}

async function save() {
  try {
    if (form.value.id) {
      await api.put(`/customers/${form.value.id}`, form.value)
    } else {
      await api.post('/customers', form.value)
    }
    showForm.value = false
    await load()
    toast.success('Pelanggan berhasil disimpan')
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Gagal menyimpan pelanggan.')
  }
}

async function remove(id) {
  const ok = await confirmDialog('Hapus pelanggan?', {
    title: 'Hapus Pelanggan',
    confirmText: 'Hapus',
    variant: 'danger'
  })
  if (ok) {
    try {
      await api.delete(`/customers/${id}`)
      await load()
      toast.success('Pelanggan berhasil dihapus')
    } catch (e) {
      toast.error(e?.response?.data?.message || 'Gagal menghapus pelanggan.')
    }
  }
}

onMounted(load)
</script>
