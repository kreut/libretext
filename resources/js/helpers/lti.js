import axios from 'axios'

export async function getLTIUser () {
  try {
    const { data } = await axios.get('/api/lti/user')
    console.log(data)
    if (data.type === 'success') {
      this.$store.dispatch('auth/saveToken', {
        token: data.token,
        remember: false
      })
      this.$store.dispatch('auth/fetchUser')
      return true
    } else {
      this.errorMessage = data.message
    }
  } catch (error) {
    this.errorMessage = error.message
  }
}
