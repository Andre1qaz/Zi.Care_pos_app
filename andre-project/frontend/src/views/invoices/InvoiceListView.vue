<template>
  <div>
    <div class="page-header">
      <h1>Daftar Invoice</h1>
    </div>
    <div class="filters card">
      <div class="filter-group">
        <label>Cari</label>
        <input v-model="search" placeholder="No. invoice..." @input="load" class="form-control" />
      </div>
      <div class="filter-group">
        <label>Status</label>
        <select v-model="statusFilter" @change="load" class="form-control">
          <option value="">Semua Status</option>
          <option value="paid">Paid</option>
          <option value="pending">Pending</option>
        </select>
      </div>
    </div>
    <div class="card table-container" style="margin-top: 1rem">
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>No. Invoice</th>
              <th>Tanggal</th>
              <th>Total</th>
              <th class="text-center">Metode</th>
              <th class="text-center">Status</th>
              <th class="text-center">Sync</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="inv in invoices" :key="inv.id">
              <td class="font-medium">{{ inv.invoice_number }}</td>
              <td>{{ formatDate(inv.created_at) }}</td>
              <td>{{ formatCurrency(inv.total_amount) }}</td>
              <td class="text-center"><span :class="['payment-badge', inv.payment_method]">{{ inv.payment_method }}</span></td>
              <td class="text-center"><span :class="['status-badge', inv.payment_status]">{{ inv.payment_status }}</span></td>
              <td class="text-center"><span :class="['sync-badge', inv.sync_status]">{{ inv.sync_status }}</span></td>
              <td class="text-center"><router-link :to="`/invoices/${inv.id}`" class="btn btn-primary btn-sm">Detail</router-link></td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="invoices.length === 0" class="empty">Tidak ada invoice ditemukan</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const invoices = ref([])
const search = ref('')
const statusFilter = ref('')

function formatCurrency(v) { return 'Rp ' + Number(v).toLocaleString('id-ID') }

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID')
}

async function load() {
  const { data } = await api.get('/invoices', {
    params: {
      search: search.value || undefined,
      payment_status: statusFilter.value || undefined
    }
  })
  invoices.value = data.data
}

onMounted(load)
</script>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.page-header h1 { margin: 0; font-size: 1.5rem; font-weight: 700; }
.filters { display: flex; gap: 1.5rem; margin-bottom: 1rem; }
.filter-group { flex: 1; }
.filter-group label { display: block; font-size: 0.75rem; font-weight: 600; color: var(--secondary, #6b7280); margin-bottom: 0.5rem; }
.form-control { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem; outline: none; transition: border-color 0.2s; }
.form-control:focus { border-color: #3b82f6; }

/* Perbaikan Layout Tabel */
.table-container { padding: 0; overflow: hidden; }
.table-responsive { overflow-x: auto; width: 100%; }
table { width: 100%; border-collapse: collapse; white-space: nowrap; }
th, td { padding: 1rem 1.25rem; text-align: left; border-bottom: 1px solid #f3f4f6; font-size: 0.875rem; }
th { font-weight: 600; color: #374151; background-color: #f9fafb; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
td { color: #4b5563; }
tbody tr { transition: background-color 0.2s; }
tbody tr:hover { background-color: #f9fafb; }
.font-medium { font-weight: 600; color: #111827; }
.text-center { text-align: center; }

/* Perbaikan Badge */
.payment-badge, .status-badge, .sync-badge { 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  padding: 0.25rem 0.75rem; 
  border-radius: 9999px; 
  font-size: 0.75rem; 
  font-weight: 600; 
  text-transform: capitalize; 
}
.payment-badge.cash { background: #d1fae5; color: #065f46; }
.payment-badge.qris { background: #dbeafe; color: #1e40af; }
.payment-badge.transfer { background: #fef3c7; color: #92400e; }
.payment-badge.ewallet { background: #fce7f3; color: #9d174d; }

.status-badge.paid { background: #d1fae5; color: #065f46; }
.status-badge.pending { background: #fef3c7; color: #92400e; }

.sync-badge.synced { background: #d1fae5; color: #065f46; }
.sync-badge.pending { background: #fef3c7; color: #92400e; }
.sync-badge.failed { background: #fee2e2; color: #991b1b; }

.empty { color: var(--secondary, #6b7280); text-align: center; padding: 3rem 1rem; font-size: 0.875rem; }
</style>