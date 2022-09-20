<template>
  <div>
    {{ qtiJson }}
    {{ selected }}
    <table class="table table-striped">
      <thead class="nurses-table-header">
      <tr>
        <th v-for="(header,colIndex) in qtiJson.headers"
            :key="`multiple-response-grouping-header-${colIndex}`"
            scope="col"
        >
          {{ header }}
        </th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(row, rowIndex) in qtiJson.rows"
          :key="`multiple-response-grouping-row-${rowIndex}`"
      >
        <th>{{ row.grouping }}</th>
        <td>
          <b-form-checkbox-group
            v-model="selected"
            stacked
          >
            <b-form-checkbox v-for="(response, responseIndex) in row.responses"
                             :key="`multiple-response-grouping-row-${rowIndex}-response-${responseIndex}`"
                             :value="response.identifier"
            >
              {{ response.value }}
            </b-form-checkbox>
          </b-form-checkbox-group>
        </td>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'MultipleResponseGrouping',
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
      let row = this.qtiJson.rows[i]
      for (let j = 0; j < row.responses.length; j++) {
        let response = row.responses[j]
        if (response.selected) {
          this.selected.push(response.identifier)
        }
      }
    }
  }
}
</script>
