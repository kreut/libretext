<template>
  <div>
    <div>
      <div class="mb-3">
        <iframe
          id="sketcher"
          v-resize="{ log: false }"
          width="100%"
          src="/api/sketcher"
          frameborder="0"
        />
      </div>
      <div class="text-center">
        <b-button
          variant="primary"
          @click="postMessage('sketcher')"
        >
          Submit
        </b-button>
      </div>
    </div>
    <div v-show="false" class="mt-5">
      <div class="mb-3">
        <b-embed
          id="bio"
          type="iframe"
          aspect="16by9"
          src="https://www.youtube.com/embed/td95okNF-Lk?rel=0"
          allowfullscreen
          class="mb-2"
        />
      </div>
      <b-button
        variant="primary"
        @click="postMessage('bio')"
      >
        Submit
      </b-button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NewTechnologiesDemo',
  created () {
    window.addEventListener('message', this.receiveMessage, false)
  },
  destroyed () {
    window.removeEventListener('message', this.receiveMessage)
  },
  methods: {
    receiveMessage (event) {
      if (event.data.submissionResults) {
        console.log(event)
        const type = event.data.correct ? 'success' : 'error'
        const message = type === 'success' ? 'That is correct.' : 'That is incorrect.'
        this.$noty[type](message)
      }
    },
    postMessage (id) {
      let method

      switch (id) {
        case ('sketcher'):
          method = 'checkSketcher'
          origin = 'someOrigin'
          break
        case ('bio'):
          method = 'someMethod'
          origin = 'someOrigin'
          break
        default:
          alert('not a valid id')
          return false
      }
      const iframe = document.getElementById(id)

      iframe.contentWindow.postMessage(method, '*')
    }
  }
}
</script>

<style scoped>

</style>
