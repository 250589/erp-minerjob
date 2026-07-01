<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default { layout: AppLayout }
</script>

<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({ units: Array, categories: Array })

const page         = usePage()
const importResult = computed(() => page.props.flash?.import_result)

const form        = useForm({ file: null })
const fileName    = ref('')
const dragging    = ref(false)

function onFileChange(e) {
  const file = e.target.files?.[0] ?? e.dataTransfer?.files?.[0]
  if (file) {
    form.file = file
    fileName.value = file.name
  }
}

function onDrop(e) {
  dragging.value = false
  onFileChange(e)
}

function submit() {
  form.post('/imports/products', { forceFormData: true })
}

const columns = [
  { col: 'SKU',               desc: 'Código único del producto',           req: true,  example: 'MAT-001' },
  { col: 'Nombre',            desc: 'Nombre del producto',                 req: true,  example: 'Cable eléctrico 10mm' },
  { col: 'Descripcion',       desc: 'Descripción breve',                   req: false, example: 'Cable de cobre' },
  { col: 'Categoria',         desc: 'Nombre exacto de la categoría',       req: false, example: 'Electricidad' },
  { col: 'Unidad',            desc: 'Abreviatura de la unidad de medida',  req: true,  example: 'MT, UND, KG, LT' },
  { col: 'Stock_Minimo',      desc: 'Cantidad mínima en inventario',       req: false, example: '50' },
  { col: 'Margen_Porcentaje', desc: 'Margen de ganancia (%)',              req: false, example: '35' },
]
</script>

<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" href="/products" class="mr-2" />
      <div>
        <h1 class="text-h5 font-weight-bold">Importar Productos desde Excel</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Carga masiva del catálogo de materiales (.xlsx)
        </p>
      </div>
    </div>

    <!-- Resultado de la importación -->
    <template v-if="importResult">
      <v-alert
        v-if="importResult.total > 0"
        type="success" variant="tonal" class="mb-4"
        prepend-icon="mdi-check-circle">
        <div class="d-flex align-center ga-4 flex-wrap">
          <span>
            <strong>Importación completada.</strong>
          </span>
          <v-chip color="success" size="small">
            <v-icon start size="14">mdi-plus-circle</v-icon>
            {{ importResult.created }} creados
          </v-chip>
          <v-chip color="info" size="small">
            <v-icon start size="14">mdi-refresh</v-icon>
            {{ importResult.updated }} actualizados
          </v-chip>
        </div>
      </v-alert>

      <v-alert
        v-if="importResult.errors?.length"
        type="warning" variant="tonal" class="mb-4"
        prepend-icon="mdi-alert">
        <p class="font-weight-bold mb-2">
          {{ importResult.errors.length }} fila(s) con errores:
        </p>
        <ul class="text-body-2 pl-4">
          <li v-for="err in importResult.errors" :key="err">{{ err }}</li>
        </ul>
      </v-alert>
    </template>

    <v-row>
      <v-col cols="12" md="7">

        <!-- ─── Paso 1: Descargar plantilla ───────────────────────────── -->
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-text class="d-flex align-center justify-space-between pa-5">
            <div class="d-flex align-center ga-3">
              <v-avatar color="success" rounded="lg" size="48">
                <v-icon color="white" size="24">mdi-file-excel</v-icon>
              </v-avatar>
              <div>
                <p class="text-subtitle-2 font-weight-bold">Paso 1 — Descarga la plantilla</p>
                <p class="text-caption text-medium-emphasis">
                  Incluye columnas correctas + hojas de referencia de Unidades y Categorías
                </p>
              </div>
            </div>
            <v-btn
              color="success"
              prepend-icon="mdi-download"
              href="/imports/products/template"
              variant="tonal">
              Descargar Plantilla
            </v-btn>
          </v-card-text>
        </v-card>

        <!-- ─── Paso 2: Subir archivo ─────────────────────────────────── -->
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-upload</v-icon>
            Paso 2 — Sube el archivo completado
          </v-card-title>
          <v-divider />
          <v-card-text class="pt-4">
            <v-form @submit.prevent="submit">

              <!-- Zona drag & drop -->
              <div
                class="border-dashed rounded-lg pa-8 text-center cursor-pointer"
                :class="dragging ? 'border-primary bg-blue-lighten-5' : 'border-grey-lighten-2'"
                style="border: 2px dashed; transition: all 0.2s"
                @dragover.prevent="dragging = true"
                @dragleave="dragging = false"
                @drop.prevent="onDrop"
                @click="$refs.fileInput.click()">

                <v-icon size="48" :color="dragging ? 'primary' : 'grey-lighten-2'">
                  mdi-file-excel-outline
                </v-icon>
                <p class="text-h6 mt-2" :class="dragging ? 'text-primary' : 'text-medium-emphasis'">
                  {{ fileName || 'Arrastra el archivo aquí o haz clic' }}
                </p>
                <p class="text-caption text-medium-emphasis mt-1">
                  Solo archivos .xlsx o .xls — máximo 10 MB
                </p>

                <input
                  ref="fileInput"
                  type="file"
                  accept=".xlsx,.xls"
                  class="d-none"
                  @change="onFileChange" />
              </div>

              <div v-if="form.errors.file" class="text-error text-caption mt-2">
                {{ form.errors.file }}
              </div>

              <div v-if="fileName" class="d-flex align-center ga-2 mt-3">
                <v-icon color="success" size="20">mdi-file-excel</v-icon>
                <span class="text-body-2 font-weight-medium">{{ fileName }}</span>
                <v-btn icon="mdi-close" size="x-small" variant="text"
                  @click="form.file = null; fileName = ''" />
              </div>

              <v-btn
                type="submit"
                color="primary"
                block
                size="large"
                class="mt-4"
                :loading="form.processing"
                :disabled="!form.file"
                prepend-icon="mdi-cloud-upload">
                Importar Productos
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- ─── Sidebar: Columnas y referencia ──────────────────────────── -->
      <v-col cols="12" md="5">

        <!-- Columnas de la plantilla -->
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="d-flex align-center pa-4 pb-3 text-subtitle-1 font-weight-bold">
            <v-icon start color="primary">mdi-table-column</v-icon>
            Columnas de la Plantilla
          </v-card-title>
          <v-divider />
          <v-list density="compact">
            <v-list-item v-for="col in columns" :key="col.col">
              <template #prepend>
                <v-chip
                  :color="col.req ? 'error' : 'default'"
                  size="x-small" label class="mr-2">
                  {{ col.req ? 'REQ' : 'OPT' }}
                </v-chip>
              </template>
              <template #title>
                <code class="text-caption font-weight-bold">{{ col.col }}</code>
              </template>
              <template #subtitle>
                <span class="text-caption">{{ col.desc }}</span>
                <br>
                <span class="text-caption text-medium-emphasis">
                  Ej: {{ col.example }}
                </span>
              </template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Unidades disponibles -->
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-title class="pa-4 pb-3 text-subtitle-2 font-weight-bold">
            <v-icon start size="18" color="info">mdi-ruler</v-icon>
            Unidades disponibles (columna Unidad)
          </v-card-title>
          <v-divider />
          <v-card-text class="pt-3">
            <div class="d-flex flex-wrap ga-1">
              <v-chip v-for="u in units" :key="u.id" size="small" color="info" variant="tonal">
                <strong>{{ u.abbreviation }}</strong>
                <span class="ml-1 text-caption">{{ u.name }}</span>
              </v-chip>
            </div>
          </v-card-text>
        </v-card>

        <!-- Categorías disponibles -->
        <v-card variant="outlined" rounded="lg">
          <v-card-title class="pa-4 pb-3 text-subtitle-2 font-weight-bold">
            <v-icon start size="18" color="secondary">mdi-tag-outline</v-icon>
            Categorías disponibles (columna Categoria)
          </v-card-title>
          <v-divider />
          <v-card-text class="pt-3">
            <div v-if="categories.length" class="d-flex flex-wrap ga-1">
              <v-chip v-for="c in categories" :key="c.id" size="small" variant="tonal">
                {{ c.name }}
              </v-chip>
            </div>
            <p v-else class="text-caption text-medium-emphasis">
              Sin categorías creadas aún. Puedes dejar la columna vacía.
            </p>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>