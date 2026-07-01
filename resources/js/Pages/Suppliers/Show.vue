<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({ supplier: Object })

const contactHeaders = [
  { title: 'Nombre',   key: 'name'     },
  { title: 'Cargo',    key: 'position' },
  { title: 'Teléfono', key: 'phone'    },
  { title: 'Correo',   key: 'email'    },
]

function confirmDelete() {
  if (confirm(`¿Eliminar al proveedor "${props.supplier.business_name}"?`)) {
    router.delete(`/suppliers/${props.supplier.id}`)
  }
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap ga-3">
      <div class="d-flex align-center">
        <v-btn icon="mdi-arrow-left" variant="text" href="/suppliers" class="mr-2" />
        <div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <h1 class="text-h5 font-weight-bold">{{ supplier.business_name }}</h1>
            <v-chip
              :color="supplier.status === 'activo' ? 'success' : 'default'"
              size="small" label>
              {{ supplier.status === 'activo' ? 'Activo' : 'Inactivo' }}
            </v-chip>
          </div>
          <p class="text-body-2 text-medium-emphasis mt-1">RUC: {{ supplier.tax_id }}</p>
        </div>
      </div>
      <div class="d-flex ga-2">
        <v-btn variant="tonal" color="error" prepend-icon="mdi-delete" @click="confirmDelete">
          Eliminar
        </v-btn>
        <v-btn variant="tonal" prepend-icon="mdi-pencil" :href="`/suppliers/${supplier.id}/edit`">
          Editar
        </v-btn>
      </div>
    </div>

    <v-row>
      <!-- Info principal -->
      <v-col cols="12" md="8">
        <!-- Contactos -->
        <v-card variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-account-multiple</v-icon>
            Contactos
            <v-chip size="x-small" color="primary" class="ml-2">
              {{ supplier.contacts.length }}
            </v-chip>
          </v-card-title>
          <v-divider />
          <v-data-table
            :headers="contactHeaders"
            :items="supplier.contacts"
            hide-default-footer
            density="compact"
          >
            <template #item.position="{ item }">{{ item.position ?? '—' }}</template>
            <template #item.phone="{ item }">{{ item.phone ?? '—' }}</template>
            <template #item.email="{ item }">
              <a v-if="item.email" :href="`mailto:${item.email}`" class="text-primary">
                {{ item.email }}
              </a>
              <span v-else>—</span>
            </template>
            <template #no-data>
              <div class="text-center pa-4 text-medium-emphasis text-body-2">
                Sin contactos registrados
              </div>
            </template>
          </v-data-table>
        </v-card>
      </v-col>

      <!-- Sidebar info -->
      <v-col cols="12" md="4">
        <!-- Datos generales -->
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-domain</v-icon>
            Información General
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-tag-outline</v-icon></template>
              <template #title><span class="text-caption">Nombre Comercial</span></template>
              <template #subtitle><span class="text-body-2">{{ supplier.trade_name ?? '—' }}</span></template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-map-marker</v-icon></template>
              <template #title><span class="text-caption">Dirección</span></template>
              <template #subtitle><span class="text-body-2">{{ supplier.address ?? '—' }}</span></template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-phone</v-icon></template>
              <template #title><span class="text-caption">Teléfono</span></template>
              <template #subtitle><span class="text-body-2">{{ supplier.phone ?? '—' }}</span></template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-email</v-icon></template>
              <template #title><span class="text-caption">Correo</span></template>
              <template #subtitle>
                <a v-if="supplier.email" :href="`mailto:${supplier.email}`" class="text-primary text-body-2">
                  {{ supplier.email }}
                </a>
                <span v-else class="text-body-2">—</span>
              </template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Condiciones comerciales -->
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-handshake</v-icon>
            Condiciones Comerciales
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-currency-usd</v-icon></template>
              <template #title><span class="text-caption">Moneda</span></template>
              <template #subtitle><span class="text-body-2">{{ supplier.currency_default }}</span></template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-calendar-clock</v-icon></template>
              <template #title><span class="text-caption">Plazo de pago</span></template>
              <template #subtitle>
                <span class="text-body-2">{{ supplier.payment_term_days }} días</span>
              </template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Datos bancarios -->
        <v-card variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-bank</v-icon>
            Datos Bancarios
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-bank-outline</v-icon></template>
              <template #title><span class="text-caption">Banco</span></template>
              <template #subtitle><span class="text-body-2">{{ supplier.bank_name ?? '—' }}</span></template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-credit-card-outline</v-icon></template>
              <template #title><span class="text-caption">Cuenta / CCI</span></template>
              <template #subtitle><span class="text-body-2">{{ supplier.bank_account ?? '—' }}</span></template>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>
