<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({ obligation: Object })

const obl = computed(() => props.obligation)

const form = useForm({
  payment_date:        new Date().toISOString().slice(0, 10),
  method:              'transferencia',
  amount:              Number(obl.value.amount),
  currency:            obl.value.currency,
  exchange_rate:       1,
  origin_account:      '',
  destination_account: obl.value.invoice?.supplier?.bank_account ?? '',
  reference_number:    '',
  notes:               '',
  voucher_file:        null,
})

const methods = [
  { title: 'Transferencia Bancaria', value: 'transferencia', icon: 'mdi-bank-transfer' },
  { title: 'Depósito',               value: 'deposito',       icon: 'mdi-cash-plus'     },
  { title: 'Cheque',                 value: 'cheque',         icon: 'mdi-checkbook'     },
]

const voucherName = ref('')

function onFileChange(e) {
  const file = e.target.files[0]
  if (file) {
    form.voucher_file = file
    voucherName.value = file.name
  }
}

function submit() {
  form.post(`/payment-obligations/${obl.value.id}/pay`, {
    forceFormData: true,
  })
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text"
        :href="`/payment-obligations/${obl.id}`" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Registrar Pago</h1>
        <p class="text-body-2 text-medium-emphasis">
          {{ obl.invoice?.supplier?.business_name }} ·
          {{ obl.invoice?.series }}-{{ obl.invoice?.number }}
          (Pasos 17-18)
        </p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row>
        <v-col cols="12" md="8">

          <!-- Resumen de la obligación -->
          <v-alert type="info" variant="tonal" density="compact" class="mb-4">
            <div class="d-flex align-center justify-space-between flex-wrap ga-2">
              <div>
                <strong>Monto a pagar:</strong>
                {{ obl.currency }} {{ Number(obl.amount).toFixed(2) }}
              </div>
              <div>
                <strong>Vencimiento:</strong> {{ obl.due_date ?? 'Sin fecha' }}
              </div>
              <div>
                <strong>OC:</strong> {{ obl.invoice?.purchase_order?.code }}
              </div>
            </div>
          </v-alert>

          <!-- Datos del pago -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-bank-transfer</v-icon>
              Datos del Pago (Paso 17)
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <!-- Método de pago -->
                <v-col cols="12">
                  <p class="text-caption text-medium-emphasis mb-2">Método de Pago *</p>
                  <div class="d-flex ga-2 flex-wrap">
                    <v-btn
                      v-for="m in methods" :key="m.value"
                      :variant="form.method === m.value ? 'tonal' : 'outlined'"
                      :color="form.method === m.value ? 'primary' : 'default'"
                      :prepend-icon="m.icon"
                      size="small"
                      @click="form.method = m.value"
                    >
                      {{ m.title }}
                    </v-btn>
                  </div>
                  <div v-if="form.errors.method" class="text-error text-caption mt-1">
                    {{ form.errors.method }}
                  </div>
                </v-col>

                <v-col cols="12" md="5">
                  <v-text-field v-model="form.payment_date" label="Fecha de Pago *"
                    type="date" variant="outlined" density="compact"
                    prepend-inner-icon="mdi-calendar"
                    :error-messages="form.errors.payment_date" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model.number="form.amount" label="Monto Pagado *"
                    type="number" min="0.01" step="0.01"
                    :prefix="form.currency" variant="outlined" density="compact"
                    :error-messages="form.errors.amount" />
                </v-col>
                <v-col cols="12" md="3">
                  <v-text-field v-model.number="form.exchange_rate" label="T/C"
                    type="number" min="0.0001" step="0.01"
                    variant="outlined" density="compact"
                    :error-messages="form.errors.exchange_rate" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="form.origin_account"
                    label="Cuenta de Origen (nuestra)"
                    variant="outlined" density="compact"
                    prepend-inner-icon="mdi-bank-outline"
                    :error-messages="form.errors.origin_account" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="form.destination_account"
                    label="Cuenta de Destino (proveedor)"
                    variant="outlined" density="compact"
                    prepend-inner-icon="mdi-bank-outline"
                    :error-messages="form.errors.destination_account" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="form.reference_number"
                    label="N° de Operación / Referencia"
                    variant="outlined" density="compact"
                    prepend-inner-icon="mdi-pound"
                    :error-messages="form.errors.reference_number" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-textarea v-model="form.notes" label="Observaciones"
                    variant="outlined" density="compact" rows="2" />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Voucher -->
          <v-card variant="outlined" rounded="lg">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-file-upload</v-icon>
              Comprobante de Pago / Voucher (Paso 18)
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <div class="d-flex align-center ga-3 flex-wrap">
                <v-btn
                  color="primary"
                  variant="tonal"
                  prepend-icon="mdi-upload"
                  @click="$refs.fileInput.click()"
                >
                  Seleccionar archivo
                </v-btn>
                <span class="text-body-2 text-medium-emphasis">
                  {{ voucherName || 'PDF, JPG o PNG — máx 5 MB' }}
                </span>
                <input
                  ref="fileInput"
                  type="file"
                  accept=".pdf,.jpg,.jpeg,.png"
                  class="d-none"
                  @change="onFileChange"
                />
              </div>
              <div v-if="form.errors.voucher_file" class="text-error text-caption mt-2">
                {{ form.errors.voucher_file }}
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" md="4">
          <!-- Info proveedor -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-domain</v-icon>
              Proveedor
            </v-card-title>
            <v-divider />
            <v-list density="compact">
              <v-list-item>
                <template #prepend><v-icon size="small">mdi-domain</v-icon></template>
                <template #title><span class="text-caption">Razón Social</span></template>
                <template #subtitle>
                  <span class="font-weight-bold text-body-2">
                    {{ obl.invoice?.supplier?.business_name }}
                  </span>
                </template>
              </v-list-item>
              <v-list-item>
                <template #prepend><v-icon size="small">mdi-bank-outline</v-icon></template>
                <template #title><span class="text-caption">Banco</span></template>
                <template #subtitle>
                  {{ obl.invoice?.supplier?.bank_name ?? '—' }}
                </template>
              </v-list-item>
              <v-list-item>
                <template #prepend><v-icon size="small">mdi-credit-card-outline</v-icon></template>
                <template #title><span class="text-caption">CCI / Cuenta</span></template>
                <template #subtitle>
                  {{ obl.invoice?.supplier?.bank_account ?? '—' }}
                </template>
              </v-list-item>
            </v-list>
          </v-card>

          <!-- Botones -->
          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <v-btn type="submit" color="success" block size="large"
                :loading="form.processing" prepend-icon="mdi-cash-check" class="mb-2">
                Registrar Pago
              </v-btn>
              <v-btn variant="tonal" block
                :href="`/payment-obligations/${obl.id}`"
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
