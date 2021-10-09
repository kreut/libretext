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
            <a id="course-z-score-tooltip"
               href="#"
            >
              <b-icon class="text-muted" icon="question-circle" />
            </a>
            <b-tooltip target="course-z-score-tooltip"
                       triggers="hover focus"
                       delay="250"
            >
              The z-score for the course is computed using all assignments that are for credit, not including extra
              credit assignments.
              Your overall weighted average is then compared with those of your peers in order to compute your relative
              standing in the course.
            </b-tooltip>
          </p>
        </div>
        <b-container>
          <b-row class="mb-4">
            <b-col lg="3">
              <b-form-select v-if="assignmentGroupOptions.length>1"
                             v-model="chosenAssignmentGroup"
                             title="Filter by assignment group"
                             :options="assignmentGroupOptions"
                             @change="updateAssignmentGroupFilter()"
              />
            </b-col>
          </b-row>
        </b-container>
        <table class="table table-striped">
          <thead>
            <tr>
              <th scope="col">
                Assignment Name
              </th>
              <th scope="col">
                Group
              </th>
              <th scope="col">
                Available From
              </th>
              <th scope="col">
                Due
              </th>
              <th scope="col">
                Submitted
              </th>
              <th scope="col">
                Score
              </th>
              <th scope="col">
                Z-Score
                <a id="z-score-explained"
                   href="#"
                >
                  <b-icon class="text-muted" icon="question-circle" />
                </a>
                <b-tooltip target="z-score-explained"
                           triggers="hover focus"
                           delay="250"
                >
                  The z-score tells you how many standard deviations you are away from the mean.
                  A z-score of 0 tells you that your score was the exact mean of the distribution.
                  For bell-shaped data, about 95% of observations will fall within 2 standard deviations of the mean;
                  z-scores outside of this range are considered atypical.
                </b-tooltip>
              </th>
              <th scope="col">
                Files
              </th>
              <th scope="col">
                Solution Key
              </th>
            </tr>
          </thead>
          <b-tbody v-model="assignments">
            <tr v-for="assignment in assignments"
                v-show="chosenAssignmentGroup === null || assignment.assignment_group === chosenAssignmentGroupText"
                :key="assignment.id"
            >
              <th role="row">
                <div v-show="assignment.is_available">
                  <a href="" @click.prevent="getAssignmentSummaryView(assignment)">{{ assignment.name }}</a>
                </div>
                <div v-show="!assignment.is_available">
                  {{ assignment.name }}
                </div>
              </th>
              <td>
                {{ assignment.assignment_group }}
              </td>
              <td>
                {{ $moment(assignment.available_from, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }} <br>
                {{ $moment(assignment.available_from, 'YYYY-MM-DD HH:mm:ss A').format('h:mm A') }}
              </td>
              <td>
                {{ $moment(assignment.due.due_date, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }} <br>
                {{ $moment(assignment.due.due_date, 'YYYY-MM-DD HH:mm:ss A').format('h:mm A') }}
                {{ assignment.due.is_extension ? '(Extension)' : '' }}
              </td>
              <td>
                {{ assignment.number_submitted }}
              </td>
              <td>
                <span v-if="assignment.score === 'Not yet released'">Not yet released</span>
                <span v-if="assignment.score !== 'Not yet released'"> {{ assignment.score }}/{{
                  assignment.total_points
                }}</span>
              </td>
              <td>
                {{ assignment.z_score }}
              </td>
              <td>
                <div v-if="assignment.submission_files === 'a'">
                  <b-icon v-b-modal.modal-uploadmodal-upload-assignment-file-file icon="cloud-upload" class="mr-2"
                          @click="openUploadAssignmentFileModal(assignment.id)"
                  />
                  <b-icon icon="pencil-square" @click="getAssignmentFileInfo(assignment.id)" />
                </div>
                <div v-else>
                  N/A
                </div>
              </td>

              <td>
                <div v-if="assignment.solution_key">
                  <b-button variant="outline-primary"
                            @click="downloadSolutionFile('a', assignment.id, null, `${assignment.name}.pdf`)"
                  >
                    Download
                  </b-button>
                </div>
                <div v-else>
                  N/A
                </div>
              </td>
            </tr>
          </b-tbody>
        </table>
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

import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { initAssignmentGroupOptions, updateAssignmentGroupFilter } from '~/helpers/Assignments'

export default {
  components: {
    Loading
  },
  middleware: 'auth',
  data: () => ({
    assignmentGroupOptions: [],
    chosenAssignmentGroupText: null,
    chosenAssignmentGroup: null,
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
        key: 'assignment_group',
        label: 'Group'
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
      'z_score',
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
    this.initAssignmentGroupOptions = initAssignmentGroupOptions
    this.updateAssignmentGroupFilter = updateAssignmentGroupFilter
  },
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getScoresByUser()
  },
  methods: {
    showRow (assignment, type) {
      return this.chosenAssignmentGroup === null || assignment.assignment_group === this.chosenAssignmentGroupText
        ? ''
        : 'is-hidden'
    },
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
        this.initAssignmentGroupOptions(this.assignments)

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
      this.$router.push(`/students/assignments/${assignment.id}/summary`)
    },
    metaInfo () {
      return { title: this.$t('home') }
    }
  }
}
</script>
