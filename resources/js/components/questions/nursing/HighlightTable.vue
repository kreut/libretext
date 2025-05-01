<template>
  <div class="pb-3">
    <table class="table table-striped">
      <thead class="nurses-table-header">
        <tr>
          <th v-for="(header,colIndex) in qtiJson.colHeaders" :key="`drop-down-table-header-${colIndex}`"
              scope="col"
          >
            <b-form-input
              v-model="qtiJson.colHeaders[colIndex]"
              type="text"
              :placeholder="`Column Header ${colIndex+1}`"
              @input="clearErrors('colHeaders', colIndex)"
            />
            <ErrorMessage v-if="questionForm.errors.get('colHeaders')
                            && JSON.parse(questionForm.errors.get('colHeaders'))[colIndex]"
                          :message="JSON.parse(questionForm.errors.get('colHeaders'))[colIndex]"
            />
          </th>
        </tr>
      </thead>
      <tbody>
        <template v-for="(row,rowIndex) in qtiJson.rows">
          <tr :key="`table-row-${rowIndex}-1`">
            <td>
              <b-input-group>
                <b-form-input
                  v-model="qtiJson.rows[rowIndex].header"
                  type="text"
                  :placeholder="`Row ${rowIndex+1}`"
                  @input="clearErrors('rows', rowIndex, 'header')"
                />
                <b-input-group-append>
                  <b-input-group-text>
                    <b-icon-trash
                      @click="deleteRow(rowIndex)"
                    />
                  </b-input-group-text>
                </b-input-group-append>
              </b-input-group>
              <ErrorMessage v-if="questionForm.errors.get('rows')
                              && JSON.parse(questionForm.errors.get('rows'))[rowIndex]
                              && JSON.parse(questionForm.errors.get('rows'))[rowIndex]['header']"
                            :message="JSON.parse(questionForm.errors.get('rows'))[rowIndex]['header']"
              />
            </td>
            <td>
              <b-form-textarea
                :id="`text-area-${rowIndex}`"
                v-model="qtiJson.rows[rowIndex].prompt"
                placeholder="Enter something..."
                rows="3"
                max-rows="6"
                @input="clearErrors('rows', rowIndex, 'responses','text')"
              />
              <ErrorMessage v-if="questionForm.errors.get('rows')
                              && JSON.parse(questionForm.errors.get('rows'))[rowIndex]
                              && JSON.parse(questionForm.errors.get('rows'))[rowIndex]['responses']
                              && JSON.parse(questionForm.errors.get('rows'))[rowIndex]['responses']['text']"
                            :message="JSON.parse(questionForm.errors.get('rows'))[rowIndex]['responses']['text']"
              />
            </td>
          </tr>
          <tr :key="`table-row-${rowIndex}-2`">
            <td colspan="2">
              <div v-if="responses[rowIndex] && responses[rowIndex].length">
                <ol>
                  <li v-for="(response,responseIndex) in responses[rowIndex]"
                      :key="`correct-response-${rowIndex}-${responseIndex}`"
                  >
                    <b-form-group>
                      <b-form-radio-group v-model="response.correctResponse"
                                          @input="updateResponse();clearErrors('rows', rowIndex, 'responses','correctResponse')"
                      >
                        {{ response.text }}
                        <b-form-radio :value="true">
                          Correct Response
                        </b-form-radio>
                        <b-form-radio :value="false">
                          Distractor
                        </b-form-radio>
                      </b-form-radio-group>
                      <ErrorMessage v-if="questionForm.errors.get('rows')
                                      && JSON.parse(questionForm.errors.get('rows'))[rowIndex]
                                      && JSON.parse(questionForm.errors.get('rows'))[rowIndex]['responses']
                                      && JSON.parse(questionForm.errors.get('rows'))[rowIndex]['responses'][responseIndex]
                                      && JSON.parse(questionForm.errors.get('rows'))[rowIndex]['responses'][responseIndex]['text']"
                                    :message="JSON.parse(questionForm.errors.get('rows'))[rowIndex]['responses'][responseIndex]['text']"
                      />
                      <ErrorMessage v-if="questionForm.errors.get('rows')
                                      && JSON.parse(questionForm.errors.get('rows'))[rowIndex]
                                      && JSON.parse(questionForm.errors.get('rows'))[rowIndex]['responses']
                                      && JSON.parse(questionForm.errors.get('rows'))[rowIndex]['responses'][responseIndex]
                                      && JSON.parse(questionForm.errors.get('rows'))[rowIndex]['responses'][responseIndex]['correctResponse']"
                                    :message="JSON.parse(questionForm.errors.get('rows'))[rowIndex]['responses'][responseIndex]['correctResponse']"
                      />
                    </b-form-group>
                  </li>
                </ol>
              </div>
              <div v-if="!(responses && responses.length)">
                <b-alert varint="info" show>
                  You currently have no responses for Row {{ rowIndex + 1 }}.
                </b-alert>
              </div>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
    <b-button class="primary" size="sm" @click="addRow">
      Add row
    </b-button>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'
import ErrorMessage from '~/components/ErrorMessage'

export default {
  name: 'HighlightTable',
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
      let responses = []
      if (this.qtiJson.rows) {
        for (let i = 0; i < this.qtiJson.rows.length; i++) {
          let row = this.qtiJson.rows[i]
          let rowCurrentResponses = this.currentResponses[i] ? this.currentResponses[i] : []
          let rowResponse = []

          const regex = /(\[.*?])/
          let matches = String(row.prompt).split(regex).filter(Boolean)
          if (matches && matches.length) {
            for (let j = 0; j < matches.length; j++) {
              let match = matches[j]
              if (match.includes('[') && match.includes(']')) {
                let text = match.replace('[', '').replace(']', '')
                let currentResponse = rowCurrentResponses.find(response => response.text === text)
                let correctResponse = currentResponse ? currentResponse.correctResponse : null
                let identifier = currentResponse ? currentResponse.identifier : uuidv4()
                rowResponse.push({ text: text, correctResponse: correctResponse, identifier: identifier })
              }
            }
            responses.push(rowResponse)
          }
        }
        console.log(responses)
        if (responses.length && this.questionForm.qti_json) {
          let questionFormRows = JSON.parse(this.questionForm.qti_json).rows
          for (let i = 0; i < questionFormRows.length; i++) {
            let questionFormRow = questionFormRows[i]
            for (let j = 0; j < questionFormRow.responses.length; j++) {
              let questionFormRowResponse = questionFormRow.responses[j]
              let response = responses[i].find(response => response.text === questionFormRowResponse.text)
              if (response) {
                response.correctResponse = questionFormRowResponse.correctResponse
              }
            }
          }
        }
        return responses
      } else {
        return []
      }
    }
  },
  watch: {
    responses: function (responses) {
      if (responses.length) {
        for (let i = 0; i < this.qtiJson.rows.length; i++) {
          this.qtiJson.rows[i].responses = responses[i]
        }
      }
    }
  },
  methods: {
    clearErrors (key, index, type1, type2) {
      let errors = this.questionForm.errors.get(key)
      errors = JSON.parse(errors)
      switch (key) {
        case ('colHeaders'):
          delete errors[index]
          break
        case ('rows'):
          switch (type1) {
            case ('header'):
              delete errors[index]['header']
              break
            case ('responses'):
              delete errors[index]['responses'][type2]

              break
          }
          console.error(errors)
          break
        default:
          alert(`The error key ${key} does not exist.`)
      }
      this.questionForm.errors.set(key, JSON.stringify(errors))
    },
    deleteRow (rowIndex) {
      if (this.qtiJson.rows.length === 1) {
        this.$noty.info('You need at least one row.')
      } else {
        this.currentResponses.splice(rowIndex, 1)
      }
      this.qtiJson.rows.splice(rowIndex, 1)
    },
    addRow () {
      this.qtiJson.rows.push({
        header: '',
        prompt: '',
        responses: []
      })
    },
    updateResponse () {
      this.currentResponses = this.responses
      this.$forceUpdate()
    }
  }
}
</script>
