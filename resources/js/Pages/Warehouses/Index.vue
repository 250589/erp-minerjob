<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ warehouses: Object, filters: Object })

const search = ref(props.filters?.search || '')
const type   = ref(props.filters?.type   || '')

const headers = [
  { title: 'Código',   key: 'code',          width: '110px' },
  { title: 'Nombre',   key: 'name'                          },
  { title: 'Tipo',     key: 'type',          width: '150px' },
  { title: 'Padre',    key: 'parent.name',   width: '160px' },
  { title: 'Responsable', key: 'manager.name', width: '150px' },
  { title: 'Productos',key: 'stocks_count',  width: '100px', align: 'center' },
  { title: 'Estado',   key: 'status',        width: '100px' },
  { title: 'Acciones', key: 'actions',       width: '100px', sortable: false },
]

const typeOptions = [
  { title: 'Todos',              value: '' },
  { title: 'Almacén Principal',  value: 'principal' },
  { title: 'Subalmacén',         value: 'subalmacen' },
  { title: 'Tránsito',           value: 'transito' },
]

function applyFilters() {
  router.get('/warehouses', {
    search: search.value || undefined,
    type:   type.value   || undefined,
  }, { preserveState: true, replace: true })
}

function goToPage(page) {
  router.get('/warehouses', {
    page,
    search: props.filters?.search || undefined,
    type:   props.filters?.type   || undefined,
  }, { preserveState: true })
}

function toggleStatus(warehouse) {
  const newStatus = warehouse.status === 'activo' ? 'inactivo' : 'activo'
  if (confirm(`¿${newStatus === 'inactivo' ? 'Desactivar' : 'Activar'} el almacén "${warehouse.name}"?`)) {
    router.delete(`/warehouses/${warehouse.id}`)
  }
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Gestión de Almacenes</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Estructura de almacenes y subalmacenes
        </p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" href="/warehouses/create">
        Nuevo Almacén
      </v-btn>
    </div>

    <v-card variant="outlined" rounded="lg" class="mb-4">
      <v-card-text class="pb-2">
        <v-row dense align="center">
          <v-col cols="12" md="5">
            <v-text-field v-model="search" prepend-inner-icon="mdi-magnify"
              label="Buscar por código o nombre..." variant="outlined"
              density="compact" hide-details clearable @keyup.enter="applyFilters" />
          </v-col>
          <v-col cols="12" md="4">
            <v-select v-model="type" :items="typeOptions" label="Tipo"
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
      <v-data-table :headers="headers" :items="warehouses.data"
        :items-per-page="warehouses.per_page" hide-default-footer density="comfortable">

        <template #item.name="{ item }">
          <div class="d-flex align-center ga-2">
            <v-icon :color="item.type_color" size="18">
              {{ item.type === 'principal' ? 'mdi-home-city' : item.type === 'subalmacen' ? 'mdi-home-map-marker' : 'mdi-truck' }}
            </v-icon>
            <span class="text-body-2 font-weight-medium">{{ item.name }}</span>
          </div>
        </template>

        <template #item.type="{ item }">
          <v-chip :color="item.type_color" size="small" label>
            {{ item.type_label }}
          </v-chip>
        </template>

        <template #item.parent.name="{ item }">
          <span class="text-caption">{{ item.parent?.name ?? '—' }}</span>
        </template>

        <template #item.manager.name="{ item }">
          <span class="text-caption">{{ item.manager?.name ?? '—' }}</span>
        </template>

        <template #item.stocks_count="{ item }">
          <v-chip size="x-small" :color="item.stocks_count > 0 ? 'primary' : 'default'">
            {{ item.stocks_count }}
          </v-chip>
        </template>

        <template #item.status="{ item }">
          <v-chip :color="item.status_color" size="small" label>
            {{ item.status === 'activo' ? 'Activo' : 'Inactivo' }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-btn icon="mdi-pencil" variant="text" size="small"
            color="primary" :href="`/warehouses/${item.id}/edit`" />
          <v-btn
            :icon="item.status === 'activo' ? 'mdi-toggle-switch' : 'mdi-toggle-switch-off'"
            variant="text" size="small"
            :color="item.status === 'activo' ? 'warning' : 'success'"
            @click="toggleStatus(item)" />
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-warehouse</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">Sin almacenes</p>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">
              {{ warehouses.total }} almacén(es)
            </span>
            <v-pagination v-if="warehouses.last_page > 1"
              :model-value="warehouses.current_page"
              :length="warehouses.last_page" density="compact"
              total-visible="5" @update:model-value="goToPage" />
          </div>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>