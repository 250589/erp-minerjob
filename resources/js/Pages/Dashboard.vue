<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  stats:             Object,
  recentKardex:      Array,
  pendingApprovals:  Array,
  pendingReceptions: Array,
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

// ─── Alertas urgentes ─────────────────────────────────────────────────────────
const alerts = computed(() => {
  const a = []
  if (props.stats.payments.vencidas > 0)
    a.push({ type: 'error', icon: 'mdi-alert-circle', text: `${props.stats.payments.vencidas} obligacion(es) de pago VENCIDA(S)`, href: '/payments?status=pendiente' })
  if (props.stats.approvals.pendiente > 0)
    a.push({ type: 'warning', icon: 'mdi-gavel', text: `${props.stats.approvals.pendiente} aprobación(es) esperando decisión`, href: '/approvals' })
  if (props.stats.stock.bajo_minimo > 0)
    a.push({ type: 'warning', icon: 'mdi-package-variant-remove', text: `${props.stats.stock.bajo_minimo} producto(s) por debajo del stock mínimo`, href: '/stock' })
  if (props.stats.transfers.en_transito > 0)
    a.push({ type: 'info', icon: 'mdi-truck-delivery', text: `${props.stats.transfers.en_transito} traslado(s) en tránsito esperando recepción`, href: '/transfers' })
  return a
})

// ─── Cards de métricas principales ───────────────────────────────────────────
const metricCards = computed(() => [
  {
    title:    'Requerimientos',
    value:    props.stats.requirements.pendiente,
    subtitle: `${props.stats.requirements.en_cotizacion} en cotización`,
    icon:     'mdi-clipboard-list-outline',
    color:    'primary',
    href:     '/requirements',
  },
  {
    title:    'Aprobaciones',
    value:    props.stats.approvals.pendiente,
    subtitle: 'pendientes de decisión',
    icon:     'mdi-gavel',
    color:    props.stats.approvals.pendiente > 0 ? 'error' : 'success',
    href:     '/approvals',
  },
  {
    title:    'Por Pagar',
    value:    `S/ ${Number(props.stats.payments.total_amount).toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2})}`,
    subtitle: `${props.stats.payments.pendiente} obligacion(es)`,
    icon:     'mdi-bank-transfer',
    color:    props.stats.payments.vencidas > 0 ? 'error' : 'warning',
    href:     '/payments',
  },
  {
    title:    'OCs Activas',
    value:    props.stats.purchase_orders.enviada + props.stats.purchase_orders.facturada,
    subtitle: `${props.stats.purchase_orders.pagada} pagadas`,
    icon:     'mdi-cart-outline',
    color:    'info',
    href:     '/purchase-orders',
  },
  {
    title:    'Facturas',
    value:    props.stats.invoices.recibida + props.stats.invoices.en_revision,
    subtitle: `${props.stats.invoices.validada} validadas`,
    icon:     'mdi-file-document-outline',
    color:    'secondary',
    href:     '/invoices',
  },
  {
    title:    'Stock Bajo',
    value:    props.stats.stock.bajo_minimo,
    subtitle: `${props.stats.stock.sin_stock} sin stock`,
    icon:     'mdi-package-variant-closed',
    color:    props.stats.stock.bajo_minimo > 0 ? 'warning' : 'success',
    href:     '/stock',
  },
])

const movementColors = {
  ingreso_compra:   { color: 'success', icon: 'mdi-arrow-down-circle', label: 'Ingreso' },
  entrada_traslado: { color: 'info',    icon: 'mdi-transfer-right',    label: 'Entrada' },
  salida_traslado:  { color: 'warning', icon: 'mdi-transfer-left',     label: 'Salida' },
  salida_entrega:   { color: 'error',   icon: 'mdi-arrow-up-circle',   label: 'Entrega' },
  ajuste_positivo:  { color: 'success', icon: 'mdi-plus-circle',       label: 'Ajuste +' },
  ajuste_negativo:  { color: 'error',   icon: 'mdi-minus-circle',      label: 'Ajuste -' },
}

// ─── Tareas pendientes por rol ────────────────────────────────────────────────
const pendingTasks = computed(() => {
  const tasks = []
  if (props.stats.requirements.pendiente > 0)
    tasks.push({ label: 'Requerimientos sin enviar', count: props.stats.requirements.pendiente, href: '/requirements', color: 'primary' })
  if (props.stats.quote_requests.abierta > 0)
    tasks.push({ label: 'Cotizaciones abiertas', count: props.stats.quote_requests.abierta, href: '/quote-requests', color: 'info' })
  if (props.stats.approvals.pendiente > 0)
    tasks.push({ label: 'Aprobaciones pendientes', count: props.stats.approvals.pendiente, href: '/approvals', color: 'error' })
  if (props.stats.purchase_orders.enviada > 0)
    tasks.push({ label: 'OCs enviadas sin facturar', count: props.stats.purchase_orders.enviada, href: '/purchase-orders', color: 'warning' })
  if (props.stats.invoices.recibida > 0)
    tasks.push({ label: 'Facturas por revisar', count: props.stats.invoices.recibida, href: '/invoices', color: 'secondary' })
  if (props.stats.invoices.validada > 0)
    tasks.push({ label: 'Facturas por registrar asiento', count: props.stats.invoices.validada, href: '/invoices', color: 'teal' })
  if (props.stats.payments.pendiente > 0)
    tasks.push({ label: 'Pagos pendientes', count: props.stats.payments.pendiente, href: '/payments', color: 'warning' })
  if (props.stats.transfers.en_transito > 0)
    tasks.push({ label: 'Traslados en tránsito', count: props.stats.transfers.en_transito, href: '/transfers', color: 'purple' })
  if (props.stats.deliveries.borrador > 0)
    tasks.push({ label: 'Notas de entrega pendientes', count: props.stats.deliveries.borrador, href: '/deliveries', color: 'orange' })
  return tasks
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap ga-3">
      <div>
        <h1 class="text-h5 font-weight-bold">
          Bienvenido, {{ user?.name }} 👋
        </h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          ERP Minerjob V1.0 — Panel de Control
          <span class="text-caption ml-2">
            · {{ new Date().toLocaleDateString('es-PE', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
          </span>
        </p>
      </div>
      <v-chip color="success" prepend-icon="mdi-circle" size="small">
        Sistema operativo
      </v-chip>
    </div>

    <!-- ─── Alertas urgentes ──────────────────────────────────────────────── -->
    <div v-if="alerts.length > 0" class="mb-4">
      <v-alert
        v-for="alert in alerts" :key="alert.text"
        :type="alert.type" variant="tonal" density="compact"
        class="mb-2 cursor-pointer"
        :prepend-icon="alert.icon"
        @click="$inertia.visit(alert.href)">
        <span class="text-body-2">{{ alert.text }}</span>
      </v-alert>
    </div>

    <!-- ─── Cards de métricas ────────────────────────────────────────────── -->
    <v-row class="mb-4">
      <v-col v-for="card in metricCards" :key="card.title"
        cols="12" sm="6" md="4" lg="2">
        <v-card variant="outlined" rounded="lg" :href="card.href" hover
          class="text-decoration-none h-100">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between mb-2">
              <v-icon :color="card.color" size="28">{{ card.icon }}</v-icon>
              <v-chip :color="card.color" size="x-small" label>
                {{ card.title }}
              </v-chip>
            </div>
            <div class="text-h4 font-weight-bold" :class="`text-${card.color}`">
              {{ card.value }}
            </div>
            <div class="text-caption text-medium-emphasis mt-1">
              {{ card.subtitle }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row>
      <!-- ─── Tareas pendientes ─────────────────────────────────────────── -->
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg" height="100%">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-format-list-checks</v-icon>
            Tareas Pendientes
            <v-chip size="x-small" color="primary" class="ml-2">
              {{ pendingTasks.length }}
            </v-chip>
          </v-card-title>
          <v-divider />

          <div v-if="pendingTasks.length === 0" class="text-center pa-6 text-medium-emphasis">
            <v-icon size="48" color="success">mdi-check-circle</v-icon>
            <p class="text-body-2 mt-2">¡Sin tareas pendientes!</p>
          </div>

          <v-list density="compact" v-else>
            <v-list-item
              v-for="task in pendingTasks" :key="task.label"
              :href="task.href"
              class="text-decoration-none">
              <template #prepend>
                <v-avatar :color="task.color" size="28" rounded="sm">
                  <span class="text-caption font-weight-bold text-white">
                    {{ task.count }}
                  </span>
                </v-avatar>
              </template>
              <template #title>
                <span class="text-body-2">{{ task.label }}</span>
              </template>
              <template #append>
                <v-icon size="16" color="grey">mdi-chevron-right</v-icon>
              </template>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>

      <!-- ─── Kardex reciente ───────────────────────────────────────────── -->
      <v-col cols="12" md="5">
        <v-card variant="outlined" rounded="lg" height="100%">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-book-open-outline</v-icon>
            Últimos Movimientos
          </v-card-title>
          <v-divider />

          <div v-if="!recentKardex?.length" class="text-center pa-6 text-medium-emphasis">
            <v-icon size="48">mdi-book-open-outline</v-icon>
            <p class="text-body-2 mt-2">Sin movimientos aún</p>
          </div>

          <v-list density="compact" v-else>
            <v-list-item
              v-for="mov in recentKardex" :key="mov.id"
              :href="`/kardex?product_id=${mov.product_id}`">
              <template #prepend>
                <v-icon
                  :color="movementColors[mov.movement_type]?.color ?? 'default'"
                  size="20">
                  {{ movementColors[mov.movement_type]?.icon ?? 'mdi-circle' }}
                </v-icon>
              </template>
              <template #title>
                <span class="text-caption font-weight-medium">
                  {{ mov.product?.name }}
                </span>
              </template>
              <template #subtitle>
                <span class="text-caption text-medium-emphasis">
                  {{ mov.warehouse?.name }} ·
                  {{ movementColors[mov.movement_type]?.label }}
                  {{ Number(mov.quantity) > 0 ? '+' : '' }}{{ Number(mov.quantity).toFixed(2) }}
                </span>
              </template>
              <template #append>
                <div class="text-right">
                  <div class="text-caption font-weight-medium">
                    {{ Number(mov.balance_quantity).toFixed(2) }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    saldo
                  </div>
                </div>
              </template>
            </v-list-item>
          </v-list>

          <v-card-actions class="pa-3 pt-1">
            <v-btn variant="text" size="small" color="primary" href="/kardex">
              Ver kardex completo →
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>

      <!-- ─── OCs pendientes de recepción ──────────────────────────────── -->
      <v-col cols="12" md="3">
        <v-card variant="outlined" rounded="lg" height="100%">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="warning">mdi-truck-delivery-outline</v-icon>
            OCs por Recibir
          </v-card-title>
          <v-divider />

          <div v-if="!pendingReceptions?.length" class="text-center pa-6 text-medium-emphasis">
            <v-icon size="48" color="success">mdi-check-circle</v-icon>
            <p class="text-body-2 mt-2">Sin OCs pendientes</p>
          </div>

          <v-list density="compact" v-else>
            <v-list-item
              v-for="oc in pendingReceptions" :key="oc.id"
              :href="`/purchase-orders/${oc.id}`">
              <template #title>
                <span class="text-caption font-weight-medium">{{ oc.code }}</span>
              </template>
              <template #subtitle>
                <span class="text-caption text-medium-emphasis">
                  {{ oc.supplier?.business_name }}
                </span>
              </template>
              <template #append>
                <span class="text-caption font-weight-bold text-warning">
                  S/ {{ Number(oc.total).toFixed(0) }}
                </span>
              </template>
            </v-list-item>
          </v-list>

          <v-card-actions class="pa-3 pt-1">
            <v-btn variant="text" size="small" color="primary"
              href="/warehouse-receptions/create">
              Registrar recepción →
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- ─── Acceso rápido ─────────────────────────────────────────────────── -->
    <v-row class="mt-4">
      <v-col cols="12">
        <p class="text-caption text-medium-emphasis mb-3 text-uppercase font-weight-bold">
          Acceso Rápido
        </p>
      </v-col>
      <v-col v-for="mod in [
        { title:'Requerimientos', icon:'mdi-clipboard-list-outline', href:'/requirements', color:'primary' },
        { title:'Cotizaciones',   icon:'mdi-file-compare',           href:'/quote-requests', color:'secondary' },
        { title:'Aprobaciones',   icon:'mdi-gavel',                  href:'/approvals', color:'warning' },
        { title:'OC',             icon:'mdi-cart-outline',           href:'/purchase-orders', color:'info' },
        { title:'Facturas',       icon:'mdi-file-document-outline',  href:'/invoices', color:'error' },
        { title:'Pagos',          icon:'mdi-bank-transfer',          href:'/payments', color:'success' },
        { title:'Recepciones',    icon:'mdi-warehouse',              href:'/warehouse-receptions', color:'primary' },
        { title:'Stock',          icon:'mdi-package-variant-closed', href:'/stock', color:'secondary' },
        { title:'Kardex',         icon:'mdi-book-open-outline',      href:'/kardex', color:'info' },
        { title:'Traslados',      icon:'mdi-transfer',               href:'/transfers', color:'warning' },
        { title:'Entregas',       icon:'mdi-hand-extended',          href:'/deliveries', color:'success' },
        { title:'Usuarios',       icon:'mdi-account-group',          href:'/users', color:'error' },
      ]" :key="mod.href" cols="6" sm="4" md="3" lg="1">
        <v-card variant="tonal" :color="mod.color" rounded="lg"
          :href="mod.href" hover class="text-decoration-none text-center pa-3">
          <v-icon :color="mod.color" size="22">{{ mod.icon }}</v-icon>
          <p class="text-caption mt-1" style="line-height:1.2">{{ mod.title }}</p>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>