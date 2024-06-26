<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-file-upload'"/>
    <b-modal id="modal-last-open-ended-submission"
             title="Last Open-Ended Submission"
             hide-footer
    >
      Open-ended questions are questions which will require a grader. Some examples are file uploads, text
      based responses, and audio uploads.
    </b-modal>
    <b-modal id="modal-last-auto-graded-submission"
             title="Last Auto-graded Submission"
             hide-footer
    >
      Auto-graded questions are questions which can be graded automatically by ADAPT.
      Some examples are multiple choice, true false, numeric based and matching.
    </b-modal>
    <b-modal id="modal-number-of-allowed-attempts"
             title="Number of Allowed Attempts"
             hide-footer
    >
      The number of allowed attempts tells you how many times you can re-submit each question before the
      due date.
    </b-modal>
    <b-modal id="modal-per-attempt-penalty"
             title="Per Attempt Penalty"
             hide-footer
    >
      After your first attempt, a penalty of {{ numberOfAllowedAttemptsPenalty }}% will be applied per
      attempt.
      As an example, if a question is worth 10 points then on the second attempt,
      you will
      receive {{ 10 * (1 - parseFloat(numberOfAllowedAttemptsPenalty) / 100) }} points.
    </b-modal>
    <b-modal
      id="modal-completed-assignment"
      ref="modalThumbsUp"
      hide-footer
      size="sm"
      title="Congratulations!"
    >
      <b-container>
        <b-row>
          <img :src="asset('assets/img/check_twice.gif?rnd=' + cacheKey)" width="275">
        </b-row>
        <b-row><span class="h5">All Question Submissions Successfully Completed.</span></b-row>
      </b-container>
    </b-modal>
    <b-modal
      id="modal-submission-accepted"
      ref="modalThumbsUp"
      hide-footer
      size="sm"
      title="Submission Accepted"
    >
      <b-container>
        <b-row>
          <img :src="asset('assets/img/check_twice.gif')"  width="275">
        </b-row>
        <b-row>
            {{ successMessage }}
        </b-row>
      </b-container>
    </b-modal>
    <b-modal
      id="modal-thumbs-down"
      ref="modalThumbsUp"
      hide-footer
      size="lg"
      title="Submission Not Accepted"
    >
      <b-alert variant="danger" :show="true">
        <span class="font-weight-bold" style="font-size: large" v-html="errorMessage"/>
      </b-alert>
    </b-modal>
    <b-modal id="modal-confirm-set-page"
             ref="confirmSetPage"
             title="Confirm Set Page"
    >
      <p>
        You already have submitted a file for this question. If you set the page again, your submission file
        will be updated with the associated page from your compiled PDF.
      </p>
      <p>Would you like to update your submission with a page from your compiled PDF?</p>
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="$bvModal.hide('modal-confirm-set-page')">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary" @click="handleSetPageAsSubmission">
          Yes, set the page!
        </b-button>
      </template>
    </b-modal>

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
        <PageTitle :title="name"/>
        <b-container>
          <div v-if="assessmentType !== 'clicker' || pastDue">
            <b-row align-h="end">
              <b-button class="ml-3 mb-2" variant="primary" size="sm" @click="getStudentView(assignmentId)">
                View Assessments
              </b-button>
            </b-row>
            <hr>
          </div>
          <div v-if="assessmentType === 'clicker' && !pastDue">
            <b-alert show variant="info">
              <span class="font-weight-bold">Please wait for your instructor to open up this assignment.</span>
            </b-alert>
          </div>
          <div v-show="isInstructorLoggedInAsStudent">
            <LoggedInAsStudent :student-name="user.first_name + ' ' + user.last_name"/>
          </div>
          <b-card v-show="assessmentType !== 'clicker'" header="default"
                  header-html="<h2 class=&quot;h5&quot;>Important Information</h2>"
          >
            <b-card-text>
              <ul style="list-style: none;">
                <li v-if="public_description" class="mb-2">
                  <span class="font-weight-bold">Description: </span> <span v-html="public_description"/>
                </li>
                <li v-if="instructions.length" class="mb-2">
                  <span v-html="instructions"/>
                </li>
                <li class="mb-2">
                  <span class="font-weight-bold">Number of Points: </span>
                  <span>This assignment is worth a total of {{
                      totalPoints
                    }} point{{ totalPoints !== 1 ? 's' : '' }}.</span>
                </li>
                <li class="mb-2">
                  <span class="font-weight-bold">Number of Questions: </span>
                  <span>This assignment has {{
                      items.length
                    }} question{{ items.length !== 1 ? 's' : '' }}.</span>
                </li>
                <li v-show="assessmentType === 'real time'" class="mb-2">
                  <span class="font-weight-bold">Number of Allowed Attempts: </span>
                  {{ numberOfAllowedAttempts }}
                  <QuestionCircleTooltipModal :aria-label="'Number of Allowed Attempts'"
                                              :modal-id="'modal-number-of-allowed-attempts'"
                  />

                </li>
                <li
                  v-show="assessmentType === 'real time' && numberOfAllowedAttempts !== '1' && numberOfAllowedAttemptsPenalty>0"
                  class="mb-2"
                >
                  <span class="font-weight-bold">Per Attempt Penalty: </span>
                  {{ numberOfAllowedAttemptsPenalty }}%
                  <QuestionCircleTooltipModal :aria-label="'Per Attempt Penalty'"
                                              :modal-id="'modal-per-attempt-penalty'"
                  />

                </li>
                <li class="mb-2">
                  <span class="font-weight-bold">Due Date: </span>
                  <span>This assignment is due {{ formattedDue }}.</span>
                  <span v-if="extension">(You have an extension until {{ extension }}).</span>
                </li>
                <li class="mb-2">
                  <span class="font-weight-bold">Late Policy: </span>
                  <span> {{ formattedLatePolicy }}</span>
                </li>
                <li v-if="bothFileUploadMode && hasAtLeastOneFileUpload" class="mb-2">
                  <span class="font-weight-bold">
                    Open-ended submissions:</span>
                  <span>
                    Upload a single compiled PDF of the questions and assign pages and/or submit individual submissions on each page.
                  </span>
                </li>
              </ul>
            </b-card-text>
          </b-card>

          <b-card v-show="compiledPdf || (bothFileUploadMode && hasAtLeastOneFileUpload)" class="mt-3 mb-3"
                  header="default"
                  header-html="<h2 class=&quot;h5&quot;>Upload Compiled PDF Submission</h2>"
          >
            Upload your compiled PDF and then set each submission.
            <file-upload
              ref="upload"
              v-model="files"
              accept="application/pdf"
              put-action="/put.method"
              @input-file="inputFile"
              @input-filter="inputFilter"
              @click="submissionFileForm.errors.clear('submission')"
            />
            <input type="hidden" class="form-control is-invalid">
            <div v-if="submissionFileForm.errors.has('submission')"
                 class="help-block invalid-feedback"
            >
              {{ submissionFileForm.errors.errors.submission[0] }}
            </div>
            <div class="upload mt-3">
              <ul v-if="files.length && (preSignedURL !== '')">
                <li v-for="file in files" :key="file.id">
                  <span :class="file.success ? 'text-success font-weight-bold' : ''">{{
                      file.name
                    }}</span> -
                  <span>{{ formatFileSize(file.size) }} </span>
                  <span v-if="file.size > 10000000">Note: large files may take up to a minute to process.</span>
                  <span v-if="file.error" class="text-danger">Error: {{ file.error }}</span>
                  <span v-else-if="file.active" class="ml-2">
                    <b-spinner small type="grow"/>
                    Uploading File...
                  </span>
                  <span v-if="processingFile">
                    <b-spinner small type="grow"/>
                    Processing file...
                  </span>
                  <b-button v-if="!processingFile && (preSignedURL !== '') && (!$refs.upload || !$refs.upload.active)"
                            variant="success"
                            size="sm"
                            style="vertical-align: top"
                            @click.prevent="$refs.upload.active = true"
                  >
                    Start Upload
                  </b-button>
                </li>
              </ul>
            </div>
            <div v-show="fullPdfUrl" class="mb-2">
              <b-embed
                :key="fullPdfUrlKey"
                type="iframe"
                aspect="16by9"
                aria-label="Compiled PDF"
                :src="getFullPdfUrlAtPage(fullPdfUrl, questionSubmissionPageForm.page)"
                allowfullscreen
              />
            </div>
          </b-card>
          <b-card v-show="items.length && assessmentType !== 'clicker'"
                  id="questions_card"
                  class="mt-3 mb-3"
                  header="default"
                  header-html="<h2 class=&quot;h5&quot;>Questions</h2>"
          >
            <b-alert variant="success" :show="completedAllAssignmentQuestions">
              <span class="font-weight-bold">You have completed all assessments on this assignment!</span>
            </b-alert>
            <div v-if="!setAllPages && fullPdfUrl">
              <b-alert show variant="info">
                Please remember to set the Initial Pages for each of the questions below.
              </b-alert>
            </div>
            <b-table
              v-show="items.length && assessmentType !== 'clicker'"
              id="summary_of_questions_and_submissions"
              aria-label="Summary of questions and submissions"
              striped
              hover
              :no-border-collapse="true"
              :fields="shownFields"
              :items="items"
            >
              <template #cell(question_number)="data">
                <a href="" @click.stop.prevent="viewQuestion(data.item.question_id)"><span style="font-size:large">&nbsp;{{
                    data.item.question_number
                  }}&nbsp;</span></a>
              </template>
              <template v-slot:head(last_question_submission)="data">
                Last Auto-Graded Submission
                <QuestionCircleTooltipModal :aria-label="'Last Auto-Graded Submission'"
                                            :modal-id="'modal-last-auto-graded-submission'"
                />
              </template>
              <template #cell(last_question_submission)="data">
                <span
                  :class="{ 'table-text-danger': data.item.questionSubmissionRequired && !data.item.showThumbsUpForQuestionSubmission }"
                >
                  {{ data.item.last_question_submission }}
                </span>
                <font-awesome-icon v-show="data.item.showThumbsUpForQuestionSubmission" class="text-success"
                                   :icon="checkIcon"
                />
              </template>

              <template #cell(last_open_ended_submission)="data">
                <span
                  :class="{ 'table-text-danger': data.item.openEndedSubmissionRequired && !data.item.showThumbsUpForOpenEndedSubmission }"
                >
                  <span v-if="!data.item.showThumbsUpForOpenEndedSubmission">{{
                      data.item.last_open_ended_submission
                    }}</span>
                  <span v-if="data.item.showThumbsUpForOpenEndedSubmission">
                    <a :href="data.item.submission_file_url" target="_blank">{{
                        data.item.last_open_ended_submission
                      }}</a>
                  </span>
                </span>
                <font-awesome-icon v-show="data.item.showThumbsUpForOpenEndedSubmission" class="text-success"
                                   :icon="checkIcon"
                />
              </template>

              <template v-slot:head(last_open_ended_submission)="data">
                Last Open-Ended Submission
                <QuestionCircleTooltipModal :aria-label="'Last Open-Ended Submission'"
                                            :modal-id="'modal-last-open-ended-submission'"
                />
              </template>
              <template #cell(solution_file_url)="data">
                <SolutionFileHtml :questions="items"
                                  :current-page="data.item.question_number"
                                  assignment-name="Question"
                />
              </template>
              <template #cell(page)="data">
                <div v-if="data.item.isOpenEndedFileSubmission">
                  <b-input-group>
                    <div class="d-flex">
                      <b-form-input :id="`set_page_for_question_${data.item.question_number}`"
                                    v-model="data.item.page"
                                    type="text"
                                    style="width: 60px"
                                    placeholder=""
                                    :class="{ 'is-invalid': data.item.question_id === questionSubmissionPageForm.questionId && questionSubmissionPageForm.errors.has('page') }"
                                    @keydown="questionSubmissionPageForm.errors.clear('page')"
                      />

                      <b-button variant="primary"
                                size="sm"
                                class="ml-1"
                                :disabled="!fullPdfUrl"
                                @click="confirmSetPageAsSubmission(data.item.question_number, data.item.question_id, data.item.page)"
                      >
                        <label :for="`set_page_for_question_${data.item.question_number}`" style="margin-bottom:0">Set
                          Page</label>
                      </b-button>
                      <has-error v-show="data.item.question_id === questionSubmissionPageForm.questionId"
                                 :form="questionSubmissionPageForm" field="page"
                      />
                    </div>
                  </b-input-group>
                </div>
                <div v-else>
                  N/A
                </div>
              </template>
            </b-table>
          </b-card>

          <b-card v-if="canViewAssignmentStatistics" class="mb-5" header="default"
                  header-html="<h2 class=&quot;h5&quot;>Statistics</h2>"
          >
            <AssignmentStatistics/>
          </b-card>
        </b-container>
      </div>
    </div>
  </div>
</template>

<script>

import { mapGetters } from 'vuex'
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import AssignmentStatistics from '~/components/AssignmentStatistics'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faThumbsUp, faCheck } from '@fortawesome/free-solid-svg-icons'
import Vue from 'vue'
import Form from 'vform'
import { getFullPdfUrlAtPage } from '~/helpers/DownloadFiles'
import LoggedInAsStudent from '~/components/LoggedInAsStudent'
import AllFormErrors from '~/components/AllFormErrors'
import SolutionFileHtml from '~/components/SolutionFileHtml'
import QuestionCircleTooltipModal from '~/components/QuestionCircleTooltipModal'
import { makeFileUploaderAccessible } from '~/helpers/accessibility/makeFileUploaderAccessible'

const VueUploadComponent = require('vue-upload-component')
Vue.component('file-upload', VueUploadComponent)

export default {
  components: {
    AssignmentStatistics,
    Loading,
    FontAwesomeIcon,
    LoggedInAsStudent,
    AllFormErrors,
    SolutionFileHtml,
    QuestionCircleTooltipModal
  },
  metaInfo () {
    return { title: 'Assignment - Summary' }
  },
  middleware: 'auth',
  data: () => ({
    setAllPages: false,
    cacheKey: 0,
    numberOfAllowedAttempts: '',
    numberOfAllowedAttemptsPenalty: '',
    hasAtLeastOneFileUpload: false,
    allFormErrors: [],
    extension: null,
    public_description: null,
    isInstructorLoggedInAsStudent: false,
    bothFileUploadMode: false,
    compiledPdf: false,
    totalPoints: '',
    completedAllAssignmentQuestions: false,
    successMessage: '',
    errorMessage: '',
    openEndedSubmissionQuestionOptions: [],
    question: {},
    fullPdfUrl: '',
    fullPdfUrlKey: 0,
    questionSubmissionPageForm: new Form({
      question_number: '',
      questionId: 0,
      page: ''
    }),
    processingFile: false,
    preSignedURL: '',
    files: [],
    submissionFileForm: new Form({
      questionFile: null,
      assignmentId: null,
      questionId: null
    }),
    thumbsUpIcon: faThumbsUp,
    checkIcon: faCheck,
    fields: [],
    items: [],
    pastDue: false,
    clickerPollingSetInterval: null,
    assessmentUrlType: '',
    assessmentType: '',
    isLoading: true,
    name: '',
    instructions: '',
    formattedLatePolicy: '',
    formattedDue: '',
    canViewAssignmentStatistics: false,
    assignmentInfo: {}
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    shownFields () {
      return this.fields.filter(field => field.shown)
    }
  },
  created () {
    this.getFullPdfUrlAtPage = getFullPdfUrlAtPage
  },
  async mounted () {
    this.assignmentId = this.$route.params.assignmentId
    await this.getAssignmentSummary()
    await this.getSelectedQuestions(this.assignmentId)
    this.isLoading = false
    if (this.assessmentType === 'clicker' && !this.pastDue) {
      this.initClickerPolling()
    }
    this.$nextTick(function () {
      this.resizeHandler()
      makeFileUploaderAccessible()
    })
    window.addEventListener('resize', this.resizeHandler)
  },
  beforeDestroy () {
    window.removeEventListener('resize', this.resizeHandler)
  },
  methods: {
    resizeHandler () {
      let table = document.getElementById('summary_of_questions_and_submissions')
      if (table && this.zoomGreaterThan(1.2)) {
        document.getElementById('questions_card').style.width = (parseInt(table.offsetWidth) + 100).toString() + 'px'
      } else {
        document.getElementById('questions_card').style.width = ''
      }
    },
    confirmSetPageAsSubmission (questionNumber, questionId, page) {
      this.questionSubmissionPageForm.questionId = questionId
      this.questionSubmissionPageForm.page = page
      this.questionSubmissionPageForm.question_number = questionNumber
      let question = this.items.find(question => question.question_id === questionId)
      question.submission_file_exists
        ? this.$bvModal.show('modal-confirm-set-page')
        : this.handleSetPageAsSubmission()
    },
    async handleSetPageAsSubmission () {
      try {
        const { data } = await this.questionSubmissionPageForm.patch(`/api/submission-files/${this.assignmentId}/${this.questionSubmissionPageForm.questionId}/page`)
        this.$bvModal.hide('modal-confirm-set-page')
        if (data.type === 'error') {
          this.errorMessage = data.message
          this.$bvModal.show('modal-thumbs-down')
          return false
        }
        let openEndedSubmission
        openEndedSubmission = this.items.find(question => question.question_id === this.questionSubmissionPageForm.questionId)
        openEndedSubmission.submission_file_exists = true
        openEndedSubmission.submission_file_url = data.submission_file_url
        openEndedSubmission.last_open_ended_submission = data.date_submitted
        if (openEndedSubmission.hasOwnProperty('total_score')) {
          openEndedSubmission.total_score = data.total_score
        }
        openEndedSubmission.showThumbsUpForOpenEndedSubmission = true
        this.successMessage = data.message
        this.completedAllAssignmentQuestions = data.completed_all_assignment_questions
        this.cacheKey++
        if (this.completedAllAssignmentQuestions) {
          this.$bvModal.show('modal-completed-assignment')
        }
        this.$bvModal.show('modal-submission-accepted')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.errorMessage = error.message
          this.$bvModal.hide('modal-confirm-set-page')
          this.$bvModal.show('modal-thumbs-down')
        }
      }
    },
    formatFileSize (size) {
      let sizes = [' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB']
      for (let i = 1; i < sizes.length; i++) {
        if (size < Math.pow(1024, i)) return (Math.round((size / Math.pow(1024, i - 1)) * 100) / 100) + sizes[i - 1]
      }
      return size
    },
    inputFile (newFile, oldFile) {
      if (newFile && oldFile && !newFile.active && oldFile.active) {
        // Get response data

        if (newFile.xhr) {
          //  Get the response status code
          console.log('status', newFile.xhr.status)
          if (newFile.xhr.status === 200) {
            if (!this.handledOK) {
              this.handledOK = true
              console.log(this.handledOK)
              this.handleOK()
            }
          } else {
            this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
          }
        } else {
          this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
        }
      }
    },
    async handleOK () {
      this.submissionFileForm.uploadLevel = 'assignment'
      this.submissionFileForm.s3_key = this.s3Key
      this.submissionFileForm.original_filename = this.originalFilename
      // Prevent modal from closing
      // Trigger submit handler
      if (this.uploading) {
        this.$noty.info('Please be patient while the file is uploading.')
        return false
      }
      this.processingFile = true

      try {
        // https://stackoverflow.com/questions/49328956/file-upload-with-vue-and-laravel
        let formData = new FormData()
        formData.append('submission', this.submissionFileForm)
        formData.append('assignmentId', this.assignmentId)
        formData.append('questionId', '0')
        formData.append('type', 'submission')
        formData.append('s3_key', this.submissionFileForm.s3_key)
        formData.append('original_filename', this.submissionFileForm.original_filename)
        formData.append('uploadLevel', this.submissionFileForm.uploadLevel)// at the assignment or question level; used for cutups
        formData.append('_method', 'put') // add this

        const { data } = await axios.post('/api/submission-files', formData)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.fullPdfUrl = data.full_pdf_url
          this.fields.find(field => field.key === 'page').shown = true
          this.setAllPages = false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.processingFile = false
      this.files = []
    },
    handleCancel () {
      this.$refs.upload.active = false
      this.files = []
      this.processingFile = false
    },
    async inputFilter (newFile, oldFile, prevent) {
      this.submissionFileForm.errors.clear()
      if (newFile && !oldFile) {
        // Filter non-image file
        if (parseInt(newFile.size) > 20000000) {
          let message = '20 MB max allowed.  Your file is too large.  '
          if (/\.(pdf)$/i.test(newFile.name)) {
            message += 'You might want to try an online PDF compressor such as https://smallpdf.com/compress-pdf to reduce the size.'
          }
          this.submissionFileForm.errors.set('submission', message)
          this.allFormErrors = this.submissionFileForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-file-upload')
          return prevent()
        }
        let validUploadTypesMessage = `The file type must be .pdf`

        let validExtension = /\.(pdf)$/i.test(newFile.name)

        if (!validExtension) {
          this.submissionFileForm.errors.set('submission', validUploadTypesMessage)
          this.allFormErrors = this.submissionFileForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-file-upload')

          return prevent()
        } else {
          try {
            this.preSignedURL = ''
            let uploadFileData = {
              assignment_id: this.assignmentId,
              upload_file_type: 'submission',
              file_name: newFile.name
            }
            const { data } = await axios.post('/api/s3/pre-signed-url', uploadFileData)
            if (data.type === 'error') {
              this.$noty.error(data.message)
              return false
            }
            this.preSignedURL = data.preSignedURL
            newFile.putAction = this.preSignedURL
            this.s3Key = data.s3_key
            this.originalFilename = newFile.name
            this.handledOK = false
          } catch (error) {
            this.$noty.error(error.message)
            return false
          }
        }
      }

      // Create a blob field
      newFile.blob = ''
      let URL = window.URL || window.webkitURL
      if (URL && URL.createObjectURL) {
        newFile.blob = URL.createObjectURL(newFile.file)
      }
    },
    viewQuestion (questionId) {
      this.$router.push({ path: `/assignments/${this.assignmentId}/questions/view/${questionId}` })
      return false
    },
    initClickerPolling () {
      let self = this
      this.submitClickerPolling(this.assignmentId)
      this.clickerPollingSetInterval = setInterval(function () {
        self.submitClickerPolling(self.assignmentId)
      }, 3000)
    },
    async submitClickerPolling (assignmentId) {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/clicker-question`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          clearInterval(this.clickerPollingSetInterval)
          this.clickerPollingSetInterval = null
          return false
        }
        let questionId = data.question_id
        if (questionId) {
          window.location = `/assignments/${this.assignmentId}/questions/view/${questionId}`
        }
      } catch (error) {
        this.$noty.error(error.message)
        this.name = 'Assignment Summary'
      }
    },
    async getAssignmentSummary () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let assignment = data.assignment
        this.isInstructorLoggedInAsStudent = assignment.is_instructor_logged_in_as_student
        this.public_description = assignment.public_description
        this.instructions = assignment.instructions
          ? assignment.instructions.replace('<p>', '<p><span class="font-weight-bold">Instructions: </span>')
          : ''
        this.formattedLatePolicy = assignment.formatted_late_policy
        this.formattedDue = assignment.formatted_due
        this.formattedAttemptsPolicy = assignment.formatted_attempts_policy
        this.totalPoints = assignment.total_points
        this.numberOfAllowedAttempts = assignment.number_of_allowed_attempts
        this.numberOfAllowedAttemptsPenalty = assignment.number_of_allowed_attempts_penalty
        this.compiledPdf = assignment.file_upload_mode === 'compiled_pdf'
        this.bothFileUploadMode = assignment.file_upload_mode === 'both'
        this.assessmentType = assignment.assessment_type
        this.extension = assignment.extension
        this.name = assignment.name
        this.pastDue = assignment.past_due
        this.canViewAssignmentStatistics = assignment.can_view_assignment_statistics
        this.fullPdfUrl = assignment.full_pdf_url
        this.fullPdfUrlKey++
        this.completedAllAssignmentQuestions = assignment.completed_all_assignment_questions
        this.fields = [
          {
            key: 'question_number',
            label: 'Question',
            shown: true,
            isRowHeader: true
          },
          {
            key: 'last_question_submission',
            shown: true
          },
          {
            key: 'last_open_ended_submission',
            shown: true
          },
          {
            key: 'page',
            label: 'Initial Page',
            shown: this.fullPdfUrl !== null,
            thStyle: { 'width': '165px' }
          },
          {
            key: 'points',
            label: 'Question Points',
            shown: assignment.show_points_per_question
          },
          {
            key: 'total_score',
            shown: true
          },
          {
            key: 'solution_file_url',
            label: 'Solution',
            shown: true
          }
        ]
      } catch (error) {
        this.$noty.error(error.message)
        this.name = 'Assignment Summary'
      }
    },
    async getSelectedQuestions (assignmentId) {
      try {
        const { data } = await axios.get(`/api/assignments/${assignmentId}/questions/view`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.hasAtLeastOneFileUpload = false
        for (let i = 0; i < data.questions.length; i++) {
          let question = data.questions[i]
          let lastOpenEndedSubmission = 'N/A'
          let lastSubmitted = 'N/A'
          let openEndedSubmissionRequired = false
          let showThumbsUpForOpenEndedSubmission = false
          let questionSubmissionRequired = false
          let showThumbsUpForQuestionSubmission = false
          if (question.open_ended_submission_type !== '0') {
            openEndedSubmissionRequired = true
            lastOpenEndedSubmission = question.date_submitted === 'N/A'
              ? 'Nothing submitted yet.'
              : question.date_submitted
            showThumbsUpForOpenEndedSubmission = question.date_submitted !== 'N/A'
            this.openEndedSubmissionQuestionOptions.push({ value: question.id, text: i + 1 })
          }
          if (question.open_ended_submission_type === 'file') {
            this.hasAtLeastOneFileUpload = true
          }
          if (question.technology_iframe || question.qti_json) {
            questionSubmissionRequired = true
            lastSubmitted = question.last_submitted === 'N/A'
              ? 'Nothing submitted yet.'
              : question.last_submitted
            showThumbsUpForQuestionSubmission = question.last_submitted !== 'N/A'
          }

          let questionInfo = {
            question_id: question.id,
            question_number: i + 1,
            last_question_submission: lastSubmitted,
            questionSubmissionRequired: questionSubmissionRequired,
            showThumbsUpForQuestionSubmission: showThumbsUpForQuestionSubmission,
            openEndedSubmissionRequired: openEndedSubmissionRequired,
            last_open_ended_submission: lastOpenEndedSubmission,
            isOpenEndedFileSubmission: question.open_ended_submission_type === 'file',
            showThumbsUpForOpenEndedSubmission: showThumbsUpForOpenEndedSubmission,
            page: question.submission_file_page ? question.submission_file_page : null,
            submission_file_exists: question.submission_file_exists,
            submission_file_url: question.submission_file_url ? question.submission_file_url : null,
            solution_type: question.solution_type,
            solution_html: question.solution_html,
            solution_file_url: question.solution_file_url ? question.solution_file_url : null,
            solution: question.solution ? question.solution : null,
            points: question.points ? question.points : 'N/A',
            total_score: question.hasOwnProperty('total_score') ? question.total_score : 'N/A',
            solution_file: question.solution_file_url
          }
          this.items.push(questionInfo)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getStudentView (assignmentId) {
      this.$router.push(`/assignments/${assignmentId}/questions/view`)
    },
    metaInfo () {
      return { title: this.$t('home') }
    }
  }
}
</script>
