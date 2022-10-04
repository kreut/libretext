<template>
  <div>
    {{ qtiJson }}
    <table class="table table-striped">
      <thead class="nurses-table-header">
      <tr>
        <th v-for="(header,colIndex) in qtiJson.colHeaders" :key="`drop-down-table-header-${colIndex}`"
            scope="col"
        >
          <b-form-input
            v-model="qtiJson.colHeaders[colIndex]"
            type="text"
            :placeholder="`Column Header ${colIndex+1}`"
          />
        </th>
      </tr>
      </thead>
      <tbody>
      <template v-for="(row,rowIndex) in qtiJson.rows">
        <tr :key="`table-row-${rowIndex}-1`">
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
          </td>
          <td>
            <b-form-textarea
              :id="`text-area-${rowIndex}`"
              v-model="qtiJson.rows[rowIndex].prompt"
              placeholder="Enter something..."
              rows="3"
              max-rows="6"
            />
          </td>
        </tr>
        <tr :key="`table-row-${rowIndex}-2`">
          <td>{{ responses[rowIndex] }}</td>
        </tr>
      </template>
      </tbody>
    </table>
    <b-button class="primary" size="sm" @click="addRow">
      Add row
    </b-button>
  </div>
</template>

<script>
export default {
  name: 'HighlightTable',
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
    responses () {
      let responses = []
      if (this.qtiJson.rows) {
        for (let i = 0; i < this.qtiJson.rows.length; i++) {
          let row = this.qtiJson.rows[i]
          let rowResponse = row.prompt
          responses.push(rowResponse)
        }
        return responses
      } else {
        return []
      }
    }
  },
  methods: {
    deleteRow (rowIndex) {
      this.qtiJson.rows.length === 1
        ? this.$noty.info('You need at least one row.')
        : this.qtiJson.rows.splice(rowIndex, 1)
    },
    addRow () {
      this.qtiJson.rows.push({
        header: '',
        prompt: '',
        responses: []
      })
    }
  }
}
</script>
