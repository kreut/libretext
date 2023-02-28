<template>
  <div class="pb-3 pr-3 pl-3 pt-2">
    <b-modal
      id="modal-submission-error"
      title="Submission Not Accepted"
      size="lg"
      hide-footer
    >
      <b-alert variant="danger" :show="true">
        <span style="font-size: large">{{ submissionErrorMessage }}</span>
      </b-alert>
    </b-modal>
    <b-alert v-if="!showQtiAnswer
               && user.role === 2
               && ['multiple_choice', 'select_choice','drop_down_rationale','multiple_answers'].includes(questionType)"
             show
             variant="info"
    >
      Students will receive a randomized ordering of possible responses.
    </b-alert>
    <div :id="showQtiAnswer ? 'answer' : 'question'">
      <SelectChoiceDropDownRationaleViewer v-if="['select_choice','drop_down_rationale'].includes(questionType)"
                                           ref="dropDownTableViewer"
                                           :qti-json="JSON.parse(qtiJson)"
                                           :show-response-feedback="showResponseFeedback"
      />

      <FillInTheBlankViewer v-if="questionType === 'fill_in_the_blank'"
                            ref="fillInTheBlankViewer"
                            :qti-json="JSON.parse(qtiJson)"
                            :show-response-feedback="showResponseFeedback"
      />
      <div
        v-if="['matching',
               'true_false',
               'multiple_choice',
               'multiple_answers',
               'numerical',
               'multiple_response_select_all_that_apply',
               'multiple_response_select_n',
               'matrix_multiple_response',
               'matrix_multiple_choice',
               'multiple_response_grouping',
               'drop_down_table',
               'highlight_table',
               'bow_tie'].includes(questionType)"
      >
        <div style="font-size:16px;font-family: Sans-Serif,serif;">
          <span v-html="prompt"/>
        </div>
        <b-form-group>
          <DropDownTableViewer v-if="questionType === 'drop_down_table'"
                               ref="dropDownTableViewer"
                               :qti-json="JSON.parse(qtiJson)"
                               :show-response-feedback="showResponseFeedback"
          />
          <MultipleResponseGroupingViewer v-if="questionType === 'multiple_response_grouping'"
                                          ref="multipleResponseGroupingViewer"
                                          :qti-json="JSON.parse(qtiJson)"
                                          :show-response-feedback="showResponseFeedback"
          />
          <BowTieViewer v-if="questionType === 'bow_tie'"
                        ref="bowTieViewer"
                        :qti-json="JSON.parse(qtiJson)"
                        :show-response-feedback="showResponseFeedback"
          />
          <MatrixMultipleResponseViewer
            v-if="questionType === 'matrix_multiple_response'"
            ref="matrixMultipleResponseViewer"
            :qti-json="JSON.parse(qtiJson)"
            :show-response-feedback="showResponseFeedback"
          />
          <MultipleResponseSelectAllThatApplyOrSelectNViewer
            v-if="['multiple_response_select_n','multiple_response_select_all_that_apply'].includes(questionType)"
            ref="multipleResponseSelectAllThatApplyOrSelectNViewer"
            :qti-json="JSON.parse(qtiJson)"
            :show-response-feedback="showResponseFeedback"
          />
          <NumericalViewer v-if="questionType === 'numerical'"
                           ref="numericalViewer"
                           :qti-json="JSON.parse(qtiJson)"

          />
          <MatchingViewer v-if="questionType === 'matching'"
                          ref="matchingViewer"
                          :qti-json="JSON.parse(qtiJson)"
                          :show-submit="showSubmit"
          />

          <MultipleChoiceTrueFalseViewer v-if="['true_false','multiple_choice'].includes(questionType)"
                                         ref="multipleChoiceTrueFalseViewer"
                                         :qti-json="JSON.parse(qtiJson)"
          />
          <MultipleAnswersViewer v-if="questionType === 'multiple_answers'"
                                 ref="multipleAnswersViewer"
                                 :qti-json="JSON.parse(qtiJson)"
                                 :show-response-feedback="showResponseFeedback"
          />
          <MatrixMultipleChoiceViewer v-if="questionType === 'matrix_multiple_choice'"
                                      ref="matrixMultipleChoiceViewer"
                                      :qti-json="JSON.parse(qtiJson)"
                                      :show-response-feedback="showResponseFeedback"
          />
        </b-form-group>
      </div>
      <DragAndDropClozeViewer
        v-if="questionType === 'drag_and_drop_cloze'"
        ref="dragAndDropClozeViewer"
        :qti-json="JSON.parse(qtiJson)"
        :show-response-feedback="showResponseFeedback"
      />
      <HighlightTextViewer
        v-if="questionType === 'highlight_text'"
        ref="highlightTextViewer"
        :qti-json="JSON.parse(qtiJson)"
        :show-response-feedback="showResponseFeedback"
      />
      <HighlightTableViewer
        v-if="questionType === 'highlight_table'"
        ref="highlightTableViewer"
        :qti-json="JSON.parse(qtiJson)"
        :show-response-feedback="showResponseFeedback"
      />
      <b-button v-if="showSubmit"
                variant="primary"
                :disabled="!submitButtonActive"
                size="sm"
                @click="submitResponse()"
      >
        Submit
      </b-button>
      <div v-if="isMe" class="pt-2">
        <b-button v-if="jsonShown" size="sm" @click="jsonShown = false">
          Hide json
        </b-button>
        <b-button v-if="!jsonShown" size="sm" @click="jsonShown = true">
          Show json
        </b-button>
      </div>
      <div v-if="jsonShown" class="pt-2">
        <hr>
        {{ question }}
      </div>
    </div>
  </div>
</template>

<script>
import $ from 'jquery'
import { mapGetters } from 'vuex'
import NumericalViewer from './viewers/NumericalViewer'
import BowTieViewer from './viewers/BowTieViewer'
import MatrixMultipleChoiceViewer from './viewers/MatrixMultipleChoiceViewer'
import MultipleResponseSelectAllThatApplyOrSelectNViewer
  from './viewers/MultipleResponseSelectAllThatApplyOrSelectNViewer'
import MultipleResponseGroupingViewer from './viewers/MultipleResponseGroupingViewer'
import DropDownTableViewer from './viewers/DropDownTableViewer'
import DragAndDropClozeViewer from './viewers/DragAndDropClozeViewer'
import HighlightTextViewer from './viewers/HighlightTextViewer'
import HighlightTableViewer from './viewers/HighlightTableViewer'
import FillInTheBlankViewer from './viewers/FillInTheBlankViewer'
import MatchingViewer from './viewers/MatchingViewer'
import SelectChoiceDropDownRationaleViewer from './viewers/SelectChoiceDropDownRationaleViewer'
import MultipleAnswersViewer from './viewers/MultipleAnswersViewer'
import MultipleChoiceTrueFalseViewer from './viewers/MultipleChoiceTrueFalseViewer'
import MatrixMultipleResponseViewer from './viewers/MatrixMultipleResponseViewer'

export default {
  name: 'QtiJsonQuestionViewer',
  components: {
    MatrixMultipleResponseViewer,
    MultipleChoiceTrueFalseViewer,
    MultipleAnswersViewer,
    NumericalViewer,
    SelectChoiceDropDownRationaleViewer,
    FillInTheBlankViewer,
    MatchingViewer,
    HighlightTableViewer,
    HighlightTextViewer,
    BowTieViewer,
    MatrixMultipleChoiceViewer,
    MultipleResponseSelectAllThatApplyOrSelectNViewer,
    MultipleResponseGroupingViewer,
    DropDownTableViewer,
    DragAndDropClozeViewer
  },
  props: {
    qtiJson: {
      type: String,
      default: ''
    },
    studentResponse: {
      type: String,
      default: ''
    },
    showSubmit: {
      type: Boolean,
      default: true
    },
    submitButtonActive: {
      type: Boolean,
      default: true
    },
    showQtiAnswer: {
      type: Boolean,
      default: false
    },
    showResponseFeedback: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
      matchingFeedback: '',
      termsToMatch: [],
      possibleMatches: [],
      jsonShown: false,
      submissionErrorMessage: '',
      questionType: '',
      selectChoices: [],
      question: {},
      prompt: '',
      simpleChoice: []
    }
  ),
  computed: {
    isMe: () => window.config.isMe,
    ...mapGetters({
      user: 'auth/user'
    })
  },
  mounted () {
    this.question = JSON.parse(this.qtiJson)
    if (!this.question) {
      console.log('no question here')
      return false
    }
    this.questionType = this.question.questionType
    console.log(this.question)
    switch (this.questionType) {
      case ('numerical'):
      case ('matching') :
      case ('multiple_answers'):
      case ('matrix_multiple_response'):
      case ('multiple_response_grouping'):
      case ('drop_down_table'):
      case ('matrix_multiple_choice'):
      case ('drag_and_drop_cloze'):
      case ('highlight_text'):
      case ('multiple_choice'):
      case ('true_false'):
      case ('bow_tie'):
      case ('highlight_table'):
        this.prompt = this.question['prompt']
        this.$nextTick(() => {
          $('#question').find('h2').css({
            'display': 'block',
            'font-size': '1.333em',
            'margin-block-start': '0.83em',
            'margin-block-end': '0.83em',
            'margin-inline-start': '0px',
            'margin-inline-end': '0px',
            'font-weight': 'bold'
          })
        })
        break
      case ('drop_down_rationale'):
      case ('select_choice'):
      case ('fill_in_the_blank'):

        break
      case ('multiple_response_select_n'):
      case ('multiple_response_select_all_that_apply'): {
        let reg = /\[([1-9]|[1-9][0-9])\]/g
        let bracketArray = this.question['prompt'].split(reg)
        this.prompt = bracketArray.join(' ')
        break
      }
      default:
        alert(`${this.questionType} is not yet supported.`)
    }
    this.$nextTick(() => {
      MathJax.Hub.Queue(['Typeset', MathJax.Hub])
    })
  },
  methods: {
    shuffleArray (array) {
      for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]]
      }
    },
    submitResponse () {
      let response
      let invalidResponse = false
      let submissionErrorMessage = ''
      switch (this.questionType) {
        case ('numerical'):
          response = this.$refs.numericalViewer.numericalResponse.toString()
          if (response === '') {
            invalidResponse = true
          }
          submissionErrorMessage = 'Please make a selection before submitting.'
          break

        case ('matching'):
          response = JSON.stringify(this.$refs.matchingViewer.termsToMatch)
          break
        case ('multiple_choice'):
        case ('true_false'):
          response = this.$refs.multipleChoiceTrueFalseViewer.selectedSimpleChoice
          // need to check client side otherwise a null submission will throw an error with a 422 message
          if ((response === null || response === '')) {
            invalidResponse = true
          }
          submissionErrorMessage = 'Please make a selection before submitting.'
          break
        case ('multiple_answers'):
          response = JSON.stringify(this.$refs.multipleAnswersViewer.selectedMultipleAnswers)
          break
        case ('matrix_multiple_response'):
          response = JSON.stringify(this.$refs.matrixMultipleResponseViewer.selectedMatrixMultipleResponses)
          console.log(response)
          break
        case ('drop_down_rationale'):
        case ('select_choice'):
          response = []
          $('select.select-choice').each(function () {
            console.log($(this).val())
            if ($(this).val() === '') {
              $(this).addClass('is-invalid-border')
              invalidResponse = true
              submissionErrorMessage = 'Please make a make a selection for all of the dropdowns before submitting.'
            }
            let identifier
            let classes = $(this).attr('class').split(/\s+/)
            for (let i = 0; i < classes.length; i++) {
              if (classes[i].startsWith('identifier-')) {
                identifier = classes[i].replace('identifier-', '')
              }
            }
            response.push({ identifier: identifier, value: $(this).val() })
          })
          response = JSON.stringify(response)
          break
        case ('fill_in_the_blank'):
          response = []
          $('input.fill-in-the-blank').each(function () {
            if ($(this).val() === '') {
              $(this).addClass('is-invalid-border')
              invalidResponse = true
              submissionErrorMessage = 'Please be sure to fill in all blanks before submitting.'
            }
            response.push({ identifier: $(this).attr('class'), value: $(this).val() })
          })
          response = JSON.stringify(response)
          break
        case ('bow_tie'):
          let actionsToTake = this.$refs.bowTieViewer.selectedActionsToTake
          if (actionsToTake.length !== 2) {
            this.$noty.info('Please select 2 Actions To Take.')
            return false
          }
          let potentialCondition = this.$refs.bowTieViewer.selectedPotentialCondition
          if (potentialCondition.length !== 1) {
            this.$noty.info('Please select 1 of the Potential Conditions.')
            return false
          }
          let parametersToMonitor = this.$refs.bowTieViewer.selectedParametersToMonitor
          if (parametersToMonitor.length !== 2) {
            this.$noty.info('Please select 2 Parameters To Monitor.')
            return false
          }
          response = JSON.stringify({
            actionsToTake: actionsToTake,
            potentialConditions: potentialCondition,
            parametersToMonitor: parametersToMonitor
          })
          console.log(response)
          break
        case ('multiple_response_select_n'):
        case ('multiple_response_select_all_that_apply'):
          response = JSON.stringify(this.$refs.multipleResponseSelectAllThatApplyOrSelectNViewer.selectedAllThatApply)
          break
        case ('multiple_response_grouping'):
          response = JSON.stringify(this.$refs.multipleResponseGroupingViewer.selected)
          break
        case ('drop_down_table'):
          response = JSON.stringify(this.$refs.dropDownTableViewer.selected)
          break
        case ('drag_and_drop_cloze'):
          this.$forceUpdate()
          let notSelecteds = []
          let selecteds = []
          let repeats = []
          $('.drop-down-cloze-select').each(function (index) {
            let response = $(this).val()
            if (response) {
              if (selecteds.includes(response)) {
                if (!repeats.includes($(this).text())) {
                  repeats.push($(this).find('option:selected').text())
                }
              }
              selecteds.push(response)
            } else {
              notSelecteds.push(index)
            }
          })
          if (notSelecteds.length) {
            for (let i = 0; i < notSelecteds.length; i++) {
              let message
              let index = notSelecteds[i]
              if (index === 0) {
                message = 'Please make a selection for the 1st dropdown.'
              } else if (index === 1) {
                message = 'Please make a selection for the 2nd dropdown.'
              } else if (index === 2) {
                message = 'Please make a selection for the 3rd dropdown.'
              } else {
                message = `Please make a selection for the ${index}th dropdown.`
              }
              this.$noty.info(message)
            }
            return false
          }
          if (repeats.length) {
            for (let i = 0; i < repeats.length; i++) {
              this.$noty.info(`${repeats[i]} has been chosen multiple times.`)
            }
            return false
          }
          response = JSON.stringify(selecteds)
          break
        case ('matrix_multiple_choice'):
          response = JSON.stringify(this.$refs.matrixMultipleChoiceViewer.selected)
          break
        case ('highlight_table'):
        case ('highlight_text'):
          let responses = []
          $('.selected.response').each(function () {
            responses.push($(this).attr('id'))
          })
          if (!responses.length) {
            this.$noty.info('Please select at least one of the groups of highlighted text.')
            return false
          }
          response = JSON.stringify(responses)
          break
        default:
          alert(`${this.questionType} hasn't been set up as a submission type yet.`)
      }
      if (invalidResponse) {
        this.submissionErrorMessage = submissionErrorMessage
        this.$bvModal.show('modal-submission-error')
        return false
      }
      console.log(response)
      this.$emit('submitResponse', { data: response, origin: 'qti' })
    }

  }
}
</script>
<style scoped>
.inline-form-control {
  width: auto;
  display: inline-block;
}

</style>
