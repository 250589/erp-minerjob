<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ purchaseOrders: Object, filters: Object })

const search       = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')

const headers = [
  { title: 'Código',        key: 'code',              width: '130px' },
  { title: 'Proveedor',     key: 'supplier.business_name'             },
  { title: 'Requerimiento', key: 'requirement.code',   width: '140px' },
  { title: 'Total',         key: 'total',              width: '130px', align: 'end' },
  { title: 'Estado',        key: 'status',             width: '120px' },
  { title: 'Fecha',         key: 'created_at',         width: '130px' },
  { title: 'Acciones',      key: 'actions',            width: '100px', sortable: false },
]

const statusOptions = [
  { title: 'Todos',      value: '' },
  { title: 'Generada',   value: 'generada' },
  { title: 'Enviada',    value: 'enviada' },
  { title: 'Facturada',  value: 'facturada' },
  { title: 'Pagada',     value: 'pagada' },
  { title: 'Anulada',    value: 'anulada' },
]

function applyFilters() {
  router.get('/purchase-orders', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
  }, { preserveState: true, replace: true })
}
function goToPage(page) {
  router.get('/purchase-orders', { page, search: props.filters?.search || undefined, status: props.filters?.status || undefined }, { preserveState: true })
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Órdenes de Compra</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">OC generadas a partir de cotizaciones aprobadas</p>
      </div>
    </div>

    <v-card variant="outlined" rounded="lg" class="mb-4">
      <v-card-text class="pb-2">
        <v-row dense align="center">
          <v-col cols="12" md="6">
            <v-text-field v-model="search" prepend-inner-icon="mdi-magnify"
              label="Buscar por código OC..." variant="outlined" density="compact"
              hide-details clearable @keyup.enter="applyFilters" />
          </v-col>
          <v-col cols="12" md="4">
            <v-select v-model="statusFilter" :items="statusOptions" label="Estado"
              variant="outlined" density="compact" hide-details
              @update:model-value="applyFilters" />
          </v-col>
          <v-col cols="12" md="2">
            <v-btn color="primary" variant="tonal" block @click="applyFilters">
              <v-icon start>mdi-filter</v-icon>Filtrar
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card variant="outlined" rounded="lg">
      <v-data-table :headers="headers" :items="purchaseOrders.data"
        :items-per-page="purchaseOrders.per_page" hide-default-footer density="comfortable">

        <template #item.total="{ item }">
          <span class="font-weight-medium">
            {{ item.currency }} {{ Number(item.total).toFixed(2) }}
          </span>
        </template>

        <template #item.status="{ item }">
          <v-chip :color="item.status_color" size="small" label>
            {{ item.status_label }}
          </v-chip>
        </template>

        <template #item.created_at="{ item }">
          <span class="text-caption">{{ item.created_at }}</span>
        </template>

        <template #item.actions="{ item }">
          <v-btn icon="mdi-eye" variant="text" size="small" color="primary"
            :href="`/purchase-orders/${item.id}`" />
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-cart-outline</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">Sin órdenes de compra</p>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">{{ purchaseOrders.total }} OC(s)</span>
            <v-pagination v-if="purchaseOrders.last_page > 1"
              :model-value="purchaseOrders.current_page" :length="purchaseOrders.last_page"
              density="compact" total-visible="5" @update:model-value="goToPage" />
          </div>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>
