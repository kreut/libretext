<template>
  <ul class="pt-2 pl-0">
    <li v-for="(simpleChoice, index) in qtiJson.simpleChoice" :key="simpleChoice.identifier"
        style="list-style: none;" class="pb-3"
    >
      <span v-show="false" class="aaa">{{ simpleChoice.identifier }} {{
          simpleChoice.value
        }}
      </span>
      <b-card header="default">
        <template #header>
          <h2 class="h7">
            <span>
              <span @click="toggleMultipleAnswersCorrectResponse(simpleChoice)">
                <b-icon-square v-show="!simpleChoice.correctResponse" scale="1.5"/>
                <b-icon-check-square-fill v-show="simpleChoice.correctResponse"
                                          scale="1.5" class="text-success"
                />
                <span class="ml-2">Response {{ index + 1 }}</span>
              </span>
              <span class="float-right">
                <b-icon-trash scale="1.5" @click="deleteResponse(simpleChoice)"/>
              </span>
            </span>
          </h2>
        </template>
        <b-card-text>
          <b-form-group
            :label-for="`qti_simple_choice_${index}`"
            class="mb-0"
          >
            <template v-slot:label>
              Text
            </template>
            <ckeditor
              :id="`qti_simple_choice_${index}`"
              v-model="simpleChoice.value"
              tabindex="0"
              :config="multipleResponseRichEditorConfig"
              @namespaceloaded="onCKEditorNamespaceLoaded"
              @ready="handleFixCKEditor()"
              @input="questionForm.errors.clear(`qti_simple_choice_${index}`)"
            />
            <ErrorMessage v-if="questionForm.errors.get(`qti_simple_choice_${index}`)"
                          :message="questionForm.errors.get(`qti_simple_choice_${index}`)"
                          />
          </b-form-group>
          <b-form-group
            :label-for="`qti_feedback_${index}`"
            class="mt-3"
          >
            <template v-slot:label>
              Feedback (Optional)
            </template>
            <ckeditor
              :id="`qti_feedback_${index}`"
              v-model="simpleChoice.feedback"
              tabindex="0"
              :config="matchingRichEditorConfig"
              @namespaceloaded="onCKEditorNamespaceLoaded"
              @ready="handleFixCKEditor()"
            />
          </b-form-group>
        </b-card-text>
      </b-card>
    </li>
    <li style="list-style: none;" class="pt-3">
      <b-row>
        <b-col sm="10">
          <b-button size="sm" variant="info"
                    @click="addResponse"
          >
            Add Response
          </b-button>
          <span v-show="false">{{ qtiJson }}</span>
        </b-col>
      </b-row>
    </li>
  </ul>
</template>

<script>
import CKEditor from 'ckeditor4-vue'
import { fixCKEditor } from '~/helpers/accessibility/fixCKEditor'
import { v4 as uuidv4 } from 'uuid'
import ErrorMessage from '../ErrorMessage.vue'

export default {
  name: 'MultipleAnswers',
  components: {
    ErrorMessage,
    ckeditor: CKEditor.component
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
    },
    matchingRichEditorConfig: {
      type: Object,
      default: () => {
      }
    },
    multipleResponseRichEditorConfig: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    simpleChoiceToRemove: {}
  }),
  methods: {
    toggleMultipleAnswersCorrectResponse (simpleChoice) {
      simpleChoice.correctResponse = !simpleChoice.correctResponse
    },
    addResponse () {
      let response = {
        identifier: uuidv4(),
        value: '',
        correctResponse: false,
        feedback: ''
      }
      this.qtiJson.simpleChoice.push(response)
    },
    deleteResponse (simpleChoiceToRemove) {
      if (this.qtiJson.simpleChoice.length === 1) {
        this.$noty.info('There must be at least one response.')
        return false
      }
      this.qtiJson.simpleChoice = this.qtiJson.simpleChoice.filter(item => item.identifier !== simpleChoiceToRemove.identifier)
    },
    handleFixCKEditor () {
      fixCKEditor(this)
    },
    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    }
  }
}
</script>

<style scoped>

</style>
