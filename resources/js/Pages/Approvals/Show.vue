<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({ approval: Object })

const quote    = computed(() => props.approval.approvable)
const qRequest = computed(() => quote.value?.quote_request)
const req      = computed(() => qRequest.value?.requirement)
const allQuotes = computed(() => qRequest.value?.quotes ?? [])

// Comparativo: otras cotizaciones recibidas
const otherQuotes = computed(() =>
  allQuotes.value.filter(q => q.id !== quote.value?.id)
)

const isPending = computed(() => props.approval.status === 'pendiente')

// Formulario de decisión
const form = useForm({
  status:   '',
  comments: '',
})

const confirmDialog  = ref(false)
const pendingDecision = ref('')

function openDialog(decision) {
  pendingDecision.value = decision
  confirmDialog.value   = true
}

function submitDecision() {
  form.status = pendingDecision.value
  form.post(`/approvals/${props.approval.id}/decide`, {
    onSuccess: () => { confirmDialog.value = false },
  })
}

function currency(val, cur = 'PEN') {
  return val !== null && val !== undefined
    ? `${cur} ${Number(val).toFixed(2)}`
    : '—'
}

const itemHeaders = [
  { title: '#',           key: 'index',      width: '50px' },
  { title: 'Descripción', key: 'description'               },
  { title: 'Cantidad',    key: 'quantity',   width: '100px', align: 'end' },
  { title: 'P. Unitario', key: 'unit_price', width: '130px', align: 'end' },
  { title: 'Subtotal',    key: 'subtotal',   width: '130px', align: 'end' },
]

const itemsWithIndex = computed(() =>
  (quote.value?.items ?? []).map((item, i) => ({ ...item, index: i + 1 }))
)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap ga-3">
      <div class="d-flex align-center">
        <v-btn icon="mdi-arrow-left" variant="text" href="/approvals" class="mr-2" />
        <div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <h1 class="text-h5 font-weight-bold">
              Evaluación de Compra
            </h1>
            <v-chip :color="approval.status_color" size="small" label>
              {{ approval.status_label }}
            </v-chip>
          </div>
          <p class="text-body-2 text-medium-emphasis mt-1">
            Cotización seleccionada por Compras · Requerimiento:
            <strong>{{ req?.code }}</strong>
          </p>
        </div>
      </div>

      <!-- Botones de decisión (solo si está pendiente) -->
      <div v-if="isPending" class="d-flex ga-2">
        <v-btn color="error" variant="tonal" prepend-icon="mdi-close-circle"
          @click="openDialog('rechazado')">
          Rechazar
        </v-btn>
        <v-btn color="success" prepend-icon="mdi-check-circle"
          @click="openDialog('aprobado')">
          Aprobar y Generar OC
        </v-btn>
      </div>

      <!-- Resultado si ya fue decidida -->
      <div v-else>
        <v-alert
          :type="approval.status === 'aprobado' ? 'success' : 'error'"
          variant="tonal"
          density="compact"
        >
          {{ approval.status === 'aprobado' ? 'Compra aprobada' : 'Compra rechazada' }}
          por <strong>{{ approval.approver?.name }}</strong>
          · {{ approval.decided_at }}
        </v-alert>
      </div>
    </div>

    <v-row>
      <!-- ─── Columna principal ─────────────────────────── -->
      <v-col cols="12" md="8">

        <!-- Datos de la cotización seleccionada -->
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="success">mdi-star-circle</v-icon>
            Cotización Seleccionada por Compras
          </v-card-title>
          <v-divider />
          <v-card-text>
            <v-row class="mb-3">
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Proveedor</div>
                <div class="font-weight-bold">{{ quote?.supplier?.business_name }}</div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">N° Cotización</div>
                <div>{{ quote?.code ?? '—' }}</div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Moneda</div>
                <div>{{ quote?.currency }}</div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Tipo de Cambio</div>
                <div>{{ quote?.exchange_rate }}</div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Plazo de Pago</div>
                <div>{{ quote?.payment_term_days }} días</div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Plazo de Entrega</div>
                <div>{{ quote?.delivery_term_days }} días</div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Válida hasta</div>
                <div>{{ quote?.validity_date ?? '—' }}</div>
              </v-col>
            </v-row>

            <!-- Ítems -->
            <v-data-table
              :headers="itemHeaders"
              :items="itemsWithIndex"
              hide-default-footer
              density="compact"
            >
              <template #item.quantity="{ item }">
                {{ Number(item.quantity).toFixed(2) }}
              </template>
              <template #item.unit_price="{ item }">
                {{ currency(item.unit_price, quote?.currency) }}
              </template>
              <template #item.subtotal="{ item }">
                {{ currency(item.subtotal, quote?.currency) }}
              </template>
              <template #bottom>
                <v-divider />
                <div class="d-flex flex-column align-end pa-3 ga-1">
                  <div class="text-body-2">
                    Subtotal: <strong>{{ currency(quote?.subtotal, quote?.currency) }}</strong>
                  </div>
                  <div class="text-body-2">
                    IGV (18%): <strong>{{ currency(quote?.tax, quote?.currency) }}</strong>
                  </div>
                  <div class="text-subtitle-1 font-weight-bold text-primary">
                    TOTAL: {{ currency(quote?.total, quote?.currency) }}
                  </div>
                </div>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>

        <!-- Comparativo con otras cotizaciones -->
        <v-card v-if="otherQuotes.length > 0" variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-table-eye</v-icon>
            Otras Cotizaciones Recibidas
          </v-card-title>
          <v-divider />
          <v-simple-table density="compact">
            <thead>
              <tr>
                <th>Proveedor</th>
                <th class="text-right">Total</th>
                <th class="text-center">Pago</th>
                <th class="text-center">Entrega</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="q in otherQuotes" :key="q.id">
                <td>{{ q.supplier?.business_name }}</td>
                <td class="text-right text-body-2">
                  {{ q.currency }} {{ Number(q.total).toFixed(2) }}
                </td>
                <td class="text-center text-caption">{{ q.payment_term_days }}d</td>
                <td class="text-center text-caption">{{ q.delivery_term_days }}d</td>
                <td>
                  <v-chip :color="q.status_color" size="x-small" label>
                    {{ q.status_label }}
                  </v-chip>
                </td>
              </tr>
            </tbody>
          </v-simple-table>
        </v-card>
      </v-col>

      <!-- ─── Sidebar información ───────────────────── -->
      <v-col cols="12" md="4">

        <!-- Requerimiento original -->
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-clipboard-list</v-icon>
            Requerimiento Original
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-identifier</v-icon></template>
              <template #title><span class="text-caption">Código</span></template>
              <template #subtitle><span class="font-weight-bold">{{ req?.code }}</span></template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-clipboard-text</v-icon></template>
              <template #title><span class="text-caption">Solicitud de Cotización</span></template>
              <template #subtitle>{{ qRequest?.code }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-account</v-icon></template>
              <template #title><span class="text-caption">Enviado a aprobación por</span></template>
              <template #subtitle>{{ approval.requested_by?.name ?? '—' }}</template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="small">mdi-calendar</v-icon></template>
              <template #title><span class="text-caption">Fecha</span></template>
              <template #subtitle>{{ approval.created_at }}</template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Comentario de la decisión (si ya fue decidida) -->
        <v-card v-if="!isPending && approval.comments" variant="outlined" rounded="lg">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start :color="approval.status === 'aprobado' ? 'success' : 'error'">
              mdi-comment-text
            </v-icon>
            Comentario de Gerencia
          </v-card-title>
          <v-divider />
          <v-card-text class="text-body-2">{{ approval.comments }}</v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- ─── Dialog de confirmación ─────────────────────── -->
    <v-dialog v-model="confirmDialog" max-width="480">
      <v-card rounded="lg">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon
            :color="pendingDecision === 'aprobado' ? 'success' : 'error'"
            class="mr-2"
          >
            {{ pendingDecision === 'aprobado' ? 'mdi-check-circle' : 'mdi-close-circle' }}
          </v-icon>
          {{ pendingDecision === 'aprobado' ? 'Confirmar Aprobación' : 'Confirmar Rechazo' }}
        </v-card-title>

        <v-card-text>
          <v-alert
            :type="pendingDecision === 'aprobado' ? 'success' : 'error'"
            variant="tonal"
            density="compact"
            class="mb-4"
          >
            <template v-if="pendingDecision === 'aprobado'">
              Se generará automáticamente la <strong>Orden de Compra</strong> y
              el requerimiento pasará a estado <strong>Convertido a OC</strong>.
            </template>
            <template v-else>
              La cotización será <strong>rechazada</strong> y Compras deberá
              solicitar nuevas cotizaciones para el requerimiento.
            </template>
          </v-alert>

          <v-textarea
            v-model="form.comments"
            label="Comentarios / Observaciones"
            variant="outlined"
            density="compact"
            rows="3"
            hint="Opcional: explique los motivos de su decisión"
            persistent-hint
          />
        </v-card-text>

        <v-card-actions class="pa-4 pt-2">
          <v-spacer />
          <v-btn variant="text" :disabled="form.processing"
            @click="confirmDialog = false">
            Cancelar
          </v-btn>
          <v-btn
            :color="pendingDecision === 'aprobado' ? 'success' : 'error'"
            :loading="form.processing"
            @click="submitDecision"
          >
            {{ pendingDecision === 'aprobado' ? 'Aprobar' : 'Rechazar' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
