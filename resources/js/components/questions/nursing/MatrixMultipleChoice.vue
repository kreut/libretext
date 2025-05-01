<template>
  <div class="pb-3">
    <table class="table table-striped">
      <thead class="nurses-table-header">
      <tr>
        <th v-for="(header,colIndex) in qtiJson.headers" :key="`matrix-multiple-choice-header-${colIndex}`"
            scope="col"
        >
          <ErrorMessage v-if="questionForm.errors.get('headers') &&
          JSON.parse(questionForm.errors.get('headers'))['general'] && colIndex === 0"
                        :message="JSON.parse(questionForm.errors.get('headers'))['general']"
          />
          <b-form-input
            v-if="colIndex === 0"
            v-model="qtiJson.headers[colIndex]"
            type="text"
            placeholder="Column 1"
            @input="clearErrors('headers', colIndex)"
          />
          <b-input-group v-if="colIndex !== 0">
            <b-form-input
              v-model="qtiJson.headers[colIndex]"
              type="text"
              :placeholder="`Column ${colIndex+1}`"
              @input="clearErrors('headers', colIndex)"
            />
            <b-input-group-append>
              <b-input-group-text>
                <b-icon-trash
                  @click="deleteColumn(colIndex)"
                />
              </b-input-group-text>
            </b-input-group-append>
          </b-input-group>
          <ErrorMessage v-if="questionForm.errors.get('headers')
&& JSON.parse(questionForm.errors.get('headers'))['specific'][colIndex]"
                        :message="JSON.parse(questionForm.errors.get('headers'))['specific'][colIndex]"
          />
        </th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(row,rowIndex) in qtiJson.rows" :key="`matrix-multiple-choice-row-${rowIndex}`">
        <td>
          <b-input-group>
            <b-form-input
              v-model="row.label"
              type="text"
              :placeholder="`Row ${rowIndex+1}`"
              @input="clearErrors('rows', rowIndex, 'label')"
            />
            <b-input-group-append>
              <b-input-group-text>
                <b-icon-trash
                  @click="deleteRow(rowIndex)"
                />
              </b-input-group-text>
            </b-input-group-append>
          </b-input-group>
          <ErrorMessage v-if="questionForm.errors.get('rows')
          && JSON.parse(questionForm.errors.get('rows'))['general']
          && rowIndex === 0"
                        :message="JSON.parse(questionForm.errors.get('rows'))['general']"
          />
          <ErrorMessage v-if="questionForm.errors.get('rows')
            && JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]
&& JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]['label']"
                        :message="JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]['label']"
          />
          <ErrorMessage v-if="questionForm.errors.get('rows')
            && JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]
&& JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]['correctResponse']"
                        :message="JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]['correctResponse']"
          />
        </td>
        <td v-for="(column, colIndex) in headersWithoutInitialColumn"
            :key="`matrix-multiple-choice-row-${rowIndex}-${colIndex}`"
        >
          <b-form-radio v-model="row.correctResponse"
                        :name="`Row ${rowIndex}`"
                        :value="colIndex"
                        @input="clearErrors('rows', rowIndex, 'correctResponse')"
          />
        </td>
      </tr>
      </tbody>
    </table>
    <b-button class="primary" size="sm" @click="addRow">
      Add row
    </b-button>
    <b-button class="primary" size="sm" @click="addColumn">
      Add column
    </b-button>
  </div>
</template>

<script>
import ErrorMessage from '~/components/ErrorMessage'

export default {
  name: 'MatrixMultipleChoice',
  components: {
    ErrorMessage
  },
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    },
    questionForm: {
      type: Object,
      default: () => {
      }
    }
  },
  computed: {
    headersWithoutInitialColumn () {
      let headers = this.qtiJson.headers
      return headers.slice(1)
    }
  },
  methods: {
    clearErrors (key, index, type) {
      let errors = this.questionForm.errors.get(key)
      errors = JSON.parse(errors)
      switch (key) {
        case ('headers'):
          delete errors['specific'][index]
          break
        case ('rows'):
          console.error(errors)
          delete errors['specific'][index][type]
          break
        default:
          alert(`The error key ${key} does not exist.`)
      }
      delete errors.general
      this.questionForm.errors.set(key, JSON.stringify(errors))
    },
    deleteColumn (colIndex) {
      if (this.qtiJson.headers.length === 2) {
        this.$noty.info('You need at least one column in the matrix.')
        return false
      }
      let indexToCompare = colIndex - 1
      for (let i = 0; i < this.qtiJson.rows.length; i++) {
        let row = this.qtiJson.rows[i]
        if (row.correctResponse === indexToCompare) {
          row.correctResponse = ''
        }

        if (row.correctResponse > indexToCompare) {
          row.correctResponse = row.correctResponse - 1
        }
      }
      this.qtiJson.headers.splice(colIndex, 1)
    },
    deleteRow (rowIndex) {
      if (this.qtiJson.rows.length === 1) {
        this.$noty.info('You need at least one row in the matrix.')
        return false
      }
      this.qtiJson.rows.splice(rowIndex, 1)
    },
    addColumn () {
      this.qtiJson.headers.push('')
    },
    addRow () {
      this.qtiJson.rows.push({ label: '', correctResponse: '' })
      this.$forceUpdate()
    }
  }
}
</script>
