<template>
  <div>
    <div v-show="loaded">
      <div v-if="!grading">
        <b-alert :variant="!missingSections ? 'success' :'info'" show>
          <span v-if="missingSections">
            You have not yet submitted the following sections: {{ missingSections }}.
          </span>
          <span v-if="!missingSections">
            All sections have been successfully submitted.
          </span>
        </b-alert>
      </div>
      <b-card header-html="<h2 class=&quot;h7&quot;>Lab Sections</h2>">
        <div v-if="!grading">
          <p>
            Copy the text from your Lab Report and paste it into the appropriate sections. Don't worry if the formatting
            is
            affected.
          </p>
        </div>
        <div v-if="grading" class="mb-3">
          <ul style="list-style:none;padding-left:0;font-size:16px">
            <li><span class="font-weight-bold">Total Percent:</span> {{ totalPercent }}%</li>
            <li><span class="font-weight-bold">Total Score:</span> {{ totalScore }}</li>
          </ul>
          <hr>
        </div>
        <b-tabs small>
          <b-tab v-show="showScores">
            <template #title>
              <span style="font-weight:bold;color:#007bff;">Grading Summary</span>
            </template>
            <table class="table table-striped">
              <thead>
              <tr>
                <th scope="col">
                  Section
                </th>
                <th scope="col" style="width:100px">
                  Points
                </th>
                <th scope="col">
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
                <td>
                  {{
                    getRubricCategorySubmissionPoints(rubricCategorySubmissions, rubricCategory.id)
                  }}/{{ getRubricCategoryPoints(rubricCategorySubmissions, rubricCategory.id) }}
                </td>
                <td>{{ getRubricCategorySubmissionItem(rubricCategorySubmissions, rubricCategory.id, 'feedback') }}</td>
              </tr>
              <tr>
                <th scope="row">
                  Overall
                </th>
                <td>{{ totalScore }}/{{ points }}</td>
                <td>
                  <div v-html="overallComments"/>
                </td>
              </tr>
            </table>
          </b-tab>
          <b-tab v-for="(rubricCategory,rubricCategoryIndex) in rubricCategories"
                 :key="`rubric-category-${rubricCategoryIndex}`"
          >
            <template #title>
              {{ rubricCategory.category }}
            </template>
            <ul class="pt-4" style="padding-left:0;list-style:none">
              <li>
                <span class="font-weight-bold">Criteria:</span> {{ rubricCategory.criteria }}
              </li>
              <li>
                <span class="font-weight-bold">Percent of Score:</span> {{ rubricCategory.percent }}%
              </li>
              <li v-if="showScores && !grading">
                <span class="font-weight-bold">
                  Points: </span> {{
                  getRubricCategorySubmissionPoints(rubricCategorySubmissions, rubricCategory.id)
                }}/{{ getRubricCategoryPoints(rubricCategorySubmissions, rubricCategory.id) }}

              </li>
              <li v-if="showScores  && !grading">
                <span class="font-weight-bold">Comments:     </span>
                {{ getRubricCategorySubmissionItem(rubricCategorySubmissions, rubricCategory.id, 'feedback') }}
              </li>
            </ul>
            <b-form-group class="mt-3">
              <div v-if="grading" class="mb-4">
                <b-form-row class="mb-2">
                  <span class="mr-2 mt-1 m-1">Percent</span>
                  <b-form-input
                    v-if="rubricCategory.rubricCategorySubmission"
                    :id="`rubric-category-submission-score-${rubricCategoryIndex}`"
                    v-model="rubricCategory.rubricCategorySubmission.score"
                    :class="{ 'is-invalid': graderErrors.score }"
                    style="width:100px"
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
                            @click="$forceUpdate();saveRubricCategorySubmissionCustomScoreAndFeedback(rubricCategory.rubricCategorySubmission.id, rubricCategory.rubricCategorySubmission.feedback,rubricCategory.rubricCategorySubmission.score, rubricCategory.percent)"
                  >
                    Save
                  </b-button>
                </b-form-row>
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

export default {
  name: 'LabReport',
  components: { ErrorMessage },
  props: {
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
    showScores: false,
    loaded: false,
    missingSections: '',
    submittedSections: [],
    totalPercent: 0,
    totalScore: 0,
    graderErrors: { score: '', feedback: '' },
    rubricCategorySubmissions: []
  }),
  computed: {
    isMe: () => window.config.isMe
  },
  mounted () {
    this.initSections()
  },
  methods: {
    getRubricCategorySubmissionPoints (rubricCategorySubmissions, rubricCategoryId) {
      let score = this.getRubricCategorySubmissionItem(rubricCategorySubmissions, rubricCategoryId, 'score')
      if (score && !isNaN(score)) {
        return this.points * score / 100
      }
    },
    getRubricCategoryPoints (rubricCategorySubmissions, rubricCategoryId) {
      let submission = rubricCategorySubmissions.find(item => item.rubric_category_id === rubricCategoryId)
      return submission ? this.points * submission.percent / 100 : 0
    },
    getRubricCategorySubmissionItem (rubricCategorySubmissions, rubricCategoryId, key) {
      let submission = rubricCategorySubmissions.find(item => item.rubric_category_id === rubricCategoryId)
      if (submission) {
        let customKey = `custom_${key}`
        return submission[customKey]
          ? submission[customKey]
          : submission[key]
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
    async saveRubricCategorySubmissionCustomScoreAndFeedback (rubricCategorySubmissionId, feedback, score, maxPercent) {
      this.graderErrors = { score: '', feedback: '' }
      if (score === '') {
        this.graderErrors.score = `You did not enter a score.`
      }
      if (isNaN(score)) {
        this.graderErrors.score = `${score} is not a number.`
      } else {
        if (score > maxPercent) {
          this.graderErrors.score = `You cannot award more than ${maxPercent}%.`
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
        this.getTotalPercentAndScore()
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
      console.log(openAIResponse)
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
    getTotalPercentAndScore () {
      this.totalPercent = 0
      for (let i = 0; i < this.rubricCategorySubmissions.length; i++) {
        let rubricCategorySubmission = this.rubricCategorySubmissions[i]
        let rubricCategory = this.rubricCategories.find(category => category.id === rubricCategorySubmission.rubric_category_id)
        if (rubricCategory) {
          rubricCategory.rubricCategorySubmission = rubricCategorySubmission
          this.totalPercent += +rubricCategory.rubricCategorySubmission.score
        }
      }
      this.totalScore = (this.totalPercent / 100) * this.points
      this.totalScore = Math.round(this.totalScore * 100) / 100
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
        console.log(data)
        for (let i = 0; i < this.rubricCategories.length; i++) {
          this.rubricCategories[i].rubricCategorySubmission = { submission: '', feedback: '', score: '' }
          this.rubricCategories[i].rubricCategorySubmission = { submission: '', feedback: '', score: '' }
          this.rubricCategories[i].error = ''
        }
        this.submittedSections = []
        this.rubricCategorySubmissions = data.rubric_category_submissions
        for (let i = 0; i < this.rubricCategorySubmissions.length; i++) {
          let rubricCategorySubmission = this.rubricCategorySubmissions[i]
          let rubricCategory = this.rubricCategories.find(category => category.id === rubricCategorySubmission.rubric_category_id)
          if (rubricCategory) {
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
        console.log('sdfdsf')
        console.log(this.rubricCategorySubmissions)
        this.getMissingCategories()
        this.getTotalPercentAndScore()
        this.loaded = true
        this.$forceUpdate()
      } catch (error) {
        this.$noty.error(error.message)
        this.loaded = true
      }
    }
  }
}
</script>
