<template>
  <div class="pb-2">
    <!-- Entry Instructions - Verbal Information -->
    <div v-if="qtiJson.entries && qtiJson.entries.length > 0" class="instructions-section pb-4 mb-4">
      <h5 class="mb-3">Journal Entry Descriptions:</h5>
      <div v-for="(entry, entryIndex) in qtiJson.entries" :key="`entry-instruction-${entryIndex}`" class="pb-2">
        <strong>{{ entry.entryText }}:</strong> {{ entry.entryDescription }}
      </div>
    </div>

    <hr class="section-divider">

    <!-- Student Work Area - Single Table for All Entries -->
    <div class="student-work-section">
      <h5 class="mb-3">Complete the Journal Entries:</h5>

      <table class="table table-bordered journal-entry-table">
        <thead class="table-header">
        <tr>
          <th scope="col" style="width: 20%">Entry</th>
          <th scope="col" style="width: 35%">Account Title</th>
          <th scope="col" style="width: 22.5%">Debit</th>
          <th scope="col" style="width: 22.5%">Credit</th>
        </tr>
        </thead>
        <tbody>
        <template v-for="(entry, entryIndex) in studentEntries">
          <tr v-for="(row, rowIndex) in entry.rows"
              :key="`entry-${entryIndex}-row-${rowIndex}`"
              :class="{'entry-divider': rowIndex === 0 && entryIndex > 0}"
          >
            <!-- Entry selection dropdown only on first row of each entry -->
            <td v-if="rowIndex === 0"
                :rowspan="entry.rows.length"
                class="entry-cell"
            >
              <b-form-select
                v-model="entry.selectedEntryIndex"
                :options="getEntryOptionsFor(entryIndex)"
                size="sm"
                :class="[getEntryCellClass(entryIndex), {'is-incomplete': isIncomplete(entryIndex, null, 'entry')}]"
                @change="clearEntryColor(entryIndex)"
              />
            </td>

            <td>
              <b-form-input
                v-model="row.accountTitle"
                type="text"
                list="account-titles-list"
                placeholder="Start typing account title..."
                autocomplete="off"
                size="sm"
                :class="[getFieldClass(entryIndex, rowIndex, 'accountTitle'), {'account-indent': isCreditRow(entryIndex, rowIndex)}, {'is-incomplete': isIncomplete(entryIndex, rowIndex, 'accountTitle')}]"
                @input="clearFieldColor(entryIndex, rowIndex, 'accountTitle')"
              />
              <datalist id="account-titles-list">
                <option v-for="account in accountTitles" :key="account" :value="account"/>
              </datalist>
            </td>
            <td>
              <b-form-input
                v-model="row.debit"
                type="text"
                inputmode="decimal"
                placeholder=""
                size="sm"
                class="amount-input"
                :class="[getFieldClass(entryIndex, rowIndex, 'debit'), {'is-incomplete': isIncomplete(entryIndex, rowIndex, 'debit')}]"
                @input="onAmountInput(entryIndex, rowIndex, 'debit')"
              />
            </td>
            <td>
              <b-form-input
                v-model="row.credit"
                type="text"
                inputmode="decimal"
                placeholder=""
                size="sm"
                class="amount-input"
                :class="[getFieldClass(entryIndex, rowIndex, 'credit'), {'is-incomplete': isIncomplete(entryIndex, rowIndex, 'credit')}]"
                @input="onAmountInput(entryIndex, rowIndex, 'credit')"
              />
            </td>
          </tr>
        </template>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AccountingJournalEntryViewer',
  props: {
    qtiJson: {
      type: Object,
      default: () => ({})
    },
    studentResponse: {
      type: [Array, String],
      default: null
    }
  },
  data () {
    return {
      studentEntries: [],
      hasStartedEditing: false, // Track if user has started editing - clears all grading colors
      accountTitles: [],
      indentTracker: 0 // Reactivity trigger for indent recalculation
    }
  },
  computed: {
    entryOptions () {
      const options = [{ value: null, text: 'Select an entry...' }]
      if (this.qtiJson.entries) {
        this.qtiJson.entries.forEach((entry, index) => {
          options.push({
            value: index,
            text: entry.entryText
          })
        })
      }
      return options
    },
    parsedGradingResults () {
      // Parse studentResponse from qtiJson if it's a string
      let response = this.qtiJson.studentResponse
      if (typeof response === 'string') {
        try {
          response = JSON.parse(response)
        } catch (error) {
          return null
        }
      }
      return response
    },
    showValidationWarning () {
      // Check if any required fields are missing - updates in real-time as user types
      if (!this.studentEntries || this.studentEntries.length === 0) {
        return false
      }

      for (const entry of this.studentEntries) {
        // Check entry selection
        if (entry.selectedEntryIndex === null) {
          return true
        }
        // Check each row
        for (const row of entry.rows) {
          // Check account title
          if (!row.accountTitle || row.accountTitle.trim() === '') {
            return true
          }
          // Check that at least one of debit or credit has a value
          if ((!row.debit || row.debit === '') && (!row.credit || row.credit === '')) {
            return true
          }
        }
      }
      return false
    },
    isComplete () {
      return !this.showValidationWarning
    }
  },
  mounted () {
    this.getAccountTitles()
    this.initializeStudentEntries()
    this.loadStudentResponse()
  },
  methods: {
    async getAccountTitles () {
      try {
        const { data } = await axios.get('/api/questions/valid-accounting-journal-entries')
        this.accountTitles = data
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getEntryOptionsFor (entryIndex) {
      // Get all selected entry indices EXCEPT the current row's selection
      const selectedByOthers = this.studentEntries
        .map((entry, idx) => idx === entryIndex ? null : entry.selectedEntryIndex)
        .filter(val => val !== null)

      const options = [{ value: null, text: 'Select an entry...' }]

      if (this.qtiJson.entries) {
        this.qtiJson.entries.forEach((entry, index) => {
          // Include if not selected by another row
          if (!selectedByOthers.includes(index)) {
            options.push({
              value: index,
              text: entry.entryText
            })
          }
        })
      }

      return options
    },
    initializeStudentEntries () {
      if (!this.qtiJson.entries) return

      this.studentEntries = this.qtiJson.entries.map((entry, entryIndex) => {
        const numRows = entry.solutionRows ? entry.solutionRows.length : 2
        return {
          selectedEntryIndex: null,
          rows: Array(numRows).fill(null).map(() => ({
            accountTitle: '',
            debit: '',
            credit: ''
          }))
        }
      })
    },
    loadStudentResponse () {
      // Use studentResponse prop if provided, otherwise fall back to qtiJson.studentResponse
      let response = this.studentResponse || this.qtiJson.studentResponse

      // Parse if it's a JSON string
      if (typeof response === 'string') {
        try {
          response = JSON.parse(response)
        } catch (error) {
          console.error('Error parsing studentResponse:', error)
          return
        }
      }

      if (response && Array.isArray(response)) {
        response.forEach((responseEntry, entryIndex) => {
          if (this.studentEntries[entryIndex]) {
            // Load selected entry index
            this.studentEntries[entryIndex].selectedEntryIndex = responseEntry.selectedEntryIndex ?? null

            // Load row data
            if (responseEntry.rows) {
              responseEntry.rows.forEach((row, rowIndex) => {
                if (this.studentEntries[entryIndex].rows[rowIndex]) {
                  this.studentEntries[entryIndex].rows[rowIndex] = {
                    accountTitle: row.accountTitle || '',
                    debit: row.debit || '',
                    credit: row.credit || ''
                  }
                }
              })
            }
          }
        })
      }
    },
    isCreditRow (entryIndex, rowIndex) {
      // Depend on indentTracker for reactivity
      // eslint-disable-next-line no-unused-expressions
      this.indentTracker
      const row = this.studentEntries[entryIndex]?.rows[rowIndex]
      if (!row) return false
      return row.credit && row.credit.trim() !== ''
    },
    onAmountInput (entryIndex, rowIndex, field) {
      this.clearFieldColor(entryIndex, rowIndex, field)
      // Bump the tracker to force indent class recalculation
      this.indentTracker++
    },
    clearEntryColor (entryIndex) {
      // Mark that user has started editing - clear all grading colors
      this.hasStartedEditing = true
    },
    clearFieldColor (entryIndex, rowIndex, field) {
      // Mark that user has started editing - clear all grading colors
      this.hasStartedEditing = true
    },
    isIncomplete (entryIndex, rowIndex, field) {
      // Don't show incomplete styling if we have grading results
      if (this.parsedGradingResults && this.parsedGradingResults.length > 0) {
        return false
      }

      // Only show incomplete styling if warning is showing
      if (!this.showValidationWarning) {
        return false
      }

      const entry = this.studentEntries[entryIndex]
      if (!entry) return false

      if (field === 'entry') {
        return entry.selectedEntryIndex === null
      }

      const row = entry.rows[rowIndex]
      if (!row) return false

      if (field === 'accountTitle') {
        return !row.accountTitle || row.accountTitle.trim() === ''
      }

      if (field === 'debit' || field === 'credit') {
        // Both debit and credit are incomplete if neither has a value
        return (!row.debit || row.debit === '') && (!row.credit || row.credit === '')
      }

      return false
    },
    getStudentResponse () {
      // Return entries if complete, null if not
      if (this.showValidationWarning) {
        return null
      }
      return this.studentEntries
    },
    getEntryCellClass (entryIndex) {
      // If user has started editing, clear all grading colors
      if (this.hasStartedEditing) {
        return ''
      }

      // Only apply color coding if we have grading results
      if (!this.parsedGradingResults ||
        !this.parsedGradingResults[entryIndex] ||
        this.parsedGradingResults[entryIndex].selectedEntryCorrect === undefined) {
        return ''
      }

      return this.parsedGradingResults[entryIndex].selectedEntryCorrect
        ? 'border-success'
        : 'border-danger'
    },
    getFieldClass (entryIndex, rowIndex, field) {
      // If user has started editing, clear all grading colors
      if (this.hasStartedEditing) {
        return ''
      }

      // Only apply color coding if we have grading results
      if (!this.parsedGradingResults ||
        !this.parsedGradingResults[entryIndex] ||
        !this.parsedGradingResults[entryIndex].rows ||
        !this.parsedGradingResults[entryIndex].rows[rowIndex]) {
        return ''
      }

      const row = this.parsedGradingResults[entryIndex].rows[rowIndex]
      const fieldKey = field + 'Correct'

      // Check if this field has been graded
      if (row[fieldKey] === undefined) {
        return ''
      }

      return row[fieldKey] ? 'border-success' : 'border-danger'
    }
  }
}
</script>

<style scoped>
.instructions-section {
  background-color: #f8f9fa;
  padding: 1.5rem;
  border-radius: 0.25rem;
}

.section-divider {
  border: none;
  border-top: 3px solid #dee2e6;
  margin: 2rem 0;
}

.student-work-section {
  padding-top: 1rem;
}

.table-header {
  background-color: #f8f9fa;
  font-weight: 600;
}

.journal-entry-table {
  margin-bottom: 0;
}

.entry-divider {
  border-top: 2px solid #6c757d !important;
}

.entry-cell {
  vertical-align: top !important;
  padding-top: 0.5rem !important;
}

.account-indent {
  padding-left: 2rem !important;
}

/* Right-align debit/credit inputs via class */
.amount-input {
  text-align: right;
}

/* Apply thin border colors directly to form controls only */
/* Using darker colors for WCAG AA accessibility */
select.border-success,
input.border-success {
  border: 2px solid #0d6832 !important;
  box-shadow: none !important;
}

select.border-danger,
input.border-danger {
  border: 2px solid #b02a37 !important;
  box-shadow: none !important;
}

/* Incomplete field styling - darker yellow/gold for accessibility */
select.is-incomplete,
input.is-incomplete {
  border: 2px solid #997404 !important;
  background-color: #fff9e6 !important;
}
</style>
