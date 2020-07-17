import _ from 'lodash'

function page (path) {
  return () => import(/* webpackChunkName: '' */ `~/pages/${path}`).then(m => m.default || m)
}

let student_paths  = [

  ]

let instructor_paths = [
  { path: '/courses', name: 'courses.index', component: page('instructors/courses.index.vue') },
  { path: '/courses/:courseId/grades', name: 'grades.index', component: page('instructors/grades.index.vue') },
  { path: '/courses/:courseId/assignments', name: 'assignments.index', component: page('instructors/assignments.index.vue') },
  { path: '/assignments/:assignmentId/questions/get', name: 'questions.index', component: page('instructors/questions.index.vue') },
  { path: '/assignments/:assignmentId/questions/view', name: 'questions.view', component: page('instructors/questions.view.vue') }]

let general_paths  = [
  { path: '/xapi', name: 'xapi.index', component: page('xapi.store.vue') },
  { path: '/', name: 'welcome', component: page('welcome.vue') },
  { path: '/login', name: 'login', component: page('auth/login.vue') },
  { path: '/register/instructor', name: 'register', component: page('auth/register.vue'), alias: '/register/student' },
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
