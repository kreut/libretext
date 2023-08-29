<template>
  <b-button size="sm" variant="primary" @click="initGrantLMSAccess">
    Grant Access
  </b-button>
</template>

<script>
import axios from 'axios'

export default {
  name: 'GrantLmsApiAccess',
  props: {
    courseId: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    clientId: '',
    oauthUrl: ''
  }),
  mounted () {
    this.getOauthUrl()
  },
  methods: {
    initGrantLMSAccess () {
      location.href = this.oauthUrl
    },
    async getOauthUrl () {
      try {
        const { data } = await axios.get(`/api/lms-api/oauth-url/${this.courseId}`)
        console.log(data)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.oauthUrl = data.oauth_url
      } catch (error) {
        this.$noty.error(error.message)
      }

    }
  }
}
</script>

