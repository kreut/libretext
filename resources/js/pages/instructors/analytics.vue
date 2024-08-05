<template>
  <div>
    <iframe
      id="analytics-iframe"
      v-resize="{ log: false }"
      width="100%"
      src="https://lad.libretexts.org"
      frameborder="0"
      @load="sendUserId"
    />
  </div>
</template>

<script>

import axios from 'axios'

export default {
  name: 'analytics',
  methods: {
    async sendUserId () {
      try {
        const courseId = parseInt(this.$route.params.courseId)
        const { data } = await axios.get(`/api/users/signed-user-id/${courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        const authToken = data.token
        const iframe = document.getElementById('analytics-iframe')
        iframe.contentWindow.postMessage({ type: 'AUTH_TOKEN', token: authToken }, 'https://lad.libretexts.org')
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
