<script>
export default { name: 'AppLayout' }
</script>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const page   = usePage()
const drawer = ref(true)
const rail   = ref(false)

const user  = computed(() => page.props.auth?.user)
const flash = computed(() => page.props.flash)

// ─── Flash notifications ──────────────────────────────────
const snackbar        = ref(false)
const snackbarMessage = ref('')
const snackbarColor   = ref('success')

watch(flash, (f) => {
  if (f?.success) {
    snackbarMessage.value = f.success
    snackbarColor.value   = 'success'
    snackbar.value        = true
  } else if (f?.error) {
    snackbarMessage.value = f.error
    snackbarColor.value   = 'error'
    snackbar.value        = true
  }
}, { immediate: true, deep: true })

// ─── Navegación principal ─────────────────────────────────
const navGroups = [
  {
    heading: null,
    items: [
      { title: 'Dashboard',        icon: 'mdi-view-dashboard',       href: '/dashboard' },
    ],
  },
  {
    heading: 'Operaciones',
    items: [
      { title: 'Requerimientos',   icon: 'mdi-clipboard-list-outline', href: '/requirements' },
      { title: 'Cotizaciones',     icon: 'mdi-file-compare',          href: '/quotes' },
      { title: 'Órdenes de Compra',icon: 'mdi-cart-outline',          href: '/purchase-orders' },
    ],
  },
  {
    heading: 'Aprobaciones',
    items: [
      { title: 'Pendientes',       icon: 'mdi-clock-check-outline',   href: '/approvals' },
    ],
  },
  {
    heading: 'Finanzas',
    items: [
      { title: 'Facturas',         icon: 'mdi-file-document-outline', href: '/invoices' },
      { title: 'Pagos',            icon: 'mdi-bank-transfer',         href: '/payments' },
    ],
  },
  {
    heading: 'Almacén',
    items: [
      { title: 'Recepciones',      icon: 'mdi-warehouse',             href: '/receptions' },
      { title: 'Stock',            icon: 'mdi-package-variant-closed', href: '/stock' },
      { title: 'Traslados',        icon: 'mdi-transfer',              href: '/transfers' },
      { title: 'Entregas',         icon: 'mdi-truck-delivery-outline', href: '/deliveries' },
      { title: 'Kardex',           icon: 'mdi-book-open-outline',     href: '/kardex' },
    ],
  },
  {
    heading: 'Configuración',
    items: [
      { title: 'Usuarios',         icon: 'mdi-account-group-outline', href: '/users' },
      { title: 'Productos',        icon: 'mdi-cube-outline',          href: '/products' },
      { title: 'Almacenes',        icon: 'mdi-home-city-outline',     href: '/warehouses' },
      { title: 'Proveedores',      icon: 'mdi-truck-outline',         href: '/suppliers' },
    ],
  },
]

function isActive(href) {
  return page.url.startsWith(href)
}

function logout() {
  router.post('/logout')
}
</script>

<template>
  <v-app>
    <!-- ─── Navigation Drawer ─────────────────────────────── -->
    <v-navigation-drawer
      v-model="drawer"
      :rail="rail"
      color="primary"
      permanent
    >
      <!-- Logo / Brand -->
      <v-list-item
        prepend-icon="mdi-hard-hat"
        title="ERP Minerjob"
        subtitle="v1.0"
        nav
      >
        <template #append>
          <v-btn
            :icon="rail ? 'mdi-chevron-right' : 'mdi-chevron-left'"
            variant="text"
            color="white"
            size="small"
            @click="rail = !rail"
          />
        </template>
      </v-list-item>

      <v-divider color="rgba(255,255,255,0.2)" class="mb-2" />

      <!-- Menu Items -->
      <v-list density="compact" nav>
        <template v-for="group in navGroups" :key="group.heading">
          <v-list-subheader
            v-if="group.heading && !rail"
            class="text-caption text-uppercase"
            style="color: rgba(255,255,255,0.5)"
          >
            {{ group.heading }}
          </v-list-subheader>

          <v-list-item
            v-for="item in group.items"
            :key="item.href"
            :prepend-icon="item.icon"
            :title="item.title"
            :href="item.href"
            :active="isActive(item.href)"
            active-color="white"
            base-color="rgba(255,255,255,0.8)"
            rounded="lg"
            class="mb-1"
          />

          <v-divider
            v-if="group.heading && !rail"
            color="rgba(255,255,255,0.1)"
            class="my-2"
          />
        </template>
      </v-list>

      <!-- User info at bottom -->
      <template #append>
        <v-divider color="rgba(255,255,255,0.2)" />
        <v-list-item
          :title="user?.name"
          :subtitle="user?.area?.name ?? 'Sin área'"
          nav
          class="py-3"
        >
          <template #prepend>
            <v-avatar color="secondary" size="32">
              <span class="text-caption text-white font-weight-bold">
                {{ user?.name?.charAt(0)?.toUpperCase() }}
              </span>
            </v-avatar>
          </template>
          <template v-if="!rail" #append>
            <v-btn
              icon="mdi-logout"
              variant="text"
              color="white"
              size="small"
              @click="logout"
            />
          </template>
        </v-list-item>
      </template>
    </v-navigation-drawer>

    <!-- ─── Top App Bar ───────────────────────────────────── -->
    <v-app-bar color="white" elevation="1">
      <v-app-bar-nav-icon @click="drawer = !drawer" />

      <v-breadcrumbs :items="['ERP Minerjob']" class="pa-0 pl-2">
        <template #divider>
          <v-icon size="small">mdi-chevron-right</v-icon>
        </template>
      </v-breadcrumbs>

      <v-spacer />

      <v-btn icon="mdi-bell-outline" />

      <v-menu min-width="200">
        <template #activator="{ props }">
          <v-avatar
            v-bind="props"
            color="primary"
            size="36"
            class="mr-2 cursor-pointer"
          >
            <span class="text-caption text-white font-weight-bold">
              {{ user?.name?.charAt(0)?.toUpperCase() }}
            </span>
          </v-avatar>
        </template>

        <v-list>
          <v-list-item :title="user?.name" :subtitle="user?.email">
            <template #prepend>
              <v-avatar color="primary" size="40">
                <span class="text-white font-weight-bold">
                  {{ user?.name?.charAt(0)?.toUpperCase() }}
                </span>
              </v-avatar>
            </template>
          </v-list-item>
          <v-divider />
          <v-list-item prepend-icon="mdi-account" title="Mi Perfil" href="/profile" />
          <v-list-item
            prepend-icon="mdi-logout"
            title="Cerrar Sesión"
            @click="logout"
          />
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- ─── Main Content ─────────────────────────────────── -->
    <v-main style="background: #F5F5F5;">
      <v-container fluid class="pa-6">
        <slot />
      </v-container>
    </v-main>

    <!-- ─── Flash Snackbar ───────────────────────────────── -->
    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      location="top right"
      :timeout="4000"
      rounded="lg"
    >
      <div class="d-flex align-center ga-2">
        <v-icon>
          {{ snackbarColor === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}
        </v-icon>
        {{ snackbarMessage }}
      </div>
      <template #actions>
        <v-btn icon="mdi-close" variant="text" @click="snackbar = false" />
      </template>
    </v-snackbar>
  </v-app>
</template>
