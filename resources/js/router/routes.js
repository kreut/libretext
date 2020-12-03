import _ from 'lodash'

function page (path) {
  return () => import(/* webpackChunkName: '' */ `~/pages/${path}`).then(m => m.default || m)
}

let student_paths  = [
  { path: '/students/courses', name: 'students.courses.index', component: page('students/courses.index.vue')},
  { path: '/students/courses/:courseId/assignments', name: 'students.assignments.index', component: page('students/assignments.index.vue') },
]

let instructor_paths = [
  { path: '/instructors/learning-trees/editor/:learningTreeId', name: 'instructors.learning_trees.editor', component: page('instructors/learning_trees.editor.vue') },
  { path: '/instructors/learning-trees', name: 'instructors.learning_trees.index', component: page('instructors/learning_trees.index.vue') },
  { path: '/assignments/:assignmentId/questions/:questionId/view', name: 'question.view', component: page('instructors/question.view.vue') },
  { path: '/assignments/:assignmentId/questions/get', name: 'questions.get', component: page('instructors/questions.get.vue') },
  { path: '/assignments/:assignmentId/:typeFiles/:questionId?/:studentUserId?', name: 'assignment.files.index', component: page('instructors/assignments.files.vue') },
  { path: '/instructors/assignment/:assignmentId/remediations/:questionId', name: 'remediation.index', component: page('instructors/remediations.vue') },
  { path: '/instructors/courses', name: 'instructors.courses.index', component: page('instructors/courses.index.vue') },
  { path: '/courses/:courseId/scores', name: 'scores.index', component: page('instructors/scores.index.vue') },
  { path: '/instructors/courses/:courseId/assignments', name: 'instructors.assignments.index', component: page('instructors/assignments.index.vue') }
]

let general_paths  = [
  { path: '/assignments/:assignmentId/summary', name: 'assignments.summary', component: page('assignments.summary.vue') },
  { path: '/assignments/:assignmentId/questions/view', name: 'questions.view', component: page('questions.view.vue') },
  { path: '/submission', name: 'submission.index', component: page('submission.store.vue') },
  { path: '/', name: 'welcome', component: page('welcome.vue') },
  { path: '/login', name: 'login', component: page('auth/login.vue') },
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
      { path: 'password', name: 'settings.password', component: page('settings/password.vue') }
    ] },
  { path: '*', component: page('errors/404.vue') }
]

export default _.concat( [{ path: '/h5p', name: 'h5p', component: page('h5p.vue') }], general_paths, student_paths, instructor_paths)
