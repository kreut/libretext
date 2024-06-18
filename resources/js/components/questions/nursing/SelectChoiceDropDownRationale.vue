<template>
  <div>
    <b-modal
      id="qti-select-choice-error"
      title="Identifier Error"
      hide-footer
    >
      <b-alert show variant="info">
        <span v-html="selectChoiceIdentifierError"/>
      </b-alert>
    </b-modal>
    <div class="text-danger">
      <span v-html="selectChoiceMultipleMatchError"/>
    </div>
    <table
      v-if="Object.keys(qtiJson.inline_choice_interactions).length"
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
          <span v-html="selectChoice"/>
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
                  v-model="qtiJson.inline_choice_interactions[selectChoice][choiceIndex].text"
                  type="text"
                  :placeholder="choiceIndex === 0 ? 'Correct Response' : `Distractor ${choiceIndex}`"
                  class="form-control"
                  :class="choiceIndex === 0 ? 'text-success' : 'text-danger'"
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
  data: () => ({
    selectChoiceIdentifierError: '',
    selectChoiceMultipleMatchError: ''
  }),
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
              uniqueMatches.push(match)
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
        console.log(this.qtiJson.dropDownRationaleType === 'dyad')
        console.log(newSelectChoices.length)
        if (this.qtiJson.dropDownRationaleType === 'dyad' && newSelectChoices.length === 3) {
          this.selectChoiceIdentifierError = `Drop-down rationale dyad questions should only have 2 dropdowns.`
          this.$bvModal.show('qti-select-choice-error')
          return false
        }
        if (this.qtiJson.dropDownRationaleType === 'triad' && newSelectChoices.length === 4) {
          this.selectChoiceIdentifierError = `Drop-down rationale dyad questions should only have 3 dropdowns.`
          this.$bvModal.show('qti-select-choice-error')
          return false
        }
        let choices = []
        for (let i = 0; i < newSelectChoices.length; i++) {
          if (newSelectChoices[i] === '') {
            this.selectChoiceIdentifierError = `You have just added empty brackets.  Please include text within the bracket to identify the select choice item.`
            this.$bvModal.show('qti-select-choice-error')
            return false
          }
          if (choices.includes(newSelectChoices[i])) {
            if (this.qtiJson.questionType === 'select_choice') {
              this.selectChoiceIdentifierError = `The identifier [${newSelectChoices[i]}] appears multiple times in your prompt. Identifiers should only appear once.<br><br>If you need to use the same correct answer multiple times, you can use a dummy identifier such as [${newSelectChoices[i]}-1] where the "1" should be increased each time you use the same correct answer. Then, below, you can update the correct answer manually.`
              this.$bvModal.show('qti-select-choice-error')
              return false
            } else {
              if (choices.includes(newSelectChoices[i])) {
                this.selectChoiceIdentifierError = `The identifier [${newSelectChoices[i]}] appears multiple times in your prompt. Identifiers should only appear once.`
                this.$bvModal.show('qti-select-choice-error')
                return false
              }
            }
          }
          choices.push(newSelectChoices[i])
        }
        for (let i = 0; i < newSelectChoices.length; i++) {
          let choice = newSelectChoices[i]
          if (!Object.keys(this.qtiJson.inline_choice_interactions).includes(choice)) {
            this.qtiJson.inline_choice_interactions[choice] = [{
              value: uuidv4(),
              text: this.qtiJson.questionType === 'select_choice' ? this.decodeHtmlEntity(choice) : '',
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
    decodeHtmlEntity (html) {
      const txt = document.createElement('textarea')
      txt.innerHTML = html
      return txt.value
    },
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
