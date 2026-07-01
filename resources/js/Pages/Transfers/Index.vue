<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ transfers: Object, filters: Object })

const search       = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')

const headers = [
  { title: 'Código',   key: 'code',                         width: '130px' },
  { title: 'Origen',   key: 'origin_warehouse.name',        width: '160px' },
  { title: 'Destino',  key: 'destination_warehouse.name',   width: '160px' },
  { title: 'Solicitado por', key: 'requested_by.name'                       },
  { title: 'Fecha',    key: 'created_at',                   width: '130px' },
  { title: 'Estado',   key: 'status',                       width: '130px' },
  { title: 'Acción',   key: 'actions',                      width: '80px', sortable: false },
]

const statusOptions = [
  { title: 'Todos',        value: '' },
  { title: 'Creada',       value: 'creada' },
  { title: 'En Tránsito',  value: 'en_transito' },
  { title: 'Recibida',     value: 'recibida' },
  { title: 'Rechazada',    value: 'rechazada' },
]

function applyFilters() {
  router.get('/transfers', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
  }, { preserveState: true, replace: true })
}

function goToPage(page) {
  router.get('/transfers', {
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
        <h1 class="text-h5 font-weight-bold">Traslados entre Almacenes</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Movimientos de mercadería entre almacenes y subalmacenes (Pasos 28-34)
        </p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" href="/transfers/create">
        Nueva Orden de Traslado
      </v-btn>
    </div>

    <v-card variant="outlined" rounded="lg" class="mb-4">
      <v-card-text class="pb-2">
        <v-row dense align="center">
          <v-col cols="12" md="5">
            <v-text-field v-model="search" prepend-inner-icon="mdi-magnify"
              label="Buscar por código TR..." variant="outlined"
              density="compact" hide-details clearable
              @keyup.enter="applyFilters" />
          </v-col>
          <v-col cols="12" md="4">
            <v-select v-model="statusFilter" :items="statusOptions"
              label="Estado" variant="outlined" density="compact"
              hide-details @update:model-value="applyFilters" />
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
      <v-data-table :headers="headers" :items="transfers.data"
        :items-per-page="transfers.per_page" hide-default-footer density="comfortable">

        <template #item.origin_warehouse.name="{ item }">
          <div class="d-flex align-center ga-1">
            <v-icon size="14" color="primary">mdi-home-city</v-icon>
            <span class="text-body-2">{{ item.origin_warehouse?.name }}</span>
          </div>
        </template>

        <template #item.destination_warehouse.name="{ item }">
          <div class="d-flex align-center ga-1">
            <v-icon size="14" color="secondary">mdi-home-map-marker</v-icon>
            <span class="text-body-2">{{ item.destination_warehouse?.name }}</span>
          </div>
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
          <v-btn icon="mdi-eye" variant="text" size="small"
            color="primary" :href="`/transfers/${item.id}`" />
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-transfer</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">Sin traslados registrados</p>
            <v-btn color="primary" prepend-icon="mdi-plus"
              href="/transfers/create" class="mt-3">
              Crear Traslado
            </v-btn>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">
              {{ transfers.total }} traslado(s)
            </span>
            <v-pagination v-if="transfers.last_page > 1"
              :model-value="transfers.current_page"
              :length="transfers.last_page" density="compact"
              total-visible="5" @update:model-value="goToPage" />
          </div>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>
