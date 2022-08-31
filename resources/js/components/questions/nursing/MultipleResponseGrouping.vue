<template>
  <div>
    {{ qtiJson }}
    <table class="table table-striped">
      <thead>
      <tr>
        <th v-for="(header,colIndex) in qtiJson.headers" :key="`multiple-response-grouping-header-${colIndex}`"
            scope="col"
        >
          <b-form-input
            v-if="colIndex === 0"
            v-model="qtiJson.headers[colIndex]"
            type="text"
            placeholder="Column Header 1"
          />
          <b-input-group v-if="colIndex !== 0">
            <b-form-input
              v-model="qtiJson.headers[colIndex]"
              type="text"
              :placeholder="`Column Header ${colIndex+1}`"
            />
          </b-input-group>
        </th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(row,rowIndex) in qtiJson.rows" :key="`multiple-response-grouping-${rowIndex}`">
        <td>
          <b-input-group>
            <b-form-input
              v-model="qtiJson.rows[rowIndex].grouping"
              type="text"
              :placeholder="`Grouping ${rowIndex+1}`"
            />
            <b-input-group-append>
              <b-input-group-text>
                <b-icon-trash
                  @click="deleteGrouping(rowIndex)"
                />
              </b-input-group-text>
            </b-input-group-append>
          </b-input-group>
        </td>
        <td>
          <div v-for="(response,responseIndex) in qtiJson.rows[rowIndex].responses"
               :key="`multiple-response-grouping-${response.identifier}`"
          >
            <b-form-checkbox
              v-model="qtiJson.rows[rowIndex]['responses'][responseIndex].correctResponse"
              :value="true"
              :unchecked-value="false"
            />
            <b-input-group>
              <b-form-input v-model="qtiJson.rows[rowIndex]['responses'][responseIndex].value"
                            :placeholder="`Response ${responseIndex + 1}`"
              />
              <b-input-group-append>
                <b-input-group-text>
                  <b-icon-trash
                    @click="deleteResponse(rowIndex, qtiJson.rows[rowIndex]['responses'][responseIndex].identifier)"
                  />
                </b-input-group-text>
              </b-input-group-append>
            </b-input-group>
          </div>
          <div class="pt-2">
            <b-button size="sm" @click="addResponse(rowIndex)">
              Add Response
            </b-button>
          </div>
        </td>
      </tr>
      </tbody>
    </table>
    <b-button size="sm" @click="addGrouping">
      Add Grouping
    </b-button>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'

export default {
  name: 'MultipleResponseGrouping',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    }
  },
  methods: {
    deleteGrouping (rowIndex) {
      if (this.qtiJson.rows.length === 1) {
        this.qtiJson = {
          questionType: 'multiple_response_grouping',
          prompt: '',
          headers: ['', ''],
          rows: [
            {
              grouping: '',
              responses: [{ identifier: uuidv4(), value: '', correctResponse: false }]
            }
          ]
        }
      } else {
        this.qtiJson.rows.splice(rowIndex, 1)
      }
    },
    addResponse (rowIndex) {
      this.qtiJson.rows[rowIndex].responses.push({ identifier: uuidv4(), value: '', correctResponse: false })
    },
    deleteResponse (rowIndex, identifier) {
      this.qtiJson.rows[rowIndex]['responses'] = this.qtiJson.rows[rowIndex]['responses'].filter(response => response.identifier !== identifier)
    },
    addGrouping () {
      if (this.qtiJson.rows.length === 5) {
        this.$noty.info('You can have at most 5 groupings.')
        return false
      }
      this.qtiJson.rows.push({ grouping: '', responses: [{ identifier: uuidv4(), value: '', correctResponse: false }] })
    }
  }
}
</script>
