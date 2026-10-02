<template>
  <div>
    <div class="page-header">
      <h1>Kategori Produk</h1>
      <button class="btn btn-primary" @click="openForm()">+ Tambah Kategori</button>
    </div>
    <div class="card table-container" style="margin-top: 1rem">
      <table class="custom-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama Kategori</th>
            <th>Deskripsi</th>
            <th style="text-align: center;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in categories" :key="c.id">
            <td>{{ c.id }}</td>
            <td>{{ c.category_name }}</td>
            <td>{{ c.description || '-' }}</td>
            <td>
              <div class="action-buttons">
                <button class="btn btn-primary btn-sm" @click="openForm(c)">Edit</button>
                <button class="btn btn-danger btn-sm" @click="remove(c.id)">Hapus</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal card">
        <h3>{{ form.id ? 'Edit' : 'Tambah' }} Kategori</h3>
        <form @submit.prevent="save">
          <div class="form-group">
            <label>Nama</label>
            <input v-model="form.category_name" required />
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <textarea v-model="form.description" rows="3"></textarea>
          </div>
          <div class="form-actions">
            <button type="button" class="btn" @click="showForm = false">Batal</button>
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

const categories = ref([])
const showForm = ref(false)
const form = ref({ category_name: '', description: '' })

async function load() {
  const { data } = await api.get('/categories')
  categories.value = data.data
}

function openForm(c = null) {
  form.value = c ? { ...c } : { category_name: '', description: '' }
  showForm.value = true
}

async function save() {
  if (form.value.id) {
    await api.put(`/categories/${form.value.id}`, form.value)
  } else {
    await api.post('/categories', form.value)
  }
  showForm.value = false
  await load()
}

async function remove(id) {
  if (confirm('Hapus kategori?')) {
    await api.delete(`/categories/${id}`)
    await load()
  }
}

onMounted(load)
</script>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.page-header h1 { margin: 0; font-size: 1.5rem; font-weight: 700; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100; }
.modal { width: 100%; max-width: 450px; }
.form-actions { display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem; }

/* Tambahan CSS untuk memperbaiki layout tabel */
.table-container {
  overflow-x: auto; /* Agar tidak rusak di layar kecil */
}

.custom-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.custom-table th, 
.custom-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
}

.custom-table th {
  font-weight: 600;
  color: #374151;
  background-color: #f9fafb; /* Latar belakang header yang sedikit lebih gelap */
}

.custom-table tbody tr:hover {
  background-color: #f9fafb; /* Efek hover pada baris */
}

.action-buttons {
  display: flex;
  gap: 0.5rem; /* Jarak antar tombol */
  justify-content: center;
}
</style>