<template>
  <div class="p-3">
    <b-modal
      id="modal-nothing-submitted"
      title="Submission Not Accepted"
      size="lg"
      hide-footer
    >
      <b-alert variant="danger" :show="true">
        <span class="font-weight-bold" style="font-size: large">Please make a selection before submitting.</span>
      </b-alert>
    </b-modal>
    <b-form-group>
      <template v-slot:label>
        <span v-html="prompt"/>
      </template>
      <div v-for="choice in simpleChoice" :key="choice['@attributes'].identifier">
        <b-form-radio v-model="selectedSimpleChoice"
                      name="simple-choice" :value="choice['@attributes'].identifier"
        >
          <span v-html="choice.value"/>
        </b-form-radio>
      </div>
    </b-form-group>
    <b-button v-if="showSubmit"
              variant="primary"
              size="sm"
              @click="submitResponse(selectedSimpleChoice)"
    >
      Submit
    </b-button>
  </div>
</template>

<script>
export default {
  name: 'QtiJsonQuestionViewer',
  props: {
    qtiJson: {
      type: String,
      default: ''
    },
    studentResponse: {
      type: String,
      default: ''
    },
    showSubmit: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
    selectedSimpleChoice: null,
    shuffle: false,
    question: {},
    prompt: '',
    simpleChoice: []
    }
  ),
  mounted () {
    this.question = JSON.parse(this.qtiJson)
    console.log(this.question)
    this.prompt = this.question.itemBody.prompt
    this.simpleChoice = this.question.itemBody.choiceInteraction.simpleChoice
    if (this.studentResponse) {
      this.selectedSimpleChoice = this.studentResponse
    }
    let shuffle = this.question.itemBody.choiceInteraction['@attributes'].shuffle === 'true'
    if (shuffle) {
      if (this.question.seed) {
        let shuffledSimpleChoice = []
        let orders = this.question.seed.toString().split('')
        for (let i = 0; i < orders.length; i++) {
          console.log(orders[i])
          if (this.simpleChoice[orders[i]]) {
            shuffledSimpleChoice.push(this.simpleChoice[orders[i]])
          }
        }
        // just in case there are more than 10 choices
        let difference = this.simpleChoice.filter(x => !shuffledSimpleChoice.includes(x))
        for (let i = 0; i < difference.length; i++) {
          shuffledSimpleChoice.push(difference[i])
        }
        this.simpleChoice = shuffledSimpleChoice
      } else {
        // for demo purposes where there will be no seed
        this.shuffleArray(this.simpleChoice)
      }
    }
  },
  methods: {
    shuffleArray (array) {
      for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]]
      }
    },
    submitResponse (response) {
      if (response === null || response === '') {
        this.$bvModal.show('modal-nothing-submitted')
        return false
      }
      this.$emit('submitResponse', { data: response, origin: 'qti' })
    }

  }
}
</script>
