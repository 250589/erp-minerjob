<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ notes: Object, filters: Object, counts: Object })

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')

const headers = [
  { title: 'Código',      key: 'code',              width: '130px' },
  { title: 'Almacén',     key: 'warehouse.name',    width: '160px' },
  { title: 'Área',        key: 'area.name',         width: '140px' },
  { title: 'Solicitado por', key: 'requested_by.name'              },
  { title: 'Fecha',       key: 'created_at',        width: '130px' },
  { title: 'Estado',      key: 'status',            width: '120px' },
  { title: 'Acción',      key: 'actions',           width: '80px', sortable: false },
]

const tabs = [
  { label: 'Pendientes', value: 'borrador',  color: 'warning', icon: 'mdi-clock-outline'  },
  { label: 'Entregadas', value: 'entregada', color: 'success', icon: 'mdi-check-circle'   },
  { label: 'Todas',      value: '',          color: 'primary', icon: 'mdi-view-list'      },
]

function switchTab(val) {
  statusFilter.value = val
  router.get('/deliveries', { status: val || undefined }, { preserveState: true, replace: true })
}

function applyFilters() {
  router.get('/deliveries', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
  }, { preserveState: true, replace: true })
}

function goToPage(page) {
  router.get('/deliveries', {
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
        <h1 class="text-h5 font-weight-bold">Entregas al Personal</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Notas de entrega de materiales al personal (Pasos 38-41)
        </p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" href="/deliveries/create">
        Nueva Nota de Entrega
      </v-btn>
    </div>

    <!-- Resumen -->
    <v-row class="mb-4">
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg">
          <v-card-text class="d-flex align-center justify-space-between pa-4">
            <div>
              <p class="text-body-2 text-medium-emphasis">Pendientes de entrega</p>
              <p class="text-h4 font-weight-bold text-warning mt-1">{{ counts.borrador }}</p>
            </div>
            <v-icon size="48" color="warning">mdi-package-variant</v-icon>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg">
          <v-card-text class="d-flex align-center justify-space-between pa-4">
            <div>
              <p class="text-body-2 text-medium-emphasis">Entregadas</p>
              <p class="text-h4 font-weight-bold text-success mt-1">{{ counts.entregada }}</p>
            </div>
            <v-icon size="48" color="success">mdi-check-circle-outline</v-icon>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg" color="primary">
          <v-card-text class="d-flex align-center justify-space-between pa-4">
            <div>
              <p class="text-body-2 text-white">Total notas</p>
              <p class="text-h4 font-weight-bold text-white mt-1">
                {{ (counts.borrador ?? 0) + (counts.entregada ?? 0) }}
              </p>
            </div>
            <v-icon size="48" color="white">mdi-file-document-multiple</v-icon>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card variant="outlined" rounded="lg">
      <!-- Tabs de estado -->
      <div class="d-flex ga-1 pa-3 pb-0">
        <v-btn
          v-for="tab in tabs" :key="tab.value"
          :color="statusFilter === tab.value ? tab.color : 'default'"
          :variant="statusFilter === tab.value ? 'tonal' : 'text'"
          :prepend-icon="tab.icon" size="small"
          @click="switchTab(tab.value)">
          {{ tab.label }}
          <v-chip v-if="tab.value" size="x-small" class="ml-1" :color="tab.color">
            {{ counts[tab.value] ?? 0 }}
          </v-chip>
        </v-btn>
        <v-spacer />
        <v-text-field v-model="search" placeholder="Buscar NE-..."
          variant="outlined" density="compact" hide-details
          prepend-inner-icon="mdi-magnify" style="max-width:200px"
          @keyup.enter="applyFilters" />
      </div>
      <v-divider class="mt-2" />

      <v-data-table :headers="headers" :items="notes.data"
        :items-per-page="notes.per_page" hide-default-footer density="comfortable">

        <template #item.warehouse.name="{ item }">
          <span class="text-body-2">{{ item.warehouse?.name ?? '—' }}</span>
        </template>

        <template #item.area.name="{ item }">
          <span class="text-body-2">{{ item.area?.name ?? '—' }}</span>
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
            color="primary" :href="`/deliveries/${item.id}`" />
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-package-variant-closed</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">Sin notas de entrega</p>
            <v-btn color="primary" prepend-icon="mdi-plus"
              href="/deliveries/create" class="mt-3">
              Crear Primera Nota
            </v-btn>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">{{ notes.total }} nota(s)</span>
            <v-pagination v-if="notes.last_page > 1"
              :model-value="notes.current_page" :length="notes.last_page"
              density="compact" total-visible="5" @update:model-value="goToPage" />
          </div>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>