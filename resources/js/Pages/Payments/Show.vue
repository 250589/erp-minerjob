<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps({ obligation: Object })

const obl       = computed(() => props.obligation)
const payments  = computed(() => obl.value.payments ?? [])
const lastPayment = computed(() => payments.value.find(p => p.status === 'registrado'))

const uploadForm  = useForm({ voucher_file: null })
const voucherName = ref('')
const uploadDialog = ref(false)

function onFileChange(e) {
  const file = e.target.files[0]
  if (file) {
    uploadForm.voucher_file = file
    voucherName.value = file.name
  }
}

function submitVoucher(paymentId) {
  uploadForm.post(`/payments/${paymentId}/upload-voucher`, {
    forceFormData: true,
    onSuccess: () => { uploadDialog.value = false; uploadForm.reset() },
  })
}

function confirmPayment() {
  if (confirm('¿Confirmar el pago? La Orden de Compra y la Factura quedarán como PAGADAS. (Paso 19)')) {
    router.patch(`/payment-obligations/${obl.value.id}/confirm`)
  }
}

const methodIcons = {
  transferencia: 'mdi-bank-transfer',
  deposito:      'mdi-cash-plus',
  cheque:        'mdi-checkbook',
}

function currency(val, cur = 'PEN') {
  return `${cur} ${Number(val).toFixed(2)}`
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap ga-3">
      <div class="d-flex align-center">
        <v-btn icon="mdi-arrow-left" variant="text" href="/payments" class="mr-2" />
        <div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <h1 class="text-h5 font-weight-bold">
              Obligación de Pago
            </h1>
            <v-chip :color="obl.status_color" size="small" label>
              {{ obl.status_label }}
            </v-chip>
          </div>
          <p class="text-body-2 text-medium-emphasis mt-1">
            {{ obl.invoice?.supplier?.business_name }} ·
            Factura: {{ obl.invoice?.series }}-{{ obl.invoice?.number }}
          </p>
        </div>
      </div>

      <div class="d-flex ga-2">
        <v-btn
          v-if="obl.status === 'pendiente'"
          color="success"
          prepend-icon="mdi-cash-plus"
          :href="`/payment-obligations/${obl.id}/pay`"
        >
          Registrar Pago
        </v-btn>
        <v-btn
          v-if="lastPayment && obl.status === 'pendiente'"
          color="primary"
          variant="tonal"
          prepend-icon="mdi-check-all"
          @click="confirmPayment"
        >
          Confirmar y Cerrar (Paso 19)
        </v-btn>
      </div>
    </div>

    <!-- Banner OC PAGADA -->
    <v-alert
      v-if="obl.status === 'pagado'"
      type="success"
      variant="tonal"
      density="compact"
      class="mb-4"
      prepend-icon="mdi-check-circle"
    >
      <strong>Pago completado. Orden de Compra y Factura marcadas como PAGADAS. ✓ (Paso 19)</strong>
    </v-alert>

    <v-row>
      <!-- Pagos realizados -->
      <v-col cols="12" md="8">
        <v-card variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-cash-multiple</v-icon>
            Pagos Registrados
            <v-chip size="x-small" color="primary" class="ml-2">
              {{ payments.length }}
            </v-chip>
          </v-card-title>
          <v-divider />

          <div v-if="payments.length === 0" class="text-center pa-6 text-medium-emphasis">
            <v-icon size="48">mdi-cash-remove</v-icon>
            <p class="text-body-2 mt-2">Sin pagos registrados aún</p>
          </div>

          <div v-for="payment in payments" :key="payment.id">
            <v-card-text>
              <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-3">
                <div class="d-flex align-center ga-2">
                  <v-icon :icon="methodIcons[payment.method]" color="primary" />
                  <div>
                    <span class="font-weight-bold">{{ payment.method_label }}</span>
                    <span class="text-caption text-medium-emphasis ml-2">
                      · {{ payment.payment_date }} · por {{ payment.created_by?.name }}
                    </span>
                  </div>
                </div>
                <div class="d-flex align-center ga-2">
                  <span class="text-h6 font-weight-bold text-primary">
                    {{ currency(payment.amount, payment.currency) }}
                  </span>
                  <v-chip :color="payment.status_color" size="small" label>
                    {{ payment.status_label }}
                  </v-chip>
                </div>
              </div>

              <v-row dense class="text-caption text-medium-emphasis">
                <v-col v-if="payment.reference_number" cols="12" md="4">
                  <strong>N° Operación:</strong> {{ payment.reference_number }}
                </v-col>
                <v-col v-if="payment.origin_account" cols="12" md="4">
                  <strong>Cuenta Origen:</strong> {{ payment.origin_account }}
                </v-col>
                <v-col v-if="payment.destination_account" cols="12" md="4">
                  <strong>Cuenta Destino:</strong> {{ payment.destination_account }}
                </v-col>
              </v-row>

              <!-- Voucher -->
              <div class="mt-3">
                <div v-if="payment.voucher" class="d-flex align-center ga-2">
                  <v-icon color="success">mdi-file-check</v-icon>
                  <a :href="payment.voucher.url" target="_blank"
                    class="text-primary text-body-2">
                    {{ payment.voucher.file_name }}
                  </a>
                  <v-chip size="x-small" color="success">Subido</v-chip>
                </div>

                <div v-else class="d-flex align-center ga-2">
                  <v-chip size="small" color="warning" label>
                    <v-icon start size="12">mdi-file-alert</v-icon>
                    Sin voucher
                  </v-chip>
                  <v-btn
                    v-if="payment.status === 'registrado'"
                    size="small"
                    color="warning"
                    variant="tonal"
                    prepend-icon="mdi-upload"
                    @click="uploadDialog = payment.id"
                  >
                    Subir Voucher
                  </v-btn>
                </div>
              </div>

              <!-- Upload dialog inline -->
              <v-dialog v-if="uploadDialog === payment.id" v-model="uploadDialog" max-width="420">
                <v-card rounded="lg">
                  <v-card-title class="pa-4">
                    <v-icon start color="primary">mdi-file-upload</v-icon>
                    Subir Voucher (Paso 18)
                  </v-card-title>
                  <v-card-text>
                    <div class="d-flex align-center ga-2 mb-2">
                      <v-btn color="primary" variant="tonal" prepend-icon="mdi-upload"
                        @click="$refs.voucherInput.click()">
                        Seleccionar
                      </v-btn>
                      <span class="text-caption">{{ voucherName || 'PDF, JPG, PNG — máx 5 MB' }}</span>
                      <input ref="voucherInput" type="file" accept=".pdf,.jpg,.jpeg,.png"
                        class="d-none" @change="onFileChange" />
                    </div>
                  </v-card-text>
                  <v-card-actions class="pa-4 pt-0">
                    <v-spacer />
                    <v-btn variant="text" @click="uploadDialog = false">Cancelar</v-btn>
                    <v-btn color="primary" :loading="uploadForm.processing"
                      :disabled="!uploadForm.voucher_file"
                      @click="submitVoucher(payment.id)">
                      Subir
                    </v-btn>
                  </v-card-actions>
                </v-card>
              </v-dialog>
            </v-card-text>
            <v-divider />
          </div>
        </v-card>
      </v-col>

      <!-- Sidebar info -->
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-information-outline</v-icon>
            Detalle de la Obligación
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-cash</v-icon></template>
              <template #title><span class="text-caption">Monto</span></template>
              <template #subtitle>
                <span class="font-weight-bold text-primary">
                  {{ currency(obl.amount, obl.currency) }}
                </span>
              </template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-calendar-alert</v-icon></template>
              <template #title><span class="text-caption">Fecha de Vencimiento</span></template>
              <template #subtitle>{{ obl.due_date ?? '—' }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-file-document</v-icon></template>
              <template #title><span class="text-caption">Factura</span></template>
              <template #subtitle>
                {{ obl.invoice?.series }}-{{ obl.invoice?.number }}
              </template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-cart</v-icon></template>
              <template #title><span class="text-caption">Orden de Compra</span></template>
              <template #subtitle>{{ obl.invoice?.purchase_order?.code }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-book-open</v-icon></template>
              <template #title><span class="text-caption">Asiento Contable</span></template>
              <template #subtitle>
                {{ obl.accounting_entry?.entry_number ?? '—' }}
              </template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Guía del flujo -->
        <v-card variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-map-marker-path</v-icon>
            Flujo de Pago
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item prepend-icon="mdi-check-circle" base-color="success">
              <template #title><span class="text-caption">Paso 16</span></template>
              <template #subtitle>Obligación recibida por Finanzas</template>
            </v-list-item>
            <v-list-item
              :prepend-icon="payments.length > 0 ? 'mdi-check-circle' : 'mdi-circle-outline'"
              :base-color="payments.length > 0 ? 'success' : 'default'"
            >
              <template #title><span class="text-caption">Paso 17</span></template>
              <template #subtitle>Depósito / Transferencia registrada</template>
            </v-list-item>
            <v-list-item
              :prepend-icon="payments.some(p => p.voucher) ? 'mdi-check-circle' : 'mdi-circle-outline'"
              :base-color="payments.some(p => p.voucher) ? 'success' : 'default'"
            >
              <template #title><span class="text-caption">Paso 18</span></template>
              <template #subtitle>Voucher subido al sistema</template>
            </v-list-item>
            <v-list-item
              :prepend-icon="obl.status === 'pagado' ? 'mdi-check-circle' : 'mdi-circle-outline'"
              :base-color="obl.status === 'pagado' ? 'success' : 'default'"
            >
              <template #title><span class="text-caption">Paso 19</span></template>
              <template #subtitle>OC marcada como PAGADA</template>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>
