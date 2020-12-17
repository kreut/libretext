export function redirectOnLogin (store, router) {
  let userTypes = { 2: 'instructors', 3: 'students', 4: 'tas' }
  let role = userTypes[store.getters['auth/user'].role]
  let name = (role === 'students') ? 'students.courses.index' : 'instructors.courses.index'
  router.push({ name: name })
}
