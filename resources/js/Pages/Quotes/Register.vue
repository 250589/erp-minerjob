<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

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
  // Pre-poblar ítems del requerimiento (usuario solo llena precio)
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

const subtotal = computed(() =>
  form.items.reduce((s, i) => s + i.quantity * (i.unit_price || 0), 0)
)
const tax   = computed(() => subtotal.value * 0.18)
const total = computed(() => subtotal.value + tax.value)

function submit() {
  form.post(`/quote-requests/${props.quoteRequest.id}/quotes`)
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

          <!-- Datos generales de la cotización -->
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
                    :error-messages="form.errors.supplier_id"
                  >
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
                <v-col cols="12" md="4">
                  <v-select v-model="form.currency" :items="currencies"
                    label="Moneda *" variant="outlined" density="compact"
                    :error-messages="form.errors.currency" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model.number="form.exchange_rate"
                    label="Tipo de Cambio *" type="number" min="0.0001" step="0.01"
                    variant="outlined" density="compact"
                    :error-messages="form.errors.exchange_rate" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model="form.validity_date"
                    label="Válida hasta" type="date"
                    variant="outlined" density="compact"
                    prepend-inner-icon="mdi-calendar" />
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
            </v-card-title>
            <v-divider />
            <v-alert v-if="form.errors.items" type="error"
              variant="tonal" density="compact" class="ma-3"
              :text="form.errors.items" />
            <v-card-text>
              <v-table density="compact">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th class="text-center">Cantidad</th>
                    <th style="min-width:140px">Precio Unit. *</th>
                    <th class="text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in form.items" :key="index">
                    <td class="text-medium-emphasis">{{ index + 1 }}</td>
                    <td>
                      <span class="text-body-2">{{ item.description }}</span>
                    </td>
                    <td class="text-center text-body-2">{{ item.quantity }}</td>
                    <td>
                      <v-text-field
                        v-model.number="form.items[index].unit_price"
                        type="number" min="0" step="0.01"
                        :prefix="form.currency"
                        variant="outlined" density="compact"
                        hide-details
                        :error-messages="form.errors[`items.${index}.unit_price`]"
                        style="min-width:130px"
                      />
                    </td>
                    <td class="text-right text-body-2">
                      {{ form.currency }}
                      {{ (item.quantity * (item.unit_price || 0)).toFixed(2) }}
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar resumen -->
        <v-col cols="12" md="4">
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
            </v-list>
          </v-card>

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
