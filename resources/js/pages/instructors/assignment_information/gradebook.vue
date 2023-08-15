<template>
  <div>
    <div class="vld-parent">
      <ErrorMessage modal-id="modal-form-errors-submission-score-overrides" :all-form-errors="allFormErrors"/>
      <b-modal
        id="modal-override-submission-score"
        title="Override Score"
      >
        <div v-if="activeSubmissionScore.field">
          <ul style="list-style:none; margin-left:0;padding-left:0">
            <li>Question: {{ activeSubmissionScore.field.label }}</li>
            <li>Student: {{ firstLast }}</li>
          </ul>
          <b-form-group
            label-cols-sm="3"
            label-cols-lg="2"
            label-for="score"
            label="Score*"
          >
            <b-form-input
              id="score"
              v-model="overrideSubmissionScoreForm.score"
              style="width: 100px"
              required
              type="text"
              :class="{ 'is-invalid':overrideSubmissionScoreForm.errors.has('score') }"
              @keydown="overrideSubmissionScoreForm.errors.clear('score')"
            />
            <has-error :form="overrideSubmissionScoreForm" field="score"/>
          </b-form-group>
        </div>
        <template #modal-footer>
          <b-button
            size="sm"
            class="float-right"
            @click="$bvModal.hide('modal-override-submission-score')"
          >
            Cancel
          </b-button>
          <b-button
            variant="primary"
            size="sm"
            class="float-right"
            @click="updateOverrideSubmissionScore"
          >
            Save
          </b-button>
        </template>
      </b-modal>
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
            <span v-if="['name'].includes(data.field.key)">
              {{ data.value }}
            </span>
            <span v-if="!['name'].includes(data.field.key)"
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
import ErrorMessage from '~/components/ErrorMessage.vue'

export default {
  middleware: 'auth',
  components: {
    ErrorMessage,
    Loading,
    TimeSpent
  },
  metaInfo () {
    return { title: 'Assignment Gradebook' }
  },
  data: () => ({
    allFormErrors: [],
    activeSubmissionScore: {},
    userId: 0,
    assignmentId: 0,
    fields: [],
    items: [],
    isLoading: true,
    showTimeSpent: false,
    toggleColors: window.config.toggleColors,
    overrideSubmissionScoreForm: new Form({
      assignment_id: 0,
      question_id: 0,
      student_user_id: 0,
      first_last: '',
      question_title: '',
      score: 0 }),
    firstLast: ''
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
    async updateOverrideSubmissionScore () {
      try {
        const { data } = await this.overrideSubmissionScoreForm.patch('/api/submission-score-overrides')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.$bvModal.hide('modal-override-submission-score')
          await this.getAssignmentQuestionScoresByUser(0)
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.overrideSubmissionScoreForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-submission-score-overrides')
        }
      }
    },
    initOverrideSubmissionScore (obj) {
      return false
      this.activeSubmissionScore = obj
      this.firstLast = this.activeSubmissionScore.item.name
      if (this.activeSubmissionScore.item.name.includes(',')) {
        let lastFirst = this.activeSubmissionScore.item.name.split(',')
        this.firstLast = lastFirst[1] + ' ' + lastFirst[0]
      }
      this.overrideSubmissionScoreForm = new Form({
        assignment_id: this.assignmentId,
        question_id: obj.field.key,
        student_user_id: obj.item.userId,
        question_title: this.activeSubmissionScore.field.label,
        first_last: this.firstLast,
        score: obj.item[obj.field.key]

      })
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
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
