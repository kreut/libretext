<template>
  <div :id="componentId">
    <p v-if="qtiJson.jsonType === 'question_json'">
      Instructions: Make a single selection from each of the following drop-downs. Each term may only be selected once.
    </p>
    <div v-if="qtiJson.jsonType==='answer_json'">
      <b-alert variant="info" show>
        The order of your selected answers does not impact the correctness of your response.
      </b-alert>
    </div>
    <b-form inline>
      <span v-for="(item,promptIndex) in parsedPrompt" :key="`prompt-${promptIndex}-${optionsKey}`">
        <span v-if="promptIndex % 2 === 0" v-html="removePTag(item)"/>
        <span v-if="promptIndex % 2 !== 0">
          <b-form-select :value="selectedOptions[(promptIndex - 1) / 2]"
                         :options="decodedSelectOptions"
                         size="sm"
                         class="drop-down-cloze-select"
                         :aria-label="`combobox ${Math.ceil(promptIndex / 2)} of ${Math.floor(decodedSelectOptions.length / 2)}`"
                         style="margin:3px"
                         @change="validateInput(selectedOptions[(promptIndex - 1) / 2],(promptIndex - 1) / 2, $event)"
          />
          <span v-if="qtiJson.studentResponse && showResponseFeedback && qtiJson.correctResponses">
            <b-icon-check-circle-fill v-if="isCorrect(promptIndex)"
                                      class="text-success mr-2"
            />
            <b-icon-x-circle-fill v-if="!isCorrect(promptIndex)"
                                  class="text-danger mr-2"
            />
          </span>
        </span>
      </span>
    </b-form>
    <GeneralFeedback :feedback="qtiJson.feedback"
                     :feedback-type="feedbackType"
    />
  </div>
</template>

<script>
import $ from 'jquery'
import GeneralFeedback from '../feedback/GeneralFeedback'
import { v4 as uuidv4 } from 'uuid'

export default {
  name: 'DragAndDropClozeViewer',
  components: { GeneralFeedback },
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    },
    showResponseFeedback: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
    componentId: '',
    optionsKey: 0,
    selectedOptions: [],
    feedbackType: ''
  }),
  computed: {
    parsedPrompt () {
      let reg = /\[(.*?)\]/g
      return this.qtiJson.prompt.split(reg)
    },
    decodedSelectOptions() {
      return this.qtiJson.selectOptions.map(opt => ({
        ...opt,
        text: opt.text.replace(/&#39;/g, "'")
      }));
    }
  },
  mounted () {
    this.componentId = uuidv4()
    this.$forceUpdate()
    for (let i = 0; i < $(`#${this.componentId} .drop-down-cloze-select`).length; i++) {
      this.selectedOptions[i] = null
    }
    if (this.qtiJson.studentResponse) {
      if (this.qtiJson.correctResponses) {
        this.feedbackType = 'correct'
        const identifiers = this.qtiJson.correctResponses.map(obj => obj.identifier)
        for (let i = 0; i < this.qtiJson.studentResponse.length; i++) {
          if (!identifiers.includes(this.qtiJson.studentResponse[i])) {
            this.feedbackType = 'incorrect'
          }
        }
      }
      let selecteds = this.qtiJson.studentResponse
      this.selectedOptions = this.qtiJson.studentResponse
      this.$nextTick(() => {
        $(`#${this.componentId} .drop-down-cloze-select`).each(function (index) {
          let selected = selecteds[index]
          $(this).val(selected)
        })
      })
    }
  },
  methods: {
    validateInput (item, index, event) {
      if (event !== null && this.selectedOptions.includes(event)) {
        console.log(index)
        console.log(this.selectedOptions)
        this.selectedOptions[index] = null
        this.optionsKey++
        let selectedText = this.qtiJson.selectOptions.find(option => option.value === event).text
        this.$noty.info(`Each option can only be chosen once and ${selectedText} has already been selected.`)
      } else {
        this.selectedOptions[index] = event
      }
    },
    removePTag (item) {
      return item.replace('<p>', '').replace('</p>', '')
    },
    isCorrect (promptIndex) {
      const identifiers = this.qtiJson.correctResponses.map(obj => obj.identifier)
      return identifiers.includes(this.qtiJson.studentResponse[(promptIndex - 1) / 2])
    }
  }
}
</script>
