<template>
  <div>
    <b-modal id="modal-show-revision"
             :key="`modal-show-revision-${differences.length}`"
             title="Updated Version Available"
             size="xl"
             @show="renderMathJax"
    >
      <b-card header-html="<h2 class=&quot;h7&quot;>Reason For Edit</h2>">
        {{ reasonForEdit }}
      </b-card>
      <hr>
      <table class="table table-striped">
        <thead>
        <tr>
          <th>Property</th>
          <th>Current Version</th>
          <th>Revised Version</th>
        </tr>
        </thead>
        <tr v-for="(difference,differenceIndex) in differences" :key="`difference-${differenceIndex}`">
          <td>{{ difference.property }}</td>
          <td>
            <div v-html="difference.currentQuestion"/>
          </td>
          <td>
            <div v-html="difference.pendingQuestionRevision"/>
          </td>
        </tr>
      </table>
      <b-alert variant="danger" show>
      <b-form-checkbox
        id="checkbox-1"
        v-model="understandStudentSubmissionsRemoved"
        name="student_submissions_removed"
        :value="true"
        :unchecked-value="false"
      >
        I understand that student submissions for this question will be removed. Please inform your class to resubmit.
      </b-form-checkbox>
      </b-alert>
      <template #modal-footer="{ cancel, ok }">
        <b-button size="sm"
                  variant="primary"
                  @click="updateTheQuestionRevision"
        >
          Update
        </b-button>
        <b-button size="sm"
                  @click="$bvModal.hide('modal-show-revision')"
        >
          Cancel
        </b-button>
      </template>
    </b-modal>

    <b-alert show variant="secondary" class="text-center">
      <h5>
        The current question has a <a href="" @click.prevent="showRevision">significant revision</a> associated with
        it.
      </h5>
    </b-alert>
  </div>
</template>

<script>
import axios from 'axios'
import { labelMapping } from '~/helpers/Revisions'

export default {
  name: 'UpdateRevision',
  props: {
    assignmentId: {
      type: Number,
      default: 0
    },
    currentQuestion: {
      type: Object,
      default: () => {
      }
    },
    pendingQuestionRevision: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    differences: [],
    reasonForEdit: '',
    understandStudentSubmissionsRemoved: false
  }),
  mounted () {
    for (const property in this.pendingQuestionRevision) {
      if (property === 'reason_for_edit') {
        this.reasonForEdit = this.pendingQuestionRevision.reason_for_edit
      }
      if (this.pendingQuestionRevision[property] !== this.currentQuestion[property] &&
        (this.pendingQuestionRevision[property] || this.currentQuestion[property])) {
        if (!['created_at', 'updated_at', 'revision_number', 'reason_for_edit', 'technology_iframe', 'action', 'id'].includes(property)) {
          this.differences.push({
            property: labelMapping[property] ? labelMapping[property] : property,
            currentQuestion: this.currentQuestion[property],
            pendingQuestionRevision: this.pendingQuestionRevision[property]
          })
        }
      }
    }
    console.log(this.differences)
  },
  methods: {
    async updateTheQuestionRevision () {
      if (!this.understandStudentSubmissionsRemoved) {
        this.$noty.info('Please check the box stating that you understand that all existing student submissions will be removed and their assignment scores will be updated.')
        return false
      }
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/question/${this.currentQuestion.id}/update-to-latest-revision`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.$bvModal.hide('modal-show-revision')
        this.$emit('reloadSingleQuestion')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    renderMathJax () {
      this.$nextTick(() => {
        MathJax.Hub.Queue(['Typeset', MathJax.Hub])
      })
    },
    showRevision () {
      this.understandStudentSubmissionsRemoved = 0
      this.$bvModal.show('modal-show-revision')
    }

  }
}
</script>
