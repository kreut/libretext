<template>
  <b-modal
    id="modal-cannot-add-assessment-to-beta-assignment"
    ref="modal"
    title="Cannot Add"
    size="sm"
    hide-footer
  >
    This is a Beta assignment.  Since it is tethered to a corresponding assignment in an
    Alpha course, you cannot add assessments to it.
  </b-modal>
</template>

<script>
export default {
  name: 'CannotAddAssessmentToBetaAssignmentModal'
}
</script>

<style scoped>

</style>
