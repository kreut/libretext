<template>
  <b-modal id="modal-redirect-to-clicker"
           title="Poll Starting"
           no-close-on-backdrop
           no-auto-focus
  ><p>You have been invited to a poll by your instructor, {{ instructorName }} in your course "{{ courseName }}". You
    may either ignore this invitation or we can take you to the poll.
  </p>
    <template #modal-footer>
      <b-button
        size="sm"
        variant="danger"
        class="float-right"
        @click="$bvModal.hide('modal-redirect-to-clicker')"
      >
        Ignore Invitation
      </b-button>
      <b-button
        size="sm"
        variant="primary"
        class="float-right"
        @click="redirectToClickerQuestion()"
      >
        Go To Poll
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import axios from 'axios'

export default {
  name: 'RedirectToClickerModal',
  props: {
    showModal: {
      type: Boolean,
      default: true
    },
    currentAssignmentId: {
      type: Number,
      default: 0
    },
    currentQuestionId: {
      type: Number,
      default: 0
    },
    assignmentId: {
      type: Number,
      default: 0
    },
    questionId: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    courseName: '',
    instructorName: ''
  }),
  mounted () {
    if (this.showModal && this.assignmentId && this.questionId) {
      this.deferModalOpen()
    }
  },
  methods: {
    deferModalOpen () {
      this.$nextTick(() => {
        requestAnimationFrame(() => {
          // This double-defer ensures DOM is fully stable before modal opens
          requestAnimationFrame(() => {
            this.showRedirectToClickerModal()
          })
        })
      })
    },
    redirectToClickerQuestion () {
      window.location.href = `/assignments/${this.assignmentId}/questions/view/${this.questionId}`
    },
    async showRedirectToClickerModal () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.courseName = data.assignment.course_name
        this.instructorName = data.assignment.instructor_name
        this.$nextTick(() => {
          this.$bvModal.show('modal-redirect-to-clicker')
        })
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

