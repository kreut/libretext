<template>
  <div>
    <table
      class="table table-striped"
    >
      <thead>
      <tr>
        <th scope="col">
          Identifier
        </th>
        <th scope="col">
          Choices
        </th>
      </tr>
      </thead>
      <tbody>
      <tr>
        <td>Condition</td>
        <td>
          <ul v-for="(choice, choiceIndex) in qtiJson.inline_choice_interactions['condition']"
              :key="`selectChoice-${choiceIndex}`"
              style="padding-left:0"
          >
            <li v-if="qtiJson.inline_choice_interactions['condition'][choiceIndex]" style="list-style:none;">
              <b-input-group class="pb-3">
                <b-button v-if="choiceIndex === 0"
                          class="text-success"
                          variant="outline-secondary"
                >
                  <b-icon-check scale="1.5"/>
                </b-button>
                <b-input-group-prepend>
                  <b-button v-if="choiceIndex !== 0"
                            class="font-weight-bold text-danger"
                            variant="outline-secondary"
                            style="width:46px"
                  >
                    X
                  </b-button>
                </b-input-group-prepend>
                <b-form-input
                  id="title"
                  v-model="qtiJson.inline_choice_interactions['condition'][choiceIndex].text"
                  type="text"
                  :placeholder="choiceIndex === 0 ? 'Correct Condition' : `Distractor ${choiceIndex}`"
                  class="form-control"
                  :class="choiceIndex === 0 ? 'text-success' : 'text-danger'"
                  required
                  @input="clearErrors ('qti_select_choice_condition','condition',qtiJson.inline_choice_interactions['condition'], choiceIndex)"
                />
                <b-input-group-append v-if="choiceIndex > 0">
                  <b-input-group-text>
                    <b-icon-trash
                      @click="deleteChoiceFromSelectChoice('condition',choice)"
                    />
                  </b-input-group-text>
                </b-input-group-append>
              </b-input-group>

              <ErrorMessage v-if="choiceIndex === 0
                                && questionForm.errors.get('qti_select_choice_condition')
                                && JSON.parse(questionForm.errors.get('qti_select_choice_condition'))['condition']
                                && JSON.parse(questionForm.errors.get('qti_select_choice_condition'))['condition'].general"
                            :message="JSON.parse(questionForm.errors.get('qti_select_choice_condition'))['condition'].general[0]"
              />
              <ErrorMessage v-if="questionForm.errors.get('qti_select_choice_condition')
                                && JSON.parse(questionForm.errors.get('qti_select_choice_condition'))['condition']
                                && JSON.parse(questionForm.errors.get('qti_select_choice_condition'))['condition'].specific
                                && JSON.parse(questionForm.errors.get('qti_select_choice_condition'))['condition']['specific'][qtiJson.inline_choice_interactions['condition'][choiceIndex].value]"
                            :message="JSON.parse(questionForm.errors.get('qti_select_choice_condition'))['condition']['specific'][qtiJson.inline_choice_interactions['condition'][choiceIndex].value][0]"
              />
            </li>
          </ul>
          <b-button size="sm" variant="outline-primary" @click="addChoiceToSelectChoice('condition')">
            Add Distractor
          </b-button>
        </td>
      </tr>
      <tr>
        <td>Rationales</td>
        <td>
          <ul v-for="(choice, choiceIndex) in qtiJson.inline_choice_interactions['rationales']"
              :key="`selectChoice-${choiceIndex}`"
              style="padding-left:0"
          >
            <li v-if="qtiJson.inline_choice_interactions['rationales'][choiceIndex]" style="list-style:none;">
              <b-input-group class="pb-3">
                <b-button v-if="[0,1].includes(choiceIndex)"
                          class="text-success"
                          variant="outline-secondary"
                >
                  <b-icon-check scale="1.5"/>
                </b-button>
                <b-input-group-prepend>
                  <b-button v-if="![0,1].includes(choiceIndex)"
                            class="font-weight-bold text-danger"
                            variant="outline-secondary"
                            style="width:46px"
                  >
                    X
                  </b-button>
                </b-input-group-prepend>
                <b-form-input
                  v-model="qtiJson.inline_choice_interactions['rationales'][choiceIndex].text"
                  type="text"
                  :placeholder="[0,1].includes(choiceIndex) ? `Correct Rationale ${choiceIndex+1}` : `Distractor ${choiceIndex-1}`"
                  class="form-control"
                  :class="[0,1].includes(choiceIndex) ? 'text-success' : 'text-danger'"
                  @input="clearErrors ('qti_select_choice_rationales','rationales',qtiJson.inline_choice_interactions['rationales'], choiceIndex)"
                  required
                />
                <b-input-group-append v-if="choiceIndex > 1">
                  <b-input-group-text>
                    <b-icon-trash
                      @click="deleteChoiceFromSelectChoice('rationales',choice)"
                    />
                  </b-input-group-text>
                </b-input-group-append>
              </b-input-group>
              <ErrorMessage v-if="choiceIndex === 0
                                && questionForm.errors.get('qti_select_choice_rationales')
                                && JSON.parse(questionForm.errors.get('qti_select_choice_rationales'))['rationales']
                                && JSON.parse(questionForm.errors.get('qti_select_choice_rationales'))['rationales'].general"
                            :message="JSON.parse(questionForm.errors.get('qti_select_choice_rationales'))['rationales'].general[0]"
              />
              <ErrorMessage v-if="questionForm.errors.get('qti_select_choice_rationales')
                                && JSON.parse(questionForm.errors.get('qti_select_choice_rationales'))['rationales']
                                && JSON.parse(questionForm.errors.get('qti_select_choice_rationales'))['rationales'].specific
                                && JSON.parse(questionForm.errors.get('qti_select_choice_rationales'))['rationales']['specific'][qtiJson.inline_choice_interactions['rationales'][choiceIndex].value]"
                            :message="JSON.parse(questionForm.errors.get('qti_select_choice_rationales'))['rationales']['specific'][qtiJson.inline_choice_interactions['rationales'][choiceIndex].value][0]"
              />
            </li>
          </ul>
          <b-button size="sm" variant="outline-primary" @click="addChoiceToSelectChoice('rationales')">
            Add Distractor
          </b-button>
        </td>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'
import ErrorMessage from '../../ErrorMessage.vue'

export default {
  name: 'DropDownRationaleTriad',
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
    clearErrors (type, key, items, index) {
      let errors = this.questionForm.errors.get(type)
      errors = JSON.parse(errors)
      delete errors[key]['specific'][items[index].value]
      delete errors[key]['general']
      this.questionForm.errors.set(type, JSON.stringify(errors))
    },
    deleteChoiceFromSelectChoice (selectChoice, choice) {
      console.log(selectChoice + ' ' + choice)
      this.qtiJson.inline_choice_interactions[selectChoice] = this.qtiJson.inline_choice_interactions[selectChoice].filter(item => item !== choice)
      this.$forceUpdate()
    },
    addChoiceToSelectChoice (selectChoice) {
      this.qtiJson.inline_choice_interactions[selectChoice].push({
        value: uuidv4(),
        text: '',
        correctResponse: false
      })
      this.$forceUpdate()
    }
  }
}
</script>

<style scoped>

</style>
