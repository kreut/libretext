<template>
  <div>
    <table class="table table-striped">
      <thead class="nurses-table-header">
      <tr>
        <th v-for="(colHeader, colHeaderIndex) in qtiJson.colHeaders"
            :key="`matrix-multiple-response-header-${colHeaderIndex}`" scope="col"
        >
          {{ colHeader }}
        </th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(row, rowIndex) in qtiJson.rows" :key="`matrix-multiple-response-row-${rowIndex}`">
        <th>{{ row.header }}</th>
        <td v-for="(response, responseIndex) in row.responses" :key="`matrix-multiple-response-row-${responseIndex}`">
          <b-form-checkbox-group>
            <b-form-checkbox v-model="selectedMatrixMultipleResponses" :value="response.identifier">
              <CheckBoxResponseFeedback
                v-if="showResponseFeedback && responses.length"
                :key="`response-feedback-${rowIndex}-${responseIndex}`"
                :identifier="response.identifier"
                :responses="responses"
                :student-response="qtiJson.studentResponse"
              />
            </b-form-checkbox>
          </b-form-checkbox-group>
        </td>
      </tr>
      </tbody>
    </table>
    <GeneralFeedback :feedback="qtiJson.feedback"
                     :feedback-type="feedbackType"
    />
  </div>
</template>

<script>
import CheckBoxResponseFeedback from '../feedback/CheckBoxResponseFeedback'
import GeneralFeedback from '../feedback/GeneralFeedback'

export default {
  name: 'MatrixMultipleResponseViewer',
  components: { CheckBoxResponseFeedback, GeneralFeedback },
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
    feedbackType: '',
    selectedMatrixMultipleResponses: [],
    responses: []
  }),
  mounted () {
    if (this.qtiJson.studentResponse) {
      this.feedbackType = 'correct'
      this.selectedMatrixMultipleResponses = this.qtiJson.studentResponse
      for (let i = 0; i < this.qtiJson.rows.length; i++) {
        let row = this.qtiJson.rows[i]
        for (let j = 0; j < row.responses.length; j++) {
          let response = row.responses[j]
          this.responses.push(response)
          if ((!response.correctResponse && this.qtiJson.studentResponse.includes(response.identifier)) ||
            (response.correctResponse && !this.qtiJson.studentResponse.includes(response.identifier))) {
            this.feedbackType = 'incorrect'
          }
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
