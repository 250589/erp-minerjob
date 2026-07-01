<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>
<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({ product: Object, categories: Array, units: Array })

const form = useForm({
  category_id:       props.product.category_id,
  unit_id:           props.product.unit_id,
  sku:               props.product.sku,
  name:              props.product.name,
  description:       props.product.description ?? '',
  min_stock:         Number(props.product.min_stock),
  max_stock:         props.product.max_stock ? Number(props.product.max_stock) : null,
  markup_percentage: Number(props.product.markup_percentage),
  status:            props.product.status,
})

function submit() { form.patch(`/products/${props.product.id}`) }
</script>

<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" href="/products" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Editar Producto</h1>
        <p class="text-body-2 text-medium-emphasis">{{ product.sku }} — {{ product.name }}</p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row justify="center">
        <v-col cols="12" md="8">
          <v-card variant="outlined" rounded="lg">
            <v-card-title class="pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-pencil</v-icon>
              Editar Producto
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12" md="4">
                  <v-text-field v-model="form.sku" label="SKU / Código *"
                    variant="outlined" density="compact" :error-messages="form.errors.sku" />
                </v-col>
                <v-col cols="12" md="8">
                  <v-text-field v-model="form.name" label="Nombre del Producto *"
                    variant="outlined" density="compact" :error-messages="form.errors.name" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select v-model="form.category_id" :items="categories"
                    item-title="name" item-value="id" label="Categoría"
                    variant="outlined" density="compact" clearable
                    :error-messages="form.errors.category_id" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select v-model="form.unit_id" :items="units"
                    item-value="id" label="Unidad de Medida *"
                    variant="outlined" density="compact" :error-messages="form.errors.unit_id">
                    <template #item="{ item, props: p }">
                      <v-list-item v-bind="p">
                        <template #title>{{ item.raw.name }}</template>
                        <template #subtitle>{{ item.raw.abbreviation }}</template>
                      </v-list-item>
                    </template>
                    <template #selection="{ item }">
                      {{ item.raw.name }} ({{ item.raw.abbreviation }})
                    </template>
                  </v-select>
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="form.description" label="Descripción"
                    variant="outlined" density="compact" rows="2" />
                </v-col>
                <v-col cols="12"><v-divider /></v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model.number="form.markup_percentage"
                    label="Margen %" type="number" suffix="%"
                    variant="outlined" density="compact"
                    :error-messages="form.errors.markup_percentage" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model.number="form.min_stock"
                    label="Stock Mínimo" type="number"
                    variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model.number="form.max_stock"
                    label="Stock Máximo" type="number"
                    variant="outlined" density="compact" clearable />
                </v-col>
                <v-col cols="12" md="4">
                  <v-select v-model="form.status"
                    :items="[{title:'Activo',value:'activo'},{title:'Inactivo',value:'inactivo'}]"
                    label="Estado" variant="outlined" density="compact" />
                </v-col>

                <!-- Precios actuales (solo lectura) -->
                <v-col v-if="product.current_purchase_price" cols="12" md="4">
                  <v-text-field
                    :model-value="`S/ ${Number(product.current_purchase_price).toFixed(4)}`"
                    label="Precio Compra Actual" variant="outlined" density="compact"
                    readonly bg-color="grey-lighten-4" />
                </v-col>
                <v-col v-if="product.current_sale_price" cols="12" md="4">
                  <v-text-field
                    :model-value="`S/ ${Number(product.current_sale_price).toFixed(2)}`"
                    label="Precio Venta Actual" variant="outlined" density="compact"
                    readonly bg-color="green-lighten-5" />
                </v-col>
              </v-row>
            </v-card-text>
            <v-divider />
            <v-card-actions class="pa-4 ga-2">
              <v-spacer />
              <v-btn variant="tonal" href="/products" :disabled="form.processing">Cancelar</v-btn>
              <v-btn type="submit" color="primary" :loading="form.processing"
                prepend-icon="mdi-content-save">
                Guardar Cambios
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-form>
  </div>
</template>