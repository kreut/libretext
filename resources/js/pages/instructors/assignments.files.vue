<template>
  <div>
    <PageTitle v-bind:title="this.title"></PageTitle>
    <div v-if="submissionFiles.length>0">

      <b-card class="col-4">
        <b-form-group id="grade-view" label="Current Grade View:" label-for="grade-view">
          <b-form-select
            id="grade-view"
            v-model="gradeView"
            v-on:change="getSubmissionFiles"
            :options="gradeViews"
          ></b-form-select>
        </b-form-group>
      </b-card>
      <div v-if="!showNoFileSubmissionsExistAlert">
        <div v-show="type === 'question'">
          <div class="text-center h5">Question</div>
          <div class="overflow-auto">
            <b-pagination
              v-on:input="changePage(currentQuestionPage)"
              v-model="currentQuestionPage"
              :total-rows="submissionFiles.length"
              :per-page="perPage"
              align="center"
              first-number
              last-number
            ></b-pagination>
          </div>
        </div>
        <div class="text-center h5">Student Submission</div>
        <div class="overflow-auto">
          <b-pagination
            v-on:input="changePage()"
            v-model="currentStudentPage"
            :total-rows="numStudents"
            :per-page="perPage"
            align="center"
            first-number
            last-number
          ></b-pagination>
        </div>
        <div v-if="type === 'question'" class="text-center">
          <h5 class="font-italic">This question is out of
            {{ submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['points'] }} points.</h5>
        </div>
        <div v-if="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission_url'] !== null">
          <div class="container">
            <div class="row">
              <div class="col-sm">
                <b-card title="Submission Information">
                  <b-card-text>
                    <p>
                      <strong>Name:</strong>
                      {{ this.submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['name'] }}<br>
                      <strong>Date Submitted:</strong> {{
                        this.submissionFiles[currentQuestionPage - 1][currentStudentPage -
                        1]['date_submitted']
                      }}<br>
                      <strong>Date Graded:</strong> {{
                        this.submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['date_graded']
                      }}<br>
                      <strong>Question Submission Score:</strong> {{
                        this.submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['question_submission_score']
                      }}<br>
                      <strong>File Submission Score:</strong> {{
                        this.submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_submission_score']
                      }}
                    </p>
                    <hr>
                    <b-form-group
                      id="fieldset-horizontal"
                      label-cols-sm="6"
                      label-cols-lg="5"
                      label="File Submission Score:"
                      label-for="input-horizontal"
                    >
                      <b-form-input id="input-horizontal"
                                    v-model="scoreForm.score"
                                    type="text"
                                    placeholder="Enter the score"
                                    :class="{ 'is-invalid': scoreForm.errors.has('score') }"
                                    @keydown="scoreForm.errors.clear('score')"
                      ></b-form-input>
                      <has-error :form="scoreForm" field="score"></has-error>
                      <b-button class="ml-3 mt-2 float-right" variant="primary" v-on:click="submitScoreForm">Submit
                        Score
                      </b-button>
                    </b-form-group>
                  </b-card-text>
                </b-card>

              </div>

              <div class="col-sm">
                <b-card title="Optional Text Comments">
                  <b-card-text>
                    <b-form ref="form">
                      <b-form-textarea
                        id="text_comments"
                        v-model="textFeedbackForm.textFeedback"
                        placeholder="Enter something..."
                        rows="6"
                        max-rows="6"
                        :class="{ 'is-invalid': textFeedbackForm.errors.has('textFeedback') }"
                        @keydown="textFeedbackForm.errors.clear('textFeedback')"
                      >

                      </b-form-textarea>
                      <has-error :form="textFeedbackForm" field="textFeedback"></has-error>
                      <div class="row mt-4 float-right">
                        <b-button variant="primary" v-on:click="submitTextFeedbackForm">Save Comments</b-button>
                      </div>
                    </b-form>
                  </b-card-text>
                </b-card>
              </div>
            </div>
          </div>
          <hr>
          <div class="container">
            <div class="row">
              <div class="col-sm">
                <b-button variant="outline-primary"
                          v-on:click="downloadSubmission(assignmentId, submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission'], submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['original_filename'])">
                  Download Submission
                </b-button>

                <toggle-button class="float-right"
                               @change="toggleView(currentStudentPage)"
                               :width="180"
                               :value="viewSubmission"
                               :font-size="14"
                               :margin="4"
                               :sync="true"
                               :color="{checked: '#007BFF', unchecked: '#75C791'}"
                               :labels="{unchecked: 'View Submission', checked: 'View File Feedback'}"/>

              </div>
              <b-form ref="form">
                <div class="col-sm">
                  <b-input-group>
                    <b-form-file
                      ref="fileFeedbackInput"
                      v-model="fileFeedbackForm.fileFeedback"
                      placeholder="Choose a file or drop it here..."
                      drop-placeholder="Drop file here..."
                      :accept="getAcceptedFileTypes()"
                    ></b-form-file>
                    <span class="ml-3">
                <b-button variant="primary" v-on:click="uploadFileFeedback()">Upload Feedback</b-button>
                </span>
                  </b-input-group>
                  <div v-if="uploading">
                    <b-spinner small type="grow"></b-spinner>
                    Uploading file...
                  </div>
                  <input type="hidden" class="form-control is-invalid">
                  <div class="help-block invalid-feedback">{{ fileFeedbackForm.errors.get('fileFeedback') }}
                  </div>


                </div>

              </b-form>
            </div>
          </div>
        </div>
        <div class="row mt-4 d-flex justify-content-center" style="height:1000px">
          <div v-show="viewSubmission">
            <div
              v-if="submissionFiles.length>0 && (submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission_url'] !== null)">
              <iframe width="600" height="600"
                      :src="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['submission_url']"></iframe>
            </div>
            <div v-else>
              <span class="text-info">{{ submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['name'] }} has not submitted a file.</span>
            </div>
          </div>
          <div v-show="!viewSubmission">
            <div
              v-if="submissionFiles.length>0 && (submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_feedback_url'] !== null)">
              <iframe width="600" height="600"
                      :src="submissionFiles[currentQuestionPage - 1][currentStudentPage - 1]['file_feedback_url']"></iframe>
            </div>
            <div v-else>
              <span class="text-info">You have not uploaded a feedback file.</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-4" v-if="showNoFileSubmissionsExistAlert">
      <b-alert show variant="warning"><a href="#" class="alert-link">
        There are no submissions for this view.</a></b-alert>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from "vform"
import {ToggleButton} from 'vue-js-toggle-button'
import {downloadFile} from '~/helpers/DownloadFiles'
import {getAcceptedFileTypes} from '~/helpers/UploadFiles'
//import pdf from 'vue-pdf'


export default {
  components: {
    ToggleButton
  },
  middleware: 'auth',
  data: () => ({
    gradeViews: [
      {text: 'All Students', value: 'allStudents'},
      {text: 'Ungraded Submissions', value: 'ungradedSubmissions'},
      {text: 'Graded Submissions', value: 'gradedSubmissions'}
    ],
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
    }),
  }),
  created() {
    this.downloadFile = downloadFile
    this.getAcceptedFileTypes = getAcceptedFileTypes
  },
  mounted() {
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentInfo()
    this.type = this.$route.params.typeFiles.replace('-files', '') //question or assignment
    this.getSubmissionFiles(this.gradeView)
  },
  methods: {
    downloadSubmission(assignmentId, submission, original_filename) {
      let data =
        {
          'assignment_id': assignmentId,
          'submission': submission
        }
      let url = '/api/submission-files/download'
      this.downloadFile(url, data, original_filename, this.$noty)
    },
    async getAssignmentInfo() {
      try {
        const {data} = await axios.get(`/api/assignments/${this.assignmentId}`)

        this.title = `Grade File Submissions For "${data.name}"`

      } catch (error) {
        this.title = 'Grade File Submissions'
      }
    },
    async toggleView() {
      this.viewSubmission = !this.viewSubmission
    },
    async submitScoreForm() {
      try {

        this.scoreForm.assignment_id = this.assignmentId
        this.scoreForm.question_id = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['question_id']
        this.scoreForm.type = this.type
        this.scoreForm.user_id = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['user_id']

        const {data} = await this.scoreForm.post('/api/submission-files/score')
        this.$noty[data.type](data.message)
        console.log(data)
        if (data.type === 'success') {
          this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['file_submission_score'] = this.scoreForm.score
          this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['date_graded'] = data.date_graded
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }


    },
    async uploadFileFeedback() {
      try {
        if (this.uploading) {
          this.$noty.info('Please be patient while the file is uploading.')
          return false
        }
        this.fileFeedbackForm.errors.set('fileFeedback', null)
        this.uploading = true
        //https://stackoverflow.com/questions/49328956/file-upload-with-vue-and-laravel
        let formData = new FormData()
        formData.append('type', this.type) //extra not really needed but makes it clearer and prevents accidents with null questionId
        formData.append('fileFeedback', this.fileFeedbackForm.fileFeedback)
        formData.append('assignmentId', this.assignmentId)
        formData.append('questionId', this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['question_id'])
        formData.append('userId', this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['user_id'])
        formData.append('_method', 'put') // add this
        const {data} = await axios.post('/api/submission-files/file-feedback', formData)
        if (data.type === 'error') {
          this.fileFeedbackForm.errors.set('fileFeedback', data.message)
        } else {
          this.$noty.success(data.message)
          this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['file_feedback_url'] = data.file_feedback_url
        }
      } catch (error) {
        if (error.message.includes('status code 413')) {
          error.message = 'The maximum size allowed is 10MB.'
        }
        this.$noty.error(error.message)

      }
      this.uploading = false
      this.$refs['fileFeedbackInput'].reset()

    },
    async submitTextFeedbackForm() {
      try {

        this.textFeedbackForm.assignment_id = this.assignmentId
        this.textFeedbackForm.question_id = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['question_id']
        this.textFeedbackForm.type = this.type
        this.textFeedbackForm.user_id = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['user_id']

        const {data} = await this.textFeedbackForm.post('/api/submission-files/text-feedback')
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
    async changePage() {

      console.log(this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1])
      this.textFeedbackForm.textFeedback = this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1]['text_feedback']
      console.log(this.currentQuestionPage - 1)
      console.log(this.currentStudentPage - 1)

      console.log(this.submissionFiles[this.currentQuestionPage - 1][this.currentStudentPage - 1])
      await this.getTemporaryUrl('file_feedback', this.currentQuestionPage, this.currentStudentPage)
      await this.getTemporaryUrl('submission', this.currentQuestionPage, this.currentStudentPage)
      this.viewSubmission = true

    },
    async getTemporaryUrl(file, currentQuestionPage, currentStudentPage) {
      if (this.submissionFiles[currentQuestionPage - 1][currentStudentPage - 1][file] && !this.submissionFiles[currentQuestionPage - 1][currentStudentPage - 1][`${file}_url`]) {
        try {
          const {data} = await axios.post('/api/submission-files/get-temporary-url-from-request',
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
    submissionUrlExists(currentStudentPage) {
      return (this.submissionFiles[currentStudentPage - 1]['submission_url'] !== null)
    },
    hasSubmissions(data, type) {
      let hasSubmissions
      console.error(data.user_and_submission_file_info)
      switch (type) {
        case('question'):
          hasSubmissions = (data.user_and_submission_file_info.length > 0)
          break;
        case ('assignment'):
          hasSubmissions = (data.user_and_submission_file_info[0].length > 0)
          break;
      }
      return hasSubmissions
    },
    async getSubmissionFiles(gradeView) {
      try {
        const {data} = await axios.get(`/api/submission-files/${this.type}/${this.assignmentId}/${gradeView}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.showNoFileSubmissionsExistAlert = !this.hasSubmissions(data, this.type)
        if (this.showNoFileSubmissionsExistAlert) {
          return false
        }

        this.submissionFiles = data.user_and_submission_file_info

        this.numStudents = Object.keys(this.submissionFiles[0]).length;
        console.log(this.submissionFiles)
        this.currentQuestionPage = 1
        this.currentStudentPage = 1
        this.textFeedbackForm.textFeedback = this.submissionFiles[0]['text_feedback']
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
