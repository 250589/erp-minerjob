<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  quoteRequest:     Object,
  pendingSuppliers: Array,
})

const reqItems = computed(() => props.quoteRequest.requirement?.items ?? [])

const form = useForm({
  supplier_id:         null,
  code:                '',
  currency:            'PEN',
  exchange_rate:       1,
  payment_term_days:   0,
  delivery_term_days:  0,
  validity_date:       '',
  notes:               '',
  quote_file:          null,
  items: reqItems.value.map(item => ({
    requirement_item_id: item.id,
    description:         item.description,
    quantity:            Number(item.quantity),
    unit_price:          null,
  })),
})

const currencies = [
  { title: 'Soles (PEN)',   value: 'PEN' },
  { title: 'Dólares (USD)', value: 'USD' },
  { title: 'Euros (EUR)',   value: 'EUR' },
]

// ─── Tipo de cambio ──────────────────────────────────────────────────────────
const rateLoading = ref(false)
const rateInfo    = ref(null)  // { sale, purchase, date }
const rateError   = ref(null)

async function fetchExchangeRate(currency) {
  if (currency === 'PEN') {
    form.exchange_rate = 1
    rateInfo.value     = null
    rateError.value    = null
    return
  }

  rateLoading.value = true
  rateError.value   = null
  rateInfo.value    = null

  try {
    const { data } = await axios.post('/exchange-rate', { currency })
    form.exchange_rate = data.sale      // tipo de cambio venta (el que se usa para comprar)
    rateInfo.value     = data
  } catch (err) {
    rateError.value = err.response?.data?.message ?? `No se pudo obtener el T/C para ${currency}.`
  } finally {
    rateLoading.value = false
  }
}

// Auto-consultar al cambiar de moneda
watch(() => form.currency, (newCurrency) => {
  fetchExchangeRate(newCurrency)
}, { immediate: false })

// ─── Cálculos ─────────────────────────────────────────────────────────────────
const subtotal = computed(() =>
  form.items.reduce((s, i) => s + i.quantity * (i.unit_price || 0), 0)
)
const tax   = computed(() => subtotal.value * 0.18)
const total = computed(() => subtotal.value + tax.value)

// ─── Archivo adjunto ──────────────────────────────────────────────────────────
const fileInput = ref(null)

function onFileChange(e) {
  const file = e.target.files?.[0]
  if (file) form.quote_file = file
}

function clearFile() {
  form.quote_file = null
  if (fileInput.value) fileInput.value.value = ''
}

function submit() {
  form.post(route('quote-requests.quotes.store', props.quoteRequest.id), {
    forceFormData: true,
  })
}
</script>

<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text"
        :href="`/quote-requests/${quoteRequest.id}`" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Registrar Cotización Recibida</h1>
        <p class="text-body-2 text-medium-emphasis">
          SC: {{ quoteRequest.code }} · {{ quoteRequest.requirement?.code }} (Paso 4)
        </p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row>
        <v-col cols="12" md="8">

          <!-- Datos generales -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-file-document</v-icon>
              Datos de la Cotización
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.supplier_id"
                    :items="pendingSuppliers"
                    item-title="business_name"
                    item-value="id"
                    label="Proveedor *"
                    variant="outlined"
                    density="compact"
                    :error-messages="form.errors.supplier_id">
                    <template #item="{ item, props: p }">
                      <v-list-item v-bind="p">
                        <template #subtitle>RUC: {{ item.raw.tax_id }}</template>
                      </v-list-item>
                    </template>
                  </v-select>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="form.code"
                    label="N° Cotización del Proveedor"
                    variant="outlined" density="compact"
                    :error-messages="form.errors.code" />
                </v-col>

                <!-- Moneda + Tipo de Cambio automático -->
                <v-col cols="12" md="4">
                  <v-select
                    v-model="form.currency"
                    :items="currencies"
                    label="Moneda *"
                    variant="outlined"
                    density="compact"
                    :error-messages="form.errors.currency" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field
                    v-model.number="form.exchange_rate"
                    label="Tipo de Cambio *"
                    type="number"
                    min="0.0001"
                    step="0.001"
                    variant="outlined"
                    density="compact"
                    :loading="rateLoading"
                    :disabled="form.currency === 'PEN'"
                    :hint="form.currency === 'PEN' ? 'PEN = 1 siempre' :
                      rateInfo ? `Venta: ${rateInfo.sale} · Compra: ${rateInfo.purchase} · ${rateInfo.date}` : ''"
                    persistent-hint
                    :error-messages="form.errors.exchange_rate">
                    <template v-if="form.currency !== 'PEN'" #append-inner>
                      <v-btn
                        icon="mdi-refresh"
                        size="x-small"
                        variant="text"
                        color="primary"
                        :loading="rateLoading"
                        title="Actualizar tipo de cambio"
                        @click="fetchExchangeRate(form.currency)" />
                    </template>
                  </v-text-field>
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model="form.validity_date"
                    label="Válida hasta" type="date"
                    variant="outlined" density="compact"
                    prepend-inner-icon="mdi-calendar" />
                </v-col>

                <!-- Alerta de error de tipo de cambio -->
                <v-col v-if="rateError" cols="12">
                  <v-alert type="warning" variant="tonal" density="compact">
                    <strong>Tipo de cambio:</strong> {{ rateError }}
                    Puedes ingresar el valor manualmente.
                  </v-alert>
                </v-col>

                <!-- Info de tipo de cambio -->
                <v-col v-if="rateInfo && form.currency !== 'PEN'" cols="12">
                  <v-alert type="success" variant="tonal" density="compact"
                    prepend-icon="mdi-bank-outline">
                    <strong>T/C del día ({{ rateInfo.date }}):</strong>
                    Venta S/ {{ rateInfo.sale }} · Compra S/ {{ rateInfo.purchase }}
                    <span class="ml-2 text-caption">
                      (Se usa el tipo venta para calcular equivalencias en PEN)
                    </span>
                  </v-alert>
                </v-col>

                <v-col cols="12" md="6">
                  <v-text-field v-model.number="form.payment_term_days"
                    label="Plazo de pago (días) *" type="number" min="0"
                    variant="outlined" density="compact" suffix="días"
                    :error-messages="form.errors.payment_term_days" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model.number="form.delivery_term_days"
                    label="Plazo de entrega (días) *" type="number" min="0"
                    variant="outlined" density="compact" suffix="días"
                    :error-messages="form.errors.delivery_term_days" />
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="form.notes" label="Observaciones"
                    variant="outlined" density="compact" rows="2" />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Precios por ítem -->
          <v-card variant="outlined" rounded="lg">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-format-list-bulleted</v-icon>
              Precios por Ítem
              <span v-if="form.currency !== 'PEN'" class="text-caption font-weight-regular ml-2 text-medium-emphasis">
                (en {{ form.currency }}, T/C: {{ form.exchange_rate }})
              </span>
            </v-card-title>
            <v-divider />
            <v-card-text>
              <v-table density="compact">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th class="text-center">Cant.</th>
                    <th style="min-width:140px">Precio Unit. *</th>
                    <th class="text-right">Subtotal</th>
                    <th v-if="form.currency !== 'PEN'" class="text-right">Equiv. PEN</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in form.items" :key="index">
                    <td class="text-medium-emphasis">{{ index + 1 }}</td>
                    <td><span class="text-body-2">{{ item.description }}</span></td>
                    <td class="text-center text-body-2">{{ item.quantity }}</td>
                    <td>
                      <v-text-field
                        v-model.number="form.items[index].unit_price"
                        type="number" min="0" step="0.01"
                        :prefix="form.currency"
                        variant="outlined" density="compact" hide-details
                        style="min-width:130px" />
                    </td>
                    <td class="text-right text-body-2 font-weight-medium">
                      {{ form.currency }}
                      {{ (item.quantity * (item.unit_price || 0)).toFixed(2) }}
                    </td>
                    <td v-if="form.currency !== 'PEN'" class="text-right text-caption text-medium-emphasis">
                      S/ {{ (item.quantity * (item.unit_price || 0) * form.exchange_rate).toFixed(2) }}
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- ─── Sidebar ─────────────────────────────────────────────────── -->
        <v-col cols="12" md="4">

          <!-- Resumen -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-calculator</v-icon>
              Resumen
            </v-card-title>
            <v-divider />
            <v-list density="compact">
              <v-list-item>
                <template #title><span class="text-body-2">Subtotal</span></template>
                <template #append>
                  <span>{{ form.currency }} {{ subtotal.toFixed(2) }}</span>
                </template>
              </v-list-item>
              <v-list-item>
                <template #title><span class="text-body-2">IGV (18%)</span></template>
                <template #append>
                  <span>{{ form.currency }} {{ tax.toFixed(2) }}</span>
                </template>
              </v-list-item>
              <v-divider />
              <v-list-item>
                <template #title>
                  <span class="font-weight-bold">Total</span>
                </template>
                <template #append>
                  <span class="font-weight-bold text-primary">
                    {{ form.currency }} {{ total.toFixed(2) }}
                  </span>
                </template>
              </v-list-item>
              <!-- Equivalente en PEN si es moneda extranjera -->
              <template v-if="form.currency !== 'PEN' && form.exchange_rate > 1">
                <v-divider />
                <v-list-item>
                  <template #title>
                    <span class="text-caption text-medium-emphasis">Equivalente PEN</span>
                  </template>
                  <template #append>
                    <span class="text-caption font-weight-medium">
                      S/ {{ (total * form.exchange_rate).toFixed(2) }}
                    </span>
                  </template>
                </v-list-item>
              </template>
            </v-list>
          </v-card>

          <!-- Archivo adjunto -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-2 font-weight-bold">
              <v-icon start color="secondary" size="20">mdi-paperclip</v-icon>
              Documento de la Cotización
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-3">
              <div v-if="!form.quote_file">
                <v-btn color="secondary" variant="tonal" block
                  prepend-icon="mdi-upload" @click="fileInput.click()">
                  Adjuntar PDF o Imagen
                </v-btn>
                <p class="text-caption text-medium-emphasis text-center mt-2">
                  PDF, JPG o PNG — máx 10 MB
                </p>
              </div>

              <div v-else>
                <v-alert type="success" variant="tonal" density="compact">
                  <div class="d-flex align-center justify-space-between">
                    <div class="d-flex align-center ga-2">
                      <v-icon size="20">
                        {{ form.quote_file.name.endsWith('.pdf')
                          ? 'mdi-file-pdf-box' : 'mdi-file-image' }}
                      </v-icon>
                      <span class="text-caption font-weight-medium"
                        style="word-break:break-all">
                        {{ form.quote_file.name }}
                      </span>
                    </div>
                    <v-btn icon="mdi-close" size="x-small" variant="text"
                      color="error" @click="clearFile" />
                  </div>
                  <p class="text-caption mt-1 text-medium-emphasis">
                    {{ (form.quote_file.size / 1024 / 1024).toFixed(2) }} MB
                  </p>
                </v-alert>
              </div>

              <input ref="fileInput" type="file" accept=".pdf,.jpg,.jpeg,.png"
                class="d-none" @change="onFileChange" />

              <div v-if="form.errors.quote_file" class="text-error text-caption mt-2">
                {{ form.errors.quote_file }}
              </div>
            </v-card-text>
          </v-card>

          <!-- Acciones -->
          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <v-btn type="submit" color="primary" block size="large"
                :loading="form.processing" prepend-icon="mdi-content-save" class="mb-2">
                Registrar Cotización
              </v-btn>
              <v-btn variant="tonal" block :href="`/quote-requests/${quoteRequest.id}`"
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