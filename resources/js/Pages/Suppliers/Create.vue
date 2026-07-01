<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { useForm } from '@inertiajs/vue3'

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
  contacts:          [],
})

const currencies = [
  { title: 'Soles (PEN)',   value: 'PEN' },
  { title: 'Dólares (USD)', value: 'USD' },
  { title: 'Euros (EUR)',   value: 'EUR' },
]

function addContact() {
  form.contacts.push({ name: '', position: '', phone: '', email: '' })
}

function removeContact(index) {
  form.contacts.splice(index, 1)
}

function submit() {
  form.post('/suppliers')
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" href="/suppliers" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Nuevo Proveedor</h1>
        <p class="text-body-2 text-medium-emphasis">Complete los datos del proveedor</p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row>
        <!-- ─── Columna principal ──────────────────── -->
        <v-col cols="12" md="8">

          <!-- Datos generales -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-domain</v-icon>
              Datos de la Empresa
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12">
                  <v-text-field
                    v-model="form.business_name"
                    label="Razón Social *"
                    variant="outlined"
                    density="compact"
                    :error-messages="form.errors.business_name"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.trade_name"
                    label="Nombre Comercial"
                    variant="outlined"
                    density="compact"
                    :error-messages="form.errors.trade_name"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.tax_id"
                    label="RUC *"
                    variant="outlined"
                    density="compact"
                    maxlength="20"
                    :error-messages="form.errors.tax_id"
                  />
                </v-col>
                <v-col cols="12">
                  <v-text-field
                    v-model="form.address"
                    label="Dirección"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-map-marker"
                    :error-messages="form.errors.address"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.phone"
                    label="Teléfono"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-phone"
                    :error-messages="form.errors.phone"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.email"
                    label="Correo Electrónico"
                    type="email"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-email"
                    :error-messages="form.errors.email"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Condiciones comerciales -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-handshake</v-icon>
              Condiciones Comerciales
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12" md="4">
                  <v-select
                    v-model="form.currency_default"
                    :items="currencies"
                    label="Moneda por defecto *"
                    variant="outlined"
                    density="compact"
                    :error-messages="form.errors.currency_default"
                  />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field
                    v-model.number="form.payment_term_days"
                    label="Plazo de pago (días) *"
                    type="number"
                    min="0"
                    variant="outlined"
                    density="compact"
                    suffix="días"
                    :error-messages="form.errors.payment_term_days"
                  />
                </v-col>
                <v-col cols="12" md="4">
                  <v-select
                    v-model="form.status"
                    :items="[{ title: 'Activo', value: 'activo' }, { title: 'Inactivo', value: 'inactivo' }]"
                    label="Estado *"
                    variant="outlined"
                    density="compact"
                    :error-messages="form.errors.status"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Datos bancarios -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-bank</v-icon>
              Datos Bancarios
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12" md="5">
                  <v-text-field
                    v-model="form.bank_name"
                    label="Banco"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-bank-outline"
                    :error-messages="form.errors.bank_name"
                  />
                </v-col>
                <v-col cols="12" md="7">
                  <v-text-field
                    v-model="form.bank_account"
                    label="Número de Cuenta / CCI"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-credit-card-outline"
                    :error-messages="form.errors.bank_account"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Contactos -->
          <v-card variant="outlined" rounded="lg">
            <div class="d-flex align-center pa-4 pb-3">
              <v-icon start color="primary">mdi-account-multiple</v-icon>
              <span class="text-subtitle-1 font-weight-bold">Contactos</span>
              <v-spacer />
              <v-btn size="small" color="primary" variant="tonal"
                prepend-icon="mdi-plus" @click="addContact">
                Agregar Contacto
              </v-btn>
            </div>
            <v-divider />

            <div v-if="form.contacts.length === 0" class="text-center pa-6 text-medium-emphasis">
              <v-icon size="40" class="mb-2">mdi-account-plus-outline</v-icon>
              <p class="text-body-2">Aún no hay contactos registrados.</p>
            </div>

            <div v-for="(contact, index) in form.contacts" :key="index">
              <v-card-text>
                <div class="d-flex align-center mb-3">
                  <v-chip color="secondary" size="x-small" label class="mr-2"># {{ index + 1 }}</v-chip>
                  <span class="text-caption font-weight-bold text-medium-emphasis">CONTACTO {{ index + 1 }}</span>
                  <v-spacer />
                  <v-btn icon="mdi-delete-outline" variant="text"
                    color="error" size="x-small" @click="removeContact(index)" />
                </div>
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.contacts[index].name"
                      label="Nombre *"
                      variant="outlined"
                      density="compact"
                      :error-messages="form.errors[`contacts.${index}.name`]"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.contacts[index].position"
                      label="Cargo"
                      variant="outlined"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.contacts[index].phone"
                      label="Teléfono"
                      variant="outlined"
                      density="compact"
                      prepend-inner-icon="mdi-phone"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.contacts[index].email"
                      label="Correo"
                      type="email"
                      variant="outlined"
                      density="compact"
                      prepend-inner-icon="mdi-email"
                      :error-messages="form.errors[`contacts.${index}.email`]"
                    />
                  </v-col>
                </v-row>
              </v-card-text>
              <v-divider v-if="index < form.contacts.length - 1" />
            </div>
          </v-card>
        </v-col>

        <!-- ─── Sidebar ─────────────────────────────── -->
        <v-col cols="12" md="4">
          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <v-btn type="submit" color="primary" block size="large"
                :loading="form.processing" prepend-icon="mdi-content-save" class="mb-2">
                Guardar Proveedor
              </v-btn>
              <v-btn variant="tonal" block href="/suppliers" :disabled="form.processing">
                Cancelar
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-form>
  </div>
</template>
