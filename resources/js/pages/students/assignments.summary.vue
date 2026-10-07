<template>
  <div>
    <RedirectToClickerModal
      :key="`redirect-to-clicker-modal-${clickerAssignmentId}-${clickerQuestionId}-${redirectToClickerModalKey}`"
      :assignment-id="clickerAssignmentId"
      :question-id="clickerQuestionId"
      @resetClickerAssignmentIdClickerQuestionId="resetClickerAssignmentIdClickerQuestionId()"
    />
    <b-modal id="modal-last-open-ended-submission"
             title="Last Open-Ended Submission"
             hide-footer
    >
      Open-ended questions are questions which will require a grader. Some examples are file uploads, text
      based responses, audio uploads, and submissions through Forge.
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
      title="Congratulations!"
    >
      <b-alert variant="info" show>
        <span style="font-size: large">
          All responses successfully submitted.
        </span>
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
        <PageTitle :title="name"
                   :assessment-type="assessmentType"
                   :has-time-limit="Boolean(timeLimitStatus.time_limit)"
        />
        <b-container>
          <div v-if="!showNoAccessMessage && (assessmentType !== 'clicker' || solutionsReleased)">
            <b-row align-h="end">
              <b-button class="ml-3 mb-2" variant="primary" size="sm" @click="getStudentView(assignmentId)">
                View Assessments
              </b-button>
            </b-row>
            <hr>
          </div>
          <div v-show="isInstructorLoggedInAsStudent" class="mb-2">
            <LoggedInAsStudent :student-name="user.first_name + ' ' + user.last_name"/>
          </div>
          <div v-if="assessmentType === 'clicker' && !solutionsReleased">
            Please wait for your instructor to open up this assignment.
          </div>
          <div v-show="showNoAccessMessage">
            <b-alert variant="danger" show>
              {{ showNoAccessMessage }}
            </b-alert>
          </div>
          <b-card v-show="!showNoAccessMessage && assessmentType !== 'clicker'" header="default"
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
                  <span v-if="hasDifferentForgeDueDates">
    This assignment has questions with multiple distinct due dates. Please review them in the table below.
  </span>
                  <span v-else>
    This assignment is due {{ formattedDue }}.
    <span v-if="extension">(You have an extension until {{ extension }}).</span>
  </span>
                </li>
                <li v-if="timeLimitStatus.time_limit" class="mb-2">
                  <span class="font-weight-bold">Time Limit: </span>
                  <span>{{ timeLimitStatus.time_limit }} once started.</span>
                  <span v-if="timeLimitStatus.started_at">
                    (You started this assignment at
                    {{ $moment(timeLimitStatus.started_at).format('M/D/YY h:mm A') }}<span v-if="timeLimitStatus.expires_at">
                      and it {{ timeLimitStatus.seconds_left > 0 ? 'expires' : 'expired' }} at
                      {{ $moment(timeLimitStatus.expires_at).format('M/D/YY h:mm A') }}</span>.)
                  </span>
                </li>
                <li class="mb-2">
                  <span class="font-weight-bold">Late Policy: </span>
                  <span> {{ formattedLatePolicy }}</span>
                </li>
                <li v-if="numberOfFileUploadQuestions > 1" class="mb-2">
                  <span class="font-weight-bold">
                    File submissions:</span>
                  <span>
                    Upload your files from each question's page. If your work for several questions is in one PDF,
                    upload it from any of those questions and then choose which page goes with each question.
                  </span>
                </li>
              </ul>
            </b-card-text>
          </b-card>

          <b-card v-show="items.length && assessmentType !== 'clicker'"
                  id="questions_card"
                  class="mt-3 mb-3"
                  header="default"
                  header-html="<h2 class=&quot;h5&quot;>Questions</h2>"
          >
            <div class="vld-parent" style="min-height: 100px;">
              <loading :active.sync="questionsLoading"
                       :can-cancel="false"
                       :is-full-page="false"
                       :width="64"
                       :height="64"
                       color="#007BFF"
                       background="#FFFFFF"
              />
              <div v-show="!questionsLoading">
                <b-alert variant="success" :show="completedAllAssignmentQuestions">
                  <span class="font-weight-bold">You have completed all assessments on this assignment!</span>
                </b-alert>
                <b-table
                  id="summary_of_questions_and_submissions"
                  aria-label="Summary of questions and submissions"
                  striped
                  hover
                  table-class="'table-layout-fixed'"
                  :no-border-collapse="true"
                  :fields="shownFields"
                  :items="items"
                >
                  <template #cell(question_number)="data">
                    <a href="" @click.stop.prevent="viewQuestion(data.item.question_id)">&nbsp;
                      {{ data.item.question_number }}. {{ data.item.title }}</a>
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
              <span v-if="data.item.is_forge" class="d-flex align-items-center">
              {{ data.item.last_open_ended_submission }}
                 <font-awesome-icon v-show="data.item.showThumbsUpForOpenEndedSubmission"
                                    class="text-success ml-1"
                                    :icon="checkIcon"
                 />
              </span>
              <span v-else>
                <a :href="data.item.submission_file_url" target="_blank">{{
                    data.item.last_open_ended_submission
                  }}</a><font-awesome-icon v-show="data.item.showThumbsUpForOpenEndedSubmission"
                                           class="text-success ml-1"
                                           :icon="checkIcon"
              />
              </span>
            </span>
          </span>
                  </template>
                  <template v-slot:head(last_open_ended_submission)="data">
                    Last Open-Ended Submission
                    <QuestionCircleTooltipModal :aria-label="'Last Open-Ended Submission'"
                                                :modal-id="'modal-last-open-ended-submission'"
                    />
                  </template>
                  <template #cell(solution_file_url)="data">
                    <SolutionFileHtml :questions="items"
                                      :modal-id="`solution-${data.item.question_number}`"
                                      :current-page="data.item.question_number"
                                      assignment-name="Question"
                    />
                  </template>
                  <template #cell(forge_due_dates)="data">
                    <ForgeDueDates
                      v-if="data.item.is_forge"
                      class="mr-2"
                      :question-title="data.item.title"
                      :assignment-id="+assignmentId"
                      :key="`forge-due-dates-${data.item.question_id}`"
                      :parent-question-id="data.item.forge_source_id || data.item.question_id"
                      :current-draft-question-id="data.item.question_id"
                      :color-coded-due-date="true"
                      @due-date-loaded="checkForgeDueDate"
                    />
                    <span v-else :class="assignmentDueDateClass()">
  {{ formattedDue }}
</span>
                  </template>
                </b-table>
              </div>
            </div>
          </b-card>

          <b-card v-if="canViewAssignmentStatistics && !questionsLoading" class="mb-5" header="default"
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
import { faCheck } from '@fortawesome/free-solid-svg-icons'
import LoggedInAsStudent from '~/components/LoggedInAsStudent'
import SolutionFileHtml from '~/components/SolutionFileHtml'
import QuestionCircleTooltipModal from '~/components/QuestionCircleTooltipModal'
import RedirectToClickerModal from '../../components/RedirectToClickerModal.vue'
import {
  initClickerAssignmentsForEnrolledAndOpenCourses,
  resetClickerAssignmentIdClickerQuestionId
} from '../../helpers/clicker'
import ForgeViewer from '~/components/viewers/ForgeViewer.vue'
import ForgeDueDates from '../../components/ForgeDueDates.vue'

export default {
  components: {
    ForgeDueDates,
    ForgeViewer,
    RedirectToClickerModal,
    AssignmentStatistics,
    Loading,
    FontAwesomeIcon,
    LoggedInAsStudent,
    SolutionFileHtml,
    QuestionCircleTooltipModal
  },
  metaInfo () {
    return { title: 'Assignment - Summary' }
  },
  middleware: 'auth',
  data: () => ({
    finalSubmissionDeadline: '',
    questionsLoading: true,
    hasDifferentForgeDueDates: false,
    forgeDueDatesChecked: 0,
    forgeQuestionCount: 0,
    redirectToClickerModalKey: 0,
    clickerAssignmentId: 0,
    clickerQuestionId: 0,
    showNoAccessMessage: '',
    solutionsReleased: false,
    assignmentId: 0,
    cacheKey: 0,
    numberOfAllowedAttempts: '',
    numberOfAllowedAttemptsPenalty: '',
    numberOfFileUploadQuestions: 0,
    extension: null,
    timeLimitStatus: {},
    public_description: null,
    isInstructorLoggedInAsStudent: false,
    totalPoints: '',
    completedAllAssignmentQuestions: false,
    openEndedSubmissionQuestionOptions: [],
    question: {},
    checkIcon: faCheck,
    fields: [],
    items: [],
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
      return this.fields.filter(field => {
        if (field.key === 'forge_due_dates') {
          if (this.questionsLoading) return true
          return this.hasDifferentForgeDueDates
        }
        return field.shown
      })
    }
  },
  async mounted () {
    this.assignmentId = this.$route.params.assignmentId
    if (await this.getAssignmentSummary() === false) {
      this.isLoading = false
      return false
    }
    await this.getTimeLimitStatus()

    await this.$nextTick(() => {
      this.getSelectedQuestions(this.assignmentId)
    })

    this.isLoading = false
    this.$nextTick(function () {
      this.resizeHandler()
    })
    window.addEventListener('resize', this.resizeHandler)
    await this.initClickerAssignmentsForEnrolledAndOpenCourses()
  },
  beforeDestroy () {
    window.removeEventListener('resize', this.resizeHandler)
    try {
      if (this.centrifuge) {
        this.centrifuge.disconnect()
      }
    } catch (error) {
      // won't be a function for all the other ones that haven't been defined on the page
    }
  },
  methods: {
    resetClickerAssignmentIdClickerQuestionId,
    initClickerAssignmentsForEnrolledAndOpenCourses,
    formatDate (dateString) {
      if (!dateString) return ''
      const d = new Date(dateString.replace(' at ', ' '))
      const month = d.getMonth() + 1
      const day = d.getDate()
      const year = String(d.getFullYear()).slice(-2)
      let hours = d.getHours()
      const minutes = String(d.getMinutes()).padStart(2, '0')
      const ampm = hours >= 12 ? 'pm' : 'am'
      hours = hours % 12 || 12
      return `${month}/${day}/${year} ${hours}:${minutes}${ampm}`
    },
    parseFormattedDate (dateStr) {
      if (!dateStr) return null
      const parts = dateStr.match(/(\d+)\/(\d+)\/(\d+)\s+(\d+):(\d+)(am|pm)/i)
      if (!parts) return null
      let [, month, day, year, hours, minutes, ampm] = parts
      hours = parseInt(hours)
      if (ampm.toLowerCase() === 'pm' && hours !== 12) hours += 12
      if (ampm.toLowerCase() === 'am' && hours === 12) hours = 0
      return new Date(2000 + parseInt(year), parseInt(month) - 1, parseInt(day), hours, parseInt(minutes))
    },
    assignmentDueDateClass () {
      const due = this.parseFormattedDate(this.formattedDue)
      if (!due) return 'dark-red'
      const now = new Date()
      if (now <= due) return 'text-success'
      if (this.finalSubmissionDeadline) {
        const finalDeadline = this.parseFormattedDate(this.finalSubmissionDeadline)
        if (finalDeadline && now <= finalDeadline) return 'text-warning'
      }
      return 'dark-red'
    },
    checkForgeDueDate (forgeDueDate) {
      this.forgeDueDatesChecked++
      const normalize = (s) => s ? s.replace(/\s+(am|pm)/i, '$1').trim() : ''
      const normalizedForgeDue = normalize(this.formatDate(forgeDueDate))
      const normalizedAssignmentDue = normalize(this.formattedDue)
      if (normalizedForgeDue !== normalizedAssignmentDue) {
        this.hasDifferentForgeDueDates = true
      }
      if (this.forgeDueDatesChecked >= this.forgeQuestionCount) {
        this.questionsLoading = false
      }
    },
    async getDiscussItQuestions (assignmentId) {
      try {
        const { data } = await axios.get(`/api/assignments/${assignmentId}/questions/discuss-it`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        } else {
          return data.discuss_it_question_info
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      return false
    },
    getThumbsUpWidth () {
      return this.inIFrame ? 150 : 275
    },
    resizeHandler () {
      let table = document.getElementById('summary_of_questions_and_submissions')
      if (table && this.zoomGreaterThan(1.2)) {
        document.getElementById('questions_card').style.width = (parseInt(table.offsetWidth) + 100).toString() + 'px'
      } else {
        document.getElementById('questions_card').style.width = ''
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
    async getTimeLimitStatus () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/time-limit/status`)
        if (data.type === 'success') {
          this.timeLimitStatus = data
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getAssignmentSummary () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
        console.log(data)
        if (data.type === 'error') {
          if (data.message === 'You are not allowed to retrieve this summary.') {
            this.showNoAccessMessage = 'You are not allowed to retrieve this summary.'
          } else {
            this.$noty.error(data.message)
          }
          return false
        }
        let assignment = data.assignment
        this.finalSubmissionDeadline = assignment.final_submission_deadline
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
        this.assessmentType = assignment.assessment_type
        this.extension = assignment.extension
        this.name = assignment.name
        this.solutionsReleased = Boolean(assignment.solutions_released)
        this.canViewAssignmentStatistics = assignment.can_view_assignment_statistics
        this.completedAllAssignmentQuestions = assignment.completed_all_assignment_questions
        this.fields = [
          {
            key: 'question_number',
            label: 'Question',
            thStyle: { minWidth: '200px', maxWidth: '350px' },
            tdAttr: { style: 'max-width: 350px; word-wrap: break-word' },
            isRowHeader: true,
            shown: true
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
            key: 'forge_due_dates',
            label: 'Due Dates',
            thStyle: { minWidth: '165px' },
            shown: true
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
      const discussItQuestions = await this.getDiscussItQuestions(this.assignmentId)

      try {
        const { data } = await axios.get(`/api/assignments/${assignmentId}/questions/view`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.numberOfFileUploadQuestions = 0
        for (let i = 0; i < data.questions.length; i++) {
          let question = data.questions[i]
          let lastOpenEndedSubmission = 'N/A'
          let lastSubmitted = 'N/A'
          let openEndedSubmissionRequired = false
          let showThumbsUpForOpenEndedSubmission = false
          let questionSubmissionRequired = false
          let showThumbsUpForQuestionSubmission = false
          if (question.open_ended_submission_type !== '0' || (question.qti_json && ['forge', 'forge_iteration'].includes(question.qti_json_type))) {
            openEndedSubmissionRequired = true
            const questionSubmitted = question.date_submitted !== 'N/A' && typeof question.date_submitted !== 'undefined'
            lastOpenEndedSubmission = !questionSubmitted
              ? 'Nothing submitted yet.'
              : question.date_submitted
            showThumbsUpForOpenEndedSubmission = questionSubmitted
            this.openEndedSubmissionQuestionOptions.push({ value: question.id, text: i + 1 })
          }
          if (question.open_ended_submission_type === 'file') {
            this.numberOfFileUploadQuestions++
          }
          if (question.technology_iframe || (question.qti_json && !['forge', 'forge_iteration'].includes(question.qti_json_type))) {
            questionSubmissionRequired = true
            lastSubmitted = question.last_submitted === 'N/A' && typeof question.last_submitted !== 'undefined'
              ? 'Nothing submitted yet.'
              : question.last_submitted
            showThumbsUpForQuestionSubmission = question.last_submitted !== 'N/A'
          }
          let solutionHtml
          if (question.solution_type === 'html') {
            solutionHtml = question.solution_html
            if (!solutionHtml && question.answer_html) {
              solutionHtml = question.answer_html
            }
          }
          let questionInfo = {
            question_id: question.id,
            question_number: i + 1,
            is_forge: question.qti_json && ['forge', 'forge_iteration'].includes(question.qti_json_type),
            forge_source_id: question.forge_source_id,
            last_question_submission: lastSubmitted,
            questionSubmissionRequired: questionSubmissionRequired,
            technology_iframe_src: question.technology_iframe,
            technology_iframe: question.technology_iframe,
            render_webwork_solution: question.render_webwork_solution,
            showThumbsUpForQuestionSubmission: showThumbsUpForQuestionSubmission,
            openEndedSubmissionRequired: openEndedSubmissionRequired,
            last_open_ended_submission: lastOpenEndedSubmission,
            isOpenEndedFileSubmission: question.open_ended_submission_type === 'file',
            showThumbsUpForOpenEndedSubmission: showThumbsUpForOpenEndedSubmission,
            submission_file_exists: question.submission_file_exists,
            submission_file_url: question.submission_file_url ? question.submission_file_url : null,
            solution_type: question.solution_type,
            title: question.title,
            solution_html: solutionHtml,
            solution_file_url: question.solution_file_url ? question.solution_file_url : null,
            solution: question.solution ? question.solution : null,
            points: question.points ? question.points : 'N/A',
            total_score: question.hasOwnProperty('total_score') ? question.total_score : 'N/A',
            solution_file: question.solution_file_url
          }
          const discussItQuestion = discussItQuestions.find(item => +item.id === +questionInfo.question_id)
          if (discussItQuestion) {
            questionInfo.last_open_ended_submission = discussItQuestion.last_submitted
            questionInfo.total_score = discussItQuestion.total_score
          }
          this.items.push(questionInfo)
        }

        this.forgeQuestionCount = this.items.filter(item => item.is_forge).length
        if (this.forgeQuestionCount === 0) {
          this.questionsLoading = false
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
<style>
.table-layout-fixed {
  table-layout: fixed;
}
</style>
