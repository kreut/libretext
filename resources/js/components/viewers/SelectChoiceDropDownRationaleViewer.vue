<template>
  <div>
    <form class="form-inline">
      <div v-html="addSelectChoices"/>
    </form>
    <GeneralFeedback v-if="qtiJson.jsonType === 'question_json'"
                     :feedback="qtiJson.feedback"
                     :feedback-type="feedbackType"
    />
  </div>
</template>

<script>

import $ from 'jquery'
import { successIcon, failureIcon } from '~/helpers/SuccessFailureIcons'
import GeneralFeedback from '../feedback/GeneralFeedback'

export default {
  name: 'SelectChoiceDropDownRationaleViewer',
  components: {
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
    successIcon: successIcon,
    failureIcon: failureIcon,
    selectChoicesArray: [],
    selectedOption: [],
    encodedStr: '',
    encoding: true,
    feedbackType: 'incorrect'
  }),
  computed: {
    addSelectChoices () {
      if (this.qtiJson.itemBody) {
        let reg = /\[(.*?)\]/g
        let selectChoicesArray = this.qtiJson.itemBody.split(reg)
        console.log(selectChoicesArray)
        let html = ''
        for (let i = 0; i < selectChoicesArray.length; i++) {
          let part = selectChoicesArray[i]
          if (i % 2 === 0) {
            html += part
          } else {
            let studentResponse = this.qtiJson.studentResponse ? this.qtiJson.studentResponse[Math.floor(i / 2)] : ''
            let chosenOption = studentResponse ? studentResponse.value : ''
            html += `<select style="margin:3px"
class="identifier-${part} select-choice custom-select custom-select-sm form-control inline-form-control"
aria-label="combobox ${Math.ceil(i / 2)} of ${Math.floor(selectChoicesArray.length / 2)}">
<option value="">Please select an option</option>`
            for (let i = 0; i < this.qtiJson.inline_choice_interactions[part].length; i++) {
              let selectChoice = this.qtiJson.inline_choice_interactions[part][i]
              let selected = selectChoice.value === chosenOption ? 'selected' : ''
              html += `<option value="${selectChoice.value}" ${selected}>${selectChoice.text}</option>`
            }
            html += '</select>'
            if (this.qtiJson.jsonType === 'question_json' && studentResponse) {
              html += studentResponse.answeredCorrectly ? this.successIcon : this.failureIcon
            }
          }
        }
        return html
      } else {
        return []
      }
    }
  },
  mounted () {
    this.$nextTick(() => {
      for (let i = 0; i < Math.floor(this.selectChoicesArray.length / 2); i++) {
        let selected = this.qtiJson.studentResponse ? this.qtiJson.studentResponse[i].value : ''
        this.selectedOption.push(selected)
      }
      if (this.qtiJson.studentResponse) {
        this.feedbackType = this.qtiJson.studentResponse.filter(response => response.answeredCorrectly).length === this.qtiJson.studentResponse.length
          ? 'correct'
          : 'incorrect'
      }
    })
    $(document).on('change', 'select.select-choice', function () {
      $(this).removeClass('is-invalid-border')
    })
    $('p:empty').remove()
    this.$forceUpdate()
  }
}
</script>
