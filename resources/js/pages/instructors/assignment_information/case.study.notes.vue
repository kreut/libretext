<template>
  <div>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading">
        <PageTitle title="Case Study Notes" />
        <b-modal id="modal-confirm-remove-updated-patient-information"
                 title="Remove Updated Patient Information"
        >
          <p>Please confirm that you would like to remove the updated patient information.</p>
          <template #modal-footer>
            <b-button
              size="sm"
              class="float-right"
              @click="$bvModal.hide('modal-confirm-remove-updated-patient-information')"
            >
              Cancel
            </b-button>
            <b-button
              size="sm"
              class="float-right"
              variant="danger"
              @click="removeUpdatedPatientInformation"
            >
              Do it!
            </b-button>
          </template>
        </b-modal>
        <b-modal id="modal-confirm-reset-notes"
                 title="Confirm Reset Case Study Notes"
        >
          <p>
            If you reset your Case Study Notes, the patient information and notes will no longer be available for your
            students. This cannot be undone.
          </p>
          <template #modal-footer>
            <b-button
              size="sm"
              class="float-right"
              @click="$bvModal.hide('modal-confirm-reset-notes')"
            >
              Cancel
            </b-button>
            <b-button
              size="sm"
              class="float-right"
              variant="danger"
              @click="resetNotes"
            >
              Do it!
            </b-button>
          </template>
        </b-modal>
        <b-modal id="modal-confirm-remove-type-from-case-study-notes"
                 title="Confirm Remove Item From the Case Study Notes"
                 size="lg"
        >
          Please confirm that you would like to remove: {{ itemTypeToRemove }}. Please note that this action cannot
          be undone.
          <template #modal-footer>
            <b-button
              size="sm"
              class="float-right"
              @click="$bvModal.hide('modal-confirm-remove-type-from-case-study-notes')"
            >
              Cancel
            </b-button>
            <b-button
              size="sm"
              class="float-right"
              variant="danger"
              @click="itemTypeToRemove === 'Patient Information' ? updateShowPatientUpdatedInformation(false) : removeTypeFromCaseStudyNotes()"
            >
              Remove Item
            </b-button>
          </template>
        </b-modal>

        <b-modal id="modal-confirm-remove-patient-information-from-case-study-notes"
                 title="Confirm Remove Item From the Case Study Notes"
                 size="lg"
        >
          Please confirm that you would like to remove the Patient Information notes. Please note that this
          action cannot
          be undone.
          <template #modal-footer>
            <b-button
              size="sm"
              class="float-right"
              @click="$bvModal.hide('modal-confirm-remove-patient-information-from-case-study-notes')"
            >
              Cancel
            </b-button>
            <b-button
              size="sm"
              class="float-right"
              variant="danger"
              @click="deletePatientInformation()"
            >
              Remove Item
            </b-button>
          </template>
        </b-modal>
        <b-modal id="modal-confirm-remove-item-from-case-study-notes"
                 title="Confirm Remove Item From the Case Study Notes"
                 size="lg"
        >
          Please confirm that you would like to remove one of your {{ itemTypeToRemove }} notes. Please note that this
          action cannot
          be undone.
          <template #modal-footer>
            <b-button
              size="sm"
              class="float-right"
              @click="$bvModal.hide('modal-confirm-remove-item-from-case-study-notes')"
            >
              Cancel
            </b-button>
            <b-button
              size="sm"
              class="float-right"
              variant="danger"
              @click="itemTypeToRemove === 'Patient Information' ? updateShowPatientUpdatedInformation(false) : removeCaseStudyNotesItemById()"
            >
              Remove Item
            </b-button>
          </template>
        </b-modal>

        <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-case-study-notes" />
        <b-alert show>
          This concept is currently set-up for Case Study notes which accompany Next Gen NCLEX (nursing) style
          questions.
          It will be expanded in the coming weeks to work with more general Case Studies.
        </b-alert>
        <p>
          Optionally add Common Question Text which will appear with each question. Then, add your initial Case Study
          Notes. You can add the same type of notes multiple times if the information changes throughout the assignment.
        </p>
        <toggle-button
          class="mt-1 mr-2"
          :width="68"
          :value="view"
          :sync="true"
          :font-size="14"
          :margin="4"
          :color="toggleColors"
          :labels="{checked: 'View', unchecked: 'Edit'}"
          @change="updateView()"
        />
        <div v-show="view">
          <b-pagination
            v-model="currentPage"
            :total-rows="questions.length"
            per-page="1"
            limit="22"
            first-number
            last-number
            @change="initGetCaseStudyNotesByQuestion"
          />
          <p>{{ commonQuestionTextForm.common_question_text }}</p>
          <CaseStudyNotesViewer :key="`case-study-notes-viewer-key-${currentPage}`"
                                :case-study-notes="caseStudyNotesByQuestion"
          />
          <div v-if="!caseStudyNotes.length">
            <b-alert show variant="info">
              You currently have no Case Study Notes.
            </b-alert>
          </div>
        </div>
        <div v-show="!view">
          <b-form-group
            label-cols-sm="4"
            label-cols-lg="3"
            label-for="common_question_text"
          >
            <template v-slot:label>
              Common Question Text
              <QuestionCircleTooltip id="common_question_text" />
              <b-tooltip target="common_question_text"
                         delay="250"
              >
                Optionally add text which will appear at the top of every question that has an associated Case Study.
              </b-tooltip>
            </template>
            <b-form-row>
              <b-textarea
                id="common_question_text"
                v-model="commonQuestionTextForm.common_question_text"
                style="width: 500px"
                type="text"
              />

              <b-button size="sm" variant="primary" style="margin:20px" @click="saveCommonQuestionText">
                Save
              </b-button>
            </b-form-row>
          </b-form-group>
          <b-form-group>
            <b-form-select id="type-of-notes"
                           v-model="type"
                           size="sm"
                           style="width:230px"
                           :options="caseStudyOptions"
                           @change="addNewCaseStudyNotes($event)"
            />
          </b-form-group>
          <b-card
            v-if="showPatientInformation || caseStudyNotes.length"
            class="mb-4"
          >
            <template #header>
              <h2 class="h7">
                Case Study Notes
                <b-button variant="primary" class="float-right pl-2" size="sm" @click="saveAll()">
                  Save
                </b-button>
                <b-button variant="info" class="float-right mr-2" size="sm" @click="initResetNotes">
                  Reset
                </b-button>
              </h2>
            </template>
            <b-card-text>
              <b-tabs>
                <b-tab v-if="showPatientInformation" :key="`tab-patient_information-${tabKey}`"
                       :active="activeTab === 'patient_information'"
                >
                  <template #title>
                    <span
                      :class="{'text-danger': errorsByType['patient_information'] && errorsByType['patient_information'].length}"
                    > Patient Information </span>
                    <b-icon-trash
                      scale=".75"
                      class="text-muted"
                      @click="initRemovePatientInformationFromCaseStudyNotes()"
                    />
                  </template>
                  <div v-if="errorsByType['patient_information'] && errorsByType['patient_information'].length"
                       class="p-2 text-danger"
                  >
                    <div>
                      Please fix the following error<span v-if="errorsByType['patient_information'].length > 1">s</span>:
                    </div>
                    <ul>
                      <li v-for="(error, errorIndex) in errorsByType['patient_information']"
                          :key="`error-${errorIndex}`"
                      >
                        {{ error }}
                      </li>
                    </ul>
                  </div>
                  <b-form class="mt-4">
                    <b-form-row>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label="Name"
                          label-for="name"
                          label-size="sm"
                        >
                          <b-form-input
                            id="name"
                            v-model="patientInfoForm.name"
                            :class="{ 'is-invalid': patientInfoForm.errors.has('name') }"
                            class="form-control"
                            type="text"
                            name="name"
                            size="sm"
                            required
                            @keydown="patientInfoForm.errors.clear('name')"
                          />
                          <has-error :form="patientInfoForm" field="name" />
                        </b-form-group>
                      </b-col>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label-size="sm"
                          label="Code Status"
                          label-for="code_status"
                        >
                          <div class="mt-1">
                            <b-form-select

                              id="code_status"
                              v-model="patientInfoForm.code_status"
                              :options="codeStatusOptions"
                              :class="{ 'is-invalid': patientInfoForm.errors.has('code_status') }"
                              cols="2"
                              size="sm"
                              @change="patientInfoForm.errors.clear('code_status')"
                            />
                            <has-error :form="patientInfoForm" field="code_status" />
                          </div>
                        </b-form-group>
                      </b-col>
                    </b-form-row>
                    <b-form-row>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label="Gender"
                          label-for="gender"
                          label-size="sm"
                        >
                          <b-form-input
                            id="gender"
                            v-model="patientInfoForm.gender"
                            :class="{ 'is-invalid': patientInfoForm.errors.has('gender') }"
                            class="form-control"
                            type="text"
                            name="gender"
                            size="sm"
                            required
                            @keydown="patientInfoForm.errors.clear('gender')"
                          />
                          <has-error :form="patientInfoForm" field="gender" />
                        </b-form-group>
                      </b-col>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label-size="sm"
                          label="Allergies"
                          label-for="allergies"
                        >
                          <b-form-input
                            id="allergies"
                            v-model="patientInfoForm.allergies"
                            :class="{ 'is-invalid': patientInfoForm.errors.has('allergies') }"
                            class="form-control"
                            size="sm"
                            type="text"
                            name="allergies"
                            @keydown="patientInfoForm.errors.clear('allergies')"
                          />
                          <has-error :form="patientInfoForm" field="allergies" />
                        </b-form-group>
                      </b-col>
                    </b-form-row>
                    <b-form-row>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label="Age"
                          label-for="age"
                          label-size="sm"
                        >
                          <b-form-input
                            id="age"
                            v-model="patientInfoForm.age"
                            :class="{ 'is-invalid': patientInfoForm.errors.has('age') }"
                            class="form-control"
                            type="text"
                            name="age"
                            size="sm"
                            required
                            @keydown="patientInfoForm.errors.clear('age')"
                          />
                          <has-error :form="patientInfoForm" field="age" />
                        </b-form-group>
                      </b-col>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label-size="sm"
                          label-for="weight"
                        >
                          <template #label>
                            Weight
                          </template>
                          <b-form-row v-show="!view">
                            <b-form-input
                              id="weight"
                              v-model="patientInfoForm.weight"
                              style="width: 90px"
                              :class="{ 'is-invalid': patientInfoForm.errors.has('weight') }"
                              class="form-control mr-3 ml-1"
                              size="sm"
                              type="text"
                              name="allergies"
                              @keydown="patientInfoForm.errors.clear('weight')"
                            />
                            <has-error :form="patientInfoForm" field="weight" />
                            <b-form-radio-group v-model="patientInfoForm.weight_units">
                              <b-form-radio value="lb">
                                lb
                              </b-form-radio>
                              <b-form-radio value="kg">
                                kg
                              </b-form-radio>
                            </b-form-radio-group>
                          </b-form-row>
                        </b-form-group>
                      </b-col>
                    </b-form-row>
                    <b-form-row>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label="DOB"
                          label-for="DOB"
                          label-size="sm"
                        >
                          <b-form-input
                            id="DOB"
                            v-model="patientInfoForm.dob"
                            :class="{ 'is-invalid': patientInfoForm.errors.has('dob') }"
                            class="form-control"
                            type="text"
                            name="DOB"
                            size="sm"
                            required
                            @keydown="patientInfoForm.errors.clear('dob')"
                          />
                          <has-error :form="patientInfoForm" field="dob" />
                        </b-form-group>
                      </b-col>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label-size="sm"
                          label="BMI"
                          label-for="BMI"
                        >
                          <b-form-input
                            id="BMI"
                            v-model="patientInfoForm.bmi"
                            :class="{ 'is-invalid': patientInfoForm.errors.has('bmi') }"
                            class="form-control"
                            size="sm"
                            type="text"
                            name="BMI"
                            @keydown="patientInfoForm.errors.clear('bmi')"
                          />
                          <has-error :form="patientInfoForm" field="bmi" />
                        </b-form-group>
                      </b-col>
                    </b-form-row>
                  </b-form>
                  <b-form v-if="showUpdatedPatientInformation">
                    <b-form-group
                      class="pt-3"
                      label-cols-sm="3"
                      label-cols-lg="2"
                      label-size="sm"
                      label="Updated Patient Information"
                      label-for="updated_patient_information"
                    >
                      <b-form-row class="pt-1">
                        <b-col lg="8">
                          <b-form-select id="updated_patient_information"
                                         v-model="patientInfoForm.first_application_of_updated_information"
                                         :options="firstApplicationOptions"
                                         size="sm"
                          />
                        </b-col>
                        <div class="mt-1 pl-2">
                          <b-icon-trash scale="1.25" @click="initRemovePatientUpdatedInformation()" />
                        </div>
                      </b-form-row>
                    </b-form-group>
                    <b-form-group
                      label-cols-sm="3"
                      label-cols-lg="2"
                      label-size="sm"
                      label="Updated Weight"
                      label-for="updated weight"
                    >
                      <b-form-row>
                        <b-form-input
                          id="weight"
                          v-model="patientInfoForm.updated_weight"
                          style="width: 90px"
                          :class="{ 'is-invalid': patientInfoForm.errors.has('updated_weight') }"
                          class="form-control mr-3 ml-1"
                          size="sm"
                          type="text"
                          name="updated weight"
                          @keydown="patientInfoForm.errors.clear('updated_weight')"
                        />
                        {{ patientInfoForm.weight_units }}
                        <has-error :form="patientInfoForm" field="updated_weight" />
                      </b-form-row>
                    </b-form-group>
                    <b-form-group
                      label-cols-sm="3"
                      label-cols-lg="2"
                      label-size="sm"
                      label="Updated BMI"
                      label-for="Updated BMI"
                    >
                      <b-form-input
                        id="Updated BMI"
                        v-model="patientInfoForm.updated_bmi"
                        :class="{ 'is-invalid': patientInfoForm.errors.has('updated_bmi') }"
                        class="form-control"
                        style="width: 90px"
                        size="sm"
                        type="text"
                        name="BMI"
                        @keydown="patientInfoForm.errors.clear('updated_bmi')"
                      />
                      <has-error :form="patientInfoForm" field="updated_bmi" />
                    </b-form-group>
                  </b-form>
                </b-tab>
                <div v-if="caseStudyNotes.length">
                  <div v-for="(item,index) in caseStudyNotes" :key="`case-study-notes-${index}`">
                    <b-tab :key="`tab-${item.type}-${tabKey}`" :active="activeTab === item.type">
                      <template #title>
                        <span :class="{'text-danger': errorsByType[item.type] && errorsByType[item.type].length}">{{ getCaseStudyText(item) }}</span>
                        <b-icon-trash
                          scale=".75"
                          class="text-muted"
                          @click="initRemoveTypeFromCaseStudyNotes(item)"
                        />
                      </template>
                      <div v-if="errorsByType[item.type] && errorsByType[item.type].length" class="p-2 text-danger">
                        <div>
                          Please fix the following<span v-if="errorsByType[item.type].length > 1">s</span>:
                        </div>
                        <ul>
                          <li v-for="(error, errorIndex) in errorsByType[item.type]"
                              :key="`error-${errorIndex}`"
                          >
                            {{ error }}
                          </li>
                        </ul>
                      </div>
                      <div v-for="(notes, caseStudyNotesIndex) in item.notes"
                           :key="`${notes.type}-${caseStudyNotesIndex}`"
                      >
                        <hr v-if="caseStudyNotesIndex !== 0" class="p-1">
                        <b-form-group
                          class="pt-3"
                          label-cols-sm="3"
                          label-cols-lg="2"
                          label-size="sm"
                          label="First Application"
                          label-for="first_application"
                        >
                          <b-form-row>
                            <div v-show="firstApplicationOptions.length>1">
                              <b-col lg="8">
                                <b-form-select id="first_application"
                                               v-model="notes.first_application"
                                               :options="firstApplicationOptions"
                                               size="sm"
                                />
                              </b-col>
                              <div class="mt-1 pl-2">
                                <b-icon-trash scale="1.25" @click="initRemoveCaseStudyNotesItemById(notes)" />
                              </div>
                            </div>
                            <div v-if="firstApplicationOptions.length === 1">
                              <b-alert show variant="info">
                                This assignment has no questions. The first application will automatically set to the
                                first question.
                              </b-alert>
                            </div>
                          </b-form-row>
                        </b-form-group>
                        <ckeditor
                          v-model="notes.text"
                          tabindex="0"
                          required
                          :config="richEditorConfig"
                          class="mt-3"
                          @namespaceloaded="onCKEditorNamespaceLoaded"
                          @input="item.showError = false"
                          @ready="handleFixCKEditor()"
                        />
                      </div>
                    </b-tab>
                  </div>
                </div>
              </b-tabs>
            </b-card-text>
          </b-card>
          <BulkImporter v-show="user.id === 3280"
                        :init-import-template="'case_study_notes'"
                        :assignment-id="+assignmentId"
                        @reloadPage="reloadPage"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>

import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import axios from 'axios'
import { mapGetters } from 'vuex'
import { fixCKEditor } from '~/helpers/accessibility/fixCKEditor'
import CKEditor from 'ckeditor4-vue'
import { ToggleButton } from 'vue-js-toggle-button'
import { faCaretDown, faCaretRight } from '@fortawesome/free-solid-svg-icons'
import Form from 'vform'
import AllFormErrors from '~/components/AllFormErrors'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import { codeStatusOptions, getCaseStudyNotesByQuestion } from '~/helpers/CaseStudyNotes'
import CaseStudyNotesViewer from '~/components/questions/nursing/CaseStudyNotesViewer'
import BulkImporter from '~/components/questions/BulkImporter.vue'

const defaultPatientInformationForm = {
  name: '',
  code_status: '',
  gender: '',
  allergies: '',
  age: '',
  weight: '',
  weight_units: 'lb',
  updated_weight: null,
  dob: '',
  bmi: '',
  first_application_of_updated_information: null,
  updated_bmi: null
}

const richEditorConfig = {
  toolbar: [
    { name: 'image', items: ['Image'] },
    { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', '-', 'Undo', 'Redo'] },
    {
      name: 'paragraph',
      items: ['BulletedList', 'NumberedList', '-', 'Outdent', 'Indent', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
    },
    { name: 'links', items: ['Link', 'Unlink', 'IFrame', 'Embed'] },
    { name: 'insert', items: ['Table', 'HorizontalRule', 'Smiley', 'SpecialChar'] },
    {
      name: 'basicstyles',
      items: ['Bold', 'Italic', 'Underline', 'Subscript', 'Superscript', 'RemoveFormat']
    },
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
  extraPlugins: 'embed,dialog,contextmenu,liststyle,image2,autogrow',
  filebrowserUploadUrl: '/api/ckeditor/upload',
  filebrowserUploadMethod: 'form',
  format_tags: 'p;h2;h3;pre',
  allowedContent: true
}
export default {
  middleware: 'auth',
  components: {
    BulkImporter,
    CaseStudyNotesViewer,
    ToggleButton,
    Loading,
    ckeditor: CKEditor.component,
    AllFormErrors
  },
  metaInfo () {
    return { title: 'Case Study Notes' }
  },
  data: () => ({
    showPatientInformation: false,
    caseStudyNotesByQuestion: [],
    questions: [],
    currentPage: 1,
    savedAll: false,
    tabKey: 0,
    errors: [],
    errorsByType: [],
    showUpdatedPatientInformation: false,
    activeTab: null,
    questionsOptions: [],
    unsavedChanges: {},
    commonQuestionTextForm: new Form({
      common_question_text: ''
    }),
    pounds: true,
    codeStatusOptions: codeStatusOptions,
    showPatientInfoFormInUpdatedInformation: false,
    updatedInformationType: null,
    patientInformationFirstApplication: null,
    firstApplication: null,
    firstApplicationOptions: [{ text: 'Choose a question', value: null }],
    originalIndex: 0,
    caseStudyNotes: [],
    patientInfoForm: new Form(defaultPatientInformationForm),
    caseStudyIndex: 0,
    view: false,
    toggleColors: window.config.toggleColors,
    allFormErrors: [],
    caseStudyNotesForm: new Form({
      text: ''
    }),
    notesIndex: 0,
    itemTypeToShow: '',
    caretDownIcon: faCaretDown,
    caretRightIcon: faCaretRight,
    itemToRemove: {},
    itemTypeToRemove: '',
    richEditorConfig: richEditorConfig,
    isLoading: true,
    assignmentId: 0,
    type: null,
    caseStudyOptions: [
      { value: null, text: 'Choose Notes To Add' },
      { value: 'patient_information', text: 'Patient Information' },
      { value: 'history_and_physical', text: 'History and Physical' },
      { value: 'progress_notes', text: 'Progress Notes' },
      { value: 'vital_signs', text: 'Vital Signs' },
      { value: 'lab_results', text: 'Lab/Diagnostic Results' },
      { value: 'provider_orders', text: 'Provider Orders' },
      { value: 'mar', text: 'MAR' },
      { value: 'handoff_report', text: 'Handoff Report' }
    ]
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    })
  },
  async beforeRouteLeave (to, from, next) {
    if (this.savedAll || !this.caseStudyNotes.length) {
      next(true)
    } else {
      this.savedAll = await this.saveAll(true)
      if (this.savedAll) {
        next(true)
      }
    }
  },
  created () {
    this.getCaseStudyNotesByQuestion = getCaseStudyNotesByQuestion
  },
  mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.reloadPage()
  },
  methods: {
    async reloadPage () {
      this.firstApplicationOptions = []
      this.caseStudyNotes = []
      await this.getAssignmentQuestions()
      await this.getPatientInformation()
      await this.getFirstApplicationOptions()
      await this.getCommonQuestionText()
      await this.getCaseStudyNotes()
    },
    async deletePatientInformation () {
      try {
        const { data } = await axios.delete(`/api/patient-information/${this.assignmentId}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.showPatientInformation = false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('modal-confirm-remove-patient-information-from-case-study-notes')
    },
    initGetCaseStudyNotesByQuestion () {
      this.getCaseStudyNotesByQuestion()
    },
    async updateView () {
      if (!this.view) {
        await this.saveAll()
      }

      this.view = !this.view
      await this.getCaseStudyNotesByQuestion()
    },
    async getAssignmentQuestions () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/view`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.questions = data.questions
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async removeUpdatedPatientInformation () {
      try {
        const { data } = await axios.patch(`/api/patient-information/delete-updated-information/${this.assignmentId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        await this.getPatientInformation()
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('modal-confirm-remove-updated-patient-information')
    },
    initRemovePatientUpdatedInformation () {
      this.$bvModal.show('modal-confirm-remove-updated-patient-information')
    },
    initRemovePatientInformationFromCaseStudyNotes () {
      this.$bvModal.show('modal-confirm-remove-patient-information-from-case-study-notes')
    },
    initRemoveCaseStudyNotesItemById (item) {
      this.itemToRemove = item
      this.itemTypeToRemove = this.getCaseStudyText(item)
      this.$bvModal.show('modal-confirm-remove-item-from-case-study-notes')
    },
    async removeCaseStudyNotesItemById () {
      try {
        const { data } = await axios.delete(`/api/case-study-notes/${this.itemToRemove.id}`)
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
      console.log(this.itemToRemove.id)
      for (let i = 0; i < this.caseStudyNotes.length; i++) {
        if (this.caseStudyNotes[i].type === this.itemToRemove.type) {
          this.caseStudyNotes[i]['notes'] = this.caseStudyNotes[i]['notes'].filter(item => item.id !== this.itemToRemove.id)
          if (!this.caseStudyNotes[i]['notes'].length) {
            this.caseStudyNotes = this.caseStudyNotes.filter(item => item.type !== this.itemToRemove.type)
          }
        }
      }
      this.$bvModal.hide('modal-confirm-remove-item-from-case-study-notes')
    },
    initRemoveTypeFromCaseStudyNotes (item) {
      console.log(item)
      this.itemToRemove = item
      this.itemTypeToRemove = this.getCaseStudyText(item)
      this.$bvModal.show('modal-confirm-remove-type-from-case-study-notes')
    },
    async removeTypeFromCaseStudyNotes () {
      try {
        const { data } = await axios.delete(`/api/case-study-notes/assignment/${this.assignmentId}/type/${this.itemToRemove.type}`)
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }

      this.caseStudyNotes = this.caseStudyNotes.filter(item => item.type !== this.itemToRemove.type)

      this.$bvModal.hide('modal-confirm-remove-type-from-case-study-notes')
    },
    async getFirstApplicationOptions () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/summary`)
        if (!data.rows.length) {
          return false
        }
        for (let i = 0; i < data.rows.length; i++) {
          let question = data.rows[i]
          this.firstApplicationOptions.push({ value: question.order, text: `${question.order}. ${question.title}` })
        }
        console.log(this.firstApplicationOptions)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async saveAll (leavingPage = false) {
      this.$forceUpdate()
      this.errorsByType = []
      try {
        const { data } = await axios.post('/api/case-study-notes/save-all', {
          case_study_notes: this.caseStudyNotes,
          patient_informations: this.patientInfoForm,
          assignment_id: this.assignmentId
        })
        if (data.type === 'success') {
          if (leavingPage) {
            return true
          }
          this.$noty.success(data.message)
        }
        if (data.type === 'error') {
          if (data.errors_by_type) {
            this.errorsByType = data.errors_by_type
            this.$nextTick(() => fixInvalid())
            this.allFormErrors = data.errors
            this.$bvModal.show('modal-form-errors-case-study-notes')
          } else {
            this.$noty.error(data.message)
          }
          return false
        }
        this.tabKey++
      } catch (error) {
        this.$noty.error(error.message)
        return false
      }
    },
    async getCommonQuestionText () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/common-question-text`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.commonQuestionTextForm.common_question_text = data.commmon_question_text
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async saveCommonQuestionText () {
      try {
        const { data } = await this.commonQuestionTextForm.patch(`/api/assignments/${this.assignmentId}/common-question-text`)
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initResetNotes () {
      this.$bvModal.show('modal-confirm-reset-notes')
    },
    async resetNotes () {
      try {
        const { data } = await axios.delete(`/api/case-study-notes/assignment/${this.assignmentId}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.$bvModal.hide('modal-confirm-reset-notes')
          this.caseStudyNotes = []
          this.showPatientInfoFormInUpdatedInformation = false
          this.updatedInformationType = null
          this.patientInformationFirstApplication = false
          this.firstApplication = null
          this.patientInfoForm = new Form(defaultPatientInformationForm)
          this.errorsByType = []
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getCodeStatus () {
      let codeStatus = this.codeStatusOptions.find(item => item.value === this.patientInfoForm.code_status)
      return codeStatus ? codeStatus.text : 'N/A'
    },
    async updateShowPatientUpdatedInformation (show) {
      if (show && this.showPatientInfoFormInUpdatedInformation) {
        this.$noty.info('The updated patient information tab already exists in these Case Study Notes.')
        this.updatedInformationType = null
        return false
      }
      try {
        const { data } = await axios.patch(`/api/patient-information/show-patient-updated-information/${this.assignmentId}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.$bvModal.hide('modal-confirm-remove-type-from-case-study-notes')
          this.showPatientInfoFormInUpdatedInformation = show
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.updatedInformationType = null
    },
    showPatientInfoFormItem (item) {
      return this.patientInfoForm[item] ? this.patientInfoForm[item] : 'N/A'
    },
    setFirstApplication (item) {
      if (item) {
        this.firstApplication = null
        let firstApplication = this.firstApplications.find(value => value.id === item.id)
        if (firstApplication) {
          this.firstApplication = firstApplication.first_application
        }
      }
    },
    async updateFirstApplication (firstApplication, type, item = {}) {
      try {
        let firstApplicationData = type === 'patient_information'
          ? {
            type: 'patient_information',
            assignment_id: this.assignmentId,
            first_application: firstApplication
          }
          : {
            type: 'case_study_notes',
            first_application: firstApplication,
            case_study_notes_id: item.id
          }
        const { data } = await axios.patch(`/api/updated-information-first-application`,
          firstApplicationData)
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async updateCaseStudyNotes (item) {
      this.caseStudyNotesForm.type = item.type
      this.caseStudyNotesForm.text = item.text
      this.caseStudyNotesForm.id = item.id
      try {
        const { data } = await this.caseStudyNotesForm.patch(`/api/case-study-notes/${this.assignmentId}`)
        this.$noty[data.type](data.message)
        return data
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.caseStudyNotesForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-case-study-notes')
        }
        return false
      }
    },
    getCaseStudyText (item) {
      let option = this.caseStudyOptions.find(option => option.value === item.type)
      return option ? option.text : ''
    },
    initRemoveUpdatedPatientInformation () {
      this.itemTypeToRemove = 'Patient Information'
      this.$bvModal.show('modal-confirm-remove-type-from-case-study-notes')
    },

    async addNewCaseStudyNotes (type) {
      if (type === 'patient_information') {
        this.activeTab = 'patient_information'
        this.showPatientInformation = true
        if (this.showUpdatedPatientInformation) {
          this.$noty.info('You can only update the Patient Information once.')
          return false
        }
        this.showUpdatedPatientInformation = true
        return
      }
      if (!type) {
        return false
      }
      try {
        const { data } = await axios.post(`/api/case-study-notes/${this.assignmentId}`, { type: type })
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          this.type = null
          return false
        }
        let notesByType = this.caseStudyNotes.find(item => item.type === type)
        let isFirstOneByType = false
        if (!notesByType) {
          isFirstOneByType = true
          this.caseStudyNotes.push({ type: type, notes: [] })
        }
        console.log(data.notes)
        if (isFirstOneByType) {
          if (this.firstApplicationOptions.length >= 2) {
            data.notes.first_application = this.firstApplicationOptions[1].value
          }
        }
        this.caseStudyNotes.find(item => item.type === type).notes.push(data.notes)
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.activeTab = type
      this.type = null
    },
    async getCaseStudyNotes () {
      try {
        const { data } = await axios.get(`/api/case-study-notes/${this.assignmentId}`)
        console.log(data)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        for (let i = 0; i < data.case_study_notes.length; i++) {
          let caseStudyNotes = data.case_study_notes[i]
          this.caseStudyNotes.push(caseStudyNotes)
        }
        console.log(this.caseStudyNotes)
        this.$forceUpdate()
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    },
    handleFixCKEditor () {
      fixCKEditor(this)
    },
    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    },
    async getPatientInformation () {
      this.showPatientInformation = false
      try {
        const { data } = await axios.get(`/api/patient-information/${this.assignmentId}`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        if (data.patient_information) {
          this.showPatientInformation = true
          for (const property in this.patientInfoForm.originalData) {
            this.patientInfoForm[property] = data.patient_information[property]
          }
          this.showUpdatedPatientInformation = Boolean(data.patient_information.show_in_updated_information) ||
            data.patient_information.updated_bmi ||
            data.patient_information.updated_weight
          this.patientInformationFirstApplication = data.patient_information.first_application_of_updated_information
        }
      } catch (error) {
        this.$noty.error(error.message)
        this.isLoading = false
      }
    },
    async updatePatientInformation () {
      try {
        const { data } = await this.patientInfoForm.patch(`/api/patient-information/${this.assignmentId}`)
        this.$noty[data.type](data.message)
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.patientInfoForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-patient-information')
        }
      }
    }
  }
}
</script>
