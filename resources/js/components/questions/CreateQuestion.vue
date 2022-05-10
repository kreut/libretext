<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="`modal-form-errors-questions-form-${questionsFormKey}`"/>
    <div v-if="questionExistsInAnotherInstructorsAssignment">
      <b-alert :show="true" class="font-weight-bold">
        <div v-if="isMe">
          Warning: This question exists in another instructor's assignment. As admin you may edit it.
        </div>
        <div v-else>
          This question exists in another instructor's assignment and cannot be edited.
        </div>
      </b-alert>
    </div>
    <div v-if="!questionExistsInAnotherInstructorsAssignment && questionExistsInOwnAssignment">
      <b-alert :show="true" class="font-weight-bold">
        Warning: You are editing a question which already exists in one of your assignments.
      </b-alert>
    </div>
    <b-modal
      :id="`confirm-remove-simple-choice-${modalId}`"
      title="Confirm deleting response"
    >
      <p>Please confirm that you would like to delete the response:</p>
      <p class="text-center font-weight-bold">
        {{ simpleChoiceToRemove.value }}
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide(`confirm-remove-simple-choice-${modalId}`)"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="deleteQtiResponse()"
        >
          Delete
        </b-button>
      </template>


    </b-modal>
    <b-modal
      :id="`modal-confirm-delete-qti-${modalId}`"
      title="Confirm reset QTI technology"
    >
      Hiding this area will delete the information associated with the QTI technology. Are you sure you would like to
      do this?
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide(`modal-confirm-delete-qti-${modalId}`)"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="deleteQtiTechnology()"
        >
          Delete
        </b-button>
      </template>
    </b-modal>
    <b-modal
      :id="modalId"
      title="Preview Question"
      :size="questionForm.solution_html ? 'xl' : 'lg'"
      ok-title="OK"
      ok-only
    >
      <QtiJsonQuestionViewer v-if="questionForm.technology === 'qti'"
                             :qti-json="JSON.stringify(qtiJson)"
                             :show-submit="false"
      />
      <ViewQuestions v-if="questionForm.technology !== 'qti'"
                     :key="questionToViewKey"
                     :question-to-view="questionToView"
      />
      <div v-if="questionForm.solution_html" class="mt-section">
        <h2 class="editable">
          Solution
        </h2>
        <div v-html="questionForm.solution_html"/>
      </div>
    </b-modal>
    <div class="mb-3">
      <RequiredText/>
      Fields marked with the
      <font-awesome-icon v-if="!sourceExpanded" :icon="caretRightIcon" size="lg"/>
      icon contain expandable text areas.
    </div>
    <b-form-group
      label-cols-sm="3"
      label-cols-lg="2"
      label-for="title"
      label="Title*"
    >
      <b-form-row>
        <b-form-input
          id="title"
          v-model="questionForm.title"
          type="text"
          required
          :class="{ 'is-invalid': questionForm.errors.has('title') }"
          @keydown="questionForm.errors.clear('title')"
        />
        <has-error :form="questionForm" field="title"/>
      </b-form-row>
    </b-form-group>
    <b-form-group
      label-cols-sm="3"
      label-cols-lg="2"
      label-for="question_type"
      :label="isEdit ? 'Question Type' : 'Question Type*'"
    >
      <b-form-row>
        <div v-if="isEdit && !isMe">
          {{ questionForm.question_type.charAt(0).toUpperCase() + questionForm.question_type.slice(1) }}
        </div>
        <div v-else>
          <b-form-radio-group
            id="question_type"
            v-model="questionForm.question_type"
            stacked
            :aria-required="!isEdit"
            @change="resetQuestionForm($event)"
          >
            <b-form-radio name="question_type" value="assessment">
              Assessment
              <QuestionCircleTooltip :id="'assessment-question-type-tooltip'"/>
              <b-tooltip target="assessment-question-type-tooltip"
                         delay="250"
                         triggers="hover focus"
              >
                Assessments can be used within assignments as questions. In addition, if they are an auto-graded
                technology,
                they can be used as root nodes in Learning Trees. Regardless of whether they have an auto-graded
                technology, assessments can be used in non-root nodes of
                Learning Trees.
              </b-tooltip>
            </b-form-radio>
            <b-form-radio name="question_type" value="exposition">
              Exposition (use in Learning Trees only)
              <QuestionCircleTooltip :id="'exposition-question-type-tooltip'"/>
              <b-tooltip target="exposition-question-type-tooltip"
                         delay="250"
                         triggers="hover focus"
              >
                An Exposition consists of source (text, video, simulation, any other html) without an auto-graded
                component. They can be used in any of the non-root
                nodes within Learning Trees.
              </b-tooltip>
            </b-form-radio>
          </b-form-radio-group>
        </div>
      </b-form-row>
    </b-form-group>

    <div v-if="questionForm.question_type">
      <b-form ref="form">
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="public"
        >
          <template slot="label">
            Public*
            <QuestionCircleTooltip :id="'public-question-tooltip'"/>
            <b-tooltip target="public-question-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              Questions that are public can be used by any instructor. Questions that are not public are only accessible
              by you.
            </b-tooltip>
          </template>
          <b-form-row class="mt-2">
            <b-form-radio-group
              id="public"
              v-model="questionForm.public"
            >
              <b-form-radio name="public" value="1">
                Yes
              </b-form-radio>
              <b-form-radio name="public" value="0">
                No
              </b-form-radio>
            </b-form-radio-group>
          </b-form-row>
        </b-form-group>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="folder"
          label="Folder*"
        >
          <b-form-row>
            <SavedQuestionsFolders
              ref="savedQuestionsFolders1"
              :key="`saved-questions-folders-key-${savedQuestionsFolderKey}`"
              class="mt-2"
              :type="'my_questions'"
              :init-saved-questions-folder="questionForm.folder_id"
              :create-modal-add-saved-questions-folder="true"
              :folder-to-choose-from="'My Questions'"
              :question-source-is-my-favorites="false"
              @reloadSavedQuestionsFolders="reloadCreateQuestionSavedQuestionsFolders"
              @savedQuestionsFolderSet="setMyCoursesFolder"
            />
          </b-form-row>
          <input type="hidden" class="form-control is-invalid">
          <div class="help-block invalid-feedback">
            {{ questionForm.errors.get('folder_id') }}
          </div>
        </b-form-group>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="author"
          label="Author(s)"
        >
          <b-form-row>
            <b-form-input
              id="author"
              v-model="questionForm.author"
              type="text"
              :class="{ 'is-invalid': questionForm.errors.has('author') }"
              @keydown="questionForm.errors.clear('author')"
            />
            <has-error :form="questionForm" field="author"/>
          </b-form-row>
        </b-form-group>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="license"
          label="License"
        >
          <b-form-row>
            <b-form-select v-model="questionForm.license"
                           style="width:200px"
                           title="license"
                           size="sm"
                           class="mt-2  mr-2"
                           :options="licenseOptions"
                           @change="updateLicenseVersions()"
            />
          </b-form-row>
        </b-form-group>
        <b-form-group
          v-if="licenseVersionOptions.length"
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="license_version"
          label="License Version*"
        >
          <b-form-row>
            <b-form-select v-model="questionForm.license_version"
                           style="width:100px"
                           title="license version"
                           required
                           size="sm"
                           class="mt-2"
                           :options="licenseVersionOptions"
            />
          </b-form-row>
          <b-form-select
            id="a11y_technology"
            v-model="trueFalseLanguage"
            title="true/false language"
            size="sm"
            inline
            class="mt-2"
            :options="trueFalseLanguageOptions"
          />
        </b-form-group>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="tags"
          label="Tags"
        >
          <b-form-row>
            <b-form-input
              id="tags"
              v-model="tag"
              style="width:200px"
              type="text"
              class="mr-2"
              size="sm"
            />
            <b-button variant="outline-primary" size="sm" @click="addTag()">
              Add Tag
            </b-button>
          </b-form-row>
          <div class="d-flex flex-row">
            <span v-for="chosenTag in questionForm.tags" :key="chosenTag" class="mt-2">
              <b-button size="sm" variant="secondary" class="mr-2" @click="removeTag(chosenTag)">{{
                  chosenTag
                }} x</b-button>
            </span>
          </div>
        </b-form-group>
        <b-form-group
          key="source"
          label-for="non_technology_text"
        >
          <template v-slot="label">
            <span style="cursor: pointer;" @click="toggleExpanded ('non_technology_text')">
              {{ questionForm.question_type === 'assessment' ? 'Source (Optional)' : 'Source*' }}
              <font-awesome-icon v-if="!editorGroups.find(group => group.id === 'non_technology_text').expanded"
                                 :icon="caretRightIcon" size="lg"
              />
              <font-awesome-icon v-if="editorGroups.find(group => group.id === 'non_technology_text').expanded"
                                 :icon="caretDownIcon" size="lg"
              />
            </span>
          </template>
        </b-form-group>
        <ckeditor
          v-show="editorGroups.find(group => group.id === 'non_technology_text').expanded"
          id="non_technology_text"
          v-model="questionForm.non_technology_text"
          tabindex="0"
          required
          :config="richEditorConfig"
          :class="{ 'is-invalid': questionForm.errors.has('non_technology_text')}"
          @namespaceloaded="onCKEditorNamespaceLoaded"
          @ready="handleFixCKEditor()"
          @keydown="questionForm.errors.clear('non_technology_text')"
        />
        <has-error :form="questionForm" field="non_technology_text"/>
        <div v-if="questionForm.question_type === 'assessment'">
          <b-form-group
            label-cols-sm="3"
            label-cols-lg="2"
            label-for="technology"
          >
            <template v-slot:label>
              <span style="cursor: pointer;" @click="toggleExpanded ('technology')">
                Auto-Graded Technology
                <font-awesome-icon v-if="!editorGroups.find(editorGroup => editorGroup.id === 'technology').expanded"
                                   :icon="caretRightIcon" size="lg"
                />
                <font-awesome-icon v-if="editorGroups.find(editorGroup => editorGroup.id === 'technology').expanded"
                                   :icon="caretDownIcon" size="lg"
                />
              </span>
            </template>
            <b-form-row v-if="editorGroups.find(editorGroup => editorGroup.id === 'technology').expanded">
              <div v-if="isEdit && !isMe" class="pt-2">
                {{ autoGradedTechnologyOptions.find(option => option.value === questionForm.technology).text }}
              </div>
              <div v-else>
                <b-form-select
                  v-model="questionFormTechnology"
                  style="width:110px"
                  title="technologies"
                  size="sm"
                  class="mt-2"
                  :options="autoGradedTechnologyOptions"
                  :aria-required="!isEdit"
                  @change="initChangeAutoGradedTechnology($event)"
                />
              </div>
              <b-form-select
                v-model="createAutoGradedTechnology"
                style="width:250px"
                title="auto-graded technologies"
                size="sm"
                class="mt-2 ml-3"
                :options="createAutoGradedTechnologyOptions"
                @change="openAutoGradedTechnologyCodeWindow($event)"
              />
            </b-form-row>
          </b-form-group>
          <div v-if="questionForm.technology === 'qti'">
            <b-form-group label="QTI Question Type">
              <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="multiple_choice"
                            @change="initQTIQuestionType($event)"
              >
                Multiple Choice
              </b-form-radio>
              <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="true_false"
                            @change="initQTIQuestionType($event)"
              >
                True/False
              </b-form-radio>
            </b-form-group>
            <b-form-group
              key="prompt"
              label-for="prompt"
            >
              <template v-slot="label">
                <span style="cursor: pointer;">
                  Prompt
                </span>
              </template>
            </b-form-group>
            <div>
              <ckeditor
                id="qtiPrompt"
                v-model="qtiJson.itemBody.prompt"
                tabindex="0"
                required
                :config="richEditorConfig"
                :class="{ 'is-invalid': questionForm.errors.has('qti_prompt')}"
                class="pb-3"
                @namespaceloaded="onCKEditorNamespaceLoaded"
                @ready="handleFixCKEditor()"
                @keydown="questionForm.errors.clear('qti_prompt')"
              />
              <has-error :form="questionForm" field="qti_prompt"/>
              <b-form-group
                v-if="qtiQuestionType === 'true_false'"
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
              <ul v-for="(simpleChoice, index) in simpleChoices" :key="simpleChoice['@attributes'].identifier"
                  class="pt-2"
              >
                <li style="list-style: none;">
                <span v-show="false" class="aaa">{{ simpleChoice['@attributes'].identifier }} {{ simpleChoice.value }}
                {{ qtiJson.responseDeclaration.correctResponse.value }}
                </span>
                  <b-row>
                    <b-col sm="1" align-self="center" class="text-right" @click="updateCorrectResponse(simpleChoice)">

                      <b-icon-circle v-show="simpleChoice['@attributes'].identifier  !== correctResponse" scale="1.5"/>
                      <b-icon-check-circle-fill v-show="simpleChoice['@attributes'].identifier  === correctResponse"
                                                scale="1.5" class="text-success"
                      />
                    </b-col>
                    <b-col sm="10">
                      <b-form-group
                        :label-for="`qti_simple_choice_${index}`"
                        class="mb-0"
                      >
                        <template v-slot:label>
                          <span v-if="qtiQuestionType ==='multiple_choice'">Response {{ index + 1 }}</span>
                          <span v-if="qtiQuestionType==='true_false'" style="font-size:1.25em">
                          {{ simpleChoice.value }}
                        </span>
                        </template>
                        <b-form-textarea
                          v-if="qtiQuestionType ==='multiple_choice'"
                          :id="`qti_simple_choice_${index}`"
                          v-model="simpleChoice.value"
                          placeholder="Enter something..."
                          size="sm"
                          :class="{ 'is-invalid': questionForm.errors.has(`qti_simple_choice_${index}`)}"
                          @keydown="questionForm.errors.clear(`qti_simple_choice_${index}`)"
                        />
                        <has-error :form="questionForm" :field="`qti_simple_choice_${index}`"/>
                      </b-form-group>
                    </b-col>
                    <b-col v-if="qtiQuestionType==='multiple_choice'" sm="1" align-self="center">
                      <b-icon-trash scale="1.5" @click="initDeleteQtiResponse(simpleChoice)"/>
                    </b-col>
                  </b-row>
                </li>
                <li v-if="index === simpleChoices.length-1" style="list-style: none;" class="pt-3">
                  <b-row>
                    <b-col sm="1"/>
                    <b-col sm="10">
                      <b-button v-if="qtiQuestionType === 'multiple_choice'" size="sm" variant="info"
                                @click="addQtiResponse"
                      >
                        Add Response
                      </b-button>
                      <span v-show="false">{{ qtiJson }}</span>
                    </b-col>
                  </b-row>
                </li>
              </ul>
            </div>
          </div>
          <b-form-group
            v-if="!['text','qti'].includes(questionForm.technology)"
            label-cols-sm="3"
            label-cols-lg="2"
            label-for="technology_id"
            :label="questionForm.technology === 'webwork' ? 'File Path' : 'ID'"
          >
            <div v-if="isEdit && !isMe" class="pt-2">
              {{ questionForm.technology_id }}
            </div>
            <b-form-row v-if="!isEdit || isMe">
              <b-form-input
                id="technology_id"
                v-model="questionForm.technology_id"
                type="text"
                :class="{ 'is-invalid': questionForm.errors.has('technology_id'), 'numerical-input' : questionForm.technology !== 'webwork' }"
                @keydown="questionForm.errors.clear('technology_id')"
              />
              <has-error :form="questionForm" field="technology_id"/>
            </b-form-row>
          </b-form-group>
        </div>
        <div v-if="questionForm.question_type === 'assessment'">
          <b-form-group
            label-cols-sm="3"
            label-cols-lg="2"
            label-for="a11y_technology"
          >
            <template v-slot:label>
              <span style="cursor: pointer;" @click="toggleExpanded ('a11y_technology')">
                A11y Auto-Graded Technology
                <font-awesome-icon
                  v-if="!editorGroups.find(editorGroup => editorGroup.id === 'a11y_technology').expanded"
                  :icon="caretRightIcon" size="lg"
                />
                <font-awesome-icon
                  v-if="editorGroups.find(editorGroup => editorGroup.id === 'a11y_technology').expanded"
                  :icon="caretDownIcon" size="lg"
                />
              </span>
            </template>
            <b-form-row v-if="editorGroups.find(editorGroup => editorGroup.id === 'a11y_technology').expanded">
              <div v-if="questionForm.technology ==='text'">
                <b-alert show variant="info">
                  Please first select an auto-graded technology for the original question.
                </b-alert>
              </div>
              <div v-else>
                <div v-if="isEdit && !isMe" class="pt-2">
                  {{
                    a11yAutoGradedTechnologyOptions.find(option => option.value === questionForm.a11y_technology).text
                  }}
                </div>
                <div v-else>
                  <b-form-select
                    id="a11y_technology"
                    v-model="questionForm.a11y_technology"
                    style="width:110px"
                    title="a11y technologies"
                    size="sm"
                    class="mt-2"
                    :options="a11yAutoGradedTechnologyOptions"
                    :aria-required="!isEdit"
                  />
                  <b-form-select
                    v-model="createA11yAutoGradedTechnology"
                    style="width:250px"
                    title="technologies"
                    size="sm"
                    class="mt-2 ml-3"
                    :options="createA11yAutoGradedTechnologyOptions"
                    @change="openAutoGradedTechnologyCodeWindow($event)"
                  />
                </div>
              </div>
            </b-form-row>
          </b-form-group>
          <b-form-group
            v-if="questionForm.a11y_technology !== null"
            label-cols-sm="3"
            label-cols-lg="2"
            label-for="technology_id"
            :label="questionForm.a11y_technology === 'webwork' ? 'A11y File Path' : 'A11y ID'"
          >
            <div v-if="isEdit && !isMe" class="pt-2">
              {{ questionForm.a11y_technology_id }}
            </div>
            <b-form-row v-if="!isEdit || isMe">
              <b-form-input
                id="a11y_technology_id"
                v-model="questionForm.a11y_technology_id"
                type="text"
                :class="{ 'is-invalid': questionForm.errors.has('a11y_technology_id'), 'numerical-input' : questionForm.a11y_technology !== 'webwork' }"
                @keydown="questionForm.errors.clear('a11y_technology_id')"
              />
              <has-error :form="questionForm" field="a11y_technology_id"/>
            </b-form-row>
          </b-form-group>
        </div>
        <b-form-group
          v-for="editorGroup in editorGroups.filter(group => !['technology','a11y_technology','non_technology_text'].includes(group.id))"
          :key="editorGroup.id"
          :label-for="editorGroup.label"
        >
          <template v-slot:label>
            <span style="cursor: pointer;" @click="toggleExpanded (editorGroup.id)">
              {{ editorGroup.label }}
              <font-awesome-icon v-if="!editorGroup.expanded" :icon="caretRightIcon" size="lg"/>
              <font-awesome-icon v-if="editorGroup.expanded" :icon="caretDownIcon" size="lg"/>
            </span>
          </template>
          <ckeditor
            v-show="editorGroup.expanded"
            :id="editorGroup.label"
            v-model="questionForm[editorGroup.id]"
            tabindex="0"
            :config="richEditorConfig"
            @namespaceloaded="onCKEditorNamespaceLoaded"
            @ready="handleFixCKEditor()"
          />
        </b-form-group>
      </b-form>
      <span class="float-right">
        <b-button v-if="isEdit"
                  size="sm"
                  @click="$bvModal.hide(`modal-edit-question-${questionToEdit.id}`)"
        >
          Cancel</b-button>
        <b-button size="sm"
                  variant="info"
                  @click="previewQuestion"
        >
          Preview
        </b-button>
        <b-button size="sm"
                  variant="primary"
                  :disabled="questionExistsInAnotherInstructorsAssignment && !isMe"
                  @click="saveQuestion"
        >Submit</b-button>
      </span>
    </div>
  </div>
</template>

<script>
import AllFormErrors from '~/components/AllFormErrors'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import Form from 'vform/src'
import { fixCKEditor } from '~/helpers/accessibility/fixCKEditor'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCaretDown, faCaretRight } from '@fortawesome/free-solid-svg-icons'
import CKEditor from 'ckeditor4-vue'
import { mapGetters } from 'vuex'
import { defaultLicenseVersionOptions, licenseOptions } from '~/helpers/Licenses'
import ViewQuestions from '~/components/ViewQuestions'
import SavedQuestionsFolders from '~/components/SavedQuestionsFolders'
import QtiJsonQuestionViewer from '~/components/QtiJsonQuestionViewer'
import $ from 'jquery'

const defaultQuestionForm = {
  question_type: 'assessment',
  public: '0',
  title: '',
  author: '',
  tags: [],
  technology: 'text',
  technology_id: '',
  non_technology_text: '',
  text_question: null,
  a11y_technology: null,
  a11y_technology_id: '',
  answer_html: null,
  solution_html: null,
  notes: null,
  hint: null,
  license: null,
  license_version: null
}

let createAutoGradedTechnologyOptions
let createA11yAutoGradedTechnologyOptions
createAutoGradedTechnologyOptions = [{ value: null, text: 'Create Auto-graded code' }]
createA11yAutoGradedTechnologyOptions = [{ value: null, text: 'Create A11y Auto-graded code' }]

let commonTechnologyOptions = [{ value: 'https://studio.libretexts.org/node/add/h5p', text: 'H5P' },
  { value: 'https://webwork.libretexts.org/webwork2', text: 'WeBWork' },
  { value: 'https://imathas.libretexts.org/imathas/course/moddataset.php', text: 'IMathAS' }]

for (let i = 0; i < commonTechnologyOptions.length; i++) {
  createAutoGradedTechnologyOptions.push(commonTechnologyOptions[i])
  createA11yAutoGradedTechnologyOptions.push(commonTechnologyOptions[i])
}

const simpleChoiceJson = {
  '@attributes': {
    'identifier': '',
    'title': '',
    'adaptive': 'false',
    'timeDependent': 'false'
  },
  'responseDeclaration': {
    '@attributes': {
      'identifier': 'RESPONSE',
      'cardinality': 'single',
      'baseType': 'identifier'
    },
    'correctResponse': {
      'value': ''
    }
  },
  'outcomeDeclaration': {
    '@attributes': {
      'identifier': 'SCORE',
      'cardinality': 'single',
      'baseType': 'float'
    }
  },
  'itemBody': {
    'prompt': '',
    'choiceInteraction': {
      '@attributes': {
        'responseIdentifier': 'RESPONSE',
        'shuffle': 'false',
        'maxChoices': '1'
      }

    }
  }
}

export default {
  name: 'CreateQuestion',
  components: {
    FontAwesomeIcon,
    ckeditor: CKEditor.component,
    AllFormErrors,
    ViewQuestions,
    SavedQuestionsFolders,
    QtiJsonQuestionViewer
  },
  props: {
    modalId: {
      type: String,
      default: ''
    },
    questionToEdit: {
      type: Object,
      default: () => {
      }
    },
    parentGetMyQuestions: {
      type: Function,
      default: () => {
      }
    },
    questionExistsInOwnAssignment: {
      type: Boolean,
      default: false
    },
    questionExistsInAnotherInstructorsAssignment: {
      type: Boolean,
      default: false
    }
  },
  data: () => ({
    questionFormTechnology: 'text',
    qtiQuestionType: 'multiple_choice',
    trueFalseLanguage: 'English',
    trueFalseLanguageOptions: [
      { text: 'English', value: 'English' },
      { text: 'Spanish', value: 'Spanish' },
      { text: 'French', value: 'French' },
      { text: 'Italian', value: 'Italian' },
      { text: 'German', value: 'German' }
    ],
    qtiPrompt: '',
    simpleChoiceToRemove: {},
    correctResponse: '',
    simpleChoices: [],
    qtiJson: {},
    sourceExpanded: false,
    caretDownIcon: faCaretDown,
    caretRightIcon: faCaretRight,
    createAutoGradedTechnology: null,
    createA11yAutoGradedTechnology: null,
    createA11yAutoGradedTechnologyOptions: createA11yAutoGradedTechnologyOptions,
    createAutoGradedTechnologyOptions: createAutoGradedTechnologyOptions,
    savedQuestionsFolderKey: 0,
    questionToView: {},
    questionToViewKey: 0,
    questionsFormKey: 0,
    isEdit: false,
    tag: '',
    toggleColors: window.config.toggleColors,
    view: 'basic',
    licenseOptions: licenseOptions,
    defaultLicenseVersionOptions: defaultLicenseVersionOptions,
    licenseVersionOptions: [],
    editorGroups: [
      { id: 'technology', expanded: false },
      { id: 'a11y_technology', expanded: false },
      { id: 'non_technology_text', label: 'Source', expanded: false },
      { label: 'Text Question', id: 'text_question', expanded: false },
      { label: 'Answer', id: 'answer_html', expanded: false },
      { label: 'Solution', id: 'solution_html', expanded: false },
      { label: 'Hint', id: 'hint', expanded: false },
      { label: 'Notes', id: 'notes', expanded: false }
    ],
    questionForm: new Form(defaultQuestionForm),
    allFormErrors: [],
    autoGradedTechnologyOptions: [
      { value: 'text', text: 'None' },
      { value: 'qti', text: 'QTI' },
      { value: 'webwork', text: 'WeBWorK' },
      { value: 'h5p', text: 'H5P' },
      { value: 'imathas', text: 'IMathAS' }
    ],
    a11yAutoGradedTechnologyOptions: [
      { value: null, text: 'None' },
      { value: 'qti', text: 'QTI' },
      { value: 'webwork', text: 'WeBWorK' },
      { value: 'h5p', text: 'H5P' },
      { value: 'imathas', text: 'IMathAS' }
    ],
    richEditorConfig: {
      toolbar: [
        { name: 'image', items: ['Image'] },
        { name: 'math', items: ['Mathjax'] },
        { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', '-', 'Undo', 'Redo'] },
        {
          name: 'basicstyles',
          items: ['Bold', 'Italic', 'Underline', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat']
        },
        {
          name: 'paragraph',
          items: ['BulletedList', 'NumberedList', '-', 'Outdent', 'Indent', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
        },
        { name: 'links', items: ['Link', 'Unlink', 'IFrame', 'Embed'] },
        { name: 'insert', items: ['Table', 'HorizontalRule', 'Smiley', 'SpecialChar'] },
        { name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize'] },
        { name: 'colors', items: ['TextColor', 'BGColor'] },
        { name: 'extra', items: ['Source', 'Maximize'] }
      ],
      embed_provider: '//ckeditor.iframe.ly/api/oembed?url={url}&callback={callback}',
      // Configure the Enhanced Image plugin to use classes instead of styles and to disable the
      // resizer (because image size is controlled by widget styles or the image takes maximum
      // 100% of the editor width).
      image2_alignClasses: ['image-align-left', 'image-align-center', 'image-align-right'],
      image2_altRequired: true,
      removeButtons: '',
      extraPlugins: 'mathjax,embed,dialog,contextmenu,liststyle,image2',
      mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
      filebrowserUploadUrl: '/api/ckeditor/upload',
      filebrowserUploadMethod: 'form'
    }
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  async mounted () {
    this.$nextTick(() => {
      // want to add more text to this
      $('#required_text').replaceWith($('<span>' + document.getElementById('required_text').innerText + '</span>'))
    })

    this.questionsFormKey++
    console.log(this.questionToEdit)
    if (this.questionToEdit && Object.keys(this.questionToEdit).length !== 0) {
      this.isEdit = true
      if (this.questionToEdit.qti_json) {
        this.qtiJson = JSON.parse(this.questionToEdit.qti_json)
        this.qtiPrompt = this.qtiJson.itemBody['prompt']
        this.simpleChoices = this.qtiJson.itemBody.choiceInteraction.simpleChoice
        this.correctResponse = this.qtiJson.responseDeclaration.correctResponse.value
        let qtiQuestionType = this.qtiJson['@attributes']['question_type']
        if (qtiQuestionType && qtiQuestionType === 'true_false') {
          this.trueFalseLanguage = this.qtiJson['@attributes']['language']
          this.qtiQuestionType = 'true_false'
        }
      }
      for (let i = 0; i < this.editorGroups.length; i++) {
        let editorGroup = this.editorGroups[i]
        switch (editorGroup.id) {
          case ('qti'):
            editorGroup.expanded = this.qtiPrompt
            break
          case ('technology'):
            editorGroup.expanded = this.questionToEdit.technology !== 'text'
            break
          case ('a11y_technology'):
            editorGroup.expanded = this.questionToEdit.a11y_technology
            break
          default:
            editorGroup.expanded = this.questionToEdit[editorGroup.id]
        }
      }

      console.log(this.questionToEdit)

      if (this.questionToEdit.license_version) {
        this.questionToEdit.license_version = Number(this.questionToEdit.license_version).toFixed(1) // some may be saved as 4 vs 4.0 in the database
      }
      this.questionForm = new Form(this.questionToEdit)
      this.questionFormTechnology = this.questionForm.technology
      console.log(this.questionForm)
      console.log(this.questionToEdit)
      this.updateLicenseVersions()
      if (this.questionToEdit.tags.length === 1 && this.questionToEdit.tags[0] === 'none') {
        this.questionForm.tags = []
      }
    } else {
      this.resetQuestionForm('assessment')
      this.initQTIQuestionType('multiple_choice')
    }
  },
  methods: {
    initChangeAutoGradedTechnology (technology) {
      if (technology === 'qti') {
        if (this.questionForm.non_technology_text) {
          this.$noty.info('Please remove any Source before changing to QTI.  You can always move your Source into the Prompt of your QTI question.')
          this.questionFormTechnology = this.questionForm.technology
        } else {
          this.editorGroups.find(editorGroup => editorGroup.id === 'non_technology_text').expanded = false
          this.questionForm.technology = 'qti'
        }
      } else {
        this.questionForm.technology = this.questionFormTechnology
      }
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
      this.qtiJson.itemBody.choiceInteraction.simpleChoice.find(choice => choice['@attributes'].identifier === 'adapt-qti-true').value = trueResponse
      this.qtiJson.itemBody.choiceInteraction.simpleChoice.find(choice => choice['@attributes'].identifier === 'adapt-qti-false').value = falseResponse
    },
    initQTIQuestionType (questionType) {
      this.qtiJson = simpleChoiceJson
      switch (questionType) {
        case ('multiple_choice'):
          this.qtiJson.itemBody.choiceInteraction.simpleChoice = [
            {
              '@attributes': {
                'identifier': 'adapt-qti-1'
              },
              'value': ''
            }
          ]
          if (this.qtiJson['@attributes']['language']) {
            delete this.qtiJson['@attributes']['language']
          }
          break
        case ('true_false'):
          this.qtiJson['@attributes']['language'] = this.trueFalseLanguage
          this.qtiJson['@attributes']['question_type'] = 'true_false'
          this.qtiJson.itemBody.choiceInteraction.simpleChoice = [
            {
              '@attributes': {
                'identifier': 'adapt-qti-true'
              },
              'value': 'True'
            },
            {
              '@attributes': {
                'identifier': 'adapt-qti-false'
              },
              'value': 'False'
            }
          ]
          this.translateTrueFalse(this.trueFalseLanguage)
          break
        default:
          alert(`Need to update the code for ${questionType}`)
      }
      this.qtiPrompt = ''
      this.simpleChoices = this.qtiJson.itemBody.choiceInteraction.simpleChoice
      this.correctResponse = ''
    },
    initDeleteQtiResponse (simpleChoiceToRemove) {
      if (this.qtiJson.itemBody.choiceInteraction.simpleChoice.length === 1) {
        this.$noty.info('There must be at least one response.')
        return false
      }
      if (simpleChoiceToRemove['@attributes'].identifier === this.qtiJson.responseDeclaration.correctResponse.value) {
        this.$noty.info('Please choose a different correct answer before removing this response.')
        return false
      }
      this.simpleChoiceToRemove = simpleChoiceToRemove
      if (this.simpleChoiceToRemove.value === '') {
        this.deleteQtiResponse()
        return false
      }
      this.$bvModal.show(`confirm-remove-simple-choice-${this.modalId}`)
    },
    deleteQtiResponse () {
      this.qtiJson.itemBody.choiceInteraction.simpleChoice = this.qtiJson.itemBody.choiceInteraction.simpleChoice.filter(item => item['@attributes'].identifier !== this.simpleChoiceToRemove['@attributes'].identifier)
      this.simpleChoices = this.qtiJson.itemBody.choiceInteraction.simpleChoice
      this.$bvModal.hide(`confirm-remove-simple-choice-${this.modalId}`)
    },
    addQtiResponse () {
      let currentIdentifiers
      let numIdentifiers
      currentIdentifiers = []
      numIdentifiers = this.qtiJson.itemBody.choiceInteraction.simpleChoice.length
      for (let i = 0; i < numIdentifiers - 1; i++) {
        currentIdentifiers.push(this.qtiJson.itemBody.choiceInteraction.simpleChoice[i]['@attributes'].identifier)
      }

      let identifier = `adapt-qti-${numIdentifiers + 1}`
      while (currentIdentifiers.includes(identifier)) {
        identifier = identifier + '1'
      }
      let response = {
        '@attributes': {
          'identifier': identifier
        },
        'value': ''
      }
      this.qtiJson.itemBody.choiceInteraction.simpleChoice.push(response)
    },
    deleteQtiTechnology () {
      this.qtiJson = {}
      this.correctResponse = ''
      this.simpleChoices = []
      this.qtiPrompt = ''
      this.$bvModal.hide(`modal-confirm-delete-qti-${this.modalId}`)
      this.editorGroups.find(editorGroup => editorGroup.id === 'technology').expanded = false
      this.questionForm.technology = 'text'
    },
    updateCorrectResponse (simpleChoice) {
      this.correctResponse = simpleChoice['@attributes'].identifier
      this.qtiJson.responseDeclaration.correctResponse.value = simpleChoice['@attributes'].identifier
    },
    isCorrect (simpleChoice) {
      return this.correctResponse === simpleChoice['@attributes'].identifier
    },
    qtiType (qtiJson) {
      if (qtiJson.itemBody && !qtiJson.itemBody.simpleChoice) {

      }
    },
    toggleExpanded (id) {
      if (id === 'non_technology_text' && this.questionForm.technology === 'qti') {
        this.$noty.info('Please enter your Source within the Prompt textarea.')
        return false
      }
      let editorGroup = this.editorGroups.find(group => group.id === id)
      if (editorGroup && editorGroup.expanded) {
        switch (id) {
          case ('technology'):
            if (this.qtiJson && Object.keys(this.qtiJson).length !== 0) {
              this.$bvModal.show(`modal-confirm-delete-qti-${this.modalId}`)
              return false
            }
            if (this.questionForm.technology !== 'text') {
              this.$noty.info('If you would like to hide the auto-graded technology input area, make sure that no technology is chosen.')
              return false
            }
            break
          case ('a11y_technology'):
            if (this.questionForm.a11y_technology !== null) {
              this.$noty.info('If you would like to hide the a11y technology input area, make sure that no a11y technology is chosen.')
              return false
            }
            break
          default:
            if (this.questionForm[id].length) {
              this.$noty.info(`If you would like to hide the ${editorGroup.label} input area, please first remove any text.`)
              return false
            }
        }
      }
      this.editorGroups.find(group => group.id === id).expanded = !editorGroup.expanded
    },
    openAutoGradedTechnologyCodeWindow (url) {
      if (url) {
        window.open(url, '_blank')
      }
    },
    reloadCreateQuestionSavedQuestionsFolders (type) {
      this.savedQuestionsFolderKey++
    },
    setMyCoursesFolder (myCoursesFolder) {
      this.questionForm.folder_id = myCoursesFolder
    },
    resetQuestionForm (questionType) {
      let folderId
      folderId = this.questionForm.folder_id
      if (questionType === 'exposition') {
        this.questionForm.technology = this.questionFormTechnology = 'text'
        this.questionForm.technology_id = ''
        this.questionForm.non_technology_text = ''
        this.questionForm.text_question = null
        this.questionForm.a11y_technology = null
        this.questionForm.a11y_technology_id = ''
        this.questionForm.answer_html = null
        this.questionForm.solution_html = null
        this.questionForm.hint = null
      } else {
        this.questionForm = new Form(defaultQuestionForm)
        this.questionForm.author = this.user.first_name + ' ' + this.user.last_name
      }
      this.questionForm.question_type = questionType
      this.questionForm.folder_id = folderId
    },
    getQuestionType () {
      if (this.questionForm.question_type === 'auto_graded') {
        return 'Auto-Graded'
      } else if (this.questionForm.question_type === 'open_ended') {
        return 'Open-ended'
      } else if (this.questionForm.question_type === 'frankenstein') {
        return 'Frankenstein'
      } else {
        return 'Question type not valid; please contact us.'
      }
    },
    async previewQuestion () {
      if (this.questionForm.technology !== 'text' && !this.questionForm.technology_id && this.questionForm.technology !== 'qti') {
        let identifier = this.questionForm.technology === 'webwork' ? 'A File Path' : 'An ID'
        let message = `${identifier} is required to preview this question.`
        this.questionForm.errors.set('technology_id', message)
        return false
      }
      try {
        if (this.questionForm.technology !== 'qti') {
          const { data } = await this.questionForm.post('/api/questions/preview')
          this.questionToView = data.question
        } else {
          this.questionToView = this.qtiJson
        }
        this.$bvModal.show(this.modalId)
        this.$nextTick(() => {
          MathJax.Hub.Queue(['Typeset', MathJax.Hub])
        })
        console.log(this.questionToView)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async saveQuestion () {
      if (this.questionForm.technology === 'qti') {
        for (const property in this.questionForm) {
          if (property.startsWith('qti_simple_choice_')) {
            // clean up in case it's been deleted then recreate from the json below
            delete this.questionForm[property]
          }
        }

        this.questionForm.qti_prompt = this.qtiJson.itemBody.prompt
        this.questionForm.qti_correct_response = this.qtiJson.responseDeclaration.correctResponse && this.qtiJson.responseDeclaration.correctResponse.value
        for (let i = 0; i < this.qtiJson.itemBody.choiceInteraction.simpleChoice.length; i++) {
          console.log(this.qtiJson.itemBody.choiceInteraction.simpleChoice[i])
          this.questionForm[`qti_simple_choice_${i}`] = this.qtiJson.itemBody.choiceInteraction.simpleChoice[i].value
        }
        if (this.qtiQuestionType === 'true_false') {
          this.qtiJson['@attributes']['language'] = this.trueFalseLanguage
          this.qtiJson['@attributes']['question_type'] = 'true_false'
        }
        this.questionForm.qti_json = JSON.stringify(this.qtiJson)
      } else {
        this.questionForm.qti_json = null
      }
      try {
        const { data } = this.isEdit
          ? await this.questionForm.patch(`/api/questions/${this.questionForm.id}`)
          : await this.questionForm.post('/api/questions')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.resetQuestionForm('assessment')
          this.tag = ''
          this.questionForm.tags.length = 0
          if (this.isEdit) {
            this.$bvModal.hide(`modal-edit-question-${this.questionToEdit.id}`)
            this.parentGetMyQuestions()
          }
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.questionForm.errors.flatten()
          this.$bvModal.show(`modal-form-errors-questions-form-${this.questionsFormKey}`)
        }
      }
    },
    removeTag (chosenTag) {
      this.questionForm.tags = this.questionForm.tags.filter(tag => tag !== chosenTag)
      this.$noty.info(`${chosenTag} has been removed.`)
    },
    addTag () {
      if (!this.questionForm.tags.includes(this.tag)) {
        this.questionForm.tags.push(this.tag)
      } else {
        this.$noty.info(`${this.tag} is already on your list of tags.`)
      }
      this.tag = ''
    },
    updateLicenseVersions () {
      this.licenseVersionOptions = this.defaultLicenseVersionOptions.filter(version => version.licenses.includes(this.questionForm.license))

      if (this.questionForm.license !== null) {
        if (['ccby', 'ccbyncnd', 'ccbynd', 'ccbysa', 'ccbyncsa', 'ccbync', 'imathascomm'].includes(this.questionForm.license)) {
          this.questionForm.license_version = '4.0'
        } else if (this.questionForm.license === 'gnufdl') {
          this.questionForm.license_version = '1.3'
        } else if (this.questionForm.license === 'gnu') {
          this.questionForm.license_version = '3.0'
        }
      }
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
.numerical-input {
  width: 150px;
}
</style>
