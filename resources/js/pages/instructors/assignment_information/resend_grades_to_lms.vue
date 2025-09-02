<template>
  <div>
    <PageTitle title="Resend Grades to LMS"/>
    <p>There are 2 situations where you may want to manually resend ADAPT grades to your LMS.</p>
    <ol>
      <li>You accidentally set the number of allowed to submissions in your Canvas assignment to something other than
        "unlimited" (in this case
        students will only see partial scores since ADAPT passes back grades after each question submission).
      </li>
      <li>You’ve changed the point value of your Canvas assignment.
        Since ADAPT sends Canvas the proportion correct multiplied by the assignment’s total points (Canvas view),
        scores will need to be recalculated to reflect the new point value.
      </li>
    </ol>
    <b-button variant="primary"
              size="sm"
              @click="resendGradesToLMS()"
    >
      Resend Grades to LMS
    </b-button>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data: () => ({
    assignmentId: 0
  }),
  mounted () {
    this.assignmentId = this.$route.params.assignmentId
  },
  methods: {
    async resendGradesToLMS () {
      try {
        const { data } = await axios.post(`/api/passback-by-assignment/${this.assignment.id}`)
        this.$noty[data.type](data.message, { timeout: 7000 })
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
