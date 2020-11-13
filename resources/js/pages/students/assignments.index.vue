<template>
  <div>
    <PageTitle v-if="canViewAssignments" :title="title"></PageTitle>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"></loading>
      <b-modal
        id="modal-upload-file"
        ref="modal"
        title="Upload File"
        @ok="handleOk"
        @hidden="resetModalForms"
        ok-title="Submit"

      >
        <b-form ref="form">
          <p>Accepted file types are: {{ getAcceptedFileTypes() }}.</p>
          <b-form-file
            ref="assignmentFileInput"
            v-model="form.assignmentFile"
            placeholder="Choose a file or drop it here..."
            drop-placeholder="Drop file here..."
            :accept="getAcceptedFileTypes()"
          ></b-form-file>
          <div v-if="uploading">
            <b-spinner small type="grow"></b-spinner>
            Uploading file...
          </div>
          <input type="hidden" class="form-control is-invalid">
          <div class="help-block invalid-feedback">{{ form.errors.get('assignmentFile') }}
          </div>

        </b-form>
      </b-modal>
      <b-modal
        id="modal-assignment-submission-feedback"
        ref="modal"
        size="xl"
        title="Assignment Submission Feedback"
        @ok="closeAssignmentSubmissionFeedbackModal"
        ok-only
        ok-variant="primary"
        ok-title="Close"

      >
        <b-card title="Summary">
          <b-card-text>
            <p>
              Submitted File:
              <b-button variant="link" style="padding:0px; padding-bottom:3px"
                        v-on:click="downloadSubmissionFile(assignmentFileInfo.assignment_id, assignmentFileInfo.submission, assignmentFileInfo.original_filename)">
                {{ this.assignmentFileInfo.original_filename }}
              </b-button>
              <br>
              Score: {{ this.assignmentFileInfo.submission_file_score }}<br>
              Date submitted: {{ this.assignmentFileInfo.date_submitted }}<br>
              Date graded: {{ this.assignmentFileInfo.date_graded }}<br>
              Text feedback: {{ this.assignmentFileInfo.text_feedback }}<br>
            <hr>

          </b-card-text>
        </b-card>
        <div v-if="assignmentFileInfo.file_feedback_url">
          <div class="d-flex justify-content-center mt-5">
            <iframe width="600" height="600" :src="this.assignmentFileInfo.file_feedback_url"></iframe>
          </div>
        </div>
      </b-modal>

      <div v-if="hasAssignments">
        <div class="text-center">
        <p v-if="studentsCanViewWeightedAverage" class="font-italic font-weight-bold">Your current weighted average is {{ weightedAverage }}
        </p>
        </div>
        <b-table striped hover :fields="fields" :items="assignments">
          <template v-slot:cell(name)="data">
            <div class="mb-0">
              <div v-show="data.item.is_available">
                <a href="" v-on:click.prevent="getAssignmentSummaryView(data.item)">{{ data.item.name }}</a>
              </div>
              <div v-show="!data.item.is_available">
                {{ data.item.name }}
              </div>
            </div>
          </template>
          <template v-slot:cell(available_from)="data">
            {{ $moment(data.item.available_from, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') }}
          </template>
          <template v-slot:cell(due)="data">
            {{ $moment(data.item.due.due_date, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') }}
            {{ data.item.due.is_extension ? '(Extension)' : '' }}
          </template>

          <template v-slot:cell(files)="data">
            <div v-if="data.item.submission_files === 'a'">
              <b-icon icon="cloud-upload" class="mr-2" v-on:click="openUploadAssignmentFileModal(data.item.id)"
                      v-b-modal.modal-uploadmodal-upload-assignment-file-file></b-icon>
              <b-icon icon="pencil-square" v-on:click="getAssignmentFileInfo(data.item.id)"
              ></b-icon>
            </div>
            <div v-else>
              N/A
            </div>
          </template>

          <template v-slot:cell(solution_key)="data">
            <div v-if="data.item.solution_key">
              <b-button variant="outline-primary"
                        v-on:click="downloadSolutionFile('a', data.item.id, null, `${data.item.name}.pdf`)">Download
              </b-button>
            </div>
            <div v-else>
              N/A
            </div>
          </template>

        </b-table>
      </div>
      <div v-else>
        <br>
        <div class="mt-4">
          <b-alert :show="showNoAssignmentsAlert" variant="warning"><a href="#" class="alert-link">This course currently
            has
            no assignments.</a></b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from "vform"
import {downloadFile} from '~/helpers/DownloadFiles'
import {submitUploadFile} from '~/helpers/UploadFiles'
import {getAcceptedFileTypes} from '~/helpers/UploadFiles'
import {downloadSolutionFile} from '~/helpers/DownloadFiles'
import {getAssignments} from "../../helpers/Assignments"
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  components: {
    Loading
  },
  middleware: 'auth',
  data: () => ({
    isLoading: true,
    studentsCanViewWeightedAverage: false,
    weightedAverage: '',
    title: '',
    form: new Form({
      assignmentFile: null,
      assignmentId: null
    }),
    assignmentFileInfo: {},
    uploading: false,
    assignments: [],
    courseId: false,
    fields: [
      'name',
      'available_from',
      'due',
      'number_submitted',
      'score',
      'files',
      'solution_key'
    ],
    hasAssignments: false,
    showNoAssignmentsAlert: false,
    canViewAssignments: false
  }),
  created() {
    this.downloadSolutionFile = downloadSolutionFile
    this.downloadFile = downloadFile
    this.submitUploadFile = submitUploadFile
    this.getAcceptedFileTypes = getAcceptedFileTypes
    this.getAssignments = getAssignments


  },
  mounted() {
    this.courseId = this.$route.params.courseId
    this.getCourseInfo()
    this.getAssignments()
  },
  methods: {
    async getCourseInfo() {
      try {
        const {data} = await axios.get(`/api/courses/${this.courseId}`)
        this.title = `${data.course.name} Assignments`
        console.log(data)
        this.studentsCanViewWeightedAverage = Boolean(data.course.students_can_view_weighted_average)
        if (this.studentsCanViewWeightedAverage) {
          await this.getWeightedAverage()
        }
      } catch (error) {
        this.$noty.error(error.message)

      }
    },
    async getWeightedAverage() {
      try {
        const {data} = await axios.get(`/api/scores/${this.courseId}/get-scores-by-user`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.weightedAverage = data.weighted_score
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    downloadSubmissionFile(assignmentId, submission, original_filename) {
      let data =
        {
          'assignment_id': assignmentId,
          'submission': submission
        }
      let url = '/api/submission-files/download'
      this.downloadFile(url, data, original_filename, this.$noty)
    },
    closeAssignmentSubmissionFeedbackModal() {
      this.$nextTick(() => {
        this.$bvModal.hide('modal-assignment-submission-feedback')
      })
    },
    async getAssignmentFileInfo(assignmentId) {
      try {
        const {data} = await axios.get(`/api/assignment-files/assignment-file-info-by-student/${assignmentId}`)
        this.assignmentFileInfo = data.assignment_file_info
        if (!this.assignmentFileInfo) {
          this.$noty.info("You can't have any feedback if you haven't submitted a file!")
          return false
        }
        console.log(this.assignmentFileInfo)

        this.$root.$emit('bv::show::modal', 'modal-assignment-submission-feedback');
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.$nextTick(() => {
            this.$bvModal.hide('modal-assignment-submission-feedback')
          })
          return false
        }
      } catch (error) {
        if (error.message.includes('status code 413')) {
          error.message = 'The maximum size allowed is 10MB.'
        }
        this.$noty.error(error.message)

      }
      //get the text comments
      //get the score
      //the the temporary url of the feedback
      //get the download url of your current submission


    },
    async handleOk(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      if (this.uploading) {
        this.$noty.info('Please be patient while the file is uploading.')
        return false
      }
      this.uploading = true
      try {
        await this.submitUploadFile('assignment', this.form, this.$noty, this.$nextTick, this.$bvModal, '/api/submission-files')
      } catch (error) {
      }
      this.uploading = false
    },

    resetModalForms() {
      // alert('reset modal')
    },
    openUploadAssignmentFileModal(assignmentId) {
      console.log(this.assignmentFileInfo)
      return false
      console.log(assignment)
      return false
      this.form.errors.clear('assignmentFile')
      this.form.assignmentId = assignmentId
    },
    getAssignmentSummaryView(assignment) {
      if (assignment.source === 'x') {
        this.$noty.info("This assignment has no questions to view because it is an external assignment.  Please contact your instructor for more information.")
        return false
      }


      if (assignment.show_scores && assignment.students_can_view_assignment_statistics) {
        this.$router.push(`/assignments/${assignment.id}/summary`)
        return false
      }

      this.$router.push(`/assignments/${assignment.id}/questions/view`)

    },
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
}
</script>
