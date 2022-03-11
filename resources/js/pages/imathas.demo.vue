<template>
  <div>
    <iframe
      v-resize="{ log: false, checkOrigin: false }"
      width="100%"
      :src="src"
      frameborder="0"
    />
    <p>Response from imathas: {{ response }}</p>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data: () => ({
    src: '',
    response: {}
  }),
  mounted () {
    this.src = this.getSrc()
    window.addEventListener('message', this.receiveMessage, false)
  },
  beforeDestroy () {
    window.removeEventListener('message', this.receiveMessage)
  },
  methods: {
    async getSrc () {
      try {
        const { data } = await axios.get('/api/imathas')
        if (data.type !== 'success') {
          this.$noty.message(data.message)
          return false
        }
        this.src = data.src
        console.log(data)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async receiveMessage (event) {
      try {
        if (JSON.parse(event.data).subject === 'lti.ext.imathas.result') {
          this.response = JSON.parse(event.data)
        }
      } catch (error) {
        console.log(error)
      }
      try {
        if (JSON.parse(event.data).subject === 'lti.frameResize') {
          let embedWrap = document.getElementById('embed1wrap')
          embedWrap.setAttribute('height', JSON.parse(event.data).wrapheight)
          let iframe = embedWrap.getElementsByTagName('iframe')[0]
          iframe.setAttribute('height', JSON.parse(event.data).height)
        }
      } catch (error) {
        console.log(error)
      }
    }
  }
}
</script>

<style scoped>

</style>
