let userTypes = { 2: 'instructor', 3: 'student', 4: 'grader', 5: 'question editor' }

export function redirectOnLogin (store, router, landingPage = '') {
  if (landingPage) {
    window.location = landingPage
    return false
  }
  let role = userTypes[store.getters['auth/user'].role]
  let name
  switch (role) {
    case ('student'):
      name = 'students.courses.index'
      break
    case ('instructor'):
    case ('grader'):
      name = 'instructors.courses.index'
      break
    case ('question editor'):
      name = 'question.editor'
      break
  }
  router.push({ name: name })
}

export function redirectOnSSOCompletion (role) {
  // refresh the page to get a new token with the user role
  window.location = (role === 'student') ? '/students/courses' : '/instructors/courses'
}
