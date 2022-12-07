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
        <PageTitle title="Case Study Notes"/>
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
        <b-modal id="modal-confirm-remove-item-from-case-study-notes"
                 title="Confirm Remove Item From the Case Study Notes"
                 size="lg"
        >
          Please confirm that you would like to remove: {{ itemTypeToRemove }}. Please note that this action cannot
          be undone.
          <div v-if="showWarningAboutUpdatedInformation()" class="pt-2">
            <b-alert show variant="info">
              You are about to remove an Initial Condition set of case study notes which has an associated set of notes
              used as Updated Information.
              Both sets of notes will be deleted.
            </b-alert>
          </div>
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
              @click="itemTypeToRemove === 'Patient Information' ? updateShowPatientUpdatedInformation(false) : removeItemFromCaseStudyNotes()"
            >
              Remove Item
            </b-button>
          </template>
        </b-modal>
        <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-case-study-notes"/>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="common_question_text"
        >
          <template v-slot:label>
            Common Question Text
            <QuestionCircleTooltip id="common_question_text"/>
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
              Update
            </b-button>
          </b-form-row>
        </b-form-group>
        <b-form-group>
          <b-form-select id="type-of-notes"
                         v-model="type"
                         size="sm"
                         style="width:230px"
                         :options="caseStudyOptions"
                         @change="addNewCaseStudyNotes($event,0)"
          />
          <toggle-button
            class="mt-1 mr-2"
            :width="68"
            :value="view"
            :sync="true"
            :font-size="14"
            :margin="4"
            :color="toggleColors"
            :labels="{checked: 'View', unchecked: 'Edit'}"
            @change="view = !view"
          />
          <b-button variant="danger" size="sm" @click="initResetNotes">
            Reset Notes
          </b-button>
        </b-form-group>
        <div v-for="(version, versionIndex) in [0,1]" :key="`version-${versionIndex}`">
          <b-form-row class="pb-3">
            <b-col lg="8">
              <b-form-select v-if="versionIndex ===1"
                             id="type-of-update-information"
                             v-model="updatedInformationType"
                             size="sm"
                             style="width:230px"
                             :options="updatedInformationOptions"
                             @change="updatedInformationType === 'patient_information' ? updateShowPatientUpdatedInformation(true) : addNewCaseStudyNotes($event,1)"
              />
            </b-col>
          </b-form-row>
          <b-card
            v-if="versionIndex === 0 ||
              (showPatientInfoFormInUpdatedInformation ||(versionIndex === 1 && typeof (caseStudyNotes.find(item => item.version === 1)) !== 'undefined'))"
            :header-html="getHeaderHtml(version)"
            class="mb-4"
          >
            <b-card-text>
              <b-tabs>
                <b-tab v-if="versionIndex === 0 || showPatientInfoFormInUpdatedInformation">
                  <template #title>
                    Patient Information
                    <b-icon-trash
                      v-if="versionIndex === 1"
                      scale=".75"
                      class="text-muted"
                      @click="initRemoveUpdatedPatientInformation"
                    />
                  </template>
                  <b-form class="mt-4">
                    <div v-if="versionIndex === 1">
                      <b-form-group
                        class="pt-3"
                        label-cols-sm="3"
                        label-cols-lg="2"
                        label="First Application"
                        label-for="first_application"
                      >
                        <b-form-row>
                          <b-col lg="8">
                            <b-form-select id="patient_information_first_application"
                                           v-model="patientInformationFirstApplication"
                                           :options="firstApplicationOptions"
                                           @change="updateFirstApplication($event, 'patient_information')"
                            />
                          </b-col>
                        </b-form-row>
                      </b-form-group>
                    </div>
                    <b-form-row>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label="Name*"
                          label-for="name"
                          label-size="sm"
                        >
                          <div v-if="versionIndex === 1 || (versionIndex === 0 && view)">
                            {{ showPatientInfoFormItem('name') }}
                          </div>
                          <b-form-input v-if="versionIndex === 0 && !view"
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
                          <has-error :form="patientInfoForm" field="name"/>
                        </b-form-group>
                      </b-col>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label-size="sm"
                          label="Code Status*"
                          label-for="code_status"
                        >
                          <div v-if="versionIndex === 1 || (versionIndex === 0 && view)">
                            {{ getCodeStatus() }}
                          </div>
                          <div class="mt-1">
                            <b-form-select
                              v-if="versionIndex === 0 && !view"
                              id="code_status"
                              v-model="patientInfoForm.code_status"
                              :options="codeStatusOptions"
                              :class="{ 'is-invalid': patientInfoForm.errors.has('code_status') }"
                              cols="2"
                              size="sm"
                              @change="patientInfoForm.errors.clear('code_status')"
                            />
                            <has-error :form="patientInfoForm" field="code_status"/>
                          </div>
                        </b-form-group>
                      </b-col>
                    </b-form-row>
                    <b-form-row>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label="Gender*"
                          label-for="gender"
                          label-size="sm"
                        >
                          <div v-if="versionIndex === 1 || (versionIndex === 0 && view)">
                            {{ showPatientInfoFormItem('gender') }}
                          </div>
                          <b-form-input v-if="versionIndex === 0 && !view"
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
                          <has-error :form="patientInfoForm" field="gender"/>
                        </b-form-group>
                      </b-col>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label-size="sm"
                          label="Allergies*"
                          label-for="allergies"
                        >
                          <div v-if="versionIndex === 1 || (versionIndex === 0 && view)">
                            {{ showPatientInfoFormItem('allergies') }}
                          </div>
                          <b-form-input v-if="versionIndex === 0 && !view"
                                        id="allergies"
                                        v-model="patientInfoForm.allergies"
                                        :class="{ 'is-invalid': patientInfoForm.errors.has('allergies') }"
                                        class="form-control"
                                        size="sm"
                                        type="text"
                                        name="allergies"
                                        @keydown="patientInfoForm.errors.clear('allergies')"
                          />
                          <has-error :form="patientInfoForm" field="allergies"/>
                        </b-form-group>
                      </b-col>
                    </b-form-row>
                    <b-form-row>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label="Age*"
                          label-for="age"
                          label-size="sm"
                        >
                          <div v-if="versionIndex === 1 || (versionIndex === 0 && view)">
                            {{ showPatientInfoFormItem('age') }}
                          </div>
                          <b-form-input v-if="versionIndex === 0 && !view"
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
                          <has-error :form="patientInfoForm" field="age"/>
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
                            Weight*
                          </template>
                          <div v-show="version === 0">
                            <div v-if="view">
                              {{ showPatientInfoFormItem('weight') }} {{ patientInfoForm.weight_units }}
                            </div>
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
                              <has-error :form="patientInfoForm" field="weight"/>
                              <b-form-radio-group v-model="patientInfoForm.weight_units">
                                <b-form-radio value="lb">
                                  lb
                                </b-form-radio>
                                <b-form-radio value="kg">
                                  kg
                                </b-form-radio>
                              </b-form-radio-group>
                            </b-form-row>
                          </div>
                          <div v-show="version === 1">
                            <div v-if="view">
                              {{
                                showPatientInfoFormItem('updated_weight') === 'N/A' ? showPatientInfoFormItem('weight') : showPatientInfoFormItem('updated_weight')
                              }} {{ patientInfoForm.weight_units }}
                            </div>
                            <b-form-row v-show="!view">
                              <b-form-input
                                id="weight"
                                v-model="patientInfoForm.updated_weight"
                                style="width: 90px"
                                :class="{ 'is-invalid': patientInfoForm.errors.has('weight') }"
                                class="form-control mr-3 ml-1"
                                size="sm"
                                type="text"
                                name="allergies"
                                @keydown="patientInfoForm.errors.clear('weight')"
                              />
                              {{ patientInfoForm.weight_units }}
                              <has-error :form="patientInfoForm" field="updated_weight"/>
                            </b-form-row>
                          </div>
                        </b-form-group>
                      </b-col>
                    </b-form-row>
                    <b-form-row>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label="DOB*"
                          label-for="DOB"
                          label-size="sm"
                        >
                          <div v-if="versionIndex === 1 || (versionIndex === 0 && view)">
                            {{ showPatientInfoFormItem('dob') }}
                          </div>
                          <b-form-input v-if="versionIndex === 0 && !view"
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
                          <has-error :form="patientInfoForm" field="dob"/>
                        </b-form-group>
                      </b-col>
                      <b-col>
                        <b-form-group
                          label-cols-sm="4"
                          label-cols-lg="3"
                          label-size="sm"
                          label="BMI*"
                          label-for="BMI"
                        >
                          <div v-show="version === 0">
                            <div v-if="view">
                              {{ patientInfoForm.bmi }}
                            </div>
                            <b-form-input v-show="!view"
                                          id="BMI"
                                          v-model="patientInfoForm.bmi"
                                          :class="{ 'is-invalid': patientInfoForm.errors.has('bmi') }"
                                          class="form-control"
                                          size="sm"
                                          type="text"
                                          name="BMI"
                                          @keydown="patientInfoForm.errors.clear('bmi')"
                            />
                            <has-error :form="patientInfoForm" field="bmi"/>
                          </div>
                          <div v-show="version === 1">
                            <div v-if="view">
                              {{
                                showPatientInfoFormItem('updated_bmi') === 'N/A' ? showPatientInfoFormItem('bmi') : showPatientInfoFormItem('updated_bmi')
                              }}
                            </div>
                            <b-form-input v-show="!view"
                                          id="BMI"
                                          v-model="patientInfoForm.updated_bmi"
                                          :class="{ 'is-invalid': patientInfoForm.errors.has('updated_bmi') }"
                                          class="form-control"
                                          size="sm"
                                          type="text"
                                          name="BMI"
                                          @keydown="patientInfoForm.errors.clear('updated_bmi')"
                            />
                            <has-error :form="patientInfoForm" field="updated_bmi"/>
                          </div>
                        </b-form-group>
                      </b-col>
                    </b-form-row>
                    <div class="float-right pt-3">
                      <b-button size="sm" variant="primary" @click="updatePatientInformation()">
                        Save Patient Information
                      </b-button>
                    </div>
                  </b-form>
                </b-tab>
                <div v-for="(item,index) in caseStudyNotes" :key="`case-study-notes-${index}`">
                  <b-tab v-if="versionIndex === 0 && item.version === 0"
                         :active="index === caseStudyIndex"
                  >
                    <template #title>
                      {{ getCaseStudyText(item) }}
                      <b-icon-trash
                        scale=".75"
                        class="text-muted"
                        @click="initRemoveItemFromCaseStudyNotes(index, item)"
                      />
                    </template>
                    <div v-if="view">
                      <div class="mt-3" v-html="item.text"/>
                      <div v-if="!item.text">
                        No {{ getCaseStudyText(item) }} notes are available.
                      </div>
                    </div>
                    <ckeditor v-if="!view"
                              v-model="item.text"
                              tabindex="0"
                              required
                              :config="richEditorConfig"
                              class="mt-3"
                              @namespaceloaded="onCKEditorNamespaceLoaded"
                              @input="item.showError = false"
                              @ready="handleFixCKEditor()"
                    />

                    <div class="float-right pt-3">
                      <b-button size="sm" variant="primary" @click="updateCaseStudyNotes(item,version)">
                        Save {{ getCaseStudyText(item) }}
                      </b-button>
                    </div>
                  </b-tab>
                  <b-tab v-if="versionIndex === 1 && item.version === 1"
                         :active="index === caseStudyIndex"
                         @click="setFirstApplication(item)"
                  >
                    <template #title>
                      {{ getCaseStudyText(item) }}
                      <b-icon-trash
                        scale=".75"
                        class="text-muted"
                        @click="initRemoveItemFromCaseStudyNotes(index, item)"
                      />
                    </template>
                    <div>
                      <b-form-group
                        class="pt-3"
                        label-cols-sm="3"
                        label-cols-lg="2"
                        label="First Application"
                        label-for="first_application"
                      >
                        <b-form-row>
                          <b-col lg="8">
                            <b-form-select id="first_application"
                                           v-model="firstApplication"
                                           :options="firstApplicationOptions"
                                           @change="updateFirstApplication($event,'case_study_notes', item)"
                            />
                          </b-col>
                        </b-form-row>
                      </b-form-group>
                    </div>
                    <div v-if="view">
                      <div class="mt-3" v-html="item.updated_text"/>
                      <div v-if="!item.updated_text">
                        No {{ getCaseStudyText(item) }} notes are available.
                      </div>
                    </div>
                    <ckeditor v-if="!view"
                              v-model="item.updated_text"
                              tabindex="0"
                              required
                              :config="richEditorConfig"
                              class="mt-3"
                              @namespaceloaded="onCKEditorNamespaceLoaded"
                              @input="item.showError = false"
                              @ready="handleFixCKEditor()"
                    />
                    <div class="float-right pt-3">
                      <b-button size="sm" variant="primary" @click="updateCaseStudyNotes(item,version)">
                        Save {{ getCaseStudyText(item) }}
                      </b-button>
                    </div>
                  </b-tab>
                </div>
              </b-tabs>
            </b-card-text>
          </b-card>
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
import { codeStatusOptions } from '~/helpers/CaseStudyNotes'

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
    ToggleButton,
    Loading,
    ckeditor: CKEditor.component,
    AllFormErrors
  },
  metaInfo () {
    return { title: 'Case Study Notes' }
  },
  data: () => ({
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
    patientInfoForm: new Form({
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
      updated_bmi: null
    }),
    caseStudyIndex: 0,
    view: false,
    toggleColors: window.config.toggleColors,
    allFormErrors: [],
    caseStudyNotesForm: new Form({
      text: '',
      updated_text: ''
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
      { value: null, text: 'Add Initial Conditions' },
      { value: 'history_and_physical', text: 'History and Physical' },
      { value: 'progress_notes', text: 'Progress Notes' },
      { value: 'vital_signs', text: 'Vital Signs' },
      { value: 'lab_results', text: 'Lab Results' },
      { value: 'provider_orders', text: 'Provider Orders' },
      { value: 'mar', text: 'MAR' },
      { value: 'handoff_report', text: 'Handoff Report' }
    ]
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    updatedInformationOptions: function () {
      let updated = [{ value: null, text: 'Add Updated Information' }, {
        value: 'patient_information',
        text: 'Patient Information'
      }]
      for (let i = 0; i < this.caseStudyNotes.length; i++) {
        let notes = this.caseStudyNotes[i]
        if (notes.version === 0) {
          updated.push(this.caseStudyOptions.find(item => item.value === notes.type))
        }
      }
      return updated
    }
  },
  mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.getCommonQuestionText()
    this.getFirstApplications()
    this.getPatientInformation()
    this.getCaseStudyNotes()
  },
  methods: {
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
          this.$bvModal.hide('modal-confirm-remove-item-from-case-study-notes')
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
    showWarningAboutUpdatedInformation () {
      return (this.itemToRemove.version === 0) && this.caseStudyNotes.filter(item => item.version === 1 && item.type === this.itemToRemove.type).length
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
    async getFirstApplications () {
      try {
        const { data } = await axios.get(`/api/updated-information-first-application/${this.assignmentId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let questions = data.questions
        for (let i = 0; i < questions.length; i++) {
          let question = questions[i]
          this.firstApplicationOptions.push({ text: question.order + '. ' + question.title, value: question.order })
        }
        this.firstApplications = data.first_applications
      } catch (error) {
        this.$noty.error(error.message)
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
    getHeaderHtml (version) {
      return version === 0 ? '<h2 class="h7">Initial Conditions</h2>' : '<h2 class="h7">Updated Information</h2>'
    },
    async updateCaseStudyNotes (item, version) {
      this.caseStudyNotesForm.type = item.type
      this.caseStudyNotesForm.text = version === 0 ? item.text : item.updated_text
      this.caseStudyNotesForm.version = version
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
      this.$bvModal.show('modal-confirm-remove-item-from-case-study-notes')
    },
    initRemoveItemFromCaseStudyNotes (index, item) {
      this.itemToRemove = item
      this.itemTypeToRemove = this.getCaseStudyText(item)
      this.$bvModal.show('modal-confirm-remove-item-from-case-study-notes')
    },
    async removeItemFromCaseStudyNotes () {
      try {
        const { data } = await axios.delete(`/api/case-study-notes/${this.itemToRemove.id}`)
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }

      if (this.itemToRemove.version === 0) {
        this.caseStudyNotes = this.caseStudyNotes.filter(item => item.type !== this.itemToRemove.type)
      } else {
        this.caseStudyNotes = this.caseStudyNotes.filter(item => item.id !== this.itemToRemove.id)
      }
      this.$bvModal.hide('modal-confirm-remove-item-from-case-study-notes')
    },
    async addNewCaseStudyNotes (type, version) {
      if (!type) {
        return false
      }
      if (this.caseStudyNotes.find(notes => notes.type === type && notes.version === 1)) {
        this.updatedInformationType = null
        let message = this.caseStudyOptions.find(option => option.value === type).text + ' already exists in this set of notes.'
        this.$noty.info(message)
        return false
      }
      try {
        let item = {
          type: type,
          version: version
        }
        if (version === 0) {
          item.text = ''
        } else {
          item.updated_text = ''
        }
        let data = await this.updateCaseStudyNotes(item, version)
        if (!data || data.type === 'error') {
          this.$noty.error('There was an error adding this tab to the study notes.')
          return false
        }
        switch (version) {
          case (0):
            this.type = null
            break
          case (1):
            this.updatedInformationType = null
            break
        }
        this.caseStudyNotes.push(data.new_notes)
      } catch (error) {
        this.$noty.error(error.message)
      }
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
          if (caseStudyNotes.version === 1) {
            caseStudyNotes.updated_text = caseStudyNotes.text
          }
          this.caseStudyNotes.push(caseStudyNotes)
        }
        console.log(this.caseStudyNotes)
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
      try {
        const { data } = await axios.get(`/api/patient-information/${this.assignmentId}`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        if (data.patient_information) {
          for (const property in this.patientInfoForm.originalData) {
            this.patientInfoForm[property] = data.patient_information[property]
          }
          this.showPatientInfoFormInUpdatedInformation = Boolean(data.patient_information.show_in_updated_information)
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
