<template>
  <q-page class="q-pa-sm">
    <q-card flat bordered>
      <q-card-section class="row items-center q-py-sm">
        <div>
          <div class="text-subtitle1 text-weight-bold">Venta / Administración de Asientos</div>
          <div class="text-caption text-grey-7">
            Clic para seleccionar. Luego abre el panel para aplicar estado y datos.
          </div>
        </div>

        <q-space />

        <q-btn flat icon="arrow_back" label="Volver" no-caps @click="$router.push('/mis-graderias')" />
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-sm">
        <!-- HEADER INFO + RESUMEN -->
        <q-card flat bordered>
          <q-card-section class="row items-center q-py-sm">
            <div class="text-subtitle2 text-weight-bold">
              {{ graderia?.nombre || 'Gradería' }}
              <span class="text-grey-7">— {{ graderia?.direccion || '' }}</span>
            </div>

            <q-space />

            <q-btn dense no-caps color="primary" icon="refresh" label="Recargar" :loading="loading" @click="load()" class="q-mr-sm" />
<!--            btn dropdown para limpiar o gestionar-->
            <q-btn-dropdown dense no-caps color="primary" icon="more_vert" label="Acciones" class="q-mr-sm">
              <q-list>
                <q-item clickable @click="modificarAsiento" v-close-popup>
                  <q-item-section avatar>
                    <q-icon name="edit" />
                  </q-item-section>
                  <q-item-section> Modificar asiento </q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
<!--            <q-btn dense no-caps flat icon="clear_all" label="Limpiar selección" :disable="selectedIds.length === 0" @click="clearSelection" class="q-mr-sm" />-->
            <q-btn dense no-caps color="primary" icon="edit" label="Gestionar selección" :disable="selectedIds.length === 0" @click="openBulkDialog()" />
          </q-card-section>

          <q-separator />

          <q-card-section class="q-pa-sm">
            <div class="row q-col-gutter-xs">
              <div class="col-6 col-md-3">
                <q-banner dense class="bg-grey-1">
                  <div class="text-caption text-grey-7">Total Bs</div>
                  <div class="text-subtitle1 text-weight-bold" >
                    <span :style="{ filter: totalMontoRealCss ? 'blur(4px)' : 'none' }">
                      {{ totalMontoReal === 0 ? '0.00' : money(totalMontoReal) }}
                    </span>
                    <q-btn dense flat :icon="totalMontoRealCss ? 'visibility' : 'visibility_off'" @click="totalMontoRealCss = !totalMontoRealCss" />
                  </div>
                </q-banner>
              </div>
              <div class="col-6 col-md-3">
                <q-banner dense class="bg-grey-1">
                  <div class="text-caption text-grey-7">Libres</div>
                  <div class="text-subtitle1 text-weight-bold">{{ stats.libre }}</div>
                </q-banner>
              </div>
              <div class="col-6 col-md-3">
                <q-banner dense class="bg-grey-1">
                  <div class="text-caption text-grey-7">Ocupados</div>
                  <div class="text-subtitle1 text-weight-bold">{{ stats.ocupado }}</div>
                </q-banner>
              </div>
              <div class="col-6 col-md-3">
                <q-banner dense class="bg-grey-1">
                  <div class="text-caption text-grey-7">Seleccionados</div>
                  <div class="text-subtitle1 text-weight-bold">{{ selectedIds.length }}</div>
                </q-banner>
              </div>
            </div>

            <div class="row q-col-gutter-xs q-mt-xs">
              <div class="col-12 col-md-3">
                <q-chip dense :color="statusMeta.LIBRE.color" text-color="white" icon="event_seat">
                  LIBRE: {{ stats.libre }}
                </q-chip>
              </div>
              <div class="col-12 col-md-3">
                <q-chip dense :color="statusMeta.RESERVADO.color" text-color="white" icon="bookmark">
                  RESERVADO: {{ stats.reservado }}
                </q-chip>
              </div>
              <div class="col-12 col-md-3">
                <q-chip dense :color="statusMeta.PAGADO.color" text-color="white" icon="paid">
                  PAGADO: {{ stats.pagado }}
                </q-chip>
              </div>
              <div class="col-12 col-md-3">
                <q-chip dense :color="statusMeta.BLOQUEADO.color" text-color="white" icon="block">
                  BLOQUEADO: {{ stats.bloqueado }}
                </q-chip>
              </div>
            </div>

            <div class="text-caption text-grey-7 q-mt-sm">
              Click = seleccionar / deseleccionar.
              <span v-if="mismatchWarning" class="text-negative">
                — Aviso: tu gradería dice {{ graderia?.filas }}x{{ graderia?.columnas }},
                pero en BD hay {{ asientosCount }} asientos. (Los grises son NO GENERADOS).
              </span>
            </div>
          </q-card-section>
        </q-card>

        <!-- GRID -->
        <q-card flat bordered class="q-mt-sm">
          <q-card-section class="row items-center q-py-sm">
            <div class="text-subtitle2 text-weight-bold">Asientos</div>
            <q-space />
            <q-badge outline color="primary">
<!--              Monto selección: {{ money(selectedTotalMonto) }}-->
              Total {{ stats.total }} asientos
            </q-badge>
          </q-card-section>

          <q-separator />

          <q-card-section class="q-pa-sm">
            <div style="width: 100%; overflow: auto;">
              <table border="1" cellpadding="6" cellspacing="0" width="100%">
                <tbody>
                <tr v-for="(row, rIdx) in gridSeats" :key="rIdx">
                  <td
                    v-for="seat in row"
                    :key="seat.codigo"
                    :bgcolor="seatBg(seat)"
                    @click="toggleSeat(seat)"
                    :style="cellStyle(seat)"
                  >
                    <div class="text-weight-bold">{{ seat.codigo }}</div>

                    <div style="font-size: 11px;">
                      {{ statusMeta[seat.status].label }}
                      <span v-if="seat.id && isSelected(seat.id)" class="text-weight-bold"> ✓</span>
                    </div>

                    <div style="font-size: 11px; margin-top: 2px;">
                      <span v-if="seat.cliente_nombre" class="text-grey-9">
                        {{ seat.cliente_nombre }}
                        <br>
                        {{ seat.monto }} Bs
                      </span>
                      <span v-else class="text-grey-6">—</span>
                    </div>
                  </td>
                </tr>
                </tbody>
              </table>
            </div>
          </q-card-section>
        </q-card>

      </q-card-section>
    </q-card>

    <!-- DIALOG MASIVO -->
    <q-dialog v-model="bulkDialog.open">
      <q-card style="width: 520px; max-width: 95vw;">
        <q-card-section class="row items-center q-py-sm">
          <div>
            <div class="text-subtitle1 text-weight-bold">Gestionar selección</div>
            <div class="text-caption text-grey-7">
              Seleccionados: <b>{{ selectedIds.length }}</b> — Total actual: <b>{{ money(selectedTotalMonto) }}</b>
            </div>
          </div>
          <q-space />
          <q-btn dense flat round icon="close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pa-sm">
          <q-input v-model="bulkForm.cliente_nombre" dense outlined label="Nombre cliente" clearable/>
          <q-input v-model="bulkForm.cliente_celular" dense outlined label="Celular" class="q-mt-sm" clearable />

          <q-input
            v-model.number="bulkForm.monto_total"
            type="number"
            dense
            outlined
            label="Monto TOTAL (se divide entre seleccionados)"
            class="q-mt-sm"
            :min="0"
            step="1"
            clearable
          />

          <q-banner dense class="bg-grey-1 q-mt-sm">
            <div class="text-caption text-grey-7">Resumen</div>
            <div class="row q-col-gutter-xs q-mt-xs">
              <div class="col-6">
                <div class="text-caption text-grey-7">Libres sel.</div>
                <div class="text-weight-bold">{{ selectedStats.libre }}</div>
              </div>
              <div class="col-6">
                <div class="text-caption text-grey-7">Ocupados sel.</div>
                <div class="text-weight-bold">

<!--                  {{ selectedStats.ocupado }} si es mayo de cero un rojo furerte y que diha alerta-->
                  <span :class="selectedStats.ocupado > 0 ? 'text-negative text-weight-bold' : ''">
                    {{ selectedStats.ocupado }}
                    <template v-if="selectedStats.ocupado > 0">
                    Cuidado!
                    </template>
                  </span>
                </div>
              </div>
            </div>
          </q-banner>

          <div class="row q-col-gutter-xs q-mt-sm">
            <div class="col-6">
              <q-btn
                class="full-width"
                no-caps
                :disable="selectedIds.length === 0 || saving"
                :loading="saving && actionRunning==='RESERVADO'"
                :color="statusMeta.RESERVADO.color"
                icon="bookmark"
                label="Reservar"
                @click="applyBulk('RESERVADO')"
              />
            </div>
            <div class="col-6">
              <q-btn
                class="full-width"
                no-caps
                :disable="selectedIds.length === 0 || saving"
                :loading="saving && actionRunning==='PAGADO'"
                :color="statusMeta.PAGADO.color"
                icon="paid"
                label="Pagar"
                @click="applyBulk('PAGADO')"
              />
            </div>

            <div class="col-6 q-mt-xs">
              <q-btn
                class="full-width"
                no-caps
                :disable="selectedIds.length === 0 || saving"
                :loading="saving && actionRunning==='BLOQUEADO'"
                :color="statusMeta.BLOQUEADO.color"
                icon="block"
                label="Bloquear"
                @click="applyBulk('BLOQUEADO')"
              />
            </div>
            <div class="col-6 q-mt-xs">
              <q-btn
                class="full-width"
                no-caps
                :disable="selectedIds.length === 0 || saving"
                :loading="saving && actionRunning==='LIBRE'"
                color="grey-8"
                icon="restart_alt"
                label="Liberar"
                @click="applyBulk('LIBRE')"
              />
            </div>
          </div>

          <div class="text-caption text-grey-7 q-mt-sm">
            * Solo se pueden seleccionar asientos existentes (no grises).
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn flat no-caps label="Cerrar" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'GraderiaVenta',

  data () {
    return {
      totalMontoRealCss: false,
      asientoSeleccionado: null,
      asientoSeleccionadoDialog: false,
      loading: false,
      saving: false,
      actionRunning: '',

      graderia: null,
      asientosCount: 0,

      seatsDB: [],
      seatDBMap: {},

      selectedIds: [],

      bulkDialog: { open: false },
      bulkForm: {
        cliente_nombre: '',
        cliente_celular: '',
        monto_total: null
      },

      statusMeta: {
        NO_GENERADO: { label: 'No generado', color: 'grey-7', bg: '#e6e6e6' },
        LIBRE:       { label: 'Libre',       color: 'positive', bg: '#82f38b' },
        RESERVADO:   { label: 'Reservado',   color: 'warning',  bg: '#f3d77d' },
        PAGADO:      { label: 'Pagado',      color: 'primary',  bg: '#79bcee' },
        BLOQUEADO:   { label: 'Bloqueado',   color: 'negative', bg: '#ec7385' }
      }
    }
  },

  computed: {
    totalMontoReal () {
      let sum = 0
      for (const s of this.seatsDB) {
        if (s.monto != null && s.monto !== '' && !isNaN(Number(s.monto))) {
          sum += Number(s.monto)
        }
      }
      return sum
    },
    graderiaId () {
      return this.$route.params.id
    },

    mismatchWarning () {
      if (!this.graderia) return false
      const expected = Number(this.graderia.filas || 0) * Number(this.graderia.columnas || 0)
      return expected > 0 && this.asientosCount > 0 && expected !== this.asientosCount
    },

    stats () {
      // stats sobre la grilla completa (config)
      const total = Number(this.graderia?.filas || 0) * Number(this.graderia?.columnas || 0)
      const s = { total, libre: 0, reservado: 0, pagado: 0, bloqueado: 0, ocupado: 0 }

      // contamos solo los existentes según BD (porque los no generados no tienen estado real)
      for (const a of this.seatsDB) {
        const st = this.normalizeEstado(a.estado) || 'LIBRE'
        if (st === 'LIBRE') s.libre++
        if (st === 'RESERVADO') s.reservado++
        if (st === 'PAGADO') s.pagado++
        if (st === 'BLOQUEADO') s.bloqueado++
      }
      s.ocupado = (s.reservado + s.pagado + s.bloqueado)
      return s
    },

    selectedSeats () {
      return this.selectedIds.map(id => {
        const s = this.seatsDB.find(x => x.id === id)
        if (!s) return null
        return { ...s, status: this.normalizeEstado(s.estado) || 'LIBRE' }
      }).filter(Boolean)
    },

    selectedStats () {
      const st = { libre: 0, ocupado: 0 }
      for (const s of this.selectedSeats) {
        if (s.status === 'LIBRE') st.libre++
        else st.ocupado++
      }
      return st
    },

    selectedTotalMonto () {
      const mt = this.bulkForm.monto_total
      if (mt !== null && mt !== '' && !isNaN(Number(mt))) return Number(mt)

      let sum = 0
      for (const s of this.selectedSeats) {
        if (s.monto != null) sum += Number(s.monto)
      }
      return sum
    },

    gridSeats () {
      if (!this.graderia) return []

      const filas = Number(this.graderia.filas || 0)
      const cols  = Number(this.graderia.columnas || 0)
      if (filas <= 0 || cols <= 0) return []

      const rowOrder = this.graderia.start_top ? this.range(1, filas) : this.range(1, filas).reverse()
      const colOrder = this.graderia.start_left ? this.range(1, cols)  : this.range(1, cols).reverse()

      const matrix = []
      for (const r of rowOrder) {
        const line = []
        for (const c of colOrder) {
          const codigo = this.makeCode(r, c)

          const db = this.seatDBMap[codigo] || null
          const status = db ? (this.normalizeEstado(db.estado) || 'LIBRE') : 'NO_GENERADO'

          line.push({
            id: db?.id || null,
            fila: r,
            columna: c,
            codigo,
            status,
            cliente_nombre: db?.cliente_nombre || null,
            cliente_celular: db?.cliente_celular || null,
            monto: db?.monto ?? null,
            reservado_at: db?.reservado_at || null,
            pagado_at: db?.pagado_at || null
          })
        }
        matrix.push(line)
      }

      return matrix
    }
  },

  mounted () {
    this.load()
  },

  methods: {
    modificarAsiento(){
      this.$q.dialog({
        title: 'Modificar Asiento',
        message: 'Ingresa el código del asiento a modificar:',
        prompt: { model: '',
          type: 'text',
          isValid: val => val.trim().length > 0,
          label: 'Código del Asiento',
          hint: 'Ejemplo: A1, B3, C5, etc.',
          attrs: { maxlength: 10 }
        },
        cancel: true,
      }).onOk(code => {
        const trimmedCode = code.trim().toUpperCase();
        const seat = this.seatsDB.find(s => s.codigo.toUpperCase() === trimmedCode);
        if (seat) {
          this.asientoSeleccionado = seat;
          console.log('Asiento encontrado:', seat);
        } else {
          this.$alert.error(`Asiento con código "${trimmedCode}" no encontrado.`);
        }
      })
    },
    normalizeEstado (v) {
      if (!v) return ''
      const up = String(v).toUpperCase()
      if (['LIBRE', 'RESERVADO', 'PAGADO', 'BLOQUEADO'].includes(up)) return up
      return ''
    },

    range (a, b) {
      const arr = []
      for (let i = a; i <= b; i++) arr.push(i)
      return arr
    },

    numToLetters (n) {
      let s = ''
      n = Number(n)
      while (n > 0) {
        n--
        s = String.fromCharCode(65 + (n % 26)) + s
        n = Math.floor(n / 26)
      }
      return s
    },

    makeCode (r, c) {
      // EXACTAMENTE como tu "Editar"
      if (String(this.graderia.etiqueta_modo) === 'fila') {
        return `${this.numToLetters(r)}${c}`
      }
      return `${this.numToLetters(c)}${r}`
    },

    money (n) {
      const x = Number(n || 0)
      return x.toFixed(2)
    },

    isSelected (id) {
      return id != null && this.selectedIds.includes(id)
    },

    seatBg (seat) {
      const base = this.statusMeta[seat.status]?.bg || '#ffffff'
      if (seat.id && this.isSelected(seat.id)) return '#d9d9d9'
      return base
    },

    cellStyle (seat) {
      const clickable = !!seat.id && seat.status !== 'NO_GENERADO'
      return {
        cursor: clickable ? 'pointer' : 'not-allowed',
        textAlign: 'center',
        opacity: clickable ? 1 : 0.6
      }
    },

    toggleSeat (seat) {
      // ✅ solo si existe en BD
      if (!seat?.id) return
      // si su estao es pado no se puede selecionar
      if (seat.status === 'PAGADO') return

      const id = seat.id
      const idx = this.selectedIds.indexOf(id)

      if (idx === -1) this.selectedIds = [...this.selectedIds, id]
      else {
        const copy = [...this.selectedIds]
        copy.splice(idx, 1)
        this.selectedIds = copy
      }
    },

    clearSelection () {
      this.selectedIds = []
    },

    openBulkDialog () {
      this.bulkForm.cliente_nombre = ''
      this.bulkForm.cliente_celular = ''
      this.bulkForm.monto_total = null
      if (this.selectedIds.length === 0) return
      this.bulkDialog.open = true
    },

    load () {
      this.loading = true

      this.$axios.get(`mis-graderias/${this.graderiaId}/venta`)
        .then(r => {
          const payload = r.data
          this.graderia = payload.graderia

          this.seatsDB = payload.asientos || []
          this.asientosCount = Number(payload.total || this.seatsDB.length || 0)

          const map = {}
          for (const s of this.seatsDB) {
            map[s.codigo] = s
          }
          this.seatDBMap = map

          this.selectedIds = []
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo cargar'))
        .finally(() => { this.loading = false })
    },

    applyBulk (estado) {
      if (this.selectedIds.length === 0) return

      if (estado === 'RESERVADO' || estado === 'PAGADO') {
        if (!this.bulkForm.cliente_nombre || !this.bulkForm.cliente_celular) {
          this.$alert.error('Completa nombre y celular para reservar/pagar.')
          return
        }
        if (this.bulkForm.monto_total === null || this.bulkForm.monto_total === '' || isNaN(Number(this.bulkForm.monto_total))) {
          this.$alert.error('Coloca un monto TOTAL para reservar/pagar.')
          return
        }
      }

      this.saving = true
      this.actionRunning = estado

      const payload = {
        seat_ids: this.selectedIds,
        estado,
        cliente_nombre: this.bulkForm.cliente_nombre || null,
        cliente_celular: this.bulkForm.cliente_celular || null,
        monto_total: (this.bulkForm.monto_total === null || this.bulkForm.monto_total === '' || isNaN(Number(this.bulkForm.monto_total)))
          ? null
          : Number(this.bulkForm.monto_total)
      }

      this.$axios.post(`mis-graderias/${this.graderiaId}/asientos/bulk`, payload)
        .then(r => {
          const updated = r.data?.updated || []

          // refrescar mapas
          const dbMap = { ...this.seatDBMap }
          for (const u of updated) dbMap[u.codigo] = u
          this.seatDBMap = dbMap

          // refresca arreglo seatsDB para stats
          const tmp = {}
          for (const s of this.seatsDB) tmp[s.id] = s
          for (const u of updated) tmp[u.id] = u
          this.seatsDB = Object.values(tmp)

          this.$alert.success(`Actualizado: ${updated.length} asiento(s)`)
          this.clearSelection()
          this.bulkDialog.open = false
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo actualizar'))
        .finally(() => {
          this.saving = false
          this.actionRunning = ''
        })
    }
  }
}
</script>
