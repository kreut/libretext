<template>
  <div class="mpc-viewer">

    <!-- Tables -->
    <div
      v-for="(table, ti) in qtiJson.tables"
      :key="`table-${ti}`"
      class="mpc-table-wrapper mb-4"
    >

      <!-- ============================================ -->
      <!-- GRID TABLE (tableType === 'table')            -->
      <!-- ============================================ -->
      <table v-if="table.tableType !== 'lineItems'" class="mpc-table">
        <thead v-if="tableHasColumnHeaders(table)">
        <tr>
          <th
            v-for="(col, ci) in table.columns"
            :key="`th-${ti}-${ci}`"
            class="mpc-col-header"
          >
            {{ col.header }}
          </th>
        </tr>
        </thead>

        <tbody>
        <tr v-for="(row, ri) in table.rows" :key="`row-${ti}-${ri}`">
          <template v-if="row.rowType === 'instruction'">
            <td :colspan="table.columns.length" class="mpc-instruction-cell">{{ row.instructionText }}</td>
          </template>
          <template v-else-if="row.rowType === 'rowheader'">
            <td
              v-for="(col, ci) in table.columns"
              :key="`rhcell-${ti}-${ri}-${ci}`"
              class="mpc-rowheader-cell"
            >{{ row.cells[ci] ? row.cells[ci].value : '' }}</td>
          </template>
          <template v-else>
            <td v-for="(col, ci) in table.columns" :key="`cell-${ti}-${ri}-${ci}`" class="mpc-data-cell">
              <template v-if="row.cells && row.cells[ci]">
                <template v-if="row.cells[ci].mode === 'blank'">&nbsp;</template>
                <template v-else-if="row.cells[ci].mode === 'display'">
                  <span class="mpc-display-value">{{ row.cells[ci].value }}</span>
                </template>
                <template v-else-if="row.cells[ci].mode === 'answer'">
                  <div v-if="showAnswer" class="mpc-answer-display">{{ formatAnswerForDisplay(row.cells[ci]) }}</div>
                  <div v-else class="mpc-answer-input-wrapper">
                    <template v-if="row.cells[ci].answerType === 'dropdown'">
                      <b-form-select
                        v-if="responsesReady && studentResponses[ti] && studentResponses[ti][ri] && studentResponses[ti][ri][ci] !== undefined"
                        v-model="studentResponses[ti][ri][ci]"
                        size="sm" class="mpc-answer-select" :class="getFieldClass(ti, ri, ci)"
                        :options="getDropdownOptions(row.cells[ci])"
                        @change="markEdited(ti, ri, ci)"
                      />
                    </template>
                    <template v-else>
                      <div class="mpc-numeric-input-group">
                        <span v-if="row.cells[ci].answerType === 'dollar'" class="mpc-unit-prefix">$</span>
                        <b-form-input
                          v-if="responsesReady && studentResponses[ti] && studentResponses[ti][ri] && studentResponses[ti][ri][ci] !== undefined"
                          v-model="studentResponses[ti][ri][ci]"
                          type="text" inputmode="decimal" size="sm"
                          class="mpc-answer-input" :class="getFieldClass(ti, ri, ci)"
                          placeholder="" @input="markEdited(ti, ri, ci)" @blur="handleBlur(ti, ri, ci)"
                        />
                        <span v-if="row.cells[ci].answerType === 'percentage'" class="mpc-unit-suffix">%</span>
                        <span v-if="row.cells[ci].answerType === 'ratio'" class="mpc-unit-suffix">:1</span>
                        <span v-if="row.cells[ci].answerType === 'custom' && row.cells[ci].customUnit" class="mpc-unit-suffix">{{ row.cells[ci].customUnit }}</span>
                      </div>
                    </template>
                  </div>
                </template>
              </template>
            </td>
          </template>
        </tr>
        </tbody>
      </table>

      <!-- ============================================ -->
      <!-- LINE ITEMS (tableType === 'lineItems')        -->
      <!-- Div/flexbox — label shrinks, answer fills     -->
      <!-- ============================================ -->
      <div v-else class="mpc-line-items">
        <div v-for="(row, ri) in table.rows" :key="`lirow-${ti}-${ri}`">

          <!-- Instruction -->
          <div v-if="row.rowType === 'instruction'" class="mpc-instruction-cell">
            {{ row.instructionText }}
          </div>

          <!-- Data row -->
          <div v-else-if="row.rowType === 'data'" class="mpc-li-row">
            <template v-for="(col, ci) in table.columns">
              <template v-if="row.cells && row.cells[ci]">

                <span v-if="row.cells[ci].mode === 'blank'" :key="`liblank-${ti}-${ri}-${ci}`" class="mpc-li-blank" />

                <span v-else-if="row.cells[ci].mode === 'display'" :key="`lidisplay-${ti}-${ri}-${ci}`" class="mpc-li-label">
                  {{ row.cells[ci].value }}
                </span>

                <span v-else-if="row.cells[ci].mode === 'answer'" :key="`lianswer-${ti}-${ri}-${ci}`" class="mpc-li-answer">
                  <span v-if="showAnswer" class="mpc-answer-display">{{ formatAnswerForDisplay(row.cells[ci]) }}</span>
                  <span v-else class="mpc-li-input-wrapper">
                    <template v-if="row.cells[ci].answerType === 'dropdown'">
                      <b-form-select
                        v-if="responsesReady && studentResponses[ti] && studentResponses[ti][ri] && studentResponses[ti][ri][ci] !== undefined"
                        v-model="studentResponses[ti][ri][ci]"
                        size="sm" class="mpc-li-select" :class="getFieldClass(ti, ri, ci)"
                        :options="getDropdownOptions(row.cells[ci])"
                        @change="markEdited(ti, ri, ci)"
                      />
                    </template>
                    <template v-else>
                      <span v-if="row.cells[ci].answerType === 'dollar'" class="mpc-unit-prefix">$</span>
                      <b-form-input
                        v-if="responsesReady && studentResponses[ti] && studentResponses[ti][ri] && studentResponses[ti][ri][ci] !== undefined"
                        v-model="studentResponses[ti][ri][ci]"
                        type="text" inputmode="decimal" size="sm"
                        class="mpc-li-input" :class="getFieldClass(ti, ri, ci)"
                        placeholder="" @input="markEdited(ti, ri, ci)" @blur="handleBlur(ti, ri, ci)"
                      />
                      <span v-if="row.cells[ci].answerType === 'percentage'" class="mpc-unit-suffix">%</span>
                      <span v-if="row.cells[ci].answerType === 'ratio'" class="mpc-unit-suffix">:1</span>
                      <span v-if="row.cells[ci].answerType === 'custom' && row.cells[ci].customUnit" class="mpc-unit-suffix">{{ row.cells[ci].customUnit }}</span>
                    </template>
                  </span>
                </span>

              </template>
            </template>
          </div>

        </div>
      </div>

    </div>
  </div>
</template>

<script>
export default {
  name: 'AccountingMultiPartComputationViewer',
  props: {
    qtiJson: { type: Object, required: true },
    showAnswer: { type: Boolean, default: false }
  },
  data () {
    return {
      studentResponses: {},
      responsesReady: false,
      editedCells: {}
    }
  },
  computed: {
    parsedGradingResults () {
      let response = this.qtiJson.studentResponse
      if (!response) return null
      if (typeof response === 'string') {
        try { response = JSON.parse(response) } catch { return null }
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
      if (this.qtiJson.tables) {
        this.qtiJson.tables.forEach((table, ti) => {
          responses[ti] = {}
          table.rows.forEach((row, ri) => {
            if (row.rowType === 'data' && row.cells) {
              responses[ti][ri] = {}
              row.cells.forEach((cell, ci) => {
                if (cell.mode === 'answer') responses[ti][ri][ci] = ''
              })
            }
          })
        })
      }
      if (this.qtiJson.studentResponse) {
        const sr = typeof this.qtiJson.studentResponse === 'string'
          ? JSON.parse(this.qtiJson.studentResponse)
          : this.qtiJson.studentResponse
        for (const ti in sr) {
          if (!responses[ti]) responses[ti] = {}
          for (const ri in sr[ti]) {
            if (!responses[ti][ri]) responses[ti][ri] = {}
            for (const ci in sr[ti][ri]) {
              const cellResult = sr[ti][ri][ci]
              responses[ti][ri][ci] = typeof cellResult === 'object' ? cellResult.studentValue : cellResult
            }
          }
        }
      }
      if (this.qtiJson.tables) {
        this.qtiJson.tables.forEach((table, ti) => {
          table.rows.forEach((row, ri) => {
            if (row.rowType === 'data' && row.cells) {
              row.cells.forEach((cell, ci) => {
                if (cell.mode === 'answer' && cell.answerType !== 'dropdown' && responses[ti] && responses[ti][ri] && responses[ti][ri][ci] !== '') {
                  responses[ti][ri][ci] = this.formatNumericDisplay(responses[ti][ri][ci], cell)
                }
              })
            }
          })
        })
      }
      this.studentResponses = responses
      this.responsesReady = true
    },

    tableHasColumnHeaders (table) {
      return table.columns && table.columns.some(col => col.header && col.header.trim() !== '')
    },

    formatAnswerForDisplay (cell) {
      const val = cell.value || ''
      if (!val && val !== 0) return ''
      switch (cell.answerType) {
        case 'dollar': {
          const num = parseFloat(String(val).replace(/[,$\s]/g, ''))
          if (isNaN(num)) return val
          const decimals = cell.dollarRounding === 'cent' ? 2 : 0
          return '$' + num.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })
        }
        case 'percentage': {
          const num = parseFloat(val)
          if (isNaN(num)) return val
          const dp = cell.decimalPlaces !== undefined ? cell.decimalPlaces : 2
          return num.toFixed(dp) + '%'
        }
        case 'ratio': {
          const num = parseFloat(val)
          if (isNaN(num)) return val
          const dp = cell.decimalPlaces !== undefined ? cell.decimalPlaces : 2
          return num.toFixed(dp) + ':1'
        }
        case 'custom': {
          const num = parseFloat(val)
          if (isNaN(num)) return val
          const dp = cell.decimalPlaces !== undefined ? cell.decimalPlaces : 2
          const unit = cell.customUnit ? ` ${cell.customUnit}` : ''
          return num.toFixed(dp) + unit
        }
        case 'dropdown': return val
        default: return val
      }
    },

    formatNumericDisplay (value, cell) {
      if (!value && value !== 0) return ''
      const cleaned = String(value).replace(/[$,\s]/g, '').trim()
      if (cleaned === '' || isNaN(cleaned)) return String(value)
      const num = parseFloat(cleaned)
      if (isNaN(num)) return String(value)
      if (cell.answerType === 'dollar') {
        const decimals = cell.dollarRounding === 'cent' ? 2 : 0
        return num.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })
      }
      const dp = cell.decimalPlaces !== undefined ? cell.decimalPlaces : 2
      return num.toLocaleString('en-US', { minimumFractionDigits: dp, maximumFractionDigits: dp })
    },

    getDropdownOptions (cell) {
      const opts = [{ value: '', text: 'Select...' }]
      if (cell.dropdownOptions && cell.dropdownOptions.length > 0) {
        cell.dropdownOptions.forEach(opt => opts.push({ value: opt, text: opt }))
      }
      return opts
    },

    handleBlur (ti, ri, ci) {
      const cell = this.qtiJson.tables[ti].rows[ri].cells[ci]
      const raw = this.studentResponses[ti][ri][ci]
      if (raw !== '') {
        this.$set(this.studentResponses[ti][ri], ci, this.formatNumericDisplay(raw, cell))
      }
      this.$forceUpdate()
    },

    markEdited (ti, ri, ci) {
      this.$set(this.editedCells, `${ti}-${ri}-${ci}`, true)
    },

    getFieldClass (ti, ri, ci) {
      if (this.editedCells[`${ti}-${ri}-${ci}`]) return ''
      if (!this.parsedGradingResults) return ''
      const tableResult = this.parsedGradingResults[String(ti)]
      if (!tableResult) return ''
      const rowResult = tableResult[String(ri)]
      if (!rowResult) return ''
      const cellResult = rowResult[String(ci)]
      if (!cellResult || typeof cellResult !== 'object' || cellResult.isCorrect === undefined || cellResult.isCorrect === null) return ''
      return cellResult.isCorrect ? 'border-success' : 'border-danger'
    },

    getStudentResponse () {
      const result = {}
      if (!this.qtiJson.tables) return result
      this.qtiJson.tables.forEach((table, ti) => {
        table.rows.forEach((row, ri) => {
          if (row.rowType === 'data' && row.cells) {
            row.cells.forEach((cell, ci) => {
              if (cell.mode === 'answer') {
                if (!result[ti]) result[ti] = {}
                if (!result[ti][ri]) result[ti][ri] = {}
                const raw = this.studentResponses[ti] && this.studentResponses[ti][ri] ? (this.studentResponses[ti][ri][ci] || '') : ''
                result[ti][ri][ci] = cell.answerType !== 'dropdown'
                  ? String(raw).replace(/[$,%\s]/g, '').replace(/:1$/, '').trim()
                  : raw
              }
            })
          }
        })
      })
      return result
    }
  }
}
</script>

<style scoped>
/* ============================================ */
/* SHARED WRAPPER                                */
/* ============================================ */
.mpc-table-wrapper { overflow-x: auto; }

/* ============================================ */
/* GRID TABLE                                    */
/* ============================================ */
.mpc-table {
  border-collapse: collapse;
  border: none;
  width: auto;
}
.mpc-table td, .mpc-table th {
  border: none;
  padding: 0.3rem 0.6rem;
  vertical-align: middle;
}
.mpc-col-header {
  font-weight: 600;
  font-size: 0.9rem;
  padding-bottom: 0.4rem;
  white-space: nowrap;
}
.mpc-rowheader-cell {
  font-weight: 600;
  font-size: 0.85rem;
  color: #343a40;
  background-color: #f1f3f5;
  padding-bottom: 0.25rem !important;
  border-bottom: 1px solid #dee2e6 !important;
}
.mpc-instruction-cell {
  font-style: italic;
  color: #495057;
  padding-top: 0.5rem;
  padding-bottom: 0.2rem;
}
.mpc-display-value { font-size: 0.95rem; }

/* ============================================ */
/* LINE ITEMS (flexbox rows)                     */
/* ============================================ */
.mpc-line-items { width: 100%; }

.mpc-li-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0.25rem 0;
}

/* Label: shrink to content */
.mpc-li-label {
  flex-shrink: 0;
  white-space: nowrap;
  font-size: 0.95rem;
  color: #212529;
}

/* Answer: natural size */
.mpc-li-answer {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

.mpc-li-input-wrapper {
  display: flex;
  align-items: center;
  gap: 3px;
  width: 100%;
}

.mpc-li-input {
  width: 130px;
  flex-shrink: 0;
  text-align: right;
}

.mpc-li-select { width: 200px; flex-shrink: 0; }

.mpc-li-blank { flex-shrink: 0; }

/* ============================================ */
/* ANSWER INPUTS (grid table)                    */
/* ============================================ */
.mpc-answer-input-wrapper {
  display: inline-flex;
  align-items: center;
}
.mpc-numeric-input-group {
  display: inline-flex;
  align-items: center;
  gap: 3px;
}
.mpc-unit-prefix, .mpc-unit-suffix {
  font-size: 0.9rem;
  color: #495057;
  white-space: nowrap;
}
.mpc-answer-input {
  width: 110px;
  text-align: right;
  display: inline-block;
}
.mpc-answer-select { min-width: 130px; }

/* ============================================ */
/* ANSWER DISPLAY (solution view)                */
/* ============================================ */
.mpc-answer-display {
  background-color: #d4edda;
  border: 1px solid #c3e6cb;
  border-radius: 3px;
  padding: 2px 10px;
  min-height: 28px;
  display: inline-block;
  min-width: 80px;
  text-align: center;
  color: #155724;
  font-weight: 600;
  font-size: 0.9rem;
}

/* ============================================ */
/* GRADING FEEDBACK                              */
/* ============================================ */
input.border-success, select.border-success {
  border: 2px solid #0d6832 !important;
  box-shadow: none !important;
}
input.border-danger, select.border-danger {
  border: 2px solid #b02a37 !important;
  box-shadow: none !important;
}
</style>
