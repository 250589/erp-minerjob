<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  stocks:     Object,
  warehouses: Array,
  filters:    Object,
  totals:     Object,
})

const search      = ref(props.filters?.search || '')
const warehouseId = ref(props.filters?.warehouse_id ? Number(props.filters.warehouse_id) : null)

const headers = [
  { title: 'Almacén',    key: 'warehouse.name',    width: '160px' },
  { title: 'SKU',        key: 'product.sku',        width: '100px' },
  { title: 'Producto',   key: 'product.name'                       },
  { title: 'Unidad',     key: 'unit',               width: '80px', align: 'center' },
  { title: 'Cantidad',   key: 'quantity',            width: '100px', align: 'end' },
  { title: 'Costo Prom.',key: 'average_cost',        width: '120px', align: 'end' },
  { title: 'Valor Total',key: 'total_value',         width: '130px', align: 'end' },
  { title: 'P. Venta',   key: 'sale_price',          width: '120px', align: 'end' },
]

function applyFilters() {
  router.get('/stock', {
    search:       search.value || undefined,
    warehouse_id: warehouseId.value || undefined,
  }, { preserveState: true, replace: true })
}

function goToPage(page) {
  router.get('/stock', {
    page,
    search:       props.filters?.search || undefined,
    warehouse_id: props.filters?.warehouse_id || undefined,
  }, { preserveState: true })
}

function stockLevel(item) {
  const min = Number(item.product?.min_stock ?? 0)
  const qty = Number(item.quantity)
  if (qty === 0) return 'error'
  if (qty <= min) return 'warning'
  return 'success'
}

const totalStockValue = computed(() =>
  props.stocks.data.reduce((s, item) =>
    s + Number(item.quantity) * Number(item.average_cost), 0
  )
)
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Stock por Almacén</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Inventario vigente (actualizado por KardexService)
        </p>
      </div>
      <div class="d-flex ga-2">
        <v-btn variant="tonal" prepend-icon="mdi-book-open-outline" href="/kardex">
          Ver Kardex
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus"
          href="/warehouse-receptions/create">
          Registrar Recepción
        </v-btn>
      </div>
    </div>

    <!-- Resumen -->
    <v-row class="mb-4">
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg">
          <v-card-text class="d-flex align-center justify-space-between pa-4">
            <div>
              <p class="text-body-2 text-medium-emphasis">Productos en stock</p>
              <p class="text-h4 font-weight-bold text-primary mt-1">{{ stocks.total }}</p>
            </div>
            <v-icon size="48" color="primary">mdi-package-variant-closed</v-icon>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg">
          <v-card-text class="d-flex align-center justify-space-between pa-4">
            <div>
              <p class="text-body-2 text-medium-emphasis">Valor total (página)</p>
              <p class="text-h5 font-weight-bold text-success mt-1">
                S/ {{ totalStockValue.toFixed(2) }}
              </p>
            </div>
            <v-icon size="48" color="success">mdi-currency-usd</v-icon>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg">
          <v-card-text class="d-flex align-center justify-space-between pa-4">
            <div>
              <p class="text-body-2 text-medium-emphasis">Almacenes activos</p>
              <p class="text-h4 font-weight-bold mt-1">{{ warehouses.length }}</p>
            </div>
            <v-icon size="48" color="secondary">mdi-home-city-outline</v-icon>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Filtros -->
    <v-card variant="outlined" rounded="lg" class="mb-4">
      <v-card-text class="pb-2">
        <v-row dense align="center">
          <v-col cols="12" md="5">
            <v-text-field v-model="search" prepend-inner-icon="mdi-magnify"
              label="Buscar por producto o SKU..." variant="outlined"
              density="compact" hide-details clearable
              @keyup.enter="applyFilters" />
          </v-col>
          <v-col cols="12" md="4">
            <v-select v-model="warehouseId" :items="warehouses"
              item-title="name" item-value="id"
              label="Almacén" variant="outlined" density="compact"
              hide-details clearable @update:model-value="applyFilters" />
          </v-col>
          <v-col cols="12" md="3">
            <v-btn color="primary" variant="tonal" block @click="applyFilters">
              <v-icon start>mdi-filter</v-icon>Filtrar
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Tabla -->
    <v-card variant="outlined" rounded="lg">
      <v-data-table :headers="headers" :items="stocks.data"
        :items-per-page="stocks.per_page" hide-default-footer density="comfortable">

        <template #item.warehouse.name="{ item }">
          <v-chip
            :color="item.warehouse?.type === 'principal' ? 'primary' : 'secondary'"
            size="x-small" label class="mr-1">
            {{ item.warehouse?.type === 'principal' ? 'Principal' : 'Sub' }}
          </v-chip>
          {{ item.warehouse?.name }}
        </template>

        <template #item.product.sku="{ item }">
          <code class="text-caption">{{ item.product?.sku }}</code>
        </template>

        <template #item.unit="{ item }">
          <span class="text-caption">{{ item.product?.unit?.abbreviation }}</span>
        </template>

        <template #item.quantity="{ item }">
          <div class="d-flex align-center justify-end ga-1">
            <v-icon :color="stockLevel(item)" size="12">mdi-circle</v-icon>
            <span :class="`text-${stockLevel(item)} font-weight-bold`">
              {{ Number(item.quantity).toFixed(2) }}
            </span>
          </div>
        </template>

        <template #item.average_cost="{ item }">
          <span class="text-body-2">S/ {{ Number(item.average_cost).toFixed(4) }}</span>
        </template>

        <template #item.total_value="{ item }">
          <span class="font-weight-medium">
            S/ {{ (Number(item.quantity) * Number(item.average_cost)).toFixed(2) }}
          </span>
        </template>

        <template #item.sale_price="{ item }">
          <span class="text-success font-weight-medium">
            S/ {{ Number(item.product?.current_sale_price ?? 0).toFixed(2) }}
          </span>
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-package-variant-closed</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">Sin stock disponible</p>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">
              {{ stocks.total }} ítem(s) en stock
            </span>
            <v-pagination v-if="stocks.last_page > 1"
              :model-value="stocks.current_page" :length="stocks.last_page"
              density="compact" total-visible="5" @update:model-value="goToPage" />
          </div>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>
