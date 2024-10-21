let userTypes = {
  2: 'instructor',
  3: 'student',
  4: 'grader',
  5: 'question editor',
  6: 'tester',
  0: 'did not complete registration'
}

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
    case ('tester'):
      name = 'testers.courses.index'
      break
    case ('instructor'):
    case ('grader'):
      name = 'instructors.courses.index'
      break
    case ('question editor'):
      name = 'question.editor'
      break
    case ('did not complete registration'):
      name = 'incomplete.registration'
      break
    default:
      alert('There was an error logging you in since you do not have one of the specified roles.  Please try again or contact us for assistance.')
      return false
  }
  router.push({ name: name }).then(() => {
    window.location.reload()
  })
}

export function redirectOnSSOCompletion (role) {
  // refresh the page to get a new token with the user role
  window.location = (role === 'student') ? '/students/courses' : '/instructors/courses'
}
