<template>
  <div class="p-3">
    <b-form-group>
      <template v-slot:label>
        <span v-html="prompt"/>
      </template>
      <div v-for="choice in simpleChoice" :key="choice['@attributes'].identifier">
        <b-form-radio v-model="selectedSimpleChoice"
                      name="simple-choice" :value="choice['@attributes'].identifier"
        >
          {{ choice.value }}
        </b-form-radio>
      </div>
    </b-form-group>
    <b-button variant="primary"
              v-if="showSubmit"
              size="sm"
              @click="submitResponse(selectedSimpleChoice)"
    >
      Submit
    </b-button>
  </div>
</template>

<script>
export default {
  name: 'JsonQuestionViewer',
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
      question: {},
      prompt: '',
      simpleChoice: []
    }
  ),
  mounted () {
    this.question = JSON.parse(this.qtiJson)
    this.prompt = this.question.itemBody.prompt
    this.simpleChoice = this.question.itemBody.choiceInteraction.simpleChoice
  },
  methods: {
    submitResponse (response) {
      this.$emit('submitResponse', { data: response, origin: 'adapt' })
    }

  }
}
</script>

