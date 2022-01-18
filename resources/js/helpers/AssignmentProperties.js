import axios from 'axios'
import Form from 'vform'

export const assignmentForm = new Form({
  name: '',
  assign_tos: [],
  assessment_type: 'real time',
  min_time_needed_in_learning_tree: null,
  percent_earned_for_exploring_learning_tree: null,
  submission_count_percent_decrease: null,
  assignment_group_id: null,
  default_open_ended_submission_type: 0,
  default_completion_scoring_mode: '100% for either',
  completion_split_auto_graded_percentage: '50',
  file_upload_mode: 'compiled_pdf',
  late_policy: 'not accepted',
  late_deduction_percent: null,
  late_deduction_applied_once: 1,
  late_deduction_application_period: null,
  type_of_submission: 'correct',
  randomizations: 0,
  number_of_randomized_assessments: null,
  source: 'a',
  scoring_type: 'p',
  include_in_weighted_average: 1,
  num_submissions_needed: '2',
  default_points_per_question: 10,
  external_source_points: 100,
  instructions: '',
  notifications: 1
})

export async function getAssignmentGroups (courseId, noty, vm) {
  let assignmentGroups = [{ value: null, text: 'Please choose one' }]
  try {
    const { data } = await axios.get(`/api/assignmentGroups/${courseId}`)
    if (data.type === 'error') {
      noty.error(data.message)
      return false
    }
    for (let i = 0; i < data.assignment_groups.length; i++) {
      assignmentGroups.push({
        value: data.assignment_groups[i]['id'],
        text: data.assignment_groups[i]['assignment_group']
      })
    }
    assignmentGroups.push({
      value: -1,
      text: 'Create new group'
    })
  } catch (error) {
    noty.error(error.message)
  }
  return assignmentGroups
}

export function defaultAssignTos (moment, courseStartDate, courseEndDate) {
  return {
    groups: [],
    selectedGroup: null,
    available_from_date: moment(courseStartDate).format('YYYY-MM-DD'),
    available_from_time: '09:00:00',
    due_date: moment(moment(), 'YYYY-MM-DD').format('YYYY-MM-DD'),
    due_time: '09:00:00',
    final_submission_deadline_date: moment(courseEndDate).format('YYYY-MM-DD'),
    final_submission_deadline_time: '09:00:00'
  }
}

export function prepareForm (form) {
  let assignTos = JSON.parse(JSON.stringify(form.assign_tos))

  for (let i = 0; i < form.assign_tos.length; i++) {
    form[`groups_${i}`] = assignTos[i].groups
    form[`available_from_date_${i}`] = assignTos[i].available_from_date
    form[`available_from_time_${i}`] = assignTos[i].available_from_time
    form[`available_from_${i}`] = assignTos[i].available_from_date + ' ' + assignTos[i].available_from_time
    form[`final_submission_deadline_date_${i}`] = assignTos[i].final_submission_deadline_date
    form[`final_submission_deadline_time_${i}`] = assignTos[i].final_submission_deadline_time
    form[`final_submission_deadline_${i}`] = assignTos[i].final_submission_deadline_date + ' ' + assignTos[i].final_submission_deadline_time
    form[`due_date_${i}`] = assignTos[i].due_date
    form[`due_time_${i}`] = assignTos[i].due_time
    form[`due_${i}`] = assignTos[i].due_date + ' ' + assignTos[i].due_time
  }
}

export function resetAssignmentForm (form, assignmentId) {
  form.name = ''
  form.public_description = ''
  form.private_description = ''
  form.available_from_date = ''
  form.available_from_time = '09:00:00'
  form.due_date = ''
  form.due_time = '09:00:00'
  form.type_of_submission = 'correct'
  form.num_submissions_needed = '2'
  form.default_open_ended_submission_type = 'file'
  form.default_points_per_question = '10'
  form.scoring_type = 'p'
  form.default_completion_scoring_mode = '100% for either'

  assignmentId = 0
  form.errors.clear()
}

export function updateModalToggleIndex () {
  // ckeditor fix for input type text --- wasn't able to click
  // https://stackoverflow.com/questions/58482267/ckeditor-i-cant-fill-any-fields-no-focus-on-inputs
  let modalAssignmentProperties = document.querySelectorAll('*[id="modal-assignment-properties___BV_modal_content_"]')[0]
  modalAssignmentProperties.removeAttribute('tabindex')
}

export async function initAddAssignment (form, courseId, assignmentGroups, noty, moment, courseStartDate, courseEndDate, bvModal) {
  form.has_submissions_or_file_submissions = 0
  form.solutionsReleased = 0
  form.scoring_type = 'p'
  form.default_completion_scoring_mode = '100% for either'
  form.completion_split_auto_graded_percentage = '50'
  form.assignment_group_id = null
  form.assign_tos = [defaultAssignTos(moment, courseStartDate, courseEndDate)]
  form.late_policy = 'not accepted'
  form.late_deduction_percent = null
  form.late_deduction_applied_once = 1
  form.late_deduction_application_period = null
  form.source = 'a'
  form.default_points_per_question = 10
  form.default_clicker_time_to_submit = ''
  form.instructions = ''
  form.assessment_type = 'real time'
  form.number_of_allowed_attempts = '1'
  form.number_of_allowed_attempts_penalty = ''
  form.solutions_availability = 'automatic'
  form.file_upload_mode = 'compiled_pdf'
  form.number_of_randomized_assessments = null
  form.randomizations = 0
  form.min_time_needed_in_learning_tree = null
  form.percent_earned_for_exploring_learning_tree = null
  form.submission_count_percent_decrease = null
  form.notifications = 1
  form.assign_tos.selectedGroup = null
  bvModal.show('modal-assignment-properties')
}

export async function editAssignment (assignment) {
  this.originalAssignment = assignment
  this.isBetaAssignment = assignment.is_beta_assignment
  this.hasSubmissionsOrFileSubmissions = assignment.has_submissions_or_file_submissions
  this.solutionsReleased = assignment.solutions_released
  this.assignmentId = assignment.id
  this.number_of_questions = assignment.num_questions
  this.form.default_clicker_time_to_submit = assignment.default_clicker_time_to_submit
  this.form.name = assignment.name
  this.form.solutions_availability = assignment.solutions_availability
  this.form.public_description = assignment.public_description
  this.form.private_description = assignment.private_description
  this.form.assessment_type = this.assessmentType = assignment.assessment_type
  this.form.number_of_allowed_attempts = assignment.number_of_allowed_attempts
  this.form.number_of_allowed_attempts_penalty = assignment.number_of_allowed_attempts_penalty
    ? `${assignment.number_of_allowed_attempts_penalty}%`
    : ''
  this.form.assign_tos = assignment.assign_tos
  for (let i = 0; i < assignment.assign_tos.length; i++) {
    this.form.assign_tos[i].groups = this.form.assign_tos[i].formatted_groups
    this.form.assign_tos[i].selectedGroup = null
  }

  this.form.min_time_needed_in_learning_tree = assignment.min_time_needed_in_learning_tree
  this.form.percent_earned_for_exploring_learning_tree = assignment.percent_earned_for_exploring_learning_tree
  this.form.submission_count_percent_decrease = assignment.submission_count_percent_decrease

  this.form.late_policy = assignment.late_policy
  this.form.late_deduction_applied_once = +(assignment.late_deduction_application_period === 'once')
  this.form.late_deduction_application_period = !this.form.late_deduction_applied_once ? assignment.late_deduction_application_period : ''
  this.form.late_deduction_percent = assignment.late_deduction_percent
  this.form.assignment_group_id = assignment.assignment_group_id
  this.form.include_in_weighted_average = assignment.include_in_weighted_average
  this.form.source = assignment.source
  this.form.instructions = assignment.instructions
  this.form.number_of_randomized_assessments = assignment.number_of_randomized_assessments
  this.form.randomizations = assignment.number_of_randomized_assessments !== null ? 1 : 0
  this.form.type_of_submission = assignment.type_of_submission
  this.form.default_open_ended_submission_type = assignment.default_open_ended_submission_type
  if (assignment.default_open_ended_text_editor) {
    this.form.default_open_ended_submission_type = assignment.default_open_ended_text_editor + ' ' + assignment.default_open_ended_submission_type
  }
  this.form.file_upload_mode = assignment.file_upload_mode
  this.form.num_submissions_needed = assignment.num_submissions_needed
  this.form.default_points_per_question = assignment.default_points_per_question
  this.form.scoring_type = assignment.scoring_type
  if (this.form.scoring_type === 'c') {
    if (assignment.default_completion_scoring_mode === '100% for either') {
      this.form.default_completion_scoring_mode = '100% for either'
    } else {
      this.form.default_completion_scoring_mode = 'split'
      this.form.completion_split_auto_graded_percentage = assignment.default_completion_scoring_mode.replace(/\D/g, '')
    }
  }

  this.form.students_can_view_assignment_statistics = assignment.students_can_view_assignment_statistics
  this.form.external_source_points = assignment.source === 'x' ? assignment.external_source_points : ''
  this.form.libretexts_url = assignment.libretexts_url
  this.form.notifications = assignment.notifications
  this.$bvModal.show('modal-assignment-properties')
}
