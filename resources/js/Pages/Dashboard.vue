<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user)

const modules = [
  { title: 'Requerimientos', icon: 'mdi-clipboard-list-outline',  color: 'primary',   href: '/requirements'        },
  { title: 'Cotizaciones',   icon: 'mdi-file-compare',            color: 'secondary',  href: '/quote-requests'      },
  { title: 'Aprobaciones',   icon: 'mdi-gavel',                   color: 'warning',    href: '/approvals'           },
  { title: 'Órdenes de Compra', icon: 'mdi-cart-outline',         color: 'info',       href: '/purchase-orders'     },
  { title: 'Proveedores',    icon: 'mdi-truck-outline',           color: 'success',    href: '/suppliers'           },
  { title: 'Facturas',       icon: 'mdi-file-document-outline',   color: 'error',      href: '/invoices'            },
  { title: 'Pagos',          icon: 'mdi-bank-transfer',           color: 'success',    href: '/payments'            },
  { title: 'Recepciones',    icon: 'mdi-warehouse',               color: 'primary',    href: '/warehouse-receptions'},
  { title: 'Stock',          icon: 'mdi-package-variant-closed',  color: 'secondary',  href: '/stock'               },
  { title: 'Kardex',         icon: 'mdi-book-open-outline',       color: 'info',       href: '/kardex'              },
  { title: 'Traslados',      icon: 'mdi-transfer',                color: 'warning',    href: '/transfers'           },
]
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-h5 font-weight-bold">
        Bienvenido, {{ user?.name }} 👋
      </h1>
      <p class="text-body-2 text-medium-emphasis mt-1">
        ERP Minerjob V1.0 — Panel de Control
      </p>
    </div>

    <!-- Banner -->
    <v-alert type="success" variant="tonal" class="mb-6"
      prepend-icon="mdi-check-circle">
      <strong>Sistema operativo.</strong>
      Base de datos conectada · Módulos 1-10 activos.
    </v-alert>

    <!-- Módulos -->
    <v-row>
      <v-col v-for="mod in modules" :key="mod.href"
        cols="12" sm="6" md="4" lg="3">
        <v-card
          variant="outlined"
          rounded="lg"
          :href="mod.href"
          class="text-decoration-none"
          hover
        >
          <v-card-text class="d-flex align-center ga-4 pa-5">
            <v-avatar :color="mod.color" size="48" rounded="lg">
              <v-icon color="white" size="24">{{ mod.icon }}</v-icon>
            </v-avatar>
            <div>
              <p class="text-subtitle-2 font-weight-bold">{{ mod.title }}</p>
              <p class="text-caption text-medium-emphasis">Módulo activo</p>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>