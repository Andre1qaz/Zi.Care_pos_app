<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Audit Log History</h1>
        <p class="page-subtitle">Rekam jejak aktivitas pengguna di dalam sistem</p>
      </div>
    </div>

    <div class="card mb-6">
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
        <div class="form-group mb-0">
          <label>User</label>
          <select v-model="filters.user_id" @change="loadLogs">
            <option value="">Semua User</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
          </select>
        </div>
        <div class="form-group mb-0">
          <label>Activity</label>
          <input v-model="filters.activity" placeholder="Cari activity..." @input="loadLogs" />
        </div>
        <div class="form-group mb-0">
          <label>Entity Type</label>
          <select v-model="filters.entity_type" @change="loadLogs">
            <option value="">Semua Entity</option>
            <option value="user">User</option>
            <option value="product">Product</option>
            <option value="category">Category</option>
            <option value="customer">Customer</option>
            <option value="invoice">Invoice</option>
          </select>
        </div>
        <div class="form-group mb-0">
          <label>Tanggal Mulai</label>
          <input v-model="filters.start_date" type="date" @change="loadLogs" />
        </div>
        <div class="form-group mb-0">
          <label>Tanggal Akhir</label>
          <input v-model="filters.end_date" type="date" @change="loadLogs" />
        </div>
      </div>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
      <div class="stat-card">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
            <ClipboardList class="h-6 w-6" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500">Total Logs</p>
            <p class="text-2xl font-bold text-slate-900">{{ stats.total_logs || 0 }}</p>
          </div>
        </div>
      </div>
      <div class="stat-card">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-success-50 text-success-600">
            <Calendar class="h-6 w-6" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500">Hari Ini</p>
            <p class="text-2xl font-bold text-slate-900">{{ stats.today_logs || 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="table-card">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>User</th>
              <th>Activity</th>
              <th>Entity</th>
              <th class="text-right">Detail</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td>{{ formatDate(log.created_at) }}</td>
              <td class="font-medium text-slate-900">{{ log.user_name || 'System' }}</td>
              <td><span class="badge badge-primary">{{ log.activity }}</span></td>
              <td>{{ log.entity_type || '-' }} {{ log.entity_id ? `#${log.entity_id}` : '' }}</td>
              <td class="text-right">
                <button class="btn btn-icon-sm btn-secondary" title="Detail" @click="showDetail(log)">
                  <Eye class="h-4 w-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="logs.length === 0" class="empty-state">
        <div class="empty-icon">
          <ClipboardList class="h-7 w-7" />
        </div>
        <p class="text-sm font-medium text-slate-500">Tidak ada audit log ditemukan</p>
      </div>

      <div class="pagination" v-if="pagination.pages > 1">
        <span>Halaman {{ pagination.page }} dari {{ pagination.pages }}</span>
        <div class="flex gap-2">
          <button class="page-btn" :disabled="pagination.page === 1" @click="changePage(pagination.page - 1)">Prev</button>
          <button class="page-btn" :disabled="pagination.page === pagination.pages" @click="changePage(pagination.page + 1)">Next</button>
        </div>
      </div>
    </div>

    <div v-if="selectedLog" class="modal-overlay" @click.self="selectedLog = null">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">Detail Audit Log</h3>
          <button class="btn btn-ghost btn-icon-sm" title="Tutup" @click="selectedLog = null">
            <X class="h-4 w-4" />
          </button>
        </div>
        <div class="modal-body">
          <div class="detail-row">
            <label>ID:</label>
            <span>{{ selectedLog.id }}</span>
          </div>
          <div class="detail-row">
            <label>Tanggal:</label>
            <span>{{ formatDate(selectedLog.created_at) }}</span>
          </div>
          <div class="detail-row">
            <label>User:</label>
            <span>{{ selectedLog.user_name || 'System' }} ({{ selectedLog.user_email }})</span>
          </div>
          <div class="detail-row">
            <label>Activity:</label>
            <span>{{ selectedLog.activity }}</span>
          </div>
          <div class="detail-row">
            <label>Entity:</label>
            <span>{{ selectedLog.entity_type || '-' }} {{ selectedLog.entity_id ? `#${selectedLog.entity_id}` : '' }}</span>
          </div>
          <div class="detail-row" v-if="selectedLog.metadata">
            <label>Metadata:</label>
            <pre>{{ JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" @click="selectedLog = null">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { toast } from '@/composables/feedback'
import { ClipboardList, Calendar, Eye, X } from 'lucide-vue-next'

const logs = ref([])
const users = ref([])
const stats = ref({})
const pagination = ref({ page: 1, limit: 50, total: 0, pages: 1 })
const filters = ref({
  user_id: '',
  activity: '',
  entity_type: '',
  start_date: '',
  end_date: ''
})
const selectedLog = ref(null)

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID')
}

async function loadLogs() {
  try {
    const { data } = await api.get('/audit-logs', {
      params: {
        page: pagination.value.page,
        limit: pagination.value.limit,
        ...Object.fromEntries(
          Object.entries(filters.value).filter(([_, v]) => v !== '' && v !== null)
        )
      }
    })
    logs.value = data.data.data || []
    pagination.value = data.data.pagination || pagination.value
  } catch (e) {
    toast.error('Gagal memuat audit log.')
  }
}

async function loadStats() {
  try {
    const { data } = await api.get('/audit-logs/stats')
    stats.value = data.data || {}
  } catch (e) {
    stats.value = {}
  }
}

async function loadUsers() {
  try {
    const { data } = await api.get('/users')
    users.value = data.data
  } catch (e) {
    users.value = []
  }
}

function showDetail(log) {
  selectedLog.value = log
}

function changePage(page) {
  pagination.value.page = page
  loadLogs()
}

onMounted(async () => {
  await Promise.all([loadUsers(), loadStats(), loadLogs()])
})
</script>

<style scoped>
.detail-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.detail-row label {
  flex-shrink: 0;
  min-width: 100px;
  font-weight: 600;
  color: var(--secondary);
}

.detail-row span {
  color: #334155;
}

.detail-row pre {
  flex: 1;
  overflow-x: auto;
  border-radius: var(--radius-sm);
  background: var(--surface);
  padding: 0.75rem;
  font-size: 0.8rem;
}
</style>
