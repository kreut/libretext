<template>
  <div>
    {{ qtiJson }}
    <div v-if="numberDoesNotExistInPrompt()">
      <b-alert :show="true" variant="info">
        Currently there is no bracketed number in the text that matches the number of Correct Responses.
      </b-alert>
    </div>
      <div class="pb-3">
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Correct Responses</h2>">
          <b-card-text>
            <ErrorMessage v-if="questionForm.errors.get('responses')"
                          :message="JSON.parse(questionForm.errors.get('responses'))['general']"
                          class="pb-2"
            />
            <div v-for="(correctResponse, index) in qtiJson.responses.filter(response => response.correctResponse)"
                 :key="`correct-response-${index}`"
                 class="pb-3"
            >
              <b-input-group>
                <b-form-input v-model="correctResponse.value" />
                <b-input-group-append>
                  <b-input-group-text>
                    <b-icon-trash
                      @click="removeSelectAllThatApply(correctResponse.identifier, true)"
                    />
                  </b-input-group-text>
                </b-input-group-append>
              </b-input-group>
              <ErrorMessage v-if="questionForm.errors.get('responses')
                              && JSON.parse(questionForm.errors.get('responses'))['specific']"
                            :message="JSON.parse(questionForm.errors.get('responses'))['specific'][correctResponse.identifier]"
              />
            </div>

            <b-button class="primary"
                      size="sm" @click="addSelectAllThatApply(true)"
            >
              Add Correct Response
            </b-button>
          </b-card-text>
        </b-card>
      </div>
      <div class="pb-3">
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Distractors</h2>">
          <b-card-text>
            <div v-for="distractor in qtiJson.responses.filter(response => !response.correctResponse)"
                 :key="distractor.identifier"
                 class="pb-3"
            >
              <b-input-group>
                <b-form-input v-model="distractor.value" />
                <b-input-group-append>
                  <b-input-group-text>
                    <b-icon-trash
                      @click="removeSelectAllThatApply(distractor.identifier, false)"
                    />
                  </b-input-group-text>
                </b-input-group-append>
              </b-input-group>

              <ErrorMessage v-if="questionForm.errors.get('responses')
                              && JSON.parse(questionForm.errors.get('responses'))['specific']"
                            :message="JSON.parse(questionForm.errors.get('responses'))['specific'][distractor.identifier]"
              />
            </div>

            <b-button class="primary" size="sm" @click="addSelectAllThatApply(false)">
              Add Distractor
            </b-button>
          </b-card-text>
        </b-card>
      </div>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'
import ErrorMessage from '~/components/ErrorMessage'

export default {
  name: 'MultipleResponseSelectAllThatApplyOrSelectN',
  components: { ErrorMessage },
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
  methods: {
    numberDoesNotExistInPrompt () {
      let correctResponses = this.qtiJson.responses.filter(response => response.correctResponse)
      return this.qtiJson.questionType === 'multiple_response_select_n' &&
        this.qtiJson.prompt &&
        correctResponses &&
        this.qtiJson.prompt.search(correctResponses.length) === -1
    },
    addSelectAllThatApply (correctResponse) {
      this.qtiJson.responses.push({ identifier: uuidv4(), value: '', correctResponse: correctResponse })
    },
    removeSelectAllThatApply (identifier, correctResponse) {
      if (this.qtiJson.responses.filter(response => response.correctResponse === correctResponse).length === 1) {
        let responseType = correctResponse ? 'Correct Response' : 'Distractor'
        this.$noty.info(`You need at least one ${responseType}.`)
        return false
      }
      this.qtiJson.responses = this.qtiJson.responses.filter(response => response.identifier !== identifier)
    }
  }
}
</script>
