<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

const props   = defineProps({ transfer: Object })
const tr      = computed(() => props.transfer)
const items   = computed(() => tr.value.items ?? [])

// ─── Formulario de recepción ──────────────────────────────────
const receiveDialog = ref(false)
const receiveForm   = useForm({
  status:       'aceptado',
  observations: '',
  items:        items.value.map(item => ({
    transfer_order_item_id: item.id,
    quantity_received:      Number(item.quantity_sent ?? item.quantity_requested),
    condition_status:       'bueno',
    notes:                  '',
  })),
})

function openReceiveDialog() {
  // Reinicializar ítems con cantidades enviadas
  receiveForm.items = items.value.map(item => ({
    transfer_order_item_id: item.id,
    quantity_received:      Number(item.quantity_sent ?? item.quantity_requested),
    condition_status:       'bueno',
    notes:                  '',
  }))
  receiveDialog.value = true
}

function submitReceive() {
  receiveForm.post(`/transfers/${tr.value.id}/receive`, {
    onSuccess: () => { receiveDialog.value = false },
  })
}

function dispatch() {
  if (confirm('¿Despachar la mercadería? Se registrará la salida en el kardex del almacén origen. (Paso 31)')) {
    router.patch(`/transfers/${tr.value.id}/dispatch`)
  }
}

// ─── Stepper de estados ───────────────────────────────────────
const steps = [
  { key: 'creada',      label: 'Creada',      icon: 'mdi-clipboard-check' },
  { key: 'en_transito', label: 'En Tránsito', icon: 'mdi-truck-delivery'  },
  { key: 'recibida',    label: 'Recibida',    icon: 'mdi-check-all'        },
]

const stepOrder = ['creada', 'en_transito', 'recibida']

function stepColor(stepKey) {
  const curr = stepOrder.indexOf(tr.value.status)
  const step = stepOrder.indexOf(stepKey)
  return step <= curr ? 'primary' : 'grey-lighten-2'
}

const itemHeaders = [
  { title: 'Producto',   key: 'product_name'                       },
  { title: 'Solicitado', key: 'quantity_requested', width: '110px', align: 'end' },
  { title: 'Despachado', key: 'quantity_sent',       width: '110px', align: 'end' },
  { title: 'Recibido',   key: 'quantity_received',   width: '110px', align: 'end' },
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
        <v-btn icon="mdi-arrow-left" variant="text" href="/transfers" class="mr-2" />
        <div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <h1 class="text-h5 font-weight-bold">{{ tr.code }}</h1>
            <v-chip :color="tr.status_color" size="small" label>
              {{ tr.status_label }}
            </v-chip>
          </div>
          <p class="text-body-2 text-medium-emphasis mt-1">
            {{ tr.origin_warehouse?.name }}
            <v-icon size="14">mdi-arrow-right</v-icon>
            {{ tr.destination_warehouse?.name }}
          </p>
        </div>
      </div>

      <div class="d-flex ga-2">
        <v-btn
          v-if="tr.status === 'creada'"
          color="primary"
          prepend-icon="mdi-truck-delivery-outline"
          @click="dispatch"
        >
          Despachar (Paso 31)
        </v-btn>
        <v-btn
          v-if="tr.status === 'en_transito'"
          color="success"
          prepend-icon="mdi-package-down"
          @click="openReceiveDialog"
        >
          Confirmar Recepción (Paso 32)
        </v-btn>
      </div>
    </div>

    <!-- Stepper -->
    <v-card variant="outlined" rounded="lg" class="mb-4">
      <v-card-text class="py-3">
        <div class="d-flex align-center justify-center ga-2">
          <template v-for="(step, i) in steps" :key="step.key">
            <div class="d-flex flex-column align-center" style="min-width:90px">
              <v-avatar :color="stepColor(step.key)" size="40">
                <v-icon color="white" size="20">{{ step.icon }}</v-icon>
              </v-avatar>
              <span class="text-caption mt-1 text-center">{{ step.label }}</span>
            </div>
            <v-divider v-if="i < steps.length - 1" class="flex-grow-1" style="max-width:80px" />
          </template>
        </div>
      </v-card-text>
    </v-card>

    <v-row>
      <!-- Tabla de ítems -->
      <v-col cols="12" md="8">
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-package-variant</v-icon>
            Productos del Traslado
          </v-card-title>
          <v-divider />

          <v-data-table :headers="itemHeaders" :items="itemsForTable"
            hide-default-footer density="compact">

            <template #item.quantity_requested="{ item }">
              <span class="text-body-2">{{ Number(item.quantity_requested).toFixed(2) }}</span>
            </template>

            <template #item.quantity_sent="{ item }">
              <span v-if="item.quantity_sent !== null" class="font-weight-medium text-info">
                {{ Number(item.quantity_sent).toFixed(2) }}
              </span>
              <span v-else class="text-medium-emphasis text-caption">Pendiente</span>
            </template>

            <template #item.quantity_received="{ item }">
              <span v-if="item.quantity_received !== null"
                class="font-weight-medium"
                :class="Number(item.quantity_received) >= Number(item.quantity_sent ?? item.quantity_requested)
                  ? 'text-success' : 'text-warning'">
                {{ Number(item.quantity_received).toFixed(2) }}
              </span>
              <span v-else class="text-medium-emphasis text-caption">—</span>
            </template>
          </v-data-table>
        </v-card>

        <!-- Guía de traslado -->
        <v-card v-if="tr.guide" variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="success">mdi-file-check</v-icon>
            Guía Interna de Traslado (Paso 30)
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-pound</v-icon></template>
              <template #title><span class="text-caption">N° de Guía</span></template>
              <template #subtitle>
                <span class="font-weight-bold">{{ tr.guide.guide_number }}</span>
              </template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-account</v-icon></template>
              <template #title><span class="text-caption">Emitida por</span></template>
              <template #subtitle>{{ tr.guide.issued_by?.name }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-calendar</v-icon></template>
              <template #title><span class="text-caption">Fecha</span></template>
              <template #subtitle>{{ tr.guide.issued_at }}</template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Recepción -->
        <v-card v-if="tr.reception" variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="success">mdi-check-all</v-icon>
            Recepción Confirmada (Pasos 32-34)
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-account</v-icon></template>
              <template #title><span class="text-caption">Recibido por</span></template>
              <template #subtitle>{{ tr.reception.received_by?.name }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-calendar</v-icon></template>
              <template #title><span class="text-caption">Fecha</span></template>
              <template #subtitle>{{ tr.reception.received_at }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-check-circle</v-icon></template>
              <template #title><span class="text-caption">Estado</span></template>
              <template #subtitle>
                <v-chip
                  :color="tr.reception.status === 'aceptado' ? 'success' : 'warning'"
                  size="x-small" label>
                  {{ tr.reception.status === 'aceptado' ? 'Aceptado' : 'Observado' }}
                </v-chip>
              </template>
            </v-list-item>
          </v-list>
          <div v-if="tr.reception.observations" class="pa-3 pt-0">
            <v-alert type="warning" variant="tonal" density="compact">
              <strong>Observación:</strong> {{ tr.reception.observations }}
            </v-alert>
          </div>
        </v-card>
      </v-col>

      <!-- Sidebar -->
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-information</v-icon>
            Detalles
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend>
                <v-icon size="small" color="primary">mdi-home-city</v-icon>
              </template>
              <template #title><span class="text-caption">Almacén Origen</span></template>
              <template #subtitle>
                <span class="font-weight-bold">{{ tr.origin_warehouse?.name }}</span>
              </template>
            </v-list-item>
            <v-list-item>
              <template #prepend>
                <v-icon size="small" color="secondary">mdi-home-map-marker</v-icon>
              </template>
              <template #title><span class="text-caption">Almacén Destino</span></template>
              <template #subtitle>
                <span class="font-weight-bold">{{ tr.destination_warehouse?.name }}</span>
              </template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-account</v-icon></template>
              <template #title><span class="text-caption">Solicitado por</span></template>
              <template #subtitle>{{ tr.requested_by?.name }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-calendar</v-icon></template>
              <template #title><span class="text-caption">Fecha creación</span></template>
              <template #subtitle>{{ tr.created_at }}</template>
            </v-list-item>
          </v-list>

          <v-divider />
          <v-card-text class="pt-3">
            <v-btn variant="tonal" block prepend-icon="mdi-book-open"
              :href="`/kardex?warehouse_id=${tr.origin_warehouse_id}`">
              Kardex Origen
            </v-btn>
            <v-btn variant="tonal" block class="mt-2"
              prepend-icon="mdi-book-open"
              :href="`/kardex?warehouse_id=${tr.destination_warehouse_id}`">
              Kardex Destino
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Dialog: Confirmar recepción -->
    <v-dialog v-model="receiveDialog" max-width="640" scrollable>
      <v-card rounded="lg">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="success" class="mr-2">mdi-package-down</v-icon>
          Confirmar Recepción en {{ tr.destination_warehouse?.name }}
        </v-card-title>
        <v-divider />

        <v-card-text>
          <!-- Estado de la recepción -->
          <div class="d-flex ga-2 mb-4">
            <v-btn
              v-for="opt in [
                { label: 'Aceptado', value: 'aceptado', color: 'success' },
                { label: 'Observado', value: 'observado', color: 'warning' },
              ]"
              :key="opt.value"
              :color="receiveForm.status === opt.value ? opt.color : 'default'"
              :variant="receiveForm.status === opt.value ? 'tonal' : 'outlined'"
              size="small"
              @click="receiveForm.status = opt.value"
            >
              {{ opt.label }}
            </v-btn>
          </div>

          <v-textarea v-if="receiveForm.status === 'observado'"
            v-model="receiveForm.observations"
            label="Detalle de la observación"
            variant="outlined" density="compact" rows="2"
            class="mb-4" />

          <!-- Ítems a recibir -->
          <v-table density="compact">
            <thead>
              <tr>
                <th>Producto</th>
                <th class="text-center">Desp.</th>
                <th style="min-width:110px">Recibido *</th>
                <th style="min-width:110px">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in receiveForm.items" :key="index">
                <td class="text-caption">
                  {{ items[index]?.product?.name }}
                </td>
                <td class="text-center text-caption text-medium-emphasis">
                  {{ Number(items[index]?.quantity_sent ?? 0).toFixed(2) }}
                </td>
                <td>
                  <v-text-field
                    v-model.number="receiveForm.items[index].quantity_received"
                    type="number" min="0" step="0.01"
                    variant="outlined" density="compact" hide-details
                  />
                </td>
                <td>
                  <v-select
                    v-model="receiveForm.items[index].condition_status"
                    :items="[
                      { title: 'Bueno',  value: 'bueno'  },
                      { title: 'Dañado', value: 'danado' },
                    ]"
                    variant="outlined" density="compact" hide-details
                    :color="receiveForm.items[index].condition_status === 'danado' ? 'error' : 'success'"
                  />
                </td>
              </tr>
            </tbody>
          </v-table>

          <v-alert type="info" variant="tonal" density="compact" class="mt-3">
            Los ítems en buen estado ingresarán al stock y kardex del almacén destino. (Pasos 32-34)
          </v-alert>
        </v-card-text>

        <v-divider />
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="receiveDialog = false">Cancelar</v-btn>
          <v-btn color="success" :loading="receiveForm.processing"
            @click="submitReceive">
            Confirmar Recepción
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
