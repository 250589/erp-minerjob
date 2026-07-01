<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({ user: Object, roles: Array, areas: Array })

const showPassword = ref(false)

const form = useForm({
  name:     props.user.name,
  email:    props.user.email,
  password: '',
  area_id:  props.user.area_id,
  phone:    props.user.phone ?? '',
  status:   props.user.status,
  role:     props.user.roles?.[0]?.name ?? null,
})

function submit() {
  form.patch(`/users/${props.user.id}`)
}
</script>

<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" href="/users" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Editar Usuario</h1>
        <p class="text-body-2 text-medium-emphasis">{{ user.email }}</p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row justify="center">
        <v-col cols="12" md="7">
          <v-card variant="outlined" rounded="lg">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-account-edit</v-icon>
              Datos del Usuario
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field v-model="form.name" label="Nombre completo *"
                    variant="outlined" density="compact"
                    prepend-inner-icon="mdi-account"
                    :error-messages="form.errors.name" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="form.email" label="Email *"
                    type="email" variant="outlined" density="compact"
                    prepend-inner-icon="mdi-email"
                    :error-messages="form.errors.email" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="form.password"
                    label="Nueva contraseña (dejar vacío para no cambiar)"
                    :type="showPassword ? 'text' : 'password'"
                    variant="outlined" density="compact"
                    prepend-inner-icon="mdi-lock"
                    :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                    :error-messages="form.errors.password"
                    @click:append-inner="showPassword = !showPassword" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="form.phone" label="Teléfono"
                    variant="outlined" density="compact"
                    prepend-inner-icon="mdi-phone"
                    :error-messages="form.errors.phone" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-select v-model="form.area_id" :items="areas"
                    item-title="name" item-value="id"
                    label="Área" variant="outlined" density="compact"
                    clearable prepend-inner-icon="mdi-domain"
                    :error-messages="form.errors.area_id" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-select v-model="form.role" :items="roles"
                    item-title="name" item-value="name"
                    label="Rol *" variant="outlined" density="compact"
                    prepend-inner-icon="mdi-shield-account"
                    :error-messages="form.errors.role" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-select v-model="form.status"
                    :items="[
                      { title: 'Activo',   value: 'activo'   },
                      { title: 'Inactivo', value: 'inactivo' },
                    ]"
                    label="Estado *" variant="outlined" density="compact"
                    prepend-inner-icon="mdi-toggle-switch"
                    :error-messages="form.errors.status" />
                </v-col>
              </v-row>
            </v-card-text>
            <v-divider />
            <v-card-actions class="pa-4 ga-2">
              <v-spacer />
              <v-btn variant="tonal" href="/users" :disabled="form.processing">
                Cancelar
              </v-btn>
              <v-btn type="submit" color="primary"
                :loading="form.processing"
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