<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ purchaseOrder: Object })

const po = computed(() => props.purchaseOrder)

const itemHeaders = [
  { title: '#',           key: 'index',      width: '50px'  },
  { title: 'Descripción', key: 'description'                },
  { title: 'Cantidad',    key: 'quantity',   width: '100px', align: 'end' },
  { title: 'P. Unitario', key: 'unit_price', width: '130px', align: 'end' },
  { title: 'Subtotal',    key: 'subtotal',   width: '130px', align: 'end' },
]

const itemsWithIndex = computed(() =>
  (po.value?.items ?? []).map((item, i) => ({ ...item, index: i + 1 }))
)

function currency(val) {
  if (val === null || val === undefined) return '—'
  return `${po.value?.currency} ${Number(val).toFixed(2)}`
}

function sendToSupplier() {
  if (confirm('¿Marcar la Orden de Compra como enviada al proveedor? (Paso 9)')) {
    router.patch(`/purchase-orders/${po.value.id}/send`)
  }
}

function cancelOrder() {
  if (confirm('¿Anular esta Orden de Compra? Esta acción no se puede deshacer.')) {
    router.patch(`/purchase-orders/${po.value.id}/cancel`)
  }
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap ga-3">
      <div class="d-flex align-center">
        <v-btn icon="mdi-arrow-left" variant="text" href="/purchase-orders" class="mr-2" />
        <div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <h1 class="text-h5 font-weight-bold">{{ po.code }}</h1>
            <v-chip :color="po.status_color" size="small" label>
              {{ po.status_label }}
            </v-chip>
          </div>
          <p class="text-body-2 text-medium-emphasis mt-1">
            Orden de Compra generada automáticamente · Paso 8
          </p>
        </div>
      </div>

      <div class="d-flex ga-2 flex-wrap">
        <v-btn v-if="po.status === 'generada'" variant="tonal" color="error"
          prepend-icon="mdi-cancel" @click="cancelOrder">
          Anular
        </v-btn>
        <v-btn v-if="po.status === 'generada'" color="primary"
          prepend-icon="mdi-send" @click="sendToSupplier">
          Enviar a Proveedor
        </v-btn>
      </div>
    </div>

    <!-- Banner de éxito para OC recién generada -->
    <v-alert
      v-if="po.status === 'generada'"
      type="success"
      variant="tonal"
      density="compact"
      class="mb-4"
      prepend-icon="mdi-check-circle"
    >
      <strong>Orden de Compra generada exitosamente.</strong>
      Haga clic en "Enviar a Proveedor" cuando esté lista para compartir. (Paso 9)
    </v-alert>

    <v-row>
      <!-- ─── Ítems ─────────────────────────────────────── -->
      <v-col cols="12" md="8">
        <v-card variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-format-list-bulleted</v-icon>
            Ítems de la Orden
            <v-chip size="x-small" color="primary" class="ml-2">
              {{ po.items?.length }}
            </v-chip>
          </v-card-title>
          <v-divider />

          <v-data-table
            :headers="itemHeaders"
            :items="itemsWithIndex"
            hide-default-footer
            density="compact"
          >
            <template #item.quantity="{ item }">
              {{ Number(item.quantity).toFixed(2) }}
            </template>
            <template #item.unit_price="{ item }">
              {{ currency(item.unit_price) }}
            </template>
            <template #item.subtotal="{ item }">
              {{ currency(item.subtotal) }}
            </template>

            <template #bottom>
              <v-divider />
              <div class="d-flex flex-column align-end pa-4 ga-1">
                <span class="text-body-2">
                  Subtotal: <strong>{{ currency(po.subtotal) }}</strong>
                </span>
                <span class="text-body-2">
                  IGV 18%: <strong>{{ currency(po.tax) }}</strong>
                </span>
                <span class="text-h6 font-weight-bold text-primary mt-1">
                  TOTAL: {{ currency(po.total) }}
                </span>
              </div>
            </template>
          </v-data-table>
        </v-card>
      </v-col>

      <!-- ─── Sidebar ───────────────────────────────────── -->
      <v-col cols="12" md="4">

        <!-- Proveedor -->
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-truck-outline</v-icon>
            Proveedor
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-domain</v-icon></template>
              <template #title><span class="text-caption">Razón Social</span></template>
              <template #subtitle>
                <span class="font-weight-bold">{{ po.supplier?.business_name }}</span>
              </template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-identifier</v-icon></template>
              <template #title><span class="text-caption">RUC</span></template>
              <template #subtitle>{{ po.supplier?.tax_id }}</template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Condiciones -->
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-handshake</v-icon>
            Condiciones
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-currency-usd</v-icon></template>
              <template #title><span class="text-caption">Moneda</span></template>
              <template #subtitle>{{ po.currency }} (T/C: {{ po.exchange_rate }})</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-calendar-clock</v-icon></template>
              <template #title><span class="text-caption">Plazo de Pago</span></template>
              <template #subtitle>{{ po.payment_term_days }} días</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-truck-delivery</v-icon></template>
              <template #title><span class="text-caption">Plazo de Entrega</span></template>
              <template #subtitle>{{ po.delivery_term_days }} días</template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Trazabilidad -->
        <v-card variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-clipboard-flow</v-icon>
            Trazabilidad
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-clipboard-list</v-icon></template>
              <template #title><span class="text-caption">Requerimiento</span></template>
              <template #subtitle>{{ po.requirement?.code ?? '—' }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-account-check</v-icon></template>
              <template #title><span class="text-caption">Aprobado por</span></template>
              <template #subtitle>{{ po.approved_by?.name ?? '—' }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-calendar</v-icon></template>
              <template #title><span class="text-caption">Fecha de creación</span></template>
              <template #subtitle>{{ po.created_at }}</template>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>
