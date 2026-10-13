<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Manajemen Pengguna</h1>
        <p class="page-subtitle">Kelola akun, role dan status pengguna</p>
      </div>
      <button class="btn btn-primary" @click="openForm()">
        <Plus class="h-4 w-4" />
        Tambah Pengguna
      </button>
    </div>

    <div class="table-card">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in users" :key="u.id">
              <td class="font-medium">{{ u.name }}</td>
              <td class="text-slate-600">{{ u.email }}</td>
              <td>
                <span
                  :class="[
                    'badge',
                    String(u.role_id) === '1'
                      ? 'badge-primary'
                      : String(u.role_id) === '2'
                        ? 'badge-warning'
                        : 'badge-success'
                  ]"
                >
                  {{ roleName(u.role_id) }}
                </span>
              </td>
              <td>
                <span :class="['badge', u.is_active ? 'badge-success' : 'badge-slate']">
                  {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td>
                <div class="flex justify-center gap-2">
                  <button class="btn btn-secondary btn-icon-sm" title="Edit" @click="openForm(u)">
                    <Pencil class="h-4 w-4" />
                  </button>
                  <button class="btn btn-danger btn-icon-sm" title="Hapus" @click="deleteUser(u.id)">
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="users.length === 0" class="empty-state">
        <div class="empty-icon">
          <Users class="h-7 w-7" />
        </div>
        <p>Tidak ada pengguna ditemukan</p>
      </div>
    </div>

    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">{{ form.id ? 'Edit' : 'Tambah' }} Pengguna</h3>
          <button class="btn btn-ghost btn-icon-sm" title="Tutup" @click="showForm = false">
            <X class="h-5 w-5" />
          </button>
        </div>
        <form @submit.prevent="saveUser">
          <div class="modal-body">
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
            <div class="form-group mb-0">
              <label>Status</label>
              <select v-model="form.is_active">
                <option :value="1">Aktif</option>
                <option :value="0">Nonaktif</option>
              </select>
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
import { Plus, Pencil, Trash2, Users, X } from 'lucide-vue-next'
import { toast, confirmDialog } from '@/composables/feedback'

const users = ref([])
const showForm = ref(false)
const form = ref({ name: '', email: '', role_id: '3', password: '', is_active: 1 })

const roles = { 1: 'Administrator', 2: 'Manager', 3: 'Cashier' }
function roleName(id) { return roles[id] || id }

async function loadUsers() {
  try {
    const { data } = await api.get('/users')
    users.value = data.data
  } catch (e) {
    toast.error('Gagal memuat daftar pengguna.')
  }
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
    toast.error(e.response?.data?.message || 'Gagal menyimpan pengguna')
  }
}

async function deleteUser(id) {
  const confirmed = await confirmDialog('Hapus pengguna ini?', {
    title: 'Hapus Pengguna',
    confirmText: 'Ya, Hapus',
    variant: 'danger'
  })
  if (confirmed) {
    try {
      await api.delete(`/users/${id}`)
      toast.success('Pengguna berhasil dihapus')
      await loadUsers()
    } catch (e) {
      toast.error(e.response?.data?.message || 'Gagal menghapus pengguna')
    }
  }
}

onMounted(async () => {
  await loadUsers()
})
</script>
