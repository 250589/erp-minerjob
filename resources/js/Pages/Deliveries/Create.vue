<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  warehouses:   Array,
  areas:        Array,
  requirements: Array,
  products:     Array,
  stockSummary: Array,
})

const form = useForm({
  warehouse_id:   null,
  area_id:        null,
  requirement_id: null,
  notes:          '',
  items: [
    { product_id: null, quantity_requested: 1, notes: '' },
  ],
})

// Stock disponible en el almacén seleccionado
const availableStock = computed(() => {
  if (!form.warehouse_id) return {}
  const result = {}
  props.stockSummary
    .filter(s => s.warehouse_id === form.warehouse_id)
    .forEach(s => { result[s.product_id] = Number(s.quantity) })
  return result
})

function stockForProduct(productId) {
  return availableStock.value[productId] ?? 0
}

function addItem() {
  form.items.push({ product_id: null, quantity_requested: 1, notes: '' })
}

function removeItem(i) {
  form.items.splice(i, 1)
}

const totalItems = computed(() => form.items.length)

function submit() { form.post('/deliveries') }
</script>

<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" href="/deliveries" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Nueva Nota de Entrega</h1>
        <p class="text-body-2 text-medium-emphasis">
          Registrar entrega de materiales al personal (Pasos 38-39)
        </p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row>
        <v-col cols="12" md="8">

          <!-- Datos generales -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-file-document-outline</v-icon>
              Datos de la Entrega
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.warehouse_id"
                    :items="warehouses"
                    item-title="name" item-value="id"
                    label="Almacén que entrega *"
                    variant="outlined" density="compact"
                    prepend-inner-icon="mdi-warehouse"
                    :error-messages="form.errors.warehouse_id">
                    <template #item="{ item, props: p }">
                      <v-list-item v-bind="p">
                        <template #subtitle>{{ item.raw.type }}</template>
                      </v-list-item>
                    </template>
                  </v-select>
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.area_id"
                    :items="areas" item-title="name" item-value="id"
                    label="Área / Carril receptor"
                    variant="outlined" density="compact"
                    clearable prepend-inner-icon="mdi-domain"
                    :error-messages="form.errors.area_id" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.requirement_id"
                    :items="requirements" item-title="code" item-value="id"
                    label="Requerimiento vinculado (opcional)"
                    variant="outlined" density="compact"
                    clearable prepend-inner-icon="mdi-clipboard-list"
                    :error-messages="form.errors.requirement_id" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-textarea
                    v-model="form.notes"
                    label="Observaciones"
                    variant="outlined" density="compact"
                    rows="2" />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Ítems -->
          <v-card variant="outlined" rounded="lg">
            <div class="d-flex align-center pa-4 pb-3">
              <v-icon start color="primary">mdi-package-variant</v-icon>
              <span class="text-subtitle-1 font-weight-bold">
                Materiales a Entregar
              </span>
              <v-chip size="x-small" color="primary" class="ml-2">
                {{ totalItems }}
              </v-chip>
              <v-spacer />
              <v-btn size="small" color="primary" variant="tonal"
                prepend-icon="mdi-plus" @click="addItem">
                Agregar
              </v-btn>
            </div>
            <v-divider />

            <v-alert v-if="!form.warehouse_id"
              type="info" variant="tonal" density="compact" class="ma-3">
              Seleccione el almacén primero para ver el stock disponible.
            </v-alert>

            <v-card-text>
              <div v-for="(item, index) in form.items" :key="index"
                class="d-flex align-center ga-3 mb-3">

                <v-autocomplete
                  v-model="form.items[index].product_id"
                  :items="products"
                  item-value="id"
                  label="Producto *"
                  variant="outlined" density="compact"
                  hide-details class="flex-grow-1"
                  :error-messages="form.errors[`items.${index}.product_id`]">
                  <template #item="{ item: p, props: pp }">
                    <v-list-item v-bind="pp">
                      <template #title>{{ p.raw.name }}</template>
                      <template #subtitle>{{ p.raw.sku }}</template>
                    </v-list-item>
                  </template>
                  <template #selection="{ item: p }">
                    {{ p.raw.name }}
                  </template>
                </v-autocomplete>

                <!-- Stock disponible -->
                <div v-if="item.product_id && form.warehouse_id"
                  class="text-center" style="min-width:90px">
                  <div class="text-caption text-medium-emphasis">Stock</div>
                  <v-chip
                    :color="stockForProduct(item.product_id) > 0 ? 'success' : 'error'"
                    size="small">
                    {{ stockForProduct(item.product_id).toFixed(2) }}
                  </v-chip>
                </div>

                <v-text-field
                  v-model.number="form.items[index].quantity_requested"
                  label="Cant. *" type="number" min="0.0001" step="1"
                  variant="outlined" density="compact" hide-details
                  style="max-width:110px"
                  :color="item.product_id && item.quantity_requested > stockForProduct(item.product_id)
                    ? 'error' : 'success'"
                  :error-messages="form.errors[`items.${index}.quantity_requested`]" />

                <v-btn v-if="form.items.length > 1"
                  icon="mdi-delete-outline" variant="text"
                  color="error" size="small" @click="removeItem(index)" />
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" md="4">
          <!-- Flujo -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-map-marker-path</v-icon>
              Pasos del Flujo
            </v-card-title>
            <v-divider />
            <v-list density="compact">
              <v-list-item prepend-icon="mdi-check-circle" base-color="success">
                <template #title><span class="text-caption">Pasos 28-37</span></template>
                <template #subtitle>Traslado al subalmacén completado</template>
              </v-list-item>
              <v-list-item prepend-icon="mdi-numeric-1-circle" base-color="primary">
                <template #title><span class="text-caption">Paso 38</span></template>
                <template #subtitle>Personal solicita materiales</template>
              </v-list-item>
              <v-list-item prepend-icon="mdi-numeric-2-circle" base-color="primary">
                <template #title><span class="text-caption">Paso 39</span></template>
                <template #subtitle>Generar Nota de Entrega NE-YYYY-NNNN</template>
              </v-list-item>
              <v-list-item prepend-icon="mdi-numeric-3-circle" base-color="warning">
                <template #title><span class="text-caption">Pasos 40-41</span></template>
                <template #subtitle>Confirmar entrega → Salida kardex</template>
              </v-list-item>
            </v-list>
          </v-card>

          <!-- Acciones -->
          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <v-btn type="submit" color="primary" block size="large"
                :loading="form.processing"
                prepend-icon="mdi-file-document-plus" class="mb-2">
                Generar Nota de Entrega
              </v-btn>
              <v-btn variant="tonal" block href="/deliveries"
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