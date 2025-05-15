<template>
  <b-modal id="modal-redirect-to-clicker"
           title="Poll Starting"
           no-close-on-backdrop
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
  async mounted () {
    if (this.assignmentId && this.questionId) {
      await this.getCourseInstructor()
      this.$bvModal.show('modal-redirect-to-clicker')
    }
  },
  methods: {
    redirectToClickerQuestion () {
      window.location.href = `/assignments/${this.assignmentId}/questions/view/${this.questionId}`
    },
    async getCourseInstructor () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.courseName = data.assignment.course_name
        this.instructorName = data.assignment.instructor_name
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

