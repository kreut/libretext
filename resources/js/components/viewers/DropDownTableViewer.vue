<template>
  <div>
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
          <b-form inline>
            <b-form-select v-model="row.selected"
                           class="mb-3 mr-2"
                           size="sm"
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
            <div v-if="row.selected && showResponseFeedback" class="pb-3">
              <b-icon-check-circle-fill v-if="isCorrect(row.selected, row.responses)"
                                        class="text-success"
              />
              <b-icon-x-circle-fill v-if="!isCorrect(row.selected, row.responses)"
                                    class="text-danger"
              />
            </div>
          </b-form>
        </td>
      </tr>
      </tbody>
    </table>
    <GeneralFeedback :feedback="qtiJson.feedback" :feedback-type="feedbackType"/>
  </div>
</template>

<script>
import GeneralFeedback from '../feedback/GeneralFeedback'

export default {
  name: 'DropDownTableViewer',
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
    feedbackType: ''
  }),
  mounted () {
    for (let i = 0; i < this.qtiJson.rows.length; i++) {
      if (this.qtiJson.rows[i].selected) {
        this.feedbackType = 'correct'
        this.selected[i] = this.qtiJson.rows[i].selected
      }
    }
    for (let i = 0; i < this.qtiJson.rows.length; i++) {
      let row = this.qtiJson.rows[i]
      let selected = row.responses.find(item => item.identifier === row.selected)
      if (selected && !selected.correctResponse) {
        this.feedbackType = 'incorrect'
      }
    }
  },
  methods: {
    updateSelected (identifier, rowIndex) {
      this.$nextTick(() => {
        this.selected[rowIndex] = identifier
      })
    },
    isCorrect (studentResponse, rowResponses) {
      return rowResponses.find(item => item.identifier === studentResponse).correctResponse
    }
  }
}
</script>
