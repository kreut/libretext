import _ from 'lodash'

function page (path) {
  return () => import(/* webpackChunkName: '' */ `~/pages/${path}`).then(m => m.default || m)
}

let student_paths  = [
  { path: '/students/courses', name: 'students.courses.index', component: page('students/courses.index.vue')},
  { path: '/students/assignments/:assignmentId/summary', name: 'students.assignments.summary', component: page('students/assignments.summary.vue') },
  { path: '/students/courses/:courseId/assignments', name: 'students.assignments.index', component: page('students/assignments.index.vue') },
]

let instructor_paths = [
  { path: '/instructors/link-assignment-to-lms/:resourceLinkId', name: 'link_assignment_to_lms', component: page('instructors/link_assignment_to_lms.vue') },
  { path: '/assignments/:assignmentId/questions/get', name: 'questions.get', component: page('instructors/questions.get.vue') },
  { path: '/assignments/:assignmentId/learning-trees/get', name: 'learning_trees.get', component: page('instructors/learning_trees.get.vue') },
  { path: '/learning-trees/:learningTreeId/get', name: 'learning_tree.get', component: page('instructors/learning_tree.get.vue') },
  { path: '/instructors/learning-trees/editor/:learningTreeId', name: 'instructors.learning_trees.editor', component: page('instructors/learning_trees.editor.vue') },
  { path: '/instructors/learning-trees', name: 'instructors.learning_trees.index', component: page('instructors/learning_trees.index.vue') },
  { path: '/assignments/:assignmentId/questions/:questionId/view', name: 'question.view', component: page('instructors/question.view.vue') },
  { path: '/assignments/:assignmentId/grading/:questionId?/:studentUserId?', name: 'assignment.grading.index', component: page('instructors/assignments.grading.vue') },
  { path: '/instructors/courses', name: 'instructors.courses.index', component: page('instructors/courses.index.vue') },
  { path: '/courses/:courseId/gradebook', name: 'gradebook.index', component: page('instructors/gradebook.index.vue') },
  { path: '/instructors/courses/:courseId/assignments', name: 'instructors.assignments.index', component: page('instructors/assignments.index.vue') }
]
let admin_paths = [

  { path: '/login-as', name: 'login.as', component: page('admin/login.as.vue') }
]
let general_paths  = [
  { path: '/init-lms-assignment/:assignmentId', name: 'init_lms_assignment', component: page('init_lms_assignment.vue') },
  { path: '/question-in-iframe', name: 'question_in_iframe', component: page('iframe_test.vue') },
  { path: '/assignments/:assignmentId/questions/view/:questionId?/:shownSections?', name: 'questions.view', component: page('questions.view.vue') },
  { path: '/submission', name: 'submission.index', component: page('submission.store.vue') },
  { path: '/', name: 'welcome', component: page('welcome.vue') },
  { path: '/login', name: 'login', component: page('auth/login.vue') },
  { path: '/finish-sso-registration', name: 'finish.sso.registration', component: page('auth/finish.sso.registration.vue') },
  { path: '/register/instructor', name: 'register', component: page('auth/register.vue'), alias: ['/register/student','/register/grader'] },
  { path: '/password/reset', name: 'password.request', component: page('auth/password/email.vue') },
  { path: '/password/reset/:token', name: 'password.reset', component: page('auth/password/reset.vue') },
  { path: '/email/verify/:id', name: 'verification.verify', component: page('auth/verification/verify.vue') },
  { path: '/email/resend', name: 'verification.resend', component: page('auth/verification/resend.vue') },
  { path: '/home', name: 'home', component: page('instructors/courses.index.vue') },
  { path: '/settings',
    component: page('settings/index.vue'),
    children: [
      { path: '', redirect: { name: 'settings.profile' } },
      { path: 'profile', name: 'settings.profile', component: page('settings/profile.vue') },
      { path: 'password', name: 'settings.password', component: page('settings/password.vue') },
      { path: 'notifications', name: 'settings.notifications', component: page('settings/notifications.vue') }
    ] },
  { path: '/instructors/assignments/:assignmentId/information',
    component: page('instructors/assignment_information/index.vue'),
    children: [
      { path: '', redirect: { name: 'instructors.assignments.questions' } },
      { path: 'questions', name: 'instructors.assignments.questions', component: page('instructors/assignment_information/questions.vue') },
      { path: 'summary', name: 'instructors.assignments.summary', component: page('instructors/assignment_information/summary.vue') },
      { path: 'properties', name: 'instructors.assignments.properties', component: page('instructors/assignment_information/properties.vue') },
      { path: 'control_panel', name: 'instructors.assignments.control_panel', component: page('instructors/assignment_information/control_panel.vue') },
      { path: 'submissions', name: 'instructors.assignments.submissions', component: page('instructors/assignment_information/submissions.vue') },
      { path: 'grader-access', name: 'instructors.assignments.grader_access', component: page('instructors/assignment_information/grader_access.vue') },
      { path: 'statistics', name: 'instructors.assignments.statistics', component: page('instructors/assignment_information/statistics.vue') },
      { path: 'gradebook', name: 'instructors.assignments.gradebook', component: page('instructors/assignment_information/gradebook.vue') },

    ] },
  { path: '/instructors/courses/:courseId/properties',
    component: page('instructors/course_properties/index.vue'),
    children: [
      { path: '', redirect: { name: 'course_properties.general_info' } },
      { path: 'details', name: 'course_properties.general_info', component: page('instructors/course_properties/general_info.vue') },
      { path: 'sections', name: 'course_properties.sections', component: page('instructors/course_properties/sections.vue') },
      { path: 'letter-grades', name: 'course_properties.letter_grades', component: page('instructors/course_properties/letter_grades.vue') },
      { path: 'assignment-group-weights', name: 'course_properties.assignment_group_weights', component: page('instructors/course_properties/assignment_group_weights.vue') },
      { path: 'graders', name: 'course_properties.graders', component: page('instructors/course_properties/graders.vue') },
      { path: 'ungraded-submissions', name: 'course_properties.ungraded_submissions', component: page('instructors/course_properties/ungraded_submissions.vue') },
      { path: 'grader-notifications', name: 'course_properties.grader_notifications', component: page('instructors/course_properties/grader_notifications.vue') },
      { path: 'students', name: 'course_properties.students', component: page('instructors/course_properties/students.vue') }
    ] },
  { path: '*', component: page('errors/404.vue') }
]

export default _.concat( [{ path: '/h5p', name: 'h5p', component: page('h5p.vue') }], general_paths, student_paths, instructor_paths, admin_paths)
