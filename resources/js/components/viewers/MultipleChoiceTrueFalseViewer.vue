<template>
  <div>
    <b-modal
      id="modal-simple-choice-feedback"
      title="Feedback"
      hide-footer
    >
      <span v-html="multipleChoiceFeedback"/>
    </b-modal>
    <div class="pt-4">
      <div v-for="choice in qtiJson.simpleChoice" :key="`identifier-${choice.identifier}-student-response`">
        <b-form-radio
          v-model="selectedSimpleChoice"
          :value="choice.identifier"
          :size="presentationMode ? 'lg' : ''"
          style="padding-bottom:5px"
        >
          <span class="multiple-choice-responses" v-html="choice.value"/>
          <span
            v-if="selectedSimpleChoice === choice.identifier
              && qtiJson.studentResponse === choice.identifier
              && qtiJson.feedback
              && JSON.stringify(qtiJson.feedback) !== '{}'
            "
          >
            <b-icon-check-circle-fill v-if="choice.correctResponse"
                                      class="text-success"
            />
            <b-icon-x-circle-fill v-if="!choice.correctResponse"
                                  class="text-danger"
            />
          </span>
        </b-form-radio>
      </div>
    </div>
    <div v-if="!isStudent && getSpecificFeedback().length">
      <hr>
      <b-card border-variant="info"
              header="Specific Feedback"
              header-bg-variant="info"
              header-text-variant="white"
              header-class="pt-2 pb-2 pl-3"
      >
        <b-table
          aria-label="Specific Feedback"
          striped
          hover
          :no-border-collapse="true"
          :items="getSpecificFeedback()"
          :fields="['choice','feedback']"
        >
          <template v-slot:cell(choice)="data">
            <div v-html="data.item.choice"/>
          </template>
          <template v-slot:cell(feedback)="data">
            <div v-html="data.item.feedback"/>
          </template>
        </b-table>
      </b-card>
    </div>
    <GeneralFeedback v-if="qtiJson.jsonType === 'question_json'"
                     :key="`general-feedback-${feedbackKey}`"
                     :feedback="qtiJson.feedback"
                     :feedback-type="feedbackType"
    />
  </div>
</template>

<script>
import GeneralFeedback from '../feedback/GeneralFeedback'
import { mapGetters } from 'vuex'
import $ from 'jquery'

export default {
  name: 'MultipleChoiceTrueFalseViewer',
  components: { GeneralFeedback },
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    },
    presentationMode: {
      type: Boolean,
      default: false
    }
  },
  data: () => ({
    feedbackKey: 0,
    specificFeedback: '',
    selectedSimpleChoice: null,
    multipleChoiceFeedback: '',
    feedbackType: 'incorrect',
    isStudent: true
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    })
  },
  mounted () {
    if (this.qtiJson.simpleChoice) {
      this.isStudent = ![2, 4, 5].includes(this.user.role)
      if (this.qtiJson.questionType === 'true_false') {
        this.setTrueFalseLanguage(this.qtiJson.simpleChoice[0].value)
      }
      if (this.qtiJson.studentResponse) {
        this.selectedSimpleChoice = this.qtiJson.studentResponse

        if (this.qtiJson.feedback && this.qtiJson.feedback[this.selectedSimpleChoice]) {
          this.qtiJson.feedback.specific = this.qtiJson.feedback[this.selectedSimpleChoice]
          this.feedbackKey++
        }
        for (let i = 0; i < this.qtiJson.simpleChoice.length; i++) {
          let simpleChoice = this.qtiJson.simpleChoice[i]
          simpleChoice.chosenStudentResponse = this.selectedSimpleChoice === simpleChoice.identifier
          if (simpleChoice.chosenStudentResponse && simpleChoice.correctResponse) {
            this.feedbackType = 'correct'
          }
        }
      }
    }
    this.$nextTick(() => {
      $('.multiple-choice-responses > p').contents().unwrap() // remove paragraphs for formatting purposes
    })
  },
  methods: {
    getSpecificFeedback () {
      let specificFeedbacks = []
      for (const identifier in this.qtiJson.feedback) {
        if (!['any', 'correct', 'incorrect'].includes(identifier)) {
          if (this.qtiJson.feedback[identifier]) {
            let simpleChoice = this.qtiJson.simpleChoice.find(choice => choice.identifier === identifier)
            if (simpleChoice) {
              let simpleChoiceText = simpleChoice.value
              let specificFeedback = {
                identifier: identifier,
                choice: simpleChoiceText,
                feedback: this.qtiJson.feedback[identifier]
              }
              console.log(specificFeedback)
              specificFeedbacks.push(specificFeedback)
            }
          }
        }
      }
      return specificFeedbacks
    },
    setTrueFalseLanguage (trueValue) {
      switch (trueValue) {
        case ('True'):
          this.trueFalseLanguage = 'English'
          break
        case ('Verdadero'):
          this.trueFalseLanguage = 'Spanish'
          break
        case ('Vrai'):
          this.trueFalseLanguage = 'French'
          break
        case ('Vero'):
          this.trueFalseLanguage = 'Italian'
          break
        case ('Richtig'):
          this.trueFalseLanguage = 'German'
          break
        default:
          this.trueFalseLanguage = 'English'
      }
    },
    showFeedback (feedback) {
      this.multipleChoiceFeedback = feedback
      this.$nextTick(() => {
        this.$bvModal.show('modal-simple-choice-feedback')
      })
    }
  }
}
</script>
