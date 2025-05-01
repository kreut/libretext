<template>
  <div class="pb-3">
    <ErrorMessage v-if="repeatedTextError" :message="repeatedTextErrorMessage"/>
    <b-card header-html="<h2 class=&quot;h7&quot;>Responses</h2>">
      <b-card-text>
        <div v-if="responses && responses.length">
          <ol>
            <li v-for="(response,index) in responses" :key="`correct-response-${index}`">
              <b-form-group>
                <b-form-radio-group v-model="response.correctResponse"
                                    @input="updateResponse($event)">
                  <span v-html="response.text"/>
                  <b-form-radio :value="true">
                    Correct Response
                  </b-form-radio>
                  <b-form-radio :value="false">
                    Distractor
                  </b-form-radio>
                  <ErrorMessage v-if="questionForm.errors.get('responses')"
                                :message="JSON.parse(questionForm.errors.get('responses'))[response.identifier]"
                  />
                </b-form-radio-group>
              </b-form-group>
            </li>
          </ol>
        </div>
        <div v-if="!(responses && responses.length)">
          <b-alert varint="info" show>
            You currently have no responses.
          </b-alert>
        </div>
      </b-card-text>
    </b-card>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'
import ErrorMessage from '~/components/ErrorMessage'

export default {
  components: {
    ErrorMessage
  },
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    },
    questionForm: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    currentResponses: [],
    repeatedTextError: false,
    repeatedTextErrorMessage: ''
  }),
  computed: {
    responses () {
      if (this.qtiJson.prompt) {
        console.log(this.currentResponses)
        const regex = /(\[.*?])/
        let matches = String(this.qtiJson.prompt).split(regex).filter(Boolean)
        let responses = []
        if (matches && matches.length) {
          for (let i = 0; i < matches.length; i++) {
            let match = matches[i]
            if (match.includes('[') && match.includes(']')) {
              let text = match.replace('[', '').replace(']', '')
              // not sure why but quotes are being replaced.  Maybe CKeditor? See storing the question serverside and HighlightText.vue
              text = text.replaceAll('&quot;', '"').replaceAll('&#39;', "'")
              let currentResponse = this.currentResponses.find(response => response.text === text)
              let correctResponse = currentResponse ? currentResponse.correctResponse : null
              let identifier = currentResponse ? currentResponse.identifier : uuidv4()
              responses.push({ text: text, correctResponse: correctResponse, identifier: identifier })
            }
          }
        }
        if (!responses.length) {
          responses = null
        }
        return responses
      } else {
        return []
      }
    }
  },
  watch: {
    responses: function (responses) {
      this.qtiJson.responses = responses
      this.repeatedTextErrorMessage = ''
      this.repeatedTextError = false
      if (responses) {
        let texts = []
        for (let i = 0; i < responses.length; i++) {
          let text = responses[i].text
          if (texts.includes(text)) {
            this.repeatedTextError = true
            this.repeatedTextErrorMessage = `You have repeated the text "${text}" more than once.  Each highlighted text item should appear only once.`
          } else {
            texts.push(text)
          }
        }
      }
    }
  },
  mounted () {
    this.$nextTick(() => {
      this.$forceUpdate()
      if (this.questionForm.qti_json) {
        let questionFormResponses = JSON.parse(this.questionForm.qti_json).responses
        console.log(this.responses)
        for (let i = 0; i < questionFormResponses.length; i++) {
          console.log(questionFormResponses[i].text)
          let promptResponse = this.responses.find(response => response.text === questionFormResponses[i].text)
          if (promptResponse) {
            promptResponse.correctResponse = questionFormResponses[i].correctResponse
          }
        }
      }
    })
  },
  methods: {
    updateResponse () {
      this.currentResponses = this.responses
      this.questionForm.errors.clear('responses')
      this.$forceUpdate()
      console.log(this.responses)
    }
  }
}
</script>
