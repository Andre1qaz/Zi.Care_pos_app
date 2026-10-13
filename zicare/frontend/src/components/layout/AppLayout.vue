<template>
  <div class="min-h-screen">
    <!-- Backdrop (mobile) -->
    <transition name="fade">
      <div
        v-if="mobileOpen"
        class="no-print fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"
        @click="mobileOpen = false"
      />
    </transition>

    <!-- Sidebar -->
    <aside
      class="no-print fixed inset-y-3 left-3 z-50 flex w-72 flex-col overflow-hidden rounded-[2rem] bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 text-white shadow-bubble-lg transition-transform duration-300 lg:translate-x-0"
      :class="mobileOpen ? 'translate-x-0' : '-translate-x-[120%]'"
    >
      <div class="flex items-center justify-between gap-3 px-6 pb-2 pt-6">
        <div class="flex items-center gap-3">
          <span class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl bg-white/95 shadow-bubble-sm">
            <img src="@/assets/zicare-logo.jpg" alt="Zi.CARE Logo" class="h-full w-full object-contain p-1" />
          </span>
          <div class="leading-tight">
            <p class="text-sm font-extrabold tracking-tight text-white">Zi.CARE</p>
            <p class="text-[11px] font-medium text-slate-400">Point of Sales</p>
          </div>
        </div>
        <button
          type="button"
          class="flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-white/10 hover:text-white lg:hidden"
          aria-label="Tutup menu"
          @click="mobileOpen = false"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      <nav class="mt-1 flex-1 space-y-1.5 overflow-y-auto px-3.5 py-2 no-scrollbar">
        <p class="px-3 pb-1 pt-2 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Menu</p>
        <router-link
          v-for="item in visibleNav"
          :key="item.to"
          :to="item.to"
          class="nav-link group"
          :class="{ 'nav-link-active': isActive(item.to) }"
          @click="mobileOpen = false"
        >
          <span
            class="flex h-9 w-9 items-center justify-center rounded-xl transition-colors"
            :class="isActive(item.to) ? 'bg-primary-100/70 text-primary-600' : 'bg-white/5 text-slate-400 group-hover:text-white'"
          >
            <component :is="item.icon" class="h-[18px] w-[18px]" />
          </span>
          <span class="flex-1 whitespace-nowrap">{{ item.label }}</span>
          <span
            v-if="isActive(item.to)"
            class="h-2 w-2 rounded-full bg-primary-500"
          />
        </router-link>
      </nav>

      <div class="p-3.5">
        <div class="flex items-center gap-3 rounded-3xl border border-white/10 bg-white/[0.06] p-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-brand-gradient text-sm font-bold text-white shadow-bubble-sm">
            {{ getInitials(auth.user?.name) }}
          </div>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-white">{{ auth.user?.name || 'Pengguna' }}</p>
            <p class="truncate text-[11px] capitalize text-slate-400">{{ auth.user?.role || '-' }}</p>
          </div>
          <button
            type="button"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/5 text-slate-400 transition-all hover:bg-danger-500 hover:text-white"
            title="Keluar"
            @click="logout"
          >
            <LogOut class="h-4 w-4" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Content -->
    <div class="lg:pl-[19.5rem]">
      <main class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8 lg:py-8">
        <!-- Top bar -->
        <header class="no-print mb-6 flex items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-soft transition-all hover:-translate-y-0.5 hover:text-primary-600 lg:hidden"
              aria-label="Buka menu"
              @click="mobileOpen = true"
            >
              <Menu class="h-5 w-5" />
            </button>
            <div class="hidden sm:block">
              <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-400">Zi.CARE POS</p>
              <p class="text-sm font-bold text-slate-700">{{ currentTitle }}</p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <!-- Low stock notifications -->
            <div class="relative">
              <button
                type="button"
                class="relative flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-soft transition-all hover:-translate-y-0.5 hover:text-primary-600"
                title="Notifikasi stok menipis"
                @click="toggleLowStock"
              >
                <Bell class="h-5 w-5" />
                <span
                  v-if="lowStock.length"
                  class="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-danger-500 px-1 text-[10px] font-bold text-white shadow"
                >
                  {{ lowStock.length > 99 ? '99+' : lowStock.length }}
                </span>
              </button>

              <div v-if="showLowStock" class="fixed inset-0 z-40" @click="showLowStock = false" />

              <transition name="fade">
                <div
                  v-if="showLowStock"
                  class="absolute right-0 top-12 z-50 w-[21rem] overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-bubble-lg"
                >
                  <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                    <div>
                      <p class="text-sm font-bold text-slate-900">Stok Menipis</p>
                      <p class="text-xs text-slate-500">Barang dengan stok ≤ {{ lowStockThreshold }}</p>
                    </div>
                    <button
                      type="button"
                      class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                      title="Segarkan"
                      @click="fetchLowStock"
                    >
                      <RefreshCw :class="['h-4 w-4', loadingLowStock && 'animate-spin']" />
                    </button>
                  </div>

                  <div class="max-h-80 overflow-y-auto">
                    <div v-if="loadingLowStock" class="flex items-center justify-center gap-2 py-10 text-sm text-slate-500">
                      <span class="spinner"></span> Memuat...
                    </div>
                    <div v-else-if="!lowStock.length" class="empty-state py-10">
                      <div class="empty-icon">
                        <CheckCircle2 class="h-7 w-7" />
                      </div>
                      <p class="text-sm text-slate-500">Semua stok aman 👍</p>
                    </div>
                    <ul v-else class="divide-y divide-slate-100">
                      <li
                        v-for="p in lowStock"
                        :key="p.id"
                        class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-slate-50"
                      >
                        <span
                          :class="[
                            'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl',
                            p.stock <= 0 ? 'bg-danger-100 text-danger-600' : 'bg-warning-100 text-warning-600'
                          ]"
                        >
                          <AlertTriangle class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                          <p class="truncate text-sm font-semibold text-slate-800">{{ p.product_name }}</p>
                          <p class="font-mono text-xs text-slate-400">{{ p.product_code }}</p>
                        </div>
                        <span :class="['badge', p.stock <= 0 ? 'badge-danger' : 'badge-warning']">
                          {{ p.stock <= 0 ? 'Habis' : 'Sisa ' + p.stock }}
                        </span>
                      </li>
                    </ul>
                  </div>

                  <button
                    v-if="canAccess(['administrator'])"
                    type="button"
                    class="flex w-full items-center justify-center gap-1.5 border-t border-slate-100 px-5 py-3 text-sm font-semibold text-primary-600 transition-colors hover:bg-primary-50"
                    @click="goToProducts"
                  >
                    <Package class="h-4 w-4" />
                    Kelola Produk
                  </button>
                </div>
              </transition>
            </div>

            <span class="chip hidden shadow-soft sm:inline-flex">
              <CalendarDays class="h-3.5 w-3.5 text-primary-500" />
              {{ today }}
            </span>
            <router-link
              v-if="canAccess(['administrator', 'cashier'])"
              to="/transactions"
              class="btn btn-primary btn-sm shadow-bubble-sm"
            >
              <Plus class="h-4 w-4" />
              <span class="hidden sm:inline">Transaksi Baru</span>
              <span class="sm:hidden">Baru</span>
            </router-link>
          </div>
        </header>

        <router-view v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import {
  LayoutDashboard,
  ShoppingCart,
  Receipt,
  Package,
  Tag,
  Users,
  BarChart3,
  UserCog,
  FileText,
  LogOut,
  Clock,
  Menu,
  X,
  CalendarDays,
  Plus,
  Bell,
  RefreshCw,
  AlertTriangle,
  CheckCircle2,
} from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const mobileOpen = ref(false)

const navItems = [
  { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, roles: ['administrator', 'manager'] },
  { to: '/transactions', label: 'Transaksi', icon: ShoppingCart, roles: ['administrator', 'cashier'] },
  { to: '/invoices', label: 'Invoice', icon: Receipt, roles: ['administrator', 'manager', 'cashier'] },
  { to: '/outstanding-payments', label: 'Pembayaran Tertunda', icon: Clock, roles: ['administrator', 'manager', 'cashier'] },
  { to: '/products', label: 'Produk', icon: Package, roles: ['administrator'] },
  { to: '/categories', label: 'Kategori', icon: Tag, roles: ['administrator'] },
  { to: '/customers', label: 'Pelanggan', icon: Users, roles: ['administrator', 'cashier'] },
  { to: '/reports', label: 'Laporan', icon: BarChart3, roles: ['administrator', 'manager'] },
  { to: '/users', label: 'Pengguna', icon: UserCog, roles: ['administrator'] },
  { to: '/audit-logs', label: 'Audit Log', icon: FileText, roles: ['administrator', 'manager'] },
]

function canAccess(roles) {
  return roles.includes(auth.userRole)
}

const visibleNav = computed(() => navItems.filter((item) => canAccess(item.roles)))

const titles = {
  dashboard: 'Dashboard',
  transactions: 'Transaksi Penjualan',
  invoices: 'Invoice',
  'invoice-detail': 'Detail Invoice',
  'outstanding-payments': 'Pembayaran Tertunda',
  products: 'Manajemen Produk',
  categories: 'Kategori',
  customers: 'Pelanggan',
  'customer-history': 'Riwayat Pelanggan',
  reports: 'Laporan',
  users: 'Manajemen Pengguna',
  'audit-logs': 'Audit Log',
}

const currentTitle = computed(() => titles[route.name] || 'Zi.CARE POS')

const today = computed(() =>
  new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
)

function isActive(to) {
  if (to === '/dashboard') return route.path === to
  return route.path === to || route.path.startsWith(`${to}/`)
}

function getInitials(name) {
  if (!name) return 'U'
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

function logout() {
  auth.logout()
  router.push('/login')
}

// ---- Low stock notifications ----
const lowStockThreshold = 10
const lowStock = ref([])
const showLowStock = ref(false)
const loadingLowStock = ref(false)

async function fetchLowStock() {
  loadingLowStock.value = true
  try {
    const { data } = await api.get('/products', {
      params: { low_stock: lowStockThreshold, limit: 100 }
    })
    lowStock.value = Array.isArray(data.data) ? data.data : []
  } catch {
    // Keep the bell quiet if the request fails (e.g. offline).
    lowStock.value = []
  } finally {
    loadingLowStock.value = false
  }
}

function toggleLowStock() {
  showLowStock.value = !showLowStock.value
  if (showLowStock.value) fetchLowStock()
}

function goToProducts() {
  showLowStock.value = false
  router.push('/products')
}

onMounted(fetchLowStock)
</script>

<style scoped>
.page-enter-active,
.page-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.page-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.page-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
