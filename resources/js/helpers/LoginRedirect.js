let userTypes = { 2: 'instructor', 3: 'student', 4: 'grader' }

export function redirectOnLogin (store, router) {
  let role = userTypes[store.getters['auth/user'].role]
  let name = (role === 'student') ? 'students.courses.index' : 'instructors.courses.index'
  router.push({ name: name })
}

export function redirectOnSSOCompletion (role) {
  // refresh the page to get a new token with the user role
  window.location = (role === 'student') ? '/students/courses' : '/instructors/courses'
}
