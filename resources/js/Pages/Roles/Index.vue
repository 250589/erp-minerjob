<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
defineProps({ roles: Array })

const roleIcons = {
  'Administrador':    { icon: 'mdi-shield-crown',      color: 'error'   },
  'Solicitante':      { icon: 'mdi-account-edit',       color: 'primary' },
  'Compras':          { icon: 'mdi-cart',               color: 'info'    },
  'Gerencia':         { icon: 'mdi-gavel',              color: 'warning' },
  'Contabilidad':     { icon: 'mdi-calculator',         color: 'success' },
  'Finanzas':         { icon: 'mdi-bank',               color: 'teal'    },
  'Almacen_Principal':{ icon: 'mdi-warehouse',          color: 'indigo'  },
  'Traslados':        { icon: 'mdi-transfer',           color: 'purple'  },
  'Subalmacen':       { icon: 'mdi-home-city',          color: 'cyan'    },
  'Consumo_Final':    { icon: 'mdi-account-hard-hat',   color: 'green'   },
}

// Grupos de permisos para mostrar visualmente
const permGroups = {
  'Requerimientos': ['requirements.view', 'requirements.create', 'requirements.edit', 'requirements.send'],
  'Cotizaciones':   ['quote_requests.view', 'quote_requests.create', 'quote_requests.manage', 'quotes.create'],
  'Aprobaciones':   ['approvals.view', 'approvals.decide'],
  'OC':             ['purchase_orders.view', 'purchase_orders.send'],
  'Facturas':       ['invoices.view', 'invoices.create', 'invoices.validate'],
  'Contabilidad':   ['accounting_entries.create'],
  'Pagos':          ['payments.view', 'payments.create', 'payments.confirm'],
  'Almacén':        ['warehouse_receptions.view', 'warehouse_receptions.create', 'stock.view', 'kardex.view'],
  'Traslados':      ['transfers.view', 'transfers.create', 'transfers.dispatch', 'transfers.receive'],
  'Entregas':       ['deliveries.view', 'deliveries.create', 'deliveries.deliver'],
  'Admin':          ['users.view', 'users.create', 'users.edit', 'users.delete', 'roles.view', 'roles.manage'],
}

function hasPermission(role, perm) {
  if (!role.permissions) return false
  return role.permissions.some(p => p.name === perm)
}

function countPerms(role) {
  return role.permissions?.length ?? 0
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-h5 font-weight-bold">Roles y Permisos</h1>
      <p class="text-body-2 text-medium-emphasis mt-1">
        10 roles correspondientes a los carriles del flujograma MinerJob SAC
      </p>
    </div>

    <v-row>
      <v-col v-for="role in roles" :key="role.id" cols="12" md="6" lg="4">
        <v-card variant="outlined" rounded="lg" height="100%">
          <v-card-text class="pa-4">
            <!-- Header del rol -->
            <div class="d-flex align-center ga-3 mb-3">
              <v-avatar
                :color="roleIcons[role.name]?.color ?? 'primary'"
                size="44" rounded="lg">
                <v-icon color="white">
                  {{ roleIcons[role.name]?.icon ?? 'mdi-account' }}
                </v-icon>
              </v-avatar>
              <div>
                <div class="text-subtitle-2 font-weight-bold">{{ role.name }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ role.users_count }} usuario(s) ·
                  {{ countPerms(role) }} permiso(s)
                </div>
              </div>
            </div>

            <v-divider class="mb-3" />

            <!-- Módulos con acceso -->
            <div class="d-flex flex-wrap ga-1">
              <template v-for="(perms, group) in permGroups" :key="group">
                <v-chip
                  v-if="perms.some(p => hasPermission(role, p))"
                  size="x-small"
                  :color="roleIcons[role.name]?.color ?? 'primary'"
                  variant="tonal"
                  label>
                  {{ group }}
                </v-chip>
              </template>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-alert type="info" variant="tonal" density="compact" class="mt-6">
      Los permisos de cada rol se configuran en <code>database/seeders/RolePermissionSeeder.php</code>
      y se aplican corriendo <code>php artisan db:seed --class=RolePermissionSeeder</code>.
    </v-alert>
  </div>
</template>