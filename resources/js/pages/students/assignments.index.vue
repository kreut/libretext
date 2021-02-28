<template>
  <div>
    <b-modal
      id="modal-upload-file"
      ref="modal"
      title="Upload File"
      ok-title="Submit"
      @ok="handleOk"
      @hidden="resetModalForms"
    >
      <b-form ref="form">
        <p>Accepted file types are: {{ getAcceptedFileTypes() }}.</p>
        <b-form-file
          ref="assignmentFileInput"
          v-model="form.assignmentFile"
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
          {{ form.errors.get('assignmentFile') }}
        </div>
      </b-form>
    </b-modal>
    <b-modal
      id="modal-assignment-submission-feedback"
      ref="modal"
      size="xl"
      title="Assignment Submission Feedback"
      ok-only
      ok-variant="primary"
      ok-title="Close"
      @ok="closeAssignmentSubmissionFeedbackModal"
    >
      <b-card title="Summary">
        <b-card-text>
          <p>
            Submitted File:
            <b-button variant="link" style="padding:0px; padding-bottom:3px"
                      @click="downloadSubmissionFile(assignmentFileInfo.assignment_id, assignmentFileInfo.submission, assignmentFileInfo.original_filename)"
            >
              {{ assignmentFileInfo.original_filename }}
            </b-button>
            <br>
            Score: {{ assignmentFileInfo.submission_file_score }}<br>
            Date submitted: {{ assignmentFileInfo.date_submitted }}<br>
            Date graded: {{ assignmentFileInfo.date_graded }}<br>
            Text feedback: {{ assignmentFileInfo.text_feedback }}<br>
          </p>
          <hr>
        </b-card-text>
      </b-card>
      <div v-if="assignmentFileInfo.file_feedback_url">
        <div class="d-flex justify-content-center mt-5">
          <iframe width="600" height="600" :src="this.assignmentFileInfo.file_feedback_url" />
        </div>
      </div>
    </b-modal>
    <PageTitle v-if="canViewAssignments" :title="title" />
    <div class="vld-parent">
      <!--Use loading instead of isLoading because there's both the assignment and scores loading-->
      <loading :active.sync="loading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="hasAssignments && !loading">
        <div class="text-center">
          <div class="text-center">
            <p v-show="letterGradesReleased" class="font-italic font-weight-bold">
              Your current letter grade is
              "{{ letterGrade }}".
            </p>
          </div>
          <p v-show="studentsCanViewWeightedAverage" class="font-italic font-weight-bold">
            Your current weighted average
            is {{ weightedAverage }}.
          </p>
          <p v-if="zScore !== false" class="font-italic font-weight-bold">
            Your current z-score for the course is {{ zScore }}.
            <b-icon id="course-z-score-tooltip"
                    v-b-tooltip.hover
                    class="text-muted"
                    icon="question-circle"
            />
            <b-tooltip target="course-z-score-tooltip" triggers="hover">
              The z-score for the course is computed using all assignments that are for credit, not including extra credit assignments.
              Your overall weighted average is then compared with those of your peers in order to compute your relative standing in the course.
            </b-tooltip>
          </p>
        </div>
        <b-table striped hover :fields="fields" :items="assignments">
          <template v-slot:cell(name)="data">
            <div class="mb-0">
              <div v-show="data.item.is_available">
                <a href="" @click.prevent="getAssignmentSummaryView(data.item)">{{ data.item.name }}</a>
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
          <template v-slot:cell(score)="data">
            <span v-if="data.item.score === 'Not yet released'">Not yet released</span>
            <span v-if="data.item.score !== 'Not yet released'"> {{ data.item.score }}/{{
              data.item.total_points
            }}</span>
          </template>
          <template v-slot:head(z_score)="data">
            Z-Score <span v-b-tooltip="showZScoreTooltip"><b-icon class="text-muted" icon="question-circle" /></span>
          </template>
          <template v-slot:cell(files)="data">
            <div v-if="data.item.submission_files === 'a'">
              <b-icon v-b-modal.modal-uploadmodal-upload-assignment-file-file icon="cloud-upload" class="mr-2"
                      @click="openUploadAssignmentFileModal(data.item.id)"
              />
              <b-icon icon="pencil-square" @click="getAssignmentFileInfo(data.item.id)" />
            </div>
            <div v-else>
              N/A
            </div>
          </template>

          <template v-slot:cell(solution_key)="data">
            <div v-if="data.item.solution_key">
              <b-button variant="outline-primary"
                        @click="downloadSolutionFile('a', data.item.id, null, `${data.item.name}.pdf`)"
              >
                Download
              </b-button>
            </div>
            <div v-else>
              N/A
            </div>
          </template>
        </b-table>
      </div>
      <div v-else>
        <b-alert :show="showNoAssignmentsAlert" variant="warning">
          <a href="#" class="alert-link">This course currently
            has
            no assignments.</a>
        </b-alert>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { downloadFile, downloadSolutionFile } from '~/helpers/DownloadFiles'
import { submitUploadFile, getAcceptedFileTypes } from '~/helpers/UploadFiles'

import { getAssignments } from '../../helpers/Assignments'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  components: {
    Loading
  },
  middleware: 'auth',
  data: () => ({
    showZScoreTooltip: {
      fallbackPlacement: ['right'],
      placement: 'right',
      title: 'The z-score tells you how many standard deviations you are away from the mean.  A z-score of 0 tells you that your score was the exact mean of the distribution.  For bell-shaped data, about 95% of observations will fall within 2 standard deviations of the mean; z-scores outside of this range are considered atypical.'
    },
    loading: true,
    studentsCanViewWeightedAverage: false,
    letterGradesReleased: false,
    zScore: false,
    weightedAverage: '',
    letterGrade: '',
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
      {
        key: 'name',
        sortable: true
      },
      {
        key: 'available_from',
        sortable: true
      },
      {
        key: 'due',
        sortable: true
      },
      {
        key: 'number_submitted',
        label: 'Submitted'
      },
      'score',
      {
        key: 'z_score'
      },
      'files',
      'solution_key'
    ],
    hasAssignments: false,
    showNoAssignmentsAlert: false,
    canViewAssignments: false
  }),
  created () {
    this.downloadSolutionFile = downloadSolutionFile
    this.downloadFile = downloadFile
    this.submitUploadFile = submitUploadFile
    this.getAcceptedFileTypes = getAcceptedFileTypes
  },
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getScoresByUser()
  },
  methods: {
    async getScoresByUser () {
      try {
        const { data } = await axios.get(`/api/scores/${this.courseId}/get-course-scores-by-user`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.loading = false
          return false
        }
        this.canViewAssignments = true
        this.hasAssignments = data.assignments.length > 0
        this.showNoAssignmentsAlert = !this.hasAssignments
        this.assignments = data.assignments

        this.title = `${data.course.name} Assignments`
        this.studentsCanViewWeightedAverage = Boolean(data.course.students_can_view_weighted_average)
        this.letterGradesReleased = Boolean(data.course.letter_grades_released)
        if (this.studentsCanViewWeightedAverage || this.letterGradesReleased) {
          this.weightedAverage = data.weighted_score
          this.letterGrade = data.letter_grade
        }
        this.zScore = data.z_score
      } catch (error) {
        this.$noty.error(error.message)
      }

      this.loading = false
    },
    downloadSubmissionFile (assignmentId, submission, originalFilename) {
      let data =
        {
          'assignment_id': assignmentId,
          'submission': submission
        }
      let url = '/api/submission-files/download'
      this.downloadFile(url, data, originalFilename, this.$noty)
    },
    closeAssignmentSubmissionFeedbackModal () {
      this.$nextTick(() => {
        this.$bvModal.hide('modal-assignment-submission-feedback')
      })
    },
    async getAssignmentFileInfo (assignmentId) {
      try {
        const { data } = await axios.get(`/api/assignment-files/assignment-file-info-by-student/${assignmentId}`)
        this.assignmentFileInfo = data.assignment_file_info
        if (!this.assignmentFileInfo) {
          this.$noty.info('You can\'t have any feedback if you haven\'t submitted a file!')
          return false
        }
        console.log(this.assignmentFileInfo)

        this.$root.$emit('bv::show::modal', 'modal-assignment-submission-feedback')
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
      // get the text comments
      // get the score
      // the the temporary url of the feedback
      // get the download url of your current submission
    },
    async handleOk (bvModalEvt) {
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

    resetModalForms () {
      // alert('reset modal')
    },
    openUploadAssignmentFileModal (assignmentId) {
      console.log(this.assignmentFileInfo)
      return false
      this.form.errors.clear('assignmentFile')
      this.form.assignmentId = assignmentId
    },
    getAssignmentSummaryView (assignment) {
      if (assignment.source === 'x') {
        this.$noty.info('This assignment has no questions to view because it is an external assignment.  Please contact your instructor for more information.')
        return false
      }

      if (assignment.assessment_type === 'clicker' || assignment.instructions || (assignment.show_scores && assignment.students_can_view_assignment_statistics)) {
        this.$router.push(`/students/assignments/${assignment.id}/summary`)
        return false
      }

      this.$router.push(`/assignments/${assignment.id}/questions/view`)
    },
    metaInfo () {
      return { title: this.$t('home') }
    }
  }
}
</script>
