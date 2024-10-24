<template>
  <div>
    <b-container>
      <b-row>
        <iframe
          v-resize="{ log: false }"
          width="100%"
          src="/assignments/1409/questions/view/165718"
          frameborder="0"
        />
      </b-row>
    </b-container>
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
      console.error(event)
    }
  }
}
</script>

