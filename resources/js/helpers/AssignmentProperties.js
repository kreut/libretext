import axios from 'axios'
import Form from 'vform'

function reformatTime (vm, time) {
  return vm.$moment(time, 'HH:mm:ss').format('h:mm A')
}

export const assignmentForm = new Form({
  name: '',
  assign_tos: [],
  assessment_type: 'real time',
  assignment_group_id: null,
  default_open_ended_submission_type: 0,
  default_completion_scoring_mode: '100% for either',
  completion_split_auto_graded_percentage: '50',
  file_upload_mode: 'individual_assessment',
  late_policy: 'not accepted',
  late_deduction_percent: null,
  late_deduction_applied_once: 1,
  late_deduction_application_period: null,
  type_of_submission: 'correct',
  randomizations: 0,
  number_of_randomized_assessments: null,
  // learning tree
  learning_tree_success_level: 'branch',
  learning_tree_success_criteria: 'assessment based',
  min_number_of_successful_assessments: '',
  min_time: '',
  number_of_successful_branches_for_a_reset: '',
  free_pass_for_satisfying_learning_tree_criteria: 1,
  // end learning tree
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
  let url = courseId
    ? `/api/assignmentGroups/${courseId}`
    : '/api/assignmentGroups'

  try {
    const { data } = await axios.get(url)
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

export async function getAssignmentTemplateOptions () {
  try {
    const { data } = await axios.get('/api/assignment-templates')
    if (data.type !== 'success') {
      this.$noty.error(data.message)
      return false
    }
    if (data.assignment_templates.length) {
      this.assignmentTemplateOptions = [{ text: 'Choose an assignment template', value: null }]
      for (let i = 0; i < data.assignment_templates.length; i++) {
        let assignmentTemplate = data.assignment_templates[i]
        this.assignmentTemplateOptions.push({
          text: assignmentTemplate.template_name,
          value: assignmentTemplate.id
        })
      }
    }
  } catch (error) {
    this.$noty.error(error.message)
  }
}

export function defaultAssignTos (moment, courseStartDate, courseEndDate) {
  return {
    groups: [],
    selectedGroup: null,
    available_from_date: moment(courseStartDate).format('YYYY-MM-DD'),
    available_from_time: '9:00 AM',
    due_date: moment(moment(), 'YYYY-MM-DD').format('YYYY-MM-DD'),
    due_time: '9:00 AM',
    final_submission_deadline_date: moment(courseEndDate).format('YYYY-MM-DD'),
    final_submission_deadline_time: '9:00 AM'
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
  form.available_from_time = '9:00 AM'
  form.due_date = ''
  form.due_time = '9:00 AM'
  form.type_of_submission = 'correct'
  form.num_submissions_needed = '2'
  form.default_open_ended_submission_type = 'file'
  form.default_points_per_question = '10'
  form.scoring_type = 'p'
  form.default_completion_scoring_mode = '100% for either'

  assignmentId = 0
  form.errors.clear()
}

export async function initAddAssignment (form, courseId, assignmentGroups, noty, moment, courseStartDate, courseEndDate, bvModal) {
  form.has_submissions_or_file_submissions = 0
  form.solutionsReleased = 0
  form.scoring_type = 'p'
  form.default_completion_scoring_mode = '100% for either'
  form.completion_split_auto_graded_percentage = '50'
  form.assignment_group_id = null
  if (form.is_template) {
    form.assign_to_everyone = 1
  }
  if (!form.is_template) {
    form.assign_tos = [defaultAssignTos(moment, courseStartDate, courseEndDate)]
  }
  form.late_policy = 'not accepted'
  form.late_deduction_percent = null
  form.late_deduction_applied_once = 1
  form.late_deduction_application_period = null
  form.source = 'a'
  form.default_points_per_question = 10
  form.points_per_question = 'number of points'
  form.total_points = ''
  form.default_clicker_time_to_submit = ''
  form.instructions = ''
  form.assessment_type = 'real time'
  form.number_of_allowed_attempts = '1'
  form.number_of_allowed_attempts_penalty = ''
  form.can_view_hint = 0
  form.hint_penalty = ''
  form.algorithmic = 0
  form.solutions_availability = 'automatic'
  form.file_upload_mode = 'individual_assessment'
  form.number_of_randomized_assessments = null
  form.randomizations = 0

  // learning tree
  form.learning_tree_success_level = 'branch'
  form.learning_tree_success_criteria = 'assessment based'
  form.min_number_of_successful_assessments = ''
  form.min_time = ''
  form.number_of_successful_branches_for_a_reset = ''
  form.number_of_resets = '1'
  form.free_pass_for_satisfying_learning_tree_criteria = '1'
  // end learning tree
  form.submission_count_percent_decrease = null
  form.notifications = 1
  form.assign_tos.selectedGroup = null
  if (!form.is_template) {
    bvModal.show('modal-assignment-properties')
  }
}

export async function editAssignmentProperties (assignmentProperties, vm) {
  vm.originalAssignment = assignmentProperties
  vm.isBetaAssignment = assignmentProperties.is_beta_assignment
  vm.overallStatusIsNotOpen = assignmentProperties.overall_status !== 'Open'
  vm.hasSubmissionsOrFileSubmissions = assignmentProperties.has_submissions_or_file_submissions
  vm.solutionsReleased = assignmentProperties.solutions_released
  vm.number_of_questions = assignmentProperties.num_questions
  if (assignmentProperties.is_template) {
    vm.assignmentTemplateId = assignmentProperties.id
    vm.form.template_name = assignmentProperties.template_name
    vm.form.template_description = assignmentProperties.template_description
    vm.form.assign_to_everyone = assignmentProperties.assign_to_everyone
    if (vm.$route.name === 'instructors.assignments.index') {
      if (vm.form.assign_to_everyone) {
        vm.form.assign_tos[0].groups = [{
          value: { course_id: parseInt(vm.courseId) },
          text: 'Everybody'
        }]
      } else {
        vm.form.assign_tos[0].groups = []
      }
      vm.form.assign_tos.length = 1 // just keep the first one in case there were other updates using new templates
      vm.$forceUpdate()
    }
  } else {
    vm.assignmentId = assignmentProperties.id
    vm.form.name = assignmentProperties.name
    vm.form.assign_tos = assignmentProperties.assign_tos
    console.log(assignmentProperties.assign_tos)
    for (let i = 0; i < assignmentProperties.assign_tos.length; i++) {
      vm.form.assign_tos[i].groups = vm.form.assign_tos[i].formatted_groups
      vm.form.assign_tos[i].selectedGroup = null
      if (vm.form.assign_tos[i].available_from_time) {
        vm.form.assign_tos[i].available_from_time = reformatTime(vm, vm.form.assign_tos[i].available_from_time)
      }
      if (vm.form.assign_tos[i].due_time) {
        vm.form.assign_tos[i].due_time = reformatTime(vm, vm.form.assign_tos[i].due_time)
      }
      if (vm.form.assign_tos[i].final_submission_deadline_time) {
        vm.form.assign_tos[i].final_submission_deadline_time = reformatTime(vm, vm.form.assign_tos[i].final_submission_deadline_time)
      }
    }
  }
  vm.form.algorithmic = assignmentProperties.algorithmic
  vm.form.default_clicker_time_to_submit = assignmentProperties.default_clicker_time_to_submit
  vm.form.solutions_availability = assignmentProperties.solutions_availability
  vm.form.public_description = assignmentProperties.public_description
  vm.form.private_description = assignmentProperties.private_description
  vm.form.assessment_type = vm.assessmentType = assignmentProperties.assessment_type
  vm.form.number_of_allowed_attempts = assignmentProperties.number_of_allowed_attempts
  vm.form.number_of_allowed_attempts_penalty = assignmentProperties.number_of_allowed_attempts_penalty !== null
    ? `${assignmentProperties.number_of_allowed_attempts_penalty}%`
    : ''
  vm.form.can_view_hint = parseInt(assignmentProperties.can_view_hint)
  vm.form.hint_penalty = assignmentProperties.hint_penalty !== null
    ? `${assignmentProperties.hint_penalty}%`
    : ''

// learning tree
  vm.form.learning_tree_success_level = assignmentProperties.learning_tree_success_level
  vm.form.min_number_of_successful_assessments = assignmentProperties.min_number_of_successful_assessments
  vm.form.learning_tree_success_criteria = assignmentProperties.learning_tree_success_criteria
  vm.form.number_of_successful_branches_for_a_reset = assignmentProperties.number_of_successful_branches_for_a_reset
  vm.form.number_of_resets = assignmentProperties.number_of_resets
  vm.form.min_time = assignmentProperties.min_time
  vm.form.free_pass_for_satisfying_learning_tree_criteria = assignmentProperties.free_pass_for_satisfying_learning_tree_criteria
// end learning tree
  vm.form.late_policy = assignmentProperties.late_policy
  vm.form.late_deduction_applied_once = +(assignmentProperties.late_deduction_application_period === 'once')
  vm.form.late_deduction_application_period = !vm.form.late_deduction_applied_once ? assignmentProperties.late_deduction_application_period : ''
  vm.form.late_deduction_percent = assignmentProperties.late_deduction_percent
  vm.form.assignment_group_id = assignmentProperties.assignment_group_id
  vm.form.include_in_weighted_average = assignmentProperties.include_in_weighted_average
  vm.form.source = assignmentProperties.source
  vm.form.instructions = assignmentProperties.instructions
  vm.form.number_of_randomized_assessments = assignmentProperties.number_of_randomized_assessments
  vm.form.randomizations = assignmentProperties.number_of_randomized_assessments !== null ? 1 : 0
  vm.form.type_of_submission = assignmentProperties.type_of_submission
  vm.form.default_open_ended_submission_type = assignmentProperties.default_open_ended_submission_type
  if (assignmentProperties.default_open_ended_text_editor) {
    vm.form.default_open_ended_submission_type = assignmentProperties.default_open_ended_text_editor + ' ' + assignmentProperties.default_open_ended_submission_type
  }
  vm.form.file_upload_mode = assignmentProperties.file_upload_mode
  vm.form.num_submissions_needed = assignmentProperties.num_submissions_needed
  vm.form.default_points_per_question = assignmentProperties.default_points_per_question
  vm.form.total_points = assignmentProperties.total_points
  vm.showDefaultPointsPerQuestion = assignmentProperties.points_per_question === 'number of points'
  vm.form.points_per_question = assignmentProperties.points_per_question
  vm.form.scoring_type = assignmentProperties.scoring_type
  if (vm.form.scoring_type === 'c') {
    if (assignmentProperties.default_completion_scoring_mode === '100% for either') {
      vm.form.default_completion_scoring_mode = '100% for either'
    } else {
      vm.form.default_completion_scoring_mode = 'split'
      vm.form.completion_split_auto_graded_percentage = assignmentProperties.default_completion_scoring_mode.replace(/\D/g, '')
    }
  }

  vm.form.students_can_view_assignment_statistics = assignmentProperties.students_can_view_assignment_statistics
  vm.form.external_source_points = assignmentProperties.source === 'x' ? assignmentProperties.external_source_points : ''
  vm.form.textbook_url = assignmentProperties.textbook_url
  vm.form.notifications = assignmentProperties.notifications

  if (!assignmentProperties.modal_already_shown) {
    assignmentProperties.is_template
      ? vm.$bvModal.show('modal-assignment-template')
      : vm.$bvModal.show('modal-assignment-properties')
  }
}
