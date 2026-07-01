<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  requirements: Object,
  filters: Object,
})

const search       = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')

const headers = [
  { title: 'Código',         key: 'code',            width: '130px' },
  { title: 'Solicitante',    key: 'requester.name'                  },
  { title: 'Área',           key: 'area.name'                       },
  { title: 'Fecha Requerida',key: 'required_date',   width: '150px' },
  { title: 'Estado',         key: 'status',           width: '160px' },
  { title: 'Acciones',       key: 'actions',          width: '120px', sortable: false },
]

const statusOptions = [
  { title: 'Todos los estados', value: '' },
  { title: 'Pendiente',         value: 'pendiente' },
  { title: 'En Cotización',     value: 'en_cotizacion' },
  { title: 'Aprobado',          value: 'aprobado' },
  { title: 'Rechazado',         value: 'rechazado' },
  { title: 'Convertido OC',     value: 'convertido_oc' },
  { title: 'Completado',        value: 'completado' },
]

function applyFilters() {
  router.get('/requirements', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
  }, { preserveState: true, replace: true })
}

function goToPage(page) {
  router.get('/requirements', {
    page,
    search: props.filters?.search || undefined,
    status: props.filters?.status || undefined,
  }, { preserveState: true })
}

function confirmDelete(id) {
  if (confirm('¿Está seguro de eliminar este requerimiento? Esta acción no se puede deshacer.')) {
    router.delete(`/requirements/${id}`)
  }
}

function clearFilters() {
  search.value = ''
  statusFilter.value = ''
  router.get('/requirements', {}, { replace: true })
}
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Requerimientos</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Gestión de solicitudes de materiales e insumos
        </p>
      </div>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        href="/requirements/create"
      >
        Nuevo Requerimiento
      </v-btn>
    </div>

    <!-- Filters -->
    <v-card variant="outlined" class="mb-4" rounded="lg">
      <v-card-text class="pb-2">
        <v-row dense align="center">
          <v-col cols="12" md="5">
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="Buscar por código..."
              variant="outlined"
              density="compact"
              hide-details
              clearable
              @keyup.enter="applyFilters"
              @click:clear="clearFilters"
            />
          </v-col>
          <v-col cols="12" md="4">
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
            <v-btn variant="text" block @click="clearFilters">
              Limpiar
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Table -->
    <v-card variant="outlined" rounded="lg">
      <v-data-table
        :headers="headers"
        :items="requirements.data"
        :items-per-page="requirements.per_page"
        hide-default-footer
        density="comfortable"
      >
        <!-- Status chip -->
        <template #item.status="{ item }">
          <v-chip
            :color="item.status_color"
            size="small"
            label
          >
            {{ item.status_label }}
          </v-chip>
        </template>

        <!-- Required date -->
        <template #item.required_date="{ item }">
          <span class="text-body-2">{{ item.required_date ?? '—' }}</span>
        </template>

        <!-- Area -->
        <template #item.area.name="{ item }">
          <span class="text-body-2">{{ item.area?.name ?? '—' }}</span>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <v-tooltip text="Ver detalle" location="top">
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                icon="mdi-eye"
                variant="text"
                size="small"
                color="primary"
                :href="`/requirements/${item.id}`"
              />
            </template>
          </v-tooltip>

          <v-tooltip
            v-if="['pendiente', 'rechazado'].includes(item.status)"
            text="Editar"
            location="top"
          >
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                icon="mdi-pencil"
                variant="text"
                size="small"
                color="warning"
                :href="`/requirements/${item.id}/edit`"
              />
            </template>
          </v-tooltip>

          <v-tooltip
            v-if="item.status === 'pendiente'"
            text="Eliminar"
            location="top"
          >
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                icon="mdi-delete"
                variant="text"
                size="small"
                color="error"
                @click="confirmDelete(item.id)"
              />
            </template>
          </v-tooltip>
        </template>

        <!-- Empty state -->
        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-clipboard-list-outline</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">Sin requerimientos</p>
            <p class="text-body-2 text-medium-emphasis mb-4">
              No se encontraron requerimientos con los filtros aplicados.
            </p>
            <v-btn color="primary" prepend-icon="mdi-plus" href="/requirements/create">
              Crear Requerimiento
            </v-btn>
          </div>
        </template>

        <!-- Pagination footer -->
        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">
              {{ requirements.total }} requerimiento(s) encontrado(s)
            </span>
            <v-pagination
              v-if="requirements.last_page > 1"
              :model-value="requirements.current_page"
              :length="requirements.last_page"
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
