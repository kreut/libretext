import axios from 'axios'

export async function getAssignments () {
  try {
    const { data } = await axios.get(`/api/assignments/courses/${this.courseId}`)
    this.isLoading = false
    if (data.type === 'error') {
      this.$noty.error(data.message)
      return false
    }
    this.canViewAssignments = true
    this.hasAssignments = data.assignments.length > 0
    this.showNoAssignmentsAlert = !this.hasAssignments
    this.assignments = data.assignments
  } catch (error) {
    this.$noty.error(error.message)
  }
}

export function isLocked (assignment) {
  // on the assignments index page I have to pass in the specific assignment
  // otherwise, I'm within an assignment.
  if (assignment) {
    assignment.assessmentType = assignment.assessment_type // coming straight from the server so I use underscore
    assignment.solutionsReleased = assignment.solutions_released
  } else {
    assignment = this
  }
  return Boolean(this.user.role === 2 && (
    (assignment.assessmentType !== 'delayed' && assignment.has_submissions_or_file_submissions) ||
    (assignment.assessmentType === 'delayed' && (assignment.has_submissions_or_file_submissions || assignment.solutionsReleased))))
}
