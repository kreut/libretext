<template>
  <div>
    <b-form inline>
      <b-input v-model="numericalResponse"
               placeholder="Please enter your response."
               style="width:200px"
               size="sm"
      />
      <span v-if="qtiJson.showResponseFeedback && qtiJson.studentResponse" class="pl-2">
        <b-icon-check-circle-fill v-if="qtiJson.studentResponse.answeredCorrectly"
                                  class="text-success"
                                  scale="1.1"
        />
        <b-icon-x-circle-fill v-if="!qtiJson.studentResponse.answeredCorrectly"
                              class="text-danger"
                              scale="1.1"
        />
      </span>
    </b-form>
    <div
      v-if="showResponseFeedback
      && qtiJson.correctResponse
      && qtiJson.correctResponse.value
      && qtiJson.correctResponse.marginOfError
       && parseFloat(qtiJson.correctResponse.marginOfError)>0"
      class="p-1"
    >
      (Responses between {{
        1*(parseFloat(qtiJson.correctResponse.value) - parseFloat(qtiJson.correctResponse.marginOfError)).toFixed(4)
      }}
      and {{
        1*(parseFloat(qtiJson.correctResponse.value) + parseFloat(qtiJson.correctResponse.marginOfError)).toFixed(4)
      }} will be marked as correct.)
    </div>
    <GeneralFeedback v-if="qtiJson.jsonType === 'question_json'"
                     :feedback="qtiJson.feedback"
                     :feedback-type="feedbackType"
    />
  </div>
</template>

<script>
import GeneralFeedback from '../feedback/GeneralFeedback'
import { mapGetters } from 'vuex'

export default {
  name: 'NumericalViewer',
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
    numericalResponse: '',
    feedbackType: 'incorrect'
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    })
  },
  mounted () {
    if (this.qtiJson.studentResponse) {
      this.numericalResponse = this.qtiJson.studentResponse.response
      if (this.qtiJson.studentResponse.answeredCorrectly) {
        this.feedbackType = 'correct'
      }
    }
  }
}
</script>
