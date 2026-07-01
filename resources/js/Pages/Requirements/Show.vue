<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  requirement: Object,
})

const headers = [
  { title: '#',           key: 'index',                   width: '50px' },
  { title: 'Producto',    key: 'product_name'                            },
  { title: 'Descripción', key: 'description'                             },
  { title: 'Cantidad',    key: 'quantity',    align: 'end', width: '100px' },
  { title: 'Unidad',      key: 'unit_abbr',  align: 'center', width: '90px' },
  { title: 'P. Est.',     key: 'price',       align: 'end', width: '120px' },
  { title: 'Subtotal',    key: 'subtotal',    align: 'end', width: '120px' },
]

const itemsWithIndex = computed(() =>
  props.requirement.items.map((item, i) => ({
    ...item,
    index:        i + 1,
    product_name: item.product?.name ?? '—',
    unit_abbr:    item.unit?.abbreviation ?? '—',
    price:        item.estimated_unit_price,
    subtotal:     Number(item.quantity) * Number(item.estimated_unit_price || 0),
  }))
)

const estimatedTotal = computed(() =>
  itemsWithIndex.value.reduce((sum, i) => sum + i.subtotal, 0)
)

const canEdit   = computed(() => ['pendiente', 'rechazado'].includes(props.requirement.status))
const canSend   = computed(() => props.requirement.status === 'pendiente')
const canDelete = computed(() => props.requirement.status === 'pendiente')

function sendToCompras() {
  if (confirm('¿Enviar este requerimiento a Compras para iniciar el proceso de cotización?')) {
    router.patch(`/requirements/${props.requirement.id}/send-to-compras`)
  }
}

function deleteRequirement() {
  if (confirm('¿Eliminar este requerimiento? Esta acción no se puede deshacer.')) {
    router.delete(`/requirements/${props.requirement.id}`)
  }
}

function currency(value) {
  if (!value || Number(value) === 0) return '—'
  return `S/ ${Number(value).toFixed(2)}`
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap ga-3">
      <div class="d-flex align-center">
        <v-btn
          icon="mdi-arrow-left"
          variant="text"
          href="/requirements"
          class="mr-2"
        />
        <div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <h1 class="text-h5 font-weight-bold">{{ requirement.code }}</h1>
            <v-chip :color="requirement.status_color" size="small" label>
              {{ requirement.status_label }}
            </v-chip>
          </div>
          <p class="text-body-2 text-medium-emphasis mt-1">
            Creado por <strong>{{ requirement.requester?.name }}</strong>
            · {{ requirement.created_at }}
          </p>
        </div>
      </div>

      <!-- Acciones -->
      <div class="d-flex ga-2 flex-wrap">
        <v-btn
          v-if="canDelete"
          variant="tonal"
          color="error"
          prepend-icon="mdi-delete"
          @click="deleteRequirement"
        >
          Eliminar
        </v-btn>
        <v-btn
          v-if="canEdit"
          variant="tonal"
          prepend-icon="mdi-pencil"
          :href="`/requirements/${requirement.id}/edit`"
        >
          Editar
        </v-btn>
        <v-btn
          v-if="canSend"
          color="primary"
          prepend-icon="mdi-send"
          @click="sendToCompras"
        >
          Enviar a Compras
        </v-btn>
      </div>
    </div>

    <v-row>
      <!-- ─── Columna principal ──────────────────────── -->
      <v-col cols="12" md="8">
        <v-card variant="outlined" rounded="lg">
          <v-card-title class="text-subtitle-1 font-weight-bold d-flex align-center pa-4 pb-3">
            <v-icon start color="primary">mdi-format-list-bulleted</v-icon>
            Ítems Solicitados
            <v-chip size="x-small" color="primary" class="ml-2">
              {{ requirement.items.length }}
            </v-chip>
          </v-card-title>
          <v-divider />

          <v-data-table
            :headers="headers"
            :items="itemsWithIndex"
            hide-default-footer
            density="compact"
          >
            <template #item.product_name="{ item }">
              <div>
                <div class="text-body-2">{{ item.product_name }}</div>
                <div v-if="item.product?.sku" class="text-caption text-medium-emphasis">
                  {{ item.product.sku }}
                </div>
              </div>
            </template>

            <template #item.quantity="{ item }">
              <span class="font-weight-medium">{{ Number(item.quantity).toFixed(2) }}</span>
            </template>

            <template #item.price="{ item }">
              <span class="text-body-2">{{ currency(item.price) }}</span>
            </template>

            <template #item.subtotal="{ item }">
              <span class="font-weight-medium">{{ currency(item.subtotal) }}</span>
            </template>

            <template #bottom>
              <v-divider />
              <div class="d-flex justify-end align-center pa-3 ga-4">
                <span class="text-body-2 text-medium-emphasis">Total estimado:</span>
                <span class="text-subtitle-1 font-weight-bold text-primary">
                  {{ currency(estimatedTotal) }}
                </span>
              </div>
            </template>
          </v-data-table>
        </v-card>
      </v-col>

      <!-- ─── Sidebar información ───────────────────── -->
      <v-col cols="12" md="4">
        <!-- Info card -->
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="text-subtitle-1 font-weight-bold d-flex align-center pa-4 pb-3">
            <v-icon start color="primary">mdi-information-outline</v-icon>
            Información
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend>
                <v-icon size="small" color="medium-emphasis">mdi-account</v-icon>
              </template>
              <template #title><span class="text-caption">Solicitante</span></template>
              <template #subtitle>
                <span class="text-body-2 font-weight-medium">
                  {{ requirement.requester?.name }}
                </span>
              </template>
            </v-list-item>
            <v-list-item>
              <template #prepend>
                <v-icon size="small" color="medium-emphasis">mdi-office-building</v-icon>
              </template>
              <template #title><span class="text-caption">Área</span></template>
              <template #subtitle>
                <span class="text-body-2">{{ requirement.area?.name ?? '—' }}</span>
              </template>
            </v-list-item>
            <v-list-item>
              <template #prepend>
                <v-icon size="small" color="medium-emphasis">mdi-calendar-alert</v-icon>
              </template>
              <template #title><span class="text-caption">Fecha Requerida</span></template>
              <template #subtitle>
                <span class="text-body-2">{{ requirement.required_date ?? '—' }}</span>
              </template>
            </v-list-item>
            <v-list-item>
              <template #prepend>
                <v-icon size="small" color="medium-emphasis">mdi-calendar-plus</v-icon>
              </template>
              <template #title><span class="text-caption">Fecha de Creación</span></template>
              <template #subtitle>
                <span class="text-body-2">{{ requirement.created_at }}</span>
              </template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Justificación -->
        <v-card
          v-if="requirement.justification"
          variant="outlined"
          rounded="lg"
        >
          <v-card-title class="text-subtitle-1 font-weight-bold d-flex align-center pa-4 pb-3">
            <v-icon start color="primary">mdi-text-box-outline</v-icon>
            Justificación
          </v-card-title>
          <v-divider />
          <v-card-text class="text-body-2">
            {{ requirement.justification }}
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>
