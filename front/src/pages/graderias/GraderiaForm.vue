<template>
  <q-page class="q-pa-md">
    <q-card flat bordered>
      <q-card-section class="row items-center">
        <div>
          <div class="text-h6 text-title">
            {{ isEdit ? 'Editar Gradería' : 'Nueva Gradería' }}
          </div>
          <div class="text-caption text-grey-7">
            {{ isEdit ? 'Actualiza datos de la gradería' : 'Crea la gradería y genera asientos automáticamente' }}
          </div>
        </div>
        <q-space />
        <q-btn flat icon="arrow_back" label="Volver" no-caps @click="$router.push('/mis-graderias')" />
      </q-card-section>

      <q-separator />

      <q-card-section>
        <q-form @submit.prevent="save">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-input v-model="form.nombre" label="Nombre" dense outlined />
            </div>

            <div class="col-12 col-md-6">
              <q-input v-model="form.codigo" label="Código" dense outlined />
            </div>

            <div class="col-12">
              <q-input v-model="form.direccion" label="Dirección" dense outlined :rules="[req]" />
            </div>

            <div class="col-12 col-md-4">
              <q-input v-model="form.ref_izquierda" label="Ref. Izquierda" dense outlined />
            </div>
            <div class="col-12 col-md-4">
              <q-input v-model="form.ref_derecha" label="Ref. Derecha" dense outlined />
            </div>
            <div class="col-12 col-md-4">
              <q-input v-model="form.ref_frente" label="Ref. Frente" dense outlined />
            </div>

            <div class="col-6 col-md-3">
              <q-input v-model.number="form.filas" type="number" label="Filas (X)" dense outlined :rules="[req]" />
            </div>
            <div class="col-6 col-md-3">
              <q-input v-model.number="form.columnas" type="number" label="Columnas (Y)" dense outlined :rules="[req]" />
            </div>

            <div class="col-12 col-md-3">
              <q-select
                v-model="form.etiqueta_modo"
                dense
                outlined
                label="Etiquetado"
                :options="['fila','columna']"
                :rules="[req]"
              />
            </div>

            <div class="col-12 col-md-3">
              <q-select
                v-model="form.activo"
                dense
                outlined
                label="Estado"
                :options="[
                  {label:'Activo', value:true},
                  {label:'Inactivo', value:false},
                ]"
                emit-value
                map-options
              />
            </div>

            <div class="col-6 col-md-3">
              <q-toggle v-model="form.start_top" label="Empieza arriba" />
            </div>
            <div class="col-6 col-md-3">
              <q-toggle v-model="form.start_left" label="Empieza izquierda" />
            </div>

            <div class="col-12" v-if="isEdit">
              <q-toggle
                v-model="form.regenerar_asientos"
                label="Regenerar asientos (borra y crea de nuevo)"
                color="negative"
              />
              <div class="text-caption text-grey-7 q-mt-xs">
                Úsalo solo si cambiaste filas/columnas o el modo de etiquetado.
              </div>
            </div>
          </div>

          <div class="row justify-end q-gutter-sm q-mt-md">
            <q-btn
              color="negative"
              label="Cancelar"
              no-caps
              flat
              @click="$router.push('/mis-graderias')"
              :disable="loading"
            />
            <q-btn
              color="primary"
              :label="isEdit ? 'Guardar cambios' : 'Crear gradería'"
              no-caps
              type="submit"
              :loading="loading"
            />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'GraderiaForm',
  data () {
    return {
      loading: false,
      form: {
        nombre: '',
        codigo: '',
        direccion: '',
        ref_izquierda: '',
        ref_derecha: '',
        ref_frente: '',
        filas: 10,
        columnas: 10,
        etiqueta_modo: 'fila',
        start_top: true,
        start_left: true,
        activo: true,
        regenerar_asientos: false
      }
    }
  },
  computed: {
    isEdit () {
      return !!this.$route.params.id
    }
  },
  mounted () {
    if (this.isEdit) this.getOne()
  },
  methods: {
    req (v) {
      return (v !== null && v !== undefined && v !== '') || 'Campo requerido'
    },

    getOne () {
      this.loading = true
      this.$axios.get(`mis-graderias/${this.$route.params.id}`)
        .then(r => {
          const g = r.data
          this.form = {
            nombre: g.nombre || '',
            codigo: g.codigo || '',
            direccion: g.direccion || '',
            ref_izquierda: g.ref_izquierda || '',
            ref_derecha: g.ref_derecha || '',
            ref_frente: g.ref_frente || '',
            filas: g.filas || 1,
            columnas: g.columnas || 1,
            etiqueta_modo: g.etiqueta_modo || 'fila',
            start_top: !!g.start_top,
            start_left: !!g.start_left,
            activo: !!g.activo,
            regenerar_asientos: false
          }
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo cargar'))
        .finally(() => { this.loading = false })
    },

    save () {
      this.loading = true
      const id = this.$route.params.id

      const payload = { ...this.form }

      const req = this.isEdit
        ? this.$axios.put(`mis-graderias/${id}`, payload)
        : this.$axios.post('mis-graderias', payload)

      req.then(() => {
        this.$alert.success(this.isEdit ? 'Gradería actualizada' : 'Gradería creada')
        this.$router.push('/mis-graderias')
      })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar'))
        .finally(() => { this.loading = false })
    }
  }
}
</script>
