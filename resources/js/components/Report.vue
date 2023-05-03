<template>
  <div>
    <div v-show="loaded">
      <div v-if="!grading && user.role !== 2">
        <b-alert :variant="!missingSections ? 'success' :'info'" show>
          <span v-if="missingSections">
            You have not yet submitted the following sections: {{ missingSections }}.
          </span>
          <span v-if="!missingSections">
            All sections have been successfully submitted.
          </span>
        </b-alert>
      </div>
      <div v-if="user.role ===2 && !grading">
        <div v-show="computedPointsNotEqualToQuestionPointsError">
          <b-alert variant="warning" show>
            {{ computedPointsNotEqualToQuestionPointsError }}
          </b-alert>
        </div>
        <b-alert variant="info" show>
          Your students will see the following after they upload their full report. They should paste each
          section into the appropriate location. Toggling the Criteria, Scores and Comments views will only
          affect the
          student view (all at the section level). Also note that Score and Comments will only be shown if scores have been released at the assignment level.
        </b-alert>
        <b-form-row class="mt-2 mb-2">
          <span style="width:125px">Criteria</span>
          <ReportToggle key="report-toggle-rubric"
                        item="criteria"
                        :question-id="questionId"
                        :assignment-id="assignmentId"
          />
        </b-form-row>
        <b-form-row class="mt-2">
          <span style="width:125px">Scores</span>
          <ReportToggle key="report-toggle-section-scores"
                        item="section_scores"
                        :question-id="questionId"
                        :assignment-id="assignmentId"
          />
        </b-form-row>
        <b-form-row class="mt-2">
          <span style="width:125px">Comments</span>
          <ReportToggle key="report-toggle-comments"
                        item="comments"
                        :question-id="questionId"
                        :assignment-id="assignmentId"
          />
        </b-form-row>
      </div>
      <b-card header-html="<h2 class=&quot;h7&quot;>Report Sections</h2>">
        <div v-if="!grading">
          <p>
            Copy the text from your report and paste it into the appropriate sections. Don't worry if the formatting
            is
            affected.
          </p>
        </div>
        <div v-if="grading" class="mb-3">
          <ul style="list-style:none;padding-left:0;font-size:16px">
            <li><span class="font-weight-bold">Total Score:</span> {{ totalScore }}</li>
          </ul>
          <hr>
        </div>
        <b-tabs v-if="loaded" smallv>
          <div v-if="showScores && (reportToggle.section_scores || reportToggle.comments)">
            <b-tab>
              <template #title>
                <span style="font-weight:bold;color:#007bff;">Grading Summary</span>
              </template>
              <div v-show="showScores && (reportToggle.section_scores || reportToggle.comments)">
                <table class="table table-striped">
                  <thead>
                  <tr>
                    <th scope="col">
                      Section
                    </th>
                    <th v-if="reportToggle.section_scores" scope="col" style="width:100px">
                      Score
                    </th>
                    <th v-if="reportToggle.comments" scope="col">
                      Comments
                    </th>
                  </tr>
                  </thead>
                  <tr v-for="(rubricCategory,rubricCategoryIndex) in rubricCategories"
                      :key="`rubric-category-submission-${rubricCategoryIndex}`"
                  >
                    <th scope="row">
                      {{ rubricCategory.category }}
                    </th>
                    <td v-if="reportToggle.section_scores">
                      {{
                        getRubricCategorySubmissionPoints(rubricCategorySubmissions, rubricCategory.id)
                      }}/{{ rubricCategory.score }}
                    </td>
                    <td v-if="reportToggle.comments">
                      {{ getRubricCategorySubmissionItem(rubricCategorySubmissions, rubricCategory.id, 'feedback') }}
                    </td>
                  </tr>
                  <tr>
                    <th scope="row">
                      Overall
                    </th>
                    <td v-if="reportToggle.section_scores">
                      {{ totalScore }}/{{ computedPoints }}
                    </td>
                    <td>
                      <div v-if="reportToggle.comments" v-html="overallComments ? overallComments : 'None provided'"/>
                    </td>
                  </tr>
                </table>
              </div>
            </b-tab>
          </div>
          <b-tab v-for="(rubricCategory,rubricCategoryIndex) in rubricCategories"
                 :key="`rubric-category-${rubricCategoryIndex}`"
          >
            <template #title>
              {{ rubricCategory.category }}
            </template>
            <ul class="pt-4" style="padding-left:0;list-style:none">
              <li v-if="reportToggle.criteria">
                <span class="font-weight-bold">Criteria:</span> {{ rubricCategory.criteria }}
              </li>
              <li v-if="grading">
                <span class="font-weight-bold">Max possible score:</span> {{ rubricCategory.score }}
              </li>
              <li v-if="showScores && reportToggle.section_scores && !grading">
                <span class="font-weight-bold">
                  Score: </span> {{
                  getRubricCategorySubmissionPoints(rubricCategorySubmissions, rubricCategory.id)
                }}/{{ rubricCategory.score }}
              </li>
              <li v-if="showScores && reportToggle.comments && !grading">
                <span class="font-weight-bold">Comments:     </span>
                {{ getRubricCategorySubmissionItem(rubricCategorySubmissions, rubricCategory.id, 'feedback') }}
              </li>
            </ul>
            <b-form-group class="mt-3">
              <div v-if="grading" class="mb-4">
                <div v-if="rubricCategory.rubricCategorySubmission.submission">
                  <b-form-row class="mb-2">
                    <span class="mr-2 mt-1 m-1">Score</span>
                    <b-form-input
                      v-if="rubricCategory.rubricCategorySubmission"
                      :id="`rubric-category-submission-score-${rubricCategoryIndex}`"
                      v-model="rubricCategory.rubricCategorySubmission.score"
                      :class="{ 'is-invalid': graderErrors.score }"
                      :style="isNaN(rubricCategory.rubricCategorySubmission.score) ? 'width:300px' : 'width:100px'"
                      size="sm"
                      rows="3"
                      @keydown="graderErrors.score =''"
                    />
                  </b-form-row>
                  <ErrorMessage :message="graderErrors.score"/>
                  <b-form-group
                    :id="`rubric-category-submission-feedback-${rubricCategoryIndex}`"
                    label="Feedback"
                  >
                    <b-textarea
                      v-if="rubricCategory.rubricCategorySubmission"
                      v-model="rubricCategory.rubricCategorySubmission.feedback"
                      :class="{ 'is-invalid': graderErrors.feedback }"
                      type="text"
                      rows="3"
                      @keydown="graderErrors.feedback =''"
                    />
                    <ErrorMessage :message="graderErrors.feedback"/>
                  </b-form-group>
                  <b-form-row>
                    <b-button v-if="grading"
                              size="sm"
                              variant="primary"
                              class="mt-2"
                              @click="$forceUpdate();saveRubricCategorySubmissionCustomScoreAndFeedback(rubricCategory.rubricCategorySubmission.id, rubricCategory.rubricCategorySubmission.feedback,rubricCategory.rubricCategorySubmission.score, rubricCategory.score)"
                    >
                      Save
                    </b-button>
                  </b-form-row>
                </div>
                <div v-else>
                  <b-alert variant="info" show>
                    Nothing submitted.
                  </b-alert>
                </div>
              </div>
              <hr>
              <b-form-group
                :id="`rubric-category-submission-${rubricCategoryIndex}`"
                :label="!grading ? `Paste your '${rubricCategory.category}' submission below:` : ''"
              >
                <div v-if="grading">
                  <div v-if="rubricCategory.rubricCategorySubmission">
                    {{ rubricCategory.rubricCategorySubmission.submission }}
                  </div>
                  <div v-else>
                    No submission.
                  </div>
                </div>
                <div v-if="!grading">
                  <b-textarea
                    v-if="rubricCategory.rubricCategorySubmission"
                    v-model="rubricCategory.rubricCategorySubmission.submission"
                    :class="{ 'is-invalid': rubricCategory.error }"
                    type="text"
                    rows="15"
                    @keydown="rubricCategory.error = '';$forceUpdate()"
                  />
                  <ErrorMessage :message="rubricCategory.error"/>
                </div>
              </b-form-group>
              <b-form-row>
                <b-button v-if="!grading"
                          size="sm"
                          variant="primary"
                          class="mt-2"
                          :disabled="user.role === 2"
                          @click="saveRubricCategorySubmission(rubricCategory.id)"
                >
                  Save '{{ rubricCategory.category }}' Submission
                </b-button>
              </b-form-row>
            </b-form-group>
          </b-tab>
        </b-tabs>
      </b-card>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import ErrorMessage from './ErrorMessage.vue'
import { mapGetters } from 'vuex'
import ReportToggle from './ReportToggle.vue'

export default {
  name: 'Report',
  components: { ReportToggle, ErrorMessage },
  props: {
    rubricScale: {
      type: String,
      default: ''
    },
    overallComments: {
      type: String,
      default: ''
    },
    rubricCategories: {
      type: Array,
      default: () => {
      }
    },
    questionId: {
      type: Number,
      default: 0
    },
    points: {
      type: Number,
      default: 0
    },
    assignmentId: {
      type: Number,
      default: 0
    },
    userId: {
      type: Number,
      default: 0
    },
    grading: {
      type: Boolean,
      default: false
    }
  },
  data: () => ({
    computedPointsNotEqualToQuestionPointsError: '',
    computedPoints: 0,
    isMe: () => window.config.isMe,
    reportToggle: { section_scores: false, comments: false, criteria: false },
    showScores: false,
    loaded: false,
    missingSections: '',
    submittedSections: [],
    totalScore: 0,
    graderErrors: { score: '', feedback: '' },
    rubricCategorySubmissions: []
  }),

  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.initSections()
  },
  methods: {
    getRubricCategorySubmissionPoints (rubricCategorySubmissions, rubricCategoryId) {
      let score = this.getRubricCategorySubmissionItem(rubricCategorySubmissions, rubricCategoryId, 'score')
      if (score && !isNaN(score)) {
        return score
      } else {
        return 0
      }
    },
    getRubricCategorySubmissionItem (rubricCategorySubmissions, rubricCategoryId, key) {
      let submission = rubricCategorySubmissions.find(item => item.rubric_category_id === rubricCategoryId)
      if (submission) {
        let customKey = `custom_${key}`
        return submission[customKey]
          ? submission[customKey]
          : submission[key]
      } else {
        if (key === 'feedback') {
          return 'Nothing submitted'
        }
      }
    },
    getMissingCategories () {
      let missingSections = this.rubricCategories.filter(item => !this.submittedSections.includes(item.id))
      let missingSectionsTextArr = []
      if (missingSections) {
        for (let i = 0; i < missingSections.length; i++) {
          missingSectionsTextArr.push(missingSections[i].category)
        }
      } else {
        missingSectionsTextArr = []
      }
      this.missingSections = missingSectionsTextArr.join(', ')
    },
    async saveRubricCategorySubmissionCustomScoreAndFeedback (rubricCategorySubmissionId, feedback, score, maxScore) {
      this.graderErrors = { score: '', feedback: '' }
      if (score === '') {
        this.graderErrors.score = `You did not enter a score.`
      }
      if (isNaN(score)) {
        this.graderErrors.score = `${score} is not a number.`
      } else {
        if (score > maxScore) {
          this.graderErrors.score = `You cannot award more than ${maxScore} points.`
        }
        if (score < 0) {
          this.graderErrors.score = `The score must be positive.`
        }
      }
      if (feedback === '') {
        this.graderErrors.feedback = 'This field is required.'
      }
      if (this.graderErrors.score || this.graderErrors.feedback) {
        return false
      }
      try {
        const { data } = await axios.patch(`/api/rubric-category-submissions/custom/${rubricCategorySubmissionId}`, {
          user_id: this.userId,
          custom_feedback: feedback,
          custom_score: score
        })
        this.$noty[data.type](data.message)
        if (data.type !== 'success') {
          return false
        }
        this.getTotalScore()
        this.$nextTick(() => {
          this.$emit('saveOpenEndedScore', this.totalScore)
        })
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getMessage (rubricCategorySubmission, key) {
      let openAIResponse = rubricCategorySubmission &&
      rubricCategorySubmission.message &&
      rubricCategorySubmission.message ? rubricCategorySubmission.message : ''
      try {
        let responseObj = JSON.parse(openAIResponse)
        let feedback = responseObj.choices[0].text
        let feedbackSubstring = feedback.substring(feedback.indexOf('{"feedback":'))
        return JSON.parse(feedbackSubstring)[key]
      } catch (error) {
        console.log(openAIResponse)
        return `Unable to retrieve the ${key}. `
      }
    },
    async saveRubricCategorySubmission (rubricCategoryId) {
      let rubricCategory = this.rubricCategories.find(rubricCategory => rubricCategory.id === rubricCategoryId)
      try {
        const { data } = await axios.patch(`/api/rubric-category-submissions/${rubricCategoryId}/assignment/${this.assignmentId}/question/${this.questionId}`, { submission: rubricCategory.rubricCategorySubmission.submission })
        if (data.type === 'success') {
          this.$noty[data.type](data.message)
          this.submittedSections.push(rubricCategoryId)
          this.getMissingCategories()
        } else {
          if (rubricCategory.rubricCategorySubmission.submission === '') {
            rubricCategory.error = 'This field is required.'
          } else {
            this.$noty.error(data.message)
          }
        }
        this.$forceUpdate()
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getTotalScore () {
      this.totalScore = 0
      for (let i = 0; i < this.rubricCategorySubmissions.length; i++) {
        console.log(this.totalScore)
        let rubricCategorySubmission = this.rubricCategorySubmissions[i]
        let rubricCategory = this.rubricCategories.find(category => category.id === rubricCategorySubmission.rubric_category_id)
        if (rubricCategory) {
          rubricCategory.rubricCategorySubmission = rubricCategorySubmission
          this.totalScore += !isNaN(rubricCategory.rubricCategorySubmission.score) ? +rubricCategory.rubricCategorySubmission.score : 0
        }
      }
    },
    async initSections () {
      try {
        const { data } = await axios.get(`/api/rubric-category-submissions/assignment/${this.assignmentId}/question/${this.questionId}/user/${this.userId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.loaded = true
          return false
        }
        this.showScores = data.show_scores
        this.reportToggle = data.report_toggle
        console.log(data)
        for (let i = 0; i < this.rubricCategories.length; i++) {
          this.rubricCategories[i].rubricCategorySubmission = { submission: '', feedback: '', score: 0 }
          this.rubricCategories[i].error = ''
        }
        this.submittedSections = []
        this.rubricCategorySubmissions = data.rubric_category_submissions
        console.log(this.rubricCategories)
        for (let i = 0; i < this.rubricCategories.length; i++) {
          this.computedPoints += this.rubricCategories[i].score
          let rubricCategory = this.rubricCategories[i]
          let rubricCategorySubmission = this.rubricCategorySubmissions.find(item => item.rubric_category_id === rubricCategory.id)
          if (rubricCategorySubmission) {
            this.submittedSections.push(rubricCategory.id)
            rubricCategory.rubricCategorySubmission = rubricCategorySubmission
            rubricCategory.rubricCategorySubmission.feedback = rubricCategory.rubricCategorySubmission.custom_feedback !== null
              ? rubricCategory.rubricCategorySubmission.custom_feedback
              : this.getMessage(rubricCategorySubmission, 'feedback')
            rubricCategory.rubricCategorySubmission.score = rubricCategory.rubricCategorySubmission.custom_score !== null
              ? rubricCategory.rubricCategorySubmission.custom_score
              : this.getMessage(rubricCategorySubmission, 'score')
          }
        }
        console.log(this.points)
        this.computedPointsNotEqualToQuestionPointsError = this.computedPoints !== this.points
          ? `The question is worth ${this.points} points while the total points in your report is equal to ${this.computedPoints} points.`
          : ''

        console.log(this.rubricCategories)
        this.getMissingCategories()
        this.getTotalScore()
        this.$forceUpdate()
        this.loaded = true
      } catch (error) {
        this.$noty.error(error.message)
        this.loaded = true
      }
    }
  }
}
</script>
