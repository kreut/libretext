import _ from 'lodash'

function page (path) {
  return () => import(/* webpackChunkName: '' */ `~/pages/${path}`).then(m => m.default || m)
}

let student_paths = [
  { path: '/students/sitemap', name: 'students.sitemap', component: page('students/sitemap.vue') },
  { path: '/students/courses', name: 'students.courses.index', component: page('students/courses.index.vue') },
  {
    path: '/students/assignments/:assignmentId/summary',
    name: 'students.assignments.summary',
    component: page('students/assignments.summary.vue')
  },
  {
    path: '/students/courses/:courseId/assignments',
    name: 'students.assignments.index',
    component: page('students/assignments.index.vue')
  },
  {
    path: '/students/courses/:courseId/assignments/anonymous-user',
    name: 'students.assignments.anonymous.user.index',
    component: page('students/assignments.anonymous.user.index.vue')
  },
]

let instructor_paths = [
  { path: '/instructors/sitemap', name: 'instructors.sitemap', component: page('instructors/sitemap.vue') },
  {
    path: '/instructors/link-assignment-to-lms/:lmsResourceLinkId',
    name: 'link_assignment_to_lms',
    component: page('instructors/link_assignment_to_lms.vue')
  },
  {
    path: '/assignments/:assignmentId/questions/get',
    name: 'questions.get',
    component: page('instructors/questions.get.vue')
  },
  {
    path: '/all-questions/get',
    name: 'all.questions.get',
    component: page('instructors/all_questions.get.vue')
  },
  {
    path: '/assignments/:assignmentId/learning-trees/get',
    name: 'learning_trees.get',
    component: page('instructors/learning_trees.get.vue')
  },
  {
    path: '/learning-trees/:learningTreeId/get',
    name: 'learning_tree.get',
    component: page('instructors/learning_tree.get.vue')
  },
  {
    path: '/instructors/learning-trees/editor/:learningTreeId?',
    name: 'instructors.learning_trees.editor',
    component: page('instructors/learning_trees.editor.vue')
  },
  {
    path: '/instructors/learning-trees',
    name: 'instructors.learning_trees.index',
    component: page('instructors/learning_trees.index.vue')
  },
  {
    path: '/assignments/:assignmentId/questions/:questionId/view',
    name: 'question.view',
    component: page('instructors/question.view.vue')
  },
  {
    path: '/assignments/:assignmentId/grading/:questionId?/:studentUserId?',
    name: 'assignment.grading.index',
    component: page('instructors/assignments.grading.vue')
  },
  {
    path: '/assignments/:assignmentId/mass-grading',
    name: 'assignment.mass_grading.index',
    component: page('instructors/assignments.mass_grading.vue')
  },

  { path: '/instructors/courses', name: 'instructors.courses.index', component: page('instructors/courses.index.vue') },
  { path: '/courses/:courseId/gradebook', name: 'gradebook.index', component: page('instructors/gradebook.index.vue') },
  {
    path: '/instructors/courses/:courseId/assignments',
    name: 'instructors.assignments.index',
    component: page('instructors/assignments.index.vue')
  }
]
let control_panel_paths = [
  {
    path: '/control-panel',
    component: page('control_panel/index.vue'),
    children: [
      { path: '', redirect: { name: 'login.as' } },
      { path: 'login-as', name: 'login.as', component: page('control_panel/login.as.vue') },
      {
        path: 'refresh-question-requests',
        name: 'refresh.question.requests',
        component: page('control_panel/refresh.question.requests.vue')
      },
      { path: 'lti-integrations', name: 'lti.integrations', component: page('control_panel/lti.integrations.vue') },
      {
        path: 'instructor-access-codes',
        name: 'instructorAccessCodes',
        component: page('control_panel/instructor.access.codes.vue')
      },
      { path: 'question-editors', name: 'questionEditors', component: page('control_panel/question.editors.vue') },
      {
        path: 'courses-to-reset',
        name: 'coursesToReset',
        component: page('control_panel/courses.to.reset.vue')
      }
    ]
  }]
let general_paths = [
  { path: '/launch-in-new-window/:ltiTokenId/:ltiFinalLocation/:ltiResourceId', name: 'launchInNewWindow', component: page('launch_in_new_window.vue')},
  { path: '/lti-login', name: 'ltiLogin', component: page('lti_login.vue')},
  { path: '/incomplete-registration', name: 'incomplete.registration', component: page('incomplete_registration.vue') },
  { path: '/sitemap', name: 'sitemap', component: page('sitemap.vue') },
  { path: '/question-editor/:tab/:questionId?', name: 'question.editor', component: page('question_editor.vue') },
  { path: '/lti/canvas/config/:campusId', name: 'lti_canvas_config', component: page('lti_canvas_config.vue') },
  { path: '/lti/blackboard/config', name: 'lti_blackboard_config', component: page('lti_blackboard_config.vue') },
  {
    path: '/beta-assignments/redirect-error',
    name: 'beta_assignments_redirect_error',
    component: page('beta_assignments_redirect_error.vue')
  },
  {
    path: '/init-lms-assignment/:assignmentId',
    name: 'init_lms_assignment',
    component: page('init_lms_assignment.vue')
  },
  { path: '/question-in-iframe', name: 'question_in_iframe', component: page('iframe_test.vue') },
  {
    path: '/assignments/:assignmentId/questions/view/:questionId?/:shownSections?',
    name: 'questions.view',
    component: page('questions.view.vue')
  },
  { path: '/submission', name: 'submission.index', component: page('submission.store.vue') },
  { path: '/', name: 'welcome', component: page('welcome.vue') },
  { path: '/login', name: 'login', component: page('auth/login.vue') },
  {
    path: '/finish-sso-registration',
    name: 'finish.sso.registration',
    component: page('auth/finish.sso.registration.vue')
  },
  {
    path: '/register/instructor/:accessCode?',
    name: 'register',
    component: page('auth/register.vue'),
    alias: ['/register/student', '/register/grader']
  },
  {
    path: '/register/question-editor/:accessCode?',
    name: 'question-editor-register',
    component: page('auth/register.vue')
  },
  { path: '/password/reset', name: 'password.request', component: page('auth/password/email.vue') },
  { path: '/password/reset/:token', name: 'password.reset', component: page('auth/password/reset.vue') },
  { path: '/email/verify/:id', name: 'verification.verify', component: page('auth/verification/verify.vue') },
  { path: '/email/resend', name: 'verification.resend', component: page('auth/verification/resend.vue') },
  { path: '/home', name: 'home', component: page('instructors/courses.index.vue') },
  { path: '/commons', name: 'commons', component: page('commons.vue') },
  {
    path: '/courses/:courseId/anonymous',
    name: 'anonymous-users-entry',
    component: page('anonymous.users.entry.vue')
  },
  {
    path: '/settings',
    component: page('settings/index.vue'),
    children: [
      { path: '', redirect: { name: 'settings.profile' } },
      { path: 'profile', name: 'settings.profile', component: page('settings/profile.vue') },
      { path: 'password', name: 'settings.password', component: page('settings/password.vue') },
      { path: 'notifications', name: 'settings.notifications', component: page('settings/notifications.vue') }
    ]
  },
  {
    path: '/instructors/assignments/:assignmentId/information',
    component: page('instructors/assignment_information/index.vue'),
    children: [
      { path: '', redirect: { name: 'instructors.assignments.questions' } },
      {
        path: 'questions',
        name: 'instructors.assignments.questions',
        component: page('instructors/assignment_information/questions.vue')
      },
      {
        path: 'summary',
        name: 'instructors.assignments.summary',
        component: page('instructors/assignment_information/summary.vue')
      },
      {
        path: 'properties',
        name: 'instructors.assignments.properties',
        component: page('instructors/assignment_information/properties.vue')
      },
      {
        path: 'control_panel',
        name: 'instructors.assignments.control_panel',
        component: page('instructors/assignment_information/control_panel.vue')
      },
      {
        path: 'submission-overrides',
        name: 'instructors.assignments.submission_overrides',
        component: page('instructors/assignment_information/submission_overrides.vue')
      },
      {
        path: 'auto-graded-submissions',
        name: 'instructors.assignments.auto_graded_submissions',
        component: page('instructors/assignment_information/auto_graded_submissions.vue')
      },
      {
        path: 'grader-access',
        name: 'instructors.assignments.grader_access',
        component: page('instructors/assignment_information/grader_access.vue')
      },
      {
        path: 'statistics',
        name: 'instructors.assignments.statistics',
        component: page('instructors/assignment_information/statistics.vue')
      },
      {
        path: 'gradebook',
        name: 'instructors.assignments.gradebook',
        component: page('instructors/assignment_information/gradebook.vue')
      },

    ]
  },
  {
    path: '/instructors/courses/:courseId/properties',
    component: page('instructors/course_properties/index.vue'),
    children: [
      { path: '', redirect: { name: 'course_properties.general_info' } },
      {
        path: 'details',
        name: 'course_properties.general_info',
        component: page('instructors/course_properties/general_info.vue')
      },
      {
        path: 'sections',
        name: 'course_properties.sections',
        component: page('instructors/course_properties/sections.vue')
      },
      {
        path: 'tethered-courses',
        name: 'course_properties.tethered_courses',
        component: page('instructors/course_properties/tethered_courses.vue')
      },
      {
        path: 'letter-grades',
        name: 'course_properties.letter_grades',
        component: page('instructors/course_properties/letter_grades.vue')
      },
      {
        path: 'assignment-group-weights',
        name: 'course_properties.assignment_group_weights',
        component: page('instructors/course_properties/assignment_group_weights.vue')
      },
      {
        path: 'graders',
        name: 'course_properties.graders',
        component: page('instructors/course_properties/graders.vue')
      },
      {
        path: 'ungraded-submissions',
        name: 'course_properties.ungraded_submissions',
        component: page('instructors/course_properties/ungraded_submissions.vue')
      },
      {
        path: 'grader-notifications',
        name: 'course_properties.grader_notifications',
        component: page('instructors/course_properties/grader_notifications.vue')
      },
      {
        path: 'students',
        name: 'course_properties.students',
        component: page('instructors/course_properties/students.vue')
      },
      {
        path: 'a11y',
        name: 'course_properties.a11y',
        component: page('instructors/course_properties/a11y.vue')
      },
      {
        path: 'iframe-properties',
        name: 'course_properties.iframe_properties',
        component: page('instructors/course_properties/iframe_properties.vue')
      },
      {
        path: 'reset',
        name: 'course_properties.reset_course',
        component: page('instructors/course_properties/reset_course.vue')
      }
    ]
  },
  { path: '*', component: page('errors/404.vue') }
]

export default _.concat([{
  path: '/h5p',
  name: 'h5p',
  component: page('h5p.vue')
}], general_paths, student_paths, instructor_paths, control_panel_paths)
