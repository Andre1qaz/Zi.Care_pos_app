import { reactive } from 'vue'

/**
 * Tiny global feedback store.
 *
 * - `toast.success/error/info/warning(message)` shows a floating notification.
 * - `confirmDialog(message, options)` returns a Promise<boolean> for a styled
 *   confirmation dialog (drop-in replacement for window.confirm).
 *
 * Rendered by src/components/ui/FeedbackHost.vue which is mounted once in App.vue.
 */
let seq = 0

export const feedbackState = reactive({
  toasts: [],
  confirm: {
    open: false,
    title: 'Konfirmasi',
    message: '',
    confirmText: 'Ya, Lanjutkan',
    cancelText: 'Batal',
    variant: 'danger',
  },
})

let resolveConfirm = null

function push(type, message, duration) {
  if (!message) return null
  const id = ++seq
  feedbackState.toasts.push({ id, type, message })
  if (duration > 0) {
    setTimeout(() => removeToast(id), duration)
  }
  return id
}

export function removeToast(id) {
  const index = feedbackState.toasts.findIndex((t) => t.id === id)
  if (index !== -1) feedbackState.toasts.splice(index, 1)
}

export const toast = {
  success: (message, duration = 3500) => push('success', message, duration),
  error: (message, duration = 5000) => push('error', message, duration),
  info: (message, duration = 3500) => push('info', message, duration),
  warning: (message, duration = 4000) => push('warning', message, duration),
}

export function confirmDialog(message, options = {}) {
  feedbackState.confirm = {
    open: true,
    title: options.title || 'Konfirmasi',
    message,
    confirmText: options.confirmText || 'Ya, Lanjutkan',
    cancelText: options.cancelText || 'Batal',
    variant: options.variant || 'danger',
  }

  return new Promise((resolve) => {
    resolveConfirm = resolve
  })
}

export function resolveConfirmDialog(result) {
  feedbackState.confirm.open = false
  if (resolveConfirm) {
    resolveConfirm(result)
    resolveConfirm = null
  }
}
