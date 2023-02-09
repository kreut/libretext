<template>
  <div>
    <b-modal
      id="modal-multiple-answers-feedback"
      title="Feedback"
      hide-footer
    >
      <span v-html="multipleAnswersFeedback"/>
    </b-modal>
    <b-form-checkbox-group
      v-model="selectedMultipleAnswers"
    >
      <div v-for="(choice,index) in qtiJson.simpleChoice"
           :key="`multiple_answers_${choice.identifier}`"
      >
        <b-form-checkbox :value="choice.identifier" style="margin-right:0px">
          <span v-html="choice.value.replace('<p>','').replace('</p>','')"/>
          <CheckBoxResponseFeedback
            v-if="showResponseFeedback && qtiJson.studentResponse"
            :key="`response-feedback-${index}`"
            :identifier="choice.identifier"
            :responses="qtiJson.simpleChoice"
            :student-response="qtiJson.studentResponse"
          />
          <span v-if="choice.feedback && qtiJson.jsonType === 'question_json'">
            <span @mouseover="showFeedback( choice.feedback)">
              <QuestionCircleTooltip
                :color="'text-danger'"
              /></span>
          </span>
        </b-form-checkbox>

      </div>
    </b-form-checkbox-group>
  </div>
</template>

<script>
import CheckBoxResponseFeedback from '../feedback/CheckBoxResponseFeedback'

export default {
  name: 'MultipleAnswersViewer',
  components: {
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
    selectedMultipleAnswers: [],
    multipleAnswersFeedback: ''
  }),
  mounted () {
    if (this.qtiJson.studentResponse) {
      this.selectedMultipleAnswers = this.qtiJson.studentResponse
      console.log(this.selectedMultipleAnswers)
    }
  },
  methods: {
    showFeedback (feedback) {
      this.multipleAnswersFeedback = feedback
      this.$nextTick(() => {
        this.$bvModal.show('modal-multiple-answers-feedback')
      })
    }
  }
}
</script>

<style scoped>

</style>
