<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  requirements: Array,
  suppliers: Array,
  preselectedRequirementId: Number,
})

const form = useForm({
  requirement_id: props.preselectedRequirementId ?? null,
  deadline_date:  '',
  supplier_ids:   [],
})

function submit() {
  form.post('/quote-requests')
}
</script>

<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" href="/quote-requests" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Nueva Solicitud de Cotización</h1>
        <p class="text-body-2 text-medium-emphasis">
          Seleccione el requerimiento y los proveedores a invitar (Paso 3)
        </p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row>
        <v-col cols="12" md="8">
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-clipboard-list</v-icon>
              Requerimiento a Cotizar
            </v-card-title>
            <v-divider />
            <v-card-text class="pt-4">
              <v-row>
                <v-col cols="12" md="7">
                  <v-select
                    v-model="form.requirement_id"
                    :items="requirements"
                    item-title="code"
                    item-value="id"
                    label="Requerimiento *"
                    variant="outlined"
                    density="compact"
                    :error-messages="form.errors.requirement_id"
                  >
                    <template #item="{ item, props: p }">
                      <v-list-item v-bind="p">
                        <template #subtitle>{{ item.raw.justification?.slice(0, 60) }}</template>
                      </v-list-item>
                    </template>
                  </v-select>
                </v-col>
                <v-col cols="12" md="5">
                  <v-text-field
                    v-model="form.deadline_date"
                    label="Fecha límite de respuesta *"
                    type="date"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-calendar-clock"
                    :error-messages="form.errors.deadline_date"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Selección de proveedores -->
          <v-card variant="outlined" rounded="lg">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-truck-outline</v-icon>
              Proveedores a Invitar
              <v-chip size="x-small" color="primary" class="ml-2">
                {{ form.supplier_ids.length }} seleccionado(s)
              </v-chip>
            </v-card-title>
            <v-divider />

            <v-alert v-if="form.errors.supplier_ids" type="error"
              variant="tonal" density="compact" class="ma-3"
              :text="form.errors.supplier_ids" />

            <v-card-text>
              <v-row dense>
                <v-col v-for="supplier in suppliers" :key="supplier.id" cols="12" md="6">
                  <v-checkbox
                    v-model="form.supplier_ids"
                    :value="supplier.id"
                    density="compact"
                    hide-details
                  >
                    <template #label>
                      <div>
                        <div class="text-body-2 font-weight-medium">
                          {{ supplier.trade_name || supplier.business_name }}
                        </div>
                        <div class="text-caption text-medium-emphasis">
                          RUC: {{ supplier.tax_id }}
                        </div>
                      </div>
                    </template>
                  </v-checkbox>
                </v-col>
              </v-row>

              <v-alert v-if="suppliers.length === 0" type="warning"
                variant="tonal" density="compact" class="mt-3"
                text="No hay proveedores activos registrados. Registre proveedores primero." />
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" md="4">
          <v-card variant="outlined" rounded="lg" class="mb-4">
            <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
              <v-icon start color="primary">mdi-information-outline</v-icon>
              Información
            </v-card-title>
            <v-divider />
            <v-card-text>
              <v-alert type="info" variant="tonal" density="compact">
                Al guardar, se creará la solicitud de cotización (SC-YYYY-NNNN) y se
                registrarán los proveedores invitados en estado <strong>Pendiente</strong>.
              </v-alert>
            </v-card-text>
          </v-card>

          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <v-btn type="submit" color="primary" block size="large"
                :loading="form.processing" prepend-icon="mdi-send" class="mb-2">
                Crear y Enviar
              </v-btn>
              <v-btn variant="tonal" block href="/quote-requests"
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
