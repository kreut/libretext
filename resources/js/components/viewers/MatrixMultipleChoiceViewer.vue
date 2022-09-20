<template>
  <table class="table table-striped">
    <thead class="nurses-table-header">
    <tr>
      <th v-for="(header, headerIndex) in qtiJson.headers"
          :key="`matrix-multiple-response-header-${headerIndex}`" scope="col"
      >
        {{ header }}
      </th>
    </tr>
    </thead>
    <tbody>
    <tr v-for="(row, rowIndex) in qtiJson.rows" :key="`matrix-multiple-choice-row-${rowIndex}`">
      <th>{{ row.label }}</th>
      <td v-for="(column, colIndex) in headersWithoutInitialColumn"
          :key="`matrix-multiple-choice-row-${rowIndex}-${colIndex}`"
      >
        <b-form-radio v-if="colIndex < qtiJson.headers.length - 1"
                      v-model="row.selected"
                      :name="`Row ${rowIndex}`"
                      :value="colIndex"
                      @input="updateSelected(rowIndex,colIndex)"
        />
      </td>
    </tr>
    </tbody>
  </table>
</template>

<script>
export default {
  name: 'MatrixMultipleChoiceViewer',
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
  computed: {
    headersWithoutInitialColumn () {
      let headers = this.qtiJson.headers
      return headers.slice(1)
    }
  },
  mounted () {
    for (let i = 0; i < this.qtiJson.rows.length; i++) {
      this.selected[i] = null
    }
  },
  methods: {
    updateSelected (rowIndex, colIndex) {
      this.$nextTick(() => {
        this.selected[rowIndex] = colIndex
      })
    }
  }
}
</script>

<style scoped>

</style>
