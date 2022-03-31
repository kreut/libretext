<template>
  <div>
    <p>This tool needs to launched in a new window.</p>
    <b-button variant="outline-primary" @click="!openedWindow
    ? handleLmsLaunchInNewWindow()
    : showAlreadyOpenedMessage()"
    >
      {{ buttonText }}
    </b-button>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data: () => ({
    buttonText: '',
    openedWindow: false
  }),
  async mounted () {
    localStorage.launchInNewWindow = 1
    localStorage.ltiTokenId = this.$route.params.ltiTokenId
    localStorage.ltiFinalLocation = this.$route.params.ltiFinalLocation
    localStorage.ltiResourceId = this.$route.params.ltiResourceId
    this.buttonText = localStorage.ltiFinalLocation === 'link'
      ? 'Link ADAPT assignment' : 'Open ADAPT assignment'
  },
  methods: {
    showAlreadyOpenedMessage () {
      this.$noty.info('The window may only be opened once.  Please re-open the assignment to launch the tool again.')
    },

    handleLmsLaunchInNewWindow () {
      let route = this.$router.resolve({ name: 'ltiLogin' })
      this.openedWindow = true
      window.open(route.href)
    }
  }
}
</script>

<style scoped>

</style>
