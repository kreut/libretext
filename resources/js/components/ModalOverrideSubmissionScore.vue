<template>
  <div>
    <ErrorMessage modal-id="modal-form-errors-submission-score-overrides" :all-form-errors="allFormErrors"/>
    <b-modal
      id="modal-override-submission-score"
      title="Override Score"
    >

      <div v-if="Object.keys(activeSubmissionScore).length">
        <ul style="list-style:none; margin-left:0;padding-left:0">
          <li>Student: {{ firstLast }}</li>
          <li>Question: {{ questionTitle }}</li>
          <li>Original Score: {{ originalScore }}</li>
        </ul>
        <RequiredText :plural="false"/>
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
          size="sm"
          variant="info"
          class="float-right"
          @click="viewInOpenGrader()"
          >View in Open Grader</b-button>
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
  </div>
</template>

<script>
import ErrorMessage from '~/components/ErrorMessage.vue'
import Form from 'vform'

export default {
  name: 'ModalOverrideSubmissionScore',
  components: { ErrorMessage },
  props: {
    activeSubmissionScore: {
      type: Object,
      default: () => {
      }
    },
    overrideSubmissionScoreForm: {
      type: Object,
      default: new Form({
        assignment_id: 0,
        question_id: 0,
        student_user_id: 0,
        first_last: '',
        question_title: '',
        score: 0
      })
    },
    firstLast: {
      type: String,
      default: ''
    },
    questionTitle: {
      type: String,
      default: ''
    },
    originalScore: {
      type: String,
      default: ''
    }
  },
  data: () => ({
    submissionScoreOverrides: [],
    originalSubmissionScores: [],
    allFormErrors: []
  }),
  methods:
    {
      viewInOpenGrader() {
        const url= `/assignments/${this.overrideSubmissionScoreForm.assignment_id}/grading/${this.overrideSubmissionScoreForm.question_id}/${this.overrideSubmissionScoreForm.student_user_id}`
        window.open(url, '_blank')
      },
      async updateOverrideSubmissionScore () {
        try {
          const { data } = await this.overrideSubmissionScoreForm.patch('/api/submission-score-overrides')
          this.$noty[data.type](data.message)
          if (data.type === 'success') {
            this.$bvModal.hide('modal-override-submission-score')
            this.$emit('reloadSubmissionScores')
          }
        } catch (error) {
          if (!error.message.includes('status code 422')) {
            this.$noty.error(error.message)
          } else {
            this.allFormErrors = this.overrideSubmissionScoreForm.errors.flatten()
            this.$bvModal.show('modal-form-errors-submission-score-overrides')
          }
        }
      }

    }
}
</script>
