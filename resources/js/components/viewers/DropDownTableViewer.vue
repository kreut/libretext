<template>
  <table class="table table-striped">
    <thead class="nurses-table-header">
    <tr>
      <th v-for="(header,colIndex) in qtiJson.colHeaders"
          :key="`drop-down-table-header-${colIndex}`"
          scope="col"
      >
        {{ header }}
      </th>
    </tr>
    </thead>
    <tbody>
    <tr v-for="(row, rowIndex) in qtiJson.rows"
        :key="`drop-down-table-row-${rowIndex}`"
    >
      <th>{{ row.header }}</th>
      <td>
        <b-form-select v-model="row.selected"
                       class="mb-3"
                       @input="updateSelected($event,rowIndex)"
        >
          <b-form-select-option :value="null">
            Please select an option
          </b-form-select-option>
          <b-form-select-option v-for="response in row.responses"
                                :key="`drop-down-table-response-${response.identifier}`"
                                :value="response.identifier"

          >
            {{ response.value }}
          </b-form-select-option>
        </b-form-select>
      </td>
    </tr>
    </tbody>
  </table>
</template>

<script>
export default {
  name: 'DropDownTableViewer',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    selected: []
  }),
  mounted () {
    for (let i = 0; i < this.qtiJson.rows.length; i++) {
      this.selected[i] = null
    }
  },
  methods: {
    updateSelected (identifier, rowIndex) {
      this.$nextTick(() => {
        this.selected[rowIndex] = identifier
      })
    }
  }
}
</script>
