<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  obligations:  Object,
  filters:      Object,
  counts:       Object,
  totalPending: [Number, String],
})

const activeStatus = ref(props.filters?.status ?? 'pendiente')

const headers = [
  { title: 'Factura',     key: 'invoice_number',          width: '140px' },
  { title: 'Proveedor',   key: 'supplier'                                 },
  { title: 'OC',          key: 'purchase_order',           width: '130px' },
  { title: 'Monto',       key: 'amount',                   width: '140px', align: 'end' },
  { title: 'Vencimiento', key: 'due_date',                 width: '130px' },
  { title: 'Estado',      key: 'status',                   width: '120px' },
  { title: 'Acciones',    key: 'actions',                  width: '100px', sortable: false },
]

const tabs = [
  { label: 'Pendientes', value: 'pendiente', color: 'warning', icon: 'mdi-clock-outline' },
  { label: 'Pagadas',    value: 'pagado',    color: 'success', icon: 'mdi-check-circle'  },
  { label: 'Todas',      value: 'todos',     color: 'primary', icon: 'mdi-view-list'     },
]

function switchTab(val) {
  activeStatus.value = val
  router.get('/payments', { status: val }, { preserveState: true, replace: true })
}

function goToPage(page) {
  router.get('/payments', {
    page, status: activeStatus.value,
  }, { preserveState: true })
}

function isDueSoon(date) {
  if (!date) return false
  const days = Math.ceil((new Date(date) - new Date()) / 86400000)
  return days >= 0 && days <= 7
}

function isOverdue(date, status) {
  if (status === 'pagado') return false
  return date && new Date(date) < new Date()
}

function currency(val, cur = 'PEN') {
  return `${cur} ${Number(val).toFixed(2)}`
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Finanzas — Obligaciones de Pago</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Gestión de pagos a proveedores (Pasos 16-19)
        </p>
      </div>
    </div>

    <!-- Cards resumen -->
    <v-row class="mb-4">
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg">
          <v-card-text class="d-flex align-center justify-space-between pa-4">
            <div>
              <p class="text-body-2 text-medium-emphasis">Por pagar</p>
              <p class="text-h5 font-weight-bold text-warning mt-1">
                S/ {{ Number(totalPending).toFixed(2) }}
              </p>
              <p class="text-caption text-medium-emphasis">
                {{ counts.pendiente }} obligacion(es) pendiente(s)
              </p>
            </div>
            <v-icon size="48" color="warning">mdi-clock-outline</v-icon>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg">
          <v-card-text class="d-flex align-center justify-space-between pa-4">
            <div>
              <p class="text-body-2 text-medium-emphasis">Pagadas</p>
              <p class="text-h5 font-weight-bold text-success mt-1">
                {{ counts.pagado }}
              </p>
              <p class="text-caption text-medium-emphasis">obligacion(es) completadas</p>
            </div>
            <v-icon size="48" color="success">mdi-check-circle-outline</v-icon>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg" color="primary">
          <v-card-text class="d-flex align-center justify-space-between pa-4">
            <div>
              <p class="text-body-2 text-white">Total obligaciones</p>
              <p class="text-h5 font-weight-bold text-white mt-1">
                {{ (counts.pendiente ?? 0) + (counts.pagado ?? 0) }}
              </p>
              <p class="text-caption text-white">registradas en el sistema</p>
            </div>
            <v-icon size="48" color="white">mdi-bank-transfer</v-icon>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Tabla con tabs de estado -->
    <v-card variant="outlined" rounded="lg">
      <div class="d-flex ga-1 pa-3 pb-0">
        <v-btn
          v-for="tab in tabs"
          :key="tab.value"
          :color="activeStatus === tab.value ? tab.color : 'default'"
          :variant="activeStatus === tab.value ? 'tonal' : 'text'"
          :prepend-icon="tab.icon"
          size="small"
          @click="switchTab(tab.value)"
        >
          {{ tab.label }}
          <v-chip
            v-if="tab.value !== 'todos'"
            size="x-small"
            class="ml-1"
            :color="tab.color"
          >
            {{ counts[tab.value] ?? 0 }}
          </v-chip>
        </v-btn>
      </div>
      <v-divider class="mt-2" />

      <v-data-table
        :headers="headers"
        :items="obligations.data"
        :items-per-page="obligations.per_page"
        hide-default-footer
        density="comfortable"
      >
        <template #item.invoice_number="{ item }">
          <span class="font-weight-medium text-body-2">
            {{ item.invoice?.series }}-{{ item.invoice?.number }}
          </span>
        </template>

        <template #item.supplier="{ item }">
          <span class="text-body-2">
            {{ item.invoice?.supplier?.business_name ?? '—' }}
          </span>
        </template>

        <template #item.purchase_order="{ item }">
          <span class="text-caption">
            {{ item.invoice?.purchase_order?.code ?? '—' }}
          </span>
        </template>

        <template #item.amount="{ item }">
          <span class="font-weight-bold"
            :class="item.status !== 'pagado' ? 'text-warning' : 'text-success'">
            {{ currency(item.amount, item.currency) }}
          </span>
        </template>

        <template #item.due_date="{ item }">
          <v-chip
            v-if="isOverdue(item.due_date, item.status)"
            color="error" size="small" label>
            <v-icon start size="12">mdi-alert</v-icon>
            Vencida
          </v-chip>
          <v-chip
            v-else-if="isDueSoon(item.due_date) && item.status !== 'pagado'"
            color="warning" size="small" label>
            {{ item.due_date }}
          </v-chip>
          <span v-else class="text-body-2">{{ item.due_date ?? '—' }}</span>
        </template>

        <template #item.status="{ item }">
          <v-chip :color="item.status_color" size="small" label>
            {{ item.status_label }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-btn icon="mdi-eye" variant="text" size="small" color="primary"
            :href="`/payment-obligations/${item.id}`" />
          <v-btn
            v-if="item.status === 'pendiente'"
            icon="mdi-cash-plus"
            variant="text" size="small" color="success"
            :href="`/payment-obligations/${item.id}/pay`"
          />
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-bank-transfer-out</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">Sin obligaciones de pago</p>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">
              {{ obligations.total }} obligacion(es)
            </span>
            <v-pagination
              v-if="obligations.last_page > 1"
              :model-value="obligations.current_page"
              :length="obligations.last_page"
              density="compact" total-visible="5"
              @update:model-value="goToPage"
            />
          </div>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>
