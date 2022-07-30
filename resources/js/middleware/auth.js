import store from '~/store'
import { toggleInstructorStudentViewRouteNames } from '~/helpers/StudentInstructorViewToggles'

export default async (to, from, next) => {
  if (!store.getters['auth/check']) {
    next({ name: 'login' })
  } else {
    if (store.getters['auth/user'].fake_student &&
      !toggleInstructorStudentViewRouteNames.includes(to.name)) {
      next({ name: 'no.access' })
    }
    next()
  }
}
