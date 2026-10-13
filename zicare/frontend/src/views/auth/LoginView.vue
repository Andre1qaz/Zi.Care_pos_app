<template>
  <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-950 p-4">
    <!-- Decorative aurora blobs -->
    <div class="pointer-events-none absolute -left-24 -top-24 h-80 w-80 rounded-full bg-primary-500/30 blur-3xl" />
    <div class="pointer-events-none absolute -bottom-28 right-0 h-96 w-96 rounded-full bg-brand-violet/25 blur-3xl" />
    <div class="pointer-events-none absolute right-1/3 top-1/4 h-72 w-72 animate-float rounded-full bg-brand-sky/20 blur-3xl" />

    <div
      class="relative grid w-full max-w-5xl overflow-hidden rounded-[2.5rem] border border-white/10 bg-white/5 shadow-bubble-lg backdrop-blur-xl lg:grid-cols-2"
    >
      <!-- Brand panel -->
      <div class="relative hidden flex-col justify-between bg-brand-gradient p-10 text-white lg:flex">
        <div class="flex items-center gap-4">
          <span class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-bubble-md ring-4 ring-white/20">
            <img src="@/assets/zicare-logo.jpg" alt="Zi.CARE Logo" class="h-full w-full object-contain p-2" />
          </span>
          <div class="leading-tight">
            <p class="text-xl font-extrabold tracking-tight">Zi.CARE</p>
            <p class="text-xs font-medium text-white/70">Point of Sales</p>
          </div>
        </div>

        <div>
          <h2 class="text-3xl font-extrabold leading-tight">
            Kelola penjualan<br />lebih mudah &amp; cepat.
          </h2>
          <p class="mt-3 max-w-xs text-sm text-white/80">
            Satu aplikasi untuk transaksi, invoice, pelanggan, laporan, dan sinkronisasi ke Odoo.
          </p>

          <ul class="mt-8 space-y-3 text-sm">
            <li v-for="feature in features" :key="feature" class="flex items-center gap-3">
              <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15">
                <Check class="h-4 w-4" />
              </span>
              {{ feature }}
            </li>
          </ul>
        </div>

        <p class="text-xs text-white/60">© {{ year }} Zi.CARE POS. Semua hak dilindungi.</p>
      </div>

      <!-- Form panel -->
      <div class="bg-white p-8 sm:p-10 lg:p-12">
        <div class="mb-8 flex flex-col items-center text-center lg:hidden">
          <span class="mb-4 flex h-20 w-20 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-bubble-md ring-4 ring-white/20">
            <img src="@/assets/zicare-logo.jpg" alt="Zi.CARE Logo" class="h-full w-full object-contain p-2.5" />
          </span>
          <h1 class="text-2xl font-extrabold text-slate-900">Zi.CARE POS</h1>
          <p class="mt-1 text-sm text-slate-500">Masuk ke sistem Point of Sales</p>
        </div>

        <div class="mb-8 hidden lg:block">
          <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Selamat datang kembali 👋</h1>
          <p class="mt-1.5 text-sm text-slate-500">Silakan masuk untuk melanjutkan.</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-5">
          <div class="form-group mb-0">
            <label for="email">Email</label>
            <div class="relative">
              <Mail class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
              <input
                id="email"
                v-model="email"
                type="email"
                required
                autocomplete="email"
                placeholder="admin@pos.local"
                class="input pl-12"
              />
            </div>
          </div>

          <div class="form-group mb-0">
            <label for="password">Password</label>
            <div class="relative">
              <Lock class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                class="input pl-12 pr-12"
              />
              <button
                type="button"
                class="absolute right-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                @click="showPassword = !showPassword"
              >
                <component :is="showPassword ? EyeOff : Eye" class="h-4 w-4" />
              </button>
            </div>
          </div>

          <transition name="fade">
            <p
              v-if="error"
              class="flex items-center gap-2 rounded-2xl bg-danger-50 px-4 py-3 text-sm font-medium text-danger-700 ring-1 ring-inset ring-danger-100"
            >
              <AlertCircle class="h-4 w-4 shrink-0" />
              {{ error }}
            </p>
          </transition>

          <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="loading">
            <span v-if="loading" class="spinner !border-white/40 !border-t-white" />
            <template v-else>
              Masuk
              <ArrowRight class="h-4 w-4" />
            </template>
          </button>
        </form>

        <p class="mt-8 text-center text-xs text-slate-400">
          Butuh bantuan? Hubungi administrator sistem.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { AlertCircle, ArrowRight, Check, Eye, EyeOff, Lock, Mail } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const showPassword = ref(false)
const year = new Date().getFullYear()

const features = ['Transaksi cepat & akurat', 'Invoice otomatis', 'Laporan real-time', 'Sinkron dengan Odoo']

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    const role = auth.userRole
    if (role === 'cashier') {
      router.push('/transactions')
    } else {
      router.push('/dashboard')
    }
  } catch (e) {
    if (e.response?.data?.message) {
      error.value = e.response.data.message
    } else if (e.code === 'ERR_NETWORK') {
      error.value = 'Tidak bisa terhubung ke server. Pastikan backend sudah berjalan.'
    } else {
      error.value = 'Login gagal'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
