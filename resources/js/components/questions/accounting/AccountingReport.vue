<template>
  <div>
    <!-- Confirm Delete Modal (unified) -->
    <b-modal
      id="modal-confirm-delete"
      title="Confirm Delete"
      @ok="executeDelete"
      @hidden="pendingDelete = null"
    >
      <p>{{ deleteMessage }}</p>
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="cancel()">
          Cancel
        </b-button>
        <b-button size="sm" variant="danger" @click="ok()">
          Delete
        </b-button>
      </template>
    </b-modal>

    <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Accounting Report Builder</h2>">
      <b-card-text>

        <!-- ============================================ -->
        <!-- OVERALL HEADING (optional)                   -->
        <!-- ============================================ -->
        <div class="pb-3">
          <b-card>
            <template #header>
              <strong>Report Heading (Optional)</strong>
            </template>

            <div>
              <p class="text-muted small mb-2">
                Add an overall heading for the report (e.g., company name, report title, date).
                Each row is centered and displayed with a colored background.
              </p>

              <div
                v-for="(headingRow, hIndex) in qtiJson.reportHeading"
                :key="`heading-${hIndex}`"
                class="d-flex align-items-center mb-2"
              >
                <b-form-input
                  v-model="qtiJson.reportHeading[hIndex]"
                  type="text"
                  :placeholder="headingPlaceholders[hIndex] || 'Heading line...'"
                  class="mr-2"
                  @input="handleInput()"
                />
                <b-button
                  :id="`heading-delete-${hIndex}`"
                  variant="outline-danger"
                  size="sm"
                  @click="confirmDelete('heading', hIndex)"
                  :disabled="qtiJson.reportHeading.length <= 1"
                  style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;"
                >
                  <b-icon-trash />
                </b-button>
                <b-tooltip
                  :target="`heading-delete-${hIndex}`"
                  delay="500"
                  triggers="hover"
                >
                  Delete Heading Line
                </b-tooltip>
              </div>

              <b-button class="primary" size="sm" @click="addHeadingRow">
                Add Heading Line
              </b-button>

              <!-- Heading Preview -->
              <div v-if="hasAnyHeadingText" class="mt-3">
                <label class="small text-muted">Preview:</label>
                <div class="report-heading-preview">
                  <div
                    v-for="(line, i) in qtiJson.reportHeading"
                    :key="`hp-${i}`"
                    class="report-heading-line"
                    :class="{ 'font-weight-bold': i < 2 }"
                  >
                    {{ line || '(empty)' }}
                  </div>
                </div>
              </div>
            </div>
          </b-card>
        </div>

        <!-- Order Mode -->
        <div class="pb-3">
          <div class="d-flex align-items-center">
            <label class="mb-0 mr-2"><strong>Row Order:</strong></label>
            <b-form-select
              v-model="qtiJson.orderMode"
              :options="orderModeOptions"
              size="sm"
              style="width: auto;"
              @change="handleInput()"
            />
            <span
              v-b-tooltip.hover
              title="Exact Order: student responses must match your row order exactly. Flexible Within Sections: rows between section headers can appear in any order, but sections themselves must be in order (e.g., Revenue items can be in any order, but Revenue must come before Expenses)."
              class="ml-2 text-muted"
            >
              <b-icon-question-circle />
            </span>
          </div>
        </div>

        <!-- ============================================ -->
        <!-- UNIFIED TABLE BUILDER                        -->
        <!-- ============================================ -->
        <div class="pb-3">
          <b-card>
            <template #header>
              <div class="d-flex justify-content-between align-items-center">
                <strong>Report Table</strong>
                <div>
                  <b-button class="primary mr-1" size="sm" @click="addColumn">
                    Add Column
                  </b-button>
                  <b-button class="primary mr-1" size="sm" @click="addRow(false)">
                    Add Row
                  </b-button>
                  <b-button variant="outline-info" size="sm" @click="addRow(true)">
                    Add Section Header
                  </b-button>
                </div>
              </div>
            </template>

            <div class="table-responsive">
              <table class="table table-sm table-bordered report-builder-table mb-0">
                <!-- Column headers with gear icon -->
                <thead>
                <tr class="report-table-header">
                  <th
                    v-for="(col, ci) in qtiJson.columns"
                    :key="`th-${ci}`"
                    class="col-header-th"
                  >
                    <div class="col-header-wrapper">
                      <div class="col-header-top">
                        <b-form-input
                          v-model="col.header"
                          type="text"
                          size="sm"
                          class="col-header-input"
                          :placeholder="`Column ${ci + 1}`"
                          @input="handleInput()"
                        />
                        <b-button
                          :id="`col-gear-${ci}`"
                          variant="link"
                          size="sm"
                          class="col-gear-btn p-0 ml-1"
                          title="Column settings"
                        >
                          <b-icon-gear-fill variant="secondary" />
                        </b-button>
                        <b-button
                          :id="`col-delete-${ci}`"
                          variant="outline-danger"
                          size="sm"
                          class="col-remove-btn p-0 ml-1"
                          :disabled="qtiJson.columns.length <= 1"
                          @click="confirmDelete('column', ci)"
                          style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;"
                        >
                          <b-icon-trash />
                        </b-button>
                        <b-tooltip
                          :target="`col-delete-${ci}`"
                          delay="500"
                          triggers="hover"
                        >
                          Delete Column
                        </b-tooltip>
                      </div>

                      <div class="col-type-badge mt-1">
                          <span class="badge" :class="col.type === 'numeric' ? 'badge-info' : 'badge-secondary'">
                            {{ col.type }}
                          </span>
                        <span v-if="col.type === 'text' && col.textInputMode === 'dropdown'" class="badge badge-warning ml-1">
                            dropdown
                          </span>
                      </div>

                      <!-- Column settings popover -->
                      <b-popover
                        :target="`col-gear-${ci}`"
                        triggers="click blur"
                        placement="bottom"
                        :title="`Column ${ci + 1} Settings`"
                        custom-class="col-settings-popover"
                      >
                        <div class="mb-2">
                          <label class="small font-weight-bold mb-1">Type</label>
                          <b-form-select
                            v-model="col.type"
                            :options="columnTypeOptions"
                            size="sm"
                            @change="onColumnTypeChange(ci); handleInput()"
                          />
                        </div>

                        <div v-if="col.type === 'text'" class="mb-2">
                          <label class="small font-weight-bold mb-1">Student Input Mode</label>
                          <b-form-select
                            v-model="col.textInputMode"
                            :options="textInputModeOptions"
                            size="sm"
                            @change="handleInput()"
                          />
                        </div>

                        <div v-if="col.type === 'text' && col.textInputMode === 'dropdown'">
                          <p class="small text-muted mb-0">
                            Dropdown options are auto-populated from the answer cell values in this column.
                          </p>
                        </div>
                      </b-popover>
                    </div>
                  </th>
                  <th class="actions-col">Actions</th>
                </tr>
                </thead>

                <tbody>
                <tr
                  v-for="(row, ri) in qtiJson.rows"
                  :key="`row-${ri}`"
                  :class="{
                      'section-header-row': row.isHeader,
                      'data-row': !row.isHeader,
                      'drag-over': dragOverIndex === ri
                    }"
                >
                  <!-- SECTION HEADER ROW -->
                  <template v-if="row.isHeader">
                    <td
                      :colspan="qtiJson.columns.length"
                      class="section-header-cell"
                      @dragover.prevent="onDragOver(ri, $event)"
                      @dragenter.prevent="onDragEnter(ri)"
                      @dragleave="onDragLeave(ri)"
                      @drop.prevent="onDrop(ri)"
                    >
                      <div class="d-flex align-items-center">
                          <span
                            class="drag-handle mr-2"
                            draggable="true"
                            @dragstart="onDragStart(ri, $event)"
                            @dragend="onDragEnd"
                            title="Drag to reorder"
                          >
                            <b-icon-grip-horizontal />
                          </span>
                        <b-form-input
                          v-model="row.headerText"
                          type="text"
                          size="sm"
                          placeholder="Section header (e.g., Revenues, Expenses)..."
                          class="section-header-input flex-grow-1"
                          :class="{ 'is-invalid': getSpecificError(ri, 'header') }"
                          @input="clearSpecificError(ri, 'header'); handleInput()"
                        />
                      </div>
                      <div v-if="getSpecificError(ri, 'header')" class="inline-error-text">
                        {{ getSpecificError(ri, 'header') }}
                      </div>
                    </td>
                  </template>

                  <!-- DATA ROW: per-cell -->
                  <template v-else>
                    <td
                      v-for="(col, ci) in qtiJson.columns"
                      :key="`cell-${ri}-${ci}`"
                      class="cell-td"
                      @dragover.prevent="onDragOver(ri, $event)"
                      @dragenter.prevent="onDragEnter(ri)"
                      @dragleave="onDragLeave(ri)"
                      @drop.prevent="onDrop(ri)"
                    >
                      <div v-if="row.cells && row.cells[ci]" class="cell-wrapper">
                        <!-- Icon bar: drag handle on first col + mode + underline -->
                        <div class="cell-icon-bar">
                            <span
                              v-if="ci === 0"
                              class="drag-handle mr-1"
                              draggable="true"
                              @dragstart="onDragStart(ri, $event)"
                              @dragend="onDragEnd"
                              title="Drag to reorder"
                            >
                              <b-icon-grip-horizontal />
                            </span>
                          <!-- Mode icons -->
                          <span
                            class="cell-icon"
                            :class="{ active: row.cells[ci].mode === 'answer' }"
                            title="Answer (student fills in)"
                            @click="setCellMode(ri, ci, 'answer')"
                          >
                              <b-icon-pencil-square />
                            </span>
                          <span
                            class="cell-icon"
                            :class="{ active: row.cells[ci].mode === 'display' }"
                            title="Display (show value)"
                            @click="setCellMode(ri, ci, 'display')"
                          >
                              <b-icon-eye />
                            </span>
                          <span
                            class="cell-icon"
                            :class="{ active: row.cells[ci].mode === 'blank' }"
                            title="Blank (empty cell)"
                            @click="setCellMode(ri, ci, 'blank')"
                          >
                              <b-icon-square />
                            </span>

                          <span class="cell-icon-divider">|</span>

                          <!-- Underline icons -->
                          <span
                            class="cell-icon underline-icon"
                            :class="{ active: row.cells[ci].underline === 'none' }"
                            title="No underline"
                            @click="setCellUnderline(ri, ci, 'none')"
                          >
                              <span class="ul-icon-none">N</span>
                            </span>
                          <span
                            class="cell-icon underline-icon"
                            :class="{ active: row.cells[ci].underline === 'single' }"
                            title="Single underline"
                            @click="setCellUnderline(ri, ci, 'single')"
                          >
                              <span class="ul-icon-single">U</span>
                            </span>
                          <span
                            class="cell-icon underline-icon"
                            :class="{ active: row.cells[ci].underline === 'double' }"
                            title="Double underline"
                            @click="setCellUnderline(ri, ci, 'double')"
                          >
                              <span class="ul-icon-double">U</span>
                            </span>
                        </div>

                        <!-- Cell value input -->
                        <div v-if="row.cells[ci].mode === 'answer' || row.cells[ci].mode === 'display'" class="cell-value-input">
                          <b-form-input
                            v-if="col.type === 'numeric'"
                            v-model="row.cells[ci].value"
                            type="text"
                            inputmode="decimal"
                            size="sm"
                            :placeholder="row.cells[ci].mode === 'answer' ? 'Answer...' : 'Value...'"
                            :class="{ 'is-invalid': getSpecificError(ri, ci, 'value') }"
                            @input="clearSpecificError(ri, ci, 'value'); handleInput()"
                            @blur="formatNumericCell(ri, ci)"
                          />
                          <b-form-input
                            v-else
                            v-model="row.cells[ci].value"
                            type="text"
                            size="sm"
                            :placeholder="row.cells[ci].mode === 'answer' ? 'Answer...' : 'Text...'"
                            :class="{ 'is-invalid': getSpecificError(ri, ci, 'value') }"
                            @input="clearSpecificError(ri, ci, 'value'); handleInput()"
                          />
                          <div v-if="getSpecificError(ri, ci, 'value')" class="inline-error-text">
                            {{ getSpecificError(ri, ci, 'value') }}
                          </div>
                        </div>

                        <!-- Blank indicator -->
                        <div v-else-if="row.cells[ci].mode === 'blank'" class="cell-blank-indicator">
                          <span class="text-muted small">(blank)</span>
                        </div>
                      </div>
                    </td>
                  </template>

                  <!-- Actions -->
                  <td class="align-middle text-center actions-col">
                    <b-button
                      :id="`row-delete-${ri}`"
                      variant="outline-danger"
                      size="sm"
                      @click="confirmDelete('row', ri)"
                      :disabled="qtiJson.rows.length <= 1"
                    >
                      <b-icon-trash />
                    </b-button>
                    <b-tooltip
                      :target="`row-delete-${ri}`"
                      delay="500"
                      triggers="hover"
                    >
                      Delete {{ row.isHeader ? 'Section Header' : 'Row' }}
                    </b-tooltip>
                  </td>
                </tr>
                </tbody>
              </table>
            </div>

            <div class="d-flex justify-content-start mt-2">
              <b-button class="primary mr-1" size="sm" @click="addColumn">
                Add Column
              </b-button>
              <b-button class="primary mr-1" size="sm" @click="addRow(false)">
                Add Row
              </b-button>
              <b-button variant="outline-info" size="sm" @click="addRow(true)">
                Add Section Header
              </b-button>
            </div>
          </b-card>
        </div>

        <!-- General Errors -->
        <ErrorMessage
          v-if="generalError"
          class="pb-2"
          :message="generalError"
        />

      </b-card-text>
    </b-card>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'
import ErrorMessage from '~/components/ErrorMessage'

export default {
  name: 'AccountingReport',
  components: { ErrorMessage },
  props: {
    qtiJson: {
      type: Object,
      default: () => ({})
    },
    questionForm: {
      type: Object,
      default: () => ({})
    }
  },
  data () {
    return {
      dragIndex: null,
      dragOverIndex: null,
      pendingDelete: null,
      headingPlaceholders: [
        'Company name (e.g., WILDHORSE CO.)',
        'Report title (e.g., Income Statement)',
        'Period (e.g., For the Year Ended December 31, 2025)'
      ],
      columnTypeOptions: [
        { value: 'text', text: 'Text' },
        { value: 'numeric', text: 'Numeric' }
      ],
      textInputModeOptions: [
        { value: 'text', text: 'Free Text Entry' },
        { value: 'dropdown', text: 'Dropdown' }
      ],
      orderModeOptions: [
        { value: 'exact', text: 'Exact Order' },
        { value: 'within_sections', text: 'Flexible Within Sections' }
      ]
    }
  },
  computed: {
    errorKey () {
      if (this.questionForm && this.questionForm.errors) {
        if (this.questionForm.errors.get('report')) {
          return 'report'
        }
        if (this.questionForm.errors.get('qti_json')) {
          return 'qti_json'
        }
      }
      return null
    },
    parsedErrors () {
      if (!this.errorKey) return null
      try {
        const raw = this.questionForm.errors.get(this.errorKey)
        if (!raw) return null
        return JSON.parse(raw)
      } catch {
        return null
      }
    },
    generalError () {
      if (this.parsedErrors && this.parsedErrors.general) {
        return this.parsedErrors.general
      }
      return null
    },
    hasAnyHeadingText () {
      if (!this.qtiJson.reportHeading || !Array.isArray(this.qtiJson.reportHeading)) {
        return false
      }
      return this.qtiJson.reportHeading.some(line => line && line.trim() !== '')
    },
    deleteMessage () {
      if (!this.pendingDelete) return ''
      const { type, index } = this.pendingDelete
      if (type === 'heading') {
        const text = this.qtiJson.reportHeading[index]
        return `Are you sure you want to delete this heading line${text ? ` "${text}"` : ''}?`
      }
      if (type === 'column') {
        const col = this.qtiJson.columns[index]
        const name = col && col.header ? `column "${col.header}"` : `column ${index + 1}`
        return `Are you sure you want to delete ${name}? This will also remove all data in that column.`
      }
      const row = this.qtiJson.rows[index]
      if (row && row.isHeader) {
        const name = row.headerText ? `section header "${row.headerText}"` : 'section header'
        return `Are you sure you want to delete this ${name}?`
      }
      return `Are you sure you want to delete this row ${index + 1}?`
    }
  },
  mounted () {
    this.initializeDefaults()
  },
  methods: {
    // ==========================================
    // INITIALIZATION
    // ==========================================
    initializeDefaults () {
      if (!this.qtiJson.reportHeading) {
        this.$set(this.qtiJson, 'reportHeading', ['', '', ''])
      }
      if (!this.qtiJson.orderMode) {
        this.$set(this.qtiJson, 'orderMode', 'exact')
      }
      if (!this.qtiJson.columns || this.qtiJson.columns.length === 0) {
        this.$set(this.qtiJson, 'columns', [
          {
            identifier: uuidv4(),
            header: '',
            type: 'text',
            textInputMode: 'text',
            dropdownOptions: []
          },
          {
            identifier: uuidv4(),
            header: '',
            type: 'numeric',
            textInputMode: 'text',
            dropdownOptions: []
          }
        ])
      }
      if (!this.qtiJson.rows || this.qtiJson.rows.length === 0) {
        this.$set(this.qtiJson, 'rows', [
          this.createRow(false),
          this.createRow(false),
          this.createRow(false)
        ])
      }
      this.handleInput()
    },

    // ==========================================
    // NUMBER FORMATTING
    // ==========================================
    formatNumber (value) {
      if (!value && value !== 0) return ''
      let cleaned = String(value).replace(/[$,\s]/g, '').trim()
      if (cleaned === '' || isNaN(cleaned)) return String(value)
      const num = parseFloat(cleaned)
      if (cleaned.includes('.') && parseFloat(cleaned) !== parseInt(cleaned)) {
        return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      }
      return num.toLocaleString('en-US')
    },
    stripNumericFormatting (value) {
      if (!value) return ''
      return String(value).replace(/[$,\s]/g, '').trim()
    },
    formatNumericCell (ri, ci) {
      const cell = this.qtiJson.rows[ri].cells[ci]
      if (cell && cell.value) {
        cell.value = this.stripNumericFormatting(cell.value)
        const formatted = this.formatNumber(cell.value)
        this.$set(cell, 'displayValue', formatted)
        this.handleInput()
        this.$forceUpdate()
      }
    },

    // ==========================================
    // ERROR HELPERS
    // ==========================================
    getSpecificError (rowIndex, cellIndexOrField, field) {
      if (!this.parsedErrors || !this.parsedErrors.specific) return null
      const rowErrors = this.parsedErrors.specific[rowIndex]
      if (!rowErrors) return null

      if (field === undefined) {
        return rowErrors[cellIndexOrField] || null
      }
      if (rowErrors[cellIndexOrField] && rowErrors[cellIndexOrField][field]) {
        return rowErrors[cellIndexOrField][field]
      }
      return null
    },
    clearSpecificError (rowIndex, cellIndexOrField, field) {
      if (!this.errorKey) return
      try {
        const raw = this.questionForm.errors.get(this.errorKey)
        if (!raw) return
        const parsed = JSON.parse(raw)
        if (!parsed.specific || !parsed.specific[rowIndex]) return

        if (field === undefined) {
          delete parsed.specific[rowIndex][cellIndexOrField]
        } else {
          if (parsed.specific[rowIndex][cellIndexOrField]) {
            delete parsed.specific[rowIndex][cellIndexOrField][field]
            if (Object.keys(parsed.specific[rowIndex][cellIndexOrField]).length === 0) {
              delete parsed.specific[rowIndex][cellIndexOrField]
            }
          }
        }
        if (Object.keys(parsed.specific[rowIndex]).length === 0) {
          delete parsed.specific[rowIndex]
        }
        const hasSpecific = Object.keys(parsed.specific).length > 0
        if (!hasSpecific && !parsed.general) {
          this.questionForm.errors.clear(this.errorKey)
        } else {
          this.questionForm.errors.set(this.errorKey, JSON.stringify(parsed))
        }
        this.$forceUpdate()
      } catch (error) {
        console.error('Error clearing specific error:', error)
      }
    },

    // ==========================================
    // DELETE CONFIRMATION (unified)
    // ==========================================
    confirmDelete (type, index) {
      const collections = {
        heading: this.qtiJson.reportHeading,
        column: this.qtiJson.columns,
        row: this.qtiJson.rows
      }
      if (collections[type].length <= 1) {
        this.$noty.info(`You need at least one ${type === 'heading' ? 'heading line' : type}.`)
        return
      }
      this.pendingDelete = { type, index }
      this.$bvModal.show('modal-confirm-delete')
    },
    executeDelete () {
      if (!this.pendingDelete) return
      const { type, index } = this.pendingDelete
      if (type === 'heading') {
        this.qtiJson.reportHeading.splice(index, 1)
      } else if (type === 'column') {
        this.qtiJson.columns.splice(index, 1)
        this.qtiJson.rows.forEach(row => {
          if (!row.isHeader && row.cells) row.cells.splice(index, 1)
        })
      } else {
        this.qtiJson.rows.splice(index, 1)
      }
      this.pendingDelete = null
      this.handleInput()
      this.$forceUpdate()
    },

    // ==========================================
    // HEADING MANAGEMENT
    // ==========================================
    addHeadingRow () {
      this.qtiJson.reportHeading.push('')
      this.handleInput()
    },

    // ==========================================
    // COLUMN MANAGEMENT
    // ==========================================
    addColumn () {
      const newCol = {
        identifier: uuidv4(),
        header: '',
        type: 'numeric',
        textInputMode: 'text',
        dropdownOptions: []
      }
      this.qtiJson.columns.push(newCol)
      this.qtiJson.rows.forEach(row => {
        if (!row.isHeader && row.cells) {
          row.cells.push(this.createCell())
        }
      })
      this.handleInput()
      this.$forceUpdate()
    },
    onColumnTypeChange (colIndex) {
      const col = this.qtiJson.columns[colIndex]
      if (col.type === 'numeric') {
        col.textInputMode = 'text'
        col.dropdownOptions = []
      }
    },

    // ==========================================
    // ROW MANAGEMENT
    // ==========================================
    createCell () {
      return {
        identifier: uuidv4(),
        mode: 'answer',
        value: '',
        underline: 'none'
      }
    },
    createRow (isHeader) {
      if (isHeader) {
        return {
          identifier: uuidv4(),
          isHeader: true,
          headerText: ''
        }
      }
      const cells = this.qtiJson.columns.map(() => this.createCell())
      return {
        identifier: uuidv4(),
        isHeader: false,
        headerText: '',
        cells
      }
    },
    addRow (isHeader) {
      this.qtiJson.rows.push(this.createRow(isHeader))
      this.handleInput()
      this.$forceUpdate()
    },
    moveRow (fromIndex, toIndex) {
      if (toIndex < 0 || toIndex >= this.qtiJson.rows.length) return
      const rows = this.qtiJson.rows
      const item = rows.splice(fromIndex, 1)[0]
      rows.splice(toIndex, 0, item)
      this.handleInput()
      this.$forceUpdate()
    },

    // ==========================================
    // DRAG AND DROP
    // ==========================================
    onDragStart (index, event) {
      this.dragIndex = index
      event.dataTransfer.effectAllowed = 'move'
      event.dataTransfer.setData('text/plain', index)

      const row = event.target.closest('tr')
      if (row) {
        row.classList.add('dragging')
        const dragImage = row.cloneNode(true)
        dragImage.classList.add('drag-ghost')
        dragImage.style.width = row.offsetWidth + 'px'
        document.body.appendChild(dragImage)
        event.dataTransfer.setDragImage(dragImage, event.offsetX, event.offsetY)
        requestAnimationFrame(() => {
          document.body.removeChild(dragImage)
        })
      }
    },
    onDragOver (index, event) {
      event.dataTransfer.dropEffect = 'move'
    },
    onDragEnter (index) {
      if (index !== this.dragIndex) {
        this.dragOverIndex = index
      }
    },
    onDragLeave (index) {
      if (this.dragOverIndex === index) {
        this.dragOverIndex = null
      }
    },
    onDrop (toIndex) {
      this.dragOverIndex = null
      if (this.dragIndex !== null && this.dragIndex !== toIndex) {
        this.moveRow(this.dragIndex, toIndex)
      }
      this.dragIndex = null
    },
    onDragEnd () {
      this.dragOverIndex = null
      this.dragIndex = null
      document.querySelectorAll('.dragging').forEach(el => el.classList.remove('dragging'))
    },
    onRowTypeChange (rowIndex) {
      const row = this.qtiJson.rows[rowIndex]
      if (row.isHeader) {
        if (!row.headerText) {
          row.headerText = ''
        }
      } else {
        if (!row.cells || row.cells.length !== this.qtiJson.columns.length) {
          this.$set(row, 'cells', this.qtiJson.columns.map(() => this.createCell()))
        }
      }
    },

    // ==========================================
    // CELL ICON ACTIONS
    // ==========================================
    setCellMode (rowIndex, colIndex, mode) {
      const cell = this.qtiJson.rows[rowIndex].cells[colIndex]
      cell.mode = mode
      if (mode === 'blank') {
        cell.value = ''
        cell.underline = 'none'
      }
      this.clearSpecificError(rowIndex, colIndex, 'value')
      this.handleInput()
      this.$forceUpdate()
    },
    setCellUnderline (rowIndex, colIndex, underline) {
      this.qtiJson.rows[rowIndex].cells[colIndex].underline = underline
      this.handleInput()
      this.$forceUpdate()
    },

    // ==========================================
    // DROPDOWN HELPERS
    // ==========================================
    getDropdownOptionsForColumn (colIndex) {
      const col = this.qtiJson.columns[colIndex]
      if (!col || !col.dropdownOptions || col.dropdownOptions.length === 0) {
        return [{ value: '', text: 'No options defined' }]
      }
      const opts = [{ value: '', text: 'Select...' }]
      col.dropdownOptions.forEach(opt => {
        opts.push({ value: opt, text: opt })
      })
      return opts
    },

    // ==========================================
    // EMIT UPDATES
    // ==========================================
    handleInput () {
      // Strip commas from numeric cell values before emitting
      const cleanedRows = JSON.parse(JSON.stringify(this.qtiJson.rows))
      cleanedRows.forEach(row => {
        if (!row.isHeader && row.cells) {
          row.cells.forEach((cell, ci) => {
            const col = this.qtiJson.columns[ci]
            if (col && col.type === 'numeric' && cell.value) {
              cell.value = this.stripNumericFormatting(cell.value)
            }
          })
        }
      })
      // Auto-populate dropdown options from answer cell values
      this.qtiJson.columns.forEach((col, ci) => {
        if (col.type === 'text' && col.textInputMode === 'dropdown') {
          const options = []
          this.qtiJson.rows.forEach(row => {
            if (!row.isHeader && row.cells && row.cells[ci]) {
              const cell = row.cells[ci]
              if (cell.mode === 'answer' && cell.value && cell.value.trim() !== '') {
                const val = cell.value.trim()
                if (!options.includes(val)) {
                  options.push(val)
                }
              }
            }
          })
          col.dropdownOptions = options.sort()
        }
      })
      this.$emit('update-qti-json', 'reportHeading', this.qtiJson.reportHeading)
      this.$emit('update-qti-json', 'columns', this.qtiJson.columns)
      this.$emit('update-qti-json', 'rows', cleanedRows)
      this.$emit('update-qti-json', 'orderMode', this.qtiJson.orderMode)
    }
  }
}
</script>

<style scoped>
/* ============================================ */
/* COLUMN WIDTHS                                 */
/* ============================================ */
.actions-col {
  width: 60px;
  min-width: 60px;
}
.col-header-th {
  min-width: 200px;
}

/* ============================================ */
/* DRAG HANDLE                                   */
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

.drag-handle:hover {
  color: #495057;
}

.drag-handle:active {
  cursor: grabbing;
}

tr.dragging {
  opacity: 0.3;
  background-color: #e9ecef;
}

tr.drag-over {
  box-shadow: inset 0 2px 0 0 #007bff;
}

.drag-ghost {
  position: absolute;
  top: -9999px;
  left: -9999px;
  background: #ffffff;
  border: 1px solid #007bff;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  opacity: 0.9;
  display: table-row;
  table-layout: fixed;
  pointer-events: none;
  border-radius: 4px;
}

/* ============================================ */
/* COLUMN HEADER                                 */
/* ============================================ */
.report-table-header {
  background-color: #f8f9fa;
}

.col-header-wrapper {
  display: flex;
  flex-direction: column;
}

.col-header-top {
  display: flex;
  align-items: center;
}

.col-header-input {
  flex: 1;
  font-weight: 600;
  font-size: 0.85rem;
}

.col-gear-btn {
  line-height: 1;
  flex-shrink: 0;
}

.col-remove-btn {
  line-height: 1;
  flex-shrink: 0;
}

.col-type-badge {
  font-size: 0.7rem;
}

/* ============================================ */
/* TABLE BODY                                    */
/* ============================================ */
.report-builder-table td {
  vertical-align: top;
}

.section-header-row {
  background-color: #e9ecef;
}

.section-header-cell {
  padding: 0.4rem;
}

.section-header-input {
  font-weight: bold;
  background-color: transparent;
  border: 1px dashed #adb5bd;
}

/* ============================================ */
/* CELL ICON BAR                                 */
/* ============================================ */
.cell-td {
  padding: 0.25rem 0.35rem !important;
}

.cell-wrapper {
  display: flex;
  flex-direction: column;
}

.cell-icon-bar {
  display: flex;
  align-items: center;
  gap: 2px;
  margin-bottom: 4px;
  flex-wrap: nowrap;
}

.cell-icon {
  cursor: pointer;
  padding: 1px 3px;
  border-radius: 3px;
  color: #999;
  font-size: 0.8rem;
  transition: all 0.15s ease;
  display: inline-flex;
  align-items: center;
}

.cell-icon:hover {
  color: #495057;
  background-color: #e9ecef;
}

.cell-icon.active {
  color: #fff;
  background-color: #007bff;
}

.cell-icon-divider {
  color: #ccc;
  font-size: 0.75rem;
  margin: 0 2px;
  user-select: none;
}

/* Underline icon styles */
.ul-icon-none {
  font-size: 0.65rem;
  font-weight: bold;
}

.ul-icon-single {
  font-size: 0.7rem;
  font-weight: bold;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.ul-icon-double {
  font-size: 0.7rem;
  font-weight: bold;
  text-decoration: underline;
  text-decoration-style: double;
  text-underline-offset: 2px;
}

.cell-value-input {
  flex: 1;
}

.cell-blank-indicator {
  text-align: center;
  padding: 4px 0;
}

/* ============================================ */
/* INLINE ERRORS                                 */
/* ============================================ */
.inline-error-text {
  color: #dc3545;
  font-size: 0.75rem;
  margin-top: 2px;
  line-height: 1.3;
}

/* ============================================ */
/* REPORT HEADING PREVIEW                        */
/* ============================================ */
.report-heading-preview {
  background-color: #1a2744;
  color: #ffffff;
  text-align: center;
  padding: 12px 20px;
  border-radius: 4px 4px 0 0;
}

.report-heading-line {
  font-size: 0.95rem;
  line-height: 1.5;
}

/* ============================================ */
/* MISC                                          */
/* ============================================ */
.table {
  margin-bottom: 1rem;
}

.data-row:hover {
  background-color: #fafbfc;
}
</style>
