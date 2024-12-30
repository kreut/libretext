<template>
  <span>
  <QuestionCircleTooltip
    class="text-primary"
    :id="'can_submit_work'"
  />
  <b-tooltip target="can_submit_work"
             delay="250"
             triggers="hover focus"
  >
    For auto-graded assessments, allow students to optionally submit their work to accompany their
    submissions.
    Instructors may then review the work and adjust the auto-graded score if appropriate.
  </b-tooltip>
    </span>
</template>

<script>
export default {
  name: 'CanSubmitWorkTooltip'
}
</script>

<style scoped>

</style>
