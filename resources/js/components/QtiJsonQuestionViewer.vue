<template>
  <div class="p-3">
    <b-modal
      id="modal-submission-error"
      title="Submission Not Accepted"
      size="lg"
      hide-footer
    >
      <b-alert variant="danger" :show="true">
        <span class="font-weight-bold" style="font-size: large">{{ submissionErrorMessage }}</span>
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
          <span v-html="addFillInTheBlanks"/>
        </form>
      </div>
      <div v-if="questionType === 'select_choice'">
        <form class="form-inline">
          <span v-html="addSelectChoices"/>
        </form>
      </div>
      <div v-if="['true_false','multiple_choice', 'multiple_answers'].includes(questionType)">
        <b-form-group style="font-family: Sans-Serif,serif;">
          <template v-slot:label>
            <div style="font-size:18px;">
              <span v-html="prompt"/>
            </div>
          </template>
          <div v-if="['true_false','multiple_choice'].includes(questionType)">
            <div v-for="choice in simpleChoice"
                 :key="choice.identifier"
            >
              <b-form-radio v-model="selectedSimpleChoice"
                            :name="showQtiAnswer ? 'simple-choice-answer' : 'simple-choice'"
                            :value="choice.identifier"
              >
                <span v-html="choice.value"/>
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
                      <span v-html="choice.value"/>
                    </b-form-checkbox>
                    <div v-if="showQtiAnswer" class=" mt-3 text-muted" v-html="choice.feedback"/>
                  </b-card>
                </div>
                <b-form-checkbox :value="choice.identifier" v-if="!showQtiAnswer">
                  <span v-html="choice.value"/>
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
    isMe: () => window.config.isMe,
    ...mapGetters({
      user: 'auth/user'
    }),
    addFillInTheBlanks () {
      if (this.question.itemBody) {
        let reg = /(?<=<u>)(.*?)(?=<\/u>)/g
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
            html += `<select class="identifier-${part} select-choice custom-select form-control inline-form-control"><option value="">Please select an option</option>`
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
        }

        if (this.showQtiAnswer) {
          this.selectedSimpleChoice = this.simpleChoice.find(choice => choice.correctResponse).identifier
        }
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
      default:
        alert(`${this.questionType} is not yet supported.`)
    }
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
