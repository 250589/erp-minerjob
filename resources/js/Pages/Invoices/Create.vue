<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  purchaseOrder:  Object,
  purchaseOrders: Array,
})

// Pre-popular items desde la OC
function itemsFromPO(po) {
  if (!po?.items) return []
  return po.items.map(item => ({
    purchase_order_item_id: item.id,
    description:            item.description,
    quantity:               Number(item.quantity),
    unit_price:             Number(item.unit_price),
  }))
}

const form = useForm({
  purchase_order_id: props.purchaseOrder?.id ?? null,
  supplier_id:       props.purchaseOrder?.supplier_id ?? null,
  series:            '',
  number:            '',
  issue_date:        '',
  currency:          props.purchaseOrder?.currency ?? 'PEN',
  exchange_rate:     props.purchaseOrder?.exchange_rate ?? 1,
  items:             itemsFromPO(props.purchaseOrder),
})

// Cuando seleccionan OC diferente, recargar página
function onPOChange(poId) {
  router.get('/invoices/create', { purchase_order_id: poId }, { preserveState: false })
}

const currencies = [
  { title: 'Soles (PEN)',   value: 'PEN' },
  { title: 'Dólares (USD)', value: 'USD' },
]

const subtotal = computed(() => form.items.reduce((s, i) => s + i.quantity * (i.unit_price || 0), 0))
const tax      = computed(() => subtotal.value * 0.18)
const total    = computed(() => subtotal.value + tax.value)

// Diferencia vs OC
const poDiff = computed(() => {
  if (!props.purchaseOrder) return null
  return total.value - Number(props.purchaseOrder.total)
})

function submit() { form.post('/invoices') }
</script>

<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" href="/invoices" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Registrar Factura</h1>
        <p class="text-body-2 text-medium-emphasis">
          Ingrese el comprobante recibido del proveedor (Paso 10)
        </p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row>
        <v-col cols="12" md="8">

          <!-- Datos del comprobante -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-file-document</v-icon>
              Datos del Comprobante
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12">
                  <v-select
                    v-model="form.purchase_order_id"
                    :items="purchaseOrders"
                    item-title="code"
                    item-value="id"
                    label="Orden de Compra *"
                    variant="outlined"
                    density="compact"
                    :error-messages="form.errors.purchase_order_id"
                    @update:model-value="onPOChange"
                  />
                </v-col>
                <v-col cols="12" md="3">
                  <v-text-field v-model="form.series" label="Serie"
                    variant="outlined" density="compact" placeholder="F001"
                    :error-messages="form.errors.series" />
                </v-col>
                <v-col cols="12" md="5">
                  <v-text-field v-model="form.number" label="Número *"
                    variant="outlined" density="compact" placeholder="00001234"
                    :error-messages="form.errors.number" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model="form.issue_date" label="Fecha de Emisión *"
                    type="date" variant="outlined" density="compact"
                    :error-messages="form.errors.issue_date" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-select v-model="form.currency" :items="currencies"
                    label="Moneda *" variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model.number="form.exchange_rate"
                    label="Tipo de Cambio *" type="number" min="0.0001"
                    variant="outlined" density="compact" />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Ítems pre-poblados desde la OC -->
          <v-card variant="outlined" rounded="lg">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-format-list-bulleted</v-icon>
              Ítems de la Factura
            </v-card-title>
            <v-divider />
            <v-card-text>
              <v-alert v-if="!purchaseOrder" type="info" variant="tonal"
                density="compact" class="mb-3">
                Seleccione una Orden de Compra para pre-cargar los ítems.
              </v-alert>

              <v-table v-if="form.items.length > 0" density="compact">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th style="min-width:100px">Cantidad</th>
                    <th style="min-width:130px">Precio Unit.</th>
                    <th class="text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in form.items" :key="index">
                    <td class="text-caption text-medium-emphasis">{{ index + 1 }}</td>
                    <td>
                      <v-text-field v-model="form.items[index].description"
                        variant="plain" density="compact" hide-details />
                    </td>
                    <td>
                      <v-text-field v-model.number="form.items[index].quantity"
                        type="number" min="0.0001" variant="outlined"
                        density="compact" hide-details style="max-width:90px" />
                    </td>
                    <td>
                      <v-text-field v-model.number="form.items[index].unit_price"
                        type="number" min="0" step="0.01" :prefix="form.currency"
                        variant="outlined" density="compact" hide-details
                        style="max-width:120px" />
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

        <!-- Sidebar -->
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
                <template #append>{{ form.currency }} {{ subtotal.toFixed(2) }}</template>
              </v-list-item>
              <v-list-item>
                <template #title><span class="text-body-2">IGV (18%)</span></template>
                <template #append>{{ form.currency }} {{ tax.toFixed(2) }}</template>
              </v-list-item>
              <v-divider />
              <v-list-item>
                <template #title>
                  <span class="font-weight-bold">Total Factura</span>
                </template>
                <template #append>
                  <span class="font-weight-bold text-primary">
                    {{ form.currency }} {{ total.toFixed(2) }}
                  </span>
                </template>
              </v-list-item>
              <v-list-item v-if="purchaseOrder">
                <template #title><span class="text-body-2">Total OC</span></template>
                <template #append>
                  {{ purchaseOrder.currency }} {{ Number(purchaseOrder.total).toFixed(2) }}
                </template>
              </v-list-item>
            </v-list>

            <!-- Alerta si hay diferencia con la OC -->
            <v-card-text v-if="poDiff !== null && Math.abs(poDiff) >= 0.01" class="pt-0">
              <v-alert
                :type="Math.abs(poDiff) > 1 ? 'warning' : 'info'"
                variant="tonal" density="compact"
              >
                Diferencia vs OC:
                <strong>{{ form.currency }} {{ poDiff.toFixed(2) }}</strong>
              </v-alert>
            </v-card-text>
          </v-card>

          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <v-btn type="submit" color="primary" block size="large"
                :loading="form.processing" prepend-icon="mdi-content-save" class="mb-2">
                Registrar Factura
              </v-btn>
              <v-btn variant="tonal" block href="/invoices" :disabled="form.processing">
                Cancelar
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-form>
  </div>
</template>
