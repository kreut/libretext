export function redirectOnLogin(store, router){
  let userTypes = {2 : 'instructors', 3: 'students'}
  let role = userTypes[store.getters['auth/user'].role]
  router.push({name: `${role}.courses.index`})
}
