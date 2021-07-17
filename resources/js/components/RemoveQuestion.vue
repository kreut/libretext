<template>
  <div>
    <b-alert :show="betaAssignmentsExist" variant="danger">
      <div class="font-weight-bold font-italic">
        <p>
          Since this assessment is an Alpha course with tethered Beta courses, by removing this
          assessment, you will delete all student scores associated with this assessment in the Alpha course
          as well as in the associated Beta courses.
        </p>
        <p>This action cannot be undone!</p>
      </div>
    </b-alert>
    <div v-show="!betaAssignmentsExist"><p>
      By removing the question, you will also delete all student submissions for this question. In addition,
      the scores will be re-computed.
    </p>
      <p><strong>Once a question is removed, the scores cannot be reverted!</strong></p>
    </div>
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
