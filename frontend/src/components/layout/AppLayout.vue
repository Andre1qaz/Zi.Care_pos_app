<template>
  <div class="flex min-h-screen bg-slate-50">
    <aside class="no-print fixed left-0 top-0 h-screen w-64 bg-gradient-to-b from-slate-900 to-slate-950 text-white flex flex-col shadow-xl z-40">
      <div class="border-b border-slate-700/50 bg-white/5 p-6">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600">
            <span class="text-lg font-bold">🏪</span>
          </div>
          <h2 class="text-xl font-bold tracking-tight">POS System</h2>
        </div>
      </div>
      
      <nav class="flex-1 overflow-y-auto p-4">
        <router-link
          v-if="canAccess(['administrator', 'manager'])"
          to="/dashboard"
          class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-300 transition-all hover:bg-white/5 hover:text-white"
          active-class="bg-primary-600 text-white shadow-lg"
        >
          <LayoutDashboard class="h-5 w-5" />
          Dashboard
        </router-link>
        
        <router-link
          v-if="canAccess(['administrator', 'cashier'])"
          to="/transactions"
          class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-300 transition-all hover:bg-white/5 hover:text-white"
          active-class="bg-primary-600 text-white shadow-lg"
        >
          <ShoppingCart class="h-5 w-5" />
          Transaksi
        </router-link>
        
        <router-link
          v-if="canAccess(['administrator', 'manager', 'cashier'])"
          to="/invoices"
          class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-300 transition-all hover:bg-white/5 hover:text-white"
          active-class="bg-primary-600 text-white shadow-lg"
        >
          <Receipt class="h-5 w-5" />
          Invoice
        </router-link>
        
        <router-link
          v-if="canAccess(['administrator', 'manager', 'cashier'])"
          to="/outstanding-payments"
          class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-300 transition-all hover:bg-white/5 hover:text-white"
          active-class="bg-primary-600 text-white shadow-lg"
        >
          <Clock class="h-5 w-5" />
          Pembayaran Tertunda
        </router-link>
        
        <router-link
          v-if="canAccess(['administrator'])"
          to="/products"
          class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-300 transition-all hover:bg-white/5 hover:text-white"
          active-class="bg-primary-600 text-white shadow-lg"
        >
          <Package class="h-5 w-5" />
          Produk
        </router-link>
        
        <router-link
          v-if="canAccess(['administrator'])"
          to="/categories"
          class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-300 transition-all hover:bg-white/5 hover:text-white"
          active-class="bg-primary-600 text-white shadow-lg"
        >
          <Tag class="h-5 w-5" />
          Kategori
        </router-link>
        
        <router-link
          v-if="canAccess(['administrator', 'cashier'])"
          to="/customers"
          class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-300 transition-all hover:bg-white/5 hover:text-white"
          active-class="bg-primary-600 text-white shadow-lg"
        >
          <Users class="h-5 w-5" />
          Pelanggan
        </router-link>
        
        <router-link
          v-if="canAccess(['administrator', 'manager'])"
          to="/reports"
          class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-300 transition-all hover:bg-white/5 hover:text-white"
          active-class="bg-primary-600 text-white shadow-lg"
        >
          <BarChart3 class="h-5 w-5" />
          Laporan
        </router-link>
        
        <router-link
          v-if="canAccess(['administrator'])"
          to="/users"
          class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-300 transition-all hover:bg-white/5 hover:text-white"
          active-class="bg-primary-600 text-white shadow-lg"
        >
          <UserCog class="h-5 w-5" />
          Pengguna
        </router-link>
        
        <router-link
          v-if="canAccess(['administrator', 'manager'])"
          to="/audit-logs"
          class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-300 transition-all hover:bg-white/5 hover:text-white"
          active-class="bg-primary-600 text-white shadow-lg"
        >
          <FileText class="h-5 w-5" />
          Audit Log
        </router-link>
      </nav>
      
      <div class="border-t border-slate-700/50 bg-black/20 p-4">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-600 font-bold text-sm">
            {{ getInitials(auth.user?.name) }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="truncate text-sm font-semibold">{{ auth.user?.name }}</p>
            <p class="truncate text-xs text-slate-400 capitalize">{{ auth.user?.role }}</p>
          </div>
          <button
            @click="logout"
            class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-800 text-slate-400 transition-all hover:bg-danger-600 hover:text-white"
            title="Logout"
          >
            <LogOut class="h-4 w-4" />
          </button>
        </div>
      </div>
    </aside>
    
    <!-- PERBAIKAN DI SINI: Pembatas Lebar Maksimal Konten -->
    <main class="ml-64 flex-1 p-8">
      <div class="mx-auto max-w-7xl">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
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
  Clock
} from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

function canAccess(roles) {
  return roles.includes(auth.userRole)
}

function getInitials(name) {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function logout() {
  auth.logout()
  router.push('/login')
}
</script>