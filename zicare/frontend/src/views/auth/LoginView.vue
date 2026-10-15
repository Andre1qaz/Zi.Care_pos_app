<template>
  <div
    ref="containerRef"
    class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-950 p-4"
    @mousemove="handleMouseMove"
  >
    <!-- Decorative aurora blobs with parallax -->
    <div
      class="pointer-events-none absolute -left-24 -top-24 h-80 w-80 rounded-full bg-primary-500/30 blur-3xl transition-transform duration-100 ease-out"
      :style="{ transform: `translate(${parallaxX * 20}px, ${parallaxY * 20}px)` }"
    />
    <div
      class="pointer-events-none absolute -bottom-28 right-0 h-96 w-96 rounded-full bg-brand-violet/25 blur-3xl transition-transform duration-100 ease-out"
      :style="{ transform: `translate(${parallaxX * -15}px, ${parallaxY * -15}px)` }"
    />
    <div
      class="pointer-events-none absolute right-1/3 top-1/4 h-72 w-72 animate-float rounded-full bg-brand-sky/20 blur-3xl transition-transform duration-100 ease-out"
      :style="{ transform: `translate(${parallaxX * 10}px, ${parallaxY * 10}px)` }"
    />

    <!-- System Status Indicator -->
    <div class="absolute top-4 right-4 z-10 hidden lg:flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 backdrop-blur-md border border-white/20">
      <span class="relative flex h-2.5 w-2.5">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75" />
        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500" />
      </span>
      <span class="text-xs font-medium text-white/90">Semua Sistem Operasional</span>
    </div>

    <!-- Login Card -->
    <div class="relative grid w-full max-w-[2000px] min-h-[700px] overflow-hidden rounded-xl border border-white/10 bg-slate-900/60 shadow-2xl backdrop-blur-xl lg:grid-cols-2 mt-12 mb-8">
      <!-- Left Panel - Brand -->
      <div class="hidden lg:flex flex-col justify-between bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-700 p-16 text-white">
        <div class="flex items-center gap-4">
          <span class="flex h-16 w-16 items-center justify-center rounded-xl bg-white shadow-lg">
            <img src="@/assets/zicare-logo.jpg" alt="Zi.CARE Logo" class="h-full w-full object-contain p-2" />
          </span>
          <div>
            <p class="text-2xl font-bold">Zi.CARE</p>
            <p class="text-base text-blue-100">Point of Sales</p>
          </div>
        </div>

        <div>
          <h2 class="mb-6 text-5xl font-bold leading-tight">
            Kelola penjualan<br>dengan lebih <span class="text-yellow-300">{{ typedWord }}</span><span class="animate-pulse">|</span>
          </h2>
          <p class="mb-10 text-lg text-blue-100 max-w-md leading-relaxed">
            Platform Point of Sales modern untuk transaksi cepat, invoice otomatis, dan laporan real-time. Tingkatkan efisiensi bisnis Anda sekarang.
          </p>

          <!-- Feature Carousel -->
          <div class="mb-10 overflow-hidden">
            <div
              class="flex transition-transform duration-500 ease-in-out"
              :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
            >
              <div
                v-for="(slide, index) in featureSlides"
                :key="index"
                class="min-w-full space-y-4"
              >
                <div v-for="feature in slide" :key="feature" class="flex items-center gap-4">
                  <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/20">
                    <Check class="h-4 w-4" />
                  </span>
                  <span class="text-lg text-blue-50">{{ feature }}</span>
                </div>
              </div>
            </div>
            <!-- Carousel Indicators -->
            <div class="mt-6 flex justify-center gap-3">
              <button
                v-for="(_, index) in featureSlides"
                :key="index"
                class="h-2.5 w-2.5 rounded-full transition-all"
                :class="currentSlide === index ? 'bg-white w-8' : 'bg-white/40'"
                @click="currentSlide = index"
              />
            </div>
          </div>

          <!-- Running Text Features -->
          <div class="overflow-hidden rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 py-4">
            <div class="whitespace-nowrap animate-marquee-smooth flex gap-10">
              <span class="text-base text-white/90">Transaksi cepat dan akurat</span>
              <span class="text-base text-white/90">Invoice otomatis</span>
              <span class="text-base text-white/90">Laporan real-time</span>
              <span class="text-base text-white/90">Sinkronisasi dengan Odoo</span>
              <span class="text-base text-white/90">Manajemen stok</span>
              <span class="text-base text-white/90">Pelacakan pelanggan</span>
              <span class="text-base text-white/90">Pembayaran parsial</span>
              <span class="text-base text-white/90">Audit log system</span>
              <span class="text-base text-white/90">Dashboard analitik</span>
              <span class="text-base text-white/90">Multi-role access</span>
              <!-- Triple duplicate for seamless loop -->
              <span class="text-base text-white/90">Transaksi cepat dan akurat</span>
              <span class="text-base text-white/90">Invoice otomatis</span>
              <span class="text-base text-white/90">Laporan real-time</span>
              <span class="text-base text-white/90">Sinkronisasi dengan Odoo</span>
              <span class="text-base text-white/90">Manajemen stok</span>
              <span class="text-base text-white/90">Pelacakan pelanggan</span>
              <span class="text-base text-white/90">Pembayaran parsial</span>
              <span class="text-base text-white/90">Audit log system</span>
              <span class="text-base text-white/90">Dashboard analitik</span>
              <span class="text-base text-white/90">Multi-role access</span>
              <span class="text-base text-white/90">Transaksi cepat dan akurat</span>
              <span class="text-base text-white/90">Invoice otomatis</span>
              <span class="text-base text-white/90">Laporan real-time</span>
              <span class="text-base text-white/90">Sinkronisasi dengan Odoo</span>
              <span class="text-base text-white/90">Manajemen stok</span>
              <span class="text-base text-white/90">Pelacakan pelanggan</span>
              <span class="text-base text-white/90">Pembayaran parsial</span>
              <span class="text-base text-white/90">Audit log system</span>
              <span class="text-base text-white/90">Dashboard analitik</span>
              <span class="text-base text-white/90">Multi-role access</span>
            </div>
          </div>
        </div>

        <p class="text-base text-blue-200">© {{ year }} Zi.CARE POS</p>
      </div>

      <!-- Right Panel - Login Form -->
      <div class="bg-slate-800/80 p-12 sm:p-16 lg:p-20 backdrop-blur-sm flex flex-col justify-center">
        <!-- Mobile Logo -->
        <div class="mb-10 text-center lg:hidden">
          <div class="mb-6 inline-flex h-20 w-20 items-center justify-center rounded-xl bg-white shadow-lg">
            <img src="@/assets/zicare-logo.jpg" alt="Zi.CARE Logo" class="h-full w-full object-contain p-2" />
          </div>
          <h1 class="text-3xl font-bold text-white">Zi.CARE POS</h1>
        </div>

        <!-- Desktop Header -->
        <div class="mb-12 hidden lg:block">
          <h1 class="text-4xl font-bold text-white">{{ greeting }}</h1>
          <p class="mt-3 text-lg text-slate-300">Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="handleLogin" class="space-y-8">
          <div class="group">
            <label for="email" class="mb-3 block text-base font-medium text-slate-300 transition-colors group-focus-within:text-blue-400">Email</label>
            <div class="relative">
              <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500 to-purple-500 opacity-0 transition-opacity group-focus-within:opacity-100 blur-sm" />
              <div class="relative flex items-center">
                <div class="absolute left-5 flex items-center justify-center rounded-lg bg-slate-700/50 p-3 transition-colors group-focus-within:bg-blue-600/30 group-focus-within:text-blue-400">
                  <Mail class="h-6 w-6 text-slate-400 transition-colors" />
                </div>
                <input
                  id="email"
                  v-model="email"
                  type="email"
                  required
                  autocomplete="email"
                  @blur="validateEmail"
                  @keyup.enter="focusPassword"
                  :class="[
                    'w-full rounded-xl border-2 bg-slate-700/50 px-14 py-4 text-lg text-white placeholder-slate-400 transition-all duration-300 focus:outline-none focus:ring-0 focus:shadow-lg',
                    emailValid ? 'border-green-500 focus:border-green-500 focus:shadow-green-500/20' : '',
                    emailHasError ? 'border-red-500 focus:border-red-500 focus:shadow-red-500/20 animate-shake' : 'border-slate-600 focus:border-blue-500 focus:shadow-blue-500/20'
                  ]"
                />
              </div>
              <transition name="fade">
                <p v-if="emailError" class="mt-2 text-sm text-red-400">{{ emailError }}</p>
              </transition>
            </div>
          </div>

          <div class="group">
            <label for="password" class="mb-3 block text-base font-medium text-slate-300 transition-colors group-focus-within:text-blue-400">Password</label>
            <div class="relative">
              <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500 to-purple-500 opacity-0 transition-opacity group-focus-within:opacity-100 blur-sm" />
              <div class="relative flex items-center">
                <div class="absolute left-5 flex items-center justify-center rounded-lg bg-slate-700/50 p-3 transition-colors group-focus-within:bg-blue-600/30 group-focus-within:text-blue-400">
                  <Lock class="h-6 w-6 text-slate-400 transition-colors" />
                </div>
                <input
                  id="password"
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  autocomplete="current-password"
                  @blur="validatePassword"
                  @keyup.enter="handleLogin"
                  :class="[
                    'w-full rounded-xl border-2 bg-slate-700/50 px-14 py-4 text-lg text-white placeholder-slate-400 transition-all duration-300 focus:outline-none focus:ring-0 focus:shadow-lg',
                    passwordValid ? 'border-green-500 focus:border-green-500 focus:shadow-green-500/20' : '',
                    passwordHasError ? 'border-red-500 focus:border-red-500 focus:shadow-red-500/20 animate-shake' : 'border-slate-600 focus:border-blue-500 focus:shadow-blue-500/20'
                  ]"
                />
                <button
                  type="button"
                  class="absolute right-5 flex h-10 w-10 items-center justify-center rounded-lg text-slate-400 transition-all duration-300 hover:bg-slate-600/50 hover:text-slate-200 active:scale-95"
                  :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                  @click="showPassword = !showPassword"
                >
                  <component :is="showPassword ? EyeOff : Eye" class="h-6 w-6" />
                </button>
              </div>
              <transition name="fade">
                <p v-if="passwordError" class="mt-2 text-sm text-red-400">{{ passwordError }}</p>
              </transition>
            </div>
          </div>

          <transition name="fade">
            <p
              v-if="error"
              class="flex items-center gap-3 rounded-lg bg-red-500/20 px-5 py-4 text-base font-medium text-red-300 border border-red-500/30"
            >
              <AlertCircle class="h-5 w-5 shrink-0" />
              {{ error }}
            </p>
          </transition>

          <button
            type="submit"
            class="group relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4 text-lg font-medium text-white transition-all duration-300 hover:from-blue-600 hover:to-blue-700 hover:shadow-lg hover:shadow-blue-500/30 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-blue-500 disabled:hover:to-blue-600"
            :disabled="loading"
          >
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer" />
            <span v-if="loading" class="flex items-center justify-center gap-3">
              <svg class="h-6 w-6 animate-spin" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
              Memproses...
            </span>
            <span v-else class="flex items-center justify-center gap-3">
              Masuk
              <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </span>
          </button>
        </form>

        <p class="mt-10 text-center text-base text-slate-400">
          Butuh bantuan? <a href="https://wa.me/6282284630001?text=Halo%20admin%2C%20saya%20butuh%20bantuan%20dengan%20sistem%20POS" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:text-blue-300 font-medium">Hubungi administrator</a>
        </p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { AlertCircle, Check, Eye, EyeOff, Lock, Mail } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const showPassword = ref(false)
const year = new Date().getFullYear()

// Input validation states
const emailValid = ref(false)
const passwordValid = ref(false)
const emailError = ref('')
const passwordError = ref('')
const emailHasError = ref(false)
const passwordHasError = ref(false)

// Mouse parallax
const containerRef = ref(null)
const parallaxX = ref(0)
const parallaxY = ref(0)

function handleMouseMove(e) {
  if (!containerRef.value) return
  const rect = containerRef.value.getBoundingClientRect()
  const centerX = rect.width / 2
  const centerY = rect.height / 2
  parallaxX.value = (e.clientX - rect.left - centerX) / centerX
  parallaxY.value = (e.clientY - rect.top - centerY) / centerY
}

// Typing effect
const typedWords = ['efisien', 'cepat', 'akurat', 'mudah']
const currentWordIndex = ref(0)
const typedWord = ref('')
const isDeleting = ref(false)
const typingSpeed = 100
const deletingSpeed = 50
const pauseDelay = 2000

function typeEffect() {
  const currentWord = typedWords[currentWordIndex.value]

  if (isDeleting.value) {
    typedWord.value = currentWord.substring(0, typedWord.value.length - 1)
    if (typedWord.value === '') {
      isDeleting.value = false
      currentWordIndex.value = (currentWordIndex.value + 1) % typedWords.length
      setTimeout(typeEffect, 500)
    } else {
      setTimeout(typeEffect, deletingSpeed)
    }
  } else {
    typedWord.value = currentWord.substring(0, typedWord.value.length + 1)
    if (typedWord.value === currentWord) {
      isDeleting.value = true
      setTimeout(typeEffect, pauseDelay)
    } else {
      setTimeout(typeEffect, typingSpeed)
    }
  }
}

// Feature carousel
const featureSlides = [
  ['Transaksi cepat & akurat', 'Invoice otomatis'],
  ['Laporan real-time', 'Sinkron dengan Odoo'],
  ['Manajemen stok', 'Pelacakan pelanggan']
]
const currentSlide = ref(0)
let carouselInterval = null

function startCarousel() {
  carouselInterval = setInterval(() => {
    currentSlide.value = (currentSlide.value + 1) % featureSlides.length
  }, 5000)
}

function stopCarousel() {
  if (carouselInterval) {
    clearInterval(carouselInterval)
    carouselInterval = null
  }
}

// Dynamic greeting
const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour >= 5 && hour < 12) {
    return '🌅 Selamat pagi, siap mulai jualan?'
  } else if (hour >= 12 && hour < 15) {
    return '☀️ Selamat siang, semangat berjualan!'
  } else if (hour >= 15 && hour < 18) {
    return '🌤️ Selamat sore, cek laporan hari ini?'
  } else {
    return '🌙 Selamat malam, sampai jumpa besok!'
  }
})

// Validation functions
function validateEmail() {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!email.value) {
    emailError.value = 'Email wajib diisi'
    emailValid.value = false
    emailHasError.value = true
    setTimeout(() => emailHasError.value = false, 500)
    return false
  } else if (!emailRegex.test(email.value)) {
    emailError.value = 'Format email tidak valid'
    emailValid.value = false
    emailHasError.value = true
    setTimeout(() => emailHasError.value = false, 500)
    return false
  } else {
    emailError.value = ''
    emailValid.value = true
    emailHasError.value = false
    return true
  }
}

function validatePassword() {
  if (!password.value) {
    passwordError.value = 'Password wajib diisi'
    passwordValid.value = false
    passwordHasError.value = true
    setTimeout(() => passwordHasError.value = false, 500)
    return false
  } else if (password.value.length < 6) {
    passwordError.value = 'Password minimal 6 karakter'
    passwordValid.value = false
    passwordHasError.value = true
    setTimeout(() => passwordHasError.value = false, 500)
    return false
  } else {
    passwordError.value = ''
    passwordValid.value = true
    passwordHasError.value = false
    return true
  }
}

function focusPassword() {
  const passwordInput = document.getElementById('password')
  if (passwordInput) passwordInput.focus()
}

// Watch for real-time validation
watch(email, () => {
  if (email.value) validateEmail()
})

watch(password, () => {
  if (password.value) validatePassword()
})

// Lifecycle
onMounted(() => {
  typeEffect()
  startCarousel()
  // Auto-focus email field
  setTimeout(() => {
    const emailInput = document.getElementById('email')
    if (emailInput) emailInput.focus()
  }, 100)
})

onUnmounted(() => {
  stopCarousel()
})

async function handleLogin() {
  // Validate before submit
  const isEmailValid = validateEmail()
  const isPasswordValid = validatePassword()

  if (!isEmailValid || !isPasswordValid) {
    return
  }

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

@keyframes marquee {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}

.animate-marquee {
  animation: marquee 40s linear infinite;
}

@keyframes marquee-smooth {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}

.animate-marquee-smooth {
  animation: marquee-smooth 30s linear infinite;
}

@keyframes float {
  0%,
  100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-20px);
  }
}

.animate-float {
  animation: float 6s ease-in-out infinite;
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-shimmer {
  animation: shimmer 2s infinite;
}

@keyframes bubble-text {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.animate-bubble-text {
  animation: bubble-text 3s ease-in-out infinite;
}

.animate-bubble-text span:nth-child(1) {
  animation-delay: 0s;
}

.animate-bubble-text span:nth-child(2) {
  animation-delay: 0.1s;
}

.animate-bubble-text span:nth-child(3) {
  animation-delay: 0.2s;
}

.animate-bubble-text span:nth-child(4) {
  animation-delay: 0.3s;
}

.animate-bubble-text span:nth-child(5) {
  animation-delay: 0.4s;
}

.animate-bubble-text span:nth-child(6) {
  animation-delay: 0.5s;
}

@keyframes bounce-custom {
  0%, 100% {
    transform: translateY(0) scale(1);
  }
  50% {
    transform: translateY(-15px) scale(1.1);
  }
}

.animate-bounce-custom {
  animation: bounce-custom 2s ease-in-out infinite;
}

@keyframes bubble-wave {
  0%, 100% {
    transform: translateX(0);
  }
  50% {
    transform: translateX(10px);
  }
}

.animate-bubble-wave {
  animation: bubble-wave 4s ease-in-out infinite;
}

@keyframes shake {
  0%, 100% {
    transform: translateX(0);
  }
  10%, 30%, 50%, 70%, 90% {
    transform: translateX(-5px);
  }
  20%, 40%, 60%, 80% {
    transform: translateX(5px);
  }
}

.animate-shake {
  animation: shake 0.5s ease-in-out;
}
</style>
