<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  purchaseOrder:  Object,
  purchaseOrders: Array,
  warehouses:     Array,
})

// Pre-poblar ítems desde la OC
function itemsFromPO(po) {
  if (!po?.items) return []
  return po.items.map(item => ({
    purchase_order_item_id: item.id,
    product_id:             item.product_id,
    product_name:           item.product ? `${item.product.sku} — ${item.product.name}` : item.description,
    quantity_ordered:       Number(item.quantity),
    quantity_received:      Number(item.quantity), // por defecto = lo ordenado
    unit_purchase_price:    Number(item.unit_price),
    markup_percentage:      Number(item.product?.markup_percentage ?? 35),
    condition_status:       'bueno',
    notes:                  '',
  }))
}

const form = useForm({
  purchase_order_id: props.purchaseOrder?.id ?? null,
  warehouse_id:      props.warehouses?.[0]?.id ?? null,
  invoice_id:        null,
  status:            'completa',
  observations:      '',
  items:             itemsFromPO(props.purchaseOrder),
})

// Recalcular precio de venta en tiempo real
function salePrice(item) {
  return (item.unit_purchase_price * (1 + item.markup_percentage / 100)).toFixed(4)
}

// Al cambiar OC, recargar la página con la nueva selección
function onPOChange(poId) {
  router.get('/warehouse-receptions/create', { purchase_order_id: poId })
}

const statusOptions = [
  { title: 'Completa',  value: 'completa',  color: 'success' },
  { title: 'Parcial',   value: 'parcial',   color: 'warning' },
  { title: 'Observada', value: 'observada', color: 'error'   },
]

const totalItems    = computed(() => form.items.length)
const totalReceived = computed(() =>
  form.items.reduce((s, i) => s + i.quantity_received * i.unit_purchase_price, 0)
)

function submit() {
  form.post('/warehouse-receptions')
}
</script>

<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text"
        href="/warehouse-receptions" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Registrar Recepción de Mercadería</h1>
        <p class="text-body-2 text-medium-emphasis">
          Verificar mercadería entregada y actualizar stock (Pasos 20-27)
        </p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row>
        <v-col cols="12" md="8">

          <!-- Datos de la recepción -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-warehouse</v-icon>
              Datos de la Recepción
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12" md="6">
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
                  >
                    <template #item="{ item, props: p }">
                      <v-list-item v-bind="p">
                        <template #subtitle>
                          {{ item.raw.supplier?.business_name }}
                        </template>
                      </v-list-item>
                    </template>
                  </v-select>
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.warehouse_id"
                    :items="warehouses"
                    item-title="name"
                    item-value="id"
                    label="Almacén de Destino *"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-home-city"
                    :error-messages="form.errors.warehouse_id"
                  />
                </v-col>

                <!-- Estado de la recepción -->
                <v-col cols="12">
                  <p class="text-caption text-medium-emphasis mb-2">Estado de la Recepción *</p>
                  <div class="d-flex ga-2">
                    <v-btn
                      v-for="opt in statusOptions"
                      :key="opt.value"
                      :color="form.status === opt.value ? opt.color : 'default'"
                      :variant="form.status === opt.value ? 'tonal' : 'outlined'"
                      size="small"
                      @click="form.status = opt.value"
                    >
                      {{ opt.title }}
                    </v-btn>
                  </div>
                </v-col>

                <v-col v-if="form.status !== 'completa'" cols="12">
                  <v-textarea
                    v-model="form.observations"
                    label="Observaciones de la recepción"
                    variant="outlined"
                    density="compact"
                    rows="2"
                    :error-messages="form.errors.observations"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Ítems a recibir -->
          <v-card variant="outlined" rounded="lg">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-package-variant</v-icon>
              Ítems Recibidos
              <v-chip size="x-small" color="primary" class="ml-2">
                {{ totalItems }}
              </v-chip>
            </v-card-title>
            <v-divider />

            <v-alert v-if="!purchaseOrder" type="info" variant="tonal"
              density="compact" class="ma-3">
              Seleccione una Orden de Compra para cargar los ítems.
            </v-alert>

            <v-alert v-if="form.errors.items" type="error"
              variant="tonal" density="compact" class="ma-3"
              :text="form.errors.items" />

            <v-card-text v-if="form.items.length > 0">
              <div v-for="(item, index) in form.items" :key="index"
                class="mb-4 pb-4"
                :class="index < form.items.length - 1 ? 'border-b' : ''">

                <!-- Header del ítem -->
                <div class="d-flex align-center mb-3">
                  <v-chip color="primary" size="x-small" label class="mr-2">
                    {{ index + 1 }}
                  </v-chip>
                  <span class="text-body-2 font-weight-medium">
                    {{ item.product_name }}
                  </span>
                </div>

                <v-row dense>
                  <v-col cols="6" md="2">
                    <v-text-field
                      :model-value="item.quantity_ordered"
                      label="Ordenado"
                      variant="outlined" density="compact"
                      hide-details readonly
                      bg-color="grey-lighten-4"
                    />
                  </v-col>
                  <v-col cols="6" md="2">
                    <v-text-field
                      v-model.number="form.items[index].quantity_received"
                      label="Recibido *"
                      type="number" min="0" step="0.01"
                      variant="outlined" density="compact" hide-details
                      :color="item.quantity_received < item.quantity_ordered ? 'warning' : 'success'"
                      :error-messages="form.errors[`items.${index}.quantity_received`]"
                    />
                  </v-col>
                  <v-col cols="6" md="2">
                    <v-text-field
                      v-model.number="form.items[index].unit_purchase_price"
                      label="P. Compra *"
                      type="number" min="0" step="0.01" prefix="S/"
                      variant="outlined" density="compact" hide-details
                      :error-messages="form.errors[`items.${index}.unit_purchase_price`]"
                    />
                  </v-col>
                  <v-col cols="6" md="2">
                    <v-text-field
                      v-model.number="form.items[index].markup_percentage"
                      label="Margen %"
                      type="number" min="0" step="1"
                      variant="outlined" density="compact" hide-details
                      suffix="%"
                    />
                  </v-col>
                  <v-col cols="6" md="2">
                    <!-- Paso 25: precio venta calculado en tiempo real -->
                    <v-text-field
                      :model-value="salePrice(item)"
                      label="P. Venta"
                      variant="outlined" density="compact"
                      hide-details readonly
                      prefix="S/"
                      bg-color="green-lighten-5"
                    />
                  </v-col>
                  <v-col cols="6" md="2">
                    <v-select
                      v-model="form.items[index].condition_status"
                      :items="[
                        { title: 'Buen estado', value: 'bueno' },
                        { title: 'Dañado', value: 'danado' },
                      ]"
                      variant="outlined" density="compact" hide-details
                      :color="item.condition_status === 'danado' ? 'error' : 'success'"
                    />
                  </v-col>
                  <v-col v-if="item.condition_status === 'danado'" cols="12">
                    <v-alert type="warning" variant="tonal" density="compact">
                      ⚠️ Los ítems dañados NO ingresarán al stock ni al kardex.
                    </v-alert>
                  </v-col>
                </v-row>
              </div>
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
                <template #title><span class="text-body-2">Total ítems</span></template>
                <template #append>
                  <v-chip color="primary" size="small">{{ totalItems }}</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <template #title><span class="text-body-2">Valor total recibido</span></template>
                <template #append>
                  <span class="font-weight-bold text-primary">
                    S/ {{ totalReceived.toFixed(2) }}
                  </span>
                </template>
              </v-list-item>
              <v-list-item>
                <template #title><span class="text-body-2">Almacén destino</span></template>
                <template #append>
                  <span class="text-caption">
                    {{ warehouses?.find(w => w.id === form.warehouse_id)?.name || '—' }}
                  </span>
                </template>
              </v-list-item>
            </v-list>

            <v-card-text class="pt-0">
              <v-alert type="info" variant="tonal" density="compact">
                Al guardar, el sistema actualizará automáticamente el
                <strong>stock</strong> y registrará el movimiento en el
                <strong>kardex valorizado</strong>. (Pasos 26-27)
              </v-alert>
            </v-card-text>
          </v-card>

          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <v-btn type="submit" color="primary" block size="large"
                :loading="form.processing"
                prepend-icon="mdi-package-down"
                class="mb-2">
                Registrar Recepción
              </v-btn>
              <v-btn variant="tonal" block href="/warehouse-receptions"
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
