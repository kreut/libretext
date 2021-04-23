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
        <PageTitle title="Auto-Graded Submissions"/>
        <b-container>
          <b-row>
            <span class="font-weight-bold mr-2">Title: </span> <a href=""
                                                                  @click.stop.prevent="viewQuestion(questions[currentQuestionPage-1].question_id)"
          >{{ questions[currentQuestionPage - 1].title }}</a>
          </b-row>
          <b-row>
            <span class="font-weight-bold mr-2">Adapt ID: </span>
            {{ questions[currentQuestionPage - 1].assignment_id_question_id }} <span class="text-info ml-1">
              <font-awesome-icon :icon="copyIcon"
                                 @click="doCopy(questions[currentQuestionPage-1].assignment_id_question_id)"
              />
            </span>
          </b-row>
          <b-row><span class="font-weight-bold mr-2">Points: </span> {{ questions[currentQuestionPage - 1].points }}
          </b-row>
        </b-container>
        <b-form-group
          id="student"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Student"
          label-for="Student"
        >
          <b-form-row>
            <b-col lg="6">
              <b-form-select v-model="studentId"
                             :options="studentsOptions"
                             @change="updateStudentsFilter($event)"
              />
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="submission"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Submission"
          label-for="Submission"
        >
          <b-form-row>
            <b-col lg="4">
              <b-form-select v-model="submission"
                             :options="submissionsOptions"
                             @change="updateSubmissionsFilter($event)"
              />
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="score"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Score"
          label-for="Score"
        >
          <b-form-row>
            <b-col lg="3">
              <b-form-select v-model="score"
                             :options="scoresOptions"
                             @change="updateScoresFilter($event)"
              />
            </b-col>
          </b-form-row>
        </b-form-group>
        <div class="overflow-auto">
          <b-pagination
            :key="currentQuestionPage"
            v-model="currentQuestionPage"
            :total-rows="questions.length"
            per-page="1"
            align="center"
            first-number
            last-number
            limit="20"
            @input="changePage(currentQuestionPage)"
          >
            <template v-slot:page="{ page, active }">
              {{ questions[page - 1].order }}
            </template>
          </b-pagination>
        </div>
        <div class="vld-parent">
          <loading :active.sync="isTableLoading"
                   :can-cancel="true"
                   :is-full-page="true"
                   :width="128"
                   :height="128"
                   color="#007BFF"
                   background="#FFFFFF"
          />
          <b-form-group
            id="apply_to"
            label-cols-sm="3"
            label-cols-lg="2"
            label="Apply To"
            label-for="Apply To"
          >
            <b-form-row>

              <b-form-radio-group
                v-model="questionScoreForm.apply_to"
                stacked
              >
                <b-form-radio name="apply_to" value="1">
                  Submission scores in the filtered group
                </b-form-radio>

                <b-form-radio name="apply_to" value="0">
                  Submission scores that are not in the filtered group
                </b-form-radio>
              </b-form-radio-group>

            </b-form-row>
          </b-form-group>
          <b-form-group
            id="new_score"
            label-cols-sm="3"
            label-cols-lg="2"
            label="New Score"
            label-for="New Score"
          >
            <b-form-row>
              <b-col lg="2">
                <b-form-input
                  id="new_score"
                  v-model="questionScoreForm.new_score"
                  lg="7"
                  type="text"
                  :class="{ 'is-invalid': questionScoreForm.errors.has('new_score') }"
                  @keydown="questionScoreForm.errors.clear('new_score')"
                />
                <has-error :form="questionScoreForm" field="new_score"/>
              </b-col>
              <b-col>
                <b-button variant="primary" size="sm" @click="updateScores()">
                  Update
                </b-button>
                <span v-if="processing">
                      <b-spinner small type="grow"/>
                      Processing...
                    </span>
              </b-col>
            </b-form-row>
          </b-form-group>
          <b-table
            v-if="items.length"
            striped
            hover
            :no-border-collapse="true"
            :fields="fields"
            :items="items"
          />
          <b-alert :show="!items.length" class="info">
            <span class="font-weight-bold">Nothing matches that set of filters.</span>
          </b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'
import { viewQuestion, doCopy } from '~/helpers/Questions'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import Form from 'vform'

export default {
  components: {
    Loading,
    FontAwesomeIcon
  },
  middleware: 'auth',
  data: () => ({
    processing: false,
    submission: null,
    score: '',
    questionScoreForm: new Form({
      new_score: null,
      apply_to: 1,
      user_ids: []
    }),
    isTableLoading: false,
    copyIcon: faCopy,
    currentQuestionPage: 1,
    studentId: null,
    studentsOptions: [],
    items: [],
    submissionsOptions: [],
    scoresOptions: [],
    questions: [],
    fields: [
      {
        key: 'name',
        sortable: true
      },
      {
        key: 'email',
        sortable: true
      },
      {
        key: 'submission',
        sortable: true
      },
      {
        key: 'submission_count',
        label: 'Count',
        sortable: true
      },
      {
        key: 'score',
        sortable: true
      }
    ],
    autoGradedSubmissionInfoByQuestionAndUser: [],
    autoGradedSubmissionInfoByUser: [],
    isLoading: true,
    assignmentId: 0
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  async mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$noty.error('You do not have access to the assignment submissions page.')
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.doCopy = doCopy
    this.viewQuestion = viewQuestion
    await this.getQuestions()
    await this.getSubmissions()
    this.setStudentIds()
    if (this.submissionsOptions.length) {
      this.submission = this.submissionsOptions[0].value
      this.score = this.scoresOptions[0].value
    }
  },
  methods: {
    async updateScores () {
      if (!this.processing) {
        this.processing = true
        try {
          const { data } = await this.questionScoreForm.patch(`/api/submissions/${this.assignmentId}/${this.questionId}/scores`)
          this.$noty[data.type](data.message)
          if (data.type === 'success') {
            await this.getSubmissions()
          }
        } catch (error) {
          if (!error.message.includes('status code 422')) {
            this.$noty.error(error.message)
          }
        }
        this.processing = false
      } else {
        this.$noty.info('Please be patient while we process this request.')
      }
    },

    async changePage (currentQuestionPage) {
      this.isTableLoading = true
      this.questionId = this.questions[currentQuestionPage - 1].question_id
      await this.getSubmissions()
      this.studentId = null
      this.submission = null
      this.score = null
      this.setStudentIds()
      this.isTableLoading = false
    },

    async updateFilter (studentId, submission, score) {
      await this.getSubmissions()
      if (this.studentId !== null) {
        this.items = this.items.filter(item => item.user_id === studentId)
      }
      if (this.submission !== null) {
        this.items = this.items.filter(item => item.submission === submission)
      }
      if (this.score !== null) {
        this.items = this.items.filter(item => item.score === score)
      }
      this.setStudentIds()
    },
    async updateStudentsFilter (value) {
      this.isTableLoading = true
      await this.updateFilter(value, this.submission, this.score)
      this.isTableLoading = false
    },
    async updateSubmissionsFilter (value) {
      this.isTableLoading = true
      await this.updateFilter(this.studentId, value, this.score)
      this.isTableLoading = false
    },
    async updateScoresFilter (value) {
      this.isTableLoading = true
      await this.updateFilter(this.studentId, this.submission, value)
      this.isTableLoading = false
    },
    setStudentIds () {
      this.questionScoreForm.user_ids = []
      for (let i = 0; i < this.items.length; i++) {
        this.questionScoreForm.user_ids.push(this.items[i].user_id)
      }
    },
    async getQuestions () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/summary`)
        for (let i = 0; i < data.rows.length; i++) {
          if (data.rows[i].technology !== 'text') {
            this.questions.push(data.rows[i])
          }
        }
        console.log(this.questions)
        this.questionId = this.questions[0].question_id
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.currentQuestionPage = 1
    },
    async getSubmissions () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/${this.questionId}/get-auto-graded-submissions`)
        console.log(data)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.items = data.auto_graded_submission_info_by_user
        this.studentsOptions = [{ 'value': null, text: 'All students with submissions' }]
        this.scoresOptions = []
        this.submissionsOptions = []
        let usedSubmissions = []
        let usedScores = []
        let submission
        for (let i = 0; i < this.items.length; i++) {
          let item = this.items[i]
          let student = { value: item.user_id, text: item.name }
          this.studentsOptions.push(student)
          let score = { value: item.score, text: item.score }
          if (!usedScores.includes(score.value)) {
            this.scoresOptions.push(score)
            usedScores.push(score.value)
          }
          submission = { value: item.submission, text: item.submission }
          if (!usedSubmissions.includes(submission.value)) {
            this.submissionsOptions.push(submission)
            usedSubmissions.push(submission.value)
          }
        }
        this.submissionsOptions = this.submissionsOptions.sort((a, b) => (a.value > b.value) ? 1 : -1)

        // move no submission to the end
        this.submissionsOptions.unshift({ value: null, text: 'Any submission' })

        this.scoresOptions = this.scoresOptions.sort((a, b) => (a.value > b.value) ? 1 : -1)
        this.scoresOptions.unshift({ value: null, text: 'Any score' })
        console.log(this.scoresOptions)
      } catch (error) {
        this.$noty.error(error.message)
        this.isLoading = false
      }
    }
  }
}
</script>
