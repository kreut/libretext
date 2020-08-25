export function redirectOnLogin(store, router){
  let userTypes = {2 : 'instructors', 3: 'students', 4: 'tas'}
  let role = userTypes[store.getters['auth/user'].role]
  alert(role)
  router.push({name: `${role}.courses.index`})
}
