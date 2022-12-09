<template>
  <div class="pb-3">
    <table class="table table-striped">
      <thead class="nurses-table-header">
      <tr>
        <th v-for="(header,colIndex) in qtiJson.colHeaders" :key="`matrix-multiple-response-header-${colIndex}`"
            scope="col"
        >
          <b-form-input
            v-if="colIndex === 0"
            v-model="qtiJson.colHeaders[colIndex]"
            type="text"
            placeholder="Column 1"
          />
          <b-input-group v-if="colIndex !== 0">
            <b-form-input
              v-model="qtiJson.colHeaders[colIndex]"
              type="text"
              :placeholder="`Column ${colIndex+1}`"
            />
            <b-input-group-append>
              <b-input-group-text>
                <b-icon-trash
                  @click="deleteColumn(colIndex)"
                />
              </b-input-group-text>
            </b-input-group-append>
          </b-input-group>
          <ErrorMessage v-if="questionForm.errors.get('colHeaders')"
                        :message="JSON.parse(questionForm.errors.get('colHeaders'))['specific'][colIndex]"
          />
        </th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(row,rowIndex) in qtiJson.rows" :key="`matrix-multiple-response-row-${rowIndex}`">
        <td>
          <b-input-group>
            <b-form-input
              v-model="qtiJson.rows[rowIndex].header"
              type="text"
              :placeholder="`Row ${rowIndex+1}`"
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
                            && JSON.parse(questionForm.errors.get('rows'))[rowIndex]"
                        :message="JSON.parse(questionForm.errors.get('rows'))[rowIndex]['header']"
          />
          <ErrorMessage v-if="questionForm.errors.get('rows')
                            && JSON.parse(questionForm.errors.get('rows'))[rowIndex]"
                        :message="JSON.parse(questionForm.errors.get('rows'))[rowIndex]['at_least_one_marked_correct']"
          />
        </td>
        <td v-for="(response,responseIndex) in row.responses"
            :key="`matrix-multiple-response-column-${rowIndex}-${responseIndex}`"
        >
          <b-form-checkbox
            v-model="response.correctResponse"
            :value="true"
            :unchecked-value="false"
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
import { v4 as uuidv4 } from 'uuid'

export default {
  name: 'MatrixMultipleResponse',
  components: { ErrorMessage },
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
  methods: {
    deleteColumn (colIndex) {
      if (this.qtiJson.colHeaders.length === 2) {
        this.$noty.info('You need at least one column in the matrix.')
        return false
      }
      this.qtiJson.colHeaders.splice(colIndex, 1)
      for (let i = 0; i < this.qtiJson.rows.length; i++) {
        this.qtiJson.rows[i].responses.splice(colIndex - 1, 1)
      }
    },
    deleteRow (rowIndex) {
      if (this.qtiJson.rows.length === 1) {
        this.$noty.info('You need at least one row in the matrix.')
        return false
      }
      this.qtiJson.rows.splice(rowIndex, 1)
    },
    addColumn () {
      this.qtiJson.colHeaders.push('')
      for (let i = 0; i < this.qtiJson.rows.length; i++) {
        this.qtiJson.rows[i].responses.push({ identifier: uuidv4(), correctResponse: false })
      }
    },
    addRow () {
      let newRow = { header: '', responses: [] }
      for (let i = 0; i < this.qtiJson.colHeaders.length - 1; i++) {
        newRow.responses.push({ identifier: uuidv4(), correctResponse: false })
      }
      this.qtiJson.rows.push(newRow)
    }
  }
}
</script>
