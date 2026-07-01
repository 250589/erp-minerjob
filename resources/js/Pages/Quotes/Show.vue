<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { computed } from 'vue'

const props = defineProps({ quote: Object })
const q = computed(() => props.quote)

const statusColors = {
  recibida:  'info',
  comparada: 'warning',
  aprobada:  'success',
  rechazada: 'error',
}
const statusLabels = {
  recibida:  'Recibida',
  comparada: 'Comparada',
  aprobada:  'Aprobada ✓',
  rechazada: 'Rechazada',
}

const itemHeaders = [
  { title: 'Descripción', key: 'description'                     },
  { title: 'Cant.',       key: 'quantity',    width:'80px',  align:'end' },
  { title: 'P. Unit.',    key: 'unit_price',  width:'110px', align:'end' },
  { title: 'Subtotal',    key: 'subtotal',    width:'120px', align:'end' },
]

// URL pública del archivo adjunto
const fileUrl = computed(() =>
  q.value.file_path ? `/storage/${q.value.file_path}` : null
)

const isImage = computed(() =>
  fileUrl.value && /\.(jpg|jpeg|png)$/i.test(fileUrl.value)
)

const isPdf = computed(() =>
  fileUrl.value && /\.pdf$/i.test(fileUrl.value)
)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap ga-3">
      <div class="d-flex align-center">
        <v-btn icon="mdi-arrow-left" variant="text"
          :href="`/quote-requests/${q.quote_request_id}`" class="mr-2" />
        <div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <h1 class="text-h5 font-weight-bold">
              Cotización {{ q.code ?? `#${q.id}` }}
            </h1>
            <v-chip
              :color="statusColors[q.status] ?? 'default'"
              size="small" label>
              {{ statusLabels[q.status] ?? q.status }}
            </v-chip>
          </div>
          <p class="text-body-2 text-medium-emphasis mt-1">
            {{ q.supplier?.business_name }}
          </p>
        </div>
      </div>

      <!-- Botón descargar archivo -->
      <v-btn
        v-if="fileUrl"
        :href="fileUrl"
        target="_blank"
        color="primary"
        variant="tonal"
        prepend-icon="mdi-paperclip">
        {{ isPdf ? 'Ver PDF' : 'Ver Imagen' }}
      </v-btn>
    </div>

    <v-row>
      <v-col cols="12" md="8">

        <!-- ─── Ítems cotizados ──────────────────────────────────────── -->
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-format-list-bulleted</v-icon>
            Ítems Cotizados
          </v-card-title>
          <v-divider />

          <v-data-table
            :headers="itemHeaders"
            :items="q.items ?? []"
            hide-default-footer
            density="comfortable">

            <template #item.quantity="{ item }">
              {{ Number(item.quantity).toFixed(2) }}
            </template>
            <template #item.unit_price="{ item }">
              {{ q.currency }} {{ Number(item.unit_price).toFixed(4) }}
            </template>
            <template #item.subtotal="{ item }">
              <span class="font-weight-medium">
                {{ q.currency }} {{ Number(item.subtotal).toFixed(2) }}
              </span>
            </template>

            <template #bottom>
              <v-divider />
              <div class="pa-4">
                <div class="d-flex justify-end">
                  <div style="min-width:220px">
                    <div class="d-flex justify-space-between py-1">
                      <span class="text-body-2 text-medium-emphasis">Subtotal</span>
                      <span class="text-body-2">
                        {{ q.currency }} {{ Number(q.subtotal).toFixed(2) }}
                      </span>
                    </div>
                    <div class="d-flex justify-space-between py-1">
                      <span class="text-body-2 text-medium-emphasis">IGV (18%)</span>
                      <span class="text-body-2">
                        {{ q.currency }} {{ Number(q.tax).toFixed(2) }}
                      </span>
                    </div>
                    <v-divider class="my-1" />
                    <div class="d-flex justify-space-between py-1">
                      <span class="text-subtitle-2 font-weight-bold">TOTAL</span>
                      <span class="text-subtitle-2 font-weight-bold text-primary">
                        {{ q.currency }} {{ Number(q.total).toFixed(2) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </v-data-table>

          <div v-if="q.notes" class="pa-4 pt-0">
            <v-alert type="info" variant="tonal" density="compact">
              <strong>Notas:</strong> {{ q.notes }}
            </v-alert>
          </div>
        </v-card>

        <!-- ─── Archivo adjunto ─────────────────────────────────────── -->
        <v-card v-if="fileUrl" variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-paperclip</v-icon>
            Documento de la Cotización
          </v-card-title>
          <v-divider />
          <v-card-text>
            <!-- Vista previa de imagen -->
            <div v-if="isImage" class="text-center">
              <v-img
                :src="fileUrl"
                max-height="500"
                contain
                rounded="lg"
                class="border" />
              <v-btn
                :href="fileUrl"
                target="_blank"
                color="primary"
                variant="tonal"
                prepend-icon="mdi-open-in-new"
                class="mt-3">
                Abrir en nueva pestaña
              </v-btn>
            </div>

            <!-- Visor de PDF -->
            <div v-if="isPdf">
              <iframe
                :src="fileUrl"
                width="100%"
                height="500px"
                style="border: 1px solid #e0e0e0; border-radius: 8px;"
                title="Cotización PDF" />
              <div class="d-flex ga-2 mt-3">
                <v-btn
                  :href="fileUrl"
                  target="_blank"
                  color="primary"
                  variant="tonal"
                  prepend-icon="mdi-open-in-new">
                  Abrir PDF
                </v-btn>
                <v-btn
                  :href="fileUrl"
                  :download="`cotizacion_${q.code ?? q.id}.pdf`"
                  color="secondary"
                  variant="tonal"
                  prepend-icon="mdi-download">
                  Descargar
                </v-btn>
              </div>
            </div>
          </v-card-text>
        </v-card>

        <!-- Sin archivo -->
        <v-alert v-else type="info" variant="tonal" density="compact">
          Esta cotización no tiene documento adjunto.
        </v-alert>
      </v-col>

      <!-- ─── Sidebar ───────────────────────────────────────────────── -->
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-information</v-icon>
            Detalles
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-domain</v-icon></template>
              <template #title><span class="text-caption">Proveedor</span></template>
              <template #subtitle>
                <strong>{{ q.supplier?.business_name }}</strong>
              </template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-currency-usd</v-icon></template>
              <template #title><span class="text-caption">Moneda</span></template>
              <template #subtitle>
                {{ q.currency }} (T/C: {{ Number(q.exchange_rate).toFixed(4) }})
              </template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-calendar-clock</v-icon></template>
              <template #title><span class="text-caption">Validez</span></template>
              <template #subtitle>{{ q.validity_date ?? '—' }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-truck-delivery</v-icon></template>
              <template #title><span class="text-caption">Entrega</span></template>
              <template #subtitle>{{ q.delivery_term_days }} día(s)</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-bank-outline</v-icon></template>
              <template #title><span class="text-caption">Plazo de pago</span></template>
              <template #subtitle>{{ q.payment_term_days }} día(s)</template>
            </v-list-item>
            <v-list-item>
              <template #prepend>
                <v-icon size="small" :color="fileUrl ? 'success' : 'grey'">
                  {{ fileUrl ? 'mdi-paperclip' : 'mdi-paperclip-off' }}
                </v-icon>
              </template>
              <template #title><span class="text-caption">Archivo adjunto</span></template>
              <template #subtitle>
                <span :class="fileUrl ? 'text-success' : 'text-medium-emphasis'">
                  {{ fileUrl ? 'Sí — disponible' : 'Sin archivo' }}
                </span>
              </template>
            </v-list-item>
          </v-list>
        </v-card>

        <v-btn variant="tonal" block prepend-icon="mdi-file-compare"
          :href="`/quote-requests/${q.quote_request_id}`">
          Ver Solicitud de Cotización
        </v-btn>
      </v-col>
    </v-row>
  </div>
</template>