<template>
  <div>
    <span v-if="!studentResponse" v-html="highlightedText"/>
    <HighlightResponseFeedback v-if="studentResponse"
                               :key="`highlighted-text-${highlightedTextIndex}`"
                               :highlighted-text="highlightedText"
                               :responses="qtiJson.responses"
                               :show-response-feedback="showResponseFeedback"
    />
    <GeneralFeedback
      :feedback="qtiJson.feedback"
      :feedback-type="feedbackType"
    />
  </div>
</template>

<script>
import $ from 'jquery'
import { addHighlights, toggleSelected } from '~/helpers/Highlighter'
import HighlightResponseFeedback from '../feedback/HighlightResponseFeedback'
import GeneralFeedback from '../feedback/GeneralFeedback'

export default {
  name: 'HighlightTextViewer',
  components: {
    HighlightResponseFeedback,
    GeneralFeedback
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
    highlightedText: '',
    studentResponse: [],
    highlightedTextIndex: 0,
    feedbackType: ''
  }),
  mounted () {
    this.studentResponse = this.qtiJson.responses.find(response => response.selected)
    this.feedbackType = 'correct'
    for (let i = 0; i < this.qtiJson.responses.length; i++) {
      let response = this.qtiJson.responses[i]
      if (response.correctResponse !== response.selected) {
        this.feedbackType = 'incorrect'
      }
    }
    this.highlightedText = addHighlights(this.qtiJson.prompt, this.qtiJson.responses)
    this.highlightedTextIndex++
    $(document).ready(function () {
      toggleSelected()
      $('.response').hover(function () {
        if (!($(this).hasClass('selected'))) {
          $(this).css({ 'border-color': 'black', 'border-width': '2px', 'border-style': 'dotted' })
        }
      }, function () {
        if (!($(this).hasClass('selected'))) {
          $(this).css('border-style', 'none')
        }
      })
      $('li').css('margin-bottom', '12px')
    })
  }
}
</script>

