<template>
  <div>
    <div class="vld-parent">
      <ModalOverrideSubmissionScore :active-submission-score="activeSubmissionScore"
                                    :override-submission-score-form="overrideSubmissionScoreForm"
                                    :first-last="firstLast"
                                    :original-score="originalScore.toString()"
                                    :question-title="questionTitle"
                                    @reloadSubmissionScores="getAssignmentQuestionScoresByUser(0)"
      />
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading">
        <PageTitle title="Assignment Gradebook"/>
        <p class="pb-2">
          Below is a table of the scores at the question level for each student. You can override individual question scores by clicking on any of the entries.
        </p>
        <a
          class="float-right mb-2 btn-sm btn-primary link-outline-primary-btn"
          :href="`/api/scores/assignment/get-assignment-questions-scores-by-user/${assignmentId}/0/1`"
        >
          Download Scores
        </a>
        <TimeSpent @updateView="getAssignmentQuestionScoresByUser"/>
        <b-table
          v-show="items.length"
          aria-label="Assignment Gradebook"
          striped
          hover
          responsive
          sticky-header="800px"
          :no-border-collapse="true"
          :fields="fields"
          :items="items"
        >
          <template v-slot:cell()="data">
            <span v-if="nonEditableFields.includes(data.field.key)">
              {{ data.value }}
            </span>
            <span v-if="!nonEditableFields.includes(data.field.key)"
                  style="cursor:pointer"
                  :class="isSubmissionScoreOverride(data) ? 'text-info' : ''"
                  @click="initOverrideSubmissionScore(data)"
            >{{ data.value }}
            </span>
          </template>
        </b-table>
        <b-alert :show="!items.length">
          <span class="font-weight-bold">
            This course has no enrolled users so there is no gradebook to show.
          </span>
        </b-alert>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import TimeSpent from '~/components/TimeSpent'
import { mapGetters } from 'vuex'
import Form from 'vform'
import ModalOverrideSubmissionScore from '~/components/ModalOverrideSubmissionScore.vue'

export default {
  middleware: 'auth',
  components: {
    ModalOverrideSubmissionScore,
    Loading,
    TimeSpent
  },
  metaInfo () {
    return { title: 'Assignment Gradebook' }
  },
  data: () => ({
    nonEditableFields: ['name', 'total_points', 'percent_correct'],
    questionTitle: '',
    originalScore: '',
    firstLast: '',
    activeSubmissionScore: {},
    overrideSubmissionScoreForm: new Form({
      assignment_id: 0,
      question_id: 0,
      student_user_id: 0,
      first_last: '',
      question_title: '',
      score: 0
    }),
    assignmentId: 0,
    fields: [],
    items: [],
    isLoading: true,
    showTimeSpent: false,
    toggleColors: window.config.toggleColors
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentQuestionScoresByUser(0)
  },
  methods: {
    isSubmissionScoreOverride (obj) {
      return this.submissionScoreOverrides.filter(item => +item.user_id === +obj.item.userId && +item.question_id === +obj.field.key).length > 0
    },
    getOriginalSubmissionScore (obj) {
      const studentUserId = obj.item.userId
      const questionId = obj.field.key
      const submittedItem = this.originalSubmissionScores.find(item => +item.user_id === +studentUserId && +item.question_id === +questionId)
      return submittedItem ? submittedItem.original_score : '-'
    },
    initOverrideSubmissionScore (obj) {
      this.activeSubmissionScore = obj
      this.firstLast = this.activeSubmissionScore.item.name
      if (this.activeSubmissionScore.item.name.includes(',')) {
        let lastFirst = this.activeSubmissionScore.item.name.split(',')
        this.firstLast = lastFirst[1] + ' ' + lastFirst[0]
      }

      let score = ''
      if (obj.item[obj.field.key].trim() !== '-') {
        score = obj.item[obj.field.key]
        score = score.slice(0, score.indexOf(' ')) // if the user is looking at the timing, don't show this
      }
      this.overrideSubmissionScoreForm = new Form({
        assignment_id: this.assignmentId,
        question_id: obj.field.key,
        student_user_id: obj.item.userId,
        first_last: this.firstLast,
        question_title: this.questionTitle,
        score: score
      })
      this.originalScore = this.getOriginalSubmissionScore(obj)
      this.questionTitle = obj.field.label
      this.$bvModal.show('modal-override-submission-score')
    },
    async getAssignmentQuestionScoresByUser (timeSpent) {
      try {
        const { data } = await axios.get(`/api/scores/assignment/get-assignment-questions-scores-by-user/${this.assignmentId}/${timeSpent}/0`)
        console.log(data)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.items = data.rows
        this.fields = data.fields
        this.submissionScoreOverrides = data.submission_score_overrides
        this.originalSubmissionScores = data.original_submission_scores
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
