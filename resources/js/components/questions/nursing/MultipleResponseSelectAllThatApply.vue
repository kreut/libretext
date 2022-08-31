<template>
  <div>
    How to collect the correct ones?
    How to actually collect the correct ones?
    I think that getMultipleResponseCorrectResponses() should probably be some other watched values
    {{ qtiJson }}
    {{ getMultipleResponseCorrectResponses() }}
    <b-row>
      <b-col>
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Correct Responses</h2>">
          <b-card-text>
            <b-row v-for="(correctResponse, index) in getMultipleResponseCorrectResponses()"
                   :key="`correct-response-${index}`"
                   class="pb-3"
            >
              <b-col v-if="qtiJson.questionType === 'multiple_response_select_all_that_apply'" sm="2">
                <label>
                  <b-icon-trash scale="1.1"
                                @click="removeSelectAllThatApply(correctResponse.identifier, true)"
                  />
                </label>
              </b-col>
              <b-col>
                <b-textarea v-model="correctResponse.value"
                            rows="2"
                />
              </b-col>
            </b-row>

            <b-button v-if="qtiJson.questionType === 'multiple_response_select_all_that_apply'" class="primary"
                      size="sm" @click="addSelectAllThatApply(true)"
            >
              Add Correct Response
            </b-button>
          </b-card-text>
        </b-card>
      </b-col>
      <b-col>
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Distractors</h2>">
          <b-card-text>
            <b-row v-for="distractor in qtiJson.responses.filter(response => !response.correctResponse)"
                   :key="distractor.identifier"
                   class="pb-3"
            >
              <b-col sm="2">
                <label>
                  <b-icon-trash scale="1.1"
                                @click="removeSelectAllThatApply(distractor.identifier, false)"
                  />
                </label>
              </b-col>
              <b-col>
                <b-textarea v-model="distractor.value"
                            rows="2"
                />
              </b-col>
            </b-row>

            <b-button class="primary" size="sm" @click="addSelectAllThatApply(false)">
              Add Distractor
            </b-button>
          </b-card-text>
        </b-card>
      </b-col>
    </b-row>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'
import { mapGetters } from 'vuex'

export default {
  name: 'MultipleResponseSelectAllThatApply',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    }
  },
  computed: {
    numberToSelect () {
      let isPositiveInteger = false
      let numberToSelect = 1
      if (this.qtiJson.questionType === 'multiple_response_select_n' && this.qtiJson.prompt) {
        const regex = /(\[.*?])/
        let matches = String(this.qtiJson.prompt).split(regex).filter(Boolean)
        for (let i = 0; i < matches.length; i++) {
          let match = matches[i]
          if (match.includes('[') && match.includes(']')) {
            numberToSelect = match.replace('[', '').replace(']', '')
            let n = Math.floor(Number(numberToSelect))
            isPositiveInteger = n !== Infinity && String(n) === numberToSelect && n >= 0
          }
        }
      }
      return isPositiveInteger ? Math.min(numberToSelect, 10) : 1
    }
  },
  methods: {
    getMultipleResponseCorrectResponses () {
      let correctResponses = []
      if (this.qtiJson.questionType === 'multiple_response_select_all_that_apply') {
        correctResponses = this.qtiJson.responses.filter(response => response.correctResponse)
      } else {
        for (let i = 0; i < this.numberToSelect; i++) {
          correctResponses.push({ identifier: uuidv4(), value: '' })
        }
      }
      return correctResponses
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
