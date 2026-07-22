import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { guest: true }
  },
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/views/dashboard/DashboardView.vue'),
        meta: { roles: ['administrator', 'manager'] }
      },
      {
        path: 'products',
        name: 'products',
        component: () => import('@/views/products/ProductListView.vue'),
        meta: { roles: ['administrator'] }
      },
      {
        path: 'categories',
        name: 'categories',
        component: () => import('@/views/categories/CategoryListView.vue'),
        meta: { roles: ['administrator'] }
      },
      {
        path: 'customers',
        name: 'customers',
        component: () => import('@/views/customers/CustomerListView.vue'),
        meta: { roles: ['administrator', 'cashier'] }
      },
      {
        path: 'customers/:id/history',
        name: 'customer-history',
        component: () => import('@/views/customers/CustomerHistoryView.vue'),
        meta: { roles: ['administrator', 'cashier'] }
      },
      {
        path: 'transactions',
        name: 'transactions',
        component: () => import('@/views/transactions/TransactionView.vue'),
        meta: { roles: ['administrator', 'cashier'] }
      },
      {
        path: 'invoices',
        name: 'invoices',
        component: () => import('@/views/invoices/InvoiceListView.vue'),
        meta: { roles: ['administrator', 'manager', 'cashier'] }
      },
      {
        path: 'invoices/:id',
        name: 'invoice-detail',
        component: () => import('@/views/invoices/InvoiceDetailView.vue'),
        meta: { roles: ['administrator', 'manager', 'cashier'] }
      },
      {
        path: 'outstanding-payments',
        name: 'outstanding-payments',
        component: () => import('@/views/outstanding-payments/OutstandingPaymentView.vue'),
        meta: { roles: ['administrator', 'manager', 'cashier'] }
      },
      {
        path: 'reports',
        name: 'reports',
        component: () => import('@/views/reports/ReportView.vue'),
        meta: { roles: ['administrator', 'manager'] }
      },
      {
        path: 'users',
        name: 'users',
        component: () => import('@/views/users/UserListView.vue'),
        meta: { roles: ['administrator'] }
      },
      {
        path: 'audit-logs',
        name: 'audit-logs',
        component: () => import('@/views/audit/AuditLogView.vue'),
        meta: { roles: ['administrator', 'manager'] }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next('/login')
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return next('/')
  }

  if (to.meta.roles && auth.userRole && !to.meta.roles.includes(auth.userRole)) {
    return next('/transactions')
  }

  next()
})

export default router
