<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  movements:  Object,
  warehouses: Array,
  products:   Array,
  filters:    Object,
})

const warehouseId = ref(props.filters?.warehouse_id ? Number(props.filters.warehouse_id) : null)
const productId   = ref(props.filters?.product_id   ? Number(props.filters.product_id)   : null)
const dateFrom    = ref(props.filters?.date_from || '')
const dateTo      = ref(props.filters?.date_to   || '')

const headers = [
  { title: 'Fecha',      key: 'movement_date',    width: '140px' },
  { title: 'Tipo',       key: 'movement_type',    width: '160px' },
  { title: 'Almacén',    key: 'warehouse.name',   width: '140px' },
  { title: 'Producto',   key: 'product.name'                     },
  { title: 'Cantidad',   key: 'quantity',          width: '100px', align: 'end' },
  { title: 'Costo Unit.',key: 'unit_cost',         width: '110px', align: 'end' },
  { title: 'Saldo Cant.',key: 'balance_quantity',  width: '110px', align: 'end' },
  { title: 'Saldo Valor',key: 'balance_value',     width: '120px', align: 'end' },
  { title: 'Registrado por', key: 'created_by.name', width: '140px' },
]

const movementColors = {
  ingreso_compra:   { color: 'success', icon: 'mdi-arrow-down-circle', label: 'Ingreso Compra' },
  entrada_traslado: { color: 'info',    icon: 'mdi-transfer-right',    label: 'Entrada Traslado' },
  salida_traslado:  { color: 'warning', icon: 'mdi-transfer-left',     label: 'Salida Traslado' },
  salida_entrega:   { color: 'error',   icon: 'mdi-arrow-up-circle',   label: 'Salida Entrega' },
  ajuste_positivo:  { color: 'success', icon: 'mdi-plus-circle',       label: 'Ajuste (+)' },
  ajuste_negativo:  { color: 'error',   icon: 'mdi-minus-circle',      label: 'Ajuste (-)' },
}

function applyFilters() {
  router.get('/kardex', {
    warehouse_id: warehouseId.value || undefined,
    product_id:   productId.value   || undefined,
    date_from:    dateFrom.value    || undefined,
    date_to:      dateTo.value      || undefined,
  }, { preserveState: true, replace: true })
}

function goToPage(page) {
  router.get('/kardex', {
    page,
    warehouse_id: props.filters?.warehouse_id || undefined,
    product_id:   props.filters?.product_id   || undefined,
    date_from:    props.filters?.date_from     || undefined,
    date_to:      props.filters?.date_to       || undefined,
  }, { preserveState: true })
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Kardex Valorizado</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Registro inmutable de todos los movimientos de inventario (Paso 27)
        </p>
      </div>
      <v-btn variant="tonal" prepend-icon="mdi-chart-bar" href="/stock">
        Ver Stock
      </v-btn>
    </div>

    <!-- Filtros -->
    <v-card variant="outlined" rounded="lg" class="mb-4">
      <v-card-text class="pb-2">
        <v-row dense align="center">
          <v-col cols="12" md="3">
            <v-select v-model="warehouseId" :items="warehouses"
              item-title="name" item-value="id" label="Almacén"
              variant="outlined" density="compact" hide-details clearable
              @update:model-value="applyFilters" />
          </v-col>
          <v-col cols="12" md="3">
            <v-select v-model="productId" :items="products"
              item-value="id" label="Producto"
              variant="outlined" density="compact" hide-details clearable
              @update:model-value="applyFilters">
              <template #item="{ item, props: p }">
                <v-list-item v-bind="p">
                  <template #title>{{ item.raw.name }}</template>
                  <template #subtitle>{{ item.raw.sku }}</template>
                </v-list-item>
              </template>
              <template #selection="{ item }">
                {{ item.raw.name }}
              </template>
            </v-select>
          </v-col>
          <v-col cols="6" md="2">
            <v-text-field v-model="dateFrom" label="Desde" type="date"
              variant="outlined" density="compact" hide-details />
          </v-col>
          <v-col cols="6" md="2">
            <v-text-field v-model="dateTo" label="Hasta" type="date"
              variant="outlined" density="compact" hide-details />
          </v-col>
          <v-col cols="12" md="2">
            <v-btn color="primary" variant="tonal" block @click="applyFilters">
              <v-icon start>mdi-filter</v-icon>Filtrar
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Tabla kardex -->
    <v-card variant="outlined" rounded="lg">
      <v-data-table :headers="headers" :items="movements.data"
        :items-per-page="movements.per_page" hide-default-footer density="compact">

        <template #item.movement_date="{ item }">
          <span class="text-caption">{{ item.movement_date }}</span>
        </template>

        <template #item.movement_type="{ item }">
          <v-chip
            :color="movementColors[item.movement_type]?.color ?? 'default'"
            size="small" label>
            <v-icon start size="12">
              {{ movementColors[item.movement_type]?.icon ?? 'mdi-circle' }}
            </v-icon>
            {{ movementColors[item.movement_type]?.label ?? item.movement_type }}
          </v-chip>
        </template>

        <template #item.product.name="{ item }">
          <div>
            <div class="text-body-2">{{ item.product?.name }}</div>
            <div class="text-caption text-medium-emphasis">{{ item.product?.sku }}</div>
          </div>
        </template>

        <template #item.quantity="{ item }">
          <span
            class="font-weight-bold"
            :class="Number(item.quantity) >= 0 ? 'text-success' : 'text-error'"
          >
            {{ Number(item.quantity) > 0 ? '+' : '' }}{{ Number(item.quantity).toFixed(4) }}
          </span>
        </template>

        <template #item.unit_cost="{ item }">
          <span class="text-body-2">S/ {{ Number(item.unit_cost).toFixed(4) }}</span>
        </template>

        <template #item.balance_quantity="{ item }">
          <span class="font-weight-medium">
            {{ Number(item.balance_quantity).toFixed(4) }}
          </span>
        </template>

        <template #item.balance_value="{ item }">
          <span class="font-weight-medium text-primary">
            S/ {{ Number(item.balance_value).toFixed(2) }}
          </span>
        </template>

        <template #item.created_by.name="{ item }">
          <span class="text-caption">{{ item.created_by?.name ?? '—' }}</span>
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-book-open-outline</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">Sin movimientos en el kardex</p>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">
              {{ movements.total }} movimiento(s)
            </span>
            <v-pagination v-if="movements.last_page > 1"
              :model-value="movements.current_page"
              :length="movements.last_page" density="compact"
              total-visible="5" @update:model-value="goToPage" />
          </div>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>
