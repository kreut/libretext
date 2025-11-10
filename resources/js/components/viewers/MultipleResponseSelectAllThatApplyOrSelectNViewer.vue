<template>
  <div>
    <b-form-checkbox-group
      v-model="selectedAllThatApply"
      name="select-all-that-apply"
    >
      <div v-for="(option,index) in selectAllThatApplyOptions" :key="`option-${index}`">
        <b-form-checkbox :value="option.value"
                         :checked="selectedAllThatApply.includes(option.identifier)"
        >
          {{ option.text }}
          <CheckBoxResponseFeedback
            v-if="showResponseFeedback"
            :key="`response-feedback-${index}`"
            :identifier="option.value"
            :responses="qtiJson.responses"
            :check-marks="checkMarks"
            :student-response="qtiJson.studentResponse"
          />
        </b-form-checkbox>
      </div>
    </b-form-checkbox-group>
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
  name: 'MultipleResponseSelectAllThatApplyOrSelectN',
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
    checkMarks: '',
    selectedAllThatApply: [],
    selectAllThatApplyOptions: [],
    feedbackType: ''
  }),
  mounted () {
    if (this.qtiJson.check_marks) {
      this.checkMarks = this.qtiJson.check_marks
    }
    if (this.qtiJson.studentResponse) {
      this.feedbackType = 'correct'
    }
    for (let i = 0; i < this.qtiJson.responses.length; i++) {
      let response = this.qtiJson.responses[i]
      this.selectAllThatApplyOptions.push({ text: response.value, value: response.identifier })
      if (this.qtiJson.studentResponse) {
        if ((!response.correctResponse && this.qtiJson.studentResponse.includes(response.identifier)) ||
          (response.correctResponse && !this.qtiJson.studentResponse.includes(response.identifier))) {
          this.feedbackType = 'incorrect'
        }
        if (this.qtiJson.studentResponse.includes(response.identifier)) {
          this.selectedAllThatApply.push(response.identifier)
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
