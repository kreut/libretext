<template>
  <div>
    <div>
      <div class="mb-3">
        <b-embed
          id="chem"
          type="iframe"
          aspect="16by9"
          src="https://www.youtube.com/embed/td95okNF-Lk?rel=0"
          allowfullscreen
        />
      </div>
      <b-button
        variant="primary"
        @click="postMessage('chem')"
      >
        Submit
      </b-button>
    </div>
    <div class="mt-5">
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
  methods: {
    receiveMessage (event) {
      console.log(event)
    },
    postMessage (id) {
      let method
      let origin

      switch (id) {
        case ('chem'):
          method = 'someMethod'
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

      iframe.contentWindow.postMessage(method, origin)
    }
  }
}
</script>

<style scoped>

</style>
