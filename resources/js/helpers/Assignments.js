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

export function isLocked () {
  return Boolean(this.user.role === 2 && (
    (this.assessmentType === 'real time' && this.has_submissions_or_file_submissions) ||
    (this.assessmentType !== 'real time' && (this.has_submissions_or_file_submissions || this.solutionsReleased))))
}
