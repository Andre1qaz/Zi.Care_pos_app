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
          <span class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-bubble-md ring-4 ring-white/20">
            <img src="@/assets/zicare-logo.jpg" alt="Zi.CARE Logo" class="h-full w-full object-contain p-2" />
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
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/5 text-slate-400 transition-all hover:bg-primary-500 hover:text-white"
            title="Keamanan akun"
            @click="openSecurity"
          >
            <ShieldCheck class="h-4 w-4" />
          </button>
          <button
            type="button"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/5 text-slate-400 transition-all hover:bg-danger-500 hover:text-white"
            title="Keluar"
            @click="logout"
          >
            <LogOut class="h-4 w-4" />
          </button>
        </div>

        <div class="mt-2 flex items-center justify-between px-1 text-[10px] text-slate-500">
          <span>v{{ appVersion }}</span>
          <span v-if="showEnvBadge" class="rounded-full bg-warning-500/90 px-2 py-0.5 font-bold text-black">
            {{ envLabel }}
          </span>
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
            <!-- Low stock & unavailable service notifications -->
            <div class="relative">
              <button
                type="button"
                class="relative flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-soft transition-all hover:-translate-y-0.5 hover:text-primary-600"
                title="Notifikasi stok menipis & jasa tidak tersedia"
                @click="toggleLowStock"
              >
                <Bell class="h-5 w-5" />
                <span
                  v-if="notificationCount"
                  class="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-danger-500 px-1 text-[10px] font-bold text-white shadow"
                >
                  {{ notificationCount > 99 ? '99+' : notificationCount }}
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
                      <p class="text-sm font-bold text-slate-900">Notifikasi</p>
                      <p class="text-xs text-slate-500">Stok menipis (≤ {{ lowStockThreshold }}) & jasa tidak tersedia</p>
                    </div>
                    <button
                      type="button"
                      class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                      title="Segarkan"
                      @click="fetchNotifications"
                    >
                      <RefreshCw :class="['h-4 w-4', loadingNotifications && 'animate-spin']" />
                    </button>
                  </div>

                  <div class="max-h-80 overflow-y-auto">
                    <div v-if="loadingNotifications" class="flex items-center justify-center gap-2 py-10 text-sm text-slate-500">
                      <span class="spinner"></span> Memuat...
                    </div>

                    <template v-else>
                      <!-- Stok menipis -->
                      <div class="flex items-center justify-between gap-2 px-5 pb-1 pt-3">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Stok Menipis</p>
                        <span :class="['badge', lowStock.length ? 'badge-warning' : 'badge-slate']">
                          {{ lowStock.length }}
                        </span>
                      </div>

                      <div v-if="!lowStock.length" class="empty-state py-6">
                        <div class="empty-icon">
                          <CheckCircle2 class="h-6 w-6" />
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

                      <!-- Jasa tidak tersedia -->
                      <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-5 pb-1 pt-3">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Jasa Tidak Tersedia</p>
                        <span :class="['badge', unavailableServices.length ? 'badge-danger' : 'badge-success']">
                          {{ unavailableServices.length }}
                        </span>
                      </div>

                      <div v-if="!unavailableServices.length" class="empty-state py-6">
                        <div class="empty-icon">
                          <CheckCircle2 class="h-6 w-6" />
                        </div>
                        <p class="text-sm text-slate-500">Semua jasa tersedia 👍</p>
                      </div>
                      <ul v-else class="divide-y divide-slate-100">
                        <li
                          v-for="s in unavailableServices"
                          :key="s.id"
                          class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-slate-50"
                        >
                          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                            <Ban class="h-4 w-4" />
                          </span>
                          <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ s.product_name }}</p>
                            <p class="font-mono text-xs text-slate-400">{{ s.product_code }}</p>
                          </div>
                          <span class="badge badge-slate">Nonaktif</span>
                        </li>
                      </ul>
                    </template>
                  </div>

                  <button
                    v-if="canAccess(['administrator', 'manager'])"
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

    <!-- Session timeout warning -->
    <transition name="fade">
      <div v-if="showSessionWarning" class="modal-overlay no-print" role="alertdialog" aria-modal="true">
        <div class="modal max-w-md">
          <div class="modal-body text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-warning-100 text-warning-600">
              <Clock class="h-7 w-7" />
            </div>
            <h3 class="text-lg font-bold text-slate-900">Sesi Anda akan segera berakhir</h3>
            <p class="mt-2 text-sm text-slate-500">
              Demi keamanan, sesi akan berakhir dalam
              <span class="font-semibold text-warning-600">{{ formatRemaining(warningSecondsLeft) }}</span>.
              Perpanjang untuk tetap melanjutkan pekerjaan Anda.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="logout">
              <LogOut class="h-4 w-4" /> Keluar
            </button>
            <button type="button" class="btn btn-primary" :disabled="extending" @click="extendSession">
              <RefreshCw :class="['h-4 w-4', extending && 'animate-spin']" />
              {{ extending ? 'Memperpanjang...' : 'Perpanjang sesi' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Account security (2FA) -->
    <transition name="fade">
      <div v-if="showSecurity" class="modal-overlay no-print" role="dialog" aria-modal="true" @click.self="showSecurity = false">
        <div class="modal max-w-lg">
          <div class="modal-header">
            <h3 class="modal-title flex items-center gap-2">
              <ShieldCheck class="h-5 w-5 text-primary-600" /> Keamanan Akun
            </h3>
            <button
              type="button"
              class="btn-icon-sm btn-ghost"
              aria-label="Tutup"
              @click="showSecurity = false"
            >
              <X class="h-5 w-5" />
            </button>
          </div>

          <div class="modal-body space-y-4">
            <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
              <div>
                <p class="text-sm font-semibold text-slate-800">Two-Factor Authentication (2FA)</p>
                <p class="text-xs text-slate-500">Lapisan keamanan tambahan dengan kode dari aplikasi authenticator.</p>
              </div>
              <span :class="['badge', securityLoading ? 'badge-slate' : (twoFactorEnabled ? 'badge-success' : 'badge-slate')]">
                {{ securityLoading ? '...' : (twoFactorEnabled ? 'Aktif' : 'Nonaktif') }}
              </span>
            </div>

            <!-- Idle: choose action -->
            <template v-if="securityStep === 'idle'">
              <p v-if="auth.isAdmin && !twoFactorEnabled" class="flex items-start gap-2 rounded-xl bg-warning-50 px-4 py-3 text-xs text-warning-700">
                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                Role administrator sangat disarankan mengaktifkan 2FA.
              </p>
              <button
                v-if="!twoFactorEnabled"
                type="button"
                class="btn btn-primary w-full"
                @click="startTwoFactorSetup"
              >
                <KeyRound class="h-4 w-4" /> Aktifkan 2FA
              </button>
              <template v-else>
                <label class="form-group block">
                  <span class="mb-2 block text-sm font-semibold text-slate-700">Kode 2FA / Password</span>
                  <input v-model="securityCode" class="input" inputmode="numeric" placeholder="Masukkan kode 6 digit" />
                </label>
                <button type="button" class="btn btn-danger w-full" :disabled="securityProcessing" @click="disableTwoFactor">
                  <ShieldOff class="h-4 w-4" /> Matikan 2FA
                </button>
              </template>
            </template>

            <!-- Setup: show secret + confirm -->
            <template v-else-if="securityStep === 'setup'">
              <p class="text-sm text-slate-600">
                Pindai kode QR di aplikasi authenticator Anda, atau masukkan rahasia berikut secara manual:
              </p>
              <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                <code class="min-w-0 flex-1 truncate font-mono text-sm text-slate-700">{{ setupData.secret }}</code>
                <button type="button" class="btn-icon-sm btn-secondary" aria-label="Salin rahasia" @click="copySecret">
                  <component :is="secretCopied ? Check : Copy" class="h-4 w-4" />
                </button>
              </div>
              <p class="break-all text-[11px] text-slate-400">{{ setupData.otpauth_url }}</p>
              <label class="form-group block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Kode verifikasi</span>
                <input v-model="securityCode" class="input" inputmode="numeric" maxlength="6" placeholder="000000" />
              </label>
              <div class="flex gap-3">
                <button type="button" class="btn btn-secondary flex-1" @click="securityStep = 'idle'">Batal</button>
                <button type="button" class="btn btn-primary flex-1" :disabled="securityProcessing || securityCode.length !== 6" @click="enableTwoFactor">
                  <Loader2 v-if="securityProcessing" class="h-4 w-4 animate-spin" />
                  <ShieldCheck v-else class="h-4 w-4" />
                  Aktifkan
                </button>
              </div>
            </template>

            <p v-if="securityError" class="flex items-center gap-2 rounded-xl bg-danger-50 px-4 py-3 text-sm text-danger-600">
              <AlertCircle class="h-4 w-4 shrink-0" /> {{ securityError }}
            </p>
            <p v-if="securitySuccess" class="flex items-center gap-2 rounded-xl bg-success-50 px-4 py-3 text-sm text-success-600">
              <CheckCircle2 class="h-4 w-4 shrink-0" /> {{ securitySuccess }}
            </p>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useSessionTimeout } from '@/composables/useSessionTimeout'
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
  AlertCircle,
  CheckCircle2,
  Ban,
  Plug,
  ShieldCheck,
  ShieldOff,
  KeyRound,
  Loader2,
  Copy,
  Check,
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
  { to: '/products', label: 'Produk', icon: Package, roles: ['administrator', 'manager'] },
  { to: '/categories', label: 'Kategori', icon: Tag, roles: ['administrator', 'manager'] },
  { to: '/customers', label: 'Pelanggan', icon: Users, roles: ['administrator', 'manager', 'cashier'] },
  { to: '/reports', label: 'Laporan', icon: BarChart3, roles: ['administrator', 'manager'] },
  { to: '/users', label: 'Pengguna', icon: UserCog, roles: ['administrator'] },
  { to: '/audit-logs', label: 'Audit Log', icon: FileText, roles: ['administrator', 'manager'] },
  { to: '/integration', label: 'Integrasi Odoo', icon: Plug, roles: ['administrator', 'manager'] },
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
  'product-stock': 'Kartu Stok',
  categories: 'Kategori',
  customers: 'Pelanggan',
  'customer-history': 'Riwayat Pelanggan',
  reports: 'Laporan',
  users: 'Manajemen Pengguna',
  'audit-logs': 'Audit Log',
  integration: 'Integrasi Odoo',
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

// ---- Low stock & unavailable service notifications ----
const lowStockThreshold = 10
const lowStock = ref([])
const unavailableServices = ref([])
const showLowStock = ref(false)
const loadingNotifications = ref(false)

const notificationCount = computed(() => lowStock.value.length + unavailableServices.value.length)

async function fetchNotifications() {
  loadingNotifications.value = true
  try {
    const [stockRes, serviceRes] = await Promise.all([
      api.get('/products', { params: { low_stock: lowStockThreshold, limit: 100 } }),
      api.get('/products', {
        params: { product_type: 'jasa', service_status: 'tidak_tersedia', limit: 100 }
      })
    ])
    lowStock.value = Array.isArray(stockRes.data.data) ? stockRes.data.data : []
    unavailableServices.value = Array.isArray(serviceRes.data.data) ? serviceRes.data.data : []
  } catch {
    // Keep the bell quiet if the request fails (e.g. offline).
    lowStock.value = []
    unavailableServices.value = []
  } finally {
    loadingNotifications.value = false
  }
}

function toggleLowStock() {
  showLowStock.value = !showLowStock.value
  if (showLowStock.value) fetchNotifications()
}

function goToProducts() {
  showLowStock.value = false
  router.push('/products')
}

// ---- Session timeout warning ----
const { showWarning: showSessionWarning, warningSecondsLeft, extending, extendSession, formatRemaining } =
  useSessionTimeout({ warnBeforeSeconds: 300 })

// ---- Environment / version indicator ----
const appVersion = typeof __APP_VERSION__ !== 'undefined' ? __APP_VERSION__ : '1.0.0'
const envLabel = computed(() =>
  (import.meta.env.VITE_APP_ENV || (import.meta.env.DEV ? 'development' : 'production')).toUpperCase()
)
const showEnvBadge = computed(() =>
  (import.meta.env.VITE_APP_ENV || (import.meta.env.DEV ? 'development' : 'production')) !== 'production'
)

// ---- Account security (2FA) ----
const showSecurity = ref(false)
const securityStep = ref('idle') // idle | setup
const securityLoading = ref(false)
const securityProcessing = ref(false)
const securityCode = ref('')
const securityError = ref('')
const securitySuccess = ref('')
const twoFactorEnabled = ref(false)
const setupData = ref({ secret: '', otpauth_url: '' })
const secretCopied = ref(false)

function resetSecurityState() {
  securityStep.value = 'idle'
  securityCode.value = ''
  securityError.value = ''
  securitySuccess.value = ''
}

async function openSecurity() {
  showSecurity.value = true
  resetSecurityState()
  securityLoading.value = true
  try {
    await auth.fetchMe()
    twoFactorEnabled.value = !!auth.user?.two_factor_enabled
  } catch {
    twoFactorEnabled.value = false
  } finally {
    securityLoading.value = false
  }
}

async function startTwoFactorSetup() {
  securityError.value = ''
  securitySuccess.value = ''
  securityProcessing.value = true
  try {
    const { data } = await api.post('/auth/2fa/setup', {})
    setupData.value = {
      secret: data.data?.secret || '',
      otpauth_url: data.data?.otpauth_url || ''
    }
    securityCode.value = ''
    securityStep.value = 'setup'
  } catch (e) {
    securityError.value = e.response?.data?.message || 'Gagal memulai setup 2FA.'
  } finally {
    securityProcessing.value = false
  }
}

async function enableTwoFactor() {
  securityError.value = ''
  securityProcessing.value = true
  try {
    await api.post('/auth/2fa/enable', { code: securityCode.value })
    twoFactorEnabled.value = true
    securityStep.value = 'idle'
    securityCode.value = ''
    securitySuccess.value = '2FA berhasil diaktifkan.'
    await auth.fetchMe()
  } catch (e) {
    securityError.value = e.response?.data?.message || 'Kode 2FA tidak valid.'
  } finally {
    securityProcessing.value = false
  }
}

async function disableTwoFactor() {
  securityError.value = ''
  if (!securityCode.value) {
    securityError.value = 'Masukkan kode 2FA atau password Anda.'
    return
  }
  securityProcessing.value = true
  try {
    await api.post('/auth/2fa/disable', { code: securityCode.value })
    twoFactorEnabled.value = false
    securityCode.value = ''
    securitySuccess.value = '2FA berhasil dimatikan.'
    await auth.fetchMe()
  } catch (e) {
    securityError.value = e.response?.data?.message || 'Gagal mematikan 2FA.'
  } finally {
    securityProcessing.value = false
  }
}

async function copySecret() {
  try {
    await navigator.clipboard.writeText(setupData.value.secret)
    secretCopied.value = true
    setTimeout(() => (secretCopied.value = false), 1500)
  } catch {
    secretCopied.value = false
  }
}

onMounted(fetchNotifications)
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
