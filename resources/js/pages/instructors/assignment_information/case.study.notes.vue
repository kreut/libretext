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
        <b-modal id="modal-show-item"
                 :title="`Viewing ${itemTypeToShow}`"
                 size="lg"
                 hide-footer
        >
          <div v-if="caseStudyNotes[notesIndex]" v-html="caseStudyNotes[notesIndex].notes"/>
        </b-modal>
        <b-modal id="modal-confirm-remove-item-from-case-study-notes"
                 title="Confirm Remove Item From the Case Study Notes"
                 size="lg"
        >
          Please confirm that you would like to remove: {{ itemTypeToRemove }}. Please note that this action cannot
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
              @click="removeItemFromCaseStudyNotes()"
            >
              Remove Item
            </b-button>
          </template>
        </b-modal>
        Patient Information

        Do the input stuff for this here as well....
        1. Do the Patient information stuff
        1.5 Save as template
        1.6 Import

        2. Update the information to the database
        3. How to apply the tabs?
        3.5 Do the general commenting thing
        4. Do the highlight question
        aaaaaaa
        <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-patient-information"/>
        <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-case-study-note"/>
        <b-card header-html="<h2 class=&quot;h7&quot;>Patient Information</h2>"
                class="mb-4"
        >
          <b-card-text>
            <b-form>
              <b-form-row>
                <b-col>
                  <b-form-group
                    label-cols-sm="4"
                    label-cols-lg="3"
                    label="Name*"
                    label-for="name"
                    label-size="sm"
                  >
                    <b-form-input id="name"
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
                    <b-form-input id="code_status"
                                  v-model="patientInfoForm.code_status"
                                  :class="{ 'is-invalid': patientInfoForm.errors.has('code_status') }"
                                  class="form-control"
                                  size="sm"
                                  type="text"
                                  name="code_status"
                                  @keydown="patientInfoForm.errors.clear('code_status')"

                    />
                    <has-error :form="patientInfoForm" field="code_status"/>
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
                    <b-form-input id="gender"
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
                    <b-form-input id="allergies"
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
                    <b-form-input id="age"
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
                    label="Weight*"
                    label-for="weight"
                  >
                    <b-form-input id="allergies"
                                  v-model="patientInfoForm.weight"
                                  :class="{ 'is-invalid': patientInfoForm.errors.has('weight') }"
                                  class="form-control"
                                  size="sm"
                                  type="text"
                                  name="allergies"
                                  @keydown="patientInfoForm.errors.clear('weight')"

                    />
                    <has-error :form="patientInfoForm" field="weight"/>
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
                    <b-form-input id="DOB"
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
                    <b-form-input id="BMI"
                                  v-model="patientInfoForm.bmi"
                                  :class="{ 'is-invalid': patientInfoForm.errors.has('bmi') }"
                                  class="form-control"
                                  size="sm"
                                  type="text"
                                  name="BMI"
                                  @keydown="patientInfoForm.errors.clear('bmi')"

                    />
                    <has-error :form="patientInfoForm" field="bmi"/>
                  </b-form-group>
                </b-col>
              </b-form-row>
              <div class="float-right">
                <b-button size="sm" variant="primary" @click="updatePatientInformation">
                  Update Patient Information
                </b-button>
              </div>
            </b-form>
          </b-card-text>
        </b-card>

        <b-card header-html="<h2 class=&quot;h7&quot;>Notes</h2>"
                class="mb-4"
        >
          <b-card-text>
            <b-form-group>
              <b-form-row>
                <b-col lg="8">
                  <b-form-select id="type-of-notes"
                                 v-model="type"
                                 :options="caseStudyNotesOptions"
                                 @change="addNewCaseStudyNotes($event)"
                  />
                </b-col>
              </b-form-row>
            </b-form-group>
            <div v-for="(item,index) in caseStudyNotes" :key="`case-study-notes-${index}`">
              {{ getCaseStudyText(item) }}
              <span style="cursor: pointer;" @click="toggleExpanded(index)">
            <font-awesome-icon v-if="!item.expanded" :icon="caretRightIcon" size="lg"/>
            <font-awesome-icon v-if="item.expanded" :icon="caretDownIcon" size="lg"/>
          </span>
              <b-icon-eye size="lg" @click="showItem(index, item)"/>
              <b-icon-trash size="lg" @click="initRemoveItemFromCaseStudyNotes(index, item)"/>
              <ckeditor v-if="item.expanded"
                        v-model="item.notes"
                        tabindex="0"
                        required
                        :config="richEditorConfig"
                        @namespaceloaded="onCKEditorNamespaceLoaded"
                        @input="item.showError = false"
                        @ready="handleFixCKEditor()"
              />
              <ErrorMessage v-if="item.showError && caseStudyNotesForm.errors.get('case_study_notes')
                              && JSON.parse(caseStudyNotesForm.errors.get('case_study_notes'))[item.type]"
                            :message="getCaseStudyText(item) + ' text is required.'"
              />
            </div>
            <div v-if="caseStudyNotes.length" class="float-right pt-3">
              <b-button size="sm" variant="primary" @click="updateNotes">
                Update Notes
              </b-button>
            </div>
          </b-card-text>
        </b-card>
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
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCaretDown, faCaretRight } from '@fortawesome/free-solid-svg-icons'
import Form from 'vform'
import AllFormErrors from '~/components/AllFormErrors'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import ErrorMessage from '~/components/ErrorMessage'

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
    Loading,
    ckeditor: CKEditor.component,
    FontAwesomeIcon,
    AllFormErrors,
    ErrorMessage
  },
  metaInfo () {
    return { title: 'Case Study Notes' }
  },
  data: () => ({
    allFormErrors: [],
    caseStudyNotesForm: new Form({
      case_study_notes: ''
    }),
    patientInfoForm: new Form({
      name: '',
      code_status: '',
      gender: '',
      allergies: '',
      age: '',
      weight: '',
      dob: '',
      bmi: ''
    }),
    notesIndex: 0,
    itemTypeToShow: '',
    caretDownIcon: faCaretDown,
    caretRightIcon: faCaretRight,
    itemToRemoveIndex: null,
    itemTypeToRemove: '',
    richEditorConfig: richEditorConfig,
    isLoading: true,
    assignmentId: 0,
    type: null,
    caseStudyNotes: [],
    caseStudyNotesOptions: [
      { value: null, text: 'Choose Case Study Notes to Add' },
      { value: 'history_and_physical', text: 'History and Physical' },
      { value: 'progress_notes', text: 'Progress Notes' },
      { value: 'vital_signs', text: 'Vital Signs' },
      { value: 'lab_results', text: 'Lab Results' },
      { value: 'provider_orders', text: 'Provider Orders' },
      { value: 'mar', text: 'MAR' },
      { value: 'handoff_report', text: 'Handoff Report' }
    ]
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.getPatientInformation()
    this.getCaseStudyNotes()
  },
  methods: {
    async updateNotes () {
      try {
        for (let i = 0; i < this.caseStudyNotes.length; i++) {
          this.caseStudyNotes[i].showError = true
        }
        this.caseStudyNotesForm = new Form({
          case_study_notes: JSON.stringify(this.caseStudyNotes)
        })
        const { data } = await this.caseStudyNotesForm.patch(`/api/case-study-notes/${this.assignmentId}`)
        this.$noty[data.type](data.message)
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.caseStudyNotesForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-case-study-notes')
        }
      }
    },
    async getPatientInformation () {
      try {
        const { data } = await axios.get(`/api/patient-information/${this.assignmentId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        if (data.patient_information) {
          for (const property in this.patientInfoForm.originalData) {
            this.patientInfoForm[property] = data.patient_information[property]
          }
        }
      } catch (error) {
        this.$noty.error(error.message)
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
    },
    showItem (index, item) {
      this.notesIndex = index
      this.itemTypeToShow = this.getCaseStudyText(item)
      this.$bvModal.show('modal-show-item')
    },
    toggleExpanded (index) {
      this.caseStudyNotes[index].expanded = !this.caseStudyNotes[index].expanded
    },
    getCaseStudyText (item) {
      let option = this.caseStudyNotesOptions.find(option => option.value === item.type)
      return option ? option.text : ''
    },
    initRemoveItemFromCaseStudyNotes (index, item) {
      this.itemToRemoveIndex = index
      this.itemTypeToRemove = this.getCaseStudyText(item)
      this.$bvModal.show('modal-confirm-remove-item-from-case-study-notes')
    },
    removeItemFromCaseStudyNotes () {
      this.caseStudyNotes.splice(this.itemToRemoveIndex, 1)
      this.$bvModal.hide('modal-confirm-remove-item-from-case-study-notes')
      this.$noty.info('The item has been removed from your Case Study Notes.')
    },
    addNewCaseStudyNotes (type) {
      this.caseStudyNotes.push({ type: type, notes: '', expanded: true })
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
        this.caseStudyNotes = data.case_study_notes
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
    }
  }

}
</script>
