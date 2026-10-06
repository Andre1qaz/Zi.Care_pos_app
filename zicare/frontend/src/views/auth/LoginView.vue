<template>
  <div class="login-page">
    <div class="login-card card">
      <div class="login-header">
        <img src="@/assets/zicare-logo.jpg" alt="Zi.CARE Logo" class="logo-image" />
        <h1>Zi.CARE POS</h1>
        <p class="subtitle">Masuk ke sistem Point of Sales</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label>Email</label>
          <div class="input-with-icon">
            <span class="input-icon">📧</span>
            <input v-model="email" type="email" required placeholder="admin@pos.local" />
          </div>
        </div>
        <div class="form-group">
          <label>Password</label>
          <div class="input-with-icon">
            <span class="input-icon">🔒</span>
            <input v-model="password" type="password" required placeholder="Admin@123" />
          </div>
        </div>
        <p v-if="error" class="error">{{ error }}</p>
        <button type="submit" class="btn btn-primary login-btn" :disabled="loading">
          {{ loading ? 'Memproses...' : 'Login' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

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
      error.value = 'Tidak bisa terhubung ke server. Pastikan backend berjalan di port 8080.'
    } else {
      error.value = 'Login gagal'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  padding: 1rem;
}

.login-card {
  width: 100%;
  max-width: 420px;
  box-shadow: var(--shadow-md);
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.logo-image {
  height: 80px;
  width: auto;
  margin-bottom: 0.5rem;
  object-fit: contain;
}

.login-card h1 {
  text-align: center;
  margin-bottom: 0.25rem;
  font-size: 1.75rem;
  font-weight: 700;
}

.subtitle {
  text-align: center;
  color: var(--secondary);
  margin-bottom: 0;
  font-size: 0.875rem;
}

.input-with-icon {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1rem;
}

.input-with-icon input {
  padding-left: 2.75rem !important;
}

.login-btn {
  width: 100%;
  padding: 0.875rem;
  margin-top: 1rem;
  font-size: 1rem;
  font-weight: 600;
}

.error {
  color: var(--danger);
  font-size: 0.875rem;
  margin-bottom: 0.75rem;
  text-align: center;
  background: #fef2f2;
  padding: 0.5rem;
  border-radius: var(--radius-sm);
}
</style>
