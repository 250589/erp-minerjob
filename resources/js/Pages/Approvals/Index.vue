<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  approvals: Object,
  filters:   Object,
  counts:    Object,
})

const statusFilter = ref(props.filters?.status || 'pendiente')

const headers = [
  { title: 'Cotización',    key: 'quote_code',     width: '130px' },
  { title: 'Requerimiento', key: 'req_code',        width: '140px' },
  { title: 'Proveedor',     key: 'supplier'                        },
  { title: 'Total',         key: 'total',           width: '140px', align: 'end' },
  { title: 'Solicitado por',key: 'requested_by',    width: '150px' },
  { title: 'Fecha',         key: 'created_at',      width: '130px' },
  { title: 'Estado',        key: 'status',          width: '120px' },
  { title: 'Acción',        key: 'actions',         width: '80px',  sortable: false },
]

const statusOptions = [
  { title: 'Pendientes',  value: 'pendiente' },
  { title: 'Aprobadas',   value: 'aprobado' },
  { title: 'Rechazadas',  value: 'rechazado' },
]

function applyFilter(val) {
  statusFilter.value = val
  router.get('/approvals', { status: val }, { preserveState: true, replace: true })
}

function goToPage(page) {
  router.get('/approvals', {
    page, status: statusFilter.value,
  }, { preserveState: true })
}

// Extraer datos del polimorfismo anidado
function quoteCode(item)    { return item.approvable?.code ?? '—' }
function reqCode(item)      { return item.approvable?.quote_request?.requirement?.code ?? '—' }
function supplierName(item) { return item.approvable?.supplier?.business_name ?? '—' }
function totalAmt(item) {
  const q = item.approvable
  if (!q) return '—'
  return `${q.currency} ${Number(q.total).toFixed(2)}`
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Aprobaciones Gerenciales</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Evaluación y aprobación de compras (Pasos 6-7)
        </p>
      </div>
    </div>

    <!-- Contadores por estado -->
    <v-row class="mb-4">
      <v-col v-for="opt in statusOptions" :key="opt.value" cols="12" md="4">
        <v-card
          variant="outlined"
          rounded="lg"
          :class="statusFilter === opt.value ? 'border-primary' : ''"
          class="cursor-pointer"
          @click="applyFilter(opt.value)"
        >
          <v-card-text class="d-flex align-center justify-space-between pa-4">
            <div>
              <p class="text-body-2 text-medium-emphasis">{{ opt.title }}</p>
              <p class="text-h4 font-weight-bold mt-1">
                {{ counts[opt.value] ?? 0 }}
              </p>
            </div>
            <v-icon
              size="40"
              :color="opt.value === 'pendiente' ? 'warning' : opt.value === 'aprobado' ? 'success' : 'error'"
            >
              {{
                opt.value === 'pendiente' ? 'mdi-clock-outline'
                : opt.value === 'aprobado' ? 'mdi-check-circle-outline'
                : 'mdi-close-circle-outline'
              }}
            </v-icon>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Tabla -->
    <v-card variant="outlined" rounded="lg">
      <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
        <v-icon start color="primary">mdi-list-status</v-icon>
        {{ statusOptions.find(o => o.value === statusFilter)?.title }}
      </v-card-title>
      <v-divider />

      <v-data-table
        :headers="headers"
        :items="approvals.data"
        :items-per-page="approvals.per_page"
        hide-default-footer
        density="comfortable"
      >
        <template #item.quote_code="{ item }">
          <span class="font-weight-medium">{{ quoteCode(item) }}</span>
        </template>

        <template #item.req_code="{ item }">
          <span class="text-body-2">{{ reqCode(item) }}</span>
        </template>

        <template #item.supplier="{ item }">
          <span class="text-body-2">{{ supplierName(item) }}</span>
        </template>

        <template #item.total="{ item }">
          <span class="font-weight-medium">{{ totalAmt(item) }}</span>
        </template>

        <template #item.requested_by="{ item }">
          <span class="text-body-2">{{ item.requested_by?.name ?? '—' }}</span>
        </template>

        <template #item.created_at="{ item }">
          <span class="text-caption">{{ item.created_at }}</span>
        </template>

        <template #item.status="{ item }">
          <v-chip :color="item.status_color" size="small" label>
            {{ item.status_label }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-tooltip
            :text="item.status === 'pendiente' ? 'Evaluar' : 'Ver detalle'"
            location="top"
          >
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                :icon="item.status === 'pendiente' ? 'mdi-gavel' : 'mdi-eye'"
                variant="text"
                size="small"
                :color="item.status === 'pendiente' ? 'warning' : 'primary'"
                :href="`/approvals/${item.id}`"
              />
            </template>
          </v-tooltip>
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-check-all</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">
              Sin aprobaciones {{ statusOptions.find(o => o.value === statusFilter)?.title?.toLowerCase() }}
            </p>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">
              {{ approvals.total }} registro(s)
            </span>
            <v-pagination
              v-if="approvals.last_page > 1"
              :model-value="approvals.current_page"
              :length="approvals.last_page"
              density="compact"
              total-visible="5"
              @update:model-value="goToPage"
            />
          </div>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>
