<template>
  <div>
    <!-- Multiple Entries Section -->
    <div class="pb-3">
      <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Journal Entries</h2>">
        <b-card-text>
          <div v-for="(entry, entryIndex) in qtiJson.entries" :key="`entry-${entryIndex}`" class="pb-4">
            <b-card>
              <template #header>
                <div class="d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center flex-grow-1">
                    <b-button
                      v-b-toggle="`entry-collapse-${entryIndex}`"
                      variant="link"
                      class="p-0 mr-2"
                      @click="handleCollapseToggle(entryIndex)"
                    >
                      <b-icon-chevron-down class="when-open" />
                      <b-icon-chevron-right class="when-closed" />
                    </b-button>
                    <div class="flex-grow-1">
                      <div><strong>Entry {{ entryIndex + 1 }}</strong></div>
                      <div v-if="entry.entryText || entry.entryDescription" class="text-muted small">
                        <span v-if="entry.entryText">{{ entry.entryText }}</span>
                        <span v-if="entry.entryText && entry.entryDescription"> - </span>
                        <span v-if="entry.entryDescription" style="max-width: 500px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; vertical-align: bottom;">
                          {{ entry.entryDescription }}
                        </span>
                      </div>
                      <div v-show="hasBeenCollapsed[entryIndex] && getEntryErrors(entryIndex).length > 0" class="collapsed-errors">
                        <small class="text-danger">{{ getEntryErrors(entryIndex).join(', ') }}</small>
                      </div>
                    </div>
                  </div>
                  <b-button
                    variant="outline-danger"
                    size="sm"
                    @click="removeEntry(entryIndex)"
                  >
                    <b-icon-trash /> Remove Entry
                  </b-button>
                </div>
              </template>

              <b-collapse :id="`entry-collapse-${entryIndex}`" visible>
                <!-- Entry Text -->
                <div class="pb-3">
                  <label><strong>Entry Text (Date/Number/Letter):</strong></label>
                  <b-form-input
                    v-model="entry.entryText"
                    type="text"
                    placeholder="e.g., January 1, 2024 or Entry #1"
                    @input="clearErrors('entries', entryIndex, 'entryText'); handleInput()"
                  />
                  <ErrorMessage
                    v-if="errorKey && questionForm.errors.get(errorKey)
                      && JSON.parse(questionForm.errors.get(errorKey))['specific']
                      && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]
                      && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['entryText']"
                    :message="JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['entryText']"
                  />
                </div>

                <!-- Entry Description -->
                <div class="pb-3">
                  <label><strong>Entry Description:</strong></label>
                  <b-form-textarea
                    v-model="entry.entryDescription"
                    placeholder="Describe the transaction that occurred..."
                    rows="3"
                    @input="clearErrors('entries', entryIndex, 'entryDescription'); handleInput()"
                  />
                  <ErrorMessage
                    v-if="errorKey && questionForm.errors.get(errorKey)
                      && JSON.parse(questionForm.errors.get(errorKey))['specific']
                      && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]
                      && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['entryDescription']"
                    :message="JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['entryDescription']"
                  />
                </div>

                <!-- Solution Table -->
                <div class="pb-3">
                  <label><strong>Solution (2-5 rows):</strong></label>
                  <table class="table table-striped">
                    <thead class="nurses-table-header">
                    <tr>
                      <th scope="col" style="width: 50%">Account Title</th>
                      <th scope="col" style="width: 20%">Type</th>
                      <th scope="col" style="width: 20%">Amount</th>
                      <th scope="col" style="width: 10%">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(row, rowIndex) in (entry.solutionRows || [])" :key="`entry-${entryIndex}-row-${rowIndex}`">
                      <td>
                        <b-form-input
                          v-model="row.accountTitle"
                          type="text"
                          list="account-titles-list"
                          placeholder="Start typing account name..."
                          autocomplete="off"
                          @input="clearErrors('entries', entryIndex, rowIndex, 'accountTitle'); handleInput()"
                        />
                        <datalist id="account-titles-list">
                          <option v-for="account in accountTitles" :key="account" :value="account" />
                        </datalist>
                        <ErrorMessage
                          v-if="errorKey && questionForm.errors.get(errorKey)
                              && JSON.parse(questionForm.errors.get(errorKey))['specific']
                              && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]
                              && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows']
                              && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows'][rowIndex]
                              && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows'][rowIndex]['accountTitle']"
                          :message="JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows'][rowIndex]['accountTitle']"
                        />
                      </td>
                      <td>
                        <b-form-select
                          v-model="row.type"
                          :options="typeOptions"
                          @change="clearErrors('entries', entryIndex, rowIndex, 'type'); handleInput()"
                        />
                        <ErrorMessage
                          v-if="errorKey && questionForm.errors.get(errorKey)
                              && JSON.parse(questionForm.errors.get(errorKey))['specific']
                              && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]
                              && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows']
                              && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows'][rowIndex]
                              && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows'][rowIndex]['type']"
                          :message="JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows'][rowIndex]['type']"
                        />
                      </td>
                      <td>
                        <b-form-input
                          v-model="row.amount"
                          type="text"
                          inputmode="decimal"
                          placeholder="0.00"
                          @input="clearErrors('entries', entryIndex, rowIndex, 'amount'); handleInput()"
                        />
                        <ErrorMessage
                          v-if="errorKey && questionForm.errors.get(errorKey)
                              && JSON.parse(questionForm.errors.get(errorKey))['specific']
                              && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]
                              && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows']
                              && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows'][rowIndex]
                              && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows'][rowIndex]['amount']"
                          :message="JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows'][rowIndex]['amount']"
                        />
                      </td>
                      <td class="text-center">
                        <b-button
                          variant="outline-danger"
                          size="sm"
                          @click="deleteRow(entryIndex, rowIndex)"
                        >
                          <b-icon-trash />
                        </b-button>
                      </td>
                    </tr>
                    </tbody>
                  </table>

                  <ErrorMessage
                    v-if="errorKey && questionForm.errors.get(errorKey)
                      && JSON.parse(questionForm.errors.get(errorKey))['specific']
                      && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]
                      && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows']
                      && JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows']['general']"
                    class="pb-2"
                    :message="JSON.parse(questionForm.errors.get(errorKey))['specific'][entryIndex]['solutionRows']['general']"
                  />

                  <div class="d-flex justify-content-between align-items-center">
                    <b-button
                      v-if="!entry.solutionRows || entry.solutionRows.length < 5"
                      class="primary"
                      size="sm"
                      @click="addRow(entryIndex)"
                    >
                      Add Row
                    </b-button>
                    <div v-if="getBalanceInfo(entryIndex).isBalanced !== null" class="ml-auto">
                      <b-alert
                        :variant="getBalanceInfo(entryIndex).isBalanced ? 'success' : 'warning'"
                        show
                        class="mb-0 d-inline-block"
                      >
                        <strong>Total Debits:</strong> ${{ formatAmount(getBalanceInfo(entryIndex).totalDebits) }} |
                        <strong>Total Credits:</strong> ${{ formatAmount(getBalanceInfo(entryIndex).totalCredits) }}
                        <span v-if="!getBalanceInfo(entryIndex).isBalanced" class="ml-2">
                          ⚠️ Entry does not balance
                        </span>
                        <span v-else class="ml-2">
                          ✓ Entry balances
                        </span>
                      </b-alert>
                    </div>
                  </div>
                </div>
              </b-collapse>
            </b-card>
          </div>

          <ErrorMessage
            v-if="errorKey && questionForm.errors.get(errorKey)
              && JSON.parse(questionForm.errors.get(errorKey))['general']"
            class="pb-2"
            :message="JSON.parse(questionForm.errors.get(errorKey))['general']"
          />

          <div class="d-flex justify-content-between">
            <b-button class="primary" size="sm" @click="addEntry">
              Add Entry
            </b-button>
            <div>
              <b-button variant="outline-secondary" size="sm" class="mr-2" @click="expandAll">
                Expand All
              </b-button>
              <b-button variant="outline-secondary" size="sm" @click="collapseAll">
                Collapse All
              </b-button>
            </div>
          </div>
        </b-card-text>
      </b-card>
    </div>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'
import ErrorMessage from '~/components/ErrorMessage'

export default {
  name: 'JournalEntry',
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
      typeOptions: [
        { value: null, text: 'Select Type' },
        { value: 'debit', text: 'Debit' },
        { value: 'credit', text: 'Credit' }
      ],
      collapsedStates: {},
      hasBeenCollapsed: {}, // Track which entries have been collapsed at least once
      accountTitles: [
        'Accounts Payable',
        'Accounts Receivable',
        'Accrued Pension Liability',
        'Accumulated Depreciation-Buildings',
        'Accumulated Depreciation-Equipment',
        'Accumulated Depreciation- Plant Assets',
        'Accumulated Other Comprehensive Income',
        'Accumulated Other Comprehensive Loss',
        'Additional Paid-in Capital, Common Stock',
        'Additional Paid-in Capital, Preferred Stock',
        'Administrative Expenses',
        'Advertising Expense',
        'Allowance for Doubtful Accounts',
        'Amortization Expense',
        'Bad Debt Expense',
        'Bank Charges Expense',
        'Bonds Payable',
        'Buildings',
        'Cash',
        'Common Stock',
        'Common Stock Dividends Distributable',
        'Copyrights',
        'Cost of Goods Sold',
        'Current Portion of Long-Term Debt',
        'Deferred Revenue',
        'Delivery Expense',
        'Depreciation Expense',
        'Discount on Bonds Payable',
        'Dividends',
        'Dividends Payable',
        'Entertainment Expense',
        'Equipment',
        'Federal Income Taxes Payable',
        'Federal Unemployment Taxes Payable',
        'FICA Taxes Payable',
        'Franchise',
        'Freight-In',
        'Freight-Out',
        'Gain on Bond Redemption',
        'Gain on Disposal of Plant Assets',
        'Gain on Sale of Investments',
        'Goodwill',
        'Impairment Loss',
        'Income Summary',
        'Income Tax Expense',
        'Income Taxes Payable',
        'Insurance Expense',
        'Intangible Assets',
        'Interest Expense',
        'Interest Income',
        'Interest Payable',
        'Interest Receivable',
        'Interest Revenue',
        'Inventory',
        'Land',
        'Land Improvements',
        'Loss on Disposal of Plant Assets',
        'Loss on Sale of Equipment',
        'Maintenance and Repairs Expense',
        'Miscellaneous Expense',
        'Mortgage Payable',
        'No Entry',
        'Notes Payable',
        'Notes Receivable',
        'Operating Expenses',
        'Other Operating Expenses',
        'Other Receivables',
        'Patents',
        'Payroll Tax Expense',
        'Petty Cash',
        'Plant Assets',
        'Postage Expense',
        'Preferred Stock',
        'Premium on Bonds Payable',
        'Prepaid Advertising',
        'Prepaid Expenses',
        'Prepaid Insurance',
        'Prepaid Rent',
        'Property Tax Expense',
        'Property Taxes Payable',
        'Purchase Discounts',
        'Purchase Returns and Allowances',
        'Purchases',
        'Rent Expense',
        'Rent Revenue',
        'Repairs Expense',
        'Research and Development Expense',
        'Retained Earnings',
        'Salaries and Wages Expense',
        'Salaries and Wages Payable',
        'Sales Discounts',
        'Sales Returns and Allowances',
        'Sales Revenue',
        'Sales Taxes Payable',
        'Selling Expense',
        'Service Charge Expense',
        'Service Revenue',
        'State Income Taxes Payable',
        'State Unemployment Taxes Payable',
        'Stock Dividends',
        'Supplies',
        'Supplies Expense',
        'Travel Expense',
        'Treasury Stock',
        'Unearned Rent Revenue',
        'Unearned Sales Revenue',
        'Unearned Service Revenue',
        'Union Dues Payable',
        'Utilities Expense',
        'Warranty Liability'
      ]
    }
  },
  computed: {
    errorKey () {
      // Return whichever key has the errors
      if (this.questionForm && this.questionForm.errors) {
        if (this.questionForm.errors.get('entries')) {
          return 'entries'
        }
        if (this.questionForm.errors.get('qti_json')) {
          return 'qti_json'
        }
      }
      return null
    }
  },
  mounted () {
    // Initialize qtiJson with default values if not provided
    if (!this.qtiJson.entries || this.qtiJson.entries.length === 0) {
      this.$set(this.qtiJson, 'entries', [
        {
          identifier: uuidv4(),
          entryText: '',
          entryDescription: '',
          solutionRows: [
            { identifier: uuidv4(), accountTitle: '', type: null, amount: '' },
            { identifier: uuidv4(), accountTitle: '', type: null, amount: '' }
          ]
        }
      ])
    }

    // Check for errors and expand entries that have errors
    this.$nextTick(() => {
      this.expandEntriesWithErrors()
    })
  },
  methods: {
    clearErrors (key, entryIndex = null, rowIndexOrField = null, field = null) {
      if (!this.questionForm || !this.questionForm.errors || !this.questionForm.errors.get) {
        return
      }

      // Map key - if it's 'entries', also check 'qti_json'
      const errorKey = key === 'entries'
        ? (this.questionForm.errors.get(errorKey) ? 'entries' : 'qti_json')
        : key

      try {
        const errors = this.questionForm.errors.get(errorKey)
        if (!errors) return

        const parsedErrors = JSON.parse(errors)

        if (errorKey === 'entries' || errorKey === 'qti_json') {
          if (entryIndex !== null) {
            if (!parsedErrors.specific || !parsedErrors.specific[entryIndex]) return

            // If we have rowIndex and field, we're clearing a solution row error
            if (typeof rowIndexOrField === 'number' && field) {
              const rowIndex = rowIndexOrField
              if (parsedErrors.specific[entryIndex].solutionRows &&
                parsedErrors.specific[entryIndex].solutionRows[rowIndex]) {
                delete parsedErrors.specific[entryIndex].solutionRows[rowIndex][field]

                // Clean up empty objects
                if (Object.keys(parsedErrors.specific[entryIndex].solutionRows[rowIndex]).length === 0) {
                  delete parsedErrors.specific[entryIndex].solutionRows[rowIndex]
                }
                if (parsedErrors.specific[entryIndex].solutionRows &&
                  Object.keys(parsedErrors.specific[entryIndex].solutionRows).length === 0) {
                  delete parsedErrors.specific[entryIndex].solutionRows
                }
              }
            } else {
              // Clearing entry-level field like entryText or entryDescription
              const fieldName = rowIndexOrField
              if (parsedErrors.specific[entryIndex][fieldName]) {
                delete parsedErrors.specific[entryIndex][fieldName]
              }
            }

            // Clean up empty objects
            if (Object.keys(parsedErrors.specific[entryIndex]).length === 0) {
              delete parsedErrors.specific[entryIndex]
            }
            if (parsedErrors.specific && Object.keys(parsedErrors.specific).length === 0) {
              delete parsedErrors.specific
            }
          }
        }

        this.questionForm.errors.set(errorKey, JSON.stringify(parsedErrors))
        this.$forceUpdate() // Force update to refresh error display in collapsed headers
      } catch (error) {
        console.error('Error clearing errors:', error)
      }
    },
    handleInput () {
      this.$emit('update-qti-json', 'entries', this.qtiJson.entries)
    },
    expandEntriesWithErrors () {
      if (!this.questionForm || !this.questionForm.errors || !this.questionForm.errors.get) {
        return
      }

      try {
        // Check for errors under 'entries' or 'qti_json'
        let entriesErrors = this.questionForm.errors.get('entries')
        if (!entriesErrors) {
          entriesErrors = this.questionForm.errors.get('qti_json')
        }

        if (!entriesErrors) return

        const parsedErrors = JSON.parse(entriesErrors)
        if (!parsedErrors.specific) return

        // For each entry with errors, expand it and mark it as having been collapsed
        Object.keys(parsedErrors.specific).forEach(entryIndex => {
          const index = parseInt(entryIndex)
          // Mark as having been collapsed so errors will show
          this.$set(this.hasBeenCollapsed, index, true)
          // Expand the entry
          this.$root.$emit('bv::toggle::collapse', `entry-collapse-${index}`)
        })
      } catch (error) {
        console.error('Error expanding entries with errors:', error)
      }
    },
    handleCollapseToggle (entryIndex) {
      // Mark this entry as having been collapsed at least once
      this.$set(this.hasBeenCollapsed, entryIndex, true)
    },
    getEntryErrors (entryIndex) {
      const errors = []

      const entry = this.qtiJson.entries[entryIndex]
      if (!entry) return errors

      // Check for entry-level errors
      if (!entry.entryText || entry.entryText.trim() === '') {
        errors.push('Missing entry text')
      }
      if (!entry.entryDescription || entry.entryDescription.trim() === '') {
        errors.push('Missing description')
      }

      // Check for solution row errors
      if (entry.solutionRows && entry.solutionRows.length > 0) {
        let rowErrorCount = 0

        entry.solutionRows.forEach((row, rowIndex) => {
          let rowHasError = false

          if (!row.accountTitle || row.accountTitle.trim() === '') {
            rowHasError = true
          }
          if (!row.type) {
            rowHasError = true
          }
          if (!row.amount || row.amount === '' || parseFloat(row.amount) <= 0) {
            rowHasError = true
          }

          if (rowHasError) {
            rowErrorCount++
          }
        })

        if (rowErrorCount > 0) {
          errors.push(`${rowErrorCount} row${rowErrorCount > 1 ? 's have' : ' has'} missing fields`)
        }

        // Check if debits and credits balance
        const balanceInfo = this.getBalanceInfo(entryIndex)
        if (balanceInfo.isBalanced === false) {
          errors.push('Entry does not balance')
        }
      } else {
        errors.push('Missing solution rows')
      }

      return errors
    },
    getBalanceInfo (entryIndex) {
      let totalDebits = 0
      let totalCredits = 0
      let hasAnyValues = false

      const entry = this.qtiJson.entries[entryIndex]
      if (!entry || !entry.solutionRows || !Array.isArray(entry.solutionRows)) {
        return {
          totalDebits: 0,
          totalCredits: 0,
          isBalanced: null
        }
      }

      entry.solutionRows.forEach((row) => {
        const amount = parseFloat(row.amount) || 0

        if (amount > 0 && row.type) {
          hasAnyValues = true
          if (row.type === 'debit') {
            totalDebits += amount
          } else if (row.type === 'credit') {
            totalCredits += amount
          }
        }
      })

      return {
        totalDebits,
        totalCredits,
        isBalanced: hasAnyValues ? Math.abs(totalDebits - totalCredits) < 0.01 : null
      }
    },
    addEntry () {
      if (!this.qtiJson.entries) {
        this.$set(this.qtiJson, 'entries', [])
      }

      this.qtiJson.entries.push({
        identifier: uuidv4(),
        entryText: '',
        entryDescription: '',
        solutionRows: [
          { identifier: uuidv4(), accountTitle: '', type: null, amount: '' },
          { identifier: uuidv4(), accountTitle: '', type: null, amount: '' }
        ]
      })
      this.$emit('update-qti-json', 'entries', this.qtiJson.entries)
      this.$forceUpdate()
    },
    removeEntry (entryIndex) {
      if (this.qtiJson.entries.length === 1) {
        this.$noty.info('You need at least one entry.')
        return
      }
      this.qtiJson.entries.splice(entryIndex, 1)
      this.$emit('update-qti-json', 'entries', this.qtiJson.entries)
    },
    addRow (entryIndex) {
      const entry = this.qtiJson.entries[entryIndex]

      if (!entry.solutionRows) {
        this.$set(entry, 'solutionRows', [])
      }

      if (entry.solutionRows.length < 5) {
        entry.solutionRows.push({
          identifier: uuidv4(),
          accountTitle: '',
          type: null,
          amount: ''
        })
        this.$emit('update-qti-json', 'entries', this.qtiJson.entries)
        this.$forceUpdate()
      } else {
        this.$noty.info('Maximum of 5 rows allowed.')
      }
    },
    deleteRow (entryIndex, rowIndex) {
      const entry = this.qtiJson.entries[entryIndex]

      if (!entry.solutionRows) {
        return
      }

      if (entry.solutionRows.length <= 2) {
        this.$noty.info('You need at least two rows for a journal entry.')
        return
      }

      entry.solutionRows.splice(rowIndex, 1)
      this.$emit('update-qti-json', 'entries', this.qtiJson.entries)
    },
    expandAll () {
      this.qtiJson.entries.forEach((entry, index) => {
        this.$root.$emit('bv::toggle::collapse', `entry-collapse-${index}`)
      })
    },
    collapseAll () {
      this.qtiJson.entries.forEach((entry, index) => {
        this.$set(this.hasBeenCollapsed, index, true)
        this.$root.$emit('bv::toggle::collapse', `entry-collapse-${index}`)
      })
    },
    formatAmount (amount) {
      return amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
    }
  }
}
</script>

<style scoped>
.nurses-table-header {
  background-color: #f8f9fa;
}

.table {
  margin-bottom: 1rem;
}

input[type="number"]:disabled {
  background-color: #e9ecef;
  cursor: not-allowed;
}

.collapsed > .when-open,
.not-collapsed > .when-closed {
  display: none;
}

.collapsed-errors {
  margin-top: 0.25rem;
}
</style>
