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
                      v-model="row.correctResponse"
                      :name="`Row ${rowIndex}`"
                      :value="colIndex"
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
  computed: {
    headersWithoutInitialColumn () {
      let headers = this.qtiJson.headers
      return headers.slice(1)
    }
  }
}
</script>

<style scoped>

</style>
