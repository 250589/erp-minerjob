<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ users: Object, roles: Array, filters: Object })

const search = ref(props.filters?.search || '')
const roleFilter = ref(props.filters?.role || '')

const headers = [
  { title: 'Usuario',  key: 'name'            },
  { title: 'Email',    key: 'email'            },
  { title: 'Área',     key: 'area',  width: '140px' },
  { title: 'Rol',      key: 'role',  width: '160px' },
  { title: 'Estado',   key: 'status',width: '110px' },
  { title: 'Acciones', key: 'actions',width: '100px', sortable: false },
]

const roleColors = {
  'Administrador':    'error',
  'Solicitante':      'primary',
  'Compras':          'info',
  'Gerencia':         'warning',
  'Contabilidad':     'success',
  'Finanzas':         'teal',
  'Almacen_Principal':'indigo',
  'Traslados':        'purple',
  'Subalmacen':       'cyan',
  'Consumo_Final':    'green',
}

function applyFilters() {
  router.get('/users', {
    search: search.value || undefined,
    role:   roleFilter.value || undefined,
  }, { preserveState: true, replace: true })
}

function deleteUser(user) {
  if (confirm(`¿Eliminar el usuario "${user.name}"? Esta acción no se puede deshacer.`)) {
    router.delete(`/users/${user.id}`)
  }
}

function goToPage(page) {
  router.get('/users', {
    page,
    search: props.filters?.search || undefined,
    role:   props.filters?.role   || undefined,
  }, { preserveState: true })
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Gestión de Usuarios</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Administrar usuarios y asignar roles del sistema
        </p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-account-plus" href="/users/create">
        Nuevo Usuario
      </v-btn>
    </div>

    <!-- Filtros -->
    <v-card variant="outlined" rounded="lg" class="mb-4">
      <v-card-text class="pb-2">
        <v-row dense align="center">
          <v-col cols="12" md="5">
            <v-text-field v-model="search" prepend-inner-icon="mdi-magnify"
              label="Buscar por nombre o email..." variant="outlined"
              density="compact" hide-details clearable
              @keyup.enter="applyFilters" />
          </v-col>
          <v-col cols="12" md="4">
            <v-select v-model="roleFilter"
              :items="[{ title: 'Todos los roles', value: '' }, ...roles.map(r => ({ title: r.name, value: r.name }))]"
              label="Filtrar por rol" variant="outlined" density="compact"
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
      <v-data-table :headers="headers" :items="users.data"
        :items-per-page="users.per_page" hide-default-footer density="comfortable">

        <template #item.name="{ item }">
          <div class="d-flex align-center ga-2 py-1">
            <v-avatar color="primary" size="32">
              <span class="text-caption font-weight-bold text-white">
                {{ item.name.charAt(0).toUpperCase() }}
              </span>
            </v-avatar>
            <div>
              <div class="text-body-2 font-weight-medium">{{ item.name }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.email }}</div>
            </div>
          </div>
        </template>

        <template #item.email="{ item }">
          <!-- email ya está en el template de name -->
        </template>

        <template #item.area="{ item }">
          <span class="text-caption">{{ item.area?.name ?? '—' }}</span>
        </template>

        <template #item.role="{ item }">
          <v-chip v-if="item.roles?.[0]"
            :color="roleColors[item.roles[0].name] ?? 'primary'"
            size="small" label>
            {{ item.roles[0].name }}
          </v-chip>
          <span v-else class="text-caption text-medium-emphasis">Sin rol</span>
        </template>

        <template #item.status="{ item }">
          <v-chip
            :color="item.status === 'activo' ? 'success' : 'error'"
            size="small" label>
            {{ item.status === 'activo' ? 'Activo' : 'Inactivo' }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-btn icon="mdi-pencil" variant="text" size="small"
            color="primary" :href="`/users/${item.id}/edit`" />
          <v-btn icon="mdi-delete-outline" variant="text"
            size="small" color="error" @click="deleteUser(item)" />
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-2">mdi-account-group</v-icon>
            <p class="text-h6 text-medium-emphasis mt-3">Sin usuarios</p>
          </div>
        </template>

        <template #bottom>
          <v-divider />
          <div class="d-flex align-center justify-space-between pa-3">
            <span class="text-caption text-medium-emphasis">
              {{ users.total }} usuario(s)
            </span>
            <v-pagination v-if="users.last_page > 1"
              :model-value="users.current_page" :length="users.last_page"
              density="compact" total-visible="5" @update:model-value="goToPage" />
          </div>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>