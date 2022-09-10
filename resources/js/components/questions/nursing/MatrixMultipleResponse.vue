<template>
  <div>
    {{ qtiJson }}
    <table class="table table-striped">
      <thead>
        <tr>
          <th v-for="(header,colIndex) in qtiJson.headers" :key="`matrix-multiple-response-header-${colIndex}`"
              scope="col"
          >
            <b-form-input
              v-if="colIndex === 0"
              v-model="qtiJson.headers[colIndex]"
              type="text"
              placeholder="Column 1"
            />
            <b-input-group v-if="colIndex !== 0">
              <b-form-input
                v-model="qtiJson.headers[colIndex]"
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
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row,rowIndex) in qtiJson.rows" :key="`matrix-multiple-response-row-${rowIndex}`">
          <td v-for="(column,columnIndex) in row" :key="`matrix-multiple-response-column-${columnIndex}`">
            <span v-if="columnIndex ===0">
              <b-input-group>
                <b-form-input
                  v-model="qtiJson.rows[rowIndex][0]"
                  type="text"
                  :placeholder="`Row ${rowIndex+1}`"
                />  <b-input-group-append>
                  <b-input-group-text>
                    <b-icon-trash
                      @click="deleteRow(rowIndex)"
                    />
                  </b-input-group-text>
                </b-input-group-append>
              </b-input-group>
            </span>
            <span v-if="columnIndex !==0">
              <b-form-checkbox
                v-model="qtiJson.rows[rowIndex][columnIndex]"
                :value="true"
                :unchecked-value="false"
              /></span>
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
export default {
  name: 'MatrixMultipleResponse',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    }
  },
  methods: {
    deleteColumn (colIndex) {
      if (this.qtiJson.headers.length === 2) {
        this.$noty.info('You need at least one column in the matrix.')
        return false
      }
      this.qtiJson.headers.splice(colIndex, 1)
      for (let i = 0; i < this.qtiJson.rows.length; i++) {
        this.qtiJson.rows[i].splice(colIndex, 1)
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
      this.qtiJson.headers.push('')
      for (let i = 0; i < this.qtiJson.rows.length; i++) {
        this.qtiJson.rows[i].push(false)
      }
    },
    addRow () {
      let newRow = []
      for (let i = 0; i < this.qtiJson.rows[0].length; i++) {
        newRow[i] = i === 0 ? '' : false
      }
      this.qtiJson.rows.push(newRow)
    }
  }
}
</script>
