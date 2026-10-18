<template>
  <div
    ref="containerRef"
    class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-950 p-4"
    @mousemove="handleMouseMove"
  >
    <!-- Decorative aurora blobs with parallax (dimatikan saat loading) -->
    <div
      class="pointer-events-none absolute -left-24 -top-24 h-80 w-80 rounded-full bg-primary-500/30 blur-3xl transition-transform duration-100 ease-out"
      :style="blobStyle(20)"
    />
    <div
      class="pointer-events-none absolute -bottom-28 right-0 h-96 w-96 rounded-full bg-brand-violet/25 blur-3xl transition-transform duration-100 ease-out"
      :style="blobStyle(-15)"
    />
    <div
      class="pointer-events-none absolute right-1/3 top-1/4 h-72 w-72 animate-float rounded-full bg-brand-sky/20 blur-3xl transition-transform duration-100 ease-out"
      :style="blobStyle(10)"
    />

    <!-- Environment Indicator -->
    <div
      v-if="showEnvBadge"
      class="absolute left-4 top-4 z-20 flex items-center gap-1.5 rounded-full bg-yellow-500 px-3 py-1 text-xs font-bold text-black shadow-lg"
      role="status"
    >
      <span class="h-1.5 w-1.5 rounded-full bg-black/70" />
      {{ envLabel }}
    </div>

    <!-- System Status Indicator -->
    <div
      class="absolute right-4 top-4 z-10 hidden items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 backdrop-blur-md lg:flex"
      role="status"
    >
      <span class="relative flex h-2.5 w-2.5">
        <span
          class="absolute inline-flex h-full w-full rounded-full opacity-75"
          :class="maintenance ? 'bg-amber-400' : 'animate-ping bg-green-400'"
        />
        <span
          class="relative inline-flex h-2.5 w-2.5 rounded-full"
          :class="maintenance ? 'bg-amber-500' : 'bg-green-500'"
        />
      </span>
      <span class="text-xs font-medium text-white/90">{{ statusLabel }}</span>
    </div>

    <!-- Login Card -->
    <div class="relative mt-6 mb-4 grid w-full max-w-[1120px] min-h-[580px] overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70 shadow-2xl backdrop-blur-xl lg:grid-cols-2">
      <!-- Left Panel - Brand -->
      <div class="hidden flex-col justify-between bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-700 p-12 text-white lg:flex">
        <div class="flex items-center gap-4">
          <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white shadow-lg">
            <img src="@/assets/zicare-logo.jpg" alt="Zi.CARE Logo" class="h-full w-full object-contain p-2" />
          </span>
          <div>
            <p class="text-lg font-semibold">Zi.CARE</p>
            <p class="text-xs text-blue-100">Point of Sales</p>
          </div>
        </div>

        <div>
          <h2 class="mb-4 text-3xl font-semibold leading-tight">
            Kelola penjualan<br>dengan lebih <span class="text-yellow-300">{{ typedWord }}</span><span class="animate-pulse">|</span>
          </h2>
          <p class="mb-6 max-w-md text-sm leading-relaxed text-blue-100">
            Platform Point of Sales modern untuk transaksi cepat, invoice otomatis, dan laporan real-time.
          </p>

          <!-- Feature Carousel -->
          <div class="mb-6 overflow-hidden">
            <div
              class="flex transition-transform duration-500 ease-in-out"
              :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
            >
              <div
                v-for="(slide, index) in featureSlides"
                :key="index"
                class="min-w-full space-y-4"
              >
                <div v-for="feature in slide" :key="feature" class="flex items-center gap-3">
                  <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20">
                    <Check class="h-3.5 w-3.5" />
                  </span>
                  <span class="text-sm text-blue-50">{{ feature }}</span>
                </div>
              </div>
            </div>
            <!-- Carousel Indicators -->
            <div class="mt-4 flex justify-center gap-2">
              <button
                v-for="(_, index) in featureSlides"
                :key="index"
                class="h-2 rounded-full transition-all"
                :class="currentSlide === index ? 'w-6 bg-white' : 'w-2 bg-white/40'"
                :aria-label="`Tampilkan fitur ${index + 1}`"
                @click="currentSlide = index"
              />
            </div>
          </div>

          <!-- Running Text Features -->
          <div class="overflow-hidden rounded-xl border border-white/20 bg-white/10 py-3 backdrop-blur-sm">
            <div class="flex animate-marquee-smooth gap-8 whitespace-nowrap">
              <span v-for="(f, i) in marqueeItems" :key="i" class="text-sm text-white/90">{{ f }}</span>
            </div>
          </div>
        </div>

        <p class="text-base text-blue-200">© {{ year }} Zi.CARE POS</p>
      </div>

      <!-- Right Panel - Auth Form -->
      <div class="relative flex flex-col justify-center bg-slate-800/95 p-5 backdrop-blur-md sm:p-7 lg:p-10">
        <!-- Loading skeleton (async system status) -->
        <div v-if="statusLoading" class="space-y-6" aria-hidden="true">
          <div class="skeleton h-8 w-2/3"></div>
          <div class="skeleton h-5 w-1/2"></div>
          <div class="space-y-2">
            <div class="skeleton h-4 w-20"></div>
            <div class="skeleton h-12 w-full rounded-xl"></div>
          </div>
          <div class="space-y-2">
            <div class="skeleton h-4 w-24"></div>
            <div class="skeleton h-12 w-full rounded-xl"></div>
          </div>
          <div class="skeleton h-12 w-full rounded-xl"></div>
        </div>

        <template v-else>
          <!-- Mobile Logo -->
          <div class="mb-4 text-center lg:hidden">
            <div class="mb-1.5 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white shadow-lg">
              <img src="@/assets/zicare-logo.jpg" alt="Zi.CARE Logo" class="h-full w-full object-contain p-2" />
            </div>
            <h1 class="text-base font-semibold text-white">Zi.CARE POS</h1>
          </div>

          <!-- Maintenance banner -->
          <div
            v-if="maintenance"
            class="mb-5 flex items-start gap-3 rounded-xl border border-amber-500/40 bg-amber-500/15 px-4 py-3 text-sm text-amber-200"
            role="alert"
          >
            <ServerCrash class="mt-0.5 h-5 w-5 shrink-0" />
            <span>Sistem sedang dalam pemeliharaan. Login mungkin akan ditolak sementara.</span>
          </div>

          <transition name="step" mode="out-in">
            <!-- ============================== LOGIN ============================== -->
            <form
              v-if="step === 'login'"
              key="login"
              class="space-y-3"
              @submit.prevent="handleLogin"
              novalidate
            >
              <div class="mb-2.5 hidden lg:block">
                <h1 class="text-2xl font-semibold leading-tight text-white">{{ greeting }}</h1>
                <p class="mt-0.5 text-sm text-slate-300">Masuk ke akun Anda untuk melanjutkan</p>
              </div>

              <!-- Email -->
              <div class="group">
                <label for="email" class="mb-2 block text-sm font-medium text-slate-300 transition-colors group-focus-within:text-blue-400">
                  Email
                </label>
                <div class="relative">
                  <div class="relative flex items-center">
                    <div class="absolute left-3 flex items-center justify-center rounded-lg bg-slate-700/60 p-2 transition-colors group-focus-within:bg-blue-600/30 group-focus-within:text-blue-400">
                      <Mail class="h-5 w-5 text-slate-400 transition-colors" />
                    </div>
                    <input
                      id="email"
                      v-model="email"
                      type="email"
                      required
                      autocomplete="email"
                      aria-label="Alamat email"
                      aria-describedby="email-error"
                      :aria-invalid="emailHasError ? 'true' : 'false'"
                      placeholder="nama@klinik.com"
                      class="min-h-[48px] w-full rounded-xl border bg-slate-700/60 px-11 py-3 text-base text-white placeholder-slate-400 transition-all duration-200 ease-smooth focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:ring-offset-0"
                      :class="[
                        emailValid ? 'border-green-500/80 focus:border-green-500 focus:ring-green-500/30' : '',
                        emailHasError ? 'border-red-500/80 focus:border-red-500 focus:ring-red-500/30' : 'border-slate-600/80 focus:border-blue-500'
                      ]"
                      @blur="validateEmail"
                      @keyup.enter="focusPassword"
                    />
                  </div>
                  <transition name="fade">
                    <p v-if="emailError" id="email-error" class="mt-2 text-sm text-red-400">{{ emailError }}</p>
                  </transition>
                </div>
              </div>

              <!-- Password -->
              <div class="group">
                <label for="password" class="mb-2 block text-sm font-medium text-slate-300 transition-colors group-focus-within:text-blue-400">
                  Password
                </label>
                <div class="relative">
                  <div class="relative flex items-center">
                    <div class="absolute left-3 flex items-center justify-center rounded-lg bg-slate-700/60 p-2 transition-colors group-focus-within:bg-blue-600/30 group-focus-within:text-blue-400">
                      <Lock class="h-5 w-5 text-slate-400 transition-colors" />
                    </div>
                    <input
                      id="password"
                      v-model="password"
                      :type="showPassword ? 'text' : 'password'"
                      required
                      autocomplete="current-password"
                      aria-label="Password"
                      aria-describedby="password-error"
                      :aria-invalid="passwordHasError ? 'true' : 'false'"
                      placeholder="••••••••"
                      class="min-h-[48px] w-full rounded-xl border bg-slate-700/60 px-11 py-3 text-base text-white placeholder-slate-400 transition-all duration-200 ease-smooth focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:ring-offset-0"
                      :class="[
                        passwordValid ? 'border-green-500/80 focus:border-green-500 focus:ring-green-500/30' : '',
                        passwordHasError ? 'border-red-500/80 focus:border-red-500 focus:ring-red-500/30' : 'border-slate-600/80 focus:border-blue-500'
                      ]"
                      @blur="validatePassword"
                      @keyup="checkCapsLock"
                      @keydown="checkCapsLock"
                      @keyup.enter="handleLogin"
                    />
                    <button
                      type="button"
                      class="absolute right-2 flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition-all duration-200 ease-smooth hover:bg-slate-600/60 hover:text-slate-200 active:scale-95"
                      :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                      @click="showPassword = !showPassword"
                    >
                      <component :is="showPassword ? EyeOff : Eye" class="h-5 w-5" />
                    </button>
                  </div>

                  <!-- Caps Lock indicator -->
                  <transition name="fade">
                    <div v-if="capsLockOn" class="mt-2 flex items-center gap-2 text-sm text-yellow-400" role="status">
                      <AlertCircle class="h-4 w-4" />
                      <span>Caps Lock aktif</span>
                    </div>
                  </transition>

                  <transition name="fade">
                    <p v-if="passwordError" id="password-error" class="mt-2 text-sm text-red-400">{{ passwordError }}</p>
                  </transition>

                  <!-- Password strength meter -->
                  <div v-if="password" class="mt-3">
                    <div class="flex h-1.5 w-full overflow-hidden rounded-full bg-slate-700">
                      <div class="h-full rounded-full transition-all duration-300 ease-smooth" :class="[loginStrength.bar, loginStrength.width]" />
                    </div>
                    <p class="mt-1 text-xs" :class="loginStrength.text">{{ loginStrength.label }}</p>
                  </div>
                </div>
              </div>

              <!-- Remember me / Forgot password -->
              <div class="flex items-center justify-between gap-3">
                <label class="flex min-h-[40px] cursor-pointer items-center gap-2">
                  <input
                    v-model="rememberMe"
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-600 bg-slate-700 text-blue-500 focus:ring-2 focus:ring-blue-500/60 focus:ring-offset-0"
                  />
                  <span class="text-sm text-slate-300">Ingat saya</span>
                </label>
                <button
                  type="button"
                  class="min-h-[40px] rounded-lg px-2 text-sm font-medium text-blue-400 transition-colors ease-smooth hover:text-blue-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60"
                  @click="goToForgot"
                >
                  Lupa password?
                </button>
              </div>

              <!-- Error / notice -->
              <transition name="fade">
                <div
                  v-if="error || notice"
                  class="flex items-start gap-3 rounded-lg px-5 py-4 text-base font-medium"
                  :class="notice ? 'border border-sky-500/30 bg-sky-500/15 text-sky-200' : errorBoxClass"
                  role="alert"
                  aria-live="assertive"
                >
                  <component :is="notice ? Info : errorIcon" class="mt-0.5 h-5 w-5 shrink-0" />
                  <span>{{ notice || error }}</span>
                </div>
              </transition>

              <!-- Attempts remaining -->
              <transition name="fade">
                <div
                  v-if="attemptsRemaining !== null && attemptsRemaining < 3"
                  class="flex items-center gap-2 text-sm text-red-400"
                  role="status"
                  aria-live="polite"
                >
                  <AlertCircle class="h-4 w-4" />
                  <span>Percobaan tersisa: {{ attemptsRemaining }}</span>
                </div>
              </transition>

              <button
                type="submit"
                class="group relative min-h-[48px] w-full overflow-hidden rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-3 text-base font-medium text-white transition-all duration-200 ease-smooth hover:from-blue-600 hover:to-blue-700 hover:shadow-lg hover:shadow-blue-500/25 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:from-blue-500 disabled:hover:to-blue-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/70 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-800"
                :disabled="loading"
              >
                <span v-if="loading" class="flex items-center justify-center gap-2">
                  <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                  Memproses...
                </span>
                <span v-else class="flex items-center justify-center gap-2">
                  Masuk
                  <ArrowRight class="h-5 w-5 transition-transform group-hover:translate-x-1" />
                </span>
              </button>
            </form>

            <!-- ============================== 2FA ============================== -->
            <form
              v-else-if="step === '2fa'"
              key="2fa"
              class="space-y-4"
              @submit.prevent="submitOtp"
              novalidate
            >
              <button
                type="button"
                class="flex min-h-[44px] items-center gap-2 rounded-lg px-1 text-sm font-medium text-slate-400 transition-colors ease-smooth hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                @click="backToLogin"
              >
                <ArrowLeft class="h-4 w-4" /> Kembali
              </button>

              <div class="text-center">
                <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-500/20 text-blue-300">
                  <Smartphone class="h-6 w-6" />
                </div>
                <h2 class="text-lg font-semibold text-white">Verifikasi Dua Langkah</h2>
                <p class="mt-2 text-sm text-slate-300">
                  Masukkan kode 6 digit dari aplikasi authenticator Anda.
                </p>
              </div>

              <div>
                <label for="otp" class="mb-2 block text-sm font-medium text-slate-300">Kode 2FA</label>
                <input
                  id="otp"
                  ref="otpInput"
                  v-model="otpCode"
                  type="text"
                  inputmode="numeric"
                  autocomplete="one-time-code"
                  maxlength="6"
                  aria-label="Kode verifikasi dua langkah"
                  placeholder="000000"
                  class="min-h-[52px] w-full rounded-xl border-2 border-slate-600 bg-slate-700/50 px-4 py-4 text-center text-2xl font-bold tracking-[0.5em] text-white placeholder-slate-500 transition-all duration-300 ease-smooth focus:border-blue-500 focus:shadow-lg focus:shadow-blue-500/20 focus:outline-none"
                  @input="otpCode = otpCode.replace(/\D/g, '').slice(0, 6)"
                />
              </div>

              <transition name="fade">
                <p v-if="otpError" class="flex items-center gap-2 text-sm text-red-400" role="alert">
                  <AlertCircle class="h-4 w-4" /> {{ otpError }}
                </p>
              </transition>

              <button
                type="submit"
                class="flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-3 text-base font-medium text-white transition-all duration-200 ease-smooth hover:from-blue-600 hover:to-blue-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="otpLoading || otpCode.length !== 6"
              >
                <Loader2 v-if="otpLoading" class="h-5 w-5 animate-spin" />
                <ShieldCheck v-else class="h-5 w-5" />
                {{ otpLoading ? 'Memverifikasi...' : 'Verifikasi & Masuk' }}
              </button>
            </form>

            <!-- ============================== FORGOT ============================== -->
            <form
              v-else-if="step === 'forgot'"
              key="forgot"
              class="space-y-4"
              @submit.prevent="submitForgot"
              novalidate
            >
              <button
                type="button"
                class="flex min-h-[44px] items-center gap-2 rounded-lg px-1 text-sm font-medium text-slate-400 transition-colors ease-smooth hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                @click="backToLogin"
              >
                <ArrowLeft class="h-4 w-4" /> Kembali
              </button>

              <div>
                <h2 class="text-2xl font-semibold text-white">Lupa Password</h2>
                <p class="mt-2 text-sm text-slate-300">
                  Masukkan email akun Anda. Kami akan membuat tautan untuk mengatur ulang password.
                </p>
              </div>

              <div>
                <label for="reset-email" class="mb-2 block text-sm font-medium text-slate-300">Email</label>
                <div class="relative flex items-center">
                  <div class="absolute left-4 rounded-lg bg-slate-700/50 p-3">
                    <Mail class="h-6 w-6 text-slate-400" />
                  </div>
                  <input
                    id="reset-email"
                    ref="forgotInput"
                    v-model="resetEmail"
                    type="email"
                    required
                    autocomplete="email"
                    aria-label="Alamat email akun"
                    placeholder="nama@klinik.com"
                    class="min-h-[48px] w-full rounded-xl border border-slate-600/80 bg-slate-700/60 px-11 py-3 text-base text-white placeholder-slate-400 transition-all duration-200 ease-smooth focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                  />
                </div>
              </div>

              <transition name="fade">
                <div
                  v-if="forgotError"
                  class="flex items-center gap-3 rounded-xl border border-red-500/30 bg-red-500/20 px-4 py-3 text-sm text-red-300"
                  role="alert"
                >
                  <AlertCircle class="h-5 w-5 shrink-0" /> {{ forgotError }}
                </div>
              </transition>

              <transition name="fade">
                <div
                  v-if="forgotSent"
                  class="space-y-3 rounded-xl border border-green-500/30 bg-green-500/15 px-4 py-3 text-sm text-green-200"
                  role="status"
                >
                  <div class="flex items-center gap-3">
                    <CheckCircle2 class="h-5 w-5 shrink-0" />
                    <span>Jika email terdaftar, tautan reset telah dibuat.</span>
                  </div>
                  <!-- Tanpa server email: tampilkan token di mode pengembangan -->
                  <div v-if="devResetToken" class="rounded-lg bg-slate-900/60 p-3 text-xs text-slate-300">
                    <p class="mb-2 font-semibold text-amber-300">Mode pengembangan — tautan reset:</p>
                    <div class="flex items-center gap-2">
                      <code class="min-w-0 flex-1 truncate rounded bg-slate-950/70 px-2 py-1 font-mono">{{ devResetToken }}</code>
                      <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-700 text-slate-200 transition-colors ease-smooth hover:bg-slate-600"
                        aria-label="Salin token"
                        @click="copyResetToken"
                      >
                        <component :is="copied ? Check : Copy" class="h-4 w-4" />
                      </button>
                    </div>
                    <button
                      type="button"
                      class="mt-3 w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors ease-smooth hover:bg-blue-700"
                      @click="goToReset(devResetToken)"
                    >
                      Lanjut atur ulang password
                    </button>
                  </div>
                </div>
              </transition>

              <button
                v-if="!forgotSent"
                type="submit"
                class="flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-3 text-base font-medium text-white transition-all duration-200 ease-smooth hover:from-blue-600 hover:to-blue-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="forgotLoading"
              >
                <Loader2 v-if="forgotLoading" class="h-5 w-5 animate-spin" />
                <Send v-else class="h-5 w-5" />
                {{ forgotLoading ? 'Mengirim...' : 'Kirim tautan reset' }}
              </button>
            </form>

            <!-- ============================== RESET ============================== -->
            <form
              v-else-if="step === 'reset'"
              key="reset"
              class="space-y-4"
              @submit.prevent="submitReset"
              novalidate
            >
              <button
                type="button"
                class="flex min-h-[44px] items-center gap-2 rounded-lg px-1 text-sm font-medium text-slate-400 transition-colors ease-smooth hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                @click="backToLogin"
              >
                <ArrowLeft class="h-4 w-4" /> Kembali
              </button>

              <div>
                <h2 class="text-2xl font-semibold text-white">Atur Ulang Password</h2>
                <p class="mt-2 text-sm text-slate-300">Buat password baru yang kuat untuk akun Anda.</p>
              </div>

              <div>
                <label for="new-password" class="mb-2 block text-sm font-medium text-slate-300">Password Baru</label>
                <div class="relative flex items-center">
                  <div class="absolute left-4 rounded-lg bg-slate-700/50 p-3">
                    <KeyRound class="h-6 w-6 text-slate-400" />
                  </div>
                  <input
                    id="new-password"
                    ref="resetInput"
                    v-model="newPassword"
                    :type="showNewPassword ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    aria-label="Password baru"
                    placeholder="••••••••"
                    class="min-h-[48px] w-full rounded-xl border border-slate-600/80 bg-slate-700/60 px-11 py-3 text-base text-white placeholder-slate-400 transition-all duration-200 ease-smooth focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                  />
                  <button
                    type="button"
                    class="absolute right-2 flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition-all duration-200 ease-smooth hover:bg-slate-600/60 hover:text-slate-200"
                    :aria-label="showNewPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                    @click="showNewPassword = !showNewPassword"
                  >
                    <component :is="showNewPassword ? EyeOff : Eye" class="h-6 w-6" />
                  </button>
                </div>

                <!-- Password strength meter -->
                <div v-if="newPassword" class="mt-3">
                  <div class="flex h-1.5 w-full overflow-hidden rounded-full bg-slate-700">
                    <div class="h-full rounded-full transition-all duration-300 ease-smooth" :class="[resetStrength.bar, resetStrength.width]" />
                  </div>
                  <p class="mt-1 text-xs" :class="resetStrength.text">{{ resetStrength.label }}</p>
                </div>
              </div>

              <div>
                <label for="confirm-password" class="mb-2 block text-sm font-medium text-slate-300">Konfirmasi Password</label>
                <div class="relative flex items-center">
                  <div class="absolute left-4 rounded-lg bg-slate-700/50 p-3">
                    <Lock class="h-6 w-6 text-slate-400" />
                  </div>
                  <input
                    id="confirm-password"
                    v-model="confirmPassword"
                    :type="showNewPassword ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    aria-label="Konfirmasi password baru"
                    placeholder="••••••••"
                    class="min-h-[48px] w-full rounded-xl border px-11 py-3 text-base text-white placeholder-slate-400 transition-all duration-200 ease-smooth focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                    :class="confirmPassword && confirmPassword !== newPassword
                      ? 'border-red-500/80 focus:border-red-500 focus:ring-red-500/30'
                      : 'border-slate-600/80 focus:border-blue-500'"
                  />
                </div>
                <transition name="fade">
                  <p v-if="confirmPassword && confirmPassword !== newPassword" class="mt-2 text-sm text-red-400">
                    Konfirmasi password tidak cocok
                  </p>
                </transition>
              </div>

              <transition name="fade">
                <div
                  v-if="resetError"
                  class="flex items-center gap-3 rounded-xl border border-red-500/30 bg-red-500/20 px-4 py-3 text-sm text-red-300"
                  role="alert"
                >
                  <AlertCircle class="h-5 w-5 shrink-0" /> {{ resetError }}
                </div>
              </transition>

              <button
                type="submit"
                class="flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-3 text-base font-medium text-white transition-all duration-200 ease-smooth hover:from-blue-600 hover:to-blue-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="resetLoading || !resetFormValid"
              >
                <Loader2 v-if="resetLoading" class="h-5 w-5 animate-spin" />
                <ShieldCheck v-else class="h-5 w-5" />
                {{ resetLoading ? 'Menyimpan...' : 'Simpan password baru' }}
              </button>
            </form>

            <!-- ============================== SUCCESS ============================== -->
            <div v-else-if="step === 'success'" key="success" class="space-y-4 text-center">
              <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-green-500/20">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-green-400 to-emerald-600 shadow-lg shadow-green-500/30 animate-success-pop">
                  <Check class="h-8 w-8 text-white" />
                </div>
              </div>
              <div>
                <h2 class="text-2xl font-semibold text-white">Login Berhasil!</h2>
                <p class="mt-2 text-base text-slate-300">Selamat datang, {{ successInfo?.user?.name || 'Pengguna' }} 👋</p>
              </div>

              <!-- Last login info -->
              <div class="mx-auto max-w-sm rounded-xl border border-white/10 bg-slate-900/60 p-3 text-left text-sm">
                <div class="mb-1 flex items-center gap-2 text-slate-400">
                  <Clock class="h-4 w-4" />
                  <span class="font-semibold uppercase tracking-wide text-xs">Login Terakhir</span>
                </div>
                <template v-if="successInfo?.lastLogin?.first_time">
                  <p class="text-slate-200">Ini login pertama Anda. Selamat bergabung! 🎉</p>
                </template>
                <template v-else>
                  <p class="text-slate-200">{{ formatDateTime(successInfo?.lastLogin?.at) }}</p>
                  <p class="mt-0.5 text-xs text-slate-400">
                    {{ deviceLabel(successInfo?.lastLogin?.user_agent) }}
                    <span v-if="successInfo?.lastLogin?.ip"> • {{ successInfo.lastLogin.ip }}</span>
                  </p>
                </template>
              </div>

              <div class="flex items-center justify-center gap-2 text-slate-400">
                <Loader2 class="h-4 w-4 animate-spin" />
                <span class="text-sm">Mengalihkan ke {{ redirectTargetLabel }}...</span>
              </div>
            </div>
          </transition>
        </template>

        <!-- Footer: version + support -->
        <div class="mt-6 space-y-1.5 text-center">
          <p class="text-sm text-slate-400">
            Butuh bantuan?
            <a
              href="https://wa.me/6282284630001?text=Halo%20admin%2C%20saya%20butuh%20bantuan%20dengan%20sistem%20POS"
              target="_blank"
              rel="noopener noreferrer"
              class="font-medium text-blue-400 hover:text-blue-300"
            >Hubungi administrator</a>
          </p>
          <p class="text-xs text-slate-500">v{{ appVersion }} | © {{ year }} Zi.CARE POS</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import {
  AlertCircle,
  ArrowLeft,
  ArrowRight,
  Check,
  CheckCircle2,
  Clock,
  Copy,
  Eye,
  EyeOff,
  Info,
  KeyRound,
  Loader2,
  Lock,
  Mail,
  Send,
  ServerCrash,
  ShieldCheck,
  Smartphone,
  WifiOff,
} from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const error = ref('')
const notice = ref('')
const errorType = ref('generic')
const loading = ref(false)
const showPassword = ref(false)
const year = new Date().getFullYear()

const step = ref('login') // login | 2fa | forgot | reset | success

const rememberMe = ref(auth.remember)
const capsLockOn = ref(false)
const attemptsRemaining = ref(null)
const successInfo = ref(null)

// Input validation states
const emailValid = ref(false)
const passwordValid = ref(false)
const emailError = ref('')
const passwordError = ref('')
const emailHasError = ref(false)
const passwordHasError = ref(false)

// 2FA
const otpCode = ref('')
const otpLoading = ref(false)
const otpError = ref('')
const otpInput = ref(null)

// Forgot / Reset
const resetEmail = ref('')
const forgotInput = ref(null)
const forgotLoading = ref(false)
const forgotError = ref('')
const forgotSent = ref(false)
const devResetToken = ref('')
const copied = ref(false)
const resetToken = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const showNewPassword = ref(false)
const resetInput = ref(null)
const resetLoading = ref(false)
const resetError = ref('')

// System status (async, drives the loading skeleton + env/version info)
const statusLoading = ref(true)
const systemStatus = ref(null)
const appVersion = ref(typeof __APP_VERSION__ !== 'undefined' ? __APP_VERSION__ : '1.0.0')

const maintenance = computed(() => !!systemStatus.value?.maintenance)
const statusLabel = computed(() => (maintenance.value ? 'Mode Pemeliharaan' : 'Semua Sistem Operasional'))

const envLabel = computed(() => {
  const env = systemStatus.value?.environment || import.meta.env.VITE_APP_ENV || (import.meta.env.DEV ? 'development' : 'production')
  return env.toUpperCase()
})
const showEnvBadge = computed(() => (systemStatus.value?.environment || (import.meta.env.DEV ? 'development' : 'production')) !== 'production')

// Mouse parallax (disabled while loading to save resources)
const containerRef = ref(null)
const parallaxX = ref(0)
const parallaxY = ref(0)
const parallaxEnabled = computed(() => !loading.value && !statusLoading.value && step.value !== 'success')

function blobStyle(factor) {
  if (!parallaxEnabled.value) return { transform: 'translate(0px, 0px)' }
  return { transform: `translate(${parallaxX.value * factor}px, ${parallaxY.value * factor}px)` }
}

function handleMouseMove(e) {
  if (!parallaxEnabled.value || !containerRef.value) return
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

const marqueeItems = [
  'Transaksi cepat dan akurat', 'Invoice otomatis', 'Laporan real-time',
  'Sinkronisasi dengan Odoo', 'Manajemen stok', 'Pelacakan pelanggan',
  'Pembayaran parsial', 'Audit log system', 'Dashboard analitik', 'Multi-role access',
  'Transaksi cepat dan akurat', 'Invoice otomatis', 'Laporan real-time',
  'Sinkronisasi dengan Odoo', 'Manajemen stok', 'Pelacakan pelanggan',
  'Pembayaran parsial', 'Audit log system', 'Dashboard analitik', 'Multi-role access',
  'Transaksi cepat dan akurat', 'Invoice otomatis', 'Laporan real-time',
  'Sinkronisasi dengan Odoo', 'Manajemen stok', 'Pelacakan pelanggan',
  'Pembayaran parsial', 'Audit log system', 'Dashboard analitik', 'Multi-role access'
]

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
  if (hour >= 5 && hour < 12) return '🌅 Selamat pagi, siap mulai jualan?'
  if (hour >= 12 && hour < 15) return '☀️ Selamat siang, semangat berjualan!'
  if (hour >= 15 && hour < 18) return '🌤️ Selamat sore, cek laporan hari ini?'
  return '🌙 Selamat malam, sampai jumpa besok!'
})

const errorIcon = computed(() => {
  if (errorType.value === 'network') return WifiOff
  if (errorType.value === 'maintenance') return ServerCrash
  return AlertCircle
})

const errorBoxClass = computed(() => {
  if (errorType.value === 'locked') return 'border border-amber-500/30 bg-amber-500/15 text-amber-200'
  return 'border border-red-500/30 bg-red-500/20 text-red-300'
})

// ---- Password strength ----
const STRENGTH = [
  { label: 'Sangat lemah', bar: 'bg-danger-500', text: 'text-danger-400', width: 'w-1/4' },
  { label: 'Lemah', bar: 'bg-danger-500', text: 'text-danger-400', width: 'w-1/4' },
  { label: 'Sedang', bar: 'bg-warning-500', text: 'text-warning-300', width: 'w-2/4' },
  { label: 'Kuat', bar: 'bg-success-500', text: 'text-success-300', width: 'w-3/4' },
  { label: 'Sangat kuat', bar: 'bg-success-500', text: 'text-success-300', width: 'w-full' }
]

function scorePassword(pw) {
  if (!pw) return 0
  let score = 0
  if (pw.length >= 8) score++
  if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++
  if (/\d/.test(pw)) score++
  if (/[^A-Za-z0-9]/.test(pw)) score++
  if (pw.length < 8) score = Math.min(score, 1)
  return Math.min(score, 4)
}

const loginStrength = computed(() => STRENGTH[scorePassword(password.value)])
const resetStrength = computed(() => STRENGTH[scorePassword(newPassword.value)])

const resetFormValid = computed(() =>
  newPassword.value.length >= 8 &&
  /[A-Za-z]/.test(newPassword.value) &&
  /\d/.test(newPassword.value) &&
  newPassword.value === confirmPassword.value
)

// ---- Validation ----
function validateEmail() {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!email.value) {
    emailError.value = 'Email wajib diisi'
    emailValid.value = false
    emailHasError.value = true
    setTimeout(() => (emailHasError.value = false), 500)
    return false
  } else if (!emailRegex.test(email.value)) {
    emailError.value = 'Format email tidak valid'
    emailValid.value = false
    emailHasError.value = true
    setTimeout(() => (emailHasError.value = false), 500)
    return false
  }
  emailError.value = ''
  emailValid.value = true
  emailHasError.value = false
  return true
}

function validatePassword() {
  if (!password.value) {
    passwordError.value = 'Password wajib diisi'
    passwordValid.value = false
    passwordHasError.value = true
    setTimeout(() => (passwordHasError.value = false), 500)
    return false
  } else if (password.value.length < 6) {
    passwordError.value = 'Password minimal 6 karakter'
    passwordValid.value = false
    passwordHasError.value = true
    setTimeout(() => (passwordHasError.value = false), 500)
    return false
  }
  passwordError.value = ''
  passwordValid.value = true
  passwordHasError.value = false
  return true
}

function focusPassword() {
  const passwordInput = document.getElementById('password')
  if (passwordInput) passwordInput.focus()
}

function checkCapsLock(event) {
  if (typeof event?.getModifierState === 'function') {
    capsLockOn.value = event.getModifierState('CapsLock')
  }
}

watch(email, () => {
  if (error.value && errorType.value !== 'network' && errorType.value !== 'maintenance') error.value = ''
  if (notice.value) notice.value = ''
  if (email.value) validateEmail()
})

watch(password, () => {
  if (password.value) validatePassword()
})

// ---- Error handling (spesifik per jenis) ----
function applyLoginError(e) {
  attemptsRemaining.value = null
  errorType.value = 'generic'

  if (!e?.response) {
    errorType.value = 'network'
    error.value = 'Tidak bisa terhubung ke server. Periksa koneksi internet atau pastikan backend berjalan.'
    return
  }

  const status = e.response.status
  const data = e.response.data || {}

  if (status === 423) {
    errorType.value = 'locked'
    error.value = data.message || 'Akun terkunci sementara karena terlalu banyak percobaan gagal.'
  } else if (status === 401) {
    error.value = data.message || 'Email atau password salah.'
    if (data.errors?.attempts_remaining !== undefined) {
      attemptsRemaining.value = data.errors.attempts_remaining
    }
  } else if (status === 503) {
    errorType.value = 'maintenance'
    error.value = data.message || 'Server sedang dalam pemeliharaan. Coba lagi nanti.'
  } else if (status === 429) {
    error.value = data.message || 'Terlalu banyak percobaan. Coba lagi nanti.'
  } else if (status === 422) {
    error.value = data.message || 'Data yang dimasukkan tidak valid.'
  } else if (status >= 500) {
    error.value = 'Terjadi gangguan pada server. Silakan coba beberapa saat lagi.'
  } else {
    error.value = data.message || 'Login gagal.'
  }
}

// ---- Mobile keyboard auto-hide ----
function blurActive() {
  if (typeof document !== 'undefined' && document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
}

function onAuthSuccess() {
  successInfo.value = { user: auth.user, lastLogin: auth.lastLogin }
  step.value = 'success'

  const target = auth.userRole === 'cashier' ? '/transactions' : '/dashboard'
  setTimeout(() => router.push(target), 1900)
}

const redirectTargetLabel = computed(() => (auth.userRole === 'cashier' ? 'halaman transaksi' : 'dashboard'))

async function handleLogin() {
  error.value = ''
  notice.value = ''
  attemptsRemaining.value = null

  const isEmailValid = validateEmail()
  const isPasswordValid = validatePassword()
  if (!isEmailValid || !isPasswordValid) return

  blurActive()
  loading.value = true
  try {
    const payload = await auth.login(email.value, password.value, rememberMe.value)
    if (payload?.requires_2fa) {
      otpCode.value = ''
      otpError.value = ''
      step.value = '2fa'
      await nextTick()
      otpInput.value?.focus()
      return
    }
    onAuthSuccess()
  } catch (e) {
    applyLoginError(e)
  } finally {
    loading.value = false
  }
}

async function submitOtp() {
  otpError.value = ''
  if (otpCode.value.length !== 6) return
  blurActive()
  otpLoading.value = true
  try {
    await auth.verifyTwoFactor(otpCode.value)
    onAuthSuccess()
  } catch (e) {
    if (!e?.response) {
      otpError.value = 'Tidak bisa terhubung ke server.'
    } else if (e.response.status === 401) {
      otpError.value = e.response.data?.message || 'Kode 2FA salah atau kedaluwarsa.'
    } else {
      otpError.value = e.response.data?.message || 'Verifikasi gagal.'
    }
    otpCode.value = ''
    await nextTick()
    otpInput.value?.focus()
  } finally {
    otpLoading.value = false
  }
}

function goToForgot() {
  forgotError.value = ''
  forgotSent.value = false
  devResetToken.value = ''
  resetEmail.value = email.value
  step.value = 'forgot'
  nextTick(() => forgotInput.value?.focus())
}

function goToReset(token) {
  resetToken.value = token || resetToken.value
  newPassword.value = ''
  confirmPassword.value = ''
  resetError.value = ''
  step.value = 'reset'
  nextTick(() => resetInput.value?.focus())
}

function backToLogin() {
  step.value = 'login'
  error.value = ''
  otpError.value = ''
  forgotError.value = ''
  resetError.value = ''
  nextTick(() => {
    const el = document.getElementById('email')
    if (el) el.focus()
  })
}

async function submitForgot() {
  forgotError.value = ''
  if (!resetEmail.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(resetEmail.value)) {
    forgotError.value = 'Masukkan email yang valid.'
    return
  }
  blurActive()
  forgotLoading.value = true
  try {
    const { data } = await api.post('/auth/forgot-password', { email: resetEmail.value })
    forgotSent.value = true
    devResetToken.value = data.data?.reset_token || ''
  } catch (e) {
    if (!e?.response) {
      forgotError.value = 'Tidak bisa terhubung ke server.'
    } else {
      forgotError.value = e.response.data?.message || 'Gagal memproses permintaan.'
    }
  } finally {
    forgotLoading.value = false
  }
}

async function submitReset() {
  resetError.value = ''
  if (!resetFormValid.value) {
    resetError.value = 'Pastikan password minimal 8 karakter, memuat huruf & angka, dan konfirmasi cocok.'
    return
  }
  blurActive()
  resetLoading.value = true
  try {
    await api.post('/auth/reset-password', { token: resetToken.value, password: newPassword.value })
    step.value = 'login'
    error.value = ''
    notice.value = 'Password berhasil diubah. Silakan masuk dengan password baru Anda.'
    password.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
  } catch (e) {
    if (!e?.response) {
      resetError.value = 'Tidak bisa terhubung ke server.'
    } else {
      resetError.value = e.response.data?.message || 'Gagal mengatur ulang password.'
    }
  } finally {
    resetLoading.value = false
  }
}

async function copyResetToken() {
  try {
    await navigator.clipboard.writeText(devResetToken.value)
    copied.value = true
    setTimeout(() => (copied.value = false), 1500)
  } catch {
    copied.value = false
  }
}

function formatDateTime(value) {
  if (!value) return '-'
  const date = new Date(String(value).replace(' ', 'T'))
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString('id-ID', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

function deviceLabel(userAgent) {
  if (!userAgent) return 'Perangkat tidak dikenal'
  const ua = userAgent.toLowerCase()
  let os = 'Perangkat tidak dikenal'
  if (ua.includes('windows')) os = 'Windows'
  else if (ua.includes('android')) os = 'Android'
  else if (ua.includes('iphone') || ua.includes('ipad')) os = 'iOS'
  else if (ua.includes('mac os')) os = 'macOS'
  else if (ua.includes('linux')) os = 'Linux'

  let browser = ''
  if (ua.includes('edg')) browser = 'Edge'
  else if (ua.includes('chrome')) browser = 'Chrome'
  else if (ua.includes('firefox')) browser = 'Firefox'
  else if (ua.includes('safari')) browser = 'Safari'

  return browser ? `${browser} • ${os}` : os
}

async function fetchSystemStatus() {
  try {
    const { data } = await api.get('/auth/status')
    systemStatus.value = data.data || null
    if (data.data?.version) appVersion.value = data.data.version
  } catch {
    systemStatus.value = null
  } finally {
    statusLoading.value = false
  }
}

// Lifecycle
onMounted(async () => {
  typeEffect()
  startCarousel()

  if (route.query.expired) {
    notice.value = 'Sesi Anda telah berakhir. Silakan masuk kembali.'
  }
  if (route.query.reset_token) {
    resetToken.value = String(route.query.reset_token)
  }

  await fetchSystemStatus()

  if (resetToken.value) {
    goToReset(resetToken.value)
  } else {
    setTimeout(() => {
      const emailInput = document.getElementById('email')
      if (emailInput) emailInput.focus()
    }, 100)
  }
})

onUnmounted(() => {
  stopCarousel()
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Smooth step transitions between auth states */
.step-enter-active,
.step-leave-active {
  transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1), transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}
.step-enter-from {
  opacity: 0;
  transform: translateY(12px);
}
.step-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.ease-smooth {
  transition-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
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
  animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}

@keyframes success-pop {
  0% {
    transform: scale(0.4);
    opacity: 0;
  }
  60% {
    transform: scale(1.15);
    opacity: 1;
  }
  100% {
    transform: scale(1);
  }
}

.animate-success-pop {
  animation: success-pop 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
}

/* Skeleton shimmer for the loading state */
.skeleton {
  position: relative;
  overflow: hidden;
  border-radius: 0.75rem;
  background: rgba(148, 163, 184, 0.18);
}
.skeleton::after {
  content: '';
  position: absolute;
  inset: 0;
  transform: translateX(-100%);
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.12), transparent);
  animation: shimmer 1.6s infinite;
}

/* Focus indicator konsisten untuk navigasi keyboard */
:deep(input:focus-visible),
:deep(button:focus-visible) {
  outline: none;
}
</style>
