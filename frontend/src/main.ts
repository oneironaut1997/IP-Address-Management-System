/**
 * Main Application Entry Point
 *
 * Initializes the Vue 3 application with Pinia, Vue Router,
 * and Vue Toastification for notifications.
 *
 * @package Main
 */

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Toast, { type PluginOptions, POSITION } from 'vue-toastification'

import App from './App.vue'
import router from './router'

// Import styles
import './assets/index.css'
import 'vue-toastification/dist/index.css'

/**
 * Toast Notification Options
 */
const toastOptions: PluginOptions = {
  position: POSITION.TOP_RIGHT,
  timeout: 5000,
  closeOnClick: true,
  pauseOnFocusLoss: true,
  pauseOnHover: true,
  draggable: true,
  draggablePercent: 0.6,
  showCloseButtonOnHover: false,
  hideProgressBar: false,
  closeButton: 'button',
  icon: true,
  rtl: false,
}

/**
 * Create Vue Application
 */
const app = createApp(App)

/**
 * Use Plugins
 */
app.use(createPinia())
app.use(router)
app.use(Toast, toastOptions)

/**
 * Mount Application
 */
app.mount('#app')
