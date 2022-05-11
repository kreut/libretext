<template>
  <div class="p-3">
    <b-form-group>
      <template v-slot:label>
        <span v-html="prompt" />
      </template>
      <div v-for="choice in simpleChoice" :key="choice['@attributes'].identifier">
        <b-form-radio v-model="selectedSimpleChoice"
                      name="simple-choice" :value="choice['@attributes'].identifier"
        >
          {{ choice.value }}
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
    this.prompt = this.question.itemBody.prompt
    this.simpleChoice = this.question.itemBody.choiceInteraction.simpleChoice
    let shuffle = this.question.itemBody.choiceInteraction['@attributes'].shuffle === 'true'
    if (shuffle) {
      this.shuffleArray(this.simpleChoice)
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
      this.$emit('submitResponse', { data: response, origin: 'adapt' })
    }

  }
}
</script>
