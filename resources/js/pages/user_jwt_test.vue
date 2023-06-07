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
        }
        this.userJWT = data.user_jwt
      } catch (error) {
        alert(error.message)
      }
    }
  }
}
</script>
