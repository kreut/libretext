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
      <b-modal
        id="modal-edit-canned-responses"
        ref="modal"
        title="Edit Canned Responses"
        size="lg"
      >
        <b-list-group-item
          v-for="cannedResponse in cannedResponses"
          :key="cannedResponse.id"
          class="flex-column align-items-start"
        >
          {{ cannedResponse.canned_response }}
          <b-icon icon="trash" @click="removeCannedResponse(cannedResponse.id)" />
        </b-list-group-item>
        <b-input-group class="mt-4">
          <b-form-input v-model="cannedResponseForm.canned_response"
                        type="text"
                        :class="{ 'is-invalid': cannedResponseForm.errors.has('canned_response') }"
                        @keydown="cannedResponseForm.errors.clear('canned_response')"
          />
          <b-input-group-append>
            <b-button variant="primary" size="sm" @click="submitCannedResponseForm">
              Save Response
            </b-button>
          </b-input-group-append>
          <has-error :form="cannedResponseForm" field="canned_response" />
        </b-input-group>
        <template #modal-footer="{ ok }">
          <b-button size="sm" variant="success" @click="ok()">
            OK
          </b-button>
        </template>
      </b-modal>

      <b-modal
        id="modal-upload-file"
        ref="modal"
        hide-footer
        size="lg"
      >
        <template v-slot:modal-title>
          {{ feedbackModalTitle }}
        </template>
        <toggle-button
          class="mt-1"
          :width="105"
          :value="feedbackTypeIsPdfImage"
          :sync="true"
          :font-size="14"
          :margin="4"
          :color="{checked: '#28a745', unchecked: '#6c757d'}"
          :labels="{checked: 'PDF/Image', unchecked: 'Audio'}"
          @change="toggleFeedbackType()"
        />
        <div v-if="feedbackTypeIsPdfImage">
          <b-form ref="form">
            <p>Accepted file types are: {{ getAcceptedFileTypes() }}.</p>
            <b-form-file
              ref="fileFeedbackInput"
              v-model="fileFeedbackForm.fileFeedback"
              placeholder="Choose a file or drop it here..."
              drop-placeholder="Drop file here..."
              :accept="getAcceptedFileTypes()"
            />
            <div v-if="uploading">
              <b-spinner small type="grow" />
              Uploading file...
            </div>
            <input type="hidden" class="form-control is-invalid">
            <div class="help-block invalid-feedback">
              {{ fileFeedbackForm.errors.get('fileFeedback') }}
            </div>
            <hr>
            <b-row align-h="end" class="mr-2">
              <b-button class="mr-2" @click="handleCancel">
                Cancel
              </b-button>
              <b-button variant="primary" @click="handleOk">
                Submit
              </b-button>
            </b-row>
          </b-form>
        </div>
        <div v-if="!feedbackTypeIsPdfImage">
          <audio-recorder
            ref="recorder"
            class="m-auto"
            :upload-url="audioFeedbackUploadUrl"
            :time="1"
            :successful-upload="submittedAudioFeedbackUpload"
            :failed-upload="failedAudioFeedbackUpload"
          />
        </div>
      </b-modal>
      <div v-if="!isLoading">
        <PageTitle :title="title" />
        <div v-if="submissionFiles.length>0">
          <b-container>
            <b-row>
              <p class="font-italic">
                <strong>Instructions:</strong> For each student, please enter a submission score for the open-ended
                component and optionally
                add comments in the form of text or a file upload. The total number of points that the student receives
                for this questions will be the sum of the points that they received for submitting any automatically
                graded responses (Question Submission Score)
                plus the number of points that you give them for their file submission (File Submission Score).
              </p>
            </b-row>
          </b-container>
          <b-form-group
            v-if="hasMultipleSections"
            id="sections"
            label-cols-sm="3"
            label-cols-lg="2"
            label="Section View"
            label-for="Section View"
          >
            <b-form-row>
              <b-col lg="3">
                <b-form-select
                  id="section-view"
                  v-model="sectionId"
                  :options="sections"
                  @change="getSubmissionFiles"
                />
              </b-col>
            </b-form-row>
          </b-form-group>
          <b-form-group
            id="submission_group"
            label-cols-sm="3"
            label-cols-lg="2"
            label="Submission Group"
            label-for="Submission Group"
          >
            <b-form-row>
              <b-col lg="3">
                <b-form-select
                  id="grade-view"
                  v-model="gradeView"
                  :options="gradeViews"
                  @change="getSubmissionFiles"
                />
              </b-col>
            </b-form-row>
          </b-form-group>
          <hr>
          <div v-if="!showNoFileSubmissionsExistAlert">
            <div class="text-center h5">
              Question
            </div>
            <div class="overflow-auto">
              <b-pagination
                :key="currentQuestionPage"
                v-model="currentQuestionPage"
                :total-rows="submissionFiles.length"
                :per-page="perPage"
                align="center"
                first-number
                last-number
                limit="20"
                @input="changePage(currentQuestionPage)"
              >
                <template v-slot:page="{ page, active }">
                  {{ submissionFiles[page - 1][currentStudentPage - 1].order }}
                </template>
              </b-pagination>
            </div>
            <div class="text-center h5">
              Student
            </div>
            <div class="overflow-auto">
              <b-pagination
                v-model="currentStudentPage"
                :total-rows="numStudents"
                :per-page="perPage"
                align="center"
                first-number
                last-number
                limit="20"
                @input="changePage()"
              />
            </div>
            <div class="text-center">
              <h5 class="font-italic">
                This question is out of
                {{ submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['points'] * 1 }} points.
              </h5>
              <div class="mb-2">
                <b-button variant="outline-primary"
                          @click="viewQuestion(submissionFiles[currentQuestionPage - 1][currentStudentPage - 1].question_id)"
                >
                  View Question
                </b-button>
                <span v-if="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['solution'] " class="ml-2">
                  <b-button variant="outline-primary"
                            @click.prevent="downloadSolutionFile('q', assignmentId, submissionFiles[currentQuestionPage - 1][currentStudentPage - 1].question_id, submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['solution'])"
                  >
                    Download Solution
                  </b-button>
                </span>
                <b-container>
                  <b-row class="justify-content-md-center mt-2">
                    <b-col lg="3">
                      <vue-bootstrap-typeahead
                        ref="queryTypeahead"
                        v-model="jumpToStudent"
                        :data="students"
                        placeholder="Enter A Student's Name"
                        @hit="setQuestionAndStudentByStudentName"
                      />
                    </b-col>
                  </b-row>
                </b-container>
              </div>
              <span v-if="!submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['solution'] "
                    class="font-italic mt-2"
              >
                You currently have no solution uploaded for this question.
              </span>
            </div>
            <div v-if="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission'] !== null">
              <hr>
              <b-container>
                <b-row>
                  <b-col>
                    <b-card header="default" :header-html="getStudentSubmissionTitle()" class="h-100">
                      <b-card-text>
                        <b-form ref="form">
                          <b-alert
                            :show="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['late_file_submission'] !== false"
                            variant="warning"
                          >
                            <span class="alert-link">
                              The file submission was late by  {{
                                submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['late_file_submission']
                              }}.
                              <span v-if="latePolicy === 'deduction'">
                                According to the late policy, a deduction of {{ lateDeductionPercent }}% should be applied once
                                <span v-if="lateDeductionApplicationPeriod !== 'once'">
                                  per "{{ lateDeductionApplicationPeriod }}"</span>.
                              </span>
                            </span>
                          </b-alert>
                          <strong>Date Submitted:</strong> {{
                            submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['date_submitted']
                          }} <br>
                          <strong>Date Graded:</strong> {{
                            submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['date_graded']
                              ? submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['date_graded']
                              : 'Not yet graded.'
                          }}<br>
                          <strong>Question Submission Score:</strong> {{
                            1 * submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['question_submission_score'] || 0
                          }}<br>
                          <strong>{{ capitalize(openEndedType) }} Submission Score:</strong> {{
                            1 * submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_submission_score'] || 0
                          }}
                          <br>
                          <strong>Total Score For This Question:</strong>
                          {{
                            (1 * submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['question_submission_score'] || 0)
                              + (1 * submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_submission_score'] || 0)
                          }} out of {{ submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['points'] * 1 }}
                          <br>
                          <b-input-group :prepend="`${capitalize(openEndedType)}  Submission Score:`" class="mt-3">
                            <b-form-input v-model="scoreForm.score"
                                          type="text"
                                          placeholder="Enter the score"
                                          :class="{ 'is-invalid': scoreForm.errors.has('score') }"
                                          @keydown="scoreForm.errors.clear('score')"
                            />
                            <b-input-group-append>
                              <b-button variant="primary" size="sm" @click="submitScoreForm">
                                Save Score
                              </b-button>
                            </b-input-group-append>
                            <has-error :form="scoreForm" field="score" />
                          </b-input-group>
                          <hr>
                          <b-container>
                            <b-row>
                              <b-col v-if="isOpenEndedFileSubmission">
                                <b-button variant="outline-primary"

                                          @click="openInNewTab(submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission_url'] )"
                                >
                                  Open File Submission
                                </b-button>
                              </b-col>
                              <b-col>
                                <b-button :disabled="viewSubmission"
                                          size="sm"
                                          @click="toggleView(currentStudentPage)"
                                >
                                  View Submission
                                </b-button>
                              </b-col>
                            </b-row>
                          </b-container>
                        </b-form>
                      </b-card-text>
                    </b-card>
                  </b-col>

                  <b-col>
                    <b-card header="default" :header-html="getGraderFeedbackTitle()" class="h-100">
                      <b-card-text>
                        <b-row class="mb-2">
                          <b-col>
                            <b-form-select v-model="textFeedbackMode"
                                           :options="textFeedbackModeOptions"
                            />
                          </b-col>
                          <b-col>
                            <b-button v-if="textFeedbackMode === 'canned_response'"
                                      variant="info"
                                      size="sm"
                                      @click="openEditCannedResponsesModal"
                            >
                              Edit Responses
                            </b-button>
                          </b-col>
                        </b-row>
                        <b-form ref="form">
                          <ckeditor v-if="textFeedbackMode === 'rich_text'"
                                    :key="`${currentQuestionPage}-${currentStudentPage}`"
                                    v-model="richTextFeedback"
                                    :config="richEditorConfig"
                                    style="margin-bottom: 23px"
                                    rows="4"
                                    max-rows="4"
                                    :class="{ 'is-invalid': textFeedbackForm.errors.has('textFeedback') }"
                                    @namespaceloaded="onCKEditorNamespaceLoaded"
                          />
                          <b-form-textarea
                            v-if="textFeedbackMode === 'plain_text'"
                            id="text_comments"
                            v-model="plainTextFeedback"
                            style="margin-bottom: 23px"
                            placeholder="Enter something..."
                            rows="4"
                            max-rows="4"
                            :class="{ 'is-invalid': textFeedbackForm.errors.has('textFeedback') }"
                            @keydown="textFeedbackForm.errors.clear('textFeedback')"
                          />
                          <has-error :form="textFeedbackForm" field="textFeedback" />

                          <b-form-select v-if="textFeedbackMode === 'canned_response'"
                                         v-model="cannedResponse"
                                         :options="cannedResponseOptions"
                                         class="mb-5"
                          />

                          <b-row  align-h="end" class="m-3">
                            <b-button variant="primary" size="sm" @click="submitTextFeedbackForm">
                              Save Comments
                            </b-button>
                          </b-row>
                          <hr>
                          <b-row class="d-flex justify-content-around">
                            <b-button
                              v-b-modal.modal-upload-file
                              variant="primary"
                              size="sm"
                              @click="openUploadFileModal()"
                            >
                              Upload Feedback
                            </b-button>

                            <b-button
                              :disabled="!viewSubmission"
                              size="sm"
                              @click="toggleView(currentStudentPage)"
                            >
                              View Feedback
                            </b-button>
                          </b-row>
                        </b-form>
                      </b-card-text>
                    </b-card>
                  </b-col>
                </b-row>
              </b-container>

              <hr>
            </div>
            <div v-show="retrievedFromS3" class="row mt-4 d-flex justify-content-center" style="height:1000px">
              <div v-show="viewSubmission">
                <div
                  v-if="(submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission_url'])"
                >
                  <div v-if="isOpenEndedFileSubmission">
                    <iframe width="600" height="600"
                            :src="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission_url']"
                    />
                  </div>
                  <div v-if="isOpenEndedAudioSubmission">
                    <b-card sub-title="Submission">
                      <audio-player
                        :src="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission_url']"
                      />
                    </b-card>
                  </div>
                </div>
                <div
                  v-if="isOpenEndedTextSubmission && submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission_text']"
                >
                  <b-card>
                    <span class="font-weight-bold"
                          v-html="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission_text']"
                    />
                  </b-card>
                </div>
                <div v-if="!submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission']">
                  <span class="text-info">{{ submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['name'] }} has not submitted a file.</span>
                </div>
              </div>
              <div v-show="!viewSubmission">
                <div
                  v-if="submissionFiles.length>0 && (submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_feedback_url'] !== null)"
                >
                  <iframe
                    v-if="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_feedback_type'] !== 'audio'"
                    width="600"
                    height="600"
                    :src="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_feedback_url']"
                  />
                  <b-card sub-title="Feedback">
                    <audio-player
                      v-if="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_feedback_type'] === 'audio'"

                      :src="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_feedback_url']"
                    />
                  </b-card>
                  <b-alert class="mt-1" :variant="audioFeedbackDataType" :show="showAudioFeedbackMessage">
                    <span class="font-weight-bold">{{ audioFeedbackDataMessage }}</span>
                  </b-alert>
                </div>

                <div v-else>
                  <span class="text-info">You have not uploaded a feedback file.</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-if="showNoFileSubmissionsExistAlert" class="mt-4">
          <b-alert show variant="warning">
            <span class="alert-link">
              There are no submissions for this view.</span>
          </b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { downloadSubmissionFile, downloadSolutionFile } from '~/helpers/DownloadFiles'
import { getAcceptedFileTypes } from '~/helpers/UploadFiles'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import Vue from 'vue'
import { ToggleButton } from 'vue-js-toggle-button'
import CKEditor from 'ckeditor4-vue'

Vue.prototype.$http = axios // needed for the audio player
export default {
  middleware: 'auth',
  components: {
    Loading,
    ToggleButton,
    VueBootstrapTypeahead,
    ckeditor: CKEditor.component
  },
  data: () => ({
    richTextFeedback: '',
    plainTextFeedback: '',
    cannedResponse: null,
    cannedResponseOptions: [],
    cannedResponseForm: new Form({
      canned_response: ''
    }),
    cannedResponses: [],
    richEditorConfig: {
      toolbar: [
        { name: 'clipboard', items: ['Cut', 'Copy', '-', 'Undo', 'Redo'] },
        {
          name: 'basicstyles',
          items: ['Bold', 'Italic', 'Underline', 'Subscript', 'Superscript']
        }
      ],
      removeButtons: '',
      height: 100
    },
    textFeedbackMode: 'plain_text',
    textFeedbackModeOptions: [
      { text: 'Plain Text', value: 'plain_text' },
      { text: 'Rich Text', value: 'rich_text' },
      { text: 'Canned Response', value: 'canned_response' }
    ],
    retrievedFromS3: false,
    hasMultipleSections: false,
    jumpToStudent: '',
    students: [],
    audioFeedbackDataType: '',
    audioFeedbackDataMessage: '',
    showAudioFeedbackMessage: false,
    feedbackModalTitle: 'Upload PDF/Image File',
    feedbackTypeIsPdfImage: true,
    audioFeedbackUploadUrl: '',
    isOpenEndedFileSubmission: false,
    isOpenEndedAudioSubmission: false,
    isOpenEndedTextSubmission: false,
    openEndedType: '',
    sections: [{ text: 'All Sections', value: 0 }],
    sectionId: 0,
    gradeViews: [
      { text: 'All Students', value: 'allStudents' },
      { text: 'Ungraded Submissions', value: 'ungradedSubmissions' },
      { text: 'Graded Submissions', value: 'gradedSubmissions' }
    ],
    latePolicy: null,
    lateDeductionApplicationPeriod: '',
    lateDeductionPercent: 0,
    isLoading: true,
    gradeView: 'allStudents',
    title: '',
    loaded: true,
    viewSubmission: true,
    showNoFileSubmissionsExistAlert: false,
    uploading: false,
    currentQuestionPage: 1,
    currentStudentPage: 1,
    perPage: 1,
    numStudents: 0,
    submissionFiles: [],
    textFeedbackForm: new Form({}),
    fileFeedbackForm: new Form({
      fileFeedback: null
    }),
    scoreForm: new Form({
      score: ''
    })
  }),
  created () {
    this.downloadSubmissionFile = downloadSubmissionFile
    this.downloadSolutionFile = downloadSolutionFile
    this.getAcceptedFileTypes = getAcceptedFileTypes
  },
  mounted () {
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentInfoForGrading()
    this.getSubmissionFiles()
    this.getCannedResponses()
  },
  methods: {
    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    },
    async removeCannedResponse (cannedResponseId) {
      try {
        const { data } = await this.cannedResponseForm.delete(`/api/canned-responses/${cannedResponseId}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          await this.getCannedResponses()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitCannedResponseForm (bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        const { data } = await this.cannedResponseForm.post('/api/canned-responses')
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.cannedResponseForm.canned_response = ''
          await this.getCannedResponses()
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async getCannedResponses () {
      try {
        const { data } = await axios.get('/api/canned-responses')
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.cannedResponseOptions = [{ text: 'Please choose a response', value: null }]
        for (let i = 0; i < data.canned_responses.length; i++) {
          let cannedResponse = data.canned_responses[i]
          let cannedResponseOption = { text: cannedResponse.canned_response, value: cannedResponse.id }
          this.cannedResponseOptions.push(cannedResponseOption)
        }
        this.cannedResponses = data.canned_responses
        return true
      } catch (error) {
        this.$noty.error(error.message)
        return false
      }
    },
    async openEditCannedResponsesModal () {
      let success = await this.getCannedResponses()
      if (success) {
        this.$bvModal.show('modal-edit-canned-responses')
      }
    },
    async getFilesFromS3 () {
      try {
        let current = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]
        if (!current.got_files) {
          const { data } = await axios.post(`/api/submission-files/get-files-from-s3/${this.assignmentId}/${current.question_id}/${current.user_id}`, { open_ended_submission_type: current.open_ended_submission_type })
          console.log(data)
          if (data.type === 'success') {
            let files = data.files
            this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1].file_feedback_url = files.file_feedback_url
            this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1].submission_url = files.submission_url
            this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1].submission_text = files.submission_text
            this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1].got_files = true
          } else {
            this.$noty.error(`We could not retrieve the files for ${current.name}`)
          }
        }
      } catch (error) {
        this.$noty.error(`We could not retrieve the files for the student. ${error.message}`)
      }
    },
    setQuestionAndStudentByStudentName () {
      for (let j = 0; j < this.submissionFiles[this.currentQuestionPage - 1].length; j++) {
        if (this.jumpToStudent === this.submissionFiles[this.currentQuestionPage - 1][j]['name']) {
          this.currentStudentPage = j + 1
          this.textFeedbackForm.textFeedback = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['text_feedback']
          this.$refs.queryTypeahead.inputValue = this.jumpToStudent = ''
          return
        }
      }
    },
    toggleFeedbackType () {
      this.feedbackTypeIsPdfImage = !this.feedbackTypeIsPdfImage
      let feedbackType = this.feedbackTypeIsPdfImage ? 'PDF/Image' : 'Audio'
      this.feedbackModalTitle = `Upload ${feedbackType} File`
    },
    handleCancel () {
      this.$bvModal.hide(`modal-upload-file`)
    },
    failedAudioFeedbackUpload (data) {
      this.$bvModal.hide('modal-upload-file')
      this.$noty.error('We were not able to perform the upload.  Please try again or contact us for assistance.')
      axios.post('/api/submission-audios/error', JSON.stringify(data))
    },
    submittedAudioFeedbackUpload (response) {
      let data = response.data
      this.audioFeedbackDataType = (data.type === 'success') ? 'success' : 'danger'
      this.audioFeedbackDataMessage = data.message
      this.showAudioFeedbackMessage = true
      setTimeout(() => {
        this.showAudioFeedbackMessage = false
      }, 3000)
      if (data.type === 'success') {
        this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1].file_feedback_url = data.file_feedback_url
        this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1].file_feedback_type = data.file_feedback_type
      }
      this.viewSubmission = false
      this.$refs.recorder.removeRecord()
      this.$bvModal.hide('modal-upload-file')
    },
    capitalize (word) {
      return word.charAt(0).toUpperCase() + word.slice(1)
    },
    getGraderFeedbackTitle () {
      let grader = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1].grader_name
        ? 'by ' + this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1].grader_name
        : ''
      return `<h5>Grader Feedback ${grader}</h5>`
    },
    getStudentSubmissionTitle () {
      return `<h5>Submission Information for  ${this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['name']}</h5>`
    },
    viewQuestion (questionId) {
      window.open(`/assignments/${this.assignmentId}/questions/view/${questionId}/view`)
    },
    openInNewTab (url) {
      console.log(url)
      window.open(url, '_blank')
    },
    async getAssignmentInfoForGrading () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/get-info-for-grading`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let assignment = data.assignment
        let sections = data.sections
        this.hasMultipleSections = sections.length > 1
        if (this.hasMultipleSections) {
          for (let i = 0; i < sections.length; i++) {
            let section = sections[i]
            this.sections.push({ text: section.name, value: section.id })
          }
        }

        this.title = `Grade Open-Ended Submissions For ${assignment.name}`
        this.latePolicy = assignment.late_policy
        this.lateDeductionApplicationPeriod = assignment.late_deduction_application_period
        this.lateDeductionPercent = assignment.late_deduction_percent
      } catch (error) {
        this.title = 'Grade Open-Ended Submissions'
      }
    },
    async toggleView () {
      this.viewSubmission = !this.viewSubmission
    },
    async submitScoreForm () {
      try {
        this.scoreForm.assignment_id = this.assignmentId
        this.scoreForm.question_id = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['question_id']
        this.scoreForm.user_id = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['user_id']

        const { data } = await this.scoreForm.post('/api/submission-files/score')
        this.$noty[data.type](data.message)
        console.log(data)
        if (data.type === 'success') {
          this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['file_submission_score'] = this.scoreForm.score
          this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['date_graded'] = data.date_graded
          this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['grader_name'] = data.grader_name
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    openUploadFileModal () {
      this.fileFeedbackForm.errors.clear('fileFeedback')
      let assignmentId = parseInt(this.assignmentId)
      let questionId = parseInt(this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['question_id'])
      let studentUserId = parseInt(this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['user_id'])
      this.audioFeedbackUploadUrl = `/api/submission-audios/audio-feedback/${studentUserId}/${assignmentId}/${questionId}`
    },
    async handleOk (bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        if (this.uploading) {
          this.$noty.info('Please be patient while the file is uploading.')
          return false
        }
        this.fileFeedbackForm.errors.set('fileFeedback', null)
        this.uploading = true
        // https://stackoverflow.com/questions/49328956/file-upload-with-vue-and-laravel
        let formData = new FormData()
        formData.append('fileFeedback', this.fileFeedbackForm.fileFeedback)
        formData.append('assignmentId', this.assignmentId)
        formData.append('questionId', this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['question_id'])
        formData.append('userId', this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['user_id'])
        formData.append('_method', 'put') // add this
        const { data } = await axios.post('/api/submission-files/file-feedback', formData)
        console.log(data)
        if (data.type === 'error') {
          this.fileFeedbackForm.errors.set('fileFeedback', data.message)
        } else {
          this.$noty.success(data.message)
          this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['file_feedback_url'] = data.file_feedback_url
          this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['file_feedback_type'] = data.file_feedback_type
          this.$bvModal.hide('modal-upload-file')
        }
      } catch (error) {
        if (error.message.includes('status code 413')) {
          error.message = 'The maximum size allowed is 10MB.'
        }
        this.$noty.error(error.message)
      }
      this.uploading = false
    },
    getTextFeedback (textFeedbackMode) {
      switch (textFeedbackMode) {
        case ('rich_text'):
          return this.richTextFeedback
        case ('plain_text'):
          return this.plainTextFeedback
        case ('canned_response'):

          for (let i = 0; i < this.cannedResponseOptions.length; i++) {
            console.log(this.cannedResponseOptions[i].value)
            console.log(this.cannedResponse)
            if (this.cannedResponseOptions[i].value === this.cannedResponse) {
              return this.cannedResponseOptions[i].text
            }
          }
          break
        default:
          return false
      }
    },
    async submitTextFeedbackForm () {
      try {
        this.textFeedbackForm.text_feedback_editor = (this.textFeedbackMode === 'rich_text') ? 'rich' : 'plain'
        this.textFeedbackForm.textFeedback = this.getTextFeedback(this.textFeedbackMode)
        if (this.textFeedbackForm.textFeedback === false) {
          this.$noty.error('That is not a valid feedback mode.')
          return false
        }
        this.textFeedbackForm.assignment_id = this.assignmentId
        this.textFeedbackForm.question_id = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['question_id']
        this.textFeedbackForm.user_id = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['user_id']

        const { data } = await this.textFeedbackForm.post('/api/submission-files/text-feedback')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['text_feedback_editor'] = this.textFeedbackForm.text_feedback_editor
          this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['text_feedback'] = this.textFeedbackForm.textFeedback
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    setTextFeedback (textFeedback) {
      this.richTextFeedback = ''
      this.plainTextFeedback = ''
      this.textFeedbackMode = 'plain_text'
      if (textFeedback) {
        switch (this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['text_feedback_editor']) {
          case ('rich'):
            this.richTextFeedback = textFeedback
            this.textFeedbackMode = 'rich_text'
            break
          case ('plain'):
            this.plainTextFeedback = textFeedback
            this.textFeedbackMode = 'plain_text'
            break
          default:
            this.plainTextFeedback = textFeedback
            this.textFeedbackMode = 'plain_text'
            break
        }
      }
    },
    async changePage () {
      this.retrievedFromS3 = false
      this.showAudioFeedbackMessage = false
      let textFeedback = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['text_feedback']
      this.setTextFeedback(textFeedback)
      this.textFeedbackForm.textFeedback = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['text_feedback']
      console.log(this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1])

      this.openEndedType = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1].open_ended_submission_type
      this.isOpenEndedFileSubmission = (this.openEndedType === 'file')
      this.isOpenEndedAudioSubmission = (this.openEndedType === 'audio')
      this.isOpenEndedTextSubmission = (this.openEndedType === 'text')
      await this.getFilesFromS3()
      this.retrievedFromS3 = true
    },
    async getTemporaryUrl (file, currentQuestionPage, currentStudentPage) {
      if (this.submissionFiles[currentQuestionPage - 1][currentStudentPage - 1][file] && !this.submissionFiles[currentQuestionPage - 1][currentStudentPage - 1][`${file}_url`]) {
        try {
          const { data } = await axios.post('/api/submission-files/get-temporary-url-from-request',
            {
              'assignment_id': this.assignmentId,
              'file': this.submissionFiles[currentQuestionPage - 1][currentStudentPage - 1][file]
            })
          if (data.type === 'error') {
            this.$noty.error(data.message)
            return false
          }
          this.submissionFiles[currentQuestionPage - 1][currentStudentPage - 1][`${file}_url`] = data.temporary_url
        } catch (error) {
          this.$noty.error(error.message)
        }
      }
    },
    submissionUrlExists (currentStudentPage) {
      return (this.submissionFiles[currentStudentPage - 1]['submission_url'] !== null)
    },
    setQuestionAndStudentByQuestionIdAndStudentUserId (questionId, studentUserId) {
      for (let i = 0; i < this.submissionFiles.length; i++) {
        for (let j = 0; j < this.submissionFiles[i].length; j++) {
          console.log(this.submissionFiles[i][j]['question_id'], this.submissionFiles[i][j]['user_id'])
          if (parseInt(questionId) === parseInt(this.submissionFiles[i][j]['question_id']) &&
            parseInt(studentUserId) === parseInt(this.submissionFiles[i][j]['user_id'])) {
            this.currentQuestionPage = i + 1
            this.currentStudentPage = j + 1
            return
          }
        }
      }
    },
    async getSubmissionFiles () {
      try {
        const { data } = await axios.get(`/api/submission-files/${this.assignmentId}/${parseInt(this.sectionId)}/${this.gradeView}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false
        }
        this.showNoFileSubmissionsExistAlert = !(data.user_and_submission_file_info.length > 0)
        if (this.showNoFileSubmissionsExistAlert) {
          this.isLoading = false
          return false
        }

        this.submissionFiles = data.user_and_submission_file_info
        this.students = []
        this.numStudents = Object.keys(this.submissionFiles[0]).length
        for (let i = 0; i < this.numStudents; i++) {
          this.students.push(this.submissionFiles[0][i].name)
        }

        this.currentQuestionPage = 1
        this.currentStudentPage = 1

        // loop through questions, inner loop through students, if match, then set question and student)

        if (this.$route.params.questionId && this.$route.params.studentUserId) {
          this.setQuestionAndStudentByQuestionIdAndStudentUserId(this.$route.params.questionId, this.$route.params.studentUserId)
          this.setTextFeedback(this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['text_feedback'])
        } else {
          this.setTextFeedback(this.submissionFiles[0][0]['text_feedback'])
        }

        this.openEndedType = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1].open_ended_submission_type
        this.isOpenEndedFileSubmission = (this.openEndedType === 'file')
        this.isOpenEndedAudioSubmission = (this.openEndedType === 'audio')
        this.isOpenEndedTextSubmission = (this.openEndedType === 'text')
        await this.getFilesFromS3()
        this.retrievedFromS3 = true
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
<style>
div.ar-icon svg {
  vertical-align: top !important;
}
</style>
