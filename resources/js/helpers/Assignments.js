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
  let message = `<p>This assignment is locked. Since students have submitted responses,
              the only items that you can update are the assignment's name, the assignment's available/due dates,
              the assignment's group, the instructions, and whether to include the assignment in the final score.</p>`
  if (!this.overallStatusIsNotOpen && !this.showDefaultPointsPerQuestion) {
    message += '<p>Once the assignment is closed, you will also be able to update the Total Assignment Points.</p>'
  }
  return message
}

export function isLocked (hasSubmissionsOrFileSubmissions) {
  return Boolean(this.user.role === 2 && hasSubmissionsOrFileSubmissions)
}

export function initAssignmentGroupOptions (assignments) {
  let assignmentGroupTexts = []
  this.assignmentGroupOptions = [{ value: null, text: 'All assignment groups' }]
  let numAssignmentGroups = 1
  for (let i = 0; i < assignments.length; i++) {
    let text = this.assignments[i].assignment_group
    let assignmentGroup = { value: numAssignmentGroups, text: text }
    if (!assignmentGroupTexts.includes(text)) {
      numAssignmentGroups++
      this.assignmentGroupOptions.push(assignmentGroup)
      assignmentGroupTexts.push(text)
    }
  }
}

export function updateAssignmentGroupFilter (courseId) {
  for (let i = 0; i < this.assignmentGroupOptions.length; i++) {
    if (this.assignmentGroupOptions[i].value === this.chosenAssignmentGroup) {
      this.chosenAssignmentGroupText = this.assignmentGroupOptions[i].text
    }
  }
  try {
    axios.patch(`/api/cookie/set-assignment-group-filter/${courseId}/${this.chosenAssignmentGroup}`)
  } catch (error) {
    console.log(error)
  }
}
