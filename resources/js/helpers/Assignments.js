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
export function isLockedMessage () {
  return `This assignment is locked. Since students have submitted responses,
              the only items that you can update are the assignment's name, the assignment's available/due dates,
              the assignment's group, the instructions, and whether to include the assignment in the final score.  In addition
              you can still add/remove questions.`
}
export function isLocked (assignment) {
  // on the assignments index page I have to pass in the specific assignment
  // otherwise, I'm within an assignment.
  if (!assignment) {
    assignment = this
  }
  return Boolean(this.user.role === 2 && assignment.has_submissions_or_file_submissions)
}
