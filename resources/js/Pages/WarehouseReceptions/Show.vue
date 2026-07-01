<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { computed } from 'vue'

const props = defineProps({ reception: Object })

const rec   = computed(() => props.reception)
const items = computed(() => rec.value.items ?? [])

const itemHeaders = [
  { title: 'Producto',    key: 'product_name'                      },
  { title: 'Ord.',        key: 'quantity_ordered',  width: '80px', align: 'end' },
  { title: 'Recibido',    key: 'quantity_received', width: '90px', align: 'end' },
  { title: 'P. Compra',   key: 'unit_purchase_price', width: '110px', align: 'end' },
  { title: 'Margen',      key: 'markup_percentage_applied', width: '90px', align: 'end' },
  { title: 'P. Venta',    key: 'unit_sale_price',   width: '110px', align: 'end' },
  { title: 'Estado',      key: 'condition_status',  width: '110px' },
]

const itemsWithName = computed(() =>
  items.value.map(item => ({
    ...item,
    product_name: item.product ? `${item.product.sku} — ${item.product.name}` : '—',
    qty_diff:     Number(item.quantity_received) - Number(item.quantity_ordered),
  }))
)

const totalValue = computed(() =>
  items.value
    .filter(i => i.condition_status === 'bueno')
    .reduce((s, i) => s + Number(i.quantity_received) * Number(i.unit_purchase_price), 0)
)

function fmt(val, prefix = 'S/') {
  return `${prefix} ${Number(val).toFixed(2)}`
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap ga-3">
      <div class="d-flex align-center">
        <v-btn icon="mdi-arrow-left" variant="text"
          href="/warehouse-receptions" class="mr-2" />
        <div>
          <div class="d-flex align-center ga-2">
            <h1 class="text-h5 font-weight-bold">{{ rec.code }}</h1>
            <v-chip :color="rec.status_color" size="small" label>
              {{ rec.status_label }}
            </v-chip>
          </div>
          <p class="text-body-2 text-medium-emphasis mt-1">
            OC: {{ rec.purchase_order?.code }} ·
            {{ rec.warehouse?.name }} ·
            {{ rec.received_at }}
          </p>
        </div>
      </div>

      <div class="d-flex ga-2">
        <v-btn variant="tonal" prepend-icon="mdi-chart-bar"
          href="/stock">
          Ver Stock
        </v-btn>
        <v-btn variant="tonal" prepend-icon="mdi-book-open"
          :href="`/kardex?warehouse_id=${rec.warehouse_id}`">
          Ver Kardex
        </v-btn>
      </div>
    </div>

    <!-- Banner éxito -->
    <v-alert type="success" variant="tonal" density="compact" class="mb-4">
      <strong>Stock actualizado y Kardex registrado.</strong>
      Los ítems en buen estado ingresaron al inventario del
      <strong>{{ rec.warehouse?.name }}</strong>. (Pasos 26-27)
    </v-alert>

    <v-row>
      <v-col cols="12" md="8">
        <!-- Tabla de ítems -->
        <v-card variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-package-variant</v-icon>
            Ítems Recibidos
            <v-chip size="x-small" color="primary" class="ml-2">
              {{ items.length }}
            </v-chip>
          </v-card-title>
          <v-divider />

          <v-data-table :headers="itemHeaders" :items="itemsWithName"
            hide-default-footer density="compact">

            <template #item.quantity_ordered="{ item }">
              <span class="text-body-2">{{ Number(item.quantity_ordered).toFixed(2) }}</span>
            </template>

            <template #item.quantity_received="{ item }">
              <span
                class="font-weight-medium"
                :class="{
                  'text-warning': item.qty_diff < 0,
                  'text-success': item.qty_diff >= 0,
                }"
              >
                {{ Number(item.quantity_received).toFixed(2) }}
                <span v-if="item.qty_diff !== 0" class="text-caption">
                  ({{ item.qty_diff > 0 ? '+' : '' }}{{ item.qty_diff.toFixed(2) }})
                </span>
              </span>
            </template>

            <template #item.unit_purchase_price="{ item }">
              {{ fmt(item.unit_purchase_price) }}
            </template>

            <template #item.markup_percentage_applied="{ item }">
              <span class="text-body-2">{{ item.markup_percentage_applied }}%</span>
            </template>

            <template #item.unit_sale_price="{ item }">
              <span class="font-weight-medium text-primary">
                {{ fmt(item.unit_sale_price) }}
              </span>
            </template>

            <template #item.condition_status="{ item }">
              <v-chip
                :color="item.condition_status === 'bueno' ? 'success' : 'error'"
                size="small" label>
                <v-icon start size="12">
                  {{ item.condition_status === 'bueno' ? 'mdi-check' : 'mdi-alert' }}
                </v-icon>
                {{ item.condition_status === 'bueno' ? 'Buen estado' : 'Dañado' }}
              </v-chip>
            </template>

            <template #bottom>
              <v-divider />
              <div class="d-flex justify-end align-center pa-3 ga-4">
                <span class="text-body-2 text-medium-emphasis">
                  Valor total ingresado al stock:
                </span>
                <span class="text-h6 font-weight-bold text-primary">
                  S/ {{ totalValue.toFixed(2) }}
                </span>
              </div>
            </template>
          </v-data-table>

          <div v-if="rec.observations" class="pa-4 pt-2">
            <v-alert type="warning" variant="tonal" density="compact">
              <strong>Observaciones:</strong> {{ rec.observations }}
            </v-alert>
          </div>
        </v-card>
      </v-col>

      <!-- Sidebar -->
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-information</v-icon>
            Datos de la Recepción
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-cart</v-icon></template>
              <template #title><span class="text-caption">Orden de Compra</span></template>
              <template #subtitle><strong>{{ rec.purchase_order?.code }}</strong></template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-home-city</v-icon></template>
              <template #title><span class="text-caption">Almacén</span></template>
              <template #subtitle>{{ rec.warehouse?.name }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-account</v-icon></template>
              <template #title><span class="text-caption">Recibido por</span></template>
              <template #subtitle>{{ rec.received_by?.name }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-calendar</v-icon></template>
              <template #title><span class="text-caption">Fecha/Hora</span></template>
              <template #subtitle>{{ rec.received_at }}</template>
            </v-list-item>
            <v-list-item v-if="rec.invoice">
              <template #prepend><v-icon size="small">mdi-file-document</v-icon></template>
              <template #title><span class="text-caption">Factura Vinculada</span></template>
              <template #subtitle>
                {{ rec.invoice?.series }}-{{ rec.invoice?.number }}
              </template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Leyenda de pasos completados -->
        <v-card variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="success">mdi-check-all</v-icon>
            Pasos Completados
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item prepend-icon="mdi-check-circle" base-color="success">
              <template #title><span class="text-caption">Paso 20-21</span></template>
              <template #subtitle>Proveedor entregó, almacén recibió</template>
            </v-list-item>
            <v-list-item prepend-icon="mdi-check-circle" base-color="success">
              <template #title><span class="text-caption">Paso 23-24</span></template>
              <template #subtitle>Ingreso al almacén y precio de compra</template>
            </v-list-item>
            <v-list-item prepend-icon="mdi-check-circle" base-color="success">
              <template #title><span class="text-caption">Paso 25</span></template>
              <template #subtitle>Precio de venta calculado (+35%)</template>
            </v-list-item>
            <v-list-item prepend-icon="mdi-check-circle" base-color="success">
              <template #title><span class="text-caption">Pasos 26-27</span></template>
              <template #subtitle>Stock y Kardex actualizados</template>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>
