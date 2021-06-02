<template>
  <div>
    <b-modal
      id="modal-completed-assignment"
      ref="modalThumbsUp"
      hide-footer
      size="sm"
      title="Congratulations!"
    >
      <b-container>
        <b-row>
          <img src="/assets/img/thumbs_up/gif/391906020_THUMBS_UP_400px.gif" alt="Thumbs up" width="275">
        </b-row>
        <b-row><h5>All Question Submissions Successfully Uploaded.</h5></b-row>
      </b-container>
    </b-modal>
    <b-modal
      id="modal-submission-accepted"
      ref="modalThumbsUp"
      hide-footer
      size="lg"
      title="Submission Accepted"
      @hidden="checkIfAssignmentCompleted"
    >
      <font-awesome-icon class="text-success"
                         :icon="checkIcon"
      />

      <span class="font-weight-bold font-italic">
  {{ successMessage }}</span>

    </b-modal>
    <b-modal
      id="modal-thumbs-down"
      ref="modalThumbsUp"
      hide-footer
      size="lg"
      title="Submission Not Accepted"
    >
      <b-alert variant="danger" :show="true">
        <span class="font-italic font-weight-bold" style="font-size: large" v-html="errorMessage"/>
      </b-alert>
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
          <b-card header="default" header-html="<h5>Important Information</h5>">
            <b-card-text>
              <p v-if="instructions.length" class="mb-2">
                <span class="font-weight-bold">Instructions: </span> <span v-html="instructions"/>
              </p>
              <p>
                <span class="font-weight-bold">Due Date: </span>
                <span class="font-italic">This assignment is due {{ formattedDue }}.</span>
              </p>
              <p>
                <span class="font-weight-bold">Late Policy: </span>
                <span class="font-italic"> {{ formattedLatePolicy }}</span>
              </p>
              <p v-if="bothFileUploadMode">
                <span class="font-weight-bold">
                  Open ended submissions:</span>
                <span class="font-italic">
                  Upload a single compiled PDF of the questions and assign pages AND/OR submit individual submissions on each page.
              </span>
              </p>
            </b-card-text>
          </b-card>

          <b-card v-show="compiledPdf" class="mt-3 mb-3" header="default" header-html="<h5>Upload Compiled PDF Submission</h5>">
            <file-upload
              ref="upload"
              v-model="files"
              put-action="/put.method"
              @input-file="inputFile"
              @input-filter="inputFilter"
            >
              Upload your compiled PDF and then set each submission.
              <b-button variant="primary" size="sm" class="mr-3">
                Select file
              </b-button>
            </file-upload>
            <div class="upload mt-3">
              <ul v-if="files.length && (preSignedURL !== '')">
                <li v-for="file in files" :key="file.id">
                  <span :class="file.success ? 'text-success font-italic font-weight-bold' : ''">{{
                      file.name
                    }}</span> -
                  <span>{{ formatFileSize(file.size) }} </span>
                  <span v-if="file.size > 10000000" class="font-italic">Note: large files may take up to a minute to process.</span>
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
                :src="getFullPdfUrlAtPage(fullPdfUrl, questionSubmissionPageForm.page)"
                allowfullscreen
              />
            </div>

          </b-card>
          <b-card class="mt-3 mb-3" header="default" header-html="<h5>Questions</h5>" v-show="items.length">
            <b-alert variant="success" :show="completedAllAssignmentQuestions">
              <span class="font-italic font-weight-bold">You have completed all assessments on this assignment!</span>
            </b-alert>
            <b-table
              v-show="items.length && assessmentType !== 'clicker'"
              striped
              hover
              :no-border-collapse="true"
              :fields="fields"
              :items="items"
            >
              <template #cell(question_number)="data">
                <a href="" @click.stop.prevent="viewQuestion(data.item.question_id)"><span style="font-size:large">&nbsp;{{ data.item.question_number }}&nbsp;</span></a>
              </template>
              <template v-slot:head(last_question_submission)="data">
                Last Auto Graded Submission <span v-b-tooltip="showAutoGradedSubmissionTooltip"><b-icon
                class="text-muted" icon="question-circle"
              /></span>
              </template>
              <template #cell(last_question_submission)="data">
                <span
                  :class="{ 'text-danger': data.item.questionSubmissionRequired && !data.item.showThumbsUpForQuestionSubmission }"
                >
                  {{ data.item.last_question_submission }}
                </span>
                <font-awesome-icon v-show="data.item.showThumbsUpForQuestionSubmission" class="text-success"
                                   :icon="checkIcon"
                />
              </template>

              <template #cell(last_open_ended_submission)="data">
                <span
                  :class="{ 'text-danger': data.item.openEndedSubmissionRequired && !data.item.showThumbsUpForOpenEndedSubmission }"
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
                Last Open Ended Submission <span v-b-tooltip="showOpenEndedSubmissionTooltip"><b-icon
                class="text-muted" icon="question-circle"
              />
              </span>
              </template>
              <template #cell(solution_file_url)="data">
                <span v-html="getSolutionFileLink(data.item)"/>
              </template>
              <template #cell(page)="data">
                <div v-if="data.item.isOpenEndedFileSubmission">
                  <b-input-group>
                    <b-form-input v-model="data.item.page"
                                  type="text"
                                  style="width: 50px"
                                  placeholder=""
                                  :class="{ 'is-invalid': data.item.question_id === questionSubmissionPageForm.questionId && questionSubmissionPageForm.errors.has('page') }"
                                  @keydown="questionSubmissionPageForm.errors.clear('page')"
                    />
                    <b-input-group-append>
                      <b-button variant="primary"
                                size="sm"
                                :disabled="!fullPdfUrl"
                                @click="handleSetPageAsSubmission(data.item.question_number, data.item.question_id, data.item.page)"
                      >
                        Set Page
                      </b-button>
                    </b-input-group-append>

                    <has-error v-show="data.item.question_id === questionSubmissionPageForm.questionId"
                               :form="questionSubmissionPageForm" field="page"
                    />
                  </b-input-group>
                </div>
                <div v-else>
                  N/A
                </div>
              </template>
            </b-table>
          </b-card>

          <b-card v-if="canViewAssignmentStatistics" class="mb-5" header="default" header-html="<h5>Statistics</h5>">
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

const VueUploadComponent = require('vue-upload-component')
Vue.component('file-upload', VueUploadComponent)

export default {
  components: {
    AssignmentStatistics,
    Loading,
    FontAwesomeIcon
  },
  middleware: 'auth',
  data: () => ({
    bothFileUploadMode: false,
    compiledPdf: false,
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
    showAutoGradedSubmissionTooltip: {
      fallbackPlacement: ['right'],
      placement: 'right',
      title: 'Auto graded questions are questions which can be graded automatically by Adapt.  Some examples are multiple choice, true false, numeric based and matching.'
    },
    showOpenEndedSubmissionTooltip: {
      fallbackPlacement: ['right'],
      placement: 'right',
      title: 'Open ended questions are questions which will require a grader.  Some examples are file uploads, text based responses, and audio uploads.'
    },
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
  computed: mapGetters({
    user: 'auth/user'
  }),
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
  },
  methods: {
    checkIfAssignmentCompleted () {
      if (this.completedAllAssignmentQuestions) {
        this.$bvModal.show('modal-completed-assignment')
      }
    },
    async handleSetPageAsSubmission (questionNumber, questionId, page) {
      this.questionSubmissionPageForm.questionId = questionId
      this.questionSubmissionPageForm.page = page
      this.questionSubmissionPageForm.question_number = questionNumber
      try {
        const { data } = await this.questionSubmissionPageForm.patch(`/api/submission-files/${this.assignmentId}/${questionId}/page`)
        if (data.type === 'error') {
          this.errorMessage = data.message
          this.$bvModal.show('modal-thumbs-down')
          return false
        }
        let openEndedSubmission
        openEndedSubmission = this.items.find(question => question.question_id === questionId)
        openEndedSubmission.submission_file_exists = true
        openEndedSubmission.submission_file_url = data.submission_file_url
        openEndedSubmission.last_open_ended_submission = data.date_submitted
        openEndedSubmission.showThumbsUpForOpenEndedSubmission = true
        this.successMessage = data.message
        this.completedAllAssignmentQuestions = data.completed_all_assignment_questions
        this.$bvModal.show('modal-submission-accepted')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.errorMessage = error.message
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
      this.submissionFileForm.errors.clear('submission')
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
        this.submissionFileForm.errors.clear('submission')
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
        if (data.type === 'error') {
          this.submissionFileForm.errors.set('submission', data.message)
        } else {
          this.fullPdfUrl = data.full_pdf_url
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
          return prevent()
        }
        let validUploadTypesMessage = `The file type must be .pdf`

        let validExtension = /\.(pdf)$/i.test(newFile.name)

        if (!validExtension) {
          this.submissionFileForm.errors.set('submission', validUploadTypesMessage)

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
    getSolutionFileLink (question) {
      return question.solution_file_url
        ? `<a href="${question.solution_file_url}" target="_blank">Solution ${question.question_number}</a>`
        : 'N/A'
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
        this.instructions = assignment.instructions
        this.formattedLatePolicy = assignment.formatted_late_policy
        this.formattedDue = assignment.formatted_due
        this.compiledPdf = assignment.file_upload_mode === 'compiled_pdf' || assignment.file_upload_mode === 'both'
        this.bothFileUploadMode = assignment.file_upload_mode === 'both'
        this.assessmentType = assignment.assessment_type
        this.name = assignment.name
        this.pastDue = assignment.past_due
        this.canViewAssignmentStatistics = assignment.can_view_assignment_statistics
        this.fullPdfUrl = assignment.full_pdf_url
        this.fullPdfUrlKey++
        this.completedAllAssignmentQuestions = assignment.completed_all_assignment_questions
        this.fields = [
          {
            key: 'question_number',
            label: 'Number'
          },
          'last_question_submission',
          'last_open_ended_submission']
        if (assignment.file_upload_mode === 'compiled_pdf' || assignment.file_upload_mode === 'both') {
          this.fields.push({ key: 'page', label: 'Initial Page'})
        }
        if (assignment.show_points_per_question) {
          this.fields.push({
            key: 'points',
            label: 'Question Points'
          })
        }
        this.fields.push('total_score', {
          key: 'solution_file_url',
          label: 'Solution File'
        })
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

          if (question.technology_iframe) {
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
            submission_file_url: question.submission_file_url ? question.submission_file_url : null,
            solution_file_url: question.solution_file_url ? question.solution_file_url : null,
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
