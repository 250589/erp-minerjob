<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  invoice:          Object,
  accounts:         Array,
  suggestedDetails: Array,
})

const form = useForm({
  entry_date:  new Date().toISOString().slice(0, 10),
  description: '',
  details:     props.suggestedDetails.map(d => ({ ...d })),
})

// Totales en tiempo real
const totalDebit  = computed(() => form.details.reduce((s, d) => s + (Number(d.debit)  || 0), 0))
const totalCredit = computed(() => form.details.reduce((s, d) => s + (Number(d.credit) || 0), 0))
const isBalanced  = computed(() => Math.abs(totalDebit.value - totalCredit.value) < 0.01)
const diff        = computed(() => Math.abs(totalDebit.value - totalCredit.value).toFixed(2))

function addDetail() {
  form.details.push({ account_id: null, debit: 0, credit: 0, description: '' })
}

function removeDetail(index) {
  form.details.splice(index, 1)
}

// Nombre legible de la cuenta
function accountName(id) {
  const acc = props.accounts.find(a => a.id === id)
  return acc ? `${acc.code} - ${acc.name}` : ''
}

function submit() {
  form.post(`/invoices/${props.invoice.id}/accounting-entry`)
}
</script>

<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text"
        :href="`/invoices/${invoice.id}`" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Registrar Asiento Contable</h1>
        <p class="text-body-2 text-medium-emphasis">
          Factura {{ invoice.full_number }} · {{ invoice.supplier?.business_name }}
          (Paso 14)
        </p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row>
        <v-col cols="12" md="8">

          <!-- Cabecera del asiento -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-book-open-variant</v-icon>
              Datos del Asiento
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12" md="5">
                  <v-text-field v-model="form.entry_date" label="Fecha del Asiento *"
                    type="date" variant="outlined" density="compact"
                    :error-messages="form.errors.entry_date" />
                </v-col>
                <v-col cols="12" md="7">
                  <v-text-field v-model="form.description" label="Descripción"
                    variant="outlined" density="compact"
                    :placeholder="`Factura ${invoice.full_number} - ${invoice.supplier?.business_name}`" />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Líneas del asiento (partida doble) -->
          <v-card variant="outlined" rounded="lg">
            <div class="d-flex align-center pa-4 pb-3">
              <v-icon start color="primary">mdi-table</v-icon>
              <span class="text-subtitle-1 font-weight-bold">Líneas del Asiento</span>
              <v-spacer />
              <v-btn size="small" color="primary" variant="tonal"
                prepend-icon="mdi-plus" @click="addDetail">
                Agregar línea
              </v-btn>
            </div>
            <v-divider />

            <v-alert v-if="form.errors.details" type="error"
              variant="tonal" density="compact" class="ma-3"
              :text="form.errors.details" />

            <v-card-text>
              <v-table density="compact">
                <thead>
                  <tr>
                    <th style="min-width:220px">Cuenta</th>
                    <th>Descripción</th>
                    <th style="min-width:130px" class="text-right">DEBE</th>
                    <th style="min-width:130px" class="text-right">HABER</th>
                    <th width="40"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(detail, index) in form.details" :key="index">
                    <td>
                      <v-select
                        v-model="form.details[index].account_id"
                        :items="accounts"
                        :item-title="a => `${a.code} - ${a.name}`"
                        item-value="id"
                        variant="outlined"
                        density="compact"
                        hide-details
                        :error-messages="form.errors[`details.${index}.account_id`]"
                        style="min-width:200px"
                      />
                    </td>
                    <td>
                      <v-text-field v-model="form.details[index].description"
                        variant="outlined" density="compact" hide-details
                        placeholder="Descripción opcional" />
                    </td>
                    <td>
                      <v-text-field v-model.number="form.details[index].debit"
                        type="number" min="0" step="0.01" variant="outlined"
                        density="compact" hide-details class="text-right"
                        @focus="form.details[index].credit = 0" />
                    </td>
                    <td>
                      <v-text-field v-model.number="form.details[index].credit"
                        type="number" min="0" step="0.01" variant="outlined"
                        density="compact" hide-details class="text-right"
                        @focus="form.details[index].debit = 0" />
                    </td>
                    <td>
                      <v-btn v-if="form.details.length > 2"
                        icon="mdi-delete-outline" variant="text"
                        color="error" size="x-small" @click="removeDetail(index)" />
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="2" class="text-right font-weight-bold pa-2">TOTALES:</td>
                    <td class="text-right font-weight-bold pa-2">
                      {{ totalDebit.toFixed(2) }}
                    </td>
                    <td class="text-right font-weight-bold pa-2">
                      {{ totalCredit.toFixed(2) }}
                    </td>
                    <td></td>
                  </tr>
                </tfoot>
              </v-table>

              <!-- Indicador de cuadre -->
              <v-alert
                :type="isBalanced ? 'success' : 'error'"
                variant="tonal"
                density="compact"
                class="mt-3"
              >
                <span v-if="isBalanced">
                  ✓ El asiento cuadra (DEBE = HABER = {{ totalDebit.toFixed(2) }})
                </span>
                <span v-else>
                  ✗ El asiento no cuadra — Diferencia: {{ diff }}
                  (DEBE: {{ totalDebit.toFixed(2) }} / HABER: {{ totalCredit.toFixed(2) }})
                </span>
              </v-alert>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" md="4">
          <!-- Datos de la factura -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-file-document</v-icon>
              Factura de Referencia
            </v-card-title>
            <v-divider />
            <v-list density="compact">
              <v-list-item>
                <template #title><span class="text-caption">Comprobante</span></template>
                <template #subtitle><span class="font-weight-bold">{{ invoice.full_number }}</span></template>
              </v-list-item>
              <v-list-item>
                <template #title><span class="text-caption">Proveedor</span></template>
                <template #subtitle>{{ invoice.supplier?.business_name }}</template>
              </v-list-item>
              <v-list-item>
                <template #title><span class="text-caption">Subtotal</span></template>
                <template #subtitle>{{ invoice.currency }} {{ Number(invoice.subtotal).toFixed(2) }}</template>
              </v-list-item>
              <v-list-item>
                <template #title><span class="text-caption">IGV</span></template>
                <template #subtitle>{{ invoice.currency }} {{ Number(invoice.tax).toFixed(2) }}</template>
              </v-list-item>
              <v-list-item>
                <template #title><span class="text-caption font-weight-bold">Total</span></template>
                <template #subtitle>
                  <span class="font-weight-bold text-primary">
                    {{ invoice.currency }} {{ Number(invoice.total).toFixed(2) }}
                  </span>
                </template>
              </v-list-item>
            </v-list>
          </v-card>

          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <v-btn type="submit" color="primary" block size="large"
                :loading="form.processing"
                :disabled="!isBalanced"
                prepend-icon="mdi-book-check"
                class="mb-2">
                Registrar Asiento
              </v-btn>
              <v-btn variant="tonal" block :href="`/invoices/${invoice.id}`"
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
