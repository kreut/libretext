<template>
  <div>
    <!-- Confirm Delete Modal -->
    <b-modal
      id="mpc-modal-confirm-delete"
      title="Confirm Delete"
      @ok="executeDelete"
      @hidden="pendingDelete = null"
    >
      <p>{{ deleteMessage }}</p>
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="cancel()">Cancel</b-button>
        <b-button size="sm" variant="danger" @click="ok()">Delete</b-button>
      </template>
    </b-modal>

    <!-- Copy Dropdown Options Modal -->
    <b-modal
      id="mpc-modal-copy-dropdown"
      title="Copy Dropdown Options From Another Cell"
      @ok="executeCopyDropdown"
      @hidden="copyDropdownTarget = null"
    >
      <p class="small text-muted mb-2">
        Select a cell whose dropdown options you want to copy into the current cell.
      </p>
      <b-form-select
        v-model="copyDropdownSource"
        :options="copyDropdownSourceOptions"
        size="sm"
      />
    </b-modal>

    <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Multi-part Computation Builder</h2>">
      <b-card-text>

        <!-- ============================================ -->
        <!-- TABLES                                        -->
        <!-- ============================================ -->
        <div
          v-for="(table, ti) in qtiJson.tables"
          :key="`table-${ti}`"
          class="pb-3"
        >
          <b-card>
            <template #header>
              <div class="d-flex align-items-center" style="gap:8px;">
                <strong style="white-space:nowrap;">Table {{ ti + 1 }}</strong>
                <b-form-input
                  v-model="table.label"
                  type="text"
                  size="sm"
                  style="max-width:220px;"
                  placeholder="Optional label..."
                  @input="handleInput()"
                />
                <b-form-radio-group
                  v-model="table.tableType"
                  :options="[{ value: 'table', text: 'Table' }, { value: 'lineItems', text: 'Line Items' }]"
                  buttons
                  button-variant="outline-secondary"
                  size="sm"
                  @change="onTableTypeChange(ti, $event)"
                />
                <b-button
                  v-if="qtiJson.tables.length > 1"
                  variant="outline-danger"
                  size="sm"
                  class="ml-auto"
                  @click="confirmDelete('table', ti)"
                >
                  <b-icon-trash /> Table {{ ti + 1 }}
                </b-button>
              </div>
            </template>

            <div class="table-responsive">
              <table class="table table-sm mpc-builder-table mb-0">
                <!-- Always-visible column strip -->
                <thead>
                <tr class="col-strip-row">
                  <th class="col-strip-actions-th" />
                  <th
                    v-for="(col, ci) in table.columns"
                    :key="`colstrip-${ti}-${ci}`"
                    class="col-strip-th"
                  >
                    <div class="d-flex align-items-center" style="gap:4px;">
                      <span class="col-strip-label">Col {{ ci + 1 }}</span>
                      <span
                        :id="`mpc-colstrip-delete-${ti}-${ci}`"
                        class="col-strip-trash"
                        :class="{ 'col-strip-trash-disabled': table.columns.length <= 1 }"
                        @click="table.columns.length > 1 && confirmDelete('column', ci, ti)"
                      >
                        <b-icon-trash />
                      </span>
                      <b-tooltip :target="`mpc-colstrip-delete-${ti}-${ci}`" delay="500" triggers="hover">Delete Col {{ ci + 1 }}</b-tooltip>
                    </div>
                  </th>
                </tr>
                </thead>
                <tbody>
                <tr
                  v-for="(row, ri) in table.rows"
                  :key="`row-${ti}-${ri}`"
                  :class="[rowClass(row), { 'drag-over': dragOverIndex === ri && dragTableIndex === ti }]"
                >
                  <!-- Actions: drag + Row N + trash — always first -->
                  <td
                    class="actions-col"
                    @dragover.prevent="onDragOver(ti, ri, $event)"
                    @dragenter.prevent="onDragEnter(ti, ri)"
                    @dragleave="onDragLeave(ti, ri)"
                    @drop.prevent="onDrop(ti, ri)"
                  >
                    <div class="row-action-cell">
                      <span
                        class="drag-handle"
                        draggable="true"
                        @dragstart="onDragStart(ti, ri, $event)"
                        @dragend="onDragEnd"
                        title="Drag to reorder"
                      ><b-icon-grip-horizontal /></span>
                      <span class="row-action-label">Row {{ ri + 1 }}</span>
                      <span
                        :id="`mpc-row-del-${ti}-${ri}`"
                        class="row-action-trash"
                        :class="{ 'row-action-trash-disabled': table.rows.length <= 1 }"
                        @click="table.rows.length > 1 && confirmDelete('row', ri, ti)"
                      ><b-icon-trash /></span>
                      <b-tooltip :target="`mpc-row-del-${ti}-${ri}`" delay="500" triggers="hover">Delete Row {{ ri + 1 }}</b-tooltip>
                    </div>
                  </td>

                  <!-- INSTRUCTION ROW -->
                  <template v-if="row.rowType === 'instruction'">
                    <td
                      :colspan="table.columns.length"
                      class="instruction-cell"
                      @dragover.prevent="onDragOver(ti, ri, $event)"
                      @dragenter.prevent="onDragEnter(ti, ri)"
                      @dragleave="onDragLeave(ti, ri)"
                      @drop.prevent="onDrop(ti, ri)"
                    >
                      <div class="d-flex align-items-center" style="gap:6px;">
                        <b-form-input
                          v-model="row.instructionText"
                          type="text"
                          size="sm"
                          placeholder="Instruction text (spans full row)..."
                          class="instruction-input flex-grow-1"
                          :class="{ 'is-invalid': getRowError(ti, ri, 'instructionText') }"
                          @input="clearRowError(ti, ri, 'instructionText'); handleInput()"
                        />
                      </div>
                      <div v-if="getRowError(ti, ri, 'instructionText')" class="inline-error-text">{{ getRowError(ti, ri, 'instructionText') }}</div>
                    </td>
                  </template>

                  <!-- ROW HEADER ROW (hidden in Line Items mode) -->
                  <template v-else-if="row.rowType === 'rowheader' && table.tableType !== 'lineItems'">
                    <td
                      v-for="(col, ci) in table.columns"
                      :key="`rhcell-${ti}-${ri}-${ci}`"
                      class="rowheader-cell"
                      @dragover.prevent="onDragOver(ti, ri, $event)"
                      @dragenter.prevent="onDragEnter(ti, ri)"
                      @dragleave="onDragLeave(ti, ri)"
                      @drop.prevent="onDrop(ti, ri)"
                    >
                      <div class="d-flex align-items-center" style="gap:4px;">
                        <b-form-input
                          v-model="row.cells[ci].value"
                          type="text"
                          size="sm"
                          placeholder="Header label..."
                          @input="handleInput()"
                        />
                      </div>
                    </td>
                  </template>

                  <!-- DATA ROW -->
                  <template v-else>
                    <td
                      v-for="(col, ci) in table.columns"
                      :key="`cell-${ti}-${ri}-${ci}`"
                      class="cell-td"
                      @dragover.prevent="onDragOver(ti, ri, $event)"
                      @dragenter.prevent="onDragEnter(ti, ri)"
                      @dragleave="onDragLeave(ti, ri)"
                      @drop.prevent="onDrop(ti, ri)"
                    >
                      <div v-if="row.cells && row.cells[ci]" class="cell-wrapper">
                        <!-- Mode icons -->
                        <div class="cell-icon-bar">
                          <span class="cell-icon" :class="{ active: row.cells[ci].mode === 'answer' }" title="Answer" @click="setCellMode(ti, ri, ci, 'answer')"><b-icon-pencil-square /></span>
                          <span class="cell-icon" :class="{ active: row.cells[ci].mode === 'display' }" title="Display" @click="setCellMode(ti, ri, ci, 'display')"><b-icon-eye /></span>
                          <span class="cell-icon" :class="{ active: row.cells[ci].mode === 'blank' }" title="Blank" @click="setCellMode(ti, ri, ci, 'blank')"><b-icon-square /></span>
                        </div>

                        <div v-if="row.cells[ci].mode === 'blank'" class="cell-blank-indicator">
                          <span class="text-muted small">(blank)</span>
                        </div>

                        <div v-else-if="row.cells[ci].mode === 'display'">
                          <b-form-input
                            v-model="row.cells[ci].value"
                            type="text"
                            size="sm"
                            placeholder="Display text..."
                            :class="{ 'is-invalid': getSpecificError(ti, ri, ci, 'value') }"
                            @input="clearSpecificError(ti, ri, ci, 'value'); handleInput()"
                          />
                          <div v-if="getSpecificError(ti, ri, ci, 'value')" class="inline-error-text">{{ getSpecificError(ti, ri, ci, 'value') }}</div>
                        </div>

                        <div v-else-if="row.cells[ci].mode === 'answer'">
                          <!-- All numeric types: one line -->
                          <template v-if="row.cells[ci].answerType !== 'dropdown'">
                            <div class="d-flex align-items-center flex-wrap" style="gap:4px;">
                              <!-- Type -->
                              <b-form-select v-model="row.cells[ci].answerType" :options="answerTypeOptions" size="sm" style="width:140px;flex-shrink:0;" @change="onAnswerTypeChange(ti, ri, ci); handleInput()" />
                              <!-- Answer -->
                              <b-form-input
                                v-model="row.cells[ci].value"
                                type="text"
                                inputmode="decimal"
                                size="sm"
                                style="width:120px;flex-shrink:0;"
                                placeholder="Answer..."
                                :class="{ 'is-invalid': getSpecificError(ti, ri, ci, 'value') }"
                                @input="clearSpecificError(ti, ri, ci, 'value'); handleInput()"
                              />
                              <!-- Dollar rounding -->
                              <b-form-select
                                v-if="row.cells[ci].answerType === 'dollar'"
                                v-model="row.cells[ci].dollarRounding"
                                :options="dollarRoundingOptions"
                                size="sm"
                                style="width:145px;flex-shrink:0;"
                                :class="{ 'is-invalid': getSpecificError(ti, ri, ci, 'dollarRounding') }"
                                @change="clearSpecificError(ti, ri, ci, 'dollarRounding'); handleInput()"
                              />
                              <!-- Custom: unit label THEN decimal input -->
                              <template v-if="row.cells[ci].answerType === 'custom'">
                                <b-form-input
                                  v-model="row.cells[ci].customUnit"
                                  type="text"
                                  size="sm"
                                  style="flex:1;min-width:60px;"
                                  placeholder="unit label..."
                                  :class="{ 'is-invalid': getSpecificError(ti, ri, ci, 'customUnit') }"
                                  @input="clearSpecificError(ti, ri, ci, 'customUnit'); handleInput()"
                                />
                                <b-input-group size="sm" style="width:130px;flex-shrink:0;">
                                  <b-form-input
                                    v-model.number="row.cells[ci].decimalPlaces"
                                    type="text"
                                    inputmode="numeric"
                                    style="width:40px;"
                                    :class="{ 'is-invalid': getSpecificError(ti, ri, ci, 'decimalPlaces') }"
                                    @input="clearSpecificError(ti, ri, ci, 'decimalPlaces'); handleInput()"
                                  />
                                  <b-input-group-append>
                                    <b-input-group-text>decimals</b-input-group-text>
                                  </b-input-group-append>
                                </b-input-group>
                              </template>
                              <!-- Ratio / percentage / general: decimal input -->
                              <template v-if="['ratio','percentage','general'].includes(row.cells[ci].answerType)">
                                <b-input-group size="sm" style="width:130px;flex-shrink:0;">
                                  <b-form-input
                                    v-model.number="row.cells[ci].decimalPlaces"
                                    type="text"
                                    inputmode="numeric"
                                    style="width:40px;"
                                    :class="{ 'is-invalid': getSpecificError(ti, ri, ci, 'decimalPlaces') }"
                                    @input="clearSpecificError(ti, ri, ci, 'decimalPlaces'); handleInput()"
                                  />
                                  <b-input-group-append>
                                    <b-input-group-text>decimals</b-input-group-text>
                                  </b-input-group-append>
                                </b-input-group>
                              </template>
                            </div>
                            <div v-if="getSpecificError(ti, ri, ci, 'value')" class="inline-error-text">{{ getSpecificError(ti, ri, ci, 'value') }}</div>
                            <div v-if="getSpecificError(ti, ri, ci, 'dollarRounding')" class="inline-error-text">{{ getSpecificError(ti, ri, ci, 'dollarRounding') }}</div>
                            <div v-if="getSpecificError(ti, ri, ci, 'decimalPlaces')" class="inline-error-text">{{ getSpecificError(ti, ri, ci, 'decimalPlaces') }}</div>
                            <div v-if="getSpecificError(ti, ri, ci, 'customUnit')" class="inline-error-text">{{ getSpecificError(ti, ri, ci, 'customUnit') }}</div>
                          </template>

                          <!-- Dropdown -->
                          <template v-else>
                            <!-- Type selector -->
                            <div class="mb-2">
                              <b-form-select v-model="row.cells[ci].answerType" :options="answerTypeOptions" size="sm" style="width:140px;" @change="onAnswerTypeChange(ti, ri, ci); handleInput()" />
                            </div>
                            <!-- Options list — click option row to mark as correct -->
                            <div
                              v-for="(opt, oi) in row.cells[ci].dropdownOptions"
                              :key="`opt-${ti}-${ri}-${ci}-${oi}`"
                              class="dropdown-option-row"
                              :class="{ 'dropdown-option-correct': row.cells[ci].correctIndex === oi }"
                              @click="setDropdownCorrect(ti, ri, ci, oi)"
                            >
                              <b-icon-check-circle-fill
                                class="dropdown-option-check"
                                :class="row.cells[ci].correctIndex === oi ? 'text-success' : 'text-muted'"
                                :style="row.cells[ci].correctIndex === oi ? 'opacity:1;font-size:1rem;flex-shrink:0;' : 'opacity:0.25;font-size:1rem;flex-shrink:0;'"
                              />
                              <b-form-input
                                v-model="row.cells[ci].dropdownOptions[oi]"
                                type="text"
                                size="sm"
                                style="flex:1;"
                                :placeholder="`Option ${oi + 1}`"
                                @click.stop
                                @input="onDropdownOptionInput(ti, ri, ci, oi); handleInput()"
                              />
                              <span
                                class="dropdown-option-remove"
                                @click.stop="removeDropdownOption(ti, ri, ci, oi)"
                                title="Remove option"
                              ><b-icon-x /></span>
                            </div>
                            <div v-if="row.cells[ci].dropdownOptions.length > 0" class="small text-muted mb-2 mt-1" style="padding-left:4px;">
                              Click an option to mark it as correct
                            </div>
                            <div class="d-flex align-items-center" style="gap:4px;">
                              <b-button size="sm" variant="outline-primary" @click="addDropdownOption(ti, ri, ci)">+ Option</b-button>
                              <b-button v-if="hasOtherDropdownCells(ti, ri, ci)" size="sm" variant="outline-secondary" @click="openCopyDropdownModal(ti, ri, ci)">Copy from...</b-button>
                            </div>
                            <div v-if="getSpecificError(ti, ri, ci, 'value')" class="inline-error-text mt-1">{{ getSpecificError(ti, ri, ci, 'value') }}</div>
                            <div v-if="getSpecificError(ti, ri, ci, 'dropdownOptions')" class="inline-error-text mt-1">{{ getSpecificError(ti, ri, ci, 'dropdownOptions') }}</div>
                          </template>
                        </div>
                      </div>
                    </td>
                  </template>

                </tr>
                </tbody>
              </table>
            </div>

            <div class="d-flex mt-2">
              <b-button class="primary mr-1" size="sm" @click="addColumn(ti)">+ Column</b-button>
              <b-button class="primary mr-1" size="sm" @click="addRow(ti, 'data')">+ Data Row</b-button>
              <b-button variant="outline-secondary" size="sm" class="mr-1" @click="addRow(ti, 'instruction')">+ Instruction Row</b-button>
              <b-button v-if="table.tableType !== 'lineItems'" variant="outline-secondary" size="sm" @click="addRow(ti, 'rowheader')">+ Row Header</b-button>
            </div>
          </b-card>
        </div>

        <div class="pb-3">
          <b-button variant="outline-primary" @click="addTable()">
            <b-icon-plus-circle /> Add Table
          </b-button>
        </div>

        <!-- Dropdown randomization setting — only shown if question has at least one dropdown cell -->
        <div v-if="hasAnyDropdowns" class="pb-3">
          <b-card class="border-0 bg-light">
            <div class="d-flex align-items-center" style="gap:10px;">
              <b-form-checkbox
                v-model="qtiJson.randomizeDropdowns"
                switch
                @change="handleInput()"
              >
                <span class="font-weight-600">Randomize dropdown option order per student</span>
              </b-form-checkbox>
              <span class="small text-muted">
                {{ qtiJson.randomizeDropdowns ? 'Each student sees a different order.' : 'All students see the same order (shuffled once on save).' }}
              </span>
            </div>
          </b-card>
        </div>

        <ErrorMessage v-if="generalError" class="pb-2" :message="generalError" />

      </b-card-text>
    </b-card>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'
import ErrorMessage from '~/components/ErrorMessage'

export default {
  name: 'AccountingMultiPartComputation',
  components: { ErrorMessage },
  props: {
    qtiJson: { type: Object, default: () => ({}) },
    questionForm: { type: Object, default: () => ({}) }
  },
  data () {
    return {
      pendingDelete: null,
      pendingDeleteTableIndex: null,
      copyDropdownTarget: null,
      copyDropdownSource: null,
      dragIndex: null,
      dragOverIndex: null,
      dragTableIndex: null,
      answerTypeOptions: [
        { value: 'dollar',     text: '$ Dollar' },
        { value: 'percentage', text: '% Percentage' },
        { value: 'ratio',      text: 'Ratio (:1)' },
        { value: 'custom',     text: 'Custom Units' },
        { value: 'general',    text: 'General' },
        { value: 'dropdown',   text: 'Dropdown' }
      ],
      dollarRoundingOptions: [
        { value: 'dollar', text: 'Nearest $1' },
        { value: 'cent',   text: 'Nearest $0.01' }
      ]
    }
  },
  computed: {
    hasAnyDropdowns () {
      if (!this.qtiJson.tables) return false
      return this.qtiJson.tables.some(table =>
        table.rows.some(row =>
          row.rowType === 'data' && row.cells &&
          row.cells.some(cell => cell.mode === 'answer' && cell.answerType === 'dropdown')
        )
      )
    },
    errorKey () {
      if (this.questionForm && this.questionForm.errors) {
        for (const key of ['multi_part_computation', 'qti_json']) {
          if (this.questionForm.errors.get(key)) return key
        }
      }
      return null
    },
    parsedErrors () {
      if (!this.errorKey) return null
      try {
        const raw = this.questionForm.errors.get(this.errorKey)
        return raw ? JSON.parse(raw) : null
      } catch { return null }
    },
    generalError () {
      return this.parsedErrors && this.parsedErrors.general ? this.parsedErrors.general : null
    },
    deleteMessage () {
      if (!this.pendingDelete) return ''
      const { type, index } = this.pendingDelete
      const ti = this.pendingDeleteTableIndex
      if (type === 'table') return `Are you sure you want to delete Table ${index + 1} and all its content?`
      if (type === 'column') {
        const col = this.qtiJson.tables[ti].columns[index]
        const name = col && col.header ? `column "${col.header}"` : `column ${index + 1}`
        return `Are you sure you want to delete ${name}? All data in that column will be removed.`
      }
      return `Are you sure you want to delete row ${index + 1}?`
    },
    allDropdownCells () {
      const cells = []
      if (!this.qtiJson.tables) return cells
      this.qtiJson.tables.forEach((table, ti) => {
        table.rows.forEach((row, ri) => {
          if (row.rowType === 'data' && row.cells) {
            row.cells.forEach((cell, ci) => {
              if (cell.mode === 'answer' && cell.answerType === 'dropdown' && cell.dropdownOptions && cell.dropdownOptions.length > 0) {
                cells.push({ ti, ri, ci, label: `Table ${ti + 1}, Row ${ri + 1}, Col ${ci + 1}: [${cell.dropdownOptions.join(', ')}]` })
              }
            })
          }
        })
      })
      return cells
    },
    copyDropdownSourceOptions () {
      if (!this.copyDropdownTarget) return []
      const { ti, ri, ci } = this.copyDropdownTarget
      return this.allDropdownCells
        .filter(c => !(c.ti === ti && c.ri === ri && c.ci === ci))
        .map(c => ({ value: JSON.stringify({ ti: c.ti, ri: c.ri, ci: c.ci }), text: c.label }))
    }
  },
  mounted () {
    this.initializeDefaults()
  },
  methods: {
    initializeDefaults () {
      if (!this.qtiJson.tables || this.qtiJson.tables.length === 0) {
        this.$set(this.qtiJson, 'tables', [this.createTable()])
        this.$forceUpdate()
      }
      if (this.qtiJson.randomizeDropdowns === undefined) {
        this.$set(this.qtiJson, 'randomizeDropdowns', false)
      }
      this.handleInput()
    },
    createTable () {
      return {
        identifier: uuidv4(),
        label: '',
        tableType: 'table',
        columns: [{ identifier: uuidv4(), header: '' }, { identifier: uuidv4(), header: '' }],
        rows: [
          this.createRow('instruction', 2),
          this.createRow('rowheader', 2),
          this.createRow('data', 2)
        ]
      }
    },
    createRow (rowType, colCount) {
      if (rowType === 'instruction') return { identifier: uuidv4(), rowType: 'instruction', instructionText: '' }
      if (rowType === 'rowheader') return { identifier: uuidv4(), rowType: 'rowheader', cells: Array.from({ length: colCount }, () => ({ identifier: uuidv4(), value: '' })) }
      return { identifier: uuidv4(), rowType: 'data', cells: Array.from({ length: colCount }, (_, ci) => this.createCell(ci === 0 ? 'display' : 'answer')) }
    },
    createCell (mode) {
      return { identifier: uuidv4(), mode: mode || 'answer', answerType: 'dollar', value: '', dollarRounding: 'dollar', decimalPlaces: 2, customUnit: '', dropdownOptions: [], correctIndex: null }
    },
    // ==========================================
    // DRAG AND DROP
    // ==========================================
    onDragStart (ti, ri, event) {
      this.dragIndex = ri
      this.dragTableIndex = ti
      event.dataTransfer.effectAllowed = 'move'
      event.dataTransfer.setData('text/plain', ri)
      const row = event.target.closest('tr')
      if (row) {
        row.classList.add('dragging')
        const dragImage = row.cloneNode(true)
        dragImage.classList.add('drag-ghost')
        dragImage.style.width = row.offsetWidth + 'px'
        document.body.appendChild(dragImage)
        event.dataTransfer.setDragImage(dragImage, event.offsetX, event.offsetY)
        requestAnimationFrame(() => { document.body.removeChild(dragImage) })
      }
    },
    onDragOver (ti, ri, event) {
      event.dataTransfer.dropEffect = 'move'
    },
    onDragEnter (ti, ri) {
      if (ti === this.dragTableIndex && ri !== this.dragIndex) {
        this.dragOverIndex = ri
      }
    },
    onDragLeave (ti, ri) {
      if (this.dragOverIndex === ri) this.dragOverIndex = null
    },
    onDrop (ti, ri) {
      this.dragOverIndex = null
      if (this.dragTableIndex === ti && this.dragIndex !== null && this.dragIndex !== ri) {
        const rows = [...this.qtiJson.tables[ti].rows]
        const item = rows.splice(this.dragIndex, 1)[0]
        rows.splice(ri, 0, item)
        this.$set(this.qtiJson.tables[ti], 'rows', rows)
        this.handleInput()
        this.$forceUpdate()
      }
      this.dragIndex = null
      this.dragTableIndex = null
    },
    onDragEnd () {
      this.dragOverIndex = null
      this.dragIndex = null
      this.dragTableIndex = null
      document.querySelectorAll('.dragging').forEach(el => el.classList.remove('dragging'))
    },

    // ==========================================
    // TABLE MANAGEMENT
    // ==========================================
    addTable () {
      this.qtiJson.tables.push(this.createTable())
      this.handleInput()
      this.$forceUpdate()
    },
    addColumn (ti) {
      const table = this.qtiJson.tables[ti]
      const isFirst = table.columns.length === 0
      table.columns.push({ identifier: uuidv4(), header: '' })
      table.rows.forEach(row => {
        if (row.rowType === 'data' && row.cells) row.cells.push(this.createCell(isFirst ? 'display' : 'answer'))
        else if (row.rowType === 'rowheader' && row.cells) row.cells.push({ identifier: uuidv4(), value: '' })
      })
      this.handleInput()
      this.$forceUpdate()
    },
    addRow (ti, rowType) {
      const table = this.qtiJson.tables[ti]
      table.rows.push(this.createRow(rowType, table.columns.length))
      this.handleInput()
      this.$forceUpdate()
    },
    setCellMode (ti, ri, ci, mode) {
      const cell = this.qtiJson.tables[ti].rows[ri].cells[ci]
      cell.mode = mode
      if (mode === 'blank') cell.value = ''
      this.handleInput()
      this.$forceUpdate()
    },
    onAnswerTypeChange (ti, ri, ci) {
      const cell = this.qtiJson.tables[ti].rows[ri].cells[ci]
      cell.value = ''
      cell.dropdownOptions = cell.dropdownOptions || []
      cell.correctIndex = null
      this.$forceUpdate()
    },
    onTableTypeChange (ti, newType) {
      if (newType === 'lineItems') {
        const table = this.qtiJson.tables[ti]
        const hasRowHeaders = table.rows.some(r => r.rowType === 'rowheader')
        if (hasRowHeaders) {
          this.$bvModal.msgBoxConfirm(
            'Switching to Line Items will remove all Row Header rows. Continue?',
            {
              title: 'Remove Row Headers?',
              okVariant: 'danger',
              okTitle: 'Yes, remove them',
              cancelTitle: 'Cancel',
              buttonSize: 'sm'
            }
          ).then(confirmed => {
            if (confirmed) {
              table.rows = table.rows.filter(r => r.rowType !== 'rowheader')
              this.handleInput()
              this.$forceUpdate()
            } else {
              // Revert toggle
              this.$set(this.qtiJson.tables[ti], 'tableType', 'table')
              this.$forceUpdate()
            }
          })
        } else {
          this.handleInput()
        }
      } else {
        this.handleInput()
      }
    },
    setDropdownCorrect (ti, ri, ci, oi) {
      const cell = this.qtiJson.tables[ti].rows[ri].cells[ci]
      this.$set(cell, 'correctIndex', oi)
      this.$set(cell, 'value', cell.dropdownOptions[oi] || '')
      this.clearSpecificError(ti, ri, ci, 'value')
      this.handleInput()
    },
    onDropdownOptionInput (ti, ri, ci, oi) {
      // Keep value in sync if this is the marked-correct option
      const cell = this.qtiJson.tables[ti].rows[ri].cells[ci]
      if (cell.correctIndex === oi) {
        this.$set(cell, 'value', cell.dropdownOptions[oi] || '')
        this.handleInput()
      }
    },
    addDropdownOption (ti, ri, ci) {
      this.qtiJson.tables[ti].rows[ri].cells[ci].dropdownOptions.push('')
      this.$forceUpdate()
    },
    removeDropdownOption (ti, ri, ci, oi) {
      const cell = this.qtiJson.tables[ti].rows[ri].cells[ci]
      cell.dropdownOptions.splice(oi, 1)
      if (cell.correctIndex === oi) {
        this.$set(cell, 'correctIndex', null)
        this.$set(cell, 'value', '')
      } else if (cell.correctIndex > oi) {
        this.$set(cell, 'correctIndex', cell.correctIndex - 1)
      }
      this.handleInput()
      this.$forceUpdate()
    },
    hasOtherDropdownCells (ti, ri, ci) {
      return this.allDropdownCells.some(c => !(c.ti === ti && c.ri === ri && c.ci === ci))
    },
    openCopyDropdownModal (ti, ri, ci) {
      this.copyDropdownTarget = { ti, ri, ci }
      this.copyDropdownSource = null
      this.$bvModal.show('mpc-modal-copy-dropdown')
    },
    executeCopyDropdown () {
      if (!this.copyDropdownSource || !this.copyDropdownTarget) return
      const src = JSON.parse(this.copyDropdownSource)
      const srcCell = this.qtiJson.tables[src.ti].rows[src.ri].cells[src.ci]
      const { ti, ri, ci } = this.copyDropdownTarget
      this.$set(this.qtiJson.tables[ti].rows[ri].cells[ci], 'dropdownOptions', [...srcCell.dropdownOptions])
      this.handleInput()
      this.$forceUpdate()
    },
    confirmDelete (type, index, ti = null) {
      if (type === 'table' && this.qtiJson.tables.length <= 1) { this.$noty.info('You need at least one table.'); return }
      if (type === 'row' && this.qtiJson.tables[ti].rows.length <= 1) { this.$noty.info('You need at least one row.'); return }
      if (type === 'column' && this.qtiJson.tables[ti].columns.length <= 1) { this.$noty.info('You need at least one column.'); return }
      this.pendingDelete = { type, index }
      this.pendingDeleteTableIndex = ti
      this.$bvModal.show('mpc-modal-confirm-delete')
    },
    executeDelete () {
      if (!this.pendingDelete) return
      const { type, index } = this.pendingDelete
      const ti = this.pendingDeleteTableIndex
      if (type === 'table') {
        this.qtiJson.tables.splice(index, 1)
      } else if (type === 'column') {
        const table = this.qtiJson.tables[ti]
        table.columns.splice(index, 1)
        table.rows.forEach(row => { if ((row.rowType === 'data' || row.rowType === 'rowheader') && row.cells) row.cells.splice(index, 1) })
      } else if (type === 'row') {
        this.qtiJson.tables[ti].rows.splice(index, 1)
      }
      this.pendingDelete = null
      this.pendingDeleteTableIndex = null
      this.handleInput()
      this.$forceUpdate()
    },
    rowClass (row) {
      if (row.rowType === 'instruction') return 'instruction-row'
      if (row.rowType === 'rowheader') return 'rowheader-row'
      return 'data-row'
    },
    rowTypeBadgeClass (rowType) {
      if (rowType === 'instruction') return 'badge-info'
      if (rowType === 'rowheader') return 'badge-warning'
      return 'badge-secondary'
    },
    rowTypeLabel (rowType) {
      if (rowType === 'instruction') return 'Instruction'
      if (rowType === 'rowheader') return 'Row Hdr'
      return 'Data'
    },
    getRowError (ti, ri, field) {
      if (!this.parsedErrors || !this.parsedErrors.specific) return null
      const t = this.parsedErrors.specific[ti]; if (!t) return null
      const r = t[ri]; if (!r) return null
      return r[field] || null
    },
    clearRowError (ti, ri, field) {
      if (!this.errorKey) return
      try {
        const raw = this.questionForm.errors.get(this.errorKey)
        if (!raw) return
        const parsed = JSON.parse(raw)
        if (parsed.specific && parsed.specific[ti] && parsed.specific[ti][ri]) {
          delete parsed.specific[ti][ri][field]
          if (Object.keys(parsed.specific[ti][ri]).length === 0) delete parsed.specific[ti][ri]
          if (Object.keys(parsed.specific[ti]).length === 0) delete parsed.specific[ti]
        }
        const hasSpecific = Object.keys(parsed.specific || {}).length > 0
        if (!hasSpecific && !parsed.general) this.questionForm.errors.clear(this.errorKey)
        else this.questionForm.errors.set(this.errorKey, JSON.stringify(parsed))
        this.$forceUpdate()
      } catch (e) { console.error('Error clearing row error:', e) }
    },
    getSpecificError (ti, ri, ci, field) {
      if (!this.parsedErrors || !this.parsedErrors.specific) return null
      const t = this.parsedErrors.specific[ti]; if (!t) return null
      const r = t[ri]; if (!r) return null
      const c = r[ci]; if (!c) return null
      return c[field] || null
    },
    clearSpecificError (ti, ri, ci, field) {
      if (!this.errorKey) return
      try {
        const raw = this.questionForm.errors.get(this.errorKey)
        if (!raw) return
        const parsed = JSON.parse(raw)
        if (parsed.specific && parsed.specific[ti] && parsed.specific[ti][ri] && parsed.specific[ti][ri][ci]) {
          delete parsed.specific[ti][ri][ci][field]
          if (Object.keys(parsed.specific[ti][ri][ci]).length === 0) delete parsed.specific[ti][ri][ci]
          if (Object.keys(parsed.specific[ti][ri]).length === 0) delete parsed.specific[ti][ri]
          if (Object.keys(parsed.specific[ti]).length === 0) delete parsed.specific[ti]
        }
        const hasSpecific = Object.keys(parsed.specific || {}).length > 0
        if (!hasSpecific && !parsed.general) this.questionForm.errors.clear(this.errorKey)
        else this.questionForm.errors.set(this.errorKey, JSON.stringify(parsed))
        this.$forceUpdate()
      } catch (e) { console.error('Error clearing specific error:', e) }
    },
    handleInput () {
      this.$emit('update-qti-json', 'tables', JSON.parse(JSON.stringify(this.qtiJson.tables)))
      this.$emit('update-qti-json', 'randomizeDropdowns', this.qtiJson.randomizeDropdowns)
    }
  }
}
</script>

<style scoped>
.mpc-builder-table td, .mpc-builder-table th { vertical-align: top; padding: 0.3rem 0.35rem !important; }
.col-strip-row { background-color: #f1f3f5; }
.col-strip-th { padding: 4px 8px !important; vertical-align: middle !important; border-bottom: 2px solid #dee2e6 !important; }
.col-strip-actions-th { width: 70px; border-bottom: 2px solid #dee2e6 !important; }
.col-strip-label { font-size: 0.78rem; font-weight: 600; color: #6c757d; white-space: nowrap; margin-right: 3px; }
.col-strip-trash { color: #dc3545; cursor: pointer; font-size: 0.85rem; padding: 2px; border-radius: 3px; transition: opacity 0.15s; }
.col-strip-trash:hover { opacity: 0.7; }
.col-strip-trash-disabled { opacity: 0.25; cursor: not-allowed; pointer-events: none; }

.row-action-cell { display: flex; align-items: center; gap: 4px; white-space: nowrap; }
.row-action-label { font-size: 0.72rem; font-weight: 600; color: #6c757d; white-space: nowrap; }
.row-action-trash { color: #dc3545; cursor: pointer; font-size: 0.9rem; padding: 2px; border-radius: 3px; transition: opacity 0.15s; }
.row-action-trash:hover { opacity: 0.7; }
.row-action-trash-disabled { opacity: 0.25; cursor: not-allowed; pointer-events: none; }
.actions-col { width: 90px; min-width: 90px; text-align: left; vertical-align: middle !important; }
.instruction-row { background-color: #e8f4f8; }
.rowheader-row { background-color: #fef9e7; }
.data-row:hover { background-color: #fafbfc; }
.instruction-cell { vertical-align: middle !important; }
.instruction-input { font-style: italic; background-color: transparent; border: 1px dashed #6cb4d0; }
.rowheader-cell { vertical-align: middle !important; }
.cell-wrapper { display: flex; flex-direction: column; }
.cell-icon-bar { display: flex; align-items: center; gap: 2px; margin-bottom: 4px; min-height: 24px; }
.cell-icon { cursor: pointer; padding: 1px 3px; border-radius: 3px; color: #999; font-size: 0.8rem; transition: all 0.15s ease; display: inline-flex; align-items: center; }
.cell-icon:hover { color: #495057; background-color: #e9ecef; }
.cell-icon.active { color: #fff; background-color: #007bff; }
.cell-blank-indicator { text-align: center; padding: 4px 0; }
.inline-error-text { color: #dc3545; font-size: 0.75rem; margin-top: 2px; line-height: 1.3; }

/* ============================================ */
/* DROPDOWN OPTION ROWS                          */
/* ============================================ */
.dropdown-option-row {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 3px 4px;
  border-radius: 4px;
  margin-bottom: 3px;
  cursor: pointer;
  border: 1px solid transparent;
  transition: background-color 0.12s, border-color 0.12s;
}
.dropdown-option-row:hover {
  background-color: #f0f4ff;
  border-color: #cfe2ff;
}
.dropdown-option-correct {
  background-color: #d1e7dd !important;
  border-color: #a3cfbb !important;
}
.dropdown-option-remove {
  color: #adb5bd;
  cursor: pointer;
  font-size: 1rem;
  flex-shrink: 0;
  padding: 1px 2px;
  border-radius: 3px;
  transition: color 0.12s;
}
.dropdown-option-remove:hover { color: #dc3545; }

/* ============================================ */
/* DRAG AND DROP                                 */
/* ============================================ */
.drag-handle {
  cursor: grab;
  color: #adb5bd;
  font-size: 1rem;
  display: inline-flex;
  align-items: center;
  padding: 2px;
  flex-shrink: 0;
}
.drag-handle:hover { color: #495057; }
.drag-handle:active { cursor: grabbing; }
tr.dragging { opacity: 0.3; background-color: #e9ecef; }
tr.drag-over { box-shadow: inset 0 2px 0 0 #007bff; }
.drag-ghost {
  position: absolute;
  top: -9999px;
  left: -9999px;
  background: #ffffff;
  border: 1px solid #007bff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  opacity: 0.9;
  display: table-row;
  pointer-events: none;
}
</style>
