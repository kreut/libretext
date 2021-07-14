<template>
  <div>
      <p v-show="betaAssignmentsExist">
        Since this assessment is an Alpha course with tethered Beta courses, by removing this
        assessment, instructors in associated Beta courses will be prompted to approve the removal in their own courses.
      </p>
    <p>
      By removing the question, you will also delete all student submissions for this question. In addition,
      the scores will be re-computed.
    </p>
    <p class="font-weight-bold">
      Once a question is removed, the scores cannot be reverted!
    </p>
  </div>
</template>

<script>
export default {
  name: 'RemoveQuestion',
  props: {
    betaAssignmentsExist: { type: Boolean, default: false }
  }
}
</script>

<style scoped>

</style>
