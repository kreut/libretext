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
        id="modal-upload-file"
        ref="modal"
        title="Upload Feedback File"
        ok-title="Submit"
        size="lg"
        @ok="handleOk"
      >
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
        </b-form>
      </b-modal>
      <div v-if="!isLoading">
        <PageTitle :title="title" />
        <div v-if="submissionFiles.length>0">
          <b-container>
            <b-row>
              <p class="font-italic">
                <strong>Instructions:</strong> For each student, please enter a file submission score and optionally
                add comments in the form of text or a file upload.  The total number of points that the student receives
                for this questions will be the sum of the points that they received for submitting any automatically
                graded responses (Question Submission Score)
                plus the number of points that you give them for their file submission (File Submission Score).
              </p>
            </b-row>
          </b-container>
          <b-form-group
            id="assignment_group"
            label-cols-sm="3"
            label-cols-lg="2"
            label="File Submission Group"
            label-for="File Submission Group"
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
            <div v-show="type === 'question'">
              <div class="text-center h5">
                Question
              </div>
              <div class="overflow-auto">
                <b-pagination
                  v-model="currentQuestionPage"
                  :total-rows="submissionFiles.length"
                  :per-page="perPage"
                  align="center"
                  first-number
                  last-number
                  limit="10"
                  @input="changePage(currentQuestionPage)"
                />
              </div>
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
                limit="10"
                @input="changePage()"
              />
            </div>
            <div v-if="type === 'question'" class="text-center">
              <h5 class="font-italic">
                This question is out of
                {{ submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['points']*1 }} points.
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
              </div>
              <span v-if="!submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['solution'] "
                    class="font-italic mt-2"
              >
                You currently have no solution uploaded for this question.
              </span>
            </div>
            <div v-if="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission_url'] !== null">
              <hr>
              <b-container>
                <b-row>
                  <b-col>
                    <b-card header="default" :header-html="getStudentSubmissionTitle()" class="h-100">
                      <b-card-text>
                        <b-form ref="form">
                          <b-alert :show="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['late_file_submission'] !== false" variant="warning">
                            <span class="alert-link">
                              The file submission was late by  {{ submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['late_file_submission'] }}.
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
                            1*submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['question_submission_score'] || 0
                          }}<br>
                          <strong>File Submission Score:</strong> {{
                            1*submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_submission_score'] || 0
                          }}
                          <br>
                          <strong>Total Score For this Question:</strong>
                          {{ (1*submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['question_submission_score'] || 0)
                            + (1*submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_submission_score'] || 0) }} out of {{ submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['points']*1 }}
                          <br>
                          <b-input-group prepend="File Submission Score:" class="mt-3">
                            <b-form-input v-model="scoreForm.score"
                                          type="text"
                                          placeholder="Enter the score"
                                          :class="{ 'is-invalid': scoreForm.errors.has('score') }"
                                          @keydown="scoreForm.errors.clear('score')"
                            />
                            <b-input-group-append>
                              <b-button variant="primary" @click="submitScoreForm">
                                Save Score
                              </b-button>
                            </b-input-group-append>
                            <has-error :form="scoreForm" field="score" />
                          </b-input-group>
                          <hr>
                          <b-container>
                            <b-row>
                              <b-col>
                                <b-button variant="outline-primary"
                                          @click="openInNewTab(submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission_url'] )"
                                >
                                  Open Submission File
                                </b-button>
                              </b-col>
                              <b-col>
                                <b-button :disabled="viewSubmission" @click="toggleView(currentStudentPage)">
                                  View Submission File
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
                        <b-form ref="form">
                          <b-form-textarea
                            id="text_comments"
                            v-model="textFeedbackForm.textFeedback"
                            style="margin-bottom: 23px"
                            placeholder="Enter something..."
                            rows="4"
                            max-rows="4"
                            :class="{ 'is-invalid': textFeedbackForm.errors.has('textFeedback') }"
                            @keydown="textFeedbackForm.errors.clear('textFeedback')"
                          />
                          <has-error :form="textFeedbackForm" field="textFeedback" />
                          <b-container>
                            <b-row align-h="end" class="m-3">
                              <b-button variant="primary" @click="submitTextFeedbackForm">
                                Save Comments
                              </b-button>
                            </b-row>
                            <hr>
                            <b-row>
                              <b-col>
                                <b-button v-b-modal.modal-upload-file
                                          variant="primary"
                                          @click="openUploadFileModal()"
                                >
                                  Upload Feedback File
                                </b-button>
                              </b-col>
                              <b-col>
                                <b-button :disabled="!viewSubmission" @click="toggleView(currentStudentPage)">
                                  View Feedback File
                                </b-button>
                              </b-col>
                            </b-row>
                          </b-container>
                        </b-form>
                      </b-card-text>
                    </b-card>
                  </b-col>
                </b-row>
              </b-container>
              <hr>
            </div>
            <div class="row mt-4 d-flex justify-content-center" style="height:1000px">
              <div v-show="viewSubmission">
                <div
                  v-if="submissionFiles.length>0 && (submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission_url'] !== null)"
                >
                  <iframe width="600" height="600"
                          :src="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission_url']"
                  />
                </div>
                <div v-else>
                  <span class="text-info">{{ submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['name'] }} has not submitted a file.</span>
                </div>
              </div>
              <div v-show="!viewSubmission">
                <div
                  v-if="submissionFiles.length>0 && (submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_feedback_url'] !== null)"
                >
                  <iframe width="600" height="600"
                          :src="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_feedback_url']"
                  />
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

export default {
  middleware: 'auth',
  components: {
    Loading
  },
  data: () => ({
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
    type: '',
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
    textFeedbackForm: new Form({
      textFeedback: ''
    }),
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
    this.getAssignmentNameAndLatePolicy()
    this.type = this.$route.params.typeFiles.replace('-files', '') // question or assignment
    this.getSubmissionFiles(this.gradeView)
  },
  methods: {
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
      window.open(`/assignments/${this.assignmentId}/questions/${questionId}/view`)
    },
    openInNewTab (url) {
      console.log(url)
      window.open(url, '_blank')
    },
    async getAssignmentNameAndLatePolicy () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/get-name`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let assignment = data.assignment
        this.title = `Grade File Submissions For ${assignment.name}`
        this.latePolicy = assignment.late_policy
        this.lateDeductionApplicationPeriod = assignment.late_deduction_application_period
        this.lateDeductionPercent = assignment.late_deduction_percent
      } catch (error) {
        this.title = 'Grade File Submissions'
      }
    },
    async toggleView () {
      this.viewSubmission = !this.viewSubmission
    },
    async submitScoreForm () {
      try {
        this.scoreForm.assignment_id = this.assignmentId
        this.scoreForm.question_id = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['question_id']
        this.scoreForm.type = this.type
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
        formData.append('type', this.type) // extra not really needed but makes it clearer and prevents accidents with null questionId
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
    async submitTextFeedbackForm () {
      try {
        this.textFeedbackForm.assignment_id = this.assignmentId
        this.textFeedbackForm.question_id = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['question_id']
        this.textFeedbackForm.type = this.type
        this.textFeedbackForm.user_id = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['user_id']

        const { data } = await this.textFeedbackForm.post('/api/submission-files/text-feedback')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['text_feedback'] = this.textFeedbackForm.textFeedback
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async changePage () {
      console.log(this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1])
      this.textFeedbackForm.textFeedback = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['text_feedback']
      console.log(this.currentQuestionPage - 1)
      console.log(this.currentStudentPage - 1)

      console.log(this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1])
      await this.getTemporaryUrl('file_feedback', this.currentQuestionPage, this.currentStudentPage)
      await this.getTemporaryUrl('submission', this.currentQuestionPage, this.currentStudentPage)
      this.viewSubmission = true
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
    hasSubmissions (data, type) {
      let hasSubmissions
      switch (type) {
        case ('question'):
          hasSubmissions = (data.user_and_submission_file_info.length > 0)
          break
        case ('assignment'):
          hasSubmissions = (data.user_and_submission_file_info[0].length > 0)
          break
      }
      return hasSubmissions
    },
    setQuestionAndStudent (questionId, studentUserId) {
      console.log(questionId, studentUserId)
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
    async getSubmissionFiles (gradeView) {
      try {
        const { data } = await axios.get(`/api/submission-files/${this.type}/${this.assignmentId}/${gradeView}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false
        }
        this.showNoFileSubmissionsExistAlert = !this.hasSubmissions(data, this.type)
        if (this.showNoFileSubmissionsExistAlert) {
          this.isLoading = false
          return false
        }

        this.submissionFiles = data.user_and_submission_file_info

        this.numStudents = Object.keys(this.submissionFiles[0]).length
        console.log(this.submissionFiles)
        this.currentQuestionPage = 1
        this.currentStudentPage = 1

        // loop through questions, inner loop through students, if match, then set question and student)
        console.log(this.submissionFiles[0])
        if (this.$route.params.questionId && this.$route.params.studentUserId) {
          this.setQuestionAndStudent(this.$route.params.questionId, this.$route.params.studentUserId)
          this.textFeedbackForm.textFeedback = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['text_feedback']
        } else {
          this.textFeedbackForm.textFeedback = this.submissionFiles[0][0]['text_feedback']
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
