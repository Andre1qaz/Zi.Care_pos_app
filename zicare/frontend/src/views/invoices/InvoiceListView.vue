<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Daftar Invoice</h1>
        <p class="page-subtitle">Lihat dan lacak seluruh transaksi penjualan</p>
      </div>
    </div>

    <div class="card mb-6">
      <div class="grid gap-4 md:grid-cols-3">
        <div class="form-group mb-0 md:col-span-2">
          <label>Cari</label>
          <input v-model="search" placeholder="No. invoice..." @input="onFilterChange" />
        </div>
        <div class="form-group mb-0">
          <label>Status</label>
          <select v-model="statusFilter" @change="onFilterChange">
            <option value="">Semua Status</option>
            <option value="unpaid">Belum Dibayar</option>
            <option value="partial">Sebagian Dibayar</option>
            <option value="paid">Lunas</option>
            <option value="overdue">Jatuh Tempo</option>
            <option value="cancelled">Dibatalkan</option>
          </select>
        </div>
      </div>
    </div>

    <div class="table-card">
      <div class="overflow-x-auto">
        <table class="table">
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
              <td class="font-mono text-sm font-semibold text-slate-900">{{ inv.invoice_number }}</td>
              <td>{{ formatDate(inv.created_at) }}</td>
              <td class="font-medium">{{ formatCurrency(inv.total_amount) }}</td>
              <td class="text-center"><span class="badge" :class="inv.payment_method">{{ inv.payment_method }}</span></td>
              <td class="text-center"><span class="badge" :class="inv.payment_status">{{ inv.payment_status }}</span></td>
              <td class="text-center"><span class="badge" :class="inv.sync_status">{{ inv.sync_status }}</span></td>
              <td class="text-center">
                <div class="flex items-center justify-center gap-2">
                  <button
                    v-if="canSync"
                    class="btn btn-secondary btn-sm"
                    :disabled="syncingId === inv.id"
                    :title="inv.sync_status === 'synced' ? 'Sync ulang ke Odoo' : 'Kirim ke Odoo Accounting'"
                    @click="syncInvoice(inv)"
                  >
                    <RefreshCw :class="['h-3.5 w-3.5', syncingId === inv.id && 'animate-spin']" />
                  </button>
                  <router-link :to="`/invoices/${inv.id}`" class="btn btn-primary btn-sm">
                    <Eye class="h-3.5 w-3.5" />
                    Detail
                  </router-link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="invoices.length === 0" class="empty-state">
        <div class="empty-icon">
          <Receipt class="h-7 w-7" />
        </div>
        <p class="text-sm font-medium text-slate-500">Tidak ada invoice ditemukan</p>
      </div>

      <div class="pagination" v-if="pagination.pages > 1">
        <span>Halaman {{ pagination.page }} dari {{ pagination.pages }}</span>
        <div class="flex gap-2">
          <button class="page-btn" :disabled="pagination.page === 1" @click="changePage(pagination.page - 1)">Prev</button>
          <button class="page-btn" :disabled="pagination.page === pagination.pages" @click="changePage(pagination.page + 1)">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { toast } from '@/composables/feedback'
import { useAuthStore } from '@/stores/auth'
import { Eye, Receipt, RefreshCw } from 'lucide-vue-next'

const auth = useAuthStore()
const invoices = ref([])
const search = ref('')
const statusFilter = ref('')
const pagination = ref({ page: 1, limit: 20, total: 0, pages: 1 })
const syncingId = ref(null)

const canSync = computed(() => ['administrator', 'manager'].includes(auth.userRole))

function formatCurrency(v) { return 'Rp ' + Number(v).toLocaleString('id-ID') }

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID')
}

async function load() {
  try {
    const { data } = await api.get('/invoices', {
      params: {
        search: search.value || undefined,
        payment_status: statusFilter.value || undefined,
        page: pagination.value.page,
        limit: pagination.value.limit
      }
    })
    invoices.value = data.data
    const total = data.meta?.total || 0
    pagination.value.total = total
    pagination.value.pages = Math.max(1, Math.ceil(total / pagination.value.limit))
  } catch (e) {
    toast.error('Gagal memuat daftar invoice.')
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

async function syncInvoice(inv) {
  syncingId.value = inv.id
  try {
    const force = inv.sync_status === 'synced'
    const { data } = await api.post(`/invoices/${inv.id}/sync`, null, {
      params: force ? { force: 1 } : {}
    })
    toast.success(data?.message || `Invoice ${inv.invoice_number} tersinkron ke Odoo.`)
    await load()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal sinkron ke Odoo.')
    await load()
  } finally {
    syncingId.value = null
  }
}

onMounted(load)
</script>

<style scoped>
.badge {
  text-transform: capitalize;
}

.badge.cash,
.badge.paid,
.badge.synced {
  background: #d1fae5;
  color: #065f46;
}

.badge.qris {
  background: #dbeafe;
  color: #1e40af;
}

.badge.transfer,
.badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.badge.ewallet {
  background: #fce7f3;
  color: #9d174d;
}

.badge.failed {
  background: #fee2e2;
  color: #991b1b;
}
</style>
