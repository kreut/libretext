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
          <div v-if="!row.studentResponse" v-html="row.highlightedText"/>
          <HighlightResponseFeedback v-if="row.studentResponse"
                                     :key="`highlighted-text-${highlightedTextIndex}`"
                                     :highlighted-text="row.highlightedText"
                                     :responses="row.responses"
                                     :show-response-feedback="showResponseFeedback"
          />
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
import { addHighlights, toggleSelected } from '~/helpers/Highlighter'
import HighlightResponseFeedback from '../feedback/HighlightResponseFeedback'
import $ from 'jquery'
import GeneralFeedback from '../feedback/GeneralFeedback'

export default {
  name: 'HighlightTableViewer',
  components: {
    GeneralFeedback,
    HighlightResponseFeedback
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
    highlightedText: [],
    studentResponse: [],
    highlightedTextIndex: 0,
    feedbackType: ''
  }),
  mounted () {
    this.feedbackType = 'correct'
    if (this.qtiJson.rows) {
      for (let i = 0; i < this.qtiJson.rows.length; i++) {
        this.row = this.qtiJson.rows[i]
        this.row.studentResponse = this.qtiJson.rows[i].responses.find(response => response.selected)
        if (this.qtiJson.rows[i].responses) {
          for (let j = 0; j < this.row.responses.length; j++) {
            let response = this.row.responses[j]
            if (response.selected !== response.correctResponse) {
              this.feedbackType = 'incorrect'
            }
          }
        }
        let highlightedText = this.row.prompt
        highlightedText = addHighlights(this.row.prompt, this.row.responses)
        this.row.highlightedText = highlightedText
      }
      this.highlightedTextIndex++
      this.$forceUpdate()
    }
    $(document).ready(function () {
      toggleSelected()
    })
  }
}
</script>

<style scoped>

</style>
