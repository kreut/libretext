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
    Allow students to optionally submit their work to accompany their
    submissions.
    Instructors may then review the work and adjust the score if appropriate.
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
