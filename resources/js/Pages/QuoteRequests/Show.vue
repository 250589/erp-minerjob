<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ quoteRequest: Object })

const activeTab       = ref('solicitud')
const selectedQuoteId = ref(null)
const confirmDialog   = ref(false)

const qr      = computed(() => props.quoteRequest)
const quotes  = computed(() => qr.value.quotes ?? [])
const reqItems = computed(() => qr.value.requirement?.items ?? [])

// ─── Comparativo automático (Paso 5) ─────────────────────────────────────────
// Construye la matriz: filas = ítems del requerimiento, columnas = cotizaciones
const comparisonMatrix = computed(() => {
  const activeQuotes = quotes.value.filter(q => q.status !== 'rechazada')
  if (!activeQuotes.length || !reqItems.value.length) return []

  return reqItems.value.map((reqItem) => {
    const prices = activeQuotes.map((quote) => {
      const qItem = quote.items.find(i => i.requirement_item_id === reqItem.id)
      return {
        quote_id:    quote.id,
        unit_price:  qItem ? Number(qItem.unit_price) : null,
        subtotal:    qItem ? Number(qItem.subtotal)   : null,
      }
    })

    const validPrices  = prices.filter(p => p.unit_price !== null).map(p => p.unit_price)
    const minUnitPrice = validPrices.length ? Math.min(...validPrices) : null

    return {
      ...reqItem,
      prices: prices.map(p => ({
        ...p,
        is_best: p.unit_price !== null && p.unit_price === minUnitPrice,
      })),
    }
  })
})

// Totales por cotización para el resumen inferior del comparativo
const quoteTotals = computed(() => {
  const activeQuotes = quotes.value.filter(q => q.status !== 'rechazada')
  return activeQuotes.map(q => ({
    quote_id:            q.id,
    supplier_name:       q.supplier?.business_name,
    currency:            q.currency,
    total:               Number(q.total),
    payment_term_days:   q.payment_term_days,
    delivery_term_days:  q.delivery_term_days,
    validity_date:       q.validity_date,
    status:              q.status,
    status_color:        q.status_color,
    status_label:        q.status_label,
  }))
})

const minTotal = computed(() => {
  const totals = quoteTotals.value.map(q => q.total).filter(Boolean)
  return totals.length ? Math.min(...totals) : null
})

function openConfirmDialog(quoteId) {
  selectedQuoteId.value = quoteId
  confirmDialog.value   = true
}

function confirmSelectWinner() {
  router.post(`/quote-requests/${qr.value.id}/select-winner`, {
    quote_id: selectedQuoteId.value,
  }, { onSuccess: () => { confirmDialog.value = false } })
}

function closeRequest() {
  if (confirm('¿Cerrar esta solicitud sin seleccionar ganador?')) {
    router.patch(`/quote-requests/${qr.value.id}/close`)
  }
}

function currency(value) {
  return value !== null && value !== undefined
    ? `S/ ${Number(value).toFixed(2)}`
    : '—'
}

// Headers tabla solicitud - proveedores
const supplierHeaders = [
  { title: 'Proveedor',  key: 'supplier.business_name' },
  { title: 'RUC',        key: 'supplier.tax_id',   width: '130px' },
  { title: 'Enviado',    key: 'sent_at',            width: '140px' },
  { title: 'Respondió',  key: 'responded_at',       width: '140px' },
  { title: 'Estado',     key: 'status',             width: '120px' },
]

// Headers tabla cotizaciones recibidas
const quoteHeaders = [
  { title: 'Proveedor',     key: 'supplier.business_name' },
  { title: 'N° Cotización', key: 'code',              width: '130px' },
  { title: 'Moneda',        key: 'currency',          width: '90px' },
  { title: 'Total',         key: 'total',             width: '120px', align: 'end' },
  { title: 'Pago (días)',   key: 'payment_term_days', width: '110px', align: 'center' },
  { title: 'Entrega (días)',key: 'delivery_term_days',width: '120px', align: 'center' },
  { title: 'Estado',        key: 'status',            width: '120px' },
  { title: 'Acción',        key: 'actions',           width: '80px', sortable: false },
]

const supplierStatusColors = { pendiente: 'warning', respondido: 'success', declinado: 'error' }
const supplierStatusLabels = { pendiente: 'Pendiente', respondido: 'Respondió', declinado: 'Declinó' }
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap ga-3">
      <div class="d-flex align-center">
        <v-btn icon="mdi-arrow-left" variant="text" href="/quote-requests" class="mr-2" />
        <div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <h1 class="text-h5 font-weight-bold">{{ qr.code }}</h1>
            <v-chip :color="qr.status_color" size="small" label>
              {{ qr.status_label }}
            </v-chip>
          </div>
          <p class="text-body-2 text-medium-emphasis mt-1">
            Requerimiento: <strong>{{ qr.requirement?.code }}</strong>
            · Fecha límite: {{ qr.deadline_date ?? '—' }}
          </p>
        </div>
      </div>
      <div class="d-flex ga-2">
        <v-btn
          v-if="qr.status === 'abierta'"
          variant="tonal" color="primary"
          prepend-icon="mdi-file-plus"
          :href="`/quote-requests/${qr.id}/quotes/create`"
        >
          Registrar Cotización
        </v-btn>
        <v-btn
          v-if="qr.status === 'abierta'"
          variant="tonal" color="error"
          prepend-icon="mdi-close"
          @click="closeRequest"
        >
          Cerrar Sin Ganador
        </v-btn>
      </div>
    </div>

    <!-- Tabs -->
    <v-card variant="outlined" rounded="lg">
      <v-tabs v-model="activeTab" color="primary">
        <v-tab value="solicitud" prepend-icon="mdi-clipboard-text">
          Solicitud
        </v-tab>
        <v-tab value="cotizaciones" prepend-icon="mdi-file-multiple">
          Cotizaciones
          <v-chip size="x-small" color="primary" class="ml-1">{{ quotes.length }}</v-chip>
        </v-tab>
        <v-tab value="comparativo" prepend-icon="mdi-table-eye">
          Comparativo
        </v-tab>
      </v-tabs>
      <v-divider />

      <v-tabs-window v-model="activeTab">

        <!-- ─── Tab 1: Solicitud y Proveedores ─────────────── -->
        <v-tabs-window-item value="solicitud">
          <v-card-text>
            <!-- Info básica -->
            <v-row class="mb-4">
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Código</div>
                <div class="font-weight-bold">{{ qr.code }}</div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Requerimiento</div>
                <div class="font-weight-bold">{{ qr.requirement?.code }}</div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Fecha de envío</div>
                <div>{{ qr.sent_date ?? '—' }}</div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Fecha límite</div>
                <div>{{ qr.deadline_date ?? '—' }}</div>
              </v-col>
            </v-row>

            <!-- Justificación del requerimiento -->
            <v-alert v-if="qr.requirement?.justification"
              type="info" variant="tonal" density="compact" class="mb-4">
              <strong>Justificación:</strong> {{ qr.requirement.justification }}
            </v-alert>

            <!-- Ítems del requerimiento -->
            <h3 class="text-subtitle-2 font-weight-bold mb-2">Ítems solicitados</h3>
            <v-table density="compact" class="mb-4">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Descripción</th>
                  <th class="text-right">Cantidad</th>
                  <th>Unidad</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, i) in reqItems" :key="item.id">
                  <td class="text-medium-emphasis">{{ i + 1 }}</td>
                  <td>{{ item.description }}</td>
                  <td class="text-right">{{ Number(item.quantity).toFixed(2) }}</td>
                  <td>{{ item.unit?.abbreviation }}</td>
                </tr>
              </tbody>
            </v-table>

            <!-- Proveedores invitados -->
            <h3 class="text-subtitle-2 font-weight-bold mb-2">Proveedores invitados</h3>
            <v-data-table :headers="supplierHeaders" :items="qr.suppliers"
              hide-default-footer density="compact">
              <template #item.status="{ item }">
                <v-chip :color="supplierStatusColors[item.status]" size="small" label>
                  {{ supplierStatusLabels[item.status] }}
                </v-chip>
              </template>
              <template #item.sent_at="{ item }">
                <span class="text-caption">{{ item.sent_at ?? '—' }}</span>
              </template>
              <template #item.responded_at="{ item }">
                <span class="text-caption">{{ item.responded_at ?? '—' }}</span>
              </template>
            </v-data-table>
          </v-card-text>
        </v-tabs-window-item>

        <!-- ─── Tab 2: Cotizaciones Recibidas (Paso 4) ──────── -->
        <v-tabs-window-item value="cotizaciones">
          <v-card-text>
            <div v-if="quotes.length === 0" class="text-center pa-8">
              <v-icon size="64" color="grey-lighten-2">mdi-file-document-outline</v-icon>
              <p class="text-h6 text-medium-emphasis mt-3">Sin cotizaciones aún</p>
              <v-btn v-if="qr.status === 'abierta'" color="primary"
                prepend-icon="mdi-plus"
                :href="`/quote-requests/${qr.id}/quotes/create`" class="mt-3">
                Registrar Primera Cotización
              </v-btn>
            </div>

            <v-data-table v-else :headers="quoteHeaders" :items="quotes"
              hide-default-footer density="comfortable">

              <template #item.total="{ item }">
                <span class="font-weight-medium">
                  {{ item.currency }} {{ Number(item.total).toFixed(2) }}
                </span>
              </template>

              <template #item.status="{ item }">
                <v-chip :color="item.status_color" size="small" label>
                  {{ item.status_label }}
                </v-chip>
              </template>

              <template #item.actions="{ item }">
                <v-btn icon="mdi-eye" variant="text" size="small" color="primary"
                  :href="`/quotes/${item.id}`" />
              </template>
            </v-data-table>
          </v-card-text>
        </v-tabs-window-item>

        <!-- ─── Tab 3: Comparativo Automático (Paso 5) ──────── -->
        <v-tabs-window-item value="comparativo">
          <v-card-text>
            <div v-if="quoteTotals.length < 2" class="text-center pa-8">
              <v-icon size="64" color="grey-lighten-2">mdi-table-eye</v-icon>
              <p class="text-h6 text-medium-emphasis mt-3">
                Se necesitan al menos 2 cotizaciones para comparar
              </p>
            </div>

            <div v-else>
              <v-alert type="info" variant="tonal" density="compact" class="mb-4">
                Las celdas en <strong class="text-success">verde</strong> muestran el mejor
                precio por ítem. Seleccione la cotización ganadora para enviar a aprobación.
              </v-alert>

              <!-- Tabla comparativa -->
              <div class="overflow-x-auto">
                <v-table density="compact" class="comparison-table">
                  <thead>
                    <tr>
                      <th style="min-width:200px">Ítem</th>
                      <th class="text-center" style="min-width:60px">Cant.</th>
                      <th v-for="qt in quoteTotals" :key="qt.quote_id"
                        class="text-center" style="min-width:140px">
                        <div class="font-weight-bold">{{ qt.supplier_name }}</div>
                        <div class="text-caption text-medium-emphasis">{{ qt.currency }}</div>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in comparisonMatrix" :key="row.id">
                      <td>
                        <div class="text-body-2">{{ row.description }}</div>
                      </td>
                      <td class="text-center text-body-2">
                        {{ Number(row.quantity).toFixed(2) }}
                        {{ row.unit?.abbreviation }}
                      </td>
                      <td v-for="price in row.prices" :key="price.quote_id"
                        class="text-center">
                        <span v-if="price.unit_price !== null"
                          :class="price.is_best ? 'text-success font-weight-bold' : 'text-body-2'">
                          <v-icon v-if="price.is_best" size="x-small" color="success">
                            mdi-check-circle
                          </v-icon>
                          {{ Number(price.unit_price).toFixed(2) }}
                        </span>
                        <span v-else class="text-medium-emphasis">—</span>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="font-weight-bold bg-grey-lighten-4">
                      <td colspan="2" class="text-right pr-4">TOTAL (con IGV):</td>
                      <td v-for="qt in quoteTotals" :key="qt.quote_id" class="text-center">
                        <span :class="qt.total === minTotal ? 'text-success font-weight-bold' : ''">
                          {{ qt.currency }} {{ qt.total.toFixed(2) }}
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2" class="text-right pr-4 text-caption">Plazo pago:</td>
                      <td v-for="qt in quoteTotals" :key="qt.quote_id"
                        class="text-center text-caption">
                        {{ qt.payment_term_days }} días
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2" class="text-right pr-4 text-caption">Plazo entrega:</td>
                      <td v-for="qt in quoteTotals" :key="qt.quote_id"
                        class="text-center text-caption">
                        {{ qt.delivery_term_days }} días
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2" class="text-right pr-4 text-caption">Válida hasta:</td>
                      <td v-for="qt in quoteTotals" :key="qt.quote_id"
                        class="text-center text-caption">
                        {{ qt.validity_date ?? '—' }}
                      </td>
                    </tr>
                    <!-- Fila de selección -->
                    <tr v-if="qr.status === 'abierta'">
                      <td colspan="2"></td>
                      <td v-for="qt in quoteTotals" :key="qt.quote_id"
                        class="text-center pa-2">
                        <v-btn
                          v-if="qt.status !== 'aprobada' && qt.status !== 'comparada'"
                          color="primary"
                          size="small"
                          variant="tonal"
                          @click="openConfirmDialog(qt.quote_id)"
                        >
                          Seleccionar
                        </v-btn>
                        <v-chip v-else color="success" size="small" label>
                          Seleccionada
                        </v-chip>
                      </td>
                    </tr>
                  </tfoot>
                </v-table>
              </div>
            </div>
          </v-card-text>
        </v-tabs-window-item>
      </v-tabs-window>
    </v-card>

    <!-- Dialog confirmación selección ganador -->
    <v-dialog v-model="confirmDialog" max-width="420">
      <v-card rounded="lg">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="warning" class="mr-2">mdi-alert</v-icon>
          Confirmar selección
        </v-card-title>
        <v-card-text>
          Al seleccionar esta cotización, las demás quedarán como
          <strong>rechazadas</strong> y la solicitud se <strong>cerrará</strong>.
          La cotización seleccionada pasará a aprobación gerencial.
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="confirmDialog = false">Cancelar</v-btn>
          <v-btn color="primary" @click="confirmSelectWinner">Confirmar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
