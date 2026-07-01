<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ products: Object, categories: Array, filters: Object, counts: Object })

const search     = ref(props.filters?.search      || '')
const status     = ref(props.filters?.status      || '')
const categoryId = ref(props.filters?.category_id ? Number(props.filters.category_id) : null)

const headers = [
  { title: 'SKU',        key: 'sku',              width: '110px' },
  { title: 'Producto',   key: 'name'                             },
  { title: 'Categoría',  key: 'category.name',    width: '140px' },
  { title: 'Unidad',     key: 'unit.abbreviation',width: '80px', align: 'center' },
  { title: 'Margen %',   key: 'markup_percentage',width: '100px', align: 'end' },
  { title: 'P.Venta',    key: 'current_sale_price',width: '110px', align: 'end' },
  { title: 'Stock Mín.', key: 'min_stock',        width: '100px', align: 'end' },
  { title: 'Estado',     key: 'status',           width: '100px' },
  { title: 'Acciones',   key: 'actions',          width: '100px', sortable: false },
]

function applyFilters() {
  router.get('/products', {
    search:      search.value      || undefined,
    status:      status.value      || undefined,
    category_id: categoryId.value  || undefined,
  }, { preserveState: true, replace: true })
}

function goToPage(page) {
  router.get('/products', {
    page,
    search:      props.filters?.search      || undefined,
    status:      props.filters?.status      || undefined,
    category_id: props.filters?.category_id || undefined,
  }, { preserveState: true })
}

function deleteProduct(product) {
  if (confirm(`¿Eliminar el producto "${product.name}"?`)) {
    router.delete(`/products/${product.id}`)
  }
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Catálogo de Productos</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          {{ counts.activo }} activos · {{ counts.inactivo }} inactivos
        </p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" href="/products/create">
        Nuevo Producto
      </v-btn>
      <v-btn variant="tonal" prepend-icon="mdi-file-excel"
        href="/imports/products" class="mr-2">
        Importar Excel
      </v-btn>
    </div>

    <!-- Filtros -->
    <v-card variant="outlined" rounded="lg" class="mb-4">
      <v-card-text class="pb-2">
        <v-row dense align="center">
          <v-col cols="12" md="4">
            <v-text-field v-model="search" prepend-inner-icon="mdi-magnify"
              label="Buscar por nombre o SKU..." variant="outlined"
              density="compact" hide-details clearable @keyup.enter="applyFilters" />
          </v-col>
          <v-col cols="12" md="3">
            <v-select v-model="categoryId" :items="categories"
              item-title="name" item-value="id"
              label="Categoría" variant="outlined" density="compact"
              hide-details clearable @update:model-value="applyFilters" />
          </v-col>
          <v-col cols="12" md="2">
            <v-select v-model="status"
              :items="[{title:'Todos',value:''},{title:'Activos',value:'activo'},{title:'Inactivos',value:'inactivo'}]"
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
      <v-data-table :headers="headers" :items="products.data"
        :items-per-page="products.per_page" hide-default-footer density="comfortable">

        <template #item.sku="{ item }">
          <code class="text-caption">{{ item.sku }}</code>
        </template>

        <template #item.name="{ item }">
          <div>
            <div class="text-body-2 font-weight-medium">{{ item.name }}</div>
            <div v-if="item.description" class="text-caption text-medium-emphasis">
              {{ item.description?.slice(0, 50) }}{{ item.description?.length > 50 ? '...' : '' }}
            </div>
          </div>
        </template>

        <template #item.markup_percentage="{ item }">
          <span class="text-body-2">{{ item.markup_percentage }}%</span>
        </template>

        <template #item.current_sale_price="{ item }">
          <span class="font-weight-medium text-primary">
            {{ item.current_sale_price ? `S/ ${Number(item.current_sale_price).toFixed(2)}` : '—' }}
          </span>
        </template>

        <template #item.min_stock="{ item }">
          <span class="text-body-2">{{ Number(item.min_stock).toFixed(2) }}</span>
        </template>

        <template #item.status="{ item }">
          <v-chip :color="item.status_color" size="small" label>
            {{ item.status_label }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-btn icon="mdi-pencil" variant="text" size="small"
            color="primary" :href="`/products/${item.id}/edit`" />
          <v-btn icon="mdi-delete-outline" variant="text"
            size="small" color="error" @click="deleteProduct(item)" />
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-package-variant</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">Sin productos</p>
            <v-btn color="primary" prepend-icon="mdi-plus"
              href="/products/create" class="mt-3">
              Crear Producto
            </v-btn>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">
              {{ products.total }} producto(s)
            </span>
            <v-pagination v-if="products.last_page > 1"
              :model-value="products.current_page"
              :length="products.last_page" density="compact"
              total-visible="5" @update:model-value="goToPage" />
          </div>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>