<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ receptions: Object, filters: Object })

const search       = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')

const headers = [
  { title: 'Código',   key: 'code',                  width: '130px' },
  { title: 'OC',       key: 'purchase_order.code',   width: '130px' },
  { title: 'Almacén',  key: 'warehouse.name'                        },
  { title: 'Recibido por', key: 'received_by.name',  width: '150px' },
  { title: 'Fecha',    key: 'received_at',            width: '130px' },
  { title: 'Estado',   key: 'status',                 width: '120px' },
  { title: 'Acción',   key: 'actions',                width: '80px', sortable: false },
]

const statusOptions = [
  { title: 'Todos',      value: '' },
  { title: 'Completa',   value: 'completa' },
  { title: 'Parcial',    value: 'parcial' },
  { title: 'Observada',  value: 'observada' },
]

function applyFilters() {
  router.get('/warehouse-receptions', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
  }, { preserveState: true, replace: true })
}

function goToPage(page) {
  router.get('/warehouse-receptions', {
    page,
    search: props.filters?.search || undefined,
    status: props.filters?.status || undefined,
  }, { preserveState: true })
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Recepciones de Almacén</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Registro de mercadería recibida de proveedores (Pasos 20-27)
        </p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus"
        href="/warehouse-receptions/create">
        Nueva Recepción
      </v-btn>
    </div>

    <v-card variant="outlined" rounded="lg" class="mb-4">
      <v-card-text class="pb-2">
        <v-row dense align="center">
          <v-col cols="12" md="5">
            <v-text-field v-model="search" prepend-inner-icon="mdi-magnify"
              label="Buscar por código REC..." variant="outlined"
              density="compact" hide-details clearable
              @keyup.enter="applyFilters" />
          </v-col>
          <v-col cols="12" md="4">
            <v-select v-model="statusFilter" :items="statusOptions"
              label="Estado" variant="outlined" density="compact" hide-details
              @update:model-value="applyFilters" />
          </v-col>
          <v-col cols="12" md="3">
            <v-btn color="primary" variant="tonal" block @click="applyFilters">
              <v-icon start>mdi-filter</v-icon>Filtrar
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card variant="outlined" rounded="lg">
      <v-data-table :headers="headers" :items="receptions.data"
        :items-per-page="receptions.per_page" hide-default-footer density="comfortable">

        <template #item.received_at="{ item }">
          <span class="text-caption">{{ item.received_at }}</span>
        </template>

        <template #item.status="{ item }">
          <v-chip :color="item.status_color" size="small" label>
            {{ item.status_label }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-btn icon="mdi-eye" variant="text" size="small" color="primary"
            :href="`/warehouse-receptions/${item.id}`" />
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-warehouse</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">Sin recepciones registradas</p>
            <v-btn color="primary" prepend-icon="mdi-plus"
              href="/warehouse-receptions/create" class="mt-3">
              Registrar Primera Recepción
            </v-btn>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">
              {{ receptions.total }} recepcion(es)
            </span>
            <v-pagination v-if="receptions.last_page > 1"
              :model-value="receptions.current_page"
              :length="receptions.last_page" density="compact"
              total-visible="5" @update:model-value="goToPage" />
          </div>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>
