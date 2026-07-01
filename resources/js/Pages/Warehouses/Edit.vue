<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>
<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({ warehouse: Object, parents: Array, users: Array })

const form = useForm({
  code:                props.warehouse.code,
  name:                props.warehouse.name,
  type:                props.warehouse.type,
  parent_warehouse_id: props.warehouse.parent_warehouse_id,
  manager_user_id:     props.warehouse.manager_user_id,
  address:             props.warehouse.address ?? '',
  status:              props.warehouse.status,
})

function submit() { form.patch(`/warehouses/${props.warehouse.id}`) }
</script>

<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" href="/warehouses" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Editar Almacén</h1>
        <p class="text-body-2 text-medium-emphasis">{{ warehouse.code }} — {{ warehouse.name }}</p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row justify="center">
        <v-col cols="12" md="7">
          <v-card variant="outlined" rounded="lg">
            <v-card-title class="pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-pencil</v-icon>
              Editar Almacén
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12" md="4">
                  <v-text-field v-model="form.code" label="Código *"
                    variant="outlined" density="compact"
                    :error-messages="form.errors.code" />
                </v-col>
                <v-col cols="12" md="8">
                  <v-text-field v-model="form.name" label="Nombre *"
                    variant="outlined" density="compact"
                    :error-messages="form.errors.name" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select v-model="form.type"
                    :items="[
                      {title:'Almacén Principal', value:'principal'},
                      {title:'Subalmacén',        value:'subalmacen'},
                      {title:'Tránsito',          value:'transito'},
                    ]"
                    label="Tipo *" variant="outlined" density="compact" />
                </v-col>
                <v-col v-if="form.type === 'subalmacen'" cols="12" md="6">
                  <v-select v-model="form.parent_warehouse_id" :items="parents"
                    item-title="name" item-value="id" label="Almacén Padre"
                    variant="outlined" density="compact" clearable />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select v-model="form.manager_user_id" :items="users"
                    item-title="name" item-value="id" label="Responsable"
                    variant="outlined" density="compact" clearable
                    prepend-inner-icon="mdi-account" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select v-model="form.status"
                    :items="[{title:'Activo',value:'activo'},{title:'Inactivo',value:'inactivo'}]"
                    label="Estado" variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12">
                  <v-text-field v-model="form.address" label="Dirección / Ubicación"
                    variant="outlined" density="compact"
                    prepend-inner-icon="mdi-map-marker" />
                </v-col>
              </v-row>
            </v-card-text>
            <v-divider />
            <v-card-actions class="pa-4 ga-2">
              <v-spacer />
              <v-btn variant="tonal" href="/warehouses" :disabled="form.processing">Cancelar</v-btn>
              <v-btn type="submit" color="primary" :loading="form.processing"
                prepend-icon="mdi-content-save">
                Guardar Cambios
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-form>
  </div>
</template>