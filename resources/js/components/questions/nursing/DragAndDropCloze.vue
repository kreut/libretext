<template>
  <div class="pb-3">
    <b-modal
      id="modal-drag-and-drop-cloze-error"
      title="Drag and Drop Cloze Error"
      hide-footer
    >
      <b-alert show variant="info">
        {{ dragAndDropClozeError }}
      </b-alert>
    </b-modal>
    {{ qtiJson }}
    <b-alert show variant="info">
      Bracket off the portions of the text where you would like the Drop-Down Cloze to occur, using a bracketed response
      to
      denote the correct answer. Then, add distractors below. Example: The client is at risk for developing [infection]
      and [seizures].
    </b-alert>
    <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Distractors</h2>">
      <b-card-text>
        <ErrorMessage v-if="questionForm.errors.get('distractors')
                        && JSON.parse(questionForm.errors.get('distractors'))"
                      :message="JSON.parse(questionForm.errors.get('distractors'))['general']"
        />
        <div v-for="(distractor,distractorIndex) in qtiJson.distractors"
             :key="distractor.identifier"
             class="pb-3"
        >
          <b-input-group>
            <b-form-input v-model="distractor.value"
                          :placeholder="`Distractor ${distractorIndex + 1}`"
            />
            <b-input-group-append>
              <b-input-group-text>
                <b-icon-trash
                  @click="removeDistractor(distractor.identifier)"
                />
              </b-input-group-text>
            </b-input-group-append>
          </b-input-group>
          <ErrorMessage v-if="questionForm.errors.get('distractors')
                          && JSON.parse(questionForm.errors.get('distractors'))
                          && JSON.parse(questionForm.errors.get('distractors'))['specific']"
                        :message="JSON.parse(questionForm.errors.get('distractors'))['specific'][distractor.identifier]"
          />
        </div>
        <b-button class="primary" size="sm" @click="addDistractor()">
          Add Distractor
        </b-button>
      </b-card-text>
    </b-card>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'
import ErrorMessage from '~/components/ErrorMessage'

export default {
  name: 'DragAndDropCloze',
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
  data: () => ({
    dragAndDropClozeError: ''
  }),
  computed: {
    correctResponses () {
      let correctResponses = []
      if (this.qtiJson && this.qtiJson.prompt) {
        const regex = /(\[.*?])/
        let allCorrectResponses = String(this.qtiJson.prompt).split(regex)
        console.log(allCorrectResponses)
        if (allCorrectResponses) {
          for (let i = 0; i < allCorrectResponses.length; i++) {
            if (allCorrectResponses[i].includes('[') && allCorrectResponses[i].includes(']')) {
              let correctResponse = allCorrectResponses[i].replace('[', '').replace(']', '')
              if (!correctResponses.includes(correctResponse)) {
                correctResponses.push(correctResponse)
              }
            }
          }
        }
      }
      return correctResponses
    }
  },
  watch: {
    correctResponses (newCorrectResponses) {
      if (Array.isArray(newCorrectResponses) && newCorrectResponses.length) {
        for (let i = 0; i < newCorrectResponses.length; i++) {
          if (newCorrectResponses[i] === '') {
            this.dragAndDropClozeError = `You have just added empty brackets.  Please include text within the bracket to identify the correct response.`
            this.$bvModal.show('modal-drag-and-drop-cloze-error')
            return false
          }
        }
        this.qtiJson.correctResponses = []
        for (let i = 0; i < newCorrectResponses.length; i++) {
          let correctResponse = newCorrectResponses[i]
          this.qtiJson.correctResponses.push({ identifier: uuidv4(), value: correctResponse })
        }
      }
    }
  },
  methods: {
    addDistractor () {
      this.qtiJson.distractors.push({ identifier: uuidv4(), value: '' })
    },
    removeDistractor (identifier) {
      if (this.qtiJson.distractors.length === 1) {
        this.$noty.info('You need at least 1 Distractor.')
        return false
      }
      this.qtiJson.distractors = this.qtiJson.distractors.filter(distractor => distractor.identifier !== identifier)
    }
  }
}
</script>
