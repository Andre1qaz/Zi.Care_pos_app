import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

/**
 * Memantau masa berlaku JWT dan menampilkan peringatan sebelum sesi habis.
 *
 * - `secondsLeft`  : sisa detik sampai token kedaluwarsa (null bila tak diketahui)
 * - `showWarning`  : true saat sisa waktu <= warnBeforeSeconds
 * - `extendSession`: minta token baru ke backend (memperpanjang sesi)
 */
export function useSessionTimeout({ warnBeforeSeconds = 300 } = {}) {
  const auth = useAuthStore()
  const router = useRouter()

  const now = ref(Date.now())
  const showWarning = ref(false)
  const extending = ref(false)
  const expired = ref(false)
  let timer = null

  const expiresAt = computed(() => auth.tokenExpiresAt)

  const secondsLeft = computed(() => {
    if (!expiresAt.value) return null
    return Math.max(0, Math.floor((expiresAt.value - now.value) / 1000))
  })

  const warningSecondsLeft = computed(() => secondsLeft.value ?? 0)

  function tick() {
    now.value = Date.now()

    if (!auth.isAuthenticated) {
      showWarning.value = false
      return
    }

    const left = secondsLeft.value
    if (left === null) return

    if (left <= 0 && !expired.value) {
      expired.value = true
      showWarning.value = false
      auth.logout()
      router.push('/login?expired=1')
      return
    }

    showWarning.value = left > 0 && left <= warnBeforeSeconds
  }

  async function extendSession() {
    if (extending.value) return
    extending.value = true
    try {
      await auth.refreshSession()
      showWarning.value = false
      now.value = Date.now()
    } finally {
      extending.value = false
    }
  }

  function formatRemaining(totalSeconds) {
    const s = Math.max(0, Math.floor(totalSeconds))
    const minutes = Math.floor(s / 60)
    const seconds = s % 60
    if (minutes <= 0) return `${seconds} detik`
    return `${minutes} menit ${String(seconds).padStart(2, '0')} detik`
  }

  onMounted(() => {
    tick()
    timer = setInterval(tick, 1000)
  })

  onUnmounted(() => {
    if (timer) clearInterval(timer)
    timer = null
  })

  return {
    secondsLeft,
    warningSecondsLeft,
    showWarning,
    extending,
    expired,
    extendSession,
    formatRemaining
  }
}
