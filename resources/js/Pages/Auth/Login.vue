<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps({
  canResetPassword: Boolean,
  status: String,
})

const form = useForm({
  email:    '',
  password: '',
  remember: false,
})

const showPassword = ref(false)

function submit() {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <v-app>
    <v-main style="background: linear-gradient(135deg, #0C447C 0%, #1565C0 50%, #0D47A1 100%);">
      <v-container class="fill-height" fluid>
        <v-row align="center" justify="center">
          <v-col cols="12" sm="8" md="5" lg="4">

            <!-- Logo y título -->
            <div class="text-center mb-8">
              <v-avatar color="white" size="72" class="mb-4 elevation-4">
                <v-icon size="40" color="primary">mdi-hard-hat</v-icon>
              </v-avatar>
              <h1 class="text-h4 font-weight-bold text-white">ERP Minerjob</h1>
              <p class="text-body-2 text-white mt-1" style="opacity:0.8">
                Sistema de Gestión Empresarial v1.0
              </p>
            </div>

            <!-- Card login -->
            <v-card rounded="xl" elevation="8">
              <v-card-text class="pa-8">
                <h2 class="text-h6 font-weight-bold mb-1">Iniciar Sesión</h2>
                <p class="text-body-2 text-medium-emphasis mb-6">
                  Ingresa tus credenciales para continuar
                </p>

                <!-- Status (password reset, etc.) -->
                <v-alert v-if="status" type="success" variant="tonal"
                  density="compact" class="mb-4">
                  {{ status }}
                </v-alert>

                <v-form @submit.prevent="submit">
                  <v-text-field
                    v-model="form.email"
                    label="Correo electrónico"
                    type="email"
                    variant="outlined"
                    prepend-inner-icon="mdi-email-outline"
                    autocomplete="email"
                    autofocus
                    :error-messages="form.errors.email"
                    class="mb-3"
                  />

                  <v-text-field
                    v-model="form.password"
                    label="Contraseña"
                    :type="showPassword ? 'text' : 'password'"
                    variant="outlined"
                    prepend-inner-icon="mdi-lock-outline"
                    :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                    autocomplete="current-password"
                    :error-messages="form.errors.password"
                    class="mb-2"
                    @click:append-inner="showPassword = !showPassword"
                  />

                  <div class="d-flex align-center justify-space-between mb-6">
                    <v-checkbox
                      v-model="form.remember"
                      label="Recordarme"
                      density="compact"
                      hide-details
                      color="primary"
                    />
                    <a v-if="canResetPassword"
                      :href="route('password.request')"
                      class="text-primary text-body-2 text-decoration-none">
                      ¿Olvidaste tu contraseña?
                    </a>
                  </div>

                  <v-btn
                    type="submit"
                    color="primary"
                    block
                    size="large"
                    :loading="form.processing"
                    prepend-icon="mdi-login"
                  >
                    Ingresar al Sistema
                  </v-btn>
                </v-form>
              </v-card-text>

              <!-- Footer de la card -->
              <v-divider />
              <v-card-text class="text-center pa-4">
                <span class="text-caption text-medium-emphasis">
                  © {{ new Date().getFullYear() }} Minerjob S.A.C. — Todos los derechos reservados
                </span>
              </v-card-text>
            </v-card>

          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>
