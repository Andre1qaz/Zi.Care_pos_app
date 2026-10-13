<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Kategori Produk</h1>
        <p class="page-subtitle">Kelola kategori untuk mengelompokkan produk</p>
      </div>
      <button class="btn btn-primary" @click="openForm()">
        <Plus class="h-4 w-4" />
        Tambah Kategori
      </button>
    </div>

    <div class="table-card">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama Kategori</th>
              <th>Deskripsi</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in categories" :key="c.id">
              <td class="font-mono text-sm text-slate-500">{{ c.id }}</td>
              <td class="font-medium">{{ c.category_name }}</td>
              <td class="text-slate-600">{{ c.description || '-' }}</td>
              <td>
                <div class="flex justify-center gap-2">
                  <button class="btn btn-secondary btn-icon-sm" title="Edit" @click="openForm(c)">
                    <Pencil class="h-4 w-4" />
                  </button>
                  <button class="btn btn-danger btn-icon-sm" title="Hapus" @click="remove(c.id)">
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="categories.length === 0" class="empty-state">
        <div class="empty-icon">
          <Tags class="h-7 w-7" />
        </div>
        <p>Tidak ada kategori ditemukan</p>
      </div>
    </div>

    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">{{ form.id ? 'Edit' : 'Tambah' }} Kategori</h3>
          <button class="btn btn-ghost btn-icon-sm" title="Tutup" @click="showForm = false">
            <X class="h-5 w-5" />
          </button>
        </div>
        <form @submit.prevent="save">
          <div class="modal-body">
            <div class="form-group">
              <label>Nama</label>
              <input v-model="form.category_name" required />
            </div>
            <div class="form-group mb-0">
              <label>Deskripsi</label>
              <textarea v-model="form.description" rows="3"></textarea>
            </div>
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
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { Plus, Pencil, Trash2, Tags, X } from 'lucide-vue-next'
import { toast, confirmDialog } from '@/composables/feedback'

const categories = ref([])
const showForm = ref(false)
const form = ref({ category_name: '', description: '' })

async function load() {
  try {
    const { data } = await api.get('/categories')
    categories.value = data.data
  } catch (e) {
    toast.error('Gagal memuat kategori.')
  }
}

function openForm(c = null) {
  form.value = c ? { ...c } : { category_name: '', description: '' }
  showForm.value = true
}

async function save() {
  const isEdit = !!form.value.id
  try {
    if (isEdit) {
      await api.put(`/categories/${form.value.id}`, form.value)
    } else {
      await api.post('/categories', form.value)
    }
    showForm.value = false
    await load()
    toast.success(isEdit ? 'Kategori berhasil diperbarui' : 'Kategori berhasil ditambahkan')
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Gagal menyimpan kategori.')
  }
}

async function remove(id) {
  const confirmed = await confirmDialog('Hapus kategori?', {
    title: 'Hapus Kategori',
    confirmText: 'Ya, Hapus',
    variant: 'danger'
  })
  if (confirmed) {
    try {
      await api.delete(`/categories/${id}`)
      toast.success('Kategori berhasil dihapus')
      await load()
    } catch (e) {
      toast.error(e?.response?.data?.message || 'Gagal menghapus kategori.')
    }
  }
}

onMounted(load)
</script>
