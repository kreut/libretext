<template>
  <div class="p-3">
    <div v-if="matchingFeedback">
      <b-modal
        id="modal-matching-feedback"
        title="Feedback"
        hide-footer
      >
        <span v-html="matchingFeedback" />
      </b-modal>
    </div>
    <b-modal
      id="modal-chosen-matching-terms-repeated-error"
      title="Chosen Matching Terms Repeated"
      size="lg"
      hide-footer
    >
      <b-alert variant="danger" :show="true">
        <span style="font-size: large">{{ doNotRepeatErrorMessage }}</span>
      </b-alert>
    </b-modal>
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
               && ['multiple_choice', 'select_choice','multiple_answers'].includes(questionType)"
             show
             variant="info"
    >
      Students will receive a randomized ordering of possible responses.
    </b-alert>
    <div :id="showQtiAnswer ? 'answer' : 'question'">
      <div v-if="questionType === 'fill_in_the_blank'">
        <form class="form-inline">
          <span v-html="addFillInTheBlanks" />
        </form>
      </div>
      <div v-if="questionType === 'select_choice'">
        <form class="form-inline">
          <span v-html="addSelectChoices" />
        </form>
      </div>
      <div v-if="questionType === 'bow_tie'">
        <b-container>
          <b-row class="text-center" align-v="center">
            <b-col>
              <div class="pb-3">
                <b-card style="background-color:#EDF5F4;">
                  Action To Take
                </b-card>
              </div>
              <b-card>Action To Take</b-card>
            </b-col>
            <b-col>
              <b-card>Condition Most Likely Experiencing</b-card>
            </b-col>
            <b-col>
              <div class="pb-3">
                <b-card>Parameter To Monitor</b-card>
              </div>
              <b-card>Parameter To Monitor</b-card>
            </b-col>
          </b-row>
        </b-container>
      </div>

      <div
        v-if="['matching',
               'true_false',
               'multiple_choice',
               'multiple_answers',
               'numerical',
               'multiple_response_select_all_that_apply',
               'multiple_response_select_n',
               'matrix_multiple_response'].includes(questionType)"
      >
        <b-form-group style="font-family: Sans-Serif,serif;">
          <template v-slot:label>
            <div style="font-size:18px;">
              <span v-html="prompt" />
            </div>
          </template>
          <div v-if="questionType === 'matrix_multiple_response'">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th v-for="(header, headerIndex) in question.headers"
                      :key="`matrix-multiple-response-header-${headerIndex}`" scope="col"
                  >
                    {{ header }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, rowIndex) in question.rows" :key="`matrix-multiple-response-row-${rowIndex}`">
                  <th>{{ row[0] }}</th>
                  <td v-for="(column, colIndex) in row.slice(1)" :key="`matrix-multiple-response-row-${colIndex}`">
                    {{ column }}
                    <b-form-checkbox :value="column" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="['multiple_response_select_n','multiple_response_select_all_that_apply'].includes(questionType)">
            <b-form-group>
              <b-form-checkbox-group
                v-model="selectedAllThatApply"
                :options="selectAllThatApplyOptions"
                name="select-all-that-apply"
                stacked
              />
            </b-form-group>
          </div>
          <div v-if="questionType === 'numerical'">
            <b-input v-if="!showQtiAnswer" v-model="numericalResponse"
                     placeholder="Please enter your response."
                     :class="numericalResponseInputClass"
                     style="width:300px"
            />
            <span v-if="showQtiAnswer" style="font-size:18px;">{{ question.correctResponse.value }} <span
              v-if="parseFloat(question.correctResponse.marginOfError)>0"
            >
              (Responses between {{
                parseFloat(question.correctResponse.value) - parseFloat(question.correctResponse.marginOfError)
              }}
              and {{
                parseFloat(question.correctResponse.value) + parseFloat(question.correctResponse.marginOfError)
              }} will be marked as correct.)
            </span>
            </span>
            <hr v-if="user.role=== 2" class="p-2">
            <b-card
              v-if="(question.feedback['any'] || question.feedback['correct'] || question.feedback['incorrect'] ) && (studentResponse || user.role === 2)"
              class="mt-2"
            >
              <template #header>
                <span class="ml-2 h7">Feedback</span>
              </template>
              <b-card-text>
                <ul style="list-style:none;" class="pl-0">
                  <li v-if="question.feedback['any']">
                    <span v-if="user.role === 2" class="font-weight-bold">Any response </span> <span
                      v-html="question.feedback['any']"
                    />
                  </li>
                  <li v-if="(answeredNumericalCorrectly || user.role=== 2) && question.feedback['correct']">
                    <span v-if="user.role === 2" class="font-weight-bold">Correct response </span><span
                      v-html="question.feedback['correct']"
                    />
                  </li>
                  <li v-if="(!answeredNumericalCorrectly || user.role=== 2) && question.feedback['incorrect']">
                    <span v-if="user.role === 2" class="font-weight-bold">Incorrect response </span><span
                      v-html="question.feedback['incorrect']"
                    />
                  </li>
                </ul>
              </b-card-text>
            </b-card>
          </div>
          <div v-if="questionType === 'matching'">
            <table id="matching-table" class="table table-striped">
              <thead>
                <tr>
                  <th scope="col">
                    Term to match
                  </th>
                  <th scope="col">
                    <div v-if="showQtiAnswer">
                      Correct matching term
                    </div>
                    <div v-else>
                      Chosen match
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in termsToMatch" :key="`matching-answer-${item.identifier}`">
                  <th scope="row">
                    <span v-html="item.termToMatch" />
                  </th>
                  <td>
                    <div v-if="showQtiAnswer">
                      <span v-html="getChosenMatch(item)" />
                      <span v-if="item.feedback" @click="showFeedback( item.feedback)"><QuestionCircleTooltip /></span>
                    </div>
                    <div v-if="!showQtiAnswer">
                      <b-dropdown :id="`matching-answer-${item.identifier}`"
                                  :html="getChosenMatch(item)"
                                  class="matching-dropdown m-md-2"
                                  no-flip
                                  :variant="item.chosenMatchIdentifier === null ? 'secondary' : 'info'"
                      >
                        <b-dropdown-item v-for="possibleMatch in nonNullPossibleMatches"
                                         :id="`dropdown-${possibleMatch.identifier}`"
                                         :key="`possible-match-${possibleMatch.identifier}`"
                                         style="overflow-x:auto;overflow-y:auto"
                                         @click="updateChosenMatch(item, possibleMatch)"
                        >
                          <span v-html="possibleMatch.matchingTerm" />
                        </b-dropdown-item>
                      </b-dropdown>
                      <input type="hidden" class="form-control is-invalid">
                      <div class="help-block invalid-feedback">
                        {{ item.errorMessage }}
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="['true_false','multiple_choice'].includes(questionType)">
            <div v-for="choice in simpleChoice"
                 :key="`identifier-${choice.identifier}-student-response-${studentResponse}`"
            >
              <div v-if="choice.chosenStudentResponse" class="mb-2">
                <b-icon-check-circle-fill v-if="choice.correctResponse"
                                          class="text-success"
                />
                <b-icon-x-circle-fill v-if="!choice.correctResponse"
                                      class="text-danger"
                />
                <span class="multiple-choice-responses" v-html="choice.value" />

                <span v-if="question.feedback && question.feedback[choice.identifier]">
                  <span @click="showFeedback( question.feedback[choice.identifier])"><QuestionCircleTooltip
                    :color="'text-danger'"
                  /></span>
                </span>
              </div>
              <b-form-radio v-if="!choice.chosenStudentResponse"
                            v-model="selectedSimpleChoice"
                            :name="showQtiAnswer ? 'simple-choice-answer' : 'simple-choice'"
                            :value="choice.identifier"
              >
                <span v-html="choice.value" />
              </b-form-radio>
            </div>
          </div>

          <div v-if="questionType === 'multiple_answers'">
            <b-form-checkbox-group
              v-model="selectedMultipleAnswers"
              :name="showQtiAnswer ? 'multiple-answers-answer' : 'multiple-answers'"
            >
              <div v-for="choice in simpleChoice"
                   :key="`multiple_answers_${choice.identifier}`"
                   :class="{ 'pb-3': showQtiAnswer }"
              >
                <div v-if="showQtiAnswer">
                  <b-card :border-variant="choice.correctResponse ? 'success' : 'danger'">
                    <b-form-checkbox :value="choice.identifier">
                      <span v-html="choice.value" />
                    </b-form-checkbox>
                    <div v-if="showQtiAnswer" class=" mt-3 text-muted" v-html="choice.feedback" />
                  </b-card>
                </div>
                <b-form-checkbox v-if="!showQtiAnswer" :value="choice.identifier">
                  <span v-html="choice.value" />
                </b-form-checkbox>
              </div>
            </b-form-checkbox-group>
          </div>
        </b-form-group>
      </div>
    </div>
    <b-button v-if="showSubmit"
              variant="primary"
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
    <div
      v-if="questionType === 'multiple_choice' && studentResponse && question.feedback && (question.feedback['any'] || question.feedback['correct'] || question.feedback['incorrect'] ) "
      class="mt-2"
    >
      <hr>
      <b-card>
        <template #header>
          <span class="ml-2 h7">Feedback</span>
        </template>
        <b-card-text>
          <div v-if="question.feedback['any']">
            <span v-html="question.feedback['any']" />
          </div>
          <div v-if="answeredSimpleChoiceCorrectly && question.feedback['correct']">
            <span v-html="question.feedback['correct']" />
          </div>
          <div v-if="!answeredSimpleChoiceCorrectly && question.feedback['incorrect']">
            <span v-html="question.feedback['incorrect']" />
          </div>
        </b-card-text>
      </b-card>
    </div>
  </div>
</template>

<script>
import $ from 'jquery'
import { mapGetters } from 'vuex'

export default {
  name: 'QtiJsonQuestionViewer',
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
    showQtiAnswer: {
      type: Boolean,
      default: false
    }
  },
  data: () => ({
    selectedAllThatApply: [],
    selectAllThatApplyOptions: [],
    answeredNumericalCorrectly: false,
    numericalResponse: '',
    answeredSimpleChoiceCorrectly: false,
    doNotRepeatErrorMessage: 'Each matching term should only be chosen once.',
    matchingFeedback: '',
    termsToMatch: [],
    possibleMatches: [],
    jsonShown: false,
    submissionErrorMessage: '',
    questionType: '',
    selectedMultipleAnswers: [],
    selectedSimpleChoice: null,
    selectChoices: [],
    question: {},
    prompt: '',
    simpleChoice: []
  }
  ),
  computed: {
    numericalResponseInputClass () {
      if (this.studentResponse) {
        return !this.answeredNumericalCorrectly ? 'is-invalid-border' : 'success-border'
      }
      return ''
    },
    nonNullPossibleMatches () {
      return this.possibleMatches.filter(possibleMatch => possibleMatch.identifier !== null)
    },
    isMe: () => window.config.isMe,
    ...mapGetters({
      user: 'auth/user'
    }),
    addFillInTheBlanks () {
      if (this.question.itemBody) {
        const reg = /(<u>.*?<\/u>)/
        let fillInTheBlankArray = this.question.itemBody.textEntryInteraction.split(reg)
        console.log(fillInTheBlankArray)
        let html = ''
        let responseIndex = 1
        for (let i = 0; i < fillInTheBlankArray.length; i++) {
          let part = fillInTheBlankArray[i]
          if (i % 2 === 0) {
            html += part.replace('<u>', '').replace('</u>', '')
          } else {
            html += `<input  type="text" class="response_${responseIndex} fill-in-the-blank form-control form-control-sm"/>`
            responseIndex++
          }
        }
        return html
      }
    },
    addSelectChoices () {
      if (this.question.itemBody) {
        let reg = /\[(.*?)\]/g
        let selectChoicesArray = this.question.itemBody.split(reg)
        let html = ''
        for (let i = 0; i < selectChoicesArray.length; i++) {
          let part = selectChoicesArray[i]
          if (i % 2 === 0) {
            html += part
          } else {
            html += `<select style="margin:3px"
class="identifier-${part} select-choice custom-select custom-select-sm form-control inline-form-control"
aria-label="combobox ${Math.ceil(i / 2)} of ${Math.floor(selectChoicesArray.length / 2)}">
<option value="">Please select an option</option>`
            for (let i = 0; i < this.question.inline_choice_interactions[part].length; i++) {
              let selectChoice = this.question.inline_choice_interactions[part][i]
              html += `<option value="${selectChoice.value}">${selectChoice.text}</option>`
            }
            html += '</select>'
          }
        }
        return html
      } else {
        return []
      }
    }
  },
  mounted () {
    $(document).on('change', 'select.select-choice', function () {
      $(this).removeClass('is-invalid-border')
    })
    $(document).on('keydown', 'input.fill-in-the-blank', function () {
      $(this).removeClass('is-invalid-border')
    })
    this.question = JSON.parse(this.qtiJson)
    this.questionType = this.question.questionType
    console.log(this.question)
    switch (this.questionType) {
      case ('numerical'):
        this.prompt = this.question['prompt']
        if (this.studentResponse) {
          this.numericalResponse = this.studentResponse
          this.answeredNumericalCorrectly = this.question.answeredCorrectly
        }
        break
      case ('matching') :
        this.prompt = this.question['prompt']
        this.termsToMatch = this.question.termsToMatch
        this.possibleMatches = this.question.possibleMatches
        let html
        let chooseMatchMessage = 'Choose a match'
        for (let i = 0; i < this.possibleMatches.length; i++) {
          let possibleMatch = this.possibleMatches[i]
          html = html = $.parseHTML(possibleMatch.matchingTerm)
          if ($(html).find('img').length) {
            chooseMatchMessage = 'Choose a match from the images below'
            $(html).find('img').each(function () {
              $(this).attr('width', '')
              $(this).attr('height', '')
              possibleMatch.matchingTerm = $(this).prop('outerHTML')
            })
          }
        }
        this.possibleMatches.push({ identifier: null, matchingTerm: chooseMatchMessage })
        for (let i = 0; i < this.termsToMatch.length; i++) {
          this.termsToMatch[i].chosenMatchIdentifier = this.showQtiAnswer
            ? this.termsToMatch[i].matchingTermIdentifier
            : null
          this.termsToMatch[i].errorMessage = ''
        }
        break
      case ('multiple_answers'):
        this.prompt = this.question['prompt']
        this.simpleChoice = this.question.simpleChoice
        console.log(this.simpleChoice)
        if (this.showQtiAnswer) {
          console.log(this.selectedMultipleAnswers)
          for (let i = 0; i < this.simpleChoice.length; i++) {
            if (this.simpleChoice[i].correctResponse) {
              this.selectedMultipleAnswers.push(this.simpleChoice[i].identifier)
            }
          }
        }
        if (this.studentResponse) {
          this.selectedMultipleAnswers = JSON.parse(this.studentResponse)
          console.log(this.selectedMultipleAnswers)
        }
        break
      case ('multiple_choice'):
      case ('true_false'):
        this.prompt = this.question['prompt']
        this.simpleChoice = this.question.simpleChoice
        if (this.studentResponse) {
          this.selectedSimpleChoice = this.studentResponse
          for (let i = 0; i < this.simpleChoice.length; i++) {
            let simpleChoice = this.simpleChoice[i]
            simpleChoice.chosenStudentResponse = this.selectedSimpleChoice === simpleChoice.identifier
            simpleChoice.answeredCorrectly = simpleChoice.chosenStudentResponse && simpleChoice.correctResponse
            if (simpleChoice.answeredCorrectly) {
              this.answeredSimpleChoiceCorrectly = true
            }
          }
        }

        if (this.showQtiAnswer) {
          this.selectedSimpleChoice = this.simpleChoice.find(choice => choice.correctResponse).identifier
        }
        this.$nextTick(() => {
          $('.multiple-choice-responses > p').contents().unwrap() // remove paragraphs for formatting purposes
        })

        break
      case ('select_choice'):
        this.$nextTick(() => {
          if (this.studentResponse) {
            let studentResponse = JSON.parse(this.studentResponse)
            console.log(studentResponse)
            $('#question').find('select').each(function (i) {
              $(this).val(studentResponse[i].value).change()
            })
          }
          if (this.showQtiAnswer) {
            for (const identifier in this.question.inline_choice_interactions) {
              let correctResponse = this.question.inline_choice_interactions[identifier].find(choice => choice.correctResponse).value
              $('#answer').find('.identifier-' + identifier).val(correctResponse).change()
            }
          }
        })
        break
      case ('fill_in_the_blank'):
        this.$nextTick(() => {
          if (this.studentResponse) {
            let studentResponse = JSON.parse(this.studentResponse)
            console.log(studentResponse)
            for (let i = 0; i < studentResponse.length; i++) {
              $(' #question').find(`.response_${i + 1}`).val(studentResponse[i].value)
            }
          }
          if (this.showQtiAnswer) {
            for (let i = 0; i < this.question.responseDeclaration.correctResponse.length; i++) {
              let correctResponse = this.question.responseDeclaration.correctResponse[i]
              $('#answer').find(`.response_${i + 1}`).val(correctResponse.value)
            }
          }
        })
        break
      case ('bow_tie'): {
        break
      }
      case ('multiple_response_select_n'):
      case ('multiple_response_select_all_that_apply'): {
        this.prompt = this.question['prompt']
        for (let i = 0; i < this.question.responses.length; i++) {
          let response = this.question.responses[i]
          this.selectAllThatApplyOptions.push({ text: response.value, value: response.identifier })
        }
        break
      }
      case ('matrix_multiple_response'):
        this.prompt = this.question['prompt']
        break
      default:
        alert(`${this.questionType} is not yet supported.`)
    }
    this.$nextTick(() => {
      MathJax.Hub.Queue(['Typeset', MathJax.Hub])
    })
  },
  methods: {
    showFeedback (feedback) {
      this.matchingFeedback = feedback
      this.$nextTick(() => {
        this.$bvModal.show('modal-matching-feedback')
      })
    },
    validateNoChosenMatchRepeats () {
      let chosenMatchIdentifiers = []
      let valid = true
      for (let i = 0; i < this.termsToMatch.length; i++) {
        let chosenMatchIdentifier = this.termsToMatch[i].chosenMatchIdentifier
        if (chosenMatchIdentifier !== null && chosenMatchIdentifiers.includes(chosenMatchIdentifier)) {
          this.termsToMatch[i].errorMessage = this.doNotRepeatErrorMessage
          valid = false
        } else {
          chosenMatchIdentifiers.push(chosenMatchIdentifier)
        }
      }
      return valid
    },
    updateChosenMatch (item, possibleMatch) {
      item.errorMessage = ''
      item.chosenMatchIdentifier = possibleMatch.identifier
      if (!this.validateNoChosenMatchRepeats()) {
        this.$bvModal.show('modal-chosen-matching-terms-repeated-error')
      }
      this.$forceUpdate()
      this.$nextTick(() => {
        $(`#matching-answer-${item.identifier}`).find('.dropdown-toggle')
          .removeClass('dropdown-toggle')
          .css('border-radius', '4px')
      })
    },
    getChosenMatch (item) {
      return this.possibleMatches.find(possibleMatch => possibleMatch.identifier === item.chosenMatchIdentifier).matchingTerm
    },
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
          response = this.numericalResponse
          break
        case ('matching'):
          let chosenMatchIdentifier
          let chosenMatches = []
          for (let i = 0; i < this.termsToMatch.length; i++) {
            chosenMatchIdentifier = this.termsToMatch[i].chosenMatchIdentifier
            chosenMatches.push({
              term_to_match_identifier: this.termsToMatch[i].identifier,
              chosen_match_identifier: chosenMatchIdentifier
            })
            if (chosenMatchIdentifier === null) {
              invalidResponse = true
              submissionErrorMessage = 'Please choose matches for all terms before submitting.'
              this.termsToMatch[i].errorMessage = submissionErrorMessage
            }
          }
          if (!invalidResponse) {
            if (!this.validateNoChosenMatchRepeats()) {
              invalidResponse = true
              submissionErrorMessage = this.doNotRepeatErrorMessage
            }
          }
          response = JSON.stringify(chosenMatches)
          break
        case ('multiple_choice'):
        case ('true_false'):
          response = this.selectedSimpleChoice
          if ((response === null || response === '')) {
            invalidResponse = true
          }
          submissionErrorMessage = 'Please make a selection before submitting.'
          break
        case ('multiple_answers'):
          response = JSON.stringify(this.selectedMultipleAnswers)
          if (response === '[]') {
            invalidResponse = true
          }
          submissionErrorMessage = 'Please make at least one selection before submitting.'
          break
        case ('select_choice'):
          response = []
          $('select.select-choice').each(function () {
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
        default:
          alert('Not a valid submission type')
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
