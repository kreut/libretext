<template>
  <div>
    <b-form-group
      v-if="qtiJson.questionType === 'true_false'"
      label-cols-sm="2"
      label-cols-lg="1"
      label-for="true_false_language"
      label="Language"
    >
      <b-form-row>
        <b-form-select
          id="true_false_language"
          v-model="trueFalseLanguage"
          style="width:100px"
          title="true/false language"
          size="sm"
          inline
          class="mt-2"
          :options="trueFalseLanguageOptions"
          @change="translateTrueFalse($event)"
        />
      </b-form-row>
    </b-form-group>

    <ul v-for="(simpleChoice, index) in qtiJson.simpleChoice"
        :key="simpleChoice.identifier"
        class="pt-2 pl-0"
    >
      <li style="list-style: none;">
        <span v-show="false" class="aaa">{{ simpleChoice.identifier }} {{
          simpleChoice.value
        }}
        </span>
        <b-row v-if="qtiJson.questionType==='true_false'">
          <b-col sm="1"
                 align-self="center"
                 class="text-right"
                 @click="updateCorrectResponse(simpleChoice)"
          >
            <b-icon-check-circle-fill v-show="simpleChoice.correctResponse"
                                      scale="1.5" class="text-success"
            />
            <b-icon-circle v-show="!simpleChoice.correctResponse" scale="1.5" />
          </b-col>
          <b-col style="padding:0;margin-top:5px">
            <b-form-group
              v-if="qtiJson.questionType==='true_false'"
              :label-for="`qti_simple_choice_${index}`"
              class="mb-0"
            >
              <template v-slot:label>
                <span style="font-size:1.25em;">
                  {{ simpleChoice.value }}</span>
              </template>
              <input type="hidden" class="form-control is-invalid">
              <div class="help-block invalid-feedback">
                {{ questionForm.errors.get(`qti_simple_choice_${index}`) }}
              </div>
            </b-form-group>
          </b-col>
        </b-row>
        <b-card v-if="qtiJson.questionType ==='multiple_choice'" header="default">
          <template #header>
            <div>
              <span @click="updateCorrectResponse(simpleChoice)">
                <b-icon-check-circle-fill v-show="simpleChoice.correctResponse"
                                          scale="1.5" class="text-success"
                />
                <b-icon-circle v-show="!simpleChoice.correctResponse" scale="1.5" />
              </span>
              <span class="ml-2 h6">Response {{ index + 1 }}</span>
              <span class="float-right">
                <b-icon-trash scale="1.5" @click="deleteResponse(simpleChoice)" /></span>
            </div>
          </template>
          <ul class="pl-0" style="list-style:none;">
            <li>
              <b-form-group
                :label-for="`qti_simple_choice_${index}`"
                class="mb-0"
              >
                <template v-slot:label>
                  <span class="font-weight-bold">Text</span>
                  <b-icon
                    :variant="simpleChoice.editorShown ? 'secondary' : 'primary'"
                    icon="pencil"
                    :aria-label="`Edit Response ${index + 1 } text`"
                    @click="toggleSimpleChoiceEditorShown(index,true)"
                  />
                </template>
                <div v-if="simpleChoice.editorShown">
                  <ckeditor
                    :id="`qti_simple_choice_${index}`"
                    v-model="simpleChoice.value"
                    tabindex="0"
                    :config="simpleChoiceConfig"
                    @namespaceloaded="onCKEditorNamespaceLoaded"
                    @ready="handleFixCKEditor()"
                    @input="questionForm.errors.clear(`qti_simple_choice_${index}`)"
                  />
                  <input type="hidden" class="form-control is-invalid">
                  <div class="help-block invalid-feedback">
                    {{ questionForm.errors.get(`qti_simple_choice_${index}`) }}
                  </div>
                  <div class="mt-2">
                    <b-button
                      size="sm"
                      variant="primary"
                      @click="toggleSimpleChoiceEditorShown(index,false)"
                    >
                      Close
                    </b-button>
                  </div>
                </div>
                <div v-if="!simpleChoice.editorShown">
                  <span v-html="simpleChoice.value" />
                </div>
              </b-form-group>
            </li>
            <li>
              <b-form-group
                :label-for="`qti_simple_choice_feedback_${index}`"
                class="mb-0"
              >
                <template v-slot:label>
                  <span class="font-weight-bold">Feedback <QuestionCircleTooltip v-show="qtiJson.questionType === 'multiple_choice'" id="feedback-type-tooltip" />
                    <b-tooltip target="feedback-type-tooltip"
                               delay="250"
                               triggers="hover focus"
                    >
                      You can provide feedback for individual responses here or use the General Feedback below as an alternative.
                    </b-tooltip>
                  </span>
                  <b-icon icon="pencil"
                          :variant="qtiJson.feedbackEditorShown[simpleChoice.identifier] ? 'secondary' : 'primary'"
                          :aria-label="`Edit Feedback ${index + 1 } text`"
                          @click="toggleFeedbackEditorShown(simpleChoice.identifier,true)"
                  />
                </template>
                <div v-if="qtiJson.feedbackEditorShown[simpleChoice.identifier]">
                  <ckeditor
                    :id="`qti_simple_choice_feedback_${index}`"
                    v-model="qtiJson.feedback[simpleChoice.identifier]"
                    tabindex="0"
                    :config="simpleChoiceConfig"
                    @namespaceloaded="onCKEditorNamespaceLoaded"
                    @ready="handleFixCKEditor()"
                  />
                  <div class="mt-2">
                    <b-button
                      size="sm"
                      variant="primary"
                      @click="toggleFeedbackEditorShown(simpleChoice.identifier,false)"
                    >
                      Close
                    </b-button>
                  </div>
                </div>
              </b-form-group>
              <div v-if="!qtiJson.feedbackEditorShown[simpleChoice.identifier]">
                <span v-html="qtiJson.feedback[simpleChoice.identifier]" />
              </div>
            </li>
          </ul>
        </b-card>
      </li>
      <li v-if="index === qtiJson.simpleChoice.length-1" style="list-style: none;" class="pt-3">
        <b-row>
          <b-col sm="10">
            <b-button v-if="qtiJson.questionType === 'multiple_choice'" size="sm" variant="info"
                      @click="addResponse"
            >
              Add Response
            </b-button>
            <span v-show="false">{{ qtiJson }}</span>
          </b-col>
        </b-row>
      </li>
    </ul>
  </div>
</template>

<script>
import { fixCKEditor } from '~/helpers/accessibility/fixCKEditor'
import CKEditor from 'ckeditor4-vue'
import { v4 as uuidv4 } from 'uuid'
export default {
  name: 'MultipleChoiceTrueFalse',
  components: {
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
    simpleChoiceConfig: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    trueFalseLanguage: 'English',
    trueFalseLanguageOptions: [
      { text: 'English', value: 'English' },
      { text: 'Spanish', value: 'Spanish' },
      { text: 'French', value: 'French' },
      { text: 'Italian', value: 'Italian' },
      { text: 'German', value: 'German' }
    ]
  }),
  mounted () {
    if (this.qtiJson.questionType === 'true_false') {
      this.qtiJson.language = this.trueFalseLanguage
      this.translateTrueFalse(this.trueFalseLanguage)
    }
  },
  methods: {
    toggleFeedbackEditorShown (identifier, boolean) {
      this.qtiJson.feedbackEditorShown[identifier] = boolean
      this.$forceUpdate()
    },
    handleFixCKEditor () {
      fixCKEditor(this)
    },
    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    },
    translateTrueFalse (language) {
      let trueResponse
      let falseResponse
      switch (language) {
        case ('English'):
          trueResponse = 'True'
          falseResponse = 'False'
          break
        case ('Spanish'):
          trueResponse = 'Verdadero'
          falseResponse = 'Falso'
          break
        case ('French'):
          trueResponse = 'Vrai'
          falseResponse = 'Faux'
          break
        case ('Italian'):
          trueResponse = 'Vero'
          falseResponse = 'Falso'
          break
        case ('German'):
          trueResponse = 'Richtig'
          falseResponse = 'Falsch'
          break
      }
      this.qtiJson.simpleChoice[0].value = trueResponse
      this.qtiJson.simpleChoice[1].value = falseResponse
    },
    updateCorrectResponse (simpleChoice) {
      this.qtiJson.simpleChoice.find(choice => choice.identifier !== simpleChoice.identifier).correctResponse = false
      simpleChoice.correctResponse = true
    },
    addResponse () {
      let response = {
        identifier: uuidv4(),
        value: ''
      }
      this.qtiJson.simpleChoice.push(response)
    },
    deleteResponse () {
      if (this.qtiJson.simpleChoice.length === 1) {
        this.$noty.info('There must be at least one response.')
        return false
      }
      this.qtiJson.simpleChoice = this.qtiJson.simpleChoice.filter(item => item.identifier !== this.simpleChoiceToRemove.identifier)
    },
    toggleSimpleChoiceEditorShown (index, boolean) {
      this.qtiJson.simpleChoice[index].editorShown = boolean
      this.$forceUpdate()
    }
  }
}
</script>

<style scoped>

</style>
