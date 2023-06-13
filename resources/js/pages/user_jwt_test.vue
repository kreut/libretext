<template>
  <div>
    User JWT: {{ userJWT }}
  </div>
</template>
<script>
import axios from 'axios'

export default {
  data: () => ({
    userJWT: 'None'
  }),
  mounted () {
    this.getUserJwt()
  },
  methods: {
    async getUserJwt () {
      try {
        const { data } = await axios.get('/api/users/get-cookie-user-jwt')
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.userJWT = data.user_jwt
        // Save the token.
        await this.$store.dispatch('auth/saveToken', {
          token: data.user_jwt,
          remember: false
        })
        console.log('Token: ' + this.userJWT)
        // Fetch the user.
        await this.$store.dispatch('auth/fetchUser')
        // Redirect to the correct home page
        console.log('Decoded route: ' + atob(this.$route.params.url))
        window.location.href = atob(this.$route.params.url)
      } catch (error) {
        alert(error.message)
      }
    }
  }
}
</script>
