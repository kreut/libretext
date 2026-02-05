<template>
  <div class="accounting-report-viewer">
    <!-- Report Heading -->
    <div v-if="hasHeading" class="report-heading">
      <div
        v-for="(line, i) in qtiJson.reportHeading"
        :key="`heading-${i}`"
        class="report-heading-line"
        :class="{ 'font-weight-bold': i < 2 }"
      >
        {{ line }}
      </div>
    </div>

    <!-- Report Table -->
    <table v-if="responsesReady" class="table table-sm report-table mb-0">
      <tbody>
      <tr
        v-for="(row, ri) in qtiJson.rows"
        :key="`row-${ri}`"
      >
        <!-- Section Header -->
        <template v-if="row.isHeader">
          <td
            :colspan="qtiJson.columns.length"
            class="section-header-cell"
          >
            {{ row.headerText }}
          </td>
        </template>

        <!-- Data Row -->
        <template v-else>
          <td
            v-for="(col, ci) in qtiJson.columns"
            :key="`cell-${ri}-${ci}`"
            :class="getCellClass(row, ci, col)"
          >
            <div
              class="cell-content"
              :class="getUnderlineClass(row, ci)"
            >
              <!-- Blank cell -->
              <template v-if="row.cells[ci].mode === 'blank'">
                &nbsp;
              </template>

              <!-- Display cell (shown to student) -->
              <template v-else-if="row.cells[ci].mode === 'display'">
                {{ row.cells[ci].value }}
              </template>

              <!-- Answer cell -->
              <template v-else-if="row.cells[ci].mode === 'answer'">
                <!-- Show correct answer (solution/answer view) -->
                <div v-if="showAnswer" class="answer-display correct">
                  {{ col.type === 'numeric' ? formatNumber(row.cells[ci].value) : row.cells[ci].value }}
                </div>
                <!-- Student input -->
                <div v-else-if="studentResponses[ri] !== undefined && studentResponses[ri][ci] !== undefined">
                  <b-form-input
                    v-if="col.type === 'numeric'"
                    v-model="studentResponses[ri][ci]"
                    type="text"
                    inputmode="decimal"
                    size="sm"
                    class="answer-input numeric-input"
                    :class="getFieldClass(ri, ci)"
                    placeholder=""
                    @input="markCellEdited(ri, ci)"
                    @blur="handleBlur(ri, ci)"
                  />
                  <b-form-select
                    v-else-if="col.type === 'text' && col.textInputMode === 'dropdown'"
                    v-model="studentResponses[ri][ci]"
                    size="sm"
                    class="answer-input"
                    :class="getFieldClass(ri, ci)"
                    :options="getDropdownOptions(ci)"
                    @change="markCellEdited(ri, ci)"
                  />
                  <b-form-input
                    v-else
                    v-model="studentResponses[ri][ci]"
                    type="text"
                    size="sm"
                    class="answer-input"
                    :class="getFieldClass(ri, ci)"
                    placeholder=""
                    @input="markCellEdited(ri, ci)"
                  />
                </div>
              </template>
            </div>
          </td>
        </template>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'AccountingReportViewer',
  props: {
    qtiJson: {
      type: Object,
      required: true
    },
    showAnswer: {
      type: Boolean,
      default: false
    }
  },
  data () {
    return {
      studentResponses: {},
      responsesReady: false,
      editedCells: {}
    }
  },
  computed: {
    hasHeading () {
      if (!this.qtiJson.reportHeading || !Array.isArray(this.qtiJson.reportHeading)) {
        return false
      }
      return this.qtiJson.reportHeading.some(line => line && line.trim() !== '')
    },
    parsedGradingResults () {
      let response = this.qtiJson.studentResponse
      if (!response) return null
      if (typeof response === 'string') {
        try {
          response = JSON.parse(response)
        } catch (error) {
          return null
        }
      }
      return response
    }
  },
  created () {
    this.initStudentResponses()
  },
  methods: {
    initStudentResponses () {
      const responses = {}
      if (this.qtiJson.rows) {
        this.qtiJson.rows.forEach((row, ri) => {
          if (!row.isHeader && row.cells) {
            responses[ri] = {}
            row.cells.forEach((cell, ci) => {
              if (cell.mode === 'answer') {
                responses[ri][ci] = ''
              }
            })
          }
        })
      }
      // Pre-populate from graded response or answer_json
      if (this.qtiJson.studentResponse) {
        const sr = typeof this.qtiJson.studentResponse === 'string'
          ? JSON.parse(this.qtiJson.studentResponse)
          : this.qtiJson.studentResponse
        for (const ri in sr) {
          if (!responses[ri]) responses[ri] = {}
          for (const ci in sr[ri]) {
            const cellResult = sr[ri][ci]
            responses[ri][ci] = typeof cellResult === 'object' ? cellResult.studentValue : cellResult
          }
        }
      }
      // Format pre-populated numeric values with commas
      if (this.qtiJson.columns) {
        for (const ri in responses) {
          for (const ci in responses[ri]) {
            if (this.qtiJson.columns[ci] && this.qtiJson.columns[ci].type === 'numeric' && responses[ri][ci] !== '') {
              responses[ri][ci] = this.formatNumber(responses[ri][ci])
            }
          }
        }
      }
      this.studentResponses = responses
      this.responsesReady = true
    },
    formatNumber (value) {
      if (!value && value !== 0) return ''
      // Strip $, commas, and whitespace
      let cleaned = String(value).replace(/[$,\s]/g, '').trim()
      if (cleaned === '' || isNaN(cleaned)) return cleaned
      const num = parseFloat(cleaned)
      // If it has meaningful decimal places, show 2; otherwise no decimals
      if (cleaned.includes('.') && parseFloat(cleaned) !== parseInt(cleaned)) {
        return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      }
      return num.toLocaleString('en-US')
    },
    handleBlur (ri, ci) {
      this.studentResponses[ri][ci] = this.formatNumber(this.studentResponses[ri][ci])
      this.$forceUpdate()
    },
    getCellClass (row, ci, col) {
      const classes = []
      if (col.type === 'numeric') {
        classes.push('text-right')
      }
      if (row.cells[ci].mode === 'blank') {
        classes.push('blank-cell')
      }
      return classes.join(' ')
    },
    getUnderlineClass (row, ci) {
      const cell = row.cells[ci]
      if (!cell) return ''
      if (cell.underline === 'single') return 'underline-single'
      if (cell.underline === 'double') return 'underline-double'
      return ''
    },
    getDropdownOptions (colIndex) {
      const col = this.qtiJson.columns[colIndex]
      if (!col || !col.dropdownOptions || col.dropdownOptions.length === 0) {
        return [{ value: '', text: 'Select...' }]
      }
      const opts = [{ value: '', text: 'Select...' }]
      col.dropdownOptions.forEach(opt => {
        opts.push({ value: opt, text: opt })
      })
      return opts
    },
    markCellEdited (ri, ci) {
      const key = `${ri}-${ci}`
      this.$set(this.editedCells, key, true)
    },
    getFieldClass (ri, ci) {
      // If the student has edited this specific cell, hide grading feedback for it
      if (this.editedCells[`${ri}-${ci}`]) {
        return ''
      }
      if (!this.parsedGradingResults) {
        return ''
      }
      const rowResult = this.parsedGradingResults[String(ri)]
      if (!rowResult) {
        return ''
      }
      const cellResult = rowResult[String(ci)]
      if (!cellResult || typeof cellResult !== 'object' || cellResult.isCorrect === undefined || cellResult.isCorrect === null) {
        return ''
      }
      return cellResult.isCorrect ? 'border-success' : 'border-danger'
    }
  }
}
</script>

<style scoped>
/* ============================================ */
/* REPORT HEADING                                */
/* ============================================ */
.report-heading {
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
/* TABLE                                         */
/* ============================================ */
.report-table {
  border: 1px solid #dee2e6;
  border-top: none;
}

.report-table td {
  border-left: none;
  border-right: none;
  padding: 0.4rem 0.75rem;
  vertical-align: middle;
}

.section-header-cell {
  background-color: #e9ecef;
  font-weight: bold;
  padding: 0.5rem 0.75rem !important;
}

.blank-cell {
  background-color: transparent;
}

/* ============================================ */
/* CELL CONTENT                                  */
/* ============================================ */
.cell-content {
  display: inline-block;
  min-width: 80px;
}

.text-right .cell-content {
  float: right;
}

/* Underline styles */
.underline-single {
  border-bottom: 1px solid #333;
  padding-bottom: 2px;
}

.underline-double {
  border-bottom: 3px double #333;
  padding-bottom: 2px;
}

/* ============================================ */
/* ANSWER INPUTS                                 */
/* ============================================ */
.answer-input {
  min-width: 100px;
  max-width: 180px;
}

.numeric-input {
  text-align: right;
}

/* ============================================ */
/* ANSWER DISPLAY (solution view)                */
/* ============================================ */
.answer-display {
  background-color: #d4edda;
  border: 1px solid #c3e6cb;
  border-radius: 3px;
  padding: 2px 8px;
  min-height: 28px;
  display: inline-block;
  min-width: 80px;
  text-align: center;
  color: #155724;
  font-weight: 600;
}

/* ============================================ */
/* GRADING FEEDBACK BORDERS                      */
/* ============================================ */
input.border-success,
select.border-success {
  border: 2px solid #0d6832 !important;
  box-shadow: none !important;
}

input.border-danger,
select.border-danger {
  border: 2px solid #b02a37 !important;
  box-shadow: none !important;
}
</style>
