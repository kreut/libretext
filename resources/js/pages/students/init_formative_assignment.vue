<template>
  <div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  layout: 'blank',
  mounted () {
    this.assignmentId = this.$route.params.assignmentId
    this.logInFormativeStudent()
  },
  methods: {
    async logInFormativeStudent () {
      try {
        const { data } = await axios.get(`/api/user/login-as-formative-student/assignment/${this.assignmentId}`)
        if (data.type === 'success') {
          // Save the token.
          await this.$store.dispatch('auth/saveToken', {
            token: data.token,
            remember: false
          })

          // Fetch the user.
          await this.$store.dispatch('auth/fetchUser')
          // Redirect to the correct home page
          await this.$router.push({ name: 'questions.view', params: { assignmentId: this.assignmentId } })
        } else {
          this.$noty.error(data.message)// no access
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
