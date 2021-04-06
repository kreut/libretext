mounted
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
                <span class="font-weight-bold">Instructions: </span> {{ instructions }}
              </p>
              <p>
                <span class="font-weight-bold">Late Policy: &nbsp;</span>
                {{ formattedLatePolicy }}
              </p>
            </b-card-text>
          </b-card>
          <b-card class="mt-3 mb-3" header="default" header-html="<h5>Questions</h5>">
            <b-table
              v-show="items.length && assessmentType !== 'clicker'"
              striped
              hover
              :no-border-collapse="true"
              :fields="fields"
              :items="items"
            >
              <template #cell(question_number)="data">
                <a href="" @click.stop.prevent="viewQuestion(data.item.question_id)">{{ data.item.question_number }}</a>
              </template>
              <template #cell(solution_file_url)="data">
                <span v-html="getSolutionFileLink(data.item)"></span>
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
import AssignmentStatistics from '../../components/AssignmentStatistics'

export default {
  components: { AssignmentStatistics, Loading },
  middleware: 'auth',
  data: () => ({
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
    canViewAssignmentStatistics: false,
    assignmentInfo: {}
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),

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
        this.assessmentType = assignment.assessment_type
        this.name = assignment.name
        this.pastDue = assignment.past_due
        this.canViewAssignmentStatistics = assignment.can_view_assignment_statistics
        this.fields = [
          'question_number',
          {
            key: 'last_question_submission',
            label: 'Date of Last Question Submission'
          },
          {
            key: 'last_open_ended_submission',
            label: 'Date Of Last Open Ended Submission'
          },
          'last_open_ended_submission']
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
          let lastOpenEndedSubmission = 'None required.'
          let lastSubmitted = 'None required.'
          if (question.open_ended_submission_type !== '0') {
            lastOpenEndedSubmission = question.date_submitted === 'N/A'
              ? 'Nothing submitted yet.'
              : question.date_submitted
          }

          if (question.technology_iframe) {
            lastSubmitted = 'None required'
            lastSubmitted = question.last_submitted === 'N/A'
              ? 'Nothing submitted yet.'
              : question.last_submitted
          }

          let questionInfo = {
            question_id: question.id,
            question_number: i + 1,
            last_question_submission: lastSubmitted,
            last_open_ended_submission: lastOpenEndedSubmission,
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
