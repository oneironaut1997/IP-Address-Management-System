/**
 * Router Configuration
 *
 * Vue Router setup with route definitions and navigation guards
 * for authentication and authorization.
 *
 * @package Router
 */

import { createRouter, createWebHistory } from 'vue-router'
import { isAuthenticated } from '@/api/client'

// Import views
import AuthLayout from '@/layouts/AuthLayout.vue'
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'

/**
 * Route Record Types
 */
export interface RouteMeta {
  /** Requires authentication to access */
  requiresAuth?: boolean
  /** Only accessible to guests (not authenticated) */
  guest?: boolean
  /** Requires super_admin role */
  requiresSuperAdmin?: boolean
}

/**
 * Route Definitions
 */
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // Auth Routes (Guest Only)
    {
      path: '/',
      redirect: '/login',
    },
    {
      path: '/login',
      component: AuthLayout,
      meta: { guest: true } as RouteMeta,
      children: [
        {
          path: '',
          name: 'login',
          component: LoginView,
        },
      ],
    },
    {
      path: '/register',
      component: AuthLayout,
      meta: { guest: true } as RouteMeta,
      children: [
        {
          path: '',
          name: 'register',
          component: RegisterView,
        },
      ],
    },

    // Protected Routes
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/dashboard/DashboardView.vue'),
      meta: { requiresAuth: true } as RouteMeta,
    },

    // IP Management Routes
    {
      path: '/ip',
      name: 'ip-list',
      component: () => import('@/views/ip/IPListView.vue'),
      meta: { requiresAuth: true } as RouteMeta,
    },
    {
      path: '/ip/:id',
      name: 'ip-detail',
      component: () => import('@/views/ip/IPDetailView.vue'),
      meta: { requiresAuth: true } as RouteMeta,
    },
    {
      path: '/ip/:id/history',
      name: 'ip-history',
      component: () => import('@/views/ip/IPHistoryView.vue'),
      meta: { requiresAuth: true } as RouteMeta,
    },

    // Audit Routes (Super Admin Only)
    {
      path: '/audit',
      name: 'audit-dashboard',
      component: () => import('@/views/audit/AuditDashboardView.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true } as RouteMeta,
    },

    // 404 Not Found
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
    },
  ],
})

/**
 * Navigation Guard
 *
 * Handles route access control:
 * - Redirects to login if accessing protected route without authentication
 * - Redirects to dashboard if accessing guest route while authenticated
 */
router.beforeEach((to, from, next) => {
  const authenticated = isAuthenticated()
  const meta = to.meta as RouteMeta

  // Check if route requires authentication
  if (meta.requiresAuth && !authenticated) {
    next({ name: 'login' })
    return
  }

  // Check if route is guest-only
  if (meta.guest && authenticated) {
    next({ name: 'dashboard' })
    return
  }

  // Allow navigation
  next()
})

export default router
