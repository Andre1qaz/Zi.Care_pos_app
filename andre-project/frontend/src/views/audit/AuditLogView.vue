<template>
  <div>
    <div class="page-header">
      <h1>Audit Log History</h1>
    </div>

    <div class="filters card">
      <div class="filter-group">
        <label>User</label>
        <select v-model="filters.user_id" @change="loadLogs">
          <option value="">Semua User</option>
          <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Activity</label>
        <input v-model="filters.activity" placeholder="Cari activity..." @input="loadLogs" />
      </div>
      <div class="filter-group">
        <label>Entity Type</label>
        <select v-model="filters.entity_type" @change="loadLogs">
          <option value="">Semua Entity</option>
          <option value="User">User</option>
          <option value="Product">Product</option>
          <option value="Category">Category</option>
          <option value="Customer">Customer</option>
          <option value="Invoice">Invoice</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Tanggal Mulai</label>
        <input v-model="filters.start_date" type="date" @change="loadLogs" />
      </div>
      <div class="filter-group">
        <label>Tanggal Akhir</label>
        <input v-model="filters.end_date" type="date" @change="loadLogs" />
      </div>
    </div>

    <div class="stats-grid" style="margin-bottom: 1.5rem">
      <div class="stat-card card">
        <span class="stat-icon">📋</span>
        <div>
          <span class="stat-label">Total Logs</span>
          <span class="stat-value">{{ stats.total_logs || 0 }}</span>
        </div>
      </div>
      <div class="stat-card card">
        <span class="stat-icon">📅</span>
        <div>
          <span class="stat-label">Hari Ini</span>
          <span class="stat-value">{{ stats.today_logs || 0 }}</span>
        </div>
      </div>
    </div>

    <div class="card">
      <table>
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>User</th>
            <th>Activity</th>
            <th>Entity</th>
            <th>Detail</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id">
            <td>{{ formatDate(log.created_at) }}</td>
            <td>{{ log.user_name || 'System' }}</td>
            <td><span class="activity-badge">{{ log.activity }}</span></td>
            <td>{{ log.entity_type || '-' }} {{ log.entity_id ? `#${log.entity_id}` : '' }}</td>
            <td>
              <button class="btn btn-primary btn-sm" @click="showDetail(log)">Detail</button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="logs.length === 0" class="empty">Tidak ada audit log ditemukan</p>
    </div>

    <div class="pagination" v-if="pagination.pages > 1">
      <button class="btn" :disabled="pagination.page === 1" @click="changePage(pagination.page - 1)">Prev</button>
      <span>Halaman {{ pagination.page }} dari {{ pagination.pages }}</span>
      <button class="btn" :disabled="pagination.page === pagination.pages" @click="changePage(pagination.page + 1)">Next</button>
    </div>

    <div v-if="selectedLog" class="modal-overlay" @click.self="selectedLog = null">
      <div class="modal card">
        <h3>Detail Audit Log</h3>
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
        <div class="form-actions">
          <button class="btn btn-primary" @click="selectedLog = null">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

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
  const { data } = await api.get('/audit-logs', {
    params: {
      page: pagination.value.page,
      limit: pagination.value.limit,
      ...Object.fromEntries(
        Object.entries(filters.value).filter(([_, v]) => v !== '' && v !== null)
      )
    }
  })
  logs.value = data.data
  pagination.value = data.pagination
}

async function loadStats() {
  const { data } = await api.get('/audit-logs/stats')
  stats.value = data
}

async function loadUsers() {
  const { data } = await api.get('/users')
  users.value = data.data
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
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.page-header h1 { margin: 0; font-size: 1.5rem; font-weight: 700; }
.filters { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-group { flex: 1; min-width: 150px; }
.filter-group label { display: block; font-size: 0.75rem; font-weight: 600; color: var(--secondary); margin-bottom: 0.25rem; }
.stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.stat-card { display: flex; align-items: center; gap: 1rem; padding: 1rem; }
.stat-icon { font-size: 1.5rem; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: var(--primary-light); border-radius: var(--radius-md); }
.stat-label { display: block; font-size: 0.875rem; color: var(--secondary); font-weight: 500; }
.stat-value { display: block; font-size: 1.25rem; font-weight: 700; color: #1f2937; }
.activity-badge { padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.75rem; font-weight: 600; background: #dbeafe; color: #1e40af; }
.empty { color: var(--secondary); text-align: center; padding: 3rem 1rem; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 1rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100; }
.modal { width: 100%; max-width: 600px; max-height: 80vh; overflow-y: auto; }
.detail-row { display: flex; margin-bottom: 0.75rem; }
.detail-row label { font-weight: 600; min-width: 100px; color: var(--secondary); }
.detail-row pre { background: var(--surface); padding: 0.75rem; border-radius: var(--radius-sm); overflow-x: auto; flex: 1; font-size: 0.8rem; }
.form-actions { display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem; }
</style>
