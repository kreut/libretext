<template>
  <div>
    <b-modal
      id="modal-progress-report"
      title="Progress Report"
      hide-footer
    >
      <p v-show="!atLeastOneAssignmentNotIncludedInWeightedAverage">
        The progress report only includes scores for assignments in which the scores are already released.
      </p>
      <p v-show="atLeastOneAssignmentNotIncludedInWeightedAverage">
        The progress report only includes released scores for those assignments which are included in your final
        average.
      </p>
      <b-table
        v-show="scoreInfoByAssignmentGroup.length"
        aria-label="Progress Report"
        striped
        hover
        :no-border-collapse="true"
        :items="scoreInfoByAssignmentGroup"
        :fields="scoreInfoByAssignmentGroupFields"
      />
    </b-modal>
    <b-modal
      id="modal-z-score"
      title="Explanation of the Z-Score"
      hide-footer
    >
      <p>
        The z-score tells you how many standard deviations you are away from the mean.
        A z-score of 0 tells you that your score was the exact mean of the distribution.
      </p>
      <p>
        For bell-shaped data, about 95% of observations will fall within 2 standard deviations of the mean;
        z-scores outside of this range are considered atypical.
      </p>
    </b-modal>
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
          <b-spinner small type="grow"/>
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
          <iframe width="600" height="600" :src="this.assignmentFileInfo.file_feedback_url"/>
        </div>
      </div>
    </b-modal>
    <PageTitle v-if="canViewAssignments" :title="title"/>
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
        <div v-if="isInLmsCourse && !user.is_instructor_logged_in_as_student">
          <b-alert show variant="info">
            All assignments are served through your LMS such as Canvas, Blackboard, or Moodle. Please log in to your LMS
            to access your assignments.
          </b-alert>
        </div>
        <div v-if="!isInLmsCourse || user.is_instructor_logged_in_as_student">
          <div class="text-center">
            <div class="text-center">
              <p v-show="letterGradesReleased" class="font-weight-bold">
                Your current letter grade is
                "{{ letterGrade }}".
              </p>
            </div>
            <p v-show="studentsCanViewWeightedAverage" class="font-weight-bold">
              Your current weighted average
              is {{ weightedAverage }}.
            </p>
            <p v-if="zScore !== false" class="font-weight-bold">
              Your current z-score for the course is {{ zScore }}.
              <a id="course-z-score-tooltip"
                 href="#"
              >
                <b-icon class="text-muted" icon="question-circle" aria-label="Explanation of z-score"/>
              </a>
              <b-tooltip target="course-z-score-tooltip"
                         triggers="hover focus"
                         delay="250"
              >
                The z-score for the course is computed using all assignments that are for credit, not including extra
                credit assignments.
                Your overall weighted average is then compared with those of your peers in order to compute your
                relative
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
                               @change="updateAssignmentGroupFilter();getAssignmentsWithinChosenAssignmentGroup()"
                />
              </b-col>
              <b-col class="pt-1">
                <b-button v-show="scoreInfoByAssignmentGroup.length && showProgressReport"
                          variant="primary"
                          size="sm"
                          @click="$bvModal.show('modal-progress-report')"
                >
                  Progress Report
                </b-button>
              </b-col>
            </b-row>
          </b-container>
          <p v-show="atLeastOneAssignmentNotIncludedInWeightedAverage">
            Submissions for assignments marked with an asterisk (<span class="text-danger">*</span>) will not be
            included
            in your final weighted average.
          </p>
          <b-table
            v-show="assignments.length"
            id="summary_of_questions_and_submissions"
            aria-label="Assignments"
            striped
            hover
            :no-border-collapse="true"
            :items="assignments"
            :fields="assignmentFields"
          >
            <template v-slot:head(z_score)="data">
              Z-Score
              <QuestionCircleTooltipModal :aria-label="'z-score-explained'" :modal-id="'modal-z-score'"/>
            </template>

            <template #cell(name)="data">
              <span v-show="data.item.is_available">
                <a href="" @click.prevent="getAssignmentSummaryView(data.item)">{{ data.item.name }}</a>
              </span>
              <span v-show="!data.item.is_available">
                {{ data.item.name }}
              </span>
              <span v-show="!data.item.include_in_weighted_average"
                    :id="`not-included-tooltip-${data.item.id}`" class="text-danger"
              >*</span>
              <b-tooltip :target="`not-included-tooltip-${data.item.id}`"
                         delay="250"
                         triggers="hover focus"
              >
                {{ data.item.name }} will not be included when computing your final weighted average.
              </b-tooltip>
            </template>
            <template #cell(available_from)="data">
              <span v-show="data.item.assessment_type !== 'clicker'">
                {{ $moment(data.item.available_from, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}
                {{ $moment(data.item.available_from, 'YYYY-MM-DD HH:mm:ss A').format('h:mm A') }}
              </span>
              <span v-show="data.item.assessment_type === 'clicker'">
                N/A</span>
            </template>
            <template #cell(due)="data">
              <span v-show="data.item.assessment_type !== 'clicker'">
                {{ $moment(data.item.due.due_date, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}
                {{ $moment(data.item.due.due_date, 'YYYY-MM-DD HH:mm:ss A').format('h:mm A') }}
                {{ data.item.due.is_extension ? '(Extension)' : '' }} <span v-show="data.item.due.late"
                                                                            v-b-tooltip.hover
                                                                            class="text-warning"
                                                                            :title="`Submissions for ${data.item.name} will be considered late.  Enter the assignment for details.`"
              >*</span>
              </span>
              <span v-show="data.item.assessment_type === 'clicker'">
                N/A
              </span>
            </template>
            <template #cell(score)="data">
              <span v-if="data.item.score === 'Not yet released'">Not yet released</span>
              <span v-if="data.item.score !== 'Not yet released'"> {{ data.item.score }}/{{
                  data.item.total_points
                }}</span>
            </template>
          </b-table>
        </div>
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
import QuestionCircleTooltipModal from '~/components/QuestionCircleTooltipModal'
import { mapGetters } from 'vuex'
import { initCentrifuge } from '~/helpers/Centrifuge'

export default {
  components: {
    Loading,
    QuestionCircleTooltipModal
  },
  metaInfo () {
    return { title: 'My Assignments' }
  },
  data: () => ({
    centrifugo: {},
    isInLmsCourse: false,
    showProgressReport: false,
    atLeastOneAssignmentNotIncludedInWeightedAverage: false,
    scoreInfoByAssignmentGroup: [],
    scoreInfoByAssignmentGroupFields: [
      {
        key: 'assignment_group',
        isRowHeader: true
      },
      {
        key: 'sum_of_scores',
        label: 'Points Received',
        tdClass: 'text-center',
        thClass: 'text-center'
      },
      {
        key: 'total_points',
        label: 'Points Possible',
        tdClass: 'text-center',
        thClass: 'text-center'
      },
      {
        key: 'percent',
        tdClass: 'text-center',
        thClass: 'text-center'
      }
    ],
    assignmentFields: [
      {
        key: 'name',
        label: 'Assignment Name',
        sortable: true,
        isRowHeader: true
      },
      'assignment_group',
      {
        key: 'available_from',
        sortable: true,
        thStyle: { width: '230px' }
      },
      {
        key: 'due',
        sortable: true,
        thStyle: { width: '230px' }
      },
      {
        key: 'number_submitted',
        label: 'Submitted'
      },
      'score',
      'z_score'
    ],
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
    hasAssignments: false,
    showNoAssignmentsAlert: false,
    canViewAssignments: false,
    originalAssignments: []
  }),
  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    this.downloadSolutionFile = downloadSolutionFile
    this.downloadFile = downloadFile
    this.submitUploadFile = submitUploadFile
    this.getAcceptedFileTypes = getAcceptedFileTypes
    this.initAssignmentGroupOptions = initAssignmentGroupOptions
    this.updateAssignmentGroupFilter = updateAssignmentGroupFilter
  },
  beforeDestroy () {
    try {
      if (this.centrifuge) {
        this.centrifuge.disconnect()
      }
    } catch (error) {
      // won't be a function for all the other ones that haven't been defined on the page
    }
  },
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getScoresByUser()
  },
  methods: {
    getAssignmentsWithinChosenAssignmentGroup () {
      this.assignments = this.chosenAssignmentGroup === null
        ? this.originalAssignments
        : this.originalAssignments.filter(assignment => assignment.assignment_group === this.chosenAssignmentGroupText)
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
        this.scoreInfoByAssignmentGroup = data.score_info_by_assignment_group
        this.canViewAssignments = true
        this.hasAssignments = data.assignments.length > 0
        this.isInLmsCourse = this.hasAssignments && data.assignments[0].is_in_lms_course
        this.showNoAssignmentsAlert = !this.hasAssignments
        this.assignments = data.assignments
        for (let i = 0; i < this.assignments.length; i++) {
          if (!this.assignments[i].include_in_weighted_average) {
            this.atLeastOneAssignmentNotIncludedInWeightedAverage = true
          }
        }
        this.originalAssignments = this.assignments
        this.initAssignmentGroupOptions(this.assignments)

        this.title = `${data.course.name} Assignments`
        this.studentsCanViewWeightedAverage = Boolean(data.course.students_can_view_weighted_average)
        this.letterGradesReleased = Boolean(data.course.letter_grades_released)
        this.showProgressReport = data.show_progress_report
        if (this.studentsCanViewWeightedAverage || this.letterGradesReleased) {
          this.weightedAverage = data.weighted_score
          this.letterGrade = data.letter_grade
        }
        this.zScore = data.z_score
        const clickerAssignments = this.assignments.filter(assignment => assignment.assessment_type === 'clicker')
        if (clickerAssignments.length) {
          this.centrifuge = await initCentrifuge()
          for (let i = 0; i < clickerAssignments.length; i++) {
            let assignment = clickerAssignments[i]
            let sub = this.centrifuge.newSubscription(`set-current-page-${assignment.id}`)
            sub.on('publication', async function (ctx) {
              console.log(ctx)
              const data = ctx.data
              window.location.href = `/assignments/${assignment.id}/questions/view/${data.question_id}`
            }).subscribe()
          }
        }
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
