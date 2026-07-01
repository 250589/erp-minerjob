<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'

const form = useForm({
  business_name:     '',
  trade_name:        '',
  tax_id:            '',
  address:           '',
  phone:             '',
  email:             '',
  currency_default:  'PEN',
  payment_term_days: 0,
  bank_name:         '',
  bank_account:      '',
  status:            'activo',
})

// ─── RUC Lookup ───────────────────────────────────────────────────────────────
const rucLoading  = ref(false)
const rucSuccess  = ref(null)
const rucError    = ref(null)

async function lookupRuc() {
  if (!form.tax_id || form.tax_id.length !== 11) {
    rucError.value = 'Ingrese los 11 dígitos del RUC antes de consultar.'
    return
  }
  rucLoading.value = true
  rucSuccess.value = null
  rucError.value   = null

  try {
    const { data } = await axios.post('/ruc-lookup', { ruc: form.tax_id })

    form.business_name = data.business_name ?? ''
    form.trade_name    = data.trade_name    ?? ''
    form.address       = data.address       ?? ''
    form.phone         = data.phone         ?? form.phone
    form.email         = data.email         ?? form.email

    rucSuccess.value = `✓ ${data.business_name} · Estado: ${data.estado} · ${data.condicion}`
  } catch (err) {
    rucError.value = err.response?.data?.message ?? 'Error al consultar el RUC.'
  } finally {
    rucLoading.value = false
  }
}

function onRucInput() {
  rucSuccess.value = null
  rucError.value   = null
  if (form.tax_id.length === 11) lookupRuc()
}

function submit() { form.post('/suppliers') }
</script>

<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" href="/suppliers" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Nuevo Proveedor</h1>
        <p class="text-body-2 text-medium-emphasis">
          Ingresa el RUC para autocompletar los datos
        </p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row>
        <v-col cols="12" md="8">

          <!-- ─── Búsqueda por RUC ──────────────────────────────────────── -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-magnify</v-icon>
              Consulta por RUC
              <v-chip size="x-small" color="info" class="ml-2">apiperu.dev</v-chip>
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <div class="d-flex ga-3 align-start">
                <v-text-field
                  v-model="form.tax_id"
                  label="RUC (11 dígitos) *"
                  variant="outlined"
                  density="compact"
                  prepend-inner-icon="mdi-card-account-details-outline"
                  maxlength="11"
                  counter="11"
                  :loading="rucLoading"
                  :error-messages="form.errors.tax_id"
                  hint="Se consultará automáticamente al ingresar 11 dígitos"
                  persistent-hint
                  @input="onRucInput"
                />
                <v-btn
                  color="primary"
                  variant="tonal"
                  :loading="rucLoading"
                  prepend-icon="mdi-cloud-search"
                  style="margin-top:2px"
                  @click="lookupRuc"
                >
                  Consultar
                </v-btn>
              </div>

              <v-alert v-if="rucSuccess" type="success" variant="tonal"
                density="compact" class="mt-3">
                {{ rucSuccess }}
              </v-alert>
              <v-alert v-if="rucError" type="error" variant="tonal"
                density="compact" class="mt-3">
                {{ rucError }}
              </v-alert>
            </v-card-text>
          </v-card>

          <!-- ─── Datos del proveedor ───────────────────────────────────── -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-domain</v-icon>
              Datos de la Empresa
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12" md="8">
                  <v-text-field v-model="form.business_name"
                    label="Razón Social *" variant="outlined" density="compact"
                    :error-messages="form.errors.business_name" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-select v-model="form.status"
                    :items="[{title:'Activo',value:'activo'},{title:'Inactivo',value:'inactivo'}]"
                    label="Estado" variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="form.trade_name"
                    label="Nombre Comercial" variant="outlined" density="compact"
                    :error-messages="form.errors.trade_name" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="form.address"
                    label="Dirección" variant="outlined" density="compact"
                    prepend-inner-icon="mdi-map-marker"
                    :error-messages="form.errors.address" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="form.phone"
                    label="Teléfono" variant="outlined" density="compact"
                    prepend-inner-icon="mdi-phone"
                    :error-messages="form.errors.phone" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="form.email"
                    label="Email" type="email" variant="outlined" density="compact"
                    prepend-inner-icon="mdi-email"
                    :error-messages="form.errors.email" />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- ─── Condiciones comerciales ───────────────────────────────── -->
          <v-card variant="outlined" rounded="lg">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-handshake-outline</v-icon>
              Condiciones Comerciales
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12" md="4">
                  <v-select v-model="form.currency_default"
                    :items="[{title:'Soles (PEN)',value:'PEN'},{title:'Dólares (USD)',value:'USD'}]"
                    label="Moneda por defecto" variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model.number="form.payment_term_days"
                    label="Plazo de pago (días)" type="number" min="0"
                    variant="outlined" density="compact" suffix="días"
                    :error-messages="form.errors.payment_term_days" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model="form.bank_name"
                    label="Banco" variant="outlined" density="compact"
                    prepend-inner-icon="mdi-bank-outline" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="form.bank_account"
                    label="CCI / N° de Cuenta" variant="outlined" density="compact"
                    prepend-inner-icon="mdi-credit-card-outline" />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- ─── Sidebar ───────────────────────────────────────────────── -->
        <v-col cols="12" md="4">
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="info">mdi-information</v-icon>
              ¿Cómo funciona?
            </v-card-title>
            <v-divider />
            <v-card-text class="text-body-2 text-medium-emphasis">
              <p class="mb-2">
                <v-icon size="16" color="primary">mdi-numeric-1-circle</v-icon>
                Ingresa el RUC de 11 dígitos
              </p>
              <p class="mb-2">
                <v-icon size="16" color="primary">mdi-numeric-2-circle</v-icon>
                Los datos se autocompletan desde SUNAT vía apiperu.dev
              </p>
              <p class="mb-2">
                <v-icon size="16" color="primary">mdi-numeric-3-circle</v-icon>
                Verifica y completa los datos comerciales
              </p>
              <p>
                <v-icon size="16" color="primary">mdi-numeric-4-circle</v-icon>
                Guarda el proveedor
              </p>
            </v-card-text>
          </v-card>

          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <v-btn type="submit" color="primary" block size="large"
                :loading="form.processing"
                prepend-icon="mdi-content-save"
                class="mb-2">
                Guardar Proveedor
              </v-btn>
              <v-btn variant="tonal" block href="/suppliers"
                :disabled="form.processing">
                Cancelar
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-form>
  </div>
</template>