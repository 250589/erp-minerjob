<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ invoices: Object, filters: Object })

const search       = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')

const headers = [
  { title: 'Comprobante',  key: 'full_number',        width: '150px' },
  { title: 'Proveedor',    key: 'supplier.business_name'              },
  { title: 'OC',           key: 'purchase_order.code', width: '130px' },
  { title: 'F. Emisión',   key: 'issue_date',          width: '120px' },
  { title: 'Total',        key: 'total',               width: '130px', align: 'end' },
  { title: 'Estado',       key: 'status',              width: '130px' },
  { title: 'Acciones',     key: 'actions',             width: '100px', sortable: false },
]

const statusOptions = [
  { title: 'Todos',        value: '' },
  { title: 'Recibida',     value: 'recibida' },
  { title: 'En Revisión',  value: 'en_revision' },
  { title: 'Observada',    value: 'observada' },
  { title: 'Validada',     value: 'validada' },
  { title: 'Registrada',   value: 'registrada' },
  { title: 'Pagada',       value: 'pagada' },
]

function applyFilters() {
  router.get('/invoices', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
  }, { preserveState: true, replace: true })
}

function goToPage(page) {
  router.get('/invoices', {
    page, search: props.filters?.search || undefined, status: props.filters?.status || undefined,
  }, { preserveState: true })
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Facturas</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Comprobantes recibidos de proveedores (Pasos 10-15)
        </p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" href="/invoices/create">
        Registrar Factura
      </v-btn>
    </div>

    <v-card variant="outlined" rounded="lg" class="mb-4">
      <v-card-text class="pb-2">
        <v-row dense align="center">
          <v-col cols="12" md="5">
            <v-text-field v-model="search" prepend-inner-icon="mdi-magnify"
              label="Buscar por serie o número..." variant="outlined" density="compact"
              hide-details clearable @keyup.enter="applyFilters" />
          </v-col>
          <v-col cols="12" md="4">
            <v-select v-model="statusFilter" :items="statusOptions" label="Estado"
              variant="outlined" density="compact" hide-details
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
      <v-data-table :headers="headers" :items="invoices.data"
        :items-per-page="invoices.per_page" hide-default-footer density="comfortable">

        <template #item.full_number="{ item }">
          <span class="font-weight-medium">{{ item.full_number }}</span>
        </template>

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

        <template #item.actions="{ item }">
          <v-btn icon="mdi-eye" variant="text" size="small" color="primary"
            :href="`/invoices/${item.id}`" />
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-file-document-outline</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">Sin facturas registradas</p>
            <v-btn color="primary" prepend-icon="mdi-plus" href="/invoices/create" class="mt-3">
              Registrar Primera Factura
            </v-btn>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">{{ invoices.total }} factura(s)</span>
            <v-pagination v-if="invoices.last_page > 1"
              :model-value="invoices.current_page" :length="invoices.last_page"
              density="compact" total-visible="5" @update:model-value="goToPage" />
          </div>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>
