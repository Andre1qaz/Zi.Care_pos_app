<template>
  <div>
    <div class="page-header">
      <h1>Manajemen Pengguna</h1>
      <button class="btn btn-primary" @click="openForm()">+ Tambah Pengguna</button>
    </div>
    <div class="card table-container" style="margin-top: 1rem">
      <table class="custom-table">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th style="text-align: center;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id">
            <td>{{ u.name }}</td>
            <td>{{ u.email }}</td>
            <td><span :class="['role-badge', u.role_id]">{{ roleName(u.role_id) }}</span></td>
            <td><span :class="['status-badge', u.is_active ? 'active' : 'inactive']">{{ u.is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
            <td>
              <div class="action-buttons">
                <button class="btn btn-primary btn-sm" @click="openForm(u)">Edit</button>
                <button class="btn btn-danger btn-sm" @click="deleteUser(u.id)">Hapus</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="users.length === 0" class="empty">Tidak ada pengguna ditemukan</p>
    </div>

    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal card">
        <h3>{{ form.id ? 'Edit' : 'Tambah' }} Pengguna</h3>
        <form @submit.prevent="saveUser">
          <div class="form-group"><label>Nama</label><input v-model="form.name" required /></div>
          <div class="form-group"><label>Email</label><input v-model="form.email" type="email" required /></div>
          <div class="form-group">
            <label>Role</label>
            <select v-model="form.role_id" required>
              <option value="1">Administrator</option>
              <option value="2">Manager</option>
              <option value="3">Cashier</option>
            </select>
          </div>
          <div class="form-group">
            <label>Password {{ form.id ? '(kosongkan jika tidak diubah)' : '' }}</label>
            <input v-model="form.password" type="password" :required="!form.id" />
          </div>
          <div class="form-group">
            <label>Status</label>
            <select v-model="form.is_active">
              <option :value="1">Aktif</option>
              <option :value="0">Nonaktif</option>
            </select>
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

const users = ref([])
const showForm = ref(false)
const form = ref({ name: '', email: '', role_id: '3', password: '', is_active: 1 })

const roles = { 1: 'Administrator', 2: 'Manager', 3: 'Cashier' }
function roleName(id) { return roles[id] || id }

async function loadUsers() {
  const { data } = await api.get('/users')
  users.value = data.data
}

function openForm(user = null) {
  if (user) {
    form.value = { ...user, password: '' }
  } else {
    form.value = { name: '', email: '', role_id: '3', password: '', is_active: 1 }
  }
  showForm.value = true
}

async function saveUser() {
  try {
    const payload = { ...form.value }
    if (!payload.password) {
      delete payload.password
    }
    if (form.value.id) {
      await api.put(`/users/${form.value.id}`, payload)
    } else {
      await api.post('/users', payload)
    }
    showForm.value = false
    form.value = { name: '', email: '', role_id: '3', password: '', is_active: 1 }
    await loadUsers()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan pengguna')
  }
}

async function deleteUser(id) {
  if (confirm('Hapus pengguna ini?')) {
    try {
      await api.delete(`/users/${id}`)
      await loadUsers()
    } catch (e) {
      alert(e.response?.data?.message || 'Gagal menghapus pengguna')
    }
  }
}

onMounted(async () => {
  await loadUsers()
})
</script>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.page-header h1 { margin: 0; font-size: 1.5rem; font-weight: 700; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100; }
.modal { width: 100%; max-width: 500px; }
.form-actions { display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem; }
.role-badge { padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.75rem; font-weight: 600; }
.role-badge[class*="1"] { background: #dbeafe; color: #1e40af; }
.role-badge[class*="2"] { background: #fef3c7; color: #92400e; }
.role-badge[class*="3"] { background: #d1fae5; color: #065f46; }
.status-badge { padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.75rem; font-weight: 600; }
.status-badge.active { background: #d1fae5; color: #065f46; }
.status-badge.inactive { background: #f3f4f6; color: #6b7280; }
.empty { color: var(--secondary); text-align: center; padding: 3rem 1rem; }

/* Tambahan CSS untuk memperbaiki layout tabel */
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