<template>
  <div/>
</template>

<script>
import axios from 'axios'

export default {
  async mounted () {
    await this.$store.dispatch('auth/logout')
    let token = await this.getLtiToken()
    if (token) {
      await this.$store.dispatch('auth/saveToken', {
        token: token,
        remember: this.remember
      })
    }

    let ltiResourceId = localStorage.ltiResourceId
    let ltiFinalLocation = localStorage.ltiFinalLocation
    if (!ltiResourceId) {
      this.$noty.error('Missing the LTI ID')
      return false
    }

    if (!['init', 'link'].includes(ltiFinalLocation)) {
      this.$noty.error('LTI final location is not valid')
      return false
    }
    // alert( ltiResourceId)
    // alert(ltiFinalLocation)
    if (localStorage.ltiFinalLocation === 'init') {
      await this.$router.push({ name: 'init_lms_assignment', params: { assignmentId: ltiResourceId } })
    }
    if (localStorage.ltiFinalLocation === 'link') {
      await this.$router.push({ name: 'link_assignment_to_lms', params: { lmsResourceLinkId: ltiResourceId } })
    }
  },
  methods: {
    async getLtiToken () {
      try {
        const { data } = await axios.post('/api/lti/get-token-by-lti-token-id',
          { 'lti_token_id': localStorage.ltiTokenId })
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        return data.token
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
