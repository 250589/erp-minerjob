<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  suppliers: Object,
  filters: Object,
})

const search       = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')

const headers = [
  { title: 'Razón Social',  key: 'business_name'   },
  { title: 'Nombre Comercial', key: 'trade_name', width: '180px' },
  { title: 'RUC',           key: 'tax_id',  width: '120px' },
  { title: 'Teléfono',      key: 'phone',   width: '120px' },
  { title: 'Contactos',     key: 'contacts_count', align: 'center', width: '100px' },
  { title: 'Estado',        key: 'status',  width: '110px' },
  { title: 'Acciones',      key: 'actions', width: '120px', sortable: false },
]

const statusOptions = [
  { title: 'Todos', value: '' },
  { title: 'Activo', value: 'activo' },
  { title: 'Inactivo', value: 'inactivo' },
]

function applyFilters() {
  router.get('/suppliers', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
  }, { preserveState: true, replace: true })
}

function clearFilters() {
  search.value = ''
  statusFilter.value = ''
  router.get('/suppliers', {}, { replace: true })
}

function goToPage(page) {
  router.get('/suppliers', {
    page,
    search: props.filters?.search || undefined,
    status: props.filters?.status || undefined,
  }, { preserveState: true })
}

function confirmDelete(id, name) {
  if (confirm(`¿Eliminar al proveedor "${name}"? Esta acción no se puede deshacer.`)) {
    router.delete(`/suppliers/${id}`)
  }
}

function toggleStatus(id) {
  router.patch(`/suppliers/${id}/toggle-status`)
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Proveedores</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Directorio de proveedores registrados
        </p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" href="/suppliers/create">
        Nuevo Proveedor
      </v-btn>
    </div>

    <!-- Filtros -->
    <v-card variant="outlined" rounded="lg" class="mb-4">
      <v-card-text class="pb-2">
        <v-row dense align="center">
          <v-col cols="12" md="6">
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="Buscar por razón social, nombre comercial o RUC..."
              variant="outlined"
              density="compact"
              hide-details
              clearable
              @keyup.enter="applyFilters"
              @click:clear="clearFilters"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="statusFilter"
              :items="statusOptions"
              label="Estado"
              variant="outlined"
              density="compact"
              hide-details
              @update:model-value="applyFilters"
            />
          </v-col>
          <v-col cols="6" md="2">
            <v-btn color="primary" variant="tonal" block @click="applyFilters">
              <v-icon start>mdi-filter</v-icon>Filtrar
            </v-btn>
          </v-col>
          <v-col cols="6" md="1">
            <v-btn variant="text" block @click="clearFilters">Limpiar</v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Tabla -->
    <v-card variant="outlined" rounded="lg">
      <v-data-table
        :headers="headers"
        :items="suppliers.data"
        :items-per-page="suppliers.per_page"
        hide-default-footer
        density="comfortable"
      >
        <template #item.trade_name="{ item }">
          {{ item.trade_name ?? '—' }}
        </template>

        <template #item.phone="{ item }">
          {{ item.phone ?? '—' }}
        </template>

        <template #item.contacts_count="{ item }">
          <v-chip size="x-small" color="primary" variant="tonal">
            {{ item.contacts_count }}
          </v-chip>
        </template>

        <template #item.status="{ item }">
          <v-chip
            :color="item.status === 'activo' ? 'success' : 'default'"
            size="small"
            label
          >
            {{ item.status === 'activo' ? 'Activo' : 'Inactivo' }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-tooltip text="Ver detalle" location="top">
            <template #activator="{ props }">
              <v-btn v-bind="props" icon="mdi-eye" variant="text"
                size="small" color="primary" :href="`/suppliers/${item.id}`" />
            </template>
          </v-tooltip>

          <v-tooltip text="Editar" location="top">
            <template #activator="{ props }">
              <v-btn v-bind="props" icon="mdi-pencil" variant="text"
                size="small" color="warning" :href="`/suppliers/${item.id}/edit`" />
            </template>
          </v-tooltip>

          <v-tooltip text="Eliminar" location="top">
            <template #activator="{ props }">
              <v-btn v-bind="props" icon="mdi-delete" variant="text"
                size="small" color="error"
                @click="confirmDelete(item.id, item.business_name)" />
            </template>
          </v-tooltip>
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-truck-outline</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">Sin proveedores</p>
            <v-btn color="primary" prepend-icon="mdi-plus" href="/suppliers/create" class="mt-3">
              Registrar Proveedor
            </v-btn>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">
              {{ suppliers.total }} proveedor(es)
            </span>
            <v-pagination
              v-if="suppliers.last_page > 1"
              :model-value="suppliers.current_page"
              :length="suppliers.last_page"
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
