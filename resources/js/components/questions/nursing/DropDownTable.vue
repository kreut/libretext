<template>
  <div>
    {{ qtiJson }}
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
          />

          <ErrorMessage v-if="questionForm.errors.get('colHeaders')"
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
                            && JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]"
                        :message="JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]['header']"
          />
        </td>
        <td>
          <div v-for="(response,responseIndex) in qtiJson.rows[rowIndex].responses"
               :key="`drop-table-responses-${response.identifier}`"
          >
            <b-form-row v-if="responseIndex ===0" class="pb-2">
              <b-form-input
                v-model="qtiJson.rows[rowIndex]['responses'][responseIndex].value"
                placeholder="Correct Response"
                class="text-success"
              />
            </b-form-row>

              <ErrorMessage
                v-if="questionForm.errors.get('rows')
                   && JSON.parse(questionForm.errors.get('rows'))['specific']
                  && JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex]"
                :message="JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex][response.identifier]"
              />

              <ErrorMessage v-if="questionForm.errors.get('rows') && responseIndex === 0"
                            :message="JSON.parse(questionForm.errors.get('rows'))['general']"
              />

            <b-form-row v-if="responseIndex >0" class="pb-2">
              <b-input-group>
                <b-form-input v-model="qtiJson.rows[rowIndex]['responses'][responseIndex].value"
                              :placeholder="`Distractor ${responseIndex}`"
                />
                <b-input-group-append>
                  <b-input-group-text>
                    <b-icon-trash
                      @click="deleteDistractor(rowIndex, qtiJson.rows[rowIndex]['responses'][responseIndex].identifier)"
                    />
                  </b-input-group-text>
                </b-input-group-append>
              </b-input-group>
              <ErrorMessage v-if="questionForm.errors.get('rows')"
                            :message="JSON.parse(questionForm.errors.get('rows'))['specific'][rowIndex][response.identifier]"
              />
            </b-form-row>
          </div>
          <div class="pt-2">
            <b-button size="sm" @click="addDistractor(rowIndex)">
              Add Distractor
            </b-button>
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
