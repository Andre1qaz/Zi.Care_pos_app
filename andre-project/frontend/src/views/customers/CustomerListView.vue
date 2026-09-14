<template>
  <div>
    <div class="page-header">
      <h1>Manajemen Pelanggan</h1>
      <button class="btn btn-primary" @click="openForm()">+ Tambah Pelanggan</button>
    </div>

    <div class="filters card">
      <div class="filter-group">
        <label>Cari</label>
        <input v-model="search" placeholder="Nama pelanggan..." @input="load" />
      </div>
    </div>
    <div class="card table-container" style="margin-top: 1rem">
      <table class="custom-table">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Telepon</th>
            <th>Email</th>
            <th>Alamat</th>
            <th style="text-align: center;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in customers" :key="c.id">
            <td>{{ c.customer_name }}</td>
            <td>{{ c.phone || '-' }}</td>
            <td>{{ c.email || '-' }}</td>
            <td>{{ c.address || '-' }}</td>
            <td>
              <div class="action-buttons">
                <button class="btn btn-primary btn-sm" @click="openForm(c)">Edit</button>
                <button class="btn btn-success btn-sm" @click="viewHistory(c)">History</button>
                <button class="btn btn-danger btn-sm" @click="remove(c.id)">Hapus</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal card">
        <h3>{{ form.id ? 'Edit' : 'Tambah' }} Pelanggan</h3>
        <form @submit.prevent="save">
          <div class="form-group"><label>Nama</label><input v-model="form.customer_name" required /></div>
          <div class="form-group"><label>Telepon</label><input v-model="form.phone" /></div>
          <div class="form-group"><label>Email</label><input v-model="form.email" type="email" /></div>
          <div class="form-group"><label>Alamat</label><textarea v-model="form.address" rows="2"></textarea></div>
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
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const customers = ref([])
const search = ref('')
const showForm = ref(false)
const form = ref({ customer_name: '', phone: '', email: '', address: '' })

async function load() {
  const { data } = await api.get('/customers', { params: { search: search.value } })
  customers.value = data.data
}

function openForm(c = null) {
  form.value = c ? { ...c } : { customer_name: '', phone: '', email: '', address: '' }
  showForm.value = true
}

function viewHistory(customer) {
  router.push({ name: 'customer-history', params: { id: customer.id } })
}

async function save() {
  if (form.value.id) {
    await api.put(`/customers/${form.value.id}`, form.value)
  } else {
    await api.post('/customers', form.value)
  }
  showForm.value = false
  await load()
}

async function remove(id) {
  if (confirm('Hapus pelanggan?')) {
    await api.delete(`/customers/${id}`)
    await load()
  }
}

onMounted(load)
</script>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.page-header h1 { margin: 0; font-size: 1.5rem; font-weight: 700; }
.filters { display: flex; gap: 1rem; margin-bottom: 1rem; }
.filter-group { flex: 1; }
.filter-group label { display: block; font-size: 0.75rem; font-weight: 600; color: var(--secondary); margin-bottom: 0.25rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100; }
.modal { width: 100%; max-width: 450px; }
.form-actions { display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem; }

.table-container {
  overflow-x: auto;
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
  background-color: #f9fafb; 
}

.custom-table tbody tr:hover {
  background-color: #f9fafb; 
}

.action-buttons {
  display: flex;
  gap: 0.5rem; 
  justify-content: center;
}
</style>