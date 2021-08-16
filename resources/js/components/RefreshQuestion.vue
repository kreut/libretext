<template>
  <div>
    <RefreshQuestionOrDenyRequest ref="refreshQuestionOrDenyRequest"/>
    <b-modal
      id="modal-question-has-submissions-in-other-assignments"
      ref="modalSubmissionsInOtherAssignment"
      title="Submissions in other assignments"
      size="lg"
    >
      <p>
        This question already has submissions in other assignments so the only type of accepted updates are ones which don't
        cause additional confusion for the students. After reviewing the nature of your update, our Admin will email you to
        let you know whether this question can be updated in Adapt.
      </p>
      <b-form-group
        id="nature_of_update"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Nature of Update"
        label-for="Nature of Update"
      >
        <b-form-textarea
          id="description"
          v-model="natureOfUpdateForEditForm.nature_of_update"
          type="text"
          :class="{ 'is-invalid': natureOfUpdateForEditForm.errors.has('nature_of_update') }"
          rows="3"
          @keydown="natureOfUpdateForEditForm.errors.clear('nature_of_update')"
        />
        <has-error :form="natureOfUpdateForEditForm" field="nature_of_update"/>
      </b-form-group>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-question-has-submissions-in-other-assignments');processingQuestionRefresh=false"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="requestApprovalForEdit()"
        >
          Submit
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-question-has-submissions-in-this-assignment"
      ref="modalSubmissionsInThisAssignment"
      title="Question Used In Assignment"
    >
      <p>
        You are trying to refresh a question in one of your assignments that already has student submissions. If this is a material
        change, we can refresh the question and remove current student submissions.</p>
      <p>However, if the change is purely cosmetic, we can refresh while leaving student submissions intact.
      </p>
      <b-form-group
        id="refresh_option"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Refresh Option"
        label-for="Refresh Option"
      >
        <b-form-radio-group class="mt-2"
          v-model="refreshAndRemoveStudentSubmissions"
          stacked
        >
          <b-form-radio :value="true">
            Refresh and remove student submissions
          </b-form-radio>
          <b-form-radio :value="false">
            Refresh but do not remove student submissions
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-alert :show="refreshAndRemoveStudentSubmissions">
        <span class="font-weight-bold font-italic">Removing student submissions cannot be undone</span>
      </b-alert>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-question-has-submissions-in-this-assignment')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          :disabled="processingQuestionRefresh"
          @click="processingQuestionRefresh=true;refreshQuestion(refreshAndRemoveStudentSubmissions)"
        >
          <span v-if="!processingQuestionRefresh">Refresh Question</span>
          <span v-if="processingQuestionRefresh"><b-spinner small type="grow"/>
        Refreshing...
      </span>
        </b-button>
      </template>
    </b-modal>
    <b-button
      class="mt-2 mb-2 mr-2"
      variant="outline-primary"
      size="sm"
      @disabled="processingQuestionRefresh"
      @click="initRefreshQuestionQuestion"
    >
      <span v-if="!processingQuestionRefresh">Refresh Question</span>
      <span v-if="processingQuestionRefresh"><b-spinner small type="grow"/>
        Refreshing...
      </span>
    </b-button>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform/src'
import RefreshQuestionOrDenyRequest from './RefreshQuestionOrDenyRequest'

export default {
  components: {
    RefreshQuestionOrDenyRequest
  },
  props: {
    assignmentId: {
      type: Number,
      default: 0
    },
    questionId: {
      type: Number,
      default: 0
    },
    reloadQuestionParent: {
      type: Function,
      default: function () {
      }
    }
  },
  data: () => ({
    refreshAndRemoveStudentSubmissions: true,
    natureOfUpdateForEditForm: new Form({
      nature_of_update: ''
    }),
    processingQuestionRefresh: false
  }),
  computed: {
    isAdmin: () => window.config.isAdmin
  },
  methods: {
    async requestApprovalForEdit () {
      try {
        const { data } = await this.natureOfUpdateForEditForm.post(`/api/refresh-question-requests/make-refresh-question-request/${this.questionId}`)
        this.$noty[data.type](data.message)
        this.$bvModal.hide('modal-question-has-submissions-in-other-assignments')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
          this.$bvModal.hide('modal-question-has-submissions-in-other-assignments')
        }
      }
    },
    async refreshQuestion (updateScores = false) {
      this.processingQuestionRefresh = true
      try {
        const { data } = await axios.post(`/api/questions/${this.questionId}/refresh/${this.assignmentId}`,
          { update_scores: updateScores })
        this.processingQuestionRefresh = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        await this.reloadQuestionParent(this.questionId, data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.processingQuestionRefresh = false
    },
    async initRefreshQuestionQuestion () {
      this.natureOfUpdateForEditForm.nature_of_update = ''
      try {
        const { data } = await axios.post(`/api/assignments/${this.assignmentId}/questions/${this.questionId}/init-refresh-question`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        if (data.question_has_auto_graded_or_file_submissions_in_other_assignments) {
          if (this.isAdmin) {
            await this.$refs.refreshQuestionOrDenyRequest.getQuestionsToCompare(this.questionId, 'pending')
            this.$bvModal.show('modal-refresh-question-or-deny-request')
          } else {
            this.$bvModal.show('modal-question-has-submissions-in-other-assignments')
          }
          return false
        }
        if (data.question_has_auto_graded_or_file_submissions_in_this_assignment) {
          this.$bvModal.show('modal-question-has-submissions-in-this-assignment')
          return false
        }

        await this.refreshQuestion()
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
