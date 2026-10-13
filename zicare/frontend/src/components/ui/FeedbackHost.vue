<template>
  <teleport to="body">
    <!-- Toast stack -->
    <div class="pointer-events-none fixed right-4 top-4 z-[100] flex w-[min(92vw,22rem)] flex-col gap-3 sm:right-6 sm:top-6">
      <transition-group name="toast">
        <div
          v-for="item in feedbackState.toasts"
          :key="item.id"
          class="pointer-events-auto flex items-start gap-3 rounded-3xl border border-white/70 bg-white/95 p-4 shadow-bubble-lg backdrop-blur-xl"
          role="alert"
        >
          <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-full', iconWrapClass(item.type)]">
            <component :is="iconFor(item.type)" class="h-5 w-5" />
          </div>
          <p class="flex-1 pt-1 text-sm font-medium leading-snug text-slate-700">{{ item.message }}</p>
          <button
            type="button"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
            aria-label="Tutup"
            @click="removeToast(item.id)"
          >
            <X class="h-4 w-4" />
          </button>
        </div>
      </transition-group>
    </div>

    <!-- Confirm dialog -->
    <transition name="fade">
      <div v-if="feedbackState.confirm.open" class="modal-overlay" @click.self="resolveConfirmDialog(false)">
        <div class="modal max-w-md" role="alertdialog" aria-modal="true">
          <div class="flex flex-col items-center gap-4 px-6 pb-2 pt-7 text-center">
            <div :class="['flex h-14 w-14 items-center justify-center rounded-full', confirmIconWrap]">
              <HelpCircle class="h-7 w-7" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-slate-900">{{ feedbackState.confirm.title }}</h3>
              <p class="mt-1.5 text-sm leading-relaxed text-slate-500">{{ feedbackState.confirm.message }}</p>
            </div>
          </div>
          <div class="modal-footer mt-4 justify-center border-t-0 bg-transparent">
            <button type="button" class="btn btn-secondary" @click="resolveConfirmDialog(false)">
              {{ feedbackState.confirm.cancelText }}
            </button>
            <button
              type="button"
              :class="['btn', feedbackState.confirm.variant === 'danger' ? 'btn-danger' : 'btn-primary']"
              @click="resolveConfirmDialog(true)"
            >
              {{ feedbackState.confirm.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed } from 'vue'
import { AlertTriangle, CheckCircle2, HelpCircle, Info, X, XCircle } from 'lucide-vue-next'
import { feedbackState, removeToast, resolveConfirmDialog } from '@/composables/feedback'

const icons = {
  success: CheckCircle2,
  error: XCircle,
  warning: AlertTriangle,
  info: Info,
}

const wraps = {
  success: 'bg-success-100 text-success-600',
  error: 'bg-danger-100 text-danger-600',
  warning: 'bg-warning-100 text-warning-600',
  info: 'bg-primary-100 text-primary-600',
}

function iconFor(type) {
  return icons[type] || Info
}

function iconWrapClass(type) {
  return wraps[type] || wraps.info
}

const confirmIconWrap = computed(() =>
  feedbackState.confirm.variant === 'danger' ? 'bg-danger-100 text-danger-600' : 'bg-primary-100 text-primary-600'
)
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(30px) scale(0.96);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(30px) scale(0.96);
}
.toast-move {
  transition: transform 0.35s ease;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
