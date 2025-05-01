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
              @input="clearErrors('colHeaders', false,colIndex)"
            />

            <ErrorMessage v-if="questionForm.errors.get('colHeaders')
                            && JSON.parse(questionForm.errors.get('colHeaders'))['specific']
                            && JSON.parse(questionForm.errors.get('colHeaders'))['specific'][colIndex]"
                          :message="JSON.parse(questionForm.errors.get('colHeaders'))['specific'][colIndex]"
            />
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row,rowIndex) in qtiJson.rows" :key="`drop-down-table-${rowIndex}`">
          <td>
            <b-input-group>
              <b-form-input
                v-model="qtiJson.rows[rowIndex].header"
                type="text"
                :placeholder="`Row ${rowIndex+1}`"
                @input="clearErrors('rows', false,rowIndex, 'header')"
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
                            && JSON.parse(questionForm.errors.get('rows'))['specific']
                            && JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]
                            && JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]['header']"
                          :message="JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]['header']"
            />
          </td>
          <td>
            <div v-for="(response,responseIndex) in qtiJson.rows[rowIndex].responses"
                 :key="`drop-table-responses-${response.identifier}`"
            >
              <b-form-row v-if="responseIndex ===0" class="pb-2">
                <b-input-group>
                  <b-input-group-prepend>
                    <b-button
                      class="text-success"
                      variant="outline-secondary"
                    >
                      <b-icon-check scale="1.5" />
                    </b-button>
                  </b-input-group-prepend>
                  <b-form-input
                    v-model="qtiJson.rows[rowIndex]['responses'][responseIndex].value"
                    placeholder="Correct Response"
                    class="text-success"
                    @input="clearErrors('rows', false,rowIndex,response.identifier);clearErrors('rows', true)"
                  />
                </b-input-group>
              </b-form-row>

              <ErrorMessage
                v-if="questionForm.errors.get('rows')
                  && JSON.parse(questionForm.errors.get('rows'))['specific']
                  && JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]
                  && JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex][response.identifier]"
                :message="JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex][response.identifier]"
              />

              <ErrorMessage v-if="questionForm.errors.get('rows')
                              && JSON.parse(questionForm.errors.get('rows'))['general']
                              && responseIndex === 0"
                            :message="JSON.parse(questionForm.errors.get('rows'))['general']"
              />

              <b-form-row v-if="responseIndex >0" class="pb-2">
                <b-input-group>
                  <b-input-group-prepend>
                    <b-button
                      class="font-weight-bold text-danger"
                      variant="outline-secondary"
                      style="width:46px"
                    >
                      X
                    </b-button>
                  </b-input-group-prepend>
                  <b-form-input v-model="qtiJson.rows[rowIndex]['responses'][responseIndex].value"
                                :placeholder="`Distractor ${responseIndex}`"
                                class="text-danger"
                                @input="clearErrors('rows', false,rowIndex,response.identifier);"
                  />
                  <b-input-group-append>
                    <b-input-group-text>
                      <b-icon-trash
                        @click="deleteDistractor(rowIndex, qtiJson.rows[rowIndex]['responses'][responseIndex].identifier)"
                      />
                    </b-input-group-text>
                  </b-input-group-append>
                </b-input-group>
                <ErrorMessage v-if="questionForm.errors.get('rows')
                                && JSON.parse(questionForm.errors.get('rows'))['specific']
                                && JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]
                                && JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex][response.identifier]"
                              :message="JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex][response.identifier]"
                />
              </b-form-row>
            </div>
            <div class="pt-2">
              <b-button size="sm" @click="addDistractor(rowIndex)">
                Add Distractor
              </b-button>
              <QuestionCircleTooltip
                :id="`row-distractor-${rowIndex}`"
              />
              <b-tooltip :target="`row-distractor-${rowIndex}`"
                         delay="250"
                         triggers="hover focus"
              >
                Add at least one distractor (incorrect choice) per row.
              </b-tooltip>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
    <b-button size="sm" @click="addRow">
      Add Row
    </b-button>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'
import ErrorMessage from '~/components/ErrorMessage'

export default {
  name: 'DropDownTable',
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
  methods: {
    clearErrors (key, general, index, identifier) {
      let errors = this.questionForm.errors.get(key)
      errors = JSON.parse(errors)
      switch (key) {
        case ('colHeaders'):
          delete errors['specific'][index]
          break
        case ('rows'):
          switch (general) {
            case (true):
              delete errors['general']
              break
            case (false):
              delete errors['specific'][index]['at_least_one_correct']
              delete errors['specific'][index]['at_least_two_responses']
              if (identifier) {
                delete errors['specific'][index][identifier]
              }
          }
          break
        default:
          alert(`The error key ${key} does not exist.`)
      }
      this.questionForm.errors.set(key, JSON.stringify(errors))
    },
    addRow () {
      this.qtiJson.rows.push({ header: '', responses: [{ identifier: uuidv4(), value: '', correctResponse: true }] })
    },
    deleteRow (rowIndex) {
      this.qtiJson.rows.length === 1
        ? this.$noty.info('You need at least one row.')
        : this.qtiJson.rows.splice(rowIndex, 1)
    },
    deleteDistractor (rowIndex, identifier) {
      this.qtiJson.rows[rowIndex]['responses'] = this.qtiJson.rows[rowIndex]['responses'].filter(response => response.identifier !== identifier)
    },
    addDistractor (rowIndex) {
      this.qtiJson.rows[rowIndex].responses.push({ identifier: uuidv4(), value: '', correctResponse: false })
    }
  }

}
</script>
