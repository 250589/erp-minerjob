<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps({ invoice: Object })

const inv         = computed(() => props.invoice)
const activeTab   = ref('factura')
const obsDialog   = ref(false)
const obsForm     = useForm({ comment: '' })

function startReview() {
  router.patch(`/invoices/${inv.value.id}/start-review`)
}
function validate() {
  if (confirm('¿Validar esta factura? Se marcará como conforme con la OC.')) {
    router.patch(`/invoices/${inv.value.id}/validate`)
  }
}
function submitObservation() {
  obsForm.post(`/invoices/${inv.value.id}/observe`, {
    onSuccess: () => { obsDialog.value = false; obsForm.reset() },
  })
}

const itemHeaders = [
  { title: '#',           key: 'index',      width: '50px'  },
  { title: 'Descripción', key: 'description'                },
  { title: 'Cantidad',    key: 'quantity',   width: '100px', align: 'end' },
  { title: 'P. Unitario', key: 'unit_price', width: '130px', align: 'end' },
  { title: 'Subtotal',    key: 'subtotal',   width: '130px', align: 'end' },
]

const itemsWithIndex = computed(() =>
  inv.value.items.map((item, i) => ({ ...item, index: i + 1 }))
)

function fmt(val) {
  return val !== null ? `${inv.value.currency} ${Number(val).toFixed(2)}` : '—'
}

// Stepper de estado
const steps = [
  { key: 'recibida',    label: 'Recibida',   icon: 'mdi-inbox-arrow-down'   },
  { key: 'en_revision', label: 'En Revisión',icon: 'mdi-magnify'            },
  { key: 'validada',    label: 'Validada',   icon: 'mdi-check'              },
  { key: 'registrada',  label: 'Registrada', icon: 'mdi-book-check'        },
  { key: 'pagada',      label: 'Pagada',     icon: 'mdi-currency-usd'       },
]

const statusOrder = ['recibida','en_revision','observada','validada','registrada','pagada']

function stepColor(stepKey) {
  const curr = statusOrder.indexOf(inv.value.status)
  const step = statusOrder.indexOf(stepKey)
  if (inv.value.status === 'observada' && stepKey === 'en_revision') return 'warning'
  if (step <= curr) return 'primary'
  return 'grey-lighten-2'
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap ga-3">
      <div class="d-flex align-center">
        <v-btn icon="mdi-arrow-left" variant="text" href="/invoices" class="mr-2" />
        <div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <h1 class="text-h5 font-weight-bold">{{ inv.full_number }}</h1>
            <v-chip :color="inv.status_color" size="small" label>
              {{ inv.status_label }}
            </v-chip>
          </div>
          <p class="text-body-2 text-medium-emphasis mt-1">
            {{ inv.supplier?.business_name }} · OC: {{ inv.purchase_order?.code }}
          </p>
        </div>
      </div>

      <!-- Acciones por estado -->
      <div class="d-flex ga-2 flex-wrap">
        <v-btn v-if="inv.status === 'recibida'" color="info" variant="tonal"
          prepend-icon="mdi-magnify" @click="startReview">
          Iniciar Revisión
        </v-btn>
        <template v-if="inv.status === 'en_revision' || inv.status === 'observada'">
          <v-btn color="warning" variant="tonal"
            prepend-icon="mdi-alert-circle" @click="obsDialog = true">
            Observar
          </v-btn>
          <v-btn color="success" prepend-icon="mdi-check"
            @click="validate">
            Validar Factura
          </v-btn>
        </template>
        <v-btn v-if="inv.status === 'validada'" color="primary"
          prepend-icon="mdi-book-plus"
          :href="`/invoices/${inv.id}/accounting-entry/create`">
          Registrar Asiento
        </v-btn>
      </div>
    </div>

    <!-- Stepper de flujo -->
    <v-card variant="outlined" rounded="lg" class="mb-4">
      <v-card-text class="py-3">
        <div class="d-flex align-center justify-space-around flex-wrap ga-2">
          <template v-for="(step, i) in steps" :key="step.key">
            <div class="d-flex align-center flex-column" style="min-width:80px">
              <v-avatar :color="stepColor(step.key)" size="36">
                <v-icon color="white" size="18">{{ step.icon }}</v-icon>
              </v-avatar>
              <span class="text-caption mt-1 text-center">{{ step.label }}</span>
              <v-chip v-if="inv.status === 'observada' && step.key === 'en_revision'"
                color="warning" size="x-small" class="mt-1">Observada</v-chip>
            </div>
            <v-divider v-if="i < steps.length - 1" class="flex-grow-1" style="max-width:50px" />
          </template>
        </div>
      </v-card-text>
    </v-card>

    <!-- Tabs -->
    <v-card variant="outlined" rounded="lg">
      <v-tabs v-model="activeTab" color="primary">
        <v-tab value="factura" prepend-icon="mdi-file-document">Factura</v-tab>
        <v-tab value="observaciones" prepend-icon="mdi-alert-circle-outline">
          Observaciones
          <v-chip v-if="inv.observations?.length" size="x-small" color="warning" class="ml-1">
            {{ inv.observations.length }}
          </v-chip>
        </v-tab>
        <v-tab v-if="inv.accounting_entry" value="asiento" prepend-icon="mdi-book-open">
          Asiento Contable
        </v-tab>
      </v-tabs>
      <v-divider />

      <v-tabs-window v-model="activeTab">

        <!-- Tab 1: Factura -->
        <v-tabs-window-item value="factura">
          <v-card-text>
            <v-row class="mb-4">
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Proveedor</div>
                <div class="font-weight-bold">{{ inv.supplier?.business_name }}</div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">F. Emisión</div>
                <div>{{ inv.issue_date }}</div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Moneda / T.C.</div>
                <div>{{ inv.currency }} / {{ inv.exchange_rate }}</div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">OC Vinculada</div>
                <div>{{ inv.purchase_order?.code }}</div>
              </v-col>
            </v-row>

            <v-data-table :headers="itemHeaders" :items="itemsWithIndex"
              hide-default-footer density="compact">
              <template #item.quantity="{ item }">{{ Number(item.quantity).toFixed(2) }}</template>
              <template #item.unit_price="{ item }">{{ fmt(item.unit_price) }}</template>
              <template #item.subtotal="{ item }">{{ fmt(item.subtotal) }}</template>
              <template #bottom>
                <v-divider />
                <div class="d-flex flex-column align-end pa-4 ga-1">
                  <span class="text-body-2">Subtotal: <strong>{{ fmt(inv.subtotal) }}</strong></span>
                  <span class="text-body-2">IGV 18%: <strong>{{ fmt(inv.tax) }}</strong></span>
                  <span class="text-h6 font-weight-bold text-primary">
                    TOTAL: {{ fmt(inv.total) }}
                  </span>
                </div>
              </template>
            </v-data-table>
          </v-card-text>
        </v-tabs-window-item>

        <!-- Tab 2: Observaciones -->
        <v-tabs-window-item value="observaciones">
          <v-card-text>
            <div v-if="inv.observations?.length === 0" class="text-center pa-6 text-medium-emphasis">
              <v-icon size="48">mdi-check-circle-outline</v-icon>
              <p class="text-body-2 mt-2">Sin observaciones registradas</p>
            </div>
            <v-timeline v-else density="compact" side="end">
              <v-timeline-item
                v-for="obs in inv.observations"
                :key="obs.id"
                :dot-color="obs.resolved_at ? 'success' : 'warning'"
                size="small"
              >
                <v-card variant="outlined" rounded="lg">
                  <v-card-text class="pa-3">
                    <div class="d-flex justify-space-between align-start">
                      <div>
                        <p class="text-body-2">{{ obs.comment }}</p>
                        <p class="text-caption text-medium-emphasis mt-1">
                          {{ obs.created_by?.name }} · {{ obs.created_at }}
                        </p>
                      </div>
                      <v-chip :color="obs.resolved_at ? 'success' : 'warning'"
                        size="x-small" label class="ml-2">
                        {{ obs.resolved_at ? 'Resuelta' : 'Pendiente' }}
                      </v-chip>
                    </div>
                  </v-card-text>
                </v-card>
              </v-timeline-item>
            </v-timeline>
          </v-card-text>
        </v-tabs-window-item>

        <!-- Tab 3: Asiento Contable -->
        <v-tabs-window-item value="asiento">
          <v-card-text v-if="inv.accounting_entry">
            <v-row class="mb-3">
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">N° Asiento</div>
                <div class="font-weight-bold">{{ inv.accounting_entry.entry_number }}</div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Fecha</div>
                <div>{{ inv.accounting_entry.entry_date }}</div>
              </v-col>
              <v-col cols="12" md="6">
                <div class="text-caption text-medium-emphasis">Descripción</div>
                <div>{{ inv.accounting_entry.description }}</div>
              </v-col>
            </v-row>

            <v-table density="compact">
              <thead>
                <tr>
                  <th>Cuenta</th>
                  <th>Descripción</th>
                  <th class="text-right">DEBE</th>
                  <th class="text-right">HABER</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="detail in inv.accounting_entry.details" :key="detail.id">
                  <td class="text-body-2">
                    <strong>{{ detail.account?.code }}</strong>
                    {{ detail.account?.name }}
                  </td>
                  <td class="text-caption">{{ detail.description }}</td>
                  <td class="text-right text-body-2">
                    {{ Number(detail.debit) > 0 ? Number(detail.debit).toFixed(2) : '—' }}
                  </td>
                  <td class="text-right text-body-2">
                    {{ Number(detail.credit) > 0 ? Number(detail.credit).toFixed(2) : '—' }}
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="font-weight-bold">
                  <td colspan="2" class="text-right">TOTALES:</td>
                  <td class="text-right">{{ Number(inv.accounting_entry.total_debit).toFixed(2) }}</td>
                  <td class="text-right">{{ Number(inv.accounting_entry.total_credit).toFixed(2) }}</td>
                </tr>
              </tfoot>
            </v-table>

            <v-alert type="success" variant="tonal" density="compact" class="mt-4">
              <strong>Factura registrada con asiento contable. Lista para pago. (Paso 15)</strong>
            </v-alert>
          </v-card-text>
        </v-tabs-window-item>
      </v-tabs-window>
    </v-card>

    <!-- Dialog: Observar factura (Paso 12A) -->
    <v-dialog v-model="obsDialog" max-width="480">
      <v-card rounded="lg">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="warning" class="mr-2">mdi-alert-circle</v-icon>
          Registrar Observación (Paso 12A)
        </v-card-title>
        <v-card-text>
          <v-alert type="warning" variant="tonal" density="compact" class="mb-4">
            La factura pasará a estado <strong>Observada</strong> y se
            solicitará corrección al proveedor (línea punteada del flujograma).
          </v-alert>
          <v-textarea v-model="obsForm.comment" label="Detalle de la observación *"
            variant="outlined" density="compact" rows="4"
            :error-messages="obsForm.errors.comment"
            hint="Describa la discrepancia encontrada respecto a la OC" />
        </v-card-text>
        <v-card-actions class="pa-4 pt-2">
          <v-spacer />
          <v-btn variant="text" @click="obsDialog = false">Cancelar</v-btn>
          <v-btn color="warning" :loading="obsForm.processing"
            @click="submitObservation">
            Registrar Observación
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
