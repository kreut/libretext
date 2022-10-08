<template>
  <div>
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
              <CheckBoxResponseFeedback
                v-if="showResponseFeedback"
                :key="`response-feedback-${rowIndex}-${responseIndex}`"
                :identifier="response.identifier"
                :responses="row.responses"
                :student-response="qtiJson.studentResponse"
              />
            </b-form-checkbox>
          </b-form-checkbox-group>
        </td>
      </tr>
      </tbody>
    </table>
    {{ feedbackType }}wefwefwefwefwef
    <GeneralFeedback :feedback="qtiJson.feedback"
                     :feedback-type="feedbackType"
    />
  </div>
</template>

<script>
import CheckBoxResponseFeedback
  from '../feedback/CheckBoxResponseFeedback'
import GeneralFeedback from '../feedback/GeneralFeedback'

export default {
  name: 'MultipleResponseGrouping',
  components: {
    GeneralFeedback,
    CheckBoxResponseFeedback
  },
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
    feedbackType: ''
  }),
  mounted () {
    if (this.qtiJson.studentResponse) {
      this.feedbackType = 'correct'
    }
    for (let i = 0; i < this.qtiJson.rows.length; i++) {
      let row = this.qtiJson.rows[i]
      for (let j = 0; j < row.responses.length; j++) {
        let response = row.responses[j]
        if (this.qtiJson.studentResponse) {
          if ((!response.correctResponse && this.qtiJson.studentResponse.includes(response.identifier)) ||
            (response.correctResponse && !this.qtiJson.studentResponse.includes(response.identifier))) {
            this.feedbackType = 'incorrect'
          }
          if (this.qtiJson.studentResponse.includes(response.identifier)) {
            this.selected.push(response.identifier)
          }
        }
      }
    }
  }
}
</script>
