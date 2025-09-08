<template>
  <div>
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
        <tr v-for="(row, rowIndex) in qtiJson.rows" :id="`row-${rowIndex+1}`"
            :key="`matrix-multiple-choice-row-${rowIndex}`"
            role="radiogroup"
            :aria-labelledby="row.label"
        >
          <th :id="row.label">
            {{ row.label }}
          </th>
          <td v-for="(column, colIndex) in headersWithoutInitialColumn"
              :key="`matrix-multiple-choice-row-${rowIndex}-${colIndex}`"
          >
            <b-form-radio v-if="colIndex < qtiJson.headers.length - 1"
                          v-model="row.selected"
                          :name="`Row-${rowIndex}-${componentId}`"
                          :value="colIndex"
                          role="radio"
                          @input="updateSelected(rowIndex,colIndex)"
            >
              <span v-if="row.selected === colIndex && showResponseFeedback && row.hasOwnProperty('correctResponse')">
                <b-icon-check-circle-fill v-if="row.correctResponse === row.selected" class="text-success" />
                <b-icon-x-circle-fill v-if="row.correctResponse !== row.selected" class="text-danger" />
              </span>
            </b-form-radio>
          </td>
        </tr>
      </tbody>
    </table>
    <GeneralFeedback :feedback="qtiJson.feedback" :feedback-type="feedbackType" />
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'
import GeneralFeedback from '../feedback/GeneralFeedback'

export default {
  name: 'MatrixMultipleChoiceViewer',
  components: { GeneralFeedback },
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    },
    showResponseFeedback: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
    selected: [],
    feedbackType: '',
    componentId: uuidv4() // needed because if the answer and question modal are both open, the radio buttons get reset
  }),
  computed: {
    headersWithoutInitialColumn () {
      let headers = this.qtiJson.headers
      return headers.slice(1)
    }
  },
  mounted () {
    for (let i = 0; i < this.qtiJson.rows.length; i++) {
      if (this.qtiJson.rows[i].selected) {
        this.feedbackType = 'correct'
      }
    }
    for (let i = 0; i < this.qtiJson.rows.length; i++) {
      let row = this.qtiJson.rows[i]
      this.selected[i] = row.hasOwnProperty('selected') ? row.selected : null
      if (row.correctResponse !== row.selected) {
        this.feedbackType = 'incorrect'
      }
      console.log(this.selected)
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
