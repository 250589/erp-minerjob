<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  products: Array,
  units: Array,
  areas: Array,
})

const form = useForm({
  area_id:       null,
  justification: '',
  required_date: null,
  items: [
    { product_id: null, unit_id: null, description: '', quantity: 1, estimated_unit_price: null },
  ],
})

// ─── Ítems ────────────────────────────────────────────────

function addItem() {
  form.items.push({
    product_id: null, unit_id: null, description: '', quantity: 1, estimated_unit_price: null,
  })
}

function removeItem(index) {
  form.items.splice(index, 1)
}

function onProductSelect(index, productId) {
  if (!productId) return
  const product = props.products.find((p) => p.id === productId)
  if (product) form.items[index].description = product.name
}

// ─── Resumen ──────────────────────────────────────────────

const estimatedTotal = computed(() =>
  form.items.reduce((sum, item) => {
    return sum + item.quantity * (item.estimated_unit_price || 0)
  }, 0)
)

// ─── Submit ───────────────────────────────────────────────

function submit() {
  form.post('/requirements')
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" href="/requirements" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Nuevo Requerimiento</h1>
        <p class="text-body-2 text-medium-emphasis">
          Complete los datos y agregue los materiales solicitados
        </p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row>
        <!-- ─── Columna principal ──────────────────────── -->
        <v-col cols="12" md="8">

          <!-- Datos generales -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="text-subtitle-1 font-weight-bold d-flex align-center pa-4 pb-3">
              <v-icon start color="primary">mdi-information-outline</v-icon>
              Datos Generales
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.area_id"
                    :items="areas"
                    item-title="name"
                    item-value="id"
                    label="Área Solicitante"
                    variant="outlined"
                    density="compact"
                    clearable
                    prepend-inner-icon="mdi-office-building"
                    :error-messages="form.errors.area_id"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.required_date"
                    label="Fecha Requerida"
                    type="date"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-calendar"
                    :error-messages="form.errors.required_date"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="form.justification"
                    label="Justificación"
                    variant="outlined"
                    density="compact"
                    rows="3"
                    prepend-inner-icon="mdi-text"
                    hint="Describa el motivo o necesidad del requerimiento"
                    persistent-hint
                    :error-messages="form.errors.justification"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Ítems -->
          <v-card variant="outlined" rounded="lg">
            <div class="d-flex align-center pa-4 pb-3">
              <v-icon start color="primary">mdi-format-list-bulleted</v-icon>
              <span class="text-subtitle-1 font-weight-bold">Ítems del Requerimiento</span>
              <v-spacer />
              <v-btn
                color="primary"
                size="small"
                variant="tonal"
                prepend-icon="mdi-plus"
                @click="addItem"
              >
                Agregar Ítem
              </v-btn>
            </div>
            <v-divider />

            <!-- Error global de ítems -->
            <v-alert
              v-if="form.errors.items"
              type="error"
              variant="tonal"
              density="compact"
              class="ma-3"
              :text="form.errors.items"
            />

            <!-- Lista de ítems -->
            <div
              v-for="(item, index) in form.items"
              :key="index"
            >
              <v-card-text>
                <!-- Header del ítem -->
                <div class="d-flex align-center mb-3">
                  <v-chip color="primary" size="x-small" label class="mr-2">
                    # {{ index + 1 }}
                  </v-chip>
                  <span class="text-caption text-medium-emphasis font-weight-bold">
                    ÍTEM {{ index + 1 }}
                  </span>
                  <v-spacer />
                  <v-btn
                    v-if="form.items.length > 1"
                    icon="mdi-delete-outline"
                    variant="text"
                    color="error"
                    size="x-small"
                    @click="removeItem(index)"
                  />
                </div>

                <v-row dense>
                  <!-- Producto (opcional) -->
                  <v-col cols="12" md="6">
                    <v-autocomplete
                      v-model="form.items[index].product_id"
                      :items="products"
                      item-title="name"
                      item-value="id"
                      label="Producto del catálogo (opcional)"
                      variant="outlined"
                      density="compact"
                      clearable
                      :error-messages="form.errors[`items.${index}.product_id`]"
                      @update:model-value="(val) => onProductSelect(index, val)"
                    >
                      <template #item="{ item: p, props: pp }">
                        <v-list-item v-bind="pp">
                          <template #prepend>
                            <v-icon size="small">mdi-cube-outline</v-icon>
                          </template>
                          <template #subtitle>{{ p.raw.sku }}</template>
                        </v-list-item>
                      </template>
                    </v-autocomplete>
                  </v-col>

                  <!-- Descripción -->
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.items[index].description"
                      label="Descripción *"
                      variant="outlined"
                      density="compact"
                      :error-messages="form.errors[`items.${index}.description`]"
                    />
                  </v-col>

                  <!-- Cantidad -->
                  <v-col cols="6" md="3">
                    <v-text-field
                      v-model.number="form.items[index].quantity"
                      label="Cantidad *"
                      type="number"
                      min="0.0001"
                      step="1"
                      variant="outlined"
                      density="compact"
                      :error-messages="form.errors[`items.${index}.quantity`]"
                    />
                  </v-col>

                  <!-- Unidad -->
                  <v-col cols="6" md="3">
                    <v-select
                      v-model="form.items[index].unit_id"
                      :items="units"
                      item-title="name"
                      item-value="id"
                      label="Unidad *"
                      variant="outlined"
                      density="compact"
                      :error-messages="form.errors[`items.${index}.unit_id`]"
                    >
                      <template #item="{ item: u, props: up }">
                        <v-list-item v-bind="up" :subtitle="u.raw.abbreviation" />
                      </template>
                    </v-select>
                  </v-col>

                  <!-- Precio estimado -->
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="form.items[index].estimated_unit_price"
                      label="Precio Estimado Unitario"
                      type="number"
                      min="0"
                      step="0.01"
                      prefix="S/"
                      variant="outlined"
                      density="compact"
                      :error-messages="form.errors[`items.${index}.estimated_unit_price`]"
                    />
                  </v-col>
                </v-row>
              </v-card-text>

              <v-divider v-if="index < form.items.length - 1" />
            </div>
          </v-card>
        </v-col>

        <!-- ─── Sidebar resumen ────────────────────────── -->
        <v-col cols="12" md="4">
          <!-- Resumen -->
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="text-subtitle-1 font-weight-bold d-flex align-center pa-4 pb-3">
              <v-icon start color="primary">mdi-calculator</v-icon>
              Resumen
            </v-card-title>
            <v-divider />
            <v-list density="compact">
              <v-list-item>
                <template #title>
                  <span class="text-body-2">Total de ítems</span>
                </template>
                <template #append>
                  <v-chip color="primary" size="small">{{ form.items.length }}</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <template #title>
                  <span class="text-body-2">Costo estimado</span>
                </template>
                <template #append>
                  <span class="font-weight-bold text-primary">
                    S/ {{ estimatedTotal.toFixed(2) }}
                  </span>
                </template>
              </v-list-item>
            </v-list>
          </v-card>

          <!-- Acciones -->
          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <v-btn
                type="submit"
                color="primary"
                block
                :loading="form.processing"
                prepend-icon="mdi-content-save"
                size="large"
                class="mb-2"
              >
                Guardar Requerimiento
              </v-btn>
              <v-btn
                variant="tonal"
                block
                href="/requirements"
                :disabled="form.processing"
              >
                Cancelar
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-form>
  </div>
</template>
