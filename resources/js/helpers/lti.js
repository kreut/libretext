import axios from 'axios'
import Form from 'vform'

export async function getLTIUser () {
  try {
    const { data } = await axios.get('/api/lti/user')
    if (data.type === 'success') {
      await this.$store.dispatch('auth/saveToken', {
        token: data.token,
        remember: false
      })
      await this.$store.dispatch('auth/fetchUser')
      return true
    } else {
      this.$noty.error(data.message)
    }
  } catch (error) {
    this.$noty.error(error.message)
  }
}

export async function validateCampusId (campusId) {
  try {
    const { data } = await axios.get(`/api/lti-registration/is-valid-campus-id/pending/${campusId}`)
    if (data.type === 'error') {
      this.$noty.error(data.message)
    }
    this.isValidCampusId = data.is_valid_campus_id
  } catch (error) {
    this.$noty.error(error.message)
  }
  this.isLoading = false
}

export async function submitDetails () {
  try {
    this.ltiRegistrationForm.errors.clear()
    const { data } = await this.ltiRegistrationForm.post('/api/lti-registration/email-details')
    this.$noty[data.type](data.message)
    if (data.type === 'success') {
      this.ltiRegistrationForm = new Form({})
    }
  } catch (error) {
    if (!error.message.includes('status code 422')) {
      this.$noty.error(error.message)
    }
  }
}

export async function getSchools () {
  try {
    const { data } = await axios.get(`/api/schools`)
    if (data.type === 'error') {
      this.$noty.error(data.message)
      return false
    }
    this.schools = data.schools
  } catch (error) {
    this.$noty.error(error.message)
  }
}
export function selectSchool (selectedSchool) {
  this.ltiRegistrationForm.school = selectedSchool
}

export function searchBySchool (input) {
  if (input.length < 1) {
    return []
  }
  let matches = this.schools.filter(school => school.toLowerCase().includes(input.toLowerCase()))
  let schools = []
  if (matches) {
    for (let i = 0; i < matches.length; i++) {
      schools.push(matches[i])
    }
    schools.sort()
  }
  return schools
}
