<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  warehouses:   Array,
  products:     Array,
  stockSummary: Array,
})

const form = useForm({
  origin_warehouse_id:      null,
  destination_warehouse_id: null,
  items: [
    { product_id: null, quantity_requested: 1 },
  ],
})

// Stock disponible en el almacén origen seleccionado
const availableStock = computed(() => {
  if (!form.origin_warehouse_id) return {}
  const result = {}
  props.stockSummary
    .filter(s => s.warehouse_id === form.origin_warehouse_id)
    .forEach(s => { result[s.product_id] = Number(s.quantity) })
  return result
})

function addItem() {
  form.items.push({ product_id: null, quantity_requested: 1 })
}

function removeItem(index) {
  form.items.splice(index, 1)
}

function productStock(productId) {
  return availableStock.value[productId] ?? 0
}

function productName(productId) {
  const p = props.products.find(p => p.id === productId)
  return p ? `${p.sku} — ${p.name}` : ''
}

// Almacenes destino: todos excepto el origen
const destinationWarehouses = computed(() =>
  props.warehouses.filter(w => w.id !== form.origin_warehouse_id)
)

function submit() { form.post('/transfers') }
</script>

<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" href="/transfers" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Nueva Orden de Traslado</h1>
        <p class="text-body-2 text-medium-emphasis">
          Seleccione origen, destino y los productos a trasladar (Paso 29)
        </p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row>
        <v-col cols="12" md="8">

          <!-- Origen y destino -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-transfer</v-icon>
              Ruta del Traslado
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row align="center">
                <v-col cols="12" md="5">
                  <v-select
                    v-model="form.origin_warehouse_id"
                    :items="warehouses"
                    item-title="name"
                    item-value="id"
                    label="Almacén Origen *"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-home-city"
                    :error-messages="form.errors.origin_warehouse_id"
                  >
                    <template #item="{ item, props: p }">
                      <v-list-item v-bind="p">
                        <template #prepend>
                          <v-icon size="small"
                            :color="item.raw.type === 'principal' ? 'primary' : 'secondary'">
                            mdi-home-city
                          </v-icon>
                        </template>
                        <template #subtitle>{{ item.raw.type }}</template>
                      </v-list-item>
                    </template>
                  </v-select>
                </v-col>

                <v-col cols="12" md="2" class="text-center">
                  <v-icon size="32" color="primary">mdi-arrow-right-bold</v-icon>
                </v-col>

                <v-col cols="12" md="5">
                  <v-select
                    v-model="form.destination_warehouse_id"
                    :items="destinationWarehouses"
                    item-title="name"
                    item-value="id"
                    label="Almacén Destino *"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-home-map-marker"
                    :error-messages="form.errors.destination_warehouse_id"
                    :disabled="!form.origin_warehouse_id"
                  >
                    <template #item="{ item, props: p }">
                      <v-list-item v-bind="p">
                        <template #prepend>
                          <v-icon size="small"
                            :color="item.raw.type === 'principal' ? 'primary' : 'secondary'">
                            mdi-home-map-marker
                          </v-icon>
                        </template>
                        <template #subtitle>{{ item.raw.type }}</template>
                      </v-list-item>
                    </template>
                  </v-select>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Productos a trasladar -->
          <v-card variant="outlined" rounded="lg">
            <div class="d-flex align-center pa-4 pb-3">
              <v-icon start color="primary">mdi-package-variant</v-icon>
              <span class="text-subtitle-1 font-weight-bold">Productos a Trasladar</span>
              <v-spacer />
              <v-btn size="small" color="primary" variant="tonal"
                prepend-icon="mdi-plus" @click="addItem">
                Agregar
              </v-btn>
            </div>
            <v-divider />

            <v-alert v-if="form.errors.items" type="error"
              variant="tonal" density="compact" class="ma-3"
              :text="form.errors.items" />

            <v-card-text>
              <div v-for="(item, index) in form.items" :key="index"
                class="d-flex align-center ga-3 mb-3">

                <v-autocomplete
                  v-model="form.items[index].product_id"
                  :items="products"
                  item-title="name"
                  item-value="id"
                  label="Producto *"
                  variant="outlined"
                  density="compact"
                  hide-details
                  class="flex-grow-1"
                  :error-messages="form.errors[`items.${index}.product_id`]"
                >
                  <template #item="{ item: p, props: pp }">
                    <v-list-item v-bind="pp">
                      <template #subtitle>{{ p.raw.sku }}</template>
                    </v-list-item>
                  </template>
                </v-autocomplete>

                <!-- Stock disponible en origen -->
                <div v-if="item.product_id && form.origin_warehouse_id"
                  class="text-center" style="min-width:100px">
                  <div class="text-caption text-medium-emphasis">Stock</div>
                  <v-chip
                    :color="productStock(item.product_id) > 0 ? 'success' : 'error'"
                    size="small">
                    {{ productStock(item.product_id).toFixed(2) }}
                  </v-chip>
                </div>

                <v-text-field
                  v-model.number="form.items[index].quantity_requested"
                  label="Cantidad *"
                  type="number"
                  min="0.0001"
                  step="1"
                  variant="outlined"
                  density="compact"
                  hide-details
                  style="max-width:120px"
                  :error-messages="form.errors[`items.${index}.quantity_requested`]"
                />

                <v-btn
                  v-if="form.items.length > 1"
                  icon="mdi-delete-outline"
                  variant="text"
                  color="error"
                  size="small"
                  @click="removeItem(index)"
                />
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" md="4">
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-information</v-icon>
              Flujo del Traslado
            </v-card-title>
            <v-divider />
            <v-list density="compact">
              <v-list-item prepend-icon="mdi-numeric-1-circle" base-color="primary">
                <template #title><span class="text-caption">Paso 29</span></template>
                <template #subtitle>Crear Orden de Traslado (TR-YYYY-NNNN)</template>
              </v-list-item>
              <v-list-item prepend-icon="mdi-numeric-2-circle" base-color="info">
                <template #title><span class="text-caption">Paso 30</span></template>
                <template #subtitle>Guía interna generada al despachar</template>
              </v-list-item>
              <v-list-item prepend-icon="mdi-numeric-3-circle" base-color="warning">
                <template #title><span class="text-caption">Paso 31</span></template>
                <template #subtitle>Salida kardex origen</template>
              </v-list-item>
              <v-list-item prepend-icon="mdi-numeric-4-circle" base-color="success">
                <template #title><span class="text-caption">Pasos 32-34</span></template>
                <template #subtitle>Entrada kardex destino</template>
              </v-list-item>
            </v-list>
          </v-card>

          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <v-btn type="submit" color="primary" block size="large"
                :loading="form.processing"
                prepend-icon="mdi-send"
                class="mb-2">
                Crear Orden de Traslado
              </v-btn>
              <v-btn variant="tonal" block href="/transfers"
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
