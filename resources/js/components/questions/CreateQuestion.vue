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
      :id="modalId"
      title="Preview Question"
      :size="questionForm.solution_html ? 'xl' : 'lg'"
      ok-title="OK"
      ok-only
    >
      <ViewQuestions :key="questionToViewKey"
                     :question-to-view="questionToView"
      />
      <div class="mt-section" v-if="questionForm.solution_html">
        <h2 class="editable">
          Solution
        </h2>
        <div v-html="questionForm.solution_html"></div>
      </div>
    </b-modal>
    <RequiredText/>
    <b-form-group
      label-cols-sm="3"
      label-cols-lg="2"
      label-for="question_type"
      :label="isEdit ? 'Question Type' : 'Question Type*'"
    >
      <b-form-row class="mt-2">
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
          label-for="non_technology_text"
          :label="questionForm.question_type === 'assessment' ? 'Source (Optional)' : 'Source*'"
        >
          <ckeditor
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
        </b-form-group>
        <div v-if="questionForm.question_type === 'assessment'">
          <b-form-group
            label-cols-sm="3"
            label-cols-lg="2"
            label-for="technology"
            :label="isEdit ? 'Auto-Graded Technology' : 'Auto-Graded Technology*'"
          >
            <b-form-row>
              <div v-if="isEdit && !isMe" class="pt-2">
                {{ autoGradedTechnologyOptions.find(option => option.value === questionForm.technology).text }}
              </div>
              <div v-else>
                <b-form-select
                  v-model="questionForm.technology"
                  style="width:110px"
                  title="technologies"
                  size="sm"
                  class="mt-2"
                  :options="autoGradedTechnologyOptions"
                  :aria-required="!isEdit"
                />
              </div>
            </b-form-row>
          </b-form-group>
          <b-form-group
            v-if="questionForm.technology !== 'text'"
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
        <div v-if="questionForm.question_type === 'assessment'">
          <b-form-row>
            <toggle-button
              :width="140"
              class="mt-2"
              :value="view === 'basic'"
              :sync="true"
              :font-size="14"
              :margin="4"
              :color="toggleColors"
              :labels="{checked: 'Collapsed View', unchecked: 'Expanded View'}"
              @change="view = view === 'basic' ? 'advanced' : 'basic'"
            />
          </b-form-row>
        </div>
        <div v-if="questionForm.question_type === 'assessment' && view === 'advanced'">
          <div v-if="questionForm.technology !== 'text'">
            <b-form-group
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="a11y_technology"
              label="A11y Auto-Graded Technology"
            >
              <b-form-row>
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
        </div>
        <b-form-group
          v-for="editorGroup in editorGroups"
          v-show="view === 'advanced'"
          :key="editorGroup.id"
          :label-for="editorGroup.label"
          :label="editorGroup.label"
        >
          <ckeditor
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
import CKEditor from 'ckeditor4-vue'
import { mapGetters } from 'vuex'
import { licenseOptions, defaultLicenseVersionOptions } from '~/helpers/Licenses'
import { ToggleButton } from 'vue-js-toggle-button'
import ViewQuestions from '~/components/ViewQuestions'
import SavedQuestionsFolders from '~/components/SavedQuestionsFolders'

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
  hint: null,
  license: null,
  license_version: null
}

export default {
  name: 'CreateQuestion',
  components: {
    ckeditor: CKEditor.component,
    ToggleButton,
    AllFormErrors,
    ViewQuestions,
    SavedQuestionsFolders
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
      { label: 'Text Question', id: 'text_question' },
      { label: 'Answer', id: 'answer_html' },
      { label: 'Solution', id: 'solution_html' },
      { label: 'Hint', id: 'hint' },
      { label: 'Notes', id: 'notes' }
    ],
    questionForm: new Form(defaultQuestionForm),
    allFormErrors: [],
    autoGradedTechnologyOptions: [
      { value: 'text', text: 'None' },
      { value: 'webwork', text: 'WeBWorK' },
      { value: 'h5p', text: 'H5P' },
      { value: 'imathas', text: 'IMathAS' }
    ],
    a11yAutoGradedTechnologyOptions: [
      { value: null, text: 'None' },
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
    this.questionsFormKey++
    console.log(this.questionToEdit)
    if (this.questionToEdit && Object.keys(this.questionToEdit).length !== 0) {
      this.isEdit = true
      let advancedOptions = [
        'text_question',
        'a11y_technology',
        'a11y_technology_id',
        'answer_html',
        'solution_html',
        'hint',
        'notes'
      ]
      for (let i = 0; i < advancedOptions.length; i++) {
        if (this.questionToEdit[advancedOptions[i]]) {
          this.view = 'advanced'
        }
      }
      this.questionForm = new Form(this.questionToEdit)
      console.log(this.questionForm)
      console.log(this.questionToEdit)
      this.updateLicenseVersions()
      if (this.questionToEdit.tags.length === 1 && this.questionToEdit.tags[0] === 'none') {
        this.questionForm.tags = []
      }
    } else {
      this.resetQuestionForm('assessment')
    }
  },
  methods: {
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
        this.questionForm.technology = 'text'
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
      if (this.questionForm.technology !== 'text' && !this.questionForm.technology_id.length) {
        let identifier = this.questionForm.technology === 'webwork' ? 'A File Path' : 'An ID'
        let message = `${identifier} is required to preview this question.`
        this.questionForm.errors.set('technology_id', message)
        return false
      }
      try {
        const { data } = await this.questionForm.post('/api/questions/preview')
        this.questionToView = data.question
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
        if (['ccby', 'ccbyncnd', 'ccbynd', 'ccbysa', 'ccbyncsa', 'ccbync'].includes(this.questionForm.license)) {
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
