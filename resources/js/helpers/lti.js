import axios from 'axios'

export async function getLTIUser () {
  try {
    const { data } = await axios.get('/api/lti/user')
    if (data.type === 'success') {
      this.$store.dispatch('auth/saveToken', {
        token: data.token,
        remember: false
      })
      this.$store.dispatch('auth/fetchUser')
      return true
    } else {
      this.$noty.error(data.message)
    }
  } catch (error) {
    this.$noty.error(error.message)
  }
}
