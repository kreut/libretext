<template>
  <div>
    <table
      v-if="qtiJson.inline_choice_interactions"
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
      <tr v-for="(selectChoice,index) in selectChoices" :key="`selectChoices-${index}`">
        <td>
          {{ selectChoice }}
          <input type="hidden" class="form-control is-invalid">
          <div class="help-block invalid-feedback">
            <span v-html="questionForm.errors.get(`qti_select_choice_${selectChoice}`)"/>
          </div>
        </td>
        <td>
          <ul v-for="(choice, choiceIndex) in qtiJson.inline_choice_interactions[selectChoice]"
              :key="`selectChoice-${choiceIndex}`"
              style="padding-left:0"
          >
            <li v-if="qtiJson.inline_choice_interactions[selectChoice][choiceIndex]" style="list-style:none;">
              <b-input-group class="pb-3">
                <b-form-input
                  id="title"
                  v-model="qtiJson.inline_choice_interactions[selectChoice][choiceIndex].text"
                  type="text"
                  :placeholder="choiceIndex === 0 ? 'Correct Response' : `Distractor ${choiceIndex}`"
                  class="form-control"
                  :class="{'text-success' : choiceIndex === 0 }"
                  required
                />
                <b-input-group-append v-if="choiceIndex > 0">
                  <b-input-group-text>
                    <b-icon-trash
                      @click="deleteChoiceFromSelectChoice(selectChoice,choice)"
                    />
                  </b-input-group-text>
                </b-input-group-append>
              </b-input-group>
              <has-error :form="questionForm" field="title"/>
            </li>
          </ul>
          <b-button size="sm" variant="outline-primary" @click="addChoiceToSelectChoice(selectChoice)">
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

export default {
  name: 'SelectChoiceDropDownRationale',
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
  computed: {
    selectChoices () {
      let uniqueMatches = []
      if (this.qtiJson && this.qtiJson.itemBody) {
        const regex = /(\[.*?])/
        let allMatches = String(this.qtiJson.itemBody).split(regex)
        console.log(allMatches)
        if (allMatches) {
          for (let i = 0; i < allMatches.length; i++) {
            if (allMatches[i].includes('[') && allMatches[i].includes(']')) {
              let match = allMatches[i].replace('[', '').replace(']', '')
              if (!uniqueMatches.includes(match)) {
                uniqueMatches.push(match)
              }
            }
          }
        }
      }
      console.log(uniqueMatches)
      return uniqueMatches
    }
  },
  watch: {
    selectChoices (newSelectChoices) {
      if (this.qtiJson.inline_choice_interactions &&
        Array.isArray(newSelectChoices) &&
        newSelectChoices.length) {
        for (let i = 0; i < newSelectChoices.length; i++) {
          if (newSelectChoices[i] === '') {
            this.selectChoiceIdentifierError = `You have just added empty brackets.  Please include text within the bracket to identify the select choice item.`
            this.$bvModal.show(`qti-select-choice-error-${this.modalId}`)
            return false
          }
          if (newSelectChoices[i].includes(' ')) {
            this.selectChoiceIdentifierError = `The identifier [${newSelectChoices[i]}] contains a space. Identifiers should not contain any spaces.`
            this.$bvModal.show(`qti-select-choice-error-${this.modalId}`)
            return false
          }
        }
        for (let i = 0; i < newSelectChoices.length; i++) {
          let choice = newSelectChoices[i]
          if (!Object.keys(this.qtiJson.inline_choice_interactions).includes(choice)) {
            this.qtiJson.inline_choice_interactions[choice] = [{
              value: Date.now().toString(),
              text: '',
              correctResponse: true
            }]
          }
        }
      }
      for (const identifier in this.qtiJson.inline_choice_interactions) {
        if (!newSelectChoices.includes(identifier)) {
          delete this.qtiJson.inline_choice_interactions[identifier]
        }
      }
      for (const identifier in this.qtiJson.inline_choice_interactions) {
        for (let i = 0; i < this.qtiJson.inline_choice_interactions[identifier].length; i++) {
          this.qtiJson.inline_choice_interactions[identifier][i].correctResponse = i === 0
        }
      }
      this.$forceUpdate()
    }
  },
  methods: {
    deleteChoiceFromSelectChoice (selectChoice, choice) {
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
