<template>
  <b-modal
    id="modal-cannot-delete-assessment-from-beta-assignment"
    ref="modal"
    title="Cannot Delete"
    size="sm"
    hide-footer
  >
    This assessments is part of a Beta assignment.  Since it is tethered to a corresponding assessment in an
    Alpha course, it cannot be deleted.
  </b-modal>
</template>

<script>
export default {
  name: 'CannotDeleteAssessmentFromBetaAssignmentModal'
}
</script>

<style scoped>

</style>
