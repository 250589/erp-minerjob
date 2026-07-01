<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props    = defineProps({ delivery: Object })
const del      = computed(() => props.delivery)
const items    = computed(() => del.value.items ?? [])

const totalValue = computed(() =>
  items.value
    .filter(i => i.quantity_delivered !== null)
    .reduce((s, i) => s + Number(i.quantity_delivered) * Number(i.unit_cost ?? 0), 0)
)

function confirmDelivery() {
  if (confirm('¿Confirmar entrega? El stock se descontará del almacén y se registrará en el kardex. (Pasos 40-41)')) {
    router.patch(`/deliveries/${del.value.id}/deliver`)
  }
}

const headers = [
  { title: 'Producto',     key: 'product_name'                        },
  { title: 'Solicitado',   key: 'quantity_requested', align: 'end', width: '110px' },
  { title: 'Entregado',    key: 'quantity_delivered', align: 'end', width: '110px' },
  { title: 'Costo Unit.',  key: 'unit_cost',          align: 'end', width: '120px' },
  { title: 'Total',        key: 'total',              align: 'end', width: '120px' },
]

const itemsForTable = computed(() =>
  items.value.map(i => ({
    ...i,
    product_name: i.product ? `${i.product.sku} — ${i.product.name}` : '—',
  }))
)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap ga-3">
      <div class="d-flex align-center">
        <v-btn icon="mdi-arrow-left" variant="text" href="/deliveries" class="mr-2" />
        <div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <h1 class="text-h5 font-weight-bold">{{ del.code }}</h1>
            <v-chip :color="del.status_color" size="small" label>
              {{ del.status_label }}
            </v-chip>
          </div>
          <p class="text-body-2 text-medium-emphasis mt-1">
            {{ del.warehouse?.name }}
            <span v-if="del.area"> → {{ del.area?.name }}</span>
          </p>
        </div>
      </div>

      <v-btn
        v-if="del.status === 'borrador'"
        color="success" size="large"
        prepend-icon="mdi-hand-extended"
        @click="confirmDelivery">
        Confirmar Entrega (Pasos 40-41)
      </v-btn>
    </div>

    <!-- Banner flujo completo -->
    <v-alert
      v-if="del.status === 'entregada'"
      type="success" variant="tonal" class="mb-4"
      prepend-icon="mdi-flag-checkered">
      <strong>✓ FLUJO COMPLETO — Pasos 1-41 del flujograma ejecutados.</strong>
      Materiales entregados a {{ del.area?.name ?? 'Personal' }} el {{ del.delivered_at }}.
      Stock descontado y kardex actualizado.
    </v-alert>

    <v-row>
      <!-- Tabla de ítems -->
      <v-col cols="12" md="8">
        <v-card variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-package-variant</v-icon>
            Materiales
            <v-chip size="x-small" color="primary" class="ml-2">
              {{ items.length }}
            </v-chip>
          </v-card-title>
          <v-divider />

          <v-data-table :headers="headers" :items="itemsForTable"
            hide-default-footer density="compact">

            <template #item.quantity_requested="{ item }">
              {{ Number(item.quantity_requested).toFixed(2) }}
            </template>

            <template #item.quantity_delivered="{ item }">
              <span v-if="item.quantity_delivered !== null"
                class="font-weight-medium text-success">
                {{ Number(item.quantity_delivered).toFixed(2) }}
              </span>
              <v-chip v-else color="warning" size="x-small" label>Pendiente</v-chip>
            </template>

            <template #item.unit_cost="{ item }">
              <span class="text-caption">
                {{ item.unit_cost ? `S/ ${Number(item.unit_cost).toFixed(4)}` : '—' }}
              </span>
            </template>

            <template #item.total="{ item }">
              <span v-if="item.quantity_delivered && item.unit_cost"
                class="font-weight-medium text-primary">
                S/ {{ (Number(item.quantity_delivered) * Number(item.unit_cost)).toFixed(2) }}
              </span>
              <span v-else class="text-medium-emphasis">—</span>
            </template>

            <template #bottom>
              <v-divider />
              <div v-if="del.status === 'entregada'"
                class="d-flex justify-end align-center pa-3 ga-4">
                <span class="text-body-2 text-medium-emphasis">Valor total entregado:</span>
                <span class="text-h6 font-weight-bold text-primary">
                  S/ {{ totalValue.toFixed(2) }}
                </span>
              </div>
            </template>
          </v-data-table>

          <div v-if="del.notes" class="pa-4 pt-2">
            <v-alert type="info" variant="tonal" density="compact">
              <strong>Nota:</strong> {{ del.notes }}
            </v-alert>
          </div>
        </v-card>
      </v-col>

      <!-- Sidebar info -->
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-information</v-icon>
            Detalles
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-warehouse</v-icon></template>
              <template #title><span class="text-caption">Almacén</span></template>
              <template #subtitle>{{ del.warehouse?.name }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-domain</v-icon></template>
              <template #title><span class="text-caption">Área receptora</span></template>
              <template #subtitle>{{ del.area?.name ?? '—' }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-clipboard-list</v-icon></template>
              <template #title><span class="text-caption">Requerimiento</span></template>
              <template #subtitle>{{ del.requirement?.code ?? '—' }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-account</v-icon></template>
              <template #title><span class="text-caption">Solicitado por</span></template>
              <template #subtitle>{{ del.requested_by?.name }}</template>
            </v-list-item>
            <v-list-item v-if="del.delivered_by">
              <template #prepend><v-icon size="small" color="success">mdi-account-check</v-icon></template>
              <template #title><span class="text-caption">Entregado por</span></template>
              <template #subtitle>{{ del.delivered_by?.name }}</template>
            </v-list-item>
            <v-list-item v-if="del.delivered_at">
              <template #prepend><v-icon size="small">mdi-calendar-check</v-icon></template>
              <template #title><span class="text-caption">Fecha entrega</span></template>
              <template #subtitle>{{ del.delivered_at }}</template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Checklist de pasos 1-41 -->
        <v-card variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start :color="del.status === 'entregada' ? 'success' : 'primary'">
              {{ del.status === 'entregada' ? 'mdi-flag-checkered' : 'mdi-map-marker-path' }}
            </v-icon>
            Estado del Flujo
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item prepend-icon="mdi-check-circle" base-color="success">
              <template #title><span class="text-caption">Pasos 1-27</span></template>
              <template #subtitle>Compra, facturación y kardex principal</template>
            </v-list-item>
            <v-list-item prepend-icon="mdi-check-circle" base-color="success">
              <template #title><span class="text-caption">Pasos 28-37</span></template>
              <template #subtitle>Traslado a subalmacén</template>
            </v-list-item>
            <v-list-item prepend-icon="mdi-check-circle" base-color="success">
              <template #title><span class="text-caption">Paso 39</span></template>
              <template #subtitle>Nota de Entrega {{ del.code }} generada</template>
            </v-list-item>
            <v-list-item
              :prepend-icon="del.status === 'entregada' ? 'mdi-check-circle' : 'mdi-circle-outline'"
              :base-color="del.status === 'entregada' ? 'success' : 'default'">
              <template #title><span class="text-caption">Paso 40</span></template>
              <template #subtitle>Salida kardex subalmacén</template>
            </v-list-item>
            <v-list-item
              :prepend-icon="del.status === 'entregada' ? 'mdi-flag-checkered' : 'mdi-circle-outline'"
              :base-color="del.status === 'entregada' ? 'success' : 'default'">
              <template #title><span class="text-caption">Paso 41</span></template>
              <template #subtitle>Personal recibe materiales ✓ FIN</template>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>